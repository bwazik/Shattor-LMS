<div class="nav-align-top">
    <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-2 gap-lg-0">
        <li class="nav-item">
            <a class="nav-link {{ isActiveRoute('parent.account.personal.edit') ? 'active' : '' }}" href="{{ route('parent.account.personal.edit') }}"><i
                    class="ri-user-settings-line me-2"></i>{{ trans('account.personal') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ isActiveRoute('parent.account.security.index') ? 'active' : '' }}" href="{{ route('parent.account.security.index') }}"><i class="ri-lock-line me-2"></i>{{ trans('account.security') }}</a>
        </li>
    </ul>
</div>
