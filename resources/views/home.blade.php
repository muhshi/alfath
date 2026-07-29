@extends('tablar::page')

@section('content')
    <!-- Page header -->
    <x-page-header title="Beranda ALFATH">
        <div class="col-12 col-md-auto ms-auto d-print-none">
            <a href="{{ route('dashboard.se2026') }}" class="btn text-white font-weight-bold shadow-sm" style="background-color: #ea580c; border-color: #ea580c; border-radius: 10px; padding: 0.6rem 1.4rem;">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-chart-bar me-1" width="22" height="22" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
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
                
                <!-- Hero Banner Card (Elegan & Clean - Non-Gradient) -->
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
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('dashboard.se2026') }}" class="btn text-white font-weight-bold px-4 py-2 shadow-sm" style="background-color: #ea580c; border-color: #ea580c; border-radius: 10px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                            Buka Executive Dashboard SE2026
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
                                        Survei Berjalan
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

                <!-- Recent Surveys Table Card -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3" style="background: #ffffff;">
                        <div class="card-header bg-transparent py-3 border-bottom">
                            <h3 class="card-title font-weight-bold text-dark mb-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon text-orange me-1" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M9 11l3 3l8 -8"/><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9"/></svg>
                                Daftar Survei Lapangan Terdaftar
                            </h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter table-hover text-nowrap">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="font-weight-bold text-muted">Nama Survei</th>
                                        <th class="font-weight-bold text-muted">Tim Kerja</th>
                                        <th class="font-weight-bold text-muted">Periode Pendataan</th>
                                        <th class="font-weight-bold text-muted">Status</th>
                                        <th class="font-weight-bold text-muted text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentSurveys as $survey)
                                        <tr>
                                            <td class="font-weight-bold text-dark">{{ $survey->name }}</td>
                                            <td><span class="badge bg-blue-lt font-weight-medium">{{ $survey->team->name ?? '-' }}</span></td>
                                            <td class="text-muted small">
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