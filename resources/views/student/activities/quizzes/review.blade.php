@extends('layouts.student.master')

@section('page-css')
    <style>
        .custom-option.checked {
            --bs-custom-option-border-color: var(--bs-primary) !important;
            border: 1.5px solid var(--bs-custom-option-border-color) !important;
            margin: 0 !important;
        }

        .custom-option.checked-success {
            --bs-custom-option-border-color: var(--bs-success) !important;
            border: 1.5px solid var(--bs-custom-option-border-color) !important;
            margin: 0 !important;
        }

        .custom-option.checked-danger {
            --bs-custom-option-border-color: var(--bs-danger) !important;
            border: 1.5px solid var(--bs-custom-option-border-color) !important;
            margin: 0 !important;
        }
    </style>
@endsection

@section('title', pageTitle(trans('admin/quizzes.reviewAnswers').' - '.$quiz->name))

@section('content')
    <div class="row g-6">
        <div class="col-lg-12">
            <div class="card mb-6">
                <h5 class="card-header">{{ $quiz->name }} - {{ trans('main.mr') }}:
                    {{ $quiz->teacher->name }}</h5>
                <div class="card-body pt-1">
                    <div class="nav-align-top nav-tabs-shadow">
                        <ul class="nav nav-tabs" role="tablist">
                            @if ($quiz->show_result)
                                <li class="nav-item" role="presentation">
                                    <button type="button" class="nav-link active waves-effect" role="tab"
                                        data-bs-toggle="tab" data-bs-target="#result-tab" aria-controls="result-tab"
                                        aria-selected="true">{{ trans('admin/quizzes.result') }}</button>
                                </li>
                            @endif
                            @if ($quiz->allow_review)
                                <li class="nav-item" role="presentation">
                                    <button type="button"
                                        class="nav-link {{ !$quiz->show_result ? 'active' : '' }} waves-effect"
                                        role="tab" data-bs-toggle="tab" data-bs-target="#answers-tab"
                                        aria-controls="answers-tab"
                                        aria-selected="{{ !$quiz->show_result ? 'true' : 'false' }}">{{ trans('admin/answers.answers') }}</button>
                                </li>
                            @endif
                        </ul>
                        <div class="tab-content">
                            @if ($quiz->show_result)
                                <div class="tab-pane fade active show" id="result-tab" role="tabpanel">
                                    <x-alert type="info" dismissible="true" icon="openai" :message="$aiMessage" />
                                    <div class="card-body">
                                        <div class="row g-4">
                                            <div class="col-xl-2 col-md-4 col-sm-6">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar" data-bs-toggle="tooltip"
                                                        data-bs-original-title="{{ trans('admin/quizzes.rank') }}">
                                                        <div class="avatar-initial bg-label-info rounded">
                                                            <i class="icon-base ri ri-trophy-line icon-24px"></i>
                                                        </div>
                                                    </div>
                                                    <div class="card-info">
                                                        <h5 class="mb-0">{{ $reviewData['formattedRank'] }}</h5>
                                                        <p class="mb-0">{{ trans('admin/quizzes.rank') }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-2 col-md-4 col-sm-6">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar" data-bs-toggle="tooltip"
                                                        data-bs-original-title="{{ trans('admin/quizzes.totalQuestions') }}">
                                                        <div class="avatar-initial bg-label-primary rounded">
                                                            <i class="icon-base ri ri-file-list-line icon-24px"></i>
                                                        </div>
                                                    </div>
                                                    <div class="card-info">
                                                        <h5 class="mb-0">{{ $quiz->questions_count }}</h5>
                                                        <p class="mb-0">{{ trans('admin/quizzes.totalQuestions') }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-2 col-md-4 col-sm-6">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar" data-bs-toggle="tooltip"
                                                        data-bs-original-title="{{ trans('admin/quizzes.correctAnswers') }}">
                                                        <div class="avatar-initial bg-label-success rounded">
                                                            <i class="icon-base ri ri-checkbox-circle-line icon-24px"></i>
                                                        </div>
                                                    </div>
                                                    <div class="card-info">
                                                        <h5 class="mb-0">{{ $reviewData['correctAnswers'] }}</h5>
                                                        <p class="mb-0">{{ trans('admin/quizzes.correctAnswers') }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-2 col-md-4 col-sm-6">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar" data-bs-toggle="tooltip"
                                                        data-bs-original-title="{{ trans('admin/quizzes.wrongAnswers') }}">
                                                        <div class="avatar-initial bg-label-danger rounded">
                                                            <i class="icon-base ri ri-close-circle-line icon-24px"></i>
                                                        </div>
                                                    </div>
                                                    <div class="card-info">
                                                        <h5 class="mb-0">{{ $reviewData['wrongAnswers'] }}</h5>
                                                        <p class="mb-0">{{ trans('admin/quizzes.wrongAnswers') }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-2 col-md-4 col-sm-6">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar" data-bs-toggle="tooltip"
                                                        data-bs-original-title="{{ trans('admin/quizzes.unanswered') }}">
                                                        <div class="avatar-initial bg-label-warning rounded">
                                                            <i class="icon-base ri ri-question-line icon-24px"></i>
                                                        </div>
                                                    </div>
                                                    <div class="card-info">
                                                        <h5 class="mb-0">{{ $reviewData['unanswered'] }}</h5>
                                                        <p class="mb-0">{{ trans('admin/quizzes.unanswered') }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <h5 class="mt-4">{{ trans('main.score') }}: {{ round($result->total_score, 1) }}
                                        {{ trans('account.from') }}
                                        {{ $quiz->total_score }}</h5>
                                    <div class="progress" style="height: 12px;">
                                        @php
                                            $percentage = $result->percentage;
                                            $progressClass =
                                                $percentage < 50
                                                    ? 'bg-danger'
                                                    : ($percentage <= 75
                                                        ? 'bg-warning'
                                                        : 'bg-success');
                                        @endphp
                                        <div class="progress-bar {{ $progressClass }}" role="progressbar"
                                            style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}"
                                            aria-valuemin="0" aria-valuemax="100">
                                            {{ round($percentage, 1) }}%
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if ($quiz->allow_review)
                                <div class="tab-pane fade {{ !$quiz->show_result ? 'active show' : '' }}"
                                    id="answers-tab" role="tabpanel">
                                    @if ($reviewData['questions']->isEmpty())
                                        <p class="text-center">{{ trans('main.errorMessage') }}</p>
                                    @else
                                        <div class="accordion accordion-popout" id="accordionPopout">
                                            @foreach ($reviewData['questions'] as $index => $question)
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header"
                                                        id="headingPopout{{ $question->question_id }}">
                                                        <button type="button"
                                                            class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#accordionPopout{{ $question->question_id }}"
                                                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                                            aria-controls="accordionPopout{{ $question->question_id }}">
                                                            {{ $index + 1 }} -
                                                            {{ $question->question->question_text ?? 'N/A' }}
                                                            @if ($question->answer_id)
                                                                @if ($question->question->answers->firstWhere('id', $question->answer_id)->is_correct)
                                                                    <span
                                                                        class="badge badge-center rounded-pill bg-label-success ms-2"><i
                                                                            class="icon-base ri ri-check-line"></i></span>
                                                                @else
                                                                    <span
                                                                        class="badge badge-center rounded-pill bg-label-danger ms-2"><i
                                                                            class="icon-base ri ri-close-line"></i></span>
                                                                @endif
                                                            @else
                                                                <span
                                                                    class="badge rounded-pill bg-label-danger text-capitalized ms-2">{{ trans('admin/quizzes.unanswered') }}</span>
                                                            @endif
                                                        </button>
                                                    </h2>
                                                    <div id="accordionPopout{{ $question->question_id }}"
                                                        class="accordion-collapse collapse"
                                                        aria-labelledby="headingPopout{{ $question->question_id }}"
                                                        data-bs-parent="#accordionPopout">
                                                        <div class="accordion-body">
                                                            <div class="row">
                                                                @foreach ($question->sorted_answers as $option)
                                                                    <div class="col-md-12 mb-3">
                                                                        <div
                                                                            class="form-check custom-option custom-option-icon {{ $option->is_correct ? 'checked-success' : ($question->answer_id == $option->id && !$option->is_correct ? 'checked-danger' : '') }} {{ $question->answer_id == $option->id ? 'checked' : '' }}">
                                                                            <label
                                                                                class="form-check-label custom-option-content"
                                                                                for="customRadioIcon{{ $option->id }}">
                                                                                <span class="custom-option-body">
                                                                                    <small>{{ $option->answer_text }}</small>
                                                                                    <small
                                                                                        class="text-secondary">({{ trans('main.score') }}:
                                                                                        {{ number_format($option->score) }})</small>
                                                                                </span>
                                                                                <input
                                                                                    name="customRadioIcon-{{ $question->question_id }}"
                                                                                    class="form-check-input"
                                                                                    type="radio"
                                                                                    value="{{ $option->id }}"
                                                                                    id="customRadioIcon{{ $option->id }}"
                                                                                    {{ $question->answer_id == $option->id ? 'checked' : '' }}
                                                                                    disabled>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')

@endsection
