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
            <input type="hidden" name="payment_type" value="EDITLEARNER" id="payment_type_operation">
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
                    <h4 class="inner-heading">Learner Other Info</h4>
                    <p class="text-danger">Note : These details are optional. You may fill them in if you wish, or leave them blank.</p>
                    <div class="row g-4">
                     
                        @if(!in_array('8', toggleHideField()))
                            <div class="col-lg-6">
                                <label for="profile_picture">Upload Profile Photo</label>
                                <input type="file" class="form-control image-cropper @error('profile_picture_image') is-invalid @enderror" name="profile_picture_image"   value="{{ old('profile_picture', $customer->profile_picture) }}"
                                    autocomplete="off" accept=".jpeg, .jpg, .png, .webp">  
                                <img class="preview-img" style="display:none; max-width:100px; margin-top:1rem;">


                                @error('profile_picture_image')
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
                           <div class="col-lg-6">
                            <label for="sended_message_type">Send Reminders Via (Optional)</label>
                            <select id="sended_message_type" class="form-select" name="sended_message_type">
                                <option value="">Select Type</option>
                                @if($hasFreeWaba ?? false)
                                <option value="whatsapp" {{ old('sended_message_type', $customer->sended_message_type) == 'whatsapp' ? 'selected' : '' }}>WhatsApp Message Only</option>
                                @endif
                                @if($hasFreeText ?? false)
                                <option value="text" {{ old('sended_message_type', $customer->sended_message_type) == 'text' ? 'selected' : '' }}>Text Message Only</option>
                                @endif
                                @if(($hasFreeWaba ?? false) && ($hasFreeText ?? false))
                                <option value="both" {{ old('sended_message_type', $customer->sended_message_type) == 'both' ? 'selected' : '' }}>Both (WhatsApp & Text Message)</option>
                                @endif
                                <option value="no" {{ old('sended_message_type', $customer->sended_message_type) == 'no' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label for="no_expiry">No Expiry Seat (Optional)</label>
                            <select name="no_expiry" id="no_expiry" class="form-select @error('no_expiry') is-invalid @enderror">
                                <option value="">Select Expiry Mode</option>
                                <option value="1" {{ old('no_expiry', $customer->no_expiry) == 1 ? 'selected' : '' }}>Yes, Make it non expired seat.</option>
                                <option value="0" {{ old('no_expiry', $customer->no_expiry) == 0 ? 'selected' : '' }}>No</option>
                            </select>
                            @error('no_expiry')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

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
                            <input type="file" class="form-control id_proof_file image-cropper @error('id_proof') is-invalid @enderror" name="id_proof" autocomplete="off">
                            <img class="preview-img one" style="display:none; max-width:250px; margin-top:1rem;">
                            @error('id_proof')
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
            <span class="d-block ">Seat No : {{ getSeatDisplayShortFloorName($customer->seat_no)}}</span>
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
