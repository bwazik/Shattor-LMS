@extends('layouts.teacher.master')

@section('page-css')

@endsection

@section('title', pageTitle('layouts/sidebar.dashboard'))

@section('content')
    <x-datatable datatableTitle="{{ trans('admin/lessons.todayLessons') }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('main.title') }}</th>
        <th>{{ trans('main.attendance') }}</th>
        <th>{{ trans('main.group') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.actions') }}</th>
    </x-datatable>

    <x-datatable id="dublicated" datatableTitle="الطلبة المكررين">
        <th></th>
        <th>#</th>
        <th>{{ trans('main.name') }}</th>
        <th>{{ trans('main.grade') }}</th>
    </x-datatable>
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('teacher.dashboard') }}", [2, 3, 4, 5, 6, 7],
            [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'title',
                    name: 'title'
                },
                {
                    data: 'attendances',
                    name: 'attendances'
                },
                {
                    data: 'group_id',
                    name: 'group_id'
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
        );

        initializeDataTable('#dublicated', "{{ route('teacher.dublicatedStudents') }}", [1, 2, 3],
            [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'details',
                    name: 'details'
                },
                {
                    data: 'grade_id',
                    name: 'grade_id'
                }
            ],
        );
    </script>
@endsection
