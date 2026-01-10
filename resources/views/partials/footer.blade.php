<div class="footer text-center d-none d-md-block">@ {{date('Y')}} All Rights Reserved by Libraro.</div>
@if(getLibrary()->is_paid == 1 && getLibrary()->status == 1)
<div class="mobile-app d-block d-md-none">
    <ul class="mobile-menu">
        <li class="{{ request()->routeIs('library.home') ? 'active' : '' }}">
            <a href="{{ route('library.home') }}">
                <i class="fa fa-home"></i>
                <span>Home</span>
            </a>
        </li>

        @can('has-permission', 'Search Learner')
        <li data-bs-toggle="tooltip"
            data-bs-placement="left"
            data-bs-title="Search Seat"
            class="{{ request()->routeIs('learner.search') ? 'active' : '' }}">
            <a href="{{ route('learner.search') }}">
                <i class="fa fa-search"></i>
                <span>Search</span>
            </a>
        </li>
        @endcan

        @can('has-permission', 'Book Seat')
        <li data-bs-toggle="tooltip"
            data-bs-placement="left"
            data-bs-title="Book Seat"
            class="{{ request()->routeIs('seat.book') ? 'active' : '' }}">
            <a href="javascript:;" class="noseat_popup">
                <i class="fa fa-chair"></i>
                <span>Book</span>
            </a>
        </li>
        @endcan

        <li class="{{ request()->routeIs('learners') ? 'active' : '' }}">
            <a href="{{ route('learners') }}">
                <i class="fa fa-user-check"></i>
                <span>Members</span>
            </a>
        </li>

        <li class="{{ request()->routeIs('seats') ? 'active' : '' }}">
            <a href="{{ route('seats') }}">
                <i class="fa fa-network-wired"></i>
                <span>Seatmap</span>
            </a>
        </li>
    </ul>
</div>
@endif