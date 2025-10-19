<div class="col-xl-4 col-lg-5 col-md-5">
    <div class="card mb-6">
        <div class="card-body">
            <div class="d-flex justify-content-around flex-wrap mb-6 gap-0 gap-md-3 gap-lg-4">
                <div class="d-flex align-items-center me-5 gap-4">
                    <div class="avatar">
                        <div class="avatar-initial bg-label-primary rounded-3">
                            <i class="ri-group-2-line ri-24px"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ $student->groups->count() }}</h5>
                        <span>{{ trans('admin/students.groupsCount') }}</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-4">
                    <div class="avatar">
                        <div class="avatar-initial bg-label-primary rounded-3">
                            <i class="ri-money-dollar-circle-line ri-24px"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ $student->balance }} {{ trans('main.currency') }}</h5>
                        <span>{{ trans('main.balance') }}</span>
                    </div>
                </div>
            </div>
            <small class="card-text text-uppercase text-muted small">{{ trans('main.personalDetails') }}</small>
            <ul class="list-unstyled my-3 py-1">
                <li class="d-flex align-items-center mb-4">
                    <i class="ri-user-3-line ri-24px"></i>
                    <span class="fw-medium mx-2">{{ trans('main.realName_ar') }}:</span>
                    <span>{{ $student->getTranslation('name', 'ar') }}</span>
                </li>
                <li class="d-flex align-items-center mb-4">
                    <i class="ri-user-3-line ri-24px"></i>
                    <span class="fw-medium mx-2">{{ trans('main.realName_en') }}:</span>
                    <span>{{ $student->getTranslation('name', 'en') }}</span>
                </li>
                <li class="d-flex align-items-center mb-4">
                    <i class="ri-at-line ri-24px"></i>
                    <span class="fw-medium mx-2">{{ trans('main.username') }}:</span>
                    <span>{{ $student->username }}</span>
                </li>
                <li class="d-flex align-items-center mb-4">
                    <i class="ri-men-line ri-24px"></i>
                    <span class="fw-medium mx-2">{{ trans('main.gender') }}:</span>
                    <span>{{ trans('main.' . ($student->gender == 1 ? 'male' : 'female')) }}</span>
                </li>
                <li class="d-flex align-items-center mb-4">
                    <i class="ri-calendar-line ri-24px"></i>
                    <span class="fw-medium mx-2">{{ trans('main.birth_date') }}:</span>
                    <span>{{ $student->birth_date ?? 'N/A' }}</span>
                </li>
            </ul>
            <small class="card-text text-uppercase text-muted small">{{ trans('main.contacts') }}</small>
            <ul class="list-unstyled my-3 py-1">
                <li class="d-flex align-items-center mb-4">
                    <i class="ri-phone-line ri-24px"></i>
                    <span class="fw-medium mx-2">{{ trans('main.phone') }}:</span>
                    <span>{{ $student->phone ?? 'N/A' }}</span>
                </li>
                <li class="d-flex align-items-center mb-4">
                    <i class="ri-mail-line ri-24px"></i>
                    <span class="fw-medium mx-2">{{ trans('main.email') }}:</span>
                    <span>{{ $student->email ?? 'N/A' }}</span>
                </li>
            </ul>
            <small
                class="card-text text-uppercase text-muted small">{{ trans('main.academicDetails') }}</small>
            <ul class="list-unstyled mb-0 mt-3 pt-1">
                <li class="d-flex align-items-center mb-4">
                    <i class="ri-survey-line ri-24px"></i>
                    <span class="fw-medium mx-2">{{ trans('main.grade') }}:</span>
                    <span>{{ $student->grade->name }}</span>
                </li>
                <li class="d-flex align-items-center mb-4">
                    <i class="ri-flask-line ri-24px"></i>
                    <span class="fw-medium mx-2">{{ trans('main.specialization') }}:</span>
                    <span>{{ trans('main.' . ($student->specialization == 1 ? 'scientific' : 'literary')) }}</span>
                </li>
                <li class="d-flex align-items-center mb-4">
                    <i class="ri-parent-line ri-24px"></i>
                    <span class="fw-medium mx-2">{{ trans('main.parent') }}:</span>
                    <span>
                        @if ($student->parent)
                            <a
                                href="{{ route('teacher.parents.profile.index', $student->parent->uuid) }}">{{ $student->parent->name }}</a>
                      		- {{ $student->parent->phone }}
                        @else
                            N/A
                        @endif
                    </span>
                </li>
            </ul>
        </div>
    </div>
</div>
