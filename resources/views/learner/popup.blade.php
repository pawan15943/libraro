@can('has-permission', 'Seat Booking')
<div class="modal fade" id="seatAllotmentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div id="success-message" class="alert alert-success" style="display:none;"></div>

        <div class="modal-content">
            <div id="error-message" class="alert alert-danger" style="display:none;"></div>
            <div id="validation-error-message" class="alert alert-danger" style="display:none;"></div>
            <div class="modal-header">
                <h1 class="modal-title px-2 fs-5" id="seat_no_head"></h1>
                
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="seatAllotmentForm">
                    <div class="detailes">
                        {{-- <input type="hidden" name="seat_id" value="" id="seat_id"> --}}
                    
                        <input type="hidden" class="form-control char-only" name="seat_no" value="" id="seat_no"
                            autocomplete="off">

                        <div class="row g-4">
                              
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
                                <select name="seat_no" class="form-select" id="seat_id" disabled>
                                    <option value="" >Choose Seat No</option>
                                    @foreach($availableseats as $key => $value)
                                        <option value="{{$value}}">{{$value}}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- ================================================================== --}}
                            <div class="col-lg-6">
                                <label for="">Full Name <span>*</span></label>
                                <input type="text" class="form-control char-only" name="name" id="name">
                            </div>
                            <div class="col-lg-6">
                                <label for="">DOB </label>
                                <input type="date" class="form-control" name="dob" id="dob" max="<?php echo date('Y-m-d', strtotime('-10 years')); ?>">
                            </div>
                            <div class="col-lg-6">
                                <label for="">Mobile Number <span>*</span></label>
                                <input type="text" class="form-control digit-only" maxlength="10" minlength="10" name="mobile" id="mobile">
                            </div>
                            <div class="col-lg-6">
                                <label for="">Email Id <span>*</span></label>
                                <input type="text" class="form-control" name="email" id="email">
                            </div>

                            <div class="col-lg-4">
                                <label for="">Select Plan <span>*</span></label>
                                <select name="plan_id" id="plan_id" class="form-select" name="plan_id">
                                {{-- <select name="plan_id" id="plan_id3" class="form-select" name="plan_id"> --}}
                                    <option value="">Select Plan</option>
                                    @foreach($plans as $key => $value)
                                    <option value="{{$value->id}}">{{$value->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-4">
                                <label for="">Plan Type <span>*</span></label>
                                <select id="plan_type_id" class="form-select" name="plan_type_id">
                                    <option value="">Select Plan Type</option>
                                   
                                </select>
                            </div>

                            <div class="col-lg-4">
                                <label for="">Plan Starts On <span>*</span></label>
                                <input type="date" class="form-control" placeholder="Plan Starts On" name="plan_start_date" id="plan_start_date">
                            </div>
                            
                                {{-- <label for="">Plan Price <span>*</span></label> --}}
                                <input type="hidden" id="plan_price_id" class="form-control" name="plan_price_id" placeholder="Example : 00 Rs" readonly>
                            
                            <div class="col-lg-4">
                                
                                  
                            <label for="toggleFieldCheckbox">Need a Locker ?</label>
                            <select name="toggleFieldCheckbox" id="toggleFieldCheckbox" class="form-select">
                                <option value="no">No</option>
                                <option value="yes">Yes, I Need a Locker</option>
                            </select>
                                
                            </div>
                            <div class="col-lg-4" id="extraFieldContainer" readonly>
                                <label for="locker_amount">Locker Amount</label>
                                <input type="text" class="form-control digit-only" name="locker_amount" id="locker_amount" placeholder="Locker Amt." readonly>
                            </div>
                            <div class="col-lg-4" id="extraFieldContainer2" >
                                <label for="locker_no">Locker No.</label>
                                <input type="text" class="form-control digit-only" name="locker_no" id="locker_no" placeholder="Enter Locker No." readonly>
                            </div>
                            
                            
                            <div class="col-lg-4">
                                <label for="">Final Payble Amount (INR)<span>*</span></label>
                                <input id="paid_amount" class="form-control digit-only" name="paid_amount" placeholder="Example : 00 Rs">
                                <span id="pending_amt" class="text-danger"></span>
                            </div>

                            <div class="col-lg-4">
                                <label for="">Choose Due Date<span>*</span></label>
                                <input type="date" class="form-control" placeholder="Plan Starts On" name="due_date" id="due_date" readonly>
                            </div>
                         
                          
                            
                            <div class="col-lg-4">
                                <label for="">Payment Mode <span>*</span></label>
                                <select name="payment_mode" id="payment_mode" class="form-select">
                                    <option value="">Select Payment Mode</option>
                                    <option value="1">Online</option>
                                    <option value="2">Offline</option>
                                    <option value="3">Pay Later</option>
                                </select>
                            </div>

                        </div>
                        <h4 class="py-4 m-0">Other Important Info
                            <i id="toggleIcon" class="fa fa-plus" style="cursor: pointer;"></i>
                        </h4>

                        <div id="idProofFields" style="display: none;">
                            <div class="row g-4">
                                <div class="col-lg-6"  >
                                    <label for="discountType">Discount Type</label>
                                    <select id="discountType" class="form-select" name="discountType">
                                        <option value="">Select Discount Type</option>
                                        <option value="amount">Amount</option>
                                        <option value="percentage">Percentage</option>
                                    </select>
                                </div>
                                <div class="col-lg-6"  >
                                    <label for="discount_amount">Discount Amount ( <span id="typeVal">INR / %</span> )</label>
                                    <input type="text" class="form-control digit-only" name="discount_amount" id="discount_amount" placeholder="Enter Discount Amount">
                                </div>
                            
                                <div class="col-lg-6">
                                    <label for="">Id Proof Received </label>
                                    <select name="" id="id_proof_name" class="form-select" name="id_proof_name">
                                        <option value="">Select Id Proof</option>
                                        <option value="1">Aadhar</option>
                                        <option value="2">Driving License</option>
                                        <option value="3">Other</option>
                                    </select>
                                    <span class="text-danger">Uploading ID proof is optional do it later.</span>
                                </div>
                                <div class="col-lg-6">
                                    <label for="id_proof_file">Upload Scan Copy of Proof</label>
                                    <input type="file" class="form-control" name="id_proof_file" id="id_proof_file"
                                        autocomplete="off">

                                    <a href="javascript:;" id="viewButton" style="display: none;">
                                        <i class="fa fa-eye"></i> View Uploaded File
                                    </a>
                                    <div id="filePopup" class="file-popup" style="display: none;">
                                        <img src="" id="imagePreview" style="display: none;" alt="Selected Image">
                                        <iframe id="pdfPreview" style="display: none;" frameborder="0"></iframe>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <select name="exam_id" id="prepareFor" class="form-select">
                                        <option value="">Prepare For</option>
                                        @foreach($exams as $key => $value)
                                        <option value="{{$value->id}}">{{$value->name}}</option>   
                                        @endforeach
                                        
                                      
                                    </select>
                                </div>
                            </div>

                        </div>


                        <div class="row mt-2">
                            <div class="col-lg-4">
                                <input type="submit" class="btn btn-primary btn-block button" id="submit"
                                    value="Book Library Seat Now" autocomplete="off">
                            </div>
                        </div>

                    </div>
                </form>
            </div>

        </div>
    </div>
</div>


@endcan