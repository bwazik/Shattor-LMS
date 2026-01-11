@extends('layouts.parent.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-profile.css') }}" />
@endsection

@section('title', pageTitle('admin/students.studentsProfile'))

@section('content')
    <!-- Header -->
    @include('parent.students.profile.header')
    <!-- Header -->

    <!-- Navbar -->
    @include('parent.students.profile.navbar')
    <!-- Navbar -->

    <!-- Details -->
    <div class="row">
        @include('parent.students.profile.details')
        <div class="col-xl-8 col-lg-7 col-md-7">
            @yield('profile-content')
        </div>
    </div>
    <!-- Details -->
@endsection

@section('page-js')
    @yield('profile-js')
@endsection
