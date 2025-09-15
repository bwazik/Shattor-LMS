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
                                            <div class="fee-entries" data-grade-id="{{ $grade->id }}">
                                                @php
                                                    $gradeFeesForMonth = isset($gradeFees[$month['key']][$grade->id])
                                                        ? $gradeFees[$month['key']][$grade->id]
                                                        : collect([]);
                                                    $feeIndex = 0;
                                                @endphp
                                                @foreach ($gradeFeesForMonth as $fee)
                                                    <div class="fee-entry mb-3">
                                                        <input type="hidden"
                                                            name="fees[{{ $gradeIndex . '-' . $feeIndex }}][grade_id]"
                                                            value="{{ $grade->id }}">
                                                        <div class="row">
                                                            <div
                                                                class="amount-col {{ isset($fee->applies_to_all_specializations) && $fee->applies_to_all_specializations ? 'col-12' : 'col-6' }}">
                                                                <div class="input-group input-group-merge">
                                                                    <span
                                                                        class="input-group-text">{{ $grade->name }}</span>
                                                                    <div class="form-floating form-floating-outline">
                                                                        <input type="number"
                                                                            id="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-amount"
                                                                            class="form-control"
                                                                            name="fees[{{ $gradeIndex . '-' . $feeIndex }}][amount]"
                                                                            step="0.01" min="0"
                                                                            value="{{ $fee->amount ?? 0.0 }}"
                                                                            placeholder="0.00" aria-label="0.00" required />
                                                                        <label
                                                                            for="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-amount">
                                                                            {{ trans('main.amount') }}
                                                                        </label>
                                                                    </div>
                                                                    <span
                                                                        class="input-group-text">{{ trans('main.currency') }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-6 specialization-group"
                                                                style="{{ isset($fee->applies_to_all_specializations) && $fee->applies_to_all_specializations ? 'display: none;' : '' }}">
                                                                <div class="form-floating form-floating-outline">
                                                                    <div class="select2-primary">
                                                                        <select
                                                                            id="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-specialization"
                                                                            class="form-select select2"
                                                                            name="fees[{{ $gradeIndex . '-' . $feeIndex }}][specialization]">
                                                                            <option value="">
                                                                                {{ trans('main.select_specialization') }}
                                                                            </option>
                                                                            <option value="1"
                                                                                {{ $fee->specialization === 1 ? 'selected' : '' }}>
                                                                                {{ trans('main.scientific') }}
                                                                            </option>
                                                                            <option value="2"
                                                                                {{ $fee->specialization === 2 ? 'selected' : '' }}>
                                                                                {{ trans('main.literary') }}
                                                                            </option>
                                                                        </select>
                                                                    </div>
                                                                    <label
                                                                        for="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-specialization">
                                                                        {{ trans('main.specialization') }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-check mt-2">
                                                            <input type="hidden"
                                                                name="fees[{{ $gradeIndex . '-' . $feeIndex }}][applies_to_all_specializations]"
                                                                value="0">
                                                            <input type="checkbox" class="form-check-input applies-to-all"
                                                                id="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-all"
                                                                name="fees[{{ $gradeIndex . '-' . $feeIndex }}][applies_to_all_specializations]"
                                                                value="1"
                                                                {{ isset($fee->applies_to_all_specializations) && $fee->applies_to_all_specializations ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                for="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-all">
                                                                {{ trans('main.applies_to_all_specializations') }}
                                                            </label>
                                                        </div>
                                                        <span class="invalid-feedback"
                                                            id="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-error"
                                                            role="alert"></span>
                                                    </div>
                                                    @php $feeIndex++; @endphp
                                                @endforeach
                                                <!-- Default empty fee entry if no fees exist -->
                                                @if ($gradeFeesForMonth->isEmpty())
                                                    <div class="fee-entry mb-3">
                                                        <input type="hidden"
                                                            name="fees[{{ $gradeIndex . '-' . $feeIndex }}][grade_id]"
                                                            value="{{ $grade->id }}">
                                                        <div class="row">
                                                            <div class="col-6 amount-col">
                                                                <div class="input-group input-group-merge">
                                                                    <span
                                                                        class="input-group-text">{{ $grade->name }}</span>
                                                                    <div class="form-floating form-floating-outline">
                                                                        <input type="number"
                                                                            id="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-amount"
                                                                            class="form-control"
                                                                            name="fees[{{ $gradeIndex . '-' . $feeIndex }}][amount]"
                                                                            step="0.01" min="0" value="0.0"
                                                                            placeholder="0.00" aria-label="0.00" required />
                                                                        <label
                                                                            for="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-amount">
                                                                            {{ trans('main.amount') }}
                                                                        </label>
                                                                    </div>
                                                                    <span
                                                                        class="input-group-text">{{ trans('main.currency') }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-6 specialization-group">
                                                                <div class="form-floating form-floating-outline">
                                                                    <div class="select2-primary">
                                                                        <select
                                                                            id="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-specialization"
                                                                            class="form-select select2"
                                                                            name="fees[{{ $gradeIndex . '-' . $feeIndex }}][specialization]">
                                                                            <option value="">
                                                                                {{ trans('main.select_specialization') }}
                                                                            </option>
                                                                            <option value="1">
                                                                                {{ trans('main.scientific') }}
                                                                            </option>
                                                                            <option value="2">
                                                                                {{ trans('main.literary') }}
                                                                            </option>
                                                                        </select>
                                                                    </div>
                                                                    <label
                                                                        for="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-specialization">
                                                                        {{ trans('main.specialization') }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-check mt-2">
                                                            <input type="hidden"
                                                                name="fees[{{ $gradeIndex . '-' . $feeIndex }}][applies_to_all_specializations]"
                                                                value="0">
                                                            <input type="checkbox" class="form-check-input applies-to-all"
                                                                id="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-all"
                                                                name="fees[{{ $gradeIndex . '-' . $feeIndex }}][applies_to_all_specializations]"
                                                                value="1">
                                                            <label class="form-check-label"
                                                                for="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-all">
                                                                {{ trans('main.applies_to_all_specializations') }}
                                                            </label>
                                                        </div>
                                                        <span class="invalid-feedback"
                                                            id="grade-{{ $grade->id }}-{{ $month['key'] }}-{{ $feeIndex }}-error"
                                                            role="alert"></span>
                                                    </div>
                                                @endif
                                                <button type="button" class="btn btn-outline-primary add-fee-btn mb-3"
                                                    data-grade-id="{{ $grade->id }}" data-month="{{ $month['key'] }}"
                                                    data-fee-index="{{ $feeIndex }}">
                                                    {{ trans('main.addItem', ['item' => trans('admin/fees.fee')]) }}
                                                </button>
                                            </div>
                                        @endforeach
                                        <div class="text-end mt-3">
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


        $(document).ready(function() {
            $('.select2').each(function() {
                const elementId = $(this).attr('id');
                const appliesToAll = $(this).closest('.fee-entry').find('.applies-to-all').is(':checked');
                const modalId = $(this).closest('.tab-pane').attr('id');
                initializeSelect2(modalId, elementId, $(this).val(), appliesToAll);
                toggleAmountWidth($(this).closest('.fee-entry'), appliesToAll);
            });
            updateAddButtonVisibility();
        });

        function toggleAmountWidth($feeEntry, isChecked) {
            const $amountCol = $feeEntry.find('.amount-col');
            if (isChecked) {
                $amountCol.removeClass('col-6').addClass('col-12');
            } else {
                $amountCol.removeClass('col-12').addClass('col-6');
            }
        }

        // Update visibility of add fee buttons
        function updateAddButtonVisibility() {
            $('.fee-entries').each(function() {
                const $feeEntries = $(this);
                const $entries = $feeEntries.find('.fee-entry');
                const $addButton = $feeEntries.find('.add-fee-btn');

                if ($entries.length >= 2) {
                    $addButton.addClass('d-none');
                } else {
                    $addButton.removeClass('d-none');
                }
            });
        }

        // Toggle specialization dropdown and amount width based on checkbox
        $(document).on('change', '.applies-to-all', function() {
            const $feeEntry = $(this).closest('.fee-entry');
            $feeEntry.find('.specialization-group').toggle(!this.checked);
            const selectId = $feeEntry.find('.select2').attr('id');
            const modalId = $feeEntry.closest('.tab-pane').attr('id');
            initializeSelect2(modalId, selectId, '', this.checked);
            toggleAmountWidth($feeEntry, this.checked);
        });

        // Add new fee entry dynamically
        $(document).on('click', '.add-fee-btn', function() {
            const $feeEntries = $(this).closest('.fee-entries');
            const currentEntries = $feeEntries.find('.fee-entry').length;

            if (currentEntries >= 2) {
                return; // Prevent adding more than 2 entries
            }

            const gradeId = $(this).data('grade-id');
            const month = $(this).data('month');
            let feeIndex = $(this).data('fee-index');
            feeIndex++;

            // Fix: Get the correct grade index by finding the position of this fee-entries within all fee-entries
            const gradeIndex = $('.fee-entries').index($feeEntries);
            const modalId = $(this).closest('.tab-pane').attr('id');
            const gradeName = $feeEntries.find('.input-group-text').first().text();

            const newEntry = `
        <div class="fee-entry mb-3">
            <input type="hidden" name="fees[${gradeIndex}-${feeIndex}][grade_id]" value="${gradeId}">
            <div class="row">
                <div class="col-6 amount-col">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text">${gradeName}</span>
                        <div class="form-floating form-floating-outline">
                            <input type="number"
                                id="grade-${gradeId}-${month}-${feeIndex}-amount"
                                class="form-control"
                                name="fees[${gradeIndex}-${feeIndex}][amount]"
                                step="0.01" min="0"
                                value="0.0"
                                placeholder="0.00" aria-label="0.00" required />
                            <label for="grade-${gradeId}-${month}-${feeIndex}-amount">
                                {{ trans('main.amount') }}
                            </label>
                        </div>
                        <span class="input-group-text">{{ trans('main.currency') }}</span>
                    </div>
                </div>
                <div class="col-6 specialization-group">
                    <div class="form-floating form-floating-outline">
                        <div class="select2-primary">
                            <select
                                id="grade-${gradeId}-${month}-${feeIndex}-specialization"
                                class="form-select select2"
                                name="fees[${gradeIndex}-${feeIndex}][specialization]">
                                <option value="">{{ trans('main.select_specialization') }}</option>
                                <option value="1">{{ trans('main.scientific') }}</option>
                                <option value="2">{{ trans('main.literary') }}</option>
                            </select>
                        </div>
                        <label for="grade-${gradeId}-${month}-${feeIndex}-specialization">
                            {{ trans('main.specialization') }}
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-check mt-2">
                <input type="checkbox"
                    class="form-check-input applies-to-all"
                    id="grade-${gradeId}-${month}-${feeIndex}-all"
                    name="fees[${gradeIndex}-${feeIndex}][applies_to_all_specializations]"
                    value="1">
                <label class="form-check-label"
                    for="grade-${gradeId}-${month}-${feeIndex}-all">
                    {{ trans('main.applies_to_all_specializations') }}
                </label>
            </div>
            <span class="invalid-feedback"
                id="grade-${gradeId}-${month}-${feeIndex}-error"
                role="alert"></span>
            <button type="button" class="btn btn-outline-danger btn-sm remove-fee-btn mt-2">
                {{ trans('main.delete') }}
            </button>
        </div>
    `;

            $(this).before(newEntry);
            initializeSelect2(modalId, `grade-${gradeId}-${month}-${feeIndex}-specialization`, '', false);
            $(this).data('fee-index', feeIndex);

            // Update button visibility after adding
            updateAddButtonVisibility();
        });

        // Add remove fee entry functionality
        $(document).on('click', '.remove-fee-btn', function() {
            const $feeEntry = $(this).closest('.fee-entry');
            const $feeEntries = $feeEntry.closest('.fee-entries');

            $feeEntry.remove();
            updateAddButtonVisibility();
        });

        @foreach ($months as $month)
            handleFormSubmit('#fees-form-{{ $month['key'] }}', ['month', 'fees']);
        @endforeach
    </script>
@endsection
