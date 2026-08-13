<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PengolahanExportService
{
    /**
     * Export the filtered SE2026 Data Petugas across all 4 Tabs to a multi-sheet native Excel (.xlsx).
     */
    public function exportToExcel(array $filteredData, array $kecNameMap)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $monitoringService = app(Se2026MonitoringService::class);
        $rankingService = app(PetugasPerformanceRankingService::class);

        $records = $filteredData['query']->get();
        $selectedDate = $filteredData['selectedDate'];
        $kodekec = $filteredData['kodekec'];
        $search = $filteredData['search'];

        $pmlRecords = $monitoringService->getPmlQuery(request(), $selectedDate);
        $slsRecords = $monitoringService->getSlsQuery(request(), $selectedDate);
        $rankingData = $rankingService->calculateRankingData($records, $selectedDate);
        $rankingRecords = $rankingData['rankingRecords'];

        $dateSuffix = !empty($selectedDate) ? '_' . str_replace('-', '', $selectedDate) : '_' . date('Ymd');
        $filename = "Export_Dashboard_SE2026{$dateSuffix}.xlsx";

        $spreadsheet = new Spreadsheet();

        // -------------------------------------------------------------
        // SHEET 1: RANKING KINERJA PETUGAS
        // -------------------------------------------------------------
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ranking Kinerja Petugas');
        $this->buildRankingSheet($sheet1, $rankingRecords, $kecNameMap, $selectedDate, $rankingData['dynamicTargetPct']);

        // -------------------------------------------------------------
        // SHEET 2: DATA PETUGAS (PPL)
        // -------------------------------------------------------------
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Data Petugas (PPL)');
        $this->buildPplSheet($sheet2, $records, $kecNameMap, $selectedDate, $kodekec, $search);

        // -------------------------------------------------------------
        // SHEET 3: AGREGASI PENGAWAS (PML)
        // -------------------------------------------------------------
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Agregasi Pengawas (PML)');
        $this->buildPmlSheet($sheet3, $pmlRecords, $kecNameMap, $selectedDate, $kodekec, $search);

        // -------------------------------------------------------------
        // SHEET 4: ALOKASI PER SLS / SUB-SLS
        // -------------------------------------------------------------
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Alokasi Per SLS');
        $this->buildSlsSheet($sheet4, $slsRecords, $kecNameMap, $selectedDate, $kodekec, $search);

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    protected function buildPplSheet($sheet, $records, $kecNameMap, $selectedDate, $kodekec, $search)
    {
        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', 'DATA PETUGAS SE2026 (PPL) - BPS KABUPATEN DEMAK');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));

        $subTitle = 'Tanggal Data: ' . (!empty($selectedDate) ? date('d M Y', strtotime($selectedDate)) : 'Semua Tanggal');
        if (!empty($kodekec)) $subTitle .= ' | Kec: ' . ($kecNameMap[$kodekec] ?? $kodekec);
        if (!empty($search)) $subTitle .= ' | Cari: ' . $search;
        $sheet->mergeCells('A2:P2');
        $sheet->setCellValue('A2', $subTitle);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

        $headers = [
            'No', 'Kode Kec', 'Nama Kecamatan', 'Nama Pencacah', 'Email Pencacah', 'Nama Pengawas',
            'Muatan Murni ⭐', 'Belum Dikerjakan', 'Beban Saat Ini', 'Total Submit', 'Capaian Submit (%)',
            'BKU Ditemukan', 'BKU Tdk Ditemukan', 'UK Ditemukan', 'Keluarga Ditemukan', 'Keluarga Tdk Ditemukan'
        ];

        foreach ($headers as $colIdx => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . '4';
            $sheet->setCellValue($cell, $header);
        }

        $headerRange = 'A4:P4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'))->setSize(10);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('0284C7');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(26);

        $rowIdx = 5;
        foreach ($records as $index => $row) {
            $sheet->setCellValue('A' . $rowIdx, $index + 1);
            $sheet->setCellValue('B' . $rowIdx, $row->kode_kec);
            $sheet->setCellValue('C' . $rowIdx, $kecNameMap[$row->kode_kec] ?? 'Kec. ' . $row->kode_kec);
            $sheet->setCellValue('D' . $rowIdx, $row->nama_pencacah);
            $sheet->setCellValue('E' . $rowIdx, $row->email_pencacah);
            $sheet->setCellValue('F' . $rowIdx, $row->nama_pengawas ?: '-');
            $sheet->setCellValue('G' . $rowIdx, (int) $row->muatan_murni);
            $sheet->setCellValue('H' . $rowIdx, (int) $row->belum_dikerjakan);
            $sheet->setCellValue('I' . $rowIdx, (int) $row->beban_saat_ini);
            $sheet->setCellValue('J' . $rowIdx, (int) $row->total_submit);
            $sheet->setCellValue('K' . $rowIdx, (float) $row->pct_submit);
            $sheet->setCellValue('L' . $rowIdx, (int) $row->jumlah_usaha_ditemukan);
            $sheet->setCellValue('M' . $rowIdx, (int) $row->usaha_tidak_ditemukan);
            $sheet->setCellValue('N' . $rowIdx, (int) $row->jumlah_usaha_keluarga);
            $sheet->setCellValue('O' . $rowIdx, (int) $row->jumlah_keluarga_ditemukan);
            $sheet->setCellValue('P' . $rowIdx, (int) $row->keluarga_tidak_ditemukan);

            $sheet->getStyle('A' . $rowIdx . ':B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $rowIdx . ':J' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('K' . $rowIdx)->getNumberFormat()->setFormatCode('0.00"%"');
            $sheet->getStyle('L' . $rowIdx . ':P' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G' . $rowIdx)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0D9488'));

            if ($index % 2 == 1) {
                $sheet->getStyle('A' . $rowIdx . ':P' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F8FAFC');
            }
            $rowIdx++;
        }

        $lastRow = $rowIdx - 1;
        $sumRow = $rowIdx;
        $sheet->setCellValue('A' . $sumRow, 'TOTAL');
        $sheet->mergeCells("A{$sumRow}:F{$sumRow}");
        $sheet->getStyle("A{$sumRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue("G{$sumRow}", "=SUM(G5:G{$lastRow})");
        $sheet->setCellValue("H{$sumRow}", "=SUM(H5:H{$lastRow})");
        $sheet->setCellValue("I{$sumRow}", "=SUM(I5:I{$lastRow})");
        $sheet->setCellValue("J{$sumRow}", "=SUM(J5:J{$lastRow})");
        $sheet->setCellValue("K{$sumRow}", "=IF(I{$sumRow}>0, ROUND((J{$sumRow}/I{$sumRow})*100, 2), 0)");
        $sheet->setCellValue("L{$sumRow}", "=SUM(L5:L{$lastRow})");
        $sheet->setCellValue("M{$sumRow}", "=SUM(M5:M{$lastRow})");
        $sheet->setCellValue("N{$sumRow}", "=SUM(N5:N{$lastRow})");
        $sheet->setCellValue("O{$sumRow}", "=SUM(O5:O{$lastRow})");
        $sheet->setCellValue("P{$sumRow}", "=SUM(P5:P{$lastRow})");

        $sumRange = "A{$sumRow}:P{$sumRow}";
        $sheet->getStyle($sumRange)->getFont()->setBold(true);
        $sheet->getStyle($sumRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');
        $sheet->getStyle("G{$sumRow}:J{$sumRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("K{$sumRow}")->getNumberFormat()->setFormatCode('0.00"%"');
        $sheet->getStyle("L{$sumRow}:P{$sumRow}")->getNumberFormat()->setFormatCode('#,##0');

        $this->applyBordersAndAutoWidth($sheet, "A4:P{$sumRow}", 'A', 'P');
    }

    protected function buildPmlSheet($sheet, $pmlRecords, $kecNameMap, $selectedDate, $kodekec, $search)
    {
        $sheet->mergeCells('A1:Q1');
        $sheet->setCellValue('A1', 'AGREGASI PENGAWAS (PML) SE2026 - BPS KABUPATEN DEMAK');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));

        $subTitle = 'Tanggal Data: ' . (!empty($selectedDate) ? date('d M Y', strtotime($selectedDate)) : 'Semua Tanggal');
        $sheet->mergeCells('A2:Q2');
        $sheet->setCellValue('A2', $subTitle);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

        $headers = [
            'No', 'Kode Kec', 'Nama Kecamatan', 'Nama Pengawas (PML)', 'Email Pengawas', 'Total PPL', 'Total SLS',
            'Muatan Murni ⭐', 'Belum Dikerjakan', 'Beban Saat Ini', 'Total Submit', 'Capaian Submit (%)',
            'BKU Ditemukan', 'BKU Tdk Ditemukan', 'UK Ditemukan', 'Keluarga Ditemukan', 'Keluarga Tdk Ditemukan'
        ];

        foreach ($headers as $colIdx => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . '4';
            $sheet->setCellValue($cell, $header);
        }

        $headerRange = 'A4:Q4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'))->setSize(10);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('475569');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(26);

        $rowIdx = 5;
        foreach ($pmlRecords as $index => $row) {
            $sheet->setCellValue('A' . $rowIdx, $index + 1);
            $sheet->setCellValue('B' . $rowIdx, $row->kode_kec);
            $sheet->setCellValue('C' . $rowIdx, $kecNameMap[$row->kode_kec] ?? 'Kec. ' . $row->kode_kec);
            $sheet->setCellValue('D' . $rowIdx, $row->nama_pengawas);
            $sheet->setCellValue('E' . $rowIdx, $row->email_pengawas);
            $sheet->setCellValue('F' . $rowIdx, (int) $row->total_ppl);
            $sheet->setCellValue('G' . $rowIdx, (int) $row->total_sls);
            $sheet->setCellValue('H' . $rowIdx, (int) $row->muatan_murni);
            $sheet->setCellValue('I' . $rowIdx, (int) $row->belum_dikerjakan);
            $sheet->setCellValue('J' . $rowIdx, (int) $row->beban_saat_ini);
            $sheet->setCellValue('K' . $rowIdx, (int) $row->total_submit);
            $sheet->setCellValue('L' . $rowIdx, (float) $row->pct_submit);
            $sheet->setCellValue('M' . $rowIdx, (int) $row->jumlah_usaha_ditemukan);
            $sheet->setCellValue('N' . $rowIdx, (int) $row->usaha_tidak_ditemukan);
            $sheet->setCellValue('O' . $rowIdx, (int) $row->jumlah_usaha_keluarga);
            $sheet->setCellValue('P' . $rowIdx, (int) $row->jumlah_keluarga_ditemukan);
            $sheet->setCellValue('Q' . $rowIdx, (int) $row->keluarga_tidak_ditemukan);

            $sheet->getStyle('A' . $rowIdx . ':B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $rowIdx . ':K' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('L' . $rowIdx)->getNumberFormat()->setFormatCode('0.00"%"');
            $sheet->getStyle('M' . $rowIdx . ':Q' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

            if ($index % 2 == 1) {
                $sheet->getStyle('A' . $rowIdx . ':Q' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F8FAFC');
            }
            $rowIdx++;
        }

        $lastRow = $rowIdx - 1;
        $sumRow = $rowIdx;
        $sheet->setCellValue('A' . $sumRow, 'TOTAL');
        $sheet->mergeCells("A{$sumRow}:E{$sumRow}");
        $sheet->getStyle("A{$sumRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue("F{$sumRow}", "=SUM(F5:F{$lastRow})");
        $sheet->setCellValue("G{$sumRow}", "=SUM(G5:G{$lastRow})");
        $sheet->setCellValue("H{$sumRow}", "=SUM(H5:H{$lastRow})");
        $sheet->setCellValue("I{$sumRow}", "=SUM(I5:I{$lastRow})");
        $sheet->setCellValue("J{$sumRow}", "=SUM(J5:J{$lastRow})");
        $sheet->setCellValue("K{$sumRow}", "=SUM(K5:K{$lastRow})");
        $sheet->setCellValue("L{$sumRow}", "=IF(J{$sumRow}>0, ROUND((K{$sumRow}/J{$sumRow})*100, 2), 0)");
        $sheet->setCellValue("M{$sumRow}", "=SUM(M5:M{$lastRow})");
        $sheet->setCellValue("N{$sumRow}", "=SUM(N5:N{$lastRow})");
        $sheet->setCellValue("O{$sumRow}", "=SUM(O5:O{$lastRow})");
        $sheet->setCellValue("P{$sumRow}", "=SUM(P5:P{$lastRow})");
        $sheet->setCellValue("Q{$sumRow}", "=SUM(Q5:Q{$lastRow})");

        $sumRange = "A{$sumRow}:Q{$sumRow}";
        $sheet->getStyle($sumRange)->getFont()->setBold(true);
        $sheet->getStyle($sumRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');
        $sheet->getStyle("F{$sumRow}:K{$sumRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("L{$sumRow}")->getNumberFormat()->setFormatCode('0.00"%"');
        $sheet->getStyle("M{$sumRow}:Q{$sumRow}")->getNumberFormat()->setFormatCode('#,##0');

        $this->applyBordersAndAutoWidth($sheet, "A4:Q{$sumRow}", 'A', 'Q');
    }

    protected function buildSlsSheet($sheet, $slsRecords, $kecNameMap, $selectedDate, $kodekec, $search)
    {
        $sheet->mergeCells('A1:T1');
        $sheet->setCellValue('A1', 'ALOKASI PER SLS / SUB-SLS SE2026 - BPS KABUPATEN DEMAK');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));

        $subTitle = 'Tanggal Data: ' . (!empty($selectedDate) ? date('d M Y', strtotime($selectedDate)) : 'Semua Tanggal');
        $sheet->mergeCells('A2:T2');
        $sheet->setCellValue('A2', $subTitle);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

        $headers = [
            'No', 'Kode Kec', 'Nama Kecamatan', 'Kode SLS (16 Digit)', 'Nama SLS / Sub-SLS', 'Nama Pencacah', 'Email Pencacah', 'Nama Pengawas',
            'Beban Saat Ini', 'Total Submit', 'Belum Disentuh (Open)', 'Capaian Submit (%)',
            'BKU Ditemukan', 'BKU Tdk/Tutup/Ganda', 'UK Ditemukan', 'UK Tdk/Tutup/Ganda',
            'Keluarga Ditemukan', 'Keluarga Tdk/Meninggal', 'TOTAL DITEMUKAN', 'TOTAL TDK DITEMUKAN / TUTUP / GANDA'
        ];

        foreach ($headers as $colIdx => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . '4';
            $sheet->setCellValue($cell, $header);
        }

        $headerRange = 'A4:T4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'))->setSize(10);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('2563EB');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(28);

        $rowIdx = 5;
        foreach ($slsRecords as $index => $row) {
            $sheet->setCellValue('A' . $rowIdx, $index + 1);
            $sheet->setCellValue('B' . $rowIdx, $row->kode_kec);
            $sheet->setCellValue('C' . $rowIdx, $kecNameMap[$row->kode_kec] ?? 'Kec. ' . $row->kode_kec);
            $sheet->setCellValue('D' . $rowIdx, $row->region_code);
            $sheet->setCellValue('E' . $rowIdx, $row->nama_sls);
            $sheet->setCellValue('F' . $rowIdx, $row->nama_pencacah);
            $sheet->setCellValue('G' . $rowIdx, $row->email_pencacah);
            $sheet->setCellValue('H' . $rowIdx, $row->nama_pengawas ?: '-');
            $sheet->setCellValue('I' . $rowIdx, (int) $row->beban_saat_ini);
            $sheet->setCellValue('J' . $rowIdx, (int) $row->total_submit);
            $sheet->setCellValue('K' . $rowIdx, (int) $row->status_open);
            $sheet->setCellValue('L' . $rowIdx, (float) $row->pct_submit);
            $sheet->setCellValue('M' . $rowIdx, (int) $row->up_ditemukan);
            $sheet->setCellValue('N' . $rowIdx, (int) $row->up_tdk);
            $sheet->setCellValue('O' . $rowIdx, (int) $row->uk_ditemukan);
            $sheet->setCellValue('P' . $rowIdx, (int) $row->uk_tdk);
            $sheet->setCellValue('Q' . $rowIdx, (int) $row->pk_ditemukan);
            $sheet->setCellValue('R' . $rowIdx, (int) $row->pk_tdk);
            $sheet->setCellValue('S' . $rowIdx, (int) $row->total_ditemukan);
            $sheet->setCellValue('T' . $rowIdx, (int) $row->total_tdk);

            $sheet->getStyle('A' . $rowIdx . ':B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I' . $rowIdx . ':K' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('L' . $rowIdx)->getNumberFormat()->setFormatCode('0.00"%"');
            $sheet->getStyle('M' . $rowIdx . ':T' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

            // Highlight Total Tdk Ditemukan column T with red fill
            if ($row->total_tdk > 0) {
                $sheet->getStyle('T' . $rowIdx)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('DC2626'));
                $sheet->getStyle('T' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FEF2F2');
            }

            if ($index % 2 == 1) {
                $sheet->getStyle('A' . $rowIdx . ':S' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F8FAFC');
            }
            $rowIdx++;
        }

        $lastRow = $rowIdx - 1;
        $sumRow = $rowIdx;
        $sheet->setCellValue('A' . $sumRow, 'TOTAL');
        $sheet->mergeCells("A{$sumRow}:H{$sumRow}");
        $sheet->getStyle("A{$sumRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue("I{$sumRow}", "=SUM(I5:I{$lastRow})");
        $sheet->setCellValue("J{$sumRow}", "=SUM(J5:J{$lastRow})");
        $sheet->setCellValue("K{$sumRow}", "=SUM(K5:K{$lastRow})");
        $sheet->setCellValue("L{$sumRow}", "=IF(I{$sumRow}>0, ROUND((J{$sumRow}/I{$sumRow})*100, 2), 0)");
        $sheet->setCellValue("M{$sumRow}", "=SUM(M5:M{$lastRow})");
        $sheet->setCellValue("N{$sumRow}", "=SUM(N5:N{$lastRow})");
        $sheet->setCellValue("O{$sumRow}", "=SUM(O5:O{$lastRow})");
        $sheet->setCellValue("P{$sumRow}", "=SUM(P5:P{$lastRow})");
        $sheet->setCellValue("Q{$sumRow}", "=SUM(Q5:Q{$lastRow})");
        $sheet->setCellValue("R{$sumRow}", "=SUM(R5:R{$lastRow})");
        $sheet->setCellValue("S{$sumRow}", "=SUM(S5:S{$lastRow})");
        $sheet->setCellValue("T{$sumRow}", "=SUM(T5:T{$lastRow})");

        $sumRange = "A{$sumRow}:T{$sumRow}";
        $sheet->getStyle($sumRange)->getFont()->setBold(true);
        $sheet->getStyle($sumRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');
        $sheet->getStyle("I{$sumRow}:K{$sumRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("L{$sumRow}")->getNumberFormat()->setFormatCode('0.00"%"');
        $sheet->getStyle("M{$sumRow}:T{$sumRow}")->getNumberFormat()->setFormatCode('#,##0');

        $this->applyBordersAndAutoWidth($sheet, "A4:T{$sumRow}", 'A', 'T');
    }

    protected function buildRankingSheet($sheet, $rankingRecords, $kecNameMap, $selectedDate, $dynamicTargetPct)
    {
        $sheet->mergeCells('A1:R1');
        $sheet->setCellValue('A1', 'RANKING KINERJA PETUGAS SE2026 - BPS KABUPATEN DEMAK');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));

        $subTitle = 'Target Harian Standar Hari Ini: ' . number_format($dynamicTargetPct, 1) . '% | Tanggal Data: ' . (!empty($selectedDate) ? date('d M Y', strtotime($selectedDate)) : 'Semua Tanggal');
        $sheet->mergeCells('A2:R2');
        $sheet->setCellValue('A2', $subTitle);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('D97706'));

        $headers = [
            'Peringkat', 'Kode Kec', 'Nama Kecamatan', 'Nama Petugas (PPL)', 'Email Petugas', 'Nama Pengawas (PML)',
            'Beban Saat Ini', 'Total Submit', 'Capaian Submit (%)', 'Status Warning (3-Hari)', 'Warning Anomali Usaha',
            'Laju s.d. 20 Agt', 'Skor Kinerja (0-100)', 'Kategori Kinerja', 'Rekomendasi Tindakan PML',
            'Status Catatan Anomali', 'Catatan Klarifikasi Petugas', 'Catatan Admin'
        ];

        foreach ($headers as $colIdx => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . '4';
            $sheet->setCellValue($cell, $header);
        }

        $headerRange = 'A4:R4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'))->setSize(10);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('D97706');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(28);

        $rowIdx = 5;
        foreach ($rankingRecords as $index => $row) {
            $sheet->setCellValue('A' . $rowIdx, '#' . ($index + 1));
            $sheet->setCellValue('B' . $rowIdx, $row->kode_kec);
            $sheet->setCellValue('C' . $rowIdx, $kecNameMap[$row->kode_kec] ?? 'Kec. ' . $row->kode_kec);
            $sheet->setCellValue('D' . $rowIdx, $row->nama_pencacah);
            $sheet->setCellValue('E' . $rowIdx, $row->email_pencacah);
            $sheet->setCellValue('F' . $rowIdx, $row->nama_pengawas ?: '-');
            $sheet->setCellValue('G' . $rowIdx, (int) $row->beban_saat_ini);
            $sheet->setCellValue('H' . $rowIdx, (int) $row->total_submit);
            $sheet->setCellValue('I' . $rowIdx, (float) $row->pct_submit);
            $draftStr = ($row->draft_today ?? 0) > 0 ? " (+" . number_format($row->draft_today) . " draft/hari)" : "";
            $submitStr = "submit " . number_format($row->submit_today ?? 0) . "/hari" . $draftStr . " | ";
            $sheet->setCellValue('J' . $rowIdx, $submitStr . match($row->warning_status) {
                'completed' => '🎉 Selesai 100%',
                'stagnant' => '🚨 Stagnan ' . ($row->stagnant_days ?? 0) . ' hari',
                'slow_progress' => '⚠️ Progres Lambat',
                default => '✅ Normal',
            });
            $sheet->setCellValue('K' . $rowIdx, $row->has_warning_usaha ? "⚠️ Warning (BKU < 5% / UK < 10%)" : "✅ Normal");
            $sheet->setCellValue('L' . $rowIdx, (float) $row->laju_harian_95);
            $sheet->setCellValue('M' . $rowIdx, (float) $row->skor_kinerja);
            $sheet->setCellValue('N' . $rowIdx, $row->kat_label ?? '-');
            $sheet->setCellValue('O' . $rowIdx, $row->rekomendasi ?? '-');

            // Anomali notes details
            $statusAnomali = '-';
            $catatanPetugasList = [];
            $catatanAdminList = [];
            if (!empty($row->anomali_sls_list)) {
                $statusAnomali = "✅ {$row->cnt_anomali_approved}/" . count($row->anomali_sls_list) . " Disetujui, ⏳ {$row->cnt_anomali_pending} Pending, 🚨 {$row->cnt_anomali_belum} Belum";
                foreach ($row->anomali_sls_list as $sls) {
                    if (!empty($sls['catatan_petugas'])) {
                        $catatanPetugasList[] = "[{$sls['region_code']}] {$sls['catatan_petugas']}";
                    }
                    if (!empty($sls['catatan_admin'])) {
                        $catatanAdminList[] = "[{$sls['region_code']}] {$sls['catatan_admin']}";
                    }
                }
            }

            $sheet->setCellValue('P' . $rowIdx, $statusAnomali);
            $sheet->setCellValue('Q' . $rowIdx, !empty($catatanPetugasList) ? implode(" | ", $catatanPetugasList) : '-');
            $sheet->setCellValue('R' . $rowIdx, !empty($catatanAdminList) ? implode(" | ", $catatanAdminList) : '-');

            $sheet->getStyle('A' . $rowIdx . ':B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $rowIdx . ':H' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I' . $rowIdx)->getNumberFormat()->setFormatCode('0.00"%"');
            $sheet->getStyle('L' . $rowIdx)->getNumberFormat()->setFormatCode('0.0" SLS/hr"');
            $sheet->getStyle('M' . $rowIdx)->getNumberFormat()->setFormatCode('0.0');

            // Highlight Skor Kinerja M
            $sheet->getStyle('M' . $rowIdx)->getFont()->setBold(true);
            $sheet->getStyle('M' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FEF3C7');

            if ($index % 2 == 1) {
                $sheet->getStyle('A' . $rowIdx . ':L' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F8FAFC');
                $sheet->getStyle('N' . $rowIdx . ':R' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F8FAFC');
            }
            $rowIdx++;
        }

        $this->applyBordersAndAutoWidth($sheet, "A4:R" . ($rowIdx - 1), 'A', 'R');
    }

    protected function applyBordersAndAutoWidth($sheet, $range, $startCol, $endCol)
    {
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'CBD5E1'],
                ],
            ],
        ];
        $sheet->getStyle($range)->applyFromArray($borderStyle);

        foreach (range($startCol, $endCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
