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
     * Export the filtered SE2026 Data Petugas to native Excel (.xlsx).
     */
    public function exportToExcel(array $filteredData, array $kecNameMap)
    {
        $records = $filteredData['query']->get();
        $selectedDate = $filteredData['selectedDate'];
        $kodekec = $filteredData['kodekec'];
        $search = $filteredData['search'];

        $dateSuffix = !empty($selectedDate) ? '_' . str_replace('-', '', $selectedDate) : '_' . date('Ymd');
        $filename = "Export_Data_Petugas_SE2026{$dateSuffix}.xlsx";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Petugas SE2026');

        // Title Banner in Excel
        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', 'DATA PETUGAS SE2026 - BPS KABUPATEN DEMAK');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $subTitle = 'Tanggal Data: ' . (!empty($selectedDate) ? date('d M Y', strtotime($selectedDate)) : 'Semua Tanggal');
        if (!empty($kodekec)) {
            $subTitle .= ' | Kecamatan: ' . ($kecNameMap[$kodekec] ?? $kodekec);
        }
        if (!empty($search)) {
            $subTitle .= ' | Pencarian: ' . $search;
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

        // Header Styling
        $headerRange = 'A4:P4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'))->setSize(10);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('0284C7');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(28);

        // Data Rows
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

            // Alignment & Number Formatting
            $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $rowIdx . ':J' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('K' . $rowIdx)->getNumberFormat()->setFormatCode('0.00"%"');
            $sheet->getStyle('L' . $rowIdx . ':P' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

            // Highlighting Muatan Murni Column G
            $sheet->getStyle('G' . $rowIdx)->getFont()->setBold(true);
            $sheet->getStyle('G' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E6FFFA');

            // Zebra striping
            if ($index % 2 == 1) {
                $sheet->getStyle('A' . $rowIdx . ':F' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F8FAFC');
                $sheet->getStyle('H' . $rowIdx . ':P' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F8FAFC');
            }

            $rowIdx++;
        }

        // Summary Total Row
        $lastRow = $rowIdx - 1;
        $sumRow = $rowIdx;
        $sheet->setCellValue('A' . $sumRow, 'TOTAL');
        $sheet->mergeCells('A' . $sumRow . ':F' . $sumRow);
        $sheet->getStyle('A' . $sumRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('G' . $sumRow, "=SUM(G5:G{$lastRow})");
        $sheet->setCellValue('H' . $sumRow, "=SUM(H5:H{$lastRow})");
        $sheet->setCellValue('I' . $sumRow, "=SUM(I5:I{$lastRow})");
        $sheet->setCellValue('J' . $sumRow, "=SUM(J5:J{$lastRow})");
        $sheet->setCellValue('K' . $sumRow, "=IF(I{$sumRow}>0, ROUND((J{$sumRow}/I{$sumRow})*100, 2), 0)");
        $sheet->setCellValue('L' . $sumRow, "=SUM(L5:L{$lastRow})");
        $sheet->setCellValue('M' . $sumRow, "=SUM(M5:M{$lastRow})");
        $sheet->setCellValue('N' . $sumRow, "=SUM(N5:N{$lastRow})");
        $sheet->setCellValue('O' . $sumRow, "=SUM(O5:O{$lastRow})");
        $sheet->setCellValue('P' . $sumRow, "=SUM(P5:P{$lastRow})");

        $sumRange = "A{$sumRow}:P{$sumRow}";
        $sheet->getStyle($sumRange)->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle($sumRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');
        $sheet->getStyle("G{$sumRow}:J{$sumRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("K{$sumRow}")->getNumberFormat()->setFormatCode('0.00"%"');
        $sheet->getStyle("L{$sumRow}:P{$sumRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getRowDimension($sumRow)->setRowHeight(24);

        // Borders across table
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'CBD5E1'],
                ],
            ],
        ];
        $sheet->getStyle("A4:P{$sumRow}")->applyFromArray($borderStyle);

        // Auto-fit column widths
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Stream output Excel download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
