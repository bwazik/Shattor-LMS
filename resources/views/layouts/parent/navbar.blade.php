<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
            <i class="ri-menu-fill ri-22px"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Search -->
        <div class="navbar-nav align-items-center">
            <div class="nav-item navbar-search-wrapper mb-0">
                <a class="nav-item nav-link search-toggler fw-normal px-0" href="javascript:void(0);">
                    <i class="ri-search-line ri-22px scaleX-n1-rtl me-3"></i>
                    <span class="d-none d-md-inline-block text-muted">Search (Ctrl+/)</span>
                </a>
            </div>
        </div>
        <!-- /Search -->

        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <!-- Language -->
            <li class="nav-item">
                @foreach (LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                    @if (App::getLocale() != $localeCode)
                        <a class="nav-link btn btn-text-secondary rounded-pill btn-icon"
                            href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}"
                            data-bs-toggle="tooltip" data-bs-original-title="{{ trans('layouts/navbar.language') }}">
                            <i class="ri-global-line ri-22px"></i>
                            <span class="visually-hidden">{{ $properties['native'] }}</span>
                        </a>
                        @break
                    @endif
                @endforeach
            </li>
            {{-- <li class="nav-item dropdown-language dropdown">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow"
                    href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="ri-translate-2 ri-22px"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach (LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                        <li>
                            <a class="dropdown-item @if (App::getLocale() == $localeCode) active @endif"
                                href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
                                <span class="align-middle">{{ $properties['native'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li> --}}
            <!--/ Language -->

            <!-- Style Switcher -->
            <li class="nav-item dropdown-style-switcher dropdown me-1 me-xl-0">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow"
                    href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="ri-22px"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                            <span class="align-middle"><i class="ri-sun-line ri-22px me-3"></i>{{ trans('landing.navbar.light') }}</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                            <span class="align-middle"><i class="ri-moon-clear-line ri-22px me-3"></i>{{ trans('landing.navbar.dark') }}</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                            <span class="align-middle"><i class="ri-computer-line ri-22px me-3"></i>{{ trans('landing.navbar.system') }}</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- / Style Switcher-->

            <!-- Quick links  -->
            <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown me-1 me-xl-0">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow"
                    href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    aria-expanded="false">
                    <i class="ri-star-smile-line ri-22px"></i>
                </a>
            </li>
            <!-- Quick links -->

            <!-- Notification -->
            <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-4 me-xl-1">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow"
                    href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    aria-expanded="false">
                    <i class="ri-notification-2-line ri-22px"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end py-0">
                    <li class="dropdown-menu-header border-bottom py-50">
                        <div class="dropdown-header d-flex align-items-center py-2">
                            <h6 class="mb-0 me-auto">Notification</h6>
                            <div class="d-flex align-items-center">
                                <a href="javascript:void(0)"
                                    class="btn btn-text-secondary rounded-pill btn-icon dropdown-notifications-all"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Mark all as read"><i
                                        class="ri-mail-open-line text-heading ri-20px"></i></a>
                            </div>
                        </div>
                    </li>
                    <li class="border-top">
                        <div class="d-grid p-4">
                            <a class="btn btn-primary btn-sm d-flex" href="javascript:void(0);">
                                <small class="align-middle">View all notifications</small>
                            </a>
                        </div>
                    </li>
                </ul>
            </li>
            <!--/ Notification -->

            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{ asset('assets/img/avatars/default.jpg') }}" alt="avatar" class="rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-2">
                                    <div class="avatar avatar-online">
                                        <img src="{{ asset('assets/img/avatars/default.jpg') }}" alt="avatar" class="rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-medium d-block small">{{ Auth::user()->name }}</span>
                                    <small class="text-muted">{{ Auth::user()->username }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('parent.account.personal.edit') }}">
                            <i class="ri-settings-4-line ri-22px me-3"></i><span class="align-middle">{{ trans('layouts/navbar.settings') }}</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('teacher.billing.index') }}">
                            <span class="d-flex align-items-center align-middle">
                                <i class="flex-shrink-0 ri-file-text-line ri-22px me-3"></i>
                                <span class="flex-grow-1 align-middle">{{ trans('layouts/navbar.billing') }}</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('student.faqs.index') }}">
                            <i class="ri-question-line ri-22px me-3"></i><span class="align-middle">{{ trans('layouts/navbar.faq') }}</span>
                        </a>
                    </li>
                    <li>
                        <div class="d-grid px-4 pt-2 pb-1">
                            <a class="btn btn-sm btn-danger d-flex" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <small class="align-middle">{{ trans('layouts/navbar.logout') }}</small>
                                <i class="ri-logout-box-r-line ms-2 ri-16px"></i>
                            </a>
                            <form id="logout-form" action="{{ route('logout', auth()->guard()->name) }}" method="POST"
                                style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>

    <!-- Search Small Screens -->
    <div class="navbar-search-wrapper search-input-wrapper d-none">
        <input type="text" class="form-control search-input container-xxl border-0" placeholder="Search..."
            aria-label="Search..." />
        <i class="ri-close-fill search-toggler cursor-pointer"></i>
    </div>
</nav>
