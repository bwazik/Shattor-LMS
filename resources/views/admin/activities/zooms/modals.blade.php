<!-- Add Offcanvas -->
<x-offcanvas offcanvasType="add" offcanvasTitle="{{ trans('main.addItem', ['item' => trans('admin/zooms.zoom')]) }}" action="{{ route('admin.zooms.insert') }}">
    <x-select-input context="offcanvas" name="teacher_id" label="{{ trans('main.teacher') }}" :options="$teachers" required/>
    <x-select-input context="offcanvas" name="grade_id" label="{{ trans('main.grade') }}" required/>
    <x-select-input context="offcanvas" name="groups" label="{{ trans('main.group') }}" multiple required/>
    <x-basic-input context="offcanvas" type="text" name="topic_ar" label="{{ trans('main.topic_ar') }}" placeholder="{{ trans('admin/zooms.placeholders.topic_ar') }}" required/>
    <x-basic-input context="offcanvas" type="text" name="topic_en" label="{{ trans('main.topic_en') }}" placeholder="{{ trans('admin/zooms.placeholders.topic_en') }}" required/>
    <x-basic-input context="offcanvas" type="number" name="duration" label="{{ trans('main.duration') }}" placeholder="60" required/>
    <x-basic-input context="offcanvas" type="text" name="start_time" classes="flatpickr-date-time" label="{{ trans('main.start_time') }}" placeholder="YYYY-MM-DD" required/>
    <x-basic-input context="offcanvas" type="password" name="password" label="{{ trans('main.password') }}"/>
</x-offcanvas>
<!-- Edit Offcanvas -->
<x-offcanvas offcanvasType="edit" offcanvasTitle="{{ trans('main.editItem', ['item' => trans('admin/zooms.zoom')]) }}" action="{{ route('admin.zooms.update') }}" id meeting_id>
    <x-select-input context="offcanvas" name="teacher_id" label="{{ trans('main.teacher') }}" :options="$teachers" required/>
    <x-select-input context="offcanvas" name="grade_id" label="{{ trans('main.grade') }}" :options="$grades" required/>
    <x-select-input context="offcanvas" name="groups" label="{{ trans('main.group') }}" :options="$groups" multiple required/>
    <x-basic-input context="offcanvas" type="text" name="topic_ar" label="{{ trans('main.topic_ar') }}" placeholder="{{ trans('admin/zooms.placeholders.topic_ar') }}" required/>
    <x-basic-input context="offcanvas" type="text" name="topic_en" label="{{ trans('main.topic_en') }}" placeholder="{{ trans('admin/zooms.placeholders.topic_en') }}" required/>
    <x-basic-input context="offcanvas" type="number" name="duration" label="{{ trans('main.duration') }}" placeholder="60" required/>
    <x-basic-input context="offcanvas" type="text" name="start_time" classes="flatpickr-date-time" label="{{ trans('main.start_time') }}" placeholder="YYYY-MM-DD" required/>
</x-offcanvas>
<!-- Delete Modal -->
<x-modal modalType="delete" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/zooms.zoom')]) }}"
    action="{{ route('admin.zooms.delete') }}" id meeting_id submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
    @include('partials.delete-modal-body')
</x-modal>
<!-- Delete Selected Modal -->
<x-modal modalType="delete-selected" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/zooms.selectedZooms')]) }}"
    action="{{ route('admin.zooms.deleteSelected') }}" ids submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
    @include('partials.delete-modal-body')
</x-modal>
