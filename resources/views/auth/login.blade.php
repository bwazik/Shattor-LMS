@extends('layouts.auth.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />
@endsection

@section('title', pageTitle(trans('layouts/login.title')))

@section('content')
    <h4 class="mb-2">{{ trans('layouts/login.welcome') }}</h4>
    @if($guard === 'parent')
        <p class="mb-5">سجل دخول للمنصة عشان تتابع حضور وغياب ودرجات ومستوي ابنك! </p>
    @else
        <p class="mb-5">{{ trans('layouts/login.sign_in_prompt') }}</p>
    @endif
    <div class="text-center mb-4">
        @switch($guard)
            @case('teacher')
                <span class="badge rounded-pill bg-label-primary text-capitalized fs-6">{{ trans('layouts/login.login_as_role', ['role' => trans('admin/teachers.teacher')]) }}</span>
            @break
            @case('assistant')
                <span class="badge rounded-pill bg-label-success text-capitalized fs-6">{{ trans('layouts/login.login_as_role', ['role' => trans('admin/assistants.assistant')]) }}</span>
            @break
            @case('student')
                <span class="badge rounded-pill bg-label-info text-capitalized fs-6">{{ trans('layouts/login.login_as_role', ['role' => trans('admin/students.student')]) }}</span>
            @break
            @case('parent')
                <span class="badge rounded-pill bg-label-warning text-capitalized fs-6">{{ trans('layouts/login.login_as_role', ['role' => trans('admin/parents.parent')]) }}</span>
            @break
            @case('developer')
                <span class="badge rounded-pill bg-label-secondary text-capitalized fs-6">{{ trans('layouts/login.login_as_role', ['role' => trans('main.founder')]) }}</span>
            @break
            @default
                <span class="badge rounded-pill bg-label-secondary text-capitalized fs-6">-</span>
        @endswitch
    </div>
    <form id="loginForm" class="mb-5" method="POST" action="{{ route('login', $guard) }}">
        @csrf
        @if ($guard === 'parent')
            <div class="form-floating form-floating-outline mb-5">
                <input type="text" class="form-control @error('student_phone') is-invalid @enderror" id="student_phone"
                    name="student_phone" placeholder="رقم هاتف الطالب" autofocus required
                    value="{{ old('student_phone') }}" />
                <label for="student_phone">رقم هاتف الطالب</label>
                @error('student_phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-floating form-floating-outline mb-5">
                <input type="text" class="form-control @error('parent_phone') is-invalid @enderror" id="parent_phone"
                    name="parent_phone" placeholder="رقم هاتف ولي الأمر" required />
                <label for="parent_phone">رقم هاتف ولي الأمر</label>
                @error('parent_phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        @else
            <div class="form-floating form-floating-outline mb-5">
                <input type="text" class="form-control @error('username') is-invalid @enderror" id="username"
                    name="username" placeholder="{{ trans('layouts/login.placeholders.username') }}" autofocus
                    required />
                <label for="username">{{ trans('layouts/login.username') }}</label>
                @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-5">
                <div class="form-password-toggle">
                    <div class="input-group input-group-merge">
                        <div class="form-floating form-floating-outline">
                            <input type="password" id="password" class="form-control @error('password') is-invalid @enderror"
                                name="password"
                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                aria-describedby="password" required />
                            <label for="password">{{ trans('layouts/login.password') }}</label>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
                    </div>
                </div>
            </div>
        @endif
        <div class="mb-5 d-flex justify-content-between mt-5">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="remember" name="remember" />
                <label class="form-check-label" for="remember">{{ trans('layouts/login.remember_me') }}</label>
            </div>
        </div>
        <div class="mb-5">
            <button class="btn btn-primary d-grid w-100" type="submit">{{ trans('layouts/login.sign_in') }}</button>
        </div>
    </form>
@endsection

@section('page-js')
    <script src="{{ asset('assets/js/pages-auth.js') }}"></script>
@endsection
