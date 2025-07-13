@extends('layouts.teacher.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('title', pageTitle(trans('main.reportsOf', ['dependency' => trans('admin/fees.fees')])))

@section('content')
    <h4 class="mb-5">{{ $fee->name }} - {{ $fee->grade->name }}</h4>
    <!-- Fee Stats -->
    <div class="card mb-6">
        <div class="card-widget-separator-wrapper">
            <div class="card-body card-widget-separator">
                <div class="row gy-4 gy-sm-1">
                    <div class="col-sm-6 col-lg-3">
                        <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                            <div>
                                <h4 class="mb-0">{{ $pageStatistics['totalStudents'] }}</h4>
                                <p class="mb-0">{{ trans('admin/students.students') }}</p>
                            </div>
                            <div class="avatar me-sm-6">
                                <span class="avatar-initial rounded-3">
                                    <i class="icon-base ri ri-user-line text-heading icon-26px"></i>
                                </span>
                            </div>
                        </div>
                        <hr class="d-none d-sm-block d-lg-none me-6">
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                            <div>
                                <h4 class="mb-0">{{ $pageStatistics['invoices'] }}</h4>
                                <p class="mb-0">{{ trans('admin/invoices.invoices') }}</p>
                            </div>
                            <div class="avatar me-lg-6">
                                <span class="avatar-initial rounded-3">
                                    <i class="icon-base ri ri-pages-line text-heading icon-26px"></i>
                                </span>
                            </div>
                        </div>
                        <hr class="d-none d-sm-block d-lg-none">
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                            <div>
                                <h4 class="mb-0">{{ formatCurrency($pageStatistics['paid']) }}
                                    {{ trans('main.currency') }}</h4>
                                <p class="mb-0">{{ trans('admin/invoices.paid') }}</p>
                            </div>
                            <div class="avatar me-sm-6">
                                <span class="avatar-initial rounded-3">
                                    <i class="icon-base ri ri-wallet-line text-heading icon-26px"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="mb-0">{{ formatCurrency($pageStatistics['unpaid']) }}
                                    {{ trans('main.currency') }}</h4>
                                <p class="mb-0">{{ trans('admin/invoices.unpaid') }}</p>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded-3">
                                    <i class="icon-base ri ri-money-dollar-circle-line text-heading icon-26px"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Payment Trends -->
    <div class="row mb-6">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-md-center align-items-start">
                    <h5 class="card-title mb-0">{{ trans('admin/fees.paymentTrends') }}</h5>
                </div>
                <div class="card-body">
                    <div id="paymentTrendsChart"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Studnet Stats & Students Without Fee Datatable -->
    <div class="row mb-6 align-items-stretch">
        <div class="col-md-7 mb-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <p class="d-block mb-0 text-body">{{ trans('admin/quizzes.totalStudents') }}</p>
                    </div>
                    <h4 class="mb-0">{{ $pageStatistics['totalStudentsWithInvoices'] }}</h4>
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
                                <p class="mb-0">{{ trans('admin/fees.paidFee') }}</p>
                            </div>
                            <h4 class="mb-2">{{ $pageStatistics['paidFeePercentage'] }}%</h4>
                            <p class="mb-0">{{ $pageStatistics['paidFee'] }}</p>
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
                                <p class="mb-0">{{ trans('admin/fees.didntPayFee') }}</p>
                                <div class="avatar avatar-xs flex-shrink-0">
                                    <div class="avatar-initial rounded bg-label-warning">
                                        <i class="icon-base ri ri-close-circle-line icon-22px"></i>
                                    </div>
                                </div>
                            </div>
                            <h4 class="mb-2">{{ $pageStatistics['didntPayFeePercentage'] }}%</h4>
                            <p class="mb-0">{{ $pageStatistics['didntPayFee'] }}</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-4">
                        <div class="progress w-100 rounded" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: {{ $pageStatistics['paidFeePercentage'] }}%"
                                role="progressbar" aria-valuenow="{{ $pageStatistics['paidFeePercentage'] }}" aria-valuemin="0"
                                aria-valuemax="100"></div>
                            <div class="progress-bar bg-warning" role="progressbar"
                                style="width: {{ $pageStatistics['didntPayFeePercentage'] }}%"
                                aria-valuenow="{{ $pageStatistics['didntPayFeePercentage'] }}" aria-valuemin="0"
                                aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5 mb-6">
            <x-datatable id="students-without-fee-datatable"
                datatableTitle="{{ trans('admin/fees.studentsWithoutFee') }}">
                <th></th>
                <th>#</th>
                <th>{{ trans('main.student') }}</th>
            </x-datatable>
        </div>
    </div>
    <!-- Students Who Paid Datatable -->
    <div class="row mb-6">
        <div class="col-md-12">
            <x-datatable id="students-paid-fee-datatable"
                datatableTitle="{{ trans('admin/fees.studentsPaidFee') }}">
                <th></th>
                <th>#</th>
                <th>{{ trans('main.student') }}</th>
                <th>{{ trans('main.amount') }}</th>
                <th>{{ trans('main.created_at') }}</th>
                <th>{{ trans('main.paymentDate') }}</th>
                <th>{{ trans('main.paymentMethod') }}</th>
                <th>{{ trans('admin/transactions.transactions') }}</th>
            </x-datatable>
        </div>
    </div>
    <!-- Students Who Didn't Pay Datatable -->
    <div class="row mb-6">
        <div class="col-md-12">
            <x-datatable id="students-havenot-paid-fee-datatable"
                datatableTitle="{{ trans('admin/fees.studentsHavenotPaidFee') }}">
                <th></th>
                <th>#</th>
                <th>{{ trans('main.student') }}</th>
                <th>{{ trans('main.amount') }}</th>
                <th>{{ trans('main.created_at') }}</th>
                <th>{{ trans('main.status') }}</th>
                <th>{{ trans('admin/transactions.transactions') }}</th>
            </x-datatable>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartColors = {
                area: {
                    series1: '#ab7efd',
                    series2: '#b992fe',
                    series3: '#e0cffe'
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

            initializeDataTable('#students-paid-fee-datatable', "{{ route('teacher.fees.studentsPaidFee', $fee->uuid) }}", [1, 2, 3, 4, 5],
                [
                    { data: "", orderable: false, searchable: false },
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'details', name: 'student_id' },
                    { data: 'amount', name: 'amount', orderable: false, searchable: false },
                    { data: 'date', name: 'date' },
                    { data: 'paymentDate', name: 'paymentDate', orderable: false, searchable: false },
                    { data: 'payment_method', name: 'payment_method', orderable: false, searchable: false },
                    { data: 'transactions', name: 'transactions', orderable: false, searchable: false },
                ],
            );
            initializeDataTable('#students-havenot-paid-fee-datatable', "{{ route('teacher.fees.studentsHavenotPaidFee', $fee->uuid) }}", [1, 2, 3, 4, 5],
                [
                    { data: "", orderable: false, searchable: false },
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'details', name: 'student_id' },
                    { data: 'amount', name: 'amount', orderable: false, searchable: false },
                    { data: 'date', name: 'date' },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'transactions', name: 'transactions', orderable: false, searchable: false },
                ],
            );
            initializeDataTable('#students-without-fee-datatable', "{{ route('teacher.fees.studentsWithoutFee', $fee->uuid) }}", [1, 2, 3],
                [
                    { data: "", orderable: false, searchable: false },
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'details', name: 'student_id' },
                ],
            );

            const paymentTrendsChartE = document.querySelector('#paymentTrendsChart'),
                paymentTrendsData = @json($data['paymentTrends']),
                maxSubmissions = Math.max(...paymentTrendsData, 1),
                yAxisMax = Math.max(5, Math.ceil(maxSubmissions * 1.2)),
                paymentTrendsChartConfig = {
                    chart: {
                        height: 400,
                        fontFamily: 'Alexandria',
                        type: 'area',
                        toolbar: {
                            show: false
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        show: true,
                        curve: 'smooth',
                        width: 2
                    },
                    legend: {
                        show: false,
                    },
                    grid: {
                        borderColor: borderColor,
                        xaxis: {
                            lines: {
                                show: true
                            }
                        }
                    },
                    colors: [chartColors.area.series1],
                    series: [{
                        name: '{{ trans('admin/fees.paymentsCount') }}',
                        data: @json($data['paymentTrends'])
                    }],
                    xaxis: {
                        categories: @json($data['paymentDates']),
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
                            },
                        },
                        tickAmount: 14,
                        tickPlacement: 'on'
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: labelColor,
                                fontSize: '13px'
                            },
                        },
                        min: 0,
                        max: yAxisMax,
                        tickAmount: 5,
                        forceNiceScale: false
                    },
                    fill: {
                        opacity: 1,
                        type: 'solid'
                    },
                    tooltip: {
                        y: {
                            formatter: (val) => `${val}`
                        },
                        x: {
                            formatter: (val, {
                                dataPointIndex
                            }) => @json($data['paymentDates'])[dataPointIndex] || ''
                        }
                    },
                };
            if (typeof paymentTrendsChartE !== undefined && paymentTrendsChartE !== null) {
                const paymentTrendsChart = new ApexCharts(paymentTrendsChartE, paymentTrendsChartConfig);
                paymentTrendsChart.render();
            }
        });
    </script>
@endsection
