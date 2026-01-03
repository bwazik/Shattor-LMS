@extends('layouts.teacher.master')

@section('page-css')
    <style>
        .attendance-legend span {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 5px;
            vertical-align: middle;
            border-radius: 4px;
            opacity: 0.7;
        }

        .status-present {
            background-color: #bdf5a1;
        }

        .status-absent {
            background-color: #ffb5b2;
        }

        .status-late {
            background-color: #ffde96;
        }

        .status-compensatory {
            background-color: #91e4ff;
        }

        .status-container {
            display: flex;
            justify-content: center;
            gap: 0.4rem;
        }

        .status-btn {
            min-width: 45px;
            padding: 0.4rem 0.7rem;
            transition: all 0.2s ease-in-out;
            border: 2px solid transparent;
            font-weight: 500;
            color: black;
            background-color: white;
        }

        .status-btn[data-status="1"] {
            border-color: #28a745;
            color: #28a745;
        }

        .status-btn[data-status="2"] {
            border-color: #dc3545;
            color: #dc3545;
        }

        .status-btn[data-status="3"] {
            border-color: #ffc107;
            color: #ffc107;
        }

        .status-btn[data-status="4"] {
            border-color: #17a2b8;
            color: #17a2b8;
        }

        .status-btn.active {
            font-weight: bold;
            color: white !important;
            border-color: transparent !important;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            transform: scale(1.05);
        }

        .status-btn.active[data-status="1"] {
            background-color: #28a745;
        }

        .status-btn.active[data-status="2"] {
            background-color: #dc3545;
        }

        .status-btn.active[data-status="3"] {
            background-color: #ffc107;
        }

        .status-btn.active[data-status="4"] {
            background-color: #17a2b8;
        }

        #qr-video {
            display: none;
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        #qr-video.active {
            display: block;
        }

        .scan-tab-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 50vh;
        }
    </style>
@endsection

@section('title', pageTitle('admin/attendance.attendance'))

@section('content')
    <div class="col-12 mb-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>{{ trans('admin/attendance.studentsSearch') }}</h4>
                <div class="attendance-legend no-print">
                    <div class="d-flex gap-4">
                        <div><span class="status-present rounded"></span> {{ trans('admin/attendance.present') }}</div>
                        <div><span class="status-absent rounded"></span> {{ trans('admin/attendance.absent') }}</div>
                        <div><span class="status-late rounded"></span> {{ trans('admin/attendance.late') }}</div>
                        <div><span class="status-compensatory rounded"></span> {{ trans('admin/attendance.compensatory') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="students-form">
                    <div class="row g-5">
                        <x-select-input context="modal" name="grade_id" label="{{ trans('main.grade') }}" :options="[$lesson->group->grade_id => $lesson->group->grade->name]"
                            required readonly />
                        <x-select-input context="modal" name="group_id" label="{{ trans('main.group') }}" :options="[$lesson->group->uuid => $lesson->group->name]"
                            required readonly />
                        <x-select-input context="modal" name="lesson_id" label="{{ trans('main.lesson') }}"
                            :options="[$lesson->uuid => $lesson->title]" required readonly />
                        <x-basic-input context="modal" type="text" name="date" classes="flatpickr-date"
                            label="{{ trans('main.date') }}" placeholder="YYYY-MM-DD" value="{{ $lesson->date }}" required
                            disabled />
                    </div>
                    <div class="pt-6">
                        <button type="button" id="mark-all"
                            class="btn btn-success">{{ trans('admin/attendance.markAllPresent') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link active waves-effect" role="tab" data-bs-toggle="tab"
                data-bs-target="#datatable-tab" aria-controls="datatable-tab"
                aria-selected="true">{{ trans('admin/attendance.attendanceDatatable') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link waves-effect" role="tab" data-bs-toggle="tab"
                data-bs-target="#scan-tab" aria-controls="scan-tab"
                aria-selected="false">{{ trans('admin/attendance.qrScan') }}</button>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade active show" id="datatable-tab" role="tabpanel">
            <x-datatable
                datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/attendance.attendance')]) }}"
                dataToggle="offcanvas" otherButton="{{ trans('admin/attendance.submit') }}" otherIcon="ri-checkbox-circle-line"
                excelButton="ss" excelButtonRoute="{{ route('teacher.attendance.export', $lesson->uuid) }}">
                <th></th>
                <th>#</th>
                <th>{{ trans('main.student') }}</th>
                <th style="width: 80px;">{{ trans('main.type') }}</th>
                <th>{{ trans('main.description') }}</th>
                <th style="width: 100px;">{{ trans('admin/attendance.manualScan') }}</th>
                <th>{{ trans('main.actions') }}</th>
            </x-datatable>
        </div>
        <div class="tab-pane fade" id="scan-tab" role="tabpanel">
            <div class="scan-tab-container">
                <div class="text-center">
                    <video id="qr-video" class="mb-3 rounded" style="overflow: hidden;"></video>
                    <button id="start-scanner"
                        class="btn btn-success">{{ trans('admin/attendance.startScanner') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    @include('teacher.activities.attendance.js')
    <script>
        initializeDataTable('#datatable', "{{ route('teacher.lessons.attendances', $lesson->uuid) }}", [2, 6],
            [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'name',
                    name: 'name',
                    orderable: false,
                    searchable: true
                },
                {
                    data: 'type',
                    name: 'type',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'note',
                    name: 'note',
                    orderable: false,
                    searchable: false
                },
                { data: 'scan_button', name: 'scan_button', orderable: false, searchable: false },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
        );

        initializeSelect2('students-form', 'grade_id', '{{ $lesson->group->grade_id }}', true);
        initializeSelect2('students-form', 'group_id', '{{ $lesson->group->uuid }}', true);
        initializeSelect2('students-form', 'lesson_id', '{{ $lesson->uuid }}', true);

        $(document).on('click', '.manual-scan-btn', function () {
            const button = $(this);
            const studentUuid = button.data('student-uuid');
            const studentName = button.data('student-name');

            if (!studentUuid) {
                toastr.error('{{ trans("admin/attendance.invalidStudent") }}');
                return;
            }

            // Get values from form (same as QR scanner)
            const gradeId = $('#grade_id').val();
            const groupId = $('#group_id').val();
            const lessonId = $('#lesson_id').val();

            if (!gradeId || !groupId || !lessonId) {
                toastr.error('Please select a grade, group, and lesson');
                return;
            }

            // Prevent double-click
            if (button.prop('disabled')) return;
            button.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm"></span>');

            const successSound = new Audio('{{ asset('assets/sounds/attendance-success-sound.mp3') }}');
            const errorSound = new Audio('{{ asset('assets/sounds/attendance-error-sound.mp3') }}');

            $.ajax({
                url: '{{ route("teacher.attendance.scan") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    uuid: studentUuid,
                    grade_id: gradeId,
                    group_id: groupId,
                    lesson_id: lessonId
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.success);
                        successSound.play().catch(() => {});
                        
                        // Update status buttons to Present (status 1)
                        const statusContainer = button.closest('tr').find('.status-container');
                        statusContainer.find('.status-btn').removeClass('active')
                            .css({ color: '', opacity: '0.5' });
                        statusContainer.find('.status-btn[data-status="1"]').addClass('active')
                            .css({ color: 'white', opacity: '1' });

                        // Optionally disable button after successful scan
                        button.prop('disabled', true)
                            .removeClass('btn-outline-primary')
                            .addClass('btn-success')
                            .html('<i class="ri-check-line"></i>');
                    } else {
                        toastr.error(response.error || 'Unknown error');
                        errorSound.play().catch(() => {});
                    }
                },
                error: function (xhr) {
                    let message = 'An error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        message = xhr.responseJSON.error;
                    } else if (xhr.status === 429) {
                        message = 'Too many requests';
                    }
                    toastr.error(message);
                    errorSound.play().catch(() => {});
                },
                complete: function () {
                    // Only re-enable if not already marked as success
                    if (!button.hasClass('btn-success')) {
                        button.prop('disabled', false)
                            .html('<i class="ri-qr-scan-2-line"></i>');
                    }
                }
            });
        });
    </script>
@endsection
