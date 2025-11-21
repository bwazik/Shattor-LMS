@extends('layouts.teacher.master')

@section('page-css')
@endsection

@section('title', pageTitle(trans('main.details') . ' - ' . $resource->title))

@section('content')
    <div class="row g-6">
        <div class="col-lg-12">
            <div class="card mb-6">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title m-0 me-2">{{ $resource->title }} - {{ $student->name }}</h5>
                </div>
                <div class="card-body pt-1">
                    <div class="nav-align-top nav-tabs-shadow">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link active waves-effect" role="tab"
                                    data-bs-toggle="tab" data-bs-target="#result-tab" aria-controls="result-tab"
                                    aria-selected="true">{{ trans('admin/resources.overview') }}</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link waves-effect" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#events-tab" aria-controls="events-tab"
                                    aria-selected="false">{{ trans('admin/resources.videoEvents') }}</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link waves-effect" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#details-tab" aria-controls="details-tab"
                                    aria-selected="false">{{ trans('admin/quizzes.anotherDetails') }}</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade active show" id="result-tab" role="tabpanel">
                                <div class="card-body">
                                    <div class="row g-4">
                                        <div class="col-xl-3 col-md-6 col-sm-6">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="avatar" data-bs-toggle="tooltip"
                                                    data-bs-original-title="{{ trans('main.views') }}">
                                                    <div class="avatar-initial bg-label-primary rounded">
                                                        <i class="icon-base ri ri-eye-line icon-24px"></i>
                                                    </div>
                                                </div>
                                                <div class="card-info">
                                                    <h5 class="mb-0">{{ $view->views }}</h5>
                                                    <p class="mb-0">{{ trans('main.views') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-6 col-sm-6">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="avatar" data-bs-toggle="tooltip"
                                                    data-bs-original-title="{{ trans('main.duration') }}">
                                                    <div class="avatar-initial bg-label-info rounded">
                                                        <i class="icon-base ri ri-time-line icon-24px"></i>
                                                    </div>
                                                </div>
                                                <div class="card-info">
                                                    <h5 class="mb-0">{{ gmdate("H:i:s", $view->duration_watched) }}</h5>
                                                    <p class="mb-0">{{ trans('main.duration') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-6 col-sm-6">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="avatar" data-bs-toggle="tooltip"
                                                    data-bs-original-title="{{ trans('main.percentage') }}">
                                                    <div class="avatar-initial bg-label-success rounded">
                                                        <i class="icon-base ri ri-pie-chart-line icon-24px"></i>
                                                    </div>
                                                </div>
                                                <div class="card-info">
                                                    <h5 class="mb-0">{{ $view->percent_watched }}%</h5>
                                                    <p class="mb-0">{{ trans('main.percentage') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-6 col-sm-6">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="avatar" data-bs-toggle="tooltip"
                                                    data-bs-original-title="{{ trans('main.status') }}">
                                                    <div
                                                        class="avatar-initial rounded @if ($view->is_banned) bg-label-danger @else bg-label-success @endif">
                                                        <i
                                                            class="icon-base ri @if ($view->is_banned) ri-prohibited-line @else ri-checkbox-circle-line @endif icon-24px"></i>
                                                    </div>
                                                </div>
                                                <div class="card-info">
                                                    <h5 class="mb-0">
                                                        @if ($view->is_banned)
                                                            {{ trans('admin/resources.banned') }}
                                                        @else
                                                            {{ trans('main.allowed') }}
                                                        @endif
                                                    </h5>
                                                    <p class="mb-0">{{ trans('main.status') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <h5 class="mt-4">{{ trans('admin/resources.completion') }}: {{ $view->percent_watched }}%</h5>
                                <div class="progress" style="height: 12px;">
                                    @php
                                        $percentage = $view->percent_watched;
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
                            <div class="tab-pane fade" id="events-tab" role="tabpanel">
                                @if ($events->isEmpty())
                                    <p class="text-center">{{ trans('main.datatable.empty_table') }}</p>
                                @else
                                    <div class="table-responsive text-nowrap">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>{{ trans('admin/resources.event') }}</th>
                                                    <th>{{ trans('main.details') }}</th>
                                                    <th>{{ trans('admin/quizzes.detectedAt') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach ($events as $event)
                                                    @php
                                                        $badgeColor = match(true) {
                                                            in_array($event->event_type, ['view', 'play', 'pause']) => 'bg-label-success',
                                                            in_array($event->event_type, ['fullscreen_enter', 'fullscreen_exit']) => 'bg-label-primary',
                                                            in_array($event->event_type, ['qualitychange', 'ratechange']) => 'bg-label-warning',
                                                            str_starts_with($event->event_type, 'security_') => 'bg-label-danger',
                                                            default => 'bg-label-secondary',
                                                        };
                                                        $eventName = trans('admin/resources.events.' . $event->event_type);
                                                        if ($eventName === 'admin/resources.events.' . $event->event_type) {
                                                            $eventName = ucfirst(str_replace('_', ' ', $event->event_type));
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <span class="badge {{ $badgeColor }}">{{ $eventName }}</span>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $eventData = json_decode($event->data, true);
                                                            @endphp
                                                            @if(!empty($eventData))
                                                                <small>
                                                                @foreach($eventData as $key => $value)
                                                                    <strong>{{ ucfirst($key) }}:</strong> {{ $value }}<br>
                                                                @endforeach
                                                                </small>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td>{{ $event->detected_at ? isoFormat($event->detected_at) : 'N/A' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                            <div class="tab-pane fade" id="details-tab" role="tabpanel">
                                <ul class="timeline card-timeline mb-0 mt-5">
                                    <li class="timeline-item timeline-item-transparent">
                                        <span class="timeline-point timeline-point-success"></span>
                                        <div class="timeline-event">
                                            <div class="timeline-header mb-3">
                                                <h6 class="mb-0">{{ trans('admin/resources.firstViewed') }}</h6>
                                            </div>
                                            <p class="mb-2">{{ $view->created_at ? isoFormat($view->created_at) : 'N/A' }}</p>
                                        </div>
                                    </li>
                                    <li class="timeline-item timeline-item-transparent">
                                        <span class="timeline-point timeline-point-info"></span>
                                        <div class="timeline-event">
                                            <div class="timeline-header mb-3">
                                                <h6 class="mb-0">{{ trans('admin/resources.lastViewed') }}</h6>
                                            </div>
                                            <p class="mb-2">{{ $view->updated_at ? isoFormat($view->updated_at) : 'N/A' }}</p>
                                        </div>
                                    </li>
                                    <li class="timeline-item timeline-item-transparent">
                                        <span class="timeline-point timeline-point-warning"></span>
                                        <div class="timeline-event">
                                            <div class="timeline-header mb-3">
                                                <h6 class="mb-0">{{ trans('main.duration') }}</h6>
                                            </div>
                                            <p class="mb-2">
                                                {{ gmdate("H:i:s", $view->duration_watched) }}
                                            </p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
