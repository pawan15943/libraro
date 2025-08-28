@extends('layouts.library')
@section('content')

<!-- Content Header (Page header) -->
@php
use Carbon\Carbon;

@endphp


<div class="row g-4">
    <div class="col-lg-4 col-md-6 col-6">
        <div class="revenue-box">
            <h4>{{number_format($today_booking_amt)}}</h4>
            <span>Today's Booking Income (A)</span>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 col-6">
        <div class="revenue-box">
            <h4>{{number_format($today_other_amt)}}</h4>
            <span>Today's Other Income (Token, Misc.) (B)</span>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 col-6">
        <div class="revenue-box">
            <h4>{{number_format($today_expense)}}</h4>
            <span>Today's Expenses (C)</span>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 col-6">
        <div class="revenue-box">
            <h4>{{number_format($today_refund)}}</h4>
            <span>Today’s Refunds (D)</span>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 col-6">
        <div class="revenue-box">
            <h4>{{number_format($today_pending)}}</h4>
            <span>Today’s Pending Receipts (E)</span>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 col-6">
        <div class="revenue-box">
            <h4>{{number_format($total_revenue)}}</h4>
            <span>Today’s Total Revenue (A + B + E - (C +D))</span>
        </div>
    </div>

    <!-- <div class="col-lg-4 col-md-6 col-6">
          <div class="revenue-box">
            <h4>1520</h4>
            <span>Montly Refunds</span>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="revenue-box">
            <h4>1520</h4>
            <span>This Month’s Revenue</span>
          </div>
        </div> -->
</div>
<!-- Fileter Layout -->

<div class="filter p-3 bg-white mt-4">
    <h4><i class="fa fa-filter"></i> Filter Expenses</h4>
    <form method="GET" action="{{ route('library.transaction.view') }}">
        <input type="hidden" name="type" value="{{request('type')}}">
        <div class="row g-4">
            <div class="col-lg-4">
                <label>Choose Payment Type</label>
                <select name="payment_type" class="form-control form-select">
                    <option value="">All Types</option>
                    <option value="EXPENSE" {{ request('payment_type') == 'EXPENSE' ? 'selected' : '' }}>EXPENSE</option>
                    <option value="SEAT ASSIGNMENT" {{ request('payment_type') == 'SEAT ASSIGNMENT' ? 'selected' : '' }}>SEAT ASSIGNMENT</option>
                    <option value="RENEW" {{ request('payment_type') == 'RENEW' ? 'selected' : '' }}>RENEW</option>
                    <option value="REACTIVE" {{ request('payment_type') == 'REACTIVE' ? 'selected' : '' }}>REACTIVE</option>
                    <option value="TOKEN MONEY" {{ request('payment_type') == 'TOKEN MONEY' ? 'selected' : '' }}>TOKEN MONEY</option>
                    <option value="MISCELLANEOUS" {{ request('payment_type') == 'MISCELLANEOUS' ? 'selected' : '' }}>MISCELLANEOUS</option>
                    <option value="PENDING" {{ request('payment_type') == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                    <option value="REFUND" {{ request('payment_type') == 'REFUND' ? 'selected' : '' }}>REFUND</option>
                    <option value="CHANGE PLAN" {{ request('payment_type') == 'CHANGE PLAN' ? 'selected' : '' }}>CHANGE PLAN</option>

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

            <div class="col-lg-3">
                <input type="submit" class="btn btn-primary button" value="Search">

            </div>
        </div>
</div>
</form>

<!-- Daily Collections Block Starts here -->
@if(request('type') === 'today_collection' || request('type') === 'monthly_collection' )
<h4 class="py-4">{{ $label }} Summary</h4>
<div class="row">
    <div class="col-lg-12 ">
        <p>
            <b>{{ $collection->total() }} Records — showing {{ $collection->perPage() }} per page</b>
        </p>
    </div>
</div>
<div class="row g-4 mb-4">
    
    @forelse ($collection as $data)
    @if($collection->isNotEmpty())
    <div class="col-lg-12">
        <div class="revenue-info">
            <ul>
                <li style="width: 8%">
                    <div class="icon">
                        @if($data->dr_cr=='Cr')
                        <i class="fa fa-long-arrow-right text-success"></i>
                        @else
                        <i class="fa fa-long-arrow-left text-danger"></i>
                        @endif

                    </div>
                </li>
                <li><span>Trxn. Id</span><a href="#" class="d-block">{{ $data->transaction_id ?? 'N/A' }}</a></li>

                <li><span>Seat Info</span>
                    <p class="truncate">{{ $data->learner->seat_no ?? 'GENERAL' }} :
                        {{ $data->learner->name ?? '' }}
                    </p>
                </li>
                <li><span>Trxn. Type</span>
                    <p>{{ $data->payment_type ?? '' }}</p>
                </li>
                <li><span>Trxn. Amt</span>
                    <p class="{{ $data->dr_cr === 'Cr' ? 'text-success' : 'text-danger' }}">
                        {{ $data->dr_cr }} : {{ $data->amount }}
                    </p>
                </li>
                <li><span>Trxn. Date</span>
                    <p>{{ $data->date ?? 'N/A' }}</p>
                </li>
                <li><span>Created by</span>
                    <p class="truncate">{{ $data->created_by ?? 'N/A' }}</p>
                </li>
            </ul>
        </div>
    </div>
    @endif
    @empty

    <div class="col-lg-12 text-center">
        <p>No expense records found.</p>
    </div>

    @endforelse
    
</div>
@if ($collection->lastPage() > 1)
<ul class="paginations mt-4">
    {{-- Prev --}}
    <li>
        <a href="{{ $collection->onFirstPage() ? '#' : $collection->previousPageUrl() }}" class="w-auto px-3 text-muted">Prev</a>
    </li>

    {{-- Page Numbers (shortened: 1 ... current ... last) --}}
    @if ($collection->currentPage() > 3)
        <li><a href="{{ $collection->url(1) }}">1</a></li>
        <li><span>...</span></li>
    @endif

    @for ($i = max(1, $collection->currentPage() - 2); $i <= min($collection->lastPage(), $collection->currentPage() + 2); $i++)
        <li>
            <a href="{{ $collection->url($i) }}" class="{{ $collection->currentPage() == $i ? 'active' : '' }}">
                {{ $i }}
            </a>
        </li>
    @endfor

    @if ($collection->currentPage() < $collection->lastPage() - 2)
        <li><span>...</span></li>
        <li><a href="{{ $collection->url($collection->lastPage()) }}">{{ $collection->lastPage() }}</a></li>
    @endif

    {{-- Next --}}
    <li>
        <a href="{{ $collection->hasMorePages() ? $collection->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted">Next</a>
    </li>
</ul>
@endif
@endif
<!-- Daily Collections Block Ends here -->




<!-- Expense Block Starts Here -->
@if(request('type') === 'today_expense' || request('type') === 'monthly_expense' )
<h4 class="py-4">Daily Expense Summery</h4>
@if($expenses->isNotEmpty() )
<!-- Loop Start -->
<div class="row">
    @foreach ($expenses as $index => $expense)
    <div class="col-lg-12">
        <div class="revenue-info">
            <ul>
                <li>
                    <div class="icon">
                        <i class="fa fa-long-arrow-left text-danger"></i>
                    </div>
                </li>
                <li>
                    <span>Expense Name</span>
                    <p>{{ $expense->particular ?? 'N/A' }}</p>
                </li>
                <li>
                    <span>Expense Amount</span>
                    <p>₹{{ number_format($expense->amount, 2) }}</p>
                </li>
                <li>
                    <span>Expense Date</span>
                    <p>{{ \Carbon\Carbon::parse($expense->date)->format('d-m-Y') }}</p>
                </li>

                <li>
                    <p><a href=""><i class="fa fa-print"></i> Downlaod Receipt</a></p>
                </li>
            </ul>
        </div>
    </div>
    @endforeach
</div>
<!-- Loop End -->
@endif
@endif
<!-- Expense Block Ends Here -->

<!-- Monthly Block Starts here -->
@if(request('type') === 'monthly_balance' )
@php
$collectionsByDate = $collection->groupBy(function ($item) {
return Carbon::parse($item->date)->toDateString();
});

$expensesByDate = $expenses->groupBy(function ($item) {
return Carbon::parse($item->date)->toDateString();
});

$allDates = collect($collectionsByDate->keys())
->merge($expensesByDate->keys())
->unique()
->sort();

$finalBalance = 0;
@endphp
<div class="row">
    <!-- Loop -->
    @foreach($allDates as $date)
    @php
    $collectionAmount = $collectionsByDate->get($date, collect())->sum('paid_amount');
    $expenseAmount = $expensesByDate->get($date, collect())->sum('amount');
    $balance = $collectionAmount - $expenseAmount;
    $finalBalance += $balance;
    @endphp
    <div class="col-lg-12">
        <div class="revenue-info">
            <ul>
                <li>
                    <span>Date</span>
                    <p>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</p>
                </li>
                <li>
                    <span>Collection</span>
                    <p>{{ number_format($collectionAmount) }}</p>
                </li>
                <li>
                    <span>Expense</span>
                    <p>{{ number_format($expenseAmount) }}</p>
                </li>
                <li>
                    <span>Balance</span>
                    <p>{{ number_format($balance) }}</p>
                </li>
                <li>
                    <span>Final Balance</span>
                    <p>{{ number_format($finalBalance) }}</p>
                </li>

            </ul>
        </div>
    </div>
    @endforeach
</div>
<!-- Loop Ends -->
@endif
<!-- Block Ends Here -->



<!-- Daily Balence Block Starts Here -->
@if(request('type') === 'today_balance')
@php
$rows = [];
$seq = 0;

// Add collections (ordered as they appear)
foreach ($todayCollection as $txn) {
$rows[] = [
'collection' => (float) $txn->paid_amount,
'expense' => 0,
'date' => $txn->paid_date,
'order' => $seq++,
];
}

// Add expenses (ordered as they appear)
foreach ($todayExpense as $exp) {
$rows[] = [
'collection' => 0,
'expense' => (float) $exp->amount,
'date' => \Carbon\Carbon::parse($exp->created_at)->format('Y-m-d'),
'order' => $seq++,
];
}

// Sort by date, then by entry order
$rows = collect($rows)->sortBy([
['date', 'asc'],
['order', 'asc']
])->values();

$finalBalance = 0;
@endphp
<h4 class="py-4">Final Daily Balance</h4>

<div class="row g-4">
    <!-- Loop -->
    @foreach ($rows as $row)
    @php
    $balance = $row['collection'] - $row['expense'];
    $finalBalance += $balance;
    @endphp
    <div class="col-lg-12">
        <div class="revenue-info">
            <ul>

                <li>
                    <span>Collection</span>
                    <p>{{ number_format($row['collection']) }}</p>
                </li>
                <li>
                    <span>Expense</span>
                    <p>{{ number_format($row['expense']) }}</p>
                </li>
                <li>
                    <span>Balance</span>
                    <p>{{ number_format($balance) }}</p>
                </li>
                <li>
                    <span>Final Balance</span>
                    <p>{{ number_format($finalBalance) }}</p>
                </li>
            </ul>
        </div>
    </div>
    @endforeach
    <!-- loop End -->
</div>
@endif

<!-- Ends Here -->

<script>
    $(document).ready(function() {
        $('#datatable-collection').DataTable();
        $('#datatable-expense').DataTable();
        $('#datatable-balance').DataTable();
        // $('#datatable-today-balance').DataTable();
    });

</script>



@endsection
