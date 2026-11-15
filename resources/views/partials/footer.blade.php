<div class="footer text-center d-none d-lg-block">© {{date('Y')}} Libraro · All rights reserved · Copying or reverse engineering of this software is not allowed · Made in India for India's libraries</div>
@if(optional(getLibrary())->is_paid == 1 && optional(getLibrary())->status == 1)
<div class="mobile-app d-block d-lg-none">
    <ul class="mobile-menu">
        <li class="{{ request()->routeIs('library.home') ? 'active' : '' }}">
            <a href="{{ route('library.home') }}">
                <x-lb-icon name="house" />
                <span>Home</span>
            </a>
        </li>

        @can('has-permission', 'Search Learner')
        <li data-bs-toggle="tooltip"
            data-bs-placement="left"
            data-bs-title="Search Seat"
            class="{{ request()->routeIs('learner.search') ? 'active' : '' }}">
            <a href="{{ route('learner.search') }}">
                <x-lb-icon name="search" />
                <span>Search</span>
            </a>
        </li>
        @endcan

        @can('has-permission', 'Book Seat')
        <li data-bs-toggle="tooltip"
            data-bs-placement="left"
            data-bs-title="Book Seat"
            class="lp-dock-main {{ request()->routeIs('seat.book') ? 'active' : '' }}">
            <a href="javascript:;" class="noseat_popup">
                <x-lb-icon name="armchair" />
                <span>Book</span>
            </a>
        </li>
        @endcan

        <li class="{{ request()->routeIs('learners') ? 'active' : '' }}">
            <a href="{{ route('learners') }}">
                <x-lb-icon name="users-round" />
                <span>Members</span>
            </a>
        </li>

        <li class="{{ request()->routeIs('seats') ? 'active' : '' }}">
            <a href="{{ route('seats') }}">
                <x-lb-icon name="layout-grid" />
                <span>Seatmap</span>
            </a>
        </li>
    </ul>
</div>
@endif
@include('partials.ai-chat-widget')