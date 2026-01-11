<div class="row">
    <div class="col-12">
        <div class="card mb-6">
            <div class="user-profile-header-banner">
                <img src="{{ asset('assets/img/pages/profile-banner.png') }}" alt="Banner image" class="rounded-top" />
            </div>
            <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
                <div class="flex-shrink-0 mt-n2 mx-sm-0 mx-auto profile-img-container position-relative">
                    <img src="{{ $student->profile_pic ? asset('storage/profiles/students/' . $student->profile_pic) : asset('assets/img/avatars/default.jpg') }}"
                        alt="avatar" class="d-block h-auto ms-0 ms-sm-5 rounded-4 user-profile-img"
                        id="uploadedAvatar" />
                </div>
                <div class="flex-grow-1 mt-4 mt-sm-12">
                    <div
                        class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                        <div class="user-profile-info">
                            <h4 class="mb-2">{{ $student->name }}</h4>
                            <ul
                                class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                                <li class="list-inline-item">
                                    <i class="ri-survey-line me-2 ri-24px"></i><span
                                        class="fw-medium">{{ $student->grade->name }}</span>
                                </li>
                                <li class="list-inline-item">
                                    <i class="ri-phone-line me-2 ri-24px"></i><span
                                        class="fw-medium">{{ $student->phone }}</span>
                                </li>
                                <li class="list-inline-item">
                                    <i class="ri-calendar-line me-2 ri-24px"></i><span
                                        class="fw-medium">{{ trans('main.joined') }}
                                        {{ $student->created_at->isoFormat('LL') }}</span>
                                </li>
                            </ul>
                        </div>
                        @if ($student->is_active)
                            <span
                                class="badge rounded-pill bg-label-success text-capitalized">{{ trans('main.active') }}</span>
                        @else
                            <span
                                class="badge rounded-pill bg-label-secondary text-capitalized">{{ trans('main.inactive') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
