@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/motivational-banner.css') }}?v={{ @filemtime(public_path('css/motivational-banner.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-members.css') }}?v={{ @filemtime(public_path('css/lb-members.css')) }}" />
@endpush

@section('content')
<script>
    try { var el1 = document.getElementById('loaderone'); if (el1) el1.remove(); } catch(e) {}
</script>

<!-- Transactions modal -->
<div class="modal fade lml-modal" id="cf-modal" tabindex="-1" aria-labelledby="cf-modal-label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="cf-modal-label">Transactions</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="transactionPendingBadge" class="small text-muted mb-2 d-none"></p>
        <div class="table-responsive mb-4">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Plan Price</th>
                <th>Other Addon Cost <span class="fw-normal text-muted small">(Locker | miscellaneous | token)</span></th>
                <th>Discount on Plan Price</th>
                <th>Paid Amt</th>
                <th>Pending Amt</th>
                <th>Payment Date</th>
                <th>Payment Mode</th>
                <th>Download Receipt</th>
              </tr>
            </thead>
            <tbody id="transactionTableBody">
              <tr>
                <td colspan="9" class="text-center text-muted">Open from a learner row to load data.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <h6 class="mb-2">All activity for this learner</h6>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>Particular</th>
                <th>Payment Type</th>
                <th>Payment Mode</th>
                <th>Amt.</th>
                <th>Payment Date</th>
                <th>Dr / Cr</th>
              </tr>
            </thead>
            <tbody id="activityTableBody">
              <tr>
                <td colspan="6" class="text-center text-muted">—</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="lp-btn lp-btn--ghost" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
{{-- Large profile preview — keep display:flex for centering --}}
<div id="imageViewModal" class="image-modal lml-photo" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" role="button" tabindex="0" aria-label="Close">&times;</span>
        <img src="" id="modalImage" alt="Profile photo preview">
    </div>
</div>

@php
$hasActiveFilters = request()->filled('search') || request()->filled('plan_id') || request()->filled('status')
    || request()->filled('seat_no') || request()->filled('payment_filter') || request()->filled('is_paid');
// Computed once per page load — these are branch/library-level settings.
$hiddenFields = toggleHideField();
$currentBranchName = getCurrentBranchName();
$isNotificationActive = notificationActive();
$isWabaNotificationActive = $isNotificationActive && wabaNotificationActive();
$isTextNotificationActive = $isNotificationActive && textNotificationActive();

// Member counts for the status tiles
$currentLibId = getLibraryId();
$currentBranchId = getCurrentBranch();
$todayDate = date('Y-m-d');
$fiveDaysLater = date('Y-m-d', strtotime('+5 days'));
$due_count = \App\Models\LearnerDetail::whereNull('deleted_at')
    ->where('library_id', $currentLibId)
    ->when($currentBranchId, fn($q) => $q->where('branch_id', $currentBranchId))
    ->where('is_paid', 0)
    ->where('status', 1)
    ->count();
$about_to_expire_count = \App\Models\LearnerDetail::whereNull('deleted_at')
    ->where('library_id', $currentLibId)
    ->when($currentBranchId, fn($q) => $q->where('branch_id', $currentBranchId))
    ->where('status', 1)
    ->whereBetween('plan_end_date', [$todayDate, $fiveDaysLater])
    ->count();
$non_expiry_count = \App\Models\LearnerDetail::whereNull('deleted_at')
    ->where('library_id', $currentLibId)
    ->when($currentBranchId, fn($q) => $q->where('branch_id', $currentBranchId))
    ->where('status', 1)
    ->whereNull('plan_end_date')
    ->count();
$future_booking_count = \App\Models\LearnerDetail::whereNull('deleted_at')
    ->where('library_id', $currentLibId)
    ->when($currentBranchId, fn($q) => $q->where('branch_id', $currentBranchId))
    ->where('status', 0)
    ->where('plan_start_date', '>', $todayDate)
    ->count();

$lml_active_count = \App\Models\LearnerDetail::whereNull('deleted_at')
    ->where('library_id', $currentLibId)
    ->when($currentBranchId, fn($q) => $q->where('branch_id', $currentBranchId))
    ->where('status', 1)
    ->where(fn ($q) => $q->whereNull('plan_end_date')->orWhere('plan_end_date', '>=', $todayDate))
    ->count();
$lmlStatus = request()->get('status');
$lmlPay = request()->get('payment_filter');
$lmlUrl = fn ($params) => route('learners', array_filter(array_merge(request()->except(['page', 'status', 'payment_filter']), $params), fn ($v) => $v !== null && $v !== ''));
$lmlTiles = [
    ['All members', $active_seat_count ?? $learners->total(), $lmlUrl([]), !$lmlStatus && !$lmlPay, 'users-round', 'acc'],
    ['Active', $lml_active_count, $lmlUrl(['status' => 'active']), $lmlStatus === 'active', 'user-check', 'ok'],
    ['About to expire', $about_to_expire_count, $lmlUrl(['status' => 'about_to_expire']), $lmlStatus === 'about_to_expire', 'clock-alert', 'warn'],
    ['Extension', $extended_seats ?? null, $lmlUrl(['status' => 'extended']), $lmlStatus === 'extended', 'hourglass', 'violet'],
    ['Payment due', $due_count, $lmlUrl(['payment_filter' => 'pending_payment']), $lmlPay === 'pending_payment', 'indian-rupee', 'bad'],
    ['Expired', $expired_seat ?? null, $lmlUrl(['status' => 'expired']), $lmlStatus === 'expired', 'calendar-x', 'mut'],
];
if (Route::has('future.bookings')) {
    $lmlTiles[] = ['Future bookings', $future_booking_count, route('future.bookings'), false, 'calendar-clock', 'blue'];
}

$lmlSeatMap = collect(generateSeatNumbers())->keyBy('main');
$lmlSeatLabel = function ($no) use ($lmlSeatMap) {
    if (empty($no)) return 'GEN';
    $s = $lmlSeatMap->get((int) $no);
    if (!$s) return $no;
    return (!empty($s['floor_name']) && !empty($s['floor'])) ? $s['floor'] . ' (' . shortFloorName($s['floor_name']) . ')' : $s['main'];
};
$lmlInitials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
$lmlMoney = fn ($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
@endphp


<div class="lml" id="lmlRoot" data-base="{{ route('learners') }}">

    <div class="lml-head">
        <div>
            <div class="lml-crumb">Library<x-lb-icon name="chevron-right" /><span>Members list</span></div>
            <h1 class="lml-title">Members list</h1>
        </div>
        @if(!empty($hasPendingSyncLearners))
        <div class="lml-head-r">
            <a href="javascript:void(0)" class="lml-sync" id="btnSyncLearnerStatus" title="Recalculate & sync expired learner statuses">
                <i class="fa-solid fa-arrows-rotate" id="syncStatusSpinIcon"></i> Sync status
            </a>
        </div>
        @endif
    </div>

    <div id="lmlTop">
    {{-- Status tiles --}}
    <div class="lml-tiles" role="list">
        @foreach($lmlTiles as $t)
        <a href="{{ $t[2] }}" class="lml-tile lml-tile--{{ $t[5] }} {{ $t[3] ? 'is-on' : '' }}" @if($t[3]) aria-current="true" @endif @if($t[2] !== route('future.bookings')) data-lml-nav @endif role="listitem">
            <span class="lml-tile-ic"><x-lb-icon :name="$t[4]" /></span>
            <span class="lml-tile-t"><b>{{ $t[1] ?? '—' }}</b><small>{{ $t[0] }}</small></span>
        </a>
        @endforeach
    </div>

    @if(!in_array('24', $hiddenFields) && !empty($planTypeCounts))
    <div class="lml-shiftcounts">
        <span class="lml-shiftcounts-l">By shift</span>
        @foreach($planTypeCounts as $plan)
        <span class="lml-sc" title="{{ $plan['name'] }}">{{ $plan['name'] }}<b>{{ $plan['count'] }}</b></span>
        @endforeach
        @if(!empty($genral_seat))
        <span class="lml-sc">General<b>{{ $genral_seat }}</b></span>
        @endif
        <span class="lml-sc">Non-expiry<b>{{ $non_expiry_count }}</b></span>
    </div>
    @endif
    </div>

    {{-- Toolbar: search · status · sort · filters · layout · export · add --}}
    <form action="{{ route('learners') }}" method="GET" class="lml-filter learner-filter-form" id="learnerFilterForm" role="search">
        @can('has-permission', 'Filter')
        <label class="lml-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" id="search_learner_input" placeholder="Search by name, mobile or email" value="{{ request()->get('search') }}" autocomplete="off">
            <span class="lml-spin" aria-hidden="true"></span>
        </label>
        <label class="lml-pick">
            <x-lb-icon name="clock-3" />
            <select name="status" id="status" aria-label="Status">
                <option value="">All</option>
                <option value="active" @selected($lmlStatus == 'active')>Active</option>
                <option value="about_to_expire" @selected($lmlStatus == 'about_to_expire')>About to expire</option>
                <option value="extended" @selected($lmlStatus == 'extended')>In extension</option>
                <option value="expired" @selected($lmlStatus == 'expired')>Expired</option>
            </select>
            <x-lb-icon name="chevron-down" class="lml-pick-chev" />
        </label>
        @endcan
        <label class="lml-pick">
            <x-lb-icon name="arrow-up-down" />
            @php $lmlSort = request('sort_by') === 'seat_no' ? (request('sort_order') === 'desc' ? 'desc' : 'asc') : ''; @endphp
            <select id="lmlSort" aria-label="Sort">
                <option value="" @selected($lmlSort === '')>Seat no. · default</option>
                <option value="asc" @selected($lmlSort === 'asc')>Seat no. · 1 → 99</option>
                <option value="desc" @selected($lmlSort === 'desc')>Seat no. · 99 → 1</option>
            </select>
            <x-lb-icon name="chevron-down" class="lml-pick-chev" />
            <input type="hidden" name="sort_by" value="{{ $lmlSort ? 'seat_no' : '' }}">
            <input type="hidden" name="sort_order" value="{{ $lmlSort }}">
        </label>
        @can('has-permission', 'Filter')
        @php $lmlMore = (int) request()->filled('plan_id') + (int) request()->filled('payment_filter') + (int) request()->filled('seat_no'); @endphp
        <div class="dropdown lml-more-f">
            <button type="button" class="lml-fbtn {{ $lmlMore ? 'is-on' : '' }}" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="More filters" title="More filters"><x-lb-icon name="funnel" /><span>Filters</span>@if($lmlMore)<em>{{ $lmlMore }}</em>@endif</button>
            <div class="dropdown-menu dropdown-menu-end lml-fpanel">
                <div class="lml-fpanel-h">Filter members</div>
                <label class="lml-ff"><span>Plan</span>
                    <select name="plan_id" id="plan_id" class="lp-sel">
                        <option value="">All plans</option>
                        @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(request()->get('plan_id') == $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lml-ff"><span>Payment</span>
                    <select name="payment_filter" id="payment_filter" class="lp-sel">
                        <option value="">Any payment</option>
                        <option value="paid" @selected($lmlPay == 'paid')>Paid</option>
                        <option value="pending_payment" @selected($lmlPay == 'pending_payment')>Pending payment</option>
                        <option value="failed_payment" @selected($lmlPay == 'failed_payment')>Failed payment</option>
                    </select>
                </label>
                <label class="lml-ff"><span>Seat</span>
                    <select name="seat_no" id="seat_no" class="lp-sel">
                        <option value="">All seats</option>
                        @for($sn = 1; $sn <= $totalSeats; $sn++)
                        <option value="{{ $sn }}" @selected(request()->get('seat_no') == $sn)>Seat {{ $lmlSeatLabel($sn) }}</option>
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
        <div class="dropdown">
            <button type="button" class="lml-iconbtn" data-bs-toggle="dropdown" aria-expanded="false" title="Export" aria-label="Export"><x-lb-icon name="download" /></button>
            <div class="dropdown-menu dropdown-menu-end lp-menu">
                <a href="{{ route('learners.export-csv') }}" class="lp-mi"><x-lb-icon name="file-spreadsheet" /><span>All data in CSV<small>Every member with plan details</small></span></a>
                <a href="{{ route('learners.list.pdf', request()->query()) }}" class="lp-mi" target="_blank" id="lmlPdfLink"><x-lb-icon name="file-text" /><span>Current list as PDF<small>Uses the filters you have applied</small></span></a>
                @can('has-permission', 'Import Library Seats')
                @if(!in_array('11', $hiddenFields))
                <hr>
                <a href="{{ route('library.upload.form') }}" class="lp-mi"><x-lb-icon name="upload" /><span>Import members<small>Upload a CSV to the portal</small></span></a>
                @endif
                @endcan
            </div>
        </div>
        @can('has-permission', 'Book Seat')
        <a href="javascript:;" class="lp-btn lml-add noseat_popup"><x-lb-icon name="plus" />Add member</a>
        @endcan
    </form>

    <div id="lmlLive" aria-live="polite">
    @if ( $learners->total()==0)
    <div class="lp-card lp-empty lml-empty no-data-found">
        <span><x-lb-icon name="users-round" /></span>
        @if($hasActiveFilters)
        <b>No members found</b>
        No members match the selected filters. Try changing or clearing the filters.
        <button type="button" class="lp-btn lp-btn--ghost lml-empty-clear" style="margin-top:8px"><x-lb-icon name="x" />Clear filters</button>
        @else
        <b>No members added yet</b>
        You haven’t added any members to your library yet. Book a seat to add your first member.
        @if(getCurrentBranch() !=0)
        <a href="javascript:;" class="lp-btn lml-book-first" style="margin-top:8px"><x-lb-icon name="plus" />Book seat</a>
        @else
        <span>Select your branch first from the top bar.</span>
        @endif
        @endif
    </div>
    @else

    <div class="lml-bar">
        <p><b>{{ $learners->total() }}</b> member{{ $learners->total() == 1 ? '' : 's' }}@if($learners->lastPage() > 1) · page {{ $learners->currentPage() }} of {{ $learners->lastPage() }}@endif</p>
    </div>

    <div class="lml-thead lml-thead--inline" aria-hidden="true"><span>Member</span><span>Shift · plan</span><span>Duration</span><span>Paid · due</span><span>Status</span><span></span></div>
    <div id="learnerCardsList" class="learner-cards-list lml-grid is-active">
    @foreach($learners as $key => $value)
        @include('learner.partials.lml-card')
    @endforeach
    </div>

    {{-- Pagination --}}
    @if ($learners->lastPage() > 1)
    <ul class="paginations lml-pages">
        <li><a href="{{ $learners->onFirstPage() ? '#' : $learners->previousPageUrl() }}" class="{{ $learners->onFirstPage() ? 'is-off' : '' }}" aria-label="Previous page"><x-lb-icon name="chevron-left" />Prev</a></li>
        @if ($learners->currentPage() > 3)
        <li><a href="{{ $learners->url(1) }}">1</a></li>
        <li><span>…</span></li>
        @endif
        @for ($i = max(1, $learners->currentPage() - 2); $i <= min($learners->lastPage(), $learners->currentPage() + 2); $i++)
            <li><a href="{{ $learners->url($i) }}" class="{{ $learners->currentPage() == $i ? 'active' : '' }}" @if($learners->currentPage() == $i) aria-current="page" @endif>{{ $i }}</a></li>
        @endfor
        @if ($learners->currentPage() < $learners->lastPage() - 2)
            <li><span>…</span></li>
            <li><a href="{{ $learners->url($learners->lastPage()) }}">{{ $learners->lastPage() }}</a></li>
        @endif
        <li><a href="{{ $learners->hasMorePages() ? $learners->nextPageUrl() : '#' }}" class="{{ $learners->hasMorePages() ? '' : 'is-off' }}" aria-label="Next page">Next<x-lb-icon name="chevron-right" /></a></li>
    </ul>
    @endif

    @endif
    </div>
</div>

@can('has-permission', 'View Seat')
@include('learner.partials.seat-details-drawer')
@endcan

{{-- Bottom Motivational Banner (Dashboard-style for Library Owners) --}}
@include('partials.motivational-banner')

<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable', {
            searching: false, // This option hides the search bar
            ordering: false
        });
    });

    var learnerTransactionsDataBase = @json(url('library/learners/transactions-data'));

    $(document).on('click', '.open-transaction-modal', function () {
        var learnerId = $(this).data('learner_id');
        var requestUrl = learnerTransactionsDataBase.replace(/\/+$/, '') + '/' + encodeURIComponent(learnerId);

        $('#transactionPendingBadge').addClass('d-none').text('');
        $('#transactionTableBody').html(
            '<tr><td colspan="9" class="text-center">Loading...</td></tr>'
        );
        $('#activityTableBody').html(
            '<tr><td colspan="6" class="text-center">Loading...</td></tr>'
        );

        $.ajax({
            url: requestUrl,
            type: 'GET',
            success: function (res) {
                if (!res.status) {
                    $('#transactionTableBody').html(
                        '<tr><td colspan="9" class="text-center text-danger">Unable to load transactions.</td></tr>'
                    );
                    $('#activityTableBody').html(
                        '<tr><td colspan="6" class="text-center text-danger">—</td></tr>'
                    );
                    return;
                }

                var badgeText = '';
                if (!res.transactions || res.transactions.length === 0) {
                    badgeText = 'No transaction records for this learner.';
                } else if (res.has_pending) {
                    badgeText = 'Showing rows with pending balance.';
                } else {
                    badgeText = 'No pending balance — showing latest transaction summary.';
                }
                $('#transactionPendingBadge').removeClass('d-none').text(badgeText);

                if (res.transactions && res.transactions.length > 0) {
                    var rows = '';
                    res.transactions.forEach(function (item, index) {
                        var receiptCell = item.receipt_url
                            ? '<a href="' + item.receipt_url + '" target="_blank" rel="noopener">Download</a>'
                            : '<span class="text-muted">—</span>';
                        rows += '<tr>' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td>' + (item.plan_price ?? '—') + '</td>' +
                            '<td>' + (item.other_addon_label ?? '—') + '</td>' +
                            '<td>' + (item.discount_amount ?? '—') + '</td>' +
                            '<td>' + (item.paid_amount ?? '—') + '</td>' +
                            '<td>' + (item.pending_amount ?? '—') + '</td>' +
                            '<td>' + (item.paid_date ?? '—') + '</td>' +
                            '<td>' + (item.payment_mode ?? '—') + '</td>' +
                            '<td>' + receiptCell + '</td>' +
                            '</tr>';
                    });
                    $('#transactionTableBody').html(rows);
                } else {
                    $('#transactionTableBody').html(
                        '<tr><td colspan="9" class="text-center">No transactions found.</td></tr>'
                    );
                }

                if (res.activities && res.activities.length > 0) {
                    var aRows = '';
                    res.activities.forEach(function (a) {
                        aRows += '<tr>' +
                            '<td>' + (a.particular ?? '—') + '</td>' +
                            '<td>' + (a.payment_type ?? '—') + '</td>' +
                            '<td>' + (a.payment_mode ?? '—') + '</td>' +
                            '<td>' + (a.amount_display ?? '—') + '</td>' +
                            '<td>' + (a.payment_date ?? '—') + '</td>' +
                            '<td>' + (a.dr_cr ?? '—') + '</td>' +
                            '</tr>';
                    });
                    $('#activityTableBody').html(aRows);
                } else {
                    $('#activityTableBody').html(
                        '<tr><td colspan="6" class="text-center">No activity recorded.</td></tr>'
                    );
                }
            },
            error: function () {
                $('#transactionTableBody').html(
                    '<tr><td colspan="9" class="text-center text-danger">Failed to load. Please try again.</td></tr>'
                );
                $('#activityTableBody').html(
                    '<tr><td colspan="6" class="text-center text-danger">—</td></tr>'
                );
            }
        });
    });

    function closeProfileImageModal() {
        var $m = $('#imageViewModal');
        $m.stop(true, true).animate({ opacity: 0 }, 150, function () {
            $m.css({ display: 'none' });
            $m.attr('aria-hidden', 'true');
            $('#modalImage').attr('src', '');
        });
    }

    function openProfileImageModal(imageUrl) {
        var $m = $('#imageViewModal');
        $('#modalImage').attr('src', imageUrl);
        $m.attr('aria-hidden', 'false');
        $m.css({ display: 'flex', opacity: 0, zIndex: 99999 }).stop(true, true).animate({ opacity: 1 }, 200);
    }

    // Profile photo: open large preview on click of avatar image or link
    $(document).on('click', 'a.view-image, .view-image, .learner-list-profile-photo, .learner-card .avatar-wrap img', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var imageUrl = $(this).attr('href') || $(this).attr('src') || $(this).find('img').attr('src');
        if (imageUrl && imageUrl !== '#' && imageUrl !== 'javascript:;') {
            openProfileImageModal(imageUrl);
        }
    });

    $('#imageViewModal .close-modal').on('click', function (e) {
        e.stopPropagation();
        closeProfileImageModal();
    });

    $('#imageViewModal').on('click', function (e) {
        if ($(e.target).is(this)) {
            closeProfileImageModal();
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#imageViewModal').css('display') === 'flex') {
            closeProfileImageModal();
        }
    });

    $(document).ready(function() {
        var $legendContainer = $('#seatLegendContainer');
        var $dots = $('#seatOverviewDots .overview-dot');

        function updateOverviewDots() {
            if (!$legendContainer.length || !$dots.length) return;
            var scrollLeft = $legendContainer.scrollLeft();
            var maxScroll = $legendContainer[0].scrollWidth - $legendContainer[0].clientWidth;
            if (maxScroll <= 5) return;
            var ratio = scrollLeft / maxScroll;
            var activeIdx = Math.min(Math.round(ratio * ($dots.length - 1)), $dots.length - 1);
            $dots.removeClass('active');
            $dots.eq(activeIdx).addClass('active');
        }

        $('#scrollLeftBtn').on('click', function() {
            $legendContainer.animate({ scrollLeft: '-=260px' }, 250, updateOverviewDots);
        });
        $('#scrollRightBtn').on('click', function() {
            $legendContainer.animate({ scrollLeft: '+=260px' }, 250, updateOverviewDots);
        });
        $legendContainer.on('scroll', function() {
            updateOverviewDots();
        });
        $legendContainer.on('wheel', function(e) {
            if (e.originalEvent.deltaY !== 0) {
                e.preventDefault();
                this.scrollLeft += e.originalEvent.deltaY;
                updateOverviewDots();
            }
        });

        $dots.on('click', function() {
            var idx = $(this).data('index');
            if (typeof idx !== 'undefined' && $legendContainer.length) {
                var maxScroll = $legendContainer[0].scrollWidth - $legendContainer[0].clientWidth;
                var targetScroll = (idx / ($dots.length - 1)) * maxScroll;
                $legendContainer.animate({ scrollLeft: targetScroll }, 250, updateOverviewDots);
            }
        });

        // Filter & Counts visibility: hidden on page load, toggle only when clicking relevant icon
        $('#learnerFilterContainer').hide();
        $('#countsContainer').hide();

        // Filter toggle button (#learnerFilterToggleBtn in header)
        $(document).on('click', '#learnerFilterToggleBtn, #filter', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            $('#learnerFilterContainer').stop(true, true).slideToggle(200);
        });

        // Counts toggle button (#counts in header)
        $(document).on('click', '#counts', function(e) {
            e.preventDefault();
            $(this).toggleClass('active');
            $('#countsContainer').stop(true, true).slideToggle(200, function() {
                if (typeof updateOverviewDots === 'function' && $(this).is(':visible')) {
                    updateOverviewDots();
                }
            });
        });

        // Toggle custom dropdowns
        $(document).on('click', '.learner-filter-module .custom-dropdown', function(e) {
            if ($(e.target).closest('.dropdown-item-option').length) {
                return;
            }
            e.stopPropagation();
            var $this = $(this);
            var wasOpen = $this.hasClass('open');
            
            $('.learner-filter-module .custom-dropdown').removeClass('open');
            if (!wasOpen) {
                $this.addClass('open');
            }
        });

        // Option click in custom dropdown
        $(document).on('click', '.learner-filter-module .dropdown-item-option', function(e) {
            e.stopPropagation();
            var $item = $(this);
            var $dropdown = $item.closest('.custom-dropdown');
            var val = $item.data('value');
            var text = $item.find('span').text();

            $dropdown.find('input[type="hidden"]').val(val);
            $dropdown.find('.filter-field-value').text(text);
            $dropdown.find('.dropdown-item-option').removeClass('active');
            $item.addClass('active');
            $dropdown.removeClass('open');
        });

        // Click outside closes custom dropdowns
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.learner-filter-module .custom-dropdown').length) {
                $('.learner-filter-module .custom-dropdown').removeClass('open');
            }
        });

        // Escape closes custom dropdowns
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.learner-filter-module .custom-dropdown').removeClass('open');
            }
        });

        // Search Learner box click to focus input
        $(document).on('click', '.learner-filter-module .filter-search-box', function(e) {
            if (!$(e.target).is('input')) {
                $(this).find('.filter-input').focus();
            }
        });

        // Clear filter button
        $(document).on('click', '.learner-filter-module #clearFilter', function(e) {
            e.preventDefault();
            var form = document.getElementById('learnerFilterForm');
            if (form) {
                form.reset();
                window.location.href = form.action;
            } else {
                window.location.href = "{{ route('learners') }}";
            }
        });

        $(document).on('click', '#btnSyncLearnerStatus', function() {
            let $btn = $(this);
            let $icon = $('#syncStatusSpinIcon');
            $btn.prop('disabled', true);
            $icon.addClass('fa-spin');

            $.ajax({
                url: "{{ route('learners.sync.status') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    $icon.removeClass('fa-spin');
                    $btn.prop('disabled', false);
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Synchronized',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            if (typeof window.lpRefresh === 'function') window.lpRefresh(); else location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Something went wrong', 'error');
                    }
                },
                error: function(xhr) {
                    $icon.removeClass('fa-spin');
                    $btn.prop('disabled', false);
                    Swal.fire('Error', 'Failed to update learner statuses.', 'error');
                }
            });
        });

        // Mobile collapsible subscription toggle
        $(document).on('click', '.js-mobile-collapsible-toggle', function() {
            var $banner = $(this);
            var $content = $banner.next('.mobile-collapsible-content');
            $content.slideToggle(200);
            $banner.toggleClass('is-open');
        });

        // Copy button micro-interaction
        $(document).on('click', '.copy-action-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var val = $(this).data('copy');
            if (val && navigator.clipboard) {
                navigator.clipboard.writeText(val).then(function() {
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Copied: ' + val);
                    }
                }).catch(function() {
                    if (typeof toastr !== 'undefined') {
                        toastr.info('Copied: ' + val);
                    }
                });
            }
        });

        // Mobile Actions "See All >" smooth scroll
        $(document).on('click', '.mobile-actions-seeall', function(e) {
            e.preventDefault();
            var $scroll = $(this).closest('.mobile-actions-container').find('.mobile-actions-scroll');
            if ($scroll.length) {
                var maxScroll = $scroll[0].scrollWidth - $scroll.innerWidth();
                if ($scroll.scrollLeft() >= maxScroll - 15) {
                    $scroll.animate({ scrollLeft: 0 }, 300);
                } else {
                    $scroll.animate({ scrollLeft: maxScroll }, 400);
                }
            }
        });

    });
</script>
<script src="{{ asset('public/js/lb-members.js') }}?v={{ @filemtime(public_path('js/lb-members.js')) }}" defer></script>
@endsection
