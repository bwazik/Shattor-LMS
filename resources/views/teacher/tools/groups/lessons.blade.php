@extends('layouts.teacher.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/lessons.lessons'))

@section('content')
    <!-- DataTable with Buttons -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/lessons.lessons')]) }} - {{ $group->grade->name }} - {!! $group->name !!}"
        dataToggle="offcanvas" deleteButton addButton="{{ trans('main.addItem', ['item' => trans('admin/lessons.lesson')]) }}">
        <th></th>
        <th class="dt-checkboxes-cell dt-checkboxes-select-all"><input type="checkbox" id="select-all" class="form-check-input"></th>
        <th>#</th>
        <th>{{ trans('main.title') }}</th>
        <th>{{ trans('main.attendance') }}</th>
        <th>{{ trans('main.date') }}</th>
        <th>{{ trans('main.time') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>
    <!-- Add Modal -->
    <x-offcanvas offcanvasType="add" offcanvasTitle="{{ trans('main.addItem', ['item' => trans('admin/lessons.lesson')]) }}" action="{{ route('teacher.lessons.insert') }}">
        <x-basic-input context="offcanvas" type="text" name="title_ar" label="{{ trans('main.title_ar') }}" placeholder="{{ trans('admin/lessons.placeholders.title_ar') }}" required/>
        <x-basic-input context="offcanvas" type="text" name="title_en" label="{{ trans('main.title_en') }}" placeholder="{{ trans('admin/lessons.placeholders.title_en') }}" required/>
        <x-basic-input context="offcanvas" type="text" name="date" classes="flatpickr-date" label="{{ trans('main.date') }}" placeholder="YYYY-MM-DD" value="{{ now()->format('Y-m-d') }}" required/>
        <x-basic-input context="offcanvas" type="text" name="time" classes="flatpickr-timeB" label="{{ trans('main.time') }}" placeholder="1:00" required/>
        <x-select-input divClasses="col-12" name="status" label="{{ trans('main.status') }}" :options="[1 => trans('main.scheduled'), 2 => trans('main.completed'), 3 => trans('main.canceled')]"  required/>
        <x-basic-input divClasses="d-none" type="text" name="group_id" label="{{ trans('main.group') }}" required/>
    </x-offcanvas>
    <!-- Edit Modal -->
    <x-offcanvas offcanvasType="edit" offcanvasTitle="{{ trans('main.editItem', ['item' => trans('admin/lessons.lesson')]) }}" action="{{ route('teacher.lessons.update') }}" id>
        <x-basic-input context="offcanvas" type="text" name="title_ar" label="{{ trans('main.title_ar') }}" placeholder="{{ trans('admin/lessons.placeholders.title_ar') }}" required/>
        <x-basic-input context="offcanvas" type="text" name="title_en" label="{{ trans('main.title_en') }}" placeholder="{{ trans('admin/lessons.placeholders.title_en') }}" required/>
        <x-basic-input context="offcanvas" type="text" name="date" classes="flatpickr-date" label="{{ trans('main.date') }}" placeholder="YYYY-MM-DD" value="{{ now()->format('Y-m-d') }}" required/>
        <x-basic-input context="offcanvas" type="text" name="time" classes="flatpickr-timeB" label="{{ trans('main.time') }}" placeholder="1:00" required/>
        <x-select-input divClasses="col-12" name="status" label="{{ trans('main.status') }}" :options="[1 => trans('main.scheduled'), 2 => trans('main.completed'), 3 => trans('main.canceled')]"  required/>
        <x-basic-input divClasses="d-none" type="text" name="group_id" label="{{ trans('main.group') }}" required/>
    </x-offcanvas>
    <!-- Delete Modal -->
    <x-modal modalType="delete" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/lessons.lesson')]) }}"
        action="{{ route('teacher.lessons.delete') }}" id submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
        @include('partials.delete-modal-body')
    </x-modal>
    <!-- Delete Selected Modal -->
    <x-modal modalType="delete-selected" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/lessons.selectedLessons')]) }}"
        action="{{ route('teacher.lessons.deleteSelected') }}" ids submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
        @include('partials.delete-modal-body')
    </x-modal>

    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('teacher.groups.lessons', $group->uuid) }}", [2, 3, 4, 5, 6, 7],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'selectbox', name: 'selectbox', orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'title', name: 'title' },
                { data: 'attendances', name: 'attendances' },
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
                status: () => '',
                group_id: () => '{{ $group->uuid }}',
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
    </script>
@endsection
