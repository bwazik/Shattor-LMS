@extends('layouts.admin.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/offlineQuizzes.offlineQuizzes'))

@section('content')
    <!-- DataTable with Buttons -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/offlineQuizzes.offlineQuizzes')]) }}"
        dataToggle="modal" deleteButton addButton="{{ trans('main.addItem', ['item' => trans('admin/offlineQuizzes.offlineQuiz')]) }}">
        <th></th>
        <th class="dt-checkboxes-cell dt-checkboxes-select-all"><input type="checkbox" id="select-all" class="form-check-input"></th>
        <th>#</th>
        <th>{{ trans('main.name') }}</th>
        <th>{{ trans('main.teacher') }}</th>
        <th>{{ trans('main.grade') }}</th>
        <th>{{ trans('main.type') }}</th>
        <th>{{ trans('main.score') }}</th>
        <th>{{ trans('main.conducted_at') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>
    @include('admin.activities.offline-quizzes.modals')
    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('admin.offline-quizzes.index') }}", [2, 3, 4, 5, 6, 7],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'selectbox', name: 'selectbox', orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'teacher_id', name: 'teacher_id' },
                { data: 'grade_id', name: 'grade_id' },
                { data: 'type', name: 'type', orderable: false, searchable: false },
                { data: 'score', name: 'score', orderable: false, searchable: false },
                { data: 'conducted_at', name: 'conducted_at', orderable: false, searchable: false },
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
                groups: () => '',
                type: () => '',
            }
        });
        // Setup edit modal
        setupModal({
            buttonId: '#edit-button',
            modalId: '#edit-modal',
            fields: {
                id: button => button.data('id'),
                name_ar: button => button.data('name_ar'),
                name_en: button => button.data('name_en'),
                teacher_id: button => button.data('teacher_id'),
                grade_id: button => button.data('grade_id'),
                groups: button => button.data('groups'),
                type: button => button.data('type'),
                score: button => button.data('score'),
                conducted_at: button => button.data('conducted_at'),
            }
        });
        // Setup delete modal
        setupModal({
            buttonId: '#delete-button',
            modalId: '#delete-modal',
            fields: {
                id: button => button.data('id'),
                itemToDelete: button => `${button.data('name_ar')} - ${button.data('name_en')}`
            }
        });

        let fields = ['name_ar', 'name_en', 'teacher_id', 'grade_id', 'type', 'score', 'conducted_at'];
        handleFormSubmit('#add-form', fields, '#add-modal', 'modal', '#datatable');
        handleFormSubmit('#edit-form', fields, '#edit-modal', 'modal', '#datatable');
        handleDeletionFormSubmit('#delete-form', '#delete-modal', '#datatable')
        handleDeletionFormSubmit('#delete-selected-form', '#delete-selected-modal', '#datatable')
        fetchMultipleDataByAjax('#add-form #teacher_id', "{{ route('admin.teachers.getGrades', '__ID__') }}", '#add-form #grade_id', 'teacher_id', 'GET')
        fetchMultipleDataByAjax('#add-form #grade_id', "{{ route('admin.fetch.teachers.grade.groups', ['__SECOND_ID__', '__ID__']) }}", '#add-form #groups', 'grade_id', 'GET');
    </script>
@endsection
