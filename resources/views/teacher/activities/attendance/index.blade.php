@extends('layouts.teacher.master')

@section('page-css')
    <style>
        .attendance-legend span {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 5px;
            vertical-align: middle;
            border-radius: 4px;
            opacity: 0.7;
        }

        .status-present {
            background-color: #bdf5a1;
        }

        .status-absent {
            background-color: #ffb5b2;
        }

        .status-late {
            background-color: #ffde96;
        }

        .status-compensatory {
            background-color: #91e4ff;
        }

        .status-container {
            display: flex;
            justify-content: center;
            gap: 0.4rem;
        }

        .status-btn {
            min-width: 45px;
            padding: 0.4rem 0.7rem;
            transition: all 0.2s ease-in-out;
            border: 2px solid transparent;
            font-weight: 500;
            color: black;
            background-color: white;
        }

        .status-btn[data-status="1"] {
            border-color: #28a745;
            color: #28a745;
        }

        .status-btn[data-status="2"] {
            border-color: #dc3545;
            color: #dc3545;
        }

        .status-btn[data-status="3"] {
            border-color: #ffc107;
            color: #ffc107;
        }

        .status-btn[data-status="4"] {
            border-color: #17a2b8;
            color: #17a2b8;
        }

        .status-btn.active {
            font-weight: bold;
            color: white !important;
            border-color: transparent !important;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            transform: scale(1.05);
        }

        .status-btn.active[data-status="1"] {
            background-color: #28a745;
        }

        .status-btn.active[data-status="2"] {
            background-color: #dc3545;
        }

        .status-btn.active[data-status="3"] {
            background-color: #ffc107;
        }

        .status-btn.active[data-status="4"] {
            background-color: #17a2b8;
        }

        #qr-video {
            display: none;
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        #qr-video.active {
            display: block;
        }

        .scan-tab-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 50vh;
        }
    </style>
@endsection

@section('title', pageTitle('admin/attendance.attendance'))

@section('content')
    @include('teacher.activities.attendance.form')
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link active waves-effect" role="tab" data-bs-toggle="tab"
                data-bs-target="#datatable-tab" aria-controls="datatable-tab"
                aria-selected="true">{{ trans('admin/attendance.attendanceDatatable') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link waves-effect" role="tab" data-bs-toggle="tab"
                data-bs-target="#scan-tab" aria-controls="scan-tab"
                aria-selected="false">{{ trans('admin/attendance.qrScan') }}</button>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade active show" id="datatable-tab" role="tabpanel">
            <x-datatable
                datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('admin/attendance.attendance')]) }}"
                dataToggle="offcanvas" otherButton="{{ trans('admin/attendance.submit') }}" otherIcon="ri-checkbox-circle-line"
                <th></th>
                <th>#</th>
                <th>{{ trans('main.student') }}</th>
                <th style="width: 80px;">{{ trans('main.type') }}</th>
                <th>{{ trans('main.description') }}</th>
                <th>{{ trans('main.actions') }}</th>
            </x-datatable>
        </div>
        <div class="tab-pane fade" id="scan-tab" role="tabpanel">
            <div class="scan-tab-container">
                <div class="text-center">
                    <video id="qr-video" class="mb-3 rounded" style="overflow: hidden;"></video>
                    <button id="start-scanner" class="btn btn-success">{{ trans('admin/attendance.startScanner') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    @include('teacher.activities.attendance.js')
    <script>
        // Setup students form
        initializeSelect2('students-form', 'grade_id');
        initializeSelect2('students-form', 'group_id');
        initializeSelect2('students-form', 'lesson_id');
        initializeSelect2('select2-primary', 'status_1');
        fetchMultipleDataByAjax('#students-form #grade_id', "{{ route('teacher.fetch.grade.groups', '__ID__') }}",
            '#students-form #group_id', 'grade_id', 'GET')
        fetchMultipleDataByAjax('#students-form #group_id', "{{ route('teacher.fetch.groups.lessons', '__ID__') }}",
            '#students-form #lesson_id', 'group_id', 'GET');
        fetchSingleDataByAjax('#students-form #lesson_id', "{{ route('teacher.fetch.lessons.data', '__ID__') }}", [{
            targetSelector: '#students-form #date',
            dataKey: 'date'
        }], 'lesson_id');
    </script>
@endsection
