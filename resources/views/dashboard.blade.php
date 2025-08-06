@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <iframe src="{{ \App\Support\WilkerstatMetabase::dashboard(6) }}" frameborder="0" width="100%" height="850">
            </iframe>
        </div>
    </div>
@endsection
