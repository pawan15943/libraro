@php
$current_route = Route::currentRouteName();
$user = getAuthenticatedUser();

@endphp

<div class="sidebar scroll">
    <h4><b>Libraro</b> <i class="fa fa-close d-block d-md-none" id="sidebar_mob"></i></h4>

    <ul class="list-unstyled ps-0 mt-4">
        @foreach($menus as $menu)
     
            @php
            $show = ($menu->name == 'Dashboard' || getLibrary()->is_paid != 0) ? 1 : 0;
               
               
            @endphp
            
            @if(is_null($menu->parent_id) && $show==1  && ($menu->guard === null || $menu->guard == 'library'))
               
                 {{-- @can('has-permission', [$menu->has_permissions]) --}}
                 @if($user)
                 
                    <li class="mb-1 {{ $current_route == $menu->url ? 'active' : '' }}">
                        <a class="btn btn-toggle d-inline-flex align-items-center rounded border-0 {{ $menu->children->count() ? '' : 'flex-start' }}"
                           href="{{ $menu->url ? route($menu->url) : '#' }}"
                           data-bs-toggle="{{ $menu->children->count() ? 'collapse' : '' }}"
                           data-bs-target="#menu_{{ $menu->id }}"
                           aria-expanded="false">
                            <i class="{{ $menu->icon }} me-2"></i> {{ $menu->name }}
                            @if($menu->children->count())
                                <i class="fa-solid fa-angle-right ms-auto"></i>
                            @endif
                        </a>

                        @if($menu->children->count())
                        
                            <div class="collapse" id="menu_{{ $menu->id }}">
                                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                                             
                                    @foreach($menu->children as $submenu)
                             
                                        @if($submenu->guard === null || $submenu->guard == 'library')
                                       
                                            @if($user && (($checkSub && $ispaid && $isProfile && $iscomp) || $is_renew_comp))
                                             
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
