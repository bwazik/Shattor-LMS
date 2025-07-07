@extends('layouts.teacher.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/studentFees.studentFees'))

@section('content')
    <!-- DataTable with Buttons -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/studentFees.studentFees')]) }}"
        dataToggle="offcanvas" deleteButton
        addButton="{{ trans('main.addItem', ['item' => trans('admin/studentFees.studentFee')]) }}">
        <th></th>
        <th class="dt-checkboxes-cell dt-checkboxes-select-all"><input type="checkbox" id="select-all" class="form-check-input"></th>
        <th>#</th>
        <th>{{ trans('main.student') }}</th>
        <th>{{ trans('main.fee') }}</th>
        <th>{{ trans('main.amount') }}</th>
        <th>{{ trans('main.discount') }}</th>
        <th>{{ trans('main.is_exempted') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>
    @include('teacher.finance.studentFees.modals')
    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('teacher.student-fees.index') }}", [2, 3, 4, 5, 6, 7],
            [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'selectbox',
                    name: 'selectbox',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'student_id',
                    name: 'student_id'
                },
                {
                    data: 'fee_id',
                    name: 'fee_id'
                },
                {
                    data: 'amount',
                    name: 'amount',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'discount',
                    name: 'discount',
                    searchable: false
                },
                {
                    data: 'is_exempted',
                    name: 'is_exempted'
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
        );

        // Setup add modal
        setupModal({
            buttonId: '#add-button',
            modalId: '#add-modal',
            fields: {
                student_id: () => '',
                fee_id: () => '',
                is_exempted: () => '',
            }
        });
        // Setup edit modal
        setupModal({
            buttonId: '#edit-button',
            modalId: '#edit-modal',
            fields: {
                id: button => button.data('id'),
                student_id: button => button.data('student_id'),
                fee_id: button => button.data('fee_id'),
                amount: button => button.data('amount'),
                discount: button => button.data('discount'),
                is_exempted: button => button.data('is_exempted'),
            }
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

        let fields = ['student_id', 'fee_id', 'discount', 'is_exempted'];
        handleFormSubmit('#add-form', fields, '#add-modal', 'offcanvas', '#datatable');
        handleFormSubmit('#edit-form', fields, '#edit-modal', 'offcanvas', '#datatable');
        handleDeletionFormSubmit('#delete-form', '#delete-modal', '#datatable')
        handleDeletionFormSubmit('#delete-selected-form', '#delete-selected-modal', '#datatable')
        fetchMultipleDataByAjax('#add-form #student_id', "{{ route('teacher.fetch.students.fees', '__ID__') }}",
            '#add-form #fee_id', 'student_id', 'GET');
        fetchSingleDataByAjax('#add-form #fee_id', "{{ route('teacher.fetch.fees.data', '__ID__') }}",
        [{ targetSelector: '#add-form #amount', dataKey: 'amount' }], 'fee_id');
        $(document).ready(function() {
            calculateAmountAndDiscount();
        });
    </script>
@endsection
