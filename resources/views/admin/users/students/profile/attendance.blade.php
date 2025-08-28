@extends('admin.users.students.profile.master')

@section('profile-content')
    <div class="row g-6 mb-6">
        <div class="col-sm-6 col-lg-6">
            <div class="card card-border-shadow-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="me-1">
                            <p class="text-heading mb-1">{{ trans('profile.attendanceRate') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['attendanceRate'] }}%</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.attendanceRateDesc') }} ({{ $stats['attendedLessons'] }} {{ trans('main.of') }} {{ $stats['totalLessons'] }})</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-success rounded-3">
                                <div class="ri-calendar-check-line ri-28px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-6">
            <div class="card card-border-shadow-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="me-1">
                            <p class="text-heading mb-1">{{ trans('profile.absentLessons') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['absentLessons'] }}</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.absentLessonsDesc') }}</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-danger rounded-3">
                                <div class="ri-calendar-close-line ri-28px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-6">
            <div class="card card-border-shadow-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="me-1">
                            <p class="text-heading mb-1">{{ trans('profile.lateLessons') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['lateLessons'] }}</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.lateLessonsDesc') }}</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-warning rounded-3">
                                <div class="ri-time-line ri-28px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-6">
            <div class="card card-border-shadow-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="me-1">
                            <p class="text-heading mb-1">{{ trans('profile.compensatoryLessons') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['compensatoryLessons'] }}</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.compensatoryLessonsDesc') }}</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-info rounded-3">
                                <div class="ri-refresh-line ri-28px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-datatable id="lessons-datatable" cardClasses="mb-6" datatableTitle="{{ trans('admin/lessons.lessons') }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('main.title') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('admin/compensatories.makeup_lesson') }}</th>
        <th>{{ trans('main.description') }}</th>
        <th>{{ trans('main.created_at') }}</th>
    </x-datatable>

    <x-datatable id="compensatories-datatable" cardClasses="mb-6"
        datatableTitle="{{ trans('admin/compensatories.compensatories') }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('admin/compensatories.original_lesson') }}</th>
        <th>{{ trans('admin/compensatories.makeup_lesson') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.reason') }}</th>
    </x-datatable>
@endsection

@section('profile-js')
    <script>
        initializeDataTable('#lessons-datatable',
            "{{ route('admin.students.profile.attendance', $student->id) }}?table=lessons", [1, 2, 3, 4, 5, 6],
            [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'title',
                    name: 'title'
                },
                {
                    data: 'attendance_status',
                    name: 'attendance_status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'makeup_status',
                    name: 'makeup_status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'attendance_note',
                    name: 'attendance_note',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'attendance_created_at',
                    name: 'attendance_created_at',
                    orderable: false,
                    searchable: false
                }
            ],
        );

        initializeDataTable('#compensatories-datatable',
            "{{ route('admin.students.profile.attendance', $student->id) }}", [1, 2, 3, 4, 5],
            [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'original_lesson_id',
                    name: 'original_lesson_id',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'makeup_lesson_id',
                    name: 'makeup_lesson_id',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'reason',
                    name: 'reason',
                    orderable: false,
                    searchable: false
                },
            ],
        );
    </script>
@endsection
