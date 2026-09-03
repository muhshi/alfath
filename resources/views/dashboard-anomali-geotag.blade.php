@extends('tablar::page')

@section('title', 'Deteksi Anomali Geotag & Klaster Titik SE2026')

@push('css')
    <!-- Leaflet CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        .anomali-card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            background: #ffffff;
            transition: all 0.2s ease-in-out;
        }

        .stat-card {
            border-radius: 10px;
            padding: 1.1rem 1.25rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
            transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
        }
        .stat-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
        }
        .stat-card.stat-danger::before { background-color: #dc2626; }
        .stat-card.stat-orange::before { background-color: #ea580c; }
        .stat-card.stat-warning::before { background-color: #ca8a04; }
        .stat-card.stat-info::before { background-color: #0284c7; }

        #anomali-map {
            height: 620px;
            min-height: 620px;
            width: 100%;
            border-radius: 10px;
            z-index: 1;
            background-color: #f1f5f9;
        }

        .map-legend {
            background: rgba(255, 255, 255, 0.95);
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 0.8rem;
            line-height: 1.5;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }
        .legend-circle {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            border: 1px solid rgba(0,0,0,0.3);
        }

        .cluster-list-container {
            max-height: 620px;
            overflow-y: auto;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .cluster-item {
            cursor: pointer;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s ease-in-out;
        }
        .cluster-item:hover {
            background-color: #f8fafc;
        }
        .cluster-item.active {
            background-color: #eff6ff;
            border-left: 3px solid #0284c7;
        }

        .nav-pills .nav-link {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 1.25rem;
            color: #475569;
            transition: all 0.2s;
        }
        .nav-pills .nav-link.active {
            background-color: #0f172a;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.2);
        }

        .leaflet-popup-content-wrapper {
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            padding: 4px;
        }
        .popup-custom-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .popup-stat-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.8125rem;
            padding: 2px 0;
            border-bottom: 1px dashed #e2e8f0;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header Title & Action Buttons -->
    <div class="row align-items-center mb-3 g-2">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-lt px-2 py-1 fw-bold fs-6">SE2026 Geotag Audit</span>
                <span class="text-muted small">| Diperbarui: {{ $generated_at }}</span>
            </div>
            <h2 class="page-title mt-1 fw-bold text-dark">
                <i class="ti ti-map-pin-off text-danger me-1"></i> Deteksi Anomali Geotag & Klaster Koordinat Petugas
            </h2>
            <p class="text-muted small mb-0">
                Peta investigasi geospasial mendeteksi titik geotag yang bertumpuk pada satu lokasi sempit (< 15 meter) yang mengindikasikan pendataan statis (bukan door-to-door).
            </p>
        </div>
        <div class="col-md-5 text-md-end d-flex flex-wrap justify-content-md-end gap-2">
            <a href="{{ route('dashboard.anomali-geotag', array_merge(request()->except('fresh'), ['fresh' => 1])) }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-refresh me-1"></i> Refresh Cache
            </a>
            <div class="dropdown">
                <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-download me-1"></i> Ekspor CSV Laporan
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('dashboard.anomali-geotag.export', array_merge(request()->all(), ['type' => 'clusters'])) }}">
                            <i class="ti ti-map-pin text-primary me-2"></i> Ekspor 734 Klaster Anomali
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('dashboard.anomali-geotag.export', array_merge(request()->all(), ['type' => 'petugas'])) }}">
                            <i class="ti ti-users text-danger me-2"></i> Ekspor Ranking 252 Petugas Terindikasi
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Alert Edukasi / Disclaimer Karakteristik Lapangan (Pasar Tradisional vs Domisili) -->
    <div class="card mb-4 border-0 shadow-sm" style="border-left: 5px solid #0284c7 !important; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-start">
                <div class="me-3 d-flex align-items-center justify-content-center rounded-3 bg-blue-lt p-2 flex-shrink-0" style="width: 44px; height: 44px; color: #0284c7;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-info-circle" width="28" height="28" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                        <path d="M12 9h.01" />
                        <path d="M11 12h1v4h1" />
                    </svg>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h4 class="fw-bold mb-0 text-dark" style="font-size: 1rem; color: #0f172a !important;">
                            Catatan Penting Pemeriksaan Lapangan (Verifikasi Pasar vs Domisili Petugas)
                        </h4>
                        <span class="badge bg-blue-lt text-primary fw-bold" style="font-size: 0.75rem;">Panduan Verifikasi</span>
                    </div>
                    <div class="small" style="line-height: 1.65; color: #334155 !important;">
                        Klaster titik koordinat bertumpuk merupakan indikasi awal. Pengawas Lapangan (PML/Koseka) disarankan <strong>selalu memverifikasi fisik lokasi melalui tombol "Cek Satelit"</strong> sebelum mengambil tindakan pembinaan, karena pada <strong style="color: #0369a1;">Pasar Tradisional</strong> (seperti Pasar Bintoro, Pasar Sayung, Pasar Karanganyar) atau <strong style="color: #0369a1;">Sentra Pertokoan/Ruko Padat</strong>, kumpulan titik geotag adalah hal yang <span class="badge bg-success-lt text-success fw-bold px-2 py-0">Wajar / Valid</span> mengingat letak kios usaha yang saling bersebelahan. Sebaliknya, jika titik bertumpuk berada di <strong style="color: #b91c1c;">Rumah Tinggal Pribadi, Warkop, atau Sawah</strong>, hal tersebut baru diindikasikan sebagai <span class="badge bg-danger-lt text-danger fw-bold px-2 py-0">Anomali Tidak Door-to-Door</span>.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-danger">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted fw-semibold small text-uppercase">Total Geotag Teranomali</span>
                    <span class="badge bg-danger-lt px-2 py-1"><i class="ti ti-map-pin"></i> 13 Batch</span>
                </div>
                <h2 class="fw-bold mb-1 text-danger">{{ number_format($kpi['total_points'], 0, ',', '.') }}</h2>
                <div class="text-muted small">Titik geotag bertumpuk di titik statis</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-orange">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted fw-semibold small text-uppercase">Total Klaster Anomali</span>
                    <span class="badge bg-orange-lt px-2 py-1"><i class="ti ti-circles"></i> Sebaran</span>
                </div>
                <h2 class="fw-bold mb-1 text-dark">{{ number_format($kpi['total_clusters'], 0, ',', '.') }}</h2>
                <div class="text-muted small">Lokasi/spot kumpulan titik tumpuk</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-warning">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted fw-semibold small text-uppercase">Petugas Terindikasi</span>
                    <span class="badge bg-warning-lt px-2 py-1"><i class="ti ti-user-exclamation"></i> PPL</span>
                </div>
                <h2 class="fw-bold mb-1 text-dark">{{ number_format($kpi['total_petugas'], 0, ',', '.') }}</h2>
                <div class="text-muted small">Pencacah dengan $\ge 1$ klaster anomali</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-info">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted fw-semibold small text-uppercase">Klaster Terbesar</span>
                    <span class="badge bg-azure-lt px-2 py-1"><i class="ti ti-flame"></i> Rekor</span>
                </div>
                <h2 class="fw-bold mb-1 text-primary">{{ number_format($kpi['max_cluster'], 0, ',', '.') }} <span class="fs-4 text-muted fw-normal">titik</span></h2>
                <div class="text-muted small">Titik terbanyak di satu koordinat mikro</div>
            </div>
        </div>
    </div>

    <!-- Filter Control Bar -->
    <div class="card anomali-card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('dashboard.anomali-geotag') }}" id="filterForm">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Filter Kecamatan:</label>
                        <select name="kecamatan" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Kecamatan (14 Kecamatan) --</option>
                            @foreach ($kecNameMap as $kCode => $kName)
                                <option value="{{ $kCode }}" {{ $selectedKec === $kCode ? 'selected' : '' }}>
                                    {{ $kCode }} - {{ $kName }}
                                </option>
                            @endforeach
                            <option value="other" {{ $selectedKec === 'other' ? 'selected' : '' }}>Lainnya / Tidak Terpetakan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Tingkat Keparahan (Severity):</label>
                        <select name="severity" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Tingkat Keparahan --</option>
                            <option value="ekstrem" {{ $selectedSeverity === 'ekstrem' ? 'selected' : '' }}>🚨 Ekstrem (> 100 Titik)</option>
                            <option value="berat" {{ $selectedSeverity === 'berat' ? 'selected' : '' }}>⚠️ Berat (51 - 100 Titik)</option>
                            <option value="sedang" {{ $selectedSeverity === 'sedang' ? 'selected' : '' }}>🟡 Sedang (21 - 50 Titik)</option>
                            <option value="ringan" {{ $selectedSeverity === 'ringan' ? 'selected' : '' }}>🔵 Ringan (10 - 20 Titik)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Pencarian Petugas / PML / Email:</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Ketik nama petugas / email..." value="{{ $search }}">
                            <button class="btn btn-primary" type="submit"><i class="ti ti-search me-1"></i> Cari</button>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        @if(!empty($selectedKec) || !empty($selectedSeverity) || !empty($search))
                            <a href="{{ route('dashboard.anomali-geotag') }}" class="btn btn-outline-danger btn-sm w-100" title="Reset Filter">
                                <i class="ti ti-x"></i> Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-pills mb-3" id="anomaliTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="map-tab" data-bs-toggle="pill" data-bs-target="#mapTabContent" type="button" role="tab">
                <i class="ti ti-map-2 me-1"></i> Peta Geospasial Klaster ({{ count($clusters) }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="petugas-tab" data-bs-toggle="pill" data-bs-target="#petugasTabContent" type="button" role="tab">
                <i class="ti ti-trophy text-warning me-1"></i> Ranking Petugas Terindikasi ({{ count($petugas_ranking) }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="kecamatan-tab" data-bs-toggle="pill" data-bs-target="#kecamatanTabContent" type="button" role="tab">
                <i class="ti ti-chart-bar me-1"></i> Persebaran per Kecamatan
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="anomaliTabContent">
        <!-- TAB 1: PETA GEOSPASIAL & PANEL KLASTER -->
        <div class="tab-pane fade show active" id="mapTabContent" role="tabpanel">
            <div class="row g-3">
                <div class="col-lg-8 col-xl-9">
                    <div class="card anomali-card p-2 position-relative">
                        <div id="anomali-map"></div>
                    </div>
                </div>
                <div class="col-lg-4 col-xl-3">
                    <div class="card anomali-card">
                        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-uppercase text-muted">Klaster Terpilih</span>
                            <span class="badge bg-secondary-lt fw-normal">{{ count($clusters) }} spot</span>
                        </div>
                        <div class="cluster-list-container" id="clusterList">
                            @forelse($clusters as $idx => $c)
                                <div class="cluster-item {{ $idx === 0 ? 'active' : '' }}" 
                                     id="cItem_{{ $c['id'] }}"
                                     onclick="focusCluster({{ $c['center_lat'] }}, {{ $c['center_lon'] }}, '{{ $c['id'] }}')">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="badge {{ $c['badge_class'] }} small py-0 px-1">
                                            {{ $c['cluster_size'] }} Titik Bertumpuk
                                        </span>
                                        <span class="text-muted small" style="font-size: 0.75rem;">
                                            Radius ~{{ $c['approx_radius_m'] }}m
                                        </span>
                                    </div>
                                    <div class="fw-bold text-dark small text-truncate" title="{{ $c['nama_petugas'] }}">
                                        {{ $c['nama_petugas'] }}
                                    </div>
                                    <div class="text-muted small d-flex justify-content-between align-items-center" style="font-size: 0.775rem;">
                                        <span><i class="ti ti-map-pin text-primary"></i> {{ $c['namakec'] }}</span>
                                        <a href="{{ $c['google_maps_url'] }}" target="_blank" rel="noopener noreferrer" class="text-azure text-decoration-none fw-semibold" onclick="event.stopPropagation()">
                                            <i class="ti ti-external-link"></i> Satelit
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-muted small">
                                    <i class="ti ti-search-off fs-2 d-block mb-1"></i>
                                    Tidak ada klaster anomali yang sesuai filter.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: RANKING PETUGAS TERINDIKASI -->
        <div class="tab-pane fade" id="petugasTabContent" role="tabpanel">
            <div class="card anomali-card">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div>
                            <h3 class="card-title fw-bold mb-0">Peringkat Petugas dengan Akumulasi Geotag Anomali Tertinggi</h3>
                            <div class="text-muted small">Diurutkan berdasarkan akumulasi titik tumpuk dan ukuran klaster terbesar.</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-3">
                    <table class="table table-vcenter table-hover table-striped" id="petugasRankingTable" style="width:100%">
                        <thead>
                            <tr class="bg-light text-muted small text-uppercase">
                                <th class="text-center" style="width: 50px;">Rank</th>
                                <th>Nama Petugas (PPL)</th>
                                <th>Kecamatan</th>
                                <th>Pengawas (PML)</th>
                                <th class="text-center">Tingkat Risiko</th>
                                <th class="text-center">Total Klaster</th>
                                <th class="text-center">Titik Terbanyak 1 Lokasi</th>
                                <th class="text-center">Total Titik Anomali</th>
                                <th class="text-center" style="width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($petugas_ranking as $p)
                                <tr>
                                    <td class="text-center fw-bold">
                                        @if($p['rank'] === 1)
                                            <span class="badge bg-danger text-white">#1</span>
                                        @elseif($p['rank'] === 2)
                                            <span class="badge bg-orange text-white">#2</span>
                                        @elseif($p['rank'] === 3)
                                            <span class="badge bg-warning text-dark">#3</span>
                                        @else
                                            <span class="text-muted">#{{ $p['rank'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $p['nama'] }}</div>
                                        <div class="text-muted small" style="font-size: 0.775rem;">{{ $p['email'] }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-blue-lt">{{ $p['namakec'] }}</span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold">{{ $p['pml_nama'] }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $p['badge_class'] }} px-2 py-1 small">
                                            {{ $p['severity_label'] }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold text-secondary">
                                        {{ $p['total_clusters'] }} spot
                                    </td>
                                    <td class="text-center fw-bold text-danger">
                                        {{ $p['max_cluster_size'] }} titik
                                    </td>
                                    <td class="text-center fw-bold text-dark fs-6">
                                        {{ number_format($p['total_anomali_points'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary" onclick="locatePetugasOnMap({{ $p['top_cluster_lat'] }}, {{ $p['top_cluster_lon'] }}, '{{ $p['top_cluster_id'] }}')">
                                            <i class="ti ti-crosshair me-1"></i> Cek di Peta
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: REKAP PER KECAMATAN -->
        <div class="tab-pane fade" id="kecamatanTabContent" role="tabpanel">
            <div class="card anomali-card">
                <div class="card-header bg-white py-3">
                    <h3 class="card-title fw-bold mb-0">Rekapitulasi Persebaran Klaster Anomali per Kecamatan di Kab. Demak</h3>
                    <div class="text-muted small">Agregasi untuk monitoring sebaran beban pengawasan lapangan per wilayah.</div>
                </div>
                <div class="table-responsive p-3">
                    <table class="table table-vcenter table-hover table-striped">
                        <thead>
                            <tr class="bg-light text-muted small text-uppercase">
                                <th>Kecamatan</th>
                                <th class="text-center">Jumlah Klaster Bertumpuk</th>
                                <th class="text-center">Jumlah Petugas Terindikasi</th>
                                <th class="text-center">Klaster Terbesar</th>
                                <th class="text-center">Total Titik Geotag Anomali</th>
                                <th class="text-center">Proporsi Terhadap Total (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalKecPoints = array_sum(array_column($kecamatan_summary, 'total_points')); @endphp
                            @foreach($kecamatan_summary as $k)
                                @php
                                    $pct = $totalKecPoints > 0 ? round(($k['total_points'] / $totalKecPoints) * 100, 1) : 0;
                                @endphp
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <i class="ti ti-map-pin text-primary me-1"></i> {{ $k['name'] }}
                                        @if($k['code'] !== 'other')
                                            <span class="badge bg-secondary-lt ms-1" style="font-size:0.7rem;">{{ $k['code'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold text-secondary">
                                        {{ $k['total_clusters'] }} klaster
                                    </td>
                                    <td class="text-center fw-bold text-primary">
                                        {{ $k['total_petugas'] }} orang
                                    </td>
                                    <td class="text-center fw-bold text-danger">
                                        {{ $k['max_cluster_size'] > 0 ? $k['max_cluster_size'] . ' titik' : '-' }}
                                    </td>
                                    <td class="text-center fw-bold fs-6 text-dark">
                                        {{ number_format($k['total_points'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <div class="progress progress-sm flex-grow-1" style="max-width: 120px; height: 6px;">
                                                <div class="progress-bar bg-danger" style="width: {{ $pct }}%"></div>
                                            </div>
                                            <span class="small fw-semibold">{{ $pct }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
    <!-- jQuery CDN (Required by DataTables & reliable DOM operations) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Leaflet JS CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

    <!-- DataTables CDN with AMD safeguard -->
    <script>
        window._tempDefine = window.define;
        window.define = null;
    </script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        window.define = window._tempDefine;
    </script>

    <script>
        // Pre-loaded clusters from PHP
        const clustersData = {!! json_encode($clusters, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
        let mapInstance = null;
        let markersById = {};

        document.addEventListener('DOMContentLoaded', function () {
            // 1. Initialize Leaflet Map FIRST and independently
            try {
                initLeafletMap();
            } catch (err) {
                console.error("Leaflet initialization failed:", err);
            }

            // 2. Initialize DataTables for Petugas Ranking inside try-catch
            try {
                if (window.jQuery && $.fn && $.fn.DataTable) {
                    $('#petugasRankingTable').DataTable({
                        pageLength: 15,
                        lengthMenu: [10, 15, 25, 50, 100],
                        language: {
                            search: "Cari:",
                            lengthMenu: "Tampilkan _MENU_ data",
                            info: "Menampilkan _START_ s.d _END_ dari _TOTAL_ petugas",
                            infoEmpty: "Data kosong",
                            zeroRecords: "Tidak ada petugas yang cocok",
                            paginate: {
                                first: "Pertama",
                                last: "Terakhir",
                                next: "Berikutnya",
                                previous: "Sebelumnya"
                            }
                        }
                    });
                }
            } catch (dtErr) {
                console.warn("DataTables initialization warning:", dtErr);
            }

            // 3. Refresh map dimensions when Tab 1 becomes visible
            const mapTabBtn = document.getElementById('map-tab');
            if (mapTabBtn) {
                mapTabBtn.addEventListener('shown.bs.tab', function () {
                    if (mapInstance) {
                        setTimeout(() => { mapInstance.invalidateSize(); }, 150);
                    }
                });
            }
        });

        function initLeafletMap() {
            const mapContainer = document.getElementById('anomali-map');
            if (!mapContainer) return;

            // Default center Kab. Demak coordinates
            const demakCenter = [-6.8944, 110.6385];
            mapInstance = L.map('anomali-map', {
                center: demakCenter,
                zoom: 11,
                scrollWheelZoom: true
            });

            // Base Layer: OpenStreetMap (Standard default, highly reliable)
            const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(mapInstance);

            // Base Layer: Carto Positron (Clean Light)
            const cartoPositron = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                maxZoom: 20,
                subdomains: 'abcd',
                attribution: '&copy; CartoDB'
            });

            // Layer Control
            L.control.layers({
                "OpenStreetMap (Default)": osm,
                "Carto Clean Light": cartoPositron
            }, null, { position: 'topright' }).addTo(mapInstance);

            // Add Custom Map Legend (Bottom Left)
            const legend = L.control({ position: 'bottomleft' });
            legend.onAdd = function () {
                const div = L.DomUtil.create('div', 'map-legend');
                div.innerHTML = `
                    <div class="fw-bold mb-1" style="font-size:0.8rem;">Tingkat Keparahan Klaster:</div>
                    <div class="legend-item"><span class="legend-circle" style="background:#dc2626;"></span> Ekstrem (&gt; 100 Titik)</div>
                    <div class="legend-item"><span class="legend-circle" style="background:#ea580c;"></span> Berat (51 - 100 Titik)</div>
                    <div class="legend-item"><span class="legend-circle" style="background:#ca8a04;"></span> Sedang (21 - 50 Titik)</div>
                    <div class="legend-item"><span class="legend-circle" style="background:#0284c7;"></span> Ringan (10 - 20 Titik)</div>
                `;
                return div;
            };
            legend.addTo(mapInstance);

            // Render Circle Markers for each cluster
            renderClusterMarkers();

            // Force recalculation of map container dimensions
            setTimeout(() => { if (mapInstance) mapInstance.invalidateSize(); }, 250);
            setTimeout(() => { if (mapInstance) mapInstance.invalidateSize(); }, 650);
        }

        function renderClusterMarkers() {
            if (!clustersData || !Array.isArray(clustersData) || clustersData.length === 0) return;

            const group = new L.featureGroup();

            clustersData.forEach(c => {
                if (!c || isNaN(parseFloat(c.center_lat)) || isNaN(parseFloat(c.center_lon))) {
                    return;
                }

                const lat = parseFloat(c.center_lat);
                const lon = parseFloat(c.center_lon);
                const size = parseInt(c.cluster_size) || 1;

                // Sizing circle radius based on cluster_size
                const r = Math.min(26, Math.max(7, Math.sqrt(size) * 2.2));

                const circle = L.circleMarker([lat, lon], {
                    radius: r,
                    fillColor: c.marker_color || '#dc2626',
                    color: '#ffffff',
                    weight: 1.5,
                    opacity: 0.9,
                    fillOpacity: 0.75
                });

                // Tooltip on Hover
                circle.bindTooltip(`<strong>${c.nama_petugas}</strong>: ${size} titik bertumpuk (${c.namakec})`, {
                    direction: 'top',
                    offset: [0, -r]
                });

                // Rich Popup on Click
                const popupContent = `
                    <div style="min-width: 250px; font-family: inherit;">
                        <div class="popup-custom-title d-flex justify-content-between align-items-center">
                            <span>${c.nama_petugas}</span>
                            <span class="badge ${c.badge_class}" style="font-size:0.75rem;">${size} Titik</span>
                        </div>
                        <div class="text-muted small mb-2" style="font-size:0.75rem;">${c.email}</div>
                        
                        <div class="popup-stat-row">
                            <span class="text-muted">Kecamatan:</span>
                            <span class="fw-semibold">${c.namakec}</span>
                        </div>
                        <div class="popup-stat-row">
                            <span class="text-muted">Pengawas (PML):</span>
                            <span class="fw-semibold">${c.pml_nama}</span>
                        </div>
                        <div class="popup-stat-row">
                            <span class="text-muted">Radius Sebaran:</span>
                            <span class="fw-semibold text-danger">~${c.approx_radius_m} meter</span>
                        </div>
                        <div class="popup-stat-row">
                            <span class="text-muted">Akurasi GPS Rata-rata:</span>
                            <span class="fw-semibold">${c.avg_accuracy} m</span>
                        </div>
                        <div class="popup-stat-row">
                            <span class="text-muted">Koordinat:</span>
                            <span class="font-monospace small">${lat.toFixed(5)}, ${lon.toFixed(5)}</span>
                        </div>

                        <div class="mt-3 pt-2 border-top d-flex gap-2">
                            <a href="${c.google_maps_url}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary flex-grow-1" style="font-size: 0.775rem;">
                                <i class="ti ti-world me-1"></i> Cek Satelit (Pasar/Rumah)
                            </a>
                            <button class="btn btn-sm btn-primary" onclick="mapInstance.setView([${lat}, ${lon}], 18)" style="font-size: 0.775rem;">
                                <i class="ti ti-zoom-in"></i> Zoom
                            </button>
                        </div>
                    </div>
                `;

                circle.bindPopup(popupContent);

                // Highlight active item on list when marker clicked
                circle.on('click', () => {
                    highlightListItem(c.id);
                });

                markersById[c.id] = circle;
                circle.addTo(mapInstance);
                group.addLayer(circle);
            });

            // Adjust bounds to fit all markers if any
            if (clustersData.length > 0 && group.getLayers().length > 0) {
                try {
                    mapInstance.fitBounds(group.getBounds().pad(0.08));
                } catch (bErr) {
                    console.warn("fitBounds warning:", bErr);
                }
            }
        }

        function focusCluster(lat, lon, id) {
            if (!mapInstance) return;

            mapInstance.flyTo([lat, lon], 17, {
                animate: true,
                duration: 1.0
            });

            if (markersById[id]) {
                markersById[id].openPopup();
            }

            highlightListItem(id);
        }

        function highlightListItem(id) {
            document.querySelectorAll('.cluster-item').forEach(el => el.classList.remove('active'));
            const targetEl = document.getElementById('cItem_' + id);
            if (targetEl) {
                targetEl.classList.add('active');
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function locatePetugasOnMap(lat, lon, topClusterId) {
            // Switch to Tab 1 (Map)
            const mapTabTrigger = document.querySelector('#map-tab');
            if (mapTabTrigger) {
                const tab = new bootstrap.Tab(mapTabTrigger);
                tab.show();
            }

            // Wait brief moment for tab animation then flyTo
            setTimeout(() => {
                focusCluster(lat, lon, topClusterId);
            }, 300);
        }
    </script>
@endpush
