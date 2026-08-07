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
                    <ul class="nav nav-tabs card-header-tabs font-weight-bold" id="dashboard-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="petugas-tab" data-bs-toggle="tab" data-bs-target="#tab-petugas" type="button" role="tab" aria-controls="tab-petugas" aria-selected="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-check me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4"/><path d="M16 19l2 2l4 -4"/></svg>
                                Ringkasan Per PPL ({{ number_format($records->count()) }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-indigo" id="pml-tab" data-bs-toggle="tab" data-bs-target="#tab-pml" type="button" role="tab" aria-controls="tab-pml" aria-selected="false">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                                Ringkasan Per PML ({{ number_format($pmlRecords->count()) }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-primary" id="sls-tab" data-bs-toggle="tab" data-bs-target="#tab-sls" type="button" role="tab" aria-controls="tab-sls" aria-selected="false">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-map-pin me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z"/></svg>
                                Alokasi Per SLS ({{ number_format($slsRecords->count()) }})
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

                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('js')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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

                    @if($search)
                        tablePetugas.search("{{ $search }}").draw();
                        tablePml.search("{{ $search }}").draw();
                        tableSls.search("{{ $search }}").draw();
                    @endif

                    // Adjust DataTables column width when switching tabs
                    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                    });

                    // Smoothly hide loading overlay and reveal DataTables once ready
                    function revealDataTables() {
                        $('#table-loading-overlay').addClass('hidden');
                        $('#pengolahan-table, #pml-table, #sls-table').removeClass('datatable-pre-init').addClass('datatable-initialized');
                    }

                    setTimeout(revealDataTables, 150);
                } else {
                    console.error("DataTables plugin is not available on isolated jQuery instance.");
                    $('#table-loading-overlay').addClass('hidden');
                    $('#pengolahan-table, #pml-table, #sls-table').removeClass('datatable-pre-init').addClass('datatable-initialized');
                }
            });
        })(window.jqDT);
    </script>
@endpush

