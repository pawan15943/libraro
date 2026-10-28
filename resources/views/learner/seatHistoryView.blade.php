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
    $shSeat = getSeatDisplayShortFloorName($seatNo ?? request()->route('id'));
    $shInitials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
    $shMoney = fn ($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
@endphp

<div class="lml" id="lmlRoot">

    <div class="lml-head">
        <div>
            <div class="lml-crumb"><a href="{{ route('seats') }}">Seat map</a><x-lb-icon name="chevron-right" /><span>Seat {{ $shSeat }} history</span></div>
            <h1 class="lml-title">Seat {{ $shSeat }} · history</h1>
            <p class="lml-sub">Everyone who sat here before — closed, expired and deleted bookings.</p>
        </div>
        <div class="lml-head-r">
            <a href="{{ route('seats') }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="layout-grid" />Seat map</a>
        </div>
    </div>

    <div id="lmlTop"></div>

    <div class="lml-results">
    @if($learners->isEmpty())
    <div class="lp-card lp-empty lml-empty">
        <span><x-lb-icon name="armchair" /></span>
        <b>No history for seat {{ $shSeat }} yet</b>
        Past bookings appear here once a member on this seat expires, closes or is deleted.
        <a href="{{ route('seats') }}" class="lp-btn lp-btn--ghost" style="margin-top:8px"><x-lb-icon name="layout-grid" />Back to seat map</a>
    </div>
    @else

    <div class="lml-bar">
        <p><b>{{ $learners->total() }}</b> past booking{{ $learners->total() == 1 ? '' : 's' }} on seat {{ $shSeat }}@if($learners->lastPage() > 1) · page {{ $learners->currentPage() }} of {{ $learners->lastPage() }}@endif</p>
    </div>

    <div class="lml-thead lml-thead--inline" aria-hidden="true"><span>Member</span><span>Shift · plan</span><span>Plan duration</span><span>Paid · due</span><span>Status</span><span></span></div>
    <div id="learnerCardsList" class="learner-cards-list lml-grid is-active">
    @foreach($learners as $value)
    @php
        $learner_detail_id = $value->id;
        $learner_id = $value->learner_id;
        $learner = myLearner($learner_id);
        $name = $learner->name ?? 'Member';
        $planStatus = getPlanStatusDetails($value->plan_end_date);
        $transaction = learnerTransaction($learner_id, $learner_detail_id);
        $op = getLearnerOperation($learner_detail_id);
        $operation = optional($op)->operation;
        $operationDate = optional($op)->created_at;
        $pending = (float) (optional($transaction)->pending_amount ?? 0);
        $paid = (float) (optional($transaction)->paid_amount ?? 0);
        $dueDate = optional($transaction)->due_date ? date('j M Y', strtotime($transaction->due_date)) : null;
        $isNonExpiry = ((int) ($value->no_expiry ?? 0) === 1);
        $isDeleted = $operation == 'deleteSeat' && $value->deleted_at != null;

        if ($operation == 'closeSeat') {
            $stText = 'Closed seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : ''); $tone = 'gray';
        } elseif ($isDeleted) {
            $stText = 'Deleted seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : ''); $tone = 'bad';
        } elseif ($isNonExpiry) {
            $stText = 'Non-Expired'; $tone = 'pink';
        } else {
            $stText = !empty($value->plan_end_date) ? 'Plan ended on ' . date('j M Y', strtotime($value->plan_end_date)) : 'Plan ended';
            $tone = 'gray';
        }
        if ($pending > 0) $tone = 'bad';
        $tag = $isNonExpiry ? 'Non-Expired' : ($isDeleted ? 'Deleted' : ($operation == 'closeSeat' ? 'Closed' : ($planStatus['status'] ?? 'Expired')));
        $tagTone = $isNonExpiry ? 'pink' : ($isDeleted ? 'bad' : 'mut');
        $duration = !empty($value->plan_start_date) && !empty($value->plan_end_date)
            ? date('j M Y', strtotime($value->plan_start_date)) . ' → ' . date('j M Y', strtotime($value->plan_end_date))
            : (!empty($value->plan_start_date) ? 'From ' . date('j M Y', strtotime($value->plan_start_date)) : '—');
        $shiftCol = ['#3a81d7', '#eb881f', '#8f68cb', '#2fa37a', '#de3b3d', '#34939f', '#bf2a82', '#1d3c86'][((int) ($value->plan_type_id ?? 0)) % 8];
        $photo = $learner->profile_picture ?? null;
    @endphp
        <article class="learner-card lml-card lml-card--inline is-{{ $tone }}" data-learner="{{ $learner_id }}">
            <div class="lml-card-top">
                <i class="lml-blink {{ $pending > 0 ? 'is-on' : '' }}" aria-hidden="true"></i>
                <span class="lml-seatno">Seat <b>{{ $shSeat }}</b></span>
                <x-lb-icon :name="$isDeleted ? 'trash' : ($operation == 'closeSeat' ? 'lock' : 'clock-3')" class="lml-top-ic" />
                <span class="lml-exp" title="{{ $stText }}">{{ $stText }}</span>
                @if($pending > 0)<span class="lml-duepill"><x-lb-icon name="indian-rupee" />{{ $shMoney($pending) }} due</span>@endif
            </div>

            <div class="lml-card-body">
                <div class="lml-who">
                    <a href="{{ asset($photo ?: 'public/img/student_profile.jpeg') }}" class="lml-av view-image {{ $pending > 0 ? 'is-due' : '' }}" title="View profile photo">
                        @if($photo)<img src="{{ asset($photo) }}" alt="{{ $name }}" loading="lazy" data-i="{{ $shInitials($name) }}" onerror="this.replaceWith(this.dataset.i)">@else{{ $shInitials($name) }}@endif
                    </a>
                    <div class="lml-who-t">
                        <h3><a href="{{ route('learners.show', $learner_id) }}" title="{{ $name }}">{{ $name }}</a></h3>
                        <div class="lml-uid">UID {{ $learner->learner_no ?? '—' }}
                            @if(!empty($learner?->learner_no))
                            <button type="button" class="copy-action-btn lml-copy" data-copy="{{ $learner->learner_no }}" title="Copy UID" aria-label="Copy UID"><x-lb-icon name="copy" /></button>
                            @endif
                            @if(!empty($learner?->mobile))
                            <i class="lml-uid-sep" aria-hidden="true"></i>
                            <a href="tel:+91{{ $learner->mobile }}" class="lml-tel" title="Call +91 {{ $learner->mobile }}" aria-label="Call"><x-lb-icon name="phone" /></a>
                            <button type="button" class="copy-action-btn lml-copy" data-copy="{{ $learner->mobile }}" title="Copy mobile number" aria-label="Copy mobile number"><x-lb-icon name="copy" /></button>
                            @endif
                        </div>
                    </div>
                    <span class="lml-st lml-st--{{ $tagTone }}">{{ $tag }}</span>
                </div>

                <div class="lml-chips">
                    @if(!empty($value->planType->name))<span class="lml-chip"><i class="lml-chip-dot" style="background: {{ $shiftCol }}"></i>{{ $value->planType->name }}</span>@endif
                    @if(!empty($value->plan->name))<span class="lml-chip"><x-lb-icon name="calendar-range" />{{ $value->plan->name }}</span>@endif
                    @if(optional($transaction)->locker_amount)<span class="lml-chip"><x-lb-icon name="lock" />Locker ₹{{ $transaction->locker_amount }}</span>@endif
                </div>
                <div class="lml-dur"><x-lb-icon name="calendar" /><span>{{ $duration }}</span></div>

                {{-- list layout columns (≥ 992px) --}}
                <span class="lml-col lml-col-seat">{{ $value->planType->name ?? '—' }}@if(!empty($value->plan->name))<small>{{ $value->plan->name }}</small>@endif</span>
                <span class="lml-col lml-col-till">{{ $duration }}</span>
                <span class="lml-col lml-col-fee">{{ $transaction ? $shMoney($paid) : '—' }}@if($pending > 0)<small class="is-due">{{ $shMoney($pending) }} due{{ $dueDate ? ' · ' . $dueDate : '' }}</small>@elseif($transaction)<small class="is-clear">All paid</small>@endif</span>
                <span class="lml-col lml-st-col"><span class="lml-st lml-st--{{ $tagTone }}">{{ $tag }}</span></span>

                <div class="lml-money">
                    <div class="lml-paid">
                        <small>Paid</small>
                        <b>{{ $transaction ? $shMoney($paid) : '—' }}</b>
                    </div>
                    @if($pending > 0)
                    <div class="lml-due is-due">
                        <small>Due @if($dueDate)· {{ $dueDate }}@endif</small>
                        <b>{{ $shMoney($pending) }}</b>
                    </div>
                    @else
                    <div class="lml-due is-clear"><small>Balance</small><b>{{ $transaction ? 'All paid' : '—' }}</b></div>
                    @endif
                </div>
            </div>

            <div class="lml-card-act">
                @if($pending > 0)
                <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="lp-btn settlement-learner lml-collect"><x-lb-icon name="indian-rupee" /><span><em>Collect </em>{{ $shMoney($pending) }}</span></a>
                @else
                <a href="{{ route('learners.show', $learner_id) }}" class="lp-btn lp-btn--ghost lml-viewbtn"><x-lb-icon name="eye" /><span>View details</span></a>
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

            {{-- actions: upper bar in list view, rows under the card in card view --}}
            <div class="lml-ai mobile-actions-scroll">
                @can('has-permission', 'View Seat')
                <div class="mobile-action-item">
                    <a href="{{ route('learners.show', $learner_id) }}" class="action-btn" title="View booking details"><i class="fas fa-eye"></i></a>
                    <span class="mobile-action-label">View</span>
                </div>
                @endcan
                @if(!$isDeleted)
                @can('has-permission', 'Reactive Seat')
                <div class="mobile-action-item">
                    <a href="{{ route('learners.reactive', $learner_id) }}" class="action-btn" title="Reactivate learner"><i class="fa-solid fa-arrows-rotate"></i></a>
                    <span class="mobile-action-label">Reactivate</span>
                </div>
                @endcan
                @endif
                @if(!empty($learner?->mobile))
                <div class="mobile-action-item">
                    <a href="https://wa.me/91{{ $learner->mobile }}" target="_blank" rel="noopener" class="action-btn noLoader" title="WhatsApp"><x-lb-brand name="whatsapp" /></a>
                    <span class="mobile-action-label">WhatsApp</span>
                </div>
                @endif
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

    @include('learner.partials.lml-pages', ['p' => $learners->appends(request()->all())])
    @endif
    </div>
</div>

@include('learner.partials.lml-page-script')
@endsection
