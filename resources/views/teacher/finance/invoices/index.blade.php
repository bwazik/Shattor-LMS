@extends('layouts.teacher.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/invoices.invoices'))

@section('content')
    @include('admin.finance.invoices.statistics')
    <form action="{{ route('teacher.invoices.index') }}" method="GET" class="card-header mb-4">
        <div class="row align-items-end g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">{{ trans('main.month') }}</label>
                <select name="month" class="form-select">
                    <option value="">{{ trans('main.all_months') }}</option>
                    @php
                        $arabicMonths = [
                            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
                            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
                            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
                        ];
                    @endphp
                    @foreach($arabicMonths as $num => $name)
                        <option value="{{ $num }}" {{ request('month') == $num ? 'selected' : '' }}>
                            {{ $num }} - {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">{{ trans('main.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ trans('main.all_status') }}</option>
                    <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>{{ trans('main.paid') }}</option>
                    <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>{{ trans('main.unpaid') }}</option>
                </select>
            </div>
        </div>
        <div class="row align-items-end g-3">
            <div class="col-12 mb-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ri-filter-3-line"></i> {{ trans('main.filter') }}
                </button>
            </div>   
            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ri-filter-3-line"></i> {{ trans('main.showAllInvoices') }} (مصاريف الشهر الي احنا فيه هي بس الي بتظهر , لو عايز تظهر مصاريف الشهور كلها اضغط هنا)
                </button>
            </div>   
        </div> 
    </form>
    <!-- DataTable with Buttons -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/invoices.invoices')]) }}"
        dataToggle="offcanvas" hrefButton="{{ trans('main.addItem', ['item' => trans('admin/invoices.invoice')]) }}"
        hrefButtonRoute="{{ route('teacher.invoices.create') }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('main.due_amount') }}</th>
        <th>{{ trans('main.student') }}</th>
        <th>{{ trans('main.fee') }}</th>
        <th>{{ trans('main.date') }}</th>
        <th>{{ trans('main.amount') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>
    @include('teacher.finance.invoices.modals')
    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script src="{{ asset('assets/js/pages-auth-two-steps.js') }}"></script>

    <script>
        const formId = $("#verify-pin-form");
        const pinSubmitButton = formId.find('button[type="submit"]');
        const originalButtonContent = pinSubmitButton.html();

        $(formId).on('submit', function(e) {
            e.preventDefault();

            pinSubmitButton.find('.waves-ripple').remove();
            pinSubmitButton.prop('disabled', true);
            pinSubmitButton.html(
                `<i class="ri-loader-4-line ri-spin ri-20px me-1"></i> {{ trans('main.processing') }}...`
            );

            const pin = $('.numeral-mask').map(function() {
                return $(this).val();
            }).get().join('');
            $('#pin').val(pin);

            const formData = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                type: $(this).attr('method'),
                dataType: "json",
                processData: false,
                contentType: false,
                data: formData,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message)
                        $('#pin-form-container').hide();
                        $('#paid-amount').text(response.paid);
                        $('#unpaid-amount').text(response.unpaid);
                        resetButtonState(pinSubmitButton, originalButtonContent);
                    } else {
                        toastr.error(response.error || '{{ trans('main.errorMessage') }}');
                        resetButtonState(pinSubmitButton, originalButtonContent);
                    }
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 429) {
                        toastr.error(tooManyRequestsMessage);
                    } else if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(key, val) {
                                toastr.error(val);
                            });
                        } else if (xhr.responseJSON.error) {
                            toastr.error(xhr.responseJSON.error);
                        } else {
                            toastr.error('{{ trans('main.errorMessage') }}');
                        }
                    } else {
                        toastr.error('{{ trans('main.errorMessage') }}');
                    }

                    resetButtonState(pinSubmitButton, originalButtonContent);
                },
                complete: function() {
                    resetButtonState(pinSubmitButton, originalButtonContent);
                }
            });
        });

        function resetButtonState(pinSubmitButton, originalButtonContent) {
            setTimeout(function() {
                pinSubmitButton.prop('disabled', false);
                pinSubmitButton.html(originalButtonContent);
                pinSubmitButton.blur();
                pinSubmitButton.find('.waves-ripple').remove();
                if (typeof Waves !== 'undefined') {
                    Waves.init();
                    Waves.attach(pinSubmitButton[0]);
                }
            }, 1500);
        }
    </script>

    <script>
        const queryParams = window.location.search;
    
        const dataUrl = "{{ route('teacher.invoices.index') }}" + (queryParams ? queryParams : "");

        initializeDataTable(
            '#datatable', 
            dataUrl,
            [2, 3, 4, 5, 6, 7], 
            [
                {
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'uuid',
                    name: 'uuid'
                },
                {
                    data: 'balance',
                    name: 'balance',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'details',
                    name: 'student_id'
                },
                {
                    data: 'fee_id',
                    name: 'fee_id'
                },
                {
                    data: 'date',
                    name: 'date'
                },
                {
                    data: 'amount',
                    name: 'amount',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ]
        );

        // Setup Payment modal
        setupModal({
            buttonId: '#payment-button',
            modalId: '#payment-modal',
            fields: {
                id: button => button.data('id'),
                payment_method: () => 1,
            },
            onShow: function(modal, button) {
                const form = modal[0].querySelector('#payment-form');
                const dueAmount = form.querySelector('#due_amount');
                if (!form.dataset.actionTemplate) {
                    form.dataset.actionTemplate = form.action;
                }
                form.action = form.dataset.actionTemplate.replace('__ID__', button.data('id'));
                dueAmount.textContent = button.data('due_amount');
            },
        });
        // Setup Refund modal
        setupModal({
            buttonId: '#refund-button',
            modalId: '#refund-modal',
            fields: {
                id: button => button.data('id'),
                payment_method: () => 1,
            },
            onShow: function(modal, button) {
                const form = modal[0].querySelector('#refund-form');
                const dueAmount = form.querySelector('#due_amount');
                if (!form.dataset.actionTemplate) {
                    form.dataset.actionTemplate = form.action;
                }
                form.action = form.dataset.actionTemplate.replace('__ID__', button.data('id'));
                dueAmount.textContent = button.data('due_amount');
            },
        });
        // Setup delete modal
        setupModal({
            buttonId: '#delete-button',
            modalId: '#delete-modal',
            fields: {
                id: button => button.data('id'),
                itemToDelete: button => `${button.data('fee')} - ${button.data('student')}`
            }
        });
        // Setup cancel modal
        setupModal({
            buttonId: '#cancel-button',
            modalId: '#cancel-modal',
            fields: {
                id: button => button.data('id'),
                itemToCancel: button => `${button.data('fee')} - ${button.data('student')}`
            }
        });

        let paymentFields = ['amount', 'payment_method', 'description'];
        handleFormSubmit('#payment-form', paymentFields, '#payment-modal', 'offcanvas', '#datatable');
        handleFormSubmit('#refund-form', paymentFields, '#refund-modal', 'offcanvas', '#datatable');
        handleDeletionFormSubmit('#delete-form', '#delete-modal', '#datatable')
        handleDeletionFormSubmit('#cancel-form', '#cancel-modal', '#datatable')
    </script>
@endsection
