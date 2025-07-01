@extends('layouts.teacher.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/lessons.lessons'))

@section('content')
    @include('admin.tools.lessons.statistics')
    <!-- DataTable with Buttons -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/lessons.lessons')]) }}"
        dataToggle="offcanvas" deleteButton addButton="{{ trans('main.addItem', ['item' => trans('admin/lessons.lesson')]) }}">
        <th></th>
        <th class="dt-checkboxes-cell dt-checkboxes-select-all"><input type="checkbox" id="select-all" class="form-check-input"></th>
        <th>#</th>
        <th>{{ trans('main.title') }}</th>
        <th>{{ trans('main.attendance') }}</th>
        <th>{{ trans('main.group') }}</th>
        <th>{{ trans('main.date') }}</th>
        <th>{{ trans('main.time') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>
    @include('teacher.tools.lessons.modals')
    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('teacher.lessons.index') }}", [2, 3, 4, 5, 6, 7],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'selectbox', name: 'selectbox', orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'title', name: 'title' },
                { data: 'attendances', name: 'attendances' },
                { data: 'group_id', name: 'group_id' },
                { data: 'date', name: 'date' },
                { data: 'time', name: 'time', orderable: false, searchable: false },
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
                grade_id: () => '',
                group_id: () => '',
                status: () => '',
            }
        });
        // Setup edit modal
        setupModal({
            buttonId: '#edit-button',
            modalId: '#edit-modal',
            fields: {
                id: button => button.data('id'),
                title_ar: button => button.data('title_ar'),
                title_en: button => button.data('title_en'),
                group_id: button => button.data('group_id'),
                date: button => button.data('date'),
                time: button => button.data('time'),
                status: button => button.data('status')
            }
        });
        // Setup delete modal
        setupModal({
            buttonId: '#delete-button',
            modalId: '#delete-modal',
            fields: {
                id: button => button.data('id'),
                itemToDelete: button => `${button.data('title_ar')} - ${button.data('title_en')}`
            }
        });

        let fields = ['title_ar', 'title_en', 'group_id', 'date', 'time', 'status'];
        handleFormSubmit('#add-form', fields, '#add-modal', 'offcanvas', '#datatable');
        handleFormSubmit('#edit-form', fields, '#edit-modal', 'offcanvas', '#datatable');
        handleDeletionFormSubmit('#delete-form', '#delete-modal', '#datatable')
        handleDeletionFormSubmit('#delete-selected-form', '#delete-selected-modal', '#datatable')
        fetchMultipleDataByAjax('#add-form #grade_id', "{{ route('teacher.fetch.grade.groups', '__ID__') }}", '#add-form #group_id', 'grade_id', 'GET')
    </script>
@endsection
