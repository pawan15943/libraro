@extends('layouts.library')
@section('content')
@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];

@endphp

<input id="swap_plan_type_id" type="hidden" name="plan_type_id" value="{{$customer->plan_type_id }}">

<div class="row g-4">
    <div class="col-lg-9 order-2 order-md-1">
        <div class="library-operations mt-4">

            <div class="info__section">
                <h4 class="inner-heading">Learner Info</h4>
                <ul>
                    <li>
                        <span>Learner UID</span>
                        <h4>{{ $customer->learner_no }}</h4>
                    </li>
                    <li>
                        <span>Full Name</span>
                        <h4>{{ $customer->name }}</h4>
                    </li>
                    @if(!in_array('2', toggleHideField()))
                    <li>
                        <span>DOB</span>
                        <h4>{{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d F, Y') : 'DOB Not Available' }}</h4>
                    </li>
                    @endif
                    <li>
                        <span>Mobile</span>
                        <h4>+91-{{ $customer->mobile }}</h4>
                    </li>
                    @if(!in_array('1', toggleHideField()))
                    <li>
                        <span>Email</span>
                        <h4><a href="mailto:{{$customer->email}}" class="text-white"> {!! $customer->email ? $customer->email : 'Email ID Not Available' !!} </a></h4>
                    </li>
                    @endif
                </ul>
            </div>

            <div class="form-input mb-4">
                <h4 class="inner-heading">Swap Seat</h4>
                <div class="tip"><i class="fa-solid fa-gem pe-1"></i> Note : You can swap your seat with any other seat that has the same plan available for booking.</div>
                <form action="{{ route('learners.swap-seat', $customer->id) }}" method="POST" enctype="multipart/form-data" id="swapseat">
                    @csrf
                    @method('PUT')
                    <input id="user_id" type="hidden" name="learner_id" value="{{ $customer->id}}">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <label>Old Seat Number</label>
                            <input class="form-control" value="{{ getSeatDisplayByMainNo($customer->seat_no) ?? 'Gen'}} - {{ $customer->plan_type_name }}" readonly>
                            <input class="form-control" value="{{ $customer->seat_no ?? 'Gen'}} - {{ $customer->plan_type_name }}" type="hidden">
                        </div>
                        <div class="col-lg-6">
                            <label>New Seat Number</label>
                            <select name="seat_id" id="new_seat_id" class="form-control form-select @error('seat_id') is-invalid @enderror">
                                <option>Select Seat</option>
                                <option value="">General</option>
                                @foreach($newAvailableSeats as $key => $value)
                                <option value="{{ $value['main'] }}">{{ $value['display'] }}</option>
                                @endforeach

                            </select>
                            @error('seat_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-12">
                            <input type="hidden" value="{{ $customer->seat_no }}" id="swap_old_value">
                            <label>Current Seat Status</label>
                            <div id="swap_status"></div>
                        </div>

                    </div>
                    <div class="button-list mt-4">
                        <input type="submit" class="btn btn-primary btn-block button w-25" id="swapsubmit" value="Swap Seat">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-3 order-1 order-md-2">
        <div class="seatnumber">
            <img src="{{ asset($customer->image) }}" alt="Seat" class="py-3 {{$class}}" style="width:60px; display:block; margin:0 auto;">
            @if($customer->seat_no)
            <span class="d-block ">Seat No : {{ $customer->seat_no}}</span>
            @else
            <span class="d-block ">General</span>
            @endif
            <div class="seat--plan">{{ $customer->plan_type_name}}</div>
        </div>

    </div>
</div>


<script>
    document.getElementById("swapsubmit").disabled = true;

    document.addEventListener('DOMContentLoaded', function() {
        handleFormChanges('swapseat', "{{ $customer->id }}");
    });
</script>

@endsection