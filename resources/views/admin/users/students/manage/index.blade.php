@extends('layouts.admin.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/students.students'))

@section('content')
    @include('admin.users.students.manage.statistics')
    <!-- DataTable with Buttons -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/students.students')]) }}"
        dataToggle="modal" deleteButton archiveButton addButton="{{ trans('main.addItem', ['item' => trans('admin/students.student')]) }}">
        <th></th>
        <th class="dt-checkboxes-cell dt-checkboxes-select-all"><input type="checkbox" id="select-all" class="form-check-input">
        </th>
        <th>#</th>
        <th>{{ trans('main.name') }}</th>
        <th>{{ trans('main.username') }}</th>
        <th>{{ trans('main.phone') }}</th>
        <th>{{ trans('main.grade') }}</th>
        <th>{{ trans('main.parent') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>
    @include('admin.users.students.manage.modals')
    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('admin.students.index') }}", [2, 3, 4, 5, 6, 7, 8],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'selectbox', name: 'selectbox', orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'details', name: 'details' },
                { data: 'username', name: 'username' },
                { data: 'phone', name: 'phone' },
                { data: 'grade_id', name: 'grade_id' },
                { data: 'parent_id', name: 'parent_id' },
                { data: 'is_active', name: 'is_active' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
        );

        // Setup add modal
        setupModal({
            buttonId: '#add-button',
            modalId: '#add-modal',
            fields: {
                name_en: () => 'default',
                password: () => generateStrongPassword(12),
                gender: () => 1,
                grade_id: () => '',
                parent_id: () => '',
                teachers: () => 1,
                groups: () => '',
                specialization: () => 1,
            },
        });
        // Setup edit modal
        setupModal({
            buttonId: '#edit-button',
            modalId: '#edit-modal',
            fields: {
                id: button => button.data('id'),
                name_ar: button => button.data('name_ar'),
                name_en: button => button.data('name_en'),
                username: button => button.data('username'),
                password: button => button.data('password'),
                phone: button => button.data('phone'),
                email: button => button.data('email'),
                birth_date: button => button.data('birth_date'),
                gender: button => button.data('gender'),
                grade_id: button => button.data('grade_id'),
                parent_id: button => button.data('parent_id'),
                teachers: button => button.data('teachers'),
                groups: button => button.data('groups'),
                is_active: button => button.data('is_active'),
                specialization: button => button.data('specialization'),
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
        // Setup archive modal
        setupModal({
            buttonId: '#archive-button',
            modalId: '#archive-modal',
            fields: {
                id: button => button.data('id'),
                itemToArchive: button => `${button.data('name_ar')} - ${button.data('name_en')}`
            }
        });

        let fields = ['name_ar', 'name_en', 'username', 'password', 'phone', 'email', 'birth_date', 'gender', 'grade_id', 'teachers', 'groups', 'specialization'];
        handleFormSubmit('#add-form', fields, '#add-modal', 'modal', '#datatable');
        handleFormSubmit('#edit-form', fields, '#edit-modal', 'modal', '#datatable');
        handleDeletionFormSubmit('#delete-form', '#delete-modal', '#datatable')
        handleDeletionFormSubmit('#archive-form', '#archive-modal', '#datatable')
        handleDeletionFormSubmit('#delete-selected-form', '#delete-selected-modal', '#datatable')
        handleDeletionFormSubmit('#archive-selected-form', '#archive-selected-modal', '#datatable')
        fetchMultipleDataByAjax('#add-form #teachers', "{{ route('admin.teachers.groups') }}", '#add-form #groups', 'teachers')

        generateRandomUsername('s');
    </script>
@endsection
