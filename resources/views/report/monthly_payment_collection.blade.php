@extends('layouts.library')
@section('content')

@can('has-permission','Monthly Revenue Report')
@php
$currentYear = date('Y');
$currentMonth = date('m');
@endphp
<style>
    .uppercase {
        text-transform: uppercase !important;
    }
    a.btn.btn-success.d-block {
        display: flex ! IMPORTANT;
        gap: .5rem;
        justify-content: center;
        align-items: center;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <form action="" method="GET">
            <div class="filter-box">
                <h4 class="mb-3">Filter Box</h4>

                <div class="row">
                   
                    <div class="col-lg-4">
                        <label>Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                    </div>

                    <div class="col-lg-4">
                        <label>End Date</label>
                        <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                    </div>
                     <div class="col-lg-4">
                        <label>Year & Month</label>
                        <div class="d-flex">
                            <select name="month" class="form-select" style="border-radius: 0 0 auto auto !important;">
                                <option value="">Select Month</option>
                                @foreach(range(1,12) as $m)
                                <option value="{{ $m }}" {{ request('month', $currentMonth) == $m ? 'selected' : '' }}>
                                    {{ date('F', mktime(0,0,0,$m,1)) }}
                                </option>
                                @endforeach
                            </select>
                            <select name="year" class="form-select form-control-sm">
                                <option value="">Select Year</option>
                                @foreach($months as $year => $monthData)
                                <option value="{{ $year }}" {{ request('year', $currentYear) == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-lg-2">
                    <button class="btn btn-primary button">
                        <i class="fa fa-search"></i> Search
                    </button>
                    
                </div>
                <div class="col-lg-2">
                    <a href="{{ route('monthly.payment.export', request()->all()) }}"
                        class="btn btn-success d-block">
                            <i class="fa fa-download"></i> Export CSV
                    </a>
                </div>
            </div>
        </form>

    </div>
    <div class="mb-3">
        
    </div>

</div>
<div class="row">
    <div class="col-lg-12">
        <div id="export" class="mb-3"></div>
        <div class="table-responsive tableRemove_scroll mt-2 mb-4">
            <table class="table text-center table-bordered">
                <thead>
                    <tr>
                        <th>Payment Date</th>
                        <th>Seat No</th>
                        <th>Name</th>
                        <th>Amount</th>
                        <th>Transaction</th>
                        <th>Payment / Expense</th>

                        <th>Payment Mode</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>

                    @forelse($groupedTransactions as $date => $rows)
                    @foreach($rows as $index => $row)
                    @php
                    $dayCr = $rows->where('dr_cr','=','Cr')->sum('amount');
                    $dayDr = $rows->where('dr_cr','=','Dr')->sum('amount');
                    $dayBalance = $dayCr - $dayDr;
                    $done_by=$row->created_by ? DB::table('library_users')->where('id',$row->created_by)->value('name') : 'Admin';
                    @endphp
                    <tr>

                        @if($index == 0)
                        <td rowspan="{{ count($rows) }}">
                            {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                        </td>
                        @endif

                        <td>{{ $row->payment_type=='EXPENSE' ? 'EXPENSE' : ($row->seat_no ?? 'GEN') }}</td>
                        <td class="uppercase text-start px-4">{{ $row->payment_type=='EXPENSE' ? $row->payment_type : ($row->name ? $row->name : '')}}</td>
                        <td class="text-left px-4 
                                {{ $row->dr_cr == 'Cr' ? 'text-success' : 'text-danger' }}" style="text-align: end !important; font-weight:700;">

                            {{ $row->dr_cr == 'Cr' ? '+' : '-' }}{{ number_format($row->amount, 0) }}
                        </td>
                        <td class="uppercase text-start px-4">{{ $row->payment_type=='EXPENSE' ? $row->particular : $row->payment_type }}</td>
                        <td>
                            <span class="{{ $row->dr_cr == 'Cr' ? 'text-success' : 'text-danger' }}">
                                {{ $row->dr_cr == 'Cr' ? 'CREDIT' : 'DEBIT' }}
                            </span>
                        </td>

                        <td>{{ $row->payment_mode }}</td>

                        @if($index == 0)
                        <td rowspan="{{ count($rows) }}" class="text-end px-3" style="font-weight: 600; font-size:.9rem;">
                            {{ $dayBalance }}
                        </td>
                        @endif
                    </tr>
                    @endforeach
             


                    @empty
                    <tr>
                        <td colspan="9">No records found</td>
                    </tr>
                    @endforelse
                           {{-- Summary Section --}}
                    @if(isset($totalCollection))
                    <tr style="background:#f8f9fa; font-weight:600;">
                        <td colspan="7" class="text-end px-3">Total Collection</td>
                        <td class="text-success text-end px-3">
                            +{{ number_format($totalCollection, 0) }}
                        </td>
                    </tr>

                    <tr style="background:#f8f9fa; font-weight:600;">
                        <td colspan="7" class="text-end px-3">Total Expense</td>
                        <td class="text-danger text-end px-3">
                            -{{ number_format($totalExpense, 0) }}
                        </td>
                    </tr>

                    <tr style="background:#f8f9fa; font-weight:600;">
                        <td colspan="7" class="text-end px-3">Total Revenue (Refund)</td>
                        <td class="text-danger text-end px-3">
                            -{{ number_format($totalRevenue, 0) }}
                        </td>
                    </tr>

                    <tr style="background:#e9ecef; font-weight:700; font-size:16px;">
                        <td colspan="7" class="text-end px-3">Grand Total</td>
                        <td class=" text-end px-3  {{ $grandTotal >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $grandTotal >= 0 ? '+' : '-' }}
                            {{ number_format(abs($grandTotal), 0) }}
                        </td>
                    </tr>
                    @endif
                   

                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="card text-center">
    <span class="text-danger">You don't have Permission to Revenue Report.</span>
</div>
@endcan



@endsection
