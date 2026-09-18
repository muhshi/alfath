@extends('tablar::page')

@section('title', 'Identifikasi & Evaluasi Hasil Pendataan SE2026')

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        .qc-stat-card {
            border-radius: 14px;
            transition: all 0.2s ease-in-out;
            border: 1px solid rgba(0,0,0,0.06);
            cursor: pointer;
            text-decoration: none !important;
        }
        .qc-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08) !important;
        }
        .qc-stat-card.active-filter {
            border: 2px solid #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2) !important;
        }
        .table-qc th {
            font-size: 0.76rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            vertical-align: middle;
        }
        .table-qc td {
            font-size: 0.85rem;
            vertical-align: middle;
        }
        .badge-qc {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.35rem 0.55rem;
            border-radius: 6px;
        }
    </style>
@endpush

@section('content')
    <!-- PAGE HEADER -->
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle text-primary font-weight-bold d-flex align-items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shield-search" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M12 21a12 12 0 0 1 -8.5 -15a12 12 0 0 1 8.5 -3a12 12 0 0 1 8.5 3c.539 1.832 .627 3.756 .263 5.615"/><path d="M18 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M20.2 20.2l1.8 1.8"/></svg>
                        QUALITY CONTROL & EVALUASI SLS SE2026
                    </div>
                    <h2 class="page-title text-dark font-weight-extrabold fs-2">
                        Identifikasi Hasil Pendataan SLS
                    </h2>
                    <div class="text-muted small mt-1">
                        Deteksi dini anomali hasil pendataan lapangan: Muatan Murni &lt; 80% Prelist, Lonjakan Ekstrem &gt; 130%, Usaha Nol, dan Drop Keluarga.
                    </div>
                </div>
                <div class="col-auto ms-auto d-flex flex-wrap gap-2">
                    <a href="{{ route('dashboard.pengolahan') }}" class="btn btn-outline-secondary font-weight-bold shadow-sm rounded-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-left me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/></svg>
                        Kembali ke Dashboard Pengolahan
                    </a>
                    <a href="{{ route('identifikasi.pendataan.export', request()->query()) }}" class="btn btn-success font-weight-bold shadow-sm rounded-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-spreadsheet me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M8 11h8"/><path d="M8 15h8"/><path d="M11 11v8"/></svg>
                        Export Excel Hasil QC
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN BODY -->
    <div class="page-body">
        <div class="container-xl">

            <!-- STATISTIC & ANOMALY KPI CARDS -->
            <div class="row row-deck row-cards g-2 mb-3">
                
                <!-- Card 1: Utama - Murni < 80% Prelist -->
                <div class="col-6 col-md-3">
                    <a href="{{ request()->fullUrlWithQuery(['kategori' => 'under_80']) }}" 
                       class="card qc-stat-card shadow-sm bg-danger-lt {{ $filterKategori === 'under_80' ? 'active-filter' : '' }}">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-danger text-white font-weight-bold px-2 py-0.5 rounded-pill fs-5">
                                    🚨 FOKUS UTAMA
                                </span>
                                <span class="text-danger small font-weight-bold">
                                    {{ $summary['total_sls'] > 0 ? number_format(($summary['cnt_under_80'] / $summary['total_sls']) * 100, 1) : 0 }}%
                                </span>
                            </div>
                            <div class="h2 m-0 font-weight-extrabold text-danger">
                                {{ number_format($summary['cnt_under_80']) }} <span class="fs-4 font-weight-normal text-muted">SLS</span>
                            </div>
                            <div class="text-danger font-weight-bold mt-1" style="font-size: 0.82rem;">
                                Muatan Murni &lt; 80% Prelist
                            </div>
                            <div class="text-muted small" style="font-size: 0.72rem;">
                                Potensi undercoverage / responden terlewat
                                @if($hideNonSls)
                                    <span class="badge bg-blue-lt text-blue py-0 px-1 font-weight-normal ms-1">Non-SLS Di-hide</span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Card 2: Lonjakan > 130% Prelist -->
                <div class="col-6 col-md-3">
                    <a href="{{ request()->fullUrlWithQuery(['kategori' => 'over_130']) }}" 
                       class="card qc-stat-card shadow-sm bg-warning-lt {{ $filterKategori === 'over_130' ? 'active-filter' : '' }}">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-warning text-dark font-weight-bold px-2 py-0.5 rounded-pill fs-5">
                                    📈 LONJAKAN
                                </span>
                                <span class="text-warning small font-weight-bold">
                                    {{ $summary['total_sls'] > 0 ? number_format(($summary['cnt_over_130'] / $summary['total_sls']) * 100, 1) : 0 }}%
                                </span>
                            </div>
                            <div class="h2 m-0 font-weight-extrabold text-dark">
                                {{ number_format($summary['cnt_over_130']) }} <span class="fs-4 font-weight-normal text-muted">SLS</span>
                            </div>
                            <div class="text-warning font-weight-bold mt-1" style="font-size: 0.82rem;">
                                Muatan Murni &gt; 130% Prelist
                            </div>
                            <div class="text-muted small" style="font-size: 0.72rem;">
                                Potensi salah batas SLS / duplikasi
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Card 3: Usaha Nol (Zero Usaha) -->
                <div class="col-6 col-md-3">
                    <a href="{{ request()->fullUrlWithQuery(['kategori' => 'zero_usaha']) }}" 
                       class="card qc-stat-card shadow-sm bg-purple-lt {{ $filterKategori === 'zero_usaha' ? 'active-filter' : '' }}">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-purple text-white font-weight-bold px-2 py-0.5 rounded-pill fs-5">
                                    🟣 NOL USAHA
                                </span>
                                <span class="text-purple small font-weight-bold">
                                    {{ $summary['total_sls'] > 0 ? number_format(($summary['cnt_zero_usaha'] / $summary['total_sls']) * 100, 1) : 0 }}%
                                </span>
                            </div>
                            <div class="h2 m-0 font-weight-extrabold text-purple">
                                {{ number_format($summary['cnt_zero_usaha']) }} <span class="fs-4 font-weight-normal text-muted">SLS</span>
                            </div>
                            <div class="text-purple font-weight-bold mt-1" style="font-size: 0.82rem;">
                                Usaha SE Nol (0 Usaha)
                            </div>
                            <div class="text-muted small" style="font-size: 0.72rem;">
                                Padahal tercatat ada potensi di Wilkerstat
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Card 4: Usaha Drop Ekstrem vs Wilkerstat -->
                <div class="col-6 col-md-3">
                    <a href="{{ request()->fullUrlWithQuery(['kategori' => 'usaha_drop']) }}" 
                       class="card qc-stat-card shadow-sm bg-orange-lt {{ $filterKategori === 'usaha_drop' ? 'active-filter' : '' }}">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-orange text-white font-weight-bold px-2 py-0.5 rounded-pill fs-5">
                                    📉 DROP USAHA
                                </span>
                                <span class="text-orange small font-weight-bold">
                                    {{ $summary['total_sls'] > 0 ? number_format(($summary['cnt_usaha_drop'] / $summary['total_sls']) * 100, 1) : 0 }}%
                                </span>
                            </div>
                            <div class="h2 m-0 font-weight-extrabold text-orange">
                                {{ number_format($summary['cnt_usaha_drop']) }} <span class="fs-4 font-weight-normal text-muted">SLS</span>
                            </div>
                            <div class="text-orange font-weight-bold mt-1" style="font-size: 0.82rem;">
                                Usaha SE &lt; Wilkerstat (&gt;30%)
                            </div>
                            <div class="text-muted small" style="font-size: 0.72rem;">
                                Defisit temuan usaha dibanding patokan
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Mini Cards Row -->
                <div class="col-6 col-sm-3 col-md-3">
                    <a href="{{ request()->fullUrlWithQuery(['kategori' => 'keluarga_drop']) }}" 
                       class="card qc-stat-card shadow-sm bg-light {{ $filterKategori === 'keluarga_drop' ? 'active-filter' : '' }}">
                        <div class="card-body p-2 text-center">
                            <div class="text-muted small font-weight-bold">Drop Keluarga &ge;15%</div>
                            <div class="h3 m-0 font-weight-extrabold text-danger">{{ number_format($summary['cnt_keluarga_drop']) }} SLS</div>
                            <div class="text-muted small" style="font-size: 0.70rem;">Keluarga tdk ditemukan tinggi</div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-sm-3 col-md-3">
                    <a href="{{ request()->fullUrlWithQuery(['kategori' => 'ganda']) }}" 
                       class="card qc-stat-card shadow-sm bg-pink-lt {{ $filterKategori === 'ganda' ? 'active-filter' : '' }}">
                        <div class="card-body p-2 text-center">
                            <div class="text-pink small font-weight-bold">Khusus Ganda &gt; 0</div>
                            <div class="h3 m-0 font-weight-extrabold text-pink">{{ number_format($summary['cnt_ganda']) }} SLS</div>
                            <div class="text-muted small" style="font-size: 0.70rem;">BKU/UK duplikat tercatat</div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-sm-3 col-md-3">
                    <a href="{{ request()->fullUrlWithQuery(['kategori' => 'bangunan_lainnya']) }}" 
                       class="card qc-stat-card shadow-sm bg-amber-lt {{ $filterKategori === 'bangunan_lainnya' ? 'active-filter' : '' }}">
                        <div class="card-body p-2 text-center">
                            <div class="text-orange small font-weight-bold">Bangunan Kosong &ge;15%</div>
                            <div class="h3 m-0 font-weight-extrabold text-orange">{{ number_format($summary['cnt_bangunan_lainnya']) }} SLS</div>
                            <div class="text-muted small" style="font-size: 0.70rem;">Beban lari ke non-respon</div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-sm-3 col-md-3">
                    <a href="{{ request()->fullUrlWithQuery(['kategori' => 'anomali_only']) }}" 
                       class="card qc-stat-card shadow-sm bg-blue-lt {{ $filterKategori === 'anomali_only' ? 'active-filter' : '' }}">
                        <div class="card-body p-2 text-center">
                            <div class="text-blue small font-weight-bold">Semua SLS Perlu QC</div>
                            <div class="h3 m-0 font-weight-extrabold text-blue">{{ number_format($summary['cnt_total_anomali']) }} SLS</div>
                            <div class="text-muted small" style="font-size: 0.70rem;">Memiliki &ge;1 jenis anomali</div>
                        </div>
                    </a>
                </div>

            </div>

            <!-- FILTER CONTROLS CARD -->
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('identifikasi.pendataan') }}" class="row g-2 align-items-center">
                        
                        <!-- Filter Kategori -->
                        <div class="col-12 col-md-3">
                            <label class="form-label small text-muted font-weight-bold mb-1">Kategori Temuan / QC:</label>
                            <select name="kategori" class="form-select form-select-sm font-weight-bold" onchange="this.form.submit()">
                                <option value="anomali_only" {{ $filterKategori === 'anomali_only' ? 'selected' : '' }}>⚠️ Semua SLS Anomali / Perlu QC ({{ number_format($summary['cnt_total_anomali']) }})</option>
                                <option value="all" {{ $filterKategori === 'all' ? 'selected' : '' }}>📋 Semua SLS ({{ number_format($summary['total_sls']) }})</option>
                                <option value="under_80" {{ $filterKategori === 'under_80' ? 'selected' : '' }}>🚨 Muatan Murni &lt; 80% Prelist ({{ number_format($summary['cnt_under_80']) }})</option>
                                <option value="over_130" {{ $filterKategori === 'over_130' ? 'selected' : '' }}>📈 Lonjakan Muatan &gt; 130% Prelist ({{ number_format($summary['cnt_over_130']) }})</option>
                                <option value="zero_usaha" {{ $filterKategori === 'zero_usaha' ? 'selected' : '' }}>🟣 Usaha SE Nol ({{ number_format($summary['cnt_zero_usaha']) }})</option>
                                <option value="usaha_drop" {{ $filterKategori === 'usaha_drop' ? 'selected' : '' }}>📉 Usaha Drop vs Wilkerstat &gt;30% ({{ number_format($summary['cnt_usaha_drop']) }})</option>
                                <option value="keluarga_drop" {{ $filterKategori === 'keluarga_drop' ? 'selected' : '' }}>🔴 Keluarga Tdk Ditemukan &ge;15% ({{ number_format($summary['cnt_keluarga_drop']) }})</option>
                                <option value="ganda" {{ $filterKategori === 'ganda' ? 'selected' : '' }}>👥 Khusus Ganda Usaha ({{ number_format($summary['cnt_ganda']) }})</option>
                                <option value="bangunan_lainnya" {{ $filterKategori === 'bangunan_lainnya' ? 'selected' : '' }}>🏚️ Bangunan Kosong/Lainnya &ge;15% ({{ number_format($summary['cnt_bangunan_lainnya']) }})</option>
                                <option value="aman" {{ $filterKategori === 'aman' ? 'selected' : '' }}>✅ SLS Wajar / Aman ({{ number_format($summary['cnt_aman']) }})</option>
                            </select>
                        </div>

                        <!-- Filter Tipe Wilayah -->
                        <div class="col-12 col-md-3">
                            <label class="form-label small text-muted font-weight-bold mb-1">Cakupan Wilayah:</label>
                            <select name="tipe_wilayah" class="form-select form-select-sm font-weight-medium" onchange="this.form.submit()">
                                <option value="sls" {{ in_array($filterTipeWilayah, ['sls', 'pemukiman']) ? 'selected' : '' }}>🏡 Hanya SLS ({{ number_format($summary['cnt_sls']) }} SLS) — Fokus Penduduk</option>
                                <option value="all" {{ $filterTipeWilayah === 'all' ? 'selected' : '' }}>🌐 Semua Wilayah ({{ number_format($summary['total_all_sls']) }} SLS & Non-SLS)</option>
                                <option value="non_sls" {{ in_array($filterTipeWilayah, ['non_sls', 'non_pemukiman']) ? 'selected' : '' }}>🌾 Hanya Non-SLS ({{ number_format($summary['cnt_non_sls']) }} Sawah/Hutan)</option>
                            </select>
                        </div>

                        <!-- Filter Kecamatan -->
                        <div class="col-12 col-md-2">
                            <label class="form-label small text-muted font-weight-bold mb-1">Filter Kecamatan:</label>
                            <select name="kodekec" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua (14 Kec)</option>
                                @foreach($kecNameMap as $code => $name)
                                    <option value="{{ $code }}" {{ $kodekec == $code ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Status Submit -->
                        <div class="col-12 col-md-2">
                            <label class="form-label small text-muted font-weight-bold mb-1">Progres Lapangan:</label>
                            <select name="status_submit" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all" {{ $statusSubmitFilter === 'all' ? 'selected' : '' }}>Semua Status</option>
                                <option value="completed" {{ $statusSubmitFilter === 'completed' ? 'selected' : '' }}>🎉 Selesai 100%</option>
                                <option value="in_progress" {{ $statusSubmitFilter === 'in_progress' ? 'selected' : '' }}>⏳ Berjalan (&lt;100%)</option>
                                <option value="open" {{ $statusSubmitFilter === 'open' ? 'selected' : '' }}>🚪 Belum (Open)</option>
                            </select>
                        </div>

                        <!-- Tanggal Data & Submit -->
                        <div class="col-12 col-md-2 d-flex align-items-end gap-1">
                            <div class="flex-grow-1">
                                <label class="form-label small text-muted font-weight-bold mb-1">Tanggal Data:</label>
                                <select name="tanggal_data" class="form-select form-select-sm" onchange="this.form.submit()">
                                    @foreach($availableDates as $d)
                                        <option value="{{ $d }}" {{ $selectedDate == $d ? 'selected' : '' }}>{{ date('d M Y', strtotime($d)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary mt-auto" title="Terapkan Filter">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-filter" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.828 4.828a2 2 0 0 0 -.586 1.414v4.172l-4 2v-6.172a2 2 0 0 0 -.586 -1.414l-4.828 -4.828a2 2 0 0 1 -.586 -1.414z"/></svg>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- TABLE IDENTIFIKASI HASIL SLS -->
            <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h4 class="card-title font-weight-bold text-dark m-0">
                            📋 Daftar Identifikasi Hasil SLS 
                            <span class="badge bg-primary text-white ms-1 rounded-pill">{{ number_format($records->count()) }} SLS</span>
                        </h4>
                        <div class="small text-muted ms-2">
                            Kategori: <strong class="text-primary">{{ strtoupper(str_replace('_', ' ', $filterKategori)) }}</strong>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <!-- Toggle Switch: Sembunyikan Non-SLS -->
                        <a href="{{ request()->fullUrlWithQuery(['hide_non_sls' => ($hideNonSls ? 0 : 1), 'tipe_wilayah' => ($hideNonSls ? 'all' : 'sls')]) }}" 
                           class="btn btn-sm {{ $hideNonSls ? 'btn-outline-primary active' : 'btn-outline-secondary' }} d-flex align-items-center gap-1.5 shadow-xs px-2.5 py-1" 
                           title="{{ $hideNonSls ? 'Klik untuk menampilkan semua wilayah (termasuk Non-SLS sawah/perairan)' : 'Klik untuk menyembunyikan wilayah Non-SLS (fokus SLS penduduk)' }}">
                            <span class="form-check form-switch p-0 m-0 d-inline-flex align-items-center pointer-events-none">
                                <input class="form-check-input ms-0 me-1" type="checkbox" {{ $hideNonSls ? 'checked' : '' }} style="pointer-events: none;">
                            </span>
                            <span class="font-weight-bold" style="font-size: 0.78rem;">
                                {{ $hideNonSls ? '🚫 Non-SLS Tersembunyi (' . number_format($summary['cnt_non_sls']) . ')' : '🌾 Tampilkan Non-SLS (' . number_format($summary['cnt_non_sls']) . ')' }}
                            </span>
                        </a>

                        <span class="text-muted small ms-1">Prelist: <strong>{{ number_format($records->sum('jml_prelist')) }}</strong> | Murni: <strong>{{ number_format($records->sum('muatan_murni')) }}</strong></span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="qc-table" class="table table-vcenter table-striped card-table table-qc text-nowrap w-100">
                            <thead>
                                <tr class="bg-light text-muted">
                                    <th class="w-1 text-center">No</th>
                                    <th>Kecamatan</th>
                                    <th>Kode & Nama SLS / Sub-SLS</th>
                                    <th>Petugas (PPL / PML)</th>
                                    <th class="text-center">Status & Indikator QC</th>
                                    <th class="text-end bg-blue-lt text-blue font-weight-bold">Jml Prelist<br><span class="font-weight-normal small">KK + Usaha</span></th>
                                    <th class="text-end bg-teal-lt text-teal font-weight-bold">Muatan Murni ⭐<br><span class="font-weight-normal small">BKU + KK Ditemukan</span></th>
                                    <th class="text-center font-weight-bold">Rasio Murni vs Prelist</th>
                                    <th class="text-end">Beban Saat Ini<br><span class="font-weight-normal small">Verifikasi</span></th>
                                    <th class="text-end">Total Submit</th>
                                    <th class="text-end">% Progres</th>
                                    <th class="text-end">Belum Disentuh</th>
                                    <th class="text-end text-purple">Total Usaha SE<br><span class="font-weight-normal small">vs Wilkerstat</span></th>
                                    <th class="text-end text-success">KK Ditemukan<br><span class="font-weight-normal small">vs Wilkerstat</span></th>
                                    <th class="text-end text-muted">Keluarga Tdk/Drop</th>
                                    <th class="text-end bg-pink-lt text-pink">Khusus Ganda</th>
                                    <th class="text-end text-orange">Bangunan Kosong</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $index => $row)
@php
$kecNama = $kecNameMap[$row->kode_kec] ?? 'Kec. ' . $row->kode_kec;
$pMurni = $row->pct_murni_vs_prelist !== null ? number_format($row->pct_murni_vs_prelist, 1) . '%' : '-';
$pSubmit = number_format($row->pct_submit, 1) . '%';
@endphp
<tr>
<td class="text-muted text-center">{{ $index + 1 }}</td>
<td><div class="font-weight-bold">{{ $kecNama }}</div><div class="small text-muted">{{ $row->kode_kec }}</div></td>
<td><div class="d-flex align-items-center gap-1 flex-wrap"><span class="font-weight-bold text-dark">{{ $row->nama_sls }}</span>@if($row->is_non_sls)<span class="badge bg-secondary-lt text-secondary px-1 py-0" style="font-size: 0.65rem;" title="Wilayah Non-SLS (Digit 11 > 0: Sawah/Perairan/Hutan)">🌾 Non-SLS</span>@endif</div><div class="small text-muted font-monospace">{{ $row->region_code }}</div></td>
<td><div class="font-weight-bold text-dark">{{ $row->nama_pencacah }}</div><div class="small text-muted">PML: {{ $row->nama_pengawas ?: '-' }}</div></td>
<td class="text-center">@if(!$row->has_anomali)<span class="badge bg-success text-white badge-qc">✅ WAJAR / AMAN</span>@else<div class="d-flex flex-column gap-1 align-items-center">@if($row->is_under_80)<span class="badge bg-danger text-white badge-qc" title="Murni < 80% Prelist">🚨 Murni &lt; 80% Prelist</span>@endif @if($row->is_over_130)<span class="badge bg-warning text-dark badge-qc" title="Murni > 130% Prelist">📈 Lonjakan &gt; 130%</span>@endif @if($row->is_zero_usaha)<span class="badge bg-purple text-white badge-qc" title="Usaha SE Nol">🟣 Zero Usaha SE</span>@endif @if($row->is_usaha_drop)<span class="badge bg-orange text-white badge-qc" title="Usaha Drop vs Wilkerstat">📉 Usaha Drop vs Wilkerstat</span>@endif @if($row->is_keluarga_drop)<span class="badge bg-secondary text-white badge-qc" title="Keluarga Drop >= 15%">🔴 Drop Keluarga Tinggi</span>@endif @if($row->is_ganda)<span class="badge bg-pink text-white badge-qc" title="Khusus Ganda">👥 Khusus Ganda ({{ $row->total_ganda }})</span>@endif @if($row->is_bangunan_lainnya)<span class="badge bg-amber text-dark badge-qc" title="Bangunan Kosong >= 15%">🏚️ Bangunan Kosong &ge;15%</span>@endif</div>@endif</td>
<td class="text-end font-weight-bold text-blue bg-blue-lt" data-order="{{ $row->jml_prelist }}">{{ number_format($row->jml_prelist) }}<div class="small text-muted font-weight-normal" style="font-size: 0.68rem;">KK: {{ number_format($row->prelist_keluarga) }} | U: {{ number_format($row->prelist_usaha) }}</div></td>
<td class="text-end font-weight-extrabold text-teal bg-teal-lt fs-3" data-order="{{ $row->muatan_murni }}">{{ number_format($row->muatan_murni) }}<div class="small text-muted font-weight-normal" style="font-size: 0.68rem;">@if($row->muatan_murni == 0)@if($row->is_non_sls)<span class="text-secondary opacity-75">Non-SLS (Sawah/Perairan)</span>@else<span class="text-danger font-weight-bold">0 Ditemukan</span>@endif @else KK: {{ number_format($row->pk_ditemukan) }} | BKU: {{ number_format($row->up_ditemukan) }}@endif</div></td>
<td class="text-center" data-order="{{ $row->rasio_order ?? 99999 }}">@if(($row->jml_prelist ?? 0) > 0)@if($row->pct_murni_vs_prelist < 80.0)<span class="badge bg-danger text-white font-weight-extrabold px-2 py-1 fs-4 shadow-xs" title="Muatan Murni Kurang (< 80% Prelist)">🚨 {{ $pMurni }}</span>@elseif($row->pct_murni_vs_prelist > 130.0)<span class="badge bg-warning text-dark font-weight-extrabold px-2 py-1 fs-4 shadow-xs" title="Muatan Murni Melonjak (> 130% Prelist)">📈 {{ $pMurni }}</span>@else<span class="badge bg-success-lt text-success font-weight-bold px-2 py-1 fs-4" title="Rasio Normal">✅ {{ $pMurni }}</span>@endif @else @if($row->muatan_murni > 0)<span class="badge bg-info-lt text-info font-weight-bold px-2 py-1" title="SLS Pemekaran Baru">Baru ({{ number_format($row->muatan_murni) }})</span>@else<span class="badge bg-light text-muted border px-2 py-1" title="Prelist Awal 0 & Muatan 0">Nol Prelist</span>@endif @endif</td>
<td class="text-end font-weight-bold" data-order="{{ $row->beban_saat_ini }}">{{ number_format($row->beban_saat_ini) }}</td>
<td class="text-end font-weight-bold text-success" data-order="{{ $row->total_submit }}">{{ number_format($row->total_submit) }}</td>
<td class="text-end" data-order="{{ $row->pct_submit }}"><span class="badge {{ $row->pct_submit >= 70 ? 'bg-success-lt text-success' : ($row->pct_submit >= 50 ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger') }} font-weight-bold px-2 py-0.5">{{ $pSubmit }}</span></td>
<td class="text-end font-weight-bold text-danger" data-order="{{ $row->status_open }}">{{ number_format($row->status_open) }}</td>
<td class="text-end font-weight-bold text-purple" data-order="{{ $row->total_usaha_se }}">{{ number_format($row->total_usaha_se) }}<div class="small text-muted font-weight-normal" style="font-size: 0.68rem;">vs Wil: {{ number_format($row->wilkerstat_usaha) }}@if($row->wilkerstat_usaha > 0)<span class="{{ $row->pct_diff_usaha < -5 ? 'text-danger font-weight-bold' : 'text-success' }}">({{ number_format($row->pct_diff_usaha, 1) }}%)</span>@endif</div></td>
<td class="text-end font-weight-bold text-success" data-order="{{ $row->pk_ditemukan }}">{{ number_format($row->pk_ditemukan) }}<div class="small text-muted font-weight-normal" style="font-size: 0.68rem;">vs KK: {{ number_format($row->wilkerstat_kk) }}@if($row->wilkerstat_kk > 0)<span class="{{ $row->pct_diff_kk < -5 ? 'text-danger font-weight-bold' : 'text-success' }}">({{ number_format($row->pct_diff_kk, 1) }}%)</span>@endif</div></td>
<td class="text-end text-muted" data-order="{{ $row->pk_tdk }}">{{ number_format($row->pk_tdk) }}</td>
<td class="text-end font-weight-bold bg-pink-lt" data-order="{{ $row->total_ganda }}">@if($row->total_ganda > 0)<span class="badge bg-pink text-white font-weight-bold px-2 py-0.5">⚠️ {{ $row->total_ganda }}</span>@else<span class="text-muted">0</span>@endif</td>
<td class="text-end font-weight-bold" data-order="{{ $row->bangunan_lainnya }}">{{ number_format($row->bangunan_lainnya) }}@if($row->is_bangunan_lainnya)<div class="badge bg-orange text-white font-weight-bold px-1.5 py-0.2" style="font-size: 0.68rem;">⚠️ {{ number_format($row->pct_bangunan_lainnya, 1) }}%</div>@else<div class="small text-muted" style="font-size: 0.68rem;">({{ number_format($row->pct_bangunan_lainnya, 1) }}%)</div>@endif</td>
</tr>
                                @empty
                                    <tr>
                                        <td colspan="17" class="text-center py-5 text-muted">
                                            <div class="mb-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-checkbox" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path d="M9 11l3 3l8 -8"/><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9"/></svg>
                                            </div>
                                            <div class="font-weight-bold fs-3">Tidak Ada SLS dalam Kategori Ini</div>
                                            <div class="small">Semua SLS dalam filter ini tidak memiliki temuan anomali atau data sesuai kriteria.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('js')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window._tempDefine = window.define;
        window.define = null;
    </script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        window.define = window._tempDefine;
        window.jqDT = jQuery.noConflict(true);
    </script>
    <script>
        (function($) {
            $(document).ready(function() {
                if ($ && $.fn && typeof $.fn.DataTable === 'function') {
                    $('#qc-table').DataTable({
                        language: {
                            search: "_INPUT_",
                            searchPlaceholder: "🔍 Cari nama SLS, kode, petugas, pengawas...",
                            lengthMenu: "Tampilkan _MENU_ SLS",
                            info: "Menampilkan <strong>_START_</strong> s.d. <strong>_END_</strong> dari total <strong>_TOTAL_</strong> SLS",
                            infoEmpty: "Menampilkan 0 s.d. 0 dari 0 SLS",
                            infoFiltered: "(disaring dari _MAX_ total data)",
                            zeroRecords: "Tidak ada data SLS yang cocok",
                            paginate: {
                                first: "Pertama",
                                previous: "← Sebelum",
                                next: "Lanjut →",
                                last: "Terakhir"
                            }
                        },
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                        order: {!! $filterKategori === 'under_80' ? '[[7, "asc"]]' : ($filterKategori === 'over_130' ? '[[7, "desc"]]' : '[[1, "asc"], [2, "asc"]]') !!},
                        columnDefs: [
                            { orderable: false, targets: [0, 4] } // Disable sort for No and Anomali badges
                        ],
                        dom: "<'row p-3 align-items-center'<'col-md-6 d-flex align-items-center gap-2'l><'col-md-6 d-flex justify-content-md-end mt-2 mt-md-0'f>>" +
                             "<'table-responsive'tr>" +
                             "<'row p-3 border-top align-items-center'<'col-md-5 text-muted small'i><'col-md-7 d-flex justify-content-md-end mt-2 mt-md-0'p>>",
                        drawCallback: function(settings) {
                            var api = this.api();
                            var startIndex = api.context[0]._iDisplayStart;
                            api.column(0, {search:'applied', order:'applied'}).nodes().each(function(cell, i) {
                                cell.innerHTML = startIndex + i + 1;
                            });
                        }
                    });
                }
            });
        })(window.jqDT || jQuery);
    </script>
@endpush
