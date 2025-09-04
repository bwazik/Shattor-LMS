<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('admin.dashboard') }}" class="app-brand-link">
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
        <li class="menu-item {{ isActiveRoute('admin.dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-home-smile-line"></i>
                <div>{{ trans('layouts/sidebar.dashboard') }}</div>
            </a>
        </li>

        <!-- Platform Managment -->
        <li class="menu-header mt-5">
            <span class="menu-header-text">{{ trans('layouts/sidebar.platformManagment') }}</span>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.stages.index') ? 'active' : '' }}">
            <a href="{{ route('admin.stages.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-building-line"></i>
                <div>{{ trans('layouts/sidebar.stages') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.grades.index') ? 'active' : '' }}">
            <a href="{{ route('admin.grades.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-survey-line"></i>
                <div>{{ trans('layouts/sidebar.grades') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.subjects.index') ? 'active' : '' }}">
            <a href="{{ route('admin.subjects.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-book-shelf-line"></i>
                <div>{{ trans('layouts/sidebar.subjects') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.plans.index') ? 'active' : '' }}">
            <a href="{{ route('admin.plans.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-money-dollar-circle-line"></i>
                <div>{{ trans('layouts/sidebar.plans') }}</div>
            </a>
        </li>

        <!-- Users Managment -->
        <li class="menu-header mt-5">
            <span class="menu-header-text">{{ trans('layouts/sidebar.usersManagment') }}</span>
        </li>
        <li
            class="menu-item {{ isActiveRoute(['admin.teachers.index', 'admin.teachers.archived']) ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle waves-effect">
                <i class="menu-icon tf-icons ri-presentation-line"></i>
                <div>{{ trans('layouts/sidebar.teachers') }}</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ isActiveRoute('admin.teachers.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.teachers.index') }}" class="menu-link">
                        <div>{{ trans('layouts/sidebar.teachersManagment') }}</div>
                    </a>
                </li>
                <li class="menu-item {{ isActiveRoute('admin.teachers.archived') ? 'active' : '' }}">
                    <a href="{{ route('admin.teachers.archived') }}" class="menu-link">
                        <div>{{ trans('layouts/sidebar.archived') }}</div>
                    </a>
                </li>
            </ul>
        </li>
        <li
            class="menu-item {{ isActiveRoute(['admin.assistants.index', 'admin.assistants.archived']) ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle waves-effect">
                <i class="menu-icon tf-icons ri-user-star-line"></i>
                <div>{{ trans('layouts/sidebar.assistants') }}</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ isActiveRoute('admin.assistants.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.assistants.index') }}" class="menu-link">
                        <div>{{ trans('layouts/sidebar.assistantsManagment') }}</div>
                    </a>
                </li>
                <li class="menu-item {{ isActiveRoute('admin.assistants.archived') ? 'active' : '' }}">
                    <a href="{{ route('admin.assistants.archived') }}" class="menu-link">
                        <div>{{ trans('layouts/sidebar.archived') }}</div>
                    </a>
                </li>
            </ul>
        </li>
        <li
            class="menu-item {{ isActiveRoute(['admin.students.index', 'admin.students.archived']) ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle waves-effect">
                <i class="menu-icon tf-icons ri-graduation-cap-line"></i>
                <div>{{ trans('layouts/sidebar.students') }}</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ isActiveRoute('admin.students.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.students.index') }}" class="menu-link">
                        <div>{{ trans('layouts/sidebar.studentsManagment') }}</div>
                    </a>
                </li>
                <li class="menu-item {{ isActiveRoute('admin.students.archived') ? 'active' : '' }}">
                    <a href="{{ route('admin.students.archived') }}" class="menu-link">
                        <div>{{ trans('layouts/sidebar.archived') }}</div>
                    </a>
                </li>
            </ul>
        </li>
        <li
            class="menu-item {{ isActiveRoute(['admin.parents.index', 'admin.parents.archived']) ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle waves-effect">
                <i class="menu-icon tf-icons ri-parent-line"></i>
                <div>{{ trans('layouts/sidebar.parents') }}</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ isActiveRoute('admin.parents.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.parents.index') }}" class="menu-link">
                        <div>{{ trans('layouts/sidebar.parentsManagment') }}</div>
                    </a>
                </li>
                <li class="menu-item {{ isActiveRoute('admin.parents.archived') ? 'active' : '' }}">
                    <a href="{{ route('admin.parents.archived') }}" class="menu-link">
                        <div>{{ trans('layouts/sidebar.archived') }}</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Tools -->
        <li class="menu-header mt-5">
            <span class="menu-header-text">{{ trans('layouts/sidebar.tools') }}</span>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.groups.index') ? 'active' : '' }}">
            <a href="{{ route('admin.groups.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-group-2-line"></i>
                <div>{{ trans('layouts/sidebar.groups') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.lessons.index') ? 'active' : '' }}">
            <a href="{{ route('admin.lessons.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-pencil-ruler-line"></i>
                <div>{{ trans('layouts/sidebar.lessons') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.resources.index') ? 'active' : '' }}">
            <a href="{{ route('admin.resources.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-folders-line"></i>
                <div>{{ trans('layouts/sidebar.resources') }}</div>
            </a>
        </li>

        <!-- Activities -->
        <li class="menu-header mt-5">
            <span class="menu-header-text">{{ trans('layouts/sidebar.activities') }}</span>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.compensatories.index') ? 'active' : '' }}">
            <a href="{{ route('admin.compensatories.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-time-line"></i>
                <div>{{ trans('layouts/sidebar.compensatories') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.attendance.index') ? 'active' : '' }}">
            <a href="{{ route('admin.attendance.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-calendar-check-line"></i>
                <div>{{ trans('layouts/sidebar.attendance') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.zooms.index') ? 'active' : '' }}">
            <a href="{{ route('admin.zooms.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-video-on-line"></i>
                <div>{{ trans('layouts/sidebar.zooms') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.quizzes.index') ? 'active' : '' }}">
            <a href="{{ route('admin.quizzes.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-brain-line"></i>
                <div>{{ trans('layouts/sidebar.quizzes') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.assignments.index') ? 'active' : '' }}">
            <a href="{{ route('admin.assignments.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-file-copy-2-line"></i>
                <div>{{ trans('layouts/sidebar.assignments') }}</div>
            </a>
        </li>

        <!-- Finance -->
        <li class="menu-header mt-5">
            <span class="menu-header-text">{{ trans('layouts/sidebar.financeManagment') }}</span>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.fees.index') ? 'active' : '' }}">
            <a href="{{ route('admin.fees.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-bank-line"></i>
                <div>{{ trans('layouts/sidebar.fees') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.student-fees.index') ? 'active' : '' }}">
            <a href="{{ route('admin.student-fees.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-user-2-line"></i>
                <div>{{ trans('layouts/sidebar.student-fees') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.teacher-subscriptions.index') ? 'active' : '' }}">
            <a href="{{ route('admin.teacher-subscriptions.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-id-card-line"></i>
                <div>{{ trans('layouts/sidebar.teacher-subscriptions') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute(['admin.invoices.index', 'admin.invoices.archived', 'admin.invoices.teachers.index', 'admin.invoices.teachers.archived']) ? 'active open' : '' }}">
            <a href="javascript:void(0)" class="menu-link menu-toggle waves-effect">
                <i class="menu-icon tf-icons ri-money-dollar-circle-line"></i>
                <div>{{ trans('layouts/sidebar.invoices') }}</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ isActiveRoute(['admin.invoices.index', 'admin.invoices.archived']) ? 'active open' : '' }}">
                    <a href="javascript:void(0)" class="menu-link menu-toggle waves-effect">
                        <div>{{ trans('admin/students.students') }}</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ isActiveRoute('admin.invoices.index') ? 'active' : '' }}">
                            <a href="{{ route('admin.invoices.index') }}" class="menu-link">
                                <div>{{ trans('layouts/sidebar.invoices') }}</div>
                            </a>
                        </li>
                        <li class="menu-item {{ isActiveRoute('admin.invoices.archived') ? 'active' : '' }}">
                            <a href="{{ route('admin.invoices.archived') }}" class="menu-link">
                                <div>{{ trans('layouts/sidebar.archived') }}</div>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
            <ul class="menu-sub">
                <li class="menu-item {{ isActiveRoute(['admin.invoices.teachers.index', 'admin.invoices.teachers.archived']) ? 'active open' : '' }}">
                    <a href="javascript:void(0)" class="menu-link menu-toggle waves-effect">
                        <div>{{ trans('admin/teachers.teachers') }}</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ isActiveRoute('admin.invoices.teachers.index') ? 'active' : '' }}">
                            <a href="{{ route('admin.invoices.teachers.index') }}" class="menu-link">
                                <div>{{ trans('layouts/sidebar.invoices') }}</div>
                            </a>
                        </li>
                        <li class="menu-item {{ isActiveRoute('admin.invoices.teachers.archived') ? 'active' : '' }}">
                            <a href="{{ route('admin.invoices.teachers.archived') }}" class="menu-link">
                                <div>{{ trans('layouts/sidebar.archived') }}</div>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </li>
        <li
            class="menu-item {{ isActiveRoute(['admin.transactions.students', 'admin.transactions.teachers']) ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle waves-effect">
                <i class="menu-icon tf-icons ri-exchange-dollar-line"></i>
                <div>{{ trans('layouts/sidebar.transactions') }}</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ isActiveRoute('admin.transactions.students') ? 'active' : '' }}">
                    <a href="{{ route('admin.transactions.students') }}" class="menu-link">
                        <div>{{ trans('admin/students.students') }}</div>
                    </a>
                </li>
                <li class="menu-item {{ isActiveRoute('admin.transactions.teachers') ? 'active' : '' }}">
                    <a href="{{ route('admin.transactions.teachers') }}" class="menu-link">
                        <div>{{ trans('admin/teachers.teachers') }}</div>
                    </a>
                </li>
            </ul>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.coupons.index') ? 'active' : '' }}">
            <a href="{{ route('admin.coupons.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-coupon-2-line"></i>
                <div>{{ trans('layouts/sidebar.coupons') }}</div>
            </a>
        </li>

        <!-- Misc -->
        <li class="menu-header mt-5">
            <span class="menu-header-text">{{ trans('layouts/sidebar.misc') }}</span>
        </li>
        <li class="menu-item {{ isActiveRoute('admin.whatsapp-messages.index') ? 'active' : '' }}">
            <a href="{{ route('admin.whatsapp-messages.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ri-whatsapp-line"></i>
                <div>{{ trans('layouts/sidebar.whatsappMessages') }}</div>
            </a>
        </li>
        <li class="menu-item {{ isActiveRoute(['admin.categories.index', 'admin.faqs.index']) ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle waves-effect">
                <i class="menu-icon tf-icons ri-customer-service-2-line"></i>
                <div>{{ trans('layouts/sidebar.support') }}</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ isActiveRoute('admin.categories.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.categories.index') }}" class="menu-link">
                        <div>{{ trans('admin/categories.categories') }}</div>
                    </a>
                </li>
                <li class="menu-item {{ isActiveRoute('admin.faqs.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.faqs.index') }}" class="menu-link">
                        <div>{{ trans('admin/faqs.faqs') }}</div>
                    </a>
                </li>
                <li class="menu-item {{ isActiveRoute('admin.help-center.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.help-center.index') }}" class="menu-link">
                        <div>{{ trans('layouts/sidebar.helpCenter') }}</div>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</aside>
