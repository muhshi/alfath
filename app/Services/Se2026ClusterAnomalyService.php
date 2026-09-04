<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Se2026ClusterAnomalyService
{
    /**
     * Master 14 Kecamatan Map (Demak BPS Codes)
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

    public function getKecNameMap(): array
    {
        return $this->kecNameMap;
    }

    /**
     * Get or build cached cluster data from public/SE2026/*.csv and DB relationships.
     */
    public function getClusterData(bool $forceRefresh = false): array
    {
        $cacheStore = Cache::store('file');
        $cacheKey = 'se2026_geotag_anomaly_dataset_v5';

        if ($forceRefresh) {
            $cacheStore->forget($cacheKey);
        }

        return $cacheStore->remember($cacheKey, 86400, function () {
            return $this->buildDatasetFromCsv();
        });
    }

    /**
     * Parse raw CSV cluster files, combine coordinates, and join with fasih DB.
     */
    protected function buildDatasetFromCsv(): array
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        // Priority 1: Clean consolidated & deduplicated query (Sep 3 16:42 query with nama_assignment)
        $cleanCandidate = public_path('SE2026/sqllab_untitled_query_6_20260903T164219.csv');
        if (!file_exists($cleanCandidate)) {
            $cleanCandidate = base_path('public/SE2026/sqllab_untitled_query_6_20260903T164219.csv');
        }

        if (file_exists($cleanCandidate)) {
            $files = [$cleanCandidate];
        } else {
            // Priority 2: Multi-file fraud bangunan
            $files = glob(public_path('SE2026/fraud bangunan/*.csv'));
            if (empty($files)) {
                $files = glob(base_path('public/SE2026/fraud bangunan/*.csv'));
            }
            // Priority 3: Fallback to original SE2026 CSVs
            if (empty($files)) {
                $files = glob(public_path('SE2026/*.csv'));
            }
            if (empty($files)) {
                $files = glob(base_path('public/SE2026/*.csv'));
            }
        }

        // 1. Preload master petugas and region mappings from fasih DB
        $connName = config()->has('database.connections.fasih') ? 'fasih' : null;
        $db = $connName ? DB::connection($connName) : DB::connection();
        $schema = $connName ? Schema::connection($connName) : Schema::connection();

        $masterPetugasMap = [];
        if ($schema->hasTable('master_petugas')) {
            $petugasList = $db->table('master_petugas')
                ->select('email', 'nama_lengkap', 'peran')
                ->get();
            foreach ($petugasList as $p) {
                $emailKey = strtolower(trim($p->email));
                $masterPetugasMap[$emailKey] = [
                    'nama' => trim($p->nama_lengkap),
                    'peran' => trim($p->peran),
                ];
            }
        }

        $pencacahKecMap = [];
        if ($schema->hasTable('monitoring_se2026')) {
            $colEmail = $schema->hasColumn('monitoring_se2026', 'email_pencacah') ? 'email_pencacah' : 'pencacah_email';
            $pencacahKec = $db->table('monitoring_se2026')
                ->select(DB::raw("LOWER(TRIM($colEmail)) as email, LEFT(region_code, 7) as kodekec, COUNT(*) as cnt"))
                ->whereNotNull($colEmail)
                ->where($colEmail, '!=', '')
                ->groupBy(DB::raw("LOWER(TRIM($colEmail)), LEFT(region_code, 7)"))
                ->orderBy('cnt', 'desc')
                ->get();

            foreach ($pencacahKec as $row) {
                if (!isset($pencacahKecMap[$row->email])) {
                    $pencacahKecMap[$row->email] = $row->kodekec;
                }
            }
        }

        // Pengawas (PML) mapping per region
        $alokasiPengawasMap = [];
        if ($schema->hasTable('alokasi_pengawas')) {
            $alokasi = $db->table('alokasi_pengawas')
                ->select(DB::raw("LEFT(region_code, 7) as kodekec, LOWER(TRIM(email_pengawas)) as pml_email"))
                ->whereNotNull('email_pengawas')
                ->where('email_pengawas', '!=', '')
                ->groupBy(DB::raw("LEFT(region_code, 7), LOWER(TRIM(email_pengawas))"))
                ->get();

            foreach ($alokasi as $a) {
                if (!isset($alokasiPengawasMap[$a->kodekec])) {
                    $alokasiPengawasMap[$a->kodekec] = $a->pml_email;
                }
            }
        }

        // 2. Parse CSV files
        $totalRawPoints = 0;
        $clusters = [];
        $batchFiles = [];

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $batchFiles[] = $filename;
            $handle = fopen($filePath, 'r');
            if (!$handle) continue;

            $header = fgetcsv($handle);
            if (!$header) {
                fclose($handle);
                continue;
            }

            $idxEmail = array_search('pencacah_email', $header);
            $idxSize = array_search('cluster_size', $header);
            $idxNonBku = array_search('cluster_non_bku_size', $header);
            $idxLabel = array_search('kode_bang_label', $header);
            $idxNamaAssign = array_search('nama_assignment', $header);
            $idxNoBang = array_search('no_bang', $header);
            $idxCenterLat = array_search('cluster_center_lat', $header);
            $idxCenterLon = array_search('cluster_center_lon', $header);
            $idxMinLat = array_search('cluster_min_lat', $header);
            $idxMaxLat = array_search('cluster_max_lat', $header);
            $idxMinLon = array_search('cluster_min_lon', $header);
            $idxMaxLon = array_search('cluster_max_lon', $header);
            $idxAvgAcc = array_search('cluster_avg_accuracy', $header);
            $idxAssign = array_search('assignment_id', $header);
            $idxPointLat = array_search('point_lat', $header);
            $idxPointLon = array_search('point_lon', $header);

            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $totalRawPoints++;
                $email = strtolower(trim($row[$idxEmail] ?? ''));
                $cLat = trim($row[$idxCenterLat] ?? '');
                $cLon = trim($row[$idxCenterLon] ?? '');

                if (empty($email) || empty($cLat) || empty($cLon)) {
                    continue;
                }

                $clusterKey = $email . '|' . $cLat . '|' . $cLon;

                if (!isset($clusters[$clusterKey])) {
                    $size = (int) ($row[$idxSize] ?? 0);
                    $minLat = (float) ($row[$idxMinLat] ?? $cLat);
                    $maxLat = (float) ($row[$idxMaxLat] ?? $cLat);
                    $minLon = (float) ($row[$idxMinLon] ?? $cLon);
                    $maxLon = (float) ($row[$idxMaxLon] ?? $cLon);
                    $avgAcc = (float) ($row[$idxAvgAcc] ?? 0);

                    // Calculate bounding diameter in meters
                    $latDistM = abs($maxLat - $minLat) * 111320;
                    $lonDistM = abs($maxLon - $minLon) * 111320 * cos(deg2rad((float) $cLat));
                    $approxRadiusM = round(sqrt(pow($latDistM, 2) + pow($lonDistM, 2)), 1);

                    // Severity Classification
                    if ($size > 100) {
                        $severity = 'ekstrem';
                        $severityLabel = '🚨 Ekstrem (>100)';
                        $badgeClass = 'bg-danger text-white';
                        $markerColor = '#dc2626'; // red-600
                    } elseif ($size > 50) {
                        $severity = 'berat';
                        $severityLabel = '⚠️ Berat (51-100)';
                        $badgeClass = 'bg-orange text-white';
                        $markerColor = '#ea580c'; // orange-600
                    } elseif ($size > 20) {
                        $severity = 'sedang';
                        $severityLabel = '🟡 Sedang (21-50)';
                        $badgeClass = 'bg-warning text-dark';
                        $markerColor = '#ca8a04'; // yellow-600
                    } else {
                        $severity = 'ringan';
                        $severityLabel = '🔵 Ringan (10-20)';
                        $badgeClass = 'bg-azure text-white';
                        $markerColor = '#0284c7'; // sky-600
                    }

                    // Resolve Officer Name
                    $namaPetugas = $masterPetugasMap[$email]['nama'] ?? $this->formatEmailToName($email);

                    // Resolve Kecamatan
                    $kodeKec = $pencacahKecMap[$email] ?? null;
                    $namaKec = $kodeKec && isset($this->kecNameMap[$kodeKec]) ? $this->kecNameMap[$kodeKec] : 'Demak (Umum)';

                    // Resolve PML
                    $pmlEmail = $kodeKec && isset($alokasiPengawasMap[$kodeKec]) ? $alokasiPengawasMap[$kodeKec] : null;
                    $pmlNama = $pmlEmail && isset($masterPetugasMap[$pmlEmail]['nama']) ? $masterPetugasMap[$pmlEmail]['nama'] : ($pmlEmail ? $this->formatEmailToName($pmlEmail) : '-');

                    $clusterId = 'cls_' . substr(md5($clusterKey), 0, 10);

                    $clusters[$clusterKey] = [
                        'id' => $clusterId,
                        'key' => $clusterKey,
                        'email' => $email,
                        'nama_petugas' => $namaPetugas,
                        'kodekec' => $kodeKec,
                        'namakec' => $namaKec,
                        'pml_email' => $pmlEmail,
                        'pml_nama' => $pmlNama,
                        'cluster_size' => $size,
                        'cluster_non_bku_size' => $idxNonBku !== false ? (int) ($row[$idxNonBku] ?? 0) : 0,
                        'bku_count' => 0,
                        'btt_count' => 0,
                        'campuran_count' => 0,
                        'lainnya_count' => 0,
                        'pasar_kw_count' => 0,
                        'sample_names' => [],
                        'center_lat' => (float) $cLat,
                        'center_lon' => (float) $cLon,
                        'min_lat' => $minLat,
                        'max_lat' => $maxLat,
                        'min_lon' => $minLon,
                        'max_lon' => $maxLon,
                        'approx_radius_m' => $approxRadiusM,
                        'avg_accuracy' => $avgAcc,
                        'severity' => $severity,
                        'severity_label' => $severityLabel,
                        'badge_class' => $badgeClass,
                        'marker_color' => $markerColor,
                        'google_maps_url' => "https://www.google.com/maps?q={$cLat},{$cLon}&z=19&t=k",
                        'sample_assignments' => [trim($row[$idxAssign] ?? '')],
                        'points' => [],
                    ];
                } else {
                    if (count($clusters[$clusterKey]['sample_assignments']) < 3) {
                        $clusters[$clusterKey]['sample_assignments'][] = trim($row[$idxAssign] ?? '');
                    }
                }

                $assignId = trim($row[$idxAssign] ?? '');
                $label = $idxLabel !== false ? trim($row[$idxLabel] ?? '') : '';
                $namaAssign = $idxNamaAssign !== false ? trim($row[$idxNamaAssign] ?? '') : '';
                $noBang = $idxNoBang !== false ? trim($row[$idxNoBang] ?? '') : '';
                $pLat = (float) ($row[$idxPointLat] ?? $cLat);
                $pLon = (float) ($row[$idxPointLon] ?? $cLon);

                if (!empty($namaAssign) && count($clusters[$clusterKey]['sample_names']) < 3 && !in_array($namaAssign, $clusters[$clusterKey]['sample_names'])) {
                    $clusters[$clusterKey]['sample_names'][] = $namaAssign;
                }

                if (!empty($assignId) && !isset($clusters[$clusterKey]['points'][$assignId])) {
                    $isBku = strpos($label, '1. Bangunan Khusus Usaha') !== false;
                    $isBtt = strpos($label, '3. Bangunan Tempat Tinggal') !== false;
                    $isCampuran = strpos($label, '2. Bangunan Campuran') !== false;
                    $isPasarKw = !empty($namaAssign) && preg_match('/\b(pasar|los|kios|lapak|toko|warung|ruko|pedagang|ikan|sayur|buah)\b/i', $namaAssign);

                    if ($isPasarKw) {
                        $clusters[$clusterKey]['pasar_kw_count']++;
                    }

                    $bType = 'lainnya';
                    $pointColor = '#8b5cf6'; // Ungu untuk Bangunan Lainnya/Kosong
                    if ($isBku || $isPasarKw) {
                        $bType = 'bku';
                        $pointColor = '#10b981'; // Hijau Segar untuk BKU / Pasar
                        $clusters[$clusterKey]['bku_count']++;
                    } elseif ($isBtt) {
                        $bType = 'btt';
                        $pointColor = '#ef4444'; // Merah Tegas untuk BTT (Tempat Tinggal / Fraud)
                        $clusters[$clusterKey]['btt_count']++;
                    } elseif ($isCampuran) {
                        $bType = 'campuran';
                        $pointColor = '#f59e0b'; // Amber/Oranye untuk Bangunan Campuran
                        $clusters[$clusterKey]['campuran_count']++;
                    } else {
                        $clusters[$clusterKey]['lainnya_count']++;
                    }

                    $clusters[$clusterKey]['points'][$assignId] = [
                        round($pLat, 7),
                        round($pLon, 7),
                        substr($assignId, 0, 8),
                        $bType,
                        $label,
                        $pointColor,
                        $namaAssign,
                        $noBang,
                    ];
                }
            }
            fclose($handle);
        }

        // Convert points to numeric array, compute BKU vs BTT metrics, and classify fraud
        foreach ($clusters as &$c) {
            $c['points'] = array_values($c['points']);
            $total = count($c['points']);
            if ($total > 0) {
                $c['cluster_size'] = $total;
            }

            $bku = $c['bku_count'];
            $btt = $c['btt_count'];
            $campuran = $c['campuran_count'];
            $lainnya = $c['lainnya_count'];
            $pasarKw = $c['pasar_kw_count'] ?? 0;

            $pctBku = round(($bku / max(1, $total)) * 100);
            $pctBtt = round(($btt / max(1, $total)) * 100);
            $pctCampuran = round(($campuran / max(1, $total)) * 100);
            $pctLainnya = round(($lainnya / max(1, $total)) * 100);

            $c['pct_bku'] = $pctBku;
            $c['pct_btt'] = $pctBtt;
            $c['pct_campuran'] = $pctCampuran;
            $c['pct_lainnya'] = $pctLainnya;

            // Classification: BKU or Pasar Keywords (Pasar/Wajar) vs BTT (Tempat Tinggal/Fraud)
            if ($bku >= ($total * 0.40) || $pasarKw >= ($total * 0.40)) {
                $c['fraud_category'] = 'wajar_bku';
                $c['fraud_label'] = '🟢 Potensi Wajar (Pasar / Ruko BKU)';
                $c['fraud_badge'] = 'bg-success text-white';
                $c['fraud_summary'] = ($bku >= $pasarKw) ? "{$pctBku}% BKU (Pasar/Usaha)" : "Sentra Pasar/Kios";
            } elseif ($btt >= ($total * 0.35) && $pasarKw < ($total * 0.20)) {
                $c['fraud_category'] = 'fraud_btt';
                $c['fraud_label'] = '🚨 Indikasi Kuat Fraud (BTT/Tempat Tinggal)';
                $c['fraud_badge'] = 'bg-danger text-white';
                $c['fraud_summary'] = "{$pctBtt}% BTT (Tempat Tinggal)";
            } else {
                $c['fraud_category'] = 'campuran';
                $c['fraud_label'] = '🟡 Campuran (BTT & BKU)';
                $c['fraud_badge'] = 'bg-warning text-dark';
                $c['fraud_summary'] = "Campuran ({$pctBku}% BKU, {$pctBtt}% BTT)";
            }
        }
        unset($c);

        // Sort clusters by cluster_size desc
        uasort($clusters, function ($a, $b) {
            return $b['cluster_size'] <=> $a['cluster_size'];
        });

        // 3. Build Petugas Ranking
        $petugasGroups = [];
        foreach ($clusters as $c) {
            $email = $c['email'];
            if (!isset($petugasGroups[$email])) {
                $petugasGroups[$email] = [
                    'email' => $email,
                    'nama' => $c['nama_petugas'],
                    'kodekec' => $c['kodekec'],
                    'namakec' => $c['namakec'],
                    'pml_nama' => $c['pml_nama'],
                    'pml_email' => $c['pml_email'],
                    'total_clusters' => 0,
                    'max_cluster_size' => 0,
                    'total_anomali_points' => 0,
                    'total_bku_points' => 0,
                    'total_btt_points' => 0,
                    'total_fraud_clusters' => 0,
                    'total_wajar_clusters' => 0,
                    'top_cluster_id' => $c['id'],
                    'top_cluster_lat' => $c['center_lat'],
                    'top_cluster_lon' => $c['center_lon'],
                    'clusters' => [],
                ];
            }

            $petugasGroups[$email]['total_clusters']++;
            $petugasGroups[$email]['total_anomali_points'] += $c['cluster_size'];
            $petugasGroups[$email]['total_bku_points'] += $c['bku_count'];
            $petugasGroups[$email]['total_btt_points'] += $c['btt_count'];

            if ($c['fraud_category'] === 'fraud_btt') {
                $petugasGroups[$email]['total_fraud_clusters']++;
            } elseif ($c['fraud_category'] === 'wajar_bku') {
                $petugasGroups[$email]['total_wajar_clusters']++;
            }

            if ($c['cluster_size'] > $petugasGroups[$email]['max_cluster_size']) {
                $petugasGroups[$email]['max_cluster_size'] = $c['cluster_size'];
                $petugasGroups[$email]['top_cluster_id'] = $c['id'];
                $petugasGroups[$email]['top_cluster_lat'] = $c['center_lat'];
                $petugasGroups[$email]['top_cluster_lon'] = $c['center_lon'];
            }
            $petugasGroups[$email]['clusters'][] = $c['id'];
        }

        // Assign severity & rank to Petugas
        uasort($petugasGroups, function ($a, $b) {
            if ($b['total_anomali_points'] === $a['total_anomali_points']) {
                return $b['max_cluster_size'] <=> $a['max_cluster_size'];
            }
            return $b['total_anomali_points'] <=> $a['total_anomali_points'];
        });

        $petugasRanking = [];
        $rank = 1;
        foreach ($petugasGroups as $email => $p) {
            $maxSize = $p['max_cluster_size'];
            if ($maxSize > 100) {
                $pSev = 'ekstrem';
                $pBadge = 'bg-danger text-white';
                $pLabel = '🚨 Kritis';
            } elseif ($maxSize > 50) {
                $pSev = 'berat';
                $pBadge = 'bg-orange text-white';
                $pLabel = '⚠️ Tinggi';
            } elseif ($maxSize > 20) {
                $pSev = 'sedang';
                $pBadge = 'bg-warning text-dark';
                $pLabel = '🟡 Sedang';
            } else {
                $pSev = 'ringan';
                $pBadge = 'bg-azure text-white';
                $pLabel = '🔵 Rendah';
            }

            $p['rank'] = $rank++;
            $p['severity'] = $pSev;
            $p['severity_label'] = $pLabel;
            $p['badge_class'] = $pBadge;
            $petugasRanking[] = $p;
        }

        // 4. Build Kecamatan Aggregation
        $kecamatanSummary = [];
        foreach ($this->kecNameMap as $code => $name) {
            $kecamatanSummary[$code] = [
                'code' => $code,
                'name' => $name,
                'total_clusters' => 0,
                'total_petugas' => 0,
                'total_points' => 0,
                'total_bku_points' => 0,
                'total_btt_points' => 0,
                'total_fraud_clusters' => 0,
                'total_wajar_clusters' => 0,
                'max_cluster_size' => 0,
                'petugas_emails' => [],
            ];
        }

        // Also add general Demak bucket if any
        $kecamatanSummary['other'] = [
            'code' => 'other',
            'name' => 'Lainnya / Tidak Terpetakan',
            'total_clusters' => 0,
            'total_petugas' => 0,
            'total_points' => 0,
            'total_bku_points' => 0,
            'total_btt_points' => 0,
            'total_fraud_clusters' => 0,
            'total_wajar_clusters' => 0,
            'max_cluster_size' => 0,
            'petugas_emails' => [],
        ];

        foreach ($clusters as $c) {
            $kCode = $c['kodekec'] ?? 'other';
            if (!isset($kecamatanSummary[$kCode])) {
                $kCode = 'other';
            }
            $kecamatanSummary[$kCode]['total_clusters']++;
            $kecamatanSummary[$kCode]['total_points'] += $c['cluster_size'];
            $kecamatanSummary[$kCode]['total_bku_points'] += $c['bku_count'];
            $kecamatanSummary[$kCode]['total_btt_points'] += $c['btt_count'];

            if ($c['fraud_category'] === 'fraud_btt') {
                $kecamatanSummary[$kCode]['total_fraud_clusters']++;
            } elseif ($c['fraud_category'] === 'wajar_bku') {
                $kecamatanSummary[$kCode]['total_wajar_clusters']++;
            }

            if ($c['cluster_size'] > $kecamatanSummary[$kCode]['max_cluster_size']) {
                $kecamatanSummary[$kCode]['max_cluster_size'] = $c['cluster_size'];
            }
            $kecamatanSummary[$kCode]['petugas_emails'][$c['email']] = true;
        }

        foreach ($kecamatanSummary as $kCode => &$kData) {
            $kData['total_petugas'] = count($kData['petugas_emails']);
            unset($kData['petugas_emails']);
        }
        unset($kData);

        // Sort kecamatan by total_points desc
        uasort($kecamatanSummary, function ($a, $b) {
            return $b['total_points'] <=> $a['total_points'];
        });

        // 5. Build KPI Summary
        $severityCounts = [
            'ekstrem' => 0,
            'berat' => 0,
            'sedang' => 0,
            'ringan' => 0,
        ];
        $fraudCounts = [
            'fraud_btt' => 0,
            'wajar_bku' => 0,
            'campuran' => 0,
        ];
        $maxClusterOverall = 0;
        foreach ($clusters as $c) {
            $severityCounts[$c['severity']]++;
            $cat = $c['fraud_category'] ?? 'campuran';
            if (isset($fraudCounts[$cat])) {
                $fraudCounts[$cat]++;
            }
            if ($c['cluster_size'] > $maxClusterOverall) {
                $maxClusterOverall = $c['cluster_size'];
            }
        }

        $clusterList = array_values($clusters);

        return [
            'total_raw_points' => $totalRawPoints,
            'total_clusters' => count($clusterList),
            'total_petugas' => count($petugasRanking),
            'max_cluster_size' => $maxClusterOverall,
            'severity_counts' => $severityCounts,
            'fraud_counts' => $fraudCounts,
            'batch_files' => $batchFiles,
            'clusters' => $clusterList,
            'petugas_ranking' => $petugasRanking,
            'kecamatan_summary' => array_values($kecamatanSummary),
            'generated_at' => date('d M Y | H:i') . ' WIB',
        ];
    }

    /**
     * Filter the cached dataset according to user inputs.
     */
    public function getFilteredViewData(Request $request): array
    {
        $forceRefresh = $request->has('fresh') || $request->has('refresh');
        $raw = $this->getClusterData($forceRefresh);

        $selectedKec = trim((string) $request->get('kecamatan', ''));
        $selectedSeverity = trim((string) $request->get('severity', ''));
        $selectedFraud = trim((string) $request->get('fraud_category', ''));
        $search = trim((string) $request->get('search', ''));

        $filteredClusters = $raw['clusters'];
        $filteredPetugas = $raw['petugas_ranking'];

        // Filter Clusters by Kecamatan
        if (!empty($selectedKec)) {
            $filteredClusters = array_values(array_filter($filteredClusters, function ($c) use ($selectedKec) {
                return ($c['kodekec'] === $selectedKec) || ($selectedKec === 'other' && empty($c['kodekec']));
            }));
        }

        // Filter Clusters by Severity
        if (!empty($selectedSeverity)) {
            $filteredClusters = array_values(array_filter($filteredClusters, function ($c) use ($selectedSeverity) {
                return $c['severity'] === $selectedSeverity;
            }));
        }

        // Filter Clusters by Fraud Category (BTT vs BKU)
        if (!empty($selectedFraud)) {
            $filteredClusters = array_values(array_filter($filteredClusters, function ($c) use ($selectedFraud) {
                return ($c['fraud_category'] ?? '') === $selectedFraud;
            }));
        }

        // Search Filter for Clusters
        if (!empty($search)) {
            $searchLower = strtolower($search);
            $filteredClusters = array_values(array_filter($filteredClusters, function ($c) use ($searchLower) {
                return str_contains(strtolower($c['nama_petugas']), $searchLower)
                    || str_contains(strtolower($c['email']), $searchLower)
                    || str_contains(strtolower($c['pml_nama']), $searchLower)
                    || str_contains(strtolower($c['namakec']), $searchLower);
            }));
        }

        // Filter Petugas Ranking
        if (!empty($selectedKec)) {
            $filteredPetugas = array_values(array_filter($filteredPetugas, function ($p) use ($selectedKec) {
                return ($p['kodekec'] === $selectedKec) || ($selectedKec === 'other' && empty($p['kodekec']));
            }));
        }

        if (!empty($selectedSeverity)) {
            $filteredPetugas = array_values(array_filter($filteredPetugas, function ($p) use ($selectedSeverity) {
                return $p['severity'] === $selectedSeverity;
            }));
        }

        if (!empty($search)) {
            $searchLower = strtolower($search);
            $filteredPetugas = array_values(array_filter($filteredPetugas, function ($p) use ($searchLower) {
                return str_contains(strtolower($p['nama']), $searchLower)
                    || str_contains(strtolower($p['email']), $searchLower)
                    || str_contains(strtolower($p['pml_nama']), $searchLower)
                    || str_contains(strtolower($p['namakec']), $searchLower);
            }));
        }

        $fraudClusters = array_filter($filteredClusters, fn($c) => ($c['fraud_category'] ?? '') === 'fraud_btt');
        $wajarClusters = array_filter($filteredClusters, fn($c) => ($c['fraud_category'] ?? '') === 'wajar_bku');
        $campuranClusters = array_filter($filteredClusters, fn($c) => ($c['fraud_category'] ?? '') === 'campuran');

        $fraudSlsData = $this->getFraudSlsGeoJson();
        $totalFraudSls = count($fraudSlsData['features'] ?? []);

        return [
            'kecNameMap' => $this->kecNameMap,
            'selectedKec' => $selectedKec,
            'selectedSeverity' => $selectedSeverity,
            'selectedFraud' => $selectedFraud,
            'search' => $search,
            'kpi' => [
                'total_points' => array_sum(array_column($filteredClusters, 'cluster_size')),
                'total_clusters' => count($filteredClusters),
                'total_petugas' => count(array_unique(array_column($filteredClusters, 'email'))),
                'max_cluster' => !empty($filteredClusters) ? max(array_column($filteredClusters, 'cluster_size')) : 0,
                'total_fraud_clusters' => count($fraudClusters),
                'total_fraud_points' => array_sum(array_column($fraudClusters, 'cluster_size')),
                'total_fraud_sls' => $totalFraudSls,
                'total_wajar_clusters' => count($wajarClusters),
                'total_wajar_points' => array_sum(array_column($wajarClusters, 'cluster_size')),
                'total_campuran_clusters' => count($campuranClusters),
                'total_campuran_points' => array_sum(array_column($campuranClusters, 'cluster_size')),
                'severity_counts' => $raw['severity_counts'],
            ],
            'clusters' => $filteredClusters,
            'petugas_ranking' => $filteredPetugas,
            'kecamatan_summary' => $raw['kecamatan_summary'],
            'generated_at' => $raw['generated_at'],
        ];
    }

    /**
     * Get filtered SLS GeoJSON containing only SLS with fraud clusters.
     */
    public function getFraudSlsGeoJson(bool $forceRefresh = false): array
    {
        $filteredFile = public_path('SE2026/peta_sls_fraud_filtered.geojson');
        if (!file_exists($filteredFile)) {
            $filteredFile = base_path('public/SE2026/peta_sls_fraud_filtered.geojson');
        }

        if (!$forceRefresh && file_exists($filteredFile)) {
            $content = file_get_contents($filteredFile);
            $decoded = json_decode($content, true);
            if (!empty($decoded['features'])) {
                return $decoded;
            }
        }

        return $this->buildFraudSlsGeoJson();
    }

    /**
     * Spatial matching: Filter raw Demak SLS GeoJSON (25MB) to only SLS with fraud clusters.
     */
    public function buildFraudSlsGeoJson(): array
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(180);

        $dataset = $this->getClusterData();
        $fraudClusters = [];
        foreach ($dataset['clusters'] as $c) {
            if (($c['fraud_category'] ?? '') === 'fraud_btt') {
                $fraudClusters[] = [
                    'id' => $c['id'],
                    'lat' => (float) $c['center_lat'],
                    'lon' => (float) $c['center_lon'],
                    'petugas' => $c['nama_petugas'] ?? '',
                    'kec_code' => $c['kodekec'] ?? '',
                    'points_count' => count($c['points'] ?? []),
                ];
            }
        }

        $geojsonPath = public_path('SE2026/peta_sls_202513321 (2).geojson');
        if (!file_exists($geojsonPath)) {
            $geojsonPath = base_path('public/SE2026/peta_sls_202513321 (2).geojson');
        }

        if (!file_exists($geojsonPath)) {
            return ['type' => 'FeatureCollection', 'features' => []];
        }

        $raw = file_get_contents($geojsonPath);
        $geojson = json_decode($raw, true);
        unset($raw);

        if (empty($geojson['features'])) {
            return ['type' => 'FeatureCollection', 'features' => []];
        }

        $indexed = [];
        foreach ($geojson['features'] as $f) {
            $geom = $f['geometry'] ?? null;
            if (!$geom) continue;
            $minX = 180.0; $minY = 90.0; $maxX = -180.0; $maxY = -90.0;
            if ($geom['type'] === 'Polygon') {
                foreach ($geom['coordinates'] as $ring) {
                    $this->scanRingBbox($ring, $minX, $minY, $maxX, $maxY);
                }
            } elseif ($geom['type'] === 'MultiPolygon') {
                foreach ($geom['coordinates'] as $poly) {
                    foreach ($poly as $ring) {
                        $this->scanRingBbox($ring, $minX, $minY, $maxX, $maxY);
                    }
                }
            }
            $indexed[] = [
                'minX' => $minX, 'minY' => $minY, 'maxX' => $maxX, 'maxY' => $maxY,
                'geom' => $geom,
                'props' => $f['properties'] ?? [],
                'feat' => $f,
            ];
        }

        $matchedSls = [];
        foreach ($fraudClusters as $fc) {
            $px = $fc['lon'];
            $py = $fc['lat'];
            foreach ($indexed as $item) {
                if ($px >= $item['minX'] && $px <= $item['maxX'] && $py >= $item['minY'] && $py <= $item['maxY']) {
                    if ($this->pointInGeom($px, $py, $item['geom'])) {
                        $idsls = $item['props']['idsls'] ?? ($item['props']['idsubsls'] ?? uniqid('sls_'));
                        if (!isset($matchedSls[$idsls])) {
                            $matchedSls[$idsls] = [
                                'feature' => $item['feat'],
                                'orig_props' => $item['props'],
                                'fraud_clusters' => [],
                                'fraud_points_count' => 0,
                                'petugas' => [],
                                'kec_codes' => [],
                            ];
                        }
                        $matchedSls[$idsls]['fraud_clusters'][] = $fc['id'];
                        $matchedSls[$idsls]['fraud_points_count'] += $fc['points_count'];
                        if (!empty($fc['petugas']) && !in_array($fc['petugas'], $matchedSls[$idsls]['petugas'])) {
                            $matchedSls[$idsls]['petugas'][] = $fc['petugas'];
                        }
                        if (!empty($fc['kec_code']) && !in_array($fc['kec_code'], $matchedSls[$idsls]['kec_codes'])) {
                            $matchedSls[$idsls]['kec_codes'][] = $fc['kec_code'];
                        }
                        break;
                    }
                }
            }
        }

        $cleanFeatures = [];
        foreach ($matchedSls as $idsls => $data) {
            $feat = $data['feature'];
            $orig = $data['orig_props'];
            $kdKecBps = !empty($data['kec_codes']) ? $data['kec_codes'][0] : ('3321' . ($orig['kdkec'] ?? ''));

            $feat['properties'] = [
                'idsls' => (string) $idsls,
                'nmsls' => $orig['nmsls'] ?? ($orig['nama_sls'] ?? ('SLS ' . $idsls)),
                'nmdesa' => $orig['nmdesa'] ?? '',
                'nmkec' => $orig['nmkec'] ?? '',
                'kdkec' => $orig['kdkec'] ?? '',
                'kd_kec_bps' => $kdKecBps,
                'fraud_clusters_count' => count($data['fraud_clusters']),
                'fraud_points_count' => $data['fraud_points_count'],
                'petugas_list' => $data['petugas'],
                'cluster_ids' => $data['fraud_clusters'],
            ];
            $cleanFeatures[] = $feat;
        }

        $result = [
            'type' => 'FeatureCollection',
            'name' => 'sls_fraud_overlay_demak',
            'crs' => ['type' => 'name', 'properties' => ['name' => 'urn:ogc:def:crs:OGC:1.3:CRS84']],
            'features' => $cleanFeatures,
        ];

        // Save filtered GeoJSON for lightning-fast subsequent access
        $outPath = public_path('SE2026/peta_sls_fraud_filtered.geojson');
        @file_put_contents($outPath, json_encode($result, JSON_UNESCAPED_UNICODE));

        return $result;
    }

    protected function scanRingBbox(array $ring, float &$minX, float &$minY, float &$maxX, float &$maxY): void
    {
        foreach ($ring as $pt) {
            $x = $pt[0]; $y = $pt[1];
            if ($x < $minX) $minX = $x;
            if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y;
            if ($y > $maxY) $maxY = $y;
        }
    }

    protected function pointInRing(float $px, float $py, array $ring): bool
    {
        $n = count($ring);
        $inside = false;
        $p1x = $ring[0][0]; $p1y = $ring[0][1];
        for ($i = 1; $i <= $n; $i++) {
            $p2x = $ring[$i % $n][0];
            $p2y = $ring[$i % $n][1];
            if ($py > min($p1y, $p2y) && $py <= max($p1y, $p2y) && $px <= max($p1x, $p2x)) {
                if ($p1y != $p2y) {
                    $xinters = ($py - $p1y) * ($p2x - $p1x) / ($p2y - $p1y) + $p1x;
                } else {
                    $xinters = $p1x;
                }
                if ($p1x == $p2x || $px <= $xinters) {
                    $inside = !$inside;
                }
            }
            $p1x = $p2x; $p1y = $p2y;
        }
        return $inside;
    }

    protected function pointInGeom(float $px, float $py, array $geom): bool
    {
        $type = $geom['type'] ?? '';
        $coords = $geom['coordinates'] ?? [];
        if ($type === 'Polygon') {
            if (!$this->pointInRing($px, $py, $coords[0])) return false;
            for ($i = 1; $i < count($coords); $i++) {
                if ($this->pointInRing($px, $py, $coords[$i])) return false;
            }
            return true;
        } elseif ($type === 'MultiPolygon') {
            foreach ($coords as $poly) {
                if ($this->pointInRing($px, $py, $poly[0])) {
                    $inHole = false;
                    for ($i = 1; $i < count($poly); $i++) {
                        if ($this->pointInRing($px, $py, $poly[$i])) {
                            $inHole = true;
                            break;
                        }
                    }
                    if (!$inHole) return true;
                }
            }
            return false;
        }
        return false;
    }

    /**
     * Fallback to turn email (e.g. fakrizainul0@gmail.com) into readable title-case name.
     */
    protected function formatEmailToName(string $email): string
    {
        $prefix = explode('@', $email)[0] ?? $email;
        $clean = preg_replace('/[0-9_\-\.]+/', ' ', $prefix);
        return ucwords(trim($clean)) ?: $email;
    }
}
