<div id="expenseNonEmptyShell">
<div class="row">
    <div class="col-lg-12 text-end">
        <a href="javascript:;" class="btn btn-primary export expense-toolbar-filter-toggle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Filter"><i class="fa-solid fa-filter"></i></a>

        <a href="javascript:;" class="btn btn-primary export" data-bs-toggle="modal" data-bs-target="#expenseModal">
            <i class="fa-solid fa-plus "></i> Add Expense
        </a>
    </div>
</div>

<div class="row mb-4" id="filterContainer">
    <div class="col-lg-12">
        <div class="filter p-3 bg-white mt-4">
            <form method="get" action="{{ route('add.expense.list') }}" id="expenseFilterForm">
                <div class="row g-2">
                    <div class="col-lg-4">
                        <label>Choose Expense Type</label>
                        <select name="expense" class="form-control form-select">
                            <option value="">All Types</option>
                            @foreach($data as $expType)
                            <option value="{{ $expType->name }}" {{ request('expense') == $expType->name ? 'selected' : '' }}>
                                {{ $expType->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <label>From</label>
                        <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                    </div>

                    <div class="col-lg-4">
                        <label>To</label>
                        <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                    </div>

                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary button noLoader">Search</button>
                    </div>
                    <div class="col-lg-1 align-self-end">
                        <button type="button"
                            id="expenseClearFilter"
                            class="btn btn-secondary button"
                            data-bs-toggle="tooltip"
                            data-bs-placement="bottom"
                            data-bs-title="Clear Filter">
                            Clear
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="expenseListAjaxWrapper">
    @include('master.partials.expense-list-entries', ['expences' => $expences])
</div>
</div>
