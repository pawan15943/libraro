
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



<div class="header">
    <div class="d-flex" style="gap:1rem">
        <div class="conatent flex" style="flex: 1;">
            @if($isLibraryActiveAndSetup)
            <i class="fa fa-bars mr-2" id="sidebar"></i>
            @endif
           
           

            
            @if ($librarydiffInDays > 0)
                    <small class="text-success ml-2">
                        <i class="fa fa-clock"></i> Enjoy your plan for the next {{ $librarydiffInDays }} day{{ $librarydiffInDays > 1 ? 's' : '' }}!
                    </small>
            @elseif(isset($librarydiffInDays) && $user && !$is_renew && $anyTranLib)
                @if ($librarydiffInDays == 0)
                    <small class="text-warning ml-2">
                        <i class="fa fa-clock"></i> Plan expires today
                    </small>
                @elseif ($inExtension_lib && $diffInExtensionDays > 0)
                    <small class="text-warning ml-2">
                        <i class="fa fa-clock"></i> Library expired. {{$lib_extenday}}-day extension ends in {{ $diffInExtensionDays }} day{{ $diffInExtensionDays > 1 ? 's' : '' }}.
                    </small>
                @elseif ($inExtension_lib && $diffInExtensionDays == 0)
                    <small class="text-danger ml-2">
                        <i class="fa fa-clock"></i> Library expired. {{$lib_extenday}}-day extension ends today.
                    </small>
                @else
                    <small class="text-danger ml-2">
                        {{-- <i class="fa fa-clock"></i> Plan expired {{ abs($diffInExtensionDays) }} day{{ abs($diffInExtensionDays) > 1 ? 's' : '' }} ago --}}
                        <i class="fa fa-clock"></i> Plan expired {{ abs($librarydiffInDays) }} day{{ abs($librarydiffInDays) > 1 ? 's' : '' }} ago

                    </small>
                @endif


               
            @elseif(isset($upcomingdiffInDays) && $user && $is_renew )
                @if($upcomingdiffInDays > 0)
                <small class="text-danger ml-2"> <i class="fa fa-clock"></i>
                    Upcoming Plan after {{$upcomingdiffInDays}} days
                </small>
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
                <a href="{{ route('subscriptions.choosePlan') }}" type="button" class="btn btn-primary button">Renew your plan</a>
            @endif
        </div>

        @if(countBranch() > 0)
            <form action="{{ route('branch.switch') }}" method="POST" >
                @csrf
                <select name="branch_id" onchange="this.form.submit()" class="form-control-sm form-select">
               
                    <option>Select Branch</option>
                   @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $user->current_branch == $b->id ? 'selected' : '' }}>
                            {{ trim($b->display_name) !== '' ? $b->display_name : $b->name }}
                        </option>
                    @endforeach

                </select>
            </form>
         
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

        <!-- Header Notification Component (Strictly Scoped under .libraro-header-notification) -->
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

        <div class="libraro-header-notification me-2">
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


        <div class="profile">
            <div class="dropdown">
                
                <div class="dropdown">
                    {{-- Mobile view: icon dropdown --}}
                   
                    <div class="d-block d-md-none">
                        <a class="dropdown-toggle uppercase" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="icon">{{ strtoupper(substr($user->library_name, 0, 2)) }}</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <img src="{{ url('public/img/user.png') }}" alt="profile" class="LibraryProfile">
                            </li>

                            @if(Auth::guard('library')->user() || Auth::guard('library_user')->user())
                            <li>
                                <a class="dropdown-item text-center" href="javascript:;">
                                    <small class="text-danger">Library Unique Id</small><br>
                                    {{ $user->library_no ?? '' }}
                                </a>
                            </li>
                    
                            <li>
                                <a class="dropdown-item" href="{{ route('change.password') }}">
                                    <i class="fas fa-key fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Change Library Password
                                </a>
                            </li>
                            @endif
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- Desktop view: icon and text dropdown --}}
                    <div class="d-none d-md-flex align-items-center gap-2">
                        <span class="icon">{{ strtoupper(substr($user->library_name, 0, 2)) }}
                           @if(Auth::guard('library_user')->user())
                                {{ strtoupper(substr(getLibrary()->library_name, 0, 2)) }}
                            @endif
                        </span>
                        <a class="dropdown-toggle uppercase" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ $user->library_name }} {{ $user->name }}
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                @if(Auth::guard('library_user')->user())
                                <img src="{{ !empty($user->profile_picture) ? asset('storage/app/public/' . $user->profile_picture) : asset('public/img/user.png') }}" alt="profile" class="LibraryProfile">

                                @else
                                    
                                <img src="{{ url('public/img/user.png') }}" alt="profile" class="LibraryProfile">
                                @endif
                            </li>

                            @if(Auth::guard('library')->user() || Auth::guard('library_user')->user())
                            <li>
                                <a class="dropdown-item text-center" href="javascript:;">
                                    <small class="text-danger">Library Unique Id</small><br>
                                    {{ $user->library_no ?? '' }}
                                     @if(Auth::guard('library_user')->user())
                                    {{getLibrary()->library_no}}
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('change.password') }}">
                                    <i class="fas fa-key fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Change Library Password
                                </a>
                            </li>
                            @endif
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
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
</div>
