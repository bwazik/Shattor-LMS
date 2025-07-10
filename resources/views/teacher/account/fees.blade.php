@extends('layouts.teacher.master')

@section('page-css')

@endsection

@section('title', pageTitle('account.fees'))

@section('content')
    <div class="row">
        <div class="col-md-12">
            @include('teacher.account.navbar')
            <div class="card mb-6">
                <h5 class="card-header">Manage Monthly Grade Fees (Academic Year {{ $year }}-{{ $year + 1 }})
                </h5>
                <div class="card-body pt-1">
                    <div class="nav-align-left nav-tabs-shadow">
                        <ul class="nav nav-tabs" role="tablist">
                            @foreach ($months as $index => $month)
                                <li class="nav-item" role="presentation">
                                    <button type="button" class="nav-link waves-effect {{ $index === 0 ? 'active' : '' }}"
                                        role="tab" data-bs-toggle="tab" data-bs-target="#month-{{ $month['key'] }}"
                                        aria-controls="month-{{ $month['key'] }}"
                                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                        {{ app()->getLocale() === 'ar' ? $month['arabic_name'] : $month['name'] }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            @foreach ($months as $index => $month)
                                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                                    id="month-{{ $month['key'] }}" role="tabpanel">
                                    <form id="fees-form-{{ $month['key'] }}" class="fees-form"
                                        data-month="{{ $month['key'] }}"
                                        action="{{ route('teacher.account.fees.update') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $month['key'] }}">
                                        @foreach ($grades as $gradeIndex => $grade)
                                            <div class="mb-5">
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text">{{ $grade->name }}</span>
                                                    <div class="form-floating form-floating-outline">
                                                        <input type="number"
                                                            id="grade-{{ $grade->id }}-{{ $month['key'] }}"
                                                            class="form-control" name="fees[{{ $gradeIndex }}][amount]"
                                                            step="0.01" min="0"
                                                            value="{{ isset($gradeFees[$month['key']]) ? $gradeFees[$month['key']]->firstWhere('grade_id', $grade->id)->amount ?? 0.0 : 0.0 }}"
                                                            placeholder="0.00" aria-label="0.00" required />
                                                        <label
                                                            for="grade-{{ $grade->id }}-{{ $month['key'] }}">{{ trans('main.amount') }}</label>
                                                    </div>
                                                    <input type="hidden" name="fees[{{ $gradeIndex }}][grade_id]"
                                                        value="{{ $grade->id }}">
                                                    <span class="input-group-text">{{ trans('main.currency') }}</span>
                                                </div>
                                                <span class="invalid-feedback"
                                                    id="grade-{{ $grade->id }}-{{ $month['key'] }}_error"
                                                    role="alert"></span>
                                            </div>
                                        @endforeach
                                        <div class="text-end">
                                            <button type="submit"
                                                class="btn btn-primary waves-effect waves-light">{{ trans('main.submit') }}</button>
                                        </div>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script>
        function handleFormSubmit(formId, fields) {
            const $form = $(formId);
            const $submitButton = $form.find('button[type="submit"]');
            const originalButtonContent = $submitButton.html();

            $form.on('submit', function(e) {
                e.preventDefault();

                $submitButton.find('.waves-ripple').remove();
                $submitButton.prop('disabled', true);
                $submitButton.html(
                    `<i class="ri-loader-4-line ri-spin ri-20px me-1"></i> ${window.translations.processing}...`
                );

                // Clear previous error states
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('').addClass('d-none').removeClass('d-block');

                const formData = new FormData(this);

                $.ajax({
                    url: $form.attr('action'),
                    type: $form.attr('method'),
                    dataType: "json",
                    processData: false,
                    contentType: false,
                    data: formData,
                    success: function(response) {
                        if (response.status === 'success') {
                            toastr.success(response.message);
                            resetButtonState($submitButton, originalButtonContent);
                        } else {
                            toastr.error(response.message || window.translations.error);
                            resetButtonState($submitButton, originalButtonContent);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 429) {
                            toastr.error(window.translations.tooManyRequests);
                        } else if (xhr.responseJSON) {
                            if (xhr.responseJSON.errors) {
                                $.each(xhr.responseJSON.errors, function(key, val) {
                                    const fieldName = key.replace(/\.\d+\./g, '.').replace(
                                        /\.\d+$/, '');
                                    const inputElement = $form.find(
                                        `[name="${fieldName.replace('.', '[').replace(/\.(\w+)$/, '][$1]')}"]`
                                        );
                                    const errorElement = inputElement.closest('.input-group')
                                        .next('.invalid-feedback');
                                    if (inputElement.length && errorElement.length) {
                                        inputElement.addClass('is-invalid');
                                        errorElement.text(val[0]).addClass('d-block')
                                            .removeClass('d-none');
                                    }
                                });
                            } else if (xhr.responseJSON.message) {
                                toastr.error(xhr.responseJSON.message);
                            } else {
                                toastr.error(window.translations.error);
                            }
                        } else {
                            toastr.error(window.translations.error);
                        }
                        resetButtonState($submitButton, originalButtonContent);
                    },
                    complete: function() {
                        resetButtonState($submitButton, originalButtonContent);
                    }
                });
            });
        }

        @foreach ($months as $month)
            handleFormSubmit('#fees-form-{{ $month['key'] }}', ['month', 'fees']);
        @endforeach
    </script>
@endsection
