@extends('layouts.library')
@section('content')

@php
$activeFilter = request()->get('filter', 'all');
$tabs = [
    'all' => 'All',
    'pending' => 'Pending',
    'overdue' => 'Overdue',
    'adjusted' => 'Adjusted',
    'expired' => 'Expired',
    'received' => 'Received',
];
@endphp

<style>
    /* Scoped Pending Payment Module Styles */
    .pp-module {
        font-family: 'Outfit', sans-serif;
    }

    /* Summary Metric Cards */
    .pp-summary-card {
        background: linear-gradient(135deg, #18225f 0%, #1e293b 100%);
        color: #ffffff;
        border-radius: 12px;
        padding: 1rem 1.15rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 3px 12px rgba(24, 34, 95, 0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .pp-summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 16px rgba(24, 34, 95, 0.15);
    }

    .pp-summary-card .card-label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        margin-bottom: 0.25rem;
        font-weight: 500;
    }

    .pp-summary-card .card-value {
        font-size: 1.45rem;
        font-weight: 600;
        color: #ffffff;
        margin: 0;
        line-height: 1.2;
    }

    .pp-summary-card .card-icon-watermark {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 2.3rem;
        color: rgba(255, 255, 255, 0.08);
        pointer-events: none;
    }

    /* Tabs Bar - Reduced Spacing */
    .pp-module .nav-pills {
        gap: 0.25rem !important;
    }

    .pp-module .nav-pills .nav-link {
        color: #475569;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.84rem;
        padding: 0.32rem 0.8rem;
        transition: all 0.15s ease;
    }

    .pp-module .nav-pills .nav-link:hover {
        background: #f1f5f9;
        color: #18225f;
    }

    .pp-module .nav-pills .nav-link.active {
        background: #18225f !important;
        color: #ffffff !important;
        border-color: #18225f !important;
        box-shadow: 0 2px 6px rgba(24, 34, 95, 0.18);
    }

    /* Filter Card */
    .pp-filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    }

    .pp-filter-card .form-control,
    .pp-filter-card .form-select {
        font-size: 0.85rem;
        font-weight: 400;
        border-radius: 6px;
        border-color: #cbd5e1;
    }

    .pp-filter-card .form-control:focus,
    .pp-filter-card .form-select:focus {
        border-color: #18225f;
        box-shadow: 0 0 0 0.15rem rgba(24, 34, 95, 0.12);
    }

    /* Table & Card Wrapper */
    .pp-table-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        overflow: hidden;
    }

    .pp-custom-table thead th {
        background-color: #18225f !important;
        color: #ffffff !important;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 0.8rem 0.95rem;
        border: none;
    }

    .pp-custom-table tbody tr {
        transition: background-color 0.15s ease;
        border-bottom: 1px solid #f1f5f9;
    }

    .pp-custom-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .pp-seat-badge {
        background-color: #f1f5f9;
        color: #18225f;
        border: 1px solid #cbd5e1;
        font-size: 0.8rem;
        font-weight: 500;
        padding: 0.3rem 0.6rem;
    }

    .pp-avatar-circle {
        width: 34px;
        height: 34px;
        min-width: 34px;
        border-radius: 50%;
        background-color: #18225f;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.84rem;
    }

    .pp-learner-name {
        color: #0f172a;
        font-size: 0.88rem;
        font-weight: 600;
        transition: color 0.15s ease;
    }

    .pp-learner-name:hover {
        color: #18225f;
    }

    .pp-pay-btn {
        background-color: #18225f !important;
        border-color: #18225f !important;
        color: #ffffff !important;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.8rem;
        padding: 0.32rem 0.75rem;
        transition: all 0.15s ease;
    }

    .pp-pay-btn:hover {
        background-color: #0f172a !important;
        border-color: #0f172a !important;
        transform: translateY(-1px);
    }

    /* Loading Overlay */
    .pp-loading-overlay {
        position: relative;
        min-height: 200px;
    }

    .pp-spinner-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: 14px;
    }
</style>

<div class="pp-module pb-4">
    <!-- Top 4 Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6 col-6">
            <div class="pp-summary-card">
                <div class="card-label">Total Pending</div>
                <div class="card-value" id="summaryTotalPending">
                    ₹ {{ rtrim(rtrim(number_format((float) $summary['total_pending_amount'], 2, '.', ''), '0'), '.') }}
                </div>
                <i class="fa-solid fa-hourglass-half card-icon-watermark"></i>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-6">
            <div class="pp-summary-card">
                <div class="card-label">Expired Learner Due</div>
                <div class="card-value" id="summaryExpiredDue">
                    ₹ {{ rtrim(rtrim(number_format((float) $summary['expired_learner_due_amount'], 2, '.', ''), '0'), '.') }}
                </div>
                <i class="fa-solid fa-user-xmark card-icon-watermark"></i>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-6">
            <div class="pp-summary-card">
                <div class="card-label">Adjusted</div>
                <div class="card-value" id="summaryAdjusted">
                    ₹ {{ rtrim(rtrim(number_format((float) $summary['adjusted_pending_amount'], 2, '.', ''), '0'), '.') }}
                </div>
                <i class="fa-solid fa-scale-balanced card-icon-watermark"></i>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-6">
            <div class="pp-summary-card">
                <div class="card-label">Final Due</div>
                <div class="card-value" id="summaryFinalDue">
                    ₹ {{ rtrim(rtrim(number_format((float) $summary['final_due'], 2, '.', ''), '0'), '.') }}
                </div>
                <i class="fa-solid fa-hand-holding-dollar card-icon-watermark"></i>
            </div>
        </div>
    </div>

    <!-- Filter Pills Tabs -->
    <div class="row mb-3">
        <div class="col-lg-12">
            <ul class="nav nav-pills" id="ppTabs">
                @foreach($tabs as $key => $label)
                <li class="nav-item">
                    <a class="nav-link pp-filter-tab {{ $activeFilter == $key ? 'active' : '' }}"
                       href="javascript:void(0)"
                       data-filter="{{ $key }}">
                        {{ $label }}
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="pp-filter-card p-3">
                <form id="ppFilterForm" action="{{ route('learner.pending.payment.list') }}" method="GET">
                    <input type="hidden" name="filter" id="activeFilterInput" value="{{ $activeFilter }}">
                    <input type="hidden" name="page" id="activePageInput" value="{{ request()->get('page', 1) }}">

                    <div class="row g-2 align-items-center">
                        <div class="col-lg-3 col-md-6">
                            <input type="text" class="form-control" name="search" id="ppSearchInput" 
                                   placeholder="Search by Name, Mobile or Seat No" 
                                   value="{{ request()->get('search') }}">
                        </div>

                        <div class="col-lg-2 col-md-3">
                            <select name="plan_type_id[]" id="ppPlanTypeSelect" class="form-select">
                                <option value="">All Shifts / Plans</option>
                                @foreach($planTypes as $planType)
                                <option value="{{ $planType->id }}" {{ in_array($planType->id, (array) request()->get('plan_type_id', [])) ? 'selected' : '' }}>
                                    {{ $planType->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-3">
                            <select name="sort_by" id="ppSortBySelect" class="form-select">
                                <option value="seat_no" {{ request()->get('sort_by') == 'seat_no' ? 'selected' : '' }}>Sort: Seat No</option>
                                <option value="name" {{ request()->get('sort_by') == 'name' ? 'selected' : '' }}>Sort: Name</option>
                                <option value="expire_date" {{ request()->get('sort_by') == 'expire_date' ? 'selected' : '' }}>Sort: Expire Date</option>
                                <option value="gen" {{ request()->get('sort_by') == 'gen' ? 'selected' : '' }}>Sort: General Seat</option>
                            </select>
                        </div>

                        <div class="col-lg-1 col-md-2">
                            <select name="sort_order" id="ppSortOrderSelect" class="form-select">
                                <option value="asc" {{ request()->get('sort_order', 'asc') == 'asc' ? 'selected' : '' }}>Asc</option>
                                <option value="desc" {{ request()->get('sort_order') == 'desc' ? 'selected' : '' }}>Desc</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-3">
                            <input type="date" class="form-control" name="from_date" id="ppFromDate" 
                                   value="{{ request()->get('from_date') }}" title="From Due Date">
                        </div>

                        <div class="col-lg-2 col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 font-outfit" style="background-color: #18225f; border-color: #18225f;">
                                <i class="fa-solid fa-filter me-1"></i> Filter
                            </button>
                            <button type="button" id="ppResetBtn" class="btn btn-outline-secondary font-outfit" title="Reset Filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Table Container (AJAX Target) -->
    <div id="pendingPaymentTableWrapper" class="pp-loading-overlay">
        @include('learner.partials.pending-payment-table')
    </div>
</div>

<script>
    $(document).ready(function () {
        function fetchPendingPayments(pushUrl = true) {
            const $wrapper = $('#pendingPaymentTableWrapper');
            
            // Add subtle spinner backdrop
            if ($wrapper.find('.pp-spinner-backdrop').length === 0) {
                $wrapper.append('<div class="pp-spinner-backdrop"><div class="spinner-border" style="color: #18225f;" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            }

            const formData = $('#ppFilterForm').serialize();
            const currentUrl = "{{ route('learner.pending.payment.list') }}?" + formData;

            $.ajax({
                url: "{{ route('learner.pending.payment.list') }}",
                type: "GET",
                data: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (response) {
                    if (response.html) {
                        $wrapper.html(response.html);
                    }

                    // Update summary counts if returned
                    if (response.summary) {
                        if (response.summary.total_pending_amount !== undefined) {
                            $('#summaryTotalPending').text('₹ ' + parseFloat(response.summary.total_pending_amount).toFixed(2).replace(/\.?0+$/, ''));
                        }
                        if (response.summary.expired_learner_due_amount !== undefined) {
                            $('#summaryExpiredDue').text('₹ ' + parseFloat(response.summary.expired_learner_due_amount).toFixed(2).replace(/\.?0+$/, ''));
                        }
                        if (response.summary.adjusted_pending_amount !== undefined) {
                            $('#summaryAdjusted').text('₹ ' + parseFloat(response.summary.adjusted_pending_amount).toFixed(2).replace(/\.?0+$/, ''));
                        }
                        if (response.summary.final_due !== undefined) {
                            $('#summaryFinalDue').text('₹ ' + parseFloat(response.summary.final_due).toFixed(2).replace(/\.?0+$/, ''));
                        }
                    }

                    if (pushUrl && window.history && window.history.pushState) {
                        window.history.pushState(null, '', currentUrl);
                    }
                },
                error: function () {
                    $wrapper.find('.pp-spinner-backdrop').remove();
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Failed to load pending payments. Please try again.');
                    }
                }
            });
        }

        // 1. Tab Click Filter (AJAX)
        $('.pp-filter-tab').on('click', function (e) {
            e.preventDefault();
            const filterKey = $(this).data('filter');

            $('.pp-filter-tab').removeClass('active');
            $(this).addClass('active');

            $('#activeFilterInput').val(filterKey);
            $('#activePageInput').val(1);

            fetchPendingPayments();
        });

        // 2. Form Submit Filter (AJAX)
        $('#ppFilterForm').on('submit', function (e) {
            e.preventDefault();
            $('#activePageInput').val(1);
            fetchPendingPayments();
        });

        // 3. Reset Button (AJAX)
        $('#ppResetBtn').on('click', function () {
            $('#ppSearchInput').val('');
            $('#ppPlanTypeSelect').val('');
            $('#ppSortBySelect').val('seat_no');
            $('#ppSortOrderSelect').val('asc');
            $('#ppFromDate').val('');
            $('#activePageInput').val(1);

            fetchPendingPayments();
        });

        // 4. Pagination Click (AJAX)
        $(document).on('click', '.ajax-page-link', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page && page > 0) {
                $('#activePageInput').val(page);
                fetchPendingPayments();
                $('html, body').animate({ scrollTop: $('#pendingPaymentTableWrapper').offset().top - 120 }, 200);
            }
        });

        // 5. Browser Back / Forward handler
        window.onpopstate = function () {
            const urlParams = new URLSearchParams(window.location.search);
            const filter = urlParams.get('filter') || 'all';
            const page = urlParams.get('page') || 1;
            const search = urlParams.get('search') || '';

            $('#activeFilterInput').val(filter);
            $('#activePageInput').val(page);
            $('#ppSearchInput').val(search);

            $('.pp-filter-tab').removeClass('active');
            $('.pp-filter-tab[data-filter="' + filter + '"]').addClass('active');

            fetchPendingPayments(false);
        };
    });
</script>

@endsection

