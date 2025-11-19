@extends('layouts.student.master')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/plyr/plyr.css') }}" />

    <style>
        .plyr__menu__container [data-plyr="quality"] {
            display: block !important;
        }

        .plyr__menu__container .plyr__control[role="menuitemradio"] {
            display: block !important;
        }
    </style>
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
                            <div class="p-2">
                                <div class="cursor-pointer">
                                    <div class="plyr__video-embed" id="player">
                                        <iframe
                                            src="https://www.youtube.com/embed/{{ $resource->video_url }}?origin=https://shattor.com&amp;iv_load_policy=3&amp;modestbranding=1&amp;playsinline=1&amp;showinfo=0&amp;rel=0&amp;enablejsapi=1"
                                            allowfullscreen allowtransparency allow="autoplay"></iframe>
                                    </div>
                                    <div id="dynamic-watermark" class="position-absolute"
                                        style="
                                        top: 0; left: 0; pointer-events: none; opacity: 0.75;
                                        color: #ffffff;
                                        text-shadow: 2px 2px 4px rgba(0, 0, 0, 1);
                                        font-size: 20px;
                                        font-weight: 900;
                                        z-index: 1000;
                                        transition: none !important;">
                                    </div>
                                </div>
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
                                            {{ $resource->file_size >= 1024 * 1024 ? number_format($resource->file_size / (1024 * 1024), 2) . ' MB' : number_format($resource->file_size / 1024, 2) . ' KB' }}
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
    <script src="{{ asset('assets/vendor/libs/plyr/plyr.js') }}"></script>
    <script>
        toggleShareButton();

        // 1. DYNAMIC WATERMARK CONFIG
        const studentName = '{{ auth()->guard('student')->name ?? 'N/A' }}';
        const studentIdentifier =
        '{{ auth()->guard('student')->phone?? 0 }}';
        const watermarkContent = `${studentName} | ID: ${studentIdentifier}`;

        const watermarkEl = document.getElementById('dynamic-watermark');

        // We will get the Plyr wrapper element after Plyr initializes
        let playerContainer;

        function moveWatermark() {
            if (!playerContainer) return;

            const containerRect = playerContainer.getBoundingClientRect();
            const watermarkRect = watermarkEl.getBoundingClientRect();

            // Calculate max safe positions (10px buffer)
            const maxX = containerRect.width - watermarkRect.width - 10;
            const maxY = containerRect.height - watermarkRect.height - 10;

            if (maxX > 0 && maxY > 0) {
                const newX = Math.floor(Math.random() * maxX);
                const newY = Math.floor(Math.random() * maxY);

                // Watermark is absolutely positioned relative to the .video-player-container
                watermarkEl.style.left = `${newX}px`;
                watermarkEl.style.top = `${newY}px`;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Plyr
            const player = new Plyr('#player', {
                // Plyr settings to ensure speed and quality controls are available
                controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'settings',
                    'fullscreen'
                ],
                settings: ['quality', 'speed'],
                speed: {
                    selected: 1,
                    options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2]
                },
                ratio: '16:9'
            });

            // 2. Identify the PLYR wrapper element
            // Plyr typically wraps the target element in a div with class .plyr
            const plyrWrapper = document.getElementById('player').closest('.plyr');
            if (plyrWrapper) {
                // Set the PLYR wrapper as the container for watermark positioning
                playerContainer = plyrWrapper;

                // Set watermark content and begin movement
                if (watermarkEl) {
                    watermarkEl.innerHTML = watermarkContent;

                    // Set the initial position and start the interval
                    moveWatermark();
                    setInterval(moveWatermark, 5000);

                    // Ensure the watermark covers the video when Plyr goes fullscreen
                    // This custom CSS rule handles the z-index fix when Plyr enters fullscreen mode
                    document.addEventListener('fullscreenchange', () => {
                        if (document.fullscreenElement) {
                            watermarkEl.style.zIndex =
                            20; // Must be higher than Plyr's z-index (usually around 19)
                            // Log fullscreen enter (analytics)
                            sendEvent('fullscreen_enter');
                        } else {
                            watermarkEl.style.zIndex = 1000; // Reset z-index when exiting
                            // Log fullscreen exit (analytics)
                            sendEvent('fullscreen_exit');
                        }
                    });
                }
            }

            // 3. Security (Right-Click/Inspect Block)
            const containerToProtect = document.querySelector('.video-player-container');
            if (containerToProtect) {
                // Block right-click (context menu)
                containerToProtect.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    sendEvent('security_inspect_attempt', {
                        method: 'right_click'
                    });
                    toastr.warning("{{ trans('admin/resources.security_warning') }}");
                });

                // Block common Inspect Element shortcuts (F12, Ctrl/Cmd + Shift + I/J/C)
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'F12' || (e.shiftKey && e.ctrlKey && (e.key === 'I' || e.key === 'J' || e
                            .key === 'C'))) {
                        e.preventDefault();
                        sendEvent('security_inspect_attempt', {
                            method: 'keyboard_shortcut',
                            key: e.key
                        });
                        toastr.warning("{{ trans('admin/resources.security_warning') }}");
                    }
                });
            }

            // 4. Plyr Event Listeners for Analytics (Simplified)
            player.on('play', () => sendEvent('play'));
            player.on('pause', () => sendEvent('pause'));
            player.on('ended', () => {
                sendEvent('ended');
                sendEvent('completed');
            });
            player.on('ratechange', (event) => {
                const speed = event.detail.plyr.speed;
                if (speed > 5 || speed < 0) { // Security Check
                    sendEvent('security_rate_manipulation', {
                        speed: speed,
                        message: 'impossible_rate_detected'
                    });
                    toastr.error("{{ trans('admin/resources.security_error_rate') }}");
                }
                sendEvent('ratechange', {
                    speed: speed
                });
            });

            // Plyr only exposes "qualitychange" for non-YouTube sources.
            // For YouTube, we rely on the iframe to report it (which Plyr does not easily expose).
            // We will log quality changes if a custom event handler is found, but rely on the standard ratechange.
            // For Plyr + YouTube, tracking playback quality changes is very difficult. We'll rely on speed.

            // Initial view log
            sendEvent('view');

            // We'll need a way to track progress/duration watched. Since Plyr doesn't fire a "progress" event
            // every second, we'll manually set an interval, using the Plyr API's currentTime
            setInterval(() => {
                if (player.playing) {
                    const currentTime = Math.floor(player.currentTime);
                    const duration = Math.floor(player.duration);
                    const percent = duration > 0 ? (currentTime / duration) * 100 : 0;

                    sendEvent('progress', {
                        current_time: currentTime,
                        duration: duration,
                        percent: parseFloat(percent.toFixed(2)),
                        duration_watched: currentTime
                    });
                }
            }, 1000); // Check every second

            // 5. Screen Capture Detection (Retaining the safer check)
            function handleDisplayChange() {
                const isTypeSupportedCheck = typeof navigator.mediaCapabilities !== 'undefined' && typeof navigator
                    .mediaCapabilities.isTypeSupported === 'function';
                const isExtendedScreen = typeof window.screen.isExtended !== 'undefined' && window.screen
                .isExtended;

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

            // 6. Server Event Sender (The final piece)
            function sendEvent(type, data = {}) {
                console.log(type, data); // Keep console logging for debugging
                // This is where we will add the AJAX call in the next step
            }
        });
    </script>
@endsection
