<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetugasPerformanceRankingService
{
    /**
     * Calculate Petugas Performance Ranking, Target 95% to 20 Aug, SLS Low Usaha Warnings (UP < 5% / UK < 10%), & 3-Day Warning Signals.
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

        // Target Date: 20 August 2026 for 95% Milestone
        $targetDate95 = \Carbon\Carbon::parse('2026-08-20');
        $daysRemainingTo20Aug = max(1, $currentDate->diffInDays($targetDate95, false));

        // 2. Fetch 3 latest snapshot dates up to selectedDate
        $recentDates = [];
        if (Schema::connection($connName)->hasTable('monitoring_se2026')) {
            $recentDates = $db->table('monitoring_se2026')
                ->whereNotNull('tanggal_tarik')
                ->when(!empty($selectedDate), function ($q) use ($selectedDate) {
                    $q->where('tanggal_tarik', '<=', $selectedDate);
                })
                ->distinct()
                ->orderBy('tanggal_tarik', 'desc')
                ->limit(3)
                ->pluck('tanggal_tarik')
                ->toArray();
        }

        // Fetch submit totals across recent 3 snapshot dates for all pencacah
        $warningMap = [];
        if (count($recentDates) >= 2) {
            $historyData = $db->table('monitoring_se2026')
                ->select(
                    'email_pencacah',
                    'tanggal_tarik',
                    DB::raw('SUM(total_beban - status_open - status_draft) as submit_total')
                )
                ->whereIn('tanggal_tarik', $recentDates)
                ->groupBy('email_pencacah', 'tanggal_tarik')
                ->get()
                ->groupBy('email_pencacah');

            foreach ($historyData as $email => $rows) {
                $byDate = $rows->pluck('submit_total', 'tanggal_tarik');
                $subLatest = $byDate[$recentDates[0]] ?? 0;
                $subPrev1 = $byDate[$recentDates[1]] ?? null;
                $subPrev2 = isset($recentDates[2]) ? ($byDate[$recentDates[2]] ?? null) : null;

                if ($subPrev1 !== null && $subLatest <= $subPrev1 && ($subPrev2 === null || $subPrev1 <= $subPrev2)) {
                    $warningMap[$email] = 'stagnant_3d'; // 🚨 3 Hari Stagnan
                } elseif ($subPrev1 !== null && ($subLatest - $subPrev1) < 5) {
                    $warningMap[$email] = 'slow_progress'; // ⚠️ Progres Lambat
                } else {
                    $warningMap[$email] = 'normal'; // ✅ Progres Lancar
                }
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

        // 3. SLS Level Usaha Check (Dual Thresholds: UP < 5% & UK < 10% dari Muatan Murni SLS)
        $slsAnomaliMap = [];
        if (Schema::connection($connName)->hasTable('monitoring_se2026')) {
            $sipwSub = $db->table('sipw')
                ->select('id_subsls', DB::raw('MAX(nama_sls) as nama_sls'))
                ->groupBy('id_subsls');

            $pkSub = $db->table('se2026_pemutakhiran_keluarga')
                ->select(
                    'kode',
                    DB::raw('MAX(sub_sls) as sub_sls'),
                    DB::raw('SUM(ditemukan + keluarga_baru) AS pk_ditemukan')
                )
                ->groupBy('kode');

            $upSub = $db->table('se2026_usaha_perusahaan')
                ->select('kode', DB::raw('SUM(CAST(status___ditemukan AS SIGNED) + CAST(status___baru AS SIGNED)) AS up_ditemukan'))
                ->groupBy('kode');

            $ukSub = $db->table('se2026_usaha_keluarga')
                ->select('kode', DB::raw('SUM(CAST(jumlah_usaha_keluarga_menurut_status_keberadaan_usaha___ditemuka AS SIGNED)) AS uk_ditemukan'))
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
                    DB::raw('(IFNULL(SUM(up.up_ditemukan), 0) + IFNULL(SUM(pk.pk_ditemukan), 0)) as muatan_murni_sls'),
                ])
                ->when(!empty($selectedDate), function ($q) use ($selectedDate) {
                    $q->where('m.tanggal_tarik', '=', $selectedDate);
                })
                ->groupBy('m.email_pencacah', 'm.region_code', 'sipw.nama_sls', 'pk.sub_sls')
                ->get();

            foreach ($slsRows as $sr) {
                // Ignore SLS if muatan murni is empty (0)
                if ($sr->muatan_murni_sls > 0) {
                    $pctUp = round(($sr->up_sls / $sr->muatan_murni_sls) * 100, 1);
                    $pctUk = round(($sr->uk_sls / $sr->muatan_murni_sls) * 100, 1);

                    $isLowUp = $pctUp < 5.0;
                    $isLowUk = $pctUk < 10.0;

                    // Warning if Usaha Perusahaan < 5% OR Usaha Keluarga < 10% of Muatan Murni
                    if ($isLowUp || $isLowUk) {
                        $catatanObj = $catatanMap[$sr->region_code] ?? null;

                        $slsAnomaliMap[$sr->email_pencacah][] = [
                            'region_code' => $sr->region_code,
                            'nama_sls' => $sr->nama_sls,
                            'submit' => $sr->submit_sls,
                            'muatan_murni' => $sr->muatan_murni_sls,
                            'up_sls' => $sr->up_sls,
                            'uk_sls' => $sr->uk_sls,
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

            // Target 95% Calculation for 20 August 2026
            $target95Count = (int) ceil($beban * 0.95);
            $neededTo95 = max(0, $target95Count - $submit);
            if ($capaianPct >= 95.0) {
                $ketTarget95 = "✅ Selesai (>= 95%)";
                $lajuHarian95 = 0;
            } else {
                $lajuHarian95 = (int) ceil($neededTo95 / $daysRemainingTo20Aug);
                $ketTarget95 = "🎯 +{$lajuHarian95} submit/hari s.d. 20 Agt (Sisa {$neededTo95})";
            }

            // Warning Usaha List (UP < 5% or UK < 10% & muatan_murni > 0)
            if ($muatanMurni > 0) {
                $anomaliSlsList = $slsAnomaliMap[$row->email_pencacah] ?? [];
            } else {
                $anomaliSlsList = [];
            }
            $hasWarningUsaha = count($anomaliSlsList) > 0;

            // Progress Score (Max 45)
            if ($capaianPct < $dynamicTargetPct) {
                $progressScore = ($dynamicTargetPct > 0) ? ($capaianPct / $dynamicTargetPct) * 31.5 : 0;
            } else {
                $denom = max(0.01, 100.0 - $dynamicTargetPct);
                $extra = min(1.0, ($capaianPct - $dynamicTargetPct) / $denom);
                $progressScore = 31.5 + ($extra * 13.5);
            }
            $progressScore = min(45.0, max(0.0, $progressScore));

            // Volume Score (Max 55)
            $volumeScore = min(55.0, ($muatanMurni / 350.0) * 55.0);

            // Total Score
            $skorKinerja = round($progressScore + $volumeScore, 1);

            // Warning logic:
            if ($capaianPct >= 100.0 || $row->belum_dikerjakan <= 0) {
                $warning = 'completed';
            } elseif ($capaianPct >= $dynamicTargetPct) {
                $warning = 'normal';
            } else {
                $warning = $warningMap[$row->email_pencacah] ?? 'normal';
            }

            // Safety Rule: Capaian >= Dynamic Target cannot be Malas
            if ($capaianPct >= $dynamicTargetPct) {
                if ($muatanMurni >= 330) {
                    $katCode = '1_SANGAT_RAJIN';
                    $katLabel = '1. Sangat Rajin (Appreciated 🌟)';
                    $katBadge = 'bg-success text-white';
                    $rekomendasi = 'Apresiasi & Bebas Beban Tambahan';
                } elseif ($muatanMurni >= 280) {
                    $katCode = '2_RAJIN';
                    $katLabel = '2. Rajin (Good Performer 🟢)';
                    $katBadge = 'bg-success-lt text-success';
                    $rekomendasi = 'Pertahankan Kinerja Baik';
                } else {
                    $katCode = '3_CUKUP';
                    $katLabel = '3. Cukup / Standar (Moderate 🟡)';
                    $katBadge = 'bg-warning-lt text-warning';
                    $rekomendasi = 'Monitoring Regular PML (On-Track)';
                }
            } else {
                if ($muatanMurni >= 330 && $capaianPct >= max(0, $dynamicTargetPct - 5.0)) {
                    $katCode = '2_RAJIN';
                    $katLabel = '2. Rajin (Good Performer 🟢)';
                    $katBadge = 'bg-success-lt text-success';
                    $rekomendasi = 'Tingkatkan Progres ke Target Harian';
                } elseif ($capaianPct >= max(0, $dynamicTargetPct - 10.0)) {
                    $katCode = '3_CUKUP';
                    $katLabel = '3. Cukup / Standar (Moderate 🟡)';
                    $katBadge = 'bg-warning-lt text-warning';
                    $rekomendasi = 'Monitoring Regular PML (Tingkatkan Progres)';
                } elseif ($capaianPct >= max(0, $dynamicTargetPct - 20.0) && $warning !== 'stagnant_3d') {
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

            $item = clone $row;
            $item->dynamic_target_pct = $dynamicTargetPct;
            $item->skor_kinerja = $skorKinerja;
            $item->kat_code = $katCode;
            $item->kat_label = $katLabel;
            $item->kat_badge = $katBadge;
            $item->rekomendasi = $rekomendasi;
            $item->warning_status = $warning;

            // Target 95% & Warning Usaha Attributes
            $item->needed_to_95 = $neededTo95;
            $item->laju_harian_95 = $lajuHarian95;
            $item->ket_target_95 = $ketTarget95;
            $item->days_remaining_to_20aug = $daysRemainingTo20Aug;
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
            'days_remaining_to_20aug' => $daysRemainingTo20Aug,
            'cnt_srajin' => $sortedRanking->where('kat_code', '1_SANGAT_RAJIN')->count(),
            'cnt_rajin' => $sortedRanking->where('kat_code', '2_RAJIN')->count(),
            'cnt_cukup' => $sortedRanking->where('kat_code', '3_CUKUP')->count(),
            'cnt_malas' => $sortedRanking->where('kat_code', '4_MALAS')->count(),
            'cnt_smalas' => $sortedRanking->where('kat_code', '5_SANGAT_MALAS')->count(),
            'cnt_stagnant' => $sortedRanking->where('warning_status', 'stagnant_3d')->count(),
            'cnt_slow' => $sortedRanking->where('warning_status', 'slow_progress')->count(),
            'cnt_warning_usaha' => $sortedRanking->where('has_warning_usaha', true)->count(),
        ];

        return [
            'rankingRecords' => $sortedRanking,
            'dynamicTargetPct' => $dynamicTargetPct,
            'rankingSummary' => $rankingSummary,
        ];
    }
}
