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
     * Build the filtered query for SE2026 Data Pengolahan / Petugas.
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
                DB::raw('IFNULL(SUM(up.up_ditemukan), 0) as jumlah_usaha_ditemukan'),
                DB::raw('IFNULL(SUM(up.up_tdk), 0) as usaha_tidak_ditemukan'),
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
            'pct_submit' => DB::raw('CASE WHEN SUM(m.total_beban) > 0 THEN ROUND(((IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) / SUM(m.total_beban)) * 100, 2) ELSE 0 END'),
            'jumlah_usaha_ditemukan' => DB::raw('IFNULL(SUM(up.up_ditemukan), 0)'),
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
     * Display the SE2026 Data Petugas Dashboard Table.
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
        $sheet->mergeCells('A1:N1');
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
        $sheet->mergeCells('A2:N2');
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
            'Beban Saat Ini',
            'Total Submit',
            'Capaian Submit (%)',
            'Usaha Ditemukan',
            'Usaha Tdk Ditemukan',
            'Keluarga Ditemukan',
            'Keluarga Tdk Ditemukan',
        ];

        $startRow = 4;
        foreach ($headers as $colIdx => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . $startRow;
            $sheet->setCellValue($cell, $header);
        }

        // Header styling
        $headerRange = 'A' . $startRow . ':N' . $startRow;
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
        $sumUsahaDitemukan = 0;
        $sumUsahaTdk = 0;
        $sumKeluargaDitemukan = 0;
        $sumKeluargaTdk = 0;
        $sumMuatanMurni = 0;

        foreach ($records as $row) {
            $namaKec = $this->kecNameMap[$row->kode_kec] ?? $row->kode_kec;

            $sumBeban += (int) $row->beban_saat_ini;
            $sumSubmit += (int) $row->total_submit;
            $sumUsahaDitemukan += (int) $row->jumlah_usaha_ditemukan;
            $sumUsahaTdk += (int) $row->usaha_tidak_ditemukan;
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
            $sheet->setCellValue('H' . $rowIdx, (int) $row->beban_saat_ini);
            $sheet->setCellValue('I' . $rowIdx, (int) $row->total_submit);
            $sheet->setCellValue('J' . $rowIdx, (float) $row->pct_submit / 100);
            $sheet->setCellValue('K' . $rowIdx, (int) $row->jumlah_usaha_ditemukan);
            $sheet->setCellValue('L' . $rowIdx, (int) $row->usaha_tidak_ditemukan);
            $sheet->setCellValue('M' . $rowIdx, (int) $row->jumlah_keluarga_ditemukan);
            $sheet->setCellValue('N' . $rowIdx, (int) $row->keluarga_tidak_ditemukan);

            // Alignment and Number Formats
            $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $rowIdx . ':N' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle('G' . $rowIdx . ':I' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('J' . $rowIdx)->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle('K' . $rowIdx . ':N' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

            // Highlight Muatan Murni Column
            $sheet->getStyle('G' . $rowIdx)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0D9488']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CCFBF1']],
            ]);

            $rowIdx++;
        }

        // Summary Total Row
        $overallPct = $sumBeban > 0 ? ($sumSubmit / $sumBeban) : 0;

        $sheet->setCellValue('A' . $rowIdx, 'TOTAL');
        $sheet->mergeCells('A' . $rowIdx . ':F' . $rowIdx);
        $sheet->setCellValue('G' . $rowIdx, $sumMuatanMurni);
        $sheet->setCellValue('H' . $rowIdx, $sumBeban);
        $sheet->setCellValue('I' . $rowIdx, $sumSubmit);
        $sheet->setCellValue('J' . $rowIdx, $overallPct);
        $sheet->setCellValue('K' . $rowIdx, $sumUsahaDitemukan);
        $sheet->setCellValue('L' . $rowIdx, $sumUsahaTdk);
        $sheet->setCellValue('M' . $rowIdx, $sumKeluargaDitemukan);
        $sheet->setCellValue('N' . $rowIdx, $sumKeluargaTdk);

        $totalRange = 'A' . $rowIdx . ':N' . $rowIdx;
        $sheet->getStyle($totalRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '475569']],
            ],
        ]);

        $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G' . $rowIdx . ':N' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G' . $rowIdx . ':I' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('J' . $rowIdx)->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle('K' . $rowIdx . ':N' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

        // Auto-fit Column Widths
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
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
