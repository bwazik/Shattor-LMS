@extends('layouts.student.master')

@section('page-css')
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
                                    <div id="dynamic-watermark" class="position-absolute"
                                        style="
                                        top: 0; left: 0; pointer-events: none; opacity: 0.5;
                                        color: rgba(255, 255, 255, 0.7); text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.9);
                                        font-size: 14px; font-weight: bold; z-index: 1000; transition: none !important;">
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
                                        {{ $resource->views }}
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
    <script>
        toggleShareButton();

        const studentName = '{{ auth()->user->name ?? 'N/A' }}';
        const studentPhone = '{{ auth()->user->phone ?? 0 }}';
        const watermarkContent = `${studentName} - Phone:${studentPhone}`;
        const watermarkEl = document.getElementById('dynamic-watermark');
        const videoContainer = document.querySelector('.video-player-container');

        if (watermarkEl && videoContainer) {
            watermarkEl.innerHTML = watermarkContent;

            function moveWatermark() {
                if (!videoContainer) return;

                const containerRect = videoContainer.getBoundingClientRect();
                const watermarkRect = watermarkEl.getBoundingClientRect();

                const maxX = containerRect.width - watermarkRect.width - 20;
                const maxY = containerRect.height - watermarkRect.height - 20;

                if (maxX > 0 && maxY > 0) {
                    const newX = Math.floor(Math.random() * maxX);
                    const newY = Math.floor(Math.random() * maxY);

                    watermarkEl.style.left = `${newX}px`;
                    watermarkEl.style.top = `${newY}px`;
                }
            }

            moveWatermark();
            setInterval(moveWatermark, 5000);
        }

        if (videoContainer) {
            videoContainer.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                sendEvent('security_inspect_attempt', {
                    method: 'right_click'
                });
                toastr.warning("{{ trans('admin/resources.security_warning') }}");
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'F12' || (e.shiftKey && e.ctrlKey && (e.key === 'I' || e.key === 'J' || e.key ===
                        'C'))) {
                    e.preventDefault();
                    sendEvent('security_inspect_attempt', {
                        method: 'keyboard_shortcut',
                        key: e.key
                    });
                    toastr.warning("{{ trans('admin/resources.security_warning') }}");
                }
            });
        }

        function handleDisplayChange() {
            const isTypeSupportedCheck = typeof navigator.mediaCapabilities !== 'undefined' &&
                typeof navigator.mediaCapabilities.isTypeSupported === 'function';

            const isExtendedScreen = typeof window.screen.isExtended !== 'undefined' && window.screen.isExtended;

            if (isExtendedScreen || (isTypeSupportedCheck && navigator.mediaCapabilities.isTypeSupported(
                    'video/webm; codecs=vp8') && !document.hidden)) {
                sendEvent('security_screen_capture', {
                    action: 'suspicion_raised',
                    message: 'display_extended_or_visible_media_capability'
                });
            }
        }
        window.addEventListener('blur', handleDisplayChange);
        window.addEventListener('focus', handleDisplayChange);

        var tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        var player;

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

        let lastTime = 0;
        let lastQuality = '';
        let lastSpeed = 1;
        let duration = 0;

        function onPlayerStateChange(event) {
            if (event.data == YT.PlayerState.PLAYING) {
                sendEvent('play');
                duration = player.getDuration();

                // تتبع كل ثانية
                setInterval(() => {
                    if (!player || !player.getCurrentTime) return;
                    const currentTime = Math.floor(player.getCurrentTime());

                    // تجنب إرسال نفس الثانية أكتر من مرة
                    if (currentTime !== lastTime) {
                        const percent = duration > 0 ? (currentTime / duration) * 100 : 0;

                        sendEvent('progress', {
                            current_time: currentTime,
                            duration: Math.floor(duration),
                            percent: parseFloat(percent.toFixed(2)),
                            duration_watched: currentTime
                        });

                        // كشف Rewind
                        if (currentTime < lastTime - 5) {
                            sendEvent('rewind', {
                                from: lastTime,
                                to: currentTime
                            });
                        }

                        lastTime = currentTime;

                        // إذا كمل 95% أو أكتر → completed
                        if (percent >= 95) {
                            sendEvent('completed');
                        }
                    }

                    // تتبع السرعة والجودة كل 5 ثواني
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

                }, 1000); // كل ثانية

            } else if (event.data == YT.PlayerState.PAUSED) {
                sendEvent('pause');
            } else if (event.data == YT.PlayerState.ENDED) {
                sendEvent('ended');
                sendEvent('completed');
            }
        }

        // Fullscreen tracking
        document.addEventListener('fullscreenchange', () => {
            if (document.fullscreenElement) {
                sendEvent('fullscreen_enter');
            } else {
                sendEvent('fullscreen_exit');
            }
        });

        // إرسال الأحداث للسيرفر
        function sendEvent(type, data = {}) {
            console.log(type, data);
        }
    </script>
@endsection
