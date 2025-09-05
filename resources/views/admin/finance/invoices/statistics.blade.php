<div class="card mb-6">
    <div class="card-widget-separator-wrapper">
        <div class="card-body card-widget-separator">
            <div class="row gy-4 gy-sm-1">
                <div class="col-sm-6 col-lg-3">
                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                        <div>
                            <h4 class="mb-0">{{ $pageStatistics['clients'] }}</h4>
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
                            <h4 class="mb-0" id="paid-amount">{{ formatCurrency($pageStatistics['paid'] ?? 00) }}
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
                            <h4 class="mb-0" id="unpaid-amount">{{ formatCurrency($pageStatistics['unpaid'] ?? 00) }}
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
    <div class="col-12 mt-4" id="pin-form-container">
        <div class="card">
            <div class="card-body">
                <h5>{{ trans('admin/fees.enterSecurityCode') }}</h5>
                <form id="verify-pin-form" action="{{ route('teacher.invoices.verify-pin') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <div
                            class="auth-input-wrapper d-flex align-items-center justify-content-between numeral-mask-wrapper">
                            <input type="tel"
                                class="form-control auth-input text-center numeral-mask h-px-50 mx-sm-1 my-2"
                                maxlength="1" autofocus />
                            <input type="tel"
                                class="form-control auth-input text-center numeral-mask h-px-50 mx-sm-1 my-2"
                                maxlength="1" />
                            <input type="tel"
                                class="form-control auth-input text-center numeral-mask h-px-50 mx-sm-1 my-2"
                                maxlength="1" />
                            <input type="tel"
                                class="form-control auth-input text-center numeral-mask h-px-50 mx-sm-1 my-2"
                                maxlength="1" />
                            <input type="tel"
                                class="form-control auth-input text-center numeral-mask h-px-50 mx-sm-1 my-2"
                                maxlength="1" />
                            <input type="tel"
                                class="form-control auth-input text-center numeral-mask h-px-50 mx-sm-1 my-2"
                                maxlength="1" />
                        </div>
                        <input id="pin" type="hidden" name="pin" />
                    </div>
                    <button type="submit"
                        class="btn btn-primary d-grid w-100 mb-5">{{ trans('main.submit') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
