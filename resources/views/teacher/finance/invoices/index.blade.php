@extends('layouts.teacher.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/invoices.invoices'))

@section('content')
    @include('admin.finance.invoices.statistics')
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
        initializeDataTable('#datatable', "{{ route('teacher.invoices.index') }}", [2, 3, 4, 5, 6, 7],
            [{
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
            ],
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
