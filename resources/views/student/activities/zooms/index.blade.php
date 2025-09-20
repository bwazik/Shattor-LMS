@extends('layouts.student.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/zooms.zooms'))

@section('content')
    <!-- DataTable -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/zooms.zooms')]) }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('main.topic') }}</th>
        <th>{{ trans('main.duration') }}</th>
        <th>{{ trans('main.start_time') }}</th>
        <th>{{ trans('main.join_url') }}</th>
    </x-datatable>
    <!--/ DataTable -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('student.zooms.index') }}", [1, 2, 3, 4],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'topic', name: 'topic' },
                { data: 'duration', name: 'duration', orderable: false, searchable: false },
                { data: 'start_time', name: 'start_time', orderable: false, searchable: false },
                { data: 'join_url', name: 'join_url', orderable: false, searchable: false},
            ],
        );
    </script>
@endsection
