@php
$user = getAuthenticatedUser();
$isLibraryActiveAndSetup = (($checkSub ?? false) && ($ispaid ?? false) && ($isProfile ?? false) && ($iscomp ?? false) && ($isBranch ?? false)) || ($is_renew_comp ?? false);
@endphp

@if(!empty($primary_color))
<style>
    :root {
        --c1: {{ $primary_color ? $primary_color : '#151F38'}} ;
        --lp-brand: {{ $primary_color ? $primary_color : '#1d3c86'}};
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
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content lp-expiry">
            <div class="modal-body">
                <button type="button" class="btn-close align-self-right" data-bs-dismiss="modal" aria-label="Close"></button>
                <span class="lp-expiry-ic"><x-lb-icon name="clock-alert" /></span>
                @if(isset($librarydiffInDays) && $librarydiffInDays > 0)
                    <h5>Plan ends in {{ $librarydiffInDays }} day{{ $librarydiffInDays > 1 ? 's' : '' }}</h5>
                    <p>Your library plan will expire in {{ $librarydiffInDays }} day{{ $librarydiffInDays > 1 ? 's' : '' }}. Please consider renewing your plan!</p>
                @elseif(isset($librarydiffInDays) && $librarydiffInDays == 0)
                    <h5>Plan ends today</h5>
                    <p>Your library plan expires today. Please consider renewing your plan!</p>
                @elseif(isset($inExtension_lib) && $inExtension_lib && $diffInExtensionDays > 0)
                    <h5>Extension active</h5>
                    <p>Your plan has expired. Your {{$lib_extenday}}-day extension is active and will end in {{ $diffInExtensionDays }} day{{ $diffInExtensionDays > 1 ? 's' : '' }}.</p>
                @elseif(isset($inExtension_lib) && $inExtension_lib && $diffInExtensionDays == 0)
                    <h5>Extension ends today</h5>
                    <p>Your plan has expired. Your {{$lib_extenday}}-day extension ends today. Please renew your plan to avoid deactivation.</p>
                @else
                    <h5>Plan expired</h5>
                    <p>Your library plan and extension expired {{ abs($diffInExtensionDays) }} day{{ abs($diffInExtensionDays) > 1 ? 's' : '' }} ago. Please renew your plan to regain access.</p>
                @endif

                <a href="{{ route('subscriptions.choosePlan', ['action' => 'renew']) }}" class="lp-btn w-100">Renew your subscription</a>
            </div>
        </div>
    </div>
</div>
<!-- Expiry Warning Ends -->

@php
    $lpLib = getLibrary();
    $lpPlanName = optional(optional($lpLib)->subscription)->name;
    $lpPlanName = $lpPlanName ? preg_replace('/\s*plan$/i', '', $lpPlanName) . ' plan' : 'Your plan';
    $lpIsPlanExpired = (!$inExtension_lib && (!empty($is_expire) || empty($is_renew_comp) || empty($checkSub) || optional($lpLib)->status != 1 || optional($lpLib)->is_paid != 1));

    // Plan status pill (same conditions as before, new look)
    $lpPill = null;
    if ($librarydiffInDays > 0) {
        $lpPill = ['tone' => $librarydiffInDays <= 5 ? 'warn' : 'ok', 'tag' => 'Active', 'text' => $librarydiffInDays . ' day' . ($librarydiffInDays > 1 ? 's' : '') . ' left', 'fill' => max(6, min(100, round($librarydiffInDays / 30 * 100)))];
    } elseif (isset($librarydiffInDays) && $user && !$is_renew && $anyTranLib) {
        if ($librarydiffInDays == 0) {
            $lpPill = ['tone' => 'warn', 'tag' => 'Today', 'text' => 'Plan expires today', 'fill' => 4];
        } elseif ($inExtension_lib && $diffInExtensionDays > 0) {
            $lpPill = ['tone' => 'warn', 'tag' => 'Extension', 'text' => $diffInExtensionDays . ' day' . ($diffInExtensionDays > 1 ? 's' : '') . ' left', 'fill' => 4];
        } elseif ($inExtension_lib && $diffInExtensionDays == 0) {
            $lpPill = ['tone' => 'bad', 'tag' => 'Extension', 'text' => 'Ends today', 'fill' => 2];
        } else {
            $lpPill = ['tone' => 'bad', 'tag' => 'Expired', 'text' => abs($librarydiffInDays) . ' day' . (abs($librarydiffInDays) > 1 ? 's' : '') . ' ago', 'fill' => 0];
        }
    } elseif (isset($upcomingdiffInDays) && $user && $is_renew) {
        if ($upcomingdiffInDays > 0) {
            $lpPill = ['tone' => 'ok', 'tag' => 'Upcoming', 'text' => 'Starts in ' . $upcomingdiffInDays . ' day' . ($upcomingdiffInDays > 1 ? 's' : ''), 'fill' => 100];
        }
    }
    $lpPillHref = Auth::guard('library')->check() ? route('library.myplan') : 'javascript:;';
    $lpShowPlanActions = (($librarydiffInDays <= 5 || $inExtension_lib || $is_expire) && !$is_renew && $anyTranLib);
    $libHeaderType = (int) (optional($lpLib)->library_type ?? 1);
    $lpBranch = getBranch();
    $lpHasQr = !$lpIsPlanExpired && $lpBranch?->uuid && $lpBranch?->upi_id && $user && $user->can('has-permission', 'QR Seat Booking');
@endphp

<div class="header libraro-main-header lp-top">
    @if($isLibraryActiveAndSetup)
    <button type="button" class="lp-ibtn" id="sidebar" title="Show or hide menu" aria-label="Show or hide menu" aria-controls="lpSidebar">
        <x-lb-icon name="panel-left-close" class="lp-show-d lp-hide-m" />
        <x-lb-icon name="menu" class="lp-show-m" />
    </button>
    @endif

    @if(countBranch() > 0 && !$lpIsPlanExpired)
        @php
            $lpCur = collect($branches ?? [])->firstWhere('id', $user->current_branch);
            $lpCurName = $lpCur ? (trim($lpCur->display_name) !== '' ? $lpCur->display_name : $lpCur->name) : 'Select branch';
        @endphp
        <div class="lp-branch dropdown libraro-branch-switcher">
            <button type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Switch branch">
                <span class="lp-branch-ic"><x-lb-icon name="building" /></span>
                <span class="lp-branch-t"><small>Branch</small><b>{{ $lpCurName }}</b></span>
                <x-lb-icon name="chevrons-up-down" />
            </button>
            <form action="{{ route('branch.switch') }}" method="POST" id="branchSwitchForm" class="dropdown-menu lp-menu">
                @csrf
                @foreach($branches as $b)
                    @php $bName = trim($b->display_name) !== '' ? $b->display_name : $b->name; $on = $user->current_branch == $b->id; @endphp
                    <button type="{{ $on ? 'button' : 'submit' }}" name="branch_id" value="{{ $b->id }}" class="lp-mi {{ $on ? 'is-on' : '' }}">
                        <x-lb-icon name="building" />
                        <span style="flex:1;min-width:0">{{ $bName }}</span>
                        @if($on)<x-lb-icon name="check" />@endif
                    </button>
                @endforeach
                @if(Auth::guard('library')->check() && Route::has('branch.list'))
                <hr>
                <a href="{{ route('branch.list') }}" class="lp-mi" style="color:var(--lp-acc-ink)"><x-lb-icon name="circle-plus" />Manage branches</a>
                @endif
            </form>
        </div>
    @endif

    <div class="lp-top-spacer"></div>

    @if($lpPill)
        <a href="{{ $lpPillHref }}" class="lp-plan {{ $lpPill['tone'] !== 'ok' ? 'is-' . $lpPill['tone'] : '' }}" title="Your Libraro plan">
            <span class="lp-plan-ic"><x-lb-icon name="gem" /></span>
            <span class="lp-plan-t">
                <b>{{ $lpPlanName }}<span class="lp-tag lp-tag--{{ $lpPill['tone'] }}">{{ $lpPill['tag'] }}</span></b>
                <small>{{ $lpPill['text'] }}</small>
            </span>
            <span class="lp-plan-bar"><span style="width:{{ $lpPill['fill'] }}%"></span></span>
        </a>
        <a href="{{ $lpPillHref }}" class="lp-plan-mini {{ $lpPill['tone'] !== 'ok' ? 'is-' . $lpPill['tone'] : '' }}" title="{{ $lpPlanName }}"><x-lb-icon name="gem" />{{ $lpPill['text'] }}</a>
    @endif

    @if($lpShowPlanActions)
        <script>
            window.addEventListener('load', function () {
                if (!sessionStorage.getItem("planExpiryModalShown")) {
                    setTimeout(function () {
                        var modal = new bootstrap.Modal(document.getElementById('planExpiryModal'));
                        modal.show();
                        sessionStorage.setItem("planExpiryModalShown", "true");
                    }, 1000);
                }
            });
        </script>
        <div class="lp-plan-act">
            @if($libHeaderType < 3)
            <a href="{{ route('subscriptions.choosePlan', ['action' => 'upgrade']) }}" class="lp-btn lp-btn--soft lp-btn--sm btn-header-upgrade"><x-lb-icon name="arrow-up-right" />Upgrade</a>
            @endif
            <a href="{{ route('subscriptions.choosePlan', ['action' => 'renew']) }}" class="lp-btn lp-btn--sm btn-header-renew"><x-lb-icon name="refresh-cw" />Renew</a>
        </div>
    @endif

    @if($lpHasQr)
    <button type="button" class="lp-ibtn lp-hide-xs" data-bs-toggle="modal" data-bs-target="#branchQR" title="Seat booking QR code" aria-label="Seat booking QR code"><x-lb-icon name="qr-code" /></button>
    @endif

    <button type="button" class="lp-ibtn" id="lpThemeBtn" title="Switch light / dark" aria-label="Switch light or dark theme">
        <x-lb-icon name="moon" class="lp-theme-moon" />
        <x-lb-icon name="sun" class="lp-theme-sun" />
    </button>

    <!-- Notifications Dropdown -->
    @php
        $authUser = Auth::guard('library')->user() ?? Auth::guard('library_user')->user() ?? Auth::guard('web')->user() ?? Auth::user() ?? getAuthenticatedUser();
        $unreadNotifications = collect();
        $unreadCount = 0;
        $todayDate = now()->toDateString();
        $libId = function_exists('getLibraryId') ? getLibraryId() : null;

        if ($authUser || $libId) {
            $notifQuery = DB::table('notifications')
                ->where(function ($q) use ($authUser, $libId) {
                    if ($authUser) {
                        $q->where('notifiable_id', $authUser->id);
                    }
                    if ($libId) {
                        $q->orWhere('notifiable_id', $libId);
                    }
                })
                ->whereNull('read_at')
                ->whereDate('start_date', '<=', $todayDate)
                ->whereDate('end_date', '>=', $todayDate);

            $unreadNotifications = (clone $notifQuery)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $unreadCount = (clone $notifQuery)->count();
        }
    @endphp

    <div class="dropdown libraro-header-notification">
        <button type="button" class="lp-ibtn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications" aria-label="Notifications{{ $unreadCount > 0 ? ' (' . $unreadCount . ' unread)' : '' }}">
            <x-lb-icon name="bell" />
            @if($unreadCount > 0)<span class="lp-dot"></span>@endif
        </button>
        <div class="dropdown-menu dropdown-menu-end lp-menu lp-notif">
            <div class="lp-notif-h">
                <div>
                    <b>Notifications</b>
                    <small>{{ $unreadCount > 0 ? $unreadCount . ' unread alert' . ($unreadCount == 1 ? '' : 's') : 'All notifications read' }}</small>
                </div>
                @if($unreadCount > 0)
                    <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit">Mark all read</button>
                    </form>
                @endif
            </div>
            <div class="lp-notif-list">
                @forelse($unreadNotifications as $n)
                    @php $nData = json_decode($n->data ?? '{}', true); @endphp
                    <a href="{{ $nData['link'] ?? route('list.notification') }}" class="lp-notif-item">
                        <span><x-lb-icon name="megaphone" /></span>
                        <span style="min-width:0">
                            <b>{{ $nData['title'] ?? 'New Notification' }}</b>
                            <p>{{ $nData['description'] ?? '' }}</p>
                            <small>{{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}</small>
                        </span>
                    </a>
                @empty
                    <div class="lp-notif-empty"><b>All caught up!</b>You have no unread notifications right now.</div>
                @endforelse
            </div>
            <a href="{{ route('list.notification') }}" class="lp-notif-f">View all notifications</a>
        </div>
    </div>

    <!-- Profile Dropdown -->
    @php
        $displayInitials = strtoupper(substr($user->library_name ?? 'LB', 0, 2));
        if (Auth::guard('library_user')->user() && getLibrary()) {
            $displayInitials = strtoupper(substr(getLibrary()->library_name, 0, 2));
        }
        $uniqueLibNo = $user->library_no ?? (Auth::guard('library_user')->user() ? (optional(getLibrary())->library_no ?? '') : '');
        $hasCustomPic = Auth::guard('library_user')->user() && !empty($user->profile_picture);
        $profilePicUrl = $hasCustomPic ? asset('storage/app/public/' . $user->profile_picture) : asset('public/img/user.png');
    @endphp
    <div class="dropdown lp-profile libraro-header-profile">
        <button type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Account menu">
            <span class="lp-av">@if($hasCustomPic)<img src="{{ $profilePicUrl }}" alt="">@else{{ $displayInitials }}@endif</span>
            <span class="lp-profile-t"><b>{{ $user->library_name }}</b><small>{{ $user->name ? $user->name : 'Library Owner' }}</small></span>
            <x-lb-icon name="chevron-down" />
        </button>
        <div class="dropdown-menu dropdown-menu-end lp-menu">
            <div class="lp-menu-head">
                <span class="lp-av lp-av--lg">@if($hasCustomPic)<img src="{{ $profilePicUrl }}" alt="">@else{{ $displayInitials }}@endif</span>
                <span style="min-width:0">
                    <b>{{ $user->library_name }}</b>
                    <small>{{ $user->name ? $user->name : 'Administrator' }}@if($uniqueLibNo) · #{{ $uniqueLibNo }}@endif</small>
                </span>
            </div>
            <hr>
            @if(Auth::guard('library')->check())
                <a class="lp-mi" href="{{ route('library.myplan') }}"><x-lb-icon name="gem" />My plan</a>
                <a class="lp-mi" href="{{ route('profile') }}"><x-lb-icon name="user-round" />My profile</a>
            @endif
            @if(Auth::guard('library')->user() || Auth::guard('library_user')->user())
                <a class="lp-mi" href="{{ route('change.password') }}"><x-lb-icon name="key-round" />Change password</a>
            @endif
            <hr>
            <a class="lp-mi is-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><x-lb-icon name="log-out" />Log out</a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
        </div>
    </div>

    @if(isset($today_renew) && $today_renew==true)
    <script>
        window.onload = function() {
            setTimeout(function() {
                var el = document.getElementById('todayrenew');
                if (!el) return;
                var modal = new bootstrap.Modal(el);
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
