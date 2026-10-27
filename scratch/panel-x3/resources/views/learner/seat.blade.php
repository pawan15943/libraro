@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/seat-module.css') }}?v={{ @filemtime(public_path('css/seat-module.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/motivational-banner.css') }}?v={{ @filemtime(public_path('css/motivational-banner.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-seatmap.css') }}?v={{ @filemtime(public_path('css/lb-seatmap.css')) }}">
@endpush

@section('content')

@php
use App\Models\Learner;
use App\Models\LearnerDetail;
use Carbon\Carbon;
$fullDayCount = 0;
$halfDayFirstHalfCount = 0;
$halfDaySecondHalfCount = 0;
$hourlyCount = 0;
$today = Carbon::today();

$allBranchPlanTypes = \App\Models\PlanType::where('branch_id', getCurrentBranch())
    ->whereNull('deleted_at')
    ->get();

// Shift colour + timing used on every seat card
$lsmPalette = ['#3a81d7', '#eb881f', '#8f68cb', '#2fa37a', '#de3b3d', '#34939f', '#bf2a82', '#1d3c86'];
$lsmFmt = function ($t) {
    if (!$t) return '';
    try { $c = \Carbon\Carbon::parse($t); return $c->format('i') === '00' ? $c->format('g A') : $c->format('g:i A'); } catch (\Throwable $e) { return (string) $t; }
};
$lsmPT = [];
foreach ($allBranchPlanTypes->values() as $i => $pt) {
    $lsmPT[$pt->id] = [
        'name' => $pt->name,
        'color' => $lsmPalette[$i % count($lsmPalette)],
        'time' => ($pt->start_time && $pt->end_time) ? $lsmFmt($pt->start_time) . '–' . $lsmFmt($pt->end_time) : '',
    ];
}
@endphp

<script>
try { var l1 = document.getElementById('loaderone'); if (l1) l1.style.display = 'none'; } catch(e) {}
</script>

<div class="lsm">
@if(getCurrentBranch() !=0 )

    <div class="lsm-head">
        <div>
            <div class="lsm-crumb">Library<x-lb-icon name="chevron-right" /><span>Seat map</span></div>
            <h1 class="lsm-title">Seat map</h1>
        </div>
        <div class="lsm-head-r">
            @can('has-permission', 'Book Seat')
            <a href="javascript:;" class="lp-btn noseat_popup"><x-lb-icon name="plus" />New booking</a>
            @endcan
        </div>
    </div>

    {{-- Shift + floor pickers --}}
    <div class="lsm-bar">
        <div class="lsm-seg lsm-hscroll" id="lsmShiftSeg" role="group" aria-label="Filter by shift">
            <button type="button" class="is-on" data-shift=""><i class="lsm-segdot lsm-segdot--all"></i><span><b>All shifts</b><small>Tap arrows on a card</small></span></button>
            @foreach($allBranchPlanTypes as $pt)
            <button type="button" data-shift="{{ strtolower($pt->name) }}"><i class="lsm-segdot" style="background: {{ $lsmPT[$pt->id]['color'] }}"></i><span><b>{{ $pt->name }}</b>@if($lsmPT[$pt->id]['time'])<small>{{ $lsmPT[$pt->id]['time'] }}</small>@endif</span></button>
            @endforeach
        </div>
        @if(count($floors ?? []) > 1)
        <div class="lsm-floors lsm-hscroll" id="lsmFloorSeg" role="group" aria-label="Filter by floor">
            <button type="button" class="is-on" data-floor="">All floors</button>
            @foreach($floors as $fi => $floor)
            <button type="button" data-floor="f{{ $floor->id ?? $fi }}">{{ $floor->name }}</button>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Search (+ the original filter controls, kept for the existing filter logic) --}}
    <div class="lsm-search">
        <x-lb-icon name="search" />
        <input type="text" id="seatSearchInput" placeholder="Find a member or seat number" autocomplete="off" aria-label="Find a member or seat number">
        <button type="button" id="resetSeatFilterBtn" class="lsm-reset" title="Clear filters"><x-lb-icon name="rotate-ccw" /><span>Reset</span></button>
        <select id="seatShiftFilterSelect" class="d-none" aria-hidden="true" tabindex="-1">
            <option value="">All Shifts</option>
            @foreach($allBranchPlanTypes as $pt)
            <option value="{{ strtolower($pt->name) }}">{{ $pt->name }}</option>
            @endforeach
        </select>
        <span class="d-none" id="activeFilterBadge"></span>
        <span class="d-none" id="toggleFilterPanelBtn"></span>
        <div class="d-none" id="seatFilterCollapse"></div>
    </div>

    {{-- Status tiles (also the status filter) --}}
    <div class="lsm-stats lsm-hscroll" role="group" aria-label="Filter by status">
        <button type="button" class="seat-status-filter-btn lsm-stat active" data-filter="all"><i class="is-all"></i><b data-count="all">–</b><small>All seats</small></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="booked"><i class="is-booked"></i><b data-count="booked">–</b><small>Booked</small></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="available"><i class="is-free"></i><b data-count="available">–</b><small>Free</small></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="due"><i class="is-due"></i><b data-count="due">–</b><small>Payment due</small></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="expiring"><i class="is-exp"></i><b data-count="expiring">–</b><small>Expiring soon</small></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="extended"><i class="is-ext"></i><b data-count="extended">–</b><small>In extension</small></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="future"><i class="is-future"></i><b data-count="future">–</b><small>Future booked</small></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="non_expired"><i class="is-nonexp"></i><b data-count="non_expired">–</b><small>Non-expired</small></button>
    </div>

    {{-- Library seats / General seats --}}
    <ul class="nav lsm-tabs" id="lsmTabs" role="tablist">
        <li role="presentation">
            <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true">
                <x-lb-icon name="armchair" />Library seats<span class="lsm-tab-n">{{ $total_seats ?? 0 }}</span>
            </button>
        </li>
        @can('has-permission', 'General Seat Booking')
        @if(!in_array('12', toggleHideField()))
        <li role="presentation">
            <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile" aria-selected="false">
                <x-lb-icon name="users-round" />General seats
                @if(countWithoutSeatNo() > 0)<span class="lsm-tab-n">{{ countWithoutSeatNo() }}</span>@endif
            </button>
        </li>
        @endif
        @endcan
    </ul>

    <div class="tab-content" id="pills-tabContent">
@php
    $currentBranchId = getCurrentBranch();
    $branchHourRecord = \App\Models\Hour::where('branch_id', $currentBranchId)->first();
    $branchTotalHours = $branchHourRecord ? (int)$branchHourRecord->hour : 24;

    // 1. Batch pre-fetch all active learners for current branch grouped by seat_no
    $activeLearnersBySeat = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
        ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
        ->where('learners.branch_id', $currentBranchId)
        ->where('learners.status', 1)
        ->where('learner_detail.status', 1)
        ->select(
            'learners.id',
            'learners.name',
            'learners.mobile',
            'learners.profile_picture',
            'learners.seat_no',
            'learners.no_expiry',
            'learners.frozen_status',
            'learner_detail.freeze_start_date',
            'learner_detail.plan_type_id',
            'plan_types.day_type_id',
            'plan_types.image',
            'learner_detail.plan_start_date',
            'learner_detail.plan_end_date',
            'learner_detail.id as learner_detail_id',
            'plan_types.name as plan_type_name'
        )
        ->get()
        ->groupBy(function($item) {
            return (string)$item->seat_no;
        });

    // 2. Batch pre-fetch total hours sum per seat for current branch
    $hoursSumBySeat = LearnerDetail::where('branch_id', $currentBranchId)
        ->where('status', 1)
        ->whereDate('plan_start_date', '<=', $today)
        ->groupBy('seat_no')
        ->selectRaw('seat_no, SUM(hour) as total_hours')
        ->pluck('total_hours', 'seat_no');

    // 3. Batch pre-fetch future bookings for current branch grouped by seat_no
    $futureBookingsBySeat = LearnerDetail::leftJoin('learners', 'learner_detail.learner_id', '=', 'learners.id')
        ->where('learner_detail.branch_id', $currentBranchId)
        ->where('learner_detail.plan_start_date', '>', date('Y-m-d'))
        ->whereNull('learner_detail.deleted_at')
        ->select('learner_detail.*', 'learners.name as learner_name', 'learners.mobile as learner_mobile')
        ->orderBy('learner_detail.plan_start_date', 'asc')
        ->get()
        ->groupBy(function($item) {
            return (string)$item->seat_no;
        });

    // 4. Batch pre-fetch pending amounts & exact due values for all active learners in current branch
    $allLearnerDetailIds = [];
    foreach ($activeLearnersBySeat as $seatGroup) {
        foreach ($seatGroup as $lItem) {
            if ($lItem->learner_detail_id) {
                $allLearnerDetailIds[] = $lItem->learner_detail_id;
            }
        }
    }

    $pendingAmountCache = [];
    $dueAmountValueCache = [];
    if (!empty($allLearnerDetailIds)) {
        $txList = \App\Models\LearnerTransaction::whereIn('learner_detail_id', $allLearnerDetailIds)
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('learner_detail_id');

        foreach ($allLearnerDetailIds as $ldId) {
            $txsForDetail = $txList->get($ldId);
            $dueVal = 0;
            $hasDueFlag = false;

            if ($txsForDetail && $txsForDetail->count() > 0) {
                $latestTx = $txsForDetail->first();
                if ((float)($latestTx->pending_amount ?? 0) > 0) {
                    $dueVal = (float)$latestTx->pending_amount;
                    $hasDueFlag = true;
                } elseif ((int)($latestTx->is_paid ?? 1) === 0) {
                    $dueVal = (float)($latestTx->total_amount ?? 0);
                    $hasDueFlag = $dueVal > 0;
                }
            }

            if (!$hasDueFlag && (pending_amt($ldId) || paylater($ldId))) {
                $hasDueFlag = true;
            }

            $pendingAmountCache[$ldId] = $hasDueFlag;
            $dueAmountValueCache[$ldId] = $dueVal;
        }
    }
@endphp

        <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab" tabindex="0">
            <div id="realSeatContentContainer" class="real-seat-content-container is-loaded">

            @if(isset($total_seats) && $total_seats != 0)
                @php $seatNo = 1; @endphp

                @foreach($floors as $index => $floor)
                    @php
                        $startSeat = $floor->from_seat ?? 1;
                        $endSeat   = $floor->to_seat ?? 0;
                        $floorId   = $floor->id ?? $index;
                        $floorBadge = function_exists('shortFloorName') ? shortFloorName($floor->name) : strtoupper(substr($floor->name, 0, 1));
                    @endphp
                    <section class="floor-collapsible-card lsm-floor" data-floor="f{{ $floorId }}">
                        <div class="lsm-floor-h" data-bs-toggle="collapse" data-bs-target="#floorCollapse_{{ $floorId }}" aria-expanded="true" aria-controls="floorCollapse_{{ $floorId }}" role="button" tabindex="0">
                            <span class="lsm-floor-b">{{ $floorBadge }}</span>
                            <b class="floor-title-text">{{ $floor->name }}</b>
                            <small>Seats {{ $startSeat }} – {{ $endSeat }}</small>
                            <span class="lsm-floor-r">
                                <span class="lp-tag lp-tag--ok" data-floor-free>– free</span>
                                <span class="lp-tag lp-tag--bad" data-floor-due hidden>– due</span>
                                <span class="lsm-floor-chev"><x-lb-icon name="chevron-down" /></span>
                            </span>
                        </div>
                        <div class="collapse show floor-seat-collapse" id="floorCollapse_{{ $floorId }}">
                            <div class="seat-booking lsm-grid">
                            @for($seatNo2 = $startSeat; $seatNo2 <= $endSeat && $seatNo <= $total_seats; $seatNo2++)
                                @include('learner.partials.seat-card', ['seatLabelText' => 'Seat ' . $seatNo])
                                @php
                                    $seatNo++;
                                    $startSeat++;
                                @endphp
                            @endfor
                            </div>
                        </div>
                    </section>
                @endforeach

                {{-- Show remaining seats if total_seats > last assigned seat --}}
                @if($seatNo <= $total_seats)
                    <section class="floor-collapsible-card lsm-floor" data-floor="funassigned">
                        <div class="lsm-floor-h" data-bs-toggle="collapse" data-bs-target="#floorCollapse_unassigned" aria-expanded="true" aria-controls="floorCollapse_unassigned" role="button" tabindex="0">
                            <span class="lsm-floor-b"><x-lb-icon name="layout-grid" /></span>
                            <b class="floor-title-text">{{ count($floors ?? []) ? 'Other seats' : 'All seats' }}</b>
                            <small>Seats {{ $seatNo }} – {{ $total_seats }}</small>
                            <span class="lsm-floor-r">
                                <span class="lp-tag lp-tag--ok" data-floor-free>– free</span>
                                <span class="lp-tag lp-tag--bad" data-floor-due hidden>– due</span>
                                <span class="lsm-floor-chev"><x-lb-icon name="chevron-down" /></span>
                            </span>
                        </div>
                        <div class="collapse show floor-seat-collapse" id="floorCollapse_unassigned">
                            <div class="seat-booking lsm-grid">
                            @for($seatNo2 = $seatNo; $seatNo2 <= $total_seats; $seatNo2++)
                                @php $seatNo = $seatNo2; @endphp
                                @include('learner.partials.seat-card', ['seatLabelText' => 'Seat ' . $seatNo])
                            @endfor
                            </div>
                        </div>
                    </section>
                @endif
            @else
                <div class="lp-card lp-empty lsm-empty">
                    <span><x-lb-icon name="armchair" /></span>
                    <b>No seats yet</b>
                    Add your seats in branch settings to see the seat map.
                </div>
            @endif

            <div class="lsm-legend">
                <span><i class="is-booked"></i>Booked</span>
                <span><i class="is-due"></i>Payment due</span>
                <span><i class="is-exp"></i>Expiring soon</span>
                <span><i class="is-ext"></i>In extension</span>
                <span><i class="is-future"></i>Future booked</span>
                <span><i class="is-nonexp"></i>Non-expired</span>
                <span><x-lb-icon name="armchair" class="lsm-legend-free" />Free shift</span>
            </div>
            </div> <!-- End #realSeatContentContainer -->
        </div>

        <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab" tabindex="0">
            <section class="floor-collapsible-card lsm-floor">
                <div class="lsm-floor-h">
                    <span class="lsm-floor-b"><x-lb-icon name="users-round" /></span>
                    <b class="floor-title-text">General shift seats</b>
                    <small>{{ countWithoutSeatNo() }} booked</small>
                </div>
                <div class="floor-seat-collapse">
                    <div class="seat-booking lsm-grid">
                    @if(countWithoutSeatNo() > 0)
                    @php
                    $usersForSeat = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                        ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                        ->where('learners.branch_id', getCurrentBranch())
                        ->where(function ($q) {
                            $q->whereNull('learners.seat_no')
                              ->orWhere('learners.seat_no', '')
                              ->orWhere('learners.seat_no', '0')
                              ->orWhere('learners.seat_no', 0)
                              ->orWhere('learners.seat_no', 'GEN');
                        })
                        ->select(
                            'learners.id',
                            'learners.name',
                            'learners.mobile',
                            'learners.profile_picture',
                            'learners.no_expiry',
                            'learner_detail.plan_type_id',
                            'plan_types.day_type_id',
                            'plan_types.image',
                            'learner_detail.plan_end_date',
                            'learner_detail.id as learner_detail_id',
                            'plan_types.name as plan_type_name'
                        )
                        ->where('learners.status', 1)
                        ->where('learner_detail.status', 1)
                        ->get();
                    @endphp
                    @foreach($usersForSeat as $genIdx => $user)
                        @php
                        $isNonExpiry = (int) ($user->no_expiry ?? 0) === 1;
                        if ($isNonExpiry) {
                            $planDetails = ['status' => 'Non-Expired', 'class' => 'non_expiry_class'];
                            $isExt = false;
                            $isExpiring = false;
                        } else {
                            $planDetails = getPlanStatusDetails($user->plan_end_date);
                            $isExt = ($planDetails['status'] === 'In Extension' || $planDetails['status'] === 'Extension ends today' || (isset($planDetails['class']) && in_array($planDetails['class'], ['extedned', 'extended'])));
                            $isExpiring = (isset($planDetails['class']) && $planDetails['class'] === 'aboutToExpire');
                        }
                        $hasDue = $pendingAmountCache[$user->learner_detail_id] ?? pending_amt($user->learner_detail_id);
                        $isFuture = (!empty($user->plan_start_date) && \Carbon\Carbon::parse($user->plan_start_date)->isFuture());
                        
                        if ($hasDue) {
                            $genStatusColor = '#ef4444'; // Red (Due)
                        } elseif ($isNonExpiry) {
                            $genStatusColor = '#c8009d'; // Pink/Magenta (Non-Expiry)
                        } elseif ($isExt) {
                            $genStatusColor = '#800000'; // Maroon (Extended)
                        } elseif ($isExpiring) {
                            $genStatusColor = '#d97706'; // Amber/Orange (Expiring)
                        } elseif ($isFuture) {
                            $genStatusColor = '#c09600'; // Yellow/Gold (Future)
                        } else {
                            $genStatusColor = '#0284c7'; // Sky Blue (General Booked)
                        }

                        $avatarBg = $hasDue ? '#ef4444' : ($isNonExpiry ? '#c8009d' : ($isExt ? '#800000' : ($isExpiring ? '#d97706' : ($isFuture ? '#c09600' : '#0284c7'))));
                        $initials = strtoupper(substr($user->name, 0, 2));
                        $avatarDotClass = $hasDue ? 'seat-avatar-dot due-dot seatBlink' : ($isNonExpiry ? 'seat-avatar-dot non-expiry-dot' : ($isExt ? 'seat-avatar-dot extension-dot seatBlink' : ($isExpiring ? 'seat-avatar-dot expiring-dot seatBlink' : 'seat-avatar-dot active-dot')));
                        $avatarTooltip = $hasDue ? 'Fee Overdue' : ($isNonExpiry ? 'Non-Expired Booking' : ($isExt ? 'Extension Active' : ($isExpiring ? 'About to Expire' : ($isFuture ? 'Future Booking' : 'Active Booking'))));
                        $hasPhoto = !empty($user->profile_picture);
                        $genIsFrozen = (int)($user->frozen_status ?? 0) === 1 || !empty($user->freeze_start_date);
                        $genLabelStr = $genIsFrozen 
                            ? ('FREEZED ON ' . (\Carbon\Carbon::parse($user->freeze_start_date)->format('d/m/Y')))
                            : strtoupper($user->plan_type_name ?? 'GENERAL');
                        @endphp
                        @php
                            $gTone = $hasDue ? 'due' : ($isNonExpiry ? 'nonexp' : ($isExt ? 'ext' : ($isExpiring ? 'exp' : ($isFuture ? 'future' : 'booked'))));
                            $gInitials = collect(explode(' ', trim((string) $user->name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: $initials;
                            $gPt = $lsmPT[$user->plan_type_id] ?? null;
                        @endphp
                        <div class="seat-card-item lsm-seat is-{{ $gTone }} {{ ($hasDue || $isExt) ? 'seat-card-alert' : '' }}"
                             data-seat-no="GEN-{{ $genIdx + 1 }}"
                             data-student-names="{{ strtolower($user->name) }}"
                             data-shifts="{{ strtolower($user->plan_type_name) }}"
                             data-has-available="0"
                             data-has-booked="1"
                             data-has-due="{{ $hasDue ? '1' : '0' }}"
                             data-has-expiring="{{ $isExpiring ? '1' : '0' }}"
                             data-has-extended="{{ $isExt ? '1' : '0' }}"
                             data-has-future="{{ $isFuture ? '1' : '0' }}"
                             data-has-non-expired="{{ $isNonExpiry ? '1' : '0' }}"
                             data-preview-name="{{ $user->name }}"
                             data-preview-mobile="{{ $user->mobile ?? '' }}"
                             data-preview-seat="GEN #{{ sprintf('%02d', $genIdx + 1) }}"
                             data-preview-shift="{{ $user->plan_type_name }}"
                             data-preview-status="{{ $avatarTooltip }}"
                             data-preview-color="{{ $genStatusColor }}"
                             data-preview-due="{{ $hasDue ? '₹' . $hasDue : '' }}"
                             data-preview-validity="{{ !empty($user->plan_end_date) ? \Carbon\Carbon::parse($user->plan_end_date)->format('d M Y') : 'N/A' }}"
                             data-preview-avatar="{{ $hasPhoto ? asset($user->profile_picture) : '' }}"
                             data-preview-initials="{{ $initials }}"
                             tabindex="0" role="button">
                            <div class="lsm-seat-top">
                                <span class="lsm-seat-no">GEN {{ sprintf('%02d', $genIdx + 1) }}</span>
                                <i class="lsm-seat-dot" aria-hidden="true"></i>
                            </div>
                            <div class="lsm-slide is-{{ $gTone }}">
                                <div class="lsm-av-wrap">
                                    @if($hasPhoto)
                                    <a href="{{ asset($user->profile_picture) }}" class="lsm-av lsm-av--photo view-image" title="View {{ $user->name }} photo"><img src="{{ asset($user->profile_picture) }}" alt="{{ $user->name }}" loading="lazy" decoding="async"></a>
                                    @else
                                    <span class="lsm-av lsm-av--init">{{ $gInitials }}</span>
                                    @endif
                                    <span class="{{ $avatarDotClass }} lsm-av-dot" title="{{ $avatarTooltip }}"></span>
                                </div>
                                <div class="shift-info">
                                    <b class="seat-student-name lsm-name" title="{{ $user->name }}">{{ $user->name }}</b>
                                    @if($hasDue)
                                    <span class="seat-due-amount-pill lsm-tag lsm-tag--due" title="Due: ₹{{ $hasDue }}">Payment due</span>
                                    @elseif($gTone !== 'booked')
                                    <span class="lsm-tag lsm-tag--{{ $gTone }}">{{ $avatarTooltip }}</span>
                                    @endif
                                    <div class="lsm-shift">
                                        <span class="lsm-shift-t seat-shift-label" title="{{ $genLabelStr }}">
                                            <span class="lsm-shift-n"><i style="background: {{ $gPt['color'] ?? '#7b7c83' }}"></i><span>{{ $genIsFrozen ? $genLabelStr : ($user->plan_type_name ?? 'General') }}</span></span>
                                            @if(!empty($gPt['time']))<small>{{ $gPt['time'] }}</small>@endif
                                        </span>
                                    </div>
                                </div>
                                <div class="card-action-bar lsm-act">
                                    <button type="button" class="lsm-btn lsm-btn--view second_popup_without_seat" data-bs-toggle="modal" data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}">View</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @else
                    <div class="lp-empty lsm-empty-gen">
                        <span><x-lb-icon name="users-round" /></span>
                        <b>No general seats booked</b>
                        There are no general seat bookings for this branch yet.
                    </div>
                    @endif
                    </div>
                </div>
            </section>
        </div>
    </div>

@else
<div class="lp-card lp-empty lsm-empty">
    <span><x-lb-icon name="building" /></span>
    <b>Select a branch</b>
    Choose a branch from the top bar to see its seat map.
</div>
@endif
</div>

@include('partials.motivational-banner')

@can('has-permission', 'View Seat')
<div class="modal fade library-seat-module" id="seatAllotmentModal2" tabindex="-1" aria-labelledby="seat_details_info" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-bottom py-3 px-3 px-md-4 bg-white">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="modal-seat-icon-badge d-inline-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-couch"></i>
                    </span>
                    <h1 class="modal-title fs-5 mb-0 fw-semibold font-outfit" id="seat_details_info">Book Seat</h1>
                    <span id="seat_name" style="display: none;"></span>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="actions border-0 shadow-none p-0 bg-transparent mb-0">
                            <!-- Top Card: Learners Info (Sleek Executive Card) -->
                            <div class="upper-box">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="upper-box-icon-badge d-inline-flex align-items-center justify-content-center">
                                            <i class="fa-solid fa-graduation-cap"></i>
                                        </span>
                                        <h4 class="mb-0 fw-semibold font-outfit text-white" style="font-size: 0.95rem;">Learners Info</h4>
                                    </div>
                                    @if(Auth::user()->can('has-permission', 'Edit Seat') || Auth::user()->can('has-permission', 'Learner Edit'))
                                     <a href="javascript:void(0)" class="btn btn-sm rounded-pill px-3 py-1 font-outfit fw-semibold shadow-none header-edit-profile-btn" id="headerEditProfileBtn">
                                         <i class="fa-solid fa-user-pen me-1"></i> Edit Profile
                                     </a>
                                    @endif
                                </div>
                                <div class="row g-2">
                                    <div class="col-sm-6 col-6">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Seat Owner Name</span>
                                            <h5 id="owner" class="uppercase modal-info-val">NA</h5>
                                        </div>
                                    </div>
                                    @if(!in_array('2', toggleHideField()))
                                    <div class="col-sm-6 col-6">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Date Of Birth</span>
                                            <h5 id="learner_dob" class="modal-info-val">NA</h5>
                                        </div>
                                    </div>
                                    @endif

                                    <div class="col-sm-6 col-6">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Mobile Number</span>
                                            <h5 id="learner_mobile" class="modal-info-val">NA</h5>
                                        </div>
                                    </div>
                                    @if(!in_array('1', toggleHideField()))
                                    <div class="col-sm-6 col-6">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Email Id</span>
                                            <h5 id="learner_email" class="modal-info-val">NA</h5>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Middle Card: Other Seat Info (Clean Card on Slate Background) -->
                            <div class="action-box mt-3">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="action-box-icon-badge d-inline-flex align-items-center justify-content-center">
                                            <i class="fa-solid fa-circle-info"></i>
                                        </span>
                                        <h4 class="mb-0 fw-semibold font-outfit" style="color: #18225f; font-size: 0.95rem;">Other Seat Info</h4>
                                    </div>
                                     <a href="javascript:void(0)" class="btn btn-sm rounded-pill px-3 py-1 font-outfit fw-semibold shadow-none header-edit-plan-btn" id="headerEditPlanBtn" style="display:none;">
                                         <i class="fa-solid fa-pen-to-square me-1"></i> Edit Plan
                                     </a>
                                </div>
                                <div class="row g-2">
                                    {{-- Combined 1: Subscription (Plan Type + Plan Name) --}}
                                    <div class="col-sm-6 col-12">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Subscription</span>
                                            <h5 class="modal-info-val" id="subscriptionDisplay">—</h5>
                                            <span id="planName" style="display:none;"></span>
                                            <span id="planTypeName" style="display:none;"></span>
                                        </div>
                                    </div>

                                    {{-- Combined 2: Plan Duration (Start Date to End Date) --}}
                                    <div class="col-sm-6 col-12">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Plan Duration</span>
                                            <h5 class="modal-info-val" id="planDurationDisplay">—</h5>
                                            <span id="startOn" style="display:none;"></span>
                                            <span id="endOn" style="display:none;"></span>
                                        </div>
                                    </div>

                                    {{-- Combined 3: Plan Price & Mode --}}
                                    <div class="col-sm-6 col-6">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Plan Price & Mode</span>
                                            <h5 class="modal-info-val" id="planPriceModeDisplay">—</h5>
                                            <span id="price" style="display:none;"></span>
                                            <span id="paymentmode" style="display:none;"></span>
                                        </div>
                                    </div>

                                    {{-- 4: Seat Timings --}}
                                    <div class="col-sm-6 col-6">
                                        <div class="modal-info-item">
                                            <span class="modal-info-label">Seat Timings</span>
                                            <h5 id="planTiming" class="modal-info-val">—</h5>
                                        </div>
                                    </div>

                                    {{-- Hidden compatibility elements --}}
                                    <span id="joinOn" style="display:none;"></span>
                                    <span id="proof" style="display:none;"></span>
                                </div>
                                
                                <!-- Status Badge Container -->
                                <div class="seat-modal-status-wrapper w-100 text-center mt-3 pt-3">
                                    <div id="extendday" class="d-inline-flex align-items-center justify-content-center text-center"></div>
                                </div>
                            </div>

                            <!-- Operations Quick Action Ribbon with Left/Right Scroll Navigation -->
                            <div class="modal-op-scroll-wrapper position-relative w-100 mt-3">
                                <button type="button" class="btn btn-sm op-scroll-arrow-btn position-absolute start-0 top-50 translate-middle-y" 
                                        id="opScrollLeftBtn" title="Scroll Left" aria-label="Previous actions">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </button>

                                <div class="modal-op-items d-flex align-items-start gap-2 flex-nowrap w-100" id="modalOpContainer" style="scroll-behavior: smooth; white-space: nowrap;">
                                    <div class="py-3 text-center text-muted small w-100" id="modalOpLoadingPlaceholder">
                                        <i class="fa-solid fa-spinner fa-spin me-1"></i> Loading actions...
                                    </div>
                                </div>

                                <button type="button" class="btn btn-sm op-scroll-arrow-btn position-absolute end-0 top-50 translate-middle-y" 
                                        id="opScrollRightBtn" title="Scroll Right" aria-label="Next actions">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>

                        @can('has-permission', 'Renew Seat')
                        <div class="row justify-content-center mt-3">
                            <div class="col-lg-6">
                                <input type="hidden" value="" id="user_id">
                                <input type="hidden" value="" id="learner_detail_id">

                                <a id="upgrade" class="btn btn-primary btn-block button w-100 py-2" style="height : auto;">Renew Library Membership</a>
                            </div>
                        </div>
                        @else
                        <span class="text-danger text-center d-block mt-2">You don't have Permission to Renew Student.</span>
                        @endcan
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endcan



<script>
    $(document).ready(function() {

        // Modal Operations Mouse Wheel Scroll
        var $opContainer = $('#modalOpContainer');
        $opContainer.on('wheel', function(e) {
            if (e.originalEvent.deltaY !== 0) {
                e.preventDefault();
                this.scrollLeft += e.originalEvent.deltaY;
            }
        });

        // Seat Card Shift Navigation (< > buttons and shift dots)
        $(document).on('click', '.shift-next-btn, .shift-prev-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $card = $(this).closest('.seat-card-item');
            var $slides = $card.find('.shift-slide-item');
            var totalSlides = $slides.length;
            var currentIdx = parseInt($slides.filter('.active-slide').attr('data-shift-idx')) || 0;
            
            var nextIdx;
            if ($(this).hasClass('shift-next-btn')) {
                nextIdx = (currentIdx + 1) % totalSlides;
            } else {
                nextIdx = (currentIdx - 1 + totalSlides) % totalSlides;
            }
            
            $slides.addClass('d-none').removeClass('active-slide');
            $slides.filter('[data-shift-idx="' + nextIdx + '"]').removeClass('d-none').addClass('active-slide');
            
            $card.find('.current-shift-num').text(nextIdx + 1);

            var $dots = $card.find('.shift-dot');
            $dots.css({'opacity': '0.35', 'transform': 'scale(1)'}).removeClass('active-dot');
            $dots.filter('[data-shift-idx="' + nextIdx + '"]').css({'opacity': '1', 'transform': 'scale(1.3)'}).addClass('active-dot');
        });

        $(document).on('click', '.shift-dot', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var targetIdx = parseInt($(this).attr('data-shift-idx'));
            var $card = $(this).closest('.seat-card-item');
            var $slides = $card.find('.shift-slide-item');
            
            $slides.addClass('d-none').removeClass('active-slide');
            $slides.filter('[data-shift-idx="' + targetIdx + '"]').removeClass('d-none').addClass('active-slide');
            
            $card.find('.current-shift-num').text(targetIdx + 1);

            var $dots = $card.find('.shift-dot');
            $dots.css({'opacity': '0.35', 'transform': 'scale(1)'}).removeClass('active-dot');
            $(this).css({'opacity': '1', 'transform': 'scale(1.3)'}).addClass('active-dot');
        });

        // Graceful Content-Aware Shimmer Dismissal
        setTimeout(function() {
            var $sk = $('#seatSkeletonContainer');
            var $real = $('#realSeatContentContainer');
            if ($sk.length) {
                $sk.addClass('fade-out');
                setTimeout(function() {
                    $sk.css('display', 'none');
                    $real.addClass('is-loaded');
                }, 200);
            } else {
                $real.addClass('is-loaded');
            }
        }, 400);

        // Safety fallback
        setTimeout(function() {
            $('#seatSkeletonContainer').css('display', 'none');
            $('#realSeatContentContainer').addClass('is-loaded');
        }, 1000);

        // Check if the animation has already been run in the current session
        if (!sessionStorage.getItem('seatsAnimated')) {
            // Animate each seat one by one
            $('.seat').each(function(index) {
                $(this).delay(index * 200).queue(function(next) {
                    $(this).css({
                        'opacity': '1',
                        'transform': 'translateY(0)'
                    });
                    next(); // Move to the next item in the queue
                });
            });

            // After all animations complete, set the sessionStorage flag
            setTimeout(function() {
                sessionStorage.setItem('seatsAnimated', 'true');
            }, $('.seat').length * 200 + 500); // Wait for all seats to animate
        } else {
            // If the animation has already run, make all seats visible immediately
            $('.seat').css({
                'opacity': '1',
                'transform': 'translateY(0)',
                'transition': 'none' // Disable the transition so they don't animate again
            });
        }
    });
</script>

{{-- <script>
    document.getElementById('plan_start_date').addEventListener('change', function() {
    
        const startDate = new Date(this.value);
        console.log("stat_date",startDate);
        if (startDate) {
            // Add 30 days to the start date
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 30);

            // Format the date to yyyy-mm-dd for the input field
            const formattedDate = endDate.toISOString().split('T')[0];

            // Set the calculated end date in the input field
            document.getElementById('plan_end_date').value = formattedDate;
        }
    });
</script> --}}
<script>
    $(document).ready(function() {
        let currentStatusFilter = 'all';

        function filterSeatCards() {
            let query = ($('#seatSearchInput').val() || '').trim().toLowerCase();
            let shiftQuery = ($('#seatShiftFilterSelect').val() || '').toLowerCase();
            let visibleCount = 0;
            let totalSeats = $('.seat-card-item').length;

            $('.seat-card-item').each(function() {
                let $card = $(this);
                let seatNo = String($card.attr('data-seat-no') || '').toLowerCase();
                let studentNames = String($card.attr('data-student-names') || '').toLowerCase();
                let shifts = String($card.attr('data-shifts') || '').toLowerCase();

                let hasAvailable = $card.attr('data-has-available') === '1';
                let hasBooked = $card.attr('data-has-booked') === '1';
                let hasDue = $card.attr('data-has-due') === '1';
                let hasExpiring = $card.attr('data-has-expiring') === '1';
                let hasExtended = $card.attr('data-has-extended') === '1';
                let hasFuture = $card.attr('data-has-future') === '1';
                let hasNonExpired = $card.attr('data-has-non-expired') === '1';

                // Check text search
                let matchText = true;
                if (query) {
                    let formattedSeat = 'seat ' + seatNo;
                    let formattedPaddedSeat = 'seat ' + (seatNo.length === 1 ? '0' + seatNo : seatNo);
                    matchText = seatNo.includes(query) || 
                                formattedSeat.includes(query) || 
                                formattedPaddedSeat.includes(query) || 
                                studentNames.includes(query);
                }

                // Check shift filter
                let matchShift = true;
                if (shiftQuery) {
                    matchShift = shifts.includes(shiftQuery);
                }

                // Check status filter
                let matchStatus = true;
                if (currentStatusFilter === 'available') {
                    matchStatus = hasAvailable;
                } else if (currentStatusFilter === 'booked') {
                    matchStatus = hasBooked;
                } else if (currentStatusFilter === 'due') {
                    matchStatus = hasDue;
                } else if (currentStatusFilter === 'expiring') {
                    matchStatus = hasExpiring;
                } else if (currentStatusFilter === 'extended') {
                    matchStatus = hasExtended;
                } else if (currentStatusFilter === 'future') {
                    matchStatus = hasFuture;
                } else if (currentStatusFilter === 'non_expired') {
                    matchStatus = hasNonExpired;
                }

                if (matchText && matchShift && matchStatus) {
                    $card.removeClass('d-none');
                    visibleCount++;

                    // If specific shift filter is selected, auto switch slide on card to matching shift
                    if (shiftQuery) {
                        $card.find('.shift-slide-item').each(function() {
                            let label = $(this).find('.shift-info').text().toLowerCase();
                            if (label.includes(shiftQuery)) {
                                let idx = $(this).data('shift-idx');
                                $card.find('.shift-slide-item').addClass('d-none').removeClass('active-slide');
                                $(this).removeClass('d-none').addClass('active-slide');
                                $card.find('.shift-dot').css({'opacity': '0.35', 'transform': 'scale(1)'}).removeClass('active-dot');
                                $card.find('.shift-dot[data-shift-idx="' + idx + '"]').css({'opacity': '1', 'transform': 'scale(1.3)'}).addClass('active-dot');
                            }
                        });
                    } else if (currentStatusFilter !== 'all') {
                        // If specific status filter is selected, auto switch slide on card to matching shift
                        $card.find('.shift-slide-item').each(function() {
                            let isMatch = false;
                            if (currentStatusFilter === 'available' && $(this).find('.avatar-circle-available').length > 0) isMatch = true;
                            if (currentStatusFilter === 'future' && $(this).find('.avatar-circle-future').length > 0) isMatch = true;
                            if (currentStatusFilter === 'due' && $(this).find('.due-dot, .seat-due-amount-pill').length > 0) isMatch = true;
                            if (currentStatusFilter === 'extended' && $(this).find('.extension-dot').length > 0) isMatch = true;
                            if (currentStatusFilter === 'expiring' && $(this).find('.seat-avatar-dot.seatBlink:not(.due-dot):not(.extension-dot)').length > 0) isMatch = true;
                            if (currentStatusFilter === 'non_expired' && $(this).find('.non-expiry-dot').length > 0) isMatch = true;

                            if (isMatch) {
                                let idx = $(this).data('shift-idx');
                                $card.find('.shift-slide-item').addClass('d-none').removeClass('active-slide');
                                $(this).removeClass('d-none').addClass('active-slide');
                                $card.find('.shift-dot').css({'opacity': '0.35', 'transform': 'scale(1)'}).removeClass('active-dot');
                                $card.find('.shift-dot[data-shift-idx="' + idx + '"]').css({'opacity': '1', 'transform': 'scale(1.3)'}).addClass('active-dot');
                            }
                        });
                    }
                } else {
                    $card.addClass('d-none');
                }
            });

            // Hide empty floor collapsible cards
            $('.floor-collapsible-card').each(function() {
                let visibleSeatsInFloor = $(this).find('.seat-card-item:not(.d-none)').length;
                if (visibleSeatsInFloor === 0) {
                    $(this).addClass('d-none');
                } else {
                    $(this).removeClass('d-none');
                }
            });

            updateFilterIndicator();
        }

        // Toggle Filter Panel on Icon Click (Mobile-First)
        $('#toggleFilterPanelBtn').on('click', function(e) {
            e.preventDefault();
            $('#seatFilterCollapse').slideToggle(200);
            $(this).toggleClass('active');
        });

        // Function to update active filter indicator dot
        function updateFilterIndicator() {
            var shiftVal = $('#seatShiftFilterSelect').val() || '';
            var hasFilter = (shiftVal !== '') || (currentStatusFilter !== 'all');
            if (hasFilter) {
                $('#activeFilterBadge').removeClass('d-none');
                $('#toggleFilterPanelBtn').addClass('active');
            } else {
                $('#activeFilterBadge').addClass('d-none');
                if (!$('#seatFilterCollapse').is(':visible')) {
                    $('#toggleFilterPanelBtn').removeClass('active');
                }
            }
        }

        // Live Search Input & Shift Select Events
        $('#seatSearchInput').on('keyup input', filterSeatCards);
        $('#seatShiftFilterSelect').on('change', filterSeatCards);

        // Status Filter Pill Buttons Click
        $(document).on('click', '.seat-status-filter-btn', function() {
            $('.seat-status-filter-btn').removeClass('active');
            $(this).addClass('active');

            currentStatusFilter = $(this).data('filter');
            filterSeatCards();
        });

        // Reset Filters Button
        $('#resetSeatFilterBtn').on('click', function() {
            $('#seatSearchInput').val('');
            $('#seatShiftFilterSelect').val('');
            currentStatusFilter = 'all';
            $('.seat-status-filter-btn').removeClass('active');
            $('.seat-status-filter-btn[data-filter="all"]').addClass('active');
            filterSeatCards();
        });

        // Profile photo lightbox preview modal
        $(document).on('click', 'a.view-image', function (e) {
            var imageUrl = $(this).attr('href');
            if (!imageUrl || !imageUrl.match(/\.(jpg|jpeg|png|webp)(\?|#|$)/i)) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            openProfileImageModal(imageUrl);
        });

        function openProfileImageModal(imageUrl) {
            var $m = $('#imageViewModal');
            if (!$m.length) return;
            $('#modalImage').attr('src', imageUrl);
            $m.attr('aria-hidden', 'false');
            $m.css({ display: 'flex', opacity: 0 }).animate({ opacity: 1 }, 200);
        }

        function closeProfileImageModal() {
            var $m = $('#imageViewModal');
            if (!$m.length) return;
            $m.animate({ opacity: 0 }, 150, function () {
                $m.css({ display: 'none', opacity: 1 });
                $m.attr('aria-hidden', 'true');
                $('#modalImage').attr('src', '');
            });
        }

        $(document).on('click', '#imageViewModal .close-modal', function (e) {
            e.stopPropagation();
            closeProfileImageModal();
        });

        $(document).on('click', '#imageViewModal', function (e) {
            if ($(e.target).is('#imageViewModal') || $(e.target).hasClass('image-modal-content')) {
                closeProfileImageModal();
            }
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $('#imageViewModal').css('display') === 'flex') {
                closeProfileImageModal();
            }
        });

        // Density View Mode Switching (Card Grid vs Dense Matrix View)
        // Card-Level Tap / Click Delegation (Mobile & Desktop UX friendly: tap anywhere on card)
        $(document).on('click', '.seat-card-item', function(e) {
            if ($(e.target).closest('button, a, .shift-prev-btn, .shift-next-btn, .shift-dot, .view-image').length) {
                return;
            }
            var $activeSlide = $(this).find('.shift-slide-item.active-slide');
            if (!$activeSlide.length) {
                $activeSlide = $(this).find('.shift-slide-item:first');
            }
            if ($activeSlide.length) {
                var $btn = $activeSlide.find('.second_popup, .first_popup, .second_popup_without_seat');
                if ($btn.length) {
                    $btn[0].click();
                }
            } else {
                var $btn = $(this).find('.second_popup_without_seat, .second_popup, .first_popup');
                if ($btn.length) {
                    $btn[0].click();
                }
            }
        });
    });
</script>

<div id="imageViewModal" class="image-modal" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" title="Close">&times;</span>
        <img id="modalImage" src="" alt="Full view">
    </div>
</div>

 <!-- End .library-seat-module -->

<script>
/* Seat map: shift + floor pickers, live counts (works with the filter script above). */
$(function () {
    var $sel = $('#seatShiftFilterSelect');
    function paintShift() {
        var v = $sel.val() || '';
        $('#lsmShiftSeg button').each(function () { $(this).toggleClass('is-on', String($(this).data('shift') || '') === v); });
    }
    $('#lsmShiftSeg').on('click', 'button', function () {
        $sel.val(String($(this).data('shift') || '')).trigger('change');
        paintShift();
    });
    $('#resetSeatFilterBtn').on('click', function () {
        setTimeout(paintShift, 0);
        $('#lsmFloorSeg button[data-floor=""]').trigger('click');
    });

    $('#lsmFloorSeg').on('click', 'button', function () {
        var f = String($(this).data('floor') || '');
        $('#lsmFloorSeg button').removeClass('is-on');
        $(this).addClass('is-on');
        $('#pills-home .lsm-floor').each(function () {
            $(this).toggleClass('lsm-floor-off', !!f && $(this).attr('data-floor') !== f);
        });
    });

    // Counts for the status tiles (library seats) and each floor header
    var $cards = $('#pills-home .seat-card-item');
    var c = { all: $cards.length, booked: 0, available: 0, due: 0, expiring: 0, extended: 0, future: 0, non_expired: 0 };
    $cards.each(function () {
        var d = this.dataset;
        if (d.hasBooked === '1') c.booked++;
        if (d.hasAvailable === '1') c.available++;
        if (d.hasDue === '1') c.due++;
        if (d.hasExpiring === '1') c.expiring++;
        if (d.hasExtended === '1') c.extended++;
        if (d.hasFuture === '1') c.future++;
        if (d.hasNonExpired === '1') c.non_expired++;
    });
    $.each(c, function (k, v) { $('.lsm-stat [data-count="' + k + '"]').text(v); });
    $('.lsm-stat').each(function () {
        var k = $(this).data('filter');
        if (k !== 'all' && k !== 'booked' && k !== 'available' && !c[k]) $(this).addClass('is-zero');
    });
    $('#pills-home .lsm-floor').each(function () {
        var $f = $(this);
        var free = $f.find('.seat-card-item[data-has-available="1"]').length;
        var due = $f.find('.seat-card-item[data-has-due="1"]').length;
        $f.find('[data-floor-free]').text(free + ' free');
        if (due) $f.find('[data-floor-due]').text(due + ' due').prop('hidden', false);
    });

    // Keyboard: Enter / Space on a seat card opens it (same as a click)
    $(document).on('keydown', '.lsm-seat', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target === this) { e.preventDefault(); $(this).trigger('click'); }
    });
});
</script>

@endsection
