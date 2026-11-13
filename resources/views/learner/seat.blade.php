@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/motivational-banner.css') }}?v={{ @filemtime(public_path('css/motivational-banner.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-seatmap.css') }}?v={{ @filemtime(public_path('css/lb-seatmap.css')) }}">
@endpush

@section('content')
@include('partials.page-skeleton', ['type' => 'seatmap'])

@php
use App\Models\Learner;
use App\Models\LearnerDetail;
use Carbon\Carbon;
$fullDayCount = 0;
$halfDayFirstHalfCount = 0;
$halfDaySecondHalfCount = 0;
$hourlyCount = 0;
$today = Carbon::today();

// Every shift, hidden timing rows included: names, timings and colours of booked shifts
$lsmAllPlanTypes = \App\Models\PlanType::where('branch_id', getCurrentBranch())
    ->whereNull('deleted_at')
    ->get();
// Shifts a numbered seat can be booked for (offered as free slides): no hidden timing rows,
// no flexible shifts (general seat only) — see docs/ADVANCED_SHIFTS.md
$allBranchPlanTypes = $lsmAllPlanTypes
    ->filter(fn ($pt) => $pt->parent_id === null && ($pt->booking_mode ?: 'fixed') !== 'flexible')
    ->values();

// Shift colour + timing used on every seat card
$lsmPalette = ['#3a81d7', '#eb881f', '#8f68cb', '#2fa37a', '#5b6b8c', '#34939f', '#bf2a82', '#1d3c86'];
$lsmFmt = function ($t) {
    if (!$t) return '';
    try { $c = \Carbon\Carbon::parse($t); return $c->format('i') === '00' ? $c->format('g A') : $c->format('g:i A'); } catch (\Throwable $e) { return (string) $t; }
};
$lsmPT = [];
foreach ($lsmAllPlanTypes->values() as $i => $pt) {
    $lsmPT[$pt->id] = [
        'name' => $pt->name,
        'color' => $lsmPalette[$i % count($lsmPalette)],
        // an advanced shift without its own time shows the library hours
        'time' => $pt->isAnyTime() ? str_replace(' – ', '–', $pt->timeLabel()) : (($pt->start_time && $pt->end_time) ? $lsmFmt($pt->start_time) . '–' . $lsmFmt($pt->end_time) : ''),
    ];
}
$lsmShowDob = !in_array('2', toggleHideField());
@endphp

<script>
try { var l1 = document.getElementById('loaderone'); if (l1) l1.style.display = 'none'; } catch(e) {}
</script>

<div class="lsm" id="lsmRoot">
@if(getCurrentBranch() !=0 )

    <div class="lsm-head">
        <div>
            <div class="lsm-crumb">Library<x-lb-icon name="chevron-right" /><span>Seat map</span></div>
            <h1 class="lsm-title">Seat map</h1>
        </div>
        <div class="lsm-head-r">
        @include('partials.view-toggles', ['vt' => count($floors ?? []) > 1 ? ['counts', 'shifts', 'filters'] : ['counts', 'shifts']])
        @can('has-permission', 'Book Seat')
        <a href="javascript:;" class="lp-btn lsm-new noseat_popup"><x-lb-icon name="user-plus" />New booking</a>
        {{-- Stable target for the booking script's click handler (seat cards are re-rendered by AJAX) --}}
        <button type="button" class="first_popup d-none" id="lsmBookProxy" data-bs-toggle="modal" data-bs-target="#seatAllotmentModal" tabindex="-1" aria-hidden="true"></button>
        @endcan
        </div>
    </div>

    {{-- Shift tabs · floors · search --}}
    <div class="lsm-bar">
        <div class="lsm-seg" id="lsmShiftSeg" role="group" aria-label="Filter by shift" data-vt="shifts">
            <button type="button" class="is-on" data-shift="" title="All shifts"><i class="lsm-segdot lsm-segdot--all"></i><span><b>All shifts</b><small>Swipe each card</small></span></button>
            @foreach($allBranchPlanTypes as $pt)
            <button type="button" data-shift="{{ strtolower($pt->name) }}" title="{{ $pt->name }}{{ $lsmPT[$pt->id]['time'] ? ' · ' . $lsmPT[$pt->id]['time'] : '' }}"><i class="lsm-segdot" style="background: {{ $lsmPT[$pt->id]['color'] }}"></i><span><b>{{ $pt->name }}</b>@if($lsmPT[$pt->id]['time'])<small>{{ $lsmPT[$pt->id]['time'] }}</small>@endif</span></button>
            @endforeach
        </div>
        @if(count($floors ?? []) > 1)
        <div class="lsm-floors" id="lsmFloorSeg" role="group" aria-label="Filter by floor" data-vt="filters">
            <button type="button" class="is-on" data-floor=""><x-lb-icon name="layers" />All floors</button>
            @foreach($floors as $fi => $floor)
            <button type="button" data-floor="f{{ $floor->id ?? $fi }}"><x-lb-icon name="layers" />{{ $floor->name }}</button>
            @endforeach
        </div>
        @endif
        <label class="lsm-search">
            <x-lb-icon name="search" />
            <input type="search" id="seatSearchInput" placeholder="Find a member or seat" autocomplete="off" aria-label="Find a member or seat">
            <button type="button" id="resetSeatFilterBtn" class="lsm-reset" title="Clear filters" hidden><x-lb-icon name="x" /><span>Clear</span></button>
        </label>
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

    {{-- Status tiles (tap one to filter, tap again to clear) --}}
    <div class="lsm-stats" role="group" aria-label="Filter by status" data-vt="counts">
        <button type="button" class="seat-status-filter-btn lsm-stat d-none active" data-filter="all"></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="booked" style="--c: var(--lp-acc)"><i></i><span><b data-count="booked">–</b><small>Booked</small></span></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="available" style="--c: var(--lp-green)"><i></i><span><b data-count="available">–</b><small>Free</small></span></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="due" style="--c: var(--lp-red)"><i></i><span><b data-count="due">–</b><small>Payment due</small></span></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="extended" style="--c: var(--lp-amber)"><i></i><span><b data-count="extended">–</b><small>Extension due</small></span></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="expiring" style="--c: #eb881f"><i></i><span><b data-count="expiring">–</b><small>Expiring soon</small></span></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="future" style="--c: #c09600"><i></i><span><b data-count="future">–</b><small>Future booked</small></span></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="non_expired" style="--c: #bf2a82"><i></i><span><b data-count="non_expired">–</b><small>Non-expired</small></span></button>
        <button type="button" class="seat-status-filter-btn lsm-stat" data-filter="bday" style="--c: var(--lp-rose)"><i></i><span><b data-count="bday">–</b><small>Birthdays today</small></span></button>
    </div>

    {{-- Library seats / General seats --}}
    <div class="lsm-tabs-row">
    <ul class="nav lsm-tabs" id="lsmTabs" role="tablist">
        <li role="presentation">
            <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true">
                <x-lb-icon name="armchair" />Library seats<span class="lsm-tab-n" data-lsm-total>{{ $total_seats ?? 0 }}</span>
            </button>
        </li>
        @can('has-permission', 'General Seat Booking')
        @if(!in_array('12', toggleHideField()))
        <li role="presentation">
            <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile" aria-selected="false">
                <x-lb-icon name="users-round" />General seats<span class="lsm-tab-n" data-lsm-gen>{{ countWithoutSeatNo() }}</span>
            </button>
        </li>
        @endif
        @endcan
    </ul>
    </div>

    <div class="tab-content" id="pills-tabContent">
@php
    $currentBranchId = getCurrentBranch();
    $branchExtendDays = (int) getExtendDays($currentBranchId);
    $branchHourRecord = \App\Models\Hour::where('branch_id', $currentBranchId)->first();
    $branchTotalHours = $branchHourRecord ? (int)$branchHourRecord->hour : 24;

    // 1. Batch pre-fetch all active learners for current branch grouped by seat_no
    $activeLearnersQuery = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
        ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
        ->where('learners.branch_id', $currentBranchId)
        ->where('learners.status', 1)
        ->where('learner_detail.status', 1)
        ->select(
            'learners.id',
            'learners.name',
            'learners.mobile',
            'learners.profile_picture',
            'learners.dob',
            'learners.seat_no',
            'learners.no_expiry',
            'learners.frozen_status',
            'learner_detail.freeze_start_date',
            'learner_detail.plan_type_id',
            'learner_detail.hour',
            'plan_types.day_type_id',
            'plan_types.start_time',
            'plan_types.end_time',
            'plan_types.slot_hours',
            'plan_types.image',
            'learner_detail.plan_start_date',
            'learner_detail.plan_end_date',
            'learner_detail.id as learner_detail_id',
            'plan_types.name as plan_type_name'
        );
    $activeLearnersBySeat = $activeLearnersQuery->get()
        ->groupBy(function($item) {
            return (string)$item->seat_no;
        });

    // Extra shifts of multi-shift bookings show on their own seat (docs/ADVANCED_SHIFTS.md)
    $slotLearnersQuery = \App\Services\SeatSlotQuery::slotVariant($activeLearnersQuery);
    if ($slotLearnersQuery) {
        foreach ($slotLearnersQuery->addSelect('learner_detail.seat_no as slot_seat_no', 'learner_detail.slot_id')->get() as $slotRow) {
            $slotSeatKey = (string) $slotRow->slot_seat_no;
            $activeLearnersBySeat->put($slotSeatKey, $activeLearnersBySeat->get($slotSeatKey, collect())->push($slotRow));
        }
    }

    // 2. Batch pre-fetch total hours sum per seat for current branch
    $hoursSumBySeat = \App\Services\SeatSlotQuery::pluckSum(LearnerDetail::where('branch_id', $currentBranchId)
        ->where('status', 1)
        ->whereDate('plan_start_date', '<=', $today)
        ->groupBy('seat_no')
        ->selectRaw('seat_no, SUM(hour) as total_hours'), 'total_hours', 'seat_no');

    // 3. Batch pre-fetch future bookings for current branch grouped by seat_no
    $futureBookingsBySeat = \App\Services\SeatSlotQuery::get(LearnerDetail::leftJoin('learners', 'learner_detail.learner_id', '=', 'learners.id')
        ->where('learner_detail.branch_id', $currentBranchId)
        ->where('learner_detail.plan_start_date', '>', date('Y-m-d'))
        ->whereNull('learner_detail.deleted_at')
        ->select('learner_detail.*', 'learners.name as learner_name', 'learners.mobile as learner_mobile')
        ->orderBy('learner_detail.plan_start_date', 'asc'))
        ->sortBy('plan_start_date')
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
                        $floorSeatCount = max(0, min($endSeat, $total_seats) - $startSeat + 1);
                    @endphp
                    <section class="floor-collapsible-card lsm-floor" data-floor="f{{ $floorId }}">
                        <button type="button" class="lsm-floor-h" data-bs-toggle="collapse" data-bs-target="#floorCollapse_{{ $floorId }}" aria-expanded="true" aria-controls="floorCollapse_{{ $floorId }}">
                            <span class="lsm-floor-b">{{ $floorBadge }}</span>
                            <b class="floor-title-text">{{ $floor->name }}</b>
                            <small>{{ $floorSeatCount }} seats · {{ $startSeat }}–{{ min($endSeat, $total_seats) }}</small>
                            <span class="lsm-floor-r">
                                <span class="lsm-pill lsm-pill--ok" data-floor-free>– free</span>
                                <span class="lsm-pill lsm-pill--bad" data-floor-due hidden>– due</span>
                            </span>
                            <span class="lsm-floor-chev"><x-lb-icon name="chevron-down" /></span>
                        </button>
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

                {{-- Seats not assigned to any floor --}}
                @if($seatNo <= $total_seats)
                    <section class="floor-collapsible-card lsm-floor" data-floor="funassigned">
                        <button type="button" class="lsm-floor-h" data-bs-toggle="collapse" data-bs-target="#floorCollapse_unassigned" aria-expanded="true" aria-controls="floorCollapse_unassigned">
                            <span class="lsm-floor-b"><x-lb-icon name="layout-grid" /></span>
                            <b class="floor-title-text">{{ count($floors ?? []) ? 'Other seats' : 'All seats' }}</b>
                            <small>{{ $total_seats - $seatNo + 1 }} seats · {{ $seatNo }}–{{ $total_seats }}</small>
                            <span class="lsm-floor-r">
                                <span class="lsm-pill lsm-pill--ok" data-floor-free>– free</span>
                                <span class="lsm-pill lsm-pill--bad" data-floor-due hidden>– due</span>
                            </span>
                            <span class="lsm-floor-chev"><x-lb-icon name="chevron-down" /></span>
                        </button>
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
                <div class="lp-card lp-empty lsm-nomatch" hidden>
                    <span><x-lb-icon name="search" /></span>
                    <b>No seats match</b>
                    Try another name, seat number or filter.
                </div>
            @else
                <div class="lp-card lp-empty lsm-empty">
                    <span><x-lb-icon name="armchair" /></span>
                    <b>No seats yet</b>
                    Add your seats in branch settings to see the seat map.
                </div>
            @endif

            <div class="lsm-legend">
                <span><i style="background: var(--lp-red)"></i>Payment due</span>
                <span><i style="background: var(--lp-amber)"></i>Extension due</span>
                <span><i style="background: #eb881f"></i>Expiring soon</span>
                <span><i style="background: #c09600"></i>Future booked</span>
                <span><i style="background: #bf2a82"></i>Non-expired</span>
                <span><x-lb-icon name="armchair" class="lsm-legend-free" />Free seat</span>
                @if($lsmShowDob)<span><x-lb-icon name="cake" class="lsm-legend-cake" />Birthday today</span>@endif
            </div>
            </div> <!-- End #realSeatContentContainer -->
        </div>

        <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab" tabindex="0">
            <section class="floor-collapsible-card lsm-floor">
                <div class="lsm-floor-h lsm-floor-h--static">
                    <span class="lsm-floor-b"><x-lb-icon name="users-round" /></span>
                    <b class="floor-title-text">General seats</b>
                    <small>{{ countWithoutSeatNo() }} booked · no fixed seat</small>
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
                            'learners.dob',
                            'learner_detail.plan_start_date',
                            'learners.frozen_status',
                            'learner_detail.freeze_start_date',
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
                        $gTone = $hasDue ? 'due' : ($isNonExpiry ? 'nonexp' : ($isExt ? 'ext' : ($isExpiring ? 'exp' : ($isFuture ? 'future' : 'booked'))));
                        $gStatus = $hasDue ? 'Fee Overdue' : ($isNonExpiry ? 'Non-Expired' : ($isExt ? 'In Extension' : ($isExpiring ? 'Expiring Soon' : ($isFuture ? 'Future Booked' : 'Active Booked'))));
                        $gInitials = collect(explode(' ', trim((string) $user->name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: strtoupper(substr($user->name, 0, 2));
                        $gShort = (function ($n) { $p = preg_split('/\s+/', trim((string) $n)); return count($p) > 1 ? $p[0] . ' ' . mb_substr(end($p), 0, 1) . '.' : $n; })($user->name);
                        $gPt = $lsmPT[$user->plan_type_id] ?? null;
                        $hasPhoto = !empty($user->profile_picture);
                        $genIsFrozen = (int)($user->frozen_status ?? 0) === 1 || !empty($user->freeze_start_date);
                        $gBday = $lsmShowDob && !empty($user->dob) && (function ($d) { try { return \Carbon\Carbon::parse($d)->format('m-d') === now()->format('m-d'); } catch (\Throwable $e) { return false; } })($user->dob);
                        $gDueVal = $dueAmountValueCache[$user->learner_detail_id] ?? 0;
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
                             data-has-bday="{{ $gBday ? '1' : '0' }}"
                             data-cur="{{ $gTone }}" data-cur-bday="{{ $gBday ? '1' : '0' }}"
                             tabindex="0" role="button" aria-label="General seat {{ $genIdx + 1 }}">
                            <i class="lsm-bday-bar" aria-hidden="true"></i>
                            <div class="lsm-seat-top">
                                <span class="lsm-seat-no">GEN {{ sprintf('%02d', $genIdx + 1) }}</span>
                                <span class="lsm-flags">
                                    <span class="lsm-flag-cake" title="Birthday today"><x-lb-icon name="cake" /></span>
                                    <i class="lsm-blink lsm-blink--due" title="Payment due"></i>
                                    <i class="lsm-blink lsm-blink--ext" title="Extension due"></i>
                                </span>
                            </div>
                            <div class="lsm-view"><div class="shift-slides-wrapper lsm-track">
                                <div class="shift-slide-item lsm-slide is-{{ $gTone }} active-slide" data-shift-idx="0" data-tone="{{ $gTone }}" data-bday="{{ $gBday ? '1' : '0' }}">
                                    <span class="lsm-av-wrap is-{{ $gTone }}">
                                        @if($hasPhoto)
                                        <a href="{{ asset($user->profile_picture) }}" class="lsm-av lsm-av--photo view-image" title="View {{ $user->name }} photo"><img src="{{ asset($user->profile_picture) }}" alt="" loading="lazy" decoding="async"></a>
                                        @else
                                        <span class="lsm-av lsm-av--init">{{ $gInitials }}</span>
                                        @endif
                                        @if($hasDue && $gDueVal > 0)<span class="seat-due-amount-pill lsm-av-due">₹{{ number_format($gDueVal) }}</span>@endif
                                    </span>
                                    <b class="seat-student-name lsm-name" title="{{ $user->name }}">{{ $gShort }}</b>
                                    @if($gBday)
                                        <span class="lsm-sub lsm-sub--bday"><x-lb-icon name="party-popper" />Happy birthday!</span>
                                    @elseif($hasDue && $gDueVal <= 0)
                                        <span class="seat-due-amount-pill lsm-sub lsm-sub--due">Payment due</span>
                                    @elseif($genIsFrozen)
                                        <span class="lsm-sub lsm-sub--frozen">Freezed on {{ \Carbon\Carbon::parse($user->freeze_start_date)->format('d/m/Y') }}</span>
                                    @elseif($gTone !== 'booked')
                                        <span class="lsm-sub lsm-sub--{{ $gTone }}">{{ $gStatus }}</span>
                                    @endif
                                    <span class="lsm-cta is-view" aria-hidden="true">View</span>
                                    <span class="card-action-bar lsm-act">
                                        <button type="button" class="second_popup_without_seat" tabindex="-1" data-bs-toggle="modal" data-bs-target="#seatAllotmentModal2" data-userid="{{ $user->id }}">View</button>
                                    </span>
                                </div>
                            </div></div>
                            <div class="lsm-foot">
                                <span class="lsm-shift" title="{{ $user->plan_type_name }}">
                                    <span class="lsm-shift-n"><i style="background: {{ $gPt['color'] ?? '#7b7c83' }}"></i><span>{{ $user->plan_type_name ?? 'General' }}</span></span>
                                    <small>{{ $gPt['time'] ?? '' }}</small>
                                </span>
                            </div>
                            <div class="lsm-dots"></div>
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
@include('learner.partials.seat-details-drawer')
@endcan

<div id="imageViewModal" class="image-modal" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" title="Close">&times;</span>
        <img id="modalImage" src="" alt="Full view">
    </div>
</div>

<script src="{{ asset('public/js/lb-seatmap.js') }}?v={{ @filemtime(public_path('js/lb-seatmap.js')) }}"></script>

@endsection
