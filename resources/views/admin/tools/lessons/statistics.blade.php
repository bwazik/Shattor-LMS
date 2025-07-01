<div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3">
        <div class="card card-border-shadow-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="me-1">
                        <p class="text-heading mb-1">{{ trans('admin/lessons.total_lessons') }}</p>
                        <div class="d-flex align-items-center">
                            <h4 class="mb-1 me-2">{{ $pageStatistics['totalLessons'] }}</h4>
                        </div>
                        <small class="mb-0">{{ trans('admin/lessons.all_lessons') }}</small>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-primary rounded-3">
                            <i class="ri-pencil-ruler-line ri-28px"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <div class="col-sm-6 col-xl-3">
        <div class="card card-border-shadow-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="me-1">
                        <p class="text-heading mb-1">{{ trans('admin/lessons.completed_lessons') }}</p>
                        <div class="d-flex align-items-center">
                            <h4 class="mb-1 me-2">{{ $pageStatistics['completedLessons'] }}</h4>
                            <p class="text-success mb-1">
                                ({{ $pageStatistics['totalLessons'] > 0 ? round(($pageStatistics['completedLessons'] / $pageStatistics['totalLessons']) * 100, 2) : 0 }}%)
                            </p>
                        </div>
                        <small class="mb-0">{{ trans('admin/lessons.completed_lessons_caption') }}</small>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-success rounded-3">
                            <i class="ri-checkbox-circle-line ri-28px"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-border-shadow-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="me-1">
                        <p class="text-heading mb-1">{{ trans('admin/lessons.scheduled_lessons') }}</p>
                        <div class="d-flex align-items-center">
                            <h4 class="mb-1 me-2">{{ $pageStatistics['scheduledLessons'] }}</h4>
                            <p class="text-warning mb-1">
                                ({{ $pageStatistics['totalLessons'] > 0 ? round(($pageStatistics['scheduledLessons'] / $pageStatistics['totalLessons']) * 100, 2) : 0 }}%)
                            </p>
                        </div>
                        <small class="mb-0">{{ trans('admin/lessons.scheduled_lessons_caption') }}</small>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-warning rounded-3">
                            <i class="ri-calendar-check-line ri-28px"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-border-shadow-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="me-1">
                        <p class="text-heading mb-1">{{ trans('admin/lessons.canceled_lessons') }}</p>
                        <div class="d-flex align-items-center">
                            <h4 class="mb-1 me-2">{{ $pageStatistics['canceledLessons'] }}</h4>
                            <p class="text-danger mb-1">
                                ({{ $pageStatistics['totalLessons'] > 0 ? round(($pageStatistics['canceledLessons'] / $pageStatistics['totalLessons']) * 100, 2) : 0 }}%)
                            </p>
                        </div>
                        <small class="mb-0">{{ trans('admin/lessons.canceled_lessons_caption') }}</small>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-danger rounded-3">
                            <i class="ri-close-circle-line ri-28px"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
