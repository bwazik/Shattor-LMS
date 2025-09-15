<!-- Add Modal -->
<x-offcanvas offcanvasType="add" offcanvasTitle="{{ trans('main.addItem', ['item' => trans('admin/fees.fee')]) }}"
    action="{{ route('admin.fees.insert') }}">
    <x-basic-input context="offcanvas" type="text" name="name_ar" label="{{ trans('main.name_ar') }}" placeholder="{{ trans('admin/fees.placeholders.name_ar') }}" required/>
    <x-basic-input context="offcanvas" type="text" name="name_en" label="{{ trans('main.name_en') }}" placeholder="{{ trans('admin/fees.placeholders.name_en') }}" required/>
    <x-basic-input context="offcanvas" price type="number" name="amount" label="{{ trans('main.amount') }}" placeholder="0.00" required/>
    <x-select-input context="offcanvas" name="teacher_id" label="{{ trans('main.teacher') }}" :options="$teachers"  required/>
    <x-select-input context="offcanvas" name="grade_id" label="{{ trans('main.grade') }}" required/>
    <x-select-input context="offcanvas" name="specialization" label="{{ trans('main.specialization') }}" :options="[1 => trans('main.scientific'), 2 => trans('main.literary')]"/>
    <x-select-input context="offcanvas" name="frequency" label="{{ trans('main.frequency') }}" :options="[1 => trans('main.one_time'), 2 => trans('main.monthly'),  3 => trans('main.custom')]" required/>
</x-offcanvas>
<!-- Edit Modal -->
<x-offcanvas offcanvasType="edit" offcanvasTitle="{{ trans('main.editItem', ['item' => trans('admin/fees.fee')]) }}"
    action="{{ route('admin.fees.update') }}" id>
    <x-basic-input context="offcanvas" type="text" name="name_ar" label="{{ trans('main.name_ar') }}" placeholder="{{ trans('admin/fees.placeholders.name_ar') }}" required/>
    <x-basic-input context="offcanvas" type="text" name="name_en" label="{{ trans('main.name_en') }}" placeholder="{{ trans('admin/fees.placeholders.name_en') }}" required/>
    <x-basic-input context="offcanvas" price type="number" name="amount" label="{{ trans('main.amount') }}" placeholder="0.00" required/>
    <x-select-input context="offcanvas" name="teacher_id" label="{{ trans('main.teacher') }}" :options="$teachers"  required/>
    <x-select-input context="offcanvas" name="grade_id" label="{{ trans('main.grade') }}" :options="$grades" required/>
    <x-select-input context="offcanvas" name="specialization" label="{{ trans('main.specialization') }}" :options="[1 => trans('main.scientific'), 2 => trans('main.literary')]"/>
    <x-select-input context="offcanvas" name="frequency" label="{{ trans('main.frequency') }}" :options="[1 => trans('main.one_time'), 2 => trans('main.monthly'),  3 => trans('main.custom')]" required/>
</x-offcanvas>
<!-- Delete Modal -->
<x-modal modalType="delete" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/fees.fee')]) }}"
    action="{{ route('admin.fees.delete') }}" id submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
    @include('partials.delete-modal-body')
</x-modal>
<!-- Delete Selected Modal -->
<x-modal modalType="delete-selected" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/fees.selectedFees')]) }}"
    action="{{ route('admin.fees.deleteSelected') }}" submitColor="danger" ids submitButton="{{ trans('main.yes_delete') }}">
    @include('partials.delete-modal-body')
</x-modal>
