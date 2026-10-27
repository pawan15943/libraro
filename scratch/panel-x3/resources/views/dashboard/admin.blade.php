@extends('layouts.library')

@section('title', 'Admin Dashboard')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-dash.css') }}?v={{ @filemtime(public_path('css/lb-dash.css')) }}">
@endpush

@section('content')
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js" defer></script>

@php
use App\Helpers\HelperService;

$lpUser = auth()->user();
$lpCan = fn ($p) => $lpUser && $lpUser->can('has-permission', $p);

// ₹ with Indian digit grouping (1,23,456)
$lpInr = function ($v) {
    $n = (float) $v; $neg = $n < 0; $n = abs($n);
    $dec = fmod($n, 1) > 0 ? '.' . str_pad((string) round(fmod($n, 1) * 100), 2, '0', STR_PAD_LEFT) : '';
    $int = (string) (int) floor($n);
    if (strlen($int) > 3) {
        $last3 = substr($int, -3); $rest = substr($int, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $int = $rest . ',' . $last3;
    }
    return ($neg ? '−' : '') . '₹' . $int . $dec;
};
$lpInitials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
$lpTone = fn ($name) => 'c' . ((crc32((string) $name) % 5) + 1);
$lpOwnerName = trim((string) ($lpUser->name ?? '')) ?: (getLibrary()->library_name ?? 'there');
$lpFirstName = explode(' ', $lpOwnerName)[0];

$completion = getProfileCompletionPercentage();

// Seats right now (shared by the layout middleware; refreshed by the AJAX call below)
$lpTotal = (int) ($total_seats ?? 0);
$lpBooked = (int) ($booked_seats ?? 0);
$lpFree = (int) ($availble_seats ?? max(0, $lpTotal - $lpBooked));
$lpPct = $lpTotal > 0 ? (int) round($lpBooked / $lpTotal * 100) : 0;

// Free seats per shift (from the same availability data used by "Available Seats")
$lpShiftFree = [];
foreach (($available_seats ?? []) as $seat) {
    foreach ($seat['available_plan_types'] as $pt) {
        $pid = $pt['id'];
        if (!isset($lpShiftFree[$pid])) $lpShiftFree[$pid] = ['name' => $pt['name'], 'free' => 0, 'seat_id' => $seat['seat_id'], 'seat_no' => $seat['seat_no']];
        $lpShiftFree[$pid]['free']++;
    }
}
$lpShiftMeta = $lpShiftFree ? \App\Models\PlanType::whereIn('id', array_keys($lpShiftFree))->get(['id', 'start_time', 'end_time'])->keyBy('id') : collect();
$lpTime = function ($t) {
    if (!$t) return '';
    try { $c = \Carbon\Carbon::parse($t); return $c->format('i') === '00' ? $c->format('g a') : $c->format('g:i a'); } catch (\Throwable $e) { return $t; }
};
$lpExtendDays = (int) getExtendDays();
$lpToday = \Carbon\Carbon::today();
$lpBranchName = getCurrentBranchName();
$lpSeatMap = collect(generateSeatNumbers())->keyBy('main');
$lpSeatLabel = function ($no) use ($lpSeatMap) { $s = $lpSeatMap->get((int) $no); if (!$s) return $no; return (!empty($s['floor_name']) && !empty($s['floor'])) ? $s['floor'] . ' (' . $s['floor_name'] . ')' : $s['main']; };
@endphp

<!-- SUCCESS MODAL -->
<div class="modal fade" id="setupSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content lpd-success">
            <div class="modal-body">
                <span class="lpd-success-ic"><x-lb-icon name="party-popper" /></span>
                <h4>Congratulations!</h4>
                <p>Your library setup has been completed successfully.<br>You are now ready to manage your library shifts seamlessly.</p>
                <button type="button" class="lp-btn" data-bs-dismiss="modal">Get started<x-lb-icon name="arrow-right" /></button>
            </div>
        </div>
    </div>
</div>

<div class="library-dashboard-section lpd has-lp-head">

    {{-- ===== Page head ===== --}}
    <div class="lpd-head">
        <div>
            <div class="lpd-crumb">Overview @if($lpBranchName)<x-lb-icon name="chevron-right" /><span>{{ $lpBranchName }}</span>@endif</div>
            @if($festival)
                <h1 class="lpd-title"><span class="lpd-fest">Happy {{ $festival->festival_name }}!</span></h1>
            @else
                <h1 class="lpd-title" id="greeting-message" data-name="{{ $lpFirstName }}">Hello, {{ $lpFirstName }}</h1>
            @endif
        </div>
        <div class="lpd-head-r">
            <span class="lpd-date"><x-lb-icon name="calendar" />{{ now()->format('l, j M Y') }}</span>
            <a href="{{ route('library.how-to-use') }}" class="lp-btn lp-btn--ghost lp-hide-xs"><x-lb-icon name="book-open" />How Libraro works</a>
            @can('has-permission', 'Book Seat')
            <a href="javascript:;" class="lp-btn noseat_popup"><x-lb-icon name="plus" />New booking</a>
            @endcan
        </div>
    </div>

    {{-- ===== Profile completion ===== --}}
    @if ($completion < 70)
    <div class="lpd-setup alert-dismissible fade show" role="alert">
        <span class="lpd-setup-ring" style="--p: {{ (float) $completion }}"><b>{{ round($completion) }}%</b></span>
        <div class="lpd-setup-t">
            <b>Finish your branch profile ({{ $completion }}% done)</b>
            <span>Complete your profile to unlock all features and appear better on Find my library.</span>
        </div>
        @if(getCurrentBranch())
        <a href="{{ route('branch.edit', getCurrentBranch()) }}" class="lp-btn lp-btn--sm">Update profile<x-lb-icon name="arrow-right" /></a>
        @endif
        <button type="button" class="lpd-x" data-bs-dismiss="alert" aria-label="Close"><x-lb-icon name="x" /></button>
    </div>
    @endif

    <div id="realDashboardContent" class="real-dashboard-content dashboard learner">

    {{-- ===== Seats right now ===== --}}
    @php
        $lpSeatPerm = $lpCan('Total Seats') || $lpCan('Booked Seats') || $lpCan('Available Seats') || $lpCan('Expired Seats');
    @endphp
    @if($lpSeatPerm)
    <section class="lpd-live lp-card lp-card--flat lp-rise">
        <x-lb-icon name="armchair" class="lpd-live-bg" />
        <div class="lpd-live-main">
            <div class="lpd-ring" id="lpdRing" style="--p: {{ $lpPct }}">
                <div><b id="lpdPct">{{ $lpPct }}%</b><small>booked</small></div>
            </div>
            <div class="lpd-live-t">
                <span class="lpd-live-tag"><i></i>SEATS RIGHT NOW</span>
                @can('has-permission', 'Booked Seats')
                <div class="lpd-live-big"><span id="booked_seat" class="count">{{ $lpBooked }}</span> seats booked</div>
                @endcan
                <div class="lpd-live-sub">
                    @can('has-permission', 'Total Seats')of <b id="total_seat">{{ $lpTotal }}</b> seats · @endcan
                    @can('has-permission', 'Available Seats')<b id="available_seat">{{ $lpFree }}</b> free @endcan
                </div>
                <div class="lpd-live-hint"><x-lb-icon name="calendar-check" />As of today, {{ now()->format('d M Y') }}</div>
            </div>
        </div>
        <div class="lpd-live-side">
            <div class="lpd-live-chips">
                @can('has-permission', 'Available Seats')
                <a href="{{ route('seats') }}" class="lp-pill"><x-lb-icon name="circle-check" />Free<b class="js-free">{{ $lpFree }}</b></a>
                @endcan
                @can('has-permission', 'Expired Seats')
                <a href="{{ route('learners.list.view', ['type' => 'expired_seats']) }}" class="lp-pill lpd-pill-bad"><x-lb-icon name="hourglass" />Expired<b id="expired_seat">{{ (int) ($expired_seat ?? 0) }}</b></a>
                @endcan
                @can('has-permission', 'Booked Seats')
                <a href="{{ route('seats.history') }}" class="lp-pill"><x-lb-icon name="users-round" />Register</a>
                @endcan
            </div>
            <div class="lpd-live-acts">
                @if(isset($branch) && $branch?->uuid && $branch?->upi_id)
                <button type="button" class="lp-round lp-round--ghost" data-bs-toggle="modal" data-bs-target="#branchQR" title="Seat booking QR code" aria-label="Seat booking QR code"><x-lb-icon name="qr-code" /></button>
                @endif
                <a href="{{ route('seats') }}" class="lp-btn lp-btn--ok"><x-lb-icon name="layout-grid" />Open seat map</a>
            </div>
        </div>
    </section>
    @endif

    {{-- ===== Money strip ===== --}}
    @php
        $canBook_01 = $lpCan('Todays Financial Snapshot');
        $canBook_02 = $lpCan('Monthly Financial Overview');
    @endphp
    @if ($canBook_01 || $canBook_02)
    <section class="lpd-money lp-rise">
        <div class="lpd-money-row">
            @if($canBook_01)
            <a href="{{ route('library.transaction.view', ['type' => 'today_collection']) }}" class="lpd-money-col viewall">
                <span class="lpd-money-l"><x-lb-icon name="indian-rupee" />Collected today</span>
                <span class="lpd-money-v">{{ $lpInr($todayCollection) }}</span>
                <span class="lpd-money-s">Balance today {{ $lpInr($todayBalance) }}</span>
            </a>
            @endif
            @if($canBook_02)
            <a href="{{ route('library.transaction.view', ['type' => 'monthly_collection']) }}" class="lpd-money-col viewall">
                <span class="lpd-money-l">This month</span>
                <span class="lpd-money-v js-money" id="total_income">₹0</span>
                <span class="lpd-money-s">Collection in the selected month</span>
            </a>
            <a href="{{ route('library.transaction.view', ['type' => 'monthly_pending']) }}" class="lpd-money-col viewall">
                <span class="lpd-money-l lpd-money-due">Pending dues</span>
                <span class="lpd-money-v lpd-money-due js-money" id="total_pending">₹0</span>
                <span class="lpd-money-s">@if($pendingDueCount > 0){{ $pendingDueCount }} member{{ $pendingDueCount == 1 ? '' : 's' }} to collect @else Due amount this month @endif</span>
            </a>
            @elseif($canBook_01)
            <a href="{{ route('library.transaction.view', ['type' => 'today_pending']) }}" class="lpd-money-col viewall">
                <span class="lpd-money-l lpd-money-due">Due today</span>
                <span class="lpd-money-v lpd-money-due">{{ $lpInr($today_pending) }}</span>
                <span class="lpd-money-s">Pending amount today</span>
            </a>
            @endif
            <button type="button" class="lp-btn lp-btn--white lpd-money-btn" data-bs-toggle="collapse" data-bs-target="#lpdMoneyMore" aria-expanded="false" aria-controls="lpdMoneyMore">View summary<x-lb-icon name="chevron-down" /></button>
        </div>
        <div class="collapse" id="lpdMoneyMore">
            <div class="lpd-money-more">
                @if($canBook_01)
                <div>
                    <h3>Today</h3>
                    <div class="lpd-mgrid">
                        <a href="{{ route('library.transaction.view', ['type' => 'today_collection']) }}" class="viewall"><small>Collection</small><b>{{ $lpInr($todayCollection) }}</b></a>
                        <a href="{{ route('library.transaction.view', ['type' => 'today_other_collection']) }}" class="viewall"><small>Other income</small><b>{{ $lpInr($today_other_amt) }}</b></a>
                        <a href="{{ route('library.transaction.view', ['type' => 'today_expense']) }}" class="viewall"><small>Expense</small><b>{{ $lpInr($todayExpense) }}</b></a>
                        <a href="{{ route('library.transaction.view', ['type' => 'today_refund']) }}" class="viewall"><small>Refund</small><b>{{ $lpInr($today_refund) }}</b></a>
                        <a href="{{ route('library.transaction.view', ['type' => 'today_pending']) }}" class="viewall"><small>Due amount</small><b>{{ $lpInr($today_pending) }}</b></a>
                        <a href="{{ route('library.transaction.view', ['type' => 'today_balance']) }}" class="viewall"><small>Balance</small><b>{{ $lpInr($todayBalance) }}</b></a>
                    </div>
                </div>
                @endif
                @if($canBook_02)
                <div>
                    <h3>Selected month</h3>
                    <div class="lpd-mgrid">
                        <a href="{{ route('library.transaction.view', ['type' => 'monthly_collection']) }}" class="viewall"><small>Collection</small><b class="js-money" data-copy="total_income">₹0</b></a>
                        <a href="{{ route('library.transaction.view', ['type' => 'monthly_other_collection']) }}" class="viewall"><small>Other income</small><b class="js-money" id="other_total_income">₹0</b></a>
                        <a href="{{ route('library.transaction.view', ['type' => 'monthly_expense']) }}" class="viewall"><small>Expense</small><b class="js-money" id="total_expense">₹0</b></a>
                        <a href="{{ route('library.transaction.view', ['type' => 'monthly_refund']) }}" class="viewall"><small>Refund</small><b class="js-money" id="total_refund">₹0</b></a>
                        <a href="{{ route('library.transaction.view', ['type' => 'monthly_pending']) }}" class="viewall"><small>Due amount</small><b class="js-money" data-copy="total_pending">₹0</b></a>
                        <a href="{{ route('library.transaction.view', ['type' => 'monthly_balance']) }}" class="viewall"><small>Final balance</small><b class="js-money" id="total_balance">₹0</b></a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>
    @endif

    {{-- ===== Needs action ===== --}}
    @php
        $lpShowExpire = $lpCan('Seat About to Expire List');
        $lpShowExtend = $lpCan('Extend Seats list');
        $lpHasDue = getCurrentBranch() && $pendingDueCount > 0;
    @endphp
    @if($lpHasDue || ((getCurrentBranch() != 0 || getCurrentBranch() != null) && ($lpShowExpire || $lpShowExtend)))
    <div class="lp-sec"><h2>Needs action</h2><p>Collect, renew, remind</p></div>

    @if($lpHasDue)
    <section class="lp-card lpd-due dashboard-payment-due-section">
        <div class="lp-card-h">
            <h3>Payment due <span class="lp-count">{{ $pendingDueCount }}</span></h3>
            <div class="lpd-due-r">
                <a href="{{ route('learner.pending.payment.list', ['filter' => 'pending']) }}" class="lp-link">View all<x-lb-icon name="arrow-right" /></a>
                <button type="button" class="lp-round lp-round--ghost lpd-sc" data-sc="-1" aria-label="Scroll left"><x-lb-icon name="chevron-left" /></button>
                <button type="button" class="lp-round lp-round--ghost lpd-sc" data-sc="1" aria-label="Scroll right"><x-lb-icon name="chevron-right" /></button>
            </div>
        </div>
        <div class="lp-hscroll lpd-due-track payment-due-scroll">
            @foreach($pendingDueMembers as $member)
            @php
                $duePendingAmount = (float) ($member['payment']['pending_amount'] ?? 0);
                $dueDate = $member['payment']['due_date'] ?? null;
                $dueDays = $dueDate ? $lpToday->diffInDays(\Carbon\Carbon::parse($dueDate)->startOfDay(), false) : null;
            @endphp
            <article class="lpd-due-card payment-due-card">
                <div class="lpd-due-who">
                    <span class="lp-face {{ $lpTone($member['name'] ?? '') }}">
                        @if(!empty($member['profile_picture']))<img src="{{ $member['profile_picture'] }}" alt="" loading="lazy">@else{{ $lpInitials($member['name'] ?? '') }}@endif
                    </span>
                    <span class="lpd-due-n">
                        <a href="{{ route('learners.show', $member['id']) }}">{{ $member['name'] }}</a>
                        <small>Seat {{ $member['seat_no'] ?? 'GEN' }}</small>
                    </span>
                </div>
                <div class="lpd-due-amt">
                    <b>{{ $lpInr($duePendingAmount) }}</b>
                    <small>
                        @if($dueDays === null) Due
                        @elseif($dueDays < 0) Overdue {{ abs($dueDays) }}d
                        @elseif($dueDays == 0) Due today
                        @else Due {{ date('d M', strtotime($dueDate)) }}
                        @endif
                    </small>
                </div>
                <div class="lpd-due-acts">
                    @if(!empty($member['mobile']))
                    <a href="https://wa.me/91{{ $member['mobile'] }}" target="_blank" rel="noopener" class="lp-btn lp-btn--ok lp-btn--sm"><x-lb-brand name="whatsapp" />Remind</a>
                    @endif
                    @if(!empty($member['transaction_id']))
                    <a href="javascript:void(0)" data-id="{{ $member['id'] }}" class="lp-btn lp-btn--sm settlement-learner">Collect</a>
                    @endif
                </div>
            </article>
            @endforeach
        </div>
    </section>
    @endif

    @if(getCurrentBranch() !=0 || getCurrentBranch() !=null)
    <div class="lpd-two">
        @if($lpShowExpire)
        <section class="lp-card lpd-list">
            <div class="lp-card-h">
                <h3>Seats about to expire <span class="lp-count">{{ $renewSeats->count() }}</span></h3>
                <a href="{{ route('learners') }}" class="lp-link">View all</a>
            </div>
            <ul class="lpd-rows">
                @forelse($renewSeats as $value)
                @php $d = $lpToday->diffInDays(\Carbon\Carbon::parse($value->plan_end_date)->startOfDay(), false); @endphp
                <li>
                    <span class="lp-face {{ $lpTone($value->name ?? '') }}">{{ $lpInitials($value->name ?? '') }}</span>
                    <span class="lpd-row-t">
                        <b>{{ $value->name ?? '' }}</b>
                        <small>Seat {{ $value['seat_no'] ? $lpSeatLabel($value['seat_no']) : 'GEN' }} · {{ optional($value->planType)->name }}</small>
                    </span>
                    <span class="lp-tag {{ $d <= 2 ? 'lp-tag--bad' : 'lp-tag--warn' }}">{{ $d <= 0 ? 'Today' : 'In ' . $d . ' day' . ($d > 1 ? 's' : '') }}</span>
                    <span class="lpd-row-acts">
                        <a class="lp-round lp-round--wa" target="_blank" rel="noopener" title="Send reminder" aria-label="Send WhatsApp reminder"
                            href="https://wa.me/{{ $value->mobile }}?text={{ rawurlencode("Dear {$value->name}(Seat No-{$value->seat_no}),\n\nYour plan expired on " . changeFormate($value['plan_end_date']) . ".\n\nPlease renew it as soon as possible to continue uninterrupted access to your library seat.\n\nFor help, feel free to contact our support team.\n\n– Team " . getCurrentBranchName()) }}"><x-lb-brand name="whatsapp" /></a>
                        @can('has-permission', 'Renew Seat')
                        <a href="javascript:;" class="lp-round lp-round--acc renew_extend" title="Renew" aria-label="Renew seat" data-seat_no="{{$value->seat_no}}" data-seat_id="{{$value->seat_id}}" data-user="{{$value->learner_id}}" data-end_date="{{$value->plan_end_date}}" data-learner_detail="{{$value->id}}"><x-lb-icon name="refresh-cw" /></a>
                        @endcan
                    </span>
                </li>
                @empty
                <li class="lp-empty"><span><x-lb-icon name="circle-check" /></span><b>All good</b>No seats expire in the next 5 days.</li>
                @endforelse
            </ul>
        </section>
        @endif

        @if($lpShowExtend)
        <section class="lp-card lpd-list">
            <div class="lp-card-h">
                <h3>Seats in extension <span class="lp-count">{{ $extend_sets->count() }}</span></h3>
                <a href="{{ route('learners') }}" class="lp-link">View all</a>
            </div>
            <ul class="lpd-rows">
                @forelse($extend_sets as $seat)
                @php $past = $lpToday->diffInDays(\Carbon\Carbon::parse($seat->plan_end_date)->startOfDay()) ; @endphp
                <li>
                    <span class="lp-face {{ $lpTone($seat->name ?? '') }}">{{ $lpInitials($seat->name ?? '') }}</span>
                    <span class="lpd-row-t">
                        <b>{{ $seat->name ?? '' }}</b>
                        <small>Seat {{ $seat->seat_no ? $lpSeatLabel($seat->seat_no) : 'GEN' }} · {{ optional($seat->planType)->name ?? 'N/A' }}</small>
                    </span>
                    <span class="lp-tag lp-tag--warn">@if($lpExtendDays > 0)Day {{ min($past, $lpExtendDays) }} of {{ $lpExtendDays }}@else Extension @endif</span>
                    <span class="lpd-row-acts">
                        <a class="lp-round lp-round--wa" target="_blank" rel="noopener" title="Send reminder" aria-label="Send WhatsApp reminder"
                            href="https://wa.me/{{ $seat->mobile }}?text={{ urlencode("{$seat->name}(Seat No-{$seat->seat_no}),\n\nYour plan expired on".changeFormate($seat->plan_end_date). ".\n\nPlease renew it as soon as possible to continue uninterrupted access to your library seat.\nYou are currently in the extension period — after this, your seat may be allotted to another learner.\n\nFor help, feel free to contact our support team.\n\n– Team " . getCurrentBranchName()) }}"><x-lb-brand name="whatsapp" /></a>
                        @can('has-permission', 'Renew Seat')
                        <a href="javascript:;" class="lp-round lp-round--acc renew_extend" title="Renew" aria-label="Renew seat" data-seat_no="{{$seat->seat_no}}" data-seat_id="{{$seat->seat_id}}" data-user="{{$seat->learner_id}}" data-end_date="{{$seat->plan_end_date}}" data-learner_detail="{{$seat->id}}"><x-lb-icon name="refresh-cw" /></a>
                        @endcan
                    </span>
                </li>
                @empty
                <li class="lp-empty"><span><x-lb-icon name="circle-check" /></span><b>No seats in extension</b>Everyone has renewed on time.</li>
                @endforelse
            </ul>
        </section>
        @endif
    </div>
    @endif
    @endif

    {{-- ===== Highlights ===== --}}
    @if($festival)
    <div class="lp-sec"><h2>Highlights</h2><p>Festivals and greetings</p></div>
    <section class="lpd-fest-card">
        <span class="lpd-fest-tag">Today · {{ now()->format('D, j M') }}</span>
        <h3>Happy {{ $festival->festival_name }}!</h3>
        <p>Send a greeting to your members and make their day.</p>
        <x-lb-icon name="party-popper" class="lpd-fest-art" />
    </section>
    @endif

    {{-- ===== Online / QR bookings + activity ===== --}}
    @if(!in_array('23', toggleHideField()))
    <div class="lp-sec"><h2>Bookings & activity</h2><p>Requests from the QR code and the app</p></div>
    <div class="lpd-split dashboard-activity-qr-section">
        <section class="lp-card lpd-qr">
            <div class="lp-card-h"><h3>Online / QR bookings @if($qrbookings?->count())<span class="lp-count">{{ $qrbookings->count() }}</span>@endif</h3></div>
            <div class="lpd-qr-body" id="requests">
                @can('has-permission', 'QR Seat Booking')
                @if($qrbookings?->count() > 0)
                <div class="table-responsive">
                <table class="lpd-table" id="onlineRequest">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Type</th>
                            <th>Plan</th>
                            <th>Payment</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($qrbookings as $key => $value)
                        <tr>
                            <td>
                                <div class="lpd-who">
                                    <span class="lp-face {{ $lpTone($value->name) }}">{{ $lpInitials($value->name) }}</span>
                                    <span><b>{{ $value->name }}</b><small>{{ $value->mobile ? '+91-' . decryptData($value->mobile) : '-' }}</small></span>
                                </div>
                            </td>
                            <td>
                                <span class="lp-tag lp-tag--acc">
                                @if($value->type=='qr_seat_book') Seat book
                                @elseif($value->type=='learner_book') Seat book (app)
                                @elseif($value->type=='qr_renew') Renew seat
                                @elseif($value->type=='demo-bookings') Demo booking
                                @else -
                                @endif
                                </span>
                                <small class="lpd-sub">Seat {{ $value->seat_no ? $lpSeatLabel($value->seat_no) : 'GEN' }}</small>
                            </td>
                            <td><b>{{ $value->planType->name ?? 'N/A' }}</b><small class="lpd-sub">₹{{ $value->total_amount ?? 0 }} · {{ \Carbon\Carbon::parse($value->plan_start_date)->format('d M Y') }}</small></td>
                            <td>
                                @if($value->payment_screenshot)
                                    <a href="{{ asset($value->payment_screenshot) }}" target="_blank" class="lp-tag lp-tag--ok">Paid · view</a>
                                @else
                                    <span class="lp-tag lp-tag--bad">Unpaid</span>
                                @endif
                            </td>
                            <td>
                                <div class="lpd-acts">
                                    @if( $value->payment_screenshot && $value->payment_mode=='online' )
                                    <form action="{{route('booking.details.approve')}}" method="POST" enctype="multipart/form-data" class="approve-form">
                                        @csrf
                                        <input type="hidden" name="booking_id" value="{{ $value->id }}">
                                        <input type="hidden" name="direct_validate" value="1">
                                        <button type="submit" class="lp-round lp-round--acc noLoader" title="Direct approve" aria-label="Approve booking"><x-lb-icon name="check" /></button>
                                    </form>
                                    @endif
                                    <a href="{{ route('booking.details', $value->id) }}" class="lp-round lp-round--ghost" title="View details" aria-label="View details"><x-lb-icon name="eye" /></a>
                                    <a href="javascript:void(0)" class="lp-round lp-round--ghost lpd-del delete-booking" data-id="{{ $value->id }}" title="Delete booking" aria-label="Delete booking"><x-lb-icon name="trash" /></a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                @else
                <div class="lp-empty"><span><x-lb-icon name="qr-code" /></span><b>No online bookings yet</b>Bookings from your QR code and the app will show up here.</div>
                @endif
                @else
                <div class="lp-empty"><span><x-lb-icon name="lock" /></span>You don't have permission to view this.</div>
                @endcan
            </div>
        </section>

        @can('has-permission', 'Recent Activity')
        <section class="lp-card lpd-act">
            <div class="lp-card-h">
                <h3>Recent activity</h3>
                <a href="{{ route('activities.all') }}" class="lp-link viewall">View all</a>
            </div>
            <div class="lpd-act-list" id="recentActivityBox">
                @if($recent_activitys->count() > 0)
                    @foreach($recent_activitys as $value)
                        @php
                            $operationDetails = HelperService::getOperationDetails($value);
                            $meta = HelperService::activityMeta($value->operation);
                            $activitySeat = ($value->learner?->seat_no ? $lpSeatLabel($value->learner->seat_no) : null) ?: 'General';
                            $activityLearnerName = $value->learner?->name ?? 'Learner';
                            $color = $meta['color_code'] ?? '#1d3c86';
                            $iconMap = [
                                'renewSeat' => 'refresh-cw', 'renewDelete' => 'rotate-ccw', 'learnerUpgrade' => 'trending-up', 'changePlan' => 'sliders-horizontal',
                                'swapseat' => 'arrow-right-left', 'reactive' => 'user-check', 'closeSeat' => 'door-closed', 'deleteSeat' => 'trash',
                                'restoreSeat' => 'rotate-ccw', 'freezePlan' => 'snowflake', 'unfreezePlan' => 'sun', 'giftDays' => 'gift', 'edit' => 'pencil',
                            ];
                            $actionIcon = $iconMap[$value->operation] ?? 'zap';
                            $actionMessage = preg_replace('/^<strong>.*?<\/strong><br\s*\/?>/is', '', $operationDetails['message']);
                            if (empty(trim(strip_tags($actionMessage)))) {
                                $actionMessage = $operationDetails['message'];
                            }
                            $createdAt = \Carbon\Carbon::parse($value->created_at ?? $value->updated_at);
                        @endphp
                        <div class="lpd-act-item" style="--ac: {{ $color }}">
                            <span class="lpd-act-ic"><x-lb-icon :name="$actionIcon" /></span>
                            <div class="lpd-act-t">
                                <div class="lpd-act-top">
                                    <b>{{ $operationDetails['operation_type'] ?: $meta['label'] }}</b>
                                    <small title="{{ $createdAt->format('d M Y, h:i A') }}">{{ $createdAt->diffForHumans() }}</small>
                                </div>
                                <div class="lpd-act-who">{{ $activityLearnerName }} · Seat {{ $activitySeat }}</div>
                                <div class="lpd-act-msg">{!! $actionMessage !!}</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="lp-empty"><span><x-lb-icon name="activity" /></span><b>No activity yet</b>Renewals, swaps and other changes will appear here.</div>
                @endif
            </div>
        </section>
        @endcan
    </div>
    @endif

    {{-- ===== Overview (filters + counts + charts) ===== --}}
    @php
        $currentYear = date('Y');
        $currentMonth = date('m');
    @endphp
    <div class="lp-sec lpd-ov-head">
        <h2>Overview</h2><p>Bookings, shifts and income</p>
        @can('has-permission', 'Year and Month Filter')
        <div class="lp-sec-r" id="filter">
            <select id="datayaer" class="lp-sel" aria-label="Year">
                <option value="">Select year</option>
                @foreach($months as $year => $monthData)
                <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
            <select id="dataFilter" class="lp-sel" aria-label="Month">
                <option value="">Select month</option>
                @if(isset($months[$currentYear]))
                @foreach($months[$currentYear] as $monthNumber => $monthName)
                <option value="{{ $monthNumber }}" {{ $monthNumber == $currentMonth ? 'selected' : '' }}>{{ $monthName }}</option>
                @endforeach
                @endif
            </select>
        </div>
        @endcan
    </div>

    @php
        $canBook_1 = $lpCan('Till Today Bookings');
        $canBook_2 = $lpCan('This Month Bookings');
    @endphp
    @if($canBook_1 || $canBook_2)
    <div class="lpd-kpis">
        @if($canBook_1)
        <section class="lp-card lpd-kpi">
            <div class="lpd-kpi-h"><span class="lpd-kpi-ic"><x-lb-icon name="layers-2" /></span><b>Slot bookings till today</b></div>
            <div class="lpd-kpi-row">
                <a href="{{ route('learners.list.view', ['type' => 'total_booking']) }}" class="viewall"><b id="totalBookings">0</b><small>Total</small></a>
                <a href="{{ route('learners.list.view', ['type' => 'active_booking']) }}" class="viewall"><b id="active_booking" class="lpd-ok">0</b><small>Active</small></a>
                <a href="{{ route('learners.list.view', ['type' => 'expired_seats']) }}" class="viewall"><b id="expiredSeats" class="lpd-bad">0</b><small>Expired</small></a>
            </div>
        </section>
        @endif
        @if($canBook_2)
        <section class="lp-card lpd-kpi">
            <div class="lpd-kpi-h"><span class="lpd-kpi-ic lpd-kpi-ic--ok"><x-lb-icon name="calendar-days" /></span><b>Slot bookings this month</b></div>
            <div class="lpd-kpi-row">
                <a href="{{ route('learners.list.view', ['type' => 'thisbooking_slot']) }}" class="viewall"><b id="thismonth_total_book">0</b><small>Total</small></a>
                <a href="{{ route('learners.list.view', ['type' => 'booing_slot']) }}" class="viewall"><b id="month_total_active_book" class="lpd-ok">0</b><small>Booked</small></a>
                @can('has-permission', 'Expired Seats')
                <a href="{{ route('learners.list.view', ['type' => 'expire_booking_slot']) }}" class="viewall"><b id="month_all_expired" class="lpd-bad">0</b><small>Expired</small></a>
                @endcan
            </div>
        </section>
        @endif
    </div>
    @endif

    @can('has-permission', 'General Seat Counts')
    <section class="lp-card lpd-gen">
        <div class="lp-card-h">
            <h3>General seats <span class="lp-tag lp-tag--acc">Beta</span></h3>
            <small class="lpd-note"><x-lb-icon name="info" />Seats without a number, to earn extra from spare space.</small>
        </div>
        <div class="lpd-gen-row">
            <div><small>Total bookings</small><b id="gen-totalBookings">0</b></div>
            <div><small>Active</small><b id="gen-active-seat" class="lpd-ok">0</b></div>
            <div><small>About to expire</small><b id="gen-aboutToExpire" class="lpd-warn">0</b></div>
            <div><small>Expired</small><b id="gen-expired" class="lpd-bad">0</b></div>
        </div>
    </section>
    @endcan

    @if(!in_array('18', toggleHideField()))
    @php
        $lpOps = [
            ['Expired in 5 Days Count', 'Expire in 5 days', 'expiredInFive', 'expired_in_five', 'calendar-x', 'bad'],
            ['Extended Seats Count', 'Extended seats', 'extended_seats', 'extended_seat', 'clock-alert', 'warn'],
            ['Online Paid Count', 'Online paid', 'onlinePaid', 'online_paid', 'credit-card', 'acc'],
            ['Offline Paid Count', 'Offline paid', 'offlinePaid', 'offline_paid', 'banknote', 'ok'],
            ['Pay Later Count', 'Pay later', 'otherPaid', 'other_paid', 'timer', 'warn'],
            ['Swap Seats Count', 'Seat swaps', 'swap_seat', 'swap_seat', 'arrow-right-left', 'acc'],
            ['Upgrade Seats Count', 'Upgrades', 'learnerUpgrade', 'learnerUpgrade', 'trending-up', 'ok'],
            ['Reactive Seats Count', 'Reactivated', 'reactive', 'reactive_seat', 'user-check', 'ok'],
            ['Renew Seat Count', 'Renewals', 'renew_seat', 'renew_seat', 'refresh-cw', 'acc'],
            ['Close Seat Count', 'Closed seats', 'close_seat', 'close_seat', 'door-closed', 'mut'],
            ['Delete Seat Count', 'Deleted seats', 'delete_seat', 'delete_seat', 'trash', 'bad'],
            ['Change Plan Count', 'Plan changes', 'change_plan_seat', 'change_plan_seat', 'sliders-horizontal', 'acc'],
        ];
        $lpOpsVisible = array_values(array_filter($lpOps, fn ($o) => $lpCan($o[0])));
    @endphp
    @if(count($lpOpsVisible))
    <section class="lp-card lpd-ops">
        <div class="lp-card-h">
            <h3>Operations summary</h3>
            <small class="lpd-note"><x-lb-icon name="info" />Expired and extended counts include past months.</small>
        </div>
        <div class="lpd-ops-grid">
            @foreach($lpOpsVisible as $o)
            <a href="{{ route('learners.list.view', ['type' => $o[3]]) }}" class="lpd-op lpd-op--{{ $o[5] }} viewall">
                <span><x-lb-icon :name="$o[4]" /></span>
                <b id="{{ $o[2] }}">0</b>
                <small>{{ $o[1] }}</small>
            </a>
            @endforeach
        </div>
    </section>
    @endif
    @endif

    @can('has-permission', 'Plan wise count')
    <section class="lp-card lpd-pw" id="planWiseHeading">
        <div class="lp-card-h"><h3>Bookings by shift</h3></div>
        <div class="lpd-pw-grid planwisecount" style="display: none;"></div>
    </section>
    @endcan

    @can('has-permission', 'Library Analytics')
    <div class="lpd-charts">
        <section class="lp-card lpd-chart">
            <div class="lp-card-h">
                <h3>Revenue by shift</h3>
                <div id="revenueChartHeaderBadge" class="lpd-badges" style="display: none;"></div>
            </div>
            <div class="lpd-chart-box">
                <canvas id="revenueChart" style="display: none;"></canvas>
                <div class="lp-empty" style="display: none;" id="no-data2"><span><x-lb-icon name="chart-column" /></span>No data for this month</div>
            </div>
        </section>
        <section class="lp-card lpd-chart">
            <div class="lp-card-h">
                <h3>Bookings by shift</h3>
                <div id="bookingChartHeaderBadge" class="lpd-badges" style="display: none;"></div>
            </div>
            <div class="lpd-chart-box">
                <canvas id="bookingCountChart" style="display: none;"></canvas>
                <div class="lp-empty" style="display: none;" id="no-data3"><span><x-lb-icon name="chart-pie" /></span>No data for this month</div>
            </div>
        </section>
    </div>
    @endcan

    {{-- ===== Shifts + seats + plan ===== --}}
    @if(getCurrentBranch() !=0 || getCurrentBranch() !=null)
    <div class="lpd-two lpd-two--end">
        @can('has-permission', 'Avaialble Seats List')
        <section class="lp-card lpd-shifts">
            <div class="lp-card-h">
                <h3>Available shifts</h3>
                <a href="{{ route('seats') }}" class="lp-link">Seat map<x-lb-icon name="arrow-right" /></a>
            </div>
            @if(getAvailableHoursSum() >= 0 && count($lpShiftFree))
            <ul class="lpd-shift-list">
                @foreach($lpShiftFree as $pid => $sh)
                @php
                    $m = $lpShiftMeta->get($pid);
                    $booked = $lpTotal > 0 ? max(0, min(100, round(($lpTotal - $sh['free']) / $lpTotal * 100))) : 0;
                @endphp
                <li>
                    <span class="lpd-shift-ic"><x-lb-icon name="clock-3" /></span>
                    <div class="lpd-shift-t">
                        <div><b>{{ $sh['name'] }}</b>@if($m && $m->start_time)<small>{{ $lpTime($m->start_time) }} – {{ $lpTime($m->end_time) }}</small>@endif<span class="{{ $booked >= 80 ? 'lpd-bad' : 'lpd-ok' }}">{{ $sh['free'] }} free</span></div>
                        <span class="lpd-bar"><i style="width: {{ $booked }}%" class="{{ $booked >= 80 ? 'is-hot' : '' }}"></i></span>
                    </div>
                    <a href="javascript:;" class="lp-btn lp-btn--ghost lp-btn--xs first_popup" data-id="{{ $sh['seat_id'] }}" data-seat_no="{{ $sh['seat_no'] }}" data-plan_type_id="{{ $pid }}" @cannot('has-permission', 'Book Seat') disabled @endcannot>Book</a>
                </li>
                @endforeach
            </ul>
            <div class="lpd-seats-h">Free seats</div>
            <div class="lpd-seat-chips">
                @foreach($available_seats as $seat)
                @if(count($seat['available_plan_types']) > 0)
                @php
                    $abbr = collect($seat['available_plan_types'])->map(fn ($p) => [
                        'First Half' => 'FH', 'Second Half' => 'SH', 'Hourly Slot 1' => 'H1', 'Hourly Slot 2' => 'H2', 'Hourly Slot 3' => 'H3',
                        'Hourly Slot 4' => 'H4', 'Full Day' => 'FD', 'Full Night' => 'FN', 'All Day' => 'AD',
                    ][$p['name']] ?? $p['name'])->implode(', ');
                @endphp
                <a href="javascript:;" class="lpd-seat first_popup book" data-bs-toggle="modal" data-bs-target="#seatAllotmentModal" data-id="{{ $seat['seat_id'] }}" data-seat_no="{{ $seat['seat_no'] }}" @cannot('has-permission', 'Book Seat') disabled @endcannot title="{{ count($seat['available_plan_types']) > 3 ? 'All shifts free' : $abbr }}">
                    <b>{{ $lpSeatLabel($seat['seat_no']) }}</b>
                    <small>{{ count($seat['available_plan_types']) > 3 ? 'Free' : $abbr }}</small>
                </a>
                @endif
                @endforeach
            </div>
            @else
            <div class="lp-empty">
                <span><x-lb-icon name="party-popper" /></span>
                <b>All seats are booked</b>
                Congratulations! All premium seats are fully booked. You can now only <a href="javascript:;" class="noseat_popup">book general seats</a>.
            </div>
            @endif
        </section>
        @endcan

        @can('has-permission', 'Show Plan Info')
        @php
            $lpDaysLeft = (int) ($librarydiffInDays ?? 0);
            $lpPlanPct = max(0, min(100, round($lpDaysLeft / max(1, (isset($check->month) && $check->month == 12) ? 365 : 30) * 100)));
            $lpUseUpgrade = isset($librarydiffInDays) && $librarydiffInDays <= 5 && !$is_renew && $isProfile;
        @endphp
        <section class="lp-card lpd-plan">
            <div class="lpd-plan-h">
                <span class="lpd-plan-ring" style="--p: {{ $lpPlanPct }}"><span><b>{{ max(0, $lpDaysLeft) }}</b><small>days</small></span></span>
                <div>
                    <span class="lpd-plan-k">YOUR PLAN</span>
                    <h3>{{ $plan?->name ?? 'Basic Plan' }}@if(isset($check) && $check) · {{ (isset($check->month) && $check->month == 12) ? 'Yearly' : 'Monthly' }}@endif</h3>
                    <small>@if(isset($check) && $check)Ends {{ \Carbon\Carbon::parse($check->end_date)->format('j M Y') }}@else Free / Trial @endif</small>
                </div>
                <span class="lp-tag {{ $lpUseUpgrade ? 'lp-tag--warn' : 'lp-tag--ok' }} lpd-plan-st">{{ $lpUseUpgrade ? 'Renew soon' : 'Active' }}</span>
            </div>
            <div class="lpd-plan-rows">
                <a href="{{ route('seats') }}"><span>Total seats</span><b>{{ $total_seats ?? 0 }}</b></a>
                <a href="{{ route('library.myplan') }}"><span>Plan features</span><b>{{ $features_count ?? 0 }}</b></a>
                <a href="{{ route('library.transaction') }}"><span>Plan price</span><b>@if(isset($check) && $check)₹{{ (int)$check->amount == $check->amount ? number_format($check->amount) : number_format($check->amount, 2) }} / {{ (isset($check->month) && $check->month == 12) ? 'yr' : 'mo' }}@else Free / Trial @endif</b></a>
                @if(isset($branch) && $branch?->uuid && $branch?->upi_id)
                <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#branchQR"><span>Seat booking QR</span><b class="lpd-acc">Download</b></a>
                @else
                <a href="{{ route('library.settings') }}"><span>Seat booking QR</span><b class="lpd-acc">Set up</b></a>
                @endif
            </div>
            <div class="lpd-plan-acts">
                <a href="{{ $lpUseUpgrade ? route('subscriptions.choosePlan') : route('library.myplan') }}" class="lp-btn">{{ $lpUseUpgrade ? 'Renew plan' : 'Manage plan' }}</a>
                <a href="{{ route('subscriptions.choosePlan', ['action' => 'upgrade']) }}" class="lp-btn lp-btn--ghost">Upgrade</a>
            </div>
        </section>
        @endcan
    </div>
    @endif

    </div>
</div>
<!-- End library-dashboard-section -->

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var yearSel = document.getElementById('datayaer');
        var monthSel = document.getElementById('dataFilter');
        if (!yearSel || !monthSel) return;
        yearSel.addEventListener('change', function () {
            const selectedYear = this.value;
            const monthsData = @json($months);
            monthSel.innerHTML = '<option value="">Select month</option>';
            if (selectedYear && monthsData[selectedYear]) {
                Object.entries(monthsData[selectedYear]).forEach(([monthNumber, monthName]) => {
                    const option = document.createElement('option');
                    option.value = monthNumber;
                    option.textContent = monthName;
                    monthSel.appendChild(option);
                });
                if (selectedYear == @json($currentYear)) {
                    monthSel.value = @json($currentMonth);
                }
                monthSel.disabled = false;
            } else {
                monthSel.disabled = true;
            }
        });
        monthSel.disabled = !yearSel.value;
    });
</script>

<script>
    $(document).ready(function() {
        var inr = function (v) { var n = Number(v) || 0; return (n < 0 ? '−' : '') + '₹' + Math.abs(n).toLocaleString('en-IN', { maximumFractionDigits: 2 }); };
        var moneyIds = ['total_income', 'other_total_income', 'total_expense', 'total_refund', 'total_pending', 'total_balance'];

        // Horizontal scroll buttons for "Payment due"
        $(document).on('click', '.lpd-sc', function () {
            var track = $(this).closest('.lpd-due').find('.lpd-due-track')[0];
            if (track) track.scrollBy({ left: Number($(this).data('sc')) * Math.max(260, track.clientWidth * 0.8), behavior: 'smooth' });
        });

        // Fetch initial data based on the default filter (month)
        var initialYear = $('#datayaer').val();
        var initialMonth = $('#dataFilter').val();
        $('.js-money, #totalBookings, #active_booking, #expiredSeats, #thismonth_total_book, #month_total_active_book, #month_all_expired, .lpd-gen b, .lpd-op b').addClass('lp-skel');
        fetchLibraryData(initialYear, initialMonth, null);
        updateAllViewLinks(initialYear, initialMonth, null);

        $('#datayaer').on('change', function() {
            var selectedYear = $(this).val();
            var selectedMonth = $('#dataFilter').val();
            fetchLibraryData(selectedYear, selectedMonth, null);
            updateAllViewLinks(selectedYear, selectedMonth, null);
        });

        $('#dataFilter').on('change', function() {
            var selectedYear = $('#datayaer').val();
            var selectedMonth = $(this).val();
            fetchLibraryData(selectedYear, selectedMonth, null);
            updateAllViewLinks(selectedYear, selectedMonth, null);
        });

        function fetchLibraryData(year, month, dateRange) {
            $('.library-dashboard-section').addClass('is-updating');

            $.ajax({
                url: '{{ route("dashboard.data.get") }}',
                method: 'POST',
                data: {
                    year: year,
                    month: month,
                    date_range: dateRange,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    var hasRevenueData = response.planTypeWiseRevenue &&
                        Array.isArray(response.planTypeWiseRevenue.labels) &&
                        response.planTypeWiseRevenue.labels.length > 0 &&
                        Array.isArray(response.planTypeWiseRevenue.data) &&
                        response.planTypeWiseRevenue.data.length > 0;

                    if (!hasRevenueData) {
                        $('#revenueChart').hide();
                        $('#revenueChartHeaderBadge').hide();
                        $('#no-data2').css('display', 'flex').show();
                    } else {
                        $('#no-data2').hide();
                        $('#revenueChart').show();
                        renderRevenueChart(
                            response.planTypeWiseRevenue.labels,
                            response.planTypeWiseRevenue.data,
                            response.planTypeWiseRevenue.paid || [],
                            response.planTypeWiseRevenue.pending || []
                        );
                    }

                    var hasCountData = response.planTypeWiseCount &&
                        Array.isArray(response.planTypeWiseCount.labels) &&
                        response.planTypeWiseCount.labels.length > 0 &&
                        Array.isArray(response.planTypeWiseCount.data) &&
                        response.planTypeWiseCount.data.length > 0 &&
                        response.planTypeWiseCount.data.some(val => Number(val) > 0);

                    if (!hasCountData) {
                        $('#bookingCountChart').hide();
                        $('#bookingChartHeaderBadge').hide();
                        $('#no-data3').css('display', 'flex').show();
                    } else {
                        $('#no-data3').hide();
                        $('#bookingCountChart').show();
                        renderBookingCountChart(response.planTypeWiseCount.labels, response.planTypeWiseCount.data);
                    }

                    updateHighlights(response.highlights || {});

                    var planWiseBookings = response.plan_wise_booking || [];
                    var $pw = $('.planwisecount');
                    $pw.empty();
                    if (planWiseBookings.length === 0) {
                        $pw.hide();
                        $('#planWiseHeading').hide();
                    } else {
                        $('#planWiseHeading').show();
                        $pw.css('display', '');
                        planWiseBookings.forEach(function(booking) {
                            var tile = $('<div class="lpd-pw-tile"><small></small><b></b></div>');
                            tile.find('small').text(booking.plan_type_name);
                            tile.find('b').text(booking.booking);
                            $pw.append(tile);
                        });
                    }
                },
                error: function(xhr) {
                    console.error(xhr);
                },
                complete: function() {
                    $('.lp-skel').removeClass('lp-skel');
                    $('.library-dashboard-section').removeClass('is-updating');
                }
            });
        }

        function setNum(id, v) {
            var el = document.getElementById(id);
            if (!el) return;
            el.textContent = (v === undefined || v === null || v === '') ? '0' : v;
        }

        function updateHighlights(highlights) {
            // general seats
            setNum('gen-totalBookings', highlights.generalBookings);
            setNum('gen-active-seat', highlights.genactiveSeat);
            setNum('gen-aboutToExpire', highlights.gengenAboutToExpire);
            setNum('gen-expired', highlights.genexpired_seats);
            // slots + operations
            setNum('totalBookings', highlights.total_booking);
            setNum('till_previous_book', highlights.previous_month);
            setNum('onlinePaid', highlights.online_paid);
            setNum('offlinePaid', highlights.offline_paid);
            setNum('otherPaid', highlights.other_paid);
            setNum('expiredInFive', highlights.expired_in_five);
            setNum('expiredSeats', highlights.expired_seats);
            setNum('extended_seats', highlights.extended_seats);
            setNum('swap_seat', highlights.swap_seat);
            setNum('learnerUpgrade', highlights.learnerUpgrade);
            setNum('reactive', highlights.reactive);
            setNum('active_booking', highlights.active_booking);
            setNum('close_seat', highlights.close_seat);
            setNum('month_total_active_book', highlights.month_total_active_book);
            setNum('month_all_expired', highlights.month_all_expired);
            setNum('thismonth_total_book', highlights.thismonth_total_book);
            setNum('renew_seat', highlights.renew_seat);
            setNum('delete_seat', highlights.delete_seat);
            setNum('change_plan_seat', highlights.change_plan_seat);
            // seats right now
            if (highlights.total_seat !== undefined) setNum('total_seat', highlights.total_seat);
            if (highlights.booked_seat !== undefined) setNum('booked_seat', highlights.booked_seat);
            if (highlights.available_seat !== undefined) { setNum('available_seat', highlights.available_seat); $('.js-free').text(highlights.available_seat); }
            if (highlights.expired_seats !== undefined) setNum('expired_seat', highlights.expired_seats);
            var tot = Number(highlights.total_seat), bk = Number(highlights.booked_seat);
            if (tot > 0 && !isNaN(bk)) {
                var pct = Math.max(0, Math.min(100, Math.round(bk / tot * 100)));
                $('#lpdRing').css('--p', pct);
                $('#lpdPct').text(pct + '%');
            }
            // money
            var money = {
                total_income: highlights.monthly_income, other_total_income: highlights.other_total_income, total_expense: highlights.monthly_expense,
                total_refund: highlights.monthly_refund, total_pending: highlights.monthly_pending, total_balance: highlights.monthly_balance
            };
            moneyIds.forEach(function (id) {
                var txt = inr(money[id]);
                var el = document.getElementById(id); if (el) el.textContent = txt;
                $('[data-copy="' + id + '"]').text(txt);
            });
        }

        function updateAllViewLinks(year, month, dateRange) {
            $('.viewall').each(function() {
                var $link = $(this);
                // Cache the original href so repeated calls rebuild from the pristine URL.
                var baseUrl = $link.data('baseHref');
                if (baseUrl === undefined) {
                    baseUrl = $link.attr('href');
                    $link.data('baseHref', baseUrl);
                }
                var queryParams = [];
                if (year) queryParams.push(`year=${year}`);
                if (month) queryParams.push(`month=${month}`);
                if (dateRange) queryParams.push(`date_range=${dateRange}`);
                if (!queryParams.length) {
                    $link.attr('href', baseUrl);
                    return;
                }
                var separator = baseUrl.indexOf('?') !== -1 ? '&' : '?';
                $link.attr('href', baseUrl + separator + queryParams.join('&'));
            });
        }
    });
</script>

<script>
    function lpdCss(name, fallback) {
        var v = getComputedStyle(document.documentElement).getPropertyValue(name);
        return (v && v.trim()) || fallback;
    }
    function lpdFont() { return "'Instrument Sans', system-ui, sans-serif"; }

    function renderRevenueChart(labels, data, paidData, pendingData) {
        var canvas = document.getElementById('revenueChart');
        if (!canvas || typeof Chart === 'undefined') return;
        if (Chart.getChart && Chart.getChart("revenueChart")) Chart.getChart("revenueChart").destroy();
        window.__lpdRev = [labels, data, paidData, pendingData];

        var ctx = canvas.getContext('2d');
        var acc = lpdCss('--lp-acc', '#1d3c86');
        var gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, '#5b7fd0');
        gradient.addColorStop(1, acc);

        var totalRevenue = data.reduce((a, b) => Number(a) + Number(b), 0);
        var totalPaid = paidData && paidData.length ? paidData.reduce((a, b) => Number(a) + Number(b), 0) : null;
        var totalPending = pendingData && pendingData.length ? pendingData.reduce((a, b) => Number(a) + Number(b), 0) : null;

        var badgeEl = document.getElementById('revenueChartHeaderBadge');
        if (badgeEl) {
            var badgeHtml = `<span class="lp-tag lp-tag--acc">Total ₹${totalRevenue.toLocaleString('en-IN')}</span>`;
            if (totalPaid !== null && totalPending > 0) {
                badgeHtml += `<span class="lp-tag lp-tag--ok">Paid ₹${totalPaid.toLocaleString('en-IN')}</span><span class="lp-tag lp-tag--bad">Due ₹${totalPending.toLocaleString('en-IN')}</span>`;
            }
            badgeEl.innerHTML = badgeHtml;
            badgeEl.style.display = 'inline-flex';
        }

        var ink = lpdCss('--lp-ink', '#16171b'), mut = lpdCss('--lp-mut2', '#7b7c83'), line = lpdCss('--lp-line', '#ececea');
        new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: [{ label: 'Revenue', data: data, backgroundColor: gradient, borderWidth: 0, borderRadius: 10, maxBarThickness: 46, borderSkipped: false }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: { duration: 900, easing: 'easeOutQuart' },
                layout: { padding: { top: 26, bottom: 4 } },
                scales: {
                    y: { beginAtZero: true, grid: { color: line, drawBorder: false }, border: { display: false },
                        ticks: { color: mut, font: { family: lpdFont(), size: 11, weight: '500' }, callback: v => '₹' + Number(v).toLocaleString('en-IN') } },
                    x: { grid: { display: false }, border: { display: false }, ticks: { color: ink, font: { family: lpdFont(), size: 12, weight: '600' } } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#16171b', padding: 10, cornerRadius: 10,
                        titleFont: { family: lpdFont(), size: 12, weight: '600' }, bodyFont: { family: lpdFont(), size: 12 },
                        callbacks: {
                            label: function(context) {
                                var idx = context.dataIndex, rev = Number(context.raw || 0);
                                var lines = [' Revenue: ₹' + rev.toLocaleString('en-IN')];
                                if (paidData && pendingData && paidData.length > idx) {
                                    var p = Number(paidData[idx] || 0), d = Number(pendingData[idx] || 0);
                                    lines.push(d > 0 ? ' Collected: ₹' + p.toLocaleString('en-IN') + '  |  Due: ₹' + d.toLocaleString('en-IN') : ' Collected: ₹' + p.toLocaleString('en-IN') + ' (fully paid)');
                                }
                                return lines;
                            }
                        }
                    },
                    datalabels: {
                        color: ink, display: true, anchor: 'end', align: 'top', offset: 2,
                        font: { family: lpdFont(), size: 11, weight: '700' },
                        formatter: (value) => '₹' + Number(value || 0).toLocaleString('en-IN')
                    }
                }
            },
            plugins: (typeof ChartDataLabels !== 'undefined') ? [ChartDataLabels] : []
        });
    }

    function renderBookingCountChart(labels, data) {
        var canvas = document.getElementById('bookingCountChart');
        if (!canvas || typeof Chart === 'undefined') return;
        if (Chart.getChart && Chart.getChart("bookingCountChart")) Chart.getChart("bookingCountChart").destroy();
        window.__lpdCnt = [labels, data];

        if (data && data.length > 0) {
            var totalBookings = data.reduce((a, b) => Number(a) + Number(b), 0);
            var bookingBadgeEl = document.getElementById('bookingChartHeaderBadge');
            if (bookingBadgeEl) {
                bookingBadgeEl.innerHTML = `<span class="lp-tag lp-tag--acc">${totalBookings} ${totalBookings === 1 ? 'booking' : 'bookings'}</span>`;
                bookingBadgeEl.style.display = 'inline-flex';
            }
            var palette = ['#1d3c86', '#2fa37a', '#eb881f', '#8f68cb', '#3a81d7', '#de3b3d', '#34939f', '#bf2a82'];
            var colors = data.map((_, i) => palette[i % palette.length]);
            var ink = lpdCss('--lp-ink2', '#3c3d42'), card = lpdCss('--lp-card', '#ffffff');

            new Chart(canvas.getContext('2d'), {
                type: 'doughnut',
                data: { labels: labels, datasets: [{ label: 'Bookings', data: data, backgroundColor: colors, borderColor: card, borderWidth: 3, hoverOffset: 6 }] },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '62%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, pointStyle: 'circle', padding: 12, color: ink, font: { family: lpdFont(), size: 12, weight: '600' } } },
                        tooltip: {
                            backgroundColor: '#16171b', padding: 10, cornerRadius: 10,
                            titleFont: { family: lpdFont(), size: 12, weight: '600' }, bodyFont: { family: lpdFont(), size: 12 },
                            callbacks: { label: t => ` ${t.label || ''}: ${t.raw || 0} ${(t.raw || 0) === 1 ? 'booking' : 'bookings'}` }
                        },
                        datalabels: {
                            color: '#ffffff', display: c => c.dataset.data[c.dataIndex] > 0, formatter: v => v,
                            font: { family: lpdFont(), size: 12, weight: '700' }
                        }
                    }
                },
                plugins: (typeof ChartDataLabels !== 'undefined') ? [ChartDataLabels] : []
            });
        }
    }

    // Re-draw charts with the right colours when the theme changes
    document.addEventListener('lp:theme', function () {
        if (window.__lpdRev) renderRevenueChart.apply(null, window.__lpdRev);
        if (window.__lpdCnt) renderBookingCountChart.apply(null, window.__lpdCnt);
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('greeting-message');
        if (!el) return;
        var h = new Date().getHours(), name = el.getAttribute('data-name') || '';
        var g = h < 5 ? 'Good night' : h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : h < 21 ? 'Good evening' : 'Good night';
        el.textContent = g + (name ? ', ' + name : '');
    });
</script>

<script>
    function printQR() {
        let svg = document.querySelector("#qrPreview svg");
        let printWindow = window.open('', '_blank');
        printWindow.document.write(`
        <html>
        <head>
            <title>Print QR</title>
            <style>
                @page { size: A4; margin: 20mm; }
                body { text-align:center; margin:0; padding:50px; }
                svg { width: 650px; height: 650px; }
            </style>
        </head>
        <body>${svg.outerHTML}</body>
        </html>
    `);
        printWindow.document.close();
        printWindow.print();
    }

    function downloadQR() {
        let svg = document.querySelector("#qrPreview svg");
        let serializer = new XMLSerializer();
        let svgData = serializer.serializeToString(svg);
        let canvas = document.createElement("canvas");
        let ctx = canvas.getContext("2d");
        let img = new Image();
        img.onload = function() {
            canvas.width = 650;
            canvas.height = 650;
            ctx.drawImage(img, 0, 0, 650, 650);
            let pngFile = canvas.toDataURL("image/png");
            let downloadLink = document.createElement("a");
            downloadLink.download = "branch-qr.png";
            downloadLink.href = pngFile;
            downloadLink.click();
        };
        img.src = "data:image/svg+xml;base64," + btoa(unescape(encodeURIComponent(svgData)));
    }
</script>
<script>
    $(document).on('click', '.delete-booking', function(e) {
        e.preventDefault();
        let bookingId = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This booking will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1d3c86',
            cancelButtonColor: '#de3b3d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('booking') }}/" + bookingId,
                    type: "DELETE",
                    data: {
                        _token: "{{ csrf_token() }}",
                    },
                    success: function(response) {
                        Swal.fire('Deleted!', 'Booking has been deleted.', 'success').then(() => {
                            location.reload();
                        });
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong. Please try again.', 'error');
                    }
                });
            }
        });
    });
</script>
<script>
    $(document).ready(function() {
        // SweetAlert confirmation before approving a QR booking
        $(document).on('submit', '.approve-form', function(e) {
            e.preventDefault();
            let form = this;
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to approve this booking.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#00793d',
                cancelButtonColor: '#de3b3d',
                confirmButtonText: 'Yes, Approve it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('setup') === 'completed') {
        const modal = new bootstrap.Modal(document.getElementById('setupSuccessModal'));
        modal.show();
        var fire = function () { if (typeof confetti === 'function') confetti({ particleCount: 250, spread: 80, origin: { y: 0.6 } }); };
        if (typeof confetti === 'function') fire(); else window.addEventListener('load', fire);
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>
@endsection
