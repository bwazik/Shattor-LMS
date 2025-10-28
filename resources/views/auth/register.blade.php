@extends('layouts.auth.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />
@endsection

@section('title', pageTitle(trans('layouts/register.register_title')))

@section('content')
    <h4 class="mb-2">{{ trans('layouts/login.welcome') }}</h4>
    <p class="mb-5">{{ trans('layouts/login.sign_up_prompt') }}</p>
    <form id="registerForm" class="mb-5" method="POST" action="{{ route('register') }}">
        @csrf
        <x-basic-input context="modal" type="text" name="username" label="{{ trans('main.username') }}" placeholder="{{ trans('admin/students.placeholders.username') }}" required/>
        <x-basic-input context="offcanvas" type="password" name="password" label="{{ trans('main.password') }}" required/>
        <div class="mb-5">
            <button class="btn btn-primary d-grid w-100" type="submit">{{ trans('layouts/login.sign_in') }}</button>
        </div>
    </form>
@endsection

@section('page-js')
    <script src="{{ asset('assets/js/pages-auth.js') }}"></script>
@endsection
