<!-- Core JS -->
<!-- build:js assets/vendor/js/core.js -->
<script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/node-waves/node-waves.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
<script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
<!-- endbuild -->

<script>
    window.translations = {
        items: @json(trans('main.items')),
        errorMessage: @json(trans('main.errorMessage')),
        tooManyRequestsMessage: @json(trans('main.tooManyRequestsMessage')),
        select_option: @json(trans('main.select_option')),
        datatable: {
            search: @json(trans('main.datatable.search')),
            empty_table: @json(trans('main.datatable.empty_table')),
            zero_records: @json(trans('main.datatable.zero_records')),
            length_menu: @json(trans('main.datatable.length_menu')),
            info: @json(trans('main.datatable.info')),
            info_empty: @json(trans('main.datatable.info_empty')),
            info_filtered: @json(trans('main.datatable.info_filtered')),
        },
        detailsOf: @json(trans('main.detailsOf')),
        weekdays: {
            ar: @json(trans('main.weekdays', [], 'ar')),
            en: @json(trans('main.weekdays', [], 'en')),
        },
        currentLocale: "{{ app()->getLocale() }}",
        processing: @json(trans('main.processing')),
        noFileUploaded: @json(trans('toasts.noFileUploaded')),
        invalidFileType: @json(trans('validation.mimes')),
        maxFileSize: @json(trans('validation.max.file')),
        linkCopiedSuccess: @json(trans('toasts.linkCopiedSuccess')),
        linkCopiedError: @json(trans('toasts.linkCopiedError')),
        share: @json(trans('main.share')),
    };
</script>

<!-- Vendors JS -->
<script src="{{ asset('assets/vendor/libs/toastr/toastr.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script src="{{ asset('assets/vendor/js/custom.js') }}"></script>

<!-- Main JS -->
<script src="{{ asset('assets/js/main.js') }}"></script>

<!-- Page JS -->
@yield('page-js')
