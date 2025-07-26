@extends('teacher.users.students.profile.master')

@section('profile-content')
    <x-datatable cardClasses="mb-6" datatableTitle="{{ trans('admin/groups.groups') }}">
        <th></th>
        <th>#</th>
        <th width="80%">{{ trans('main.name') }}</th>
    </x-datatable>
@endsection

@section('profile-js')
    <script>
        initializeDataTable('#datatable', "{{ route('teacher.students.profile.index', $student->uuid) }}", [1, 2],
            [
                { data: "", orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
            ],
        );
    </script>
@endsection
