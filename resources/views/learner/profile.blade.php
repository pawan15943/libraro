@extends('layouts.learner')
@section('content')
<div class="row">
    <div class="col-lg-9">
        <div class="actions">

            {{-- ==================== PERSONAL INFO BOX ==================== --}}
            <div class="upper-box">
                <div class="d-flex">
                    <h4 class="mb-3">Learner Info</h4>
                    <a href="javascript:void(0);" class="go-back" onclick="window.history.back();">
                        Go Back <i class="fa-solid fa-backward pl-2"></i>
                    </a>
                </div>

                <div class="row g-4">

                    <div class="col-lg-6">
                        <span>Learner UID</span>
                        <h5>{{ $customer->learner_no ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-6">
                        <span>Full Name</span>
                        <h5>{{ $customer->name ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-6">
                        <span>Date Of Birth</span>
                        <h5>{{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d F, Y') : 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-6">
                        <span>Mobile Number</span>
                        <h5>{{ $customer->mobile ? '+91-'.$customer->mobile : 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-6">
                        <span>Alternate Mobile</span>
                        <h5>{{ $customer->alternate_mobile ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-6">
                        <span>Email Id</span>
                        <h5>{{ $customer->email ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-6">
                        <span>Father Name</span>
                        <h5>{{ $customer->father_name ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-12">
                        <span>Address</span>
                        <h5>{{ $customer->address ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-12">
                        <span>Remark</span>
                        <h5>{{ $customer->remark ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-6">
                        <span>ID Proof</span>
                        <h5>
                            @if($customer->id_proof_name == 1)
                                Aadhar
                            @elseif($customer->id_proof_name == 2)
                                Driving License
                            @elseif($customer->id_proof_name == 3)
                                Other
                            @else
                                Not Updated Yet
                            @endif
                        </h5>
                        @if($customer->id_proof_file)
                            <img src="{{ asset($customer->id_proof_file) }}" width="120" class="mt-2 rounded">
                        @endif
                    </div>

                </div>
            </div>

            {{-- ==================== ACTIVE PLAN INFO ==================== --}}
            <div class="action-box">
                <h4>Active Plan Info</h4>

                <div class="row g-4">
                    <div class="col-lg-4">
                        <span>Plan</span>
                        <h5>{{ $customer->plan_name ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-4">
                        <span>Plan Type</span>
                        <h5>{{ $customer->plan_type_name ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-4">
                        <span>Plan Price</span>
                        <h5>{{ $customer->plan_price_id ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-4">
                        <span>Seat Booked On</span>
                        <h5>{{ $customer->join_date ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-4">
                        <span>Plan Start Date</span>
                        <h5>{{ $customer->plan_start_date ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-4">
                        <span>Plan End Date</span>
                        <h5>{{ $customer->plan_end_date ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-12">
                        <span>Seat Timings</span>
                        <h5>
                            {{ $customer->hours ? $customer->hours.' Hours ('.$customer->start_time.' to '.$customer->end_time.')' : 'Not Updated Yet' }}
                        </h5>
                    </div>

                    <div class="col-lg-6">
                        <span>Plan Expired In</span>
                        <h5>{!! $customer->plan_end_date ? getUserStatusWithSpan($customer->plan_end_date,$customer->id) : 'Not Updated Yet' !!}</h5>
                    </div>

                    <div class="col-lg-6">
                        <span>Current Plan Status</span>
                        <h5>
                            @if($customer->status == 1)
                                <span class="text-success">Active</span>
                            @elseif($customer->plan_end_date)
                                <span class="text-danger">Expired on {{ $customer->plan_end_date }}</span>
                            @else
                                Not Updated Yet
                            @endif
                        </h5>
                    </div>

                    <div class="col-lg-4">
                        <span>Seat Created At</span>
                        <h5>{{ $customer->created_at ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-4">
                        <span>Seat Updated At</span>
                        <h5>{{ $customer->updated_at ?? 'Not Updated Yet' }}</h5>
                    </div>

                    <div class="col-lg-4">
                        <span>Seat Deleted At</span>
                        <h5>{{ $customer->deleted_at ?? 'Not Updated Yet' }}</h5>
                    </div>

                </div>
            </div>

        </div>
    </div>

    {{-- ==================== RIGHT SIDE BOX ==================== --}}
    <div class="col-lg-3 order-1 order-md-2">
        <div class="seat--info">

            <span class="d-block">
                Seat No :
                {{ $customer->seat_no ? getSeatDisplayByMainNo($customer->seat_no) : 'General' }}
            </span>

            <img src="{{ asset($customer->image ?? 'public/img/available.png') }}"
                 alt="Seat" class="seat py-3" style="width:60px;">

            <p>{{ $customer->plan_name ?? '' }}</p>

            <button class="mb-3">
                Booked for <b>{{ $customer->plan_type_name ?? '' }}</b>
            </button>

        </div>
    </div>
</div>


@include('learner.script')
@endsection