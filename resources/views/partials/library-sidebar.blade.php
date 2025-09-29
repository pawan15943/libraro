@php
$current_route = Route::currentRouteName();
$user = getAuthenticatedUser();


@endphp
<style>
/* Optional: rotate submenu arrow when active */
.rotate-90 {
    transform: rotate(90deg);
    transition: transform 0.3s ease;
}
</style>

<div class="sidebar scroll">
    <h4><b>Libraro</b> <i class="fa fa-close d-block d-md-none" id="sidebar_mob"></i></h4>

    <ul class="list-unstyled ps-0 mt-4">
        @foreach($menus as $menu)
     
            @php
            
            $show = ($menu->name == 'Dashboard' || getLibrary()->status == 1 || (getLibrary()->is_paid == 1 && $menu->name == 'Library Master Console')) ? 1 : 0;
            $toggleHidden = toggleHideField(); // Dynamic hidden toggle
            $toglleCategory="Menu";
            $predefinedHidden =DB::table('toggle_features')->where('category', $toglleCategory)->pluck('id')->toArray();
            $finalHidden = array_intersect($toggleHidden, $predefinedHidden);
            $finalHiddenName = DB::table('toggle_features')->whereIn('id', $finalHidden)
                        ->pluck('name') 
                        ->toArray();
           // Check if any submenu matches the current route
            $isSubmenuActive = $menu->children->contains(function ($submenu) use ($current_route) {
                return $current_route == $submenu->url;
            });

            // Parent active if its own URL or a submenu is active
            $isMenuActive = $current_route == $menu->url || $isSubmenuActive;
                
                
            @endphp
            
            @if(is_null($menu->parent_id) && $show==1  && ($menu->guard === null || $menu->guard == 'library') && !in_array($menu->has_permissions, $finalHiddenName))
               
                 {{-- @can('has-permission', [$menu->has_permissions]) --}}
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

                                            @if($user && (($checkSub && $ispaid && $isProfile && $iscomp) || $is_renew_comp) && !in_array($submenu->has_permissions, $finalHiddenName))

                                                    {{-- If submenu is toggle.feature, only show if status == 1 --}}

                                                    @if($submenu->url == 'toggle.feature' && !$iscomp)
                                                        @continue
                                                    @endif

                                                <li>
                                                    <a href="{{ route($submenu->url) }}"
                                                       class="{{ $current_route == $submenu->url ? 'active' : '' }}">
                                                        {{ $submenu->name }}
                                                    </a>
                                                </li>
                                            @endif
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </li>
                @endif
                {{-- @endcan --}}
            @endif
            
        @endforeach
    </ul>
</div>
