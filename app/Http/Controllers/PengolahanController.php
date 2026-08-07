<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
     * Build the filtered query for SE2026 Data Petugas.
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
                // Belum Dikerjakan = Beban Saat Ini - Total Submit (Status Open + Draft)
                DB::raw('(IFNULL(SUM(m.status_open), 0) + IFNULL(SUM(m.status_draft), 0)) as belum_dikerjakan'),
                DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END as pct_submit'),
                DB::raw('IFNULL(SUM(up.up_ditemukan), 0) as jumlah_usaha_ditemukan'),
                DB::raw('IFNULL(SUM(up.up_tdk), 0) as usaha_tidak_ditemukan'),
                // Usaha Keluarga (Informasi Tambahan)
                DB::raw('IFNULL(SUM(uk.uk_ditemukan), 0) as jumlah_usaha_keluarga'),
                DB::raw('IFNULL(SUM(pk.pk_ditemukan), 0) as jumlah_keluarga_ditemukan'),
                DB::raw('IFNULL(SUM(pk.pk_tdk), 0) as keluarga_tidak_ditemukan'),
                // Muatan Murni = Usaha Perusahaan Ditemukan/Baru + Keluarga Ditemukan/Baru
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
    protected function getSlsQuery(Request $request, $selectedDate)
    {
        $connName = config()->has('database.connections.fasih') ? 'fasih' : null;
        $db = $connName ? DB::connection($connName) : DB::connection();

        $search = trim((string) $request->get('search', ''));
        $kodekec = trim((string) $request->get('kodekec', ''));

        // Subquery for SIPW (Wilkerstat) to resolve SLS names
        $sipwSub = $db->table('sipw')
            ->select('id_subsls', DB::raw('MAX(nama_sls) as nama_sls'))
            ->groupBy('id_subsls');

        // Subquery for Pemutakhiran Keluarga to resolve SLS names
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
     * Display the SE2026 Data Petugas Dashboard Table.
     */
    public function index(Request $request)
    {
        $data = $this->getFilteredQuery($request);
        $query = $data['query'];

        // Retrieve records for Tab 1 (Petugas summary) and Tab 2 (SLS detail)
        $records = $query->get();
        $slsRecords = $this->getSlsQuery($request, $data['selectedDate']);

        // Summary KPI Metrics across current filtered query
        $kpiSummary = [
            'total_petugas' => $records->count(),
            'total_beban' => $records->sum('beban_saat_ini'),
            'total_submit' => $records->sum('total_submit'),
            'total_belum_dikerjakan' => $records->sum('belum_dikerjakan'),
            'total_usaha_ditemukan' => $records->sum('jumlah_usaha_ditemukan'),
            'total_usaha_keluarga' => $records->sum('jumlah_usaha_keluarga'),
            'total_keluarga_ditemukan' => $records->sum('jumlah_keluarga_ditemukan'),
            'total_muatan_murni' => $records->sum('muatan_murni'),
            'total_sls' => $slsRecords->count(),
            'pct_overall_submit' => $records->sum('beban_saat_ini') > 0
                ? round(($records->sum('total_submit') / $records->sum('beban_saat_ini')) * 100, 2)
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
            'records' => $records,
            'slsRecords' => $slsRecords,
            'kpiSummary' => $kpiSummary,
        ]);
    }

    /**
     * Export the filtered SE2026 Data Petugas to native Excel (.xlsx).
     */
    public function export(Request $request)
    {
        $filtered = $this->getFilteredQuery($request);
        $records = $filtered['query']->get();

        $dateSuffix = !empty($filtered['selectedDate']) ? '_' . str_replace('-', '', $filtered['selectedDate']) : '_' . date('Ymd');
        $filename = "Export_Data_Petugas_SE2026{$dateSuffix}.xlsx";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Petugas SE2026');

        // Title Banner in Excel
        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', 'DATA PETUGAS SE2026 - BPS KABUPATEN DEMAK');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $subTitle = 'Tanggal Data: ' . (!empty($filtered['selectedDate']) ? date('d M Y', strtotime($filtered['selectedDate'])) : 'Semua Tanggal');
        if (!empty($filtered['kodekec'])) {
            $subTitle .= ' | Kecamatan: ' . ($this->kecNameMap[$filtered['kodekec']] ?? $filtered['kodekec']);
        }
        if (!empty($filtered['search'])) {
            $subTitle .= ' | Pencarian: ' . $filtered['search'];
        }
        $sheet->mergeCells('A2:P2');
        $sheet->setCellValue('A2', $subTitle);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

        // Headers
        $headers = [
            'No',
            'Kode Kec',
            'Nama Kecamatan',
            'Nama Pencacah',
            'Email Pencacah',
            'Nama Pengawas',
            'Muatan Murni ⭐',
            'Belum Dikerjakan',
            'Beban Saat Ini',
            'Total Submit',
            'Capaian Submit (%)',
            'Usaha Perusahaan Ditemukan',
            'Usaha Perusahaan Tdk Ditemukan',
            'Usaha Keluarga (Ditemukan)',
            'Keluarga Ditemukan',
            'Keluarga Tdk Ditemukan',
        ];

        $startRow = 4;
        foreach ($headers as $colIdx => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . $startRow;
            $sheet->setCellValue($cell, $header);
        }

        // Header styling
        $headerRange = 'A' . $startRow . ':P' . $startRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0284C7'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension($startRow)->setRowHeight(28);

        // Fill Data Rows
        $rowIdx = $startRow + 1;
        $no = 1;
        $sumBeban = 0;
        $sumSubmit = 0;
        $sumBelum = 0;
        $sumUsahaDitemukan = 0;
        $sumUsahaTdk = 0;
        $sumUsahaKeluarga = 0;
        $sumKeluargaDitemukan = 0;
        $sumKeluargaTdk = 0;
        $sumMuatanMurni = 0;

        foreach ($records as $row) {
            $namaKec = $this->kecNameMap[$row->kode_kec] ?? $row->kode_kec;

            $sumBeban += (int) $row->beban_saat_ini;
            $sumSubmit += (int) $row->total_submit;
            $sumBelum += (int) $row->belum_dikerjakan;
            $sumUsahaDitemukan += (int) $row->jumlah_usaha_ditemukan;
            $sumUsahaTdk += (int) $row->usaha_tidak_ditemukan;
            $sumUsahaKeluarga += (int) $row->jumlah_usaha_keluarga;
            $sumKeluargaDitemukan += (int) $row->jumlah_keluarga_ditemukan;
            $sumKeluargaTdk += (int) $row->keluarga_tidak_ditemukan;
            $sumMuatanMurni += (int) $row->muatan_murni;

            $sheet->setCellValue('A' . $rowIdx, $no++);
            $sheet->setCellValueExplicit('B' . $rowIdx, $row->kode_kec, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $rowIdx, $namaKec);
            $sheet->setCellValue('D' . $rowIdx, $row->nama_pencacah);
            $sheet->setCellValue('E' . $rowIdx, $row->email_pencacah);
            $sheet->setCellValue('F' . $rowIdx, $row->nama_pengawas ?: '-');
            $sheet->setCellValue('G' . $rowIdx, (int) $row->muatan_murni);
            $sheet->setCellValue('H' . $rowIdx, (int) $row->belum_dikerjakan);
            $sheet->setCellValue('I' . $rowIdx, (int) $row->beban_saat_ini);
            $sheet->setCellValue('J' . $rowIdx, (int) $row->total_submit);
            $sheet->setCellValue('K' . $rowIdx, (float) $row->pct_submit / 100);
            $sheet->setCellValue('L' . $rowIdx, (int) $row->jumlah_usaha_ditemukan);
            $sheet->setCellValue('M' . $rowIdx, (int) $row->usaha_tidak_ditemukan);
            $sheet->setCellValue('N' . $rowIdx, (int) $row->jumlah_usaha_keluarga);
            $sheet->setCellValue('O' . $rowIdx, (int) $row->jumlah_keluarga_ditemukan);
            $sheet->setCellValue('P' . $rowIdx, (int) $row->keluarga_tidak_ditemukan);

            // Alignment and Number Formats
            $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $rowIdx . ':P' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle('G' . $rowIdx . ':J' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('K' . $rowIdx)->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle('L' . $rowIdx . ':P' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

            // Highlight Muatan Murni Column
            $sheet->getStyle('G' . $rowIdx)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0D9488']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CCFBF1']],
            ]);

            // Highlight Belum Dikerjakan Column
            $sheet->getStyle('H' . $rowIdx)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
            ]);

            $rowIdx++;
        }

        // Summary Total Row
        $overallPct = $sumBeban > 0 ? ($sumSubmit / $sumBeban) : 0;

        $sheet->setCellValue('A' . $rowIdx, 'TOTAL');
        $sheet->mergeCells('A' . $rowIdx . ':F' . $rowIdx);
        $sheet->setCellValue('G' . $rowIdx, $sumMuatanMurni);
        $sheet->setCellValue('H' . $rowIdx, $sumBelum);
        $sheet->setCellValue('I' . $rowIdx, $sumBeban);
        $sheet->setCellValue('J' . $rowIdx, $sumSubmit);
        $sheet->setCellValue('K' . $rowIdx, $overallPct);
        $sheet->setCellValue('L' . $rowIdx, $sumUsahaDitemukan);
        $sheet->setCellValue('M' . $rowIdx, $sumUsahaTdk);
        $sheet->setCellValue('N' . $rowIdx, $sumUsahaKeluarga);
        $sheet->setCellValue('O' . $rowIdx, $sumKeluargaDitemukan);
        $sheet->setCellValue('P' . $rowIdx, $sumKeluargaTdk);

        $totalRange = 'A' . $rowIdx . ':P' . $rowIdx;
        $sheet->getStyle($totalRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '475569']],
            ],
        ]);

        $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G' . $rowIdx . ':P' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G' . $rowIdx . ':J' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('K' . $rowIdx)->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle('L' . $rowIdx . ':P' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

        // Auto-fit Column Widths
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set active sheet to 0
        $spreadsheet->setActiveSheetIndex(0);

        // SHEET 2: Rincian Alokasi Per SLS
        $slsRecords = $this->getSlsQuery($request, $filtered['selectedDate']);

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Rincian Alokasi Per SLS');

        // Title Banner in Sheet 2
        $sheet2->mergeCells('A1:K1');
        $sheet2->setCellValue('A1', 'RINCIAN ALOKASI & PROGRESS PER SLS - BPS KABUPATEN DEMAK');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet2->mergeCells('A2:K2');
        $sheet2->setCellValue('A2', $subTitle);
        $sheet2->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

        $headersSls = [
            'No',
            'Kode Kec',
            'Nama Kecamatan',
            'Kode SLS / Sub-SLS',
            'Nama SLS',
            'Nama Pencacah',
            'Nama Pengawas',
            'Beban Saat Ini',
            'Total Submit',
            'Belum Disentuh (Open)',
            'Capaian Submit (%)',
        ];

        foreach ($headersSls as $colIdx => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . '4';
            $sheet2->setCellValue($cell, $header);
        }

        $sheet2->getStyle('A4:K4')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0284C7'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet2->getRowDimension(4)->setRowHeight(28);

        $rowIdx2 = 5;
        $no2 = 1;
        foreach ($slsRecords as $row) {
            $namaKec = $this->kecNameMap[$row->kode_kec] ?? $row->kode_kec;
            $sheet2->setCellValue('A' . $rowIdx2, $no2++);
            $sheet2->setCellValueExplicit('B' . $rowIdx2, $row->kode_kec, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet2->setCellValue('C' . $rowIdx2, $namaKec);
            $sheet2->setCellValueExplicit('D' . $rowIdx2, $row->region_code, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet2->setCellValue('E' . $rowIdx2, $row->nama_sls);
            $sheet2->setCellValue('F' . $rowIdx2, $row->nama_pencacah);
            $sheet2->setCellValue('G' . $rowIdx2, $row->nama_pengawas ?: '-');
            $sheet2->setCellValue('H' . $rowIdx2, (int) $row->beban_saat_ini);
            $sheet2->setCellValue('I' . $rowIdx2, (int) $row->total_submit);
            $sheet2->setCellValue('J' . $rowIdx2, (int) $row->status_open);
            $sheet2->setCellValue('K' . $rowIdx2, (float) $row->pct_submit / 100);

            $sheet2->getStyle('A' . $rowIdx2 . ':D' . $rowIdx2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle('H' . $rowIdx2 . ':J' . $rowIdx2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet2->getStyle('K' . $rowIdx2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet2->getStyle('H' . $rowIdx2 . ':J' . $rowIdx2)->getNumberFormat()->setFormatCode('#,##0');
            $sheet2->getStyle('K' . $rowIdx2)->getNumberFormat()->setFormatCode('0.00%');

            $rowIdx2++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
