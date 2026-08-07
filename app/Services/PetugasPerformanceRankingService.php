<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetugasPerformanceRankingService
{
    /**
     * Calculate Petugas Performance Ranking & 3-Day Warning Signals.
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

        // 3. Process Each Petugas
        $rankingList = collect();
        foreach ($records as $row) {
            $beban = max(1, $row->beban_saat_ini);
            $submit = $row->total_submit;
            $capaianPct = $row->pct_submit;
            $muatanMurni = $row->muatan_murni;

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

            $warning = $warningMap[$row->email_pencacah] ?? 'normal';

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
                // Capaian < Dynamic Target
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

            $rankingList->push($item);
        }

        // Sort ranking list by Skor Kinerja DESC, then Muatan Murni DESC
        $sortedRanking = $rankingList->sort(function ($a, $b) {
            if ($a->skor_kinerja == $b->skor_kinerja) {
                return $b->muatan_murni <=> $a->muatan_murni;
            }
            return $b->skor_kinerja <=> $a->skor_kinerja;
        })->values();

        $rankingSummary = [
            'dynamic_target_pct' => $dynamicTargetPct,
            'cnt_srajin' => $sortedRanking->where('kat_code', '1_SANGAT_RAJIN')->count(),
            'cnt_rajin' => $sortedRanking->where('kat_code', '2_RAJIN')->count(),
            'cnt_cukup' => $sortedRanking->where('kat_code', '3_CUKUP')->count(),
            'cnt_malas' => $sortedRanking->where('kat_code', '4_MALAS')->count(),
            'cnt_smalas' => $sortedRanking->where('kat_code', '5_SANGAT_MALAS')->count(),
            'cnt_stagnant' => $sortedRanking->where('warning_status', 'stagnant_3d')->count(),
            'cnt_slow' => $sortedRanking->where('warning_status', 'slow_progress')->count(),
        ];

        return [
            'rankingRecords' => $sortedRanking,
            'dynamicTargetPct' => $dynamicTargetPct,
            'rankingSummary' => $rankingSummary,
        ];
    }
}
