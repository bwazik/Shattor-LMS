<div class="row">
    <div class="col-md-12">
        <div class="nav-align-top">
            <ul class="nav nav-pills flex-column flex-sm-row mb-6 row-gap-2">
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('parent.students.profile.index') ? 'active' : '' }}"
                        href="{{ route('parent.students.profile.index', $student->uuid) }}"><i
                            class="ri-user-3-line me-2"></i>الإحصائيات</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('parent.students.profile.attendance') ? 'active' : '' }}"
                        href="{{ route('parent.students.profile.attendance', $student->uuid) }}"><i
                            class="ri-calendar-check-line me-2"></i>{{ trans('admin/attendance.attendance') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('parent.students.profile.offline-quizzes') ? 'active' : '' }}"
                        href="{{ route('parent.students.profile.offline-quizzes', $student->uuid) }}"><i
                            class="ri-brain-line me-2"></i>الإمتحانات</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('parent.students.profile.fees') ? 'active' : '' }}"
                        href="{{ route('parent.students.profile.fees', $student->uuid) }}"><i
                            class="ri-bank-line me-2"></i>{{ trans('admin/fees.fees') }}</a>
                </li>
            </ul>
        </div>
    </div>
</div>
