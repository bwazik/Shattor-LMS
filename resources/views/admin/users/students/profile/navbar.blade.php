<div class="row">
    <div class="col-md-12">
        <div class="nav-align-top">
            <ul class="nav nav-pills flex-column flex-sm-row mb-6 row-gap-2">
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('admin.students.profile.index') ? 'active' : '' }}"
                        href="{{ route('admin.students.profile.index', $student->id) }}"><i
                            class="ri-user-3-line me-2"></i>{{ trans('main.profile') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('admin.students.profile.attendance') ? 'active' : '' }}"
                        href="{{ route('admin.students.profile.attendance', $student->id) }}"><i
                            class="ri-calendar-check-line me-2"></i>{{ trans('admin/attendance.attendance') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('admin.students.profile.quizzes') ? 'active' : '' }}"
                        href="{{ route('admin.students.profile.quizzes', $student->id) }}"><i
                            class="ri-brain-line me-2"></i>{{ trans('admin/quizzes.quizzes') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('admin.students.profile.offline-quizzes') ? 'active' : '' }}"
                        href="{{ route('admin.students.profile.offline-quizzes', $student->id) }}"><i
                            class="ri-brain-line me-2"></i>{{ trans('admin/offlineQuizzes.offlineQuizzes') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('admin.students.profile.assignments') ? 'active' : '' }}"
                        href="{{ route('admin.students.profile.assignments', $student->id) }}"><i
                            class="ri-file-copy-2-line me-2"></i>{{ trans('admin/assignments.assignments') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('admin.students.profile.fees') ? 'active' : '' }}"
                        href="{{ route('admin.students.profile.fees', $student->id) }}"><i
                            class="ri-bank-line me-2"></i>{{ trans('admin/fees.fees') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ isActiveRoute('admin.students.profile.security') ? 'active' : '' }}"
                        href="{{ route('admin.students.profile.security', $student->id) }}"><i
                            class="ri-lock-line me-2"></i>{{ trans('account.security') }}</a>
                </li>
            </ul>
        </div>
    </div>
</div>
