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
    <script>
        initializeDataTable('#datatable', "{{ route('teacher.invoices.index') }}", [2, 3, 4, 5, 6, 7],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'uuid', name: 'uuid' },
                { data: 'balance', name: 'balance', orderable: false, searchable: false },
                { data: 'details', name: 'student_id' },
                { data: 'fee_id', name: 'fee_id' },
                { data: 'date', name: 'date' },
                { data: 'amount', name: 'amount', orderable: false, searchable: false },
                { data: 'status', name: 'status' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
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
