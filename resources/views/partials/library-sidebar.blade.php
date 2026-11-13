@php
$current_route = Route::currentRouteName();
// report pages that live as tabs inside another report highlight that report's menu item
$current_route = [
    'pending.payment.report' => 'upcoming.payment.report',
    'expired.learner.report' => 'upcoming.payment.report',
][$current_route] ?? $current_route;
// Reports is one link to the hub: every report page lights it up
if (in_array($current_route, ['payment.collection.report', 'monthly.payment.collection.report', 'report.monthly',
    'partial.payment.collection.report', 'upcoming.payment.report', 'learner.report', 'reports.growth',
    'reports.occupancy', 'attendance.report', 'activity.report'], true)) {
    $current_route = 'reports.hub';
}
$user = getAuthenticatedUser();

// Hoisted out of the menu loop below: none of this depends on the loop's $menu,
// so it was previously re-querying the same two toggle_features lookups once per
// menu item instead of once per page render.
$toggleHidden = toggleHideField(); // Dynamic hidden toggle
$toglleCategory = "Menu";
$predefinedHidden = DB::table('toggle_features')->where('category', $toglleCategory)->pluck('id')->toArray();
$finalHidden = array_intersect($toggleHidden, $predefinedHidden);
$finalHiddenName = DB::table('toggle_features')->whereIn('id', $finalHidden)
            ->pluck('name')
            ->toArray();

$lib = getLibrary();
$isPlanExpired = (!$inExtension_lib && (!empty($is_expire) || empty($is_renew_comp) || empty($checkSub) || optional($lib)->status != 1 || optional($lib)->is_paid != 1));

// Lucide icon for each menu (falls back to the menu's own Font Awesome icon).
$lpMenuIcon = function ($menu) {
    $hay = strtolower(($menu->name ?? '') . ' ' . ($menu->icon ?? '') . ' ' . ($menu->url ?? ''));
    $map = [
        // new groups first: 'setup library' would otherwise match 'library'
        'setup' => 'sliders-horizontal', 'staff' => 'users-round', 'advanced' => 'settings', 'settings' => 'settings', 'subscription' => 'gem',
        'learners' => 'graduation-cap', 'chat' => 'message-circle',
        'dashboard' => 'layout-dashboard', 'attendance' => 'clipboard-check', 'report' => 'chart-column',
        'master' => 'sliders-horizontal', 'custom' => 'settings', 'account' => 'user-round', 'enquiry' => 'message-square-text',
        'trxn' => 'credit-card', 'transaction' => 'credit-card', 'referral' => 'gift', 'feedback' => 'message-square',
        'corner' => 'lightbulb', 'suggestion' => 'lightbulb', 'user' => 'users-round', 'library' => 'library-big',
        'building' => 'library-big',
    ];
    foreach ($map as $k => $icon) {
        if (str_contains($hay, $k)) return $icon;
    }
    return null;
};

$lpLibName = optional($lib)->library_name ?: ($user->library_name ?? 'Libraro');
$lpRole = Auth::guard('library_user')->check() ? 'Staff' : 'Owner';
@endphp

<aside class="sidebar scroll lp-sb" id="lpSidebar" aria-label="Main menu">
    <script>try { if (window.innerWidth > 991 && localStorage.getItem('lp-sbc') === '1') document.currentScript.parentNode.classList.add('w-120'); } catch (e) {}</script>
    <div class="lp-sb-inner">
        <div class="lp-sb-head">
            <span class="lp-sb-mark"><x-lb-icon name="library-big" /></span>
            <span class="lp-sb-name" title="{{ $lpLibName }}">{{ $lpLibName }}</span>
            <span class="lp-sb-role">{{ $lpRole }}</span>
            <button type="button" class="lp-sb-close" id="sidebar_mob" aria-label="Close menu"><x-lb-icon name="x" /></button>
        </div>

        {{-- first-time setup: only the setup steps (Choose plan → Branch → Shift); the menus come after --}}
        @php $lpSetup = librarySetup(); @endphp
        @if($lpSetup['in_setup'])
            @php
                $lpSetupItems = [
                    'plan' => ['Choose plan', 'gem', 'subscriptions.choosePlan'],
                    'branch' => ['Branch', 'building', 'branch.configure.create'],
                    'shift' => ['Shift', 'clock-3', 'library.configration'],
                ];
            @endphp
            <p class="lp-setup-h">Set up your library</p>
            <ul class="lp-nav lp-setup-nav">
                @foreach($lpSetupItems as $key => [$label, $icon, $routeName])
                    @php
                        $isDone = $lpSetup['done'][$key];
                        $isNow = $lpSetup['current'] === $key;
                        $n = array_search($key, array_keys($lpSetupItems)) + 1;
                    @endphp
                    <li>
                        @if($isNow)
                            <a class="lp-nav-item is-on" href="{{ route($routeName) }}" aria-current="step">
                                <span class="lp-nav-ic"><x-lb-icon :name="$icon" /></span><span class="lp-nav-t">{{ $label }}</span><em class="lp-setup-n">{{ $n }}</em>
                            </a>
                        @else
                            <span class="lp-nav-item lp-setup-item {{ $isDone ? 'is-done' : 'is-locked' }}" aria-disabled="true">
                                <span class="lp-nav-ic"><x-lb-icon :name="$isDone ? 'circle-check' : $icon" /></span><span class="lp-nav-t">{{ $label }}</span>
                                @unless($isDone)<x-lb-icon name="lock" class="lp-setup-lock" />@endunless
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
        <ul class="lp-nav" id="lpNav">
            @foreach($menus as $menu)
                @php
                if ($isPlanExpired) {
                    $show = ($menu->name == 'Dashboard') ? 1 : 0;
                } else {
                    $show = ($menu->name == 'Dashboard' || $inExtension_lib || optional($lib)->status == 1 || (optional($lib)->is_paid == 1 && $menu->children->contains('url', 'branch.list'))) ? 1 : 0;
                }
                // Check if any submenu matches the current route
                $isSubmenuActive = $menu->children->contains(function ($submenu) use ($current_route) {
                    return $current_route == $submenu->url;
                });
                // Parent active if its own URL or a submenu is active
                $isMenuActive = $current_route == $menu->url || $isSubmenuActive;
                $hasKids = $menu->children->count() > 0;
                $lpIcon = $lpMenuIcon($menu);
                // links of this group the viewer can actually open (same rules as the list below);
                // a group with none left is not shown at all
                $lpKids = $menu->children->filter(function ($submenu) use ($user, $checkSub, $ispaid, $isProfile, $iscomp, $is_renew_comp, $inExtension_lib, $finalHiddenName) {
                    return ($submenu->guard === null || $submenu->guard == 'library')
                        && $user && (($checkSub && $ispaid && $isProfile && $iscomp) || $is_renew_comp || $inExtension_lib)
                        && !in_array($submenu->has_permissions, $finalHiddenName)
                        && !($submenu->url == 'toggle.feature' && !$iscomp)
                        && Gate::allows('has-permission', [$submenu->has_permissions]);
                });
                @endphp

                @if(is_null($menu->parent_id) && $show==1 && (!$hasKids || $lpKids->isNotEmpty()) && ($menu->guard === null || $menu->guard == 'library') && !in_array($menu->has_permissions, $finalHiddenName))
                    @can('has-permission', [$menu->has_permissions])
                    @if($user)
                    <li>
                        @if($hasKids)
                            <button type="button" class="lp-nav-item {{ $isMenuActive && !$isSubmenuActive ? 'is-on' : '' }}"
                                data-bs-toggle="collapse" data-bs-target="#menu_{{ $menu->id }}"
                                aria-expanded="{{ $isSubmenuActive ? 'true' : 'false' }}" aria-controls="menu_{{ $menu->id }}">
                                <span class="lp-nav-ic">@if($lpIcon)<x-lb-icon :name="$lpIcon" />@else<i class="{{ $menu->icon }}"></i>@endif</span>
                                <span class="lp-nav-t">{{ $menu->name }}</span>
                                <x-lb-icon name="chevron-down" class="lp-chev" />
                            </button>
                            <div class="collapse {{ $isSubmenuActive ? 'show' : '' }}" id="menu_{{ $menu->id }}" data-bs-parent="#lpNav">
                                <ul class="lp-nav-kids">
                                    @foreach($lpKids as $submenu)
                                        <li>
                                            <a href="{{ route($submenu->url) }}" class="{{ $current_route == $submenu->url ? 'is-on active' : '' }}" @if($current_route == $submenu->url) aria-current="page" @endif>{{ $submenu->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <a class="lp-nav-item {{ $isMenuActive ? 'is-on' : '' }}" href="{{ $menu->url ? route($menu->url) : '#' }}" @if($isMenuActive) aria-current="page" @endif>
                                <span class="lp-nav-ic">@if($lpIcon)<x-lb-icon :name="$lpIcon" />@else<i class="{{ $menu->icon }}"></i>@endif</span>
                                <span class="lp-nav-t">{{ $menu->name }}</span>
                                @if ($menu->url === 'chat.index')<em data-lc-badge hidden title="Chats with new messages">0</em>@endif
                            </a>
                        @endif
                    </li>
                    @endif
                    @endcan
                @endif
            @endforeach
        </ul>
        @endif

        <div class="lp-sb-foot">
            <div class="lp-help">
                <b><x-lb-icon name="headset" />Need help?</b>
                <p>Talk to the Libraro team, 9 am – 8 pm.</p>
                <a href="tel:+918114479678"><x-lb-icon name="phone" />+91 81144 79678</a>
            </div>
        </div>
    </div>
</aside>
<script>
    // one menu group open at a time (backs up data-bs-parent, which needs every group to be a direct child)
    document.addEventListener('show.bs.collapse', function (e) {
        var nav = document.getElementById('lpNav');
        if (!nav || !nav.contains(e.target) || !window.bootstrap) return;
        nav.querySelectorAll('.collapse.show').forEach(function (el) {
            if (el !== e.target) bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).hide();
        });
    });
</script>
