@extends('layouts.library')
@section('content')

@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];

if (Route::currentRouteName() == 'learner.renew.plan') {
$displayNone = 'style="display: none;"';

$readonlyStyle = 'pointer-events: none; background-color: #e9ecef;';


} else {
$displayNone = '';

$readonlyStyle = '';

}
if($customer->locker_no){
$locker_read='';
}else{
$locker_read='readonly';
}


if(Route::currentRouteName() == 'booking.details'){
$paymentType='Request Approve';
$route=route('learner.upgrade.renew.store');
$ids='approvwRequest';
}

@endphp


<div class="row g-4">
    <div class="col-lg-9 order-2 order-md-1">

        <div class="library-operations mt-4">

            <div class="info__section">
                <h4 class="inner-heading">Learner Info</h4>
                <ul>

                    <li>
                        <span>Full Name</span>
                        <h4>{{ $customer->name }}</h4>
                    </li>
                    <li>
                        <span>DOB</span>
                        <h4>{{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d F, Y') : 'DOB Not Available' }}</h4>
                    </li>
                    <li>
                        <span>Mobile</span>
                        <h4>+91-{{ $customer->mobile }}</h4>
                    </li>
                    <li>
                        <span>Email</span>
                        <h4><a href="mailto:{{$customer->email}}" class="text-white"> {!! $customer->email ? $customer->email : 'Email ID Not Available' !!} </a></h4>
                    </li>
                </ul>
            </div>

            <div class="form-input mb-4">
                <h4 class="inner-heading"> QR / Online Bookings</h4>
                <div class="tip text-danger">
                    <b>Note:</b> Seats booked through QR code or online mode are not activated immediately. They require verification and approval from the library owner before becoming active.
                </div>

                <form action="{{route('booking.details.approve')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('POST')

                    <input type="hidden" name="booking_id" value="{{ $customer->id ?? '' }}" id="user_id">
                    <input type="hidden" name="learner_id" value="{{ $learner->id ?? '' }}">
                    <input type="hidden" name="branch_id" value="{{ $customer->branch_id ?? '' }}">
                    <input type="hidden" name="learner_detail_id" value="{{ $transaction->learner_detail_id ?? '' }}" id="learner_detail_id">

                    <h4 class="mt-4 mb-3">Current Plan Info</h4>
                    <div class="row g-4">
                        @php
                        $availableSeatsArray = $availableseats->toArray();
                        @endphp

                        <div class="col-lg-6">
                            <label for="seat_id">Choose Seat No. <span>*</span></label>
                            <select name="seat_no" class="form-select" id="seat_id11">
                                <option value="gen"
                                    {{ ($customer->seat_no ?? 'gen') == 'gen' || !in_array($customer->seat_no, $availableSeatsArray) ? 'selected' : '' }}>
                                    GEN
                                </option>

                                @foreach($availableseats as $value)
                                <option value="{{ $value }}"
                                    {{ ($customer->seat_no ?? '') == $value && in_array($customer->seat_no, $availableSeatsArray) ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-4 mt-0">
                        <div class="col-lg-6">
                            <label for="plan_id11">Plan <span>*</span></label>
                            <select id="plan_id11" class="form-control form-select @error('plan_id') is-invalid @enderror"
                                name="plan_id">
                                <option value="">Select Plan</option>
                                @foreach($plans as $key => $value)
                                <option value="{{ $value->id }}" {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>
                                    {{ $value->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('plan_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-lg-6">
                            <label for="plan_type_id11">Plan Type <span>*</span></label>
                            <select id="plan_type_id11"
                                class="form-control form-select @error('plan_type_id') is-invalid @enderror"
                                name="plan_type_id">

                                {{-- Default placeholder --}}
                                <option value="">Choose Shift</option>

                                {{-- Pre-fill selected plan type only (will be replaced/updated by AJAX) --}}
                                @if(!empty($customer->plan_type_id))
                                <option value="{{ $customer->plan_type_id }}" selected>
                                    {{ $customer->planType->name ?? 'Selected Plan' }}
                                </option>
                                @endif
                            </select>

                            @error('plan_type_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>


                        <div class="col-lg-6">
                            <label for="plan_price11">Plan Price (₹)<span>*</span></label>
                            <input type="text" id="plan_price11" name="plan_price_id"
                                class="form-control @error('plan_price_id') is-invalid @enderror"
                                value="{{ old('plan_price_id', $customer->plan_price_id) }}" readonly>

                            @error('plan_price_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-lg-6">
                            <label for="">Plan Start Date <span>*</span></label>
                            <input type="text" name="plan_start_date"
                                class="form-control @error('plan_start_date') is-invalid @enderror"
                                value="{{ old('plan_start_date', $customer->plan_start_date) }}" readonly>
                        </div>
                    </div>

                    <h4 class="mt-4 mb-3">Your plan Addon's
                        <i class="fa fa-plus toggleIcon1" style="cursor: pointer;"></i>
                    </h4>

                    <div style="display: none;" class="mb-3 idProofFields1">
                        <div class="row g-4">
                            @if(!in_array('3', toggleHideField()) || (in_array('3', toggleHideField())))
                            <div class="col-lg-4 {{ !is_locker() ? 'd-none' : '' }}">
                                <label for="toggleFieldCheckbox11">Locker?</label>
                                <select name="locker" id="toggleFieldCheckbox11" class="form-control @error('locker') is-invalid @enderror">
                                    <option value="no" {{ old('locker', (($transaction?->locker_amount ?? 0) > 0 ? 'yes' : 'no')) == 'no' ? 'selected' : '' }}>No</option>
                                    <option value="yes" {{ old('locker', (($transaction?->locker_amount ?? 0) > 0 ? 'yes' : 'no')) == 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>

                                </select>
                                @error('locker')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror

                            </div>

                            <div class="col-lg-4">
                                <label for="locker_amount11">Locker Amount</label>
                                <input type="text" id="locker_amount11" name="locker_amount"
                                    class="form-control @error('locker_amount') is-invalid @enderror"
                                    value="{{ old('locker_amount', $transaction?->locker_amount ?? 0) }}"
                                    readonly>
                                @error('locker_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                <label for="locker_no11">Locker No.</label>
                                <input type="text"
                                    class="form-control digit-only @error('locker_no') is-invalid @enderror"
                                    name="locker_no" id="locker_no11" placeholder="Enter Locker No."
                                    value="{{ old('locker_no', ((optional($transaction)->locker_amount > 0) && !empty(optional($learner)->locker_no)) ? $learner->locker_no : '') }}">


                                @error('locker_no')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif

                            @if(!in_array('6', toggleHideField()) || (in_array('6', toggleHideField()) && $transaction?->discount_amount))
                            <div class="col-lg-4">
                                <label for="discountType11">Discount Type</label>
                                <select id="discountType11" name="discount_type" class="form-control @error('discount_type') is-invalid @enderror">
                                    <option value="">Select Discount Type</option>
                                    <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                    <option value="amount" {{ !empty($transaction?->discount_amount) ? 'selected' : '' }}>Amount</option>

                                </select>
                                @error('discount_type')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-lg-4">
                                <label>Discount</label>
                                <input type="text" class="form-control  @error('discount_amount') is-invalid @enderror" name="discount_amount" id="discount_amount11" value="{{ $transaction->discount_amount ?? 0 }}" readonly>
                                @error('discount_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            @endif
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <label for="previous_amount11">Previously Paid Amount (₹)</label>
                            <input type="text" id="previous_amount11" name="previous_amount"
                                class="form-control"
                                value="{{ old('previous_amount', $customer->total_amount ?? 0) }}"
                                readonly>
                            <small><a href="" class="text-danger">Payment Proof / Screenshot</a></small>

                        </div>
                        <div class="col-lg-4">
                            <label for="total_amount11">New Plan Total (₹)</label>
                            <input type="text" id="total_amount11" name="total_amount"
                                class="form-control"
                                value="{{ old('total_amount', $customer->total_amount ?? 0) }}"
                                readonly>
                            {{-- <img src="{{asset($customer->payment_screenshot)}}" alt=""> --}}
                        </div>


                        <div class="col-lg-4">
                            <label for="diffrence_amount11">Amount Difference (₹)</label>
                            <input type="text" id="diffrence_amount11" name="diffrence_amount"
                                class="form-control"
                                value="{{ old('diffrence_amount', $customer->diffrence_amount ?? 0) }}" readonly>
                        </div>
                        <div class="col-lg-4">
                            <label for="paid_amount11">Pay Refundable / Pending Amount (₹)</label>
                            <input type="text" id="paid_amount11" name="paid_amount"
                                class="form-control"
                                value="{{ old('paid_amount') }}" placeholder="0">
                        </div>

                        <div class="col-lg-4">
                            <label for="pending_amt11">Pending Payment (₹)</label>
                            <input type="text" id="pending_amt11" name="pending_amount"
                                class="form-control"
                                value="{{ old('pending_amount', $customer->pending_amount ?? 0) }}"
                                readonly>
                            <span id="pending_amt_error" class="text-danger"></span>
                        </div>

                        <div class="col-lg-4">
                            <label for="due_date11">Payment Due Date<span>*</span></label>
                            <input type="date" id="due_date11" name="due_date"
                                class="form-control"
                                value="{{ old('due_date', $customer->due_date ?? '') }}"
                                readonly>
                            @error('due_date')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-lg-4 mt-4">
                            <input type="submit" class="btn btn-primary btn-block button" value="Verify Seat Info and Activate Seat">
                        </div>
                    </div>


                </form>

            </div>
        </div>

    </div>
    <div class="col-lg-3 order-1 order-md-2">
        <div class="seatnumber">
            <img src="{{asset('public/img/booked.png')}}" alt="Seat" class="py-3 " style="width:60px; display:block; margin:0 auto;">
            <span class="d-block ">General</span>
            <div class="seat--plan">{{ $customer->planType->name}}</div>
        </div>

    </div>
</div>

<script>
    // start new according
    // on plan change-total change,price change, locker amount change
    // on plan type change-total change,price change
    // on locker yes -total change, locker amount ,locker no
    // on discount type change -total change, red text change
    // on total input change-pending get, due date on, 

    // diffrence amount - hidden , change plan show
    // diffrence amount on change-change plan

    $(document).ready(function() {

        const plan_id11 = $('#plan_id11').val();
        const selectedPlanType = $('#plan_type_id11').val();
        const learner_detail_id = $('#learner_detail_id').val();

        const seat = $('#seat_id11').val();

        if (learner_detail_id) {

            // if (!seat || seat === 'gen') {
            //     fetchPlanTypesRenewSeat('',learner_detail_id)

            // } else {
            //     fetchPlanTypesRenewSeat(seat, learner_detail_id); 
            // }

        } else {


            if (!seat || seat === 'gen') {
                getTypeSeatwise('', selectedPlanType); // load all plan types
            } else {
                getTypeSeatwise(seat, selectedPlanType); // load plan types seatwise
            }
        }
        getPlanPrice(selectedPlanType, plan_id11);
        calculatePaidTotalAmount();

        var lockerCheck = $('#toggleFieldCheckbox11').val();


        if (lockerCheck == 'yes') {
            $('#locker_no11').attr('readonly', false);

        }

        if ($('#discountType11').val() == 'percentage' || $('#discountType11').val() == 'amount') {
            $('#discount_amount11').attr('readonly', false);
        } else {
            $('#discount_amount11').attr('readonly', true);

        }




    });
    $('#seat_id11').on('change', function() {
        const newSeat = $(this).val();
        getTypeSeatwise(newSeat, null); // reset plan type when seat changes
    });

    $('#plan_id11').on('change', function(event) {
        event.preventDefault();
        const plan_id11 = $(this).val();
        const plan_type_id11 = $('#plan_type_id11').val();
        var lockerCheck = $('#toggleFieldCheckbox11').val();
        if (plan_type_id11 && plan_id11) {
            getPlanPrice(plan_type_id11, plan_id11);
            calculatePaidTotalAmount();
            if (lockerCheck == 'yes') {
                lockerAmtGet(plan_id11);
            }

        } else {
            $("#plan_price11").val('');
        }
    });
    $('#plan_type_id11').on('change', function(event) {
        event.preventDefault();
        const plan_type_id11 = $(this).val();
        const plan_id11 = $('#plan_id11').val();
        var lockerCheck = $('#toggleFieldCheckbox11').val();
        console.log("Values =>", {
            plan_type_id11,
            plan_id11,
            lockerCheck
        });
        if (plan_type_id11 && plan_id11) {
            console.log("Calling getPlanPriceAmount...");
            getPlanPrice(plan_type_id11, plan_id11);

            if (lockerCheck == 'yes') {
                lockerAmtGet(plan_id11);
            }
        } else {
            $("#plan_price11").val('');
        }
    });

    $('#toggleFieldCheckbox11').on('change', function() {

        var needLocker = $(this).val();
        const plan_id11 = $('#plan_id11').val();

        if (needLocker === 'yes') {
            $('#locker_no11').removeAttr('readonly');
            lockerAmtGet(plan_id11)

        } else {
            $('#locker_amount11').attr('readonly', true);
            $('#locker_no11').attr('readonly', true);
            $('#locker_amount11').val(0);


        }
        calculatePaidTotalAmount();
        $('#pending_amt11').val("");
    });
    $('#discountType11').on('change', function() {
        const type = $(this).val();
        if (type === 'percentage') {
            $('#typeVal11').text('%');
            $('#discount_amount11').attr('readonly', false);
        } else if (type === 'amount') {
            $('#typeVal11').text('INR');
            $('#discount_amount11').attr('readonly', false);
        } else {
            $('#typeVal11').text('INR / %');
            $('#discount_amount11').attr('readonly', true);
        }
        calculatePaidTotalAmount();
        $('#pending_amt11').val("");

    });
    $('#discount_amount11').on('input', function() {
        calculatePaidTotalAmount();
    });
    $('#total_amount11').on('input', function() {
        calculatePendingAmt($(this).val());
    });

    $('#paid_amount11').on('input', function() {
        calculatePendingAmt($(this).val());
    });

    function getPlanPrice(plan_type_id11, plan_id11) {

        if (plan_type_id11 && plan_id11) {
            $.ajax({
                url: "{{ route('getPricePlanwise') }}",
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "plan_type_id": plan_type_id11,
                    "plan_id": plan_id11,
                },
                dataType: 'json',
                success: function(html) {
                    console.log('htmoll', html);

                    if (html && html !== undefined) {

                        $('#pending_amt11').html('');
                        $("#plan_price11").prop("value", html);


                        console.log("Value now in input:", $("#plan_price11").val());
                        calculatePaidTotalAmount();
                        $("#error-message").hide();
                    } else {
                        $("#plan_price11").val("");

                        $("#pending_amt11").html("No Plan Price Added Yet.");
                        $("#total_amount11").val("");
                    }
                }

            });
        } else {
            $("#plan_price11").empty();

            $("#total_amount11").empty();

        }
    }

    function getTypeSeatwise(seatId, selectedPlanType = null) {
        $('#plan_type_id11').empty().append('<option value="">Choose Shift</option>');

        $.ajax({
            url: "{{ route('gettypeSeatwise') }}",
            type: 'GET',
            data: {
                "_token": "{{ csrf_token() }}",
                "seatNo": seatId,
            },
            dataType: 'json',
            success: function(html) {
                if (html && html.length > 0) {
                    $("#plan_type_id11").empty().append('<option value="">Choose Shift</option>');

                    $.each(html, function(index, planType) {
                        let isSelected = (selectedPlanType && selectedPlanType == planType.id) ? 'selected' : '';
                        $("#plan_type_id11").append('<option value="' + planType.id + '" ' + isSelected + '>' + planType.name + '</option>');
                    });
                } else {
                    $("#plan_type_id11").empty().append('<option value="">No Plan Types Available</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
            }
        });
    }


    function lockerAmtGet(plan_id11) {
        $.get("{{ route('locker.price') }}", {
                plan_id: plan_id11
            })
            .done(function(json) {
                $('#locker_amount11').val(json.price);
                calculatePaidTotalAmount();
            })
            .fail(function() {
                $('#locker_amount11').val('').prop('readonly', true);
                calculatePaidTotalAmount();
            });
    }

    function calculatePaidTotalAmount() {
        const planPrice = parseFloat($('#plan_price11').val()) || 0;

        const lockerAmount = parseFloat($('#locker_amount11').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount11').val()) || 0;
        const discountType = $('#discountType11').val();



        var discountAmount = 0;

        if (discountType === 'percentage') {
            console.log('heena_prencetenge');
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            console.log('heena_amontcheck');
            discountAmount = discountRaw;
        }

        if (discountType !== 'percentage' && discountType !== 'amount') {
            $('#discount_amount11').val("");
        }

        const autoPaid = planPrice + lockerAmount - discountAmount;
        console.log('heena_planPrice', planPrice);
        console.log('heena_lockerAmount', lockerAmount);
        console.log('heena_discountAmount', discountAmount);
        console.log('heena_autoPaid', autoPaid);
        $('#total_amount11').val(autoPaid);

        const previous_amount = $('#previous_amount11').val();

        const diffrence = autoPaid - previous_amount;
        $('#diffrence_amount11').val(diffrence);
        if (diffrence < 0) {
            $('label[for="paid_amount11"]').text("Refund Amount *");


        } else {
            $('label[for="paid_amount11"]').text("Paid Amount *");

        }
    }

    function calculatePendingAmt(paid_val) {
        const planPrice = parseFloat($('#plan_price11').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount11').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount11').val()) || 0;
        const discountType = $('#discountType11').val();
        const previous_amount11 = parseFloat($('#previous_amount11').val()) || 0;


        discountAmount = 0;
        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmount = discountRaw;
        }
        const paidAmount = parseFloat(paid_val) || 0;

        const effectivePaid = planPrice + lockerAmount - discountAmount;
        let pendingAmount;

        if (effectivePaid - previous_amount11 < 0) {
            pendingAmount = effectivePaid - previous_amount11 + paidAmount;
        } else {
            pendingAmount = effectivePaid - previous_amount11 - paidAmount;
        }

        $('#pending_amt11').val(pendingAmount);


        if ((paid_val > effectivePaid)) {
            $('#pending_amt_error').html('High price not allowed.' + pendingAmount);
            $('#due_date11').attr('readonly', true);
        } else {
            $('#pending_amt_error').html('');
        }
        if (pendingAmount != 0) {
            $('#due_date11').attr('readonly', false);
        } else {

            $('#due_date11').attr('readonly', true);
        }
        if (pendingAmount < 0) {
            $('#pending_amt11').prev('label').text("Pending Refund Amount *");

        } else {
            $('#pending_amt11').prev('label').text("Pending Amount *");

        }


    }
    //  function fetchPlanTypesRenewSeat(seat_no,learner_detail_id) {

    //             if (seat_no  && learner_detail_id) {
    //                 $.ajax({
    //                     url: '{{ route('gettypePlanwise') }}',
    //                     headers: {
    //                         'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    //                     },
    //                     type: 'GET',
    //                     data: {
    //                         "_token": "{{ csrf_token() }}",
    //                         "seat_no": seat_no,
    //                         "learner_detail_id": learner_detail_id,
    //                     },
    //                     dataType: 'json',
    //                     success: function (html) {
    //                         console.log("renew",html);
    //                         $("#plan_type_id_renew").empty(); 
    //                         $("#plan_id2").empty(); 

    //                         if (html[0]) {
    //                             $.each(html[0], function (key, value) {
    //                                 $("#plan_type_id_renew").append('<option value="' + key + '">' + value + '</option>');
    //                             });
    //                         } else {
    //                             $("#plan_type_id_renew").append('<option value="">Choose</option>');
    //                         }


    //                         if (html[1]) {
    //                              $.each(html[1], function (key, value) {
    //                                 $("#plan_id2").append('<option value="' + key + '">' + value + '</option>');
    //                             });
    //                         }

    //                         if (html[2]){
    //                            $("#plan_price_id2").val(html[2].plan_price_id);      
    //                         }

    //                         if(html[3]){
    //                             $("#locker_amount2").val(html[3].locker_amount);  
    //                             $("#discount_amount3").val(html[3].discount_amount);  
    //                             $("#new_plan_price").val(html[3].discount_amount);  

    //                             if (html[3].locker_amount && parseFloat(html[3].locker_amount) > 0) {
    //                                 $("#locker").val('yes');
    //                                 $("#locker_amount2").val(html[3].locker_amount);

    //                             } else {
    //                                 $("#locker").val('no');
    //                                 $("#locker_amount2").val('');

    //                             }

    //                             if (html[3].discount_amount && parseFloat(html[3].discount_amount) > 0) {
    //                                 $("#discount_type").val('amount');
    //                                 $("#discount_amount3").val(html[3].discount_amount);
    //                             } else {
    //                                 $("#discount_type").val('');
    //                                 $("#discount_amount3").val('');
    //                             }
    //                         }
    //                          if (html[4]){
    //                            $("#locker_no2").val(html[4].locker_no);
    //                            if(html[4].locker_no){
    //                             $("#locker_no2").removeAttr('readonly');
    //                            }      
    //                         }

    //                         popupautoCalculatePaidAmount(); 
    //                     },
    //                     error: function (xhr, status, error) {
    //                         console.error("AJAX error:", status, error); // Log any errors
    //                     }
    //                 });
    //             } else {
    //                 $("#plan_type_id_renew").empty();
    //                 $("#plan_type_id_renew").append('<option value="">Choose Shift</option>');
    //             }
    //         }

    //  end 
</script>
@endsection