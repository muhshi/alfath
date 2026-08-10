<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PengolahanDataService
{
    /**
     * Master Kecamatan Map (Demak BPS Codes)
     */
    public array $kecNameMap = [
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

    /**
     * Get the database connection instance (fasih or default).
     */
    public function getDbConnection(): \Illuminate\Database\Connection
    {
        $connName = config()->has('database.connections.fasih') ? 'fasih' : null;
        return $connName ? DB::connection($connName) : DB::connection();
    }

    /**
     * Get the connection name string.
     */
    public function getConnName(): ?string
    {
        return config()->has('database.connections.fasih') ? 'fasih' : null;
    }

    /**
     * Get available dates and resolve selected date from request.
     */
    public function resolveDate(Request $request): array
    {
        $db = $this->getDbConnection();
        $connName = $this->getConnName();

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

        return compact('availableDates', 'selectedDate');
    }

    /**
     * Build the filtered query for SE2026 Data Petugas (Tab 1: PPL).
     */
    public function getFilteredQuery(Request $request): array
    {
        $db = $this->getDbConnection();

        $dateInfo = $this->resolveDate($request);
        $selectedDate = $dateInfo['selectedDate'];
        $availableDates = $dateInfo['availableDates'];

        $search = trim((string) $request->get('search', ''));
        $kodekec = trim((string) $request->get('kodekec', ''));
        $sortBy = $request->get('sort', 'kode_kec');
        $sortDir = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100])) {
            $perPage = 15;
        }

        // Subquery for Usaha Perusahaan
        $upSubquery = $db->table('se2026_usaha_perusahaan')
            ->select(
                'kode',
                DB::raw('SUM(CAST(status___ditemukan AS SIGNED) + CAST(status___baru AS SIGNED)) AS up_ditemukan'),
                DB::raw('SUM(CAST(status___tutup AS SIGNED) + CAST(status___ganda AS SIGNED) + CAST(status___tidak_ditemukan AS SIGNED)) AS up_tdk')
            )
            ->groupBy('kode');

        // Subquery for Usaha Keluarga (Information Only)
        $ukSubquery = $db->table('se2026_usaha_keluarga')
            ->select(
                'kode',
                DB::raw('SUM(CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka AS SIGNED)) AS uk_ditemukan')
            )
            ->groupBy('kode');

        // Subquery for Pemutakhiran Keluarga
        $pkSubquery = $db->table('se2026_pemutakhiran_keluarga')
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
                DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END as pct_submit'),
                DB::raw('IFNULL(SUM(up.up_ditemukan), 0) as jumlah_usaha_ditemukan'),
                DB::raw('IFNULL(SUM(up.up_tdk), 0) as usaha_tidak_ditemukan'),
                DB::raw('IFNULL(SUM(uk.uk_ditemukan), 0) as jumlah_usaha_keluarga'),
                DB::raw('IFNULL(SUM(pk.pk_ditemukan), 0) as jumlah_keluarga_ditemukan'),
                DB::raw('IFNULL(SUM(pk.pk_tdk), 0) as keluarga_tidak_ditemukan'),
                DB::raw('(IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) as muatan_murni'),
            ])
            ->groupBy([
                'm.tanggal_tarik',
                DB::raw('LEFT(m.region_code, 7)'),
                'm.email_pencacah',
                'p_cacah.nama_lengkap',
            ]);

        // Filter: Tanggal Data
        if (!empty($selectedDate)) {
            $query->where('m.tanggal_tarik', '=', $selectedDate);
        }

        // Filter: Kode Kecamatan
        if (!empty($kodekec)) {
            $query->where('m.region_code', 'LIKE', $kodekec . '%');
        }

        // Filter: Search Keyword
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('p_cacah.nama_lengkap', 'LIKE', "%{$search}%")
                  ->orWhere('m.email_pencacah', 'LIKE', "%{$search}%")
                  ->orWhere('p_awas.nama_lengkap', 'LIKE', "%{$search}%");
            });
        }

        // Sorting mapping
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
    public function getSlsBaseQuery(Request $request, $selectedDate)
    {
        $db = $this->getDbConnection();
        $kodekec = trim((string) $request->get('kodekec', ''));

        $sipwSub = $db->table('sipw')
            ->select('id_subsls', DB::raw('MAX(nama_sls) as nama_sls'))
            ->groupBy('id_subsls');

        $pkSub = $db->table('se2026_pemutakhiran_keluarga')
            ->select('kode', DB::raw('MAX(sub_sls) as sub_sls'))
            ->groupBy('kode');

        $query = $db->table('monitoring_se2026 as m')
            ->leftJoin('alokasi_pengawas as a', 'm.region_code', '=', 'a.region_code')
            ->leftJoin('master_petugas as p_cacah', 'm.email_pencacah', '=', 'p_cacah.email')
            ->leftJoin('master_petugas as p_awas', 'a.email_pengawas', '=', 'p_awas.email')
            ->leftJoin('monitoring_sls_se2026 as sls', 'm.region_code', '=', 'sls.level_6_full_code')
            ->leftJoinSub($sipwSub, 'sipw', 'm.region_code', '=', 'sipw.id_subsls')
            ->leftJoinSub($pkSub, 'pk', 'm.region_code', '=', 'pk.kode')
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
            ])
            ->groupBy([
                'm.tanggal_tarik',
                'm.region_code',
                DB::raw('LEFT(m.region_code, 7)'),
                'sls.nmsls',
                'sipw.nama_sls',
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

        return $query;
    }

    /**
     * Build query for Agregasi Per PML (Pengawas).
     */
    public function getPmlBaseQuery(Request $request, $selectedDate)
    {
        $db = $this->getDbConnection();
        $kodekec = trim((string) $request->get('kodekec', ''));

        $upSubquery = $db->table('se2026_usaha_perusahaan')
            ->select(
                'kode',
                DB::raw('SUM(CAST(status___ditemukan AS SIGNED) + CAST(status___baru AS SIGNED)) AS up_ditemukan'),
                DB::raw('SUM(CAST(status___tutup AS SIGNED) + CAST(status___ganda AS SIGNED) + CAST(status___tidak_ditemukan AS SIGNED)) AS up_tdk')
            )
            ->groupBy('kode');

        $ukSubquery = $db->table('se2026_usaha_keluarga')
            ->select(
                'kode',
                DB::raw('SUM(CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka AS SIGNED)) AS uk_ditemukan')
            )
            ->groupBy('kode');

        $pkSubquery = $db->table('se2026_pemutakhiran_keluarga')
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
            ->leftJoinSub($upSubquery, 'up', 'm.region_code', '=', 'up.kode')
            ->leftJoinSub($ukSubquery, 'uk', 'm.region_code', '=', 'uk.kode')
            ->leftJoinSub($pkSubquery, 'pk', 'm.region_code', '=', 'pk.kode')
            ->select([
                'm.tanggal_tarik as tanggal_data',
                DB::raw('LEFT(m.region_code, 7) as kode_kec'),
                'a.email_pengawas',
                DB::raw('IFNULL(p_awas.nama_lengkap, IFNULL(a.email_pengawas, "Belum Dialokasikan")) as nama_pengawas'),
                DB::raw('COUNT(DISTINCT m.email_pencacah) as total_ppl'),
                DB::raw('COUNT(DISTINCT m.region_code) as total_sls'),
                DB::raw('SUM(m.total_beban) as beban_saat_ini'),
                DB::raw('(IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) as total_submit'),
                DB::raw('(IFNULL(SUM(m.status_open), 0) + IFNULL(SUM(m.status_draft), 0)) as belum_dikerjakan'),
                DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END as pct_submit'),
                DB::raw('IFNULL(SUM(up.up_ditemukan), 0) as jumlah_usaha_ditemukan'),
                DB::raw('IFNULL(SUM(up.up_tdk), 0) as usaha_tidak_ditemukan'),
                DB::raw('IFNULL(SUM(uk.uk_ditemukan), 0) as jumlah_usaha_keluarga'),
                DB::raw('IFNULL(SUM(pk.pk_ditemukan), 0) as jumlah_keluarga_ditemukan'),
                DB::raw('IFNULL(SUM(pk.pk_tdk), 0) as keluarga_tidak_ditemukan'),
                DB::raw('(IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) as muatan_murni'),
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

        return $query;
    }

    /**
     * Get lightweight KPI counts (PML count, SLS count) without full aggregation.
     */
    public function getLightweightCounts(Request $request, $selectedDate): array
    {
        $db = $this->getDbConnection();
        $kodekec = trim((string) $request->get('kodekec', ''));

        $pmlQuery = $db->table('monitoring_se2026 as m')
            ->leftJoin('alokasi_pengawas as a', 'm.region_code', '=', 'a.region_code')
            ->where('m.tanggal_tarik', '=', $selectedDate);

        $slsQuery = $db->table('monitoring_se2026 as m')
            ->where('m.tanggal_tarik', '=', $selectedDate);

        if (!empty($kodekec)) {
            $pmlQuery->where('m.region_code', 'LIKE', $kodekec . '%');
            $slsQuery->where('m.region_code', 'LIKE', $kodekec . '%');
        }

        $pmlCount = (clone $pmlQuery)->distinct()->count(DB::raw('CONCAT(LEFT(m.region_code, 7), "_", IFNULL(a.email_pengawas, ""))'));
        $slsCount = (clone $slsQuery)->distinct()->count('m.region_code');

        return [
            'total_pml' => $pmlCount,
            'total_sls' => $slsCount,
        ];
    }

    /**
     * Get formatted JSON response for PML server-side DataTables.
     */
    public function getPmlDataTablesResponse(Request $request): array
    {
        $dateInfo = $this->resolveDate($request);
        $selectedDate = $dateInfo['selectedDate'];

        $baseQuery = $this->getPmlBaseQuery($request, $selectedDate);

        $searchValue = trim((string) $request->get('search')['value'] ?? '');
        if (!empty($searchValue)) {
            $baseQuery->having(function ($q) use ($searchValue) {
                $q->having(DB::raw('IFNULL(p_awas.nama_lengkap, IFNULL(a.email_pengawas, "Belum Dialokasikan"))'), 'LIKE', "%{$searchValue}%")
                  ->orHaving(DB::raw('LEFT(m.region_code, 7)'), 'LIKE', "%{$searchValue}%");
            });
        }

        $db = $this->getDbConnection();

        $totalQuery = $this->getPmlBaseQuery($request, $selectedDate);
        $totalRecords = $db->table(DB::raw("({$totalQuery->toSql()}) as sub"))
            ->mergeBindings($totalQuery)
            ->count();

        $filteredRecords = $db->table(DB::raw("({$baseQuery->toSql()}) as sub"))
            ->mergeBindings($baseQuery)
            ->count();

        $orderColumnIndex = (int) ($request->get('order')[0]['column'] ?? 5);
        $orderDir = strtolower($request->get('order')[0]['dir'] ?? 'desc') === 'desc' ? 'desc' : 'asc';

        $dtColumns = [
            0 => null,
            1 => DB::raw('LEFT(m.region_code, 7)'),
            2 => DB::raw('IFNULL(p_awas.nama_lengkap, IFNULL(a.email_pengawas, "Belum Dialokasikan"))'),
            3 => DB::raw('COUNT(DISTINCT m.email_pencacah)'),
            4 => DB::raw('COUNT(DISTINCT m.region_code)'),
            5 => DB::raw('(IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0))'),
            6 => DB::raw('(IFNULL(SUM(m.status_open), 0) + IFNULL(SUM(m.status_draft), 0))'),
            7 => DB::raw('SUM(m.total_beban)'),
            8 => DB::raw('(IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0))'),
            9 => DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END'),
            10 => DB::raw('IFNULL(SUM(up.up_ditemukan), 0)'),
            11 => DB::raw('IFNULL(SUM(up.up_tdk), 0)'),
            12 => DB::raw('IFNULL(SUM(uk.uk_ditemukan), 0)'),
            13 => DB::raw('IFNULL(SUM(pk.pk_ditemukan), 0)'),
            14 => DB::raw('IFNULL(SUM(pk.pk_tdk), 0)'),
        ];

        $dataQuery = $this->getPmlBaseQuery($request, $selectedDate);
        if (!empty($searchValue)) {
            $dataQuery->having(function ($q) use ($searchValue) {
                $q->having(DB::raw('IFNULL(p_awas.nama_lengkap, IFNULL(a.email_pengawas, "Belum Dialokasikan"))'), 'LIKE', "%{$searchValue}%")
                  ->orHaving(DB::raw('LEFT(m.region_code, 7)'), 'LIKE', "%{$searchValue}%");
            });
        }

        if (isset($dtColumns[$orderColumnIndex])) {
            $dataQuery->orderBy($dtColumns[$orderColumnIndex], $orderDir);
        }

        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 25);
        if ($length > 0) {
            $dataQuery->offset($start)->limit($length);
        }

        $rows = $dataQuery->get();

        $data = [];
        foreach ($rows as $index => $row) {
            $namaKec = $this->kecNameMap[$row->kode_kec] ?? 'Kec. ' . $row->kode_kec;
            $pctClass = $row->pct_submit >= 70 ? 'bg-success-lt text-success' : ($row->pct_submit >= 50 ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger');

            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'no' => $start + $index + 1,
                'kecamatan' => '<div class="font-weight-bold">' . e($namaKec) . '</div><div class="small text-muted">Kode: ' . e($row->kode_kec) . '</div>',
                'nama_pengawas' => '<div class="font-weight-bold text-dark">' . e($row->nama_pengawas) . '</div><div class="small text-muted">' . e($row->email_pengawas ?: '-') . '</div>',
                'total_ppl' => '<span class="badge bg-blue-lt text-blue px-2 py-1 fs-4">' . number_format($row->total_ppl) . ' PPL</span>',
                'total_sls' => '<span class="badge bg-indigo-lt text-indigo px-2 py-1 fs-4">' . number_format($row->total_sls) . ' SLS</span>',
                'muatan_murni' => (int) $row->muatan_murni,
                'belum_dikerjakan' => (int) $row->belum_dikerjakan,
                'beban_saat_ini' => (int) $row->beban_saat_ini,
                'total_submit' => (int) $row->total_submit,
                'pct_submit' => '<span class="badge ' . $pctClass . ' font-weight-bold px-2 py-1">' . number_format($row->pct_submit, 1) . '%</span>',
                'jumlah_usaha_ditemukan' => (int) $row->jumlah_usaha_ditemukan,
                'usaha_tidak_ditemukan' => (int) $row->usaha_tidak_ditemukan,
                'jumlah_usaha_keluarga' => (int) $row->jumlah_usaha_keluarga,
                'jumlah_keluarga_ditemukan' => (int) $row->jumlah_keluarga_ditemukan,
                'keluarga_tidak_ditemukan' => (int) $row->keluarga_tidak_ditemukan,
            ];
        }

        return [
            'draw' => (int) $request->get('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ];
    }

    /**
     * Get formatted JSON response for SLS server-side DataTables.
     */
    public function getSlsDataTablesResponse(Request $request): array
    {
        $dateInfo = $this->resolveDate($request);
        $selectedDate = $dateInfo['selectedDate'];

        $baseQuery = $this->getSlsBaseQuery($request, $selectedDate);

        $searchValue = trim((string) $request->get('search')['value'] ?? '');
        if (!empty($searchValue)) {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->where('p_cacah.nama_lengkap', 'LIKE', "%{$searchValue}%")
                  ->orWhere('m.email_pencacah', 'LIKE', "%{$searchValue}%")
                  ->orWhere('p_awas.nama_lengkap', 'LIKE', "%{$searchValue}%")
                  ->orWhere('sls.nmsls', 'LIKE', "%{$searchValue}%")
                  ->orWhere('m.region_code', 'LIKE', "%{$searchValue}%");
            });
        }

        $db = $this->getDbConnection();

        $totalQuery = $this->getSlsBaseQuery($request, $selectedDate);
        $totalRecords = $db->table(DB::raw("({$totalQuery->toSql()}) as sub"))
            ->mergeBindings($totalQuery)
            ->count();

        $filteredRecords = $db->table(DB::raw("({$baseQuery->toSql()}) as sub"))
            ->mergeBindings($baseQuery)
            ->count();

        $orderColumnIndex = (int) ($request->get('order')[0]['column'] ?? 1);
        $orderDir = strtolower($request->get('order')[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $dtColumns = [
            0 => null,
            1 => DB::raw('LEFT(m.region_code, 7)'),
            2 => 'm.region_code',
            3 => DB::raw('IFNULL(p_cacah.nama_lengkap, m.email_pencacah)'),
            4 => DB::raw('GROUP_CONCAT(DISTINCT p_awas.nama_lengkap SEPARATOR ", ")'),
            5 => DB::raw('SUM(m.total_beban)'),
            6 => DB::raw('(IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0))'),
            7 => DB::raw('IFNULL(SUM(m.status_open), 0)'),
            8 => DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END'),
        ];

        $dataQuery = $this->getSlsBaseQuery($request, $selectedDate);
        if (!empty($searchValue)) {
            $dataQuery->where(function ($q) use ($searchValue) {
                $q->where('p_cacah.nama_lengkap', 'LIKE', "%{$searchValue}%")
                  ->orWhere('m.email_pencacah', 'LIKE', "%{$searchValue}%")
                  ->orWhere('p_awas.nama_lengkap', 'LIKE', "%{$searchValue}%")
                  ->orWhere('sls.nmsls', 'LIKE', "%{$searchValue}%")
                  ->orWhere('m.region_code', 'LIKE', "%{$searchValue}%");
            });
        }

        if (isset($dtColumns[$orderColumnIndex])) {
            $dataQuery->orderBy($dtColumns[$orderColumnIndex], $orderDir);
        }

        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 25);
        if ($length > 0) {
            $dataQuery->offset($start)->limit($length);
        }

        $rows = $dataQuery->get();

        $data = [];
        foreach ($rows as $index => $row) {
            $namaKec = $this->kecNameMap[$row->kode_kec] ?? 'Kec. ' . $row->kode_kec;
            $pctClass = $row->pct_submit >= 70 ? 'bg-success-lt text-success' : ($row->pct_submit >= 50 ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger');

            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'no' => $start + $index + 1,
                'kecamatan' => '<div class="font-weight-bold">' . e($namaKec) . '</div><div class="small text-muted">Kode: ' . e($row->kode_kec) . '</div>',
                'sls' => '<div class="font-weight-bold text-dark">' . e($row->nama_sls) . '</div><div class="small text-muted font-monospace">' . e($row->region_code) . '</div>',
                'nama_pencacah' => '<div class="font-weight-bold text-dark">' . e($row->nama_pencacah) . '</div><div class="small text-muted">' . e($row->email_pencacah) . '</div>',
                'nama_pengawas' => '<div class="small font-weight-medium">' . e($row->nama_pengawas ?: '-') . '</div>',
                'beban_saat_ini' => (int) $row->beban_saat_ini,
                'total_submit' => (int) $row->total_submit,
                'status_open' => (int) $row->status_open,
                'pct_submit' => '<span class="badge ' . $pctClass . ' font-weight-bold px-2 py-1">' . number_format($row->pct_submit, 1) . '%</span>',
            ];
        }

        return [
            'draw' => (int) $request->get('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ];
    }
}
