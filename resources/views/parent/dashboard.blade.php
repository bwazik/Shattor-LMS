@extends('layouts.parent.master')

@section('title', pageTitle(trans('layouts/sidebar.dashboard')))

@section('content')
    <div class="row g-6">
        @foreach ($students as $student)
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <div class="avatar avatar-lg me-3">
                                <img src="{{ $student->profile_pic ? asset('storage/' . $student->profile_pic) : asset('assets/img/avatars/default.jpg') }}" 
                                     alt="{{ $student->name }}" class="rounded-circle">
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-0">{{ $student->name }}</h5>
                                <small class="text-muted">{{ $student->grade->name ?? '-' }}</small>
                            </div>
                        </div>
                        
                        <div class="d-flex flex-column gap-2 mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">{{ trans('admin/students.studentNumber') }}</span>
                                <span class="fw-medium">{{ $student->username }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">{{ trans('layouts/sidebar.quizzes') }}</span>
                                <span class="badge bg-label-info">{{ $student->quizzes_count }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">{{ trans('layouts/sidebar.assignments') }}</span>
                                <span class="badge bg-label-primary">{{ $student->assignments_count }}</span>
                            </div>
                        </div>

                        <div class="d-grid">
                            <a href="{{ route('parent.students.profile.index', $student->uuid) }}" class="btn btn-outline-primary waves-effect">
                                <i class="ri-user-settings-line me-1_5"></i>
                                {{ trans('main.details') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @if($students->isEmpty())
            <div class="col-12">
                <div class="text-center py-10">
                    <img src="{{ asset('assets/img/illustrations/misc-coming-soon-illustration.png') }}" alt="no-students" class="img-fluid mb-4" width="200">
                    <h5 class="text-muted">لم يتم العثور على أبناء مسجلين.</h5>
                </div>
            </div>
        @endif
    </div>
@endsection