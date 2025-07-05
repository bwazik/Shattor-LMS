@extends('layouts.student.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/compensatories.compensatories'))

@section('content')
    <!-- DataTable with Buttons -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/compensatories.compensatories')]) }}"
        dataToggle="offcanvas" addButton="{{ trans('main.addItem', ['item' => trans('admin/compensatories.compensatory')]) }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('admin/compensatories.original_lesson_group') }}</th>
        <th>{{ trans('admin/compensatories.original_lesson') }}</th>
        <th>{{ trans('admin/compensatories.makeup_lesson_group') }}</th>
        <th>{{ trans('admin/compensatories.makeup_lesson') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>
    @include('student.activities.compensatories.modals')
    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('student.compensatories.index') }}", [2, 3, 4, 5, 6],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
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
                original_lesson_group_id: () => '',
                original_lesson_id: () => '',
                makeup_lesson_group_id: () => '',
                makeup_lesson_id: () => '',
            }
        });
        // Setup edit modal
        setupModal({
            buttonId: '#edit-button',
            modalId: '#edit-modal',
            fields: {
                id: button => button.data('id'),
                teacher: button => button.data('teacher'),
                original_lesson_group: button => button.data('original_lesson_group'),
                original_lesson: button => button.data('original_lesson'),
                makeup_lesson_group: button => button.data('makeup_lesson_group'),
                makeup_lesson: button => button.data('makeup_lesson'),
                reason: button => button.data('reason'),
            }
        });
        // Setup delete modal
        setupModal({
            buttonId: '#delete-button',
            modalId: '#delete-modal',
            fields: {
                id: button => button.data('id'),
                itemToDelete: button => `${button.data('reason')}`
            }
        });

        let insertFields = ['teacher_id', 'original_lesson_group_id', 'original_lesson_id', 'makeup_lesson_group_id', 'makeup_lesson_id', 'reason'];
        let updateFields = ['reason'];
        handleFormSubmit('#add-form', insertFields, '#add-modal', 'offcanvas', '#datatable');
        handleFormSubmit('#edit-form', updateFields, '#edit-modal', 'offcanvas', '#datatable');
        handleDeletionFormSubmit('#delete-form', '#delete-modal', '#datatable')
        fetchMultipleDataByAjax('#add-form #teacher_id', "{{ route('student.fetch.teachers.groups', '__ID__') }}",
            '#add-form #original_lesson_group_id', 'teacher_id', 'GET');
        fetchMultipleDataByAjax('#add-form #original_lesson_group_id', "{{ route('student.fetch.groups.lessons', '__ID__') }}",
            '#add-form #original_lesson_id', 'original_lesson_group_id', 'GET');
        fetchMultipleDataByAjax('#add-form #teacher_id', "{{ route('student.fetch.teachers.groups', '__ID__') }}?type=makeup",
            '#add-form #makeup_lesson_group_id', 'teacher_id', 'GET');
        fetchMultipleDataByAjax('#add-form #makeup_lesson_group_id', "{{ route('student.fetch.groups.lessons', '__ID__') }}?type=makeup",
            '#add-form #makeup_lesson_id', 'makeup_lesson_group_id', 'GET');
    </script>
@endsection
