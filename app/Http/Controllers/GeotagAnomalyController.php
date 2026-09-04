<?php

namespace App\Http\Controllers;

use App\Services\Se2026ClusterAnomalyService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeotagAnomalyController extends Controller
{
    public function __construct(
        protected Se2026ClusterAnomalyService $clusterService
    ) {}

    /**
     * Display the SE2026 Geotag Anomaly & Cluster Detection Dashboard.
     */
    public function index(Request $request)
    {
        $viewData = $this->clusterService->getFilteredViewData($request);

        return view('dashboard-anomali-geotag', $viewData);
    }

    /**
     * Return filtered GeoJSON containing only SLS polygons with fraud anomalies.
     */
    public function slsGeojson(Request $request)
    {
        $forceRefresh = $request->boolean('refresh', false);
        $geojson = $this->clusterService->getFraudSlsGeoJson($forceRefresh);

        return response()->json($geojson, 200, [
            'Content-Type' => 'application/geo+json; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Export anomaly cluster records or officer rankings to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $viewData = $this->clusterService->getFilteredViewData($request);
        $type = $request->get('type', 'clusters');
        $filename = 'anomali_geotag_se2026_' . $type . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($viewData, $type) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            if ($type === 'petugas') {
                fputcsv($handle, [
                    'Rank', 'Nama Petugas', 'Email', 'Kecamatan', 'PML (Pengawas)',
                    'Tingkat Risiko', 'Total Klaster', 'Titik BTT (Rumah/Fraud)', 'Titik BKU (Pasar/Wajar)',
                    'Titik Terbanyak 1 Spot', 'Total Titik Anomali', 'Koordinat Klaster Terbesar'
                ]);

                foreach ($viewData['petugas_ranking'] as $p) {
                    fputcsv($handle, [
                        $p['rank'],
                        $p['nama'],
                        $p['email'],
                        $p['namakec'],
                        $p['pml_nama'],
                        $p['severity_label'],
                        $p['total_clusters'],
                        $p['total_btt_points'] ?? 0,
                        $p['total_bku_points'] ?? 0,
                        $p['max_cluster_size'],
                        $p['total_anomali_points'],
                        $p['top_cluster_lat'] . ', ' . $p['top_cluster_lon'],
                    ]);
                }
            } elseif ($type === 'titik' || $type === 'detail_bangunan') {
                // Export Detail Setiap Titik Bangunan di dalam Klaster
                fputcsv($handle, [
                    'No', 'ID Klaster', 'Label Klaster', 'Nama Petugas', 'Email Petugas', 'Kecamatan',
                    'Kode Desa', 'Nama Desa', 'Kode SLS', 'Nama SLS', 'Kode Sub-SLS',
                    'ID Assignment', 'No Bangunan', 'Nama Usaha / Responden',
                    'Jenis Bangunan', 'Tipe Anomali', 'Latitude Titik', 'Longitude Titik', 'Akurasi GPS (meter)', 'Google Maps Link Titik'
                ]);

                $pointNo = 1;
                $targetClusterId = $request->get('cluster_id');

                foreach ($viewData['clusters'] as $c) {
                    if (!empty($targetClusterId) && $c['id'] !== $targetClusterId) {
                        continue;
                    }

                    $clusterLabel = $c['cluster_title'] ?? ('Klaster #' . ($c['officer_cluster_num'] ?? 1));

                    foreach ($c['points'] as $pt) {
                        $pLat = $pt[0] ?? $c['center_lat'];
                        $pLon = $pt[1] ?? $c['center_lon'];
                        $bType = $pt[3] ?? 'lainnya';
                        $bLabel = $pt[4] ?? '-';
                        $namaAssign = $pt[6] ?? '';
                        $noBang = $pt[7] ?? '';
                        $fullAssignId = $pt[8] ?? ($pt[2] ?? '');
                        $subSls = $pt[9] ?? ($c['id_sub_sls'] ?? '');
                        $ptDesa = !empty($pt[10]) && $pt[10] !== '-' ? $pt[10] : ($c['namadesa'] ?? '');
                        $ptSls = !empty($pt[11]) && $pt[11] !== '-' ? $pt[11] : ($c['namasls'] ?? '');
                        $pAcc = $pt[12] ?? ($c['avg_accuracy'] ?? '');

                        $bTypeName = match($bType) {
                            'bku' => 'BKU (Khusus Usaha)',
                            'btt' => 'BTT (Tempat Tinggal)',
                            'campuran' => 'Campuran (Usaha & Hunian)',
                            default => 'Lainnya / Bangunan Rusak',
                        };

                        fputcsv($handle, [
                            $pointNo++,
                            $c['id'],
                            $clusterLabel,
                            $c['nama_petugas'],
                            $c['email'],
                            $c['namakec'],
                            $c['kodedesa'] ?? '',
                            $ptDesa,
                            $c['kodesls'] ?? '',
                            $ptSls,
                            $subSls,
                            $fullAssignId,
                            $noBang,
                            $namaAssign,
                            $bTypeName,
                            $bLabel,
                            $pLat,
                            $pLon,
                            $pAcc,
                            "https://www.google.com/maps?q={$pLat},{$pLon}&z=20&t=k",
                        ]);
                    }
                }
            } else {
                fputcsv($handle, [
                    'ID Klaster', 'Label Klaster', 'Nama Petugas', 'Email', 'Kecamatan',
                    'Kode Desa', 'Nama Desa', 'Kode SLS', 'Nama SLS', 'Kode Sub-SLS', 'Landmark / Usaha Utama',
                    'PML (Pengawas)', 'Klasifikasi Fraud', 'Komposisi', 'Titik BTT (Rumah)', 'Titik BKU (Pasar)',
                    'Tingkat Keparahan', 'Jumlah Titik Bertumpuk', 'Lat Pusat', 'Lon Pusat',
                    'Radius Sebaran (meter)', 'Akurasi GPS (meter)', 'Google Maps Link'
                ]);

                foreach ($viewData['clusters'] as $c) {
                    fputcsv($handle, [
                        $c['id'],
                        $c['cluster_title'] ?? ('Klaster #' . ($c['officer_cluster_num'] ?? 1)),
                        $c['nama_petugas'],
                        $c['email'],
                        $c['namakec'],
                        $c['kodedesa'] ?? '',
                        $c['namadesa'] ?? '',
                        $c['kodesls'] ?? '',
                        $c['namasls'] ?? '',
                        $c['id_sub_sls'] ?? '',
                        $c['landmark'] ?? '',
                        $c['pml_nama'],
                        $c['fraud_label'] ?? '-',
                        $c['fraud_summary'] ?? '-',
                        $c['btt_count'] ?? 0,
                        $c['bku_count'] ?? 0,
                        $c['severity_label'],
                        $c['cluster_size'],
                        $c['center_lat'],
                        $c['center_lon'],
                        $c['approx_radius_m'],
                        $c['avg_accuracy'],
                        $c['google_maps_url'],
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
