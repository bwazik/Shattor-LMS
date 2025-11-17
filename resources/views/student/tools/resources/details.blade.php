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
                                            src="https://www.youtube.com/embed/{{ $resource->video_url }}?origin=https://plyr.io&amp;iv_load_policy=3&amp;modestbranding=1&amp;playsinline=1&amp;showinfo=0&amp;rel=0&amp;enablejsapi=1"
                                            allowfullscreen allowtransparency allow="autoplay"></iframe>
                                    </div>
                                    <div class="plyr__video-embed" id="player">
                                        <iframe
                                            src="https://www.youtube.com/embed/{{ $resource->video_url }}?enablejsapi=1&origin={{ urlencode(config('app.url')) }}&rel=0&modestbranding=1&playsinline=1&widget_referrer={{ urlencode(url()->current()) }}"
                                            allowfullscreen allowtransparency allow="autoplay; encrypted-media">
                                        </iframe>
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

        document.addEventListener('DOMContentLoaded', function() {
            const playerElement = document.getElementById('player');
            if (!playerElement) return;

            const player = new Plyr('#player', {
                debug: false,
                controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'settings',
                    'pip', 'airplay', 'fullscreen'
                ],
                settings: ['captions', 'quality', 'speed'],
                speed: {
                    selected: 1,
                    options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2]
                },
                quality: {
                    default: 480,
                    options: [1080, 720, 576, 480, 360, 240],
                    forced: true
                }
            });

            const resourceId = '{{ $resource->id }}';
            const studentId = '{{ auth('student')->id() }}';

            // دالة إرسال الحدث للسيرفر
            const sendEvent = async (type, data = {}) => {
                try {
                    console.log('Analytics event:', type, data);
                } catch (err) {
                    console.warn('Analytics event failed (silently):', err.message);
                }
            };

            // دالة تتبع التقدم (مع حماية كاملة)
            const trackProgress = () => {
                if (!player.media || player.media.readyState < 1) return;

                const currentTime = player.currentTime || 0;
                const duration = player.duration || 0;
                if (duration === 0) return;

                const percent = (currentTime / duration) * 100;
                const durationWatched = Math.floor(currentTime);

                sendEvent('progress', {
                    currentTime: parseFloat(currentTime.toFixed(2)),
                    duration: parseFloat(duration.toFixed(2)),
                    percent: parseFloat(percent.toFixed(2)),
                    duration_watched: durationWatched
                });
            };

            // دالة debounce
            const debounce = (func, wait) => {
                let timeout;
                return (...args) => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            };

            const debouncedProgress = debounce(trackProgress, 8000); // كل 8 ثواني

            // ننتظر لحد ما Plyr يجهز تماماً
            player.on('ready', () => {
                console.log('Plyr is ready – YouTube loaded');

                // أول زيارة
                sendEvent('view');

                // تتبع الأحداث الأساسية
                player.on('play', () => sendEvent('play'));
                player.on('pause', () => sendEvent('pause'));
                player.on('ended', () => sendEvent('ended'));
                player.on('enterfullscreen', () => sendEvent('fullscreen_enter'));
                player.on('exitfullscreen', () => sendEvent('fullscreen_exit'));

                // تغيير السرعة
                player.on('ratechange', () => {
                    if (player.media) {
                        sendEvent('ratechange', {
                            speed: player.speed
                        });
                    }
                });

                // تغيير الجودة (YouTube فقط)
                player.on('qualitychange', (e) => {
                    sendEvent('qualitychange', {
                        quality: e.detail.quality
                    });
                });

                // تتبع التقدم
                player.on('timeupdate', debouncedProgress);
                player.on('pause', trackProgress);
                player.on('ended', trackProgress);
                player.on('seeked', trackProgress);

                // تحديث فوري عند أول تشغيل
                player.on('playing', trackProgress);
            });

            // في حالة إن الـ ready ما اشتغلش (نادر جداً)
            player.on('canplay', () => {
                if (!player.media) return;
                sendEvent('canplay');
            });
        });
    </script>
@endsection
