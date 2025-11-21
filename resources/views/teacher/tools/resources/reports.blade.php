@extends('layouts.teacher')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span>{{ trans('admin/resources.totalStudents') }}</span>
                                <div class="d-flex align-items-end mt-2">
                                    <h4 class="mb-0 me-2">{{ $data['totalStudents'] }}</h4>
                                    <small class="text-success">(100%)</small>
                                </div>
                                <small>{{ trans('admin/resources.eligibleStudents') }}</small>
                            </div>
                            <span class="badge bg-label-primary rounded p-2">
                                <i class="ti ti-users ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span>{{ trans('admin/resources.viewedResource') }}</span>
                                <div class="d-flex align-items-end mt-2">
                                    <h4 class="mb-0 me-2">{{ $data['viewedResource'] }}</h4>
                                    <small class="text-success">({{ $data['viewedPercentage'] }}%)</small>
                                </div>
                                <small>{{ trans('admin/resources.studentsWhoViewed') }}</small>
                            </div>
                            <span class="badge bg-label-success rounded p-2">
                                <i class="ti ti-eye ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span>{{ trans('admin/resources.didntViewResource') }}</span>
                                <div class="d-flex align-items-end mt-2">
                                    <h4 class="mb-0 me-2">{{ $data['didntViewResource'] }}</h4>
                                    <small class="text-danger">({{ $data['didntViewPercentage'] }}%)</small>
                                </div>
                                <small>{{ trans('admin/resources.studentsWhoDidntView') }}</small>
                            </div>
                            <span class="badge bg-label-danger rounded p-2">
                                <i class="ti ti-eye-off ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span>{{ trans('admin/resources.averageEngagement') }}</span>
                                <div class="d-flex align-items-end mt-2">
                                    <h4 class="mb-0 me-2">{{ $data['averagePercentage'] }}%</h4>
                                </div>
                                <small>{{ trans('admin/resources.avgCompletion') }}</small>
                            </div>
                            <span class="badge bg-label-warning rounded p-2">
                                <i class="ti ti-chart-pie ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-bottom">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#students-viewed" role="tab" aria-selected="true">
                            {{ trans('admin/resources.studentsViewed') }}
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#students-not-viewed" role="tab" aria-selected="false">
                            {{ trans('admin/resources.studentsNotViewed') }}
                        </button>
                    </li>
                </ul>
            </div>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="students-viewed" role="tabpanel">
                    <div class="card-datatable table-responsive">
                        <table class="datatables-students-viewed table border-top">
                            <thead>
                                <tr>
                                    <th>{{ trans('admin/resources.student') }}</th>
                                    <th>{{ trans('admin/resources.views') }}</th>
                                    <th>{{ trans('admin/resources.duration') }}</th>
                                    <th>{{ trans('admin/resources.percentage') }}</th>
                                    <th>{{ trans('admin/resources.lastWatched') }}</th>
                                    <th>{{ trans('global.actions') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="students-not-viewed" role="tabpanel">
                    <div class="card-datatable table-responsive">
                        <table class="datatables-students-not-viewed table border-top">
                            <thead>
                                <tr>
                                    <th>{{ trans('admin/resources.student') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-style')
    @include('vendor.datatable.styles')
@endsection

@section('vendor-script')
    @include('vendor.datatable.scripts')
@endsection

@section('page-script')
    <script>
        $(function() {
            var viewedTable = $('.datatables-students-viewed');
            if (viewedTable.length) {
                viewedTable.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('teacher.resources.studentsViewed', $resource->uuid) }}",
                    columns: [
                        { data: 'details', name: 'details' },
                        { data: 'views', name: 'views_count', searchable: false },
                        { data: 'duration', name: 'duration_watched', searchable: false },
                        { data: 'percentage', name: 'percent_watched', searchable: false },
                        { data: 'last_watched', name: 'last_watched_at', searchable: false },
                        { data: 'link', name: 'link', orderable: false, searchable: false }
                    ],
                    order: [[3, 'desc']],
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                    language: {
                        sLengthMenu: '_MENU_',
                        search: '',
                        searchPlaceholder: '{{ trans('global.search') }}'
                    }
                });
            }

            var notViewedTable = $('.datatables-students-not-viewed');
            if (notViewedTable.length) {
                notViewedTable.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('teacher.resources.studentsNotViewed', $resource->uuid) }}",
                    columns: [
                        { data: 'details', name: 'details' }
                    ],
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                    language: {
                        sLengthMenu: '_MENU_',
                        search: '',
                        searchPlaceholder: '{{ trans('global.search') }}'
                    }
                });
            }
        });
    </script>
@endsection
