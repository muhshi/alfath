<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PengolahanController extends Controller
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

    /**
     * Build the filtered query for SE2026 Data Pengolahan.
     */
    protected function getFilteredQuery(Request $request): array
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

        // Subquery for Usaha Perusahaan
        $upSubquery = $db->table('se2026_usaha_perusahaan')
            ->select(
                'kode',
                DB::raw('SUM(CAST(status___ditemukan AS SIGNED) + CAST(status___baru AS SIGNED)) AS up_ditemukan'),
                DB::raw('SUM(CAST(status___tutup AS SIGNED) + CAST(status___ganda AS SIGNED) + CAST(status___tidak_ditemukan AS SIGNED)) AS up_tdk')
            )
            ->groupBy('kode');

        // Subquery for Usaha Keluarga
        $ukSubquery = $db->table('se2026_usaha_keluarga')
            ->select(
                'kode',
                DB::raw('SUM(CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka AS SIGNED)) AS uk_ditemukan'),
                DB::raw('SUM(CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tutup AS SIGNED) + CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___tidak_di AS SIGNED)) AS uk_tdk')
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
                DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END as pct_submit'),
                DB::raw('IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(uk.uk_ditemukan), 0) as jumlah_usaha_ditemukan'),
                DB::raw('IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(uk.uk_tdk), 0) as usaha_tidak_ditemukan'),
                DB::raw('IFNULL(SUM(pk.pk_ditemukan), 0) as jumlah_keluarga_ditemukan'),
                DB::raw('IFNULL(SUM(pk.pk_tdk), 0) as keluarga_tidak_ditemukan'),
                DB::raw('(IFNULL(SUM(m.total_beban), 0) - (IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(uk.uk_tdk), 0) + IFNULL(SUM(pk.pk_tdk), 0))) as muatan_murni'),
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
            'beban_saat_ini' => DB::raw('SUM(m.total_beban)'),
            'total_submit' => DB::raw('(IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0))'),
            'pct_submit' => DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END'),
            'jumlah_usaha_ditemukan' => DB::raw('IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(uk.uk_ditemukan), 0)'),
            'jumlah_keluarga_ditemukan' => DB::raw('IFNULL(SUM(pk.pk_ditemukan), 0)'),
            'muatan_murni' => DB::raw('(IFNULL(SUM(m.total_beban), 0) - (IFNULL(SUM(up.up_tdk), 0) + IFNULL(SUM(uk.uk_tdk), 0) + IFNULL(SUM(pk.pk_tdk), 0)))'),
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
     * Display the SE2026 Data Pengolahan Dashboard Table.
     */
    public function index(Request $request)
    {
        $data = $this->getFilteredQuery($request);
        $query = $data['query'];

        // Paginate results
        $paginatedData = $query->paginate($data['perPage'])->withQueryString();

        // Summary KPI Metrics across current filtered query
        $summaryQuery = clone $query;
        $allRecords = $summaryQuery->get();

        $kpiSummary = [
            'total_petugas' => $allRecords->count(),
            'total_beban' => $allRecords->sum('beban_saat_ini'),
            'total_submit' => $allRecords->sum('total_submit'),
            'total_usaha_ditemukan' => $allRecords->sum('jumlah_usaha_ditemukan'),
            'total_keluarga_ditemukan' => $allRecords->sum('jumlah_keluarga_ditemukan'),
            'total_muatan_murni' => $allRecords->sum('muatan_murni'),
            'pct_overall_submit' => $allRecords->sum('beban_saat_ini') > 0
                ? round(($allRecords->sum('total_submit') / $allRecords->sum('beban_saat_ini')) * 100, 2)
                : 0
        ];

        return view('dashboard-pengolahan', [
            'kecNameMap' => $this->kecNameMap,
            'availableDates' => $data['availableDates'],
            'selectedDate' => $data['selectedDate'],
            'search' => $data['search'],
            'kodekec' => $data['kodekec'],
            'sortBy' => $data['sortBy'],
            'sortDir' => $data['sortDir'],
            'perPage' => $data['perPage'],
            'paginatedData' => $paginatedData,
            'kpiSummary' => $kpiSummary,
        ]);
    }

    /**
     * Export the filtered SE2026 Data Pengolahan to Excel (CSV with UTF-8 BOM).
     */
    public function export(Request $request)
    {
        $filtered = $this->getFilteredQuery($request);
        $records = $filtered['query']->get();

        $dateSuffix = !empty($filtered['selectedDate']) ? '_' . str_replace('-', '', $filtered['selectedDate']) : '_' . date('Ymd');
        $filename = "Export_Data_Pengolahan_SE2026{$dateSuffix}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $kecNameMap = $this->kecNameMap;

        $callback = function () use ($records, $kecNameMap) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel to open properly with unicode text
            fputs($file, "\xEF\BB\xBF");

            // Header columns
            fputcsv($file, [
                'No',
                'Kode Kec',
                'Nama Kecamatan',
                'Nama Pencacah',
                'Email Pencacah',
                'Nama Pengawas',
                'Muatan Murni Saat Ini',
                'Beban Saat Ini',
                'Total Submit',
                'Capaian Submit (%)',
                'Usaha Ditemukan',
                'Usaha Tidak Ditemukan',
                'Keluarga Ditemukan',
                'Keluarga Tidak Ditemukan',
            ]);

            $no = 1;
            $sumBeban = 0;
            $sumSubmit = 0;
            $sumUsahaDitemukan = 0;
            $sumUsahaTdk = 0;
            $sumKeluargaDitemukan = 0;
            $sumKeluargaTdk = 0;
            $sumMuatanMurni = 0;

            foreach ($records as $row) {
                $namaKec = $kecNameMap[$row->kode_kec] ?? $row->kode_kec;

                $sumBeban += (int) $row->beban_saat_ini;
                $sumSubmit += (int) $row->total_submit;
                $sumUsahaDitemukan += (int) $row->jumlah_usaha_ditemukan;
                $sumUsahaTdk += (int) $row->usaha_tidak_ditemukan;
                $sumKeluargaDitemukan += (int) $row->jumlah_keluarga_ditemukan;
                $sumKeluargaTdk += (int) $row->keluarga_tidak_ditemukan;
                $sumMuatanMurni += (int) $row->muatan_murni;

                fputcsv($file, [
                    $no++,
                    $row->kode_kec,
                    $namaKec,
                    $row->nama_pencacah,
                    $row->email_pencacah,
                    $row->nama_pengawas ?: '-',
                    $row->muatan_murni,
                    $row->beban_saat_ini,
                    $row->total_submit,
                    $row->pct_submit,
                    $row->jumlah_usaha_ditemukan,
                    $row->usaha_tidak_ditemukan,
                    $row->jumlah_keluarga_ditemukan,
                    $row->keluarga_tidak_ditemukan,
                ]);
            }

            // Summary row at the bottom
            $overallPct = $sumBeban > 0 ? round(($sumSubmit / $sumBeban) * 100, 2) : 0;
            fputcsv($file, [
                'TOTAL',
                '-',
                '-',
                '-',
                '-',
                '-',
                $sumMuatanMurni,
                $sumBeban,
                $sumSubmit,
                $overallPct,
                $sumUsahaDitemukan,
                $sumUsahaTdk,
                $sumKeluargaDitemukan,
                $sumKeluargaTdk,
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
