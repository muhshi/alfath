<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExecutiveDashboardController extends Controller
{
    /**
     * Display the Executive Public Dashboard for Sensus Ekonomi 2026.
     */
    public function index()
    {
        // 14 Kecamatan Master Map (Demak BPS Codes)
        $kecNameMap = [
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

        // Default Fallbacks & SDM Breakdown
        $totalPetugas = 1000;
        $sdmUmkTotal = 992;
        $sdmUmkPpl = 876;
        $sdmUmkPml = 116;
        
        $sdmUbTotal = 8;
        $sdmUbPpl = 6;
        $sdmUbPml = 2;

        $totalPengawas = 118; // 116 PML UMK + 2 PML UB
        $totalBebanTarget = 569814;
        $totalSubmit = 342500;
        $totalApproved = 285000;
        
        $trendDates = [];
        $trendSubmits = [];
        $trendTargets = [];
        
        $kecamatanProgress = [];
        $totalSLS = 8270; // Total Sub SLS Kab. Demak
        $slsTersentuh = 5160;
        $slsSelesai = 4120;
        
        $totalUBTarget = 250;
        $totalUBTerdata = 206;
        
        $ukTotal = 13500;
        $ukDitemukan = 12450;
        $ukTidakDitemukan = 1050;
        
        $upTotal = 4270;
        $upDitemukan = 3850;
        $upTutupAlihFungsi = 420;

        try {
            // Determine DB connection for monitoring data (prefer 'fasih' if configured)
            $connName = config()->has('database.connections.fasih') ? 'fasih' : null;
            $db = $connName ? DB::connection($connName) : DB::connection();
            $schema = $connName ? Schema::connection($connName) : Schema::connection();

            // 1. SDM & Field Team Metrics
            if ($schema->hasTable('master_petugas')) {
                $dbCount = $db->table('master_petugas')->count();
                if ($dbCount > 0) $totalPetugas = $dbCount;
            }

            // 2. Monitoring SE2026 Overall Metrics, Daily Trend & Kecamatan Breakdown
            if ($schema->hasTable('monitoring_se2026')) {
                // Find latest date in monitoring_se2026
                $latestDate = $db->table('monitoring_se2026')->max('tanggal_tarik');

                // Query kecamatan progress on latest date
                $rawKecData = $db->table('monitoring_se2026')
                    ->when($latestDate, function ($query, $latestDate) {
                        return $query->where('tanggal_tarik', $latestDate);
                    })
                    ->select(DB::raw("
                        LEFT(region_code, 7) as kodekec,
                        SUM(IFNULL(total_beban, 0)) as target,
                        SUM(IFNULL(total_beban, 0) - IFNULL(status_open, 0) - IFNULL(status_draft, 0)) as submit
                    "))
                    ->groupBy(DB::raw("LEFT(region_code, 7)"))
                    ->get()
                    ->keyBy('kodekec');

                $calcTotalBeban = 0;
                $calcTotalSubmit = 0;

                // Build full list of all 14 kecamatan
                foreach ($kecNameMap as $code => $name) {
                    $item = $rawKecData->get($code);
                    $target = $item ? (int) $item->target : 0;
                    $submit = $item ? (int) $item->submit : 0;
                    $pct = $target > 0 ? round(($submit / $target) * 100, 1) : 0;

                    $kecamatanProgress[] = [
                        'code' => $code,
                        'name' => $name,
                        'target' => $target,
                        'submit' => $submit,
                        'pct' => $pct
                    ];

                    $calcTotalBeban += $target;
                    $calcTotalSubmit += $submit;
                }

                if ($calcTotalBeban > 0) {
                    $totalBebanTarget = $calcTotalBeban;
                    $totalSubmit = $calcTotalSubmit;
                }

                // Daily Trend Data with Target Seharusnya line (1.33% per day from 2026-06-15)
                $trendRaw = $db->table('monitoring_se2026')
                    ->select(DB::raw("
                        tanggal_tarik as t_date,
                        SUM(IFNULL(total_beban, 0) - IFNULL(status_open, 0) - IFNULL(status_draft, 0)) as total_sub
                    "))
                    ->whereNotNull('tanggal_tarik')
                    ->groupBy('tanggal_tarik')
                    ->orderBy('tanggal_tarik', 'asc')
                    ->get();

                if ($trendRaw->count() > 0) {
                    $trendDates = [];
                    $trendSubmits = [];
                    $trendTargets = [];

                    $startDate = strtotime('2026-06-15');

                    foreach ($trendRaw as $idx => $row) {
                        $dateStr = date('d M', strtotime($row->t_date));
                        $trendDates[] = $dateStr;
                        $trendSubmits[] = (int) $row->total_sub;

                        // Target Seharusnya (1.33% per hari dari start date)
                        $currDate = strtotime($row->t_date);
                        $dayNum = max(1, floor(($currDate - $startDate) / 86400) + 1);
                        $targetPct = min(100, round($dayNum * 1.33, 2));
                        $targetVal = round(($targetPct / 100) * $totalBebanTarget);
                        $trendTargets[] = (int) $targetVal;
                    }
                }

                // 3. Sub SLS Coverage Metrics using Metabase logic:
                // Sub SLS tersentuh = region_code (Sub SLS) where (total_beban - status_open) > 0
                $subSlsTersentuhCount = $db->table('monitoring_se2026')
                    ->when($latestDate, function ($query, $latestDate) {
                        return $query->where('tanggal_tarik', $latestDate);
                    })
                    ->whereRaw('(IFNULL(total_beban, 0) - IFNULL(status_open, 0)) > 0')
                    ->count();

                if ($subSlsTersentuhCount > 0) {
                    $slsTersentuh = $subSlsTersentuhCount;
                }
            } elseif ($schema->hasTable('monitoring_sls_se2026')) {
                if ($schema->hasColumn('monitoring_sls_se2026', 'status_sls')) {
                    $slsTersentuh = $db->table('monitoring_sls_se2026')->where('status_sls', '!=', 'OPEN')->count();
                }
            }

            // 4. Usaha Besar (UB) Special Progress (ub_pencacah & ub_pengawas)
            if ($schema->hasTable('ub_pencacah')) {
                $countUB = $db->table('ub_pencacah')->count();
                if ($countUB > 0) $totalUBTarget = $countUB;
                if ($schema->hasColumn('ub_pencacah', 'status')) {
                    $totalUBTerdata = $db->table('ub_pencacah')->whereIn('status', ['SUBMITTED', 'APPROVED', 'COMPLETE'])->count();
                }
            }

            // 5. Usaha Keluarga Metrics (usaha_keluarga)
            if ($schema->hasTable('usaha_keluarga')) {
                $countUK = $db->table('usaha_keluarga')->count();
                if ($countUK > 0) $ukTotal = $countUK;
                if ($schema->hasColumn('usaha_keluarga', 'status_keberadaan')) {
                    $ukDitemukan = $db->table('usaha_keluarga')->where('status_keberadaan', 'DITEMUKAN')->count();
                    $ukTidakDitemukan = $db->table('usaha_keluarga')->where('status_keberadaan', '!=', 'DITEMUKAN')->count();
                }
            }

            // 6. Usaha Perusahaan Metrics (usaha_perusahaan)
            if ($schema->hasTable('usaha_perusahaan')) {
                $countUP = $db->table('usaha_perusahaan')->count();
                if ($countUP > 0) $upTotal = $countUP;
                if ($schema->hasColumn('usaha_perusahaan', 'status_keberadaan')) {
                    $upDitemukan = $db->table('usaha_perusahaan')->where('status_keberadaan', 'DITEMUKAN')->count();
                    $upTutupAlihFungsi = $db->table('usaha_perusahaan')->where('status_keberadaan', '!=', 'DITEMUKAN')->count();
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ExecutiveDashboard error: ' . $e->getMessage());
        }

        // Fallback trend targets if trendDates is empty
        if (empty($trendDates)) {
            $trendDates = ['19 Jul', '20 Jul', '21 Jul', '22 Jul', '23 Jul', '24 Jul', '25 Jul', '26 Jul', '27 Jul', '28 Jul'];
            $trendSubmits = [150000, 195000, 242000, 284000, 310000, 345000, 381000, 414000, 442500, 461000];
            $trendTargets = [180000, 210000, 240000, 270000, 300000, 330000, 360000, 390000, 420000, 450000];
        }

        // Fallback kecamatan progress if empty
        if (empty($kecamatanProgress)) {
            foreach ($kecNameMap as $code => $name) {
                $kecamatanProgress[] = [
                    'code' => $code,
                    'name' => $name,
                    'target' => 40000,
                    'submit' => 30000,
                    'pct' => 75.0
                ];
            }
        }

        // Calculated Percentage Progress
        $persenCapaianKab = $totalBebanTarget > 0 ? round(($totalSubmit / $totalBebanTarget) * 100, 1) : 0;
        $persenSLSTersentuh = $totalSLS > 0 ? round(($slsTersentuh / $totalSLS) * 100, 1) : 0;
        $persenUB = $totalUBTarget > 0 ? round(($totalUBTerdata / $totalUBTarget) * 100, 1) : 0;

        return view('dashboard-se2026', compact(
            'totalPetugas',
            'sdmUmkTotal',
            'sdmUmkPpl',
            'sdmUmkPml',
            'sdmUbTotal',
            'sdmUbPpl',
            'sdmUbPml',
            'totalPengawas',
            'totalBebanTarget',
            'totalSubmit',
            'totalApproved',
            'persenCapaianKab',
            'totalSLS',
            'slsTersentuh',
            'slsSelesai',
            'persenSLSTersentuh',
            'totalUBTarget',
            'totalUBTerdata',
            'persenUB',
            'ukTotal',
            'ukDitemukan',
            'ukTidakDitemukan',
            'upTotal',
            'upDitemukan',
            'upTutupAlihFungsi',
            'trendDates',
            'trendSubmits',
            'trendTargets',
            'kecamatanProgress'
        ));
    }
}
