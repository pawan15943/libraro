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

            <form action="{{ route('pending.payment.report') }}" method="GET">
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
                    <!-- Filter By Plan -->
                    
                    <div class="col-lg-2">
                        <label for="plan_id">Filter By Plan</label>
                        <select name="plan_id" id="plan_id" class="form-select">
                            <option value="">Choose Plan</option>
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ request()->get('plan_id') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter By plan type Status -->
                    <div class="col-lg-3">
                        <label for="status">Filter By Plan Type </label>
                        <select name="plan_type" id="plan_type" class="form-select">
                            <option value="">Choose Plan type</option>
                            @foreach($planTypes as $key => $value)
                            <option value="{{ $value->id }}" {{ request()->get('plan_type') == $value->id ? 'selected' : '' }}>
                                {{ $value->name }}
                            </option>
                                
                            @endforeach
                        </select>
                    </div>

                    <!-- Search By Name, Mobile & Email -->
                    <div class="col-lg-3">
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
                        <th>Due date</th>
                       
                        <th>is_Extended </th>
                        <th>Make Payment</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($learners as $value)
                 
                    @php
                 
                    $today = Carbon::today();
                    $endDate = Carbon::parse($value->plan_end_date);
                    $diffInDays = $today->diffInDays($endDate, false);
                    $inextendDate = $endDate->copy()->addDays($extendDay); // Preserving the original $endDate
                    $diffExtendDay= $today->diffInDays($inextendDate, false)
                    @endphp

                    <tr>
                        <td>{{$value->learner->seat_no}}<br>
                            <small>{{$value->planType->name}}</small>
                        </td>
                        <td><span class="uppercase truncate name" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->learner->name}}" data-bs-placement="bottom">{{$value->learner->name}}</span>
                            <br> <small>{{$value->learner->dob}}</small>
                        </td>
                        <td><span class="truncate" >
                            {!! $value->learner->email ? $value->learner->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} 
                            </span> <br>
                            <small> +91-{{$value->learner->mobile}}</small>
                        </td>
                        <td>{{$value->plan_start_date}}<br>
                            <small>{{$value->plan->name}}</small>
                        </td>
                       
                        <td>{{$value->plan_end_date}}<br>
                            @if ($diffInDays > 0)
                            <small class="text-success">Plan Expires in {{ $diffInDays }} days</small>
                        @elseif ($diffInDays <= 0 && $diffExtendDay>0)
                            <small class="text-danger fs-10 d-block">Extension active! {{ abs($diffExtendDay) }} days Left.</small>
                        @elseif ($diffInDays < 0 && $diffExtendDay==0)
                            <small class="text-warning fs-10 d-block">Plan Expires today</small>
                        @else
                            <small class="text-danger fs-10 d-block">Plan Expired {{ abs($diffInDays) }} days ago</small>
                        @endif

                        </td>
                        <td>
                            @if ($diffInDays <= 0 && $diffExtendDay>0)
                                Yes
                            @else
                                 No
                            @endif
                        </td>
                        <td>
                            <ul class="actionalbls">
                            <!-- Make payment -->
                            <li><a href="{{route('learner.payment',$value->id)}}" title="Payment Lerners" class="payment-learner"><i class="fas fa-credit-card"></i></a></li>
                            </ul>
                        </td>
                        <td>
                            <ul class="actionalbls">
                             <!-- Sent Mail -->
                             <li><a href="https://web.whatsapp.com/send?phone=91{{$value->learner->mobile}}&text=Hey!%20🌟%0A%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0A%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="{{$value->learner->id}}" data-bs-toggle="tooltip" data-bs-placement="bottom" title=""  data-original-title="Send WhatsApp Reminder"><i class="fa-brands fa-whatsapp"></i></a></li>


                             <!-- Sent Mail -->
                             <li><a href="mailto:RECIPIENT_EMAIL?subject=Library Seat Renewal Reminder&body=Hey!%20🌟%0D%0A%0D%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0D%0A%0D%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="{{$value->learner->id}}" data-bs-toggle="tooltip" data-bs-placement="bottom" title=""  data-original-title="Send Email Reminders"><i class="fas fa-envelope"></i></a></li>
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