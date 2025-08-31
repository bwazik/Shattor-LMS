@extends('layouts.parent.master')

@section('page-css')

@endsection

@section('title', pageTitle('account.personal'))

@section('content')
    <div class="row">
        <div class="col-md-12">
            @include('parent.account.navbar')
            <!-- Parent Data -->
            <div class="card mb-6">
                <x-account.profile-picture qrcode="{{ $qrcode }}" />
                <div class="card-body pt-0">
                    <form id="edit-form" action="{{ route('parent.account.personal.update') }}" method="POST"
                        autocomplete="off">
                        @csrf
                        <div class="row mt-1 g-5">
                            <x-basic-input context="modal" type="text" name="name_ar"
                                label="{{ trans('main.realName_ar') }}"
                                placeholder="{{ $data['parent']->getTranslation('name', 'ar') }}"
                                value="{{ $data['parent']->getTranslation('name', 'ar') }}" />
                            <x-basic-input context="modal" type="text" name="name_en"
                                label="{{ trans('main.realName_en') }}"
                                placeholder="{{ $data['parent']->getTranslation('name', 'en') }}"
                                value="{{ $data['parent']->getTranslation('name', 'en') }}" />
                            <x-basic-input context="modal" type="text" name="username"
                                label="{{ trans('main.username') }}" placeholder="{{ $data['parent']->username }}"
                                value="{{ $data['parent']->username }}" required />
                            <x-basic-input context="modal" type="number" name="phone" label="{{ trans('main.phone') }}"
                                placeholder="{{ $data['parent']->phone }}" value="{{ $data['parent']->phone }}" />
                            <x-basic-input context="modal" type="email" name="email" label="{{ trans('main.email') }}"
                                placeholder="{{ $data['parent']->email }}" value="{{ $data['parent']->email }}" />
                            <x-select-input context="modal" name="gender" label="{{ trans('main.gender') }}"
                                :options="[1 => trans('main.male'), 2 => trans('main.female')]" value="{{ $data['parent']->gender }}" required />
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="btn btn-primary me-3">{{ trans('main.submit') }}</button>
                            <button type="reset" class="btn btn-outline-secondary">{{ trans('main.cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Parent Data -->

            <!-- Students Data -->
            <div class="card mb-6">
                <h5 class="card-header">معلومات الأبناء</h5>
                <div class="card-body pt-0">
                    @if (!empty($data['parent']->students) && $data['parent']->students->count() > 0)
                        @foreach ($data['parent']->students as $index => $student)
                            <div class="row mt-1 g-5">
                                <x-basic-input context="modal" type="text" name="name_ar"
                                    label="{{ trans('main.realName_ar') }}"
                                    placeholder="{{ $student->getTranslation('name', 'ar') }}"
                                    value="{{ $student->getTranslation('name', 'ar') }}" readonly />
                                <x-basic-input context="modal" type="text" name="name_en"
                                    label="{{ trans('main.realName_en') }}"
                                    placeholder="{{ $student->getTranslation('name', 'en') }}"
                                    value="{{ $student->getTranslation('name', 'en') }}" readonly />
                                <x-basic-input context="modal" type="text" name="username"
                                    label="{{ trans('main.username') }}" placeholder="{{ $student->username }}"
                                    value="{{ $student->username }}" readonly />
                                <x-basic-input context="modal" type="text" name="grade_id"
                                    label="{{ trans('main.grade') }}" placeholder="{{ $student->grade->name }}"
                                    value="{{ $student->grade->name }}" readonly />
                                <x-basic-input context="modal" type="number" name="phone"
                                    label="{{ trans('main.phone') }}" placeholder="{{ $student->phone }}"
                                    value="{{ $student->phone }}" readonly />
                                <x-basic-input context="modal" type="email" name="email"
                                    label="{{ trans('main.email') }}" placeholder="{{ $student->email ?? 'N/A' }}"
                                    value="{{ $student->email ?? 'N/A' }}" readonly />
                                <x-basic-input context="modal" type="text" name="birth_date" classes="flatpickr-date"
                                    label="{{ trans('main.birth_date') }}" placeholder="YYYY-MM-DD"
                                    value="{{ $student->birth_date }}" readonly />
                                <x-basic-input context="modal" type="text" name="gender"
                                    label="{{ trans('main.gender') }}"
                                    placeholder="{{ $student->gender == 1 ? trans('main.male') : trans('main.female') }}"
                                    value="{{ $student->gender == 1 ? trans('main.male') : trans('main.female') }}"
                                    readonly />
                            </div>
                            @if (!$loop->last)
                                <hr class="my-5">
                            @endif
                        @endforeach
                    @else
                        {{ trans('toasts.noStudentsFound') }}
                    @endif
                </div>
            </div>
            <!-- Students Data -->
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/js/pages-account-settings-account.js') }}"></script>

    <script>
        const gender = '{{ $data['parent']->gender }}';
        let fields = ['name_ar', 'name_en', 'username', 'email', 'phone', 'gender'];

        initializeSelect2('edit-form', 'gender', gender);
        handleFormSubmit('#edit-form', fields);
    </script>
@endsection
