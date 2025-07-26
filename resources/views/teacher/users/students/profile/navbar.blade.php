<div class="row">
    <div class="col-md-12">
        <div class="nav-align-top">
            <ul class="nav nav-pills flex-column flex-sm-row mb-6 row-gap-2">
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('teacher.students.profile.index') ? 'active' : '' }}"
                        href="{{ route('teacher.students.profile.index', $student->uuid) }}"><i
                            class="ri-user-3-line me-2"></i>{{ trans('main.profile') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('teacher.students.profile.attendance') ? 'active' : '' }}"
                        href="{{ route('teacher.students.profile.attendance', $student->uuid) }}"><i
                            class="ri-calendar-check-line me-2"></i>{{ trans('admin/attendance.attendance') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href=""><i
                            class="ri-brain-line me-2"></i>{{ trans('admin/quizzes.quizzes') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href=""><i
                            class="ri-file-copy-2-line me-2"></i>{{ trans('admin/assignments.assignments') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href=""><i
                            class="ri-bank-line me-2"></i>{{ trans('admin/fees.fees') }}</a>
                </li>
            </ul>
        </div>
    </div>
</div>
