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


<div class="row mb-4">
   
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
                        <th>Make Payment</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($learners as $value)
                    @foreach ($value->learnerDetails as $detail)
                    @php
                   
                    $today = Carbon::today();
                    $endDate = Carbon::parse($detail->plan_end_date);
                    $diffInDays = $today->diffInDays($endDate, false);
                    @endphp

                    <tr>
                        <td>{{$value->seat_no}}<br>
                            <small>{{$detail->planType->name}}</small>
                        </td>
                        <td><span class="uppercase truncate name" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->name}}" data-bs-placement="bottom">{{$value->name}}</span>
                            <br> <small>{{$value->dob}}</small>
                        </td>
                        <td><span class="truncate" >
                            {!! $value->email ? $value->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} 
                            </span> <br>
                            <small> +91-{{$value->mobile}}</small>
                        </td>
                        <td>{{$detail->plan_start_date}}<br>
                            <small>{{$detail->plan->name}}</small>
                        </td>
                       
                        <td>{{$detail->plan_end_date}}<br>
                            
                        
                            @if ($diffInDays > 0)
                            <small class="text-success fs-10 d-block">Expires in {{ $diffInDays }} days</small>
                            @elseif ($diffInDays < 0)
                            <small class="text-danger fs-10 d-block">Expired {{ abs($diffInDays) }} days ago</small>
                            @else
                            <small class="text-warning fs-10 d-block">Expires today</small>
                            @endif
                        </td>
                      
                        <td>
                            <ul class="actionalbls">
                            <!-- Make payment -->
                            <li><a href="{{route('learner.payment',$detail->id)}}" title="Payment Lerners" class="payment-learner"><i class="fas fa-credit-card"></i></a></li>
                            </ul>
                        </td>
                        <td>
                            <ul class="actionalbls">
                             <!-- Sent Mail -->
                             <li><a target="_blank" href="https://wa.me/{{ $value->mobile }}?text={{ rawurlencode("Dear {$value->name},\n\nYour plan expired on {$value->plan_end_date}.\n\nPlease renew it as soon as possible to continue uninterrupted access to your library seat.\n\nFor help, feel free to contact our support team.\n\n– Team Libraro") }}">
                                        <i class="fab fa-whatsapp"></i>
                                    </a></li>

                             <!-- Sent Mail -->
                             <li><a href="mailto:RECIPIENT_EMAIL?subject=Library Seat Renewal Reminder&body=Hey!%20🌟%0D%0A%0D%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0D%0A%0D%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="{{$value->id}}" data-bs-toggle="tooltip" data-bs-placement="bottom" title=""  data-original-title="Send Email Reminders"><i class="fas fa-envelope"></i></a></li>
                            </ul>
                        </td>

                    </tr>
                    @endforeach
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