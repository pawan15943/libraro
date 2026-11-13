@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-members.css') }}?v={{ @filemtime(public_path('css/lb-members.css')) }}" />
@endpush

@section('content')
<script>
    try { var el1 = document.getElementById('loaderone'); if (el1) el1.remove(); } catch(e) {}
</script>

<!-- Profile photo preview -->
<div id="imageViewModal" class="image-modal lml-photo" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" role="button" tabindex="0" aria-label="Close">&times;</span>
        <img src="" id="modalImage" alt="Profile photo preview">
    </div>
</div>

@php
    $shInitials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
    $shMoney = fn ($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');

    // One row per member: seat-wise first, then general (GEN) seats. Same data as before, one card template.
    $shRows = [];
    foreach ($seats as $seat) {
        foreach ($seat->learners as $user) {
            $shRows[] = ['user' => $user, 'learner' => optional($user), 'learner_id' => $user->id, 'profile_id' => $user->learner_id,
                'seat' => getSeatDisplayShortFloorName($seat->seat_no), 'history' => url('seats/history', $seat->seat_no),
                'mobile' => $user->mobile, 'general' => false];
        }
    }
    foreach ($finalGeneralLearners as $user) {
        $gl = myLearner($user->learner_id);
        $shRows[] = ['user' => $user, 'learner' => optional($gl), 'learner_id' => optional($gl)->id, 'profile_id' => optional($gl)->id,
            'seat' => 'GEN', 'history' => route('general.seat.history'), 'mobile' => $user->mobile, 'general' => true];
    }
    $shSeatCount = collect($shRows)->where('general', false)->count();
    $shGenCount = collect($shRows)->where('general', true)->count();
@endphp

<div class="lml" id="lmlRoot" data-base="{{ route('seats.history') }}">

    <div class="lml-head">
        <div>
            <div class="lml-crumb">Seats<x-lb-icon name="chevron-right" /><span>Seat history</span></div>
            <h1 class="lml-title">Seat history</h1>
        </div>
        @php $vtActive = collect(request()->except(['page', 'search', 'view']))->filter(fn ($v) => $v !== null && $v !== '')->count(); @endphp
        <div class="lml-head-r">@include('partials.view-toggles', ['vt' => ['counts'], 'vtActive' => $vtActive])</div>
    </div>

    <div id="lmlTop">
    @if(count($shRows))
    <div class="lml-note" id="shNote">
        <x-lb-icon name="info" />
        <div><b>Important:</b> The Seat History page displays a comprehensive list of all library seats, along with seat-specific booking details in a single view. If you need information about library seats, this section provides helpful details to guide you.</div>
        <button type="button" class="lml-note-x" onclick="this.parentElement.remove()" aria-label="Dismiss"><x-lb-icon name="x" /></button>
    </div>
    <div class="lml-tiles" role="list" data-vt="counts">
        <span class="lml-tile lml-tile--acc" role="listitem"><span class="lml-tile-ic"><x-lb-icon name="users-round" /></span><span class="lml-tile-t"><b>{{ count($shRows) }}</b><small>Active members</small></span></span>
        <span class="lml-tile lml-tile--ok" role="listitem"><span class="lml-tile-ic"><x-lb-icon name="armchair" /></span><span class="lml-tile-t"><b>{{ $shSeatCount }}</b><small>On numbered seats</small></span></span>
        <span class="lml-tile lml-tile--blue" role="listitem"><span class="lml-tile-ic"><x-lb-icon name="layout-grid" /></span><span class="lml-tile-t"><b>{{ $shGenCount }}</b><small>On general seats</small></span></span>
    </div>
    @endif
    </div>

    <form class="lml-filter" id="learnerFilterForm" onsubmit="return false">
        <div class="lml-view" role="group" aria-label="Layout" style="margin-left:auto">
            <button type="button" data-view="grid" class="is-on" aria-pressed="true" title="Cards" aria-label="Cards"><x-lb-icon name="layout-grid" /></button>
            <button type="button" data-view="list" aria-pressed="false" title="List" aria-label="List"><x-lb-icon name="list" /></button>
        </div>
    </form>

    <div id="lmlLive" aria-live="polite">
    @if (!count($shRows))
    <div class="lp-card lp-empty lml-empty no-data-found">
        <span><x-lb-icon name="armchair" /></span>
        <b>No member added yet</b>
        You haven’t added any members to your library yet. Book a seat to add your first member.
        @if(getCurrentBranch() != 0)
        <a href="javascript:;" class="lp-btn noseat_popup" style="margin-top:8px"><x-lb-icon name="plus" />Book seat</a>
        @else
        <span>Select your branch first from the top bar.</span>
        @endif
    </div>
    @else

    <div class="lml-bar">
        <p><b>{{ count($shRows) }}</b> member{{ count($shRows) == 1 ? '' : 's' }} · {{ $shSeatCount }} on seats · {{ $shGenCount }} general</p>
    </div>

    <div class="lml-thead lml-thead--inline" aria-hidden="true"><span>Member</span><span>Shift · plan</span><span>Plan duration</span><span>Payment</span><span>Status</span><span></span></div>
    <div id="learnerCardsList" class="learner-cards-list lml-grid is-active">
    @foreach($shRows as $row)
    @php
        $user = $row['user'];
        $learner = $row['learner'];
        $learner_id = $row['learner_id'];
        $learner_detail_id = $user->learner_detail_id;
        $planStatus = getPlanStatusDetails($user->plan_end_date);
        $op = getLearnerOperation($learner_detail_id);
        $operation = optional($op)->operation;
        $transaction = learnerTransaction($learner_id, $learner_detail_id);
        $due_date = ($transaction && isset($transaction->pending_amount) && $transaction->due_date) ? $transaction->due_date : null;
        $formattedDueDate = !empty($due_date) ? (is_object($due_date) ? (!empty($due_date->due_date) ? date('j M', strtotime($due_date->due_date)) : '') : date('j M', strtotime($due_date))) : '';
        $totalPendingAmt = optional($transaction)->pending_amount ?? 0;
        $hasPendingBalance = (paylater($learner_detail_id) && $totalPendingAmt != 0) || pending_amt($learner_detail_id);
        $fullyPaid = !$hasPendingBalance && !empty($totalPendingAmt) && $totalPendingAmt == 0;
        $isNonExpiry = ((int) ($user->no_expiry ?? 0) === 1);
        $isDeleted = $operation == 'deleteSeat' && $user->deleted_at != null;
        // general seats used the plan end date for closed/deleted, numbered seats the operation date (as before)
        $opDate = $row['general'] ? $user->plan_end_date : optional($op)->created_at;

        if ($operation == 'closeSeat') {
            $stText = 'Closed seat on ' . ($opDate ? date('j M Y', strtotime($opDate)) : ''); $tone = 'mut';
        } elseif ($isDeleted) {
            $stText = 'Deleted seat on ' . ($opDate ? date('j M Y', strtotime($opDate)) : ''); $tone = 'bad';
        } elseif ($isNonExpiry) {
            $stText = 'Non-Expired'; $tone = 'pink';
        } else {
            $stText = trim(strip_tags(getUserStatusWithSpan($user->plan_end_date, $learner_id)));
            $l = strtolower($stText);
            $tone = (str_contains($l, 'expired') || str_contains($l, 'extension')) ? 'bad' : ((str_contains($l, 'about to expire') || str_contains($l, 'today')) ? 'warn' : (str_contains($l, 'start') || str_contains($l, 'frozen') ? 'blue' : 'ok'));
        }
        $tag = $isNonExpiry ? 'Non-Expired' : ($planStatus['status'] ?? 'Active');
        $tagTone = $isNonExpiry ? 'pink' : match ($planStatus['class'] ?? '') { 'aboutToExpire' => 'warn', 'extedned', 'expired' => 'bad', 'frozen' => 'blue', default => 'ok' };
        $shiftName = optional(myPlanType($user->plan_type_id))->name;
        $planName = optional(myPlan($user->plan_id))->name;
        $duration = !empty($user->plan_start_date) && !empty($user->plan_end_date)
            ? date('j M Y', strtotime($user->plan_start_date)) . ' → ' . date('j M Y', strtotime($user->plan_end_date))
            : (!empty($user->plan_start_date) ? 'From ' . date('j M Y', strtotime($user->plan_start_date)) : '—');
        $dueTxt = $shMoney($totalPendingAmt) . (!empty($formattedDueDate) ? ' (' . $formattedDueDate . ')' : '');
        $photo = $learner?->profile_picture;
        $shiftCol = ['#3a81d7', '#eb881f', '#8f68cb', '#2fa37a', '#5b6b8c', '#34939f', '#bf2a82', '#1d3c86'][((int) ($user->plan_type_id ?? 0)) % 8];
    @endphp
        <article class="learner-card lml-card lml-card--inline is-{{ $hasPendingBalance ? 'bad' : $tone }}" data-learner="{{ $learner_id }}">
            <div class="lml-card-top">
                <i class="lml-blink {{ ($hasPendingBalance || in_array($tone, ['warn', 'bad'])) ? 'is-on' : '' }}" aria-hidden="true"></i>
                <span class="lml-seatno">Seat <b>{{ $row['seat'] }}</b></span>
                <x-lb-icon :name="in_array($tone, ['warn', 'bad']) ? 'alarm-clock' : 'shield-check'" class="lml-top-ic" />
                <span class="lml-exp" title="{{ $stText }}">{{ $stText }}</span>
                @if($hasPendingBalance)<span class="lml-duepill"><x-lb-icon name="indian-rupee" />{{ $dueTxt }} due</span>@endif
            </div>

            <div class="lml-card-body">
                <div class="lml-who">
                    <a href="{{ asset($photo ?: 'public/img/student_profile.jpeg') }}" class="lml-av view-image {{ $hasPendingBalance ? 'is-due' : ($tone === 'warn' ? 'is-warn' : '') }}" title="View profile photo">
                        @if($photo)<img src="{{ asset($photo) }}" alt="{{ $learner->name ?? 'profile' }}" loading="lazy" data-i="{{ $shInitials($learner->name ?? '') }}" onerror="this.replaceWith(this.dataset.i)">@else{{ $shInitials($learner->name ?? '') }}@endif
                    </a>
                    <div class="lml-who-t">
                        <h3><a href="{{ route('learners.show', $row['profile_id']) }}" title="{{ $learner->name ?? '' }}">{{ $learner->name ?? '' }}</a></h3>
                        <div class="lml-uid">UID {{ $learner->learner_no ?? '' }}
                            <button type="button" class="copy-action-btn lml-copy" data-copy="{{ $learner->learner_no ?? '' }}" title="Copy UID" aria-label="Copy UID"><x-lb-icon name="copy" /></button>
                            @if(!empty($row['mobile']))
                            <i class="lml-uid-sep" aria-hidden="true"></i>
                            <a href="tel:+91{{ $row['mobile'] }}" class="lml-tel" title="Call +91 {{ $row['mobile'] }}" aria-label="Call"><x-lb-icon name="phone" /></a>
                            <button type="button" class="copy-action-btn lml-copy" data-copy="{{ $row['mobile'] }}" title="Copy mobile number" aria-label="Copy mobile number"><x-lb-icon name="copy" /></button>
                            @endif
                        </div>
                    </div>
                    <span class="lml-st lml-st--{{ $tagTone }}">{{ $tag }}</span>
                </div>

                <div class="lml-chips">
                    @if($shiftName)<span class="lml-chip"><i class="lml-chip-dot" style="background: {{ $shiftCol }}"></i>{{ $shiftName }}</span>@endif
                    @if($planName)<span class="lml-chip"><x-lb-icon name="calendar-range" />{{ $planName }}</span>@endif
                    @if($user->join_date)<span class="lml-chip"><x-lb-icon name="user-plus" />Joined {{ date('j M Y', strtotime($user->join_date)) }}</span>@endif
                </div>
                <div class="lml-dur"><x-lb-icon name="calendar" /><span>{{ $duration }}</span></div>

                {{-- list layout columns (≥ 992px) --}}
                <span class="lml-col lml-col-seat">{{ $shiftName ?: '—' }}@if($planName)<small>{{ $planName }}</small>@endif</span>
                <span class="lml-col lml-col-till">{{ $duration }}</span>
                <span class="lml-col lml-col-fee">{{ $hasPendingBalance ? $dueTxt . ' due' : ($fullyPaid ? 'Fully paid' : '—') }}</span>
                <span class="lml-col lml-st-col"><span class="lml-st lml-st--{{ $tagTone }}">{{ $tag }}</span></span>

                <div class="lml-money">
                    <div class="lml-paid">
                        <small>Valid till</small>
                        <b title="{{ $duration }}">{{ !empty($user->plan_end_date) ? date('j M Y', strtotime($user->plan_end_date)) : '—' }}</b>
                    </div>
                    @if($hasPendingBalance)
                    <div class="lml-due is-due">
                        <small>Payment due</small>
                        <b>{{ $dueTxt }}</b>
                        <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="lml-txn settlement-learner" title="Collect payment" aria-label="Collect payment"><x-lb-icon name="external-link" /></a>
                    </div>
                    @elseif($fullyPaid)
                    <div class="lml-due is-clear">
                        <small>Payment</small>
                        <b>Fully paid</b>
                        @if(!$row['general'] && optional($transaction)->id)
                        <form action="{{ route('learner.receipt.download') }}" method="POST" target="_blank" enctype="multipart/form-data" class="lml-rcpt">
                            @csrf
                            <input type="hidden" name="learner_id" value="{{ $learner_id }}">
                            <input type="hidden" name="id" value="{{ $transaction->id }}">
                            <input type="hidden" name="type" value="learner">
                            <button type="submit" class="noLoader" title="Download receipt" aria-label="Download receipt"><x-lb-icon name="download" /></button>
                        </form>
                        @endif
                    </div>
                    @else
                    <div class="lml-paid"><small>Payment</small><b>—</b></div>
                    @endif
                </div>
            </div>

            <div class="lml-card-act">
                <a href="{{ $row['history'] }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="clock-3" />Seat history</a>
                @if(!$row['general'] && optional($transaction)->id)
                <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data" target="_blank" class="lml-rcpt2">
                    @csrf
                    <input type="hidden" name="learner_id" value="{{ $learner_id }}">
                    <input type="hidden" name="id" value="{{ $transaction->id }}">
                    <input type="hidden" name="learner_detail_id" value="{{ $learner_detail_id }}">
                    <input type="hidden" name="type" value="learner">
                    <button type="submit" class="lp-btn lp-btn--ghost noLoader" title="Print receipt" aria-label="Print receipt"><x-lb-icon name="printer" /><span>Receipt</span></button>
                </form>
                @endif
            </div>

            {{-- every action, always visible: upper bar in list view, two rows in card view --}}
            <div class="lml-ai mobile-actions-scroll">
                    <div class="mobile-action-item">
                        <a href="{{ $row['history'] }}" class="action-btn" title="View seat previous history"><i class="fa-solid fa-clock-rotate-left"></i></a>
                        <span class="mobile-action-label">History</span>
                    </div>
                    <div class="mobile-action-item">
                        <a href="{{ route('learners.show', $row['profile_id']) }}" class="action-btn" title="View profile"><i class="fas fa-eye"></i></a>
                        <span class="mobile-action-label">Profile</span>
                    </div>
                    @if($row['mobile'])
                    <div class="mobile-action-item">
                        <a href="https://wa.me/+91{{ $row['mobile'] }}" target="_blank" rel="noopener" class="action-btn noLoader" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        <span class="mobile-action-label">WhatsApp</span>
                    </div>
                    @endif
            </div>
        </article>
    @endforeach
    </div>
    @endif
    </div>
</div>

@include('learner.partials.lml-page-script')
@endsection
