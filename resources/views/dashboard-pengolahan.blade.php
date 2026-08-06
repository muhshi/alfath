@extends('tablar::page')

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
                        <input type="hidden" name="sort" value="{{ $sortBy }}">
                        <input type="hidden" name="dir" value="{{ $sortDir }}">

                        <!-- Input Search -->
                        <div class="col-12 col-md-4">
                            <label class="form-label font-weight-bold small text-muted mb-1">Pencarian Petugas / Pengawas</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/></svg>
                                </span>
                                <input type="text" name="search" class="form-control" placeholder="Cari nama / email pencacah / pengawas..." value="{{ $search }}">
                            </div>
                        </div>

                        <!-- Dropdown Kecamatan -->
                        <div class="col-12 col-sm-6 col-md-3">
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

                        <!-- Dropdown Tanggal Data -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label font-weight-bold small text-muted mb-1">Tanggal Data Snapshot</label>
                            <select name="tanggal_data" class="form-select">
                                @foreach($availableDates as $d)
                                    <option value="{{ $d }}" {{ $selectedDate == $d ? 'selected' : '' }}>
                                        {{ date('d M Y', strtotime($d)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Submit & Reset Buttons -->
                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary font-weight-bold w-100" style="border-radius: 8px;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/></svg>
                                Filter
                            </button>
                            @if($search || $kodekec || ($selectedDate && !empty($availableDates) && $selectedDate != $availableDates[0]))
                                <a href="{{ route('dashboard.pengolahan') }}" class="btn btn-outline-secondary" title="Reset Filter" style="border-radius: 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-rotate" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M19.95 11a8 8 0 1 0 -.5 4m.5 5v-5h-5"/></svg>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table Data Section -->
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-white border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h3 class="card-title font-weight-bold mb-0 text-dark">Data Hasil Pengolahan Per Petugas</h3>
                        <span class="text-muted small">Klik header kolom berpanah (▲/▼) untuk mengurutkan data.</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('dashboard.pengolahan.export', request()->query()) }}" class="btn btn-sm btn-success font-weight-bold" style="border-radius: 8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-spreadsheet me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M8 11h8" /><path d="M8 15h8" /><path d="M11 11v8" /></svg>
                            Export Excel
                        </a>
                        <span class="text-muted small ms-2">Tampilkan:</span>
                        <form method="GET" action="{{ route('dashboard.pengolahan') }}" class="d-inline">
                            <input type="hidden" name="search" value="{{ $search }}">
                            <input type="hidden" name="kodekec" value="{{ $kodekec }}">
                            <input type="hidden" name="tanggal_data" value="{{ $selectedDate }}">
                            <input type="hidden" name="sort" value="{{ $sortBy }}">
                            <input type="hidden" name="dir" value="{{ $sortDir }}">
                            <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15 data</option>
                                <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 data</option>
                                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 data</option>
                                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 data</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter table-striped card-table text-nowrap">
                        <thead>
                            <tr class="bg-light text-uppercase small font-weight-bold">
                                <th class="w-1">No</th>
                                
                                <!-- Sortable Column: Kode / Nama Kec -->
                                <th>
                                    @php $nextDir = ($sortBy == 'kode_kec' && $sortDir == 'asc') ? 'desc' : 'asc'; @endphp
                                    <a href="{{ route('dashboard.pengolahan', array_merge(request()->query(), ['sort' => 'kode_kec', 'dir' => $nextDir])) }}" class="text-dark text-decoration-none">
                                        Kecamatan 
                                        @if($sortBy == 'kode_kec')
                                            <span class="text-primary font-weight-bold">{{ $sortDir == 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span class="text-muted small">↕</span>
                                        @endif
                                    </a>
                                </th>

                                <!-- Sortable Column: Nama Pencacah -->
                                <th>
                                    @php $nextDir = ($sortBy == 'nama_pencacah' && $sortDir == 'asc') ? 'desc' : 'asc'; @endphp
                                    <a href="{{ route('dashboard.pengolahan', array_merge(request()->query(), ['sort' => 'nama_pencacah', 'dir' => $nextDir])) }}" class="text-dark text-decoration-none">
                                        Nama Petugas / Pencacah
                                        @if($sortBy == 'nama_pencacah')
                                            <span class="text-primary font-weight-bold">{{ $sortDir == 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span class="text-muted small">↕</span>
                                        @endif
                                    </a>
                                </th>

                                <th>Nama Pengawas</th>

                                <!-- Sortable Column: Muatan Murni (Kolom Pertama Metrics) -->
                                <th class="text-end bg-teal-lt text-teal font-weight-bold">
                                    @php $nextDir = ($sortBy == 'muatan_murni' && $sortDir == 'asc') ? 'desc' : 'asc'; @endphp
                                    <a href="{{ route('dashboard.pengolahan', array_merge(request()->query(), ['sort' => 'muatan_murni', 'dir' => $nextDir])) }}" class="text-teal text-decoration-none font-weight-bold">
                                        Muatan Murni ⭐
                                        @if($sortBy == 'muatan_murni')
                                            <span class="font-weight-bold">{{ $sortDir == 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span class="small opacity-50">↕</span>
                                        @endif
                                    </a>
                                </th>

                                <!-- Sortable Column: Beban Saat Ini -->
                                <th class="text-end">
                                    @php $nextDir = ($sortBy == 'beban_saat_ini' && $sortDir == 'asc') ? 'desc' : 'asc'; @endphp
                                    <a href="{{ route('dashboard.pengolahan', array_merge(request()->query(), ['sort' => 'beban_saat_ini', 'dir' => $nextDir])) }}" class="text-dark text-decoration-none">
                                        Beban Saat Ini
                                        @if($sortBy == 'beban_saat_ini')
                                            <span class="text-primary font-weight-bold">{{ $sortDir == 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span class="text-muted small">↕</span>
                                        @endif
                                    </a>
                                </th>

                                <!-- Sortable Column: Total Submit -->
                                <th class="text-end">
                                    @php $nextDir = ($sortBy == 'total_submit' && $sortDir == 'asc') ? 'desc' : 'asc'; @endphp
                                    <a href="{{ route('dashboard.pengolahan', array_merge(request()->query(), ['sort' => 'total_submit', 'dir' => $nextDir])) }}" class="text-dark text-decoration-none">
                                        Total Submit
                                        @if($sortBy == 'total_submit')
                                            <span class="text-primary font-weight-bold">{{ $sortDir == 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span class="text-muted small">↕</span>
                                        @endif
                                    </a>
                                </th>

                                <!-- Sortable Column: % Submit -->
                                <th class="text-end">
                                    @php $nextDir = ($sortBy == 'pct_submit' && $sortDir == 'asc') ? 'desc' : 'asc'; @endphp
                                    <a href="{{ route('dashboard.pengolahan', array_merge(request()->query(), ['sort' => 'pct_submit', 'dir' => $nextDir])) }}" class="text-dark text-decoration-none">
                                        % Progres
                                        @if($sortBy == 'pct_submit')
                                            <span class="text-primary font-weight-bold">{{ $sortDir == 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span class="text-muted small">↕</span>
                                        @endif
                                    </a>
                                </th>

                                <!-- Sortable Column: Jumlah Usaha -->
                                <th class="text-end">
                                    @php $nextDir = ($sortBy == 'jumlah_usaha_ditemukan' && $sortDir == 'asc') ? 'desc' : 'asc'; @endphp
                                    <a href="{{ route('dashboard.pengolahan', array_merge(request()->query(), ['sort' => 'jumlah_usaha_ditemukan', 'dir' => $nextDir])) }}" class="text-dark text-decoration-none">
                                        Usaha (Ditemukan+Baru)
                                        @if($sortBy == 'jumlah_usaha_ditemukan')
                                            <span class="text-primary font-weight-bold">{{ $sortDir == 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span class="text-muted small">↕</span>
                                        @endif
                                    </a>
                                </th>

                                <th class="text-end">Usaha Tdk Ditemukan</th>

                                <!-- Sortable Column: Jumlah Keluarga -->
                                <th class="text-end">
                                    @php $nextDir = ($sortBy == 'jumlah_keluarga_ditemukan' && $sortDir == 'asc') ? 'desc' : 'asc'; @endphp
                                    <a href="{{ route('dashboard.pengolahan', array_merge(request()->query(), ['sort' => 'jumlah_keluarga_ditemukan', 'dir' => $nextDir])) }}" class="text-dark text-decoration-none">
                                        Keluarga (Ditemukan+Baru)
                                        @if($sortBy == 'jumlah_keluarga_ditemukan')
                                            <span class="text-primary font-weight-bold">{{ $sortDir == 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span class="text-muted small">↕</span>
                                        @endif
                                    </a>
                                </th>

                                <th class="text-end">Keluarga Tdk Ditemukan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paginatedData as $index => $row)
                                <tr>
                                    <td class="text-muted small">{{ $paginatedData->firstItem() + $index }}</td>
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
                                    <td class="text-end font-weight-extrabold text-teal bg-teal-lt fs-3">
                                        {{ number_format($row->muatan_murni) }}
                                    </td>
                                    <td class="text-end font-weight-bold">{{ number_format($row->beban_saat_ini) }}</td>
                                    <td class="text-end font-weight-bold text-success">{{ number_format($row->total_submit) }}</td>
                                    <td class="text-end">
                                        <span class="badge {{ $row->pct_submit >= 70 ? 'bg-success-lt text-success' : ($row->pct_submit >= 50 ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger') }} font-weight-bold px-2 py-1">
                                            {{ number_format($row->pct_submit, 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-end font-weight-bold text-info">{{ number_format($row->jumlah_usaha_ditemukan) }}</td>
                                    <td class="text-end text-muted">{{ number_format($row->usaha_tidak_ditemukan) }}</td>
                                    <td class="text-end font-weight-bold text-warning">{{ number_format($row->jumlah_keluarga_ditemukan) }}</td>
                                    <td class="text-end text-muted">{{ number_format($row->keluarga_tidak_ditemukan) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center py-5 text-muted">
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

                <!-- Footer Pagination -->
                @if($paginatedData->hasPages() || $paginatedData->total() > 0)
                    <div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-between p-3 border-top gap-2">
                        <div class="text-muted small">
                            Menampilkan <strong>{{ $paginatedData->firstItem() ?? 0 }}</strong> s/d <strong>{{ $paginatedData->lastItem() ?? 0 }}</strong> dari total <strong>{{ number_format($paginatedData->total()) }}</strong> petugas/records.
                        </div>
                        <div class="pagination-wrapper">
                            {{ $paginatedData->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection
