<!-- Restore Modal -->
<x-modal modalType="restore" modalTitle="{{ trans('main.restoreItem', ['item' => trans('admin/students.student')]) }}"
    action="{{ route('teacher.students.restore') }}" id submitButton="{{ trans('main.yes_restore') }}">
    @include('partials.restore-modal-body')
</x-modal>
<!-- Restore Selected Modal -->
<x-modal modalType="restore-selected" modalTitle="{{ trans('main.restoreItem', ['item' => trans('admin/students.selectedStudents')]) }}"
    action="{{ route('teacher.students.restoreSelected') }}" ids submitButton="{{ trans('main.yes_restore') }}">
    @include('partials.restore-modal-body')
</x-modal>
