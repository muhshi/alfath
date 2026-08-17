@extends('tablar::auth.layout')
@section('title', 'Login - ALFATH')
@section('content')
    <div class="container container-tight py-4">
        <div class="text-center mb-3 mt-4">
            <a href="{{ url('/') }}" class="navbar-brand navbar-brand-autodark">
                <img src="{{ file_exists(public_path('assets/logo_bps.png')) ? asset('assets/logo_bps.png') : asset('assets/logo.svg') }}" height="48" alt="Logo">
            </a>
            <h3 class="mt-2 text-primary font-weight-bold">ALFATH</h3>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                <div class="d-flex">
                    <div>{{ session('error') }}</div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif

        <div class="card card-md shadow-sm">
            <div class="card-body">
                <h2 class="h2 text-center mb-4">Masuk ke Akun Anda</h2>
                <form action="{{ route('login') }}" method="post" autocomplete="off" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Email address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                               placeholder="nama@bps.go.id" value="{{ old('email') }}"
                               autocomplete="off" required>
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label">
                            Password
                            @if(Route::has('password.request'))
                                <span class="form-label-description">
                                    <a href="{{ route('password.request') }}">Lupa password?</a>
                                </span>
                            @endif
                        </label>
                        <div class="input-group input-group-flat">
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Password Anda"
                                   autocomplete="off" required>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-check">
                            <input type="checkbox" name="remember" class="form-check-input"/>
                            <span class="form-check-label">Ingat saya di perangkat ini</span>
                        </label>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100">Masuk</button>
                    </div>
                </form>
            </div>
            
            <div class="hr-text">atau</div>
            
            <div class="card-body pt-0">
                <a href="{{ route('sipetra.login') }}" class="btn btn-outline-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm">
                    <img src="{{ file_exists(public_path('assets/logo_bps.png')) ? asset('assets/logo_bps.png') : asset('assets/logo.svg') }}" 
                         alt="Logo BPS" 
                         style="width: 20px; height: 20px; object-fit: contain;">
                    <span class="fw-bold">Masuk dengan SIPETRA SSO</span>
                </a>
                <p class="text-center text-muted small mt-2 mb-0">
                    Login terpusat akun BPS Kabupaten Demak
                </p>
            </div>
        </div>

        @if(Route::has('register'))
            <div class="text-center text-muted mt-3">
                Belum punya akun? <a href="{{ route('register') }}" tabindex="-1">Daftar sekarang</a>
            </div>
        @endif
    </div>
@endsection
