@extends('tablar::page')

@section('content')
    <!-- Page header -->
    @php
        $category = request('category');
        $breadcrumb = ['Surveys'];
        if ($category) {
            $breadcrumb[] = ucfirst($category);
        }
    @endphp

    <x-page-header :title="ucfirst($category ?? 'Surveys')" pretitle="Menu" :breadcrumb="$breadcrumb">
        <div class="col-12 col-md-auto ms-auto d-print-none">
            <x-create-button />
        </div>
    </x-page-header>
    <!-- BEGIN PAGE BODY -->
    <div class="page-body">
        <div class="container-xl">
            @foreach ($teams as $team)
                <div class="row row-deck row-cards">
                    <div class="col-12">
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ $team->name }}</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row row-cards">
                                        @foreach ($team->surveys as $survey)
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <div>
                                                            <h3 class="card-title">{{ $survey->name }}</h3>
                                                            <p class="card-subtitle">
                                                                {{ $survey->description }}<br>
                                                                {{ $survey->start_periode->format('d M Y') }} –
                                                                {{ $survey->end_periode->format('d M Y') }}
                                                            </p>
                                                        </div>
                                                        <div class="card-actions">
                                                            <a href="{{ route('surveys.embed', $survey) }}"
                                                                class="btn btn-primary btn-2">
                                                                Lihat Dashboard
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
