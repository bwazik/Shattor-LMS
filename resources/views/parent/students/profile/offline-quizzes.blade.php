@extends('parent.students.profile.master')

@section('profile-content')
    <div class="row g-6 mb-6">
        <div class="col-sm-6 col-lg-6">
            <div class="card card-border-shadow-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="me-1">
                            <p class="text-heading mb-1">{{ trans('profile.completionRate') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['completionRate'] }}%</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.completionRateDesc') }} ({{ $stats['passedQuizzes'] }} {{ trans('main.of') }} {{ $stats['totalQuizzes'] }})</small>
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
                            <p class="text-heading mb-1">{{ trans('profile.topQuizScore') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['topScore'] }}</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.topQuizScoreDesc') }}</small>
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

    <x-datatable id="offline-quizzes-datatable" cardClasses="mb-6" datatableTitle="{{ trans('admin/offlineQuizzes.offlineQuizzes') }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('admin/quizzes.rank') }}</th>
        <th>{{ trans('main.name') }}</th>
        <th>{{ trans('main.score') }}</th>
        <th>{{ trans('main.percentage') }}</th>
    </x-datatable>
@endsection

@section('profile-js')
    <script>
        initializeDataTable('#offline-quizzes-datatable', "{{ route('parent.students.profile.offline-quizzes', $student->uuid) }}?table=offline-quizzes", [1, 2, 3, 4, 5],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'rank', name: 'rank', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'score', name: 'score', orderable: false, searchable: false },
                { data: 'percentage', name: 'percentage', orderable: false, searchable: false },
            ],
        );
    </script>
@endsection
