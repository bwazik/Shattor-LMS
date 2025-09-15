<!-- Add Modal -->
<x-modal modalType="add" modalSize="modal-lg" modalTitle="{{ trans('main.addItem', ['item' => trans('admin/students.student')]) }}"
    action="{{ route('admin.students.insert') }}">
    <div class="row g-5">
        <x-basic-input context="modal" type="text" name="name_ar" label="{{ trans('main.realName_ar') }}" placeholder="{{ trans('admin/students.placeholders.name_ar') }}" required/>
        <x-basic-input context="modal" type="text" name="name_en" label="{{ trans('main.realName_en') }}" placeholder="{{ trans('admin/students.placeholders.name_en') }}" required/>
        <x-basic-input context="modal" type="text" name="username" label="{{ trans('main.username') }}" placeholder="{{ trans('admin/students.placeholders.username') }}" required/>
        <x-basic-input context="modal" type="password" name="password" label="{{ trans('main.password') }}" required/>
        <x-basic-input context="modal" type="number" name="phone" label="{{ trans('main.phone') }}" placeholder="{{ trans('admin/students.placeholders.phone') }}" required/>
        <x-basic-input context="modal" type="email" name="email" label="{{ trans('main.email') }}" placeholder="{{ trans('admin/students.placeholders.email') }}"/>
        <x-basic-input context="modal" type="text" name="birth_date" classes="flatpickr-date" label="{{ trans('main.birth_date') }}" placeholder="YYYY-MM-DD" value="{{ now()->format('Y-m-d') }}"/>
        <x-select-input context="modal" name="gender" label="{{ trans('main.gender') }}" :options="[1 => trans('main.male'), 2 => trans('main.female')]" required/>
        <x-select-input context="modal" name="grade_id" label="{{ trans('main.grade') }}" :options="$grades"  required/>
        <x-select-input context="modal" name="parent_id" label="{{ trans('main.parent') }}" :options="$parents"/>
        <x-select-input context="modal" name="teachers" label="{{ trans('main.teachers') }}" :options="$teachers" multiple/>
        <x-select-input context="modal" name="groups" label="{{ trans('main.groups') }}" :options="$groups" multiple required/>
        <x-select-input divClasses="col-12" name="specialization" label="{{ trans('main.specialization') }}" :options="[1 => trans('main.scientific'), 2 => trans('main.literary')]" required/>
    </div>
</x-modal>
<!-- Edit Modal -->
<x-modal modalType="edit" modalSize="modal-lg" modalTitle="{{ trans('main.editItem', ['item' => trans('admin/students.student')]) }}"
    action="{{ route('admin.students.update') }}" id>
    <div class="row g-5">
        <x-basic-input context="modal" type="text" name="name_ar" label="{{ trans('main.realName_ar') }}" placeholder="{{ trans('admin/students.placeholders.name_ar') }}" required/>
        <x-basic-input context="modal" type="text" name="name_en" label="{{ trans('main.realName_en') }}" placeholder="{{ trans('admin/students.placeholders.name_en') }}" required/>
        <x-basic-input context="modal" type="text" name="username" label="{{ trans('main.username') }}" placeholder="{{ trans('admin/students.placeholders.username') }}" required/>
        <x-basic-input context="modal" type="password" name="password" label="{{ trans('main.password') }}"/>
        <x-basic-input context="modal" type="number" name="phone" label="{{ trans('main.phone') }}" placeholder="{{ trans('admin/students.placeholders.phone') }}" required/>
        <x-basic-input context="modal" type="email" name="email" label="{{ trans('main.email') }}" placeholder="{{ trans('admin/students.placeholders.email') }}"/>
        <x-basic-input context="modal" type="text" name="birth_date" classes="flatpickr-date" label="{{ trans('main.birth_date') }}" placeholder="YYYY-MM-DD"/>
        <x-select-input context="modal" name="gender" label="{{ trans('main.gender') }}" :options="[1 => trans('main.male'), 2 => trans('main.female')]" required/>
        <x-select-input context="modal" name="grade_id" label="{{ trans('main.grade') }}" :options="$grades"  required/>
        <x-select-input context="modal" name="parent_id" label="{{ trans('main.parent') }}" :options="$parents"/>
        <x-select-input context="modal" name="teachers" label="{{ trans('main.teachers') }}" :options="$teachers" multiple required/>
        <x-select-input context="modal" name="groups" label="{{ trans('main.groups') }}" :options="$groups" multiple/>
        <x-select-input context="modal" name="is_active" label="{{ trans('main.status') }}" :options="[1 => trans('main.active'), 0 => trans('main.inactive')]" required/>
        <x-select-input context="modal" name="specialization" label="{{ trans('main.specialization') }}" :options="[1 => trans('main.scientific'), 2 => trans('main.literary')]" required/>
    </div>
</x-modal>
<!-- Delete Modal -->
<x-modal modalType="delete" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/students.student')]) }}"
    action="{{ route('admin.students.delete') }}" id submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
    @include('partials.delete-modal-body')
</x-modal>
<!-- Delete Selected Modal -->
<x-modal modalType="delete-selected" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/students.selectedStudents')]) }}"
    action="{{ route('admin.students.deleteSelected') }}" submitColor="danger" ids submitButton="{{ trans('main.yes_delete') }}">
    @include('partials.delete-modal-body')
</x-modal>
<!-- Archive Modal -->
<x-modal modalType="archive" modalTitle="{{ trans('main.archiveItem', ['item' => trans('admin/students.student')]) }}"
    action="{{ route('admin.students.archive') }}" id submitButton="{{ trans('main.yes_archive') }}">
    @include('partials.archive-modal-body')
</x-modal>
<!-- Archive Selected Modal -->
<x-modal modalType="archive-selected" modalTitle="{{ trans('main.archiveItem', ['item' => trans('admin/students.selectedStudents')]) }}"
    action="{{ route('admin.students.archiveSelected') }}" ids submitButton="{{ trans('main.yes_archive') }}">
    @include('partials.archive-modal-body')
</x-modal>
