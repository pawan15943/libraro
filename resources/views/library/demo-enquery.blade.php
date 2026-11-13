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
    $hasActiveFilters = request()->filled('search') || request()->filled('status');
    $dmStatus = request('status');
    $dmTiles = [
        // [label, count, url, active, icon, tone]
        ['Total inquiries', $totalCount ?? $qrbookings->count(), route('demo-users.index'), !$dmStatus, 'users-round', 'acc'],
        ['Paid', $paidCount ?? 0, route('demo-users.index', ['status' => 'paid']), $dmStatus === 'paid', 'circle-check', 'ok'],
        ['Unpaid', $unpaidCount ?? 0, route('demo-users.index', ['status' => 'unpaid']), $dmStatus === 'unpaid', 'clock-alert', 'warn'],
        ["Today's", $todayCount ?? 0, route('demo-users.index', ['status' => 'today']), $dmStatus === 'today', 'calendar-days', 'violet'],
    ];
    $dmInitials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
    $dmMoney = fn ($v) => '₹' . number_format((float) $v, 0);
    $dmWaText = urlencode("Your demo plan is about to expire. Please book your monthly seat to experience the Library.\n\n– Team " . (getCurrentBranchName() ?: 'Library'));
@endphp

<div class="lml dm" id="lmlRoot" data-base="{{ route('demo-users.index') }}">

    <div class="lml-head">
        <div>
            <div class="lml-crumb">Members<x-lb-icon name="chevron-right" /><span>Demo inquiries</span></div>
            <h1 class="lml-title">Demo inquiries</h1>
        </div>
        @php $vtActive = collect(request()->except(['page', 'search', 'view']))->filter(fn ($v) => $v !== null && $v !== '')->count(); @endphp
        <div class="lml-head-r">@include('partials.view-toggles', ['vt' => ['counts'], 'vtActive' => $vtActive])</div>
    </div>

    <div id="lmlTop">
    {{-- Status tiles: one swipe row with arrows, shown by default (as on the members list) --}}
    <div class="lml-tilebar vt-on" data-vt="counts">
        <button type="button" class="lml-tilenav lml-tilenav--prev" data-tilenav="-1" aria-label="Previous filters" hidden><x-lb-icon name="chevron-left" /></button>
        <div class="lml-tiles" role="list">
            @foreach($dmTiles as $t)
            <a href="{{ $t[2] }}" class="lml-tile lml-tile--{{ $t[5] }} {{ $t[3] ? 'is-on' : '' }}" @if($t[3]) aria-current="true" @endif data-lml-nav role="listitem">
                <span class="lml-tile-ic"><x-lb-icon :name="$t[4]" /></span>
                <span class="lml-tile-t"><b>{{ $t[1] }}</b><small>{{ $t[0] }}</small></span>
            </a>
            @endforeach
        </div>
        <button type="button" class="lml-tilenav lml-tilenav--next" data-tilenav="1" aria-label="More filters" hidden><x-lb-icon name="chevron-right" /></button>
    </div>
    </div>

    {{-- Toolbar: search · status · layout · add --}}
    <form action="{{ route('demo-users.index') }}" method="GET" class="lml-filter learner-filter-form" id="learnerFilterForm" role="search">
        <label class="lml-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" id="search_learner_input" placeholder="Search by name, mobile, email or ID" value="{{ request()->get('search') }}" autocomplete="off">
            <span class="lml-spin" aria-hidden="true"></span>
        </label>
        {{-- always next to the search, as the members list's status dropdown --}}
        <label class="lml-pick lml-pick--status">
            <x-lb-icon name="credit-card" />
            <select name="status" id="status" aria-label="Payment status">
                <option value="">All inquiries</option>
                <option value="paid" @selected($dmStatus == 'paid')>Paid</option>
                <option value="unpaid" @selected($dmStatus == 'unpaid')>Unpaid</option>
                <option value="today" @selected($dmStatus == 'today')>Today's inquiries</option>
            </select>
            <x-lb-icon name="chevron-down" class="lml-pick-chev" />
        </label>
        <button type="button" id="clearFilter" class="lml-fbtn {{ $hasActiveFilters ? '' : 'd-none' }}" title="Clear filters"><x-lb-icon name="x" /><span>Clear</span></button>
        <div class="lml-view" role="group" aria-label="Layout">
            <button type="button" data-view="grid" class="is-on" aria-pressed="true" title="Cards" aria-label="Cards"><x-lb-icon name="layout-grid" /></button>
            <button type="button" data-view="list" aria-pressed="false" title="List" aria-label="List"><x-lb-icon name="list" /></button>
        </div>
        <a href="{{ route('demo-users.create') }}" class="lp-btn lml-add"><x-lb-icon name="plus" />Add inquiry</a>
    </form>

    <div id="lmlLive" aria-live="polite">
    @if(!$qrbookings || $qrbookings->count() == 0)
    <div class="lp-card lp-empty lml-empty no-data-found">
        <span><x-lb-icon name="user-search" /></span>
        @if($hasActiveFilters)
        <b>No inquiries found</b>
        No demo inquiries match the selected search or filter. Try changing or clearing it.
        <button type="button" class="lp-btn lp-btn--ghost lml-empty-clear" style="margin-top:8px"><x-lb-icon name="x" />Clear filters</button>
        @else
        <b>No demo inquiries yet</b>
        Demo bookings from your QR code and the ones you add yourself show up here.
        <a href="{{ route('demo-users.create') }}" class="lp-btn" style="margin-top:8px"><x-lb-icon name="plus" />Add inquiry</a>
        @endif
    </div>
    @else

    <div class="lml-bar">
        <p><b>{{ $qrbookings->count() }}</b> inquir{{ $qrbookings->count() == 1 ? 'y' : 'ies' }}</p>
    </div>

    <div class="lml-thead lml-thead--inline lml-thead--member" aria-hidden="true"><span>Inquiry</span><span>Shift</span><span>Plan</span><span>Starts on</span><span>Payment</span><span></span></div>
    <div id="learnerCardsList" class="learner-cards-list lml-grid is-active">
    @foreach($qrbookings as $value)
    @php
        $startDate = !empty($value->plan_start_date) ? \Carbon\Carbon::parse($value->plan_start_date) : null;
        $isPaid = !empty($value->payment_screenshot);
        $canApprove = $isPaid && $value->payment_mode == 'online';
        $seatTxt = $value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'GEN';
        $shift = $value->planType->name ?? 'Demo plan';
        $amount = $dmMoney($value->total_amount ?? 0);
        $mode = $value->payment_mode ? ucfirst($value->payment_mode) : 'Offline';
        $photo = $value->profile_picture ? asset($value->profile_picture) : null;
    @endphp
        @php
            // same look as a member on the members list: amber until the payment is in, then green
            $dmTone = $isPaid ? 'ok' : 'ext';
        @endphp
        <article class="learner-card lml-card lml-card--inline lml-card--member is-{{ $dmTone }} lml-tint-{{ $dmTone }}" data-booking="{{ $value->id }}">
            <div class="lml-card-top">
                <i class="lml-sdot lml-headdot is-{{ $dmTone }}{{ $isPaid ? '' : ' is-blink' }}" aria-hidden="true"></i>
                <span class="lml-headseat">{{ $seatTxt === 'GEN' ? 'General seat' : 'Seat' }}@if($seatTxt !== 'GEN') <b>{{ $seatTxt }}</b>@endif</span>
                <span class="lml-exp lml-stxt is-{{ $dmTone }}">{{ $startDate ? 'Starts ' . $startDate->format('j M Y') : 'Demo booking' }}</span>
                @if(!empty($value->mobile))
                <a href="https://wa.me/+91{{ $value->mobile }}?text={{ $dmWaText }}" target="_blank" rel="noopener" class="lml-share noLoader" title="Send WhatsApp message" aria-label="Send WhatsApp message to {{ $value->name }}"><x-lb-icon name="share-2" /></a>
                @endif
            </div>

            <div class="lml-card-body">
                <div class="lml-who">
                    @if($photo)
                    <a href="{{ $photo }}" class="lml-av view-image" title="View profile photo"><img src="{{ $photo }}" alt="" loading="lazy" decoding="async"></a>
                    @else
                    <span class="lml-av" style="cursor:default"><span>{{ $dmInitials($value->name) }}</span></span>
                    @endif
                    <div class="lml-who-t">
                        <small class="lml-lbl lml-lbl--member">Inquiry</small>
                        <div class="lml-name-row">
                            <h3 title="{{ $value->name }}"><a href="{{ route('booking.details', $value->id) }}">{{ $value->name }}</a></h3>
                            <span class="lml-state is-{{ $dmTone }}">{{ $isPaid ? 'Paid' : 'Unpaid' }}</span>
                        </div>
                        <div class="lml-uid">ID <b>#{{ $value->id }}</b>
                            <button type="button" class="copy-action-btn lml-copy" data-copy="#{{ $value->id }}" title="Copy ID" aria-label="Copy ID"><x-lb-icon name="copy" /></button>
                        </div>
                    </div>
                    <span class="lml-st lml-st--{{ $isPaid ? 'ok' : 'warn' }}">{{ $isPaid ? 'Paid' : 'Unpaid' }}</span>
                </div>

                {{-- labelled fields: columns in list view, one row each in card view --}}
                <span class="lml-col lml-col-shift"><small class="lml-lbl">Shift</small><span class="lml-val">{{ $shift }}</span></span>
                <span class="lml-col lml-col-plan"><small class="lml-lbl">Plan</small><span class="lml-val">{{ $value->plan->name ?? '—' }}</span></span>
                <span class="lml-col lml-col-till"><small class="lml-lbl">Starts on</small><span class="lml-val">{{ $startDate ? $startDate->format('j M Y') : '—' }}</span></span>
                <span class="lml-col lml-col-fee"><small class="lml-lbl">Payment</small><span class="lml-val">{{ $amount }}@if($isPaid)<em class="is-clear"> · Received</em>@else<em class="is-due-soon"> · Pending</em>@endif<small>{{ $mode }}@if($value->payment_screenshot) · <a href="{{ asset($value->payment_screenshot) }}" target="_blank" class="dm-proof noLoader">Screenshot</a>@endif</small></span></span>
            </div>

            <div class="lml-card-act">
                @if($canApprove)
                <form action="{{ route('booking.details.approve') }}" method="POST" class="approve-form dm-approve lml-renewbtn">
                    @csrf
                    <input type="hidden" name="booking_id" value="{{ $value->id }}">
                    <input type="hidden" name="direct_validate" value="1">
                    <button type="submit" class="lp-btn lp-btn--ok noLoader" style="width:100%"><x-lb-icon name="check" /><span>Approve booking</span></button>
                </form>
                @else
                <a href="{{ route('booking.details', $value->id) }}" class="lp-btn lml-renewbtn"><x-lb-icon name="eye" /><span>View details</span></a>
                @endif
                @if(!empty($value->mobile))
                <a href="tel:+91{{ $value->mobile }}" class="lp-btn lp-btn--ghost lml-callbtn" title="Call +91 {{ $value->mobile }}" aria-label="Call {{ $value->name }}"><x-lb-icon name="phone" /></a>
                @endif
            </div>

            {{-- every action, always visible: upper bar in list view, two rows in card view --}}
            <div class="lml-ai mobile-actions-scroll">
                    <div class="mobile-action-item">
                        <a href="{{ route('booking.details', $value->id) }}" class="action-btn" title="View details"><i class="fa-solid fa-eye"></i></a>
                        <span class="mobile-action-label">View</span>
                    </div>
                    @if($canApprove)
                    <div class="mobile-action-item">
                        <button type="button" class="action-btn dm-approve-btn" title="Approve booking"><x-lb-icon name="check" /></button>
                        <span class="mobile-action-label">Approve</span>
                    </div>
                    @endif
                    @if(!empty($value->mobile))
                    <div class="mobile-action-item">
                        <a href="https://wa.me/+91{{ $value->mobile }}?text={{ $dmWaText }}" target="_blank" rel="noopener" class="action-btn noLoader" title="Send WhatsApp message"><i class="fab fa-whatsapp"></i></a>
                        <span class="mobile-action-label">WhatsApp</span>
                    </div>
                    @endif
                    <div class="mobile-action-item">
                        <a href="javascript:void(0)" class="action-btn delete-booking" data-id="{{ $value->id }}" title="Delete inquiry"><i class="fa-solid fa-trash"></i></a>
                        <span class="mobile-action-label">Delete</span>
                    </div>
            </div>
        </article>
    @endforeach
    </div>
    @endif
    </div>
</div>

<script>
$(function () {
    // Copy ID / mobile
    $(document).on('click', '.dm .copy-action-btn', function (e) {
        e.preventDefault();
        var $btn = $(this), val = $btn.data('copy');
        if (!val || !navigator.clipboard) return;
        navigator.clipboard.writeText(String(val)).then(function () {
            if (window.lpToast) window.lpToast('Copied ' + val);
            $btn.addClass('is-copied'); setTimeout(function () { $btn.removeClass('is-copied'); }, 1200);
        });
    });

    // Profile photo preview
    var $modal = $('#imageViewModal'), $img = $('#modalImage');
    $(document).on('click', '.dm .view-image', function (e) {
        e.preventDefault();
        var src = $(this).attr('href') || $(this).find('img').attr('src');
        if (!src) return;
        $img.attr('src', src);
        $modal.css({ display: 'flex', opacity: '1' }).attr('aria-hidden', 'false');
    });
    $modal.on('click', function (e) {
        if (e.target === this || $(e.target).hasClass('close-modal')) {
            $modal.css({ opacity: '0', display: 'none' }).attr('aria-hidden', 'true');
            $img.attr('src', '');
        }
    });

    // "Approve" in the actions menu submits the card's approve form
    $(document).on('click', '.dm .dm-approve-btn', function () {
        $(this).closest('.lml-card').find('form.dm-approve').trigger('submit');
    });

    // Delete inquiry
    $(document).on('click', '.dm .delete-booking', function (e) {
        e.preventDefault();
        var bookingId = $(this).data('id');
        Swal.fire({
            title: 'Delete this inquiry?',
            text: 'This demo inquiry will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#de3b3d',
            cancelButtonColor: '#7b7c83',
            confirmButtonText: 'Yes, delete it'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('booking') }}/" + bookingId,
                type: 'DELETE',
                data: { _token: "{{ csrf_token() }}" }
            }).done(function () {
                if (window.lpToast) window.lpToast('Demo inquiry deleted');
                if (window.lmlRefresh) window.lmlRefresh(); else location.reload();
            }).fail(function () {
                Swal.fire({ title: 'Error', text: 'Something went wrong. Please try again.', icon: 'error', confirmButtonColor: '#1d3c86' });
            });
        });
    });

    // Show "Clear" only while a search or status filter is applied
    $(document).on('lml:refreshed', function () {
        $('#clearFilter').toggleClass('d-none', !($.trim($('#search_learner_input').val()) || $('#status').val()));
    });
});
</script>
<script src="{{ asset('public/js/lb-members.js') }}?v={{ @filemtime(public_path('js/lb-members.js')) }}" defer></script>
@endsection
