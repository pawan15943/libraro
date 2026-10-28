@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-members.css') }}?v={{ @filemtime(public_path('css/lb-members.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}" />
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
// Computed once per page load — branch/library-level settings used by the row actions.
$hiddenFields = toggleHideField();
$currentBranchName = getCurrentBranchName();
$isNotificationActive = notificationActive();
$isWabaNotificationActive = $isNotificationActive && wabaNotificationActive();
$isTextNotificationActive = $isNotificationActive && textNotificationActive();

$lmlSeatMap = collect(generateSeatNumbers())->keyBy('main');
$lmlSeatLabel = function ($no) use ($lmlSeatMap) {
    if (empty($no)) return 'GEN';
    $s = $lmlSeatMap->get((int) $no);
    if (!$s) return $no;
    return (!empty($s['floor_name']) && !empty($s['floor'])) ? $s['floor'] . ' (' . shortFloorName($s['floor_name']) . ')' : $s['main'];
};
$lmlInitials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
$lmlMoney = fn ($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');

$lsrQuery = trim((string) request()->get('search'));
$lsrExpired = request()->boolean('include_expired');
if ($learners && method_exists($learners, 'appends')) {
    $learners->appends(request()->except('page'));
}
@endphp

@can('has-permission', 'Search Learner')
<div class="lml lsr" id="lmlRoot" data-base="{{ route('learner.search') }}">

    <div class="lml-head">
        <div>
            <div class="lml-crumb">Members<x-lb-icon name="chevron-right" /><span>Search</span></div>
            <h1 class="lml-title">Find a member</h1>
            <p class="lsr-sub">Search everyone by name, mobile number, UID or seat — results update as you type.</p>
        </div>
    </div>

    <form action="{{ route('learner.search') }}" method="GET" class="lml-filter lsr-form" id="learnerFilterForm" role="search">
        <label class="lml-search lsr-search">
            <x-lb-icon name="search" />
            <input type="search" name="search" id="search_learner_input" placeholder="Name, mobile, UID or seat no." value="{{ $lsrQuery }}" autocomplete="off" enterkeyhint="search" @if($lsrQuery === '') autofocus @endif>
            <span class="lml-spin" aria-hidden="true"></span>
        </label>
        <label class="lsr-switch">
            <input type="checkbox" name="include_expired" value="1" id="includeExpiredToggle" @checked($lsrExpired)>
            <span class="lsr-track" aria-hidden="true"><i></i></span>
            <span class="lsr-switch-t">Include expired &amp; closed</span>
        </label>
        <div class="lml-view" role="group" aria-label="Layout">
            <button type="button" data-view="grid" class="is-on" aria-pressed="true" title="Cards" aria-label="Cards"><x-lb-icon name="layout-grid" /></button>
            <button type="button" data-view="list" aria-pressed="false" title="List" aria-label="List"><x-lb-icon name="list" /></button>
        </div>
    </form>

    <div id="lmlTop"></div>

    <div id="lmlLive" aria-live="polite">
    @if($learners === null)
        <section class="lsr-start">
            <span class="lsr-start-ic"><x-lb-icon name="user-search" /></span>
            <b>Who are you looking for?</b>
            <p>Start typing above. You can search with any of these:</p>
            <div class="lsr-ways">
                <span><x-lb-icon name="user-round" />Name<small>e.g. Rahul</small></span>
                <span><x-lb-icon name="phone" />Mobile<small>last 4 digits work</small></span>
                <span><x-lb-icon name="id-card" />UID<small>e.g. LN006734</small></span>
                <span><x-lb-icon name="armchair" />Seat<small>e.g. 12</small></span>
            </div>
        </section>
    @elseif(method_exists($learners, 'total') && $learners->total() == 0)
        <div class="lp-card lp-empty lml-empty no-data-found">
            <span><x-lb-icon name="user-x" /></span>
            <b>No members match “{{ $lsrQuery }}”</b>
            @if($lsrExpired)
                Try a different spelling, the last 4 digits of the mobile number, or the seat number.
            @else
                They may have an expired or closed seat. Turn on <b>Include expired &amp; closed</b> to search everyone.
            @endif
        </div>
    @else
        <div class="lml-bar">
            <p><b>{{ $learners->total() }}</b> match{{ $learners->total() == 1 ? '' : 'es' }} for “{{ $lsrQuery }}”{{ $lsrExpired ? ' · including expired' : '' }}@if($learners->lastPage() > 1) · page {{ $learners->currentPage() }} of {{ $learners->lastPage() }}@endif</p>
        </div>

        <div class="lml-thead lml-thead--inline" aria-hidden="true"><span>Member</span><span>Shift · plan</span><span>Duration</span><span>Paid · due</span><span>Status</span><span></span></div>
        <div id="learnerCardsList" class="learner-cards-list lml-grid is-active">
            @foreach($learners as $key => $value)
                @include('learner.partials.lml-card')
            @endforeach
        </div>

        @if($learners->lastPage() > 1)
        <ul class="paginations lml-pages">
            <li><a href="{{ $learners->onFirstPage() ? '#' : $learners->previousPageUrl() }}" class="{{ $learners->onFirstPage() ? 'is-off' : '' }}" aria-label="Previous page"><x-lb-icon name="chevron-left" />Prev</a></li>
            @for ($i = max(1, $learners->currentPage() - 2); $i <= min($learners->lastPage(), $learners->currentPage() + 2); $i++)
                <li><a href="{{ $learners->url($i) }}" class="{{ $learners->currentPage() == $i ? 'active' : '' }}" @if($learners->currentPage() == $i) aria-current="page" @endif>{{ $i }}</a></li>
            @endfor
            <li><a href="{{ $learners->hasMorePages() ? $learners->nextPageUrl() : '#' }}" class="{{ $learners->hasMorePages() ? '' : 'is-off' }}" aria-label="Next page">Next<x-lb-icon name="chevron-right" /></a></li>
        </ul>
        @endif
    @endif
    </div>
</div>
@else
<div class="lpg lpg--narrow">
    <div class="lpg-empty">
        <span><x-lb-icon name="lock" /></span>
        <b>You don’t have access to search</b>
        <p>Ask the library owner to give your account the “Search Learner” permission.</p>
    </div>
</div>
@endcan

@can('has-permission', 'View Seat')
@include('learner.partials.seat-details-drawer')
@endcan

@include('learner.partials.lml-page-script')

<script>
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

    // The expired switch re-runs the search in place (lb-members.js handles the form submit)
    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'includeExpiredToggle') {
            var f = document.getElementById('learnerFilterForm');
            if (f.requestSubmit) f.requestSubmit(); else f.submit();
        }
    });
</script>

@endsection
