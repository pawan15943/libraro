@extends('layouts.library')
@section('content')
    

<!-- Content Header (Page header) -->
@php
use Carbon\Carbon;
$currentYear = date('Y');
$currentMonth = date('m');
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

            <form action="{{ route('payment.collection.report') }}" method="GET">
                <div class="row g-4">
                        <!-- Filter By Payment Status -->
                        <div class="col-lg-2">
                            <label for="year">Filter By Year</label>
                            <select id="year" class="form-select " name="year">
                                <option value="">Select Year</option>
                                @foreach($dynamicyears as $year)
                                    <!-- Default to current year if no year is selected, else use selected year -->
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
                        <th>Learner Info</th>
                        {{-- <th>Contact Info</th> --}}
                        <th>Plan Price</th>
                         <th>Locker Amt</th>
                        <th>Total Amt</th>
                        <th>Discount</th>
                         <th>Paid Amt</th>
                        <th>Pending Amt</th>
                        <th>Paid On</th>
                        <th>Trxn Id</th>
                        <th>Receipt</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($learners as $value)
                 
                    <tr>
                        <td>{{$value->learner->seat_no ?? 'General'}}<br> 
                         
                        </td>
                        <td><span class="uppercase truncate" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->learner->name}}" data-bs-placement="bottom">{{$value->learner->name}}</span>
                            <br> <small>{{$value->learner->dob}}</small>
                        </td>
                        
                        {{-- <td><span class="truncate" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->learner->email }}" data-bs-placement="bottom"><i
                                    class="fa-solid fa-times text-danger"></i></i>
                                {{$value->learner->email }}</span> <br>
                            <small> +91-{{$value->learner->mobile}}</small>
                        </td> --}}
                        <td>₹ {{myPlanPrice($value->learner_detail_id)}}</td>
                         <td>₹ {{$value->locker_amount}} </td>
                        <td>₹ {{$value->total_amount}} </td>
                          <td>₹ {{$value->discount_amount}} </td>
                        <td><span class="text-success">₹ {{$value->paid_amount}}</span> </td>
                        <td><span class="text-danger">₹ {{$value->pending_amount}}</span> </td>
                        <td>{{$value->paid_date}} </td>
                       
                      
                        <td>{{$value->transaction_id ?? 'NA'}} </td>
                       
                       
                        <td>
                            <ul class="actionalbls">
                            @can('has-permission', 'Receipt Generation')
                                @if($value->is_paid==1)
                                <li>

                                    <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $value->learner_detail_id ?? 'NA'}}">
                                        <input type="hidden" name="type" value="learner">

                                        <button type="submit">
                                            <i class="fa fa-print"></i>
                                        </button>
                                    </form>

                                </li>
                                @endif

                            @endcan
                            </ul>
                        </td>
                        

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