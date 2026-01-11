@extends('parent.students.profile.master')

@section('profile-content')
    <!-- Sessions -->
    <x-account.recent-sessions :sessions="$sessions" />
    <!-- Sessions -->

    <!-- Authorized Devices -->
    <x-account.authorized-devices :devices="$devices" />
    <!-- Authorized Devices -->
@endsection

@section('profile-js')

@endsection
