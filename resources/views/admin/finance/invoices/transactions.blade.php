@extends('layouts.admin.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/transactions.transactions'))

@section('content')
    <!-- DataTable with Buttons -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/transactions.transactions')]) }} - {{ $invoice->student->name }} - {{ $invoice->fee->name }} - {{ $invoice->student->grade->name }}">
        <th></th>
        <th>{{ trans('main.invoice') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.student') }}</th>
        <th>{{ trans('main.amount') }}</th>
        <th>{{ trans('main.balance_after') }}</th>
        <th>{{ trans('main.description') }}</th>
        <th>{{ trans('main.paymentMethod') }}</th>
        <th>{{ trans('main.date') }}</th>
        <th>{{ trans('main.created_at') }}</th>
    </x-datatable>
    <!--/ DataTable with Buttons -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('admin.invoices.transactions', $invoice->id) }}", [2, 3, 4, 5, 6, 7],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'invoice_id', name: 'invoice_id', orderable: false, searchable: false },
                { data: 'type', name: 'type', orderable: false, searchable: false },
                { data: 'student_id', name: 'student_id' },
                { data: 'amount', name: 'amount' },
                { data: 'balance_after', name: 'balance_after' },
                { data: 'description', name: 'description', orderable: false, searchable: false },
                { data: 'payment_method', name: 'payment_method', orderable: false, searchable: false },
                { data: 'date', name: 'date' },
                { data: 'created_at', name: 'created_at' },
            ],
        );
    </script>
@endsection
