@extends('admin.users.students.profile.master')

@section('profile-content')
    <!-- Sessions -->
    <x-account.recent-sessions :sessions="$sessions" />
    <!-- Sessions -->

    <!-- Authorized Devices -->
    <x-account.authorized-devices :devices="$devices" />
    <!-- Authorized Devices -->

    @if (isAdmin())
        <!-- Delete Modal -->
        <x-modal modalType="delete" modalTitle="{{ trans('main.deleteItem', ['item' => trans('account.device')]) }}"
            action="{{ route('admin.students.profile.deleteDevice', $student->id) }}" id submitColor="danger" submitButton="{{ trans('main.yes_delete') }}">
            @include('partials.delete-modal-body')
        </x-modal>
    @endif
@endsection

@section('profile-js')
    @if (isAdmin())
        <script>
            // Setup delete modal
            setupModal({
                buttonId: '#delete-button',
                modalId: '#delete-modal',
                fields: {
                    id: button => button.data('id'),
                    itemToDelete: button => `${button.data('platform')}`
                }
            });

            handleDeletionFormSubmit('#delete-form', '#delete-modal')
        </script>
    @endif
@endsection
