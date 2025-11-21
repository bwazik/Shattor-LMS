@extends('layouts.admin.master')

@section('page-css')

@endsection

@section('title', pageTitle('admin/resources.resources'))

@section('content')
    <div class="app-academy">
        <div class="card p-0 mb-6">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between p-0 pt-6">
                <div class="app-academy-md-25 card-body py-0 pt-6 ps-12">
                    <img src="{{ asset('assets/img/illustrations/bulb-light.png') }}"
                        class="img-fluid app-academy-img-height scaleX-n1-rtl" alt="Bulb in hand"
                        data-app-light-img="illustrations/bulb-light.png" data-app-dark-img="illustrations/bulb-dark.png"
                        height="90" />
                </div>
                <div class="app-academy-md-50 card-body d-flex align-items-md-center flex-column text-md-center mb-6 py-6">
                    <span class="card-title mb-4 lh-lg px-md-12 h4 text-heading">
                        {{ trans('admin/resources.resources_header') }}<br />
                        {{ trans('admin/resources.resources_header2') }} <span class="text-primary text-nowrap">{{ trans('admin/resources.resources_highlight') }}</span>.
                    </span>
                    <p class="mb-4 px-0 px-md-2">
                        {{ trans('admin/resources.resources_description') }}<br />
                        {{ trans('admin/resources.resources_description2') }}
                    </p>
                    <div class="d-flex align-items-center justify-content-between app-academy-md-80">
                        <form id="search-form" method="GET" action="{{ route('admin.resources.index') }}"
                            class="d-flex align-items-center justify-content-between app-academy-md-80">
                            <input type="search" name="search" placeholder="{{ trans('admin/resources.search_resources') }}"
                                class="form-control form-control-sm me-4" value="{{ request('search') }}" />
                            <button type="submit" class="btn btn-primary btn-icon me-2">
                                <i class="ri-search-line ri-22px"></i>
                            </button>
                            <button id="add-button" type="button" class="btn btn-primary waves-effect waves-light" tabindex="0"
                                data-bs-toggle="offcanvas" data-bs-target="#add-modal">
                                <i class="ri-add-line ri-22px"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="app-academy-md-25 d-flex align-items-end justify-content-end">
                    <img src="{{ asset('assets/img/illustrations/pencil-rocket.png') }}" alt="pencil rocket" height="180"
                        class="scaleX-n1-rtl" />
                </div>
            </div>
        </div>

        <div class="card mb-6">
            <div class="card-header d-flex flex-wrap justify-content-between gap-4">
                <div class="card-title mb-0 me-1">
                    <h5 class="mb-0">{{ trans('main.datatableTitle', ['item' => trans('admin/resources.resources')]) }}</h5>
                    <p class="mb-0 text-body">{{ trans('admin/resources.total_resources', ['count' => $resources->total()]) }}</p>
                </div>
                <form id="filter-form" method="GET" action="{{ route('admin.resources.index') }}"
                    class="d-flex justify-content-md-end align-items-center gap-6 flex-wrap">
                    <select id="grade_id" class="form-select form-select-sm w-px-250" name="grade_id">
                        <option value="">{{ trans('admin/grades.grades') }}</option>
                        @foreach ($grades as $id => $name)
                            <option value="{{ $id }}" {{ request('grade_id') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    <select id="teacher_id" class="form-select form-select-sm w-px-250" name="teacher_id">
                        <option value="">{{ trans('admin/teachers.teachers') }}</option>
                        @foreach ($teachers as $id => $name)
                            <option value="{{ $id }}" {{ request('teacher_id') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    <select id="sort" class="form-select form-select-sm w-px-150" name="sort">
                        <option value="created_at-desc" {{ request('sort') == 'created_at-desc' ? 'selected' : '' }}>
                            {{ trans('admin/resources.newest_first') }}
                        </option>
                        <option value="views-desc" {{ request('sort') == 'views-desc' ? 'selected' : '' }}>
                            {{ trans('admin/resources.most_viewed') }}
                        </option>
                        <option value="downloads-desc" {{ request('sort') == 'downloads-desc' ? 'selected' : '' }}>
                            {{ trans('admin/resources.most_downloaded') }}
                        </option>
                    </select>
                    <div class="form-check form-switch mb-0">
                        <input type="checkbox" class="form-check-input" id="resource-switch" name="hide_inactive"
                            {{ request('hide_inactive') ? 'checked' : '' }} />
                        <label class="form-check-label text-nowrap mb-0" for="resource-switch">{{ trans('admin/resources.hide_inactive') }}</label>
                    </div>
                </form>
            </div>
            <div class="card-body mt-1">
                <div class="row gy-6 mb-6">
                    @forelse ($resources as $resource)
                        <div class="col-sm-6 col-lg-4">
                            <div class="card">
                                @php
                                    $extension = $resource->file_name ? strtolower(pathinfo($resource->file_name, PATHINFO_EXTENSION)) : 'default';
                                    $imageSrc = match ($extension) {
                                        'pdf' => asset('assets/img/icons/misc/pdf.svg'),
                                        'jpg', 'jpeg' => asset('assets/img/icons/misc/jpg.svg'),
                                        'png' => asset('assets/img/icons/misc/png.svg'),
                                        'doc', 'docx' => asset('assets/img/icons/misc/docx.svg'),
                                        'xls', 'xlsx' => asset('assets/img/icons/misc/xlsx.svg'),
                                        'txt' => asset('assets/img/icons/misc/txt.svg'),
                                        'zip', 'rar', '7z', 'tar', 'gz', '7zip', 'bz2', 'iso', 'xz', 'tgz' => asset(
                                            'assets/img/icons/misc/file.png',
                                        ),
                                        default => asset('assets/img/icons/misc/mp4.svg'),
                                    }
                                @endphp
                                <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 241px; width: 100%; background-color: #f8f9fa;">
                                    <img src="{{ $imageSrc }}" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;" alt="resource image">
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div class="d-flex">
                                            <div class="badge bg-label-success rounded-pill me-4">
                                                {{ $resource->grade->name ?? 'N/A' }}</div>
                                            <div
                                                class="badge {{ $resource->is_active ? 'bg-label-success' : 'bg-label-secondary' }} rounded-pill">
                                                {{ $resource->is_active ? trans('main.active') : trans('main.inactive') }}
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-text-secondary rounded-pill text-muted border-0 p-1 waves-effect waves-light"
                                                type="button" id="financeApp_{{ $resource->id }}" data-bs-toggle="dropdown"
                                                aria-haspopup="true" aria-expanded="false">
                                                <i class="ri-more-2-line ri-20px"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="financeApp_{{ $resource->id }}">
                                                <a target="_blank" href="{{ route('admin.resources.reports', $resource->id) }}" class="dropdown-item waves-effect">{{ trans('main.reports') }}</a>
                                                <a target="_blank" href="{{ route('admin.resources.details', $resource->id) }}" class="dropdown-item waves-effect">{{ trans('main.details') }}</a>
                                                <a href="javascript:;" class="dropdown-item waves-effect" tabindex="0" type="button" data-bs-toggle="offcanvas" data-bs-target="#edit-modal"
                                                    id="edit-button"
                                                    data-id="{{ $resource->id }}"
                                                    data-title_ar="{{ $resource->getTranslation('title', 'ar') }}"
                                                    data-title_en="{{ $resource->getTranslation('title', 'en') }}"
                                                    data-teacher_id="{{ $resource->teacher_id }}"
                                                    data-grade_id="{{ $resource->grade_id }}"
                                                    data-video_url="{{ $resource->video_url }}"
                                                    data-description="{{ $resource->description }}"
                                                    data-is_active="{{ $resource->is_active ? 1 : 0 }}">
                                                    {{ trans('main.edit') }}
                                                </a>
                                                <a href="{{ route('admin.resources.download', $resource->id) }}" class="dropdown-item waves-effect">{{ trans('main.download') }}</a>
                                                <div class="dropdown-divider"></div>
                                                <a href="javascript:;" class="dropdown-item waves-effect text-danger"
                                                    id="delete-button"
                                                    data-id="{{ $resource->id }}"
                                                    data-title_ar="{{ $resource->getTranslation('title', 'ar') }}"
                                                    data-title_en="{{ $resource->getTranslation('title', 'en') }}"
                                                    data-bs-target="#delete-modal"
                                                    data-bs-toggle="modal"
                                                    data-bs-dismiss="modal">
                                                    {{ trans('main.delete') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <a target="_blank" href="{{ route('admin.resources.details', $resource->id) }}" class="h5 mb-1">{{ Str::limit($resource->title, 50) }}</a>
                                    <p class="fw-medium small">{{ isoFormat($resource->created_at ?? now()) }}</p>
                                    <p class="my-4 small">{{ Str::limit($resource->description ?? '-', 50) }}</p>
                                    @php
                                        $totalStudents = $resource->grade->students->count() ?: 1;
                                        $downloadedStudents = $resource->downloads;
                                        $percentage = round(($downloadedStudents / $totalStudents) * 100);
                                    @endphp
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between gap-3">
                                            <h6 class="mb-2 small">
                                                <i class="ri-file-text-line rphi-20px me-1"></i>
                                                {{ $resource->file_size >= 1024 * 1024 ? number_format($resource->file_size / (1024 * 1024), 2) . ' MB' : number_format($resource->file_size / 1024, 2) . ' KB' }}
                                            </h6>
                                            <span class="h6 mb-0 small">
                                                {{ $percentage }}%
                                            </span>
                                        </div>
                                        <div class="progress w-100 rounded bg-label-success" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: {{ $percentage }}%"
                                                role="progressbar" aria-valuenow="{{ $percentage }}" aria-valuemin="0"
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <ul class="list-unstyled m-0 d-flex align-items-center avatar-group">
                                            <li data-bs-toggle="tooltip" data-popup="tooltip-custom"
                                                data-bs-placement="top" class="avatar avatar-sm pull-up"
                                                aria-label="{{ $resource->teacher->name ?? 'N/A' }}"
                                                data-bs-original-title="{{ $resource->teacher->name ?? 'N/A' }}">
                                                <img class="rounded-circle"
                                                    src="{{ $resource->teacher->profile_pic ? asset('storage/profiles/teachers/' . $resource->teacher->profile_pic) : asset('assets/img/avatars/default.jpg') }}"
                                                    alt="Avatar">
                                            </li>
                                        </ul>
                                        <div class="d-flex">
                                            <div class="me-3 text-muted" data-bs-toggle="tooltip" title="{{ trans('main.downloads') }}">
                                                <i class="ri-download-line ri-24px"></i>
                                                <span class="fw-medium">{{ $resource->downloads }}</span>
                                            </div>
                                            <div class="me-3 text-muted" data-bs-toggle="tooltip" title="{{ trans('main.views') }}">
                                                <i class="ri-eye-line ri-24px"></i>
                                                <span class="fw-medium">{{ $resource->views }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center">
                            <p class="text-muted">{{ trans('admin/resources.no_files_uploaded') }}</p>
                        </div>
                    @endforelse
                </div>
                <nav aria-label="Page navigation" class="d-flex align-items-center justify-content-center">
                    {{ $resources->appends(request()->query())->links('partials.paginations') }}
                </nav>
            </div>
        </div>
    </div>
    @include('admin.tools.resources.modals')
@endsection

@section('page-js')
    <script>
        // Setup add modal
        setupModal({
            buttonId: '#add-button',
            modalId: '#add-modal',
            fields: {
                teacher_id: () => '',
                grade_id: () => '',
            }
        });
        // Setup edit modal
        setupModal({
            buttonId: '#edit-button',
            modalId: '#edit-modal',
            fields: {
                id: button => button.data('id'),
                title_ar: button => button.data('title_ar'),
                title_en: button => button.data('title_en'),
                teacher_id: button => button.data('teacher_id'),
                grade_id: button => button.data('grade_id'),
                video_url: button => button.data('video_url'),
                description: button => button.data('description'),
                is_active: button => button.data('is_active')
            }
        });
        // Setup delete modal
        setupModal({
            buttonId: '#delete-button',
            modalId: '#delete-modal',
            fields: {
                id: button => button.data('id'),
                itemToDelete: button => `${button.data('title_ar')} - ${button.data('title_en')}`
            }
        });

        let fields = ['title_ar', 'title_en', 'teacher_id', 'grade_id', 'video_url', 'description', 'is_active'];
        handleFormSubmit('#add-form', fields, '#add-modal', 'offcanvas');
        handleFormSubmit('#edit-form', fields, '#edit-modal', 'offcanvas');
        handleDeletionFormSubmit('#delete-form', '#delete-modal');
        fetchMultipleDataByAjax('#add-form #teacher_id', "{{ route('admin.teachers.getGrades', '__ID__') }}",
            '#add-form #grade_id', 'teacher_id', 'GET');

        function updateResources(page = 1) {
            const searchForm = $('#search-form');
            const filterForm = $('#filter-form');
            const submitButton = searchForm.find('[type="submit"]');
            const originalButtonContent = submitButton.html();

            const setLoadingState = () => {
                if (submitButton.length) {
                    submitButton.find('.waves-ripple').remove();
                    submitButton.prop('disabled', true);
                    submitButton.html(`<i class="ri-loader-4-line ri-spin ri-22px"></i>`);
                }
            };

            const resetLoadingState = () => {
                if (submitButton.length) {
                    submitButton.prop('disabled', false);
                    submitButton.html(originalButtonContent);
                    submitButton.blur();
                    submitButton.find('.waves-ripple').remove();
                    if (typeof Waves !== 'undefined') {
                        Waves.init();
                        Waves.attach(submitButton[0]);
                    }
                }
            };

            setLoadingState();

            let formData = searchForm.serializeArray().concat(filterForm.serializeArray());
            formData.push({name: 'page', value: page});

            const detailsRouteBase = "{{ route('admin.resources.details', ':id') }}";
            const downloadRouteBase = "{{ route('admin.resources.download', ':id') }}";

            $.ajax({
                url: "{{ route('admin.resources.index') }}",
                type: 'GET',
                data: formData,
                headers: {
                    'Accept': 'application/json'
                },
                success: function(data) {
                    const resourcesContainer = $('.row.gy-6.mb-6');
                    resourcesContainer.empty();

                    data.resources.data.forEach(resource => {
                        const extension = resource.file_name ? resource.file_name.split('.').pop().toLowerCase() : 'default';

                        const imageSrc = {
                            'pdf': '{{ asset('assets/img/icons/misc/pdf.svg') }}',
                            'jpg': '{{ asset('assets/img/icons/misc/jpg.svg') }}',
                            'jpeg': '{{ asset('assets/img/icons/misc/jpg.svg') }}',
                            'png': '{{ asset('assets/img/icons/misc/png.svg') }}',
                            'doc': '{{ asset('assets/img/icons/misc/docx.svg') }}',
                            'docx': '{{ asset('assets/img/icons/misc/docx.svg') }}',
                            'xls': '{{ asset('assets/img/icons/misc/xlsx.svg') }}',
                            'xlsx': '{{ asset('assets/img/icons/misc/xlsx.svg') }}',
                            'txt': '{{ asset('assets/img/icons/misc/txt.svg') }}',
                            'zip': '{{ asset('assets/img/icons/misc/file.png') }}',
                            'rar': '{{ asset('assets/img/icons/misc/file.png') }}',
                            '7z': '{{ asset('assets/img/icons/misc/file.png') }}',
                            'tar': '{{ asset('assets/img/icons/misc/file.png') }}',
                            'gz': '{{ asset('assets/img/icons/misc/file.png') }}',
                            '7zip': '{{ asset('assets/img/icons/misc/file.png') }}',
                            'bz2': '{{ asset('assets/img/icons/misc/file.png') }}',
                            'iso': '{{ asset('assets/img/icons/misc/file.png') }}',
                            'xz': '{{ asset('assets/img/icons/misc/file.png') }}',
                            'tgz': '{{ asset('assets/img/icons/misc/file.png') }}',
                            'default': '{{ asset('assets/img/icons/misc/mp4.svg') }}'
                        }[extension] || '{{ asset('assets/img/icons/misc/mp4.svg') }}';

                        const totalStudents = resource.grade.total_students || 1;
                        const downloadedStudents = resource.downloads;
                        const percentage = Math.round((downloadedStudents / totalStudents) * 100);

                        const fileSizeFormatted = resource.file_size >= 1024 * 1024 ?
                            (resource.file_size / (1024 * 1024)).toFixed(2) + ' MB' :
                            (resource.file_size / 1024).toFixed(2) + ' KB';

                        const createdAt = resource.created_at;

                        const teacherProfilePic = resource.teacher.profile_pic ?
                            '{{ asset('storage/profiles/teachers') }}' + '/' + resource.teacher.profile_pic :
                            '{{ asset('assets/img/avatars/default.jpg') }}';

                        const detailsUrl = detailsRouteBase.replace(':id', resource.id);
                        const downloadUrl = downloadRouteBase.replace(':id', resource.id);

                        const newCard = $('<div>').addClass('col-sm-6 col-lg-4').html(`
                            <div class="card">
                                <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 241px; width: 100%; background-color: #f8f9fa;">
                                    <img src="${imageSrc}" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;" alt="resource image">
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div class="d-flex">
                                            <div class="badge bg-label-success rounded-pill me-4">
                                                ${resource.grade.name}
                                            </div>
                                            <div class="badge ${resource.is_active ? 'bg-label-success' : 'bg-label-secondary'} rounded-pill">
                                                ${resource.is_active ? '{{ trans('main.active') }}' : '{{ trans('main.inactive') }}'}
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-text-secondary rounded-pill text-muted border-0 p-1 waves-effect waves-light" type="button" id="financeApp_${resource.id}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="ri-more-2-line ri-20px"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="financeApp_${resource.id}">
                                                <a target="_blank" href="${detailsUrl.replace('details', 'reports')}" class="dropdown-item waves-effect">{{ trans('admin/resources.reports') }}</a>
                                                <a target="_blank" href="${detailsUrl}" class="dropdown-item waves-effect">{{ trans('main.details') }}</a>
                                                <a href="javascript:;" class="dropdown-item waves-effect" tabindex="0" type="button" data-bs-toggle="offcanvas" data-bs-target="#edit-modal"
                                                    id="edit-button"
                                                    data-id="${resource.id}"
                                                    data-title_ar="${resource.title_ar || ''}"
                                                    data-title_en="${resource.title_en || ''}"
                                                    data-teacher_id="${resource.teacher_id}"
                                                    data-grade_id="${resource.grade_id}"
                                                    data-video_url="${resource.video_url || ''}"
                                                    data-description="${resource.description || ''}"
                                                    data-is_active="${resource.is_active ? 1 : 0}">
                                                    {{ trans('main.edit') }}
                                                </a>
                                                <a href="${downloadUrl}" class="dropdown-item waves-effect">{{ trans('main.download') }}</a>
                                                <div class="dropdown-divider"></div>
                                                <a href="javascript:;" class="dropdown-item waves-effect text-danger"
                                                    id="delete-button"
                                                    data-id="${resource.id}"
                                                    data-title_ar="${resource.title_ar || ''}"
                                                    data-title_en="${resource.title_en || ''}"
                                                    data-bs-target="#delete-modal"
                                                    data-bs-toggle="modal"
                                                    data-bs-dismiss="modal">
                                                    {{ trans('main.delete') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <a target="_blank" href="${detailsUrl}" class="h5 mb-1">${strLimit(resource.title, 50)}</a>
                                    <p class="fw-medium small">${createdAt}</p>
                                    <p class="my-4 small">${strLimit(resource.description || '-', 50)}</p>
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between gap-3">
                                            <h6 class="mb-2 small">
                                                <i class="ri-file-text-line rphi-20px me-1"></i>
                                                ${fileSizeFormatted}
                                            </h6>
                                            <span class="h6 mb-0 small">
                                                ${percentage}%
                                            </span>
                                        </div>
                                        <div class="progress w-100 rounded bg-label-success" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: ${percentage}%"
                                                role="progressbar" aria-valuenow="${percentage}" aria-valuemin="0"
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <ul class="list-unstyled m-0 d-flex align-items-center avatar-group">
                                            <li data-bs-toggle="tooltip" data-popup="tooltip-custom"
                                                data-bs-placement="top" class="avatar avatar-sm pull-up"
                                                aria-label="${resource.teacher.name}" data-bs-original-title="${resource.teacher.name}">
                                                <img class="rounded-circle" src="${teacherProfilePic}" alt="Avatar">
                                            </li>
                                        </ul>
                                        <div class="d-flex">
                                            <div class="me-3 text-muted" data-bs-toggle="tooltip" title="{{ trans('main.downloads') }}">
                                                <i class="ri-download-line ri-24px"></i>
                                                <span class="fw-medium">${resource.downloads}</span>
                                            </div>
                                            <div class="me-3 text-muted" data-bs-toggle="tooltip" title="{{ trans('main.views') }}">
                                                <i class="ri-eye-line ri-24px"></i>
                                                <span class="fw-medium">${resource.views}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `);

                        resourcesContainer.append(newCard);

                        $('[data-bs-toggle="tooltip"]').tooltip();
                    });

                    const totalElement = $('.card-title p');
                    totalElement.text(`{{ trans('admin/resources.total_resources', ['count' => '${data.resources.total}']) }}`);

                    const paginationContainer = $('nav[aria-label="Page navigation"]');
                    if (paginationContainer.length) {
                        paginationContainer.html(data.pagination || '');
                        attachPaginationListeners();
                    } else {
                        console.error('Pagination container not found in the DOM');
                    }

                    setTimeout(() => {
                        resetLoadingState();
                    }, 1500);
                },
                error: function(xhr, status, error) {
                    setTimeout(() => {
                        resetLoadingState();
                    }, 1500);

                    if (xhr.status === 429) {
                        toastr.error(tooManyRequestsMessage);
                    } else if (xhr.responseJSON) {
                        if (xhr.responseJSON.error) {
                            toastr.error(xhr.responseJSON.error);
                        } else {
                            toastr.error(errorMessage);
                        }
                    } else {
                        toastr.error(errorMessage);
                    }
                },
                complete: function() {
                    resetLoadingState();
                }
            });
        }

        function attachPaginationListeners() {
            $('nav[aria-label="Page navigation"] .page-link').off('click').on('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href);
                const page = url.searchParams.get('page') || 1;
                updateResources(page);
            });
        }

        $('#search-form').on('submit', function(e) {
            e.preventDefault();
            updateResources();
        });

        $('#grade_id, #teacher_id, #sort, #resource-switch').on('change', updateResources);

        attachPaginationListeners();
    </script>
@endsection
