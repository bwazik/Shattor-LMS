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
                            <h4 class="mb-0">{{ formatCurrency($pageStatistics['paid']) }} {{ trans('main.currency') }}</h4>
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
                            <h4 class="mb-0">{{ formatCurrency($pageStatistics['unpaid']) }} {{ trans('main.currency') }}</h4>
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
