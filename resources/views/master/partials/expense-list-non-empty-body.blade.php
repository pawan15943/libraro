@php
    $hasCustomFilter = request()->filled('expense') || request()->filled('from') || request()->filled('to') || request()->filled('payment_mode');
    $exInr = fn ($v) => '₹' . number_format((float) $v, 0, '.', ',');
@endphp

<div id="expenseNonEmptyShell">
    <div class="lpg-stats">
        <div class="lpg-stat is-bad">
            <span class="lpg-stat-ic"><x-lb-icon name="trending-down" /></span>
            <span>Total spent</span>
            <b id="kpiTotalExpense">{{ $exInr($totalExpenseAmount ?? 0) }}</b>
            <small>All recorded expenses</small>
        </div>
        <div class="lpg-stat is-violet">
            <span class="lpg-stat-ic"><x-lb-icon name="calendar-range" /></span>
            <span>This month</span>
            <b id="kpiMonthExpense">{{ $exInr($thisMonthExpense ?? 0) }}</b>
            <small>{{ now()->format('F Y') }}</small>
        </div>
        <div class="lpg-stat is-warn">
            <span class="lpg-stat-ic"><x-lb-icon name="zap" /></span>
            <span>Today</span>
            <b id="kpiTodayExpense">{{ $exInr($todayExpense ?? 0) }}</b>
            <small>{{ now()->format('d M') }}</small>
        </div>
        <div class="lpg-stat">
            <span class="lpg-stat-ic"><x-lb-icon name="receipt-text" /></span>
            <span>Entries</span>
            <b id="kpiCountExpense">{{ number_format($expences->total()) }}</b>
            <small>Expense vouchers</small>
        </div>
    </div>

    <div class="lpg-tools lex-tools">
        <div class="lpg-seg" role="group" aria-label="Payment mode">
            <button type="button" class="lpg-seg-b quick-tab-btn is-on active" data-tab="all" id="tabAll">All<span class="lpg-n" id="tabCountAll">{{ $expences->total() }}</span></button>
            <button type="button" class="lpg-seg-b quick-tab-btn" data-tab="online" id="tabOnline"><x-lb-icon name="smartphone" />Online<span class="lpg-n" id="tabCountOnline">{{ $metrics['online_count'] ?? 0 }}</span></button>
            <button type="button" class="lpg-seg-b quick-tab-btn" data-tab="offline" id="tabOffline"><x-lb-icon name="banknote" />Cash<span class="lpg-n" id="tabCountOffline">{{ $metrics['offline_count'] ?? 0 }}</span></button>
            <button type="button" class="lpg-seg-b quick-tab-btn" data-tab="paylater" id="tabPaylater"><x-lb-icon name="clock" />Pay later<span class="lpg-n" id="tabCountPaylater">{{ $metrics['paylater_count'] ?? 0 }}</span></button>
        </div>
        <label class="lpg-search">
            <x-lb-icon name="search" />
            <input type="search" id="cardSearchInput" placeholder="Search this page" autocomplete="off">
            <button type="button" class="lpg-x d-none" id="clearSearchBtn" aria-label="Clear search"><x-lb-icon name="x" /></button>
        </label>
        <button type="button" class="lpg-ibtn expense-toolbar-filter-toggle {{ $hasCustomFilter ? 'is-on' : '' }}" id="toggleFilterBtn" title="Filters" aria-label="Filters" aria-controls="filterContainer">
            <x-lb-icon name="sliders-horizontal" />@if($hasCustomFilter)<span class="lpg-dot"></span>@endif
        </button>
    </div>

    <div class="lex-filters" id="filterContainer" style="display: {{ $hasCustomFilter ? 'block' : 'none' }};">
        <form method="get" action="{{ route('add.expense.list') }}" id="expenseFilterForm">
            <label class="is-full">Category
                <span class="lpg-field is-select">
                    <x-lb-icon name="tag" />
                    <select name="expense">
                        <option value="">All categories</option>
                        @foreach($data as $expType)
                            <option value="{{ $expType->name }}" @selected(request('expense') == $expType->name)>{{ $expType->name }}</option>
                        @endforeach
                    </select>
                </span>
            </label>
            <label>From
                <span class="lpg-field"><input type="date" name="from" value="{{ request('from') }}"></span>
            </label>
            <label>To
                <span class="lpg-field"><input type="date" name="to" value="{{ request('to') }}"></span>
            </label>
            <label class="is-full">Mode
                <span class="lpg-field is-select">
                    <x-lb-icon name="credit-card" />
                    <select name="payment_mode">
                        <option value="">All modes</option>
                        <option value="1" @selected(request('payment_mode') == '1')>Online</option>
                        <option value="2" @selected(request('payment_mode') == '2')>Offline</option>
                        <option value="3" @selected(request('payment_mode') == '3')>Pay later</option>
                    </select>
                </span>
            </label>
            <div class="lex-filters-f">
                <button type="button" id="expenseClearFilter" class="lp-btn lp-btn--ghost lp-btn--sm">Reset</button>
                <button type="submit" class="lp-btn lp-btn--sm noLoader" id="btnApplyFilter">Apply</button>
            </div>
        </form>
    </div>

    <div id="expenseListAjaxWrapper">
        @include('master.partials.expense-list-entries', ['expences' => $expences])
    </div>
</div>
