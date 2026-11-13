@php
    $axUser = Auth::guard('web')->user();
    $axName = $axUser->name ?? 'Administrator';
    $axInitials = collect(preg_split('/\s+/', trim($axName)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    $axRole = (method_exists($axUser, 'getRoleNames') ? $axUser->getRoleNames()->first() : null) ?? 'Administrator';
@endphp
<header class="ax-toolbar">
    <button type="button" class="ax-icon-btn" id="axNavToggle" title="Show / hide sidebar" aria-label="Show / hide sidebar" aria-controls="axSidebar" aria-expanded="true">
        <i class="fa-solid fa-table-columns" aria-hidden="true"></i>
    </button>

    <nav class="ax-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="crumb-parent max-sm:hidden!">Admin</a>
        @if($axPage['section'])
            <i class="fa-solid fa-chevron-right sep max-sm:hidden!" aria-hidden="true"></i>
            <span class="crumb-parent max-sm:hidden!">{{ $axPage['section'] }}</span>
        @endif
        @if($axPage['parent'])
            <i class="fa-solid fa-chevron-right sep max-sm:hidden!" aria-hidden="true"></i>
            <a href="{{ route($axPage['parent']['route']) }}" class="crumb-parent max-sm:hidden!">{{ $axPage['parent']['label'] }}</a>
        @endif
        <i class="fa-solid fa-chevron-right sep max-sm:hidden!" aria-hidden="true"></i>
        <span class="now" aria-current="page">{{ $axPage['title'] }}</span>
    </nav>

    <div class="ax-toolbar-spacer"></div>

    @if(Route::has('crm.reminders.index') && Gate::allows('has-permission', ['crm-view-leads']))
        <a href="{{ route('crm.reminders.index') }}" class="ax-icon-btn" title="Follow-ups due" aria-label="Follow-ups due">
            <i class="fa-solid fa-phone-volume" aria-hidden="true"></i>
            <b class="ax-badge" data-crm-due-badge hidden></b>
        </a>
    @endif

    @if(\App\Support\AdminNav::canOpen('admin.notifications.index'))
        <a href="{{ route('admin.notifications.index') }}" class="ax-icon-btn" title="Notifications" aria-label="Notifications">
            <i class="fa-regular fa-bell" aria-hidden="true"></i>
        </a>
    @endif

    @if(\App\Support\AdminNav::canOpen('library.create'))
        @can('has-permission', ['manage-libraries'])
            <a href="{{ route('library.create') }}" class="ax-icon-btn hidden sm:inline-grid" title="Add library" aria-label="Add library">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
            </a>
        @endcan
    @endif

    <div class="dropdown">
        <button class="ax-profile-btn" type="button" id="axProfileBtn" data-bs-toggle="dropdown" aria-haspopup="menu" aria-expanded="false" aria-label="Account menu">
            <span class="ui-avatar ui-avatar--sm" aria-hidden="true">{{ $axInitials ?: 'A' }}</span>
            <span class="ax-profile-name">{{ $axName }}</span>
            <i class="fa-solid fa-chevron-down ax-chevron text-[9px] text-label-4" aria-hidden="true"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end ax-menu" data-ui-portal role="menu" aria-labelledby="axProfileBtn">
            <div class="ax-menu-head flex items-center gap-3">
                <span class="ui-avatar" aria-hidden="true">{{ $axInitials ?: 'A' }}</span>
                <div class="min-w-0">
                    <div class="ax-user-name">{{ $axName }}</div>
                    <div class="ax-user-mail">{{ $axUser->email ?? '' }}</div>
                    <span class="ui-badge ui-badge--blue ui-badge--pill mt-1.5">{{ ucwords($axRole) }}</span>
                </div>
            </div>
            <a class="dropdown-item" role="menuitem" href="{{ route('home') }}"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> Dashboard</a>
            @if(\App\Support\AdminNav::canOpen('admin.users') && Gate::allows('has-permission', ['view-users']))
                <a class="dropdown-item" role="menuitem" href="{{ route('admin.users') }}"><i class="fa-solid fa-user-gear" aria-hidden="true"></i> Admin users</a>
            @endif
            <a class="dropdown-item" role="menuitem" href="{{ url('/') }}" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Open website</a>
            <div class="dropdown-divider" role="separator"></div>
            <div class="px-2.5 pt-1 pb-2" role="group" aria-labelledby="axAppearanceLbl">
                <div id="axAppearanceLbl" class="mb-1.5 text-xs font-medium text-label-3">Appearance</div>
                <div class="ui-seg ui-seg--block ui-seg--sm" data-ax-theme-picker>
                    <label><input type="radio" name="ax_theme" value="system"><span class="ui-seg__item"><i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i> Auto</span></label>
                    <label><input type="radio" name="ax_theme" value="light"><span class="ui-seg__item"><i class="fa-regular fa-sun" aria-hidden="true"></i> Light</span></label>
                    <label><input type="radio" name="ax_theme" value="dark"><span class="ui-seg__item"><i class="fa-regular fa-moon" aria-hidden="true"></i> Dark</span></label>
                </div>
            </div>
            <div class="dropdown-divider" role="separator"></div>
            <button type="submit" form="axLogout" class="dropdown-item is-danger" role="menuitem">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> Sign out
            </button>
        </div>
    </div>
</header>
