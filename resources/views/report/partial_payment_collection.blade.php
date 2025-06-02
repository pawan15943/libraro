@extends('layouts.library')
@section('content')

<!-- Content Header (Page header) -->
@php
use Carbon\Carbon;
$currentYear = date('Y');
$currentMonth = date('m');
$today = \Carbon\Carbon::today();
                            

@endphp

@if (session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif
@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif


<div class="row">
   
    <div class="col-lg-12">
        <div class="filter-box">
            <h4 class="mb-3">Filter Box</h4>

            <form action="{{ route('partial.payment.collection.report') }}" method="GET">
                <div class="row g-4">
                     
                        <div class="col-lg-2">
                            <label for="year">Filter By Year</label>
                            <select id="year" class="form-select " name="year">
                                <option value="">Select Year</option>
                                @foreach($dynamicyears as $year)
                                  
                                    <option value="{{ $year }}" 
                                        {{ (request('year') ?? $currentYear) == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-lg-2">
                            <label for="month">Select Month:</label>
                            <select id="month" class="form-select " name="month">
                                <option value="">Select Month</option>
                                @foreach($dynamicmonths as $month)
                                    <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}" 
                                        {{ request('month') == str_pad($month, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $month)->format('M') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                   
                </div>

                <div class="row mt-3">
                    <div class="col-lg-2">
                        <button class="btn btn-primary button">
                            <i class="fa fa-search"></i> Search Records
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row mb-4 mt-4">
   
    <div class="col-lg-12">
        <div class="table-responsive ">
            <table class="table text-center datatable border-bottom" id="datatable">
                <thead>
                    <tr>
                        <th>Seat No.</th>
                        <th>Learner Name</th>
                        <th>Contact Info</th>
                        <th>Pending Amt</th>
                        <th>Due Date</th>
                        <th>Payment Status</th>
                        <th>Payment Mode</th>
                        <th>Payment Date</th>
                        
                        
                        
                    </tr>
                </thead>

                <tbody>
                    @foreach($learners as $value)
                 
                    <tr>
                        <td>{{$value->seat_no ?? 'General'}}<br> 
                         
                        </td>
                        <td>{{$value->name}}</span>
                            
                        </td>
                        <td><span class="truncate" data-bs-toggle="tooltip"
                                data-bs-title=" {{ decryptData($value->email)}}" data-bs-placement="bottom"><i
                                    class="fa-solid fa-times text-danger"></i></i>
                                {{ decryptData($value->email)}}</span> <br>
                            <small> +91-{{decryptData($value->mobile)}}</small>
                        </td>
                      
                        <td>₹ {{$value->pending_amount}} </td>
                          @php
                        $dueDate = \Carbon\Carbon::parse($value->due_date);
                       @endphp
                         <td>
                        {{ $value->due_date }}

                        @if($value->status != 1 && $dueDate->lt($today))
                            @php
                                $overdueDays = $today->diffInDays($dueDate);
                            @endphp
                            <br>
                            <small class="text-danger">{{ $overdueDays }} day{{ $overdueDays > 1 ? 's' : '' }} overdue</small>
                        @endif
                    </td>
                       <td>
                     

                        @if($value->status == 1)
                            <span class="text-success">Paid</span>
                        
                        @else
                            <span class="text-warning">Unpaid</span>
                        @endif
                        </td>
                        <td>{{ $value->payment_mode ?? 'Not Paid Yet'}} </td>
                        <td>{{  $value->paid_date ?? 'Not Paid Yet'}} </td>
                     
                    </tr>
                    @endforeach
                 
                </tbody>
                

            </table>
            

        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable');
       
    });
</script>

@include('learner.script')
@endsection