@php
$current_route = Route::currentRouteName();
@endphp

<div class="sidebar scroll">
    <h4><b>Libraro</b> <i class="fa fa-close d-block d-md-none" id="sidebar_mob"></i></h4>

    <ul class="list-unstyled ps-0 mt-4">
        @foreach($menus as $menu)
        
        @php
        if($menu->guard=='web'){
            $show = 1;
        }elseif($menu->guard=='library'){
            $show = ($menu->name == 'Dashboard' || (optional(Auth::user())->is_paid ?? 0) != 0) ? 1 : 0;
        }else{
            $show = ($menu->name == 'Dashboard' || (optional(Auth::user())->status ?? 1) == 1) ? 1 : 0;
        }
        
        $hasActiveChild = $menu->children->pluck('url')->contains($current_route);
        @endphp
    
        {{-- Check if it's a parent menu and the guard matches --}}
        @if(is_null($menu->parent_id) && $show==1 && ($menu->guard === null || Auth::guard($menu->guard)->check()))
        {{-- Parent menu logic --}}
        @can('has-permission', [$menu->has_permissions])
        <li class="mb-1 {{ ($current_route == $menu->url || $hasActiveChild) ? 'active' : '' }}">
            <a class="btn btn-toggle d-flex align-items-center justify-content-between w-100 rounded border-0 {{ $menu->children->count() ? '' : 'flex-start' }}"
                href="{{ $menu->children->count() ? '#menu_' . $menu->id : (Route::has($menu->url) ? route($menu->url) : '#') }}"
                data-bs-toggle="{{ $menu->children->count() ? 'collapse' : '' }}"
                data-bs-target="#menu_{{ $menu->id }}"
                aria-expanded="{{ $hasActiveChild ? 'true' : 'false' }}">
                <span><i class="{{ $menu->icon }} me-2"></i> {{ $menu->name }}</span>
                @if($menu->children->count())
                <i class="fa-solid fa-angle-right ms-auto {{ $hasActiveChild ? 'rotate' : '' }}"></i>
                @endif
            </a>

            {{-- If menu has children (submenu) --}}
            @if($menu->children->count())
            <div class="collapse {{ $hasActiveChild ? 'show' : '' }}" id="menu_{{ $menu->id }}">
                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                    @foreach($menu->children as $submenu)
                    {{-- Check guard for submenu --}}
                    @if($submenu->guard === null || Auth::guard($submenu->guard)->check())

                    @if($submenu->guard !== 'library')
                    @can('has-permission', [$submenu->has_permissions])
                    <li>
                        <a href="{{ Route::has($submenu->url) ? route($submenu->url) : '#' }}"
                            class="{{ $current_route == $submenu->url ? 'active' : '' }}">
                            {{ $submenu->name }}
                        </a>
                    </li>
                    @endcan
                    
                    {{-- Show submenu for library guard only if conditions are met --}}
                    @elseif(Auth::guard('library')->check() && (($checkSub && $ispaid && $isProfile && $iscomp) || $is_renew_comp))
                    @can('has-permission', [$submenu->has_permissions])
                    <li>
                        <a href="{{ Route::has($submenu->url) ? route($submenu->url) : '#' }}"
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
        @endcan
        @endif
        @endforeach
    </ul>
</div>