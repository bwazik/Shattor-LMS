@extends('layouts.admin.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/compensatories.compensatories'))

@section('content')
    <!-- DataTable with Buttons -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/compensatories.compensatories')]) }} - {{ $lesson->group->teacher->name }} - {!! $lesson->title !!}"
        dataToggle="offcanvas" AcceptButton RejectButton addButton="{{ trans('main.addItem', ['item' => trans('admin/compensatories.compensatory')]) }}">
        <th></th>
        <th class="dt-checkboxes-cell dt-checkboxes-select-all"><input type="checkbox" id="select-all" class="form-check-input"></th>
        <th>#</th>
        <th>{{ trans('main.student') }}</th>
        <th>{{ trans('admin/compensatories.original_lesson_group') }}</th>
        <th>{{ trans('admin/compensatories.original_lesson') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>
    <!-- Add Modal -->
    <x-offcanvas offcanvasType="add" offcanvasTitle="{{ trans('main.addItem', ['item' => trans('admin/compensatories.compensatory')]) }}"
        action="{{ route('admin.compensatories.insert') }}">
        <x-select-input context="offcanvas" name="student_id" label="{{ trans('main.student') }}" :options="$students" required/>
        <x-select-input context="offcanvas" name="original_lesson_group_id" label="{{ trans('admin/compensatories.original_lesson_group') }}" required/>
        <x-select-input context="offcanvas" name="original_lesson_id" label="{{ trans('admin/compensatories.original_lesson') }}" required/>
        <x-text-area context="offcanvas" name="reason" label="{{ trans('main.reason') }}" placeholder="{{ trans('admin/compensatories.placeholders.reason') }}" maxlength=1000 required/>
        <x-basic-input divClasses="d-none" type="text" name="makeup_lesson_id" label="{{ trans('admin/compensatories.makeup_lesson') }}" required/>
    </x-offcanvas>
    <!-- Delete Modal -->
    <x-modal modalType="delete" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/compensatories.compensatory')]) }}"
        action="{{ route('admin.compensatories.delete') }}" id submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
        @include('partials.delete-modal-body')
    </x-modal>
    <!-- Accept Modal -->
    <x-modal modalType="accept" modalTitle="{{ trans('main.acceptItem', ['item' => trans('admin/compensatories.compensatory')]) }}"
        action="{{ route('admin.compensatories.accept') }}" id submitColor="success" submitButton="{{ trans('main.yes_accept') }}">
        @include('partials.accept-modal-body')
    </x-modal>
    <!-- Reject Modal -->
    <x-modal modalType="reject" modalTitle="{{ trans('main.rejectItem', ['item' => trans('admin/compensatories.compensatory')]) }}"
        action="{{ route('admin.compensatories.reject') }}" id submitColor="danger" submitButton="{{ trans('main.yes_reject') }}">
        @include('partials.reject-modal-body')
    </x-modal>
    <!-- Accept Selected Modal -->
    <x-modal modalType="accept-selected" modalTitle="{{ trans('main.acceptItem', ['item' => trans('admin/compensatories.selectedCompensatories')]) }}"
        action="{{ route('admin.compensatories.acceptSelected') }}" submitColor="success" ids submitButton="{{ trans('main.yes_accept') }}">
        @include('partials.accept-modal-body')
    </x-modal>
    <!-- Reject Selected Modal -->
    <x-modal modalType="reject-selected" modalTitle="{{ trans('main.rejectItem', ['item' => trans('admin/compensatories.selectedCompensatories')]) }}"
        action="{{ route('admin.compensatories.rejectSelected') }}" submitColor="danger" ids submitButton="{{ trans('main.yes_reject') }}">
        @include('partials.reject-modal-body')
    </x-modal>
    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('admin.lessons.compensatories', $lesson->id) }}", [2, 3, 4, 5, 6],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'selectbox', name: 'selectbox', orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'student_id', name: 'student_id' },
                { data: 'original_lesson_group', name: 'original_lesson_group', orderable: false, searchable: false },
                { data: 'original_lesson_id', name: 'original_lesson_id', orderable: false, searchable: false },
                { data: 'status', name: 'status', orderable: false, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
        );

        // Setup add modal
        setupModal({
            buttonId: '#add-button',
            modalId: '#add-modal',
            fields: {
                student_id: () => '',
                original_lesson_group_id: () => '',
                original_lesson_id: () => '',
                makeup_lesson_id: () => '{{ $lesson->id }}',
            }
        });
        // Setup delete modal
        setupModal({
            buttonId: '#delete-button',
            modalId: '#delete-modal',
            fields: {
                id: button => button.data('id'),
                itemToDelete: button => `${button.data('student')} - ${button.data('reason')}`
            }
        });
        // Setup accept modal
        setupModal({
            buttonId: '#accept-button',
            modalId: '#accept-modal',
            fields: {
                id: button => button.data('id'),
                itemToAccept: button => `${button.data('student')} - ${button.data('reason')}`
            }
        });
        // Setup reject modal
        setupModal({
            buttonId: '#reject-button',
            modalId: '#reject-modal',
            fields: {
                id: button => button.data('id'),
                itemToReject: button => `${button.data('student')} - ${button.data('reason')}`
            }
        });

        let fields = ['student_id', 'original_lesson_group_id', 'original_lesson_id', 'makeup_lesson_id', 'reason'];
        handleFormSubmit('#add-form', fields, '#add-modal', 'offcanvas', '#datatable');
        handleDeletionFormSubmit('#delete-form', '#delete-modal', '#datatable');
        handleDeletionFormSubmit('#accept-form', '#accept-modal', '#datatable');
        handleDeletionFormSubmit('#reject-form', '#reject-modal', '#datatable');
        handleDeletionFormSubmit('#accept-selected-form', '#accept-selected-modal', '#datatable')
        handleDeletionFormSubmit('#reject-selected-form', '#reject-selected-modal', '#datatable')
        fetchMultipleDataByAjax('#add-form #student_id', "{{ route('admin.fetch.students.groups', '__ID__') }}",
            '#add-form #original_lesson_group_id', 'student_id', 'GET');
        fetchMultipleDataByAjax('#add-form #original_lesson_group_id', "{{ route('admin.fetch.groups.lessons', '__ID__') }}",
            '#add-form #original_lesson_id', 'original_lesson_group_id', 'GET');
    </script>
@endsection
