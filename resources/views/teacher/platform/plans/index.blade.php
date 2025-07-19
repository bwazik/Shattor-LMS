@extends('layouts.teacher.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-pricing.css') }}" />
@endsection

@section('title', pageTitle('admin/plans.plans'))

@section('content')
    <div class="alert alert-warning alert-dismissible" role="alert">
        <h5 class="alert-heading d-flex align-items-center">
            <span class="alert-icon rounded"><i class="icon-base ri ri-alert-line icon-md"></i></span>
            {{ trans('admin/plans.subscription_warnings.title') }}
        </h5>
        <hr style="color: #fdb528 !important">
        <div class="d-flex flex-column gap-2">
            <p class="fw-medium mb-0">1 - {{ trans('admin/plans.subscription_warnings.trial_numbers') }}</p>
            <p class="mb-0">2 - {{ trans('admin/plans.subscription_warnings.billing') }}<a href="{{ route('teacher.billing.index') }}" class="text-warning fw-medium text-decoration-underline">({{ trans('main.clickHere') }})</a></p>
            <p class="mb-0">3 - {{ trans('admin/plans.subscription_warnings.term_period') }}</p>
            <p class="mb-0">4 - {{ trans('admin/plans.subscription_warnings.yearly_period') }}</p>
            <p class="mb-0">5 - {{ trans('admin/plans.subscription_warnings.auto_renewal') }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <div class="card">
        <!-- Pricing Plans -->
        <div class="pb-sm-12 pb-2 rounded-top">
            <div class="container py-12">
                <h4 class="text-center mb-2 mt-0 mt-md-4">{{ trans('admin/plans.plans_header') }}</h4>
                <p class="text-center mb-2">{{ trans('admin/plans.plans_description') }}</p>
                <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 pt-7 mb-6">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio1"
                            value="monthly">
                        <label class="form-check-label" for="inlineRadio1">{{ trans('main.monthly') }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2"
                            value="term">
                        <label class="form-check-label" for="inlineRadio2">{{ trans('main.termly') }} (3.5 {{ trans('main.months') }})</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio3"
                            value="yearly">
                        <label class="form-check-label" for="inlineRadio3">{{ trans('main.yearly') }} (9.5 {{ trans('main.months') }})</label>
                    </div>
                    <div class="mt-n5 ms-n5 ml-2 mb-8 d-none d-sm-flex align-items-center gap-1">
                        <i class="ri-corner-left-down-fill ri-24px text-muted scaleX-n1-rtl"></i>
                        <span
                            class="badge badge-sm bg-label-primary rounded-pill mb-2">{{ trans('admin/plans.discount') }}</span>
                    </div>
                </div>

                <div class="pricing-plans row mx-4 gy-3 px-lg-12">
                    @foreach ($plans as $plan)
                        <div class="col-lg mb-lg-0 mb-3">
                            <div class="card border {{ $plan->id === 3 ? 'border-primary' : 'shadow-none' }}">
                                <div class="card-body {{ $plan->id === 3 ? 'position-relative pt-4' : 'pt-12' }}">
                                    @if ($plan->id === 3)
                                        <div class="position-absolute end-0 me-6 top-0 mt-6">
                                            <span class="badge bg-label-primary rounded-pill">{{ trans('admin/plans.popular') }}</span>
                                        </div>
                                    @endif
                                    <div class="{{ $plan->id === 3 ? 'my-5 pt-6' : 'mt-3 mb-5' }} text-center">
                                        @if ($plan->id === 1)
                                            <img src="{{ asset('assets/img/illustrations/pricing-basic.png') }}"
                                                alt="{{ $plan->name }}" height="100" />
                                        @elseif($plan->id === 2)
                                            <img src="{{ asset('assets/img/illustrations/pricing-standard.png') }}"
                                                alt="{{ $plan->name }}" height="100" />
                                        @else
                                            <img src="{{ asset('assets/img/illustrations/pricing-enterprise.png') }}"
                                                alt="{{ $plan->name }}" height="100" />
                                        @endif
                                    </div>
                                    <h4 class="card-title text-center text-capitalize mb-2">{{ $plan->name }}</h4>
                                    <p class="text-center mb-5">{{ $plan->description }}</p>
                                    <div class="text-center">
                                        <div class="d-flex justify-content-center">
                                            <sup class="h6 pricing-currency mt-2 mb-0 me-1 text-body">{{ trans('main.currency') }}</sup>
                                            <h1 class="price-toggle price-monthly text-primary mb-0">{{ number_format($plan->monthly_price, 0) }}</h1>
                                            <h1 class="price-toggle price-term text-primary mb-0 d-none">{{ number_format($plan->term_price / 3.5, 0) }}</h1>
                                            <h1 class="price-toggle price-yearly text-primary mb-0 d-none">{{ number_format($plan->year_price / 9.5, 0) }}</h1>
                                            <sub class="h6 text-body pricing-duration mt-auto mb-1 ms-1">/{{ trans('main.monthly') }}</sub>
                                        </div>
                                        <small class="price-term price-term-toggle text-muted d-none">
                                            {{ trans('main.currency') }} {{ number_format($plan->term_price, 0) }} / {{ trans('main.termly') }}
                                        </small>
                                        <small class="price-yearly price-yearly-toggle text-muted d-none">
                                            {{ trans('main.currency') }} {{ number_format($plan->year_price, 0) }} / {{ trans('main.yearly') }}
                                        </small>
                                    </div>

                                    <ul class="list-group ps-6 my-5 pt-4">
                                        <li class="mb-4">{{ trans('admin/plans.student_limit') }}: {{ $plan->student_limit }}</li>
                                        <li class="mb-4">{{ trans('admin/plans.parent_limit') }}: {{ $plan->parent_limit }}</li>
                                        <li class="mb-4">{{ trans('admin/plans.assistant_limit') }}: {{ $plan->assistant_limit }}</li>
                                        <li class="mb-4">{{ trans('admin/plans.group_limit') }}: {{ $plan->group_limit }}</li>
                                        <li class="mb-4 price-toggle price-monthly">{{ trans('admin/plans.quiz_monthly_limit') }}: {{ $plan->quiz_monthly_limit }}</li>
                                        <li class="mb-4 price-toggle price-term d-none">{{ trans('admin/plans.quiz_term_limit') }}: {{ $plan->quiz_term_limit }}</li>
                                        <li class="mb-4 price-toggle price-yearly d-none">{{ trans('admin/plans.quiz_year_limit') }}: {{ $plan->quiz_year_limit }}</li>
                                        <li class="mb-4 price-toggle price-monthly">{{ trans('admin/plans.assignment_monthly_limit') }}: {{ $plan->assignment_monthly_limit }}</li>
                                        <li class="mb-4 price-toggle price-term d-none">{{ trans('admin/plans.assignment_term_limit') }}: {{ $plan->assignment_term_limit }}</li>
                                        <li class="mb-4 price-toggle price-yearly d-none">{{ trans('admin/plans.assignment_year_limit') }}: {{ $plan->assignment_year_limit }}</li>
                                        <li class="mb-4 price-toggle price-monthly">{{ trans('admin/plans.resource_monthly_limit') }}: {{ $plan->resource_monthly_limit }}</li>
                                        <li class="mb-4 price-toggle price-term d-none">{{ trans('admin/plans.resource_term_limit') }}: {{ $plan->resource_term_limit }}</li>
                                        <li class="mb-4 price-toggle price-yearly d-none">{{ trans('admin/plans.resource_year_limit') }}: {{ $plan->resource_year_limit }}</li>
                                        <li class="mb-4 price-toggle price-monthly">{{ trans('admin/plans.zoom_monthly_limit') }}: {{ $plan->zoom_monthly_limit }}</li>
                                        <li class="mb-4 price-toggle price-term d-none">{{ trans('admin/plans.zoom_term_limit') }}: {{ $plan->zoom_term_limit }}</li>
                                        <li class="mb-4 price-toggle price-yearly d-none">{{ trans('admin/plans.zoom_year_limit') }}: {{ $plan->zoom_year_limit }}</li>
                                        <li class="mb-4">{{ trans('admin/plans.attendance_reports') }}: @if ($plan->attendance_reports === 1) <i class="icon-base ri ri-checkbox-circle-line icon-22px text-success me-2"></i> @else <i class="icon-base ri ri-close-circle-line icon-22px text-danger me-2"></i> @endif</li>
                                        <li class="mb-4">{{ trans('admin/plans.financial_reports') }}: @if ($plan->financial_reports === 1) <i class="icon-base ri ri-checkbox-circle-line icon-22px text-success me-2"></i> @else <i class="icon-base ri ri-close-circle-line icon-22px text-danger me-2"></i> @endif</li>
                                        <li class="mb-4">{{ trans('admin/plans.performance_reports') }}: @if ($plan->performance_reports === 1) <i class="icon-base ri ri-checkbox-circle-line icon-22px text-success me-2"></i> @else <i class="icon-base ri ri-close-circle-line icon-22px text-danger me-2"></i> @endif</li>
                                        <li class="mb-4">{{ trans('admin/plans.whatsapp_messages') }}: @if ($plan->whatsapp_messages === 1) <i class="icon-base ri ri-checkbox-circle-line icon-22px text-success me-2"></i> @else <i class="icon-base ri ri-close-circle-line icon-22px text-danger me-2"></i> @endif</li>
                                        <li class="mb-4">{{ trans('admin/plans.instant_customer_service') }}: @if ($plan->instant_customer_service === 1) <i class="icon-base ri ri-checkbox-circle-line icon-22px text-success me-2"></i> @else <i class="icon-base ri ri-close-circle-line icon-22px text-danger me-2"></i> @endif</li>
                                    </ul>

                                    <button type="button" tabindex="0" data-bs-toggle="offcanvas" data-bs-target="#add-modal"
                                        id="add-button" data-plan_id="{{ $plan->id }}" data-plan_name="{{ $plan->name }}"
                                        class="btn {{ $plan->id === Auth::guard('teacher')->user()->plan_id || ($subscription && $plan->id === $subscription->plan_id) ? 'btn-success' : ($plan->id === 3 ? 'btn-primary' : 'btn-outline-primary') }} d-grid w-100">
                                        {{ $plan->id === Auth::guard('teacher')->user()->plan_id || ($subscription && $plan->id === $subscription->plan_id) ? trans('admin/plans.current_plan') : trans('admin/plans.subscripe_now') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <!--/ Pricing Plans -->
    </div>

    <!-- Subscription Modal -->
    <x-offcanvas offcanvasType="add" offcanvasTitle="{{ trans('main.addItem', ['item' => trans('admin/teacherSubscriptions.subscription')]) }}"
        action="{{ route('teacher.subscriptions.insert') }}">
        <input type="hidden" id="plan_id" class="form-control" name="plan_id">
        <x-basic-input context="offcanvas" type="text" name="plan_name" label="{{ trans('main.plan') }}" disabled/>
        <x-select-input context="offcanvas" name="period" label="{{ trans('main.period') }}" :options="[1 => trans('main.monthly'), 2 => trans('main.termly'), 3 => trans('main.yearly')]"/>
        <x-basic-input context="offcanvas" type="number" name="amount" label="{{ trans('main.amount') }}" disabled/>
        <x-basic-input context="offcanvas" type="text" name="start_date" classes="flatpickr-date" label="{{ trans('main.start_date') }}" placeholder="YYYY-MM-DD" value="{{ now()->format('Y-m-d') }}" disabled/>
        <x-basic-input context="offcanvas" type="text" name="end_date" classes="flatpickr-date" label="{{ trans('main.end_date') }}" placeholder="YYYY-MM-DD" value="{{ now()->addDays(30)->format('Y-m-d') }}" disabled/>
    </x-offcanvas>
@endsection

@section('page-js')
    <script src="{{ asset('assets/js/pages-pricing.js') }}"></script>

    <script>
        // Setup add modal
        setupModal({
            buttonId: '#add-button',
            modalId: '#add-modal',
            fields: {
                plan_id: button => button.data('plan_id'),
                plan_name: button => button.data('plan_name'),
                period: () => 1,
            }
        });
        fetchSingleDataByAjax('#add-form #period', "{{ route('teacher.fetch.plans.data', ['__SECOND_ID__', '__ID__']) }}", [
            { targetSelector: '#add-form #amount', dataKey: 'amount' },
        ], 'period', 'GET', 'plan_id');

        let fields = ['plan_id', 'period'];
        handleFormSubmit('#add-form', fields, '#add-modal', 'offcanvas', null, '{{ route("teacher.billing.index") }}');
    </script>
@endsection
