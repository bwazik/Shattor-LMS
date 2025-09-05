@extends('layouts.teacher.master')

@section('page-css')

@endsection

@section('title', pageTitle('account.security'))

@section('content')
    <div class="row">
        <div class="col-md-12">
            @include('teacher.account.navbar')
            <!-- Change Password -->
            <x-account.change-password action="{{ route('teacher.account.password.update') }}" />
            <!-- Change Password -->

            <!-- Change Security Code -->
            <div class="card mb-6">
                <h5 class="card-header">{{ trans('admin/fees.securityCode') }}</h5>
                <div class="card-body pt-1">
                    <form id="change-security-code-form" action="{{ route('teacher.account.security-code.update') }}" method="POST" autocomplete="off">
                        @csrf
                        <div class="row">
                            <x-basic-input divClasses="mb-5 col-md-6" type="password" name="currentSecurityCode"
                                label="{{ trans('account.currentSecurityCode') }}" required />
                        </div>
                        <div class="row g-5 mb-6">
                            <x-basic-input context="modal" type="password" name="newSecurityCode"
                                label="{{ trans('account.newSecurityCode') }}" required />
                            <x-basic-input context="modal" type="password" name="confirmNewSecurityCode"
                                label="{{ trans('account.confirmNewSecurityCode') }}" required />
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="btn btn-primary me-3">{{ trans('main.submit') }}</button>
                            <button type="reset" class="btn btn-outline-secondary">{{ trans('main.cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Change Security Code -->

            <!-- Zoom Account -->
            <div class="card mb-6">
                <h5 class="card-header mb-1">{{ trans('account.linkZoomAccount') }}</h5>
                <div class="row row-gap-1">
                    <div class="col-xl-5 col-md-7">
                        <div class="card-body">
                            <x-alert type="info" :dismissible=false icon="error-warning" :message="trans('account.zoomAccountAlert')" />
                            <form id="zoom-account-form" action="{{ route('teacher.account.zoom.update') }}" method="POST"
                                autocomplete="off">
                                @csrf
                                <div class="row gy-5">
                                    <x-basic-input context="offcanvas" type="text" name="accountId"
                                        label="{{ trans('account.accountId') }}"
                                        placeholder="{{ trans('account.placeholders.accountId') }}"
                                        value="{{ $zoomAccount['accountId'] ?? '' }}" required />
                                    <x-basic-input context="offcanvas" type="text" name="clientId"
                                        label="{{ trans('account.clientId') }}"
                                        placeholder="{{ trans('account.placeholders.clientId') }}"
                                        value="{{ $zoomAccount['clientId'] ?? '' }}" required />
                                    <x-basic-input context="offcanvas" type="password" name="clientSecret"
                                        label="{{ trans('account.clientSecret') }}"
                                        value="{{ $zoomAccount['clientSecret'] ?? '' }}" required />
                                    <div class="col-12">
                                        <button type="submit"
                                            class="btn btn-primary me-2 w-100">{{ trans('main.submit') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-xl-7 col-md-5">
                        <div class="text-center">
                            <img src="{{ asset('assets/img/illustrations/account-settings-security-illustration.png') }}"
                                class="img-fluid" alt="Zoom Account Image" width="143" />
                        </div>
                    </div>
                </div>
            </div>
            <!-- Zoom Account -->

            <!-- Sessions -->
            <x-account.recent-sessions :sessions="$sessions" />
            <!-- Sessions -->

            <!-- Authorized Devices -->
            <x-account.authorized-devices :devices="$devices" />
            <!-- Authorized Devices -->
        </div>
    </div>
@endsection

@section('page-js')
    <script>
        let fields = ['currentPassword', 'newPassword', 'confirmNewPassword'];
        let pinFields = ['currentSecurityCode', 'newSecurityCode', 'confirmNewSecurityCode'];
        let zoomFields = ['accountId', 'clientId', 'clientSecret'];
        handleFormSubmit('#change-password-form', fields);
        handleFormSubmit('#change-security-code-form', pinFields);
        handleFormSubmit('#zoom-account-form', zoomFields);
    </script>
@endsection
