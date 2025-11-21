@extends('layouts.student.master')

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
                        <form id="search-form" method="GET" action="{{ route('student.resources.index') }}"
                            class="d-flex align-items-center justify-content-between app-academy-md-80">
                            <input type="search" name="search" placeholder="{{ trans('admin/resources.search_resources') }}"
                                class="form-control form-control-sm me-4" value="{{ request('search') }}" />
                            <button type="submit" class="btn btn-primary btn-icon me-2">
                                <i class="ri-search-line ri-22px"></i>
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
                <form id="filter-form" method="GET" action="{{ route('student.resources.index') }}"
                    class="d-flex justify-content-md-end align-items-center gap-6 flex-wrap">
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
                                        </div>
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-text-secondary rounded-pill text-muted border-0 p-1 waves-effect waves-light"
                                                type="button" id="financeApp_{{ $resource->uuid }}" data-bs-toggle="dropdown"
                                                aria-haspopup="true" aria-expanded="false">
                                                <i class="ri-more-2-line ri-20px"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="financeApp_{{ $resource->uuid }}">
                                                <a target="_blank" href="{{ route('student.resources.details', $resource->uuid) }}" class="dropdown-item waves-effect">{{ trans('main.details') }}</a>
                                                <a href="{{ route('student.resources.download', $resource->uuid) }}" class="dropdown-item waves-effect">{{ trans('main.download') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                    <a target="_blank" href="{{ route('student.resources.details', $resource->uuid) }}" class="h5 mb-1">{{ Str::limit($resource->title, 50) }}</a>
                                    <p class="fw-medium small">{{ isoFormat($resource->created_at ?? now()) }}</p>
                                    <p class="my-4 small">{{ Str::limit($resource->description ?? '-', 50) }}</p>
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
                                                <span class="fw-medium">{{ $resource->resource_views_sum_views }}</span>
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
@endsection

@section('page-js')
    <script>
        @if(session('error'))
            toastr.error("{{ session('error') }}");
        @endif
        @if(session('success'))
            toastr.success("{{ session('success') }}");
        @endif

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

            const detailsRouteBase = "{{ route('student.resources.details', ':id') }}";
            const downloadRouteBase = "{{ route('student.resources.download', ':id') }}";

            $.ajax({
                url: "{{ route('student.resources.index') }}",
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

                        const detailsUrl = detailsRouteBase.replace(':id', resource.uuid);
                        const downloadUrl = downloadRouteBase.replace(':id', resource.uuid);

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
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-text-secondary rounded-pill text-muted border-0 p-1 waves-effect waves-light" type="button" id="financeApp_${resource.uuid}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="ri-more-2-line ri-20px"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="financeApp_${resource.uuid}">
                                                <a target="_blank" href="${detailsUrl}" class="dropdown-item waves-effect">{{ trans('main.details') }}</a>
                                                <a href="${downloadUrl}" class="dropdown-item waves-effect">{{ trans('main.download') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                    <a target="_blank" href="${detailsUrl}" class="h5 mb-1">${strLimit(resource.title, 50)}</a>
                                    <p class="fw-medium small">${createdAt}</p>
                                    <p class="my-4 small">${strLimit(resource.description || '-', 50)}</p>
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

        $('#sort').on('change', updateResources);

        attachPaginationListeners();
    </script>
@endsection
