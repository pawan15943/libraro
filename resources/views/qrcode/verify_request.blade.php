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
@if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

<style>
    /* Final image preview */
.profile-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ddd;
    display: none;
    margin-top: 10px;
}

    .preview-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 100px;
        border: 1px solid #ddd;
    }

    .preview-img.one {
        width: 200px;
        height: 150px;
        border-radius: 1rem;
    }
    .image-modal{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.7);
    z-index:9999;
    display:flex;
    align-items:center;
    justify-content:center;
}

.image-modal-content{
    position:relative;
    background:#fff;
    padding:10px;
    border-radius:8px;
    max-width:90%;
    max-height:90%;
}

.image-modal-content img{
    max-width:100%;
    max-height:80vh;
    display:block;
}

.close-modal{
    position:absolute;
    top:5px;
    right:10px;
    font-size:24px;
    cursor:pointer;
}
span.close-modal {
    background: #fff;
    width: 25px;
    height: 25px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 25px;
    margin-top: 10px;
    margin-right: 4px ! IMPORTANT;
}

</style>
 <div id="imageViewModal" class="image-modal" style="display:none;">
    <div class="image-modal-content">
        <span class="close-modal">&times;</span>
        <img src="" id="modalImage">
    </div>
</div>
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="row g-4">
                <form action="{{route('booking.details.approve')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="detailes">
                        {{-- @php
                            // Normalize available seats
                            $availableSeatsArray = collect($availableseats)->filter()->values()->toArray();
                                // Default (BOOK case)
                            $seatList = $availableSeatsArray;
                            // Renew case → ensure current seat is present
                            if (isset($customer) && ($customer->type ?? null) === 'qr_renew' && !empty($customer->seat_no) && !in_array($customer->seat_no, $availableSeatsArray)) {
                                $seatList = array_merge([$customer->seat_no], $seatList);
                            }

                            // Always keep order clean
                            sort($seatList);
                       
                        @endphp --}}

                        @php
                            /*
                            |--------------------------------------------------------------------------
                            | 1. Normalize available seat numbers (plain main seat numbers)
                            |--------------------------------------------------------------------------
                            */
                            $availableSeatNumbers = collect($availableseats)
                                ->filter()
                                ->values()
                                ->toArray();

                            /*
                            |--------------------------------------------------------------------------
                            | 2. Get ALL seats with full structure
                            |    [
                            |      main, floor, floor_name, floor_no, display
                            |    ]
                            |--------------------------------------------------------------------------
                            */
                            $allSeats = collect(generateSeatNumbers());

                            /*
                            |--------------------------------------------------------------------------
                            | 3. Filter only AVAILABLE seats (keep structure)
                            |--------------------------------------------------------------------------
                            */
                            $seatList = $allSeats->filter(function ($seat) use ($availableSeatNumbers) {
                                return in_array($seat['main'], $availableSeatNumbers);
                            })->values();

                            /*
                            |--------------------------------------------------------------------------
                            | 4. Renew case → ensure current seat is present
                            |--------------------------------------------------------------------------
                            */
                            if (
                                isset($customer) &&
                                ($customer->type ?? null) === 'qr_renew' &&
                                !empty($customer->seat_no) &&
                                !$seatList->contains('main', $customer->seat_no)
                            ) {
                                $currentSeat = $allSeats->firstWhere('main', $customer->seat_no);

                                if ($currentSeat) {
                                    $seatList->prepend($currentSeat);
                                }
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | 5. Always keep order clean (by main seat number)
                            |--------------------------------------------------------------------------
                            */
                            $seatList = $seatList->sortBy('main')->values();
                        @endphp

                       

                        <div class="row g-3">
                            <input type="hidden" name="booking_id" value="{{ $customer->id ?? '' }}" >
                            <input type="hidden" name="learner_id" value="{{ $learner->id ?? '' }}" id="renew_learner_id">
                          
                            <input type="hidden" name="branch_id" value="{{ $customer->branch_id ?? '' }}">
                      
                            {{--Seat Concept======================================================================  --}}
                            <div class="col-lg-6">
                                <label for="qr_general_seat">Assign Seat No ?</label>
                                <select name="general_seat" id="qr_general_seat" class="form-select">

                                    <option value="yes">No</option>

                                    <option value="no">Yes, Allot a Seat No.</option>
                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label for="seat_id11">Choose Seat No. <span>*</span></label>

                                <select name="seat_no" class="form-select  @error('seat_no') is-invalid @enderror" id="seat_id11">
                                        <option value="">GEN</option>
                                      @foreach ($seatList as $seat)
                                            <option value="{{ $seat['main'] }}"
                                                {{ ($customer->seat_no ?? '') == $seat['main'] ? 'selected' : '' }}>
                                                {{ $seat['display'] }}
                                            </option>
                                        @endforeach
                                </select>
                                 @error('seat_no')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- ================================================================== --}}
                            <div class="col-lg-6">
                                <label for="">Full Name <span>*</span></label>
                                <input type="text" class="form-control char-only @error('name') is-invalid @enderror" name="name" value="{{ old('name') ?? $customer->name ?? '' }}">
                                @error('name')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <label for="">Mobile Number <span>*</span></label>
                                <input type="text" class="form-control digit-only @error('mobile') is-invalid @enderror" maxlength="10" minlength="10" name="mobile"  value="{{ old('mobile') ?? decryptData($customer->mobile) ?? '' }}">
                                 @error('mobile')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            @if(!in_array('2', toggleHideField()))
                            <div class="col-lg-6">
                                <label for="">DOB (Optional)</label>
                                <input type="date"
                                class="form-control dob @error('dob') is-invalid @enderror"
                                name="dob"
                                value="{{ old('dob') ?? (optional($customer)->dob ? \Carbon\Carbon::parse($customer->dob)->format('Y-m-d') : '') }}"
                                max="{{ date('Y-m-d', strtotime('-5 years')) }}">

                                 @error('dob')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif

                            @if(!in_array('1', toggleHideField()))
                            <div class="col-lg-6">
                                <label for="">Email Id (Optional)</label>
                                <input type="text" class="form-control  @error('email') is-invalid @enderror" name="email" value="{{ old('email') ?? decryptData($customer->email) ?? '' }}" >
                                 @error('email')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            @endif

                            <div class="col-lg-4">
                                <label for="plan_id11">Plan <span>*</span></label>
                                <select id="plan_id11" class="form-control form-select @error('plan_id') is-invalid @enderror" name="plan_id">
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

                            <div class="col-lg-4">
                                <label for="plan_type_id11">Plan Type <span>*</span></label>
                                <select id="plan_type_id11" class="form-control form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id">

                                    <option value="">Choose Shift</option>
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

                            <div class="col-lg-4">
                                <label for="">Plan Starts On <span>*</span></label>
                                <input type="date" name="plan_start_date" class="form-control @error('plan_start_date') is-invalid @enderror" value="{{ old('plan_start_date', $customer->plan_start_date) }}">
                                 @error('plan_start_date')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <input type="hidden" id="plan_price11" class="form-control" name="plan_price_id" placeholder="Example : 00 Rs" value="{{ old('plan_price_id', $customer->plan_price_id) }}" readonly>
                        </div>
                        @if(!in_array('3', toggleHideField()) || !in_array('6', toggleHideField()))


                        <h4 class="my-3">Your Plan Addon's 
                            <i class="fa fa-plus qr_addonToggleIcon" style="cursor: pointer;"></i>
                 
                        </h4>
                        <div class="qr_lockerFields idProofFields1" style="display: none;">
                            <div class="row g-3">
                                @if(!in_array('3', toggleHideField()))
                                <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}">
                                    <label for="toggleFieldCheckbox11">Need a Locker ?</label>
                                    <select name="locker" id="toggleFieldCheckbox11" class="form-control form-select @error('locker') is-invalid @enderror">
                                        <option value="no" {{ old('locker', (($transaction?->locker_amount ?? 0) > 0 ? 'yes' : 'no')) == 'no' ? 'selected' : '' }}>No</option>
                                        <option value="yes" {{ old('locker', (($transaction?->locker_amount ?? 0) > 0 ? 'yes' : 'no')) == 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>

                                    </select>
                                    @error('locker')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer" readonly>
                                    <label for="locker_amount11">Locker Amount</label>
                                    <input type="text" id="locker_amount11" name="locker_amount" class="form-control @error('locker_amount') is-invalid @enderror" value="{{ old('locker_amount', $transaction?->locker_amount ?? 0) }}" readonly>
                                    @error('locker_amount')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror </div>
                                <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                    <label for="locker_no11">Locker No.</label>
                                    <input type="text" class="form-control digit-only @error('locker_no') is-invalid @enderror" name="locker_no" id="locker_no11" placeholder="Enter Locker No." value="{{ old('locker_no', ((optional($transaction)->locker_amount > 0) && !empty(optional($learner)->locker_no)) ? $learner->locker_no : '') }}">

                                    @error('locker_no')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                @endif
                                @if(!in_array('6', toggleHideField()))
                                <div class="col-lg-6">
                                    <label for="discountType11">Discount Type</label>
                                    <select id="discountType11" name="discount_type" class="form-control form-select @error('discount_type') is-invalid @enderror">
                                        <option value="">Select Discount Type</option>
                                        <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                        <option value="amount" {{ !empty($transaction?->discount_amount) ? 'selected' : '' }}>Amount</option>

                                    </select>
                                    @error('discount_type')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label for="discount_amount">Discount Amount ( <span id="typeVal">INR / %</span> )</label>
                                    <input type="text" class="form-control  @error('discount_amount') is-invalid @enderror" name="discount_amount" id="discount_amount11" value="{{ $transaction->discount_amount ?? 0 }}" readonly>
                                    @error('discount_amount')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                        <div class="row g-3 mt-0">
                            <div class="col-lg-4">
                                <label for="">Final Payble Amount (INR)<span>*</span></label>
                                <input id="paid_amount11" class="form-control digit-only @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount') }}" name="paid_amount" placeholder="Example : 00 Rs">
                                <span id="pending_amt11" class="text-danger"></span>
                                @error('paid_amount')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-lg-4">
                                <label for="">Choose Due Date<span>*</span></label>
                                <input type="date" class="form-control duedate" placeholder="Enter Due Date" name="due_date" id="due_date11" value="{{ old('due_date', $customer->due_date ?? '') }}" readonly>
                            </div>

                            <div class="col-lg-4">
                                <label for="">Payment Mode <span>*</span></label>
                                <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                    <option value="">Select Payment Mode</option>
                                    <option value="online" {{ old('payment_mode', $customer->payment_mode ?? '') == 'online' ? 'selected' : '' }}>Online</option>
                                    <option value="offline" {{ old('payment_mode', $customer->payment_mode ?? '') == 'offline' ? 'selected' : '' }}>Offline</option>
                                </select>

                                @error('payment_mode')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            @if($customer->type=='qr_seat_book')
                            @if(notificationActive())
                            <div class="col-lg-12">
                                <label for="">Send Reminders Via (Optional)</label>
                                <select class="form-select" name="sended_message_type">
                                    <option value="">Select Type</option>
                                    <option value="whatsapp">WhatsApp Message Only</option>
                                    <option value="text">Text Message Only</option>
                                    <option value="both">Both (WhatsApp & Text Message)</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            @endif
                            @endif
                        </div>
                        @if($customer->type=='qr_seat_book')
                        @if(!in_array('7', toggleHideField()))
                        <h4 class="py-4 m-0">Other Optional Fields <i class="fa fa-plus qr_toggleIcon" style="cursor: pointer;"></i></h4>

                       <div class="qr_idProofFields" style="display: none;">
                        <div class="row g-3">

                            {{-- ================= ID PROOF ================= --}}
                            @if(!in_array('5', toggleHideField()))
                            <div class="col-lg-6">
                                <label>Id Proof Received</label>
                                <select class="form-select" name="id_proof_name">
                                    <option value="">Select Id Proof</option>
                                    <option value="1"
                                        {{ (old('id_proof_name') ?? $customer->id_proof_name ?? '') == '1' ? 'selected' : '' }}>
                                        Aadhar
                                    </option>
                                    <option value="2"
                                        {{ (old('id_proof_name') ?? $customer->id_proof_name ?? '') == '2' ? 'selected' : '' }}>
                                        Driving License
                                    </option>
                                    <option value="3"
                                        {{ (old('id_proof_name') ?? $customer->id_proof_name ?? '') == '3' ? 'selected' : '' }}>
                                        Other
                                    </option>
                                </select>
                                <span class="text-danger">Uploading ID proof is optional do it later.</span>
                            </div>

                            <div class="col-lg-6">
                                <label for="id_proof_file">Upload Scan Copy of Proof</label>
                                

                                <input type="file" class="form-control id_proof_file image-cropper @error('id_proof_file') is-invalid @enderror" name="id_proof_file" autocomplete="off">
                                <img class="preview-img one" style="display:none; max-width:250px; margin-top:1rem;">
                                @error('id_proof_file')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                                @if($customer->id_proof_file)
                                <a href="{{ asset($customer->id_proof_file) }}" class="view-image">View</a>

                                @endif
                            </div>
                            @endif
                             @if(!in_array('8', toggleHideField()))
                           
                             <div class="col-lg-6">
                                <label for="profile_picture">Upload Profile Photo</label>
                                <input type="file" class="form-control image-cropper @error('profile_picture') is-invalid @enderror" name="profile_picture"   value="{{ old('profile_picture', $customer->profile_picture) }}"
                                    autocomplete="off" accept=".jpeg, .jpg, .png, .webp">  
                                <img class="preview-img" style="display:none; max-width:100px; margin-top:1rem;">


                                @error('profile_picture')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            @if($customer->profile_picture)
                                <a href="{{ asset($customer->profile_picture) }}" class="view-image">View</a>
                                
                            @endif
                            </div>
                            @endif
                            @if(!in_array('29', toggleHideField()))
                            <div class="col-lg-6 ">
                                <label for="father_name">Father Name</label>
                                <input type="text" class="form-control char-only" name="father_name" id="father_name" placeholder="Enter Father name" value="{{old('father_name')}}">
                            </div>
                            @endif

                            {{-- ================= ALTERNATE MOBILE ================= --}}
                            @if(!in_array('30', toggleHideField()))
                            <div class="col-lg-6">
                                <label for="alternate_mobile">Alternate Mobile No.</label>
                                <input type="text"
                                    class="form-control digit-only"
                                    name="alternate_mobile"
                                    maxlength="10"
                                    minlength="10"
                                    placeholder="Enter Alternate Mobile No."
                                    value="{{ old('alternate_mobile') ?? $customer->alternate_mobile ?? '' }}">
                            </div>
                            @endif

                            {{-- ================= PREPARE FOR ================= --}}
                            @if(!in_array('4', toggleHideField()))
                            <div class="col-lg-6">
                                <label for="prepareFor">Prepare For</label>
                                <select name="exam_id" class="form-select">
                                    <option value="">Learner is Prepare For Exam</option>
                                    @foreach($exams as $value)
                                        <option value="{{ $value->id }}"
                                            {{ (old('exam_id') ?? $customer->exam_id ?? '') == $value->id ? 'selected' : '' }}>
                                            {{ $value->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            {{-- ================= ADDRESS ================= --}}
                            @if(!in_array('32', toggleHideField()))
                            <div class="col-lg-12">
                                <label for="address">Address</label>
                                <textarea class="form-control"
                                        name="address"
                                        rows="3"
                                        placeholder="Enter address">{{ old('address') ?? $customer->address ?? '' }}</textarea>
                            </div>
                            @endif

                            {{-- ================= REMARK ================= --}}
                            @if(!in_array('31', toggleHideField()))
                            <div class="col-lg-12">
                                <label for="remark">Remark</label>
                                <textarea class="form-control"
                                        name="remark"
                                        rows="3"
                                        placeholder="Enter Remark">{{ old('remark') ?? $customer->remark ?? '' }}</textarea>
                            </div>
                            @endif

                        </div>
                       </div>

                        @endif
                        @endif

                        <div class="row mt-4">
                            <div class="col-lg-4">
                                <input type="submit" class="btn btn-primary btn-block button" value="Book Library Seat Now" autocomplete="off">
                            </div>
                        </div>

                    </div>
                </form>
 

            </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="seatnumber">
                <img src="{{ asset('public/img/available.png') }}" alt="Seat" class="py-3 {{$planDetails['class']}}" style="width:60px; display:block; margin:0 auto;">
               @if($customer->seat_no)
                <span class="d-block ">Seat No : {{ getSeatDisplayByMainNo($customer->seat_no)}}</span>
                @else
                <span class="d-block ">General</span>
                @endif
                <div class="seat--plan">{{ $customer->planType->name ?? 'No Plan Type' }}</div>
                
            </div>
        </div>
    </div>

<script>
           
    $(document).ready(function () {

        // Plan Addons Toggle
        $(document).on('click', '.qr_addonToggleIcon', function () {
            $('.qr_lockerFields').slideToggle(200);
            $(this).toggleClass('fa-plus fa-minus');
        });

        // Other Optional Fields Toggle
        $(document).on('click', '.qr_toggleIcon', function () {
            $('.qr_idProofFields').slideToggle(200);
            $(this).toggleClass('fa-plus fa-minus');
        });

    });
</script>

<script>
    $(document).ready(function() {
        $('#toggleIcon').click(function() {
            $('#idProofFields').slideToggle();

            if ($('#idProofFields').is(':visible')) {
                $('#toggleIcon').removeClass('fa-plus').addClass('fa-minus');
            } else {
                $('#toggleIcon').removeClass('fa-minus').addClass('fa-plus');
            }
        });
    });

</script>



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
   

        const toggleHiddenFields = @json(toggleHideField());
        const plan_id11 = $('#plan_id11').val();
        const selectedPlanType = $('#plan_type_id11').val();
        const learner_id = $('#renew_learner_id').val();

        const seatNo = $('#seat_id11').val();
        console.log('selectedPlanType',selectedPlanType);
     
        var seatDisplayMap = @json(
            collect(generateSeatNumbers())->mapWithKeys(function($seat) {
                // If floor info exists, show "floor-seat (floor name)"
                if (!empty($seat['floor']) && !empty($seat['floor_name'])) {
                    return [$seat['main'] => $seat['floor'] . ' (' . $seat['floor_name'] . ')'];
                } else {
                    // Fallback: show main seat number
                    return [$seat['main'] => $seat['main']];
                }
            })
        );

        if (seatNo !=='' && seatNo !== 0 && seatNo !== undefined) {
           
           var seatDisplay = seatDisplayMap[seatNo] ?? seatNo;
          
            $('#qr_general_seat').val('no').trigger('change');
         

        } else if (toggleHiddenFields.includes('12') === true) {
            
            $('#qr_general_seat').val('no').trigger('change');
            
        } else {
          
            @can('has-permission', 'General Seat Booking')
                $('#qr_general_seat').val('yes').trigger('change');
            @else
                // User does NOT have permission → force NO and hide YES option
                $('#qr_general_seat').val('no').trigger('change');

                // Hide the "yes" option from the dropdown
                $('#qr_general_seat option[value="yes"]').hide();
            @endcan
            $('#seat_id11').prop('disabled', true);
     
        }

        $('#qr_general_seat').on('change', function () {
           
            if ($(this).val() === 'no') {
                $('#seat_id11').prop('disabled', false);
                 $('#seat_id11').val(seatNo);
                 getTypeSeatwise(seatNo,selectedPlanType);
            } else {
                $('#seat_id11').val($('#seat_id11 option:first').val()); 
                $('#seat_id11').prop('disabled', true);
                getTypeSeatwise('',selectedPlanType);
            }
        });
       
        


        if (learner_id) {

            if (!seatNo || seatNo === 'gen') {
                getTypeSeatwise('',selectedPlanType)
            } else {
                getTypeSeatwise(seatNo, selectedPlanType); 
            }

        } else {


            if (!seatNo || seatNo === 'gen') {
                getTypeSeatwise('', selectedPlanType); // load all plan types
            } else {
                getTypeSeatwise(seatNo, selectedPlanType); // load plan types seatwise
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
        $('#paid_amount11').val("");
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
            plan_type_id11
            , plan_id11
            , lockerCheck
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
                url: "{{ route('getPricePlanwise') }}"
                , type: 'GET'
                , data: {
                    "_token": "{{ csrf_token() }}"
                    , "plan_type_id": plan_type_id11
                    , "plan_id": plan_id11
                , }
                , dataType: 'json'
                , success: function(html) {
                    console.log('htmoll', html);

                    if (html && html !== undefined) {

                        $('#pending_amt11').html('');
                        $("#plan_price11").prop("value", html);

                        calculatePaidTotalAmount();
                        $("#error-message").hide();
                    } else {
                        $("#plan_price11").val("");

                        $("#pending_amt11").html("No Plan Price Added Yet.");
                        $("#paid_amount11").val("");
                    }
                }

            });
        } else {
            $("#plan_price11").empty();

            $("#paid_amount11").empty();

        }
    }

    function getTypeSeatwise(seatId, selectedPlanType = null) {
        $('#plan_type_id11').empty().append('<option value="">Choose Shift</option>');

        $.ajax({
            url: "{{ route('getPlanTypeForRenew') }}"
            , type: 'GET'
            , data: {
                "_token": "{{ csrf_token() }}"
                , "seatNo": seatId
                , "planType": selectedPlanType

            , }
            , dataType: 'json'
            , success: function(html) {
                console.log('plantype',html);
                if (html && html.length > 0) {
                    // $("#plan_type_id11").empty().append('<option value="">Choose Shift</option>');

                    $.each(html, function(index, planType) {
                        let isSelected = (selectedPlanType && selectedPlanType == planType.id) ? 'selected' : '';
                        $("#plan_type_id11").append('<option value="' + planType.id + '" ' + isSelected + '>' + planType.name + '</option>');
                    });
                } else {
                    $("#plan_type_id11").empty().append('<option value="">No Plan Types Available</option>');
                }
            }
            , error: function(xhr, status, error) {
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
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
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
        $('#paid_amount11').val(autoPaid);

        // const previous_amount = $('#previous_amount11').val();

        // const diffrence = autoPaid - previous_amount;
        // $('#diffrence_amount11').val(diffrence);
        // if (diffrence < 0) {
        //     $('label[for="paid_amount11"]').text("Refund Amount *");


        // } else {
        //     $('label[for="paid_amount11"]').text("Paid Amount *");

        // }
        // if (diffrence === 0) {
        //     $('.differencePayment').hide();
        // } else {
        //     $('.differencePayment').show();
        // }

    }

    function calculatePendingAmt() {
        const planPrice = parseFloat($('#plan_price11').val()) || 0;
        const paidAmount = parseFloat($('#paid_amount11').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount11').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount11').val()) || 0;
        const discountType = $('#discountType11').val();



        discountAmount = 0;
        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmount = discountRaw;
        }


        const effectivePaid = planPrice + lockerAmount - discountAmount;
        const pendingAmount = effectivePaid - paidAmount;


        $('#pending_amt11').val(pendingAmount);

        if (pendingAmount > 0) {
            $('#pending_amt11').html('Pending Amount: ' + pendingAmount);
        } else if (pendingAmount < 0) {
            $('#pending_amt11').html('High price not allowed.' + pendingAmount);
        } else {
            $('#pending_amt11').html('');
        }

        if (pendingAmount > 0) {
            $('#due_date11').removeAttr('readonly');
        } else {
            $('#due_date11').attr('readonly', true);
        }


    }

    // function fetchPlanTypesRenewSeat(seat_no,learner_id) {

    //     if (seat_no  && learner_id) {
    //         $.ajax({
    //             url: '{{ route('getPlanTypeForRenew') }}',
    //             headers: {
    //                 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    //             },
    //             type: 'GET',
    //             data: {
    //                 "_token": "{{ csrf_token() }}",
    //                 "seat_no": seat_no,
    //                 "learner_id": learner_id,
    //             },
    //             dataType: 'json',
    //             success: function (html) {
    //                 console.log("renew",html);
    //                 $("#plan_type_id_renew").empty(); 
    //                 $("#plan_id2").empty(); 

    //                 if (html[0]) {
    //                     $.each(html[0], function (key, value) {
    //                         $("#plan_type_id_renew").append('<option value="' + key + '">' + value + '</option>');
    //                     });
    //                 } else {
    //                     $("#plan_type_id_renew").append('<option value="">Choose</option>');
    //                 }


    //                 if (html[1]) {
    //                         $.each(html[1], function (key, value) {
    //                         $("#plan_id2").append('<option value="' + key + '">' + value + '</option>');
    //                     });
    //                 }

    //                 if (html[2]){
    //                     $("#plan_price_id2").val(html[2].plan_price_id);      
    //                 }

    //                 if(html[3]){
    //                     $("#locker_amount2").val(html[3].locker_amount);  
    //                     $("#discount_amount3").val(html[3].discount_amount);  
    //                     $("#new_plan_price").val(html[3].discount_amount);  

    //                     if (html[3].locker_amount && parseFloat(html[3].locker_amount) > 0) {
    //                         $("#locker").val('yes');
    //                         $("#locker_amount2").val(html[3].locker_amount);

    //                     } else {
    //                         $("#locker").val('no');
    //                         $("#locker_amount2").val('');

    //                     }

    //                     if (html[3].discount_amount && parseFloat(html[3].discount_amount) > 0) {
    //                         $("#discount_type").val('amount');
    //                         $("#discount_amount3").val(html[3].discount_amount);
    //                     } else {
    //                         $("#discount_type").val('');
    //                         $("#discount_amount3").val('');
    //                     }
    //                 }
    //                     if (html[4]){
    //                     $("#locker_no2").val(html[4].locker_no);
    //                     if(html[4].locker_no){
    //                     $("#locker_no2").removeAttr('readonly');
    //                     }      
    //                 }

    //                 popupautoCalculatePaidAmount(); 
    //             },
    //             error: function (xhr, status, error) {
    //                 console.error("AJAX error:", status, error); // Log any errors
    //             }
    //         });
    //     } else {
    //         $("#plan_type_id_renew").empty();
    //         $("#plan_type_id_renew").append('<option value="">Choose Shift</option>');
    //     }
    // }

    

</script>

<script>
$(document).ready(function () {

    // Intercept all "View" image links
    $(document).on("click", 'a.view-image, a[target="_blank"]', function (e) {

        const imageUrl = $(this).attr("href");

        // Only handle image links
        if (!imageUrl.match(/\.(jpg|jpeg|png|webp)$/i)) {
            return;
        }

        e.preventDefault();

        $("#modalImage").attr("src", imageUrl);
        $("#imageViewModal").fadeIn(200);
    });

    // Close modal
    $(".close-modal").on("click", function () {
        $("#imageViewModal").fadeOut(200);
        $("#modalImage").attr("src", "");
    });

    // Close on background click
    $("#imageViewModal").on("click", function (e) {
        if ($(e.target).is(this)) {
            $(this).fadeOut(200);
            $("#modalImage").attr("src", "");
        }
    });

});
</script>

@endsection
