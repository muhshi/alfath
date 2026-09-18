<!doctype html>
<html lang="{{ Config::get('app.locale') }}" {!! config('tablar.layout') == 'rtl' ? 'dir="rtl"' : '' !!}>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Custom Meta Tags --}}
    @yield('meta_tags')
    {{-- Title --}}
    <title>
        @yield('title_prefix', config('tablar.title_prefix', ''))
        @yield('title', config('tablar.title', 'Tablar'))
        @yield('title_postfix', config('tablar.title_postfix', ''))
    </title>

    <!-- CSS/JS files -->
    @if(config('tablar','vite'))
        @vite('resources/js/app.js')
    @endif

    {{-- Livewire Styles --}}
    @if(config('tablar.livewire'))
        @livewireStyles
    @endif

    {{-- Custom Stylesheets (post Tablar) --}}
    @yield('tablar_css')

    <style>
        .dropdown-menu.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 1050 !important;
        }
        @media (min-width: 992px) {
            .navbar-nav .nav-item.dropdown:hover > .dropdown-menu {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                margin-top: 0;
            }
        }
    </style>
</head>
@yield('body')
@include('tablar::extra.modal')

{{-- Livewire Script --}}
@if(config('tablar.livewire'))
    @livewireScripts
@endif

@yield('tablar_js')

<!-- Global Bulletproof Dropdown Handler for Navbar & Elements -->
<script>
    (function () {
        function setupDropdowns() {
            document.addEventListener('click', function (e) {
                var toggle = e.target.closest('[data-bs-toggle="dropdown"], .dropdown-toggle');
                if (toggle) {
                    var parent = toggle.closest('.dropdown, .nav-item.dropdown, .dropend, .btn-group');
                    if (parent) {
                        e.preventDefault();
                        e.stopPropagation();
                        var menu = parent.querySelector('.dropdown-menu');
                        if (menu) {
                            var isShown = menu.classList.contains('show');
                            // Tutup dropdown lain yang sedang terbuka
                            document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
                                if (m !== menu) {
                                    m.classList.remove('show');
                                    var p = m.closest('.dropdown, .nav-item.dropdown, .dropend, .btn-group');
                                    if (p) {
                                        var t = p.querySelector('[data-bs-toggle="dropdown"], .dropdown-toggle');
                                        if (t) {
                                            t.classList.remove('show');
                                            t.setAttribute('aria-expanded', 'false');
                                        }
                                    }
                                }
                            });

                            if (isShown) {
                                menu.classList.remove('show');
                                toggle.classList.remove('show');
                                toggle.setAttribute('aria-expanded', 'false');
                            } else {
                                menu.classList.add('show');
                                toggle.classList.add('show');
                                toggle.setAttribute('aria-expanded', 'true');
                            }
                        }
                    }
                } else if (!e.target.closest('.dropdown-menu')) {
                    // Tutup semua dropdown saat klik di luar
                    document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
                        m.classList.remove('show');
                        var p = m.closest('.dropdown, .nav-item.dropdown, .dropend, .btn-group');
                        if (p) {
                            var t = p.querySelector('[data-bs-toggle="dropdown"], .dropdown-toggle');
                            if (t) {
                                t.classList.remove('show');
                                t.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });
                }
            });

            // Tutup dropdown dengan tombol ESC
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
                        m.classList.remove('show');
                        var p = m.closest('.dropdown, .nav-item.dropdown, .dropend, .btn-group');
                        if (p) {
                            var t = p.querySelector('[data-bs-toggle="dropdown"], .dropdown-toggle');
                            if (t) {
                                t.classList.remove('show');
                                t.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupDropdowns);
        } else {
            setupDropdowns();
        }
    })();
</script>
</html>
