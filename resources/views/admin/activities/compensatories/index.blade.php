@extends('layouts.admin.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/compensatories.compensatories'))

@section('content')
    <!-- DataTable with Buttons -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/compensatories.compensatories')]) }}"
        dataToggle="offcanvas" AcceptButton RejectButton addButton="{{ trans('main.addItem', ['item' => trans('admin/compensatories.compensatory')]) }}">
        <th></th>
        <th class="dt-checkboxes-cell dt-checkboxes-select-all"><input type="checkbox" id="select-all" class="form-check-input"></th>
        <th>#</th>
        <th>{{ trans('main.student') }}</th>
        <th>{{ trans('admin/compensatories.original_lesson_group') }}</th>
        <th>{{ trans('admin/compensatories.original_lesson') }}</th>
        <th>{{ trans('admin/compensatories.makeup_lesson_group') }}</th>
        <th>{{ trans('admin/compensatories.makeup_lesson') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>
    @include('admin.activities.compensatories.modals')
    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('admin.compensatories.index') }}", [2, 3, 4, 5, 6, 7, 8],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'selectbox', name: 'selectbox', orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'student_id', name: 'student_id' },
                { data: 'original_lesson_group', name: 'original_lesson_group', orderable: false, searchable: false },
                { data: 'original_lesson_id', name: 'original_lesson_id', orderable: false, searchable: false },
                { data: 'makeup_lesson_group', name: 'makeup_lesson_group', orderable: false, searchable: false },
                { data: 'makeup_lesson_id', name: 'makeup_lesson_id', orderable: false, searchable: false },
                { data: 'status', name: 'status', orderable: false, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
        );

        // Setup add modal
        setupModal({
            buttonId: '#add-button',
            modalId: '#add-modal',
            fields: {
                teacher_id: () => '',
                student_id: () => '',
                original_lesson_group_id: () => '',
                original_lesson_id: () => '',
                makeup_lesson_group_id: () => '',
                makeup_lesson_id: () => '',
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

        let fields = ['teacher_idw', 'student_id', 'original_lesson_group_id', 'original_lesson_id', 'makeup_lesson_group_id', 'makeup_lesson_id', 'reason'];
        handleFormSubmit('#add-form', fields, '#add-modal', 'offcanvas', '#datatable');
        handleDeletionFormSubmit('#delete-form', '#delete-modal', '#datatable');
        handleDeletionFormSubmit('#accept-form', '#accept-modal', '#datatable');
        handleDeletionFormSubmit('#reject-form', '#reject-modal', '#datatable');
        handleDeletionFormSubmit('#accept-selected-form', '#accept-selected-modal', '#datatable')
        handleDeletionFormSubmit('#reject-selected-form', '#reject-selected-modal', '#datatable')
        fetchMultipleDataByAjax('#add-form #teacher_id', "{{ route('admin.fetch.teachers.students', '__ID__') }}",
            '#add-form #student_id', 'teacher_id', 'GET');
        fetchMultipleDataByAjax('#add-form #student_id', "{{ route('admin.fetch.students.groups', '__ID__') }}",
            '#add-form #original_lesson_group_id', 'student_id', 'GET');
        fetchMultipleDataByAjax('#add-form #original_lesson_group_id', "{{ route('admin.fetch.groups.lessons', '__ID__') }}",
            '#add-form #original_lesson_id', 'original_lesson_group_id', 'GET');
        fetchMultipleDataByAjax('#add-form #student_id', "{{ route('admin.fetch.students.groups', '__ID__') }}?type=makeup",
            '#add-form #makeup_lesson_group_id', 'student_id', 'GET');
        fetchMultipleDataByAjax('#add-form #makeup_lesson_group_id', "{{ route('admin.fetch.groups.lessons', '__ID__') }}",
            '#add-form #makeup_lesson_id', 'makeup_lesson_group_id', 'GET');
    </script>
@endsection
