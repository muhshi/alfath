@props(['title' => 'Dashboard', 'pretitle' => 'Menu', 'breadcrumb' => []])

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                {{-- <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        @foreach ($breadcrumb as $item)
                        <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}" {{ $loop->last ?
                            'aria-current=page' : '' }}>
                            @if ($loop->last)
                            {{ $item }}
                            @else
                            <a href="#">{{ $item }}</a>
                            @endif
                        </li>
                        @endforeach
                    </ol>
                </nav> --}}
                <div class="page-pretitle">
                    {{ $pretitle }}
                </div>
                <h2 class="page-title">
                    {{ $title }}
                </h2>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
