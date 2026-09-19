@php
    $hasCustomFilter = request()->filled('expense') || request()->filled('from') || request()->filled('to') || request()->filled('payment_mode');
    $currentMonth = date('m');
    $currentYear = date('Y');
@endphp

<div id="expenseNonEmptyShell">
    <!-- 1. Header Toolbar: Right-aligned buttons (Filters, Export CSV, Add Expense) -->
    <div class="heading-list py-1 d-flex justify-content-end align-items-center gap-2 mb-3">
        <div class="header-actions">
            <button type="button" class="btn btn-filter-toggle expense-toolbar-filter-toggle {{ $hasCustomFilter ? 'active' : '' }}" id="toggleFilterBtn" title="Filter Records">
                <i class="fa-solid fa-filter"></i>
                <span>Filters</span>
                @if($hasCustomFilter)
                    <span class="filter-badge-dot" title="Active Filter Applied"></span>
                @endif
            </button>

            <button type="button" class="btn btn-export-csv" id="btnExportExpenseCsv" title="Export CSV">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </button>

            <a href="javascript:;" class="btn btn-primary btn-add-expense-top" data-bs-toggle="modal" data-bs-target="#expenseModal">
                <i class="fa-solid fa-plus"></i> Add Expense
            </a>
        </div>
    </div>

    <!-- 2. 4 Financial KPI Summary Cards (2x2 on Mobile, 4 columns on Desktop) -->
    <div class="report-kpi-grid">
        <!-- Card 1: Total Recorded Outflows -->
        <div class="report-kpi-card kpi-total-exp">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Outflows</div>
                <div class="kpi-value text-danger" id="kpiTotalExpense">₹{{ number_format($totalExpenseAmount ?? 0, 2) }}</div>
                <div class="kpi-sub">Total Recorded Expenses</div>
            </div>
        </div>

        <!-- Card 2: This Month's Expenses -->
        <div class="report-kpi-card kpi-month-exp">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">This Month</div>
                <div class="kpi-value" style="color: #34939F;" id="kpiMonthExpense">₹{{ number_format($thisMonthExpense ?? 0, 2) }}</div>
                <div class="kpi-sub">Current Month Outflows</div>
            </div>
        </div>

        <!-- Card 3: Spent Today -->
        <div class="report-kpi-card kpi-today-exp">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-bolt"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Spent Today</div>
                <div class="kpi-value" style="color: #18225f;" id="kpiTodayExpense">₹{{ number_format($todayExpense ?? 0, 2) }}</div>
                <div class="kpi-sub">Today's Transactions</div>
            </div>
        </div>

        <!-- Card 4: Total Transactions -->
        <div class="report-kpi-card kpi-count-exp">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Transactions</div>
                <div class="kpi-value text-success" id="kpiCountExpense">{{ number_format($expences->total()) }} Records</div>
                <div class="kpi-sub">Expense Vouchers</div>
            </div>
        </div>
    </div>

    <!-- 3. Quick Filter Tabs -->
    <div class="report-quick-tabs">
        <button type="button" class="quick-tab-btn active" data-tab="all" id="tabAll">
            All Expenses <span class="tab-count" id="tabCountAll">{{ $expences->total() }}</span>
        </button>
        <button type="button" class="quick-tab-btn tab-online" data-tab="online" id="tabOnline">
            <i class="fa-solid fa-globe text-primary"></i> Online
            <span class="tab-count" id="tabCountOnline">{{ $metrics['online_count'] ?? 0 }}</span>
        </button>
        <button type="button" class="quick-tab-btn tab-offline" data-tab="offline" id="tabOffline">
            <i class="fa-solid fa-money-bill-wave text-success"></i> Offline / Cash
            <span class="tab-count" id="tabCountOffline">{{ $metrics['offline_count'] ?? 0 }}</span>
        </button>
        <button type="button" class="quick-tab-btn tab-paylater" data-tab="paylater" id="tabPaylater">
            <i class="fa-regular fa-clock text-warning"></i> Pay Later
            <span class="tab-count" id="tabCountPaylater">{{ $metrics['paylater_count'] ?? 0 }}</span>
        </button>
    </div>

    <!-- 4. Collapsible Single-Line Filter Bar -->
    <div class="report-filter-wrapper" id="filterContainer" style="display: {{ $hasCustomFilter ? 'block' : 'none' }};">
        <form method="get" action="{{ route('add.expense.list') }}" id="expenseFilterForm" class="single-line-filter-form">
            <div class="filter-col">
                <label class="filter-inline-label"><i class="fa-solid fa-tags"></i> Category</label>
                <select name="expense" class="form-select filter-control">
                    <option value="">All Categories</option>
                    @foreach($data as $expType)
                    <option value="{{ $expType->name }}" {{ request('expense') == $expType->name ? 'selected' : '' }}>
                        {{ $expType->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-col">
                <label class="filter-inline-label"><i class="fa-regular fa-calendar-days"></i> From Date</label>
                <input type="date" name="from" class="form-control filter-control" value="{{ request('from') }}">
            </div>

            <div class="filter-col">
                <label class="filter-inline-label"><i class="fa-regular fa-calendar-days"></i> To Date</label>
                <input type="date" name="to" class="form-control filter-control" value="{{ request('to') }}">
            </div>

            <div class="filter-col">
                <label class="filter-inline-label"><i class="fa-solid fa-credit-card"></i> Mode</label>
                <select name="payment_mode" class="form-select filter-control">
                    <option value="">All Modes</option>
                    <option value="1" {{ request('payment_mode') == '1' ? 'selected' : '' }}>Online</option>
                    <option value="2" {{ request('payment_mode') == '2' ? 'selected' : '' }}>Offline</option>
                    <option value="3" {{ request('payment_mode') == '3' ? 'selected' : '' }}>Pay Later</option>
                </select>
            </div>

            <div class="filter-col filter-col-actions">
                <span class="filter-inline-label" aria-hidden="true">&nbsp;</span>
                <div class="filter-actions-inline">
                    <button type="submit" class="btn btn-filter-apply noLoader" id="btnApplyFilter">
                        <i class="fa-solid fa-magnifying-glass"></i> Filter
                    </button>
                    <button type="button" id="expenseClearFilter" class="btn btn-filter-reset" title="Clear Filters">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- 5. AJAX Table / Records Wrapper -->
    <div id="expenseListAjaxWrapper">
        @include('master.partials.expense-list-entries', ['expences' => $expences])
    </div>
</div>
