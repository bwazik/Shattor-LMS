@extends('layouts.student.master')

@section('page-css')

@endsection

@section('title', pageTitle($quiz->name))

@section('content')
    <div class="row g-6">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-6 gap-1">
                        <div class="me-1">
                            <h5 class="mb-0">{{ $quiz->name }}</h5>
                            <p class="mb-0">{{ trans('main.mr') }}: <span
                                    class="fw-medium text-heading">{{ $quiz->teacher->name ?? 'N/A' }} </span></p>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-label-success rounded-pill">{{ $quiz->grade->name ?? 'N/A' }}</span>
                            <i class="ri-share-forward-line ri-24px mx-4 cursor-pointer" data-bs-toggle="tooltip"
                                title="{{ trans('main.share') }}"></i>
                        </div>
                    </div>
                    <div class="card academy-content shadow-none border">
                        <div class="card-body pt-3">
                            <h5>{{ trans('admin/assignments.details') }}</h5>
                            <div class="d-flex flex-wrap row-gap-2">
                                <div class="me-12">
                                    <p class="text-nowrap mb-3">
                                        <i class="ri-timer-line ri-20px me-2"></i>{{ trans('main.duration') }}:
                                        {{ formatDuration($quiz->duration) }}
                                    </p>
                                    <p class="text-nowrap mb-3">
                                        <i
                                            class="ri-question-line ri-20px me-2"></i>{{ trans('admin/quizzes.totalQuestions') }}:
                                        {{ $quiz->questions_count }}
                                    </p>
                                    <p class="text-nowrap mb-3">
                                        <i class="ri-trophy-line ri-20px me-2"></i>{{ trans('admin/quizzes.totalScore') }}:
                                        {{ $quiz->total_score }}
                                    </p>
                                    <p class="text-nowrap mb-3">
                                        <i class="ri-play-circle-line ri-20px me-2"></i>{{ trans('main.start_time') }}:
                                        {{ isoFormat($quiz->start_time) }}
                                    </p>
                                    <p class="text-nowrap mb-3">
                                        <i class="ri-stop-circle-line ri-20px me-2"></i>{{ trans('main.end_time') }}:
                                        {{ isoFormat($quiz->end_time) }}
                                    </p>
                                </div>
                            </div>
                            <hr class="my-6" />
                            <h5>{{ trans('admin/assignments.instructions') }}</h5>
                            <x-alert type="info" :dismissible=false icon="openai" :message="trans('admin/quizzes.instructionsAlert')"/>
                            <button class="btn btn-primary me-2 waves-effect waves-light" type="button"
                                data-bs-toggle="collapse" data-bs-target="#instructions" aria-expanded="true"
                                aria-controls="instructions">{{ trans('main.showInstructions') }}</button>
                            @if (!now()->between($quiz->start_time, $quiz->end_time))
                                <a href="#"
                                    class="btn btn-secondary me-2 waves-effect waves-light">{{ trans('account.notAvailable') }}</a>
                            @elseif ($result && $result->status == 1)
                                <a href="{{ route('student.quizzes.take', $quiz->uuid) }}"
                                    class="btn btn-primary waves-effect waves-light">{{ trans('admin/quizzes.resumeQuiz') }}</a>
                            @else
                                <a href="{{ route('student.quizzes.take', $quiz->uuid) }}"
                                    class="btn btn-primary waves-effect waves-light">{{ trans('admin/quizzes.startQuiz') }}</a>
                            @endif
                            <div class="collapse mt-3" id="instructions" style="">
                                <div class="demo-inline-spacing mt-4">
                                    <ol class="list-group list-group-numbered">
                                        <li class="list-group-item list-group-item-action list-group-item-warning waves-effect waves-light">{{ e(trans('admin/quizzes.instructions.screen_lock')) }}</li>
                                        <li class="list-group-item list-group-item-action list-group-item-warning waves-effect waves-light">{{ e(trans('admin/quizzes.instructions.cheating')) }}</li>
                                        <li class="list-group-item list-group-item-action waves-effect waves-light">{{ e(trans('admin/quizzes.instructions.navigation')) }}</li>
                                        <li class="list-group-item list-group-item-action waves-effect waves-light">{{ e(trans('admin/quizzes.instructions.saving')) }}</li>
                                        <li class="list-group-item list-group-item-action waves-effect waves-light">{{ e(trans('admin/quizzes.instructions.submission')) }}</li>
                                        <li class="list-group-item list-group-item-action waves-effect waves-light">{{ e(trans('admin/quizzes.instructions.warnings')) }}</li>
                                        <li class="list-group-item list-group-item-action waves-effect waves-light">{{ e(trans('admin/quizzes.instructions.review')) }}</li>
                                        <li class="list-group-item list-group-item-action waves-effect waves-light">{{ e(trans('admin/quizzes.instructions.closure')) }}</li>
                                    </ol>
                                </div>
                            </div>
                            <hr class="my-6" />
                            <h5>{{ app()->getLocale() === 'ar' ? 'ال' : '' }}{{ trans('admin/teachers.teacher') }}
                            </h5>
                            <div class="d-flex justify-content-start align-items-center user-name">
                                <div class="avatar-wrapper">
                                    <div class="avatar me-4">
                                        <img src="{{ $quiz->teacher->profile_pic ? asset('storage/profiles/teachers/' . $quiz->teacher->profile_pic) : asset('assets/img/avatars/default.jpg') }}"
                                            alt="Avatar" class="rounded-circle" />
                                    </div>
                                </div>
                                <div class="d-flex flex-column">
                                    <h6 class="mb-1">{{ $quiz->teacher->name ?? 'N/A' }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script>
        toggleShareButton();
    </script>
@endsection
