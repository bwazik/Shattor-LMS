@extends('layouts.parent.master')

@section('page-css')

@endsection

@section('title', pageTitle('layouts/sidebar.dashboard'))

@section('content')
    <div class="text-center">
        <h1>{{ trans('main.soon') }} 🚀</h1>
        <img src="{{ asset('assets/img/illustrations/misc-coming-soon-illustration.png') }}" alt="misc-coming-soon"
            class="img-fluid z-1" width="190" />
    </div>
@endsection

@section('page-js')
@endsection
