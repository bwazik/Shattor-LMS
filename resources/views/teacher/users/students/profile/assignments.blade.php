@extends('teacher.users.students.profile.master')

@section('profile-content')
    <div class="row g-6 mb-6">
        <div class="col-sm-6 col-lg-6">
            <div class="card card-border-shadow-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="me-1">
                            <p class="text-heading mb-1">{{ trans('profile.avgAssignmentScore') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['avgScore'] }}</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.avgAssignmentScoreDesc') }}</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-info rounded-3">
                                <div class="ri-file-copy-2-line ri-28px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-6">
            <div class="card card-border-shadow-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="me-1">
                            <p class="text-heading mb-1">{{ trans('profile.avgAssignmentPercentage') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['avgPercentage'] }}%</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.avgAssignmentPercentageDesc') }}</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-primary rounded-3">
                                <div class="ri-percent-line ri-28px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-6">
            <div class="card card-border-shadow-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="me-1">
                            <p class="text-heading mb-1">{{ trans('profile.submissionRate') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['submissionRate'] }}%</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.submissionRateDesc') }} ({{ $stats['submittedCount'] }} {{ trans('main.of') }} {{ $stats['totalAssignments'] }})</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-success rounded-3">
                                <div class="ri-checkbox-circle-line ri-28px"></div>
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
                            <p class="text-heading mb-1">{{ trans('profile.topAssignmentScore') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['topScore'] }}</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.topAssignmentScoreDesc') }}</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-warning rounded-3">
                                <div class="ri-star-line ri-28px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-datatable id="assignments-datatable" cardClasses="mb-6" datatableTitle="{{ trans('admin/assignments.assignments') }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('admin/quizzes.rank') }}</th>
        <th>{{ trans('main.title') }}</th>
        <th>{{ trans('main.score') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.link') }}</th>
    </x-datatable>
@endsection

@section('profile-js')
    <script>
        initializeDataTable('#assignments-datatable', "{{ route('teacher.students.profile.assignments', $student->uuid) }}?table=assignments", [1, 2, 3, 4, 5],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'rank', name: 'rank', orderable: false, searchable: false },
                { data: 'title', name: 'title' },
                { data: 'score', name: 'score', orderable: false, searchable: false },
                { data: 'status', name: 'status', orderable: false, searchable: false },
                { data: 'link', name: 'link', orderable: false, searchable: false },
            ],
        );
    </script>
@endsection
