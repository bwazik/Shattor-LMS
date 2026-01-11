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
        <div class="col-xl-12 col-lg-12 col-md-12">
            @yield('profile-content')
        </div>
    </div>
    <!-- Details -->
@endsection

@section('page-js')
    @yield('profile-js')
@endsection
