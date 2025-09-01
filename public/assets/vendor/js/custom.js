let errorMessage = window.translations.errorMessage || 'An unexpected error occurred. Please try again later!';
let tooManyRequestsMessage = window.translations.tooManyRequestsMessage || 'You have exceeded the maximum number of requests. Please try again later!';
let submitButton;
const weekdays = window.translations.weekdays;
const currentLocale = window.translations.currentLocale;
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            boundary: document.body
        });
    });
});

$('#invoices-datatable, #lessons-datatable, #compensatory-students-datatable, #compensated-students-datatable, #present-late-students-datatable, #datatable').on('draw.dt', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            boundary: document.body
        });
    });
});

// Function to toggle all checkboxes and update the main checkbox state
function toggleCheckboxes(className, mainCheckbox) {
    $(`.${className}`).prop('checked', $(mainCheckbox).prop('checked'));
    updateMainCheckboxState(className);
}

// Function to update the main checkbox state based on individual checkboxes
function updateMainCheckboxState(className) {
    const $checkboxes = $(`.${className}`);
    const $mainCheckbox = $('#select-all');

    const allChecked = $checkboxes.length > 0 && $checkboxes.length === $checkboxes.filter(':checked')
        .length;
    const anyChecked = $checkboxes.filter(':checked').length > 0;

    if (allChecked) {
        $mainCheckbox.prop('checked', true);
        $mainCheckbox.prop('indeterminate', false); // All checkboxes are checked
    } else if (anyChecked) {
        $mainCheckbox.prop('checked', false);
        $mainCheckbox.prop('indeterminate', true); // Some checkboxes are checked (mixed state)
    } else {
        $mainCheckbox.prop('checked', false);
        $mainCheckbox.prop('indeterminate', false); // No checkboxes are checked
    }
}

// Use event delegation to handle checkbox changes dynamically
$(document).on('change', '.dt-checkboxes', function() {
    updateMainCheckboxState('dt-checkboxes');
});

// Attach the change event to the main checkbox to toggle all checkboxes
$('#select-all').on('change', function() {
    toggleCheckboxes('dt-checkboxes', this);
});

// Put the selected data ids in the ids container in the deleting selected modal
$(function() {
    $('body').on('click', '#delete-selected-btn', function(e) {
        e.preventDefault();

        $('#delete-selected-form #ids-container').empty();
        const selected = Array.from($("#datatable td input[type=checkbox]:checked"))
            .map(
                checkbox => checkbox.value);

        if (selected.length > 0) {
            $('#delete-selected-modal').modal('show');

            selected.forEach(id => {
                $('#delete-selected-form #ids-container').append(
                    `<input type="hidden" name="ids[]" value="${id}">`
                );
            });

            $('input[id="itemToDelete"]').val(window.translations.items + ': ' + selected.length);
        } else {
            $('#delete-selected-modal').modal('show');
        }
    });
});
$(function() {
    $('body').on('click', '#archive-selected-btn', function(e) {
        e.preventDefault();

        $('#archive-selected-form #ids-container').empty();
        const selected = Array.from($("#datatable td input[type=checkbox]:checked"))
            .map(
                checkbox => checkbox.value);

        if (selected.length > 0) {
            $('#archive-selected-modal').modal('show');

            selected.forEach(id => {
                $('#archive-selected-form #ids-container').append(
                    `<input type="hidden" name="ids[]" value="${id}">`
                );
            });

            $('input[id="itemToArchive"]').val(window.translations.items + ': ' + selected.length);
        } else {
            $('#archive-selected-modal').modal('show');
        }
    });
});
$(function() {
    $('body').on('click', '#restore-selected-btn', function(e) {
        e.preventDefault();

        $('#restore-selected-form #ids-container').empty();
        const selected = Array.from($("#datatable td input[type=checkbox]:checked"))
            .map(
                checkbox => checkbox.value);

        if (selected.length > 0) {
            $('#restore-selected-modal').modal('show');

            selected.forEach(id => {
                $('#restore-selected-form #ids-container').append(
                    `<input type="hidden" name="ids[]" value="${id}">`
                );
            });

            $('input[id="itemToRestore"]').val(window.translations.items + ': ' + selected.length);
        } else {
            $('#restore-selected-modal').modal('show');
        }
    });
});
$(function() {
    $('body').on('click', '#accept-selected-btn', function(e) {
        e.preventDefault();

        $('#accept-selected-form #ids-container').empty();
        const selected = Array.from($("#datatable td input[type=checkbox]:checked"))
            .map(
                checkbox => checkbox.value);

        if (selected.length > 0) {
            $('#accept-selected-modal').modal('show');

            selected.forEach(id => {
                $('#accept-selected-form #ids-container').append(
                    `<input type="hidden" name="ids[]" value="${id}">`
                );
            });

            $('input[id="itemToAccept"]').val(window.translations.items + ': ' + selected.length);
        } else {
            $('#accept-selected-modal').modal('show');
        }
    });
});
$(function() {
    $('body').on('click', '#reject-selected-btn', function(e) {
        e.preventDefault();

        $('#reject-selected-form #ids-container').empty();
        const selected = Array.from($("#datatable td input[type=checkbox]:checked"))
            .map(
                checkbox => checkbox.value);

        if (selected.length > 0) {
            $('#reject-selected-modal').modal('show');

            selected.forEach(id => {
                $('#reject-selected-form #ids-container').append(
                    `<input type="hidden" name="ids[]" value="${id}">`
                );
            });

            $('input[id="itemToReject"]').val(window.translations.items + ': ' + selected.length);
        } else {
            $('#reject-selected-modal').modal('show');
        }
    });
});

toastr.options = {
    'closeButton': true,
    'progressBar': true,
}

document.addEventListener('DOMContentLoaded', function () {
    const datePickers = document.querySelectorAll('.flatpickr-date');
    const timePickers = document.querySelectorAll('.flatpickr-timeB');
    const dateTimePickers = document.querySelectorAll('.flatpickr-date-time');

    Array.from(datePickers).forEach((datepicker) => {
        flatpickr(datepicker, {
            dateFormat: 'Y-m-d',
        });
    });
    Array.from(timePickers).forEach((timePicker) => {
        flatpickr(timePicker, {
            enableTime: true,
            noCalendar: true,
            allowInput: true,
        });
    });
    Array.from(dateTimePickers).forEach((dateTimePicker) => {
        flatpickr(dateTimePicker, {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            minuteIncrement: 1
        });
    });
});

function generateRandomString(length = 8) {
    const array = new Uint8Array(length);
    window.crypto.getRandomValues(array);
    return Array.from(array, byte => byte.toString(36)).join('').substring(0, length);
}

function generateRandomUsername(suffix) {
    $('#add-form #name_en').on('input', function() {
        const username = 'Shattor' + generateRandomString() + suffix;
        $('#add-form #username').val(username);
    });
}

function generateStrongPassword(length = 12) {
    // Define character sets
    const lowercase = 'abcdefghijklmnopqrstuvwxyz';
    const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const numbers = '0123456789';

    // Combine all characters
    const allChars = lowercase + uppercase + numbers;

    // Ensure at least one character from each set
    let password = '';
    password += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
    password += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
    password += numbers.charAt(Math.floor(Math.random() * numbers.length));

    // Fill the rest with random characters
    for (let i = 4; i < length; i++) {
        password += allChars.charAt(Math.floor(Math.random() * allChars.length));
    }

    // Shuffle the password
    password = password.split('').sort(() => 0.5 - Math.random()).join('');

    return password;
}


function refreshDataTable(datatableId) {
    $(datatableId).DataTable().ajax.reload(null, false);
}

function initializeSelect2(modalId, elementId, value = null, disabled = false) {
    const select2Element = $('#' + modalId + ' #' + elementId);
    if (typeof $.fn.select2 !== 'undefined' && select2Element.length) {
        // Destroy any existing instance
        if (select2Element.hasClass('select2-hidden-accessible')) {
            select2Element.select2('destroy');
        }

        select2Focus(select2Element);
        select2Element.wrap('<div class="position-relative"></div>').select2({
            placeholder: window.translations.select_option,
            dropdownParent: select2Element.parent(),
            disabled: disabled,
        });

        if (value !== null) {
            select2Element.val(value).trigger('change');
        } else {
            select2Element.val('').trigger('change');
        }
    }
}

function initializeDataTable(tableId, ajaxUrl, exportColumns, columns) {
    $(document).ready(function () {
        let table = $(tableId);

        if (table.length) {
            table = table.DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: ajaxUrl,
                    error: function (xhr, error, code) {
                        toastr.error(errorMessage);
                    },
                },
                columns: columns,
                columnDefs: [
                    {
                        // For Responsive
                        targets: 0,
                        responsivePriority: 2,
                        className: "control",
                        render: function (data, type, full, meta) {
                            return "";
                        },
                    },
                ],
                displayLength: 7,
                lengthMenu: [7, 10, 25, 50, 75, 100, 500],
                language: {
                    search: window.translations.datatable.search,
                    emptyTable: window.translations.datatable.empty_table,
                    zeroRecords: window.translations.datatable.zero_records,
                    lengthMenu: window.translations.datatable.length_menu,
                    info: window.translations.datatable.info,
                    infoEmpty: window.translations.datatable.info_empty,
                    infoFiltered: window.translations.datatable.info_filtered,
                    paginate: {
                        next: '<i class="ri-arrow-right-s-line"></i>',
                        previous: '<i class="ri-arrow-left-s-line"></i>',
                    },
                },
                buttons: [
                    {
                        extend: "collection",
                        className:
                            "btn btn-label-primary dropdown-toggle me-4 waves-effect waves-light",
                        text: '<i class="ri-external-link-line me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
                        buttons: [
                            {
                                extend: "print",
                                text: '<i class="ri-printer-line me-1" ></i>Print',
                                className: "dropdown-item",
                                exportOptions: {
                                    columns: exportColumns,
                                },
                                customize: function (win) {
                                    //customize print view for dark

                                    $(win.document.body)
                                        .css(
                                            "color",
                                            config.colors.headingColor
                                        )
                                        .css(
                                            "border-color",
                                            config.colors.borderColor
                                        )
                                        .css(
                                            "background-color",
                                            config.colors.bodyBg
                                        );
                                    $(win.document.body)
                                        .find("table")
                                        .addClass("compact")
                                        .css("color", "inherit")
                                        .css("border-color", "inherit")
                                        .css("background-color", "inherit");
                                },
                            },
                            {
                                extend: "csv",
                                text: '<i class="ri-file-text-line me-1" ></i>Csv',
                                className: "dropdown-item",
                                exportOptions: {
                                    columns: exportColumns,
                                },
                            },
                            {
                                extend: "excel",
                                text: '<i class="ri-file-excel-line me-1"></i>Excel',
                                className: "dropdown-item",
                                exportOptions: {
                                    columns: exportColumns,
                                },
                            },
                            {
                                extend: "pdf",
                                text: '<i class="ri-file-pdf-line me-1"></i>Pdf',
                                className: "dropdown-item",
                                exportOptions: {
                                    columns: exportColumns,
                                },
                            },
                            {
                                extend: "copy",
                                text: '<i class="ri-file-copy-line me-1" ></i>Copy',
                                className: "dropdown-item",
                                exportOptions: {
                                    columns: exportColumns,
                                },
                            },
                        ],
                    },
                ],
                responsive: {
                    details: {
                        display: $.fn.dataTable.Responsive.display.modal({
                            header: function (row) {
                                var data = row.data();
                                let displayName = '';
                                if (data.name && typeof data.name === 'object') {
                                    displayName = currentLocale === 'ar' ? data.name.ar : data.name.en;
                                } else {
                                    displayName = data.name || data.title || data.topic || '';
                                }
                                return window.translations.detailsOf + ': ' + displayName;
                            },
                        }),
                        type: "column",
                        renderer: function (api, rowIdx, columns) {
                            var data = $.map(columns, function (col, i) {
                                if (i !== 0) {
                                    return col.title !== "" // ? Do not show row in modal popup if title is blank (for check box)
                                        ? '<tr data-dt-row="' +
                                            col.rowIndex +
                                            '" data-dt-column="' +
                                            col.columnIndex +
                                            '">' +
                                            "<td>" +
                                            col.title +
                                            ":" +
                                            "</td> " +
                                            "<td>" +
                                            col.data +
                                            "</td>" +
                                            "</tr>"
                                        : "";
                                }
                                return "";
                            }).join("");

                            return data
                                ? $('<table class="table"/><tbody />').append(
                                    data
                                  )
                                : false;
                        },
                    },
                },
            });

            table.on("init", function () {
                fields = ["print", "csv", "excel", "pdf", "copy"];
                $.each(fields, function (key, field) {
                    $("." + field + "-button").on("click", function () {
                        table.button(".buttons-" + field).trigger();
                    });
                });
            });
        }
        else {
            toastr.error(errorMessage);
        }
    });
}

function initializePostDataTable(tableId, ajaxUrl, exportColumns, columns, extraData = {}, formSelector = null) {
    $(document).ready(function () {
        let table = $(tableId);

        if (table.length) {
            table = table.DataTable({
                processing: true,
                serverSide: true,
                bDestroy : true,
                ajax: {
                    url: ajaxUrl,
                    type: 'POST',
                    data: function (d) {
                        let token;
                        if (formSelector && $(formSelector).length) {
                            token = $(formSelector + ' input[name="_token"]').val();
                        }
                        if (!token) {
                            token = $('meta[name="csrf-token"]').attr('content');
                        }
                        return $.extend({}, d, extraData, {
                            _token: token,
                        });
                    },
                    dataSrc: function(response) {
                        setTimeout(function() {
                            submitButton.prop('disabled', false);
                        }, 1500);

                        return response.data || [];
                    },
                    error: function(xhr, status, error) {
                        if (xhr.status === 429) {
                            toastr.error(tooManyRequestsMessage);
                        } else if (xhr.responseJSON) {
                            if (xhr.responseJSON.errors) {
                                $.each(xhr.responseJSON.errors, function(key, val) {
                                    const inputElement = $(formSelector + ' #' + key);
                                    const errorElement = $(formSelector + ' #' + key + '_error');
                                    inputElement.addClass('is-invalid');
                                    errorElement.text(val[0]).addClass('d-block').removeClass('d-none');
                                });
                            } else if (xhr.responseJSON.error) {
                                toastr.error(xhr.responseJSON.error);
                            } else {
                                toastr.error(errorMessage);
                            }
                        } else {
                            toastr.error(errorMessage);
                        }

                        setTimeout(function() {
                            submitButton.prop('disabled', false);
                        }, 1500);
                    },
                },
                columns: columns,
                columnDefs: [
                    {
                        // For Responsive
                        targets: 0,
                        responsivePriority: 2,
                        className: "control",
                        render: function (data, type, full, meta) {
                            return "";
                        },
                    },
                ],
                displayLength: 7,
                lengthMenu: [7, 10, 25, 50, 75, 100, 500],
                language: {
                    search: window.translations.datatable.search,
                    emptyTable: window.translations.datatable.empty_table,
                    zeroRecords: window.translations.datatable.zero_records,
                    lengthMenu: window.translations.datatable.length_menu,
                    info: window.translations.datatable.info,
                    infoEmpty: window.translations.datatable.info_empty,
                    infoFiltered: window.translations.datatable.info_filtered,
                    paginate: {
                        next: '<i class="ri-arrow-right-s-line"></i>',
                        previous: '<i class="ri-arrow-left-s-line"></i>',
                    },
                },
                buttons: [
                    {
                        extend: "collection",
                        className:
                            "btn btn-label-primary dropdown-toggle me-4 waves-effect waves-light",
                        text: '<i class="ri-external-link-line me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
                        buttons: [
                            {
                                extend: "print",
                                text: '<i class="ri-printer-line me-1" ></i>Print',
                                className: "dropdown-item",
                                exportOptions: {
                                    columns: exportColumns,
                                },
                                customize: function (win) {
                                    //customize print view for dark
                                    $(win.document.body)
                                        .css(
                                            "color",
                                            config.colors.headingColor
                                        )
                                        .css(
                                            "border-color",
                                            config.colors.borderColor
                                        )
                                        .css(
                                            "background-color",
                                            config.colors.bodyBg
                                        );
                                    $(win.document.body)
                                        .find("table")
                                        .addClass("compact")
                                        .css("color", "inherit")
                                        .css("border-color", "inherit")
                                        .css("background-color", "inherit");
                                },
                            },
                            {
                                extend: "csv",
                                text: '<i class="ri-file-text-line me-1" ></i>Csv',
                                className: "dropdown-item",
                                exportOptions: {
                                    columns: exportColumns,
                                },
                            },
                            {
                                extend: "excel",
                                text: '<i class="ri-file-excel-line me-1"></i>Excel',
                                className: "dropdown-item",
                                exportOptions: {
                                    columns: exportColumns,
                                },
                            },
                            {
                                extend: "pdf",
                                text: '<i class="ri-file-pdf-line me-1"></i>Pdf',
                                className: "dropdown-item",
                                exportOptions: {
                                    columns: exportColumns,
                                },
                            },
                            {
                                extend: "copy",
                                text: '<i class="ri-file-copy-line me-1" ></i>Copy',
                                className: "dropdown-item",
                                exportOptions: {
                                    columns: exportColumns,
                                },
                            },
                        ],
                    },
                ],
                responsive: {
                    details: {
                        display: $.fn.dataTable.Responsive.display.modal({
                            header: function (row) {
                                var data = row.data();
                                return window.translations.detailsOf + ': ' + data["name"];
                            },
                        }),
                        type: "column",
                        renderer: function (api, rowIdx, columns) {
                            var data = $.map(columns, function (col, i) {
                                if (i !== 0) {
                                    return col.title !== "" // ? Do not show row in modal popup if title is blank (for check box)
                                        ? '<tr data-dt-row="' +
                                            col.rowIndex +
                                            '" data-dt-column="' +
                                            col.columnIndex +
                                            '">' +
                                            "<td>" +
                                            col.title +
                                            ":" +
                                            "</td> " +
                                            "<td>" +
                                            col.data +
                                            "</td>" +
                                            "</tr>"
                                        : "";
                                }
                                return "";
                            }).join("");

                            return data
                                ? $('<table class="table"/><tbody />').append(
                                    data
                                  )
                                : false;
                        },
                    },
                },
            });

            table.on("init", function () {
                fields = ["print", "csv", "excel", "pdf", "copy"];
                $.each(fields, function (key, field) {
                    $("." + field + "-button").on("click", function () {
                        table.button(".buttons-" + field).trigger();
                    });
                });
            });
        }
        else {
            toastr.error(errorMessage);
        }
    });
}

function setupModal({ buttonId, modalId, fields = {}, onShow = null }) {
    $('body').on('click', buttonId, function(e) {
        e.preventDefault();

        const $modal = $(modalId);

        // Populate fields dynamically
        Object.entries(fields).forEach(([field, getValue]) => {
            const $fieldElement = $modal.find('#' + field);
            const $radioElement = $modal.find('.' + field);

            if (typeof getValue === 'function') {
                let value = getValue($(this));

                if ($fieldElement.is('select')) {
                    // Check if the value is a string and needs to be split for multiple select
                    if (typeof value === 'string') {
                        value = value.split(','); // Split the string into an array
                    }
                    // Automatically initialize Select2 for select fields
                    initializeSelect2(modalId.replace('#', ''), field, value);
                } else if ($radioElement.is('input[type="radio"]')) {
                    // For radio buttons, check the value and set the checked attribute
                    $radioElement.each(function() {
                        if ($(this).val() == value) {
                            $(this).prop('checked', true);
                            $(this).closest('.form-check').addClass('checked');
                        } else {
                            $(this).closest('.form-check').removeClass('checked');
                        }
                    });
                } else {
                    $fieldElement.val(value);
                }
            }
        });

        // Execute any custom logic before showing the modal
        if (typeof onShow === 'function') {
            onShow($modal, $(this), buttonId);
        }
    });
}

function handleFormSubmit(formId, fields, modalId, modalType, getDatatableId, redirectTo = null) {
    const $form = $(formId);
    const $submitButton = $form.find('button[type="submit"]');
    const originalButtonContent = $submitButton.html();

    $(formId).on('submit', function(e) {
        e.preventDefault();

        $submitButton.find('.waves-ripple').remove();
        $submitButton.prop('disabled', true);
        $submitButton.html(`<i class="ri-loader-4-line ri-spin ri-20px me-1"></i> ${window.translations.processing}...`);

        // Clear previous error states
        $.each(fields, function(_, field) {
            $(formId + ' #' + field).removeClass('is-invalid');
            $(formId + ' #' + field + '_error').text('').addClass('d-none').removeClass('d-block');
        });

        const formData = new FormData(this);
        let datatableId = typeof getDatatableId === 'function' ? getDatatableId() : getDatatableId;

        $.ajax({
            url: $(this).attr('action'),
            type: $(this).attr('method'),
            dataType: "json",
            processData: false,
            contentType: false,
            data: formData,
            success: function(response) {
                if (response.success) {
                    $.each(fields, function(_, field) {
                        const fieldElement = $(formId + ' #' + field);
                        if (fieldElement.is('select')) {
                            fieldElement.val('').trigger('change');
                        } else {
                            fieldElement.val('');
                        }
                    });
                    toastr.success(response.success)
                    resetButtonState($submitButton, originalButtonContent);
                    if (modalType === 'offcanvas') {
                        $(modalId).offcanvas('hide');
                    } else if (modalType === 'modal') {
                        $(modalId).modal('hide');
                    }
                    if ($(datatableId).length) {
                        refreshDataTable(datatableId);
                    } else if (redirectTo) {
                        window.location.href = redirectTo;
                    } else {
                        location.reload();
                    }
                } else {
                    toastr.error(response.error || errorMessage);
                    resetButtonState($submitButton, originalButtonContent);
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 429) {
                    toastr.error(tooManyRequestsMessage);
                } else if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, val) {
                            const normalizedKey = key.replace(/\.\d+$/, '');
                            const inputElement = $(formId + ' #' + normalizedKey);
                            const errorElement = $(formId + ' #' + normalizedKey + '_error');
                            inputElement.addClass('is-invalid');
                            errorElement.text(val[0]).addClass('d-block').removeClass('d-none');
                        });
                    } else if (xhr.responseJSON.error) {
                        toastr.error(xhr.responseJSON.error);
                    } else {
                        toastr.error(errorMessage);
                    }
                } else {
                    toastr.error(errorMessage);
                }

                resetButtonState($submitButton, originalButtonContent);
            },
            complete: function() {
                resetButtonState($submitButton, originalButtonContent);
            }
        });
    });
}

function handleDeletionFormSubmit(formId, modalId, getDatatableId, redirectTo = null) {
    const $form = $(formId);
    const $submitButton = $form.find('button[type="submit"]');
    const originalButtonContent = $submitButton.html();

    $(formId).on('submit', function(e) {
        e.preventDefault();

        $submitButton.find('.waves-ripple').remove();
        $submitButton.prop('disabled', true);
        $submitButton.html(`<i class="ri-loader-4-line ri-spin ri-20px me-1"></i> ${window.translations.processing}...`);

        const formData = new FormData(this);
        let datatableId = typeof getDatatableId === 'function' ? getDatatableId() : getDatatableId;

        $.ajax({
            url: $(this).attr('action'),
            type: $(this).attr('method'),
            dataType: "json",
            processData: false,
            contentType: false,
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.success)
                    resetButtonState($submitButton, originalButtonContent);
                    $(modalId).modal('hide')
                    if ($(datatableId).length) {
                        refreshDataTable(datatableId);
                    } else if (redirectTo) {
                        window.location.href = redirectTo;
                    } else {
                        location.reload();
                    }
                } else {
                    toastr.error(response.error || errorMessage);
                    resetButtonState($submitButton, originalButtonContent);
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 429) {
                    toastr.error(tooManyRequestsMessage);
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    toastr.error(xhr.responseJSON.error);
                } else {
                    toastr.error(errorMessage);
                }
                resetButtonState($submitButton, originalButtonContent);
            },
            complete: function() {
                resetButtonState($submitButton, originalButtonContent);
            }
        });
    });
}

function fetchMultipleDataByAjax(triggerSelector, urlTemplate, targetSelector, requestDataKey, type = 'POST') {
    $(triggerSelector).on('change', function(e) {
        e.preventDefault();

        const selectedValue  = $(this).val();
        const secondSelectedValue = $(this).closest('form').find('select').not(triggerSelector).val();

        if (selectedValue  && selectedValue .length > 0) {
            const url = urlTemplate.replace('__ID__', selectedValue).replace('__SECOND_ID__', secondSelectedValue);

            $.ajax({
                url: url,
                type: type,
                dataType: "json",
                data: {
                    [requestDataKey]: selectedValue,
                    _token: csrfToken,
                },
                success: function(response) {
                    if(response.status === 'success') {
                        $(targetSelector).empty();
                        $(targetSelector).append('<option value="">' + window.translations.select_option + '</option>');
                        $.each(response.data, function(key, value) {
                            $(targetSelector).append('<option value="' +
                                key + '">' +
                                value + '</option>');
                        });
                    }else {
                        toastr.error(response.message || errorMessage);
                    }
                },
                error: function(xhr) {
                    $(targetSelector).empty();
                    if (xhr.status === 429) {
                        toastr.error(tooManyRequestsMessage);
                    } else if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(key, val) {
                                toastr.error(val);
                            });
                        } else if (xhr.responseJSON.message) {
                            toastr.error(xhr.responseJSON.message);
                        } else if (xhr.responseJSON.error) {
                            toastr.error(xhr.responseJSON.error);
                        } else {
                            toastr.error(errorMessage);
                        }
                    } else {
                        toastr.error(errorMessage);
                    }
                },
            });
        } else {
            $(targetSelector).empty();
        }
    });
}

function fetchSingleDataByAjax(triggerSelector, urlTemplate, mappings, requestDataKey, type = 'GET', secondSelectId = null) {
    $(triggerSelector).on('change', function(e) {
        e.preventDefault();

        const selectedValue = $(this).val();
        const secondSelectedValue = $(this).closest('form').find(`[id="${secondSelectId}"]`).val();

        if (selectedValue && selectedValue.length > 0) {
            const url = urlTemplate.replace('__ID__', selectedValue).replace('__SECOND_ID__', secondSelectedValue);

            $.ajax({
                url: url,
                type: type,
                dataType: "json",
                data: {
                    [requestDataKey]: selectedValue,
                    _token: csrfToken,
                },
                success: function(response) {
                    if (response.status === 'success') {
                        mappings.forEach(mapping => {
                            let value = response.data;

                            if (mapping.dataKey && typeof response.data === 'object') {
                                const keys = mapping.dataKey.split('.');
                                value = keys.reduce((obj, key) => obj && obj[key], response.data);
                            }

                            const $target = $(mapping.targetSelector);
                            if ($target.is('select')) {
                                const [modalId, elementId] = mapping.targetSelector.substring(1).split(' #');
                                let disabledStatus = false;
                                if (elementId === 'exemptition') {
                                    disabledStatus = true;
                                }
                                initializeSelect2(modalId, elementId, value, disabledStatus);
                            } else if ($target.is('input')) {
                                $target.val(value);
                            } else {
                                $target.text(value);
                            }
                        });
                    } else {
                        toastr.error(response.message || errorMessage);
                    }
                },
                error: function(xhr) {
                    // Clear all target fields on error
                    mappings.forEach(mapping => {
                        const $target = $(mapping.targetSelector);
                        if ($target.is('input')) {
                            $target.val('');
                        } else {
                            $target.text('');
                        }
                    });

                    if (xhr.status === 429) {
                        toastr.error(tooManyRequestsMessage);
                    } else if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(key, val) {
                                toastr.error(val);
                            });
                        } else if (xhr.responseJSON.message) {
                            toastr.error(xhr.responseJSON.message);
                        } else if (xhr.responseJSON.error) {
                            toastr.error(xhr.responseJSON.error);
                        } else {
                            toastr.error(errorMessage);
                        }
                    } else {
                        toastr.error(errorMessage);
                    }
                },
            });
        } else {
            // Clear all target fields when no value is selected
            mappings.forEach(mapping => {
                const $target = $(mapping.targetSelector);
                if ($target.is('input')) {
                    $target.val('');
                } else {
                    $target.text('');
                }
            });
        }
    });
}

function getWeekdayName(day, locale) {
    return weekdays[locale][day] || '-';
}

function updateGroupNames() {
    const day1 = $('#day_1').val();
    const day2 = $('#day_2').val();
    const time = $('#time').val();

    let groupNameAr = '';
    let groupNameEn = '';

    if (day1 && !day2) {
        groupNameAr = `${getWeekdayName(day1, 'ar')} ${time}`;
        groupNameEn = `${getWeekdayName(day1, 'en')} ${time}`;
    } else if (day1 && day2) {
        groupNameAr = `${getWeekdayName(day1, 'ar')} & ${getWeekdayName(day2, 'ar')} ${time}`;
        groupNameEn = `${getWeekdayName(day1, 'en')} & ${getWeekdayName(day2, 'en')} ${time}`;
    }
    $('#name_ar').val(groupNameAr);
    $('#name_en').val(groupNameEn);
}

function resetButtonState(submitButton, originalButtonContent) {
    setTimeout(function() {
        submitButton.prop('disabled', false);
        submitButton.html(originalButtonContent);
        submitButton.blur();
        submitButton.find('.waves-ripple').remove();
        if (typeof Waves !== 'undefined') {
            Waves.init();
            Waves.attach(submitButton[0]);
        }
    }, 1500);
}

function validateFile(file, fileSize, allowedExtensions) {
    if (!file) {
        toastr.error(window.translations.noFileUploaded);
        return false;
    }

    const fileExtension = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(fileExtension)) {
        toastr.error(window.translations.invalidFileType.replace(':values', allowedExtensions.join(', ')));
        return false;
    }

    if (file.size > fileSize * 1024 * 1024) {
        toastr.error(window.translations.maxFileSize.replace(':max', fileSize * 1024));
        return false;
    }

    return true;
}

function handleProfilePicSubmit(formId, fileSize, allowedExtensions) {
    const $form = $(formId);
    const $submitButton = $form.find('button[type="submit"]');
    const $fileInput = $form.find('input[type="file"]');
    const originalButtonContent = $submitButton.html();

    $(formId).on('submit', function(e) {
        e.preventDefault();

        $submitButton.find('.waves-ripple').remove();
        $submitButton.prop('disabled', true);
        $submitButton.html(`<i class="ri-loader-4-line ri-spin ri-20px me-1"></i> ${window.translations.processing}...`);

        const file = $fileInput[0].files[0];

        if (!validateFile(file, fileSize, allowedExtensions)) {
            resetButtonState($submitButton, originalButtonContent);
            return;
        }

        const formData = new FormData(this);

        $.ajax({
            url: $(this).attr('action'),
            type: $(this).attr('method'),
            dataType: "json",
            processData: false,
            contentType: false,
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.success);
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.error || errorMessage);
                    resetButtonState($submitButton, originalButtonContent);
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 429) {
                    toastr.error(tooManyRequestsMessage);
                } else if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, val) {
                            $.each(val, function(index, message) {
                                toastr.error(message);
                            });
                        });
                    } else if (xhr.responseJSON.error) {
                        toastr.error(xhr.responseJSON.error);
                    } else {
                        toastr.error(errorMessage);
                    }
                } else {
                    toastr.error(errorMessage);
                }

                resetButtonState($submitButton, originalButtonContent);
            },
        });
    });
}

function strLimit(str, limit = 50, ending = '...') {
    if (typeof str !== 'string') {
        str = String(str);
    }
    if (str.length <= limit) {
        return str;
    }

    let truncated = str.substr(0, limit);
    let lastSpace = truncated.lastIndexOf(' ');

    if (lastSpace !== -1) {
        truncated = truncated.substr(0, lastSpace);
    } else {
        truncated = str.substr(0, limit);
    }

    return truncated + ending;
}

function toggleShareButton()
{
    document.querySelector('.ri-share-forward-line').addEventListener('click', async () => {
        // Get current URL
        const url = encodeURIComponent(window.location.href);
        const title = encodeURIComponent(document.title);

        // Create sharing options
        const shareOptions = [
            {
                name: 'WhatsApp',
                url: `https://wa.me/?text=${title}%20${url}`,
                icon: 'ri-whatsapp-line'
            },
            {
                name: 'Copy Link',
                url: 'copy',
                icon: 'ri-file-copy-line'
            }
        ];

        // Create share modal HTML
        const modalHtml = `
            <div class="modal fade" id="shareModal" tabindex="-1">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${window.translations?.share || 'Share'}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-around">
                        ${shareOptions.map(option => `
                            <button class="btn btn-icon ${option.url === 'copy' ? 'btn-label-primary' : 'btn-label-success'} share-btn" data-url="${option.url}">
                            <i class="${option.icon}"></i>
                            </button>
                        `).join('')}
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        `;

        // Add modal to body if not exists
        if (!document.getElementById('shareModal')) {
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        // Show modal
        const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
        shareModal.show();

        // Handle share button clicks
        document.querySelectorAll('.share-btn').forEach(btn => {
            btn.onclick = async () => {
                const shareUrl = btn.dataset.url;

                if (shareUrl === 'copy') {
                    try {
                        await navigator.clipboard.writeText(window.location.href);
                        toastr.success(window.translations?.linkCopiedSuccess || 'Link copied successfully!');
                    } catch (err) {
                        toastr.error(window.translations?.linkCopiedError || 'Failed to copy link');
                    }
                    shareModal.hide();
                } else {
                    window.open(shareUrl, '_blank', 'width=600,height=400');
                }
            };
        });
    });
}

function calculateAmountAndDiscount() {
    let originalAmount = 0;
    let savedDiscount = 0;

    function updateAmount() {
        if ($('#amount').val() && !originalAmount) {
            originalAmount = parseFloat($('#amount').val());
        }

        const isExempted = $('#is_exempted').val() === '1';

        if (isExempted) {
            savedDiscount = parseFloat($('#discount').val()) || 0;
            $('#discount').val(0);
            $('#amount').val('0.00');
            $('#amount').attr('readonly', true);
        } else {
            const discount = Math.min(100, Math.max(0, parseFloat($('#discount').val()) || 0));
            $('#discount').val(discount);
            let amount = originalAmount * (1 - discount / 100);
            amount = Math.max(0, Math.min(originalAmount, amount)).toFixed(2);
            $('#amount').val(amount);
            $('#amount').removeAttr('readonly');
        }
    }

    function updateDiscount() {
        if (originalAmount > 0 && $('#is_exempted').val() !== '1') {
            let currentAmount = parseFloat($('#amount').val()) || 0;
            currentAmount = Math.min(originalAmount, currentAmount);
            const discount = ((originalAmount - currentAmount) / originalAmount * 100).toFixed(2);
            if (discount >= 0 && discount <= 100) {
                $('#discount').val(discount);
            }
        }
    }

    $('#discount').on('input', updateAmount);
    $('#is_exempted').on('change', function() {
        if ($(this).val() === '0') {
            $('#discount').val(savedDiscount);
        }
        updateAmount();
    });
    $('#amount').on('input', function() {
        if (!originalAmount) {
            originalAmount = parseFloat($(this).val()) || 0;
        }
        updateDiscount();
    });
    updateAmount();

    $('#fee_id').on('change', function() {
        originalAmount = 0;
        savedDiscount = 0;
        updateAmount();
    });
}

