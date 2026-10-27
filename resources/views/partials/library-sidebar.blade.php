@php
$current_route = Route::currentRouteName();
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
    <div class="lp-sb-inner">
        <div class="lp-sb-head">
            <span class="lp-sb-mark"><x-lb-icon name="library-big" /></span>
            <span class="lp-sb-name" title="{{ $lpLibName }}">{{ $lpLibName }}</span>
            <span class="lp-sb-role">{{ $lpRole }}</span>
            <button type="button" class="lp-sb-close" id="sidebar_mob" aria-label="Close menu"><x-lb-icon name="x" /></button>
        </div>

        <ul class="lp-nav">
            @foreach($menus as $menu)
                @php
                if ($isPlanExpired) {
                    $show = ($menu->name == 'Dashboard') ? 1 : 0;
                } else {
                    $show = ($menu->name == 'Dashboard' || $inExtension_lib || optional($lib)->status == 1 || (optional($lib)->is_paid == 1 && $menu->name == 'Library Master Console')) ? 1 : 0;
                }
                // Check if any submenu matches the current route
                $isSubmenuActive = $menu->children->contains(function ($submenu) use ($current_route) {
                    return $current_route == $submenu->url;
                });
                // Parent active if its own URL or a submenu is active
                $isMenuActive = $current_route == $menu->url || $isSubmenuActive;
                $hasKids = $menu->children->count() > 0;
                $lpIcon = $lpMenuIcon($menu);
                @endphp

                @if(is_null($menu->parent_id) && $show==1 && ($menu->guard === null || $menu->guard == 'library') && !in_array($menu->has_permissions, $finalHiddenName))
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
                            <div class="collapse {{ $isSubmenuActive ? 'show' : '' }}" id="menu_{{ $menu->id }}">
                                <ul class="lp-nav-kids">
                                    @foreach($menu->children as $submenu)
                                        @if($submenu->guard === null || $submenu->guard == 'library')
                                            @if($user && (($checkSub && $ispaid && $isProfile && $iscomp) || $is_renew_comp || $inExtension_lib) && !in_array($submenu->has_permissions, $finalHiddenName))
                                                {{-- If submenu is toggle.feature, only show if status == 1 --}}
                                                @if($submenu->url == 'toggle.feature' && !$iscomp)
                                                    @continue
                                                @endif
                                                @can('has-permission', [$submenu->has_permissions])
                                                <li>
                                                    <a href="{{ route($submenu->url) }}" class="{{ $current_route == $submenu->url ? 'is-on active' : '' }}" @if($current_route == $submenu->url) aria-current="page" @endif>{{ $submenu->name }}</a>
                                                </li>
                                                @endcan
                                            @endif
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <a class="lp-nav-item {{ $isMenuActive ? 'is-on' : '' }}" href="{{ $menu->url ? route($menu->url) : '#' }}" @if($isMenuActive) aria-current="page" @endif>
                                <span class="lp-nav-ic">@if($lpIcon)<x-lb-icon :name="$lpIcon" />@else<i class="{{ $menu->icon }}"></i>@endif</span>
                                <span class="lp-nav-t">{{ $menu->name }}</span>
                            </a>
                        @endif
                    </li>
                    @endif
                    @endcan
                @endif
            @endforeach
        </ul>

        <div class="lp-sb-foot">
            <div class="lp-help">
                <b><x-lb-icon name="headset" />Need help?</b>
                <p>Talk to the Libraro team, 9 am – 8 pm.</p>
                <a href="tel:+918114479678"><x-lb-icon name="phone" />+91 81144 79678</a>
            </div>
        </div>
    </div>
</aside>
