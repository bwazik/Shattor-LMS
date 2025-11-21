@extends('layouts.admin')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <!-- Student Info -->
            <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="user-avatar-section">
                            <div class=" d-flex align-items-center flex-column">
                                <img class="img-fluid rounded mb-3 pt-1 mt-4" src="{{ $student->profile_pic ? asset('storage/profiles/students/' . $student->profile_pic) : asset('assets/img/avatars/1.png') }}" height="100" width="100" alt="User avatar" />
                                <div class="user-info text-center">
                                    <h4 class="mb-2">{{ $student->name }}</h4>
                                    <span class="badge bg-label-secondary mt-1">{{ trans('admin/users.student') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-around flex-wrap mt-3 pt-3 pb-4 border-bottom">
                            <div class="d-flex align-items-start me-4 mt-3 gap-2">
                                <span class="badge bg-label-primary p-2 rounded"><i class='ti ti-eye ti-sm'></i></span>
                                <div>
                                    <p class="mb-0 fw-semibold">{{ $view->views }}</p>
                                    <small>{{ trans('admin/resources.views') }}</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mt-3 gap-2">
                                <span class="badge bg-label-success p-2 rounded"><i class='ti ti-clock ti-sm'></i></span>
                                <div>
                                    <p class="mb-0 fw-semibold">{{ gmdate("H:i:s", $view->duration_watched) }}</p>
                                    <small>{{ trans('admin/resources.duration') }}</small>
                                </div>
                            </div>
                        </div>
                        <p class="mt-4 small text-uppercase text-muted">{{ trans('admin/users.details') }}</p>
                        <div class="info-container">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <span class="fw-semibold me-1">{{ trans('global.phone') }}:</span>
                                    <span>{{ $student->phone }}</span>
                                </li>
                                <li class="mb-2">
                                    <span class="fw-semibold me-1">{{ trans('admin/resources.firstWatched') }}:</span>
                                    <span>{{ $view->first_watched_at ? $view->first_watched_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                                </li>
                                <li class="mb-2">
                                    <span class="fw-semibold me-1">{{ trans('admin/resources.lastWatched') }}:</span>
                                    <span>{{ $view->last_watched_at ? $view->last_watched_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                                </li>
                                <li class="mb-2">
                                    <span class="fw-semibold me-1">{{ trans('admin/resources.completion') }}:</span>
                                    <span>{{ $view->percent_watched }}%</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ Student Info -->

            <!-- Event Log -->
            <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
                <div class="card">
                    <h5 class="card-header">{{ trans('admin/resources.eventLog') }}</h5>
                    <div class="table-responsive">
                        <table class="table border-top">
                            <thead>
                                <tr>
                                    <th>{{ trans('admin/resources.eventType') }}</th>
                                    <th>{{ trans('admin/resources.timestamp') }}</th>
                                    <th>{{ trans('admin/resources.details') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($events as $event)
                                    <tr>
                                        <td>
                                            @if($event->event_type == 'play')
                                                <span class="badge bg-label-success">{{ trans('admin/resources.play') }}</span>
                                            @elseif($event->event_type == 'pause')
                                                <span class="badge bg-label-warning">{{ trans('admin/resources.pause') }}</span>
                                            @elseif($event->event_type == 'seek')
                                                <span class="badge bg-label-info">{{ trans('admin/resources.seek') }}</span>
                                            @elseif($event->event_type == 'ended')
                                                <span class="badge bg-label-danger">{{ trans('admin/resources.ended') }}</span>
                                            @else
                                                <span class="badge bg-label-secondary">{{ $event->event_type }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $event->detected_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            @if($event->data)
                                                <small>
                                                    @foreach($event->data as $key => $value)
                                                        <strong>{{ ucfirst($key) }}:</strong> {{ $value }}<br>
                                                    @endforeach
                                                </small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">{{ trans('global.noData') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ Event Log -->
        </div>
    </div>
@endsection
