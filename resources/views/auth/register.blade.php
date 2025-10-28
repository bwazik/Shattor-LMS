@extends('layouts.auth.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />
@endsection

@section('title', pageTitle(trans('layouts/login.register_title')))

@section('content')
    <h4 class="mb-2">{{ trans('layouts/login.welcome') }}</h4>
    <p class="mb-5">{{ trans('layouts/login.sign_up_prompt') }}</p>
    <form id="register-form" class="mb-5" method="POST" action="{{ route('register') }}">
        @csrf
        <x-basic-input context="modal" type="text" name="name_ar" label="{{ trans('main.realName_ar') }}"
            placeholder="{{ trans('admin/students.placeholders.name_ar') }}" required />
        <x-basic-input context="modal" type="number" name="phone" label="{{ trans('main.phone') }}"
            placeholder="{{ trans('admin/students.placeholders.phone') }}" required />
        <x-basic-input context="offcanvas" type="text" name="username" label="{{ trans('main.username') }}"
            placeholder="{{ trans('admin/students.placeholders.username') }}" required />
        <x-basic-input context="offcanvas" type="password" name="password" label="{{ trans('main.password') }}" required />
        <x-select-input context="offcanvas" name="gender" label="{{ trans('main.gender') }}" :options="[1 => trans('main.male'), 2 => trans('main.female')]" required />
        <x-select-input context="offcanvas" name="grade_id" label="{{ trans('main.grade') }}" :options="$grades" required />
        <x-select-input context="offcanvas" name="groups" label="{{ trans('main.groups') }}" multiple required />
        <x-select-input divClasses="col-12" name="specialization" label="{{ trans('main.specialization') }}"
            :options="[1 => trans('main.scientific'), 2 => trans('main.literary')]" required />
        <div class="mb-5">
            <button class="btn btn-primary d-grid w-100" type="submit">{{ trans('layouts/login.sign_in') }}</button>
        </div>
    </form>
@endsection

@section('page-js')
    <script src="{{ asset('assets/js/pages-auth.js') }}"></script>


    <script>
        let fields = ['name_ar', 'name_en', 'username', 'password', 'phone', 'email', 'birth_date', 'gender', 'grade_id',
            'groups', 'specialization'
        ];
        handleFormSubmit('#add-form', fields, '#add-modal', 'modal', '#datatable');
        handleFormSubmit('#edit-form', fields, '#edit-modal', 'modal', '#datatable');
        fetchMultipleDataByAjax('#register-form #grade_id', "{{ route('teacher.fetch.grade.groups', '__ID__') }}",
            '#register-form #groups', 'grade_id', 'GET')
        generateRandomUsername('s');
    </script>
@endsection
