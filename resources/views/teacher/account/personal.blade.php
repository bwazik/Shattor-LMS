@extends('layouts.teacher.master')

@section('page-css')

@endsection

@section('title', pageTitle('account.personal'))

@section('content')
    <div class="row">
        <div class="col-md-12">
            @include('teacher.account.navbar')
            <!-- Teacher cards -->
            <div class="row text-nowrap">
                <div class="col-md-6 mb-6">
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
                                    <h5 class="text-primary mb-0">{{ number_format(Auth::user()->balance, 2) }} {{ trans('main.currency') }}</h5>
                                    <p class="mb-0">{{ trans('account.balanceLeft') }}</p>
                                </div>
                                <p class="mb-0 text-truncate" style="text-wrap: auto;">{{ trans('account.accountBalanceDescription') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-icon mb-2">
                                <div class="avatar">
                                    <div class="avatar-initial rounded-3 bg-label-success">
                                        <i class="ri-graduation-cap-line ri-24px"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-info">
                                <h5 class="card-title mb-2">{{ trans('account.studentsCount') }}</h5>
                                <div class="d-flex align-items-baseline gap-1">
                                    <h5 class="text-success mb-0">{{ $data['teacher']->students()->count() }}</h5>
                                    <p class="mb-0">{{ trans('admin/students.student') }}</p>
                                </div>
                                <p class="mb-0 text-truncate" style="text-wrap: auto;">{{ trans('account.studentsCountDescription', ['remaining' => $data['remainingStudents']]) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-icon mb-2">
                                <div class="avatar">
                                    <div class="avatar-initial rounded-3 bg-label-info">
                                        <i class="ri-group-2-line ri-24px"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-info">
                                <h5 class="card-title mb-2">{{ trans('account.groupsCount') }}</h5>
                                <div class="d-flex align-items-baseline gap-1">
                                    <h5 class="text-info mb-0">{{ $data['teacher']->groups()->count() }}</h5>
                                    <p class="mb-0">{{ trans('admin/groups.group') }}</p>
                                </div>

                                <p class="mb-0 text-truncate" style="text-wrap: auto;">{{ trans('account.groupsCountDescription', ['remaining' => $data['remainingGroups']]) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-icon mb-2">
                                <div class="avatar">
                                    <div class="avatar-initial rounded-3 bg-label-warning">
                                        <i class="ri-star-smile-line ri-24px"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-info">
                                <h5 class="card-title mb-2">{{ trans('account.averageRating') }}</h5>
                                <div class="d-flex align-items-baseline gap-1">
                                    <h5 class="text-warning mb-0">{{ Auth::user()->average_rating }}</h5>
                                    <p class="mb-0">{{ trans('account.from') }} 10</p>
                                </div>
                                <p class="mb-0 text-truncate" style="text-wrap: auto;">{{ trans('account.averageRatingDescription', ['platform' => trans('layouts/sidebar.platformName')]) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ Teacher cards -->

            <!-- Teacher Data -->
            <div class="card mb-6">
                <x-account.profile-picture action="{{ route('teacher.account.updateProfilePic') }}" guard="teachers" qrcode="{{ $qrcode }}"/>
                <div class="card-body pt-0">
                    <form id="edit-form" action="{{ route('teacher.account.personal.update') }}" method="POST"
                        autocomplete="off">
                        @csrf
                        <div class="row mt-1 g-5">
                            <x-basic-input divClasses="col-12" type="text" name="username"
                                label="{{ trans('main.username') }}" placeholder="{{ $data['teacher']->username }}"
                                value="{{ $data['teacher']->username }}" required />
                            <x-basic-input context="modal" type="text" name="name_ar"
                                label="{{ trans('main.realName_ar') }}"
                                placeholder="{{ $data['teacher']->getTranslation('name', 'ar') }}"
                                value="{{ $data['teacher']->getTranslation('name', 'ar') }}" required />
                            <x-basic-input context="modal" type="text" name="name_en"
                                label="{{ trans('main.realName_en') }}"
                                placeholder="{{ $data['teacher']->getTranslation('name', 'en') }}"
                                value="{{ $data['teacher']->getTranslation('name', 'en') }}" required />
                            <x-basic-input context="modal" type="number" name="phone"
                                label="{{ trans('main.phone') }}" placeholder="{{ $data['teacher']->phone }}"
                                value="{{ $data['teacher']->phone }}" required />
                            <x-basic-input context="modal" type="email" name="email"
                                label="{{ trans('main.email') }}" placeholder="{{ $data['teacher']->email }}"
                                value="{{ $data['teacher']->email }}" />
                            <x-select-input context="modal" name="subject_id" label="{{ trans('main.subject') }}"
                                :options="$data['subjects']" required />
                            <x-select-input context="modal" name="grades" label="{{ trans('main.grades') }}"
                                :options="$data['grades']" multiple required />
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="btn btn-primary me-3">{{ trans('main.submit') }}</button>
                            <button type="reset" class="btn btn-outline-secondary">{{ trans('main.cancel') }}</button>
                        </div>
                    </form>
                </div>
                <!-- Settings -->
            </div>
            <!-- Teacher Data -->
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/js/pages-account-settings-account.js') }}"></script>

    <script>
        const allowedExtensions = ['jpg', 'jpeg', 'png'];
        handleProfilePicSubmit('#update-profile-form', 2, allowedExtensions);

        initializeSelect2('edit-form', 'subject_id', '{{ $data['teacher']->subject_id }}');
        const grades = '{{ $data['teacher']->grades }}'.split(',');
        initializeSelect2('edit-form', 'grades', grades);

        let fields = ['name_ar', 'name_en', 'username', 'email', 'phone', 'subject_id', 'grades'];
        handleFormSubmit('#edit-form', fields);
    </script>
@endsection
