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
    $hasActiveFilters = request()->filled('search') || request()->filled('plan_id') || request()->filled('is_paid') || request()->filled('status');
    $lhStatus = request('status');
    $lhPay = request('is_paid');
    $lhMore = (int) request()->filled('plan_id') + (int) (request('is_paid') !== null && request('is_paid') !== '');
    $lhInitials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
    $lhMoney = fn ($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
@endphp

<div class="lml" id="lmlRoot" data-base="{{ route('learnerHistory') }}">

    <div class="lml-head">
        <div>
            <div class="lml-crumb">Members<x-lb-icon name="chevron-right" /><span>History</span></div>
            <h1 class="lml-title">Members history</h1>
            <p class="lml-sub">Expired, closed and deleted seat bookings.</p>
        </div>
    </div>

    <div id="lmlTop"></div>

    {{-- Toolbar: search · status · filters · layout --}}
    @can('has-permission', 'Filter')
    <form action="{{ route('learnerHistory') }}" method="GET" class="lml-filter learner-filter-form" id="learnerFilterForm" role="search">
        <label class="lml-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" id="search_learner_input" placeholder="Search by name, mobile or email" value="{{ request()->get('search') }}" autocomplete="off">
            <span class="lml-spin" aria-hidden="true"></span>
        </label>
        <label class="lml-pick">
            <x-lb-icon name="clock-3" />
            <select name="status" id="status" aria-label="Status">
                <option value="">All history</option>
                <option value="active" @selected($lhStatus === 'active')>Active</option>
                <option value="expired" @selected($lhStatus === 'expired')>Expired</option>
            </select>
            <x-lb-icon name="chevron-down" class="lml-pick-chev" />
        </label>
        <div class="dropdown lml-more-f">
            <button type="button" class="lml-fbtn {{ $lhMore ? 'is-on' : '' }}" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="More filters" title="More filters"><x-lb-icon name="funnel" /><span>Filters</span>@if($lhMore)<em>{{ $lhMore }}</em>@endif</button>
            <div class="dropdown-menu dropdown-menu-end lml-fpanel">
                <div class="lml-fpanel-h">Filter history</div>
                <label class="lml-ff"><span>Plan</span>
                    <select name="plan_id" id="plan_id" class="lp-sel">
                        <option value="">All plans</option>
                        @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(request()->get('plan_id') == $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lml-ff"><span>Payment</span>
                    <select name="is_paid" id="is_paid" class="lp-sel">
                        <option value="">Any payment</option>
                        <option value="1" @selected($lhPay === '1')>Paid</option>
                        <option value="0" @selected($lhPay === '0')>Unpaid</option>
                    </select>
                </label>
                <div class="lml-fpanel-f">
                    <button type="button" id="clearFilter" class="lml-clear">Clear all</button>
                    <button type="submit" class="lp-btn lp-btn--sm">Apply</button>
                </div>
            </div>
        </div>
        <div class="lml-view" role="group" aria-label="Layout">
            <button type="button" data-view="grid" class="is-on" aria-pressed="true" title="Cards" aria-label="Cards"><x-lb-icon name="layout-grid" /></button>
            <button type="button" data-view="list" aria-pressed="false" title="List" aria-label="List"><x-lb-icon name="list" /></button>
        </div>
    </form>
    @endcan

    <div id="lmlLive" aria-live="polite">
    @if ($learnerHistory->total() == 0)
    <div class="lp-card lp-empty lml-empty no-data-found">
        <span><x-lb-icon name="clock-3" /></span>
        @if($hasActiveFilters)
        <b>No members found</b>
        No members match the selected filters. Try changing or clearing the filters.
        <button type="button" class="lp-btn lp-btn--ghost lml-empty-clear" style="margin-top:8px"><x-lb-icon name="x" />Clear filters</button>
        @else
        <b>No members in history yet</b>
        You don’t have any members that are expired, deleted or closed yet.
        @if(getCurrentBranch() != 0)
        <a href="javascript:;" class="lp-btn noseat_popup" style="margin-top:8px"><x-lb-icon name="plus" />Book seat</a>
        @else
        <span>Select your branch first from the top bar.</span>
        @endif
        @endif
    </div>
    @else

    <div class="lml-bar">
        <p><b>{{ $learnerHistory->total() }}</b> record{{ $learnerHistory->total() == 1 ? '' : 's' }} · {{ $learnerHistory->perPage() }} per page @if($learnerHistory->lastPage() > 1) · page {{ $learnerHistory->currentPage() }} of {{ $learnerHistory->lastPage() }}@endif</p>
    </div>

    <div class="lml-thead lml-thead--inline" aria-hidden="true"><span>Member</span><span>Shift · plan</span><span>Plan duration</span><span>Refunded</span><span>Status</span><span></span></div>
    <div id="learnerCardsList" class="learner-cards-list lml-grid is-active">
    @foreach($learnerHistory as $value)
    @php
        $learner_detail_id = $value->learner_detail_id;
        $learner_id = $value->id;
        $planStatus = getPlanStatusDetails($value->plan_end_date);
        $transaction = learnerTransaction($value->id, $value->learner_detail_id);
        $op = getLearnerOperation($learner_detail_id);
        $operation = optional($op)->operation;
        $operationDate = optional($op)->created_at;
        $refundAmount = round($transaction?->refund ?? 0);
        $refunded = round(refund($value->id));
        $isNonExpiry = ((int) ($value->no_expiry ?? 0) === 1);
        $isDeleted = $operation == 'deleteSeat' && $value->deleted_at != null;

        if ($operation == 'closeSeat') {
            $stText = 'Closed seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : ''); $tone = 'gray';
        } elseif ($isDeleted) {
            $stText = 'Deleted seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : ''); $tone = 'bad';
        } elseif ($isNonExpiry) {
            $stText = 'Non-Expired'; $tone = 'pink';
        } else {
            $stText = trim(strip_tags(getUserStatusWithSpan($value->plan_end_date, $learner_id)));
            $l = strtolower($stText);
            $tone = str_contains($l, 'expired') ? 'gray' : (str_contains($l, 'extension') ? 'bad' : ((str_contains($l, 'about to expire') || str_contains($l, 'today')) ? 'warn' : 'ok'));
        }
        if ($refundAmount > 0) $tone = 'bad';
        $tag = $isNonExpiry ? 'Non-Expired' : ($planStatus['status'] ?? 'Expired');
        $tagTone = $isNonExpiry ? 'pink' : ($planStatus['class'] == 'expired' ? 'bad' : 'mut');
        $seatTxt = $value->seat_no ? getSeatDisplayShortFloorName($value->seat_no) : 'GEN';
        $duration = !empty($value->plan_start_date) && !empty($value->plan_end_date)
            ? date('j M Y', strtotime($value->plan_start_date)) . ' → ' . date('j M Y', strtotime($value->plan_end_date))
            : (!empty($value->plan_start_date) ? 'From ' . date('j M Y', strtotime($value->plan_start_date)) : '—');
        $canRefund = refund($value->id) != 0 && ($transaction?->refund ?? 0) > 0;
        $canDelete = $value->status == 0 && (empty($transaction?->refund) || $transaction->refund == 0);
        $shiftCol = ['#3a81d7', '#eb881f', '#8f68cb', '#2fa37a', '#de3b3d', '#34939f', '#bf2a82', '#1d3c86'][((int) ($value->plan_type_id ?? 0)) % 8];
    @endphp
        <article class="learner-card lml-card lml-card--inline is-{{ $tone }}" data-learner="{{ $learner_id }}">
            <div class="lml-card-top">
                <i class="lml-blink {{ ($refundAmount > 0 || $tone === 'warn') ? 'is-on' : '' }}" aria-hidden="true"></i>
                <span class="lml-seatno">Seat <b>{{ $seatTxt }}</b></span>
                <x-lb-icon :name="$isDeleted ? 'trash' : ($operation == 'closeSeat' ? 'lock' : 'clock-3')" class="lml-top-ic" />
                <span class="lml-exp" title="{{ $stText }}">{{ $stText }}</span>
                @if(!empty($value->plan_end_date))<span class="lml-date" title="Plan end date">{{ date('j M Y', strtotime($value->plan_end_date)) }}</span>@endif
                @if($refundAmount > 0)<span class="lml-duepill"><x-lb-icon name="indian-rupee" />Refund due {{ $lhMoney($refundAmount) }}</span>@endif
            </div>

            <div class="lml-card-body">
                <div class="lml-who">
                    <a href="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" class="lml-av view-image" title="View profile photo">
                        @if($value->profile_picture)<img src="{{ asset($value->profile_picture) }}" alt="{{ $value->name }}" loading="lazy" data-i="{{ $lhInitials($value->name) }}" onerror="this.replaceWith(this.dataset.i)">@else{{ $lhInitials($value->name) }}@endif
                    </a>
                    <div class="lml-who-t">
                        <h3><a href="{{ route('learners.show', $value->id) }}" title="{{ $value->name }}">{{ $value->name }}</a></h3>
                        <div class="lml-uid">UID {{ $value->learner_no }}
                            <button type="button" class="copy-action-btn lml-copy" data-copy="{{ $value->learner_no }}" title="Copy UID" aria-label="Copy UID"><x-lb-icon name="copy" /></button>
                            @if(!empty($value->mobile))
                            <i class="lml-uid-sep" aria-hidden="true"></i>
                            <a href="tel:+91{{ $value->mobile }}" class="lml-tel" title="Call +91 {{ $value->mobile }}" aria-label="Call"><x-lb-icon name="phone" /></a>
                            <button type="button" class="copy-action-btn lml-copy" data-copy="{{ $value->mobile }}" title="Copy mobile number" aria-label="Copy mobile number"><x-lb-icon name="copy" /></button>
                            @endif
                        </div>
                    </div>
                    <span class="lml-st lml-st--{{ $tagTone }}">{{ $tag }}</span>
                </div>

                <div class="lml-chips">
                    @if(!empty($value->plan_type_name))<span class="lml-chip"><i class="lml-chip-dot" style="background: {{ $shiftCol }}"></i>{{ $value->plan_type_name }}</span>@endif
                    @if(!empty($value->plan_name))<span class="lml-chip"><x-lb-icon name="calendar-range" />{{ $value->plan_name }}</span>@endif
                    <span class="lml-chip"><x-lb-icon name="lock" />{{ optional($transaction)->locker_amount ? 'Locker ₹' . $transaction->locker_amount . ' paid' : 'No locker' }}</span>
                </div>
                <div class="lml-dur"><x-lb-icon name="calendar" /><span>{{ $duration }}</span></div>

                {{-- list layout columns (≥ 992px) --}}
                <span class="lml-col lml-col-seat">{{ $value->plan_type_name ?: '—' }}@if(!empty($value->plan_name))<small>{{ $value->plan_name }}</small>@endif</span>
                <span class="lml-col lml-col-till">{{ $duration }}</span>
                <span class="lml-col lml-col-fee">{{ $lhMoney($refunded) }}</span>
                <span class="lml-col lml-st-col"><span class="lml-st lml-st--{{ $tagTone }}">{{ $tag }}</span></span>

                <div class="lml-money">
                    <div class="lml-paid">
                        <small>Refunded</small>
                        <b>{{ $lhMoney($refunded) }}</b>
                    </div>
                    @if($refundAmount > 0)
                    <div class="lml-due is-due">
                        <small>Refund due</small>
                        <b>{{ $lhMoney($refundAmount) }}</b>
                        @if($canRefund)<a href="{{ route('learner.other.payment', $value->learner_detail_id) }}" class="lml-txn payment-learner" title="Pay refund" aria-label="Pay refund"><x-lb-icon name="external-link" /></a>@endif
                    </div>
                    @else
                    <div class="lml-due is-clear"><small>Refund due</small><b>None</b></div>
                    @endif
                </div>
            </div>

            <div class="lml-card-act">
                @if($isDeleted)
                <a href="#" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{ $value->seat_no }}" class="lp-btn lp-btn--ok restore-customer"><x-lb-icon name="rotate-ccw" />Restore</a>
                @else
                @can('has-permission', 'Reactive Seat')
                <a href="{{ route('learners.reactive', $value->id) }}" class="lp-btn"><x-lb-icon name="refresh-cw" />Reactivate seat</a>
                @else
                <a href="{{ route('learners.show', $value->id) }}" class="lp-btn lp-btn--ghost lml-viewbtn"><x-lb-icon name="eye" />View details</a>
                @endcan
                @endif
                @if(optional($transaction)->id)
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
                    @can('has-permission', 'Reactive Seat')
                    <div class="mobile-action-item">
                        <a href="{{ route('learners.reactive', $value->id) }}" class="action-btn" title="Reactivate learner"><i class="fa-solid fa-arrows-rotate"></i></a>
                        <span class="mobile-action-label">Reactivate</span>
                    </div>
                    @endcan
                    @if($canRefund)
                    <div class="mobile-action-item">
                        <a href="{{ route('learner.other.payment', $value->learner_detail_id) }}" class="action-btn payment-learner" title="Learner refund"><i class="fa-solid fa-money-bill"></i></a>
                        <span class="mobile-action-label">Refund</span>
                    </div>
                    @endif
                    @can('has-permission', 'View Seat')
                    <div class="mobile-action-item">
                        <a href="{{ route('learners.show', $value->id) }}" class="action-btn" title="View seat booking full details"><i class="fas fa-eye"></i></a>
                        <span class="mobile-action-label">View</span>
                    </div>
                    @endcan
                    @can('has-permission', 'Delete Seat')
                    @if($canDelete)
                    <div class="mobile-action-item">
                        <a href="#" data-id="{{ $learner_id }}" data-learnerDetail="{{ $value->learner_detail_id }}" data-seat="{{ $value->seat_no }}" data-permanent="1" class="action-btn delete-permanent-customer" title="Permanently delete learner"><i class="fas fa-trash"></i></a>
                        <span class="mobile-action-label">Delete</span>
                    </div>
                    @endif
                    @endcan
                    @if($isDeleted)
                    <div class="mobile-action-item">
                        <a href="#" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{ $value->seat_no }}" class="action-btn restore-customer" title="Restore learner"><x-lb-icon name="rotate-ccw" /></a>
                        <span class="mobile-action-label">Restore</span>
                    </div>
                    @endif
            </div>
        </article>
    @endforeach
    </div>

    @include('learner.partials.lml-pages', ['p' => $learnerHistory->appends(request()->all())])
    @endif
    </div>
</div>

@include('learner.partials.lml-page-script')
@endsection
