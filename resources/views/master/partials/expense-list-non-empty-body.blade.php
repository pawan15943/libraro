<div id="expenseNonEmptyShell">
    <!-- Header Toolbar: Buttons at right -->
    <div class="expense-header-bar justify-content-end mb-3">
        <div class="expense-toolbar-actions">
            <button type="button" class="btn-expense-filter expense-toolbar-filter-toggle" data-bs-toggle="tooltip" title="Filter Records">
                <i class="fa-solid fa-filter"></i> Filters
            </button>
            <a href="javascript:;" class="btn-expense-add" data-bs-toggle="modal" data-bs-target="#expenseModal">
                <i class="fa-solid fa-plus"></i> Add Expense
            </a>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="expense-kpi-grid">
        <div class="expense-kpi-card">
            <div class="kpi-icon-wrap kpi-icon-red">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
            <div class="kpi-details">
                <p class="kpi-label">Total Filtered Amount</p>
                <h3 class="kpi-value">₹{{ number_format($totalExpenseAmount ?? 0, 2) }}</h3>
            </div>
        </div>

        <div class="expense-kpi-card">
            <div class="kpi-icon-wrap kpi-icon-teal">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="kpi-details">
                <p class="kpi-label">This Month's Total</p>
                <h3 class="kpi-value">₹{{ number_format($thisMonthExpense ?? 0, 2) }}</h3>
            </div>
        </div>

        <div class="expense-kpi-card">
            <div class="kpi-icon-wrap kpi-icon-navy">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div class="kpi-details">
                <p class="kpi-label">Total Transactions</p>
                <h3 class="kpi-value">{{ $expences->total() }} Records</h3>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="expense-filter-card" id="filterContainer" style="display: {{ (request('expense') || request('from') || request('to')) ? 'block' : 'none' }};">
        <div class="filter-card-header">
            <h5 class="filter-card-title"><i class="fa-solid fa-sliders"></i> Filter Expense Records</h5>
        </div>
        <form method="get" action="{{ route('add.expense.list') }}" id="expenseFilterForm">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="filter-form-label">Expense Category</label>
                    <select name="expense" class="filter-form-control">
                        <option value="">All Categories</option>
                        @foreach($data as $expType)
                        <option value="{{ $expType->name }}" {{ request('expense') == $expType->name ? 'selected' : '' }}>
                            {{ $expType->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="filter-form-label">From Date</label>
                    <input type="date" name="from" class="filter-form-control" value="{{ request('from') }}">
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="filter-form-label">To Date</label>
                    <input type="date" name="to" class="filter-form-control" value="{{ request('to') }}">
                </div>

                <div class="col-lg-2 col-md-6 d-flex gap-2">
                    <button type="submit" class="btn-filter-search noLoader">
                        <i class="fa-solid fa-magnifying-glass"></i> Search
                    </button>
                    <button type="button" id="expenseClearFilter" class="btn-filter-clear" title="Clear Filters">
                        Clear
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- AJAX Table Wrapper -->
    <div id="expenseListAjaxWrapper">
        @include('master.partials.expense-list-entries', ['expences' => $expences])
    </div>
</div>
