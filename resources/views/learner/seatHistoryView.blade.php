@extends('layouts.library')
@section('content')
@php
use App\Models\Learner;
use App\Models\PlanType;
use Carbon\Carbon;
$today = Carbon::today();
@endphp


<div class="row">
    <div class="col-lg-12">
        

        @if($learners->isEmpty())
        <p class="not-found info-message">
        <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
    
        There is currently no history available for this seat for any learners.</p>
        @else
        <div class="table-responsive mt-2">
            <table class="table text-center data-table" id="datatable" style="display:table !important;">
                <thead>
                    <tr>
                        <th>Seat No.</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Plan</th>
                        <th>Plan Type</th>
                        <th>Starts On</th>
                        <th>Ends On</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($learners as $learner)
                    @foreach($learner->learnerDetails as $detail)
                   
                    @if($detail->status==0)
                    <tr>
                        <td>{{ $learner->seat_no }}</td>
                        <td><span class="uppercase truncate m-auto text-center d-block">{{ $learner->name }}</span></td>
                        <td> +91-{{ $learner->mobile }}</td>
                        <td> {{ $learner->email }}</td>
                        <td> {{ $detail->plan->name ?? 'N/A' }}</td>
                        <td> {{ $detail->planType->name ?? 'N/A' }}</td>
                        <td> {{ $detail->plan_start_date }}</td>
                        <td> {{ $detail->plan_end_date }}<br>
                            {!! getUserStatusDetails($detail->plan_end_date) !!}
                        </td>
                    </tr>
                    @endif
                  
                    @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>


@endsection