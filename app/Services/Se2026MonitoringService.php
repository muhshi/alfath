<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Se2026MonitoringService
{
    /**
     * Master Kecamatan Map (Demak BPS Codes)
     */
    protected array $kecNameMap = [
        '3321010' => 'Mranggen',
        '3321020' => 'Karangawen',
        '3321030' => 'Guntur',
        '3321040' => 'Sayung',
        '3321050' => 'Karangtengah',
        '3321060' => 'Bonang',
        '3321070' => 'Demak',
        '3321080' => 'Wonosalam',
        '3321090' => 'Dempet',
        '3321091' => 'Kebonagung',
        '3321100' => 'Gajah',
        '3321110' => 'Karanganyar',
        '3321120' => 'Mijen',
        '3321130' => 'Wedung',
    ];

    public function getKecNameMap(): array
    {
        return $this->kecNameMap;
    }

    /**
     * Build the filtered query for SE2026 Data Petugas (PPL).
     */
    public function getFilteredQuery(Request $request): array
    {
        $connName = config()->has('database.connections.fasih') ? 'fasih' : null;
        $db = $connName ? DB::connection($connName) : DB::connection();

        $availableDates = [];
        $selectedDate = $request->get('tanggal_data');

        if (Schema::connection($connName)->hasTable('monitoring_se2026')) {
            $availableDates = $db->table('monitoring_se2026')
                ->whereNotNull('tanggal_tarik')
                ->distinct()
                ->orderBy('tanggal_tarik', 'desc')
                ->pluck('tanggal_tarik')
                ->toArray();

            if (empty($selectedDate) && !empty($availableDates)) {
                $selectedDate = $availableDates[0];
            }
        }

        $search = trim((string) $request->get('search', ''));
        $kodekec = trim((string) $request->get('kodekec', ''));
        $sortBy = $request->get('sort', 'kode_kec');
        $sortDir = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100])) {
            $perPage = 15;
        }

        $upDate = $this->getTargetDateForTable('se2026_usaha_perusahaan', $selectedDate);
        $ukDate = $this->getTargetDateForTable('se2026_usaha_keluarga', $selectedDate);
        $pkDate = $this->getTargetDateForTable('se2026_pemutakhiran_keluarga', $selectedDate);

        // Subquery for SIPW (Wilkerstat 2025)
        $sipwSubquery = $db->table('sipw')
            ->select(
                'id_subsls',
                DB::raw('MAX(nama_sls) as nama_sls'),
                DB::raw('MAX(CAST(muatan_kk AS SIGNED)) as wilkerstat_kk'),
                DB::raw('MAX(CAST(bku AS SIGNED)) as wilkerstat_bku'),
                DB::raw('MAX(CAST(muatan_usaha AS SIGNED)) as wilkerstat_usaha')
            )
            ->groupBy('id_subsls');

        // Subquery for Usaha Perusahaan
        $upSubquery = $db->table('se2026_usaha_perusahaan')
            ->when($upDate, fn ($q) => $q->where('tanggal_data', $upDate))
            ->select(
                'kode',
                DB::raw('SUM(CAST(status___ditemukan AS SIGNED) + CAST(status___baru AS SIGNED)) AS up_ditemukan'),
                DB::raw('SUM(CAST(status___tutup AS SIGNED) + CAST(status___ganda AS SIGNED) + CAST(status___tidak_ditemukan AS SIGNED)) AS up_tdk')
            )
            ->groupBy('kode');

        // Subquery for Usaha Keluarga
        $ukSubquery = $db->table('se2026_usaha_keluarga')
            ->when($ukDate, fn ($q) => $q->where('tanggal_data', $ukDate))
            ->select(
                'kode',
                DB::raw('SUM(CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka AS SIGNED) + CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___baru AS SIGNED)) AS uk_ditemukan'),
                DB::raw('SUM(CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tutup AS SIGNED) + CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ganda AS SIGNED) + CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tidak_di AS SIGNED)) AS uk_tdk')
            )
            ->groupBy('kode');

        // Subquery for Pemutakhiran Keluarga
        $pkSubquery = $db->table('se2026_pemutakhiran_keluarga')
            ->when($pkDate, fn ($q) => $q->where('tanggal_data', $pkDate))
            ->select(
                'kode',
                DB::raw('SUM(ditemukan + keluarga_baru) AS pk_ditemukan'),
                DB::raw('SUM(meninggal + tidak_eligible + tidak_dapat_ditemui + tidak_ditemukan) AS pk_tdk')
            )
            ->groupBy('kode');

        // Base Query
        $query = $db->table('monitoring_se2026 as m')
            ->leftJoin('alokasi_pengawas as a', 'm.region_code', '=', 'a.region_code')
            ->leftJoin('master_petugas as p_cacah', 'm.email_pencacah', '=', 'p_cacah.email')
            ->leftJoin('master_petugas as p_awas', 'a.email_pengawas', '=', 'p_awas.email')
            ->leftJoinSub($sipwSubquery, 'sipw', 'm.region_code', '=', 'sipw.id_subsls')
            ->leftJoinSub($upSubquery, 'up', 'm.region_code', '=', 'up.kode')
            ->leftJoinSub($ukSubquery, 'uk', 'm.region_code', '=', 'uk.kode')
            ->leftJoinSub($pkSubquery, 'pk', 'm.region_code', '=', 'pk.kode')
            ->select([
                'm.tanggal_tarik as tanggal_data',
                DB::raw('LEFT(m.region_code, 7) as kode_kec'),
                'm.email_pencacah',
                DB::raw('IFNULL(p_cacah.nama_lengkap, m.email_pencacah) as nama_pencacah'),
                DB::raw('GROUP_CONCAT(DISTINCT p_awas.nama_lengkap SEPARATOR ", ") as nama_pengawas'),
                DB::raw('SUM(m.total_beban) as beban_saat_ini'),
                DB::raw('(IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) as total_submit'),
                DB::raw('(IFNULL(SUM(m.status_open), 0) + IFNULL(SUM(m.status_draft), 0)) as belum_dikerjakan'),
                DB::raw('IFNULL(SUM(m.status_draft), 0) as total_draft'),
                DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END as pct_submit'),
                DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND((IFNULL(SUM(m.status_draft), 0) / SUM(m.total_beban)) * 100, 2) ELSE 0 END as pct_draft'),
                DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END as pct_submit_draft'),
                DB::raw('IFNULL(SUM(up.up_ditemukan), 0) as jumlah_usaha_ditemukan'),
                DB::raw('IFNULL(SUM(up.up_tdk), 0) as usaha_tidak_ditemukan'),
                DB::raw('IFNULL(SUM(uk.uk_ditemukan), 0) as jumlah_usaha_keluarga'),
                DB::raw('IFNULL(SUM(pk.pk_ditemukan), 0) as jumlah_keluarga_ditemukan'),
                DB::raw('IFNULL(SUM(pk.pk_tdk), 0) as keluarga_tidak_ditemukan'),
                DB::raw('(IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) as muatan_murni'),
                DB::raw('(IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(uk.uk_ditemukan), 0)) as total_usaha_se'),
                DB::raw('IFNULL(SUM(sipw.wilkerstat_kk), 0) as wilkerstat_kk'),
                DB::raw('IFNULL(SUM(sipw.wilkerstat_bku), 0) as wilkerstat_bku'),
                DB::raw('IFNULL(SUM(sipw.wilkerstat_usaha), 0) as wilkerstat_usaha'),
                DB::raw('CASE WHEN IFNULL(SUM(sipw.wilkerstat_kk), 0) > 0 THEN ROUND(((IFNULL(SUM(pk.pk_ditemukan), 0) - IFNULL(SUM(sipw.wilkerstat_kk), 0)) / IFNULL(SUM(sipw.wilkerstat_kk), 0)) * 100, 1) ELSE 0 END as pct_diff_kk'),
                DB::raw('CASE WHEN IFNULL(SUM(sipw.wilkerstat_kk), 0) > 0 AND (((IFNULL(SUM(pk.pk_ditemukan), 0) - IFNULL(SUM(sipw.wilkerstat_kk), 0)) / IFNULL(SUM(sipw.wilkerstat_kk), 0)) * 100) < -5.0 THEN 1 ELSE 0 END as has_warning_diff_kk'),
                DB::raw('CASE WHEN IFNULL(SUM(sipw.wilkerstat_usaha), 0) > 0 THEN ROUND((((IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(uk.uk_ditemukan), 0)) - IFNULL(SUM(sipw.wilkerstat_usaha), 0)) / IFNULL(SUM(sipw.wilkerstat_usaha), 0)) * 100, 1) ELSE 0 END as pct_diff_usaha'),
                DB::raw('CASE WHEN IFNULL(SUM(sipw.wilkerstat_usaha), 0) > 0 AND ((((IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(uk.uk_ditemukan), 0)) - IFNULL(SUM(sipw.wilkerstat_usaha), 0)) / IFNULL(SUM(sipw.wilkerstat_usaha), 0)) * 100) < -5.0 THEN 1 ELSE 0 END as has_warning_diff_usaha'),
                DB::raw('GREATEST(0, (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) - (IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) - (IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(pk.pk_tdk), 0))) as bangunan_lainnya'),
                DB::raw('CASE WHEN (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) > 0 THEN ROUND((GREATEST(0, (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) - (IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) - (IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(pk.pk_tdk), 0))) / (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0))) * 100, 2) ELSE 0 END as pct_bangunan_lainnya'),
                DB::raw('CASE WHEN (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) > 0 AND ((GREATEST(0, (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) - (IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) - (IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(pk.pk_tdk), 0))) / (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0))) * 100) >= 5.0 THEN 1 ELSE 0 END as has_warning_bangunan_lainnya'),
            ])
            ->groupBy([
                'm.tanggal_tarik',
                DB::raw('LEFT(m.region_code, 7)'),
                'm.email_pencacah',
                'p_cacah.nama_lengkap',
            ]);

        if (!empty($selectedDate)) {
            $query->where('m.tanggal_tarik', '=', $selectedDate);
        }

        if (!empty($kodekec)) {
            $query->where('m.region_code', 'LIKE', $kodekec . '%');
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('p_cacah.nama_lengkap', 'LIKE', "%{$search}%")
                  ->orWhere('m.email_pencacah', 'LIKE', "%{$search}%")
                  ->orWhere('p_awas.nama_lengkap', 'LIKE', "%{$search}%");
            });
        }

        $allowedSorts = [
            'kode_kec' => DB::raw('LEFT(m.region_code, 7)'),
            'nama_pencacah' => DB::raw('IFNULL(p_cacah.nama_lengkap, m.email_pencacah)'),
            'nama_pengawas' => DB::raw('GROUP_CONCAT(DISTINCT p_awas.nama_lengkap SEPARATOR ", ")'),
            'beban_saat_ini' => DB::raw('SUM(m.total_beban)'),
            'total_submit' => DB::raw('(IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0))'),
            'belum_dikerjakan' => DB::raw('(IFNULL(SUM(m.status_open), 0) + IFNULL(SUM(m.status_draft), 0))'),
            'pct_submit' => DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END'),
            'jumlah_usaha_ditemukan' => DB::raw('IFNULL(SUM(up.up_ditemukan), 0)'),
            'jumlah_usaha_keluarga' => DB::raw('IFNULL(SUM(uk.uk_ditemukan), 0)'),
            'jumlah_keluarga_ditemukan' => DB::raw('IFNULL(SUM(pk.pk_ditemukan), 0)'),
            'muatan_murni' => DB::raw('(IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0))'),
        ];

        $sortColumn = $allowedSorts[$sortBy] ?? DB::raw('LEFT(m.region_code, 7)');
        $query->orderBy($sortColumn, $sortDir);

        return [
            'query' => $query,
            'selectedDate' => $selectedDate,
            'availableDates' => $availableDates,
            'search' => $search,
            'kodekec' => $kodekec,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'perPage' => $perPage,
        ];
    }

    /**
     * Build query for Alokasi & Progress Per SLS / Sub-SLS.
     */
    public function getSlsQuery(Request $request, $selectedDate)
    {
        $connName = config()->has('database.connections.fasih') ? 'fasih' : null;
        $db = $connName ? DB::connection($connName) : DB::connection();

        $search = trim((string) $request->get('search', ''));
        $kodekec = trim((string) $request->get('kodekec', ''));

        $upDate = $this->getTargetDateForTable('se2026_usaha_perusahaan', $selectedDate);
        $ukDate = $this->getTargetDateForTable('se2026_usaha_keluarga', $selectedDate);
        $pkDate = $this->getTargetDateForTable('se2026_pemutakhiran_keluarga', $selectedDate);

        $sipwSub = $db->table('sipw')
            ->select(
                'id_subsls',
                DB::raw('MAX(nama_sls) as nama_sls'),
                DB::raw('MAX(CAST(muatan_kk AS SIGNED)) as wilkerstat_kk'),
                DB::raw('MAX(CAST(bku AS SIGNED)) as wilkerstat_bku'),
                DB::raw('MAX(CAST(muatan_usaha AS SIGNED)) as wilkerstat_usaha')
            )
            ->groupBy('id_subsls');

        $pkSub = $db->table('se2026_pemutakhiran_keluarga')
            ->when($pkDate, fn ($q) => $q->where('tanggal_data', $pkDate))
            ->select(
                'kode',
                DB::raw('MAX(sub_sls) as sub_sls'),
                DB::raw('SUM(ditemukan + keluarga_baru) AS pk_ditemukan'),
                DB::raw('SUM(meninggal + tidak_eligible + tidak_dapat_ditemui + tidak_ditemukan) AS pk_tdk')
            )
            ->groupBy('kode');

        $upSub = $db->table('se2026_usaha_perusahaan')
            ->when($upDate, fn ($q) => $q->where('tanggal_data', $upDate))
            ->select(
                'kode',
                DB::raw('SUM(CAST(status___ditemukan AS SIGNED) + CAST(status___baru AS SIGNED)) AS up_ditemukan'),
                DB::raw('SUM(CAST(status___tutup AS SIGNED) + CAST(status___ganda AS SIGNED) + CAST(status___tidak_ditemukan AS SIGNED)) AS up_tdk')
            )
            ->groupBy('kode');

        $ukSub = $db->table('se2026_usaha_keluarga')
            ->when($ukDate, fn ($q) => $q->where('tanggal_data', $ukDate))
            ->select(
                'kode',
                DB::raw('SUM(CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka AS SIGNED) + CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___baru AS SIGNED)) AS uk_ditemukan'),
                DB::raw('SUM(CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tutup AS SIGNED) + CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ganda AS SIGNED) + CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tidak_di AS SIGNED)) AS uk_tdk')
            )
            ->groupBy('kode');

        $query = $db->table('monitoring_se2026 as m')
            ->leftJoin('alokasi_pengawas as a', 'm.region_code', '=', 'a.region_code')
            ->leftJoin('master_petugas as p_cacah', 'm.email_pencacah', '=', 'p_cacah.email')
            ->leftJoin('master_petugas as p_awas', 'a.email_pengawas', '=', 'p_awas.email')
            ->leftJoin('monitoring_sls_se2026 as sls', 'm.region_code', '=', 'sls.level_6_full_code')
            ->leftJoinSub($sipwSub, 'sipw', 'm.region_code', '=', 'sipw.id_subsls')
            ->leftJoinSub($pkSub, 'pk', 'm.region_code', '=', 'pk.kode')
            ->leftJoinSub($upSub, 'up', 'm.region_code', '=', 'up.kode')
            ->leftJoinSub($ukSub, 'uk', 'm.region_code', '=', 'uk.kode')
            ->select([
                'm.tanggal_tarik as tanggal_data',
                'm.region_code',
                DB::raw('LEFT(m.region_code, 7) as kode_kec'),
                DB::raw('COALESCE(
                    NULLIF(sls.nmsls, "-"),
                    NULLIF(sipw.nama_sls, "-"),
                    NULLIF(pk.sub_sls, "-"),
                    NULLIF(pk.sub_sls, "TIDAK DIKETAHUI"),
                    CONCAT("SLS ", m.region_code)
                ) as nama_sls'),
                'm.email_pencacah',
                DB::raw('IFNULL(p_cacah.nama_lengkap, m.email_pencacah) as nama_pencacah'),
                DB::raw('GROUP_CONCAT(DISTINCT p_awas.nama_lengkap SEPARATOR ", ") as nama_pengawas'),
                DB::raw('SUM(m.total_beban) as beban_saat_ini'),
                DB::raw('(IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) as total_submit'),
                DB::raw('IFNULL(SUM(m.status_open), 0) as status_open'),
                DB::raw('IFNULL(SUM(m.status_draft), 0) as status_draft'),
                DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END as pct_submit'),
                DB::raw('IFNULL(SUM(up.up_ditemukan), 0) as up_ditemukan'),
                DB::raw('IFNULL(SUM(up.up_tdk), 0) as up_tdk'),
                DB::raw('IFNULL(SUM(uk.uk_ditemukan), 0) as uk_ditemukan'),
                DB::raw('IFNULL(SUM(uk.uk_tdk), 0) as uk_tdk'),
                DB::raw('IFNULL(SUM(pk.pk_ditemukan), 0) as pk_ditemukan'),
                DB::raw('IFNULL(SUM(pk.pk_tdk), 0) as pk_tdk'),
                DB::raw('(IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(uk.uk_ditemukan), 0)) as total_usaha_se'),
                DB::raw('IFNULL(sipw.wilkerstat_kk, 0) as wilkerstat_kk'),
                DB::raw('IFNULL(sipw.wilkerstat_bku, 0) as wilkerstat_bku'),
                DB::raw('IFNULL(sipw.wilkerstat_usaha, 0) as wilkerstat_usaha'),
                DB::raw('CASE WHEN IFNULL(sipw.wilkerstat_kk, 0) > 0 THEN ROUND(((IFNULL(SUM(pk.pk_ditemukan), 0) - IFNULL(sipw.wilkerstat_kk, 0)) / IFNULL(sipw.wilkerstat_kk, 0)) * 100, 1) ELSE 0 END as pct_diff_kk'),
                DB::raw('CASE WHEN IFNULL(sipw.wilkerstat_kk, 0) > 0 AND (((IFNULL(SUM(pk.pk_ditemukan), 0) - IFNULL(sipw.wilkerstat_kk, 0)) / IFNULL(sipw.wilkerstat_kk, 0)) * 100) < -5.0 THEN 1 ELSE 0 END as has_warning_diff_kk'),
                DB::raw('CASE WHEN IFNULL(sipw.wilkerstat_usaha, 0) > 0 THEN ROUND((((IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(uk.uk_ditemukan), 0)) - IFNULL(sipw.wilkerstat_usaha, 0)) / IFNULL(sipw.wilkerstat_usaha, 0)) * 100, 1) ELSE 0 END as pct_diff_usaha'),
                DB::raw('CASE WHEN IFNULL(sipw.wilkerstat_usaha, 0) > 0 AND ((((IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(uk.uk_ditemukan), 0)) - IFNULL(sipw.wilkerstat_usaha, 0)) / IFNULL(sipw.wilkerstat_usaha, 0)) * 100) < -5.0 THEN 1 ELSE 0 END as has_warning_diff_usaha'),
                DB::raw('(IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(uk.uk_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) as total_ditemukan'),
                DB::raw('(IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(uk.uk_tdk), 0) + IFNULL(SUM(pk.pk_tdk), 0)) as total_tdk'),
                DB::raw('GREATEST(0, (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) - (IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) - (IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(pk.pk_tdk), 0))) as bangunan_lainnya'),
                DB::raw('CASE WHEN (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) > 0 THEN ROUND((GREATEST(0, (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) - (IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) - (IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(pk.pk_tdk), 0))) / (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0))) * 100, 2) ELSE 0 END as pct_bangunan_lainnya'),
                DB::raw('CASE WHEN (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) > 0 AND ((GREATEST(0, (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) - (IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) - (IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(pk.pk_tdk), 0))) / (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0))) * 100) >= 5.0 THEN 1 ELSE 0 END as has_warning_bangunan_lainnya'),
            ])
            ->groupBy([
                'm.tanggal_tarik',
                'm.region_code',
                DB::raw('LEFT(m.region_code, 7)'),
                'sls.nmsls',
                'sipw.nama_sls',
                'sipw.wilkerstat_kk',
                'sipw.wilkerstat_bku',
                'sipw.wilkerstat_usaha',
                'pk.sub_sls',
                'm.email_pencacah',
                'p_cacah.nama_lengkap',
            ])
            ->orderBy('m.region_code', 'asc');

        if (!empty($selectedDate)) {
            $query->where('m.tanggal_tarik', '=', $selectedDate);
        }

        if (!empty($kodekec)) {
            $query->where('m.region_code', 'LIKE', $kodekec . '%');
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('p_cacah.nama_lengkap', 'LIKE', "%{$search}%")
                  ->orWhere('m.email_pencacah', 'LIKE', "%{$search}%")
                  ->orWhere('p_awas.nama_lengkap', 'LIKE', "%{$search}%")
                  ->orWhere('sls.nmsls', 'LIKE', "%{$search}%")
                  ->orWhere('m.region_code', 'LIKE', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * Build query for Agregasi Per PML (Pengawas).
     */
    public function getPmlQuery(Request $request, $selectedDate)
    {
        $connName = config()->has('database.connections.fasih') ? 'fasih' : null;
        $db = $connName ? DB::connection($connName) : DB::connection();

        $search = trim((string) $request->get('search', ''));
        $kodekec = trim((string) $request->get('kodekec', ''));

        $upDate = $this->getTargetDateForTable('se2026_usaha_perusahaan', $selectedDate);
        $ukDate = $this->getTargetDateForTable('se2026_usaha_keluarga', $selectedDate);
        $pkDate = $this->getTargetDateForTable('se2026_pemutakhiran_keluarga', $selectedDate);

        $sipwSubquery = $db->table('sipw')
            ->select(
                'id_subsls',
                DB::raw('MAX(nama_sls) as nama_sls'),
                DB::raw('MAX(CAST(muatan_kk AS SIGNED)) as wilkerstat_kk'),
                DB::raw('MAX(CAST(bku AS SIGNED)) as wilkerstat_bku'),
                DB::raw('MAX(CAST(muatan_usaha AS SIGNED)) as wilkerstat_usaha')
            )
            ->groupBy('id_subsls');

        $upSubquery = $db->table('se2026_usaha_perusahaan')
            ->when($upDate, fn ($q) => $q->where('tanggal_data', $upDate))
            ->select(
                'kode',
                DB::raw('SUM(CAST(status___ditemukan AS SIGNED) + CAST(status___baru AS SIGNED)) AS up_ditemukan'),
                DB::raw('SUM(CAST(status___tutup AS SIGNED) + CAST(status___ganda AS SIGNED) + CAST(status___tidak_ditemukan AS SIGNED)) AS up_tdk')
            )
            ->groupBy('kode');

        $ukSubquery = $db->table('se2026_usaha_keluarga')
            ->when($ukDate, fn ($q) => $q->where('tanggal_data', $ukDate))
            ->select(
                'kode',
                DB::raw('SUM(CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka AS SIGNED)) AS uk_ditemukan')
            )
            ->groupBy('kode');

        $pkSubquery = $db->table('se2026_pemutakhiran_keluarga')
            ->when($pkDate, fn ($q) => $q->where('tanggal_data', $pkDate))
            ->select(
                'kode',
                DB::raw('SUM(ditemukan + keluarga_baru) AS pk_ditemukan'),
                DB::raw('SUM(meninggal + tidak_eligible + tidak_dapat_ditemui + tidak_ditemukan) AS pk_tdk')
            )
            ->groupBy('kode');

        $query = $db->table('monitoring_se2026 as m')
            ->leftJoin('alokasi_pengawas as a', 'm.region_code', '=', 'a.region_code')
            ->leftJoin('master_petugas as p_cacah', 'm.email_pencacah', '=', 'p_cacah.email')
            ->leftJoin('master_petugas as p_awas', 'a.email_pengawas', '=', 'p_awas.email')
            ->leftJoinSub($sipwSubquery, 'sipw', 'm.region_code', '=', 'sipw.id_subsls')
            ->leftJoinSub($upSubquery, 'up', 'm.region_code', '=', 'up.kode')
            ->leftJoinSub($ukSubquery, 'uk', 'm.region_code', '=', 'uk.kode')
            ->leftJoinSub($pkSubquery, 'pk', 'm.region_code', '=', 'pk.kode')
            ->select([
                'm.tanggal_tarik as tanggal_data',
                DB::raw('LEFT(m.region_code, 7) as kode_kec'),
                'a.email_pengawas',
                DB::raw('IFNULL(p_awas.nama_lengkap, a.email_pengawas) as nama_pengawas'),
                DB::raw('COUNT(DISTINCT m.email_pencacah) as total_ppl'),
                DB::raw('COUNT(DISTINCT m.region_code) as total_sls'),
                DB::raw('SUM(m.total_beban) as beban_saat_ini'),
                DB::raw('(IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) as total_submit'),
                DB::raw('(IFNULL(SUM(m.status_open), 0) + IFNULL(SUM(m.status_draft), 0)) as belum_dikerjakan'),
                DB::raw('IFNULL(SUM(m.status_draft), 0) as total_draft'),
                DB::raw('IFNULL(SUM(m.status_submitted), 0) as total_submitted_ppl'),
                DB::raw('IFNULL(SUM(m.status_approved), 0) as total_approved_pml'),
                DB::raw('IFNULL(SUM(m.status_rejected), 0) as total_rejected_pml'),
                DB::raw('(IFNULL(SUM(m.status_approved), 0) + IFNULL(SUM(m.status_rejected), 0)) as total_dikerjakan_pml'),
                DB::raw('CASE WHEN (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) > 0 THEN ROUND(((IFNULL(SUM(m.status_approved), 0) + IFNULL(SUM(m.status_rejected), 0)) / (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0))) * 100, 2) ELSE 0 END as pct_pengerjaan_pml'),
                DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END as pct_submit'),
                DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END as pct_submit_draft'),
                DB::raw('IFNULL(SUM(up.up_ditemukan), 0) as jumlah_usaha_ditemukan'),
                DB::raw('IFNULL(SUM(up.up_tdk), 0) as usaha_tidak_ditemukan'),
                DB::raw('IFNULL(SUM(uk.uk_ditemukan), 0) as jumlah_usaha_keluarga'),
                DB::raw('IFNULL(SUM(pk.pk_ditemukan), 0) as jumlah_keluarga_ditemukan'),
                DB::raw('IFNULL(SUM(pk.pk_tdk), 0) as keluarga_tidak_ditemukan'),
                DB::raw('(IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) as muatan_murni'),
                DB::raw('(IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(uk.uk_ditemukan), 0)) as total_usaha_se'),
                DB::raw('IFNULL(SUM(sipw.wilkerstat_kk), 0) as wilkerstat_kk'),
                DB::raw('IFNULL(SUM(sipw.wilkerstat_bku), 0) as wilkerstat_bku'),
                DB::raw('IFNULL(SUM(sipw.wilkerstat_usaha), 0) as wilkerstat_usaha'),
                DB::raw('CASE WHEN IFNULL(SUM(sipw.wilkerstat_kk), 0) > 0 THEN ROUND(((IFNULL(SUM(pk.pk_ditemukan), 0) - IFNULL(SUM(sipw.wilkerstat_kk), 0)) / IFNULL(SUM(sipw.wilkerstat_kk), 0)) * 100, 1) ELSE 0 END as pct_diff_kk'),
                DB::raw('CASE WHEN IFNULL(SUM(sipw.wilkerstat_kk), 0) > 0 AND (((IFNULL(SUM(pk.pk_ditemukan), 0) - IFNULL(SUM(sipw.wilkerstat_kk), 0)) / IFNULL(SUM(sipw.wilkerstat_kk), 0)) * 100) < -5.0 THEN 1 ELSE 0 END as has_warning_diff_kk'),
                DB::raw('CASE WHEN IFNULL(SUM(sipw.wilkerstat_usaha), 0) > 0 THEN ROUND((((IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(uk.uk_ditemukan), 0)) - IFNULL(SUM(sipw.wilkerstat_usaha), 0)) / IFNULL(SUM(sipw.wilkerstat_usaha), 0)) * 100, 1) ELSE 0 END as pct_diff_usaha'),
                DB::raw('CASE WHEN IFNULL(SUM(sipw.wilkerstat_usaha), 0) > 0 AND ((((IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(uk.uk_ditemukan), 0)) - IFNULL(SUM(sipw.wilkerstat_usaha), 0)) / IFNULL(SUM(sipw.wilkerstat_usaha), 0)) * 100) < -5.0 THEN 1 ELSE 0 END as has_warning_diff_usaha'),
                DB::raw('GREATEST(0, (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) - (IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) - (IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(pk.pk_tdk), 0))) as bangunan_lainnya'),
                DB::raw('CASE WHEN (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) > 0 THEN ROUND((GREATEST(0, (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) - (IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) - (IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(pk.pk_tdk), 0))) / (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0))) * 100, 2) ELSE 0 END as pct_bangunan_lainnya'),
                DB::raw('CASE WHEN (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) > 0 AND ((GREATEST(0, (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) - (IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) - (IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(pk.pk_tdk), 0))) / (IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0))) * 100) >= 5.0 THEN 1 ELSE 0 END as has_warning_bangunan_lainnya'),
            ])
            ->groupBy([
                'm.tanggal_tarik',
                DB::raw('LEFT(m.region_code, 7)'),
                'a.email_pengawas',
                'p_awas.nama_lengkap',
            ]);

        if (!empty($selectedDate)) {
            $query->where('m.tanggal_tarik', '=', $selectedDate);
        }

        if (!empty($kodekec)) {
            $query->where('m.region_code', 'LIKE', $kodekec . '%');
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('p_awas.nama_lengkap', 'LIKE', "%{$search}%")
                  ->orWhere('a.email_pengawas', 'LIKE', "%{$search}%")
                  ->orWhere('p_cacah.nama_lengkap', 'LIKE', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * Calculate Summary KPI metrics across filtered records.
     */
    public function getKpiSummary($records, $pmlRecords, $slsRecords): array
    {
        return [
            'total_petugas' => $records->count(),
            'total_pml' => $pmlRecords->count(),
            'total_beban' => $records->sum('beban_saat_ini'),
            'total_submit' => $records->sum('total_submit'),
            'total_draft' => $records->sum('total_draft'),
            'total_belum_dikerjakan' => $records->sum('belum_dikerjakan'),
            'total_usaha_ditemukan' => $records->sum('jumlah_usaha_ditemukan'),
            'total_usaha_keluarga' => $records->sum('jumlah_usaha_keluarga'),
            'total_usaha_se' => $records->sum('total_usaha_se'),
            'total_keluarga_ditemukan' => $records->sum('jumlah_keluarga_ditemukan'),
            'total_wilkerstat_kk' => $records->sum('wilkerstat_kk'),
            'total_wilkerstat_usaha' => $records->sum('wilkerstat_usaha'),
            'cnt_warning_diff_kk' => $records->where('has_warning_diff_kk', 1)->count(),
            'total_muatan_murni' => $records->sum('muatan_murni'),
            'total_bangunan_lainnya' => $records->sum('bangunan_lainnya'),
            'cnt_warning_bangunan_lainnya' => $records->where('has_warning_bangunan_lainnya', 1)->count(),
            'total_sls' => $slsRecords->count(),
            'pct_overall_submit' => $records->sum('beban_saat_ini') > 0
                ? round(($records->sum('total_submit') / $records->sum('beban_saat_ini')) * 100, 2)
                : 0,
            'pct_overall_draft' => $records->sum('beban_saat_ini') > 0
                ? round(($records->sum('total_draft') / $records->sum('beban_saat_ini')) * 100, 2)
                : 0,
            'pct_overall_submit_draft' => $records->sum('beban_saat_ini') > 0
                ? round((($records->sum('total_submit') + $records->sum('total_draft')) / $records->sum('beban_saat_ini')) * 100, 2)
                : 0
        ];
    }

    /**
     * Get target tanggal_data for a given table based on selectedDate.
     */
    public function getTargetDateForTable(string $tableName, ?string $selectedDate): ?string
    {
        $connName = config()->has('database.connections.fasih') ? 'fasih' : null;
        $db = DB::connection($connName);

        if (!Schema::connection($connName)->hasTable($tableName)) {
            return null;
        }

        $targetDate = $db->table($tableName)
            ->whereNotNull('tanggal_data')
            ->when(!empty($selectedDate), function ($q) use ($selectedDate) {
                $q->where('tanggal_data', '<=', $selectedDate);
            })
            ->orderBy('tanggal_data', 'desc')
            ->value('tanggal_data');

        if (!$targetDate) {
            $targetDate = $db->table($tableName)->max('tanggal_data');
        }

        return $targetDate;
    }
}
