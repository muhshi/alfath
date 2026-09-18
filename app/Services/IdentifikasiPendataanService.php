<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class IdentifikasiPendataanService
{
    public function __construct(
        protected Se2026MonitoringService $monitoringService
    ) {}

    /**
     * Get processed & identified SLS records for Quality Control.
     */
    public function getIdentifikasiData(Request $request): array
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $cacheStore = Cache::store('file');

        if ($request->has('fresh') || $request->has('refresh')) {
            try {
                $cacheStore->increment('se2026_dash_version');
            } catch (\Throwable $e) {}
        }

        $cacheVersion = 7;
        try {
            $cacheVersion = (int) $cacheStore->get('se2026_dash_version', 7);
            if ($cacheVersion < 7) {
                $cacheVersion = 7;
                $cacheStore->set('se2026_dash_version', 7);
            }
        } catch (\Throwable $e) {}

        $datesInfo = $this->monitoringService->getAvailableDates($request->get('tanggal_data'));
        $selectedDate = $datesInfo['selectedDate'];
        $availableDates = $datesInfo['availableDates'];
        $kecNameMap = $this->monitoringService->getKecNameMap();

        $filterKategori = $request->get('kategori', 'anomali_only');
        $statusSubmitFilter = $request->get('status_submit', 'all');
        $kodekec = $request->get('kodekec', '');
        $search = trim((string) $request->get('search', ''));

        // Filter Tipe Wilayah:
        // Jika ada parameter hide_non_sls: 1 => 'sls', 0 => 'all'
        // Jika tidak, baca tipe_wilayah (default: 'sls' agar fokus ke wilayah SLS penduduk)
        $hideNonSlsParam = $request->get('hide_non_sls');
        if ($hideNonSlsParam !== null) {
            $filterTipeWilayah = ($hideNonSlsParam == '1' || $hideNonSlsParam === 'true') ? 'sls' : 'all';
        } else {
            $filterTipeWilayah = $request->get('tipe_wilayah', 'sls');
        }
        $hideNonSls = in_array($filterTipeWilayah, ['sls', 'pemukiman']);

        $cacheKey = "se2026_identifikasi_v{$cacheVersion}_" . md5(json_encode([
            'date' => $selectedDate,
            'kodekec' => $kodekec,
            'kategori' => $filterKategori,
            'status_submit' => $statusSubmitFilter,
            'tipe_wilayah' => $filterTipeWilayah,
            'hide_non_sls' => $hideNonSls ? 1 : 0,
            'search' => $search,
        ]));

        $result = $cacheStore->remember($cacheKey, now()->addHours(12), function () use (
            $request, $selectedDate, $availableDates, $kecNameMap,
            $filterKategori, $statusSubmitFilter, $filterTipeWilayah, $hideNonSls, $kodekec, $search
        ) {
            $rawSlsRecords = $this->monitoringService->getSlsQuery($request, $selectedDate);

            $summary = [
                'total_sls' => 0,          // Total wilayah yang masuk dalam cakupan evaluasi aktif
                'total_all_sls' => 0,      // Total seluruh wilayah (8.270)
                'cnt_sls' => 0,            // Total SLS penduduk (digit 11 == 0: 7.337)
                'cnt_non_sls' => 0,        // Total Non-SLS (digit 11 > 0: 933)
                'cnt_pemukiman' => 0,
                'cnt_non_pemukiman' => 0,
                'cnt_under_80' => 0,
                'cnt_saved_by_wilkerstat' => 0, // SLS < 80% Prelist tapi muatan >= 90% Wilkerstat (Aman)
                'cnt_over_130' => 0,
                'cnt_zero_usaha' => 0,
                'cnt_usaha_drop' => 0,
                'cnt_keluarga_drop' => 0,
                'cnt_ganda' => 0,
                'cnt_bangunan_lainnya' => 0,
                'cnt_total_anomali' => 0,
                'cnt_aman' => 0,
                'total_murni' => 0,
                'total_prelist' => 0,
                'total_wilkerstat' => 0,
            ];

            $identifiedRecords = [];

            foreach ($rawSlsRecords as $row) {
                // Deteksi Karakteristik SLS: Digit ke-11 dari region_code (16 digit)
                // Standar BPS: Digit ke-11 == 0 adalah SLS biasa (RT, RW, Dusun, Lingkungan)
                //              Digit ke-11 > 0 adalah Non-SLS (Sawah, Perkebunan, Hutan, Tambak, Perairan, dll)
                $digit11 = substr((string) ($row->region_code ?? ''), 10, 1);
                $isNonSls = ($digit11 !== false && (int) $digit11 > 0);

                // Fallback pendukung jika ada kode khusus
                if (!$isNonSls && (isset($row->jenis_sls) && !in_array($row->jenis_sls, ['SLS', 'NONSLS_PEMUKIMAN']))) {
                    $isNonSls = true;
                }

                $row->is_non_sls = (bool) $isNonSls;
                $row->is_non_pemukiman = (bool) $isNonSls;
                $row->digit11 = $digit11;

                $summary['total_all_sls']++;
                if ($isNonSls) {
                    $summary['cnt_non_sls']++;
                    $summary['cnt_non_pemukiman']++;
                } else {
                    $summary['cnt_sls']++;
                    $summary['cnt_pemukiman']++;
                }

                // Filter Tipe Wilayah:
                // Jika fokus ke SLS murni (hide Non-SLS), lewati wilayah non-sls
                if (in_array($filterTipeWilayah, ['sls', 'pemukiman']) && $isNonSls) {
                    continue;
                } elseif (in_array($filterTipeWilayah, ['non_sls', 'non_pemukiman']) && !$isNonSls) {
                    continue;
                }

                $summary['total_sls']++;

                $prelist = (int) ($row->jml_prelist ?? 0);
                $murni = (int) ($row->muatan_murni ?? ($row->up_ditemukan + $row->pk_ditemukan));
                $wilkerstatUsaha = (int) ($row->wilkerstat_usaha ?? 0);
                $wilkerstatKk = (int) ($row->wilkerstat_kk ?? 0);
                $wilkerstatMuatan = $wilkerstatKk + $wilkerstatUsaha;

                $row->wilkerstat_muatan = $wilkerstatMuatan;
                $summary['total_prelist'] += $prelist;
                $summary['total_murni'] += $murni;
                $summary['total_wilkerstat'] += $wilkerstatMuatan;

                // Perhitungan Rasio Murni vs Wilkerstat (KK + Usaha)
                if ($wilkerstatMuatan > 0) {
                    $pctMurniWilkerstat = round(($murni / $wilkerstatMuatan) * 100, 2);
                } else {
                    $pctMurniWilkerstat = null;
                }
                $row->pct_murni_vs_wilkerstat = $pctMurniWilkerstat;

                // Perhitungan Rasio Murni vs Prelist
                if ($prelist > 0) {
                    $pctMurniPrelist = round(($murni / $prelist) * 100, 2);
                    $isUnder80Prelist = ($pctMurniPrelist < 80.0);
                    $isOver130 = ($pctMurniPrelist > 130.0);
                    $rasioOrder = $pctMurniPrelist;
                } else {
                    $pctMurniPrelist = null; // Prelist 0 bukan rasio 0.0%
                    $isUnder80Prelist = false; // Jangan anggap anomali kurang prelist jika prelist awalnya memang 0
                    $isOver130 = ($murni > 0); // Jika prelist 0 tapi ada muatan, ini temuan baru / pemekaran
                    $rasioOrder = 99999;     // Nilai order tinggi agar tidak meloncat ke atas saat sorting ascending
                }

                // ATURAN TOLERANSI WILKERSTAT:
                // Jika muatan murni < 80% Prelist, tetapi jika dibandingkan dengan total muatan Wilkerstat
                // sudah mencapai >= 90%, maka SLS ini dianggap SUDAH AMAN / WAJAR (karena defisit disebabkan
                // prelist awal over-estimasi, bukan undercoverage petugas lapangan).
                $isSavedByWilkerstat = ($isUnder80Prelist && $pctMurniWilkerstat !== null && $pctMurniWilkerstat >= 90.0);
                $isUnder80 = ($isUnder80Prelist && !$isSavedByWilkerstat);

                $row->pct_murni_vs_prelist = $pctMurniPrelist;
                $row->rasio_order = $rasioOrder;
                $row->is_under_80_prelist = $isUnder80Prelist;
                $row->is_saved_by_wilkerstat = $isSavedByWilkerstat;

                $totalUsaha = (int) ($row->total_usaha_se ?? ($row->up_ditemukan + $row->uk_ditemukan));
                $row->total_usaha_se = $totalUsaha;

                // Anomali Flags
                $isZeroUsaha = ($wilkerstatUsaha > 0 && $totalUsaha == 0);
                $isUsahaDrop = ($wilkerstatUsaha > 0 && ($row->pct_diff_usaha ?? 0) < -30.0);
                $isKeluargaDrop = ($prelist > 0 && ((int) $row->pk_tdk / max(1, $prelist)) * 100 >= 15.0);
                $isGanda = ((int) ($row->total_ganda ?? 0) > 0);
                $isBangunanLainnya = ((float) ($row->pct_bangunan_lainnya ?? 0) >= 15.0);

                $anomaliTags = [];
                if ($isUnder80) {
                    $summary['cnt_under_80']++;
                    $anomaliTags[] = 'under_80';
                }
                if ($isSavedByWilkerstat) {
                    $summary['cnt_saved_by_wilkerstat']++;
                }
                if ($isOver130) {
                    $summary['cnt_over_130']++;
                    $anomaliTags[] = 'over_130';
                }
                if ($isZeroUsaha) {
                    $summary['cnt_zero_usaha']++;
                    $anomaliTags[] = 'zero_usaha';
                }
                if ($isUsahaDrop) {
                    $summary['cnt_usaha_drop']++;
                    $anomaliTags[] = 'usaha_drop';
                }
                if ($isKeluargaDrop) {
                    $summary['cnt_keluarga_drop']++;
                    $anomaliTags[] = 'keluarga_drop';
                }
                if ($isGanda) {
                    $summary['cnt_ganda']++;
                    $anomaliTags[] = 'ganda';
                }
                if ($isBangunanLainnya) {
                    $summary['cnt_bangunan_lainnya']++;
                    $anomaliTags[] = 'bangunan_lainnya';
                }

                $hasAnomali = count($anomaliTags) > 0;
                if ($hasAnomali) {
                    $summary['cnt_total_anomali']++;
                } else {
                    $summary['cnt_aman']++;
                }

                $row->is_under_80 = $isUnder80;
                $row->is_over_130 = $isOver130;
                $row->is_zero_usaha = $isZeroUsaha;
                $row->is_usaha_drop = $isUsahaDrop;
                $row->is_keluarga_drop = $isKeluargaDrop;
                $row->is_ganda = $isGanda;
                $row->is_bangunan_lainnya = $isBangunanLainnya;
                $row->has_anomali = $hasAnomali;
                $row->anomali_tags = $anomaliTags;

                // Apply Kategori Filter
                if ($filterKategori === 'anomali_only' && !$hasAnomali) {
                    continue;
                } elseif ($filterKategori === 'under_80' && !$isUnder80) {
                    continue;
                } elseif ($filterKategori === 'saved_wilkerstat' && !$isSavedByWilkerstat) {
                    continue;
                } elseif ($filterKategori === 'over_130' && !$isOver130) {
                    continue;
                } elseif ($filterKategori === 'zero_usaha' && !$isZeroUsaha) {
                    continue;
                } elseif ($filterKategori === 'usaha_drop' && !$isUsahaDrop) {
                    continue;
                } elseif ($filterKategori === 'keluarga_drop' && !$isKeluargaDrop) {
                    continue;
                } elseif ($filterKategori === 'ganda' && !$isGanda) {
                    continue;
                } elseif ($filterKategori === 'bangunan_lainnya' && !$isBangunanLainnya) {
                    continue;
                } elseif ($filterKategori === 'aman' && $hasAnomali) {
                    continue;
                }

                // Apply Status Submit Filter
                if ($statusSubmitFilter === 'completed' && (float) $row->pct_submit < 100.0) {
                    continue;
                } elseif ($statusSubmitFilter === 'in_progress' && ((float) $row->pct_submit <= 0 || (float) $row->pct_submit >= 100.0)) {
                    continue;
                } elseif ($statusSubmitFilter === 'open' && (int) $row->total_submit > 0) {
                    continue;
                }

                $identifiedRecords[] = $row;
            }

            return [
                'records' => collect($identifiedRecords),
                'summary' => $summary,
                'availableDates' => $availableDates,
                'selectedDate' => $selectedDate,
                'kecNameMap' => $kecNameMap,
                'filterKategori' => $filterKategori,
                'statusSubmitFilter' => $statusSubmitFilter,
                'filterTipeWilayah' => $filterTipeWilayah,
                'hideNonSls' => $hideNonSls,
                'kodekec' => $kodekec,
                'search' => $search,
            ];
        });

        // Defensive fallback: Pastikan summary selalu memiliki semua default keys
        $defaultSummary = [
            'total_sls' => 0,
            'total_all_sls' => 0,
            'cnt_sls' => 0,
            'cnt_non_sls' => 0,
            'cnt_pemukiman' => 0,
            'cnt_non_pemukiman' => 0,
            'cnt_under_80' => 0,
            'cnt_saved_by_wilkerstat' => 0,
            'cnt_over_130' => 0,
            'cnt_zero_usaha' => 0,
            'cnt_usaha_drop' => 0,
            'cnt_keluarga_drop' => 0,
            'cnt_ganda' => 0,
            'cnt_bangunan_lainnya' => 0,
            'cnt_total_anomali' => 0,
            'cnt_aman' => 0,
            'total_murni' => 0,
            'total_prelist' => 0,
            'total_wilkerstat' => 0,
        ];
        $result['summary'] = array_merge($defaultSummary, (array) ($result['summary'] ?? []));

        return $result;
    }

    /**
     * Export Identifikasi Hasil Pendataan to native Excel (.xlsx).
     */
    public function exportToExcel(array $identifikasiData)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $records = $identifikasiData['records'];
        $kecNameMap = $identifikasiData['kecNameMap'];
        $selectedDate = $identifikasiData['selectedDate'];
        $filterKategori = $identifikasiData['filterKategori'];

        $dateSuffix = !empty($selectedDate) ? '_' . str_replace('-', '', $selectedDate) : '_' . date('Ymd');
        $kategoriSuffix = '_' . strtolower($filterKategori);
        $filename = "Export_Identifikasi_Hasil_SE2026{$kategoriSuffix}{$dateSuffix}.xlsx";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Identifikasi Hasil SLS');

        // Header Title
        $sheet->mergeCells('A1:AG1');
        $sheet->setCellValue('A1', 'IDENTIFIKASI & EVALUASI HASIL PENDATAAN SLS SE2026 - BPS KABUPATEN DEMAK');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));

        $subTitle = 'Kategori Filter: ' . strtoupper(str_replace('_', ' ', $filterKategori)) . ' | Tanggal Data: ' . (!empty($selectedDate) ? date('d M Y', strtotime($selectedDate)) : 'Semua Tanggal');
        $sheet->mergeCells('A2:AG2');
        $sheet->setCellValue('A2', $subTitle);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

        $headers = [
            'No', 'Kode Kec', 'Nama Kecamatan', 'Kode SLS (16 Digit)', 'Jenis Wilayah', 'Nama SLS / Sub-SLS', 'Nama Pencacah', 'Nama Pengawas',
            'Status Temuan / QC', 'Rincian Indikator Terdeteksi',
            'Jml Prelist (KK+Usaha)', 'Prelist KK', 'Prelist Usaha',
            'Total Ditemukan (Muatan Murni)', 'Rasio Murni vs Prelist (%)', 'Kategori Rasio Prelist',
            'Muatan Wilkerstat 2025 (KK+Usaha)', 'Rasio Murni vs Wilkerstat (%)', 'Toleransi Wilkerstat (≥90%)',
            'Beban Saat Ini', 'Total Submit', 'Capaian Submit (%)', 'Belum Disentuh (Open)', 'Total Draft',
            'BKU Ditemukan', 'UK Ditemukan', 'Total Usaha SE', 'Usaha Wilkerstat 2025', 'Selisih Usaha vs Wilkerstat (%)',
            'Keluarga Ditemukan', 'KK Wilkerstat 2025', 'Khusus Ganda', 'Bangunan Kosong/Lainnya'
        ];

        foreach ($headers as $colIdx => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . '4';
            $sheet->setCellValue($cell, $header);
        }

        $headerRange = 'A4:AG4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'))->setSize(10);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('DC2626');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(30);

        $rowIdx = 5;
        foreach ($records as $index => $row) {
            $rincianList = [];
            if ($row->is_under_80) $rincianList[] = "Muatan Murni < 80% Prelist (" . number_format($row->pct_murni_vs_prelist, 1) . "%)";
            if ($row->is_saved_by_wilkerstat) $rincianList[] = "Toleransi Wilkerstat: Lolos Aman (" . number_format($row->pct_murni_vs_wilkerstat, 1) . "% Wilkerstat)";
            if ($row->is_over_130) $rincianList[] = "Lonjakan Muatan > 130% (" . number_format($row->pct_murni_vs_prelist, 1) . "%)";
            if ($row->is_zero_usaha) $rincianList[] = "Usaha SE Nol (Potensi Wilkerstat: " . number_format($row->wilkerstat_usaha) . ")";
            if ($row->is_usaha_drop) $rincianList[] = "Usaha Drop vs Wilkerstat (" . number_format($row->pct_diff_usaha, 1) . "%)";
            if ($row->is_keluarga_drop) $rincianList[] = "Keluarga Tdk Ditemukan Tinggi (" . number_format($row->pk_tdk) . ")";
            if ($row->is_ganda) $rincianList[] = "Khusus Ganda (" . number_format($row->total_ganda) . ")";
            if ($row->is_bangunan_lainnya) $rincianList[] = "Bangunan Kosong/Lainnya >= 15% (" . number_format($row->pct_bangunan_lainnya, 1) . "%)";

            $statusQc = $row->has_anomali ? "⚠️ PERLU KONFIRMASI" : ($row->is_saved_by_wilkerstat ? "🛡️ LOLOS WILKERSTAT (AMAN)" : "✅ WAJAR / AMAN");
            $katRasio = $row->pct_murni_vs_prelist < 80.0 ? ($row->is_saved_by_wilkerstat ? "🛡️ TOLERANSI WILKERSTAT" : "🚨 KURANG (<80%)") : ($row->pct_murni_vs_prelist > 130.0 ? "📈 LONJAKAN (>130%)" : "✅ NORMAL");
            $toleransiWilkerstat = $row->is_saved_by_wilkerstat ? "YA (≥90%)" : ($row->pct_murni_vs_wilkerstat !== null && $row->pct_murni_vs_wilkerstat >= 90.0 ? "Aman Wilkerstat" : "-");
            $jenisWilayah = $row->is_non_sls ? "🌾 Non-SLS" : "🏡 SLS";

            $sheet->setCellValue('A' . $rowIdx, $index + 1);
            $sheet->setCellValue('B' . $rowIdx, $row->kode_kec);
            $sheet->setCellValue('C' . $rowIdx, $kecNameMap[$row->kode_kec] ?? 'Kec. ' . $row->kode_kec);
            $sheet->setCellValue('D' . $rowIdx, $row->region_code);
            $sheet->setCellValue('E' . $rowIdx, $jenisWilayah);
            $sheet->setCellValue('F' . $rowIdx, $row->nama_sls);
            $sheet->setCellValue('G' . $rowIdx, $row->nama_pencacah);
            $sheet->setCellValue('H' . $rowIdx, $row->nama_pengawas ?: '-');
            $sheet->setCellValue('I' . $rowIdx, $statusQc);
            $sheet->setCellValue('J' . $rowIdx, implode('; ', $rincianList) ?: '-');
            $sheet->setCellValue('K' . $rowIdx, (int) ($row->jml_prelist ?? 0));
            $sheet->setCellValue('L' . $rowIdx, (int) ($row->prelist_keluarga ?? 0));
            $sheet->setCellValue('M' . $rowIdx, (int) ($row->prelist_usaha ?? 0));
            $sheet->setCellValue('N' . $rowIdx, (int) ($row->muatan_murni ?? 0));
            $sheet->setCellValue('O' . $rowIdx, $row->pct_murni_vs_prelist !== null ? (float) $row->pct_murni_vs_prelist : '-');
            $sheet->setCellValue('P' . $rowIdx, $katRasio);
            $sheet->setCellValue('Q' . $rowIdx, (int) ($row->wilkerstat_muatan ?? 0));
            $sheet->setCellValue('R' . $rowIdx, $row->pct_murni_vs_wilkerstat !== null ? (float) $row->pct_murni_vs_wilkerstat : '-');
            $sheet->setCellValue('S' . $rowIdx, $toleransiWilkerstat);
            $sheet->setCellValue('T' . $rowIdx, (int) ($row->beban_saat_ini ?? 0));
            $sheet->setCellValue('U' . $rowIdx, (int) ($row->total_submit ?? 0));
            $sheet->setCellValue('V' . $rowIdx, (float) ($row->pct_submit ?? 0));
            $sheet->setCellValue('W' . $rowIdx, (int) ($row->status_open ?? 0));
            $sheet->setCellValue('X' . $rowIdx, (int) ($row->status_draft ?? 0));
            $sheet->setCellValue('Y' . $rowIdx, (int) ($row->up_ditemukan ?? 0));
            $sheet->setCellValue('Z' . $rowIdx, (int) ($row->uk_ditemukan ?? 0));
            $sheet->setCellValue('AA' . $rowIdx, (int) ($row->total_usaha_se ?? 0));
            $sheet->setCellValue('AB' . $rowIdx, (int) ($row->wilkerstat_usaha ?? 0));
            $sheet->setCellValue('AC' . $rowIdx, (float) ($row->pct_diff_usaha ?? 0));
            $sheet->setCellValue('AD' . $rowIdx, (int) ($row->pk_ditemukan ?? 0));
            $sheet->setCellValue('AE' . $rowIdx, (int) ($row->wilkerstat_kk ?? 0));
            $sheet->setCellValue('AF' . $rowIdx, (int) ($row->total_ganda ?? 0));
            $sheet->setCellValue('AG' . $rowIdx, (int) ($row->bangunan_lainnya ?? 0));

            $sheet->getStyle('A' . $rowIdx . ':B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $rowIdx . ':E' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('K' . $rowIdx . ':N' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('O' . $rowIdx)->getNumberFormat()->setFormatCode('0.00"%"');
            $sheet->getStyle('Q' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('R' . $rowIdx)->getNumberFormat()->setFormatCode('0.00"%"');
            $sheet->getStyle('S' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('T' . $rowIdx . ':U' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('V' . $rowIdx)->getNumberFormat()->setFormatCode('0.00"%"');
            $sheet->getStyle('W' . $rowIdx . ':AB' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('AC' . $rowIdx)->getNumberFormat()->setFormatCode('0.00"%"');
            $sheet->getStyle('AD' . $rowIdx . ':AG' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

            if ($row->is_under_80) {
                $sheet->getStyle('N' . $rowIdx . ':P' . $rowIdx)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('DC2626'));
                $sheet->getStyle('N' . $rowIdx . ':P' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FEF2F2');
            } elseif ($row->is_saved_by_wilkerstat) {
                $sheet->getStyle('Q' . $rowIdx . ':S' . $rowIdx)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0D9488'));
                $sheet->getStyle('Q' . $rowIdx . ':S' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F0FDFA');
            }

            if ($index % 2 == 1) {
                $sheet->getStyle('A' . $rowIdx . ':AG' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F8FAFC');
            }
            $rowIdx++;
        }

        // Apply borders and auto widths
        $lastRow = max(5, $rowIdx - 1);
        $sheet->getStyle("A4:AG{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('CBD5E1');
        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('AA')->setAutoSize(true);
        $sheet->getColumnDimension('AB')->setAutoSize(true);
        $sheet->getColumnDimension('AC')->setAutoSize(true);
        $sheet->getColumnDimension('AD')->setAutoSize(true);
        $sheet->getColumnDimension('AE')->setAutoSize(true);
        $sheet->getColumnDimension('AF')->setAutoSize(true);
        $sheet->getColumnDimension('AG')->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
