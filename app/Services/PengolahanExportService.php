<?php

namespace App\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PengolahanExportService
{
    protected PengolahanDataService $dataService;

    public function __construct(PengolahanDataService $dataService)
    {
        $this->dataService = $dataService;
    }

    /**
     * Generate multi-sheet (.xlsx) Excel stream response.
     */
    public function exportToExcel(Request $request): StreamedResponse
    {
        $filtered = $this->dataService->getFilteredQuery($request);
        $records = $filtered['query']->get();
        $kecNameMap = $this->dataService->kecNameMap;

        $dateSuffix = !empty($filtered['selectedDate']) ? '_' . str_replace('-', '', $filtered['selectedDate']) : '_' . date('Ymd');
        $filename = "Export_Data_Petugas_SE2026{$dateSuffix}.xlsx";

        $spreadsheet = new Spreadsheet();
        
        // ==========================================
        // SHEET 1: Data Petugas SE2026 (PPL)
        // ==========================================
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Petugas SE2026');

        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', 'DATA PETUGAS SE2026 - BPS KABUPATEN DEMAK');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $subTitle = 'Tanggal Data: ' . (!empty($filtered['selectedDate']) ? date('d M Y', strtotime($filtered['selectedDate'])) : 'Semua Tanggal');
        if (!empty($filtered['kodekec'])) {
            $subTitle .= ' | Kecamatan: ' . ($kecNameMap[$filtered['kodekec']] ?? $filtered['kodekec']);
        }
        if (!empty($filtered['search'])) {
            $subTitle .= ' | Pencarian: ' . $filtered['search'];
        }
        $sheet->mergeCells('A2:P2');
        $sheet->setCellValue('A2', $subTitle);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

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

        $headerRange = 'A' . $startRow . ':P' . $startRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($startRow)->setRowHeight(28);

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
            $namaKec = $kecNameMap[$row->kode_kec] ?? $row->kode_kec;

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

            $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $rowIdx . ':P' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle('G' . $rowIdx . ':J' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('K' . $rowIdx)->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle('L' . $rowIdx . ':P' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

            $sheet->getStyle('G' . $rowIdx)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0D9488']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CCFBF1']],
            ]);

            $sheet->getStyle('H' . $rowIdx)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
            ]);

            $rowIdx++;
        }

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

        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ==========================================
        // SHEET 2: Ringkasan Per PML (Pengawas)
        // ==========================================
        $pmlRecords = $this->dataService->getPmlBaseQuery($request, $filtered['selectedDate'])->get();
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Ringkasan PML (Pengawas)');

        $sheet2->mergeCells('A1:Q1');
        $sheet2->setCellValue('A1', 'DATA AGREGASI PER PML (PENGAWAS) SE2026 - BPS KABUPATEN DEMAK');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet2->mergeCells('A2:Q2');
        $sheet2->setCellValue('A2', $subTitle);
        $sheet2->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

        $headersPml = [
            'No',
            'Kode Kec',
            'Nama Kecamatan',
            'Nama Pengawas (PML)',
            'Email Pengawas',
            'Jumlah PPL',
            'Jumlah SLS',
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

        foreach ($headersPml as $colIdx => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . '4';
            $sheet2->setCellValue($cell, $header);
        }

        $sheet2->getStyle('A4:Q4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet2->getRowDimension(4)->setRowHeight(28);

        $rowIdx2 = 5;
        $no2 = 1;
        foreach ($pmlRecords as $row) {
            $namaKec = $kecNameMap[$row->kode_kec] ?? $row->kode_kec;
            $sheet2->setCellValue('A' . $rowIdx2, $no2++);
            $sheet2->setCellValueExplicit('B' . $rowIdx2, $row->kode_kec, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet2->setCellValue('C' . $rowIdx2, $namaKec);
            $sheet2->setCellValue('D' . $rowIdx2, $row->nama_pengawas);
            $sheet2->setCellValue('E' . $rowIdx2, $row->email_pengawas ?: '-');
            $sheet2->setCellValue('F' . $rowIdx2, (int) $row->total_ppl);
            $sheet2->setCellValue('G' . $rowIdx2, (int) $row->total_sls);
            $sheet2->setCellValue('H' . $rowIdx2, (int) $row->muatan_murni);
            $sheet2->setCellValue('I' . $rowIdx2, (int) $row->belum_dikerjakan);
            $sheet2->setCellValue('J' . $rowIdx2, (int) $row->beban_saat_ini);
            $sheet2->setCellValue('K' . $rowIdx2, (int) $row->total_submit);
            $sheet2->setCellValue('L' . $rowIdx2, (float) $row->pct_submit / 100);
            $sheet2->setCellValue('M' . $rowIdx2, (int) $row->jumlah_usaha_ditemukan);
            $sheet2->setCellValue('N' . $rowIdx2, (int) $row->usaha_tidak_ditemukan);
            $sheet2->setCellValue('O' . $rowIdx2, (int) $row->jumlah_usaha_keluarga);
            $sheet2->setCellValue('P' . $rowIdx2, (int) $row->jumlah_keluarga_ditemukan);
            $sheet2->setCellValue('Q' . $rowIdx2, (int) $row->keluarga_tidak_ditemukan);

            $sheet2->getStyle('A' . $rowIdx2 . ':B' . $rowIdx2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle('F' . $rowIdx2 . ':Q' . $rowIdx2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet2->getStyle('F' . $rowIdx2 . ':K' . $rowIdx2)->getNumberFormat()->setFormatCode('#,##0');
            $sheet2->getStyle('L' . $rowIdx2)->getNumberFormat()->setFormatCode('0.00%');
            $sheet2->getStyle('M' . $rowIdx2 . ':Q' . $rowIdx2)->getNumberFormat()->setFormatCode('#,##0');

            $rowIdx2++;
        }

        foreach (range('A', 'Q') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // ==========================================
        // SHEET 3: Rincian Alokasi Per SLS
        // ==========================================
        $slsRecords = $this->dataService->getSlsBaseQuery($request, $filtered['selectedDate'])->get();

        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Rincian Alokasi Per SLS');

        $sheet3->mergeCells('A1:K1');
        $sheet3->setCellValue('A1', 'RINCIAN ALOKASI & PROGRESS PER SLS - BPS KABUPATEN DEMAK');
        $sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet3->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet3->mergeCells('A2:K2');
        $sheet3->setCellValue('A2', $subTitle);
        $sheet3->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

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
            $sheet3->setCellValue($cell, $header);
        }

        $sheet3->getStyle('A4:K4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet3->getRowDimension(4)->setRowHeight(28);

        $rowIdx3 = 5;
        $no3 = 1;
        foreach ($slsRecords as $row) {
            $namaKec = $kecNameMap[$row->kode_kec] ?? $row->kode_kec;
            $sheet3->setCellValue('A' . $rowIdx3, $no3++);
            $sheet3->setCellValueExplicit('B' . $rowIdx3, $row->kode_kec, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet3->setCellValue('C' . $rowIdx3, $namaKec);
            $sheet3->setCellValueExplicit('D' . $rowIdx3, $row->region_code, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet3->setCellValue('E' . $rowIdx3, $row->nama_sls);
            $sheet3->setCellValue('F' . $rowIdx3, $row->nama_pencacah);
            $sheet3->setCellValue('G' . $rowIdx3, $row->nama_pengawas ?: '-');
            $sheet3->setCellValue('H' . $rowIdx3, (int) $row->beban_saat_ini);
            $sheet3->setCellValue('I' . $rowIdx3, (int) $row->total_submit);
            $sheet3->setCellValue('J' . $rowIdx3, (int) $row->status_open);
            $sheet3->setCellValue('K' . $rowIdx3, (float) $row->pct_submit / 100);

            $sheet3->getStyle('A' . $rowIdx3 . ':D' . $rowIdx3)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet3->getStyle('H' . $rowIdx3 . ':J' . $rowIdx3)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet3->getStyle('K' . $rowIdx3)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet3->getStyle('H' . $rowIdx3 . ':J' . $rowIdx3)->getNumberFormat()->setFormatCode('#,##0');
            $sheet3->getStyle('K' . $rowIdx3)->getNumberFormat()->setFormatCode('0.00%');

            $rowIdx3++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
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
