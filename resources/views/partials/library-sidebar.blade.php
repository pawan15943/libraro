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
@endphp
<style>
/* Optional: rotate submenu arrow when active */
.rotate-90 {
    transform: rotate(90deg);
    transition: transform 0.3s ease;
}
</style>

<div class="sidebar scroll">
    <h4><b>Libraro</b> <i class="fa fa-close d-block d-lg-none" id="sidebar_mob"></i></h4>

    @php
    $lib = getLibrary();
    $isPlanExpired = (!$inExtension_lib && (!empty($is_expire) || empty($is_renew_comp) || empty($checkSub) || optional($lib)->status != 1 || optional($lib)->is_paid != 1));
    $sidebarBranches = $branches ?? [];
    if (empty($sidebarBranches) && function_exists('getLibraryId')) {
        $sidebarBranches = \App\Models\Branch::where('library_id', getLibraryId())->get();
    }
    @endphp

    @if(countBranch() > 0 && !$isPlanExpired && !empty($sidebarBranches) && count($sidebarBranches) > 0)
    <div class="sidebar-branch-switcher-mobile d-block d-lg-none px-3 pt-2 pb-1">
        <form action="{{ route('branch.switch') }}" method="POST" id="sidebarBranchSwitchForm">
            @csrf
            <div class="sidebar-branch-box p-2 rounded" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15);">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; color: rgba(255,255,255,0.85);">
                        <i class="fa-solid fa-code-branch me-1 text-warning"></i> Current Branch
                    </span>
                    @if(count($sidebarBranches) > 1)
                        <span class="badge bg-warning text-dark" style="font-size: 0.62rem; padding: 2px 6px; font-weight: 600;">Switch</span>
                    @endif
                </div>
                <div class="position-relative">
                    <select name="branch_id" onchange="this.form.submit()" class="form-select sidebar-branch-select w-100" style="font-size: 0.82rem; font-weight: 600; color: #18225f; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding-left: 28px; height: 36px; cursor: pointer;" title="Switch Library Branch">
                        <option disabled>Select Branch</option>
                        @foreach($sidebarBranches as $b)
                            <option value="{{ $b->id }}" {{ $user->current_branch == $b->id ? 'selected' : '' }}>
                                {{ trim($b->display_name) !== '' ? $b->display_name : $b->name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="fa-solid fa-building position-absolute top-50 start-0 translate-middle-y ms-2 text-muted" style="font-size: 11px; pointer-events: none;"></i>
                </div>
            </div>
        </form>
    </div>
    @endif

    <ul class="list-unstyled ps-0 mt-3">

        @foreach($menus as $menu)
     
            @php

            $lib = getLibrary();
            $isPlanExpired = (!$inExtension_lib && (!empty($is_expire) || empty($is_renew_comp) || empty($checkSub) || optional($lib)->status != 1 || optional($lib)->is_paid != 1));
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
                
                
            @endphp
            
            @if(is_null($menu->parent_id) && $show==1  && (!$menu->url || Route::has($menu->url)) && ($menu->guard === null || $menu->guard == 'library') && !in_array($menu->has_permissions, $finalHiddenName))
               
                  @can('has-permission', [$menu->has_permissions]) 
                    @if($user)
                    {{-- <li class="mb-1 {{ $current_route == $menu->url ? 'active' : '' }}"> --}}
                    <li class="mb-1 ">
                        <a class="btn btn-toggle d-inline-flex align-items-center rounded border-0 {{ $menu->children->count() ? '' : 'flex-start' }} {{ $isMenuActive ? 'active' : '' }}"
                           href="{{ $menu->url ? route($menu->url) : '#' }}"
                           data-bs-toggle="{{ $menu->children->count() ? 'collapse' : '' }}"
                           data-bs-target="#menu_{{ $menu->id }}"
                           aria-expanded="{{ $isSubmenuActive ? 'true' : 'false' }}">
                            <i class="{{ $menu->icon }} me-2"></i> {{ $menu->name }}
                            @if($menu->children->count())
                                <i class="fa-solid fa-angle-right ms-auto"></i>
                            @endif
                        </a>

                        @if($menu->children->count())
                        
                            <div class="collapse {{ $isSubmenuActive ? 'show' : '' }}" id="menu_{{ $menu->id }}">
                                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                                             
                                    @foreach($menu->children as $submenu)
                                        @if($submenu->guard === null || $submenu->guard == 'library')

                                            @if($user && (($checkSub && $ispaid && $isProfile && $iscomp) || $is_renew_comp || $inExtension_lib) && !in_array($submenu->has_permissions, $finalHiddenName))

                                                    {{-- If submenu is toggle.feature, only show if status == 1 --}}

                                                    @if($submenu->url == 'toggle.feature' && !$iscomp)
                                                        @continue
                                                    @endif
                                                    {{-- Skip menus whose route isn't defined in this branch --}}
                                                    @if(!Route::has($submenu->url))
                                                        @continue
                                                    @endif
                                                @can('has-permission', [$submenu->has_permissions]) 
                                                <li>
                                                    <a href="{{ route($submenu->url) }}"
                                                       class="{{ $current_route == $submenu->url ? 'active' : '' }}">
                                                        {{ $submenu->name }}
                                                    </a>
                                                </li>
                                                @endcan
                                            @endif
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </li>
                    @endif
                 @endcan 
            @endif
            
        @endforeach
    </ul>
</div>
