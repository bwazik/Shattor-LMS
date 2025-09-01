<!-- Add Modal -->
<x-offcanvas offcanvasType="add" offcanvasTitle="{{ trans('main.addItem', ['item' => trans('admin/groups.group')]) }}" action="{{ route('teacher.groups.insert') }}">
    <x-basic-input context="offcanvas" type="text" name="name_ar" label="{{ trans('main.name_ar') }}" placeholder="{{ trans('admin/groups.placeholders.name_ar') }}" required/>
    <x-basic-input context="offcanvas" type="text" name="name_en" label="{{ trans('main.name_en') }}" placeholder="{{ trans('admin/groups.placeholders.name_en') }}" required/>
    <x-select-input context="offcanvas" name="grade_id" label="{{ trans('main.grade') }}" :options="$grades" required/>
    <x-select-input context="offcanvas" name="day_1" label="{{ trans('admin/groups.day_1') }}" :options="trans('main.weekdays')" required/>
    <x-select-input context="offcanvas" name="day_2" label="{{ trans('admin/groups.day_2') }}" :options="trans('main.weekdays')" required/>
    <x-basic-input context="offcanvas" type="text" name="time" classes="flatpickr-timeB" label="{{ trans('admin/groups.time') }}" placeholder="1:00" required/>
</x-offcanvas>
<!-- Edit Modal -->
<x-offcanvas offcanvasType="edit" offcanvasTitle="{{ trans('main.editItem', ['item' => trans('admin/groups.group')]) }}" action="{{ route('teacher.groups.update') }}" id>
    <x-basic-input context="offcanvas" type="text" name="name_ar" label="{{ trans('main.name_ar') }}" placeholder="{{ trans('admin/groups.placeholders.name_ar') }}" required/>
    <x-basic-input context="offcanvas" type="text" name="name_en" label="{{ trans('main.name_en') }}" placeholder="{{ trans('admin/groups.placeholders.name_en') }}" required/>
    <x-select-input context="offcanvas" name="grade_id" label="{{ trans('main.grade') }}" :options="$grades" required/>
    <x-select-input context="offcanvas" name="day_1" label="{{ trans('admin/groups.day_1') }}" :options="trans('main.weekdays')" required/>
    <x-select-input context="offcanvas" name="day_2" label="{{ trans('admin/groups.day_2') }}" :options="trans('main.weekdays')" required/>
    <x-basic-input context="offcanvas" type="text" name="time" classes="flatpickr-timeB" label="{{ trans('admin/groups.time') }}" placeholder="1:00" required/>
    <x-select-input context="offcanvas" name="is_active" label="{{ trans('main.status') }}" :options="[1 => trans('main.active'), 0 => trans('main.inactive')]"  required/>
</x-offcanvas>
<!-- Delete Modal -->
<x-modal modalType="delete" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/groups.group')]) }}"
    action="{{ route('teacher.groups.delete') }}" id submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
    @include('partials.delete-modal-body')
</x-modal>
<!-- Delete Selected Modal -->
<x-modal modalType="delete-selected" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/groups.selectedGroups')]) }}"
    action="{{ route('teacher.groups.deleteSelected') }}" ids submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
    @include('partials.delete-modal-body')
</x-modal>
<!-- Lessons Modal -->
<x-offcanvas offcanvasType="lessons" offcanvasTitle="{{ trans('admin/lessons.generate') }}" action="{{ route('teacher.groups.generateLessons') }}" id>
    <x-basic-input context="offcanvas" type="text" name="name" label="{{ trans('main.name_ar') }}" placeholder="{{ trans('admin/groups.placeholders.name_ar') }}" disabled/>
    <x-basic-input context="offcanvas" type="text" name="start_date" classes="flatpickr-date" label="{{ trans('main.date') }}" placeholder="YYYY-MM-DD" value="{{ now()->format('Y-m-d') }}" required/>
    <x-basic-input context="offcanvas" type="text" name="end_date" classes="flatpickr-date" label="{{ trans('main.date') }}" placeholder="YYYY-MM-DD" value="{{ now()->addMonth()->format('Y-m-d') }}" required/>
</x-offcanvas>
<!-- Excel import Modal -->
<x-offcanvas offcanvasType="excel-import" offcanvasTitle="{{ trans('main.excelImport') }}" action="{{ route('teacher.groups.importStudents') }}" id>
    <x-basic-input context="offcanvas" type="text" name="name" label="{{ trans('main.name_ar') }}" placeholder="{{ trans('admin/groups.placeholders.name_ar') }}" disabled/>
    <x-basic-input context="offcanvas" type="file" name="file" label="{{ trans('main.files') }}" required/>
</x-offcanvas>