@extends('layouts.student.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/offlineQuizzes.offlineQuizzes'))

@section('content')
    <!-- DataTable -->
    <x-datatable datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/offlineQuizzes.offlineQuizzes')]) }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('main.title') }}</th>
        <th>{{ trans('main.teacher') }}</th>
        <th>{{ trans('admin/quizzes.finalScore') }}</th>
        <th>{{ trans('main.conducted_at') }}</th>
        <th>{{ trans('admin/quizzes.showMeMyScore') }}</th>
    </x-datatable>
    <!--/ DataTable -->
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('student.offline-quizzes.index') }}", [2, 3, 4, 5, 6, 7],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'teacher_id', name: 'teacher_id' },
                { data: 'score', name: 'score', orderable: false, searchable: false },
                { data: 'conducted_at', name: 'conducted_at', orderable: false, searchable: false },
                { data: 'scoreLink', name: 'scoreLink', orderable: false, searchable: false },
            ],
        );

        @if(session('error'))
            toastr.error("{{ session('error') }}");
        @endif
        @if(session('success'))
            toastr.success("{{ session('success') }}");
        @endif
    </script>
@endsection
