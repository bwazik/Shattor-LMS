@extends('layouts.teacher.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-invoice.css') }}">
@endsection

@section('title', pageTitle(trans('main.editItem', ['item' => trans('admin/invoices.invoice')])))

@section('content')
    <form id="edit-form" action="{{ route('teacher.invoices.update', $invoice->uuid) }}" method="POST" autocomplete="off">
        @csrf
        <div class="row invoice-add">
            <div class="col-lg-9 col-12 mb-lg-0 mb-6">
                <div class="card invoice-preview-card p-sm-12 p-6">
                    <div class="card-body invoice-preview-header rounded-4 text-heading p-6 px-3">
                        <div class="row mx-0 px-3 row-gap-6">
                            <div class="col-md-8 ps-0">
                                <div class="d-flex svg-illustration align-items-center gap-2 mb-6">
                                    <span class="app-brand-logo demo">
                                        <img width="60" height="60" src="{{ asset('assets/img/brand/navbar.png') }}"
                                            alt="Shattor">
                                    </span>
                                    <span
                                        class="mb-0 app-brand-text demo fw-semibold">{{ trans('layouts/sidebar.platformName') }}</span>
                                </div>
                                <p class="mb-1">01098617164</p>
                                <p class="mb-0">bwazik@outlook.com</p>
                            </div>
                            <div class="col-md-4 col-8 pe-0 ps-0 ps-md-2">
                                <dl class="row mb-0 gx-4">
                                    <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-start">
                                        <span
                                            class="h5 text-capitalize mb-0 text-nowrap">{{ trans('admin/invoices.theInvoice') }}</span>
                                    </dt>
                                    <dd class="col-sm-7">
                                        <div class="input-group input-group-merge input-group-sm">
                                            <span class="input-group-text">#</span>
                                            <input type="text" class="form-control" placeholder="{{ substr($invoice->uuid, 14, 4) }}"
                                                value="{{ substr($invoice->uuid, 14, 4) }}" disabled />
                                        </div>
                                    </dd>
                                    <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-start">
                                        <span class="fw-normal">{{ trans('main.status') }}: </span>
                                    </dt>
                                    <dd class="col-sm-7">
                                        <input type="text" id="status"
                                            class="form-control form-control-sm"
                                            name="status"
                                            placeholder="{{ trans('main.status') }}"
                                            value="{{ trans('main.' . match($invoice->status) {
                                                1 => 'pending',
                                                2 => 'paid',
                                                3 => 'overdue',
                                                4 => 'canceled',
                                                default => 'n_a'
                                            }) }}"
                                            disabled/>
                                    </dd>
                                    <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-start">
                                        <span class="fw-normal">{{ trans('main.date') }}: </span>
                                    </dt>
                                    <dd class="col-sm-7">
                                        <input type="text" id="date"
                                            class="form-control form-control-sm flatpickr-date" name="date"
                                            placeholder="YYYY-MM-DD" value="{{ $invoice->date }}" />
                                        <span class="invalid-feedback" id="date_error" role="alert"></span>
                                    </dd>
                                    <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-start">
                                        <span class="fw-normal text-nowrap">{{ trans('main.due_date') }}:</span>
                                    </dt>
                                    <dd class="col-sm-7 mb-0">
                                        <input type="text" id="due_date"
                                            class="form-control form-control-sm flatpickr-date" name="due_date"
                                            placeholder="YYYY-MM-DD" value="{{ $invoice->due_date }}" />
                                        <span class="invalid-feedback" id="due_date_error" role="alert"></span>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="card-body py-6 px-0">
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-12 mb-sm-0 mb-6">
                                <x-select-input divClasses="mb-4" name="student_id" label="{{ trans('main.student') }}"
                                    :options="$students" required />
                                <p id="student_grade" class="mb-1">{{ $invoice->student->grade->name }}</p>
                                <p id="student_phone" class="mb-0">{{ $invoice->student->phone }}</p>
                                <p id="student_email" class="mb-1">{{ $invoice->student->email }}</p>
                            </div>
                            <div class="col-md-6 col-sm-6 col-12 mb-sm-0 mb-6">
                                <x-basic-input divClasses="mb-4" type="text" name="parent_name"
                                    label="{{ trans('main.parent') }}"
                                    placeholder="{{ trans('main.placeholders.realName') }}"
                                    value="{{ $invoice->student->parent->name ?? 'N/A' }}" disabled />
                                <p id="parent_phone" class="mb-1">{{ $invoice->student->parent->phone ?? 'N/A' }}</p>
                                <p id="parent_email" class="mb-0">{{ $invoice->student->parent->email ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    <hr class="mt-0 mb-6" />
                    <div class="card-body p-0 pb-6">
                        <div class="mb-4">
                            <div class="pt-0 pt-md-9">
                                <div class="d-flex border rounded position-relative pe-0">
                                    <div class="row w-100 p-5 gx-5">
                                        <div class="col-md-6 col-12 mb-md-0 mb-4">
                                            <p class="h6 repeater-title">{{ trans('main.fee') }}</p>
                                            <x-select-input divClasses="mb-5" name="student_fee_id"
                                                label="{{ trans('main.fee') }}" :options="$studentFees" required />
                                        </div>
                                        <div class="col-md-3 col-12 mb-md-0 mb-4">
                                            <p class="h6 repeater-title">{{ trans('main.amount') }}</p>
                                            <x-basic-input divClasses="mb-5" price type="number" name="amount"
                                                label="{{ trans('main.amount') }}" value="{{ $invoice->fee->amount }}"
                                                placeholder="0.00" disabled />
                                        </div>
                                        <div class="col-md-3 col-12 mb-md-0 mb-4">
                                            <p class="h6 repeater-title">{{ trans('main.discount') }}</p>
                                            <x-basic-input divClasses="mb-5" type="number" name="discount"
                                                label="{{ trans('main.discount') }}"
                                                value="{{ $invoice->studentFee->discount }}" placeholder="0" disabled />
                                            <x-basic-input divClasses="" type="text" name="exemptition"
                                                label="{{ trans('main.status') }}"
                                                value="{{ $invoice->studentFee->is_exempted }}"
                                                placeholder="{{ trans('main.status') }}" disabled />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-1" />
                    <div class="card-body px-0 pb-6">
                        <div class="row row-gap-4">
                            <div class="col-md-6 mb-md-0 mb-3">
                                <x-basic-input divClasses="mb-4" type="text" name="teacher_name"
                                    label="{{ trans('main.teacher') }}"
                                    placeholder="{{ trans('main.placeholders.realName') }}"
                                    value="{{ $invoice->fee->teacher->name }}" disabled />
                                <p id="teacher_phone" class="mb-1">{{ $invoice->fee->teacher->phone }}</p>
                                <p id="teacher_email" class="mb-0">{{ $invoice->fee->teacher->email }}</p>
                            </div>
                            <div class="col-md-6 d-flex justify-content-md-end mt-2">
                                <div class="invoice-calculations">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="w-px-100">{{ trans('main.amount') }}:</span>
                                        <h6 class="mb-0"><span id="fee_amount">{{ $invoice->fee->amount }}</span>
                                            {{ trans('main.currency') }}</h6>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="w-px-100">{{ trans('main.discount') }}:</span>
                                        <h6 class="mb-0"><span
                                                id="fee_discount">{{ $invoice->studentFee->discount }}</span> %</h6>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="w-px-100">{{ trans('main.status') }}:</span>
                                        <h6 id="fee_exempted_status" class="mb-0">
                                            {{ $invoice->studentFee->is_exempted }}</h6>
                                    </div>
                                    <hr class="my-2" />
                                    <div class="d-flex justify-content-between">
                                        <span class="w-px-100">{{ trans('main.total') }}:</span>
                                        <h6 class="mb-0"><span
                                                id="fee_total">{{ $invoice->studentFee->totalAmount }}</span>
                                            {{ trans('main.currency') }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-0" />
                    <div class="card-body py-6 px-0">
                        <div class="row">
                            <div class="col-12">
                                <x-text-area divClasses="" name="description" label="{{ trans('main.description') }}"
                                    value="{{ $invoice->description }}"
                                    placeholder="{{ trans('main.placeholders.description') }}" maxlength=500 />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-12 invoice-actions">
                <div class="card mb-6">
                    <div class="card-body">
                        <button type="submit" id="submit" class="btn btn-primary d-grid w-100 mb-4">
                            <span class="d-flex align-items-center justify-content-center text-nowrap">{{ trans('main.submit') }}</span>
                        </button>
                        <button type="button" class="btn btn-success d-grid w-100 mb-4" data-bs-toggle="offcanvas" data-bs-target="#payment-modal" >
                            <span class="d-flex align-items-center justify-content-center text-nowrap">{{ trans('main.addItem', ['item' => trans('main.payment')]) }}</span>
                        </button>
                        <button type="button" class="btn btn-danger d-grid w-100 mb-4" data-bs-toggle="offcanvas" data-bs-target="#refund-modal" >
                            <span class="d-flex align-items-center justify-content-center text-nowrap">{{ trans('main.addItem', ['item' => trans('main.refund')]) }}</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary d-grid w-100 mb-4" id="cancel-button"
                            data-id="{{ $invoice->uuid }}" data-fee="{{ $invoice->fee->name }}" data-student="{{ $invoice->student->name }}"
                            data-bs-target="#cancel-modal" data-bs-toggle="modal" data-bs-dismiss="modal">
                            <span class="d-flex align-items-center justify-content-center text-nowrap">{{ trans('main.cancelItem', ['item' => trans('admin/invoices.theInvoice')]) }}</span>
                        </button>
                        <div class="d-flex">
                            <a href="{{ route('teacher.invoices.preview', $invoice->uuid) }}" class="btn btn-outline-secondary d-grid w-100 me-4 waves-effect text-nowrap">
                                {{ trans('main.previewItem', ['item' => trans('admin/invoices.theInvoice')]) }}
                            </a>
                            <a href="{{ route('teacher.invoices.index') }}" class="btn btn-outline-secondary d-grid w-100 waves-effect text-nowrap">
                                {{ trans('admin/invoices.invoices') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Payment Modal -->
    <x-offcanvas offcanvasType="payment" offcanvasTitle="{{ trans('main.addItem', ['item' => trans('main.payment')]) }}"
        action="{{ route('teacher.invoices.payment', $invoice->uuid) }}">
        <div class="d-flex justify-content-between bg-lighter p-2 mb-5 rounded">
            <p class="mb-0">{{ trans('main.due_amount') }}:</p>
            <p class="fw-medium mb-0">{{ $dueAmount }} {{ trans('main.currency') }}</p>
        </div>
        <x-basic-input context="offcanvas" price type="number" name="amount" label="{{ trans('main.amount') }}" placeholder="0.00" required/>
        <x-basic-input context="offcanvas" type="text" name="date" classes="flatpickr-date" label="{{ trans('main.date') }}" placeholder="YYYY-MM-DD" value="{{ now()->format('Y-m-d') }}" disabled/>
        <x-select-input context="offcanvas" name="payment_method" label="{{ trans('main.paymentMethod') }}" :options="[1 => trans('main.cash'), 2 => trans('main.vodafoneCash'), 3 => trans('main.instapay')]" required/>
        <x-text-area context="offcanvas" name="description" label="{{ trans('main.description') }}" placeholder="{{ trans('main.placeholders.description') }}"/>
    </x-offcanvas>
    <!-- Refund Modal -->
    <x-offcanvas offcanvasType="refund" offcanvasTitle="{{ trans('main.addItem', ['item' => trans('main.refund')]) }}"
        action="{{ route('teacher.invoices.refund', $invoice->uuid) }}">
        <div class="d-flex justify-content-between bg-lighter p-2 mb-5 rounded">
            <p class="mb-0">{{ trans('main.due_amount') }}:</p>
            <p class="fw-medium mb-0">{{ $dueAmount }} {{ trans('main.currency') }}</p>
        </div>
        <x-basic-input context="offcanvas" price type="number" name="amount" label="{{ trans('main.amount') }}" placeholder="0.00" required/>
        <x-basic-input context="offcanvas" type="text" name="date" classes="flatpickr-date" label="{{ trans('main.date') }}" placeholder="YYYY-MM-DD" value="{{ now()->format('Y-m-d') }}" disabled/>
        <x-select-input context="offcanvas" name="payment_method" label="{{ trans('main.paymentMethod') }}" :options="[1 => trans('main.cash'), 2 => trans('main.vodafoneCash'), 3 => trans('main.instapay')]" required/>
        <x-text-area context="offcanvas" name="description" label="{{ trans('main.description') }}" placeholder="{{ trans('main.placeholders.description') }}"/>
    </x-offcanvas>
    <!-- Cancel Modal -->
    <x-modal modalType="cancel" modalTitle="{{ trans('main.cancelItem', ['item' => trans('admin/invoices.invoice')]) }}"
        action="{{ route('teacher.invoices.cancel') }}" id submitColor="danger" submitButton="{{ trans('main.yes_cancel') }}">
        @include('partials.cancel-modal-body')
    </x-modal>
@endsection

@section('page-js')
    <script>
        initializeSelect2('edit-form', 'student_id', '{{ $invoice->student->uuid }}');
        initializeSelect2('edit-form', 'student_fee_id', '{{ $invoice->studentFee->uuid }}');
        initializeSelect2('payment-form', 'payment_method', 1);
        initializeSelect2('refund-form', 'payment_method', 1);

        fetchMultipleDataByAjax('#edit-form #student_id', "{{ route('teacher.fetch.students.student-fees', '__ID__') }}",
            '#edit-form #student_fee_id', 'student_id', 'GET');
        fetchSingleDataByAjax('#edit-form #student_id', "{{ route('teacher.fetch.students.data', '__ID__') }}", [{
                targetSelector: '#edit-form #student_grade',
                dataKey: 'grade.name'
            },
            {
                targetSelector: '#edit-form #student_phone',
                dataKey: 'phone'
            },
            {
                targetSelector: '#edit-form #student_email',
                dataKey: 'email'
            },
            {
                targetSelector: '#edit-form #parent_name',
                dataKey: 'parent.name'
            },
            {
                targetSelector: '#edit-form #parent_phone',
                dataKey: 'parent.phone'
            },
            {
                targetSelector: '#edit-form #parent_email',
                dataKey: 'parent.email'
            },
        ], 'student_id');
        fetchSingleDataByAjax('#edit-form #student_fee_id', "{{ route('teacher.fetch.student-fees.data', '__ID__') }}", [{
                targetSelector: '#edit-form #teacher_name',
                dataKey: 'fee.teacher.name'
            },
            {
                targetSelector: '#edit-form #teacher_phone',
                dataKey: 'fee.teacher.phone'
            },
            {
                targetSelector: '#edit-form #teacher_email',
                dataKey: 'fee.teacher.email'
            },
            {
                targetSelector: '#edit-form #amount',
                dataKey: 'fee.amount'
            },
            {
                targetSelector: '#edit-form #fee_amount',
                dataKey: 'fee.amount'
            },
            {
                targetSelector: '#edit-form #discount',
                dataKey: 'discount'
            },
            {
                targetSelector: '#edit-form #exemptition',
                dataKey: 'exempted_status'
            },
            {
                targetSelector: '#edit-form #fee_discount',
                dataKey: 'discount'
            },
            {
                targetSelector: '#edit-form #fee_exempted_status',
                dataKey: 'exempted_status'
            },
            {
                targetSelector: '#edit-form #fee_total',
                dataKey: 'amount'
            },
        ], 'student_fee_id');

        // Setup cancel modal
        setupModal({
            buttonId: '#cancel-button',
            modalId: '#cancel-modal',
            fields: {
                id: button => button.data('id'),
                itemToCancel: button => `${button.data('fee')} - ${button.data('student')}`
            }
        });

        let fields = ['student_id', 'student_fee_id', 'date', 'due_date', 'description'];
        let paymentFields = ['amount', 'payment_method', 'description'];
        handleFormSubmit('#edit-form', fields, '#add-modal');
        handleFormSubmit('#payment-form', paymentFields, '#payment-modal');
        handleFormSubmit('#refund-form', paymentFields, '#refund-modal');
        handleDeletionFormSubmit('#cancel-form', '#cancel-modal')
    </script>
@endsection
