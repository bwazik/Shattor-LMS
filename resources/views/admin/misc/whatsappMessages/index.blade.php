@extends('layouts.admin.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/whatsappMessages.whatsappMessages'))

@section('content')
    <!-- DataTable with Buttons -->
    <x-datatable
        datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/whatsappMessages.whatsappMessages')]) }}"
        dataToggle="offcanvas" deleteButton>
        <th></th>
        <th class="dt-checkboxes-cell dt-checkboxes-select-all"><input type="checkbox" id="select-all" class="form-check-input">
        </th>
        <th>#</th>
        <th>{{ trans('main.phone') }}</th>
        <th>{{ trans('admin/whatsappMessages.template') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('admin/whatsappMessages.sent_at') }}</th>
        <th>{{ trans('main.details') }}</th>
        <th>{{ trans('admin/whatsappMessages.attempts') }}</th>
        <th>{{ trans('admin/whatsappMessages.errorMessage') }}</th>
        <th>{{ trans('admin/whatsappMessages.queue') }}</th>
        <th>{{ trans('main.created_at') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>
    @include('admin.misc.whatsappMessages.modals')
    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('admin.whatsapp-messages.index') }}", [2, 3],
            [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'selectbox',
                    name: 'selectbox',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'phone',
                    name: 'phone'
                },
                {
                    data: 'template',
                    name: 'template'
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'sent_at',
                    name: 'sent_at',
                    searchable: false
                },
                {
                    data: 'data',
                    name: 'data',
                    searchable: false,
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'attempts',
                    name: 'attempts',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'error_message',
                    name: 'error_message',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'queue',
                    name: 'queue',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    searchable: false
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
        );

        // Setup data modal
        $(document).on('click', '#data-button', function() {
            const rawData = $(this).attr('data-message-data');
            let parsedData;

            try {
                parsedData = JSON.parse(rawData);
            } catch (e) {
                parsedData = {
                    error: 'Invalid data format'
                };
            }

            const $modalBody = $('#data-modal .modal-body');
            $modalBody.empty();

            if (parsedData.error) {
                $modalBody.html('<p class="text-danger">' + parsedData.error + '</p>');
                return;
            }

            let html = '<div class="demo-inline-spacing mt-4">';
            html += '<div class="list-group">';

            $.each(parsedData, function(key, value) {
                html +=
                    '<a href="javascript:void(0);" class="list-group-item list-group-item-action waves-effect">';
                html += '<strong>' + key + ':</strong> ' + value;
                html += '</a>';
            });

            html += '</div></div>';

            $modalBody.html(html);
        });

        // Setup delete modal
        setupModal({
            buttonId: '#delete-button',
            modalId: '#delete-modal',
            fields: {
                id: button => button.data('id'),
                itemToDelete: button => `${button.data('phone')} - ${button.data('phone')}`
            }
        });

        handleDeletionFormSubmit('#delete-form', '#delete-modal', '#datatable')
        handleDeletionFormSubmit('#delete-selected-form', '#delete-selected-modal', '#datatable')
    </script>
@endsection
