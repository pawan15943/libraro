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
    $hasActiveFilters = request()->filled('search') || request()->filled('plan_id') || request()->filled('status') || request()->filled('seat_no') || request()->filled('is_paid');
    $hiddenFields = toggleHideField();
    $currentBranchName = getCurrentBranchName();
    $fbStatus = request('status');
    $fbPay = request('is_paid');
    $fbMore = (int) request()->filled('plan_id') + (int) ($fbPay !== null && $fbPay !== '') + (int) request()->filled('seat_no');
    $fbSort = request('sort_by') === 'seat_no' ? (request('sort_order') === 'desc' ? 'desc' : 'asc') : '';
    $fbInitials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
    $fbMoney = fn ($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
@endphp

<div class="lml" id="lmlRoot" data-base="{{ route('future.bookings') }}">

    <div class="lml-head">
        <div>
            <div class="lml-crumb">Members<x-lb-icon name="chevron-right" /><span>Future bookings</span></div>
            <h1 class="lml-title">Future bookings</h1>
            <p class="lml-sub">Members whose plan starts on an upcoming date.</p>
        </div>
    </div>

    <div id="lmlTop">
    @if(!in_array('24', $hiddenFields))
    <div class="lml-counts">
        <span class="lml-shiftcounts-l">Seats</span>
        <span class="lml-sc">Total<b>{{ $total_seats ?? 0 }}</b></span>
        <span class="lml-sc"><i style="background:oklch(0.62 0.15 155)"></i>Available<b>{{ $availble_seats ?? 0 }}</b></span>
        <span class="lml-sc"><i style="background:oklch(0.5 0.14 264)"></i>Booked<b>{{ $booked_seats ?? 0 }}</b></span>
        <span class="lml-sc"><i style="background:oklch(0.6 0.15 245)"></i>General<b>{{ $genral_seat ?? 0 }}</b></span>
        <span class="lml-sc"><i style="background:oklch(0.5 0.17 15)"></i>Extension<b>{{ $extended_seats ?? 0 }}</b></span>
        <span class="lml-sc"><i style="background:oklch(0.62 0.21 25)"></i>Expired<b>{{ $expired_seat ?? 0 }}</b></span>
    </div>
    @if(!empty($planTypeCounts))
    <div class="lml-shiftcounts">
        <span class="lml-shiftcounts-l">By shift</span>
        @foreach($planTypeCounts as $plan)
        <span class="lml-sc" title="{{ $plan['name'] }}">{{ $plan['abbr'] }} · {{ $plan['name'] }}<b>{{ $plan['count'] }}</b></span>
        @endforeach
    </div>
    @endif
    @endif
    </div>

    {{-- Toolbar: search · status · sort · filters · layout · export --}}
    <form action="{{ route('future.bookings') }}" method="GET" class="lml-filter learner-filter-form" id="learnerFilterForm" role="search">
        @can('has-permission', 'Filter')
        <label class="lml-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" id="search_learner_input" placeholder="Search by name, mobile or email" value="{{ request()->get('search') }}" autocomplete="off">
            <span class="lml-spin" aria-hidden="true"></span>
        </label>
        <label class="lml-pick">
            <x-lb-icon name="clock-3" />
            <select name="status" id="status" aria-label="Status">
                <option value="">All bookings</option>
                <option value="active" @selected($fbStatus == 'active')>Active</option>
                <option value="expired" @selected($fbStatus == 'expired')>Expired</option>
            </select>
            <x-lb-icon name="chevron-down" class="lml-pick-chev" />
        </label>
        @endcan
        <label class="lml-pick">
            <x-lb-icon name="arrow-up-down" />
            <select id="lmlSort" aria-label="Sort">
                <option value="" @selected($fbSort === '')>Seat no. · default</option>
                <option value="asc" @selected($fbSort === 'asc')>Seat no. · 1 → 99</option>
                <option value="desc" @selected($fbSort === 'desc')>Seat no. · 99 → 1</option>
            </select>
            <x-lb-icon name="chevron-down" class="lml-pick-chev" />
            <input type="hidden" name="sort_by" value="{{ $fbSort ? 'seat_no' : '' }}">
            <input type="hidden" name="sort_order" value="{{ $fbSort }}">
        </label>
        @can('has-permission', 'Filter')
        <div class="dropdown lml-more-f">
            <button type="button" class="lml-fbtn {{ $fbMore ? 'is-on' : '' }}" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="More filters" title="More filters"><x-lb-icon name="funnel" /><span>Filters</span>@if($fbMore)<em>{{ $fbMore }}</em>@endif</button>
            <div class="dropdown-menu dropdown-menu-end lml-fpanel">
                <div class="lml-fpanel-h">Filter bookings</div>
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
                        <option value="1" @selected($fbPay === '1')>Paid</option>
                        <option value="0" @selected($fbPay === '0')>Unpaid</option>
                    </select>
                </label>
                <label class="lml-ff"><span>Seat</span>
                    <select name="seat_no" id="seat_no" class="lp-sel">
                        <option value="">All seats</option>
                        @for($sn = 1; $sn <= $totalSeats; $sn++)
                        <option value="{{ $sn }}" @selected(request()->get('seat_no') == $sn)>Seat {{ getSeatDisplayShortFloorName($sn) }}</option>
                        @endfor
                    </select>
                </label>
                <div class="lml-fpanel-f">
                    <button type="button" id="clearFilter" class="lml-clear">Clear all</button>
                    <button type="submit" class="lp-btn lp-btn--sm">Apply</button>
                </div>
            </div>
        </div>
        @endcan
        <div class="lml-view" role="group" aria-label="Layout">
            <button type="button" data-view="grid" class="is-on" aria-pressed="true" title="Cards" aria-label="Cards"><x-lb-icon name="layout-grid" /></button>
            <button type="button" data-view="list" aria-pressed="false" title="List" aria-label="List"><x-lb-icon name="list" /></button>
        </div>
        @php
            $fbCanExport = \Illuminate\Support\Facades\Gate::allows('has-permission', 'Export Library Seats') && !in_array('22', $hiddenFields);
            $fbCanImport = \Illuminate\Support\Facades\Gate::allows('has-permission', 'Import Library Seats') && !in_array('11', $hiddenFields);
        @endphp
        @if($fbCanExport || $fbCanImport)
        <div class="dropdown">
            <button type="button" class="lml-iconbtn" data-bs-toggle="dropdown" aria-expanded="false" title="Export / import" aria-label="Export / import"><x-lb-icon name="download" /></button>
            <div class="dropdown-menu dropdown-menu-end lp-menu">
                @if($fbCanExport)
                <a href="{{ route('learners.export-csv') }}" class="lp-mi"><x-lb-icon name="file-spreadsheet" /><span>Export to CSV<small>Every member with plan details</small></span></a>
                @endif
                @if($fbCanImport)
                @if($fbCanExport)<hr>@endif
                <a href="{{ route('library.upload.form') }}" class="lp-mi"><x-lb-icon name="upload" /><span>Import members<small>Upload a CSV to the portal</small></span></a>
                @endif
            </div>
        </div>
        @endif
        @can('has-permission', 'Book Seat')
        @if(getCurrentBranch() != 0)
        <a href="javascript:;" class="lp-btn lml-add noseat_popup"><x-lb-icon name="plus" />Book seat</a>
        @endif
        @endcan
    </form>

    <div id="lmlLive" aria-live="polite">
    @if ($learners->total() == 0)
    <div class="lp-card lp-empty lml-empty no-data-found">
        <span><x-lb-icon name="calendar-clock" /></span>
        @if($hasActiveFilters)
        <b>No bookings found</b>
        No future bookings match the selected filters. Try changing or clearing the filters.
        <button type="button" class="lp-btn lp-btn--ghost lml-empty-clear" style="margin-top:8px"><x-lb-icon name="x" />Clear filters</button>
        @else
        <b>No future bookings</b>
        You haven’t added any members for upcoming dates yet.
        @if(getCurrentBranch() != 0)
        <a href="javascript:;" class="lp-btn lml-book-first" style="margin-top:8px"><x-lb-icon name="plus" />Book seat</a>
        @else
        <span>Select your branch first from the top bar.</span>
        @endif
        @endif
    </div>
    @else

    <div class="lml-bar">
        <p><b>{{ $learners->total() }}</b> booking{{ $learners->total() == 1 ? '' : 's' }} · {{ $learners->perPage() }} per page @if($learners->lastPage() > 1) · page {{ $learners->currentPage() }} of {{ $learners->lastPage() }}@endif</p>
    </div>

    <div class="lml-thead" aria-hidden="true"><span>Member</span><span>Seat · shift</span><span>Starts on</span><span>Payment</span><span>Status</span><span></span></div>
    <div id="learnerCardsList" class="learner-cards-list lml-grid is-active">
    @foreach($learners as $value)
    @php
        $learner_detail_id = $value->learner_detail_id;
        $learner_id = $value->id;
        $transaction = learnerTransaction($value->id, $learner_detail_id);
        $totalPendingAmt = optional($transaction)->pending_amount ?? 0;
        $due_date = optional($transaction)->due_date;
        $op = getLearnerOperation($learner_detail_id);
        $operation = optional($op)->operation;
        $operationDate = optional($op)->created_at;
        $formattedDueDate = !empty($due_date) ? (is_object($due_date) ? (!empty($due_date->due_date) ? date('j M', strtotime($due_date->due_date)) : '') : date('j M', strtotime($due_date))) : '';
        $hasPendingBalance = (paylater($learner_detail_id) && $totalPendingAmt != 0) || pending_amt($learner_detail_id);
        $paybleRefundAmt = function_exists('paybleRefund') ? paybleRefund($learner_detail_id) : 0;
        $isNonExpiry = ((int) ($value->no_expiry ?? 0) === 1);
        $isDeleted = $operation == 'deleteSeat' && $value->deleted_at != null;

        if ($operation == 'closeSeat') {
            $stText = 'Closed seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : ''); $tone = 'mut';
        } elseif ($isDeleted) {
            $stText = 'Deleted seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : ''); $tone = 'bad';
        } elseif ($isNonExpiry) {
            $stText = 'Non-Expired'; $tone = 'pink';
        } elseif (!empty($value->plan_start_date)) {
            $stText = 'Starts on ' . date('j M Y', strtotime($value->plan_start_date)); $tone = 'blue';
        } else {
            $stText = 'Future booking'; $tone = 'blue';
        }
        $tag = $isNonExpiry ? 'Non-Expired' : 'Upcoming';
        $tagTone = $isNonExpiry ? 'pink' : 'blue';
        $seatTxt = $value->seat_no ? getSeatDisplayShortFloorName($value->seat_no) : 'GEN';
        $duration = !empty($value->plan_start_date) && !empty($value->plan_end_date)
            ? date('j M Y', strtotime($value->plan_start_date)) . ' → ' . date('j M Y', strtotime($value->plan_end_date))
            : (!empty($value->plan_start_date) ? 'From ' . date('j M Y', strtotime($value->plan_start_date)) : '—');
        $dueTxt = $fbMoney($totalPendingAmt) . (!empty($formattedDueDate) ? ' (' . $formattedDueDate . ')' : '');
        $fullyPaid = !$hasPendingBalance && !empty($totalPendingAmt) && $totalPendingAmt == 0;
        $shiftCol = ['#3a81d7', '#eb881f', '#8f68cb', '#2fa37a', '#de3b3d', '#34939f', '#bf2a82', '#1d3c86'][((int) ($value->plan_type_id ?? 0)) % 8];
    @endphp
        <article class="learner-card lml-card is-{{ $hasPendingBalance ? 'bad' : $tone }}" data-learner="{{ $learner_id }}">
            <div class="lml-card-top">
                <i class="lml-blink {{ $hasPendingBalance ? 'is-on' : '' }}" aria-hidden="true"></i>
                <x-lb-icon :name="$isDeleted ? 'trash' : 'calendar-clock'" class="lml-top-ic" />
                <span class="lml-exp" title="{{ $stText }}">{{ $stText }}</span>
                @if(!empty($value->plan_end_date))<span class="lml-date" title="Plan end date">till {{ date('j M Y', strtotime($value->plan_end_date)) }}</span>@endif
                @if($hasPendingBalance)<span class="lml-duepill"><x-lb-icon name="indian-rupee" />{{ $dueTxt }} due</span>@endif
            </div>

            <div class="lml-card-body">
                <div class="lml-who">
                    <a href="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" class="lml-av view-image {{ $hasPendingBalance ? 'is-due' : '' }}" title="View profile photo">
                        @if($value->profile_picture)<img src="{{ asset($value->profile_picture) }}" alt="{{ $value->name }}" loading="lazy" data-i="{{ $fbInitials($value->name) }}" onerror="this.replaceWith(this.dataset.i)">@else{{ $fbInitials($value->name) }}@endif
                    </a>
                    <div class="lml-who-t">
                        <h3><a href="{{ route('learners.show', $value->id) }}" title="{{ $value->name }}">{{ $value->name }}</a></h3>
                        <div class="lml-uid">UID {{ $value->learner_no }}
                            <button type="button" class="copy-action-btn lml-copy" data-copy="{{ $value->learner_no }}" title="Copy UID" aria-label="Copy UID"><x-lb-icon name="copy" /></button>
                        </div>
                    </div>
                    <span class="lml-st lml-st--{{ $tagTone }}">{{ $tag }}</span>
                </div>

                <div class="lml-chips">
                    <span class="lml-chip"><x-lb-icon name="armchair" />Seat {{ $seatTxt }}</span>
                    @if(!empty($value->plan_type_name))<span class="lml-chip"><i class="lml-chip-dot" style="background: {{ $shiftCol }}"></i>{{ $value->plan_type_name }}</span>@endif
                    @if(!empty($value->plan_name))<span class="lml-chip"><x-lb-icon name="calendar-range" />{{ $value->plan_name }}</span>@endif
                    <span class="lml-chip"><x-lb-icon name="calendar" />{{ $duration }}</span>
                </div>

                {{-- list layout columns (≥ 992px) --}}
                <span class="lml-col lml-col-seat">Seat {{ $seatTxt }}@if(!empty($value->plan_type_name)) · {{ $value->plan_type_name }}@endif</span>
                <span class="lml-col lml-col-till">{{ !empty($value->plan_start_date) ? date('j M Y', strtotime($value->plan_start_date)) : '—' }}</span>
                <span class="lml-col lml-col-fee">{{ $hasPendingBalance ? $dueTxt . ' due' : ($fullyPaid ? 'Fully paid' : '—') }}</span>
                <span class="lml-col lml-st-col"><span class="lml-st lml-st--{{ $tagTone }}">{{ $tag }}</span></span>

                <div class="lml-money">
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
                        @if(optional($transaction)->id)
                        <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data" target="_blank" class="lml-rcpt">
                            @csrf
                            <input type="hidden" name="learner_id" value="{{ $learner_id }}">
                            <input type="hidden" name="id" value="{{ $transaction->id ?? 0 }}">
                            <input type="hidden" name="type" value="learner">
                            <button type="submit" class="noLoader" title="Download receipt" aria-label="Download receipt"><x-lb-icon name="download" /></button>
                        </form>
                        @endif
                    </div>
                    @else
                    <div class="lml-paid"><small>Payment</small><b>—</b></div>
                    @endif
                    <div class="lml-paid">
                        <small>Locker</small>
                        <b>{{ ($transaction && $transaction->locker_amount) ? $fbMoney($transaction->locker_amount) . ' paid' : 'No' }}</b>
                    </div>
                </div>
            </div>

            <div class="lml-card-act">
                @if($hasPendingBalance)
                <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="lp-btn settlement-learner lml-collect"><x-lb-icon name="indian-rupee" />Collect {{ $fbMoney($totalPendingAmt) }}</a>
                @else
                <a href="{{ route('learners.show', $value->id) }}" class="lp-btn lp-btn--ghost lml-viewbtn"><x-lb-icon name="eye" />View details</a>
                @endif
                <div class="lml-quick" aria-label="Quick actions"></div>
                <div class="dropdown lml-more">
                    <button type="button" class="lml-round" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" title="All actions" aria-label="All actions"><x-lb-icon name="ellipsis" /></button>
                    <div class="dropdown-menu dropdown-menu-end lml-acts">
                        <div class="lml-acts-h"><b>{{ $value->name }}</b><small>Seat {{ $seatTxt }} · +91 {{ $value->mobile }}</small></div>
                        <div class="lml-acts-contact">
                            <a href="tel:+91{{ $value->mobile }}"><x-lb-icon name="phone" />Call</a>
                            <button type="button" class="copy-action-btn" data-copy="{{ $value->mobile }}"><x-lb-icon name="copy" />Copy number</button>
                            @if($value->email)<a href="mailto:{{ $value->email }}"><x-lb-icon name="mail" />Email</a>@endif
                        </div>
                        <div class="lml-acts-grid mobile-actions-scroll">
                            @include('learner.partials.future-booking-actions', ['isMobile' => true])
                        </div>
                        <a href="{{ route('learners.show', $value->id) }}" class="lml-acts-f">View full profile<x-lb-icon name="arrow-right" /></a>
                    </div>
                </div>
            </div>
        </article>
    @endforeach
    </div>

    @include('learner.partials.lml-pages', ['p' => $learners])
    @endif
    </div>
</div>

@include('learner.partials.lml-page-script')
@endsection
