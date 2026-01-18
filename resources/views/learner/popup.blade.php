@can('has-permission', 'Book Seat')



<div class="modal fade" id="seatAllotmentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div id="success-message" class="alert alert-success" style="display:none;"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title px-2 fs-5" id="seat_no_head"></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="error-message" class="alert alert-danger mb-4 mt-0" style="display:none;"></div>
                <div id="validation-error-message" class="alert alert-danger mb-4 mt-0" style="display:none;"></div>
                <form id="seatAllotmentForm">
                    @csrf
                    <div class="detailes">

                        <input type="hidden" class="form-control char-only" name="seat_no" value="" id="seat_no" autocomplete="off">

                        <div class="row g-3">

                            {{--Seat Concept======================================================================  --}}
                            <div class="col-lg-6">
                                <label for="general_seat">Assign Seat No ?</label>
                                <select name="general_seat" id="general_seat" class="form-select">

                                    <option value="yes">No</option>

                                    <option value="no">Yes, Allot a Seat No.</option>
                                </select>
                            </div>
                            {{-- Show Only Available Slots or Seat No. --}}
                            <div class="col-lg-6">
                                <label for="seat_id">Choose Seat No. <span>*</span></label>

                                <select name="seat_no" class="form-select" id="seat_id">
                                    <option value="">Choose Seat No</option>
                                    @foreach($newAvailableSeats as $key => $value)
                                    
                                    <option value="{{ $value['main'] }}">{{ $value['display'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- ================================================================== --}}
                            <div class="col-lg-6">
                                <label for="">Full Name <span>*</span></label>
                                <input type="text" class="form-control " name="name" id="name">
                            </div>
                            <div class="col-lg-6">
                                <label for="">Mobile Number <span>*</span></label>
                                <input type="text" class="form-control digit-only" maxlength="10" minlength="10" name="mobile" id="mobile">
                            </div>

                            @if(!in_array('2', toggleHideField()))
                            <div class="col-lg-6">
                                <label for="">DOB (Optional)</label>
                                <input type="date" class="form-control dob" name="dob" id="dob" max="<?php echo date('Y-m-d', strtotime('-10 years')); ?>">
                            </div>
                            @endif
                            @if(!in_array('1', toggleHideField()))
                            <div class="col-lg-6">
                                <label for="">Email Id (Optional)</label>
                                <input type="text" class="form-control" name="email" id="email">
                                <span class="text-danger" id="email-error"></span>
                            </div>
                            @endif

                            <div class="col-lg-4">
                                <label for="">Plan <span>*</span></label>
                                <select name="plan_id" id="plan_id3" class="form-select" name="plan_id">

                                    <option value="">Choose</option>
                                    @foreach($plans as $key => $value)
                                    <option value="{{$value->id}}">{{$value->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-4">
                                <label for="">Plan Type / Shift <span>*</span></label>
                                <select id="plan_type_id" class="form-select" name="plan_type_id">
                                    <option value="">Choose</option>

                                </select>
                            </div>

                            <div class="col-lg-4">
                                <label for="">Plan Starts On <span>*</span></label>
                                <input type="date" class="form-control datepicker" placeholder="Plan Starts On" name="plan_start_date" id="plan_start_date">
                            </div>

                            <input type="hidden" id="plan_price_id" class="form-control" name="plan_price_id" placeholder="Example : 00 Rs" readonly>
                        </div>
                        @if(!in_array('3', toggleHideField()) || !in_array('6', toggleHideField()))


                        <h4 class="my-3">Your Plan Addon's <i class="fa fa-plus toggleIcon1" style="cursor: pointer;"></i></h4>
                        <div class="idProofFields1">
                            <div class="row g-3">
                                @if(!in_array('3', toggleHideField()))
                                <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}">
                                    <label for="toggleFieldCheckbox">Need a Locker ?</label>
                                    <select name="toggleFieldCheckbox" id="toggleFieldCheckbox2" class="form-select">
                                        <option value="no">No</option>
                                        <option value="yes">Yes, I Need a Locker</option>
                                    </select>
                                </div>
                                <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer" readonly>
                                    <label for="locker_amount">Locker Amount</label>
                                    <input type="text" class="form-control digit-only" name="locker_amount" id="locker_amount_book" placeholder="Locker Amt." readonly>
                                </div>
                                <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                    <label for="locker_no">Locker No.</label>
                                    <input type="text" class="form-control digit-only" name="locker_no" id="locker_no" placeholder="Enter Locker No." readonly>
                                </div>
                                @endif
                                @if(!in_array('6', toggleHideField()))
                                <div class="col-lg-6">
                                    <label for="discountType">Discount Type</label>
                                    <select id="discountType" class="form-select" name="discountType">
                                        <option value="">Discount Type</option>
                                        <option value="amount">Amount</option>
                                        <option value="percentage">Percentage</option>
                                    </select>
                                </div>
                                <div class="col-lg-6">
                                    <label for="discount_amount">Discount Amount ( <span id="typeVal">INR / %</span> )</label>
                                    <input type="text" class="form-control digit-only" name="discount_amount" id="discount_amount" placeholder="Enter Discount Amount">
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                        <div class="row g-3 mt-0">
                            <div class="col-lg-4">
                                <label for="">Final Payble Amount (INR)<span>*</span></label>
                                <input id="paid_amount" class="form-control digit-only" name="paid_amount" placeholder="Example : 00 Rs">
                                <span id="pending_amt" class="text-danger"></span>
                            </div>

                            <div class="col-lg-4">
                                <label for="">Choose Due Date<span>*</span></label>
                                <input type="date" class="form-control duedate" placeholder="Enter Due Date" name="due_date" id="due_date" readonly>
                            </div>



                            <div class="col-lg-4">
                                <label for="">Payment Mode <span>*</span></label>
                                <select name="payment_mode" id="payment_mode" class="form-select">
                                    <option value="">Choose</option>
                                    <option value="1">Online</option>
                                    <option value="2">Offline</option>
                                    <option value="3">Pay Later</option>

                                    {{-- <optgroup label="Cash">
                                        <option value="CASH">CASH</option>
                                    </optgroup>

                                    <optgroup label="Bank Transfers">
                                        <option value="BANK TRANSFER">BANK TRANSFER</option>
                                        <option value="ONLINE BANKING">ONLINE BANKING</option>
                                        <option value="UPI">UPI</option>
                                    </optgroup>

                                    <optgroup label="Digital Wallets">
                                        <option value="PHONE PAY">PHONE PAY</option>
                                        <option value="GOOGLE PAY">GOOGLE PAY</option>
                                        <option value="BHARAT PAY">BHARAT PAY</option>
                                        <option value="PAYTM">PAYTM</option>
                                        <option value="AMAZON PAY">AMAZON PAY</option>
                                        <option value="WHATSAPP PAY">WHATSAPP PAY</option>
                                    </optgroup>

                                    <optgroup label="Other">
                                        <option value="MANUAL">MANUAL</option>
                                        <option value="OTHER">OTHER</option>
                                    </optgroup> --}}
                                </select>
                            </div>
                            @if(notificationActive())
                            <div class="col-lg-12">
                                <label for="">Send Reminders Via (Optional)</label>
                                <select id="sended_message_type" class="form-select" name="sended_message_type">
                                    <option value="">Select Type</option>
                                    <option value="whatsapp">WhatsApp Message Only</option>
                                    <option value="text">Text Message Only</option>
                                    <option value="both">Both (WhatsApp & Text Message)</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            @endif
                        </div>
                        @if(!in_array('7', toggleHideField()))
                        <h4 class="py-4 m-0">Other Optional Fields
                            <i id="toggleIcon" class="fa fa-plus" style="cursor: pointer;"></i>
                        </h4>

                        <div id="idProofFields" style="display: none;">
                            <div class="row g-3">
                               @if(!in_array('8', toggleHideField()))
                                <div class="col-lg-6">
                                    <label class="form-label">Upload Profile Photo</label>
                                    <input
                                        type="file"
                                        class="form-control image-cropper"
                                        name="profile_picture" id="profile_picture" autocomplete="off" accept=".jpeg, .jpg, .png, .webp"
                                    />

                                    <!-- Final Image Preview -->
                                    <div class="mt-3">
                                        <img id="finalImage" class="profile-preview">
                                    </div>
                                </div>
                                @endif
                                @if(!in_array('30', toggleHideField()))
                                <div class="col-lg-6 ">
                                    <label for="alternate_mobile">Alternate Mobile No.</label>
                                    <input type="text" class="form-control digit-only" name="alternate_mobile" id="alternate_mobile" maxlength="10" minlength="10" placeholder="Enter Alternate Mobile No.">
                                </div>
                                @endif
                                
                                @if(!in_array('29', toggleHideField()))
                                <div class="col-lg-6 ">
                                    <label for="father_name">Father Name</label>
                                    <input type="text" class="form-control char-only" name="father_name" id="father_name" placeholder="Enter Father name">
                                </div>
                                @endif

                                @if(!in_array('4', toggleHideField()))
                                <div class="col-lg-6 ">
                                    <label for="prepareFor">Prepare For</label>
                                    <select name="exam_id" id="prepareFor" class="form-select">
                                        <option value="">Learner is Prepare For Exam</option>
                                        @foreach($exams as $key => $value)
                                        <option value="{{$value->id}}">{{$value->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                               

                                @if(!in_array('5', toggleHideField()))
                                <div class="col-lg-6">
                                    <label for="">ID Proof Name(Optional)</label>
                                    <select id="id_proof_name" class="form-select" name="id_proof_name">
                                        <option value="">Select Id Proof</option>
                                        <option value="1">Aadhar</option>
                                        <option value="2">Driving License</option>
                                        <option value="3">Other</option>
                                    </select>
                                    <span class="text-danger">Uploading ID proof is optional do it later.</span>
                                </div>

                                <div class="col-lg-6">
                                    <label for="id_proof_file">Upload Scan Copy of Proof</label>
                                    <input type="file" class="form-control" name="id_proof_file" id="id_proof_file" autocomplete="off">

                                    <a href="javascript:;" id="viewButton" style="display: none;">
                                        <i class="fa fa-eye"></i> View Uploaded File
                                    </a>
                                    <div id="filePopup" class="file-popup" style="display: none;">
                                        <img src="" id="imagePreview" style="display: none;" alt="Selected Image">
                                        <iframe id="pdfPreview" style="display: none;" frameborder="0"></iframe>
                                    </div>
                                </div>
                                @endif
                                
                               
                                @if(!in_array('32', toggleHideField()))
                                <div class="col-lg-12 ">
                                    <label for="address">Address</label>
                                    <textarea class="form-control" name="address" id="address" rows="3" placeholder="Enter address"></textarea>
                                </div>
                                @endif
                                @if(!in_array('31', toggleHideField()))
                                <div class="col-lg-12 ">
                                    <label for="remark">Remark</label>
                                    <textarea class="form-control" name="remark" id="remark" rows="3" placeholder="Enter Remark"></textarea>
                                </div>
                                @endif
                            </div>

                        </div>
                        @endif

                        <div class="row mt-4">
                            <div class="col-lg-4">
                                <button type="submit" class="btn btn-primary btn-block button">Book Seat Now</button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
@endcan



@can('has-permission', 'Renew Seat')
<div class="modal fade" id="seatAllotmentModal3" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div id="success-message" class="alert alert-success" style="display:none;"></div>
    <div id="error-message" class="alert alert-danger" style="display:none;"></div>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="seat_number_upgrades"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body m-0">
                <form id="upgradeForm">

                    <div class="detailes">
                        <input type="hidden" id="hidden_plan">
                        <p class="text-danger mb-3"><b>Note</b> :Your upcoming plan starts after your current plan expires.</p>
                        <div class="actions">
                            <div class="upper-box">
                                <div class="row g-4">
                                    <div class="col-lg-12 col-6">
                                        <span>Learner UID</span>
                                        <h5 id="learner_uid" class="uppercase">NA</h5>
                                    </div>
                                    <div class="col-lg-6 col-6">
                                        <span>Seat Owner Name</span>
                                        <h5 id="learner_name" class="uppercase">NA</h5>
                                    </div>

                                    <div class="col-lg-6 col-6">
                                        <span>Mobile Number</span>
                                        <h5 id="learner_mobilepop">NA</h5>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <h4 class="mt-4 mb-3">Current Plan Info</h4>
                        <div class="row g-3">
                            <!-- Plan Info -->
                            <div class="col-lg-4">
                                <label for="">Select Plan <span>*</span></label>
                                <select id="plan_id2" class="form-control" name="plan_id" @readonly(true)></select>
                            </div>
                            <div class="col-lg-4">
                                <label for="">Plan Type <span>*</span></label>
                                <select id="plan_type_id_renew" class="form-control" name="plan_type_id" @readonly(true)></select>
                            </div>
                            <div class="col-lg-4">
                                <label for="">Plan Price <span>*</span></label>
                                <input id="plan_price_id2" class="form-control" placeholder="Plan Price" name="plan_price_id" readonly>
                            </div>
                        </div>


                        <h4 class="mt-4 mb-3">Your plan Addon's
                            <i class="fa fa-plus toggleIcon1" style="cursor: pointer;"></i>
                        </h4>

                        <div style="display: none;" class="mb-3 idProofFields1">
                            @if(!in_array('3', toggleHideField()))
                            <div class="row g-3">
                                <div class="col-lg-4 {{ !is_locker() ? 'd-none' : '' }}">
                                    <label for="locker">Locker?</label>
                                    <select name="locker" id="locker" class="form-select">
                                        <option value="no">No</option>
                                        <option value="yes">Yes, I Need a Locker</option>
                                    </select>
                                </div>
                                <div class="col-lg-4 {{ !is_locker() ? 'd-none' : '' }}">
                                    <label for="">Locker Amount <span>*</span></label>
                                    <input type="text" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" id="locker_amount2" readonly>

                                </div>
                                <div class="col-lg-4 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                    <label for="locker_no">Locker No.</label>
                                    <input type="text" class="form-control digit-only" name="locker_no" id="locker_no2" placeholder="Enter Locker No." readonly>
                                </div>
                            </div>

                            @endif

                            @if(!in_array('6', toggleHideField()))
                            <div class="row g-3 mt-2">
                                <div class="col-lg-6">
                                    <label for="discount_type">Discount Type</label>
                                    <select id="discount_type" class="form-select" name="discountType">
                                        <option value="">Select Discount Type</option>
                                        <option value="amount">Amount</option>
                                        <option value="percentage">Percentage</option>
                                    </select>
                                </div>
                                <div class="col-lg-6">
                                    <label for="discount_amount">Discount Amount ( <span id="typeVal2">INR / %</span> )</label>
                                    <input type="text" class="form-control @error('discount_amount') is-invalid @enderror" name="discount_amount" id="discount_amount3" value="">
                                </div>

                            </div>
                            @endif

                        </div>
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label for="">Total Amt (Plan Price + Locker Amt. - Discount)<span>*</span></label>
                                <input type="text" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount" id="new_plan_price2" value="">
                                <span id="pending_amt2" class="text-danger"></span>
                            </div>
                            <div class="col-lg-6">
                                <label for="">Choose Due Date<span>*</span></label>
                                <input type="date" class="form-control duedate" placeholder="Enter Due Date" name="due_date" id="due_date2" readonly>
                            </div>
                            <div class="col-lg-6">
                                <label for="">Payment Mode <span>*</span></label>
                                <select name="payment_mode" id="payment_mode" class="form-select">
                                    <option value="">Select Payment Mode</option>
                                    <option value="1">Online</option>
                                    <option value="2">Offline</option>
                                    <option value="3">Pay Later</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-lg-4">
                                <input type="hidden" class="form-control " name="seat_no" value="" id="update_seat_no">
                                <input type="hidden" class="form-control " name="user_id" value="" id="update_user_id">
                                <input type="submit" class="btn btn-primary btn-block button" id="submit" value="Renew Membership Now">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endcan

<div class="modal fade" id="wabaSendModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title px-2 fs-5">Send WhatsApp Reminder</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="error-message" class="alert alert-danger mb-4 mt-0" style="display:none;"></div>
                <div id="validation-error-message" class="alert alert-danger mb-4 mt-0" style="display:none;"></div>
                <form id="notification_menual">
                    @csrf
                    <div class="detailes">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="">Operation Name<span>*</span></label>
                                <select id="waba_template_select" class="form-select" name="template_id">
                                    <option value="">Select Template</option>
                                    @foreach($wabaTemplates as $t)
                                    <option value="{{ $t->id }}">
                                        {{ $t->operation_name }} - {{ $t->template_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-12">
                                <label for="">Message Content<span>*</span></label>
                                <textarea id="waba_final_message" name="message" class="form-control mt-2" rows="5"></textarea>
                            </div>
                           <div class="col-lg-12">
                                <label>Choose Mobile No. <span class="text-danger">*</span></label>
                                <select id="learner_mobile_select" class="form-select" name="mobileNo">
                                    <option value="">Select Mobile</option>
                                </select>
                            </div>

                            <input type="hidden" id="modal_learner_id" name="learner_id">

                        </div>
                        <div class="row mt-4">
                            <div class="col-lg-12">
                                <input id="sendWabaMessage" class="btn btn-primary btn-block button" value="Send WhatsApp Message" autocomplete="off">
                            </div>
                        </div>

                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<div class="modal fade" id="textSendModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
       
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title px-2 fs-5">Send Text Reminder</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                {{-- <div id="error-message" class="alert alert-danger mb-4 mt-0" style="display:none;"></div>
                <div id="validation-error-message" class="alert alert-danger mb-4 mt-0" style="display:none;"></div> --}}
                <form >
                    @csrf
                    <div class="detailes">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="">Operation Name<span>*</span></label>
                                <select id="text_template_select" class="form-select" name="template_id">
                                    <option value="">Select Template</option>
                                    @foreach($textTemplates as $t)
                                    <option value="{{ $t->id }}">
                                        {{ $t->operation_name }} - {{ $t->template_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-12">
                                <label for="">Message Content<span>*</span></label>
                                <textarea id="text_final_message" name="message" class="form-control mt-2" rows="5"></textarea>
                            </div>
                           <div class="col-lg-12">
                                <label>Choose Mobile No. <span class="text-danger">*</span></label>
                                <select id="learner_mobile_select2" class="form-select" name="mobileNo">
                                    <option value="">Select Mobile</option>
                                </select>
                            </div>

                            <input type="hidden" id="modal_learner_id2" name="learner_id">

                        </div>
                        <div class="row mt-4">
                            <div class="col-lg-12">
                                <input id="sendTextMessage" class="btn btn-primary btn-block button" value="Send Text Message" autocomplete="off">
                            </div>
                        </div>

                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

