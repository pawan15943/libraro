@extends('layouts.library')
@section('content')

<!-- Content Header (Page header) -->
@php
     use Carbon\Carbon;
    
@endphp

<div class="row mb-4">
     <b class="d-block pb-3">{{$label}}</b>
    <div class="col-lg-12">
       
        <div class="table-responsive">
            @if(request('type') === 'today_collection' || request('type') === 'monthly_collection' )
                <table class="table text-center datatable border-bottom f-width" id="datatable-collection">
                    <thead>
                        <tr>
                            <th>Seat No.</th>
                            <th>Learner Info</th>
                            <th>Contact Info</th>
                            <th>Plan Info</th>
                            <th>Amount received</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($collection->isNotEmpty())
                        @foreach ($collection as $data)
                           
                            <tr>
                                <td>{{ optional($data->learner)->seat_no ?? 'GENERAL'}}</td> 
                                
                                <td><span class="uppercase truncate" data-bs-toggle="tooltip"
                                    data-bs-title="{{optional($data->learner)->name ?? ''}}" data-bs-placement="bottom">{{optional($data->learner)->name ?? ''}}</span>
                                <br> <small>{{$data->dob}}</small>
                                </td>
                            
                            <td><span class="truncate" >
                                    {!! optional($data->learner)->email ? optional($data->learner)->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} 
                                    </span> <br>
                                    <small> +91-{{optional($data->learner)->mobile}}</small>
                                </td>
                                <td>
                                {{  myPlanType($data->learnerDetail->plan_type_id)->name  ?? 'N/A' }}<br>
                                        <small>{{ myPlan($data->learnerDetail->plan_id)->name  ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    {{ $data->paid_amount ?? 'N/A' }}<br>
                                    
                                </td>
                                <td>
                                    {{ $data->paid_date ?? 'N/A' }}<br>
                                    
                                </td>
                                
                                
                            </tr>
                    
                        @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-end"><strong>Total Collection</strong></td>
                            <td colspan="2"><strong>₹{{ number_format($totalPaid, 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            @endif
            @if(request('type') === 'today_expense' || request('type') === 'monthly_expense' )
                <table class="table text-center datatable border-bottom f-width" id="datatable-expense">
                    
                    <thead>
                        <tr>
                            <th>S.N</th>
                            <th>Expense Name</th>
                            <th>Expense Amount</th>
                            <th>Expense Date</th>
                        </tr>
                    </thead>
                    @if($expenses->isNotEmpty() )
                    <tbody>
                        

                        @foreach ($expenses as $index => $expense)
                            
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $expense->expense_name ?? 'N/A' }}</td>
                                <td>₹{{ number_format($expense->amount, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($expense->created_at)->format('d-m-Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-end"><strong>Total Expense</strong></td>
                            <td colspan="2"><strong>₹{{ number_format($totalExpense, 2) }}</strong></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            @endif
            @if(request('type') === 'monthly_balance' )
                @php
                   

                    $collectionsByDate = $collection->groupBy(function ($item) {
                        return Carbon::parse($item->paid_date)->toDateString();
                    });

                    $expensesByDate = $expenses->groupBy(function ($item) {
                        return Carbon::parse($item->created_at)->toDateString();
                    });

                    $allDates = collect($collectionsByDate->keys())
                        ->merge($expensesByDate->keys())
                        ->unique()
                        ->sort();

                    $finalBalance = 0;
                @endphp

             <table class="table text-center datatable border-bottom f-width" id="datatable-balance">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Collection</th>
                        <th>Expense</th>
                        <th>Balance</th>
                        <th>Final Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allDates as $date)
                        @php
                            $collectionAmount = $collectionsByDate->get($date, collect())->sum('paid_amount');
                            $expenseAmount = $expensesByDate->get($date, collect())->sum('amount');
                            $balance = $collectionAmount - $expenseAmount;
                            $finalBalance += $balance;
                        @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                            <td>{{ number_format($collectionAmount) }}</td>
                            <td>{{ number_format($expenseAmount) }}</td>
                            <td>{{ number_format($balance) }}</td>
                            <td>{{ number_format($finalBalance) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @endif
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

                <table class="table text-center datatable border-bottom" id="datatable-today-balance">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>Collection</th>
                            <th>Expense</th>
                            <th>Balance</th>
                            <th>Final Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                        
                            @php
                           
                                $balance = $row['collection'] - $row['expense'];
                                $finalBalance += $balance;
                            @endphp
                            <tr>
                                <td>{{ number_format($row['collection']) }}</td>
                                <td>{{ number_format($row['expense']) }}</td>
                                <td>{{ number_format($balance) }}</td>
                                <td>{{ number_format($finalBalance) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif



          
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        $('#datatable-collection').DataTable();
        $('#datatable-expense').DataTable();
        $('#datatable-balance').DataTable();
        // $('#datatable-today-balance').DataTable();
    });
</script>



@endsection