<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetugasTimelineService
{
    public function __construct(
        protected Se2026MonitoringService $monitoringService
    ) {}

    /**
     * Build Petugas Daily Submit Heatmap & Timeline Matrix (Ultra Fast In-Memory Filtering)
     */
    public function getTimelineData(Request $request): array
    {
        $connName = config()->has('database.connections.fasih') ? 'fasih' : null;
        $db = $connName ? DB::connection($connName) : DB::connection();

        $search = trim((string) $request->get('search', ''));
        $kodekec = trim((string) $request->get('kodekec', ''));

        $cacheStore = Cache::store('file');

        // Allow forcing fresh cache refresh via ?fresh=1 or ?refresh=1
        if ($request->has('fresh') || $request->has('refresh')) {
            try {
                $cacheStore->increment('se2026_dash_version');
            } catch (\Throwable $e) {}
        }

        $cacheVersion = 2;
        try {
            $cacheVersion = (int) $cacheStore->get('se2026_dash_version', 2);
        } catch (\Throwable $e) {}

        // Cache the entire base snapshot dataset (aggregated per email & date) ONCE
        $baseCacheKey = "se2026_timeline_base_data_v{$cacheVersion}";

        $baseData = $cacheStore->remember($baseCacheKey, now()->addHours(12), function () use ($connName, $db) {
            $availableDates = [];
            if (Schema::connection($connName)->hasTable('monitoring_se2026')) {
                $availableDates = $db->table('monitoring_se2026')
                    ->whereNotNull('tanggal_tarik')
                    ->distinct()
                    ->orderBy('tanggal_tarik', 'asc')
                    ->pluck('tanggal_tarik')
                    ->map(fn($d) => (string) $d)
                    ->toArray();
            }

            if (empty($availableDates)) {
                return [
                    'availableDates' => [],
                    'calendarDates' => [],
                    'pulledDatesSet' => [],
                    'petugasData' => [],
                ];
            }

            $pulledDatesSet = array_flip($availableDates);

            // Generate full continuous calendar dates from earliest available date to latest
            $minDateStr = $availableDates[0];
            $maxDateStr = end($availableDates);
            $period = CarbonPeriod::create($minDateStr, $maxDateStr);
            
            $calendarDates = [];
            foreach ($period as $date) {
                $calendarDates[] = $date->format('Y-m-d');
            }

            // Single query to aggregate cumulative submit totals per email_pencacah per snapshot date
            $rawRows = $db->table('monitoring_se2026 as m')
                ->leftJoin('master_petugas as p', 'm.email_pencacah', '=', 'p.email')
                ->select(
                    'm.email_pencacah',
                    DB::raw('IFNULL(p.nama_lengkap, m.email_pencacah) as nama_pencacah'),
                    DB::raw('MIN(LEFT(m.region_code, 7)) as kode_kec'),
                    'm.tanggal_tarik',
                    DB::raw('SUM(m.total_beban - m.status_open - m.status_draft) as submit_cumulative')
                )
                ->whereIn('m.tanggal_tarik', $availableDates)
                ->groupBy('m.email_pencacah', 'p.nama_lengkap', 'm.tanggal_tarik')
                ->get();

            $petugasData = [];
            foreach ($rawRows as $row) {
                $email = $row->email_pencacah;
                if (!isset($petugasData[$email])) {
                    $petugasData[$email] = [
                        'email' => $email,
                        'nama' => $row->nama_pencacah,
                        'kode_kec' => $row->kode_kec,
                        'date_cum_map' => [],
                    ];
                }
                $petugasData[$email]['date_cum_map'][(string) $row->tanggal_tarik] = (int) $row->submit_cumulative;
            }

            return [
                'availableDates' => $availableDates,
                'calendarDates' => $calendarDates,
                'pulledDatesSet' => $pulledDatesSet,
                'petugasData' => $petugasData,
            ];
        });

        $kecNameMap = $this->monitoringService->getKecNameMap();

        $availableDates = $baseData['availableDates'];
        $calendarDates = $baseData['calendarDates'];
        $pulledDatesSet = $baseData['pulledDatesSet'];
        $allPetugasData = $baseData['petugasData'];

        if (empty($availableDates)) {
            return [
                'records' => collect([]),
                'calendarDates' => [],
                'pulledDatesSet' => [],
                'kecNameMap' => $kecNameMap,
                'summary' => [
                    'totalPetugas' => 0,
                    'avgHariKerja' => 0,
                    'totalSubmit' => 0,
                    'avgSubmitPerHari' => 0,
                ],
                'kodekec' => $kodekec,
                'search' => $search,
            ];
        }

        // Fast In-Memory Filtering & Matrix Calculation (~2ms execution time)
        $records = collect();
        $totalSubmitsAll = 0;
        $totalWorkingDaysAll = 0;

        foreach ($allPetugasData as $email => $meta) {
            // Apply kecamatan filter
            if (!empty($kodekec) && $meta['kode_kec'] !== $kodekec) {
                continue;
            }

            // Apply search filter
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $matchName = str_contains(strtolower($meta['nama']), $searchLower);
                $matchEmail = str_contains(strtolower($email), $searchLower);
                if (!$matchName && !$matchEmail) {
                    continue;
                }
            }

            $dateCumMap = $meta['date_cum_map'];
            $dailySubmits = [];

            $prevCum = null;
            $workingDays = 0;
            $pulledDaysCount = 0;
            $lastCumulativeSubmit = 0;

            foreach ($calendarDates as $calDate) {
                $isPulled = isset($pulledDatesSet[$calDate]);

                if (!$isPulled) {
                    $dailySubmits[$calDate] = [
                        'status' => 'no_data',
                        'val' => null,
                        'label' => 'Data tidak ditarik',
                    ];
                } else {
                    $pulledDaysCount++;
                    $cumCurrent = $dateCumMap[$calDate] ?? ($prevCum ?? 0);
                    $lastCumulativeSubmit = max($lastCumulativeSubmit, $cumCurrent);

                    if ($prevCum === null) {
                        $dailyVal = max(0, $cumCurrent);
                    } else {
                        $dailyVal = max(0, $cumCurrent - $prevCum);
                    }

                    if ($dailyVal > 0) {
                        $workingDays++;
                    }

                    $dailySubmits[$calDate] = [
                        'status' => 'ok',
                        'val' => $dailyVal,
                        'label' => number_format($dailyVal, 0, ',', '.'),
                    ];

                    $prevCum = $cumCurrent;
                }
            }

            $totalSubmit = $lastCumulativeSubmit;
            $avgSubmitPerWorkingDay = $workingDays > 0 ? round($totalSubmit / $workingDays, 1) : 0;
            $activityPct = $pulledDaysCount > 0 ? round(($workingDays / $pulledDaysCount) * 100, 1) : 0;

            $totalSubmitsAll += $totalSubmit;
            $totalWorkingDaysAll += $workingDays;

            $namaKec = $kecNameMap[$meta['kode_kec']] ?? $meta['kode_kec'];

            $records->push([
                'email' => $email,
                'nama' => $meta['nama'],
                'kode_kec' => $meta['kode_kec'],
                'nama_kec' => $namaKec,
                'daily_submits' => $dailySubmits,
                'total_submit' => $totalSubmit,
                'working_days' => $workingDays,
                'pulled_days_count' => $pulledDaysCount,
                'avg_submit_per_working_day' => $avgSubmitPerWorkingDay,
                'activity_pct' => $activityPct,
            ]);
        }

        // Sort records by nama ascending by default
        $records = $records->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $totalPetugas = $records->count();
        $avgHariKerja = $totalPetugas > 0 ? round($totalWorkingDaysAll / $totalPetugas, 1) : 0;
        $overallAvgSubmit = $totalWorkingDaysAll > 0 ? round($totalSubmitsAll / $totalWorkingDaysAll, 1) : 0;

        return [
            'records' => $records,
            'calendarDates' => $calendarDates,
            'pulledDatesSet' => $pulledDatesSet,
            'availableDates' => $availableDates,
            'kecNameMap' => $kecNameMap,
            'summary' => [
                'totalPetugas' => $totalPetugas,
                'avgHariKerja' => $avgHariKerja,
                'totalSubmit' => $totalSubmitsAll,
                'avgSubmitPerHari' => $overallAvgSubmit,
            ],
            'kodekec' => $kodekec,
            'search' => $search,
        ];
    }
}
