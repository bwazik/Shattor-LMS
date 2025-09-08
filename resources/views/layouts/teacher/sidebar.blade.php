<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('teacher.dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img width="50" height="50" src="{{ asset('assets/img/brand/navbar.png') }}" alt="Shattor">
            </span>
            <span
                class="app-brand-text demo menu-text fw-semibold ms-2">{{ trans('layouts/sidebar.platformName') }}</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M8.47365 11.7183C8.11707 12.0749 8.11707 12.6531 8.47365 13.0097L12.071 16.607C12.4615 16.9975 12.4615 17.6305 12.071 18.021C11.6805 18.4115 11.0475 18.4115 10.657 18.021L5.83009 13.1941C5.37164 12.7356 5.37164 11.9924 5.83009 11.5339L10.657 6.707C11.0475 6.31653 11.6805 6.31653 12.071 6.707C12.4615 7.09747 12.4615 7.73053 12.071 8.121L8.47365 11.7183Z"
                    fill-opacity="0.9" />
                <path
                    d="M14.3584 11.8336C14.0654 12.1266 14.0654 12.6014 14.3584 12.8944L18.071 16.607C18.4615 16.9975 18.4615 17.6305 18.071 18.021C17.6805 18.4115 17.0475 18.4115 16.657 18.021L11.6819 13.0459C11.3053 12.6693 11.3053 12.0587 11.6819 11.6821L16.657 6.707C17.0475 6.31653 17.6805 6.31653 18.071 6.707C18.4615 7.09747 18.4615 7.73053 18.071 8.121L14.3584 11.8336Z"
                    fill-opacity="0.4" />
            </svg>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item {{ isActiveRoute('teacher.dashboard') ? 'active' : '' }}">
            <a href="{{ route('teacher.dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-home-smile-line"></i>
                <div>{{ trans('layouts/sidebar.dashboard') }}</div>
            </a>
        </li>

        <!-- Platform Managment -->
        <li class="menu-header mt-5">
            <span class="menu-header-text">{{ trans('layouts/sidebar.platformManagment') }}</span>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.plans.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.plans.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-money-dollar-circle-line"></i>
                <div>{{ trans('layouts/sidebar.plans') }}</div>
            </a>
        </li>

        <!-- Tools -->
        <li class="menu-header mt-5">
            <span class="menu-header-text">{{ trans('layouts/sidebar.tools') }}</span>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.grades.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.grades.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-survey-line"></i>
                <div>{{ trans('layouts/sidebar.grades') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.groups.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.groups.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-group-2-line"></i>
                <div>{{ trans('layouts/sidebar.groups') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.lessons.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.lessons.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-pencil-ruler-line"></i>
                <div>{{ trans('layouts/sidebar.lessons') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.resources.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.resources.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-folders-line"></i>
                <div>{{ trans('layouts/sidebar.resources') }}</div>
            </a>
        </li>

        <!-- Users Managment -->
        <li class="menu-header mt-5">
            <span class="menu-header-text">{{ trans('layouts/sidebar.usersManagment') }}</span>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.assistants.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.assistants.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-user-star-line"></i>
                <div>{{ trans('layouts/sidebar.assistants') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.students.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.students.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-graduation-cap-line"></i>
                <div>{{ trans('layouts/sidebar.students') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.parents.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.parents.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-parent-line"></i>
                <div>{{ trans('layouts/sidebar.parents') }}</div>
            </a>
        </li>

        <!-- Activities -->
        <li class="menu-header mt-5">
            <span class="menu-header-text">{{ trans('layouts/sidebar.activities') }}</span>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.compensatories.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.compensatories.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-time-line"></i>
                <div>{{ trans('layouts/sidebar.compensatories') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.attendance.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.attendance.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-calendar-check-line"></i>
                <div>{{ trans('layouts/sidebar.attendance') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.zooms.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.zooms.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-video-on-line"></i>
                <div>{{ trans('layouts/sidebar.zooms') }}</div>
            </a>
        </li>
        <li
            class="menu-item {{ isActiveRoute(['teacher.quizzes.index', 'teacher.offline-quizzes.index']) ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle waves-effect">
                <i class="menu-icon tf-icons ri-brain-line"></i>
                <div>{{ trans('layouts/sidebar.quizzes') }}</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ isActiveRoute('teacher.quizzes.index') ? 'active' : '' }}">
                    <a href="{{ route('teacher.quizzes.index') }}" class="menu-link">
                        <div>{{ trans('layouts/sidebar.online') }}</div>
                    </a>
                </li>
                <li class="menu-item {{ isActiveRoute('teacher.offline-quizzes.index') ? 'active' : '' }}">
                    <a href="{{ route('teacher.offline-quizzes.index') }}" class="menu-link">
                        <div>{{ trans('layouts/sidebar.offline') }}</div>
                    </a>
                </li>
            </ul>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.assignments.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.assignments.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-file-copy-2-line"></i>
                <div>{{ trans('layouts/sidebar.assignments') }}</div>
            </a>
        </li>

        <!-- Finance -->
        <li class="menu-header mt-5">
            <span class="menu-header-text">{{ trans('layouts/sidebar.financeManagment') }}</span>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.fees.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.fees.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-bank-line"></i>
                <div>{{ trans('layouts/sidebar.fees') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.student-fees.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.student-fees.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-user-2-line"></i>
                <div>{{ trans('layouts/sidebar.student-fees') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.invoices.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.invoices.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-money-dollar-circle-line"></i>
                <div>{{ trans('layouts/sidebar.invoices') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.transactions.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.transactions.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-exchange-dollar-line"></i>
                <div>{{ trans('layouts/sidebar.transactions') }}</div>
            </a>
        </li>

        <!-- Misc -->
        <li class="menu-header mt-5">
            <span class="menu-header-text">{{ trans('layouts/sidebar.misc') }}</span>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.faqs.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.faqs.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-question-line"></i>
                <div>{{ trans('layouts/sidebar.faqs') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('teacher.help-center.index') ? 'active' : '' }}">
            <a href="{{ route('teacher.help-center.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-customer-service-2-line"></i>
                <div>{{ trans('layouts/sidebar.helpCenter') }}</div>
            </a>
        </li>
    </ul>
</aside>
