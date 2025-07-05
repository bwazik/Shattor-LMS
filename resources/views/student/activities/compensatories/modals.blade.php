<!-- Add Modal -->
<x-offcanvas offcanvasType="add" offcanvasTitle="{{ trans('main.addItem', ['item' => trans('admin/compensatories.compensatory')]) }}"
    action="{{ route('student.compensatories.insert') }}">
    <x-select-input context="offcanvas" name="teacher_id" label="{{ trans('main.teacher') }}" :options="$teachers" required/>
    <x-select-input context="offcanvas" name="original_lesson_group_id" label="{{ trans('admin/compensatories.original_lesson_group') }}" required/>
    <x-select-input context="offcanvas" name="original_lesson_id" label="{{ trans('admin/compensatories.original_lesson') }}" required/>
    <x-select-input context="offcanvas" name="makeup_lesson_group_id" label="{{ trans('admin/compensatories.makeup_lesson_group') }}" required/>
    <x-select-input context="offcanvas" name="makeup_lesson_id" label="{{ trans('admin/compensatories.makeup_lesson') }}" required/>
    <x-text-area context="offcanvas" name="reason" label="{{ trans('main.reason') }}" placeholder="{{ trans('admin/compensatories.placeholders.reason') }}" maxlength=1000 required/>
</x-offcanvas>
<!-- Edit Modal -->
<x-offcanvas offcanvasType="edit" offcanvasTitle="{{ trans('main.editItem', ['item' => trans('admin/compensatories.compensatory')]) }}"
    action="{{ route('student.compensatories.update') }}" id>
    <x-basic-input context="offcanvas" type="text" name="teacher" label="{{ trans('main.teacher') }}" placeholder="{{ trans('main.teacher') }}" value="" disabled/>
    <x-basic-input context="offcanvas" type="text" name="original_lesson_group" label="{{ trans('admin/compensatories.original_lesson_group') }}" placeholder="{{ trans('admin/compensatories.original_lesson_group') }}" disabled/>
    <x-basic-input context="offcanvas" type="text" name="original_lesson" label="{{ trans('admin/compensatories.original_lesson') }}" placeholder="{{ trans('admin/compensatories.original_lesson') }}" disabled/>
    <x-basic-input context="offcanvas" type="text" name="makeup_lesson_group" label="{{ trans('admin/compensatories.makeup_lesson_group') }}" placeholder="{{ trans('admin/compensatories.makeup_lesson_group') }}" disabled/>
    <x-basic-input context="offcanvas" type="text" name="makeup_lesson" label="{{ trans('admin/compensatories.makeup_lesson') }}" placeholder="{{ trans('admin/compensatories.makeup_lesson') }}" disabled/>
    <x-text-area context="offcanvas" name="reason" label="{{ trans('main.reason') }}" placeholder="{{ trans('admin/compensatories.placeholders.reason') }}" maxlength=1000 required/>
</x-offcanvas>
<!-- Delete Modal -->
<x-modal modalType="delete" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/compensatories.compensatory')]) }}"
    action="{{ route('student.compensatories.delete') }}" id submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
    @include('partials.delete-modal-body')
</x-modal>