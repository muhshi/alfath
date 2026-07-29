@extends('tablar::page')

@section('content')
    <!-- Page header -->
    <x-page-header title="Beranda ALFATH">
        <div class="col-12 col-md-auto ms-auto d-print-none">
            <a href="{{ route('dashboard.se2026') }}" class="btn btn-warning btn-lg shadow-sm text-dark font-weight-bold">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-chart-bar me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M3 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"></path>
                    <path d="M12 8m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"></path>
                    <path d="M4 20l14 0"></path>
                </svg>
                Dashboard Executive SE2026
            </a>
        </div>
    </x-page-header>

    <!-- BEGIN PAGE BODY -->
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                
                <!-- Hero Banner Card -->
                <div class="col-12">
                    <div class="card card-md border-0 text-white shadow-sm" style="background: linear-gradient(135deg, #1d4ed8 0%, #ea580c 100%); border-radius: 16px;">
                        <div class="card-body p-4 p-md-5">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <span class="badge bg-white text-dark font-weight-bold px-3 py-2 mb-3 rounded-pill shadow-xs">
                                        <span class="status-dot status-dot-animated bg-success me-1"></span> SYSTEM MONITORING BPS DEMAK
                                    </span>
                                    <h1 class="display-6 font-weight-bold text-white mb-2">Selamat Datang di ALFATH</h1>
                                    <p class="fs-3 text-white-50 mb-4" style="max-width: 600px;">
                                        Aplikasi FASIH Monitoring Harian BPS Kabupaten Demak. Portal terpadu pemanduan data lapangan dan monitoring Sensus Ekonomi 2026.
                                    </p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('dashboard.se2026') }}" class="btn btn-warning btn-lg font-weight-bold text-dark px-4 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                            Buka Public Dashboard Executive SE2026
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-4 d-none d-md-block text-end">
                                    <div class="p-3 bg-white-10 rounded-3 backdrop-blur" style="border: 1px solid rgba(255,255,255,0.2);">
                                        <i class="fa-solid fa-chart-pie display-1 text-white opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm shadow-xs border-0 rounded-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-primary-lt text-primary avatar rounded-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M9 17l0 -5"/><path d="M12 17l0 -1"/><path d="M15 17l0 -3"/></svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-bold fs-2 text-dark">
                                        {{ number_format($totalSurveys) }}
                                    </div>
                                    <div class="text-muted font-weight-medium">
                                        Total Survei Terdaftar
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm shadow-xs border-0 rounded-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-green-lt text-green avatar rounded-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-bold fs-2 text-dark">
                                        {{ number_format($totalTeams) }}
                                    </div>
                                    <div class="text-muted font-weight-medium">
                                        Tim Kerja Aktif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm shadow-xs border-0 rounded-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-warning-lt text-warning avatar rounded-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M3 12h4l3 8l4 -16l3 8h4"/></svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-bold fs-2 text-dark">
                                        {{ number_format($activeSurveys) }}
                                    </div>
                                    <div class="text-muted font-weight-medium">
                                        Survei Berjalan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm shadow-xs border-0 rounded-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-orange-lt text-orange avatar rounded-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M12 21l-8 -4.5v-9l8 -4.5l8 4.5v9z"/><path d="M12 12l8 -4.5"/><path d="M12 12v9"/><path d="M12 12l-8 -4.5"/></svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-bold fs-2 text-dark">
                                        8.270
                                    </div>
                                    <div class="text-muted font-weight-medium">
                                        Sub SLS SE2026
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Surveys Table Card -->
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-transparent py-3">
                            <h3 class="card-title font-weight-bold">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon text-primary me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M9 11l3 3l8 -8"/><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9"/></svg>
                                Daftar Survei Lapangan Terbaru
                            </h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Nama Survei</th>
                                        <th>Tim Kerja</th>
                                        <th>Periode Pendataan</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentSurveys as $survey)
                                        <tr>
                                            <td class="font-weight-bold text-dark">{{ $survey->name }}</td>
                                            <td><span class="badge bg-blue-lt">{{ $survey->team->name ?? '-' }}</span></td>
                                            <td class="text-muted fs-4">
                                                {{ $survey->start_periode?->format('d M Y') }} - {{ $survey->end_periode?->format('d M Y') }}
                                            </td>
                                            <td>
                                                @if($survey->start_periode <= now() && $survey->end_periode >= now())
                                                    <span class="badge bg-success-lt text-success font-weight-bold">● Aktif</span>
                                                @elseif($survey->end_periode < now())
                                                    <span class="badge bg-secondary-lt text-secondary font-weight-bold">● Selesai</span>
                                                @else
                                                    <span class="badge bg-info-lt text-info font-weight-bold">● Mendatang</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('surveys.embed', $survey) }}" class="btn btn-outline-primary btn-sm rounded-pill font-weight-bold">
                                                    Lihat Monitoring
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="fa-solid fa-folder-open mb-2 fs-2 d-block opacity-50"></i>
                                                Belum ada data survei yang terdaftar.
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
    <!-- END PAGE BODY -->
@endsection