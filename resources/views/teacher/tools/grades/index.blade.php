@extends('layouts.teacher.master')

@section('page-css')
    <style>
        .custom-option-header {
            flex-direction: column;
        }
    </style>
@endsection

@section('title', pageTitle('admin/grades.grades'))

@section('content')
    <div class="card">
        <div class="card-datatable table-responsive pt-0">
            <div class="card-header d-flex align-items-center justify-content-between flex-column flex-md-row border-bottom">
                <div class="head-label text-center">
                    <h5 class="card-title mb-0">{{ trans('main.datatableTitle', ['item' => trans('admin/grades.grades')]) }}</h5>
                </div>
            </div>
            <div class="card-body mt-4">
                <div class="accordion accordion-popout accordion-header-primary" id="accordionPopout">
                    @forelse($grades as $index => $grade)
                        <div class="accordion-item">
                            <h2 class="accordion-header d-flex align-items-center" id="headingPopout{{ $index }}">
                                <button type="button" class="accordion-button collapsed flex-grow-1 d-flex align-items-center text-start" data-bs-toggle="collapse"
                                    data-bs-target="#accordionPopout{{ $index }}" data-grade-id="{{ $grade->id }}" aria-expanded="false" aria-controls="accordionPopout{{ $index }}">
                                    <span class="flex-grow-1 text-break">{{ $grade->name }}</span>
                                </button>
                            </h2>
                            <div id="accordionPopout{{ $index }}" class="accordion-collapse collapse"
                                aria-labelledby="headingPopout{{ $index }}" data-bs-parent="#accordionPopout" style="">
                                <div class="accordion-body">
                                    <div class="card">
                                        <div class="card-datatable table-responsive pt-0">
                                            <div class="card-header d-flex align-items-center justify-content-between flex-column flex-md-row border-bottom">
                                                <div class="head-label text-center">
                                                    <h5 class="card-title mb-0">{{ trans('admin/groups.groups') }} - {{ $grade->name }}</h5>
                                                </div>
                                            </div>
                                            <table id="datatable{{ $grade->id }}" class="datatables-basic table">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>#</th>
                                                        <th>{{ trans('main.name') }}</th>
                                                        <th>{{ trans('admin/lessons.lessons') }}</th>
                                                        <th>{{ trans('admin/students.students') }}</th>
                                                        <th>{{ trans('admin/groups.day_1') }}</th>
                                                        <th>{{ trans('admin/groups.day_2') }}</th>
                                                        <th>{{ trans('admin/groups.time') }}</th>
                                                        <th>{{ trans('main.status') }}</th>
                                                        <th>{{ trans('main.created_at') }}</th>
                                                        <th>{{ trans('main.updated_at') }}</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-3 text-muted">
                            {{ trans('admin/grades.noGradesFound') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script>
        function getTableId(gradeId) {
            return `#datatable${gradeId}`;
        }

        $(document).ready(function() {
            $('.accordion-button').on('click', function() {
                const accordionItem = $(this).closest('.accordion-item');
                const gradeId = accordionItem.find('button').data('grade-id');
                const tableId = getTableId(gradeId);
                const accordionBody = accordionItem.find('.accordion-collapse');

                if (!accordionBody.hasClass('show') && !$.fn.DataTable.isDataTable(tableId)) {
                    initializeDataTable(
                        tableId,
                        "{{ route('teacher.grades.groups', ['gradeId' => ':gradeId']) }}".replace(':gradeId', gradeId),
                        [2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
                        [
                            { data: "", orderable: false, searchable: false },
                            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                            { data: 'name', name: 'name' },
                            { data: 'lessons', name: 'lessons', orderable: false, searchable: false },
                            { data: 'students', name: 'students', orderable: false, searchable: false },
                            { data: 'day_1', name: 'day_1' },
                            { data: 'day_2', name: 'day_2' },
                            { data: 'time', name: 'time' },
                            { data: 'is_active', name: 'is_active', orderable: false, searchable: false },
                            { data: 'created_at', name: 'created_at', orderable: false, searchable: false },
                            { data: 'updated_at', name: 'updated_at', orderable: false, searchable: false },
                        ]
                    );
                }
            });
        });
    </script>
@endsection
