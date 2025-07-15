@extends('layouts.admin.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('title', pageTitle(trans('main.reportsOf', ['dependency' => trans('admin/lessons.lessons')])))

@section('content')
    <h4 class="mb-5">{{ $lesson->group->teacher->name }} - {{ $lesson->title }} - {{ $lesson->group->grade->name }}</h4>
    <!-- Stats -->
    <div class="row mb-6">
        <div class="col-md-6 mb-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title m-0 me-2">{{ trans('admin/lessons.attendanceStats') }}</h5>
                </div>
                <div class="card-body pb-4">
                    <div class="mb-9">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md">
                                <div class="avatar-initial bg-label-success rounded-3">
                                    <i class="ri-graduation-cap-line ri-24px"></i>
                                </div>
                            </div>
                            <div class="ms-4">
                                <h3 class="mb-0">{{ $stats['total_expected'] }}</h3>
                                <p class="mb-0">{{ trans('admin/lessons.totalExpected') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-5">
                        <h6 class="mb-2">{{ trans('admin/lessons.attendanceRate') }}</h6>
                        <div class="progress w-100 rounded bg-label-success" style="height: 6px">
                            <div class="progress-bar bg-success" style="width: {{ $stats['attendance_rate'] }}%"
                                role="progressbar" aria-valuenow="{{ $stats['attendance_rate'] }}" aria-valuemin="0"
                                aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="mb-5">
                        <h6 class="mb-2">{{ trans('admin/lessons.compensatoryRate') }}</h6>
                        <div class="progress w-100 rounded bg-label-info" style="height: 6px">
                            <div class="progress-bar bg-info" style="width: {{ $stats['compensatory_rate'] }}%"
                                role="progressbar" aria-valuenow="{{ $stats['compensatory_rate'] }}" aria-valuemin="0"
                                aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table">
                            <tbody class="table-border-bottom-0">
                                <tr>
                                    <td class="ps-0 pe-12 pb-4">
                                        <i class="ri-circle-fill ri-14px text-success me-3"></i>
                                        <span
                                            class="text-heading align-middle">{{ trans('admin/lessons.presentStudents') }}</span>
                                    </td>
                                    <td class="text-end pb-4"><span
                                            class="text-heading fw-medium">{{ $stats['present'] }}</span></td>
                                    <td class="pe-0 pb-4">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <span
                                                class="text-heading fw-medium me-2">{{ $stats['percentages']['present'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-0 pe-12 py-4">
                                        <i class="ri-circle-fill ri-14px text-warning me-3"></i>
                                        <span
                                            class="text-heading align-middle">{{ trans('admin/lessons.lateStudents') }}</span>
                                    </td>
                                    <td class="text-end py-4"><span
                                            class="text-heading fw-medium">{{ $stats['late'] }}</span></td>
                                    <td class="pe-0 py-4">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <span
                                                class="text-heading fw-medium me-2">{{ $stats['percentages']['late'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-0 pe-12 pt-4">
                                        <i class="ri-circle-fill ri-14px text-danger me-3"></i>
                                        <span
                                            class="text-heading align-middle">{{ trans('admin/lessons.absentStudents') }}</span>
                                    </td>
                                    <td class="text-end pt-4"><span
                                            class="text-heading fw-medium">{{ $stats['absent'] }}</span></td>
                                    <td class="pe-0 pt-4">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <span
                                                class="text-heading fw-medium me-2">{{ $stats['percentages']['absent'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-0 pe-12 pt-4">
                                        <i class="ri-circle-fill ri-14px text-primary me-3"></i>
                                        <span
                                            class="text-heading align-middle">{{ trans('admin/lessons.compensatedStudents') }}</span>
                                    </td>
                                    <td class="text-end pt-4"><span
                                            class="text-heading fw-medium">{{ $stats['compensated'] }}</span></td>
                                    <td class="pe-0 pt-4">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <span
                                                class="text-heading fw-medium me-2">{{ $stats['percentages']['compensated'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-0 pe-12 pt-4">
                                        <i class="ri-circle-fill ri-14px text-info me-3"></i>
                                        <span
                                            class="text-heading align-middle">{{ trans('admin/lessons.compensatoryStudents') }}</span>
                                    </td>
                                    <td class="text-end pt-4"><span
                                            class="text-heading fw-medium">{{ $stats['compensatory'] }}</span></td>
                                    <td class="pe-0 pt-4">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <span
                                                class="text-heading fw-medium me-2">{{ $stats['percentages']['compensatory'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-0 pe-12 pt-4">
                                        <i class="ri-circle-fill ri-14px text-secondary me-3"></i>
                                        <span
                                            class="text-heading align-middle">{{ trans('admin/lessons.unrecordedStudents') }}</span>
                                    </td>
                                    <td class="text-end pt-4"><span
                                            class="text-heading fw-medium">{{ $stats['unrecorded'] }}</span></td>
                                    <td class="pe-0 pt-4">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <span
                                                class="text-heading fw-medium me-2">{{ $stats['percentages']['unrecorded'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-0">{{ trans('admin/lessons.attendanceStats') }}</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div id="attendancePieChart"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Chart -->
    <div class="row mb-6">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-md-center align-items-start">
                    <h5 class="card-title mb-0">Data Science</h5>
                </div>
                <div class="card-body">
                    <div id="lessonsChart"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Absent and Compensatory Students Datatable -->
    <div class="row mb-6">
        <div class="col-md-5 mb-6">
            <x-datatable id="absent-students-datatable" datatableTitle="{{ trans('admin/lessons.absentStudents') }}">
                <th></th>
                <th>#</th>
                <th>{{ trans('main.student') }}</th>
                <th>{{ trans('main.description') }}</th>
                <th>{{ trans('main.created_at') }}</th>
            </x-datatable>
        </div>
        <div class="col-md-7 mb-6">
            <x-datatable id="compensatory-students-datatable"
                datatableTitle="{{ trans('admin/lessons.compensatoryStudents') }}">
                <th></th>
                <th>#</th>
                <th>{{ trans('main.student') }}</th>
                <th>{{ trans('admin/compensatories.original_lesson') }}</th>
                <th>{{ trans('main.description') }}</th>
                <th>{{ trans('main.status') }}</th>
                <th>{{ trans('main.created_at') }}</th>
            </x-datatable>
        </div>
    </div>
    <!-- Compensated Students Datatable -->
    <div class="row mb-6">
        <div class="col-md-12 mb-6">
            <x-datatable id="compensated-students-datatable"
                datatableTitle="{{ trans('admin/lessons.compensatedStudents') }}">
                <th></th>
                <th>#</th>
                <th>{{ trans('main.student') }}</th>
                <th>{{ trans('admin/compensatories.makeup_lesson') }}</th>
                <th>{{ trans('main.description') }}</th>
                <th>{{ trans('main.status') }}</th>
                <th>{{ trans('main.created_at') }}</th>
            </x-datatable>
        </div>
    </div>
    <!-- Present & late and Unrecorded Students Datatable -->
    <div class="row mb-6">
        <div class="col-md-7 mb-6">
            <x-datatable id="present-late-students-datatable"
                datatableTitle="{{ trans('admin/lessons.presentStudents') }}">
                <th></th>
                <th>#</th>
                <th>{{ trans('main.student') }}</th>
                <th>{{ trans('main.status') }}</th>
                <th>{{ trans('main.description') }}</th>
                <th>{{ trans('main.created_at') }}</th>
            </x-datatable>
        </div>
        <div class="col-md-5 mb-6">
            <x-datatable id="unrecorded-students-datatable"
                datatableTitle="{{ trans('admin/lessons.unrecordedStudents') }}">
                <th></th>
                <th>#</th>
                <th>{{ trans('main.student') }}</th>
                <th>{{ trans('main.created_at') }}</th>
            </x-datatable>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>

    <script>
        let cardColor, headingColor, labelColor, borderColor, legendColor;

        if (isDarkStyle) {
            cardColor = config.colors_dark.cardColor;
            headingColor = config.colors_dark.headingColor;
            labelColor = config.colors_dark.textMuted;
            legendColor = config.colors_dark.bodyColor;
            borderColor = config.colors_dark.borderColor;
        } else {
            cardColor = config.colors.cardColor;
            headingColor = config.colors.headingColor;
            labelColor = config.colors.textMuted;
            legendColor = config.colors.bodyColor;
            borderColor = config.colors.borderColor;
        }

        // Color constant
        const chartColors = {
            column: {
                series1: '#28a745',
                series2: '#ffc107',
                series3: '#dc3545',
                series4: '#007bff',
                bg: '#FFFFFF1A'
            },
            donut: {
                series1: '#28a745',
                series2: '#ffc107',
                series3: '#dc3545',
                series4: '#007bff',
                series5: '#17a2b8',
                series6: '#6c757d'
            }
        };

        const lessonStats = @json($lessonStats);

        const lessonsChartEl = document.querySelector('#lessonsChart'),
            lessonsChartConfig = {
                chart: {
                    height: 400,
                    fontFamily: 'Alexandria',
                    type: 'bar',
                    stacked: true,
                    parentHeightOffset: 0,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        columnWidth: '8%',
                        colors: {
                            backgroundBarColors: Array(4).fill(chartColors.column.bg),
                            backgroundBarRadius: 10
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    show: true,
                    position: 'top',
                    horizontalAlign: 'start',
                    fontSize: '13px',
                    markers: {
                        width: 10,
                        height: 10
                    },
                    labels: {
                        colors: legendColor,
                        useSeriesColors: false
                    }
                },
                colors: [
                    chartColors.column.series1,
                    chartColors.column.series2,
                    chartColors.column.series3,
                    chartColors.column.series4
                ],
                stroke: {
                    show: true,
                    colors: ['transparent']
                },
                grid: {
                    borderColor: borderColor,
                    xaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                series: lessonStats.length ? [{
                        name: '{{ trans('admin/lessons.presentStudents') }}',
                        data: lessonStats.map(stat => stat.present || 0)
                    },
                    {
                        name: '{{ trans('admin/lessons.lateStudents') }}',
                        data: lessonStats.map(stat => stat.late || 0)
                    },
                    {
                        name: '{{ trans('admin/lessons.absentStudents') }}',
                        data: lessonStats.map(stat => stat.absent || 0)
                    },
                    {
                        name: '{{ trans('admin/lessons.compensatedStudents') }}',
                        data: lessonStats.map(stat => stat.compensated || 0)
                    }
                ] : [],
                xaxis: {
                    categories: lessonStats.length ? lessonStats.map(stat => stat.title || 'N/A') : ['N/A'],
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
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: labelColor,
                            fontSize: '13px'
                        }
                    }
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return `${val} {{ trans('admin/students.student') }}`;
                        }
                    }
                },
                noData: {
                    text: '{{ trans('main.datatable.empty_table') }}',
                    align: 'center',
                    verticalAlign: 'middle',
                    offsetX: 0,
                    offsetY: 0,
                    style: {
                        color: headingColor,
                        fontSize: '14px',
                        fontFamily: 'Alexandria'
                    }
                }
            };
        if (typeof lessonsChartEl !== undefined && lessonsChartEl !== null) {
            const lessonsChart = new ApexCharts(lessonsChartEl, lessonsChartConfig);
            lessonsChart.render();
        }

        const percentages = @json($stats['percentages']);

        const attendancePieChartEl = document.querySelector('#attendancePieChart'),
            attendancePieChartConfig = {
                chart: {
                    height: 390,
                    fontFamily: 'Alexandria',
                    type: 'donut'
                },
                labels: [
                    '{{ trans('admin/lessons.presentStudents') }}',
                    '{{ trans('admin/lessons.lateStudents') }}',
                    '{{ trans('admin/lessons.absentStudents') }}',
                    '{{ trans('admin/lessons.compensatedStudents') }}',
                    '{{ trans('admin/lessons.compensatoryStudents') }}',
                    '{{ trans('admin/lessons.unrecordedStudents') }}'
                ],
                series: [
                    {{ $stats['present'] }},
                    {{ $stats['late'] }},
                    {{ $stats['absent'] }},
                    {{ $stats['compensated'] }},
                    {{ $stats['compensatory'] }},
                    {{ $stats['unrecorded'] }}
                ],
                colors: [
                    chartColors.donut.series1,
                    chartColors.donut.series2,
                    chartColors.donut.series3,
                    chartColors.donut.series4,
                    chartColors.donut.series5,
                    chartColors.donut.series6
                ],
                stroke: {
                    show: false,
                    curve: 'straight'
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opt) {
                        return parseFloat(val).toFixed(1) + '%';
                    },
                    style: {
                        fontSize: '15px',
                        fontWeight: 'normal'
                    },
                    dropShadow: {
                        enabled: false
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val, {
                            seriesIndex
                        }) {
                            const keys = ['present', 'late', 'absent', 'compensated', 'compensatory', 'unrecorded'];
                            const percentage = percentages[keys[seriesIndex]];
                            return `${val} (${percentage.toFixed(1)}%)`;
                        }
                    }
                },
                legend: {
                    show: true,
                    position: 'bottom',
                    fontSize: '13px',
                    markers: {
                        offsetX: -3,
                        width: 10,
                        height: 10
                    },
                    itemMargin: {
                        vertical: 3,
                        horizontal: 10
                    },
                    labels: {
                        colors: legendColor,
                        useSeriesColors: false
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                name: {
                                    fontSize: '2rem',
                                    fontFamily: 'Alexandria',
                                    color: headingColor,
                                    offsetY: -10
                                },
                                value: {
                                    fontSize: '0.9375rem',
                                    fontWeight: 500,
                                    fontFamily: 'Alexandria',
                                    color: legendColor,
                                    offsetY: 10,
                                    formatter: function(val) {
                                        return parseInt(val, 10);
                                    }
                                },
                                total: {
                                    show: true,
                                    fontSize: '0.9375rem',
                                    fontWeight: 500,
                                    fontFamily: 'Alexandria',
                                    color: headingColor,
                                    label: '{{ trans('admin/lessons.totalExpected') }}',
                                    formatter: function(w) {
                                        return {{ $stats['total_expected'] }};
                                    }
                                }
                            }
                        }
                    }
                },
                responsive: [{
                        breakpoint: 992,
                        options: {
                            chart: {
                                height: 380
                            },
                            legend: {
                                position: 'bottom',
                                labels: {
                                    colors: legendColor,
                                    useSeriesColors: false
                                }
                            }
                        }
                    },
                    {
                        breakpoint: 576,
                        options: {
                            chart: {
                                height: 320
                            },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        labels: {
                                            show: true,
                                            name: {
                                                fontSize: '1.5rem'
                                            },
                                            value: {
                                                fontSize: '1rem'
                                            },
                                            total: {
                                                fontSize: '1.5rem'
                                            }
                                        }
                                    }
                                }
                            },
                            legend: {
                                position: 'bottom',
                                labels: {
                                    colors: legendColor,
                                    useSeriesColors: false
                                }
                            }
                        }
                    },
                    {
                        breakpoint: 420,
                        options: {
                            chart: {
                                height: 280
                            },
                            legend: {
                                show: false
                            }
                        }
                    },
                    {
                        breakpoint: 360,
                        options: {
                            chart: {
                                height: 250
                            },
                            legend: {
                                show: false
                            }
                        }
                    }
                ]
            };
        if (typeof attendancePieChartEl !== undefined && attendancePieChartEl !== null) {
            const attendancePieChart = new ApexCharts(attendancePieChartEl, attendancePieChartConfig);
            attendancePieChart.render();
        }

        initializeDataTable('#absent-students-datatable', "{{ route('admin.lessons.absent', $lesson->id) }}", [1, 2, 3,
                4
            ],
            [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'student_id',
                    name: 'student_id'
                },
                {
                    data: 'details',
                    name: 'student_id'
                },
                {
                    data: 'note',
                    name: 'note',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    orderable: false,
                    searchable: false
                },
            ],
        );
        initializeDataTable('#compensated-students-datatable', "{{ route('admin.lessons.compensated', $lesson->id) }}",
            [1, 2, 3, 4, 5],
            [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'student_id',
                    name: 'student_id'
                },
                {
                    data: 'details',
                    name: 'student_id'
                },
                {
                    data: 'makeup_lesson_title',
                    name: 'makeup_lesson_title',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'reason',
                    name: 'reason',
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
                    data: 'created_at',
                    name: 'created_at',
                    orderable: false,
                    searchable: false
                },
            ],
        );
        initializeDataTable('#present-late-students-datatable',
            "{{ route('admin.lessons.present_late', $lesson->id) }}", [1, 2, 3, 4, 5],
            [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'student_id',
                    name: 'student_id'
                },
                {
                    data: 'details',
                    name: 'student_id'
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'note',
                    name: 'note',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    orderable: false,
                    searchable: false
                },
            ],
        );
        initializeDataTable('#compensatory-students-datatable',
            "{{ route('admin.lessons.compensatory', $lesson->id) }}", [1, 2, 3, 4, 5],
            [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'student_id',
                    name: 'student_id'
                },
                {
                    data: 'details',
                    name: 'student_id'
                },
                {
                    data: 'original_lesson_title',
                    name: 'original_lesson_title',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'reason',
                    name: 'reason',
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
                    data: 'created_at',
                    name: 'created_at',
                    orderable: false,
                    searchable: false
                },
            ],
        );
        initializeDataTable('#unrecorded-students-datatable', "{{ route('admin.lessons.unrecorded', $lesson->id) }}", [
                1, 2, 3, 4, 5
            ],
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
                    data: 'details',
                    name: 'student_id'
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    orderable: false,
                    searchable: false
                },
            ],
        );
    </script>
@endsection
