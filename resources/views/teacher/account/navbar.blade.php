<div class="nav-align-top">
    <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-2 gap-lg-0">
        <li class="nav-item">
            <a class="nav-link {{ isActiveRoute('teacher.account.personal.edit') ? 'active' : '' }}" href="{{ route('teacher.account.personal.edit') }}"><i
                    class="ri-user-settings-line me-2"></i>{{ trans('account.personal') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ isActiveRoute('teacher.account.security.index') ? 'active' : '' }}" href="{{ route('teacher.account.security.index') }}"><i class="ri-lock-line me-2"></i>{{ trans('account.security') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ isActiveRoute('teacher.billing.index') ? 'active' : '' }}" href="{{ route('teacher.billing.index') }}"><i
                    class="ri-bank-card-line me-2"></i>{{ trans('account.billing') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ isActiveRoute('teacher.account.coupons.index') ? 'active' : '' }}" href="{{ route('teacher.account.coupons.index') }}"><i
                    class="ri-coupon-2-line me-2"></i>{{ trans('admin/coupons.coupons') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ isActiveRoute('teacher.account.fees.index') ? 'active' : '' }}" href="{{ route('teacher.account.fees.index') }}"><i
                    class="ri-bank-line me-2"></i>{{ trans('account.fees') }}</a>
        </li>
    </ul>
</div>
