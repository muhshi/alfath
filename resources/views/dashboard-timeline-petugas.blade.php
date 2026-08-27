@extends('tablar::page')

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        .timeline-card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            background: #ffffff;
            position: relative;
        }

        .stat-badge-card {
            border-radius: 10px;
            padding: 1rem 1.25rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            transition: transform 0.15s ease-in-out;
        }

        .stat-badge-card:hover {
            transform: translateY(-2px);
        }

        /* Loading Overlay Style */
        .table-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(3px);
            z-index: 50;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.2s ease-out, visibility 0.2s ease-out;
            border-radius: 12px;
        }
        .table-loading-overlay.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        /* Heatmap Cell Custom Classes */
        .hm-cell {
            padding: 0.4rem 0.5rem !important;
            text-align: center !important;
            vertical-align: middle !important;
            font-size: 0.8125rem;
            min-width: 46px;
            border: 1px solid #e2e8f0 !important;
            transition: background-color 0.15s ease, filter 0.15s ease;
        }

        .hm-cell:hover {
            filter: brightness(0.92);
            cursor: pointer;
        }

        /* Data Tidak Ditarik - Abu-abu Netral */
        .hm-no-data {
            background-color: #f1f5f9 !important;
            color: #94a3b8 !important;
            font-weight: 500;
        }

        /* 0 Submit - Merah Kontras Tinggi */
        .hm-zero {
            background-color: #fee2e2 !important;
            color: #991b1b !important;
            font-weight: 700;
        }

        /* 1 - 5 Submit - Hijau Muda */
        .hm-low {
            background-color: #dcfce7 !important;
            color: #166534 !important;
            font-weight: 700;
        }

        /* 6 - 15 Submit - Hijau Sedang */
        .hm-med {
            background-color: #86efac !important;
            color: #14532d !important;
            font-weight: 700;
        }

        /* > 15 Submit - Hijau Tua Pekat (Teks Putih Kontras) */
        .hm-high {
            background-color: #22c55e !important;
            color: #ffffff !important;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .sticky-col-left {
            position: sticky !important;
            left: 0 !important;
            background-color: #ffffff !important;
            z-index: 10 !important;
            box-shadow: 3px 0 6px -2px rgba(0, 0, 0, 0.08) !important;
        }

        .sticky-col-left-header {
            position: sticky !important;
            left: 0 !important;
            background-color: #f8fafc !important;
            z-index: 20 !important;
            box-shadow: 3px 0 6px -2px rgba(0, 0, 0, 0.08) !important;
        }

        .table-responsive-timeline {
            max-height: calc(100vh - 300px);
            overflow-y: auto;
            overflow-x: auto;
        }

        .legend-box {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
            margin-bottom: 0.25rem;
            border: 1px solid rgba(0,0,0,0.08);
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-3">
        <!-- Page Title & Navigation Header -->
        <div class="row align-items-center mb-3">
            <div class="col">
                <div class="page-pretitle text-muted">SE2026 Monitoring Activity Log</div>
                <h2 class="page-title fw-bold text-dark mb-0">
                    <span class="me-2">📅</span> Timeline & Heatmap Submit Harian Petugas
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('dashboard.pengolahan') }}" class="btn btn-outline-secondary shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-left" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l14 0" />
                            <path d="M5 12l6 6" />
                            <path d="M5 12l6 -6" />
                        </svg>
                        Dashboard Pengolahan Utama
                    </a>
                </div>
            </div>
        </div>

        <!-- Summary KPI Cards -->
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="stat-badge-card border-start border-4 border-primary">
                    <div class="text-muted small fw-semibold text-uppercase">Total Petugas (PPL)</div>
                    <div class="h2 fw-bold text-primary mb-0 mt-1">{{ number_format($summary['totalPetugas'], 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-badge-card border-start border-4 border-info">
                    <div class="text-muted small fw-semibold text-uppercase">Rata-rata Hari Kerja</div>
                    <div class="h2 fw-bold text-info mb-0 mt-1">{{ $summary['avgHariKerja'] }} <span class="fs-6 text-muted font-normal">hari</span></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-badge-card border-start border-4 border-success">
                    <div class="text-muted small fw-semibold text-uppercase">Total Submit Seluruh Petugas</div>
                    <div class="h2 fw-bold text-success mb-0 mt-1">{{ number_format($summary['totalSubmit'], 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-badge-card border-start border-4 border-teal">
                    <div class="text-muted small fw-semibold text-uppercase">Rerata Submit / Hari Kerja</div>
                    <div class="h2 fw-bold text-teal mb-0 mt-1">{{ $summary['avgSubmitPerHari'] }} <span class="fs-6 text-muted font-normal">dok/hari</span></div>
                </div>
            </div>
        </div>

        <!-- Heatmap Table Card -->
        <div class="card timeline-card">
            <!-- Loading Overlay Component -->
            <div id="tableLoadingOverlay" class="table-loading-overlay hidden">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="fw-bold text-dark fs-3 mb-1">Memuat Data Timeline...</div>
                <div class="text-muted small">Mohon tunggu sejenak, mengagregasi data submit harian petugas</div>
            </div>

            <div class="card-header bg-light py-2 px-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <!-- Legend Bar -->
                <div class="d-flex flex-wrap align-items-center">
                    <span class="text-muted small fw-bold me-2">Indikator Heatmap:</span>
                    <span class="legend-box hm-no-data" data-bs-toggle="tooltip" title="Sistem Alfath tidak melakukan penarikan data pada tanggal ini">
                        - Data Tidak Ditarik
                    </span>
                    <span class="legend-box hm-zero" data-bs-toggle="tooltip" title="Petugas ditarik tapi tidak melakukan submit (0)">
                        0 Submit (Tidak Aktif)
                    </span>
                    <span class="legend-box hm-low">
                        1 - 5 Submit
                    </span>
                    <span class="legend-box hm-med">
                        6 - 15 Submit
                    </span>
                    <span class="legend-box hm-high">
                        > 15 Submit
                    </span>
                </div>

                <!-- Filters Toolbar -->
                <form id="timelineFilterForm" action="{{ route('dashboard.timeline-petugas') }}" method="GET" class="d-flex align-items-center gap-2 m-0 ms-auto">
                    <select name="kodekec" class="form-select form-select-sm" style="min-width: 150px;" onchange="document.getElementById('timelineFilterForm').requestSubmit()">
                        <option value="">-- Semua Kecamatan --</option>
                        @foreach ($kecNameMap as $code => $name)
                            <option value="{{ $code }}" {{ $kodekec == $code ? 'selected' : '' }}>
                                {{ $code }} - {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="search" class="form-select form-select-sm" style="min-width: 200px;" placeholder="Cari Nama / Email..." value="{{ $search }}">
                    <button type="submit" id="btnFilterSubmit" class="btn btn-sm btn-primary">
                        Cari
                    </button>
                    @if (!empty($search) || !empty($kodekec))
                        <a href="{{ route('dashboard.timeline-petugas') }}" id="btnFilterReset" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive table-responsive-timeline">
                    <table class="table table-vcenter table-bordered table-sm mb-0">
                        <thead class="bg-light sticky-top" style="z-index: 15;">
                            <tr>
                                <th class="sticky-col-left-header text-center align-middle bg-light" style="width: 40px;">No</th>
                                <th class="sticky-col-left-header align-middle bg-light" style="min-width: 200px;">Nama Petugas</th>
                                <th class="text-center align-middle bg-light" style="min-width: 110px;">Kecamatan</th>
                                
                                {{-- Tanggal Headers --}}
                                @foreach ($calendarDates as $cDate)
                                    @php
                                        $cCarbon = \Carbon\Carbon::parse($cDate);
                                        $isPulled = isset($pulledDatesSet[$cDate]);
                                    @endphp
                                    <th class="text-center align-middle px-1 py-2 {{ !$isPulled ? 'bg-slate-100 text-muted font-normal' : 'bg-light' }}" 
                                        style="font-size: 0.7rem; min-width: 48px; line-height: 1.1;"
                                        title="{{ $cCarbon->isoFormat('D MMMM YYYY') }} {{ !$isPulled ? '(Data tidak ditarik)' : '' }}">
                                        <div>{{ $cCarbon->format('d/m') }}</div>
                                        <div class="text-muted small" style="font-size: 0.65rem;">{{ $cCarbon->isoFormat('dd') }}</div>
                                    </th>
                                @endforeach

                                {{-- Ringkasan Headers --}}
                                <th class="text-center align-middle bg-info-lt text-dark fw-bold" style="min-width: 90px;">
                                    Hari Kerja
                                </th>
                                <th class="text-center align-middle bg-success-lt text-dark fw-bold" style="min-width: 100px;">
                                    Total Submit
                                </th>
                                <th class="text-center align-middle bg-primary-lt text-dark fw-bold" style="min-width: 110px;">
                                    Rata-rata/Hari
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($records as $index => $rec)
                                <tr>
                                    <td class="sticky-col-left text-center align-middle text-muted small fw-semibold">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="sticky-col-left align-middle">
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 220px;" title="{{ $rec['nama'] }}">
                                            {{ $rec['nama'] }}
                                        </div>
                                        <div class="text-muted small text-truncate" style="font-size: 0.7rem; max-width: 220px;" title="{{ $rec['email'] }}">
                                            {{ $rec['email'] }}
                                        </div>
                                    </td>
                                    <td class="text-center align-middle text-muted small">
                                        <span class="badge bg-secondary-lt">{{ $rec['nama_kec'] }}</span>
                                    </td>

                                    {{-- Tanggal Heatmap Cells --}}
                                    @foreach ($calendarDates as $cDate)
                                        @php
                                            $cell = $rec['daily_submits'][$cDate] ?? ['status' => 'no_data', 'val' => null, 'label' => '-'];
                                            $cFormatted = \Carbon\Carbon::parse($cDate)->isoFormat('D MMMM YYYY');
                                            
                                            $cellClass = 'hm-no-data';
                                            $tooltipText = $rec['nama'] . ' (' . $cFormatted . '): Data tidak ditarik';
                                            $cellValStr = '-';

                                            if ($cell['status'] === 'ok') {
                                                $val = (int) $cell['val'];
                                                $cellValStr = number_format($val, 0, ',', '.');
                                                $tooltipText = $rec['nama'] . ' (' . $cFormatted . '): ' . $cellValStr . ' submit';

                                                if ($val === 0) {
                                                    $cellClass = 'hm-zero';
                                                } elseif ($val <= 5) {
                                                    $cellClass = 'hm-low';
                                                } elseif ($val <= 15) {
                                                    $cellClass = 'hm-med';
                                                } else {
                                                    $cellClass = 'hm-high';
                                                }
                                            }
                                        @endphp

                                        <td class="hm-cell {{ $cellClass }}" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="{{ $tooltipText }}">
                                            {{ $cellValStr }}
                                        </td>
                                    @endforeach

                                    {{-- Ringkasan Columns --}}
                                    <td class="text-center align-middle bg-info-lt">
                                        <span class="badge bg-info text-white fw-bold px-2 py-1 fs-6">
                                            {{ $rec['working_days'] }} <span class="small font-normal">hr</span>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle bg-success-lt fw-bold text-success fs-6">
                                        {{ number_format($rec['total_submit'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-center align-middle bg-primary-lt">
                                        <span class="fw-bold text-primary">
                                            {{ $rec['avg_submit_per_working_day'] }}
                                        </span>
                                        <span class="text-muted small">/hr</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($calendarDates) + 6 }}" class="text-center py-4 text-muted">
                                        <em>Tidak ada data petugas yang cocok dengan filter.</em>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer bg-light py-2 px-3 d-flex align-items-center justify-content-between text-muted small">
                <div>
                    Menampilkan <strong>{{ $records->count() }}</strong> petugas dari total <strong>{{ $summary['totalPetugas'] }}</strong>.
                </div>
                <div>
                    <span class="me-2">📍 Catatan: <strong>Data tidak ditarik</strong> menandakan Alfath tidak menjalankan scraper/sync pada hari tersebut.</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Enable Bootstrap Tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: 'hover'
                });
            });

            // Loading Feedback Overlay & Button Spinner on Filter Form
            var filterForm = document.getElementById('timelineFilterForm');
            var loadingOverlay = document.getElementById('tableLoadingOverlay');
            var btnSubmit = document.getElementById('btnFilterSubmit');

            if (filterForm) {
                filterForm.addEventListener('submit', function () {
                    showLoadingState();
                });
            }

            var resetBtn = document.getElementById('btnFilterReset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    showLoadingState();
                });
            }

            function showLoadingState() {
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('hidden');
                }
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memuat...';
                }
            }
        });
    </script>
@endpush
