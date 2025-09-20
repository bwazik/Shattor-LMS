@extends('layouts.teacher.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/zooms.zooms'))

@section('content')
    <!-- DataTable with Buttons -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/zooms.zooms')]) }}"
        dataToggle="offcanvas" deleteButton addButton="{{ trans('main.addItem', ['item' => trans('admin/zooms.zoom')]) }}">
        <th></th>
        <th class="dt-checkboxes-cell dt-checkboxes-select-all"><input type="checkbox" id="select-all" class="form-check-input"></th>
        <th>#</th>
        <th>{{ trans('main.topic') }}</th>
        <th>{{ trans('main.grade') }}</th>
        <th>{{ trans('main.duration') }}</th>
        <th>{{ trans('main.start_time') }}</th>
        <th>{{ trans('main.join_url') }}</th>
        <th>{{ trans('main.created_at') }}</th>
        <th>{{ trans('main.updated_at') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>
    @include('teacher.activities.zooms.modals')
    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('teacher.zooms.index') }}", [2, 3, 4, 5, 6, 7, 8, 9],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'selectbox', name: 'selectbox', orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'topic', name: 'topic' },
                { data: 'grade_id', name: 'grade_id' },
                { data: 'duration', name: 'duration', orderable: false, searchable: false },
                { data: 'start_time', name: 'start_time', orderable: false, searchable: false },
                { data: 'join_url', name: 'join_url', orderable: false, searchable: false},
                { data: 'created_at', name: 'created_at', orderable: false, searchable: false },
                { data: 'updated_at', name: 'updated_at', orderable: false, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
        );

        // Setup add modal
        setupModal({
            buttonId: '#add-button',
            modalId: '#add-modal',
            fields: {
                grade_id: () => '',
                groups: () => '',
            }
        });
        // Setup edit modal
        setupModal({
            buttonId: '#edit-button',
            modalId: '#edit-modal',
            fields: {
                id: button => button.data('id'),
                meeting_id: button => button.data('meeting_id'),
                grade_id: button => button.data('grade_id'),
                groups: button => button.data('groups'),
                topic_ar: button => button.data('topic_ar'),
                topic_en: button => button.data('topic_en'),
                duration: button => button.data('duration'),
                start_time: button => button.data('start_time'),
            }
        });
        // Setup delete modal
        setupModal({
            buttonId: '#delete-button',
            modalId: '#delete-modal',
            fields: {
                id: button => button.data('id'),
                meeting_id: button => button.data('meeting_id'),
                itemToDelete: button => `${button.data('topic_ar')} - ${button.data('topic_en')}`
            }
        });

        let fields = ['grade_id', 'topic_ar', 'topic_en', 'duration', 'start_time', 'password'];
        handleFormSubmit('#add-form', fields, '#add-modal', 'offcanvas', '#datatable');
        handleFormSubmit('#edit-form', fields, '#edit-modal', 'offcanvas', '#datatable');
        handleDeletionFormSubmit('#delete-form', '#delete-modal', '#datatable')
        handleDeletionFormSubmit('#delete-selected-form', '#delete-selected-modal', '#datatable')
        fetchMultipleDataByAjax('#add-form #grade_id', "{{ route('teacher.fetch.grade.groups', '__ID__') }}", '#add-form #groups', 'grade_id', 'GET')
    </script>
@endsection
