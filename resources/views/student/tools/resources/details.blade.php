@extends('layouts.student.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}">
@endsection

@section('title', pageTitle('admin/resources.resources'))

@section('content')
    <div class="row g-6">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-6 gap-1">
                        <div class="me-1">
                            <h5 class="mb-0">{{ $resource->title }}</h5>
                            <p class="mb-0">{{ trans('main.mr') }}: <span class="fw-medium text-heading">
                                    {{ $resource->teacher->name ?? 'N/A' }} </span></p>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-label-success rounded-pill">{{ $resource->grade->name ?? 'N/A' }}</span>
                            <i class="ri-share-forward-line ri-24px mx-4 cursor-pointer" data-bs-toggle="tooltip"
                                title="{{ trans('main.share') }}"></i>
                        </div>
                    </div>
                    <div class="card academy-content shadow-none border">
                        @if ($resource->video_url)
                            <div class="p-2 video-player-container position-relative">
                                @if ($resource->video_url)
                                    <div class="ratio ratio-16x9">
                                        <div id="youtube-player"></div>
                                    </div>
                                @endif
                                <hr class="my-6" />
                            </div>
                        @endif
                        <div class="card-body pt-3">
                            <h5>{{ trans('admin/resources.details') }}</h5>
                            <div class="d-flex flex-wrap row-gap-2">
                                <div class="me-12">
                                    <p class="text-nowrap mb-3">
                                        <i
                                            class="ri-calendar-schedule-line ri-20px me-2"></i>{{ trans('main.created_at') }}:
                                        {{ isoFormat($resource->created_at ?? now()) }}
                                    </p>
                                    <p class="text-nowrap mb-3">
                                        <i class="ri-survey-line ri-20px me-2"></i>{{ trans('main.grade') }}:
                                        {{ $resource->grade->name ?? 'N/A' }}
                                    </p>
                                    <p class="text-nowrap mb-3">
                                        <i class="ri-download-line ri-20px me-2"></i>{{ trans('main.downloads') }}:
                                        {{ $resource->downloads }}
                                    </p>
                                    <p class="text-nowrap mb-3">
                                        <i class="ri-eye-line ri-20px me-2"></i>{{ trans('main.views') }}:
                                        {{ $resource->resource_views_sum_views }}
                                    </p>
                                </div>
                            </div>
                            <hr class="my-6" />
                            <h5>{{ trans('admin/resources.instructions') }}</h5>
                            <p class="mb-6">
                                {{ $resource->description ?: '-' }}
                            </p>
                            <hr class="my-6" />
                            <h5>{{ app()->getLocale() === 'ar' ? 'ال' : '' }}{{ trans('admin/teachers.teacher') }}</h5>
                            <div class="d-flex justify-content-start align-items-center user-name">
                                <div class="avatar-wrapper">
                                    <div class="avatar me-4">
                                        <img src="{{ $resource->teacher->profile_pic ? asset('storage/profiles/teachers/' . $resource->teacher->profile_pic) : asset('assets/img/avatars/default.jpg') }}"
                                            alt="Avatar" class="rounded-circle" />
                                    </div>
                                </div>
                                <div class="d-flex flex-column">
                                    <h6 class="mb-1">{{ $resource->teacher->name ?? 'N/A' }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="accordion stick-top accordion-custom-button" id="resourceDetails">
                <div class="accordion-item active mb-0">
                    <div class="accordion-header border-bottom-0" id="headingOne">
                        <button type="button" class="accordion-button" data-bs-toggle="collapse"
                            data-bs-target="#resourcefiles" aria-expanded="true" aria-controls="resourcefiles">
                            <span class="d-flex flex-column">
                                <span class="h5 mb-0">{{ trans('admin/resources.file') }}</span>
                            </span>
                        </button>
                    </div>
                    <div id="resourcefiles" class="accordion-collapse collapse show" data-bs-parent="#resourceDetails">
                        <div class="accordion-body py-4 border-top">
                            @if ($resource->file_name && $resource->file_path)
                                <div class="d-flex align-items-center mb-4">
                                    <div class="me-2">
                                        @php
                                            $extension = $resource->file_name
                                                ? strtolower(pathinfo($resource->file_name, PATHINFO_EXTENSION))
                                                : 'default';
                                            $imageSrc = match ($extension) {
                                                'pdf' => asset('assets/img/icons/misc/pdf.svg'),
                                                'jpg', 'jpeg' => asset('assets/img/icons/misc/jpg.svg'),
                                                'png' => asset('assets/img/icons/misc/png.svg'),
                                                'doc', 'docx' => asset('assets/img/icons/misc/docx.svg'),
                                                'xls', 'xlsx' => asset('assets/img/icons/misc/xlsx.svg'),
                                                'txt' => asset('assets/img/icons/misc/txt.svg'),
                                                default => asset('assets/img/icons/misc/file.png'),
                                            };
                                        @endphp
                                        <img src="{{ $imageSrc }}" alt="{{ $extension }} icon"
                                            style="width: 32px; height: 32px;" />
                                    </div>
                                    <span class="text-nowrap overflow-hidden text-truncate" style="max-width: 200px;"
                                        data-bs-toggle="tooltip" data-bs-original-title="{{ $resource->file_name }}">
                                        <a href="{{ route('student.resources.download', $resource->uuid) }}"
                                            class="text-decoration-none">
                                            {{ $resource->file_name }}
                                        </a>
                                        <small class="text-body d-block">
                                            {{ $resource->file_size >= 1024 * 1024
                                                ? number_format($resource->file_size / (1024 * 1024), 2) . ' MB'
                                                : number_format($resource->file_size / 1024, 2) . ' KB' }}
                                        </small>
                                    </span>
                                    <div class="ms-auto">
                                        <button
                                            class="btn btn-sm btn-icon btn-text-danger rounded-pill text-body waves-effect waves-light"
                                            id="delete-button" data-id="{{ $resource->uuid }}"
                                            data-file_name="{{ $resource->file_name }}" data-bs-target="#delete-modal"
                                            data-bs-toggle="modal" data-bs-dismiss="modal">
                                            <i class="ri-delete-bin-7-line ri-20px text-danger"></i>
                                        </button>
                                    </div>
                                </div>
                            @else
                                <p class="text-muted">{{ trans('admin/resources.no_files_uploaded') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>

    <script>
        toggleShareButton();

        function showAlert(title, text, icon, confirmButtonText) {
            if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                return Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    confirmButtonText: confirmButtonText || '{{ trans('main.submit') }}',
                    customClass: {
                        confirmButton: 'btn btn-primary waves-effect waves-light'
                    },
                    buttonsStyling: false
                });
            } else {
                Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    confirmButtonText: confirmButtonText || '{{ trans('main.submit') }}',
                    customClass: {
                        confirmButton: 'btn btn-primary waves-effect waves-light'
                    },
                    buttonsStyling: false
                });
            }
        }

        $(document).ready(function() {
            let lastDetectionTime = {};

            function trackEvent(type) {
                const now = Date.now();
                const validTypes = ['copy', 'paste', 'context_menu', 'screenshot', 'dev_tools', 'tampering'];

                if (!validTypes.includes(type)) {
                    return;
                }

                if (validTypes.includes(type)) {
                    if (!lastDetectionTime[type] || (now - lastDetectionTime[type] > 30000)) {
                        lastDetectionTime[type] = now;
                    } else {
                        return;
                    }
                }

                sendEvent('security_' + type, {
                    violation_type: type,
                    event_source: 'trackEvent'
                });

                showAlert(
                    "{{ trans('main.warning') }}",
                    "{{ trans('admin/resources.detectionMessage') }}",
                    "error",
                    "{{ trans('admin/resources.detectionButtonText') }}"
                );
            }

            let cheatDateDetector = Date.now();
            setInterval(() => {
                $.ajax({
                    url: '{{ route('student.resources.cheatDetector', $resource->uuid) }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: JSON.stringify({
                        timestamp: Date.now()
                    }),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(response) {
                        cheatDateDetector = Date.now();
                    },
                    error: function(xhr) {
                        if (xhr.status !== 429 && Date.now() - cheatDateDetector > 60000) {
                            trackEvent('tampering');
                            cheatDateDetector = Date.now();
                        }
                    }
                });
            }, 30000);

            $(document).on('copy', function(e) {
                trackEvent('copy');
                e.preventDefault();
            });
            $(document).on('paste', function(e) {
                trackEvent('paste');
                e.preventDefault();
            });
            $(document).on('contextmenu', function(e) {
                trackEvent('context_menu');
                e.preventDefault();
            });
            $(document).on('keydown', function(e) {
                if (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 't')) {
                    trackEvent('copy');
                    e.preventDefault();
                }
                if (e.key === 'PrintScreen' || (e.metaKey && e.shiftKey && (e.key === '3' || e.key ===
                        '4'))) {
                    trackEvent('screenshot');
                    e.preventDefault();
                }
                if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e
                        .key === 'C'))) {
                    trackEvent('dev_tools');
                    e.preventDefault();
                }
            });
        });

        var tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        var player;

        let lastTime = 0;
        let lastQuality = '';
        let lastSpeed = 1;
        let duration = 0;
        let progressInterval = null;

        function onYouTubeIframeAPIReady() {
            player = new YT.Player('youtube-player', {
                videoId: "{{ $resource->video_url }}",
                playerVars: {
                    rel: 0,
                    modestbranding: 1
                },
                events: {
                    'onReady': onPlayerReady,
                    'onStateChange': onPlayerStateChange
                }
            });
        }

        function onPlayerReady(event) {
            sendEvent('view');
        }

        function onPlayerStateChange(event) {
            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }

            if (event.data == YT.PlayerState.PLAYING) {
                sendEvent('play');
                duration = player.getDuration();

                progressInterval = setInterval(() => {
                    if (!player || !player.getCurrentTime) return;

                    const currentTime = Math.floor(player.getCurrentTime());
                    const percent = duration > 0 ? (currentTime / duration) * 100 : 0;

                    sendEvent('progress', {
                        current_time: currentTime,
                        duration: Math.floor(duration),
                        percent: parseFloat(percent.toFixed(2)),
                        duration_watched: currentTime
                    });

                    const currentSpeed = player.getPlaybackRate();
                    const currentQuality = player.getPlaybackQuality();

                    if (currentSpeed !== lastSpeed) {
                        sendEvent('ratechange', {
                            speed: currentSpeed
                        });
                        lastSpeed = currentSpeed;
                    }

                    if (currentQuality && currentQuality !== lastQuality) {
                        sendEvent('qualitychange', {
                            quality: currentQuality
                        });
                        lastQuality = currentQuality;
                    }

                    if (percent >= 95) {
                        sendEvent('completed');
                    }

                    lastTime = currentTime;

                }, 30000);

            } else if (event.data == YT.PlayerState.PAUSED || event.data == YT.PlayerState.BUFFERING) {
                if (event.data == YT.PlayerState.PAUSED) {
                    sendEvent('pause');
                }
            } else if (event.data == YT.PlayerState.ENDED) {
                sendEvent('ended');
                sendEvent('completed');
            } else if (event.data == YT.PlayerState.CUED) {
                const currentTime = Math.floor(player.getCurrentTime());
                const percent = duration > 0 ? (currentTime / duration) * 100 : 0;

                if (Math.abs(currentTime - lastTime) > 5) {
                    sendEvent('rewind', {
                        from: lastTime,
                        to: currentTime
                    });

                    sendEvent('progress', {
                        current_time: currentTime,
                        duration: Math.floor(duration),
                        percent: parseFloat(percent.toFixed(2)),
                        duration_watched: currentTime
                    });
                }
            }
        }

        document.addEventListener('fullscreenchange', () => {
            if (document.fullscreenElement) {
                sendEvent('fullscreen_enter');
            } else {
                sendEvent('fullscreen_exit');
            }
        });

        function sendEvent(type, data = {}) {
            if (type === 'ratechange' && (data.speed > 5 || data.speed < 0)) {
                sendEvent('security_rate_manipulation', {
                    speed: data.speed,
                    message: 'impossible_rate_detected'
                });
                toastr.error("{{ trans('admin/resources.security_error_rate') }}");
                return;
            }

            if (type === 'progress') {
                //
            }

            $.ajax({
                url: '{{ route('student.resources.track', $resource->uuid) }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    type: type,
                    data: JSON.stringify(data),
                },
                dataType: 'json',
                success: function(response) {
                    if (response.ban_triggered) {
                        showAlert(
                            "{{ trans('main.error') }}",
                            "{{ trans('toasts.tooManyViolations') }}",
                            "error",
                            "{{ trans('main.submit') }}"
                        );
                        setTimeout(() => {
                            window.location.href = '{{ route('student.resources.index') }}';
                        }, 1500);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 403 || xhr.status === 419) {
                        showAlert(
                            "{{ trans('main.error') }}",
                            "{{ trans('toasts.tooManyViolations') }}",
                            "error",
                            "{{ trans('main.submit') }}"
                        );
                        setTimeout(() => {
                            window.location.href = '{{ route('student.resources.index') }}';
                        }, 1500);
                    }
                }
            });
        }
    </script>
@endsection
