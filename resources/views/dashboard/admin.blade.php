@extends('layouts.library')

@section('title', 'Admin Dashboard')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-dash.css') }}?v={{ @filemtime(public_path('css/lb-dash.css')) }}">
@endpush

@section('content')
@include('partials.page-skeleton', ['type' => 'dashboard'])
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
$lpShiftIcon = function ($start) {
    if (!$start) return 'clock-3';
    $h = (int) substr($start, 0, 2);
    return $h < 11 ? 'sunrise' : ($h < 17 ? 'sun' : ($h < 21 ? 'sunset' : 'moon'));
};
$lpExtendDays = (int) getExtendDays();
$lpToday = \Carbon\Carbon::today();
$lpBranchName = getCurrentBranchName();
$lpSeatMap = collect(generateSeatNumbers())->keyBy('main');
$lpSeatLabel = function ($no) use ($lpSeatMap) { $s = $lpSeatMap->get((int) $no); if (!$s) return $no; return (!empty($s['floor_name']) && !empty($s['floor'])) ? $s['floor'] . ' (' . $s['floor_name'] . ')' : $s['main']; };
// Available seats tile: the seat number on its floor + the floor's short name (GF, FF, F1 …), or just the number
$lpSeatParts = function ($no) use ($lpSeatMap) { $s = $lpSeatMap->get((int) $no); if (!$s) return [(string) $no, null]; return (!empty($s['floor_name']) && !empty($s['floor'])) ? [(string) $s['floor'], shortFloorName($s['floor_name'])] : [(string) $s['main'], null]; };
$lpHasBranch = (bool) getCurrentBranch();
$lpQrReady = isset($branch) && $branch?->uuid;
$lpMoneyToday = $lpCan('Todays Financial Snapshot');
$lpMoneyMonth = $lpCan('Monthly Financial Overview');
@endphp

<!-- SUCCESS MODAL -->
<div class="modal fade" id="setupSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content lpd-success">
            <div class="modal-body">
                <span class="lpd-success-ic"><x-lb-icon name="party-popper" /></span>
                <h4>Welcome to Libraro{{ optional(getLibrary())->library_name ? ', ' . getLibrary()->library_name : '' }}!</h4>
                <p>Your library is set up and ready.<br>Book your first seat to get started.</p>
                <button type="button" class="lp-btn" data-bs-dismiss="modal">Go to dashboard<x-lb-icon name="arrow-right" /></button>
            </div>
        </div>
    </div>
</div>

<div class="library-dashboard-section lpd has-lp-head">

    {{-- ===== Page head ===== --}}
    <div class="lpd-head">
        <div class="lpd-hi">
            <span class="lpd-hi-emoji" id="greetEmoji" aria-hidden="true">👋</span>
            <div class="lpd-hi-t">
                <h1 class="lpd-title" id="greeting-message" data-name="{{ $lpFirstName }}">Hello, {{ $lpFirstName }}</h1>
                {{-- one short line: the festival, else the branch --}}
                @if($festival)
                    <p class="lpd-mood is-festival"><span aria-hidden="true">🎉</span><b>Happy {{ $festival->festival_name }}!</b></p>
                @elseif($lpBranchName)
                    <p class="lpd-mood">{{ $lpBranchName }}</p>
                @endif
            </div>
        </div>
        <div class="lpd-head-r">
            <span class="lpd-date"><x-lb-icon name="calendar" />{{ now()->format('D, j M') }}</span>
            <a href="{{ route('library.how-to-use') }}" class="lp-btn lp-btn--ghost lp-hide-xs lpd-help" title="How Libraro works" aria-label="How Libraro works"><x-lb-icon name="book-open" /></a>
            @can('has-permission', 'Book Seat')
            <a href="javascript:;" class="lp-btn lpd-cta noseat_popup"><x-lb-icon name="plus" />New booking</a>
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

    {{-- ===== Tabs ===== --}}
    <nav class="lpd-tabs" role="tablist" aria-label="Dashboard sections">
        <button type="button" role="tab" class="lpd-tab is-on" id="lpdTab-desk" data-lpd-tab="desk" aria-controls="lpdPane-desk" aria-selected="true"><x-lb-icon name="layout-dashboard" /><span>Command Center</span></button>
        <button type="button" role="tab" class="lpd-tab" id="lpdTab-reports" data-lpd-tab="reports" aria-controls="lpdPane-reports" aria-selected="false" tabindex="-1"><x-lb-icon name="chart-no-axes-combined" /><span>Insights &amp; Reports</span></button>
    </nav>

    <div class="lpd-pane is-on" id="lpdPane-desk" role="tabpanel" aria-labelledby="lpdTab-desk" data-lpd-pane="desk">
    {{-- ===== Highlights (festivals, birthdays, founder's day, QR) ===== --}}
    @php
        $lpBranchLabel = $lpBranchName ?: (getLibrary()->library_name ?? 'our library');
        $lpSlides = [];
        if ($festival) {
            $lpSlides[] = ['tone' => 'fest', 'art' => 'sparkles', 'tag' => 'Today · ' . now()->format('D, j M'), 'tagIcon' => 'calendar-heart',
                'title' => 'Happy ' . $festival->festival_name . '!', 'text' => ($festival->description ?? '') ?: 'Send a greeting to your members and make their day.',
                'cta' => 'Send wishes', 'href' => 'https://wa.me/?text=' . rawurlencode('Happy ' . $festival->festival_name . '! Warm wishes from Team ' . $lpBranchLabel . '.')];
        }
        $lpBdays = $highlights['birthdays'] ?? [];
        if (count($lpBdays)) {
            $names = collect($lpBdays)->take(4)->pluck('first')->implode(', ') . (count($lpBdays) > 4 ? ' and ' . (count($lpBdays) - 4) . ' more' : '');
            $lpSlides[] = ['tone' => 'bday', 'art' => 'cake', 'tag' => 'Birthdays today', 'tagIcon' => 'cake',
                'title' => count($lpBdays) == 1 ? $lpBdays[0]['first'] . ' turns a year older' : count($lpBdays) . ' members celebrate today', 'text' => $names,
                'cta' => 'Wish them', 'href' => 'https://wa.me/?text=' . rawurlencode('Happy birthday! Wishing you a great year and success in your exams. — Team ' . $lpBranchLabel), 'faces' => array_slice($lpBdays, 0, 4)];
        }
        if (!empty($highlights['founder'])) {
            $y = $highlights['founder']['years'];
            $lpSlides[] = ['tone' => 'founder', 'art' => 'party-popper', 'tag' => "Founder's day", 'tagIcon' => 'party-popper',
                'title' => $y . ' year' . ($y > 1 ? 's' : '') . ' of ' . $lpBranchLabel, 'text' => 'Thank your members for being part of the journey.',
                'cta' => 'Share the news', 'href' => 'https://wa.me/?text=' . rawurlencode('Today ' . $lpBranchLabel . ' turns ' . $y . '! Thank you for studying with us.')];
        }
        foreach (($highlights['festivals'] ?? []) as $f) {
            if ($f['in'] === 0 && $festival) continue;
            $lpSlides[] = ['tone' => 'soon', 'art' => 'flame', 'tag' => $f['when'], 'tagIcon' => 'calendar-heart',
                'title' => $f['name'], 'text' => ($f['desc'] ? $f['desc'] . '. ' : '') . 'Plan a greeting or a renewal offer for your members.',
                'cta' => 'Draft a greeting', 'href' => 'https://wa.me/?text=' . rawurlencode('Happy ' . $f['name'] . '! Warm wishes from Team ' . $lpBranchLabel . '.')];
        }
        $lpSlides[] = ['tone' => 'qr', 'art' => 'qr-code', 'tag' => 'Grow your library', 'tagIcon' => 'sparkles',
            'title' => 'Let members book online', 'text' => 'Share your seat-booking QR so new members can book and pay in a minute.',
            'cta' => $lpQrReady ? 'Show QR code' : 'Set up QR', 'href' => $lpQrReady ? null : route('library.settings'), 'qr' => $lpQrReady];
    @endphp
    <div class="lp-sec"><x-lb-icon name="party-popper" class="lp-sec-ic" /><h2>Highlights</h2><p>Festivals, birthdays and banners</p></div>
    <section class="lpd-hl" id="lpdHl" data-count="{{ count($lpSlides) }}">
        <div class="lpd-hl-track">
            @foreach($lpSlides as $i => $s)
            <div class="lpd-hl-slide lpd-hl--{{ $s['tone'] }}" role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ count($lpSlides) }}">
                <x-lb-icon :name="$s['art']" class="lpd-hl-art" />
                <div class="lpd-hl-t">
                    <span class="lpd-hl-tag"><x-lb-icon :name="$s['tagIcon']" />{{ $s['tag'] }}</span>
                    <h3>{{ $s['title'] }}</h3>
                    <p>{{ $s['text'] }}</p>
                    @if(!empty($s['qr']))
                    <button type="button" class="lpd-hl-cta" data-bs-toggle="modal" data-bs-target="#branchQR"><x-lb-icon name="qr-code" />{{ $s['cta'] }}</button>
                    @elseif(str_starts_with($s['href'] ?? '', 'https://wa.me'))
                    <a href="{{ $s['href'] }}" target="_blank" rel="noopener" class="lpd-hl-cta"><x-lb-brand name="whatsapp" />{{ $s['cta'] }}</a>
                    @else
                    <a href="{{ $s['href'] }}" class="lpd-hl-cta">{{ $s['cta'] }}<x-lb-icon name="arrow-right" /></a>
                    @endif
                </div>
                @if(!empty($s['faces']))
                <div class="lpd-hl-faces">
                    @foreach($s['faces'] as $b)
                    <span class="lp-face {{ $lpTone($b['name']) }}">@if($b['img'])<img src="{{ $b['img'] }}" alt="" loading="lazy">@else{{ $b['initials'] }}@endif</span>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @if(count($lpSlides) > 1)
        <div class="lpd-hl-nav">
            <div class="lpd-hl-dots">
                @foreach($lpSlides as $i => $s)<button type="button" data-go="{{ $i }}" aria-label="Slide {{ $i + 1 }}" class="{{ $i === 0 ? 'is-on' : '' }}"></button>@endforeach
            </div>
            <div class="lpd-hl-arrows">
                <button type="button" data-step="-1" aria-label="Previous slide"><x-lb-icon name="chevron-left" /></button>
                <button type="button" data-step="1" aria-label="Next slide"><x-lb-icon name="chevron-right" /></button>
            </div>
        </div>
        @endif
    </section>

    {{-- ===== Overview ===== --}}
    <div class="lp-sec"><x-lb-icon name="layout-dashboard" class="lp-sec-ic" /><h2>Overview</h2><p>Members, seats, shifts and income</p></div>
    <div class="lpd-kgrid">
        <a href="{{ route('learners') }}" class="lpd-k">
            <span class="lpd-k-top"><span class="lpd-k-ic"><x-lb-icon name="users-round" /></span><span class="lpd-delta {{ ($memberKpis['joined'] ?? 0) > 0 ? 'is-up' : 'is-flat' }}">+{{ $memberKpis['joined'] ?? 0 }} this month</span></span>
            <span><b>{{ $memberKpis['active'] ?? 0 }}</b><small>Active members</small></span>
        </a>
        @can('has-permission', 'Booked Seats')
        <a href="{{ route('seats') }}" class="lpd-k">
            <span class="lpd-k-top"><span class="lpd-k-ic lpd-k-ic--ok"><x-lb-icon name="armchair" /></span><span class="lpd-delta {{ $lpPct >= 50 ? 'is-up' : 'is-flat' }}" id="lpdPct">{{ $lpPct }}%</span></span>
            <span><b><span id="booked_seat">{{ $lpBooked }}</span> / <span id="total_seat">{{ $lpTotal }}</span></b><small>Seats filled today</small></span>
        </a>
        @endcan
        @can('has-permission', 'Available Seats')
        <a href="{{ route('seats') }}" class="lpd-k">
            <span class="lpd-k-top"><span class="lpd-k-ic lpd-k-ic--warn"><x-lb-icon name="circle-check" /></span><span class="lpd-delta is-flat">Free</span></span>
            <span><b id="available_seat">{{ $lpFree }}</b><small>Seats free now</small></span>
        </a>
        @endcan
        @can('has-permission', 'Expired Seats')
        <a href="{{ route('learners.list.view', ['type' => 'expired_seats']) }}" class="lpd-k">
            <span class="lpd-k-top"><span class="lpd-k-ic lpd-k-ic--bad"><x-lb-icon name="calendar-x" /></span><span class="lpd-delta is-down">Renew</span></span>
            <span><b id="expired_seat">{{ (int) ($expired_seat ?? 0) }}</b><small>Expired seats</small></span>
        </a>
        @endcan
    </div>

    {{-- ===== General seats (seats without a number): right under the overview ===== --}}
    @can('has-permission', 'General Seat Counts')
    <div class="lp-sec"><x-lb-icon name="sofa" class="lp-sec-ic" /><h2>General Seat Info</h2><p>Seats without a number</p></div>
    <section class="lrp lrp-card lrp-gen lpd-gen">
        <div class="lrp-card-h">
            <span class="lrp-ic t-violet"><x-lb-icon name="sofa" /></span>
            <div><b>General seats <span class="lrp-beta">Beta</span></b><small>Seats without a number — extra income from spare space</small></div>
        </div>
        <div class="lrp-gen-row">
            <div><small>Total</small><b id="gen-totalBookings">0</b></div>
            <div class="t-ok"><small>Active</small><b id="gen-active-seat">0</b></div>
            <div class="t-warn"><small>Ending soon</small><b id="gen-aboutToExpire">0</b></div>
            <div class="t-bad"><small>Ended</small><b id="gen-expired">0</b></div>
        </div>
    </section>
    @endcan

    {{-- ===== Live in library: today's attendance at a glance, opens the attendance summary ===== --}}
    {{-- (not when Attendance is switched off on Show / Hide Options, App\Support\FeatureToggles) --}}
    @if($live && !\App\Support\FeatureToggles::hidden('10'))
    <div class="lp-sec"><x-lb-icon name="fingerprint-pattern" class="lp-sec-ic" /><h2>Library Live Attendance</h2><p>Who is in the library right now</p></div>
    <section class="lpd-live lp-rise is-link" id="lpdLive" aria-live="polite" data-href="{{ route('get.learner.attendance') }}" title="Open today's attendance">
        <x-lb-icon name="fingerprint-pattern" class="lpd-live-bg" />
        <div class="lpd-live-main">
            <div class="lpd-ring" data-live="ring"><div><b data-live="pct">0%</b><small>present</small></div></div>
            <div class="lpd-live-t">
                <span class="lpd-live-tag"><i></i>LIVE IN LIBRARY</span>
                <div class="lpd-live-big"><span data-live="in">0</span> <span data-live="noun">students</span> inside</div>
                <div class="lpd-live-sub">of <b data-live="all">0</b> booked · <span data-live="shift">All shifts</span> · <b data-live="clock">Now</b></div>
                <div class="lpd-live-hint"><x-lb-icon name="trending-up" /><span data-live="hint"></span></div>
            </div>
        </div>
        <div class="lpd-live-side">
            <div class="lpd-live-chips" data-live="floors"></div>
            <div class="lpd-live-acts">
                <div class="lpd-faces" data-live="faces"></div>
                <a href="{{ route('attendance.qrcode') }}" class="lp-round lp-round--ghost" title="Attendance QR code" aria-label="Attendance QR code"><x-lb-icon name="qr-code" /></a>
                <a href="{{ route('get.learner.attendance') }}" class="lp-btn lp-btn--ok"><x-lb-icon name="user-check" />View present</a>
            </div>
        </div>
    </section>
    @endif


    {{-- ===== Money: collection strip + today's and this month's books (was the Financial Summary tab) ===== --}}
    @if ($lpMoneyToday || $lpMoneyMonth)
    <div class="lp-sec"><x-lb-icon name="wallet" class="lp-sec-ic" /><h2>Money</h2><p>Collection, dues and balance. Tap any figure to see its transactions</p></div>
    @php
        $lpColToday = $collections['today']['total'] ?? $todayCollection;
        $lpColTodayN = $collections['today']['count'] ?? 0;
        $lpColYest = $collections['yesterday']['total'] ?? 0;
        $lpColPeriod = $collections['period'] ?? null;
    @endphp
    <button type="button" class="lpd-money lp-rise" data-bs-toggle="offcanvas" data-bs-target="#lpdCollect" aria-controls="lpdCollect" aria-label="Open collection summary">
        @if($lpMoneyToday)
        <span class="lpd-money-col">
            <span class="lpd-money-l"><x-lb-icon name="indian-rupee" />Collected today</span>
            <span class="lpd-money-v">{{ $lpInr($lpColToday) }}</span>
            <span class="lpd-money-s">{{ $lpColTodayN }} payment{{ $lpColTodayN == 1 ? '' : 's' }} · yesterday {{ $lpInr($lpColYest) }}</span>
        </span>
        @endif
        @if($lpMoneyMonth && $lpColPeriod)
        <span class="lpd-money-col">
            <span class="lpd-money-l">Last {{ $lpColPeriod['days'] }} days</span>
            <span class="lpd-money-v lpd-money-v--md">{{ $lpInr($lpColPeriod['total']) }}</span>
            <span class="lpd-money-s">{{ $lpColPeriod['count'] }} payment{{ $lpColPeriod['count'] == 1 ? '' : 's' }} · {{ $lpColPeriod['from'] }} – {{ $lpColPeriod['to'] }}</span>
        </span>
        @endif
        @if($lpMoneyMonth || $lpMoneyToday)
        <span class="lpd-money-col">
            <span class="lpd-money-l lpd-money-due"><x-lb-icon name="alarm-clock" />Pending dues</span>
            <span class="lpd-money-v lpd-money-v--md lpd-money-due">{{ $lpInr($pendingTotal ?? 0) }}</span>
            <span class="lpd-money-s">@if($pendingDueCount > 0){{ $pendingDueCount }} member{{ $pendingDueCount == 1 ? '' : 's' }} to collect @else Nothing to collect @endif</span>
        </span>
        @endif
        <span class="lpd-money-btn">View summary<x-lb-icon name="arrow-right" /></span>
    </button>
    @endif

    {{-- ===== Books: today + selected month ===== --}}
    @if ($lpMoneyToday || $lpMoneyMonth)
    @php
        $lpBook = fn ($type) => route('library.transaction.view', ['type' => $type]);
        $lpTodayIn = (float) $todayCollection + (float) $today_other_amt;
        $lpTodayOut = (float) $todayExpense + (float) $today_refund;
        $lpTodayFlow = max(1, $lpTodayIn + $lpTodayOut);
    @endphp
    <div class="lpd-books">
        @if($lpMoneyToday)
        <section class="lpd-book">
            <header class="lpd-book-h">
                <span class="lpd-book-ic"><x-lb-icon name="calendar-check" /></span>
                <div><h3>Today</h3><small>{{ now()->format('l, j M Y') }}</small></div>
                <a href="{{ $lpBook('today_collection') }}" class="lpd-book-all viewall">All transactions<x-lb-icon name="arrow-up-right" /></a>
            </header>
            <a href="{{ $lpBook('today_balance') }}" class="lpd-book-hero viewall">
                <span><small>Balance today</small><b class="{{ $todayBalance < 0 ? 'lpd-bad' : '' }}">{{ $lpInr($todayBalance) }}</b></span>
                <span class="lpd-book-flow" aria-hidden="true"><i class="is-in" style="width: {{ round($lpTodayIn / $lpTodayFlow * 100) }}%"></i><i class="is-out" style="width: {{ round($lpTodayOut / $lpTodayFlow * 100) }}%"></i></span>
                <span class="lpd-book-legend"><em class="is-in">In {{ $lpInr($lpTodayIn) }}</em><em class="is-out">Out {{ $lpInr($lpTodayOut) }}</em></span>
                <x-lb-icon name="arrow-up-right" class="lpd-book-go" />
            </a>
            <div class="lpd-book-grid">
                <a href="{{ $lpBook('today_collection') }}" class="lpd-bt is-ok viewall"><span><x-lb-icon name="indian-rupee" /></span><small>Collection</small><b>{{ $lpInr($todayCollection) }}</b><x-lb-icon name="arrow-up-right" class="lpd-bt-go" /></a>
                <a href="{{ $lpBook('today_other_collection') }}" class="lpd-bt is-acc viewall"><span><x-lb-icon name="hand-coins" /></span><small>Other income</small><b>{{ $lpInr($today_other_amt) }}</b><x-lb-icon name="arrow-up-right" class="lpd-bt-go" /></a>
                <a href="{{ $lpBook('today_expense') }}" class="lpd-bt is-bad viewall"><span><x-lb-icon name="receipt" /></span><small>Expense</small><b>{{ $lpInr($todayExpense) }}</b><x-lb-icon name="arrow-up-right" class="lpd-bt-go" /></a>
                <a href="{{ $lpBook('today_refund') }}" class="lpd-bt is-warn viewall"><span><x-lb-icon name="rotate-ccw" /></span><small>Refund</small><b>{{ $lpInr($today_refund) }}</b><x-lb-icon name="arrow-up-right" class="lpd-bt-go" /></a>
                <a href="{{ $lpBook('today_pending') }}" class="lpd-bt is-due viewall"><span><x-lb-icon name="alarm-clock" /></span><small>Due amount</small><b>{{ $lpInr($today_pending) }}</b><x-lb-icon name="arrow-up-right" class="lpd-bt-go" /></a>
                {{-- paid over the bill today: part of the collection, kept for the learner's next renewal (docs/LEARNER_ADVANCE.md) --}}
                <a href="{{ $lpBook('today_advance') }}" class="lpd-bt is-adv viewall" title="Paid more than the bill — already inside Collection; used on the next renewal"><span><x-lb-icon name="wallet" /></span><small>Advance received</small><b>{{ $lpInr($todayAdvance['received'] ?? 0) }}</b><x-lb-icon name="arrow-up-right" class="lpd-bt-go" /></a>
            </div>
        </section>
        @endif
        @if($lpMoneyMonth)
        <section class="lpd-book lpd-book--month">
            <header class="lpd-book-h">
                <span class="lpd-book-ic"><x-lb-icon name="calendar-days" /></span>
                <div><h3 data-book-month>{{ now()->format('F Y') }}</h3><small>Follows the month picked in Insights &amp; Reports</small></div>
                <a href="{{ $lpBook('monthly_collection') }}" class="lpd-book-all viewall">All transactions<x-lb-icon name="arrow-up-right" /></a>
            </header>
            <a href="{{ $lpBook('monthly_balance') }}" class="lpd-book-hero viewall">
                <span><small>Final balance</small><b class="js-money" id="total_balance">₹0</b></span>
                <span class="lpd-book-flow" aria-hidden="true" data-book-flow><i class="is-in" style="width: 0%"></i><i class="is-out" style="width: 0%"></i></span>
                <span class="lpd-book-legend"><em class="is-in" data-book-in>In ₹0</em><em class="is-out" data-book-out>Out ₹0</em></span>
                <x-lb-icon name="arrow-up-right" class="lpd-book-go" />
            </a>
            <div class="lpd-book-grid">
                <a href="{{ $lpBook('monthly_collection') }}" class="lpd-bt is-ok viewall"><span><x-lb-icon name="indian-rupee" /></span><small>Collection</small><b class="js-money" id="total_income">₹0</b><x-lb-icon name="arrow-up-right" class="lpd-bt-go" /></a>
                <a href="{{ $lpBook('monthly_other_collection') }}" class="lpd-bt is-acc viewall"><span><x-lb-icon name="hand-coins" /></span><small>Other income</small><b class="js-money" id="other_total_income">₹0</b><x-lb-icon name="arrow-up-right" class="lpd-bt-go" /></a>
                <a href="{{ $lpBook('monthly_expense') }}" class="lpd-bt is-bad viewall"><span><x-lb-icon name="receipt" /></span><small>Expense</small><b class="js-money" id="total_expense">₹0</b><x-lb-icon name="arrow-up-right" class="lpd-bt-go" /></a>
                <a href="{{ $lpBook('monthly_refund') }}" class="lpd-bt is-warn viewall"><span><x-lb-icon name="rotate-ccw" /></span><small>Refund</small><b class="js-money" id="total_refund">₹0</b><x-lb-icon name="arrow-up-right" class="lpd-bt-go" /></a>
                <a href="{{ $lpBook('monthly_pending') }}" class="lpd-bt is-due viewall"><span><x-lb-icon name="alarm-clock" /></span><small>Due amount</small><b class="js-money" id="total_pending">₹0</b><x-lb-icon name="arrow-up-right" class="lpd-bt-go" /></a>
                <a href="{{ $lpBook('monthly_advance') }}" class="lpd-bt is-adv viewall" title="Paid more than the bill — already inside Collection; used on the next renewal"><span><x-lb-icon name="wallet" /></span><small>Advance received</small><b class="js-money" id="total_advance">₹0</b><x-lb-icon name="arrow-up-right" class="lpd-bt-go" /></a>
            </div>
        </section>
        @endif
    </div>
    @endif

    {{-- ===== Orders (Ordering & Services): orders and income, today and this month =====
         $ordersCard is null unless the plan has the module, the user has "Orders Summary" and the owner did not hide it --}}
    @if (!empty($ordersCard))
    @php
        $lpOc = $ordersCard;
        $lpOcBoard = $lpCan('Manage Orders') ? route('orders.board') : null;
        $lpOcReports = $lpOcBoard && !in_array('orders.reports', \App\Services\Ordering\OrderingVisibility::hiddenMenuUrls(), true);
        $lpOcLink = fn ($range) => $lpOcReports ? route('orders.reports', ['range' => $range]) : $lpOcBoard;
        // a tile is a link only when the user can open the page behind it
        $lpOcOpen = fn ($href) => $href ? '<a href="' . e($href) . '" class="lpd-k">' : '<div class="lpd-k">';
        $lpOcClose = fn ($href) => $href ? '</a>' : '</div>';
    @endphp
    <div class="lp-sec"><x-lb-icon name="coffee" class="lp-sec-ic" /><h2>Orders</h2><p>@if ($lpOc['on'])Cafeteria orders and income @else Ordering is off for learners right now @endif</p></div>
    <div class="lpd-kgrid lpd-orders">
        {!! $lpOcOpen($lpOcBoard) !!}
            <span class="lpd-k-top"><span class="lpd-k-ic lpd-k-ic--warn"><x-lb-icon name="coffee" /></span><span class="lpd-delta {{ $lpOc['open'] > 0 ? 'is-up' : 'is-flat' }}">{{ $lpOc['open'] }} open now</span></span>
            <span><b>{{ $lpOc['today']['orders'] }}</b><small>Orders today</small></span>
        {!! $lpOcClose($lpOcBoard) !!}
        {!! $lpOcOpen($lpOcLink('today')) !!}
            <span class="lpd-k-top"><span class="lpd-k-ic lpd-k-ic--ok"><x-lb-icon name="indian-rupee" /></span><span class="lpd-delta is-flat">Yesterday {{ $lpInr($lpOc['yesterday_income']) }}</span></span>
            <span><b>{{ $lpInr($lpOc['today']['income']) }}</b><small>Income today</small></span>
        {!! $lpOcClose($lpOcLink('today')) !!}
        {!! $lpOcOpen($lpOcLink('month')) !!}
            <span class="lpd-k-top"><span class="lpd-k-ic"><x-lb-icon name="calendar-days" /></span><span class="lpd-delta is-flat">{{ $lpOc['month_label'] }}</span></span>
            <span><b>{{ $lpOc['month']['orders'] }}</b><small>Orders this month</small></span>
        {!! $lpOcClose($lpOcLink('month')) !!}
        {!! $lpOcOpen($lpOcLink('month')) !!}
            <span class="lpd-k-top"><span class="lpd-k-ic lpd-k-ic--ok"><x-lb-icon name="wallet" /></span><span class="lpd-delta {{ $lpOc['to_collect'] > 0 ? 'is-down' : 'is-flat' }}">{{ $lpOc['to_collect'] > 0 ? $lpInr($lpOc['to_collect']) . ' to collect' : 'All collected' }}</span></span>
            <span><b>{{ $lpInr($lpOc['month']['income']) }}</b><small>Income this month</small></span>
        {!! $lpOcClose($lpOcLink('month')) !!}
    </div>
    @endif

    {{-- ===== Needs action ===== --}}
    @php
        $lpShowExpire = $lpCan('Seat About to Expire List');
        $lpShowExtend = $lpCan('Extend Seats list');
    @endphp
    @if($lpHasBranch)
    <div class="lp-sec"><x-lb-icon name="list-checks" class="lp-sec-ic" /><h2>Needs action</h2><p>Collect, renew, remind</p></div>

    <section class="lpd-card lpd-due dashboard-payment-due-section">
        <div class="lpd-card-h">
            <h3><x-lb-icon name="indian-rupee" class="lpd-h-ic lpd-h-ic--bad" />Payment due <span class="lp-count">{{ $pendingDueCount }}</span></h3>
            <div class="lpd-due-r">
                @if($pendingDueCount > 0)
                <span class="lpd-due-sum">{{ $lpInr($pendingTotal ?? 0) }} pending</span>
                <button type="button" class="lp-round lp-round--ghost lpd-sc" data-sc="-1" aria-label="Scroll left"><x-lb-icon name="chevron-left" /></button>
                <button type="button" class="lp-round lp-round--ghost lpd-sc" data-sc="1" aria-label="Scroll right"><x-lb-icon name="chevron-right" /></button>
                @endif
                <a href="{{ route('learner.pending.payment.list', ['filter' => 'pending']) }}" class="lp-link">View all<x-lb-icon name="chevron-right" /></a>
            </div>
        </div>
        @if($pendingDueCount > 0)
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
                    @php
                        // "Pending Payment Reminder" template, on the channel chosen for this learner (WhatsApp when none)
                        $dueLinks = pendingPaymentReminderLinks((string) $member['mobile'], (string) ($member['name'] ?? ''), $duePendingAmount, $dueDate, $member['sended_message_type'] ?? null, $member['id'] ?? null);
                    @endphp
                    @if($dueLinks['whatsapp'])
                    <a href="{{ $dueLinks['whatsapp'] }}" target="_blank" rel="noopener" class="lp-btn lp-btn--wa lp-btn--sm" title="Send WhatsApp reminder"><x-lb-brand name="whatsapp" />Remind</a>
                    @endif
                    @if($dueLinks['sms'])
                    <a href="{{ $dueLinks['sms'] }}" class="lp-btn lp-btn--sm" title="Send SMS reminder"><x-lb-icon name="message-square-text" />{{ $dueLinks['whatsapp'] ? 'SMS' : 'Remind' }}</a>
                    @endif
                    @endif
                    @if(!empty($member['transaction_id']))
                    <a href="javascript:void(0)" data-id="{{ $member['id'] }}" class="lp-btn lp-btn--sm settlement-learner">Collect</a>
                    @endif
                </div>
            </article>
            @endforeach
        </div>
        @else
        <div class="lp-empty lpd-empty-due"><span><x-lb-icon name="badge-check" /></span><b>No payment due</b>Every member is fully paid. Nice work!</div>
        @endif
    </section>

    @if($lpShowExpire || $lpShowExtend)
    <div class="lpd-two">
        @if($lpShowExpire)
        <section class="lpd-card lpd-list">
            <div class="lpd-card-h">
                <h3><x-lb-icon name="alarm-clock" class="lpd-h-ic lpd-h-ic--warn" />Seats about to expire <span class="lp-count">{{ $renewSeats->count() }}</span></h3>
                <a href="{{ route('learners', ['status' => 'about_to_expire']) }}" class="lp-link">View all<x-lb-icon name="chevron-right" /></a>
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
                    <span class="lpd-chip {{ $d <= 2 ? 'lpd-chip--bad' : 'lpd-chip--warn' }}">{{ $d <= 0 ? 'Today' : 'In ' . $d . ' day' . ($d > 1 ? 's' : '') }}</span>
                    <span class="lpd-row-acts">
                        <a class="lp-round lp-round--wa" target="_blank" rel="noopener" title="Send reminder" aria-label="Send WhatsApp reminder"
                            href="https://wa.me/{{ $value->mobile }}?text={{ rawurlencode(appendPayLink("Dear {$value->name}(Seat No-{$value->seat_no}),\n\nYour plan expired on " . changeFormate($value['plan_end_date']) . ".\n\nPlease renew it as soon as possible to continue uninterrupted access to your library seat.\n\nFor help, feel free to contact our support team.\n\n– Team " . getCurrentBranchName(), learnerPayLink($value->learner_id, 'renew'), 'renew')) }}"><x-lb-brand name="whatsapp" /></a>
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
        <section class="lpd-card lpd-list">
            <div class="lpd-card-h">
                <h3><x-lb-icon name="hourglass" class="lpd-h-ic lpd-h-ic--amber" />Seats in extension <span class="lp-count">{{ $extend_sets->count() }}</span></h3>
                <a href="{{ route('learners', ['status' => 'extended']) }}" class="lp-link">View all<x-lb-icon name="chevron-right" /></a>
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
                    <span class="lpd-chip lpd-chip--warn">@if($lpExtendDays > 0)Day {{ min($past, $lpExtendDays) }} of {{ $lpExtendDays }}@else Extension @endif</span>
                    <span class="lpd-row-acts">
                        <a class="lp-round lp-round--wa" target="_blank" rel="noopener" title="Send reminder" aria-label="Send WhatsApp reminder"
                            href="https://wa.me/{{ $seat->mobile }}?text={{ rawurlencode(appendPayLink("{$seat->name}(Seat No-{$seat->seat_no}),\n\nYour plan expired on".changeFormate($seat->plan_end_date). ".\n\nPlease renew it as soon as possible to continue uninterrupted access to your library seat.\nYou are currently in the extension period — after this, your seat may be allotted to another learner.\n\nFor help, feel free to contact our support team.\n\n– Team " . getCurrentBranchName(), learnerPayLink($seat->learner_id, 'renew'), 'renew')) }}"><x-lb-brand name="whatsapp" /></a>
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

    {{-- ===== Online / QR bookings + activity ===== --}}
    @if(!in_array('23', toggleHideField()))
    <div class="lp-sec"><x-lb-icon name="qr-code" class="lp-sec-ic" /><h2>Bookings &amp; activity</h2><p>Requests from the QR code and the app</p></div>
    <div class="lpd-split dashboard-activity-qr-section">
        <section class="lpd-card lpd-qr">
            @php
                $bkList = $qrbookings ?? collect();
                $bkPaid = $bkList->filter(fn ($b) => !empty($b->payment_screenshot))->count();
                $bkTypes = ['qr_seat_book' => ['QR booking', 'qr-code'], 'learner_book' => ['App booking', 'smartphone'], 'qr_renew' => ['Renewal', 'refresh-cw'], 'qr_due_payment' => ['Dues', 'indian-rupee'], 'demo-bookings' => ['Demo', 'calendar-clock']];
            @endphp
            <div class="lpd-card-h">
                <h3><x-lb-icon name="smartphone" class="lpd-h-ic" />Online / QR bookings <span class="lp-count">{{ $bkList->count() }}</span></h3>
                @can('has-permission', 'QR Seat Booking')
                @if($bkList->count() > 0)
                <div class="lpd-bk-seg" role="tablist" aria-label="Filter bookings">
                    <button type="button" class="is-on" data-bk="all">All</button>
                    <button type="button" data-bk="paid">Paid <i>{{ $bkPaid }}</i></button>
                    <button type="button" data-bk="unpaid">Unpaid <i>{{ $bkList->count() - $bkPaid }}</i></button>
                </div>
                @endif
                @endcan
            </div>
            <div class="lpd-qr-body" id="requests">
                @can('has-permission', 'QR Seat Booking')
                @if($bkList->count() > 0)
                <ul class="lpd-bk" id="onlineRequest">
                    @foreach($bkList as $value)
                    @php
                        $bkHasShot = !empty($value->payment_screenshot);
                        $bkOnline = $value->payment_mode === 'online';
                        [$bkTypeLabel, $bkTypeIcon] = $bkTypes[$value->type] ?? ['Booking', 'armchair'];
                        $bkPhone = preg_replace('/\D/', '', (string) $value->mobile);
                        $bkPay = $bkHasShot ? ['ok', 'badge-check', 'Paid · see proof']
                            : ($bkOnline ? ['bad', 'circle-alert', 'Payment not received'] : ['warn', 'banknote', 'Will pay at desk']);
                        $bkStart = $value->plan_start_date ? \Carbon\Carbon::parse($value->plan_start_date) : null;
                        // renewals and pending-fee payments (pay link, docs/QR_PAY_LINK.md) get their own look
                        $bkKind = $value->type === 'qr_renew' ? 'renew' : ($value->type === 'qr_due_payment' ? 'due' : null);
                        $bkEnd = ($bkKind === 'renew' && $value->plan_end_date) ? \Carbon\Carbon::parse($value->plan_end_date) : null;
                    @endphp
                    <li class="lpd-bk-row is-{{ $bkPay[0] }}{{ $bkKind ? ' is-kind-' . $bkKind : '' }}" data-bk-paid="{{ $bkHasShot ? '1' : '0' }}">
                        <div class="lpd-bk-who">
                            <span class="lp-face {{ $lpTone($value->name) }}">{{ $lpInitials($value->name) }}</span>
                            <div class="lpd-bk-id">
                                <div class="lpd-bk-name">
                                    <b title="{{ $value->name }}">{{ $value->name }}</b>
                                    <span class="lpd-bk-type{{ $bkKind ? ' is-' . $bkKind : '' }}"><x-lb-icon :name="$bkTypeIcon" />{{ $bkTypeLabel }}</span>
                                </div>
                                <small>
                                    @if($bkPhone)<a href="tel:+91{{ $bkPhone }}" class="noLoader">+91 {{ $bkPhone }}</a>@else No number @endif
                                    @if($value->created_at)<span aria-hidden="true">·</span><span title="{{ $value->created_at->format('d M Y, h:i A') }}">{{ $value->created_at->diffForHumans() }}</span>@endif
                                </small>
                            </div>
                        </div>

                        <ul class="lpd-bk-meta">
                            @if($bkKind === 'due')
                            <li><x-lb-icon name="indian-rupee" /><b>{{ number_format((float) ($value->total_amount ?? 0)) }}</b>&nbsp;paid for dues</li>
                            <li><x-lb-icon name="clock-3" />{{ $value->planType->name ?? 'Shift' }}</li>
                            @else
                            <li><x-lb-icon name="armchair" />{{ $value->seat_no ? 'Seat ' . $lpSeatLabel($value->seat_no) : 'General' }}</li>
                            <li><x-lb-icon name="clock-3" />{{ $value->planType->name ?? 'Shift' }}</li>
                            <li><x-lb-icon name="indian-rupee" /><b>{{ number_format((float) ($value->total_amount ?? 0)) }}</b></li>
                            @if($bkKind === 'renew' && $bkStart && $bkEnd)
                            <li class="is-renew-span"><x-lb-icon name="refresh-cw" />{{ $bkStart->format('d M') }} – {{ $bkEnd->format('d M') }}</li>
                            @elseif($bkStart)<li><x-lb-icon name="calendar" />{{ $bkStart->isToday() ? 'From today' : 'From ' . $bkStart->format('d M') }}</li>@endif
                            @endif
                        </ul>

                        <div class="lpd-bk-end">
                            @if($bkHasShot)
                            <a href="{{ asset($value->payment_screenshot) }}" target="_blank" rel="noopener" class="lpd-bk-pay is-ok noLoader"><x-lb-icon name="{{ $bkPay[1] }}" />{{ $bkPay[2] }}</a>
                            @else
                            <span class="lpd-bk-pay is-{{ $bkPay[0] }}"><x-lb-icon name="{{ $bkPay[1] }}" />{{ $bkPay[2] }}</span>
                            @endif

                            <div class="lpd-bk-acts">
                                @if($bkKind === 'due')
                                {{-- pending fee: check the screenshot, keep / correct the amount received, approve --}}
                                <form action="{{ route('booking.due.approve', $value->id) }}" method="POST" class="approve-form lpd-bk-due" data-name="{{ $value->name }}">
                                    @csrf
                                    <label class="lpd-bk-amt" title="Amount received"><span>₹</span><input type="number" name="amount" min="1" step="0.01" inputmode="decimal" value="{{ rtrim(rtrim(number_format((float) $value->total_amount, 2, '.', ''), '0'), '.') }}" aria-label="Amount received" required></label>
                                    <button type="submit" class="lp-btn lp-btn--sm lpd-bk-go noLoader"><x-lb-icon name="check" />Approve</button>
                                </form>
                                @elseif($bkHasShot && $bkOnline)
                                <form action="{{ route('booking.details.approve') }}" method="POST" class="approve-form">
                                    @csrf
                                    <input type="hidden" name="booking_id" value="{{ $value->id }}">
                                    <input type="hidden" name="direct_validate" value="1">
                                    <button type="submit" class="lp-btn lp-btn--sm lpd-bk-go noLoader"><x-lb-icon name="check" />Approve</button>
                                </form>
                                <a href="{{ route('booking.details', $value->id) }}" class="lp-round lp-round--ghost" title="View details" aria-label="View details"><x-lb-icon name="eye" /></a>
                                @else
                                <a href="{{ route('booking.details', $value->id) }}" class="lp-btn lp-btn--sm lpd-bk-go"><x-lb-icon name="eye" />Review</a>
                                @endif
                                @if($bkPhone)
                                <a href="https://wa.me/91{{ $bkPhone }}" target="_blank" rel="noopener" class="lp-round lp-round--ghost lpd-bk-wa noLoader" title="WhatsApp {{ $value->name }}" aria-label="WhatsApp {{ $value->name }}"><x-lb-brand name="whatsapp" /></a>
                                @endif
                                <a href="javascript:void(0)" class="lp-round lp-round--ghost lpd-del delete-booking" data-id="{{ $value->id }}" title="Delete booking" aria-label="Delete booking"><x-lb-icon name="trash" /></a>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
                <div class="lp-empty lpd-fill lpd-bk-none" hidden><span><x-lb-icon name="inbox" /></span><b>Nothing here</b>No booking matches this filter.</div>
                @else
                <div class="lp-empty lpd-fill"><span><x-lb-icon name="qr-code" /></span><b>No online bookings yet</b>Bookings from your QR code and the app will show up here.</div>
                @endif
                @else
                <div class="lp-empty lpd-fill"><span><x-lb-icon name="lock" /></span><b>No access</b>You don't have permission to view this.</div>
                @endcan
            </div>
        </section>

        @can('has-permission', 'Recent Activity')
        <section class="lpd-card lpd-act">
            <div class="lpd-card-h">
                <h3><x-lb-icon name="activity" class="lpd-h-ic" />Recent activity</h3>
                <a href="{{ route('activities.all') }}" class="lp-link viewall">View all<x-lb-icon name="chevron-right" /></a>
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
                    <div class="lp-empty lpd-fill"><span><x-lb-icon name="activity" /></span><b>No activity yet</b>Renewals, swaps and other changes will appear here.</div>
                @endif
            </div>
        </section>
        @endcan
    </div>
    @endif
    {{-- ===== Shifts, fees & plan ===== --}}
    <div class="lp-sec"><x-lb-icon name="armchair" class="lp-sec-ic" /><h2>Seats, fees &amp; plan</h2><p>Free seats right now, fee trend and your Libraro plan</p></div>
    <div class="lpd-ogrid">
        @if($lpHasBranch)
        @can('has-permission', 'Avaialble Seats List')
        <section class="lpd-card lpd-shifts lpd-seats">
            @php
                // Seats with at least one free shift (same data as the old "Available Seats" list)
                $lpFreeSeats = collect($available_seats ?? [])->filter(fn ($st) => count($st['available_plan_types']) > 0)->values();
                $lpAllShifts = count($lpShiftFree);
                $lpShiftChip = function ($pt) use ($lpShiftMeta, $lpTime) {
                    $m = $lpShiftMeta->get($pt['id']);
                    if ($m && $m->start_time && $m->end_time && $m->start_time !== $m->end_time) return $lpTime($m->start_time) . '–' . $lpTime($m->end_time);
                    return $pt['name'];
                };
            @endphp
            <div class="lpd-card-h">
                <h3><x-lb-icon name="armchair" class="lpd-h-ic" />Available seats <span class="lp-count">{{ $lpFreeSeats->count() }}</span></h3>
                <a href="{{ route('seats') }}" class="lp-link">Seat map<x-lb-icon name="chevron-right" /></a>
            </div>
            @if(getAvailableHoursSum() >= 0 && $lpFreeSeats->count())
            @if($lpAllShifts > 1)
            <div class="lpd-seat-f" role="tablist" aria-label="Filter seats by shift">
                <button type="button" class="is-on" data-seat-f="">All<i>{{ $lpFreeSeats->count() }}</i></button>
                @foreach($lpShiftFree as $pid => $sh)
                <button type="button" data-seat-f="{{ $pid }}" title="{{ $sh['name'] }}">{{ $sh['name'] }}<i>{{ $sh['free'] }}</i></button>
                @endforeach
            </div>
            @endif
            <ul class="lpd-seat-list">
                @foreach($lpFreeSeats as $st)
                @php
                    $stTypes = collect($st['available_plan_types']);
                    $stAll = $lpAllShifts > 1 && $stTypes->count() >= $lpAllShifts;
                @endphp
                <li data-seat-shifts=",{{ $stTypes->pluck('id')->implode(',') }},">
                    @php [$stNo, $stFloor] = $lpSeatParts($st['seat_no']); @endphp
                    <span class="lpd-seat-no {{ $stFloor ? 'has-floor' : '' }}" title="Seat {{ $lpSeatLabel($st['seat_no']) }}">
                        <x-lb-icon name="armchair" class="lpd-seat-ic" />
                        <b>{{ $stNo }}</b>
                        @if($stFloor)<em>{{ $stFloor }}</em>@endif
                    </span>
                    <div class="lpd-seat-t">
                        @if($stAll)
                        <span class="lpd-seat-all"><x-lb-icon name="circle-check" />All shifts free</span>
                        @else
                        <div class="lpd-seat-chips">
                            @foreach($stTypes as $pt)
                            <span title="{{ $pt['name'] }}">{{ $lpShiftChip($pt) }}</span>
                            @endforeach
                        </div>
                        @endif
                        <small>{{ $stTypes->count() }} of {{ max($lpAllShifts, $stTypes->count()) }} {{ \Illuminate\Support\Str::plural('shift', max($lpAllShifts, $stTypes->count())) }} free</small>
                    </div>
                    <a href="javascript:;" class="lp-btn lp-btn--ghost lp-btn--xs first_popup" data-id="{{ $st['seat_id'] }}" data-seat_no="{{ $st['seat_no'] }}" @if($stTypes->count() === 1) data-plan_type_id="{{ $stTypes->first()['id'] }}" data-one="{{ $stTypes->first()['id'] }}" @endif @cannot('has-permission', 'Book Seat') disabled @endcannot>Book</a>
                </li>
                @endforeach
            </ul>
            <div class="lp-empty lpd-fill lpd-seat-none" hidden><span><x-lb-icon name="armchair" /></span><b>No free seat</b>This shift is full on every seat.</div>
            @else
            <div class="lp-empty lpd-fill">
                <span><x-lb-icon name="party-popper" /></span>
                <b>All seats are booked</b>
                Every premium seat is taken. You can still <a href="javascript:;" class="noseat_popup">book a general seat</a>.
            </div>
            @endif
        </section>
        @endcan
        @endif

        @if($feesSeries && ($lpMoneyToday || $lpMoneyMonth))
        <section class="lpd-card lpd-fees" id="lpdFees">
            <div class="lpd-card-h">
                <h3><x-lb-icon name="chart-column" class="lpd-h-ic" />Fees collected</h3>
                <div class="lp-seg lpd-seg-sm" role="tablist">
                    <button type="button" data-fees="Daily" class="is-on">Daily</button>
                    <button type="button" data-fees="Weekly">Weekly</button>
                    <button type="button" data-fees="Monthly">Monthly</button>
                </div>
            </div>
            <div class="lpd-fees-sum"><b data-fees-total>₹0</b><span class="lpd-delta" data-fees-delta></span><small data-fees-sub></small></div>
            <div class="lpd-fees-bars" data-fees-bars></div>
        </section>
        @endif

        @can('has-permission', 'Show Plan Info')
        @php
            $lpDaysLeft = (int) ($librarydiffInDays ?? 0);
            $lpPlanSpan = (isset($check->month) && $check->month == 12) ? 365 : 30;
            $lpPlanPct = max(0, min(100, round($lpDaysLeft / max(1, $lpPlanSpan) * 100)));
            $lpUseUpgrade = isset($librarydiffInDays) && $librarydiffInDays <= 5 && !$is_renew && $isProfile;
            $lpBranchCount = \App\Models\Branch::where('library_id', getLibraryId())->count();
        @endphp
        <section class="lpd-card lpd-plan">
            <div class="lpd-plan-glow"></div>
            <div class="lpd-plan-h">
                <span class="lpd-plan-ring" style="--p: {{ $lpPlanPct }}"><span><b>{{ max(0, $lpDaysLeft) }}</b><small>days</small></span></span>
                <div>
                    <span class="lpd-plan-k"><x-lb-icon name="crown" />YOUR PLAN</span>
                    <h3>{{ $plan?->name ?? 'Basic Plan' }}@if(isset($check) && $check) · {{ $lpPlanSpan == 365 ? 'Yearly' : 'Monthly' }}@endif</h3>
                    <small>@if(isset($check) && $check)Renews {{ \Carbon\Carbon::parse($check->end_date)->format('j M Y') }}@else Free / Trial @endif</small>
                </div>
            </div>
            <div class="lpd-plan-meters">
                <a href="{{ route('seats') }}"><span><span>Seats</span><b>{{ $lpBooked }} / {{ $lpTotal }}</b></span><i><em style="width: {{ $lpPct }}%"></em></i></a>
                <a href="{{ route('library.myplan') }}"><span><span>Branches</span><b>{{ $lpBranchCount }}</b></span><i><em class="is-violet" style="width: {{ min(100, $lpBranchCount * 25) }}%"></em></i></a>
                <a href="{{ route('library.myplan') }}"><span><span>Plan features</span><b>{{ $features_count ?? 0 }}</b></span><i><em class="is-ok" style="width: {{ min(100, ($features_count ?? 0) * 3) }}%"></em></i></a>
                <a href="{{ route('library.transaction') }}"><span><span>Plan price</span><b>@if(isset($check) && $check)₹{{ (int)$check->amount == $check->amount ? number_format($check->amount) : number_format($check->amount, 2) }} / {{ $lpPlanSpan == 365 ? 'yr' : 'mo' }}@else Free @endif</b></span></a>
            </div>
            <div class="lpd-plan-acts">
                <a href="{{ $lpUseUpgrade ? route('subscriptions.choosePlan') : route('library.myplan') }}" class="lp-btn"><x-lb-icon name="refresh-cw" />{{ $lpUseUpgrade ? 'Renew' : 'Manage' }}</a>
                <a href="{{ route('subscriptions.choosePlan', ['action' => 'upgrade']) }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="circle-arrow-up" />Upgrade</a>
            </div>
        </section>
        @endcan
    </div>
    </div>


    <div class="lpd-pane lrp" id="lpdPane-reports" role="tabpanel" aria-labelledby="lpdTab-reports" data-lpd-pane="reports" hidden>
    {{-- ===== Monthly report: one story, top to bottom — seats → what needs attention → payments → activity → shifts ===== --}}
    @php
        $currentYear = date('Y');
        $currentMonth = date('m');
        $canBook_1 = $lpCan('Till Today Bookings');
        $canBook_2 = $lpCan('This Month Bookings');
        // [permission, label, element id, list type, icon, tone, plain-language hint]
        $lpOps = [
            ['Expired in 5 Days Count', 'Expiring in 5 days', 'expiredInFive', 'expired_in_five', 'calendar-x', 'bad', 'Plans ending soon — remind them to renew'],
            ['Extended Seats Count', 'In grace period', 'extended_seats', 'extended_seat', 'clock-alert', 'warn', 'Plan ended, seat still held — collect the fee'],
            ['Online Paid Count', 'Online', 'onlinePaid', 'online_paid', 'smartphone', 'acc', 'UPI, card or net banking'],
            ['Offline Paid Count', 'Cash', 'offlinePaid', 'offline_paid', 'banknote', 'ok', 'Paid at the front desk'],
            ['Pay Later Count', 'Pay later', 'otherPaid', 'other_paid', 'timer', 'warn', 'Booked now, will pay later'],
            ['Renew Seat Count', 'Renewals', 'renew_seat', 'renew_seat', 'refresh-cw', 'ok', 'Members who continued'],
            ['Upgrade Seats Count', 'Upgrades', 'learnerUpgrade', 'learnerUpgrade', 'trending-up', 'ok', 'Moved to a bigger plan'],
            ['Change Plan Count', 'Plan changes', 'change_plan_seat', 'change_plan_seat', 'sliders-horizontal', 'acc', 'Switched plan or shift'],
            ['Swap Seats Count', 'Seat swaps', 'swap_seat', 'swap_seat', 'arrow-right-left', 'acc', 'Moved to another seat'],
            ['Reactive Seats Count', 'Came back', 'reactive', 'reactive_seat', 'user-check', 'ok', 'Old members who rejoined'],
            ['Close Seat Count', 'Closed seats', 'close_seat', 'close_seat', 'door-closed', 'mut', 'Left before the plan ended'],
            ['Delete Seat Count', 'Deleted', 'delete_seat', 'delete_seat', 'trash', 'bad', 'Bookings removed'],
        ];
        $showOps = !in_array('18', toggleHideField());
        $opsByKey = collect($lpOps)->filter(fn ($o) => $showOps && $lpCan($o[0]))->keyBy(2);
        $rpAttention = $opsByKey->only(['expiredInFive', 'extended_seats']);
        $rpPay = $opsByKey->only(['onlinePaid', 'offlinePaid', 'otherPaid']);
        // keeping members (what earns next month) before the other changes
        $rpRetain = $opsByKey->only(['renew_seat', 'reactive', 'learnerUpgrade', 'close_seat']);
        $rpActivity = $opsByKey->only(['change_plan_seat', 'swap_seat', 'delete_seat']);
        $rpBookUrl = fn ($type) => route('library.transaction.view', ['type' => $type]);
        $rpStep = 0;
    @endphp

    <section class="lrp-head">
        <span class="lrp-head-ic"><x-lb-icon name="chart-no-axes-combined" /></span>
        <div class="lrp-head-t">
            <h2>Monthly report · <span data-rp-month>{{ now()->format('F Y') }}</span></h2>
            <p>Everything that happened in your library this month. Tap any number to see the members behind it.</p>
        </div>
        @can('has-permission', 'Year and Month Filter')
        <div class="lrp-pick" id="filter">
            <label><span>Year</span>
                <select id="datayaer" class="lp-sel" aria-label="Year">
                    <option value="">Year</option>
                    @foreach($months as $year => $monthData)
                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </label>
            <label><span>Month</span>
                <select id="dataFilter" class="lp-sel" aria-label="Month">
                    <option value="">Month</option>
                    @if(isset($months[$currentYear]))
                    @foreach($months[$currentYear] as $monthNumber => $monthName)
                    <option value="{{ $monthNumber }}" {{ $monthNumber == $currentMonth ? 'selected' : '' }}>{{ $monthName }}</option>
                    @endforeach
                    @endif
                </select>
            </label>
        </div>
        @endcan
    </section>

    {{-- ===== Month at a glance: four numbers that answer "how did the library do?" ===== --}}
    <div class="lrp-kpis" aria-label="Month at a glance">
        @if($lpMoneyMonth)
        <a href="{{ $rpBookUrl('monthly_collection') }}" class="lrp-kpi viewall">
            <span class="lrp-kpi-ic t-ok"><x-lb-icon name="indian-rupee" /></span>
            <small>Collected</small><b data-kpi="collected">₹0</b>
            <em data-kpi="collected-s">—</em>
        </a>
        @endif
        <a href="{{ route('seats') }}" class="lrp-kpi">
            <span class="lrp-kpi-ic t-acc"><x-lb-icon name="armchair" /></span>
            <small>Seats filled</small><b data-kpi="fill">0%</b>
            <span class="lrp-kpi-bar"><i data-kpi="fill-bar"></i></span>
            <em data-kpi="fill-s">—</em>
        </a>
        <a href="{{ route('learners.list.view', ['type' => 'thisbooking_slot']) }}" class="lrp-kpi viewall">
            <span class="lrp-kpi-ic t-violet"><x-lb-icon name="trending-up" /></span>
            <small>Net growth</small><b data-kpi="growth">0</b>
            <em data-kpi="growth-s">—</em>
        </a>
        <a href="{{ route('learners.list.view', ['type' => 'renew_seat']) }}" class="lrp-kpi viewall">
            <span class="lrp-kpi-ic t-warn"><x-lb-icon name="refresh-cw" /></span>
            <small>Renewal rate</small><b data-kpi="renew">—</b>
            <em data-kpi="renew-s">—</em>
        </a>
    </div>

    {{-- ===== What this means: the few things worth doing, most important first ===== --}}
    <section class="lrp-card lrp-ins" aria-labelledby="lrpInsT">
        <div class="lrp-card-h"><span class="lrp-ic t-acc"><x-lb-icon name="sparkles" /></span><div><b id="lrpInsT">What this means</b><small>Read from this month's numbers</small></div></div>
        <ul class="lrp-ins-list" data-ins
            data-u-five="{{ route('learners.list.view', ['type' => 'expired_in_five']) }}"
            data-u-grace="{{ route('learners.list.view', ['type' => 'extended_seat']) }}"
            data-u-ended="{{ route('learners.list.view', ['type' => 'expire_booking_slot']) }}"
            data-u-later="{{ route('learners.list.view', ['type' => 'other_paid']) }}"
            data-u-pending="{{ $rpBookUrl('monthly_pending') }}"
            data-u-seats="{{ route('seats') }}"
            data-money="{{ $lpMoneyMonth ? '1' : '0' }}"></ul>
    </section>

    @if($canBook_1 || $canBook_2)
    <h3 class="lrp-sec"><i>{{ ++$rpStep }}</i>Seats &amp; bookings<small>How full your library is</small></h3>
    <div class="lrp-two">
        @if($canBook_1)
        <section class="lrp-card">
            <div class="lrp-card-h"><span class="lrp-ic t-acc"><x-lb-icon name="layers-2" /></span><div><b>All bookings so far</b><small>Since you started on Libraro</small></div></div>
            <a href="{{ route('learners.list.view', ['type' => 'total_booking']) }}" class="lrp-big viewall"><b id="totalBookings">0</b><small>bookings</small><x-lb-icon name="arrow-up-right" /></a>
            <div class="lrp-bar" data-rp-bar="life"><i class="t-ok"></i><i class="t-bad"></i></div>
            <div class="lrp-legend">
                <a href="{{ route('learners.list.view', ['type' => 'active_booking']) }}" class="viewall"><i class="t-ok"></i>Still studying<b id="active_booking">0</b><x-lb-icon name="arrow-up-right" class="lrp-go" /></a>
                <a href="{{ route('learners.list.view', ['type' => 'expired_seats']) }}" class="viewall"><i class="t-bad"></i>Plan ended<b id="expiredSeats">0</b><x-lb-icon name="arrow-up-right" class="lrp-go" /></a>
            </div>
            <p class="lrp-say" data-rp-say="life"></p>
        </section>
        @endif
        @if($canBook_2)
        <section class="lrp-card">
            <div class="lrp-card-h"><span class="lrp-ic t-ok"><x-lb-icon name="calendar-days" /></span><div><b>Bookings in <span data-rp-month>{{ now()->format('F Y') }}</span></b><small>Started or ended this month</small></div></div>
            <a href="{{ route('learners.list.view', ['type' => 'thisbooking_slot']) }}" class="lrp-big viewall"><b id="thismonth_total_book">0</b><small>bookings</small><x-lb-icon name="arrow-up-right" /></a>
            <div class="lrp-bar" data-rp-bar="month"><i class="t-ok"></i><i class="t-bad"></i></div>
            <div class="lrp-legend">
                <a href="{{ route('learners.list.view', ['type' => 'booing_slot']) }}" class="viewall"><i class="t-ok"></i>New / active<b id="month_total_active_book">0</b><x-lb-icon name="arrow-up-right" class="lrp-go" /></a>
                @can('has-permission', 'Expired Seats')
                <a href="{{ route('learners.list.view', ['type' => 'expire_booking_slot']) }}" class="viewall"><i class="t-bad"></i>Ended this month<b id="month_all_expired">0</b><x-lb-icon name="arrow-up-right" class="lrp-go" /></a>
                @endcan
            </div>
            <p class="lrp-say" data-rp-say="month"></p>
        </section>
        @endif
    </div>
    @endif

    @if($rpAttention->count())
    <h3 class="lrp-sec"><i>{{ ++$rpStep }}</i>Needs your attention<small>Act on these to keep seats filled</small></h3>
    <div class="lrp-alerts">
        @foreach($rpAttention as $o)
        <a href="{{ route('learners.list.view', ['type' => $o[3]]) }}" class="lrp-alert t-{{ $o[5] }} viewall">
            <span class="lrp-ic"><x-lb-icon :name="$o[4]" /></span>
            <span class="lrp-alert-t"><small>{{ $o[1] }}</small><b id="{{ $o[2] }}">0</b><em>{{ $o[6] }}</em></span>
            <span class="lrp-alert-go">See list<x-lb-icon name="arrow-right" /></span><x-lb-icon name="arrow-up-right" class="lrp-go" />
        </a>
        @endforeach
    </div>
    <p class="lrp-note"><x-lb-icon name="info" />These two counts include members from earlier months too.</p>
    @endif

    @if($rpRetain->count())
    <h3 class="lrp-sec"><i>{{ ++$rpStep }}</i>Keeping members<small>Who stayed, came back or left this month</small></h3>
    <div class="lrp-acts">
        @foreach($rpRetain as $o)
        <a href="{{ route('learners.list.view', ['type' => $o[3]]) }}" class="lrp-act t-{{ $o[5] }} viewall" title="{{ $o[6] }}">
            <span class="lrp-ic"><x-lb-icon :name="$o[4]" /></span>
            <span class="lrp-act-t"><b id="{{ $o[2] }}">0</b><small>{{ $o[1] }}</small><em>{{ $o[6] }}</em></span>
            <x-lb-icon name="arrow-up-right" class="lrp-go" />
        </a>
        @endforeach
    </div>
    @endif

    @if($rpPay->count())
    <h3 class="lrp-sec"><i>{{ ++$rpStep }}</i>How members paid<small>Bookings this month by payment mode</small></h3>
    <section class="lrp-card lrp-pay">
        <div class="lrp-bar lrp-bar--lg" data-rp-bar="pay">
            @foreach($rpPay as $o)<i class="t-{{ $o[5] }}" data-k="{{ $o[2] }}"></i>@endforeach
        </div>
        <div class="lrp-pay-grid">
            @foreach($rpPay as $o)
            <a href="{{ route('learners.list.view', ['type' => $o[3]]) }}" class="lrp-pay-i t-{{ $o[5] }} viewall">
                <span class="lrp-ic"><x-lb-icon :name="$o[4]" /></span>
                <span><small>{{ $o[1] }}</small><b id="{{ $o[2] }}" data-rp-pay>0</b><em data-rp-pct>0%</em></span><x-lb-icon name="arrow-up-right" class="lrp-go" />
            </a>
            @endforeach
        </div>
    </section>
    @endif

    @if($rpActivity->count())
    <h3 class="lrp-sec"><i>{{ ++$rpStep }}</i>Other changes<small>Plan, shift and seat changes this month</small></h3>
    <div class="lrp-acts">
        @foreach($rpActivity as $o)
        <a href="{{ route('learners.list.view', ['type' => $o[3]]) }}" class="lrp-act t-{{ $o[5] }} viewall" title="{{ $o[6] }}">
            <span class="lrp-ic"><x-lb-icon :name="$o[4]" /></span>
            <span class="lrp-act-t"><b id="{{ $o[2] }}">0</b><small>{{ $o[1] }}</small><em>{{ $o[6] }}</em></span>
            <x-lb-icon name="arrow-up-right" class="lrp-go" />
        </a>
        @endforeach
    </div>
    @endif


    @php $canShift = \Illuminate\Support\Facades\Gate::allows('has-permission', 'Plan wise count'); $canCharts = \Illuminate\Support\Facades\Gate::allows('has-permission', 'Library Analytics'); @endphp
    @if($canShift || $canCharts)
    <h3 class="lrp-sec"><i>{{ ++$rpStep }}</i>Shifts<small>Which shifts are busiest and earn the most</small></h3>
    @endif

    @if($canShift)
    <section class="lrp-card lpd-pw" id="planWiseHeading">
        <div class="lrp-card-h"><span class="lrp-ic t-acc"><x-lb-icon name="clock-3" /></span><div><b>Bookings by shift</b><small>Longest bar = most popular shift</small></div></div>
        <div class="lrp-shifts planwisecount"></div>
        <div class="lp-empty lpd-pw-empty" style="display: none;"><span><x-lb-icon name="layers-2" /></span>No bookings in this month</div>
    </section>
    @endif

    @if($canCharts)
    <div class="lpd-charts">
        <section class="lpd-card lpd-chart">
            <div class="lpd-card-h">
                <h3>Revenue by shift</h3>
                <div id="revenueChartHeaderBadge" class="lpd-badges" style="display: none;"></div>
            </div>
            <div class="lpd-chart-box">
                <canvas id="revenueChart" style="display: none;"></canvas>
                <div class="lp-empty" style="display: none;" id="no-data2"><span><x-lb-icon name="chart-column" /></span><b>No revenue yet</b>No data for this month</div>
            </div>
        </section>
        <section class="lpd-card lpd-chart">
            <div class="lpd-card-h">
                <h3>Bookings by shift</h3>
                <div id="bookingChartHeaderBadge" class="lpd-badges" style="display: none;"></div>
            </div>
            <div class="lpd-chart-box">
                <canvas id="bookingCountChart" style="display: none;"></canvas>
                <div class="lp-empty" style="display: none;" id="no-data3"><span><x-lb-icon name="chart-pie" /></span><b>No bookings yet</b>No data for this month</div>
            </div>
        </section>
    </div>
    @endif
    </div>

    </div>
</div>
<!-- End library-dashboard-section -->

@if($live)
<div class="modal fade lpd-ps" id="lpdPunches" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" data-ps="body"></div>
    </div>
</div>
@endif

{{-- ===== Drawer: collection summary ===== --}}
@if(($lpMoneyToday || $lpMoneyMonth) && $collections)
<div class="offcanvas offcanvas-end lpd-drawer" tabindex="-1" id="lpdCollect" aria-labelledby="lpdCollectTitle">
    <div class="lpd-drawer-top lpd-drawer-top--pad">
        <div><h2 id="lpdCollectTitle">Collection summary</h2><small>{{ $lpBranchName }}</small></div>
        <button type="button" class="lpd-x2" data-bs-dismiss="offcanvas" aria-label="Close"><x-lb-icon name="x" /></button>
    </div>
    <div class="lpd-drawer-b lpd-drawer-b--pad">
        <div class="lp-seg lpd-seg-full" data-fs="tabs">
            <button type="button" data-fs-view="today" class="is-on">Today</button>
            <button type="button" data-fs-view="period">Last {{ $collections['period']['days'] }} days</button>
        </div>
        <div class="lpd-fs-hero">
            <small data-fs="range"></small>
            <b data-fs="total">₹0</b>
            <div class="lpd-fs-stats" data-fs="stats"></div>
        </div>
        <label class="lpd-search"><x-lb-icon name="search" /><input type="search" data-fs="q" placeholder="Search name, seat or receipt" autocomplete="off"></label>
        <div class="lpd-chips" data-fs="modes"></div>
        <div data-fs="list"></div>

    </div>
    <div class="lpd-drawer-f">
        <a href="https://wa.me/?text={{ rawurlencode('Collection today at ' . $lpBranchName . ': ' . $lpInr($collections['today']['total']) . ' (' . $collections['today']['count'] . ' payments). Last ' . $collections['period']['days'] . ' days: ' . $lpInr($collections['period']['total']) . '.') }}" target="_blank" rel="noopener" class="lp-btn lp-btn--ghost"><x-lb-brand name="whatsapp" />Share</a>
        <a href="{{ route('library.transaction.view', ['type' => 'today_collection']) }}" class="lp-btn"><x-lb-icon name="receipt-text" />All transactions</a>
    </div>
</div>
@endif

@php
    $lpdData = [
        'sprite' => asset('public/img/lb-icons.svg') . '?v=' . filemtime(public_path('img/lb-icons.svg')),
        'liveUrl' => $live ? route('library.dashboard.live') : null,
        'live' => $live,
        'collections' => ($lpMoneyToday || $lpMoneyMonth) ? $collections : null,
        'fees' => ($lpMoneyToday || $lpMoneyMonth) ? $feesSeries : null,
    ];
@endphp
<script type="application/json" id="lpdData">@json($lpdData)</script>
<script src="{{ asset('public/js/lb-dash.js') }}?v={{ @filemtime(public_path('js/lb-dash.js')) }}" defer></script>

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
        var moneyIds = ['total_income', 'other_total_income', 'total_expense', 'total_refund', 'total_pending', 'total_balance', 'total_advance'];

        // Horizontal scroll buttons for "Payment due"
        $(document).on('click', '.lpd-sc', function () {
            var track = $(this).closest('.lpd-due').find('.lpd-due-track')[0];
            if (track) track.scrollBy({ left: Number($(this).data('sc')) * Math.max(260, track.clientWidth * 0.8), behavior: 'smooth' });
        });

        // Fetch initial data based on the default filter (month)
        var initialYear = $('#datayaer').val();
        var initialMonth = $('#dataFilter').val();
        $('.js-money, .lrp b[id]').addClass('lp-skel');
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
                        $('.lpd-pw-empty').css('display', 'flex');
                    } else {
                        $('.lpd-pw-empty').hide();
                        $pw.css('display', '');
                        var pwMax = Math.max.apply(null, planWiseBookings.map(function (b) { return Number(b.booking) || 0; }).concat([1]));
                        var pwSum = planWiseBookings.reduce(function (t, b) { return t + (Number(b.booking) || 0); }, 0) || 1;
                        planWiseBookings
                            .slice().sort(function (a, b) { return (Number(b.booking) || 0) - (Number(a.booking) || 0); })
                            .forEach(function (booking, i) {
                                var n = Number(booking.booking) || 0;
                                var row = $('<div class="lrp-shift"><span class="lrp-shift-n"></span><span class="lrp-shift-bar"><i></i></span><b></b><small></small></div>');
                                row.toggleClass('is-top', i === 0 && n > 0);
                                row.find('.lrp-shift-n').text(booking.plan_type_name);
                                row.find('i').css('width', Math.round(n / pwMax * 100) + '%');
                                row.find('b').text(n);
                                row.find('small').text(Math.round(n / pwSum * 100) + '%');
                                $pw.append(row);
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

        var LRP_SPRITE = @json(asset('public/img/lb-icons.svg'));
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
            if (highlights.available_seat !== undefined) setNum('available_seat', highlights.available_seat);
            if (highlights.expired_seats !== undefined) setNum('expired_seat', highlights.expired_seats);
            var tot = Number(highlights.total_seat), bk = Number(highlights.booked_seat);
            if (tot > 0 && !isNaN(bk)) {
                var pct = Math.max(0, Math.min(100, Math.round(bk / tot * 100)));
                $('#lpdPct').text(pct + '%').toggleClass('is-up', pct >= 50).toggleClass('is-flat', pct < 50);
            }
            // money
            var money = {
                total_income: highlights.monthly_income, other_total_income: highlights.other_total_income, total_expense: highlights.monthly_expense,
                total_refund: highlights.monthly_refund, total_pending: highlights.monthly_pending, total_balance: highlights.monthly_balance,
                total_advance: highlights.monthly_advance
            };
            var mIn = (Number(money.total_income) || 0) + (Number(money.other_total_income) || 0), mOut = (Number(money.total_expense) || 0) + (Number(money.total_refund) || 0), mAll = Math.max(1, mIn + mOut);
            $('[data-book-flow] .is-in').css('width', Math.round(mIn / mAll * 100) + '%');
            $('[data-book-flow] .is-out').css('width', Math.round(mOut / mAll * 100) + '%');
            $('[data-book-in]').text('In ' + inr(mIn));
            $('[data-book-out]').text('Out ' + inr(mOut));
            var ySel = $('#datayaer').val(), mSel = $('#dataFilter option:selected');
            if (ySel && mSel.val()) $('[data-book-month]').text(mSel.text() + ' ' + ySel);
            moneyIds.forEach(function (id) {
                var txt = inr(money[id]);
                var el = document.getElementById(id); if (el) el.textContent = txt;
                $('[data-copy="' + id + '"]').text(txt);
            });
            reportStory(highlights);
        }

        // Monthly report: proportion bars + one plain sentence per card
        function reportStory(h) {
            var n = function (v) { return Number(v) || 0; };
            var bar = function (key, parts) {
                var total = parts.reduce(function (t, v) { return t + v; }, 0);
                $('[data-rp-bar="' + key + '"] > i').each(function (i) { $(this).css('width', (total ? parts[i] / total * 100 : 0) + '%'); });
                return total;
            };
            var act = n(h.active_booking), exp = n(h.expired_seats);
            var lifeTotal = bar('life', [act, exp]);
            $('[data-rp-say="life"]').text(lifeTotal ? Math.round(act / lifeTotal * 100) + '% of everyone who ever booked is still studying with you.' : 'No bookings yet.');
            var mNew = n(h.month_total_active_book), mEnd = n(h.month_all_expired);
            var mTotal = bar('month', [mNew, mEnd]);
            var diff = mNew - mEnd;
            $('[data-rp-say="month"]').text(!mTotal ? 'No bookings started or ended this month.' : (diff > 0 ? diff + ' more joined than left — your library grew this month.' : diff < 0 ? Math.abs(diff) + ' more left than joined — follow up on renewals.' : 'As many joined as left this month.'));
            var pay = { onlinePaid: n(h.online_paid), offlinePaid: n(h.offline_paid), otherPaid: n(h.other_paid) };
            var payTotal = 0;
            $('[data-rp-bar="pay"] > i').each(function () { payTotal += pay[this.getAttribute('data-k')] || 0; });
            $('[data-rp-bar="pay"] > i').each(function () { $(this).css('width', (payTotal ? (pay[this.getAttribute('data-k')] || 0) / payTotal * 100 : 0) + '%'); });
            $('.lrp-pay-i').each(function () { var v = pay[$(this).find('b[id]').attr('id')] || 0; $(this).find('[data-rp-pct]').text(payTotal ? Math.round(v / payTotal * 100) + '%' : '0%'); });
            var ySel = $('#datayaer').val(), mOpt = $('#dataFilter option:selected');
            if (ySel && mOpt.val()) $('[data-rp-month]').text(mOpt.text() + ' ' + ySel);
            $('.lrp-alert, .lrp-act').each(function () { $(this).toggleClass('is-zero', !n($(this).find('b[id]').text())); });
            reportGlance(h);
        }

        // Month at a glance + "What this means"
        function reportGlance(h) {
            var n = function (v) { return Number(v) || 0; }, inr0 = function (v) { return '₹' + Math.round(n(v)).toLocaleString('en-IN'); };
            var collected = n(h.monthly_income) + n(h.other_total_income), pending = n(h.monthly_pending), balance = n(h.monthly_balance);
            var total = n(h.total_seat), booked = n(h.booked_seat), fill = total ? Math.round(booked / total * 100) : 0;
            var joined = n(h.month_total_active_book), ended = n(h.month_all_expired), growth = joined - ended;
            var renewed = n(h.renew_seat), renewBase = renewed + ended, rate = renewBase ? Math.round(renewed / renewBase * 100) : null;
            var five = n(h.expired_in_five), grace = n(h.extended_seats);
            var payAll = n(h.online_paid) + n(h.offline_paid) + n(h.other_paid), later = n(h.other_paid), laterPct = payAll ? Math.round(later / payAll * 100) : 0;
            var k = function (key, v) { $('[data-kpi="' + key + '"]').text(v); };
            k('collected', inr0(collected)); k('collected-s', pending > 0 ? inr0(pending) + ' still due' : 'Balance ' + inr0(balance));
            k('fill', fill + '%'); $('[data-kpi="fill-bar"]').css('width', fill + '%'); k('fill-s', booked + ' of ' + total + ' seats · ' + Math.max(0, total - booked) + ' free');
            k('growth', (growth > 0 ? '+' : '') + growth); k('growth-s', joined + ' joined · ' + ended + ' ended');
            $('[data-kpi="growth"]').toggleClass('is-up', growth > 0).toggleClass('is-down', growth < 0);
            k('renew', rate === null ? '—' : rate + '%'); k('renew-s', rate === null ? 'No plan ended yet' : renewed + ' of ' + renewBase + ' renewed');
            $('[data-kpi="renew"]').toggleClass('is-up', rate !== null && rate >= 70).toggleClass('is-down', rate !== null && rate < 40);

            var $l = $('[data-ins]'), u = function (key) { return $l.attr('data-u-' + key); }, items = [];
            var add = function (tone, icon, text, href, cta) { items.push({ tone: tone, icon: icon, text: text, href: href, cta: cta }); };
            if (five > 0) add('bad', 'calendar-x', '<b>' + five + '</b> plan' + (five > 1 ? 's end' : ' ends') + ' in the next 5 days. A reminder today keeps the seat' + (five > 1 ? 's' : '') + ' filled.', u('five'), 'Remind');
            if (grace > 0) add('warn', 'clock-alert', '<b>' + grace + '</b> member' + (grace > 1 ? 's are' : ' is') + ' in the grace period: the plan ended but the seat is held. Collect the renewal fee.', u('grace'), 'Collect');
            if ($l.attr('data-money') === '1' && pending > 0) add('warn', 'indian-rupee', '<b>' + inr0(pending) + '</b> of this month\'s fees is still due.', u('pending'), 'See dues');
            if (rate !== null) add(rate >= 70 ? 'ok' : (rate >= 40 ? 'warn' : 'bad'), 'refresh-cw', rate >= 70 ? 'Strong retention: <b>' + rate + '%</b> of members whose plan ended renewed.' : '<b>' + rate + '%</b> renewal rate: ' + (renewBase - renewed) + ' member' + (renewBase - renewed > 1 ? 's' : '') + ' left without renewing. Call them and ask why.', u('ended'), 'See who');
            if (laterPct >= 30) add('warn', 'timer', '<b>' + laterPct + '%</b> of this month\'s bookings are on pay later. Ask for part payment at booking.', u('later'), 'See list');
            if (total && fill < 60) add('acc', 'armchair', '<b>' + (total - booked) + '</b> seats are empty (' + fill + '% filled). Share your booking QR or run a referral offer.', u('seats'), 'Seat map');
            else if (total && fill >= 90) add('ok', 'armchair', 'Almost full: <b>' + fill + '%</b> of seats are taken. Add a waiting list or a new shift.', u('seats'), 'Seat map');
            if (growth > 0 && items.length < 4) add('ok', 'trending-up', 'Your library grew by <b>' + growth + '</b> this month: more members joined than left.', null, null);
            if (!items.length) add('ok', 'circle-check', 'All good: nothing needs your attention this month.', null, null);
            var rank = { bad: 0, warn: 1, acc: 2, ok: 3 };
            items.sort(function (a, b) { return rank[a.tone] - rank[b.tone]; });
            $l.html(items.slice(0, 4).map(function (it) {
                return '<li class="t-' + it.tone + '"><span class="lrp-ins-ic"><svg class="lb-i" aria-hidden="true"><use href="' + LRP_SPRITE + '#i-' + it.icon + '"></use></svg></span><p>' + it.text + '</p>' +
                    (it.href ? '<a href="' + it.href + '" class="lrp-ins-go">' + it.cta + '</a>' : '') + '</li>';
            }).join(''));
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
    function lpdFont() { return "'Geist', system-ui, sans-serif"; }

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
            var palette = ['#1d3c86', '#2fa37a', '#eb881f', '#8f68cb', '#3a81d7', '#5b6b8c', '#34939f', '#bf2a82'];
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
        var emoji = document.getElementById('greetEmoji');
        if (emoji) emoji.textContent = h < 5 ? '🌙' : h < 12 ? '☀️' : h < 17 ? '🌤️' : h < 21 ? '🌇' : '🌙';
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
    // Available seats: filter by a free shift
    $(document).on('click', '.lpd-seat-f button', function () {
        var f = String($(this).data('seat-f') || ''), shown = 0;
        $(this).addClass('is-on').siblings().removeClass('is-on');
        $('.lpd-seat-list > li').each(function () {
            var ok = !f || $(this).attr('data-seat-shifts').indexOf(',' + f + ',') !== -1;
            this.hidden = !ok; if (ok) shown++;
            // booking from a filtered list starts on that shift
            var btn = $(this).find('.first_popup');
            var one = btn.attr('data-one');
            btn.removeData('plan_type_id');
            if (f) btn.attr('data-plan_type_id', f); else if (one) btn.attr('data-plan_type_id', one); else btn.removeAttr('data-plan_type_id');
        });
        $('.lpd-seat-none').prop('hidden', shown > 0);
    });
    // Online / QR bookings: All / Paid / Unpaid
    $(document).on('click', '.lpd-bk-seg button', function () {
        var f = $(this).data('bk'), shown = 0;
        $(this).addClass('is-on').siblings().removeClass('is-on');
        $('#onlineRequest .lpd-bk-row').each(function () {
            var paid = $(this).data('bk-paid') == 1, show = f === 'all' || (f === 'paid' ? paid : !paid);
            this.hidden = !show; if (show) shown++;
        });
        $('.lpd-bk-none').prop('hidden', shown > 0);
    });
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
            // pending-fee payment: say what will be recorded (docs/QR_PAY_LINK.md)
            let dueAmt = form.querySelector('.lpd-bk-due input[name="amount"]') || (form.classList.contains('lpd-bk-due') ? form.querySelector('input[name="amount"]') : null);
            Swal.fire({
                title: 'Are you sure?',
                text: dueAmt ? ('Record ₹' + dueAmt.value + ' received from ' + (form.dataset.name || 'this learner') + '? Check the amount in the screenshot first.') : "You are about to approve this booking.",
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
