@extends('admin.users.students.profile.master')

@section('profile-content')
    <div class="row g-6 mb-6">
        <div class="col-sm-6 col-lg-6">
            <div class="card card-border-shadow-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="me-1">
                            <p class="text-heading mb-1">{{ trans('profile.totalFees') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['totalFees'] }}</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.totalFeesDesc') }}</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-info rounded-3">
                                <div class="ri-bank-line ri-28px"></div>
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
                            <p class="text-heading mb-1">{{ trans('profile.totalPaidAmount') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['totalPaidAmount'] }} {{ trans('main.currency') }}</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.totalPaidAmountDesc') }}</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-success rounded-3">
                                <div class="ri-money-dollar-circle-line ri-28px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-6">
            <div class="card card-border-shadow-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="me-1">
                            <p class="text-heading mb-1">{{ trans('profile.unpaidFees') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['unpaidFees'] }}</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.unpaidFeesDesc') }}</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-danger rounded-3">
                                <div class="ri-alert-line ri-28px"></div>
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
                            <p class="text-heading mb-1">{{ trans('profile.favoritePaymentMethod') }}</p>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 me-2">{{ $stats['favoritePaymentMethod'] }}</h4>
                            </div>
                            <small class="mb-0">{{ trans('profile.favoritePaymentMethodDesc') }}</small>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-primary rounded-3">
                                <div class="ri-bank-card-line ri-28px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-datatable id="fees-datatable" cardClasses="mb-6" datatableTitle="{{ trans('admin/fees.fees') }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('main.name') }}</th>
        <th>{{ trans('main.created_at') }}</th>
        <th>{{ trans('main.paymentDate') }}</th>
        <th>{{ trans('main.paymentMethod') }}</th>
        <th>{{ trans('admin/transactions.transactions') }}</th>
    </x-datatable>
@endsection

@section('profile-js')
    <script>
        initializeDataTable('#fees-datatable', "{{ route('admin.students.profile.fees', $student->id) }}?table=fees", [1, 2, 3, 4, 5],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'date', name: 'date', orderable: false, searchable: false },
                { data: 'paymentDate', name: 'paymentDate', orderable: false, searchable: false },
                { data: 'payment_method', name: 'payment_method', orderable: false, searchable: false },
                { data: 'transactions', name: 'transactions', orderable: false, searchable: false },
            ],
        );
    </script>
@endsection
