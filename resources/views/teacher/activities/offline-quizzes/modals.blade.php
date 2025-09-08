<!-- Add Offcanvas -->
<x-modal modalType="add" modalSize="modal-lg" modalTitle="{{ trans('main.addItem', ['item' => trans('admin/offlineQuizzes.offlineQuiz')]) }}" action="{{ route('teacher.offline-quizzes.insert') }}">
    <div class="row g-5">
        <x-basic-input context="modal" type="text" name="name_ar" label="{{ trans('main.name_ar') }}" placeholder="{{ trans('admin/offlineQuizzes.placeholders.name_ar') }}" required/>
        <x-basic-input context="modal" type="text" name="name_en" label="{{ trans('main.name_en') }}" placeholder="{{ trans('admin/offlineQuizzes.placeholders.name_en') }}" required/>
        <x-select-input context="modal" name="grade_id" label="{{ trans('main.grade') }}" :options="$grades" required/>
        <x-select-input context="modal" name="groups" label="{{ trans('main.group') }}" multiple required/>
        <x-select-input context="modal" name="type" label="{{ trans('main.type') }}" :options="[1 => trans('admin/offlineQuizzes.quiz'), 2 => trans('admin/offlineQuizzes.exam')]" required/>
        <x-basic-input context="modal" type="number" name="score" label="{{ trans('main.score') }}" placeholder="100" required/>
        <x-basic-input divClasses="col-12" type="text" name="conducted_at" classes="flatpickr-date" label="{{ trans('main.conducted_at') }}" placeholder="YYYY-MM-DD" value="{{ now()->format('Y-m-d') }}"/>
    </div>
</x-modal>
<!-- Edit Offcanvas -->
<x-modal modalType="edit" modalSize="modal-lg" modalTitle="{{ trans('main.editItem', ['item' => trans('admin/offlineQuizzes.offlineQuiz')]) }}" action="{{ route('teacher.offline-quizzes.update') }}" id>
    <div class="row g-5">
        <x-basic-input context="modal" type="text" name="name_ar" label="{{ trans('main.name_ar') }}" placeholder="{{ trans('admin/quizzes.placeholders.name_ar') }}" required/>
        <x-basic-input context="modal" type="text" name="name_en" label="{{ trans('main.name_en') }}" placeholder="{{ trans('admin/quizzes.placeholders.name_en') }}" required/>
        <x-select-input context="modal" name="grade_id" label="{{ trans('main.grade') }}" :options="$grades" required/>
        <x-select-input context="modal" name="groups" label="{{ trans('main.group') }}" :options="$groups" multiple required/>
        <x-select-input context="modal" name="type" label="{{ trans('main.type') }}" :options="[1 => trans('admin/offlineQuizzes.quiz'), 2 => trans('admin/offlineQuizzes.exam')]" required/>
        <x-basic-input context="modal" type="number" name="score" label="{{ trans('main.score') }}" placeholder="100" required/>
        <x-basic-input divClasses="col-12" type="text" name="conducted_at" classes="flatpickr-date" label="{{ trans('main.conducted_at') }}" placeholder="YYYY-MM-DD" value="{{ now()->format('Y-m-d') }}"/>
    </div>
</x-modal>
<!-- Delete Modal -->
<x-modal modalType="delete" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/offlineQuizzes.offlineQuiz')]) }}"
    action="{{ route('teacher.offline-quizzes.delete') }}" id submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
    @include('partials.delete-modal-body')
</x-modal>
<!-- Delete Selected Modal -->
<x-modal modalType="delete-selected" modalTitle="{{ trans('main.deleteItem', ['item' => trans('admin/offlineQuizzes.selectedOfflineQuizzes')]) }}"
    action="{{ route('teacher.offline-quizzes.deleteSelected') }}" ids submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
    @include('partials.delete-modal-body')
</x-modal>
