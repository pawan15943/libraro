@extends('layouts.library')
@section('content')
@php

$current_route = Route::currentRouteName();
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class=$planDetails['class'];
if($customer->locker_no){
    $locker_read='';
}else{
    $locker_read='readonly';
}
@endphp


<div id="imageViewModal" class="image-modal" style="display:none;">
    <div class="image-modal-content">
        <span class="close-modal">&times;</span>
        <img src="" id="modalImage">
    </div>
</div>



<div class="row g-4">
    <div class="col-lg-9 order-2 order-md-1">
        <form action="{{ route('learners.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input id="edit_seat" type="hidden" name="seat_no" value="{{ old('seat_no', $customer->seat_no) }}">
            <input name="user_id" type="hidden" value="{{$customer->id}}">
            <input name="learner_id" type="hidden" value="{{$customer->id}}">
            <input name="plan_id" type="hidden" value="{{$customer->plan_id}}" id="plan_id10">
            <input name="plan_type_id" type="hidden" value="{{$customer->plan_type_id}}" id="plan_type_id10">
            {{-- <input name="plan_price_id" type="hidden" value="{{$customer->plan_price_id}}" id="plan_price10"> --}}
            <input type="hidden" name="payment_type" value="EDIT" id="payment_type_operation">
            {{-- <input name="plan_start_date" type="hidden" value="{{$customer->plan_start_date}}"> --}}
        
            <div class="library-operations mt-4">
                <div class="info__section">
                    <h4 class="inner-heading">Learner Info</h4>
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <label for="" class="text-white">Seat Owner Name <span>*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror " placeholder="Full Name" name="name" id="name" value="{{ old('name', $customer->name) }}">
                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <label for="" class="text-white">DOB <span>*</span></label>
                            <input type="date" class="form-control @error('dob') is-invalid @enderror" placeholder="DOB" name="dob" id="dob" value="{{ old('dob', $customer->dob) }}">
                            @error('dob')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <label for="" class="text-white">Mobile Number <span>*</span></label>
                            <input type="text" class="form-control @error('mobile') is-invalid @enderror digit-only" maxlength="10" minlength="10" placeholder="Mobile Number" name="mobile" id="mobile" value="{{ old('mobile', $customer->mobile) }}">
                            @error('mobile')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6 ">
                            <label for="" class="text-white">Email Id </label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email Id" name="email" id="email" value="{{ old('email', $customer->email) }}">
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="form-input mb-4">
                    <h4 class="inner-heading">Update Plan Duration</h4>
                    <p class="text-danger">⚠️ Warning: Changing the start date will also affect the plan end date. Please ensure you update it carefully and with full understanding of its impact.</p>
                    <div class="row g-4">

                        <div class="col-lg-6">
                            <label for=""> Plan <span>*</span></label>

                            <select class="form-select" name="plan_id" disabled >
                                <option value="{{ $customer->plan_name }}" selected>{{ $customer->plan_name }}</option>
                            </select>

                            @error('plan_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <label for="">Plan Type <span>*</span></label>
                            <select class="form-select" name="plan_id" disabled>
                                <option value="{{ $customer->plan_type_id }}" selected>{{ $customer->plan_type_name }}</option>
                            </select>

                        </div>
                        <div class="col-lg-6">
                            <label for="plan_start_date">Plan Start Date</label>
                            <input type="date" class="form-control datepicker @error('plan_start_date') is-invalid @enderror" 
                                name="plan_start_date" value="{{ old('plan_start_date', $customer->plan_start_date) }}" id="start_date10">
                            @error('plan_start_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-lg-6">
                            <label for="plan_end_date">Plan End Date</label>
                            <input type="date" class="form-control datepicker @error('plan_end_date') is-invalid @enderror" 
                                name="plan_end_date" value="{{ old('plan_end_date', $customer->plan_end_date) }}" id="plan_end_date_edit" readonly>
                            @error('plan_end_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label>Plan Price <span>*</span></label>
                            <input id="plan_price10" class="form-control @error('plan_price_id') is-invalid @enderror" value="{{ old('plan_price_id', $customer->plan_price_id) }}"  name="plan_price_id" readonly>
                            @error('plan_price_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <h4 class="mt-4 mb-3">Your plan Addon's
                                <i class="fa fa-plus toggleIcon1" style="cursor: pointer;"></i>
                        </h4>

                        <div style="display: none;" class="mb-3 idProofFields1">
                            <div class="row g-4">
                                @if(!in_array('3', toggleHideField()) || (in_array('3', toggleHideField()) && ($hasLocker == 'yes')))
                                <div class="col-lg-4 {{ !is_locker() ? 'd-none' : '' }}">
                                    <label>Locker?</label>
                                    <select name="locker" id="toggleFieldCheckbox10" class="form-control">
                                    
                                        <option value="no" {{ old('locker', $hasLocker) === 'no' ? 'selected' : '' }}>No</option>
                                        <option value="yes" {{ old('locker', $hasLocker) === 'yes' ? 'selected' : '' }}>Yes, I Need a Locker</option>
                                    </select>
                                </div>

                                <div class="col-lg-4 {{ !is_locker() ? 'd-none' : '' }}">
                                    <label>Locker Amount<span>*</span></label>
                                    <input type="text" id="locker_amount10" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" placeholder="0.00"  value="{{ old('locker_amount', $locker_amt) }}" readonly>
                                    @error('locker_amount')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2" >
                                    <label for="locker_no">Locker No.</label>
                                    <input type="text" class="form-control digit-only @error('locker_no') is-invalid @enderror" name="locker_no" id="locker_no10"  placeholder="Enter Locker No." value="{{ old('locker_no', $customer->locker_no) }}"  {{$locker_read}}>
                                    @error('locker_no')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                @endif
                                @if(!in_array('6', toggleHideField()) || (in_array('6', toggleHideField()) && $discountAmount) )
                                <div class="col-lg-6">
                                    <label>Discount Type</label>
                                    <select id="discountType10" class="form-control form-select" name="discountType">
                                        <option value="">Select Discount Type</option>
                                        <option value="amount" {{ old('discountType', $selectedDiscountType) == 'amount' ? 'selected' : '' }}>Amount</option>
                                        <option value="percentage" {{ old('discountType', $selectedDiscountType) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                        
                                    </select>
                                </div>

                                <div class="col-lg-6">
                                    <label>Discount Amount ( <span id="typeVal10">INR / %</span> )</label>
                                    <input type="text" id="discount_amount10" class="form-control @error('discount_amount') is-invalid @enderror" placeholder="0.00" name="discount_amount"  
                                
                                    value="{{ old('discount_amount', currentTransaction($customer->learner_detail_id)->discount_amount ?? 0) }}"
                                    readonly>
                                    @error('discount_amount')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                @endif
                                
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <label for="">Last paid Amount <span>*</span></label>
                            <input type="text" class="form-control @error('previous_amount') is-invalid @enderror"
                                name="previous_amount" id="previous_amount10"
                                value="{{ currentTransaction($customer->learner_detail_id)->paid_amount }}" readonly>
                            @error('previous_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label>Total Amount <span>*</span></label>
                            <input type="text" id="total_amount10" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount"   value="{{ old('paid_amount', optional(currentTransaction($customer->learner_detail_id))->total_amount) }}" {{ (Route::currentRouteName() == 'learner.change.plan' ) ? 'readonly' : '' }}> 
                            @error('paid_amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                            <span id="chargeable_days10" class="text-info"></span>
                        </div>
                        <div class="col-lg-4">
                            <label for="diffrence_amount10">Diffrence Amount <span>*</span></label>
                            <input type="text" class="form-control @error('diffrence_amount') is-invalid @enderror"
                                name="diffrence_amount" id="diffrence_amount10"  value="{{ old('diffrence_amount') }}"    placeholder="0.00">
                            @error('diffrence_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                                
                        </div>
                        <div class="col-lg-4">
                            <label for="pending_amt10">Pending Amount <span>*</span></label>
                            <input type="text" id="pending_amt10" class="form-control" name="pending_amount" placeholder="0" value="{{ old('pending_amount') }}"  readonly>
                            <span id="pending_amt_error" class="text-danger"></span>
                        </div>
                        <div class="col-lg-4">
                            <label for="">Choose Due Date</label>
                            <input type="date" id="due_date10" class="form-control duedate  @error('due_date') is-invalid @enderror" placeholder="Enter Due Date" name="due_date"  readonly>
                            @error('due_date')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        
                        <div class="col-lg-4">
                            <label>Payment Mode <span>*</span></label>
                            <select name="payment_mode" id="payment_mode10" class="form-control form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="1" {{ $customer->payment_mode == 1 ? 'selected' : '' }}>Online</option>
                                <option value="2" {{ $customer->payment_mode == 2 ? 'selected' : '' }}>Offline</option>
                                <option value="3" {{ $customer->payment_mode == 3 ? 'selected' : '' }}>Pay Later</option>
                            </select>
                            @error('payment_mode')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                    </div>
                    
                </div>

                <div class="form-input mb-4">
                    <h4 class="inner-heading">Learner Other Info</h4>
                    <p class="text-danger">Note : These details are optional. You may fill them in if you wish, or leave them blank.</p>
                    <div class="row g-4">
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

                        @if(!in_array('30', toggleHideField()))
                        <div class="col-lg-6 ">
                            <label for="alternate_mobile">Alternate Mobile No.</label>
                            <input type="text" class="form-control @error('alternate_mobile') is-invalid @enderror digit-only" name="alternate_mobile"  maxlength="10" minlength="10" placeholder="Enter Alternate Mobile No." value="{{ old('alternate_mobile', $customer->alternate_mobile) }}">
                            @error('alternate_mobile')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        @endif

                        @if(!in_array('29', toggleHideField()))
                        <div class="col-lg-6 ">
                            <label for="father_name">Father Name</label>
                            <input type="text" class="form-control @error('father_name') is-invalid @enderror char-only" name="father_name"  placeholder="Enter Father name" value="{{ old('father_name', $customer->father_name) }}">
                            @error('father_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        @endif

                        @if(!in_array('4', toggleHideField()))
                        <div class="col-lg-6 ">
                            <label for="prepareFor">Prepare For</label>
                            <select name="exam_id"  class="form-select @error('exam_id') is-invalid @enderror">
                                <option value="">Learner is Prepare For Exam</option>
                                @foreach($exams as $key => $value)
                                <option value="{{$value->id}}" {{ old('exam_id', $customer->exam_id) == $value->id ? 'selected' : '' }}>{{$value->name}}</option>   
                                @endforeach
                            </select>
                            @error('exam_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        @endif

                        @if(!in_array('5', toggleHideField()))
                        <div class="col-lg-4">
                            <label for="">ID Proof Name(Optional)</label>
                            <select  class="form-select @error('id_proof_name') is-invalid @enderror" name="id_proof_name" value="{{ old('id_proof_name', $customer->id_proof_name) }}">
                                <option value="">Select Id Proof</option>
                               
                                <option value="1" {{ old('id_proof_name', $customer->id_proof_name) == 1 ? 'selected' : '' }}>
                                    Aadhar Card
                                </option>

                                <option value="2" {{ old('id_proof_name', $customer->id_proof_name) == 2 ? 'selected' : '' }}>
                                    Driving License
                                </option>

                                <option value="4" {{ old('id_proof_name', $customer->id_proof_name) == 4 ? 'selected' : '' }}>
                                    Pan Card
                                </option>

                                <option value="5" {{ old('id_proof_name', $customer->id_proof_name) == 5 ? 'selected' : '' }}>
                                    Voter Id
                                </option>

                                <option value="3" {{ old('id_proof_name', $customer->id_proof_name) == 3 ? 'selected' : '' }}>
                                    Other
                                </option>
                            </select>
                            @error('id_proof_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                         <div class="col-lg-4">
                            <label for="address">ID Proof No.</label>
                            <input type="text" class="form-control  @error('id_proof_number') is-invalid @enderror" name="id_proof_number" placeholder="Enter ID proof no." maxlength="12" value="{{ old('id_proof_number', $customer->id_proof_number) }}">
                        </div>

                        <div class="col-lg-4">
                            <label for="">Upload Scan Copy of Proof (Optional)</label>
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
                            <span class="text-danger">*Upload front side of document.</span>
                        </div>
                        @endif

                        @if(!in_array('32', toggleHideField()))
                        <div class="col-lg-12 ">
                            <label for="address">Address</label>
                            <textarea class="form-control h-auto @error('address') is-invalid @enderror" name="address"  rows="3" placeholder="Enter address">{{ old('address', $customer->address) }}</textarea>
                            @error('address')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        @endif

                        @if(!in_array('31', toggleHideField()))
                        <div class="col-lg-12 ">
                            <label for="remark">Remark</label>
                            <textarea class="form-control h-auto @error('remark') is-invalid @enderror" name="remark"  rows="3" placeholder="Enter Remark">{{ old('remark', $customer->remark) }}</textarea>
                            @error('remark')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        @endif
                    
                    </div>
                    <div class="row mt-3">
                        <div class="col-lg-3">
                            <button type="submit" class="btn btn-primary btn-block button">Update Seat Info</button>
                        </div>
                    </div>
                </div>
            </div>
            
        </form>
    </div>
    <div class="col-lg-3 order-1 order-md-2">
        <div class="seatnumber">
            <img src="{{ asset($customer->image) }}" alt="Seat" class="py-3 {{$class}}" style="width:60px; display:block; margin:0 auto;">
            @if($customer->seat_no)
            <span class="d-block ">Seat No : {{ getSeatDisplayByMainNo($customer->seat_no)}}</span>
            @else
            <span class="d-block ">General</span>
            @endif
            <div class="seat--plan">{{ $customer->plan_type_name}}</div>
        </div>
    </div>
</div>



<script>
$(document).ready(function () {

    $("#plan_start_date_edit").on("change", function () {

        let startDate = $(this).val();
        let totalDays = parseInt($("#total_days").val()); // inclusive days

        // If missing data, do nothing
        if (!startDate || !totalDays || totalDays < 1) return;

        let start = new Date(startDate);

        // Inclusive means: add (days - 1)
        start.setDate(start.getDate() + (totalDays - 1));

        // Format to yyyy-mm-dd
        let yyyy = start.getFullYear();
        let mm = ("0" + (start.getMonth() + 1)).slice(-2);
        let dd = ("0" + start.getDate()).slice(-2);

        let formatted = `${yyyy}-${mm}-${dd}`;

        // UPDATE THE END DATE
        $("#plan_end_date_edit").val(formatted).trigger("change");

    });

});


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