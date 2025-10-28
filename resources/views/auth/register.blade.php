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
        <div class="form-floating form-floating-outline mb-5">
            <input type="text" class="form-control @error('username') is-invalid @enderror" id="username"
                name="username" placeholder="{{ trans('layouts/login.placeholders.username') }}" autofocus
                required />
            <label for="username">{{ trans('layouts/login.username') }}</label>
            @error('username')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <x-basic-input context="offcanvas" type="password" name="password" label="{{ trans('main.password') }}" required/>
        <div class="mb-5">
            <button class="btn btn-primary d-grid w-100" type="submit">{{ trans('layouts/login.sign_in') }}</button>
        </div>
    </form>
@endsection

@section('page-js')
    <script src="{{ asset('assets/js/pages-auth.js') }}"></script>
@endsection
