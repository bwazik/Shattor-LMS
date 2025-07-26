@extends('teacher.users.students.profile.master')

@section('profile-content')
    <x-datatable id="lessons-datatable" cardClasses="mb-6" datatableTitle="{{ trans('admin/lessons.lessons') }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('main.title') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('admin/compensatories.makeup_lesson') }}</th>
    </x-datatable>

    <x-datatable id="compensatories-datatable" cardClasses="mb-6" datatableTitle="{{ trans('admin/compensatories.compensatories') }}">
        <th></th>
        <th>#</th>
        <th>{{ trans('admin/compensatories.original_lesson') }}</th>
        <th>{{ trans('admin/compensatories.makeup_lesson') }}</th>
        <th>{{ trans('main.status') }}</th>
        <th>{{ trans('main.reason') }}</th>
    </x-datatable>
@endsection

@section('profile-js')
    <script>
        initializeDataTable('#lessons-datatable', "{{ route('teacher.students.profile.attendance', $student->uuid) }}?table=lessons", [1, 2, 3, 4],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'title', name: 'title' },
                { data: 'attendance_status', name: 'attendance_status', orderable: false, searchable: false },
                { data: 'makeup_status', name: 'makeup_status', orderable: false, searchable: false }
            ],
        );

        initializeDataTable('#compensatories-datatable', "{{ route('teacher.students.profile.attendance', $student->uuid) }}", [1, 2, 3, 4, 5],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'original_lesson_id', name: 'original_lesson_id', orderable: false, searchable: false },
                { data: 'makeup_lesson_id', name: 'makeup_lesson_id', orderable: false, searchable: false },
                { data: 'status', name: 'status', orderable: false, searchable: false },
                { data: 'reason', name: 'reason', orderable: false, searchable: false },
            ],
        );
    </script>
@endsection
