@props([
    'id' => 'datatable',
    'datatableTitle',
    'cardClasses' => null,
    'deleteButton' => false,
    'archiveButton' => false,
    'restoreButton' => false,
    'addButton' => false,
    'dataToggle' => 'modal',
    'otherButton' => false,
    'otherIcon' => null,
    'hrefButton' => false,
    'hrefButtonRoute' => null,
    'excelButton' => false,
    'excelButtonRoute' => null,
])

<div class="card {{ $cardClasses }}">
    {{-- <div class="card-header border-bottom">
        <div class="d-flex justify-content-between align-items-center row gap-5 gx-6 gap-md-0">
            @isset($studentOrTeacherFilter)
                @if ($studentOrTeacherFilter)
                <div class="col-md-12">
                    <form id="student-or-teacher-filter-form" class="me-4" method="GET" action="{{ $route }}">
                        <select id="type" class="form-select" name="type">
                            <option value="all" selected>{{ trans('main.showAll') }}</option>
                            <option value="teachers">{{ trans('admin/teachers.teachers') }}</option>
                            <option value="students">{{ trans('admin/students.students') }}</option>
                        </select>
                    </form>
                </div>
                @endif
            @endisset
        </div>
    </div> --}}
    <div class="card-datatable table-responsive pt-0">
        <div class="card-header d-flex align-items-center justify-content-between flex-column flex-md-row border-bottom">
            <div class="head-label text-center">
                <h5 class="card-title mb-0">{{ $datatableTitle }}</h5>
            </div>
            <div class="dt-action-buttons text-end pt-3 pt-md-0">
                <div class="dt-buttons btn-group flex-wrap">
                    <div class="btn-group">
                        <button class="btn btn-secondary buttons-collection dropdown-toggle btn-label-primary me-4 waves-effect waves-light"
                            data-bs-toggle="dropdown" tabindex="0" aria-controls="datatable" type="button"
                            aria-haspopup="dialog" aria-expanded="false">
                            <span>
                                <i class="ri-external-link-line me-sm-1"></i>
                                <span class="d-none d-sm-inline-block">{{ trans('main.export') }}</span>
                            </span>
                        </button>
                        <div class="dropdown-menu dt-button-collection" aria-modal="true" role="dialog">
                            <div role="menu">
                                <a class="dt-button dropdown-item print-button" tabindex="0" href="#"><span><i class="ri-printer-line me-1"></i>Print</span></a>
                                <a class="dt-button dropdown-item csv-button buttons-html5" tabindex="0"href="#"><span><i class="ri-file-text-line me-1"></i>Csv</span></a>
                                <a class="dt-button dropdown-item excel-button buttons-html5" tabindex="0"href="#"><span><i class="ri-file-excel-line me-1"></i>Excel</span></a>
                                <a class="dt-button dropdown-item pdf-button buttons-html5" tabindex="0"href="#"><span><i class="ri-file-pdf-line me-1"></i>Pdf</span></a>
                                <a class="dt-button dropdown-item copy-button buttons-html5" tabindex="0"href="#"><span><i class="ri-file-copy-line me-1"></i>Copy</span></a>
                            </div>
                        </div>
                    </div>
                    @isset($deleteButton)
                        @if ($deleteButton)
                        <button id="delete-selected-btn" class="btn btn-danger me-4 waves-effect waves-light" tabindex="0"
                            data-bs-target="#delete-selected-modal" data-bs-toggle="modal" data-bs-dismiss="modal">
                            <span>
                                <i class="ri-delete-bin-line ri-16px me-sm-2"></i>
                                <span class="d-none d-sm-inline-block">{{ trans('main.deleteSelected') }}</span>
                            </span>
                        </button>
                        @endif
                    @endisset
                    @isset($archiveButton)
                        @if ($archiveButton)
                        <button id="archive-selected-btn" class="btn btn-primary me-4 waves-effect waves-light" tabindex="0"
                            data-bs-target="#archive-selected-modal" data-bs-toggle="modal" data-bs-dismiss="modal">
                            <span>
                                <i class="ri-inbox-archive-line ri-16px me-sm-2"></i>
                                <span class="d-none d-sm-inline-block">{{ trans('main.archiveSelected') }}</span>
                            </span>
                        </button>
                        @endif
                    @endisset
                    @isset($restoreButton)
                        @if ($restoreButton)
                        <button id="restore-selected-btn" class="btn btn-primary me-4 waves-effect waves-light" tabindex="0"
                            data-bs-target="#restore-selected-modal" data-bs-toggle="modal" data-bs-dismiss="modal">
                            <span>
                                <i class="ri-arrow-go-back-line ri-16px me-sm-2"></i>
                                <span class="d-none d-sm-inline-block">{{ trans('main.restoreSelected') }}</span>
                            </span>
                        </button>
                        @endif
                    @endisset
                    @isset($rejectButton)
                        @if ($rejectButton)
                        <button id="reject-selected-btn" class="btn btn-danger me-4 waves-effect waves-light" tabindex="0"
                            data-bs-target="#reject-selected-modal" data-bs-toggle="modal" data-bs-dismiss="modal">
                            <span>
                                <i class="ri-close-line ri-16px me-sm-2"></i>
                                <span class="d-none d-sm-inline-block">{{ trans('main.rejectSelected') }}</span>
                            </span>
                        </button>
                        @endif
                    @endisset
                    @isset($acceptButton)
                        @if ($acceptButton)
                        <button id="accept-selected-btn" class="btn btn-success me-4 waves-effect waves-light" tabindex="0"
                            data-bs-target="#accept-selected-modal" data-bs-toggle="modal" data-bs-dismiss="modal">
                            <span>
                                <i class="ri-check-line ri-16px me-sm-2"></i>
                                <span class="d-none d-sm-inline-block">{{ trans('main.acceptSelected') }}</span>
                            </span>
                        </button>
                        @endif
                    @endisset
                    @isset($addButton)
                        @if ($addButton)
                        <button id="add-button" class="btn btn-primary waves-effect waves-light" tabindex="0"
                            data-bs-toggle="{{ $dataToggle }}" data-bs-target="#add-modal">
                            <span>
                                <i class="ri-add-line ri-16px me-sm-2"></i>
                                <span class="d-none d-sm-inline-block">{{ $addButton }}</span>
                            </span>
                        </button>
                        @endif
                    @endisset
                    @isset($excelButton)
                        @if ($excelButton)
                            <a href="{{ $excelButtonRoute }}" id="excel-button" class="btn btn-success me-4 waves-effect waves-light" style="border-radius: 0.5rem; !important">
                                <span>
                                    <i class="ri-file-excel-2-line ri-16px me-sm-2"></i>
                                    <span class="d-none d-sm-inline-block">{{ trans('main.excelExport') }}</span>
                                </span>
                            </a>
                        @endif
                    @endisset
                    @isset($otherButton)
                        @if ($otherButton)
                            <button id="other-button" class="btn btn-primary waves-effect waves-light">
                                <span>
                                    <i class="{{ $otherIcon }} ri-16px me-sm-2"></i>
                                    <span class="d-none d-sm-inline-block">{{ $otherButton }}</span>
                                </span>
                            </button>
                        @endif
                    @endisset
                    @isset($hrefButton)
                        @if ($hrefButton)
                            <a href="{{ $hrefButtonRoute }}" id="href-button" class="btn btn-primary waves-effect waves-light" style="border-radius: 0.5rem; !important">
                                <span>
                                    <i class="ri-add-line ri-16px me-sm-2"></i>
                                    <span class="d-none d-sm-inline-block">{{ $hrefButton }}</span>
                                </span>
                            </a>
                        @endif
                    @endisset
                </div>
            </div>
        </div>
        <table id="{{ $id }}" class="datatables-basic table ">
            <thead>
                <tr>
                    {{ $slot }}
                </tr>
            </thead>
        </table>
    </div>
</div>
