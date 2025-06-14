<div class="nav-align-top">
    <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-2 gap-lg-0">
        <li class="nav-item">
            <a class="nav-link {{ isActiveRoute('student.account.personal.edit') ? 'active' : '' }}" href="{{ route('student.account.personal.edit') }}"><i
                    class="ri-user-settings-line me-2"></i>{{ trans('account.personal') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ isActiveRoute('student.account.security.index') ? 'active' : '' }}" href="{{ route('student.account.security.index') }}"><i class="ri-lock-line me-2"></i>{{ trans('account.security') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ isActiveRoute('student.account.coupons.index') ? 'active' : '' }}" href="{{ route('student.account.coupons.index') }}"><i
                    class="ri-coupon-2-line me-2"></i>{{ trans('admin/coupons.coupons') }}</a>
        </li>
    </ul>
</div>
