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


<div class="row g-4">
 <div class="col-lg-9 order-2 order-md-1">
<form action="{{ route('learners.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input id="edit_seat" type="hidden" name="seat_no" value="{{ old('seat_no', $customer->seat_no) }}">
    <input name="user_id" type="hidden" value="{{$customer->id}}">
    <input name="plan_id" type="hidden" value="{{$customer->plan_id}}">
    <input name="plan_type_id" type="hidden" value="{{$customer->plan_type_id}}">
    <input name="plan_price_id" type="hidden" value="{{$customer->plan_price_id}}">
    <input name="plan_start_date" type="hidden" value="{{$customer->plan_start_date}}">
  
    <div class="library-operations mt-4">
        <div class="info__section">
            <h4 class="inner-heading">Learner Info</h4>
            <div class="row g-4">
                <div class="col-lg-6">
                    <label for="" class="text-white">Seat Owner Name <span>*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror char-only" placeholder="Full Name" name="name" id="name" value="{{ old('name', $customer->name) }}">
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

                    <select class="form-select" name="plan_id" disabled id="plan_id">
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
                        name="plan_start_date" 
                        value="{{ old('plan_start_date', $customer->plan_start_date) }}" 
                        id="plan_start_date_edit">
                    @error('plan_start_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6">
                    <label for="plan_end_date">Plan End Date</label>
                    <input type="date" class="form-control datepicker @error('plan_end_date') is-invalid @enderror" 
                        name="plan_end_date" 
                        value="{{ old('plan_end_date', $customer->plan_end_date) }}" 
                        id="plan_end_date_edit" readonly>
                    @error('plan_end_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                @php
                $start = \Carbon\Carbon::parse($customer->plan_start_date);
                $end   = \Carbon\Carbon::parse($customer->plan_end_date);
                $inclusive_days = $start->diffInDays($end) + 1;   // always inclusive
                @endphp

                <input type="hidden" id="total_days" value="{{ $inclusive_days }}">

            </div>
            
        </div>

        <div class="form-input mb-4">
            <h4 class="inner-heading">Learner Other Info</h4>
            <p class="text-danger">Note : These details are optional. You may fill them in if you wish, or leave them blank.</p>
            <div class="row g-4">
                @if(!in_array('8', toggleHideField()))
                    <div class="col-lg-6">
                        <label for="profile_picture">Upload Profile Photo</label>
                        <input type="file" class="form-control @error('profile_picture') is-invalid @enderror" name="profile_picture"  value="{{ old('profile_picture', $customer->profile_picture) }}"
                            autocomplete="off" accept=".jpeg, .jpg, .png, .webp">  
                        @error('profile_picture')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    @if($customer->profile_picture)
                        <a href="{{ asset($customer->profile_picture) }}" target="_blank">View</a>
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
                <div class="col-lg-6">
                    <label for="">ID Proof Name(Optional)</label>
                    <select  class="form-select @error('id_proof_name') is-invalid @enderror" name="id_proof_name" value="{{ old('id_proof_name', $customer->id_proof_name) }}">
                        <option value="">Select Id Proof</option>
                        <option value="1" {{ old('id_proof_name', $customer->id_proof_name) == 1 ? 'selected' : '' }}>Aadhar Card</option>
                        <option value="2" {{ old('id_proof_name', $customer->id_proof_name) == 2 ? 'selected' : '' }}>Driving License</option>
                        <option value="3" {{ old('id_proof_name', $customer->id_proof_name) == 3 ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('id_proof_name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="col-lg-6">
                    <label for="">Upload Scan Copy of Proof (Optional)</label>
                    <input type="file" class="form-control @error('id_proof_file') is-invalid @enderror" name="id_proof_file" autocomplete="off">
                    @error('id_proof_file')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    @if($customer->id_proof_file)
                    <a href="{{ asset('public/'.$customer->id_proof_file) }}" target="_blank">View</a>
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
                    <input type="submit" class="btn btn-primary btn-block button"  value="Update Seat Info" autocomplete="off">
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

@endsection