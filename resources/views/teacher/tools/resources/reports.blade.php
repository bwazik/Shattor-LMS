@extends('layouts.teacher.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('title', pageTitle(trans('main.reportsOf', ['dependency' => trans('admin/resources.resources')])))

@section('content')
    <!-- Student Stats & Completion Distribution -->
    <div class="row g-6 mb-6 align-items-stretch">
        <!-- Student Stats -->
        <div class="col-md-6">
            <div class="d-flex flex-column h-100">
                <div class="row mt-auto">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="avatar me-4">
                                        <div class="avatar-initial bg-label-primary rounded-3">
                                            <i class="icon-base ri ri-eye-line icon-24px"> </i>
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="d-flex align-items-center">
                                            <h5 class="mb-0 me-2">{{ $data['averageViews'] }}</h5>
                                        </div>
                                        <p class="mb-0">{{ trans('admin/resources.averageViews') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="avatar me-4">
                                        <div class="avatar-initial bg-label-primary rounded-3">
                                            <i class="icon-base ri ri-pie-chart-line icon-24px"> </i>
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="d-flex align-items-center">
                                            <h5 class="mb-0 me-2">{{ $data['averagePercentage'] }}%</h5>
                                        </div>
                                        <p class="mb-0">{{ trans('admin/resources.averageCompletion') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="avatar me-4">
                                        <div class="avatar-initial bg-label-primary rounded-3">
                                            <i class="icon-base ri ri-time-line icon-24px"> </i>
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="d-flex align-items-center">
                                            <h5 class="mb-0 me-2">{{ gmdate("H:i:s", $data['averageDuration']) }}</h5>
                                        </div>
                                        <p class="mb-0">{{ trans('admin/resources.averageDuration') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <p class="d-block mb-0 text-body">{{ trans('admin/quizzes.totalStudents') }}</p>
                        </div>
                        <h4 class="mb-0">{{ $data['totalStudents'] }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-4">
                                <div class="d-flex gap-2 align-items-center mb-2">
                                    <div class="avatar avatar-xs flex-shrink-0">
                                        <div class="avatar-initial rounded bg-label-primary">
                                            <i class="icon-base ri ri-checkbox-circle-line icon-22px"></i>
                                        </div>
                                    </div>
                                    <p class="mb-0">{{ trans('admin/resources.viewed') }}</p>
                                </div>
                                <h4 class="mb-2">{{ $data['viewedPercentage'] }}%</h4>
                                <p class="mb-0">{{ $data['viewedResource'] }}</p>
                            </div>
                            <div class="col-4">
                                <div class="divider divider-vertical">
                                    <div class="divider-text">
                                        <span class="badge-divider-bg bg-label-secondary p-2">VS</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="d-flex gap-2 justify-content-end align-items-center mb-2">
                                    <p class="mb-0">{{ trans('admin/resources.notViewed') }}</p>
                                    <div class="avatar avatar-xs flex-shrink-0">
                                        <div class="avatar-initial rounded bg-label-warning">
                                            <i class="icon-base ri ri-close-circle-line icon-22px"></i>
                                        </div>
                                    </div>
                                </div>
                                <h4 class="mb-2">{{ $data['didntViewPercentage'] }}%</h4>
                                <p class="mb-0">{{ $data['didntViewResource'] }}</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-4">
                            <div class="progress w-100 rounded" style="height: 8px;">
                                <div class="progress-bar bg-primary" style="width: {{ $data['viewedPercentage'] }}%"
                                    role="progressbar" aria-valuenow="{{ $data['viewedPercentage'] }}" aria-valuemin="0"
                                    aria-valuemax="100"></div>
                                <div class="progress-bar bg-warning" role="progressbar"
                                    style="width: {{ $data['didntViewPercentage'] }}%"
                                    aria-valuenow="{{ $data['didntViewPercentage'] }}" aria-valuemin="0"
                                    aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Completion Distribution -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0 mb-1">{{ trans('admin/resources.completionDistribution') }}</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div id="completionDistributionChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Students Who Viewed Datatable & Top Students -->
    <div class="row g-6 mb-6">
        <!-- Students Who Viewed Datatable -->
        <div class="col-md-8">
            <x-datatable id="students-viewed-datatable"
                datatableTitle="{{ trans('admin/resources.studentsWhoViewed') }}">
                <th></th>
                <th>{{ trans('main.name') }}</th>
                <th>{{ trans('admin/resources.views') }}</th>
                <th>{{ trans('admin/resources.duration') }}</th>
                <th>{{ trans('admin/resources.percentage') }}</th>
                <th>{{ trans('admin/resources.lastWatched') }}</th>
                <th>{{ trans('main.link') }}</th>
            </x-datatable>
        </div>
        <!-- Top Students -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="card-title mb-0">
                        <h5 class="m-0 me-2">{{ trans('admin/resources.topViewers') }}</h5>
                    </div>
                </div>
                <div class="px-5 py-4 border border-start-0 border-end-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-heading text-uppercase">{{ trans('admin/students.students') }}</small>
                        <small class="text-heading text-uppercase">{{ trans('admin/resources.totalDuration') }}</small>
                    </div>
                </div>
                <div class="card-body pt-5">
                    @foreach ($data['topStudents'] as $student)
                        <div class="d-flex justify-content-between align-items-center mb-6">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar me-4">
                                    <img src="{{ $student['profile_pic'] ? asset('storage/profiles/students/' . $student['profile_pic']) : asset('assets/img/avatars/default.jpg') }}"
                                        alt="avatart" class="rounded-circle">
                                </div>
                                <div>
                                    <div>
                                        <a target="_blank" href="{{ route('teacher.students.profile.index', $student['uuid']) }}"
                                            class="h6 text-truncate">
                                            <p class="mb-0 fw-medium">{{ $student['name'] }}</p>
                                        </a>
                                        <small class="text-truncate">{{ $student['phone'] }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-0">{{ gmdate("H:i:s", $student['duration_watched']) }}</h6>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!--  Students Who Didn't View Datatable -->
    <div class="row g-6 mb-6">
        <!--  Students Who Didn't View Datatable -->
        <div class="col-md-12">
            <x-datatable id="students-not-viewed-datatable"
                datatableTitle="{{ trans('admin/resources.studentsWhoDidnotView') }}">
                <th></th>
                <th>{{ trans('main.name') }}</th>
            </x-datatable>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartColors = {
                column: {
                    series1: '#72e128',
                    series2: '#ff4d49',
                    bg: '#FFFFFF1A',
                },
            };
            let borderColor, labelColor, headingColor, legendColor;

            if (isDarkStyle) {
                borderColor = config.colors_dark.borderColor;
                labelColor = config.colors_dark.textMuted;
                headingColor = config.colors_dark.headingColor;
                legendColor = config.colors_dark.bodyColor;
            } else {
                borderColor = config.colors.borderColor;
                labelColor = config.colors.textMuted;
                headingColor = config.colors.headingColor;
                legendColor = config.colors.bodyColor;
            }

            initializeDataTable('#students-viewed-datatable',
                "{{ route('teacher.resources.studentsViewed', $resource->uuid) }}", [0, 1, 2],
                [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'details',
                    name: 'details'
                },
                {
                    data: 'views',
                    name: 'views',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'duration',
                    name: 'duration',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'percentage',
                    name: 'percentage',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'last_watched',
                    name: 'last_watched',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'link',
                    name: 'link',
                    orderable: false,
                    searchable: false
                },
                ],
            );
            initializeDataTable('#students-not-viewed-datatable',
                "{{ route('teacher.resources.studentsNotViewed', $resource->uuid) }}", [0, 1, 2],
                [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'details',
                    name: 'details'
                },
                ],
            );

            const completionDistributionChartE = document.querySelector('#completionDistributionChart'),
                completionDistributionChartConfig = {
                    chart: {
                        fontFamily: 'Alexandria',
                        type: 'bar',
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            barHeight: '30%',
                            startingShape: 'rounded',
                            borderRadius: 8
                        }
                    },
                    grid: {
                        borderColor: borderColor,
                        xaxis: {
                            lines: {
                                show: false
                            }
                        },
                        padding: {
                            top: -20,
                            bottom: -12
                        }
                    },
                    colors: config.colors.info,
                    series: [{
                        name: '{{ trans("account.studentsCount") }}',
                        data: Object.values(@json($data['completionDistribution']))
                    }],
                    xaxis: {
                        categories: @json($data['completionRanges']),
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        },
                        labels: {
                            style: {
                                colors: labelColor,
                                fontSize: '13px'
                            }
                        },
                        max: {{ max($data['completionDistribution']) + 2 }},
                        tickAmount: {{ max(1, max($data['completionDistribution']) + 2) }},
                        forceNiceScale: false,
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: labelColor,
                                fontSize: '13px'
                            },
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: (val) => `${val}`
                        }
                    }
                };

            if (typeof completionDistributionChartE !== undefined && completionDistributionChartE !== null) {
                const completionDistributionChart = new ApexCharts(completionDistributionChartE, completionDistributionChartConfig);
                completionDistributionChart.render();
            }
        });
    </script>
@endsection
