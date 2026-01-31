@extends('layouts.library')
@section('content')
@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];

if (Route::currentRouteName() == 'learner.change.plan') {
$displayNone = 'style="display: none;"';

$readonlyStyle = 'pointer-events: none; background-color: #e9ecef;';

} else {
$displayNone = '';

$readonlyStyle = '';
}
@endphp


<div class="row">
    <div class="col-lg-9">
        <div class="library-operations mt-4">

            <div class="info__section">
                <h4 class="inner-heading">Learner Info</h4>
                <ul>
                    <li>
                        <span>Learner UID</span>
                        <h4>{{ $customer->learner_no }}</h4>
                    </li>
                    @if(!in_array('2', toggleHideField()))
                    <li>
                        <span>Full Name</span>
                        <h4>{{ $customer->name }}</h4>
                    </li>
                    @endif
                    <li>
                        <span>DOB</span>
                        <h4>{{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d F, Y') : 'DOB Not Available' }}</h4>
                    </li>
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
                <h4 class="inner-heading">Change Plan</h4>
                <div class="tip"><i class="fa-solid fa-gem pe-1"></i> Note : You can swap your seat with any other seat that has the same plan available for booking.</div>
                <form action="{{ route('learners.update.changePlan', $customer->id) }}" method="POST" enctype="multipart/form-data" id="changePlan">
                    @csrf
                    @method('PUT')

                    <input type="hidden" value="{{$customer->learner_detail_id}}" name="learner_detail_id">


                    {{-- === Inserted Full Structure Below === --}}
                    <div class="row g-4">
                        <input id="edit_seat" type="hidden" name="seat_no" value="{{ old('seat_no', $customer->seat_no) }}">
                        <input type="hidden" name="user_id" value="{{ old('user_id', $customer->id) }}">

                        <div class="col-lg-4">
                            <label for=""> Plan <span>*</span></label>
                            <select id="plan_id" class="form-control @error('plan_id') is-invalid @enderror" name="plan_id" style="{{ $readonlyStyle }}">
                                <option value="">Select Plan</option>
                                @foreach($plans as $key => $value)
                                <option value="{{ $value->id }}" {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
                                @endforeach
                            </select>
                            @error('plan_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label for="">Plan Type <span>*</span></label>
                            <select id="plan_type_id2" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id">
                                @foreach($filteredPlanTypes as $planType)
                                <option value="{{ $planType['id'] }}"
                                    {{ ($customer->plan_type_id == $planType['id']) ? 'selected' : (old('plan_type_id') == $planType['id'] ? 'selected' : '') }}>
                                    {{ $planType['name'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('plan_type_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label for="">Plan Price <span>*</span></label>
                            <input id="plan_price" class="form-control @error('plan_price_id') is-invalid @enderror"
                                value="{{ old('plan_price_id', $customer->plan_price_id) }}" readonly name="plan_price_id">
                            @error('plan_price_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        @php
                        $hasLocker = currentTransaction($customer->learner_detail_id)->locker_amount > 0 ? 'yes' : 'no';
                         $discountAmount = currentTransaction($customer->learner_detail_id)->discount_amount ?? null;
                        $selectedDiscountType = $discountAmount ? 'amount' : '';
                        @endphp
                         @if(!in_array('3', toggleHideField()) || (in_array('3', toggleHideField()) && ($hasLocker === 'yes')))
                        <div class="col-lg-4">
                            <label for="locker">Locker?</label>
                            <select name="locker" id="toggleFieldCheckbox" class="form-select">
                                <option value="no" {{ $hasLocker === 'no' ? 'selected' : '' }}>No</option>
                                <option value="yes" {{ $hasLocker === 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>
                            </select>
                        </div>

                        <div class="col-lg-4">
                            <label for="">Locker Amount <span>*</span></label>
                            <input type="text" class="form-control @error('locker_amount') is-invalid @enderror"
                                name="locker_amount" id="locker_amount"
                                value="{{ currentTransaction($customer->learner_detail_id)->locker_amount }}" readonly>
                            @error('locker_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                         <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2" >
                            <label for="locker_no">Locker No.</label>
                            <input type="text" class="form-control digit-only" name="locker_no" id="locker_no2" placeholder="Enter Locker No." readonly>
                        </div>
                        @endif

                        @if(!in_array('6', toggleHideField()) || (in_array('6', toggleHideField()) && $discountAmount) )
                          <div class="col-lg-4">
                            <label>Discount Type</label>
                            <select id="discountType2" class="form-control form-select" name="discountType">
                                <option value="">Select Discount Type</option>
                                <option value="amount" {{ $selectedDiscountType == 'amount' ? 'selected' : '' }}>Amount</option>
                                <option value="percentage" {{ $selectedDiscountType == 'percentage' ? 'selected' : '' }}>Percentage</option>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label for="">Discount Amount <span>*</span></label>
                            <input type="text" class="form-control @error('discount_amount') is-invalid @enderror"
                                name="discount_amount" id="discount_amount2"
                                value="{{ currentTransaction($customer->learner_detail_id)->discount_amount ?? 0 }}" readonly>
                            @error('discount_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        @endif

                        <div class="col-lg-4">
                            <label for="">Last paid Amount <span>*</span></label>
                            <input type="text" class="form-control @error('total_amount') is-invalid @enderror"
                                name="total_amount" id="total_amount2"
                                value="{{ currentTransaction($customer->learner_detail_id)->total_amount }}" readonly>
                            @error('total_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label for="">Diffrence Amount <span>*</span></label>
                            <input type="text" class="form-control @error('diffrence_amount') is-invalid @enderror"
                                name="diffrence_amount" id="diffrence_amount" value="" readonly placeholder="0.00">
                            @error('diffrence_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                             <span id="pending_amt4" class="text-danger"></span>
                        </div>
                        <div class="col-lg-4">
                            <label for="">Choose Due Date<span>*</span></label>
                            <input type="date" class="form-control duedate" placeholder="Enter Due Date" name="due_date" id="due_date3" readonly>
                        </div>

                        <div class="col-lg-4 col-6">
                            <label for="">Payment Mode</label>
                            <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="1">Online</option>
                                <option value="2">Offline</option>
                                <option value="3">Pay Later</option>
                            </select>
                            @error('payment_mode')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <input type="hidden" id="new_plan_price" value="" name="new_plan_price" class="form-control @error('new_plan_price') is-invalid @enderror">
                        @error('new_plan_price')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror

                    </div>

                    @php
                    $oneWeekLater = \Carbon\Carbon::parse($customer->plan_start_date)->addWeek();
                    $today = \Carbon\Carbon::now();
                    @endphp
                    <div class="row mt-4">
                        @if(!$today->greaterThanOrEqualTo($oneWeekLater))
                        <div class="col-lg-3">
                            <input type="submit" class="btn btn-primary btn-block button" value="Update Seat Info">
                        </div>
                        @else
                        <p class="text-danger"><b>*</b>Button is available Within 7 Days of Seat Booking</p>
                        @endif
                    </div>
                    {{-- === End Inserted Structure === --}}


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

    </form>
</div>

<script>
    // Call the handleFormChanges function for the specific form when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
         handleFormChanges('changePlan', {{ $customer->id }});
       
    });
</script>


@endsection