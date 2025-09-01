@extends('layouts.student.master')

@section('page-css')

@endsection

@section('title', pageTitle('account.personal'))

@section('content')
    <div class="row">
        <div class="col-md-12">
            @include('student.account.navbar')
            <!-- Student cards -->
            <div class="row text-nowrap">
                <div class="col-md-12 mb-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="card-icon mb-2">
                                <div class="avatar">
                                    <div class="avatar-initial rounded-3 bg-label-primary">
                                        <i class="ri-money-dollar-circle-line ri-24px"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-info">
                                <h5 class="card-title mb-2">{{ trans('account.accountBalance') }}</h5>
                                <div class="d-flex align-items-baseline gap-1">
                                    <h5 class="text-primary mb-0">{{ number_format(Auth::user()->balance, 2) }}
                                        {{ trans('main.currency') }}</h5>
                                    <p class="mb-0">{{ trans('account.balanceLeft') }}</p>
                                </div>
                                <p class="mb-0 text-truncate" style="text-wrap: auto;">
                                    {{ trans('account.accountBalanceDescription') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ Student cards -->

            <!-- Student Data -->
            <div class="card mb-6">
                <x-account.profile-picture action="{{ route('student.account.updateProfilePic') }}" guard="students"
                    qrcode="{{ $qrcode }}" />
                <div class="card-body pt-0">
                    <form id="edit-form" action="{{ route('student.account.personal.update') }}" method="POST"
                        autocomplete="off">
                        @csrf
                        <div class="row mt-1 g-5">
                            <x-basic-input context="modal" type="text" name="name_ar"
                                label="{{ trans('main.realName_ar') }}"
                                placeholder="{{ $data['student']->getTranslation('name', 'ar') }}"
                                value="{{ $data['student']->getTranslation('name', 'ar') }}" disabled />
                            <x-basic-input context="modal" type="text" name="name_en"
                                label="{{ trans('main.realName_en') }}"
                                placeholder="{{ $data['student']->getTranslation('name', 'en') }}"
                                value="{{ $data['student']->getTranslation('name', 'en') }}" />
                            <x-basic-input context="modal" type="text" name="username"
                                label="{{ trans('main.username') }}" placeholder="{{ $data['student']->username }}"
                                value="{{ $data['student']->username }}" required />
                            <x-basic-input context="modal" type="text" name="grade_id" label="{{ trans('main.grade') }}"
                                placeholder="{{ $data['student']->grade->name }}"
                                value="{{ $data['student']->grade->name }}" disabled />
                            <x-basic-input context="modal" type="number" name="phone" label="{{ trans('main.phone') }}"
                                placeholder="{{ $data['student']->phone }}" value="{{ $data['student']->phone }}"
                                disabled />
                            <x-basic-input context="modal" type="email" name="email" label="{{ trans('main.email') }}"
                                placeholder="{{ $data['student']->email }}" value="{{ $data['student']->email }}" />
                            <x-basic-input context="modal" type="text" name="birth_date" classes="flatpickr-date"
                                label="{{ trans('main.birth_date') }}" placeholder="YYYY-MM-DD"
                                value="{{ $data['student']->birth_date }}" />
                            <x-select-input context="modal" name="gender" label="{{ trans('main.gender') }}"
                                :options="[1 => trans('main.male'), 2 => trans('main.female')]" value="{{ $data['student']->gender }}" required />
                            <x-select-input context="modal" name="teachers" label="{{ trans('main.teachers') }}"
                                :options="$data['teachers']" multiple disabled />
                            <x-select-input context="modal" name="groups" label="{{ trans('main.groups') }}"
                                :options="$data['groups']" multiple disabled />
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="btn btn-primary me-3">{{ trans('main.submit') }}</button>
                            <button type="reset" class="btn btn-outline-secondary">{{ trans('main.cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Student Data -->

            <!-- Parent Data -->
            <div class="card mb-6">
                <h5 class="card-header">معلومات ولي الأمر</h5>
                <div class="card-body pt-0">
                    @if ($data['student']->parent)
                        <div class="row mt-1 g-5">
                            <x-basic-input context="modal" type="text" name="name_ar"
                                label="{{ trans('main.realName_ar') }}"
                                placeholder="{{ $data['student']->parent->getTranslation('name', 'ar') }}"
                                value="{{ $data['student']->parent->getTranslation('name', 'ar') }}" readonly />
                            <x-basic-input context="modal" type="text" name="name_en"
                                label="{{ trans('main.realName_en') }}"
                                placeholder="{{ $data['student']->parent->getTranslation('name', 'en') }}"
                                value="{{ $data['student']->parent->getTranslation('name', 'en') }}" readonly />
                            <x-basic-input context="modal" type="number" name="phone"
                                label="{{ trans('main.phone') }}" placeholder="{{ $data['student']->parent->phone }}"
                                value="{{ $data['student']->parent->phone }}" readonly />
                            <x-basic-input context="modal" type="email" name="email"
                                label="{{ trans('main.email') }}"
                                placeholder="{{ $data['student']->parent->email ?? 'N/A' }}"
                                value="{{ $data['student']->parent->email ?? 'N/A' }}" readonly />
                            <x-basic-input divClasses="col-12" type="text" name="gender"
                                label="{{ trans('main.gender') }}"
                                placeholder="{{ $data['student']->parent->gender == 1 ? trans('main.male') : trans('main.female') }}"
                                value="{{ $data['student']->parent->gender == 1 ? trans('main.male') : trans('main.female') }}"
                                readonly />
                        </div>
                    @else
                        {{ trans('toasts.noParentFoundForStudent') }}
                    @endif
                </div>
            </div>
            <!-- Parent Data -->
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/js/pages-account-settings-account.js') }}"></script>

    <script>
        const allowedExtensions = ['jpg', 'jpeg', 'png'];
        const gender = '{{ $data['student']->gender }}';
        const teachers = '{{ $data['student']->teachers }}'.split(',');
        const groups = '{{ $data['student']->groups }}'.split(',');
        let fields = ['name_en', 'username', 'email', 'birth_date', 'gender'];

        handleProfilePicSubmit('#update-profile-form', 2, allowedExtensions);
        initializeSelect2('edit-form', 'gender', gender);
        initializeSelect2('edit-form', 'teachers', teachers, true);
        initializeSelect2('edit-form', 'groups', groups, true);
        handleFormSubmit('#edit-form', fields);
    </script>
@endsection
