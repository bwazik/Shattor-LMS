@extends('parent.students.profile.master')

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
                            <small class="mb-0">{{ trans('profile.attendanceRateDesc') }}</small>
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
            <div class="card card-border-shadow-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="me-1">
                            <p class="text-heading mb-1">{{ trans('profile.avgQuizPercentage') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['avgQuizPercentage'] }}%</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.avgQuizPercentageDesc') }}</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-info rounded-3">
                                <div class="ri-brain-line ri-28px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-lg-12">
            <div class="card card-border-shadow-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="me-1">
                            <p class="text-heading mb-1">{{ trans('profile.totalPaidFees') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['totalPaidFees'] }}</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.totalPaidFeesDesc') }}</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-warning rounded-3">
                                <div class="ri-bank-line ri-28px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-datatable cardClasses="mb-6" datatableTitle="{{ trans('admin/groups.groups') }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('main.name') }}</th>
        <th>{{ trans('main.teacher') }}</th>
    </x-datatable>
@endsection

@section('profile-js')
    <script>
        initializeDataTable('#datatable', "{{ route('parent.students.profile.index', $student->uuid) }}", [1, 2],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'teacher_name', name: 'teacher_name' },
            ],
        );
    </script>
@endsection
