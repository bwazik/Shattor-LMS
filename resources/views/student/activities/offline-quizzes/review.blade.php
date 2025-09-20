@extends('layouts.student.master')

@section('page-css')
@endsection

@section('title', pageTitle(trans('admin/quizzes.reviewAnswers').' - '.$offlineQuiz->name))

@section('content')
    <div class="row g-6">
        <div class="col-lg-12">
            <div class="card mb-6">
                <h5 class="card-header">{{ $offlineQuiz->name }} - {{ trans('main.mr') }}:
                    {{ $offlineQuiz->teacher->name }}</h5>
                <div class="card-body pt-1">
                    <div class="nav-align-top nav-tabs-shadow">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link active waves-effect" role="tab"
                                    data-bs-toggle="tab" data-bs-target="#result-tab" aria-controls="result-tab"
                                    aria-selected="true">{{ trans('admin/quizzes.result') }}</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade active show" id="result-tab" role="tabpanel">
                                <x-alert type="info" dismissible="true" icon="openai" :message="$aiMessage" />
                                <div class="card-body">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="avatar" data-bs-toggle="tooltip"
                                                    data-bs-original-title="{{ trans('admin/quizzes.rank') }}">
                                                    <div class="avatar-initial bg-label-info rounded">
                                                        <i class="icon-base ri ri-trophy-line icon-24px"></i>
                                                    </div>
                                                </div>
                                                <div class="card-info">
                                                    <h5 class="mb-0">{{ $rank }}</h5>
                                                    <p class="mb-0">{{ trans('admin/quizzes.rank') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <h5 class="mt-4">{{ trans('main.score') }}: {{ round($result->total_score, 1) }}
                                    {{ trans('account.from') }}
                                    {{ $offlineQuiz->score }}</h5>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')

@endsection
