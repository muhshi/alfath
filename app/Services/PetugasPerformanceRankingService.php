<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetugasPerformanceRankingService
{
    /**
     * Calculate Petugas Performance Ranking, Target 95% to 20 Aug, SLS Low Usaha Warnings (BKU < 5% / UK < 10%), & Stagnant Warning Signals.
     */
    public function calculateRankingData(Collection $records, ?string $selectedDate): array
    {
        $connName = config()->has('database.connections.fasih') ? 'fasih' : null;
        $db = $connName ? DB::connection($connName) : DB::connection();

        // 1. Target Harian Standar (1.333% per hari dari 15 Juni 2026)
        $startDate = \Carbon\Carbon::parse('2026-06-15');
        $currentDate = !empty($selectedDate) ? \Carbon\Carbon::parse($selectedDate) : \Carbon\Carbon::now();
        $diffDays = max(1, $startDate->diffInDays($currentDate, false) + 1);
        $dynamicTargetPct = min(100.0, round($diffDays * 1.33333, 2));

        // Target Date: 25 August 2026 for 100% Completion Milestone
        $targetDate100 = \Carbon\Carbon::parse('2026-08-25');
        $daysRemainingTo25Aug = max(1, $currentDate->diffInDays($targetDate100, false));

        // 2. Fetch available snapshot dates up to selectedDate (ordered newest to oldest)
        $recentDates = [];
        if (Schema::connection($connName)->hasTable('monitoring_se2026')) {
            $recentDates = $db->table('monitoring_se2026')
                ->whereNotNull('tanggal_tarik')
                ->when(!empty($selectedDate), function ($q) use ($selectedDate) {
                    $q->where('tanggal_tarik', '<=', $selectedDate);
                })
                ->distinct()
                ->orderBy('tanggal_tarik', 'desc')
                ->limit(30)
                ->pluck('tanggal_tarik')
                ->toArray();
        }

        // Fetch submit & draft totals across snapshot dates for all pencacah
        $warningMap = [];
        $submitTodayMap = [];
        $draftTodayMap = [];
        $stagnantDaysMap = [];
        if (count($recentDates) >= 1) {
            $historyData = $db->table('monitoring_se2026')
                ->select(
                    'email_pencacah',
                    'tanggal_tarik',
                    DB::raw('SUM(total_beban - status_open - status_draft) as submit_total'),
                    DB::raw('SUM(status_draft) as draft_total')
                )
                ->whereIn('tanggal_tarik', $recentDates)
                ->groupBy('email_pencacah', 'tanggal_tarik')
                ->get()
                ->groupBy('email_pencacah');

            foreach ($historyData as $email => $rows) {
                $byDateSubmit = $rows->pluck('submit_total', 'tanggal_tarik');
                $byDateDraft = $rows->pluck('draft_total', 'tanggal_tarik');

                $subLatest = $byDateSubmit[$recentDates[0]] ?? 0;
                $subPrev1 = isset($recentDates[1]) ? ($byDateSubmit[$recentDates[1]] ?? null) : null;
                $submitToday = ($subPrev1 !== null) ? max(0, $subLatest - $subPrev1) : 0;
                $submitTodayMap[$email] = $submitToday;

                $draftLatest = $byDateDraft[$recentDates[0]] ?? 0;
                $draftPrev1 = isset($recentDates[1]) ? ($byDateDraft[$recentDates[1]] ?? null) : null;
                $draftToday = ($draftPrev1 !== null) ? max(0, $draftLatest - $draftPrev1) : 0;
                $draftTodayMap[$email] = $draftToday;

                // Calculate consecutive stagnant days/snapshots where submit did not increase
                $stagnantDays = 0;
                if (count($recentDates) >= 2) {
                    for ($i = 0; $i < count($recentDates) - 1; $i++) {
                        $currDate = $recentDates[$i];
                        $prevDate = $recentDates[$i + 1];
                        $currVal = $byDateSubmit[$currDate] ?? null;
                        $prevVal = $byDateSubmit[$prevDate] ?? null;

                        if ($currVal !== null && $prevVal !== null && $currVal <= $prevVal) {
                            $stagnantDays++;
                        } else {
                            break;
                        }
                    }
                }
                $stagnantDaysMap[$email] = $stagnantDays;
            }
        }

        // Fetch all anomaly notes from se2026_anomali_catatan
        $catatanMap = [];
        if (Schema::connection($connName)->hasTable('se2026_anomali_catatan')) {
            $catatanMap = $db->table('se2026_anomali_catatan')
                ->get()
                ->keyBy('region_code')
                ->toArray();
        }

        // 3. SLS Level Usaha Check & Per-Petugas SLS Aggregation for Ranking
        $slsAnomaliMap = [];
        $slsStatsPerPetugas = [];
        if (Schema::connection($connName)->hasTable('monitoring_se2026')) {
            $monitoringService = app(Se2026MonitoringService::class);
            $upDate = $monitoringService->getTargetDateForTable('se2026_usaha_perusahaan', $selectedDate);
            $ukDate = $monitoringService->getTargetDateForTable('se2026_usaha_keluarga', $selectedDate);
            $pkDate = $monitoringService->getTargetDateForTable('se2026_pemutakhiran_keluarga', $selectedDate);

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
                    DB::raw('SUM(ditemukan + keluarga_baru) AS pk_ditemukan')
                )
                ->groupBy('kode');

            $upSub = $db->table('se2026_usaha_perusahaan')
                ->when($upDate, fn ($q) => $q->where('tanggal_data', $upDate))
                ->select('kode', DB::raw('SUM(CAST(status___ditemukan AS SIGNED) + CAST(status___baru AS SIGNED)) AS up_ditemukan'))
                ->groupBy('kode');

            $ukSub = $db->table('se2026_usaha_keluarga')
                ->when($ukDate, fn ($q) => $q->where('tanggal_data', $ukDate))
                ->select('kode', DB::raw('SUM(CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka AS SIGNED) + CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___baru AS SIGNED)) AS uk_ditemukan'))
                ->groupBy('kode');

            $slsRows = $db->table('monitoring_se2026 as m')
                ->leftJoinSub($sipwSub, 'sipw', 'm.region_code', '=', 'sipw.id_subsls')
                ->leftJoinSub($pkSub, 'pk', 'm.region_code', '=', 'pk.kode')
                ->leftJoinSub($upSub, 'up', 'm.region_code', '=', 'up.kode')
                ->leftJoinSub($ukSub, 'uk', 'm.region_code', '=', 'uk.kode')
                ->select([
                    'm.email_pencacah',
                    'm.region_code',
                    DB::raw('COALESCE(
                        NULLIF(sipw.nama_sls, "-"),
                        NULLIF(pk.sub_sls, "-"),
                        NULLIF(pk.sub_sls, "TIDAK DIKETAHUI"),
                        CONCAT("SLS ", m.region_code)
                    ) as nama_sls'),
                    DB::raw('SUM(m.total_beban) as beban_sls'),
                    DB::raw('(IFNULL(SUM(m.total_beban), 0) - IFNULL(SUM(m.status_open), 0) - IFNULL(SUM(m.status_draft), 0)) as submit_sls'),
                    DB::raw('IFNULL(SUM(up.up_ditemukan), 0) as up_sls'),
                    DB::raw('IFNULL(SUM(uk.uk_ditemukan), 0) as uk_sls'),
                    DB::raw('IFNULL(SUM(pk.pk_ditemukan), 0) as pk_sls'),
                    DB::raw('(IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) as muatan_murni_sls'),
                    DB::raw('IFNULL(sipw.wilkerstat_kk, 0) as wilkerstat_kk'),
                    DB::raw('IFNULL(sipw.wilkerstat_bku, 0) as wilkerstat_bku'),
                    DB::raw('IFNULL(sipw.wilkerstat_usaha, 0) as wilkerstat_usaha'),
                ])
                ->when(!empty($selectedDate), function ($q) use ($selectedDate) {
                    $q->where('m.tanggal_tarik', '=', $selectedDate);
                })
                ->groupBy('m.email_pencacah', 'm.region_code', 'sipw.nama_sls', 'sipw.wilkerstat_kk', 'sipw.wilkerstat_bku', 'sipw.wilkerstat_usaha', 'pk.sub_sls')
                ->get();

            foreach ($slsRows as $sr) {
                $email = $sr->email_pencacah;
                $totalUsahaSe = (int) $sr->up_sls + (int) $sr->uk_sls;
                $wilkerstatUsaha = (int) $sr->wilkerstat_usaha;

                if (!isset($slsStatsPerPetugas[$email])) {
                    $slsStatsPerPetugas[$email] = [
                        'total_sls' => 0,
                        'sls_with_usaha' => 0,
                        'sls_usaha_optimal' => 0,
                    ];
                }

                $slsStatsPerPetugas[$email]['total_sls']++;
                if ($wilkerstatUsaha > 0) {
                    $slsStatsPerPetugas[$email]['sls_with_usaha']++;
                    if ($totalUsahaSe >= $wilkerstatUsaha) {
                        $slsStatsPerPetugas[$email]['sls_usaha_optimal']++;
                    }
                } else {
                    // SLS tanpa target wilkerstat usaha (dianggap optimal jika totalUsahaSe >= 0)
                    $slsStatsPerPetugas[$email]['sls_usaha_optimal']++;
                }

                // SLS Anomali Check (Dual Thresholds: UP < 5% & UK < 10% dari Muatan Murni SLS)
                if ($sr->muatan_murni_sls > 0) {
                    $pctUp = round(($sr->up_sls / $sr->muatan_murni_sls) * 100, 1);
                    $pctUk = round(($sr->uk_sls / $sr->muatan_murni_sls) * 100, 1);

                    $isLowUp = $pctUp < 5.0;
                    $isLowUk = $pctUk < 10.0;

                    // Warning if Usaha Perusahaan < 5% OR Usaha Keluarga < 10% of Muatan Murni
                    if ($isLowUp || $isLowUk) {
                        $catatanObj = $catatanMap[$sr->region_code] ?? null;

                        $diffKkPct = $sr->wilkerstat_kk > 0
                            ? round((($sr->pk_sls - $sr->wilkerstat_kk) / $sr->wilkerstat_kk) * 100, 1)
                            : 0;

                        $diffUsahaPct = $sr->wilkerstat_usaha > 0
                            ? round((($totalUsahaSe - $sr->wilkerstat_usaha) / $sr->wilkerstat_usaha) * 100, 1)
                            : 0;

                        $hasWarningDiffKk = $sr->wilkerstat_kk > 0 && $diffKkPct < -5.0;
                        $hasWarningDiffUsaha = $sr->wilkerstat_usaha > 0 && $diffUsahaPct < -5.0;

                        $slsAnomaliMap[$email][] = [
                            'region_code' => $sr->region_code,
                            'nama_sls' => $sr->nama_sls,
                            'submit' => $sr->submit_sls,
                            'muatan_murni' => $sr->muatan_murni_sls,
                            'up_sls' => $sr->up_sls,
                            'uk_sls' => $sr->uk_sls,
                            'pk_sls' => $sr->pk_sls,
                            'total_usaha_se' => $totalUsahaSe,
                            'wilkerstat_kk' => (int) $sr->wilkerstat_kk,
                            'wilkerstat_usaha' => (int) $sr->wilkerstat_usaha,
                            'diff_kk_pct' => $diffKkPct,
                            'diff_usaha_pct' => $diffUsahaPct,
                            'has_warning_diff_kk' => $hasWarningDiffKk,
                            'has_warning_diff_usaha' => $hasWarningDiffUsaha,
                            'is_usaha_optimal' => $wilkerstatUsaha > 0 ? ($totalUsahaSe >= $wilkerstatUsaha) : true,
                            'pct_up' => $pctUp,
                            'pct_uk' => $pctUk,
                            'is_low_up' => $isLowUp,
                            'is_low_uk' => $isLowUk,
                            'status_tindak_lanjut' => $catatanObj ? $catatanObj->status : 'belum',
                            'catatan_petugas' => $catatanObj ? $catatanObj->catatan : null,
                            'catatan_admin' => $catatanObj ? $catatanObj->catatan_admin : null,
                            'nama_petugas_catatan' => $catatanObj ? $catatanObj->nama_petugas : null,
                        ];
                    }
                }
            }
        }

        // 4. Process Each Petugas
        $rankingList = collect();
        foreach ($records as $row) {
            $beban = max(1, $row->beban_saat_ini);
            $submit = $row->total_submit;
            $capaianPct = $row->pct_submit;
            $muatanMurni = $row->muatan_murni;

            // Target 100% Calculation for 25 August 2026
            $target100Count = (int) $beban;
            $neededTo100 = max(0, $target100Count - $submit);
            if ($capaianPct >= 100.0 || $neededTo100 <= 0) {
                $ketTarget100 = "✅ Selesai (100%)";
                $lajuHarian100 = 0;
            } else {
                $lajuHarian100 = (int) ceil($neededTo100 / $daysRemainingTo25Aug);
                $ketTarget100 = "🎯 +{$lajuHarian100} submit/hari s.d. 25 Agt (Sisa {$neededTo100})";
            }

            // Warning Usaha List (UP < 5% or UK < 10% & muatan_murni > 0)
            if ($muatanMurni > 0) {
                $anomaliSlsList = $slsAnomaliMap[$row->email_pencacah] ?? [];
            } else {
                $anomaliSlsList = [];
            }
            $hasWarningUsaha = count($anomaliSlsList) > 0;

            // ==========================================
            // 5-PILLAR PERFORMANCE RANKING FORMULA (0 - 100)
            // ==========================================

            // 1. Progress Score (Max 30 Poin)
            if ($capaianPct < $dynamicTargetPct) {
                $progressScore = ($dynamicTargetPct > 0) ? ($capaianPct / $dynamicTargetPct) * 20.0 : 0;
            } else {
                $denom = max(0.01, 100.0 - $dynamicTargetPct);
                $extra = min(1.0, ($capaianPct - $dynamicTargetPct) / $denom);
                $progressScore = 20.0 + ($extra * 10.0);
            }
            $progressScore = min(30.0, max(0.0, $progressScore));

            // 2. Kualitas Probing Usaha Total vs Wilkerstat Petugas (Max 25 Poin)
            $totalUsaha = (int) ($row->total_usaha_se ?? ((int) ($row->jumlah_usaha_ditemukan ?? 0) + (int) ($row->jumlah_usaha_keluarga ?? 0)));
            $wilkerstatUsaha = (int) ($row->wilkerstat_usaha ?? 0);
            if ($wilkerstatUsaha > 0) {
                $usahaTotalScore = min(25.0, ($totalUsaha / $wilkerstatUsaha) * 25.0);
            } else {
                $usahaTotalScore = 25.0; // Baseline penuh jika wilayah non-usaha
            }
            $usahaTotalScore = min(25.0, max(0.0, $usahaTotalScore));

            // 3. Ketelitian SLS Probing / SLS Optimal Rate (Max 20 Poin)
            $slsStats = $slsStatsPerPetugas[$row->email_pencacah] ?? ['total_sls' => 1, 'sls_with_usaha' => 0, 'sls_usaha_optimal' => 1];
            $totalSlsWithUsaha = $slsStats['sls_with_usaha'];
            $totalSlsOptimal = $slsStats['sls_usaha_optimal'];
            if ($totalSlsWithUsaha > 0) {
                $slsOptimalRatio = min(1.0, $totalSlsOptimal / $totalSlsWithUsaha);
                $usahaSlsScore = $slsOptimalRatio * 20.0;
            } else {
                $usahaSlsScore = 20.0;
            }
            $usahaSlsScore = min(20.0, max(0.0, $usahaSlsScore));

            // 4. Spotting Usaha Keluarga / Rasio UK per KK (Max 10 Poin)
            $ukCount = (int) ($row->jumlah_usaha_keluarga ?? 0);
            $kkCount = (int) ($row->jumlah_keluarga_ditemukan ?? 0);
            if ($kkCount > 0) {
                $ratioUkKk = $ukCount / $kkCount;
                // Target acuan: rasio UK/KK >= 15% (0.15) mendapat poin maksimal 10
                $spottingUkScore = min(10.0, ($ratioUkKk / 0.15) * 10.0);
            } else {
                $spottingUkScore = ($ukCount > 0) ? 10.0 : 5.0;
            }
            $spottingUkScore = min(10.0, max(0.0, $spottingUkScore));

            // 5. Volume Score - Muatan Murni (KK + BKU) (Max 15 Poin)
            $volumeScore = min(15.0, ($muatanMurni / 350.0) * 15.0);

            // Total Score (0 - 100)
            $skorKinerja = round($progressScore + $usahaTotalScore + $usahaSlsScore + $spottingUkScore + $volumeScore, 1);

            $submitToday = $submitTodayMap[$row->email_pencacah] ?? 0;
            $draftToday = $draftTodayMap[$row->email_pencacah] ?? 0;
            $stagnantDays = $stagnantDaysMap[$row->email_pencacah] ?? 0;

            // Warning logic:
            if ($capaianPct >= 100.0 || $row->belum_dikerjakan <= 0) {
                $warning = 'completed';
            } elseif ($capaianPct >= $dynamicTargetPct) {
                // Petugas sudah di atas/sesuai Target Harian Standar (On-Track)
                $warning = 'normal';
            } elseif ($stagnantDays >= 1) {
                // Submit 0/hari (tidak ada penambahan submit dibanding snapshot kemarin) & di bawah target
                $warning = 'stagnant';
            } elseif ($submitToday < $lajuHarian100) {
                // Ada submit (>0) tetapi di bawah target laju harian s.d. 25 Agustus
                $warning = 'slow_progress';
            } else {
                // Laju submit harian memadai (>= target laju harian 100%)
                $warning = 'normal';
            }

            // Safety Rule: Capaian >= Dynamic Target cannot be Malas
            if ($capaianPct >= $dynamicTargetPct) {
                if ($skorKinerja >= 80.0 || ($muatanMurni >= 300 && $totalUsaha >= 60)) {
                    $katCode = '1_SANGAT_RAJIN';
                    $katLabel = '1. Sangat Rajin (Appreciated 🌟)';
                    $katBadge = 'bg-success text-white';
                    $rekomendasi = 'Apresiasi & Bebas Beban Tambahan';
                } elseif ($skorKinerja >= 65.0 || ($muatanMurni >= 250 && $totalUsaha >= 40)) {
                    $katCode = '2_RAJIN';
                    $katLabel = '2. Rajin (Good Performer 🟢)';
                    $katBadge = 'bg-success-lt text-success';
                    $rekomendasi = 'Pertahankan Kinerja Baik';
                } else {
                    $katCode = '3_CUKUP';
                    $katLabel = '3. Cukup / Standar (Moderate 🟡)';
                    $katBadge = 'bg-warning-lt text-warning';
                    $rekomendasi = 'Monitoring Regular PML (Tingkatkan Usaha/Volume)';
                }
            } else {
                if ($skorKinerja >= 75.0 && $capaianPct >= max(0, $dynamicTargetPct - 5.0)) {
                    $katCode = '2_RAJIN';
                    $katLabel = '2. Rajin (Good Performer 🟢)';
                    $katBadge = 'bg-success-lt text-success';
                    $rekomendasi = 'Tingkatkan Progres ke Target Harian';
                } elseif ($capaianPct >= max(0, $dynamicTargetPct - 10.0) || $skorKinerja >= 60.0) {
                    $katCode = '3_CUKUP';
                    $katLabel = '3. Cukup / Standar (Moderate 🟡)';
                    $katBadge = 'bg-warning-lt text-warning';
                    $rekomendasi = 'Monitoring Regular PML (Tingkatkan Progres)';
                } elseif ($capaianPct >= max(0, $dynamicTargetPct - 20.0) && $warning !== 'stagnant') {
                    $katCode = '4_MALAS';
                    $katLabel = '4. Malas / Kurang (Low Performer ⚠️)';
                    $katBadge = 'bg-orange-lt text-orange';
                    $rekomendasi = 'Pengawasan Ketat & Target Harian PML';
                } else {
                    $katCode = '5_SANGAT_MALAS';
                    $katLabel = '5. Sangat Malas (Critical Underperformer 🔴)';
                    $katBadge = 'bg-danger text-white';
                    $rekomendasi = 'Teguran Langsung & Pendampingan Lapangan PML';
                }
            }

            // Bangunan Kosong / Bangunan Lainnya Calculation & Warning Threshold (>= 5% of total submit)
            $bangunanLainnya = max(0, $submit - $muatanMurni - (($row->usaha_tidak_ditemukan ?? 0) + ($row->keluarga_tidak_ditemukan ?? 0)));
            $pctBangunanLainnya = $submit > 0 ? round(($bangunanLainnya / $submit) * 100, 2) : 0;
            $hasWarningBangunanLainnya = $pctBangunanLainnya >= 5.0;

            $item = clone $row;
            $item->dynamic_target_pct = $dynamicTargetPct;
            $item->progress_score = round($progressScore, 1);
            $item->usaha_total_score = round($usahaTotalScore, 1);
            $item->usaha_sls_score = round($usahaSlsScore, 1);
            $item->spotting_uk_score = round($spottingUkScore, 1);
            $item->volume_score = round($volumeScore, 1);
            $item->total_usaha_se = $totalUsaha;
            $item->sls_total_with_usaha = $totalSlsWithUsaha;
            $item->sls_usaha_optimal = $totalSlsOptimal;
            $item->skor_kinerja = $skorKinerja;
            $item->kat_code = $katCode;
            $item->kat_label = $katLabel;
            $item->kat_badge = $katBadge;
            $item->rekomendasi = $rekomendasi;
            $item->warning_status = $warning;
            $item->submit_today = $submitToday;
            $item->draft_today = $draftToday;
            $item->stagnant_days = $stagnantDays;

            // Bangunan Kosong / Bangunan Lainnya Attributes
            $item->bangunan_lainnya = $bangunanLainnya;
            $item->pct_bangunan_lainnya = $pctBangunanLainnya;
            $item->has_warning_bangunan_lainnya = $hasWarningBangunanLainnya;

            // Target 100% & Warning Usaha Attributes
            $item->needed_to_100 = $neededTo100;
            $item->laju_harian_100 = $lajuHarian100;
            $item->ket_target_100 = $ketTarget100;
            $item->days_remaining_to_25aug = $daysRemainingTo25Aug;
            $item->has_warning_usaha = $hasWarningUsaha;
            $item->anomali_sls_list = $anomaliSlsList;

            // Count follow-up statuses per pencacah
            $cntBelum = 0;
            $cntPending = 0;
            $cntApproved = 0;
            foreach ($anomaliSlsList as $an) {
                if ($an['status_tindak_lanjut'] === 'approved') {
                    $cntApproved++;
                } elseif ($an['status_tindak_lanjut'] === 'pending') {
                    $cntPending++;
                } else {
                    $cntBelum++;
                }
            }
            $item->cnt_anomali_belum = $cntBelum;
            $item->cnt_anomali_pending = $cntPending;
            $item->cnt_anomali_approved = $cntApproved;

            $rankingList->push($item);
        }

        // Primary Sort: % Capaian Submit (pct_submit) DESC
        // Secondary Sort: Skor Kinerja (skor_kinerja) DESC
        // Tertiary Sort: Muatan Murni (muatan_murni) DESC
        $sortedRanking = $rankingList->sort(function ($a, $b) {
            if ($a->pct_submit == $b->pct_submit) {
                if ($a->skor_kinerja == $b->skor_kinerja) {
                    return $b->muatan_murni <=> $a->muatan_murni;
                }
                return $b->skor_kinerja <=> $a->skor_kinerja;
            }
            return $b->pct_submit <=> $a->pct_submit;
        })->values();

        $rankingSummary = [
            'dynamic_target_pct' => $dynamicTargetPct,
            'days_remaining_to_25aug' => $daysRemainingTo25Aug,
            'cnt_srajin' => $sortedRanking->where('kat_code', '1_SANGAT_RAJIN')->count(),
            'cnt_rajin' => $sortedRanking->where('kat_code', '2_RAJIN')->count(),
            'cnt_cukup' => $sortedRanking->where('kat_code', '3_CUKUP')->count(),
            'cnt_malas' => $sortedRanking->where('kat_code', '4_MALAS')->count(),
            'cnt_smalas' => $sortedRanking->where('kat_code', '5_SANGAT_MALAS')->count(),
            'cnt_stagnant' => $sortedRanking->where('warning_status', 'stagnant')->count(),
            'cnt_slow' => $sortedRanking->where('warning_status', 'slow_progress')->count(),
            'cnt_warning_usaha' => $sortedRanking->where('has_warning_usaha', true)->count(),
            'cnt_warning_bangunan_lainnya' => $sortedRanking->where('has_warning_bangunan_lainnya', true)->count(),
        ];

        return [
            'rankingRecords' => $sortedRanking,
            'dynamicTargetPct' => $dynamicTargetPct,
            'rankingSummary' => $rankingSummary,
        ];
    }
}
