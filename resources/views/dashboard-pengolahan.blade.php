@extends('tablar::page')

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        .dataTables_wrapper {
            padding: 0;
        }
        .dataTables_wrapper .dataTables_length {
            margin-bottom: 0;
        }
        .dataTables_wrapper .dataTables_length select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0.35rem 2.25rem 0.35rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #334155;
            background-color: #f8fafc;
        }
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 0;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0.45rem 0.85rem;
            font-size: 0.875rem;
            box-shadow: none;
            margin-left: 0.5rem;
            min-width: 260px;
            transition: all 0.2s ease-in-out;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #0284c7;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
            outline: none;
        }
        table.dataTable {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border-collapse: collapse !important;
            width: 100% !important;
        }
        table.dataTable thead th {
            border-bottom: 2px solid #e2e8f0 !important;
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.02em;
            color: #334155;
            background-color: #f8fafc;
            vertical-align: middle;
            padding: 0.65rem 0.45rem !important;
            white-space: normal !important;
            line-height: 1.25;
            text-wrap: balance;
            text-align: center !important;
        }
        table.dataTable tbody td {
            vertical-align: middle;
            padding: 0.5rem 0.5rem !important;
            font-size: 0.825rem;
        }
        table.dataTable tbody tr:hover {
            background-color: #f1f5f9 !important;
        }
        .dataTables_wrapper .dataTables_paginate .pagination {
            margin-bottom: 0;
            gap: 4px;
        }
        .dataTables_wrapper .dataTables_paginate .page-item .page-link {
            border-radius: 8px !important;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.4rem 0.75rem;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
            background-color: #0284c7;
            border-color: #0284c7;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(2, 132, 199, 0.25);
        }
        .dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
            color: #94a3b8;
            background-color: #f8fafc;
            border-color: #f1f5f9;
        }
        .table-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(4px);
            z-index: 20;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.25s ease-out, visibility 0.25s ease-out;
            border-radius: 16px;
        }
        .table-loading-overlay.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .datatable-pre-init {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .datatable-initialized {
            opacity: 1 !important;
        }
        /* Custom High-Contrast Modern Pill Tabs */
        .custom-pill-tabs {
            background-color: #f1f5f9;
            padding: 5px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            display: inline-flex;
            gap: 6px;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        .custom-pill-tabs .nav-item {
            margin-bottom: 0;
        }
        .custom-pill-tabs .nav-link {
            border: none !important;
            border-radius: 8px !important;
            padding: 0.55rem 1.15rem !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            color: #475569 !important;
            background: transparent;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .custom-pill-tabs .nav-link:hover {
            color: #0f172a !important;
            background-color: rgba(255, 255, 255, 0.7);
        }
        /* Active States with Distinct High Contrast Colors */
        .custom-pill-tabs .nav-link.active#petugas-tab {
            background-color: #0284c7 !important; /* Sky Blue */
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
        }
        .custom-pill-tabs .nav-link.active#pml-tab {
            background-color: #4f46e5 !important; /* Indigo */
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
        }
        .custom-pill-tabs .nav-link.active#sls-tab {
            background-color: #059669 !important; /* Emerald Green */
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
        }
        .custom-pill-tabs .nav-link.active#ranking-tab {
            background-color: #d97706 !important; /* Amber Gold */
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35);
        }
        .custom-pill-tabs .nav-link .badge-tab-count {
            padding: 0.2rem 0.6rem;
            font-size: 0.75rem;
            border-radius: 9999px;
            font-weight: 700;
            background-color: #e2e8f0;
            color: #334155;
            transition: all 0.2s ease;
        }
        .custom-pill-tabs .nav-link.active .badge-tab-count {
            background-color: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }
    </style>
@endpush

@section('content')
    <!-- Page header -->
    <x-page-header title="Tabel Petugas SE2026">
        <div class="col-12 col-md-auto ms-auto d-print-none flex-wrap gap-2 d-flex">
            <a href="{{ route('home') }}" class="btn btn-outline-secondary font-weight-bold shadow-sm" style="border-radius: 10px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-left me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/></svg>
                Beranda
            </a>
            <a href="{{ route('dashboard.se2026') }}" class="btn btn-outline-primary font-weight-bold shadow-sm" style="border-radius: 10px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-chart-bar me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M3 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M12 8m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M4 20l14 0"/></svg>
                Executive Dashboard
            </a>
            <a href="{{ route('dashboard.pengolahan.export', request()->query()) }}" class="btn btn-success font-weight-bold shadow-sm" style="border-radius: 10px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-spreadsheet me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M8 11h8" /><path d="M8 15h8" /><path d="M11 11v8" /></svg>
                Export Excel
            </a>
        </div>
    </x-page-header>

    <!-- BEGIN PAGE BODY -->
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards mb-4">
                
                <!-- Hero Header Banner -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm" style="background: #ffffff; border: 1px solid #e2e8f0; border-left: 6px solid #0284c7 !important; border-radius: 16px;">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-9">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-light text-primary font-weight-bold px-3 py-1.5 rounded-pill border">
                                            <span class="status-dot status-dot-animated bg-primary me-1"></span> SE2026 MONITORING & PETUGAS
                                        </span>
                                        @if($selectedDate)
                                            <span class="badge bg-info-lt text-dark px-3 py-1.5 rounded-pill">
                                                📅 Tanggal Data: {{ date('d M Y', strtotime($selectedDate)) }}
                                            </span>
                                        @endif
                                    </div>
                                    <h2 class="font-weight-extrabold text-dark mb-1" style="font-family: 'Outfit', sans-serif; color: #0f172a;">
                                        Tabel Petugas SE2026
                                    </h2>
                                    <p class="text-muted mb-0" style="font-size: 0.95rem;">
                                        Integrasi agregasi data harian dari Monitoring FASIH, Pemutakhiran Usaha (Perusahaan & Keluarga), serta Pemutakhiran Keluarga per Petugas dan Kecamatan.
                                    </p>
                                </div>
                                <div class="col-md-3 d-none d-md-flex justify-content-end align-items-center">
                                    <div class="text-center p-2 rounded-3 bg-light border" style="max-width: 180px;">
                                        <img src="{{ asset('assets/logo_bps.png') }}" alt="Logo BPS" class="img-fluid mb-1" style="max-height: 55px; object-fit: contain;">
                                        <div class="small font-weight-bold text-muted" style="font-size: 0.75rem;">BPS KAB. DEMAK</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI Summary Cards -->
                <div class="col-6 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <div class="text-muted small font-weight-bold mb-1">TOTAL PETUGAS</div>
                            <div class="h2 font-weight-extrabold text-dark mb-0">{{ number_format($kpiSummary['total_petugas'] ?? 0) }}</div>
                            <div class="small text-muted mt-1">Pencacah Terdaftar</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <div class="text-muted small font-weight-bold mb-1">BEBAN SAAT INI</div>
                            <div class="h2 font-weight-extrabold text-primary mb-0">{{ number_format($kpiSummary['total_beban'] ?? 0) }}</div>
                            <div class="small text-muted mt-1">Muatan SLS/Sub-SLS</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <div class="text-muted small font-weight-bold mb-1">TOTAL SUBMIT</div>
                            <div class="h2 font-weight-extrabold text-success mb-0">{{ number_format($kpiSummary['total_submit'] ?? 0) }}</div>
                            <div class="small text-success font-weight-bold mt-1">{{ number_format($kpiSummary['pct_overall_submit'] ?? 0, 1) }}% Capaian</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <div class="text-muted small font-weight-bold mb-1">USAHA DITEMUKAN</div>
                            <div class="h2 font-weight-extrabold text-info mb-0">{{ number_format($kpiSummary['total_usaha_ditemukan'] ?? 0) }}</div>
                            <div class="small text-muted mt-1">Usaha Perusahaan</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <div class="text-muted small font-weight-bold mb-1">KELUARGA DITEMUKAN</div>
                            <div class="h2 font-weight-extrabold text-warning mb-0">{{ number_format($kpiSummary['total_keluarga_ditemukan'] ?? 0) }}</div>
                            <div class="small text-muted mt-1">Ditemukan + Baru</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="card border-0 shadow-sm rounded-3 bg-teal-lt">
                        <div class="card-body p-3">
                            <div class="text-teal font-weight-bold small mb-1">MUATAN MURNI</div>
                            <div class="h2 font-weight-extrabold text-teal mb-0">{{ number_format($kpiSummary['total_muatan_murni'] ?? 0) }}</div>
                            <div class="small text-teal mt-1">Usaha Perusahaan + Keluarga</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Section -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
                <div class="card-body p-3 p-md-4">
                    <form method="GET" action="{{ route('dashboard.pengolahan') }}" class="row g-3 align-items-end">
                        <!-- Dropdown Tanggal Data -->
                        <div class="col-12 col-md-5">
                            <label class="form-label font-weight-bold small text-muted mb-1">Tanggal Data Snapshot</label>
                            <select name="tanggal_data" class="form-select">
                                @foreach($availableDates as $d)
                                    <option value="{{ $d }}" {{ $selectedDate == $d ? 'selected' : '' }}>
                                        {{ date('d M Y', strtotime($d)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Dropdown Kecamatan -->
                        <div class="col-12 col-md-5">
                            <label class="form-label font-weight-bold small text-muted mb-1">Filter Kecamatan</label>
                            <select name="kodekec" class="form-select">
                                <option value="">-- Semua Kecamatan (14 Kec) --</option>
                                @foreach($kecNameMap as $code => $name)
                                    <option value="{{ $code }}" {{ $kodekec == $code ? 'selected' : '' }}>
                                        [{{ $code }}] {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Submit & Reset Buttons -->
                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary font-weight-bold w-100" style="border-radius: 8px;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/></svg>
                                Filter Snapshot
                            </button>
                            @if($kodekec || ($selectedDate && !empty($availableDates) && $selectedDate != $availableDates[0]))
                                <a href="{{ route('dashboard.pengolahan') }}" class="btn btn-outline-secondary" title="Reset Filter" style="border-radius: 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-rotate" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M19.95 11a8 8 0 1 0 -.5 4m.5 5v-5h-5"/></svg>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table Data Section (DataTables Enabled with Tabs) -->
            <div class="card border-0 shadow-sm position-relative" style="border-radius: 16px; min-height: 380px;">
                <!-- Animated Loading Overlay during DataTables Initialization -->
                <div id="table-loading-overlay" class="table-loading-overlay">
                    <div class="spinner-border text-primary me-2" role="status" style="width: 2.25rem; height: 2.25rem;"></div>
                    <div class="mt-3 font-weight-bold text-dark fs-3">Memuat Data & DataTables SE2026...</div>
                    <div class="text-muted small">Menyiapkan sorting instan, live search, dan tata letak tabel...</div>
                </div>

                <div class="card-header bg-white border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <ul class="nav custom-pill-tabs font-weight-bold" id="dashboard-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="petugas-tab" data-bs-toggle="tab" data-bs-target="#tab-petugas" type="button" role="tab" aria-controls="tab-petugas" aria-selected="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-check" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4"/><path d="M16 19l2 2l4 -4"/></svg>
                                <span>Ringkasan Per PPL</span>
                                <span class="badge-tab-count">{{ number_format($records->count()) }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pml-tab" data-bs-toggle="tab" data-bs-target="#tab-pml" type="button" role="tab" aria-controls="tab-pml" aria-selected="false">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                                <span>Ringkasan Per PML</span>
                                <span class="badge-tab-count">{{ number_format($pmlRecords->count()) }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sls-tab" data-bs-toggle="tab" data-bs-target="#tab-sls" type="button" role="tab" aria-controls="tab-sls" aria-selected="false">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-map-pin" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z"/></svg>
                                <span>Alokasi Per SLS</span>
                                <span class="badge-tab-count">{{ number_format($slsRecords->count()) }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ranking-tab" data-bs-toggle="tab" data-bs-target="#tab-ranking" type="button" role="tab" aria-controls="tab-ranking" aria-selected="false">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trophy me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M8 21l8 0"/><path d="M12 17l0 4"/><path d="M7 4l10 0"/><path d="M17 4v8a5 5 0 0 1 -10 0v-8"/><path d="M5 9m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M19 9m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/></svg>
                                <span>Ranking Kinerja</span>
                                <span class="badge-tab-count" style="background-color: #f59e0b; color: #ffffff;">{{ number_format($rankingRecords->count()) }}</span>
                            </button>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('dashboard.pengolahan.export', request()->query()) }}" class="btn btn-sm btn-success font-weight-bold" style="border-radius: 8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-spreadsheet me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M8 11h8" /><path d="M8 15h8" /><path d="M11 11v8" /></svg>
                            Export Excel
                        </a>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="tab-content" id="dashboard-tabs-content">
                        
                        <!-- TAB 1: RINGKASAN PER PPL (PENCACAH) -->
                        <div class="tab-pane fade show active" id="tab-petugas" role="tabpanel" aria-labelledby="petugas-tab">
                            <table id="pengolahan-table" class="table table-vcenter table-striped card-table text-nowrap w-100 datatable-pre-init">
                                <thead>
                                    <tr class="bg-light text-uppercase small font-weight-bold">
                                        <th class="w-1 text-center">No</th>
                                        <th>Kecamatan</th>
                                        <th>Nama Petugas<br><span class="text-muted font-weight-normal small">/ Pencacah</span></th>
                                        <th>Nama<br>Pengawas</th>
                                        <th class="text-end bg-teal-lt text-teal font-weight-bold">Muatan<br>Murni ⭐</th>
                                        <th class="text-end bg-danger-lt text-danger font-weight-bold">Belum<br>Dikerjakan</th>
                                        <th class="text-end">Beban<br>Saat Ini</th>
                                        <th class="text-end">Total<br>Submit</th>
                                        <th class="text-end">% Progres</th>
                                        <th class="text-end">Usaha<br>Perusahaan</th>
                                        <th class="text-end">Usaha Perusahaan<br><span class="text-muted font-weight-normal small">Tdk Ditemukan</span></th>
                                        <th class="text-end">Usaha Keluarga<br><span class="text-purple font-weight-normal small">(Ditemukan)</span></th>
                                        <th class="text-end">Keluarga<br><span class="text-warning font-weight-normal small">(Ditemukan+Baru)</span></th>
                                        <th class="text-end">Keluarga<br><span class="text-muted font-weight-normal small">Tdk Ditemukan</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($records as $index => $row)
                                        <tr>
                                            <td class="text-muted small text-center">{{ $index + 1 }}</td>
                                            <td>
                                                <div class="font-weight-bold">{{ $kecNameMap[$row->kode_kec] ?? 'Kec. ' . $row->kode_kec }}</div>
                                                <div class="small text-muted">Kode: {{ $row->kode_kec }}</div>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold text-dark">{{ $row->nama_pencacah }}</div>
                                                <div class="small text-muted">{{ $row->email_pencacah }}</div>
                                            </td>
                                            <td>
                                                <div class="small font-weight-medium">{{ $row->nama_pengawas ?: '-' }}</div>
                                            </td>
                                            <td class="text-end font-weight-extrabold text-teal bg-teal-lt fs-3" data-order="{{ $row->muatan_murni }}">
                                                {{ number_format($row->muatan_murni) }}
                                            </td>
                                            <td class="text-end font-weight-bold text-danger bg-danger-lt fs-3" data-order="{{ $row->belum_dikerjakan }}">
                                                {{ number_format($row->belum_dikerjakan) }}
                                            </td>
                                            <td class="text-end font-weight-bold" data-order="{{ $row->beban_saat_ini }}">{{ number_format($row->beban_saat_ini) }}</td>
                                            <td class="text-end font-weight-bold text-success" data-order="{{ $row->total_submit }}">{{ number_format($row->total_submit) }}</td>
                                            <td class="text-end" data-order="{{ $row->pct_submit }}">
                                                <span class="badge {{ $row->pct_submit >= 70 ? 'bg-success-lt text-success' : ($row->pct_submit >= 50 ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger') }} font-weight-bold px-2 py-1">
                                                    {{ number_format($row->pct_submit, 1) }}%
                                                </span>
                                            </td>
                                            <td class="text-end font-weight-bold text-info" data-order="{{ $row->jumlah_usaha_ditemukan }}">{{ number_format($row->jumlah_usaha_ditemukan) }}</td>
                                            <td class="text-end text-muted" data-order="{{ $row->usaha_tidak_ditemukan }}">{{ number_format($row->usaha_tidak_ditemukan) }}</td>
                                            <td class="text-end font-weight-bold text-purple" data-order="{{ $row->jumlah_usaha_keluarga }}">{{ number_format($row->jumlah_usaha_keluarga) }}</td>
                                            <td class="text-end font-weight-bold text-warning" data-order="{{ $row->jumlah_keluarga_ditemukan }}">{{ number_format($row->jumlah_keluarga_ditemukan) }}</td>
                                            <td class="text-end text-muted" data-order="{{ $row->keluarga_tidak_ditemukan }}">{{ number_format($row->keluarga_tidak_ditemukan) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="14" class="text-center py-5 text-muted">
                                                <div class="mb-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-inbox" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M4 13h3l3 3h4l3 -3h3"/></svg>
                                                </div>
                                                <div class="font-weight-bold fs-3">Tidak Ada Data Ditemukan</div>
                                                <div class="small">Coba ubah kata kunci pencarian atau filter kecamatan yang dipilih.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- TAB 2: RINGKASAN PER PML (PENGAWAS) -->
                        <div class="tab-pane fade" id="tab-pml" role="tabpanel" aria-labelledby="pml-tab">
                            <table id="pml-table" class="table table-vcenter table-striped card-table text-nowrap w-100 datatable-pre-init">
                                <thead>
                                    <tr class="bg-light text-uppercase small font-weight-bold">
                                        <th class="w-1 text-center">No</th>
                                        <th>Kecamatan</th>
                                        <th>Nama Pengawas<br><span class="text-muted font-weight-normal small">/ PML</span></th>
                                        <th class="text-center">Jml PPL<br><span class="text-muted font-weight-normal small">Didampingi</span></th>
                                        <th class="text-center">Jml SLS<br><span class="text-muted font-weight-normal small">Didampingi</span></th>
                                        <th class="text-end bg-teal-lt text-teal font-weight-bold">Muatan<br>Murni ⭐</th>
                                        <th class="text-end bg-danger-lt text-danger font-weight-bold">Belum<br>Dikerjakan</th>
                                        <th class="text-end">Beban<br>Saat Ini</th>
                                        <th class="text-end">Total<br>Submit</th>
                                        <th class="text-end">% Progres</th>
                                        <th class="text-end">Usaha<br>Perusahaan</th>
                                        <th class="text-end">Usaha Perusahaan<br><span class="text-muted font-weight-normal small">Tdk Ditemukan</span></th>
                                        <th class="text-end">Usaha Keluarga<br><span class="text-purple font-weight-normal small">(Ditemukan)</span></th>
                                        <th class="text-end">Keluarga<br><span class="text-warning font-weight-normal small">(Ditemukan+Baru)</span></th>
                                        <th class="text-end">Keluarga<br><span class="text-muted font-weight-normal small">Tdk Ditemukan</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pmlRecords as $index => $row)
                                        <tr>
                                            <td class="text-muted small text-center">{{ $index + 1 }}</td>
                                            <td>
                                                <div class="font-weight-bold">{{ $kecNameMap[$row->kode_kec] ?? 'Kec. ' . $row->kode_kec }}</div>
                                                <div class="small text-muted">Kode: {{ $row->kode_kec }}</div>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold text-dark">{{ $row->nama_pengawas }}</div>
                                                <div class="small text-muted">{{ $row->email_pengawas ?: '-' }}</div>
                                            </td>
                                            <td class="text-center font-weight-bold" data-order="{{ $row->total_ppl }}">
                                                <span class="badge bg-blue-lt text-blue px-2 py-1 fs-4">{{ number_format($row->total_ppl) }} PPL</span>
                                            </td>
                                            <td class="text-center font-weight-bold" data-order="{{ $row->total_sls }}">
                                                <span class="badge bg-indigo-lt text-indigo px-2 py-1 fs-4">{{ number_format($row->total_sls) }} SLS</span>
                                            </td>
                                            <td class="text-end font-weight-extrabold text-teal bg-teal-lt fs-3" data-order="{{ $row->muatan_murni }}">
                                                {{ number_format($row->muatan_murni) }}
                                            </td>
                                            <td class="text-end font-weight-bold text-danger bg-danger-lt fs-3" data-order="{{ $row->belum_dikerjakan }}">
                                                {{ number_format($row->belum_dikerjakan) }}
                                            </td>
                                            <td class="text-end font-weight-bold" data-order="{{ $row->beban_saat_ini }}">{{ number_format($row->beban_saat_ini) }}</td>
                                            <td class="text-end font-weight-bold text-success" data-order="{{ $row->total_submit }}">{{ number_format($row->total_submit) }}</td>
                                            <td class="text-end" data-order="{{ $row->pct_submit }}">
                                                <span class="badge {{ $row->pct_submit >= 70 ? 'bg-success-lt text-success' : ($row->pct_submit >= 50 ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger') }} font-weight-bold px-2 py-1">
                                                    {{ number_format($row->pct_submit, 1) }}%
                                                </span>
                                            </td>
                                            <td class="text-end font-weight-bold text-info" data-order="{{ $row->jumlah_usaha_ditemukan }}">{{ number_format($row->jumlah_usaha_ditemukan) }}</td>
                                            <td class="text-end text-muted" data-order="{{ $row->usaha_tidak_ditemukan }}">{{ number_format($row->usaha_tidak_ditemukan) }}</td>
                                            <td class="text-end font-weight-bold text-purple" data-order="{{ $row->jumlah_usaha_keluarga }}">{{ number_format($row->jumlah_usaha_keluarga) }}</td>
                                            <td class="text-end font-weight-bold text-warning" data-order="{{ $row->jumlah_keluarga_ditemukan }}">{{ number_format($row->jumlah_keluarga_ditemukan) }}</td>
                                            <td class="text-end text-muted" data-order="{{ $row->keluarga_tidak_ditemukan }}">{{ number_format($row->keluarga_tidak_ditemukan) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="15" class="text-center py-5 text-muted">
                                                <div class="mb-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-inbox" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M4 13h3l3 3h4l3 -3h3"/></svg>
                                                </div>
                                                <div class="font-weight-bold fs-3">Tidak Ada Data PML Ditemukan</div>
                                                <div class="small">Coba ubah kata kunci pencarian atau filter kecamatan yang dipilih.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- TAB 3: ALOKASI & PROGRESS PER SLS / SUB-SLS -->
                        <div class="tab-pane fade" id="tab-sls" role="tabpanel" aria-labelledby="sls-tab">
                            <table id="sls-table" class="table table-vcenter table-striped card-table text-nowrap w-100 datatable-pre-init">
                                <thead>
                                    <tr class="bg-light text-uppercase small font-weight-bold">
                                        <th class="w-1 text-center">No</th>
                                        <th>Kecamatan</th>
                                        <th>Kode & Nama SLS / Sub-SLS</th>
                                        <th>Nama Petugas<br><span class="text-muted font-weight-normal small">/ Pencacah</span></th>
                                        <th>Nama<br>Pengawas</th>
                                        <th class="text-end">Beban<br>Saat Ini</th>
                                        <th class="text-end">Total<br>Submit</th>
                                        <th class="text-end bg-danger-lt text-danger font-weight-bold">Belum Disentuh<br>(Open)</th>
                                        <th class="text-end">% Progres</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($slsRecords as $index => $row)
                                        <tr>
                                            <td class="text-muted small text-center">{{ $index + 1 }}</td>
                                            <td>
                                                <div class="font-weight-bold">{{ $kecNameMap[$row->kode_kec] ?? 'Kec. ' . $row->kode_kec }}</div>
                                                <div class="small text-muted">Kode: {{ $row->kode_kec }}</div>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold text-dark">{{ $row->nama_sls }}</div>
                                                <div class="small text-muted font-monospace">{{ $row->region_code }}</div>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold text-dark">{{ $row->nama_pencacah }}</div>
                                                <div class="small text-muted">{{ $row->email_pencacah }}</div>
                                            </td>
                                            <td>
                                                <div class="small font-weight-medium">{{ $row->nama_pengawas ?: '-' }}</div>
                                            </td>
                                            <td class="text-end font-weight-bold" data-order="{{ $row->beban_saat_ini }}">{{ number_format($row->beban_saat_ini) }}</td>
                                            <td class="text-end font-weight-bold text-success" data-order="{{ $row->total_submit }}">{{ number_format($row->total_submit) }}</td>
                                            <td class="text-end font-weight-bold text-danger bg-danger-lt fs-3" data-order="{{ $row->status_open }}">
                                                {{ number_format($row->status_open) }}
                                            </td>
                                            <td class="text-end" data-order="{{ $row->pct_submit }}">
                                                <span class="badge {{ $row->pct_submit >= 70 ? 'bg-success-lt text-success' : ($row->pct_submit >= 50 ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger') }} font-weight-bold px-2 py-1">
                                                    {{ number_format($row->pct_submit, 1) }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-5 text-muted">
                                                <div class="mb-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-inbox" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M4 13h3l3 3h4l3 -3h3"/></svg>
                                                </div>
                                                <div class="font-weight-bold fs-3">Tidak Ada Data SLS Ditemukan</div>
                                                <div class="small">Coba ubah kata kunci pencarian atau filter kecamatan yang dipilih.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- TAB 4: RANKING KINERJA PETUGAS -->
                        <div class="tab-pane fade" id="tab-ranking" role="tabpanel" aria-labelledby="ranking-tab">
                            
                            <!-- Ranking Summary Cards & Target Info -->
                            <div class="p-3 bg-light border-bottom">
                                <div class="row g-2 align-items-center mb-3">
                                    <div class="col-12 col-md-7">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="badge bg-amber text-white font-weight-bold px-3 py-1.5 rounded-pill fs-4 shadow-sm">
                                                🏆 TARGET HARIAN STANDAR: {{ number_format($dynamicTargetPct, 1) }}%
                                            </span>
                                            <span class="text-muted small">
                                                (Laju 1.333%/hari sejak 15 Juni 2026)
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-5 text-md-end">
                                        <button class="btn btn-sm btn-outline-secondary font-weight-bold shadow-sm rounded-2" type="button" data-bs-toggle="collapse" data-bs-target="#methodologyCollapse" aria-expanded="false" aria-controls="methodologyCollapse">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-info-circle me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 9h.01"/><path d="M11 12h1v4h1"/></svg>
                                            <span>Lihat Metodologi & Formulasi</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Collapsible Methodology Explanation -->
                                <div class="collapse mb-3" id="methodologyCollapse">
                                    <div class="card card-body bg-white border border-amber-lt shadow-sm rounded-3">
                                        <h4 class="font-weight-extrabold text-amber mb-2">
                                            📘 Metodologi, Target Harian Standar, & Indikator Kinerja
                                        </h4>
                                        <div class="row g-3 small">
                                            <div class="col-md-4">
                                                <div class="p-2.5 border rounded bg-light h-100">
                                                    <div class="font-weight-bold text-primary mb-1">1. Target Harian Standar (1.333%/Hari)</div>
                                                    <p class="text-muted mb-0">Dihitung otomatis sejak 15 Juni 2026. Setiap hari target naik 1.333% hingga 100% pada hari ke-75. Target hari ini = <strong>{{ number_format($dynamicTargetPct, 1) }}%</strong>.</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-2.5 border rounded bg-light h-100">
                                                    <div class="font-weight-bold text-success mb-1">2. Skor Kinerja (Skala 0 - 100)</div>
                                                    <ul class="ps-3 text-muted mb-0">
                                                        <li><strong>Progress Score (Maks 45)</strong>: Rasio capaian submit vs target harian.</li>
                                                        <li><strong>Volume Score (Maks 55)</strong>: Volume muatan murni (usaha + keluarga terdata).</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-2.5 border rounded bg-light h-100">
                                                    <div class="font-weight-bold text-danger mb-1">3. Safety Rule & Warning 3-Hari</div>
                                                    <p class="text-muted mb-0">Petugas dengan Capaian &ge; Target Harian <strong>dilarang masuk kategori Malas</strong>. Warning 🚨 3 Hari Stagnan diberikan jika submit tidak bertambah 3 snapshot berturut-turut.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mini Metric Badges -->
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-success text-white px-2.5 py-1.5 rounded-pill font-weight-bold">
                                        🌟 Sangat Rajin: {{ number_format($rankingSummary['cnt_srajin'] ?? 0) }}
                                    </span>
                                    <span class="badge bg-success-lt text-success px-2.5 py-1.5 rounded-pill font-weight-bold border border-success-lt">
                                        🟢 Rajin: {{ number_format($rankingSummary['cnt_rajin'] ?? 0) }}
                                    </span>
                                    <span class="badge bg-warning-lt text-warning px-2.5 py-1.5 rounded-pill font-weight-bold border border-warning-lt">
                                        🟡 Cukup / Standar: {{ number_format($rankingSummary['cnt_cukup'] ?? 0) }}
                                    </span>
                                    <span class="badge bg-orange-lt text-orange px-2.5 py-1.5 rounded-pill font-weight-bold border border-orange-lt">
                                        ⚠️ Malas: {{ number_format($rankingSummary['cnt_malas'] ?? 0) }}
                                    </span>
                                    <span class="badge bg-danger text-white px-2.5 py-1.5 rounded-pill font-weight-bold">
                                        🔴 Sangat Malas: {{ number_format($rankingSummary['cnt_smalas'] ?? 0) }}
                                    </span>
                                    @if(($rankingSummary['cnt_stagnant'] ?? 0) > 0)
                                        <span class="badge bg-danger-lt text-danger px-2.5 py-1.5 rounded-pill font-weight-bold border border-danger">
                                            🚨 {{ number_format($rankingSummary['cnt_stagnant']) }} Petugas 3 Hari Stagnan
                                        </span>
                                    @endif
                                    @if(($rankingSummary['cnt_warning_usaha'] ?? 0) > 0)
                                        <span class="badge bg-danger text-white px-2.5 py-1.5 rounded-pill font-weight-bold shadow-xs">
                                            ⚠️ {{ number_format($rankingSummary['cnt_warning_usaha']) }} Petugas Warning Usaha (UP < 5% / UK < 10%)
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <table id="ranking-table" class="table table-vcenter table-striped card-table text-nowrap w-100 datatable-pre-init">
                                <thead>
                                    <tr class="bg-light text-uppercase small font-weight-bold">
                                        <th class="w-1 text-center">No</th>
                                        <th class="text-center">Peringkat</th>
                                        <th>Kecamatan</th>
                                        <th>Nama Petugas<br><span class="text-muted font-weight-normal small">/ Pencacah</span></th>
                                        <th>Nama Pengawas<br><span class="text-muted font-weight-normal small">/ PML</span></th>
                                        <th class="text-end">Beban<br>Saat Ini</th>
                                        <th class="text-end text-success font-weight-bold">Total<br>Submit</th>
                                        <th class="text-end text-primary font-weight-extrabold">% Capaian<br><span class="text-muted font-weight-normal small">vs {{ number_format($dynamicTargetPct, 1) }}%</span></th>
                                        <th class="text-center">Status / Warning<br><span class="text-muted font-weight-normal small">3 Hari Last</span></th>
                                        <th class="text-center text-danger">Warning Anomali Usaha<br><span class="text-muted font-weight-normal small">(UP < 5% / UK < 10%)</span></th>
                                        <th class="text-center text-indigo">Laju s.d. 20 Agt<br><span class="text-muted font-weight-normal small">(Kejar Target 95%)</span></th>
                                        <th class="text-end bg-amber-lt text-amber font-weight-extrabold">Skor Kinerja<br><span class="text-muted font-weight-normal small">(0 - 100)</span></th>
                                        <th class="text-center">Kategori Kinerja</th>
                                        <th>Rekomendasi Tindakan PML</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rankingRecords as $index => $row)
                                        <tr>
                                            <td class="text-muted small text-center">{{ $index + 1 }}</td>
                                            <td class="text-center font-weight-extrabold" data-order="{{ $index + 1 }}">
                                                @if($index == 0)
                                                    <span class="badge bg-warning text-dark px-2.5 py-1 rounded-circle fs-3 shadow-sm" title="Juara 1 Progres">🥇 #1</span>
                                                @elseif($index == 1)
                                                    <span class="badge bg-secondary text-white px-2.5 py-1 rounded-circle fs-3 shadow-sm" title="Juara 2 Progres">🥈 #2</span>
                                                @elseif($index == 2)
                                                    <span class="badge bg-amber-lt text-amber px-2.5 py-1 rounded-circle fs-3 border border-amber" title="Juara 3 Progres">🥉 #3</span>
                                                @else
                                                    <span class="badge bg-light text-dark border px-2 py-1">#{{ $index + 1 }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="font-weight-bold">{{ $kecNameMap[$row->kode_kec] ?? 'Kec. ' . $row->kode_kec }}</div>
                                                <div class="small text-muted">Kode: {{ $row->kode_kec }}</div>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold text-dark">{{ $row->nama_pencacah }}</div>
                                                <div class="small text-muted">{{ $row->email_pencacah }}</div>
                                            </td>
                                            <td>
                                                <div class="small font-weight-medium">{{ $row->nama_pengawas ?: '-' }}</div>
                                            </td>
                                            <td class="text-end font-weight-bold" data-order="{{ $row->beban_saat_ini }}">{{ number_format($row->beban_saat_ini) }}</td>
                                            <td class="text-end font-weight-bold text-success" data-order="{{ $row->total_submit }}">{{ number_format($row->total_submit) }}</td>
                                            <td class="text-end font-weight-extrabold text-primary fs-3" data-order="{{ $row->pct_submit }}">
                                                <span class="badge {{ $row->pct_submit >= $dynamicTargetPct ? 'bg-success-lt text-success' : ($row->pct_submit >= max(0, $dynamicTargetPct - 10) ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger') }} font-weight-bold px-2 py-1">
                                                    {{ number_format($row->pct_submit, 1) }}%
                                                </span>
                                            </td>
                                            <td class="text-center" data-order="{{ $row->warning_status }}">
                                                @if($row->warning_status === 'completed')
                                                    <span class="badge bg-success text-white font-weight-bold px-2 py-1 shadow-sm" title="Pencacahan Selesai 100%">
                                                        🎉 Selesai 100%
                                                    </span>
                                                @elseif($row->warning_status === 'stagnant_3d')
                                                    <span class="badge bg-danger text-white font-weight-bold px-2 py-1 shadow-sm" title="Submit tidak bertambah dalam 3 snapshot terakhir">
                                                        🚨 3 Hari Stagnan
                                                    </span>
                                                @elseif($row->warning_status === 'slow_progress')
                                                    <span class="badge bg-warning-lt text-warning font-weight-bold px-2 py-1" title="Laju harian di bawah rata-rata target">
                                                        ⚠️ Progres Lambat
                                                    </span>
                                                @else
                                                    <span class="badge bg-success-lt text-success px-2 py-1" title="Progres submit teratur">
                                                        ✅ On-Track
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center" data-order="{{ $row->has_warning_usaha ? count($row->anomali_sls_list) : 0 }}">
                                                @if($row->has_warning_usaha)
                                                    @php
                                                        $cntApproved = $row->cnt_anomali_approved;
                                                        $cntPending = $row->cnt_anomali_pending;
                                                        $cntBelum = $row->cnt_anomali_belum;
                                                        $totalAnomali = count($row->anomali_sls_list);
                                                        $slsJsonData = base64_encode(json_encode([
                                                            'nama_pencacah' => $row->nama_pencacah,
                                                            'email_pencacah' => $row->email_pencacah,
                                                            'sls_list' => $row->anomali_sls_list
                                                        ]));
                                                    @endphp

                                                    <div class="d-flex flex-column align-items-center gap-1">
                                                        @if($cntApproved === $totalAnomali && $totalAnomali > 0)
                                                            <span class="badge bg-success text-white font-weight-bold px-2 py-1 shadow-sm">
                                                                ✅ {{ $cntApproved }}/{{ $totalAnomali }} SLS Disetujui
                                                            </span>
                                                        @elseif($cntPending > 0)
                                                            <span class="badge bg-warning text-dark font-weight-bold px-2 py-1 shadow-sm">
                                                                ⏳ {{ $cntPending }} Menunggu Approval
                                                            </span>
                                                        @else
                                                            <span class="badge bg-danger text-white font-weight-bold px-2 py-1 shadow-sm">
                                                                🚨 {{ $cntBelum }} SLS Belum Ditindaklanjuti
                                                            </span>
                                                        @endif
                                                        <button type="button" class="btn btn-xs btn-outline-primary font-weight-bold px-2 py-0.5 mt-1 btn-detail-anomali"
                                                            data-sls-data="{{ $slsJsonData }}" style="border-radius: 6px; font-size: 0.75rem;">
                                                            🔍 Detail / Catatan
                                                        </button>
                                                    </div>
                                                @else
                                                    <span class="badge bg-success-lt text-success px-2 py-1">
                                                        ✅ Usaha Normal
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center" data-order="{{ $row->laju_harian_95 }}">
                                                @if($row->pct_submit >= 95.0)
                                                    <span class="badge bg-success-lt text-success font-weight-bold px-2 py-1">
                                                        ✅ Aman (&ge; 95%)
                                                    </span>
                                                @else
                                                    <div class="text-nowrap">
                                                        <span class="badge font-weight-extrabold px-2.5 py-1 border shadow-xs" style="color: #78350f !important; background-color: #fef3c7 !important; border-color: #f59e0b !important; font-size: 0.85rem;">
                                                            +{{ number_format($row->laju_harian_95) }} / hari
                                                        </span>
                                                        <div class="small text-muted mt-0.5" style="font-size: 0.75rem;">
                                                            Sisa {{ number_format($row->needed_to_95) }} submit ({{ $row->days_remaining_to_20aug }} hr lg)
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-end font-weight-extrabold text-amber bg-amber-lt fs-2" data-order="{{ $row->skor_kinerja }}">
                                                {{ number_format($row->skor_kinerja, 1) }}
                                            </td>
                                            <td class="text-center" data-order="{{ $row->kat_code }}">
                                                <span class="badge {{ $row->kat_badge }} px-2.5 py-1 font-weight-bold">
                                                    {{ $row->kat_label }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="small font-weight-medium text-dark">{{ $row->rekomendasi }}</div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="12" class="text-center py-5 text-muted">
                                                <div class="mb-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trophy-off" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path d="M8 21l8 0"/><path d="M12 17l0 4"/><path d="M8 4h9"/><path d="M17 4v8c0 .31-.028.614-.082.909"/><path d="M5 9a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M19 9a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M3 3l18 18"/></svg>
                                                </div>
                                                <div class="font-weight-bold fs-3">Tidak Ada Data Ranking Kinerja</div>
                                                <div class="small">Coba ubah kata kunci pencarian atau filter kecamatan yang dipilih.</div>
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
    </div>

    <!-- MODAL DETAIL & TINDAK LANJUT ANOMALI SLS -->
    <div class="modal fade" id="modalAnomaliDetail" tabindex="-1" aria-labelledby="modalAnomaliDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg" style="border-radius: 16px; border: 1px solid #cbd5e1;">
                <div class="modal-header bg-light py-3 border-bottom">
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark mb-0" id="modalAnomaliDetailLabel">
                            🚨 Detail & Tindak Lanjut Anomali Usaha SLS
                        </h5>
                        <div class="small text-muted" id="modalAnomaliSubTitle">Petugas: -</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 bg-info-lt text-dark mb-3 p-3 rounded-3 small">
                        <div class="font-weight-bold mb-1">ℹ️ Panduan Tindak Lanjut Anomali:</div>
                        <ul class="mb-0 ps-3">
                            <li><b>Perubahan Data (Probing Usaha Nambah):</b> Jika hasil pendataan bertambah usahanya (sehingga UP &ge; 5% / UK &ge; 10%), anomali akan <b>hilang otomatis</b> saat data diperbarui.</li>
                            <li><b>Data Tetap / Tidak Berubah:</b> Petugas dapat memberikan <b>catatan klarifikasi</b> (misal: SLS kawasan persawahan / pemukiman non-usaha). Catatan akan diajukan ke Admin untuk disetujui (Approval).</li>
                        </ul>
                    </div>

                    <!-- Container Tabel Daftar SLS Anomali -->
                    <div class="table-responsive">
                        <table class="table table-vcenter table-bordered small card-table">
                            <thead class="bg-light">
                                <tr>
                                    <th>Nama SLS & Kode</th>
                                    <th class="text-center">Muatan Murni</th>
                                    <th>Rincian Probing Usaha</th>
                                    <th class="text-center">Status & Catatan Admin</th>
                                    <th class="text-center">Aksi / Tindak Lanjut</th>
                                </tr>
                            </thead>
                            <tbody id="modalAnomaliTbody">
                                <!-- Injected dynamically via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Form Accordion untuk Input Catatan -->
                    <div id="formContainerCatatan" class="mt-4 p-3 bg-light rounded-3 border d-none">
                        <h6 class="font-weight-bold text-primary mb-2">
                            ✏️ Form Input Catatan Klarifikasi: <span id="formNamaSls" class="text-dark"></span>
                        </h6>
                        <form id="formSimpanCatatan">
                            @csrf
                            <input type="hidden" id="formRegionCode" name="region_code" value="">
                            <input type="hidden" id="formNamaPetugas" name="nama_petugas" value="">
                            <input type="hidden" id="formEmailPetugas" name="email_petugas" value="">

                            <div class="mb-3">
                                <label class="form-label font-weight-bold small text-muted">Catatan Klarifikasi / Alasan Lapangan <span class="text-danger">*</span></label>
                                <textarea id="formCatatanText" name="catatan" class="form-control" rows="3" required minlength="5" placeholder="Contoh: SLS 001B merupakan kawasan persawahan murni dan pemukiman tani, tidak ditemukan usaha komersial/perusahaan."></textarea>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary btn-sm" id="btnCancelFormCatatan">Batal</button>
                                <button type="submit" class="btn btn-primary btn-sm font-weight-bold" id="btnSubmitCatatan">
                                    <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerSubmit"></span>
                                    Kirim Catatan ke Admin
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent AMD loaders (e.g. Vite/TinyMCE) from hijacking DataTables CDN attachment
        window._tempDefine = window.define;
        window.define = null;
    </script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        window.define = window._tempDefine;
        // Isolate DataTables jQuery instance to guarantee $.fn.DataTable is preserved
        window.jqDT = jQuery.noConflict(true);
    </script>
    <script>
        (function($) {
            $(document).ready(function() {
                if ($ && $.fn && typeof $.fn.DataTable === 'function') {
                    // Table 1: Petugas Summary
                    var tablePetugas = $('#pengolahan-table').DataTable({
                        language: {
                            search: "_INPUT_",
                            searchPlaceholder: "🔍 Cari cepat nama, email, pengawas, kec...",
                            lengthMenu: "Tampilkan _MENU_ data",
                            info: "Menampilkan <strong>_START_</strong> s.d. <strong>_END_</strong> dari total <strong>_TOTAL_</strong> petugas",
                            infoEmpty: "Menampilkan 0 s.d. 0 dari 0 petugas",
                            infoFiltered: "(disaring dari _MAX_ total data)",
                            zeroRecords: "Tidak ada data petugas yang cocok dengan pencarian",
                            paginate: {
                                first: "Pertama",
                                previous: "← Sebelum",
                                next: "Lanjut →",
                                last: "Terakhir"
                            }
                        },
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                        order: [[4, 'desc']], // Default sort: Muatan Murni (Index 4) Descending
                        columnDefs: [
                            { orderable: false, targets: [0] } // Disable sorting for 'No' column
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

                    // Table 2: PML Summary
                    var tablePml = $('#pml-table').DataTable({
                        language: {
                            search: "_INPUT_",
                            searchPlaceholder: "🔍 Cari nama pengawas/PML, email, kec...",
                            lengthMenu: "Tampilkan _MENU_ data",
                            info: "Menampilkan <strong>_START_</strong> s.d. <strong>_END_</strong> dari total <strong>_TOTAL_</strong> PML",
                            infoEmpty: "Menampilkan 0 s.d. 0 dari 0 PML",
                            infoFiltered: "(disaring dari _MAX_ total data)",
                            zeroRecords: "Tidak ada data PML yang cocok dengan pencarian",
                            paginate: {
                                first: "Pertama",
                                previous: "← Sebelum",
                                next: "Lanjut →",
                                last: "Terakhir"
                            }
                        },
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                        order: [[5, 'desc']], // Default sort: Muatan Murni (Index 5) Descending
                        columnDefs: [
                            { orderable: false, targets: [0] } // Disable sorting for 'No' column
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

                    // Table 3: SLS Allocation
                    var tableSls = $('#sls-table').DataTable({
                        language: {
                            search: "_INPUT_",
                            searchPlaceholder: "🔍 Cari nama SLS, kode, petugas, pengawas...",
                            lengthMenu: "Tampilkan _MENU_ data",
                            info: "Menampilkan <strong>_START_</strong> s.d. <strong>_END_</strong> dari total <strong>_TOTAL_</strong> SLS",
                            infoEmpty: "Menampilkan 0 s.d. 0 dari 0 SLS",
                            infoFiltered: "(disaring dari _MAX_ total SLS)",
                            zeroRecords: "Tidak ada data SLS yang cocok dengan pencarian",
                            paginate: {
                                first: "Pertama",
                                previous: "← Sebelum",
                                next: "Lanjut →",
                                last: "Terakhir"
                            }
                        },
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                        order: [[1, 'asc']], // Default sort: Kecamatan ascending
                        columnDefs: [
                            { orderable: false, targets: [0] } // Disable sorting for 'No' column
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

                    // Table 4: Ranking Kinerja Petugas
                    var tableRanking = $('#ranking-table').DataTable({
                        language: {
                            search: "_INPUT_",
                            searchPlaceholder: "🔍 Cari nama petugas, pengawas, kec, status...",
                            lengthMenu: "Tampilkan _MENU_ data",
                            info: "Menampilkan <strong>_START_</strong> s.d. <strong>_END_</strong> dari total <strong>_TOTAL_</strong> petugas",
                            infoEmpty: "Menampilkan 0 s.d. 0 dari 0 petugas",
                            infoFiltered: "(disaring dari _MAX_ total data)",
                            zeroRecords: "Tidak ada data petugas yang cocok dengan pencarian",
                            paginate: {
                                first: "Pertama",
                                previous: "← Sebelum",
                                next: "Lanjut →",
                                last: "Terakhir"
                            }
                        },
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                        order: [[7, 'desc']], // Default sort: % Capaian (Index 7) Descending
                        columnDefs: [
                            { orderable: false, targets: [0] } // Disable sorting for 'No' column
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

                    @if($search)
                        tablePetugas.search("{{ $search }}").draw();
                        tablePml.search("{{ $search }}").draw();
                        tableSls.search("{{ $search }}").draw();
                        tableRanking.search("{{ $search }}").draw();
                    @endif

                    // Adjust DataTables column width when switching tabs
                    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                    });

                    // Smoothly hide loading overlay and reveal DataTables once ready
                    function revealDataTables() {
                        $('#table-loading-overlay').addClass('hidden');
                        $('#pengolahan-table, #pml-table, #sls-table, #ranking-table').removeClass('datatable-pre-init').addClass('datatable-initialized');
                    }

                    function b64DecodeUnicode(str) {
                        try {
                            return decodeURIComponent(Array.prototype.map.call(atob(str), function(c) {
                                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                            }).join(''));
                        } catch(e) {
                            return atob(str);
                        }
                    }

                    // =====================================================
                    // Anomali SLS Detail Modal & Form JS Handlers
                    // =====================================================
                    $(document).on('click', '.btn-detail-anomali', function() {
                        var rawData = $(this).attr('data-sls-data');
                        if (!rawData) return;
                        var data = {};
                        try {
                            data = JSON.parse(b64DecodeUnicode(rawData));
                        } catch(e) {
                            console.error("Failed parsing b64 json:", e);
                            return;
                        }

                        $('#modalAnomaliSubTitle').text('Petugas: ' + data.nama_pencacah + ' (' + data.email_pencacah + ')');
                        $('#formContainerCatatan').addClass('d-none');

                        var html = '';
                        $.each(data.sls_list, function(idx, sls) {
                            var upHtml = sls.is_low_up ? '<span class="text-danger font-weight-bold">🏢 UP: ' + sls.up_sls + ' (' + sls.pct_up + '% < min 5%)</span>' : '<span class="text-success">🏢 UP: ' + sls.up_sls + ' (' + sls.pct_up + '% ✅)</span>';
                            var ukHtml = sls.is_low_uk ? '<span class="text-danger font-weight-bold">🏡 UK: ' + sls.uk_sls + ' (' + sls.pct_uk + '% < min 10%)</span>' : '<span class="text-success">🏡 UK: ' + sls.uk_sls + ' (' + sls.pct_uk + '% ✅)</span>';

                            var statusBadge = '';
                            var catatanContent = '';
                            var aksiBtn = '';

                            if (sls.status_tindak_lanjut === 'approved') {
                                statusBadge = '<span class="badge bg-success text-white font-weight-bold px-2 py-1">✅ Disetujui Admin</span>';
                                catatanContent = '<div class="mt-1 small bg-white p-2 rounded border text-dark text-start"><b>Catatan Petugas:</b> ' + (sls.catatan_petugas || '-') + '</div>';
                                if (sls.catatan_admin) {
                                    catatanContent += '<div class="mt-1 small text-success text-start"><b>Catatan Admin:</b> ' + sls.catatan_admin + '</div>';
                                }
                                aksiBtn = '<span class="text-muted small font-weight-bold">✅ Selesai</span>';
                            } else if (sls.status_tindak_lanjut === 'pending') {
                                statusBadge = '<span class="badge bg-warning text-dark font-weight-bold px-2 py-1">⏳ Menunggu Approval</span>';
                                catatanContent = '<div class="mt-1 small bg-white p-2 rounded border text-dark text-start"><b>Catatan:</b> ' + (sls.catatan_petugas || '-') + '</div>';
                                aksiBtn = '<button type="button" class="btn btn-xs btn-outline-secondary btn-buka-form" data-code="' + sls.region_code + '" data-nama="' + sls.nama_sls + '" data-pencacah="' + data.nama_pencacah + '" data-email="' + data.email_pencacah + '" data-existing="' + (sls.catatan_petugas || '') + '">✏️ Edit Catatan</button>';
                            } else if (sls.status_tindak_lanjut === 'rejected') {
                                statusBadge = '<span class="badge bg-danger text-white font-weight-bold px-2 py-1">❌ Catatan Ditolak</span>';
                                catatanContent = '<div class="mt-1 small text-danger text-start"><b>Alasan Penolakan:</b> ' + (sls.catatan_admin || 'Perlu perbaikan alasan') + '</div>';
                                aksiBtn = '<button type="button" class="btn btn-xs btn-outline-danger btn-buka-form" data-code="' + sls.region_code + '" data-nama="' + sls.nama_sls + '" data-pencacah="' + data.nama_pencacah + '" data-email="' + data.email_pencacah + '" data-existing="' + (sls.catatan_petugas || '') + '">✏️ Perbaiki Catatan</button>';
                            } else {
                                statusBadge = '<span class="badge bg-secondary text-white px-2 py-1">🔴 Belum Ada Catatan</span>';
                                aksiBtn = '<button type="button" class="btn btn-xs btn-primary font-weight-bold btn-buka-form" data-code="' + sls.region_code + '" data-nama="' + sls.nama_sls + '" data-pencacah="' + data.nama_pencacah + '" data-email="' + data.email_pencacah + '" data-existing="">➕ Beri Catatan</button>';
                            }

                            html += '<tr>';
                            html += '<td><div class="font-weight-bold">' + sls.nama_sls + '</div><div class="font-monospace text-muted small">' + sls.region_code + '</div></td>';
                            html += '<td class="text-center font-weight-bold text-teal">' + sls.muatan_murni + '</td>';
                            html += '<td>' + upHtml + '<br>' + ukHtml + '</td>';
                            html += '<td class="text-center">' + statusBadge + catatanContent + '</td>';
                            html += '<td class="text-center">' + aksiBtn + '</td>';
                            html += '</tr>';
                        });

                        $('#modalAnomaliTbody').html(html);
                        var modalEl = document.getElementById('modalAnomaliDetail');
                        if (window.bootstrap && window.bootstrap.Modal) {
                            var myModal = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
                            myModal.show();
                        } else if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
                            $(modalEl).modal('show');
                        } else {
                            $(modalEl).addClass('show').css('display', 'block');
                        }
                    });

                    // Buka Form Input Catatan
                    $(document).on('click', '.btn-buka-form', function() {
                        var code = $(this).attr('data-code');
                        var nama = $(this).attr('data-nama');
                        var pencacah = $(this).attr('data-pencacah');
                        var email = $(this).attr('data-email');
                        var existing = $(this).attr('data-existing');

                        $('#formRegionCode').val(code);
                        $('#formNamaPetugas').val(pencacah);
                        $('#formEmailPetugas').val(email);
                        $('#formNamaSls').text(nama + ' (' + code + ')');
                        $('#formCatatanText').val(existing);

                        $('#formContainerCatatan').removeClass('d-none');
                    });

                    $('#btnCancelFormCatatan').click(function() {
                        $('#formContainerCatatan').addClass('d-none');
                    });

                    // AJAX Submit Catatan
                    $('#formSimpanCatatan').submit(function(e) {
                        e.preventDefault();

                        var btn = $('#btnSubmitCatatan');
                        var spinner = $('#spinnerSubmit');
                        btn.prop('disabled', true);
                        spinner.removeClass('d-none');

                        $.ajax({
                            url: '{{ route("dashboard.pengolahan.catatan-anomali") }}',
                            type: 'POST',
                            data: $(this).serialize(),
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                            },
                            success: function(resp) {
                                btn.prop('disabled', false);
                                spinner.addClass('d-none');

                                alert('✅ ' + resp.message);
                                $('#formContainerCatatan').addClass('d-none');
                                location.reload();
                            },
                            error: function(err) {
                                btn.prop('disabled', false);
                                spinner.addClass('d-none');
                                var errMsg = 'Gagal menyimpan catatan.';
                                if (err.responseJSON && err.responseJSON.message) {
                                    errMsg = err.responseJSON.message;
                                }
                                alert('❌ Error: ' + errMsg);
                            }
                        });
                    });

                    setTimeout(revealDataTables, 150);
                } else {
                    console.error("DataTables plugin is not available on isolated jQuery instance.");
                    $('#table-loading-overlay').addClass('hidden');
                    $('#pengolahan-table, #pml-table, #sls-table, #ranking-table').removeClass('datatable-pre-init').addClass('datatable-initialized');
                }
            });
        })(window.jqDT);
    </script>
@endpush

