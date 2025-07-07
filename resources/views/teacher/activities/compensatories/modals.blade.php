<!-- Add Modal -->
<x-offcanvas offcanvasType="add" offcanvasTitle="{{ trans('main.addItem', ['item' => trans('admin/compensatories.compensatory')]) }}"
    action="{{ route('teacher.compensatories.insert') }}">
    <x-select-input context="offcanvas" name="student_id" label="{{ trans('main.student') }}" :options="$students" required/>
    <x-select-input context="offcanvas" name="original_lesson_group_id" label="{{ trans('admin/compensatories.original_lesson_group') }}" required/>
    <x-select-input context="offcanvas" name="original_lesson_id" label="{{ trans('admin/compensatories.original_lesson') }}" required/>
    <x-select-input context="offcanvas" name="makeup_lesson_group_id" label="{{ trans('admin/compensatories.makeup_lesson_group') }}" required/>
    <x-select-input context="offcanvas" name="makeup_lesson_id" label="{{ trans('admin/compensatories.makeup_lesson') }}" required/>
    <x-text-area context="offcanvas" name="reason" label="{{ trans('main.reason') }}" placeholder="{{ trans('admin/compensatories.placeholders.reason') }}" maxlength=1000 required/>
</x-offcanvas>
<!-- Delete Modal -->
<x-modal modalType="delete" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/compensatories.compensatory')]) }}"
    action="{{ route('teacher.compensatories.delete') }}" id submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
    @include('partials.delete-modal-body')
</x-modal>
<!-- Accept Modal -->
<x-modal modalType="accept" modalTitle="{{ trans('main.acceptItem', ['item' => trans('admin/compensatories.compensatory')]) }}"
    action="{{ route('teacher.compensatories.accept') }}" id submitColor="success" submitButton="{{ trans('main.yes_accept') }}">
    @include('partials.accept-modal-body')
</x-modal>
<!-- Reject Modal -->
<x-modal modalType="reject" modalTitle="{{ trans('main.rejectItem', ['item' => trans('admin/compensatories.compensatory')]) }}"
    action="{{ route('teacher.compensatories.reject') }}" id submitColor="danger" submitButton="{{ trans('main.yes_reject') }}">
    @include('partials.reject-modal-body')
</x-modal>
<!-- Accept Selected Modal -->
<x-modal modalType="accept-selected" modalTitle="{{ trans('main.acceptItem', ['item' => trans('admin/compensatories.selectedCompensatories')]) }}"
    action="{{ route('teacher.compensatories.acceptSelected') }}" submitColor="success" ids submitButton="{{ trans('main.yes_accept') }}">
    @include('partials.accept-modal-body')
</x-modal>
<!-- Reject Selected Modal -->
<x-modal modalType="reject-selected" modalTitle="{{ trans('main.rejectItem', ['item' => trans('admin/compensatories.selectedCompensatories')]) }}"
    action="{{ route('teacher.compensatories.rejectSelected') }}" submitColor="danger" ids submitButton="{{ trans('main.yes_reject') }}">
    @include('partials.reject-modal-body')
</x-modal>
