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

            <form action="{{ route('expired.learner.report') }}" method="GET">
                <div class="row g-4">
                    
                    <div class="col-lg-4">
                        <label for="expiredyear">Filter By Year</label>
                        <select id="expiredyear" class="form-select " name="expiredyear">
                            <option value="">Select Year</option>
                            @foreach($dynamicyears as $year)
                                <!-- Default to current year if no year is selected, else use selected year -->
                                <option value="{{ $year }}" 
                                    {{ (request('expiredyear') ?? $currentYear) == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-lg-4">
                        <label for="expiredmonth">Select Month:</label>
                        <select id="expiredmonth" class="form-select " name="expiredmonth">
                            <option value="">Select Month</option>
                            @foreach($dynamicmonths as $month)
                                <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}" 
                                    {{ request('expiredmonth') == str_pad($month, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                    {{ DateTime::createFromFormat('!m', $month)->format('M') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    

                    <!-- Search By Name, Mobile & Email -->
                    <div class="col-lg-4">
                        <label for="search">Search By Name, Mobile & Email</label>
                        <input type="text" class="form-control" name="search" placeholder="Enter Name, Mobile or Email"
                            value="{{ request()->get('search') }}">
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
                        <th>Contact Info</th>
                        <th>Active Plan</th>
                        <th>Expired On</th>
                       
                    </tr>
                </thead>

                <tbody>
                    @foreach($learners as $value)
                 
                    @php
                    $today = Carbon::today();
                    $endDate = Carbon::parse($value->plan_end_date);
                    $diffInDays = $today->diffInDays($endDate, false);
                    @endphp

                    <tr>
                        <td>{{$value->learner->seat_no}}<br>
                            <small>{{$value->planType->name}}</small>
                        </td>
                        <td> <span class="uppercase truncate name" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->learner->name}}" data-bs-placement="bottom">{{$value->learner->name}}</span>
                            <br> <small>{{$value->learner->dob}}</small>
                        </td>
                        <td><span class="truncate" >
                            {!! $value->learner->email ? $value->learner->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} 
                            </span>  <br>
                            <small> +91-{{$value->learner->mobile}}</small>
                        </td>
                        <td>{{$value->plan_start_date}}<br>
                            <small>{{$value->plan->name}}</small>
                        </td>
                       
                        <td>{{$value->plan_end_date}}<br>
                           
                            @if ($diffInDays > 0)
                            <small class="text-success fs-10 d-block">Expires in {{ $diffInDays }} days</small>
                            @elseif ($diffInDays < 0)
                                <small class="text-danger fs-10 d-block">Expired {{ abs($diffInDays) }} days ago</small>
                                @else
                                <small class="text-warning fs-10 d-block">Expires today</small>
                                @endif
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