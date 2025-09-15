@extends('layouts.teacher.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/students.students'))

@section('content')
    <!-- DataTable with Buttons -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/students.students')]) }} - {{ $group->grade->name }} - {!! $group->name !!}"
        dataToggle="modal" deleteButton addButton="{{ trans('main.addItem', ['item' => trans('admin/students.student')]) }}">
        <th></th>
        <th class="dt-checkboxes-cell dt-checkboxes-select-all"><input type="checkbox" id="select-all" class="form-check-input">
        </th>
        <th>#</th>
        <th>{{ trans('main.name') }}</th>
        <th>{{ trans('main.username') }}</th>
        <th>{{ trans('main.phone') }}</th>
        <th>{{ trans('main.parent') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>
    <!-- Add Modal -->
    <x-modal modalType="add" modalSize="modal-lg" modalTitle="{{ trans('main.addItem', ['item' => trans('admin/students.student')]) }}"
        action="{{ route('teacher.students.insert') }}">
        <div class="row g-5">
            <x-basic-input context="modal" type="text" name="name_ar" label="{{ trans('main.realName_ar') }}" placeholder="{{ trans('admin/students.placeholders.name_ar') }}" required/>
            <x-basic-input context="modal" type="text" name="name_en" label="{{ trans('main.realName_en') }}" placeholder="{{ trans('admin/students.placeholders.name_en') }}" required/>
            <x-basic-input context="modal" type="text" name="username" label="{{ trans('main.username') }}" placeholder="{{ trans('admin/students.placeholders.username') }}" required/>
            <x-basic-input context="modal" type="password" name="password" label="{{ trans('main.password') }}" required/>
            <x-basic-input context="modal" type="number" name="phone" label="{{ trans('main.phone') }}" placeholder="{{ trans('admin/students.placeholders.phone') }}" required/>
            <x-basic-input context="modal" type="email" name="email" label="{{ trans('main.email') }}" placeholder="{{ trans('admin/students.placeholders.email') }}"/>
            <x-basic-input context="modal" type="text" name="birth_date" classes="flatpickr-date" label="{{ trans('main.birth_date') }}" placeholder="YYYY-MM-DD" value="{{ now()->format('Y-m-d') }}"/>
            <x-select-input context="modal" name="gender" label="{{ trans('main.gender') }}" :options="[1 => trans('main.male'), 2 => trans('main.female')]" required/>
            <x-select-input context="modal" name="parent_id" label="{{ trans('main.parent') }}" :options="$parents"/>
            <x-select-input context="modal" name="specialization" label="{{ trans('main.specialization') }}" :options="[1 => trans('main.scientific'), 2 => trans('main.literary')]" required/>
            <x-select-input divClasses="d-none" name="grade_id" label="{{ trans('main.grade') }}" :options="$grades" required/>
            <x-select-input divClasses="d-none" name="groups" label="{{ trans('main.groups') }}" :options="$groups" multiple required/>
        </div>
    </x-modal>
    <!-- Edit Modal -->
    <x-modal modalType="edit" modalSize="modal-lg" modalTitle="{{ trans('main.editItem', ['item' => trans('admin/students.student')]) }}"
        action="{{ route('teacher.students.update') }}" id>
        <div class="row g-5">
            <x-basic-input context="modal" type="text" name="name_ar" label="{{ trans('main.realName_ar') }}" placeholder="{{ trans('admin/students.placeholders.name_ar') }}" required/>
            <x-basic-input context="modal" type="text" name="name_en" label="{{ trans('main.realName_en') }}" placeholder="{{ trans('admin/students.placeholders.name_en') }}" required/>
            <x-basic-input context="modal" type="text" name="username" label="{{ trans('main.username') }}" placeholder="{{ trans('admin/students.placeholders.username') }}" required/>
            <x-basic-input context="modal" type="password" name="password" label="{{ trans('main.password') }}"/>
            <x-basic-input context="modal" type="number" name="phone" label="{{ trans('main.phone') }}" placeholder="{{ trans('admin/students.placeholders.phone') }}" required/>
            <x-basic-input context="modal" type="email" name="email" label="{{ trans('main.email') }}" placeholder="{{ trans('admin/students.placeholders.email') }}"/>
            <x-basic-input context="modal" type="text" name="birth_date" classes="flatpickr-date" label="{{ trans('main.birth_date') }}" placeholder="YYYY-MM-DD"/>
            <x-select-input context="modal" name="gender" label="{{ trans('main.gender') }}" :options="[1 => trans('main.male'), 2 => trans('main.female')]" required/>
            <x-select-input context="modal" name="parent_id" label="{{ trans('main.parent') }}" :options="$parents"/>
            <x-select-input context="modal" name="is_active" label="{{ trans('main.status') }}" :options="[1 => trans('main.active'), 0 => trans('main.inactive')]" required/>
            <x-select-input divClasses="col-12" name="specialization" label="{{ trans('main.specialization') }}" :options="[1 => trans('main.scientific'), 2 => trans('main.literary')]" required/>
            <x-select-input divClasses="d-none" name="grade_id" label="{{ trans('main.grade') }}" :options="$grades" required/>
            <x-select-input divClasses="d-none" name="groups" label="{{ trans('main.groups') }}" :options="$groups" multiple/>
        </div>
    </x-modal>
    <!-- Delete Modal -->
    <x-modal modalType="delete" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/students.student')]) }}"
        action="{{ route('teacher.students.delete') }}" id submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
        @include('partials.delete-modal-body')
    </x-modal>
    <!-- Delete Selected Modal -->
    <x-modal modalType="delete-selected" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/students.selectedStudents')]) }}"
        action="{{ route('teacher.students.deleteSelected') }}" submitColor="danger" ids submitButton="{{ trans('main.yes_delete') }}">
        @include('partials.delete-modal-body')
    </x-modal>
    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('teacher.groups.students', $group->uuid) }}", [2, 3, 4, 5, 6, 7],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'selectbox', name: 'selectbox', orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'details', name: 'details' },
                { data: 'username', name: 'username' },
                { data: 'phone', name: 'phone' },
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
                grade_id: () => '{{ $group->grade->id }}',
                parent_id: () => '',
                specialization: () => '',
                groups: () => '{{ $group->uuid }}',
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
                specialization: button => button.data('specialization'),
                groups: button => button.data('groups'),
                is_active: button => button.data('is_active'),
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

        let fields = ['name_ar', 'name_en', 'username', 'password', 'phone', 'email', 'birth_date', 'gender', 'grade_id', 'specialization', 'groups'];
        handleFormSubmit('#add-form', fields, '#add-modal', 'modal', '#datatable');
        handleFormSubmit('#edit-form', fields, '#edit-modal', 'modal', '#datatable');
        handleDeletionFormSubmit('#delete-form', '#delete-modal', '#datatable')
        handleDeletionFormSubmit('#delete-selected-form', '#delete-selected-modal', '#datatable')
        generateRandomUsername('s');
    </script>
@endsection
