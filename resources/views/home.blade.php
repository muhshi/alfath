@extends('tablar::page')

@section('content')
    <!-- Page header -->
    <x-page-header title="Beranda ALFATH">
        <div class="col-12 col-md-auto ms-auto d-print-none d-flex align-items-center gap-2" style="position: relative; z-index: 100;">
            <div class="dropdown" id="dropdownPintasanContainer">
                <button id="btnPintasanSe2026" 
                        class="btn btn-white dropdown-toggle font-weight-medium shadow-xs border text-secondary px-3 py-2 d-inline-flex align-items-center gap-1" 
                        type="button" 
                        data-bs-toggle="dropdown" 
                        data-bs-auto-close="outside"
                        aria-expanded="false" 
                        style="border-radius: 8px; border-color: #e2e8f0; background: #ffffff; cursor: pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-apps text-orange me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/></svg>
                    <span>Pintasan Modul SE2026</span>
                </button>
                <div id="menuPintasanSe2026" 
                     class="dropdown-menu dropdown-menu-end shadow-md border-0 py-2" 
                     style="border-radius: 12px; min-width: 270px; border: 1px solid #e2e8f0 !important; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05) !important;">
                    <div class="dropdown-header text-uppercase font-weight-bold text-muted small px-3 py-1" style="letter-spacing: 0.5px; font-size: 0.7rem;">Dashboard Utama</div>
                    <a class="dropdown-item py-2 px-3 d-flex align-items-center" href="{{ route('dashboard.se2026') }}">
                        <span class="avatar avatar-xs bg-orange-lt text-orange rounded me-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-chart-bar" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M3 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M12 8m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M4 20l14 0"/></svg>
                        </span>
                        <div>
                            <div class="font-weight-bold text-dark" style="font-size: 0.85rem;">Executive Dashboard</div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Ringkasan progress &amp; estimasi makro</div>
                        </div>
                    </a>
                    <a class="dropdown-item py-2 px-3 d-flex align-items-center" href="{{ route('dashboard.pengolahan') }}">
                        <span class="avatar avatar-xs bg-blue-lt text-primary rounded me-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-table" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M3 5a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-14z"/><path d="M3 10h18"/><path d="M10 3v18"/></svg>
                        </span>
                        <div>
                            <div class="font-weight-bold text-dark" style="font-size: 0.85rem;">Tabel Petugas SE2026</div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Alokasi, beban &amp; ranking PPL/PML</div>
                        </div>
                    </a>
                    <div class="dropdown-divider my-1.5" style="border-color: #f1f5f9;"></div>
                    <div class="dropdown-header text-uppercase font-weight-bold text-muted small px-3 py-1" style="letter-spacing: 0.5px; font-size: 0.7rem;">Quality Control &amp; Analisis</div>
                    <a class="dropdown-item py-2 px-3 d-flex align-items-center" href="{{ route('dashboard.timeline-petugas') }}">
                        <span class="avatar avatar-xs bg-teal-lt text-teal rounded me-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar-time" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M11.795 21h-6.795a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v4"/><path d="M18 18m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M15 3v4"/><path d="M7 3v4"/><path d="M3 11h16"/><path d="M18 16.496v1.504l1 1"/></svg>
                        </span>
                        <div>
                            <div class="font-weight-bold text-dark" style="font-size: 0.85rem;">Timeline Submit</div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Sebaran waktu &amp; heatmap pengiriman</div>
                        </div>
                    </a>
                    <a class="dropdown-item py-2 px-3 d-flex align-items-center" href="{{ route('dashboard.anomali-geotag') }}">
                        <span class="avatar avatar-xs bg-red-lt text-danger rounded me-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-map-pin-off" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M9.442 9.432a3 3 0 0 0 4.113 4.134m1.445 -2.566a3 3 0 0 0 -3 -3"/><path d="M17.152 17.162l-3.714 3.712a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 0 1 -.48 -10.795m2.583 -1.427a8.018 8.018 0 0 1 10.902 1.458"/><path d="M3 3l18 18"/></svg>
                        </span>
                        <div>
                            <div class="font-weight-bold text-dark" style="font-size: 0.85rem;">Anomali Geotag</div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Deteksi titik bertumpuk &amp; di luar batas</div>
                        </div>
                    </a>
                    <a class="dropdown-item py-2 px-3 d-flex align-items-center" href="{{ route('identifikasi.pendataan') }}">
                        <span class="avatar avatar-xs bg-azure-lt text-azure rounded me-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shield-check" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/><path d="M9 12l2 2l4 -4"/></svg>
                        </span>
                        <div>
                            <div class="font-weight-bold text-dark" style="font-size: 0.85rem;">Identifikasi Hasil SLS</div>
                            <div class="text-muted small" style="font-size: 0.72rem;">Deteksi muatan &lt; 80% &amp; anomali usaha</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </x-page-header>

    <!-- BEGIN PAGE BODY -->
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                
                <!-- Hero Banner Card (Clean Corporate Style) -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm" style="background: #ffffff; border: 1px solid #e2e8f0; border-left: 6px solid #ea580c !important; border-radius: 16px;">
                        <div class="card-body p-4 p-md-5">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="badge bg-light text-dark font-weight-bold px-3 py-2 rounded-pill border">
                                            <span class="status-dot status-dot-animated bg-success me-1"></span> BPS KABUPATEN DEMAK
                                        </span>
                                    </div>
                                    <h1 class="display-6 font-weight-extrabold text-dark mb-2" style="font-family: 'Outfit', sans-serif; color: #0f172a;">
                                        ALFATH
                                    </h1>
                                    <p class="fs-2 font-weight-bold mb-3" style="color: #ea580c;">
                                        Aplikasi Fasih Monitoring Harian
                                    </p>
                                    <p class="fs-3 text-muted mb-4" style="max-width: 620px; line-height: 1.6;">
                                        Portal monitoring terpadu untuk pemanduan data harian pendataan Sensus Ekonomi 2026 dan survei statistik BPS Kabupaten Demak.
                                    </p>
                                    
                                    <!-- Action Buttons with Clear Visual Hierarchy -->
                                    <div class="d-flex flex-wrap align-items-center gap-3 mb-4" style="position: relative; z-index: 10;">
                                        <a href="{{ route('dashboard.se2026') }}" class="btn text-white font-weight-bold px-4 py-2 shadow-sm" style="background-color: #ea580c; border: none; border-radius: 10px; cursor: pointer;">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M3 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"></path>
                                                <path d="M12 8m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"></path>
                                                <path d="M4 20l14 0"></path>
                                            </svg>
                                            Buka Executive Dashboard
                                        </a>
                                        <a href="{{ route('dashboard.pengolahan') }}" class="btn btn-outline-secondary font-weight-bold px-4 py-2 shadow-xs" style="border-radius: 10px; border-color: #cbd5e1; color: #334155; background-color: #ffffff; cursor: pointer;">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-table me-1 text-primary" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M3 5a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-14z"/><path d="M3 10h18"/><path d="M10 3v18"/></svg>
                                            Tabel Petugas SE2026
                                        </a>
                                    </div>

                                    <!-- Clean Harmonious Sub-Navigation Pills -->
                                    <div class="pt-3 border-top d-flex align-items-center flex-wrap gap-2" style="border-color: #f1f5f9 !important; position: relative; z-index: 10;">
                                        <span class="text-muted small font-weight-semibold me-2 d-none d-sm-inline">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-bolt text-warning me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0z"/></svg>
                                            Modul Analisis &amp; QC:
                                        </span>
                                        <a href="{{ route('dashboard.timeline-petugas') }}" class="badge bg-white text-secondary border px-3 py-2 text-decoration-none rounded-pill d-inline-flex align-items-center shadow-xs" style="font-size: 0.825rem; cursor: pointer;">
                                            <span class="status-dot bg-teal me-2"></span> Timeline Submit
                                        </a>
                                        <a href="{{ route('dashboard.anomali-geotag') }}" class="badge bg-white text-secondary border px-3 py-2 text-decoration-none rounded-pill d-inline-flex align-items-center shadow-xs" style="font-size: 0.825rem; cursor: pointer;">
                                            <span class="status-dot bg-danger me-2"></span> Anomali Geotag
                                        </a>
                                        <a href="{{ route('identifikasi.pendataan') }}" class="badge bg-white text-secondary border px-3 py-2 text-decoration-none rounded-pill d-inline-flex align-items-center shadow-xs" style="font-size: 0.825rem; cursor: pointer;">
                                            <span class="status-dot bg-azure me-2"></span> Identifikasi Hasil SLS
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-4 d-none d-md-flex justify-content-end align-items-center">
                                    <div class="text-center p-3 rounded-4 bg-light border" style="max-width: 240px;">
                                        <img src="{{ asset('assets/logo_bps.png') }}" alt="Logo BPS" class="img-fluid mb-2" style="max-height: 110px; object-fit: contain;">
                                        <div class="small font-weight-bold text-muted">BADAN PUSAT STATISTIK</div>
                                        <div class="small text-muted">KABUPATEN DEMAK</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clean KPI Cards -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-xs rounded-3" style="border-top: 4px solid #1d4ed8 !important; background: #ffffff;">
                        <div class="card-body p-3">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-blue-lt text-blue avatar rounded-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M9 17l0 -5"/><path d="M12 17l0 -1"/><path d="M15 17l0 -3"/></svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-extrabold fs-1 text-dark">
                                        {{ number_format($totalSurveys) }}
                                    </div>
                                    <div class="text-muted font-weight-medium small">
                                        Total Survei Terdaftar
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-xs rounded-3" style="border-top: 4px solid #059669 !important; background: #ffffff;">
                        <div class="card-body p-3">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-green-lt text-green avatar rounded-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-extrabold fs-1 text-dark">
                                        {{ number_format($totalTeams) }}
                                    </div>
                                    <div class="text-muted font-weight-medium small">
                                        Tim Kerja Aktif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-xs rounded-3" style="border-top: 4px solid #d97706 !important; background: #ffffff;">
                        <div class="card-body p-3">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-warning-lt text-warning avatar rounded-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M3 12h4l3 8l4 -16l3 8h4"/></svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-extrabold fs-1 text-dark">
                                        {{ number_format($activeSurveys) }}
                                    </div>
                                    <div class="text-muted font-weight-medium small">
                                        Survei Berjalan (Aktif)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-xs rounded-3" style="border-top: 4px solid #ea580c !important; background: #ffffff;">
                        <div class="card-body p-3">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-orange-lt text-orange avatar rounded-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M12 21l-8 -4.5v-9l8 -4.5l8 4.5v9z"/><path d="M12 12l8 -4.5"/><path d="M12 12v9"/><path d="M12 12l-8 -4.5"/></svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-extrabold fs-1 text-dark">
                                        8.270
                                    </div>
                                    <div class="text-muted font-weight-medium small">
                                        Sub SLS SE2026
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent & Active Surveys Table Card -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3" style="background: #ffffff;">
                        <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between">
                            <h3 class="card-title font-weight-bold text-dark mb-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon text-orange me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M9 11l3 3l8 -8"/><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9"/></svg>
                                Daftar Survei Lapangan BPS Kab. Demak
                            </h3>
                            <span class="badge bg-light text-muted font-weight-bold px-3 py-2 border rounded-pill">
                                Diurutkan: <strong class="text-primary">Status Aktif</strong> → <strong class="text-dark">Tanggal Terkini</strong>
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter table-hover text-nowrap">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="font-weight-bold text-muted py-3">Nama Survei</th>
                                        <th class="font-weight-bold text-muted py-3">Tim Kerja</th>
                                        <th class="font-weight-bold text-muted py-3">Periode Pendataan</th>
                                        <th class="font-weight-bold text-muted py-3">Status Survei</th>
                                        <th class="font-weight-bold text-muted py-3 text-end">Aksi Monitoring</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($surveys as $survey)
                                        <tr>
                                            <td class="font-weight-bold text-dark py-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar avatar-xs me-2 rounded bg-light text-dark font-weight-bold" style="border: 1px solid #e2e8f0;">
                                                        <i class="fa-solid fa-clipboard-list text-orange"></i>
                                                    </span>
                                                    <span>{{ $survey->name }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                <span class="badge bg-blue-lt font-weight-bold px-2 py-1">
                                                    <i class="fa-solid fa-users text-blue me-1"></i> {{ $survey->team->name ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="text-muted small py-3">
                                                <i class="fa-regular fa-calendar me-1"></i>
                                                {{ $survey->start_periode?->format('d M Y') }} – {{ $survey->end_periode?->format('d M Y') }}
                                            </td>
                                            <td class="py-3">
                                                @if($survey->start_periode <= now() && $survey->end_periode >= now())
                                                    <span class="badge bg-success-lt text-success font-weight-bold px-3 py-1 rounded-pill">
                                                        <span class="status-dot status-dot-animated bg-success me-1"></span> Aktif Berjalan
                                                    </span>
                                                @elseif($survey->end_periode < now())
                                                    <span class="badge bg-secondary-lt text-secondary font-weight-medium px-3 py-1 rounded-pill">
                                                        ● Selesai
                                                    </span>
                                                @else
                                                    <span class="badge bg-info-lt text-info font-weight-bold px-3 py-1 rounded-pill">
                                                        ⏳ Mendatang
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end py-3">
                                                <a href="{{ route('surveys.embed', $survey) }}" class="btn btn-primary btn-sm text-white font-weight-bold px-3 shadow-xs" style="background-color: #1d4ed8; border-color: #1d4ed8; border-radius: 8px;">
                                                    <i class="fa-solid fa-chart-line me-1"></i> Lihat Monitoring
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <div class="mb-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder-off text-muted opacity-50" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path d="M3 3l18 18"/><path d="M19 19h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 1.172 -1.821m3.828 -.179h1l3 3h7a2 2 0 0 1 2 2v8"/></svg>
                                                </div>
                                                <div class="font-weight-bold fs-3 text-dark">Belum Ada Data Survei</div>
                                                <div class="small">Survei yang ditambahkan akan otomatis muncul dan diurutkan berdasarkan status aktif di sini.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Links & Footer -->
                        @if($surveys instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between bg-transparent border-top py-3">
                                <div class="text-muted small">
                                    Menampilkan <strong>{{ $surveys->firstItem() ?? 0 }}</strong> s.d. <strong>{{ $surveys->lastItem() ?? 0 }}</strong> dari total <strong>{{ $surveys->total() }}</strong> survei
                                </div>
                                <div>
                                    {{ $surveys->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- END PAGE BODY -->
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Dropdown Pintasan SE2026 Bulletproof Handler
            var btnPintasan = document.getElementById('btnPintasanSe2026');
            var menuPintasan = document.getElementById('menuPintasanSe2026');
            
            if (btnPintasan && menuPintasan) {
                // Inisialisasi Bootstrap Dropdown jika library tersedia
                if (window.bootstrap && window.bootstrap.Dropdown) {
                    try {
                        window.bootstrap.Dropdown.getOrCreateInstance(btnPintasan);
                    } catch (err) {
                        console.warn('Bootstrap dropdown auto-init fallback:', err);
                    }
                }

                // Dedicated click toggle event
                btnPintasan.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var isShown = menuPintasan.classList.contains('show');
                    if (isShown) {
                        menuPintasan.classList.remove('show');
                        btnPintasan.classList.remove('show');
                        btnPintasan.setAttribute('aria-expanded', 'false');
                    } else {
                        menuPintasan.classList.add('show');
                        btnPintasan.classList.add('show');
                        btnPintasan.setAttribute('aria-expanded', 'true');
                    }
                });

                // Tutup dropdown saat klik di luar area
                document.addEventListener('click', function (e) {
                    if (!btnPintasan.contains(e.target) && !menuPintasan.contains(e.target)) {
                        menuPintasan.classList.remove('show');
                        btnPintasan.classList.remove('show');
                        btnPintasan.setAttribute('aria-expanded', 'false');
                    }
                });

                // Tutup dropdown saat tombol Escape ditekan
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && menuPintasan.classList.contains('show')) {
                        menuPintasan.classList.remove('show');
                        btnPintasan.classList.remove('show');
                        btnPintasan.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            // Instant Loading Feedback on Navigation Buttons
            var navButtons = document.querySelectorAll('a.btn[href*="timeline-petugas"], a.btn[href*="dashboard-pengolahan"], a.btn[href*="dashboard-se2026"]');
            navButtons.forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    if (this.getAttribute('data-nav-loading') === 'true') return;
                    this.setAttribute('data-nav-loading', 'true');
                    this.style.pointerEvents = 'none';
                    this.style.opacity = '0.85';
                    
                    var cleanText = this.textContent.replace(/\s+/g, ' ').trim();
                    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Memuat ' + cleanText + '...';
                });
            });
        });
    </script>
@endpush