
@php
$user = getAuthenticatedUser();
$isLibraryActiveAndSetup = (($checkSub ?? false) && ($ispaid ?? false) && ($isProfile ?? false) && ($iscomp ?? false) && ($isBranch ?? false)) || ($is_renew_comp ?? false);
@endphp

@if(!empty($primary_color))
<style>
    :root {
        --c1: {{ $primary_color ? $primary_color : '#151F38'}} ;
    }
</style>
@else
<style>
    :root {
        --c1: #151F38;
    }
</style>
@endif


<!-- Expiry Warning -->
<div class="modal" id="planExpiryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="btn-close align-self-right" data-bs-dismiss="modal" aria-label="Close"></button>
                <img src="{{ url('public/img/plan-expire.png') }}" alt="plan-expire" class="plan-expire img-fluid">
                @if(isset($librarydiffInDays) && $librarydiffInDays > 0)
                    <p class="text-success text-center">
                        Your library plan will expire in {{ $librarydiffInDays }} day{{ $librarydiffInDays > 1 ? 's' : '' }}. Please consider renewing your plan!
                    </p>
                @elseif(isset($librarydiffInDays) && $librarydiffInDays == 0)
                    <p class="text-warning text-center text-bold">
                        Your library plan expires today. Please consider renewing your plan!
                    </p>
                @elseif(isset($inExtension_lib) && $inExtension_lib && $diffInExtensionDays > 0)
                    <p class="text-warning text-center">
                        Your plan has expired. Your {{$lib_extenday}}-day extension is active and will end in {{ $diffInExtensionDays }} day{{ $diffInExtensionDays > 1 ? 's' : '' }}.
                    </p>
                @elseif(isset($inExtension_lib) && $inExtension_lib && $diffInExtensionDays == 0)
                    <p class="text-danger text-center">
                        Your plan has expired. Your {{$lib_extenday}}-day extension ends today. Please renew your plan to avoid deactivation.
                    </p>
                @else
                    <p class="text-danger text-center">
                        Your library plan and extension expired {{ abs($diffInExtensionDays) }} day{{ abs($diffInExtensionDays) > 1 ? 's' : '' }} ago. Please renew your plan to regain access.
                    </p>
                @endif

                <button type="button" class="btn btn-primary button m-auto w-100" data-bs-dismiss="modal" aria-label="Close">Renew your Subscription</button>
            </div>
        </div>
    </div>
</div>
<!-- Expiry Warning Ends -->

{{-- <div class="modal" tabindex="-1" id="todayrenew">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- <div class="modal-header">
                
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div> -->
            <div class="modal-body text-center p-4">
                <h5 class="modal-title text-center mb-3">🎉 Congratulations, Library Owner!</h5>
                <p>
                    Your upcoming plan is ready. Activate it now to keep enjoying all our services!</p>
                <button id="renewButton" type="button" class="btn btn-primary button w-50" onclick="renewPlan()">Activate Now</button>
            </div>
           
        </div>
    </div>
</div> --}}

<!-- All learner status update -->
 {{-- @if(!empty($showDailyPopup) && $showDailyPopup)
<div class="modal show d-block" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Daily Confirmation</h5>
            </div>

            <div class="modal-body">
                <p>Please confirm to continue using the library today.</p>
            </div>

            <div class="modal-footer">
                <form method="POST" action="{{ route('library.daily-popup.confirm') }}">
                    @csrf
                    <button class="btn btn-primary w-100">
                        I Understand, Continue
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endif --}}
<div class="header libraro-main-header">
    <div class="header-inner-flex">
        <!-- Left Zone: Sidebar toggle, Plan Expiry Status Pill, Plan Actions -->
        <div class="header-left-zone">
            @if($isLibraryActiveAndSetup)
            <button type="button" class="header-sidebar-toggle-btn" id="sidebar" title="Toggle Navigation">
                <i class="fa fa-bars"></i>
            </button>
            @endif

            @if ($librarydiffInDays > 0)
                <div class="libraro-plan-pill active" title="Active Subscription">
                    <span class="plan-pill-dot"></span>
                    <i class="fa-regular fa-clock me-1"></i>
                    <span>Plan active: <strong>{{ $librarydiffInDays }} day{{ $librarydiffInDays > 1 ? 's' : '' }} remaining</strong></span>
                </div>
            @elseif(isset($librarydiffInDays) && $user && !$is_renew && $anyTranLib)
                @if ($librarydiffInDays == 0)
                    <div class="libraro-plan-pill warning" title="Subscription expires today">
                        <span class="plan-pill-dot"></span>
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        <span>Plan <strong>expires today</strong></span>
                    </div>
                @elseif ($inExtension_lib && $diffInExtensionDays > 0)
                    <div class="libraro-plan-pill warning" title="Extension active">
                        <span class="plan-pill-dot"></span>
                        <i class="fa-regular fa-clock me-1"></i>
                        <span>Extension: <strong>{{ $diffInExtensionDays }} day{{ $diffInExtensionDays > 1 ? 's' : '' }} left</strong></span>
                    </div>
                @elseif ($inExtension_lib && $diffInExtensionDays == 0)
                    <div class="libraro-plan-pill danger" title="Extension ends today">
                        <span class="plan-pill-dot"></span>
                        <i class="fa-solid fa-circle-exclamation me-1"></i>
                        <span>Extension <strong>ends today</strong></span>
                    </div>
                @else
                    <div class="libraro-plan-pill danger" title="Plan expired">
                        <span class="plan-pill-dot"></span>
                        <i class="fa-solid fa-circle-exclamation me-1"></i>
                        <span>Plan expired <strong>{{ abs($librarydiffInDays) }} day{{ abs($librarydiffInDays) > 1 ? 's' : '' }} ago</strong></span>
                    </div>
                @endif
            @elseif(isset($upcomingdiffInDays) && $user && $is_renew )
                @if($upcomingdiffInDays > 0)
                <div class="libraro-plan-pill info" title="Upcoming Plan">
                    <span class="plan-pill-dot"></span>
                    <i class="fa-regular fa-calendar-check me-1"></i>
                    <span>Upcoming plan starts in <strong>{{ $upcomingdiffInDays }} day{{ $upcomingdiffInDays > 1 ? 's' : '' }}</strong></span>
                </div>
                @endif
            @endif

            @if(($librarydiffInDays <= 5 && !$is_renew && $is_expire))
                <script>
                    window.onload = function() {
                    if (!sessionStorage.getItem("planExpiryModalShown")) {
                    setTimeout(function() {
                    var modal = new bootstrap.Modal(document.getElementById('planExpiryModal'));
                    modal.show();
                    sessionStorage.setItem("planExpiryModalShown", "true");
                    }, 1000);
                    }
                    };
                </script>
                @php
                    $lib = getLibrary();
                    $libHeaderType = (int) (optional($lib)->library_type ?? 1);
                @endphp
                @if($libHeaderType < 3)
                <a href="{{ route('subscriptions.choosePlan', ['action' => 'upgrade']) }}" class="btn-header-upgrade">
                    <i class="fa-solid fa-arrow-up-right-dots"></i>
                    <span>Upgrade Plan</span>
                </a>
                @endif
                <a href="{{ route('subscriptions.choosePlan', ['action' => 'renew']) }}" class="btn-header-renew">
                    <i class="fa-solid fa-rotate"></i>
                    <span>Renew Plan</span>
                </a>
            @endif
        </div>

        <!-- Right Zone: Branch Switcher, Notifications, User Profile -->
        <div class="header-right-zone">
            @php
            $lib = getLibrary();
            $isPlanExpired = (!empty($is_expire) || empty($is_renew_comp) || empty($checkSub) || optional($lib)->status != 1 || optional($lib)->is_paid != 1);
            @endphp

            @if(countBranch() > 0 && !$isPlanExpired)
            <div class="libraro-branch-switcher">
                <form action="{{ route('branch.switch') }}" method="POST" id="branchSwitchForm">
                    @csrf
                    <div class="branch-select-wrapper">
                        <i class="fa-solid fa-code-branch branch-select-icon"></i>
                        <select name="branch_id" onchange="this.form.submit()" class="branch-select-input" title="Switch Library Branch">
                            <option disabled>Select Branch</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ $user->current_branch == $b->id ? 'selected' : '' }}>
                                    {{ trim($b->display_name) !== '' ? $b->display_name : $b->name }}
                                </option>
                            @endforeach
                        </select>
                        <i class="fa-solid fa-chevron-down branch-select-arrow"></i>
                    </div>
                </form>
            </div>
            @endif

            <!-- Notifications Dropdown -->
            @php
                $authUser = Auth::guard('library')->user() ?? Auth::guard('web')->user() ?? Auth::user() ?? getAuthenticatedUser();
                $unreadNotifications = collect();
                $unreadCount = 0;
                if ($authUser) {
                    $unreadNotifications = DB::table('notifications')
                        ->where('notifiable_id', $authUser->id)
                        ->whereNull('read_at')
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();
                    $unreadCount = DB::table('notifications')
                        ->where('notifiable_id', $authUser->id)
                        ->whereNull('read_at')
                        ->count();
                }
            @endphp

            <div class="libraro-header-notification">
                <div class="dropdown">
                    <a class="notif-bell-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                        <i class="fas fa-bell notif-bell-icon"></i>
                        @if($unreadCount > 0)
                            <span class="notif-count-badge"></span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notif-dropdown-menu">
                        <!-- Dropdown Header -->
                        <div class="notif-dropdown-header">
                            <div>
                                <h6 class="notif-header-title"><i class="fa-solid fa-bell me-1" style="font-size: 12px;"></i> Notifications</h6>
                                <div class="notif-header-subtitle">
                                    {{ $unreadCount > 0 ? $unreadCount . ' unread alert' . ($unreadCount == 1 ? '' : 's') : 'All notifications read' }}
                                </div>
                            </div>
                            @if($unreadCount > 0)
                                <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="notif-mark-read-btn">
                                        <i class="fa-solid fa-check-double me-1" style="font-size: 10px;"></i> Mark all read
                                    </button>
                                </form>
                            @endif
                        </div>

                        <!-- Scrollable Notifications List -->
                        <div class="notif-list-container">
                            @forelse($unreadNotifications as $n)
                                @php
                                    $nData = json_decode($n->data ?? '{}', true);
                                @endphp
                                <a href="{{ $nData['link'] ?? route('list.notification') }}" class="notif-item">
                                    <div class="notif-icon-box">
                                        <i class="fa-solid fa-envelope-open-text"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-center justify-content-between gap-1">
                                            <div class="notif-title">{{ $nData['title'] ?? 'New Notification' }}</div>
                                            <span class="notif-unread-dot" title="Unread"></span>
                                        </div>
                                        <div class="notif-description">{{ $nData['description'] ?? '' }}</div>
                                        <div class="notif-time">
                                            <i class="fa-regular fa-clock"></i>
                                            <span>{{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <!-- Empty State -->
                                <div class="notif-empty-box">
                                    <div class="notif-empty-icon">
                                        <i class="fa-solid fa-bell-slash"></i>
                                    </div>
                                    <div class="notif-empty-title">All Caught Up!</div>
                                    <div class="notif-empty-text">You have no unread notifications right now.</div>
                                </div>
                            @endforelse
                        </div>

                        <!-- Dropdown Footer -->
                        <div class="notif-dropdown-footer">
                            <a href="{{ route('list.notification') }}" class="notif-footer-link">
                                <span>View All Notifications</span>
                                <i class="fa-solid fa-arrow-right-long" style="font-size: 11px;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Dropdown Component -->
            @php
                $displayInitials = strtoupper(substr($user->library_name ?? 'LB', 0, 2));
                if (Auth::guard('library_user')->user() && getLibrary()) {
                    $displayInitials = strtoupper(substr(getLibrary()->library_name, 0, 2));
                }
                $uniqueLibNo = $user->library_no ?? (Auth::guard('library_user')->user() ? (optional(getLibrary())->library_no ?? '') : '');
                $hasCustomPic = Auth::guard('library_user')->user() && !empty($user->profile_picture);
                $profilePicUrl = $hasCustomPic ? asset('storage/app/public/' . $user->profile_picture) : asset('public/img/user.png');
            @endphp

            <div class="libraro-header-profile">
                <div class="dropdown">
                    <!-- Mobile view: compact icon dropdown trigger -->
                    <div class="d-block d-md-none">
                        <a class="profile-mobile-trigger" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="User Menu">
                            <span class="profile-avatar-mini">{{ $displayInitials }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end libraro-profile-menu">
                            <li class="profile-menu-header">
                                <div class="profile-header-avatar">
                                    @if($hasCustomPic)
                                        <img src="{{ $profilePicUrl }}" alt="Profile" class="profile-avatar-lg">
                                    @else
                                        <span class="profile-avatar-initials-lg">{{ $displayInitials }}</span>
                                    @endif
                                </div>
                                <div class="profile-header-info">
                                    <h6 class="profile-name">{{ $user->library_name }}</h6>
                                    <span class="profile-subname">{{ $user->name }}</span>
                                    @if($uniqueLibNo)
                                        <div class="profile-badge-id">
                                            <i class="fa-solid fa-hashtag" style="font-size: 9px;"></i>
                                            <span>{{ $uniqueLibNo }}</span>
                                        </div>
                                    @endif
                                </div>
                            </li>
                            @if(Auth::guard('library')->user() || Auth::guard('library_user')->user())
                            <li>
                                <a class="dropdown-item profile-menu-link" href="{{ route('change.password') }}">
                                    <div class="profile-icon-box"><i class="fa-solid fa-key"></i></div>
                                    <span>Change Password</span>
                                </a>
                            </li>
                            @endif
                            <li>
                                <a class="dropdown-item profile-menu-link logout-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-mob').submit();">
                                    <div class="profile-icon-box"><i class="fa-solid fa-right-from-bracket"></i></div>
                                    <span>Logout</span>
                                    <form id="logout-form-mob" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Desktop view: pill button with initials/avatar, details, and chevron -->
                    <div class="d-none d-md-flex align-items-center">
                        <a class="profile-pill-trigger" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Account Menu">
                            <span class="profile-avatar-circle">
                                @if($hasCustomPic)
                                    <img src="{{ $profilePicUrl }}" alt="Avatar" class="profile-avatar-img">
                                @else
                                    {{ $displayInitials }}
                                @endif
                            </span>
                            <div class="profile-pill-meta">
                                <span class="profile-pill-title">{{ $user->library_name }}</span>
                                <span class="profile-pill-role">{{ $user->name ? $user->name : 'Library Owner' }}</span>
                            </div>
                            <i class="fa-solid fa-chevron-down profile-pill-arrow"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end libraro-profile-menu">
                            <li class="profile-menu-header">
                                <div class="profile-header-avatar">
                                    @if($hasCustomPic)
                                        <img src="{{ $profilePicUrl }}" alt="Profile" class="profile-avatar-lg">
                                    @else
                                        <span class="profile-avatar-initials-lg">{{ $displayInitials }}</span>
                                    @endif
                                </div>
                                <div class="profile-header-info">
                                    <h6 class="profile-name">{{ $user->library_name }}</h6>
                                    <span class="profile-subname">{{ $user->name ? $user->name : 'Administrator' }}</span>
                                    @if($uniqueLibNo)
                                        <div class="profile-badge-id">
                                            <i class="fa-solid fa-hashtag" style="font-size: 9px;"></i>
                                            <span>{{ $uniqueLibNo }}</span>
                                        </div>
                                    @endif
                                </div>
                            </li>
                            @if(Auth::guard('library')->user() || Auth::guard('library_user')->user())
                            <li>
                                <a class="dropdown-item profile-menu-link" href="{{ route('change.password') }}">
                                    <div class="profile-icon-box"><i class="fa-solid fa-key"></i></div>
                                    <span>Change Password</span>
                                </a>
                            </li>
                            @endif
                            <li>
                                <a class="dropdown-item profile-menu-link logout-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <div class="profile-icon-box"><i class="fa-solid fa-right-from-bracket"></i></div>
                                    <span>Logout</span>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($today_renew) && $today_renew==true)
    <script>
        window.onload = function() {
            setTimeout(function() {
                var modal = new bootstrap.Modal(document.getElementById('todayrenew'));
                modal.show();
            }, 1000);
        };

        function renewPlan() {
            document.getElementById('renewButton').disabled = true;

            $.ajax({
                url: "{{ route('renew.configration') }}",
                type: 'GET',
                success: function(response) {
                    alert("Plan successfully renewed!");
                    var modal = bootstrap.Modal.getInstance(document.getElementById('todayrenew'));
                    modal.hide();
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error("Error renewing plan:", error);
                    alert("Failed to renew the plan. Please try again later.");
                },
                complete: function() {
                    document.getElementById('renewButton').disabled = false;
                }
            });
        }
    </script>
    @endif
</div>

