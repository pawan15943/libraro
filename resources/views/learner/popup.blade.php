<link rel="stylesheet" href="{{ asset('public/css/booking-modal.css') }}?v={{ time() }}" />

@can('has-permission', 'Book Seat')

<div class="modal fade booking-modal-module" id="seatAllotmentModal" tabindex="-1" aria-labelledby="seat_no_head" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div id="success-message" class="alert alert-success" style="display:none;"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="seat_no_head">
                    <i class="fa-solid fa-chair"></i>
                    <span>Booking Form</span>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="seatAllotmentForm">
                @csrf
                <div class="modal-body">
                    <div id="error-message" class="alert alert-danger mb-3 mt-0" style="display:none;"></div>
                    <div id="validation-error-message" class="alert alert-danger mb-3 mt-0" style="display:none;"></div>
                    <div class="detailes">

                        <input type="hidden" class="form-control char-only" name="seat_no" value="" id="seat_no" autocomplete="off">

                        {{-- 1. CLUBBED LEARNER & SHIFT DETAILS CARD (PHOTO + BASIC INFO + SHIFT INFO) --}}
                        <div class="edit-card booking-main-card mb-3">
                            <div class="booking-caps-header no-collapse">
                                <div class="caps-title">
                                    <i class="fa-regular fa-user me-1"></i>
                                    <span>LEARNER & SHIFT DETAILS</span>
                                </div>
                                <div class="caps-right">
                                    <span class="caps-badge badge-required">Required</span>
                                </div>
                            </div>
                            <div class="edit-card-body">

                                {{-- Compact Inline Profile Avatar Row --}}
                                @if(!in_array('8', toggleHideField()))
                                <div class="booking-avatar-inline-box mb-3 pb-3 border-bottom d-flex align-items-center gap-3">
                                    <div class="upload-avatar-wrapper compact" id="bookingAvatarTrigger" title="Click to upload profile photo">
                                        <div class="upload-avatar-circle" id="bookingAvatarCircle">
                                            <i class="fa-solid fa-user avatar-icon-placeholder" id="bookingAvatarDefaultIcon"></i>
                                            <img id="bookingAvatarPreview" src="" alt="Profile Photo" style="display:none;">
                                            <div class="avatar-hover-overlay">
                                                <i class="fa-solid fa-camera"></i>
                                            </div>
                                        </div>
                                        <div class="avatar-camera-badge">
                                            <i class="fa-solid fa-camera"></i>
                                        </div>
                                    </div>
                                    <div class="avatar-inline-info">
                                        <div class="avatar-inline-title fw-semibold text-dark" id="bookingAvatarTitle">Profile Photo</div>
                                        <p class="avatar-inline-subtext text-muted mb-0" id="bookingAvatarSubtext">Click avatar to upload photo (optional).</p>
                                    </div>

                                    {{-- Hidden File Input for Cropper & Form --}}
                                    <input
                                        type="file"
                                        class="d-none image-cropper"
                                        name="profile_picture_image"
                                        id="profile_picture"
                                        autocomplete="off"
                                        accept=".jpeg, .jpg, .png, .webp" />
                                    <img class="preview-img d-none" style="display:none !important; visibility:hidden !important; position:absolute !important;" alt="Preview">
                                </div>
                                @endif

                                <div class="row g-3">
                                    {{-- Seat Concept --}}
                                    <div class="col-lg-6">
                                        <label for="general_seat" class="form-label">Assign Seat No ?</label>
                                        <select name="general_seat" id="general_seat" class="form-select">
                                            <option value="yes">No</option>
                                            <option value="no">Yes, Allot a Seat No.</option>
                                        </select>
                                    </div>
                                    {{-- Show Only Available Slots or Seat No. --}}
                                    <div class="col-lg-6">
                                        <label for="seat_id" class="form-label">Choose Seat No. <span class="required-star">*</span></label>
                                        <select class="form-select" id="seat_id" disabled>
                                            <option value="" selected>Choose Seat No.</option>
                                            @foreach($newAvailableSeats as $key => $value)
                                            <option value="{{ $value['main'] }}">{{ $value['display'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-lg-6">
                                        <label for="name" class="form-label">Full Name <span class="required-star">*</span></label>
                                        <input type="text" class="form-control" name="name" id="name" placeholder="Enter Full Name">
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="mobile" class="form-label">Mobile Number <span class="required-star">*</span></label>
                                        <input type="text" class="form-control digit-only" maxlength="10" minlength="10" name="mobile" id="mobile" placeholder="10-digit mobile number">
                                    </div>

                                    @if(!in_array('2', toggleHideField()))
                                    <div class="col-lg-6">
                                        <label for="dob" class="form-label">DOB (Optional)</label>
                                        <div class="booking-date-group">
                                            <input type="date" class="form-control" name="dob" id="dob" max="<?php echo date('Y-m-d', strtotime('-5 years')); ?>">
                                            <i class="fa-regular fa-calendar-days booking-date-icon"></i>
                                        </div>
                                    </div>
                                    @endif
                                    @if(!in_array('1', toggleHideField()))
                                    <div class="col-lg-6">
                                        <label for="email" class="form-label">Email Id (Optional)</label>
                                        <input type="text" class="form-control" name="email" id="email" placeholder="example@domain.com">
                                        <span class="text-danger small" id="email-error"></span>
                                    </div>
                                    @endif

                                    {{-- Shift Timing Divider --}}
                                    <div class="col-12 pt-2">
                                        <div class="booking-inner-divider d-flex align-items-center gap-2">
                                            <span class="booking-inner-divider-text"><i class="fa-regular fa-calendar-check me-1"></i> Plan & Shift Timing</span>
                                            <div class="booking-inner-divider-line flex-grow-1"></div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label for="plan_id3" class="form-label">Plan <span class="required-star">*</span></label>
                                        <select name="plan_id" id="plan_id3" class="form-select">
                                            <option value="">Choose</option>
                                            @foreach($plans as $key => $value)
                                            <option value="{{$value->id}}">{{$value->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-lg-4">
                                        <label for="plan_type_id" class="form-label">Plan Type / Shift <span class="required-star">*</span></label>
                                        <select id="plan_type_id" class="form-select" name="plan_type_id">
                                            <option value="">Choose</option>
                                        </select>
                                    </div>

                                    <div class="col-lg-4">
                                        <label for="plan_start_date" class="form-label">Plan Starts On <span class="required-star">*</span></label>
                                        <div class="booking-date-group">
                                            <input type="date" class="form-control datepicker" placeholder="Plan Starts On" name="plan_start_date" id="plan_start_date">
                                            <i class="fa-regular fa-calendar-days booking-date-icon"></i>
                                        </div>
                                        <span id="chargeable_days" class="text-info info-hint"></span>
                                        <span id="end_date_show" class="text-danger info-hint"></span>
                                    </div>

                                    <input type="hidden" id="plan_price_id" class="form-control" name="plan_price_id" placeholder="Example : 00 Rs" readonly>
                                </div>
                            </div>
                        </div>

                        {{-- 2. PLAN ADD-ONS (COLLAPSIBLE WITH VERY SMALL HEADING IN CAPS) --}}
                        @if(!in_array('3', toggleHideField()) || !in_array('6', toggleHideField()))
                        <div class="booking-caps-collapse-card mb-3">
                            <div class="booking-caps-header booking-collapsible-header" data-target="#bookingAddonCollapse" role="button" tabindex="0">
                                <div class="caps-title">
                                    <i class="fa-solid fa-layer-group me-1"></i>
                                    <span>ADD-ONS</span>
                                </div>
                                <div class="caps-right">
                                    <span class="caps-badge badge-optional">Optional</span>
                                    <i class="fa-solid fa-chevron-down edit-header-toggle" id="bookingAddonToggleIcon"></i>
                                </div>
                            </div>
                            <div class="booking-card-collapse" id="bookingAddonCollapse" style="display: none;">
                                <div class="booking-caps-body">
                                    <div class="row g-3">
                                        @if(!in_array('3', toggleHideField()))
                                        <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}">
                                            <label for="toggleFieldCheckbox2" class="form-label">Need a Locker ?</label>
                                            <select name="toggleFieldCheckbox" id="toggleFieldCheckbox2" class="form-select">
                                                <option value="no">No</option>
                                                <option value="yes">Yes, I Need a Locker</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer" readonly>
                                            <label for="locker_amount_book" class="form-label">Locker Amount</label>
                                            <input type="text" class="form-control digit-only" name="locker_amount" id="locker_amount_book" placeholder="Locker Amt." readonly>
                                        </div>
                                        <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                            <label for="locker_no" class="form-label">Locker No.</label>
                                            <input type="text" class="form-control digit-only" name="locker_no" id="locker_no" placeholder="Enter Locker No." readonly>
                                        </div>
                                        @endif
                                        @if(!in_array('6', toggleHideField()))
                                        <div class="col-lg-6">
                                            <label for="discountType" class="form-label">Discount Type</label>
                                            <select id="discountType" class="form-select" name="discountType">
                                                <option value="">Discount Type</option>
                                                <option value="amount">Amount</option>
                                                <option value="percentage">Percentage</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-6">
                                            <label for="discount_amount" class="form-label">Discount Amount ( <span id="typeVal">INR / %</span> )</label>
                                            <input type="text" class="form-control digit-only" name="discount_amount" id="discount_amount" placeholder="Enter Discount Amount">
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- 3. PAYMENT & REMINDER SETTINGS (WHITE BACKGROUND BOX LIKE LEARNER & SHIFT BOX) --}}
                        <div class="edit-card booking-payment-card mb-3">
                            <div class="edit-card-body">
                                <div class="row g-3">
                                    <div class="col-lg-4">
                                        <label for="paid_amount" class="form-label">Final Payable Amount (INR) <span class="required-star">*</span></label>
                                        <input id="paid_amount" class="form-control digit-only" name="paid_amount" placeholder="Example : 00 Rs">
                                        <span id="pending_amt" class="text-danger info-hint"></span>
                                    </div>

                                    <div class="col-lg-4 form-group">
                                        <label for="due_date" class="form-label">Choose Due Date <span class="required-star" id="due_date_star_booking" style="display: none;">*</span></label>
                                        <div class="booking-date-group position-relative">
                                            <input type="date" class="form-control duedate" placeholder="Enter Due Date" name="due_date" id="due_date" readonly>
                                            <i class="fa-regular fa-calendar-days booking-date-icon"></i>
                                        </div>
                                        <span class="invalid-feedback d-block booking-date-error-msg" id="due_date_error" style="display: none !important;"></span>
                                    </div>

                                    <div class="col-lg-4">
                                        <label for="payment_mode" class="form-label">Payment Mode <span class="required-star">*</span></label>
                                        <select name="payment_mode" id="payment_mode" class="form-select">
                                            <option value="">Choose</option>
                                            <option value="1">Online</option>
                                            <option value="2">Offline</option>
                                            <option value="3">Pay Later</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="sended_message_type" class="form-label">Send Reminders Via (Optional)</label>
                                        <select id="sended_message_type" class="form-select" name="sended_message_type">
                                            <option value="">Select Type</option>
                                            @if($hasFreeWaba ?? false)
                                            <option value="whatsapp">WhatsApp Message Only</option>
                                            @endif
                                            @if($hasFreeText ?? false)
                                            <option value="text">Text Message Only</option>
                                            @endif
                                            @if(($hasFreeWaba ?? false) && ($hasFreeText ?? false))
                                            <option value="both">Both (WhatsApp & Text Message)</option>
                                            @endif
                                            <option value="no">No</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="no_expiry" class="form-label">No Expiry Seat (Optional)</label>
                                        <select name="no_expiry" id="no_expiry" class="form-select">
                                            <option value="">Select Expiry Mode</option>
                                            <option value="1">Yes, Make it non expired seat.</option>
                                            <option value="0" selected>No</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 4. OTHER OPTIONAL FIELDS CARD (COLLAPSIBLE AT BOTTOM WITH VERY SMALL HEADING IN CAPS) --}}
                        @if(!in_array('7', toggleHideField()))
                        <div class="booking-caps-collapse-card mb-3">
                            <div class="booking-caps-header booking-collapsible-header" data-target="#bookingOtherOptionalCollapse" role="button" tabindex="0">
                                <div class="caps-title">
                                    <i class="fa-regular fa-id-card me-1"></i>
                                    <span>OTHER OPTIONAL FIELDS</span>
                                </div>
                                <div class="caps-right">
                                    <span class="caps-badge badge-optional">Optional</span>
                                    <i class="fa-solid fa-chevron-down edit-header-toggle" id="bookingOtherOptionalToggleIcon"></i>
                                </div>
                            </div>
                            <div class="booking-card-collapse" id="bookingOtherOptionalCollapse" style="display: none;">
                                <div class="booking-caps-body">
                                    <div class="row g-3">
                                        @if(!in_array('30', toggleHideField()))
                                        <div class="col-lg-6">
                                            <label for="alternate_mobile" class="form-label">Alternate Mobile No.</label>
                                            <input type="text" class="form-control digit-only" name="alternate_mobile" id="alternate_mobile" maxlength="10" minlength="10" placeholder="Enter Alternate Mobile No.">
                                        </div>
                                        @endif

                                        @if(!in_array('29', toggleHideField()))
                                        <div class="col-lg-6">
                                            <label for="father_name" class="form-label">Father Name</label>
                                            <input type="text" class="form-control char-only" name="father_name" id="father_name" placeholder="Enter Father name">
                                        </div>
                                        @endif

                                        @if(!in_array('4', toggleHideField()))
                                        <div class="col-lg-6">
                                            <label for="prepareFor" class="form-label">Prepare For</label>
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
                                            <label for="id_proof_name" class="form-label">ID Proof Name (Optional)</label>
                                            <select id="id_proof_name" class="form-select" name="id_proof_name">
                                                <option value="">Select Id Proof</option>
                                                <option value="1">Aadhar Card</option>
                                                <option value="2">Driving License</option>
                                                <option value="4">Pan Card</option>
                                                <option value="5">Voter Id</option>
                                                <option value="3">Other</option>
                                            </select>
                                        </div>

                                        <div class="col-lg-6">
                                            <label for="id_proof_number" class="form-label">ID Proof No.</label>
                                            <input type="text" class="form-control @error('id_proof_number') is-invalid @enderror" id="id_proof_number" name="id_proof_number" placeholder="Enter ID proof no." maxlength="12">
                                        </div>

                                        {{-- Match Edit Details Page Document Upload UI --}}
                                        <div class="col-12">
                                            <label class="form-label">Upload Scan Copy of Proof (Optional)</label>
                                            <div class="doc-dropzone" id="bookingDocDropzone" title="Click or drag file to upload">
                                                {{-- Placeholder state --}}
                                                <div class="doc-dropzone-content" id="bookingDocDropContent">
                                                    <i class="fa-solid fa-cloud-arrow-up doc-dropzone-icon"></i>
                                                    <div class="doc-dropzone-text">Drag & drop file here or <span class="browse-link">browse</span></div>
                                                    <div class="doc-dropzone-hint" id="bookingDocDropHint">Supports JPG, PNG, WEBP, PDF (Max 5 MB)</div>
                                                </div>

                                                {{-- Preview state --}}
                                                <div class="doc-dropzone-preview" id="bookingDocDropPreview" style="display:none;">
                                                    <i class="fa-solid fa-file-circle-check text-success fs-4"></i>
                                                    <div class="doc-file-info">
                                                        <span class="doc-file-name" id="bookingDocFileName">Document attached</span>
                                                        <div class="doc-file-actions-row">
                                                            <span class="doc-file-action text-danger" id="bookingRemoveDocFile" title="Change or remove file">
                                                                <i class="fa-solid fa-arrow-rotate-left"></i> Change File
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <input
                                                type="file"
                                                class="d-none id_proof_file image-cropper @error('id_proof') is-invalid @enderror"
                                                name="id_proof"
                                                id="id_proof_file_input"
                                                autocomplete="off"
                                                accept=".jpeg, .jpg, .png, .webp, .pdf">
                                            <img class="preview-img one d-none" style="display:none !important; visibility:hidden !important; position:absolute !important;" alt="Doc Preview">
                                            <span class="info-hint text-danger mt-1">* Upload front side of document (JPG, PNG, WEBP, PDF).</span>
                                        </div>
                                        @endif

                                        @if(!in_array('32', toggleHideField()))
                                        <div class="col-lg-12">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea class="form-control" name="address" id="address" rows="3" placeholder="Enter full address"></textarea>
                                        </div>
                                        @endif
                                        @if(!in_array('31', toggleHideField()))
                                        <div class="col-lg-12">
                                            <label for="remark" class="form-label">Remark</label>
                                            <textarea class="form-control" name="remark" id="remark" rows="3" placeholder="Enter Remark"></textarea>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>

                {{-- MODAL FOOTER ACTIONS (FIXED AT BOTTOM OF MODAL) --}}
                <div class="modal-footer booking-modal-footer">
                    <button type="button" class="btn btn-cancel-booking" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-book-seat" id="bookSeatSubmitBtn">
                        <i class="fa-solid fa-check-circle"></i> Book Seat Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 1. Profile Picture Avatar Upload Trigger & Live Preview
    $('#bookingAvatarTrigger').on('click', function(e) {
        e.preventDefault();
        $('#profile_picture').trigger('click');
    });

    $('#profile_picture').on('change', function() {
        const file = this.files && this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#bookingAvatarPreview').attr('src', e.target.result).show();
                $('#bookingAvatarDefaultIcon').hide();
                $('#bookingAvatarTitle').text('Photo selected');
                $('#bookingAvatarSubtext').text('Click avatar to change.');
            };
            reader.readAsDataURL(file);
        }
    });

    // Observer for Cropper image changes if cropper updates sibling preview-img
    const cropperSibling = document.querySelector('#profile_picture ~ .preview-img');
    if (cropperSibling) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
                    const src = cropperSibling.getAttribute('src') || cropperSibling.src;
                    if (src && src !== window.location.href) {
                        $('#bookingAvatarPreview').attr('src', src).show();
                        $('#bookingAvatarDefaultIcon').hide();
                        $('#bookingAvatarTitle').text('Photo uploaded');
                    }
                }
            });
        });
        observer.observe(cropperSibling, { attributes: true, attributeFilter: ['src'] });
    }

    // 2. Document Dropzone Upload & Live Preview
    $('#bookingDocDropzone').on('click', function(e) {
        if ($(e.target).closest('#bookingRemoveDocFile').length) return;
        $('#id_proof_file_input').trigger('click');
    });

    $('#id_proof_file_input').on('change', function() {
        const file = this.files && this.files[0];
        if (file) {
            $('#bookingDocFileName').text(file.name);
            $('#bookingDocDropContent').hide();
            $('#bookingDocDropPreview').css('display', 'flex');
        }
    });

    $('#bookingRemoveDocFile').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#id_proof_file_input').val('');
        $('#bookingDocDropContent').show();
        $('#bookingDocDropPreview').hide();
        $('.preview-img.one').removeAttr('src');
    });

    // Observer for ID Proof Cropper
    const docSibling = document.querySelector('#id_proof_file_input ~ .preview-img.one');
    if (docSibling) {
        const docObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
                    const src = docSibling.getAttribute('src') || docSibling.src;
                    if (src && src !== window.location.href) {
                        $('#bookingDocFileName').text('Document Attached');
                        $('#bookingDocDropContent').hide();
                        $('#bookingDocDropPreview').css('display', 'flex');
                    }
                }
            });
        });
        docObserver.observe(docSibling, { attributes: true, attributeFilter: ['src'] });
    }

    // 3. Collapsible header toggle handler
    $(document).on('click', '.booking-modal-module .booking-collapsible-header', function(e) {
        if ($(e.target).is('input, select, textarea, button, a, label')) return;
        e.preventDefault();
        e.stopPropagation();

        const $header = $(this);
        const target = $header.data('target');
        const $body = $(target);
        const $toggle = $header.find('.edit-header-toggle');

        if ($body.is(':visible')) {
            $toggle.removeClass('expanded');
            $body.stop(true, true).slideUp(200);
        } else {
            $toggle.addClass('expanded');
            $body.stop(true, true).slideDown(200);
        }
    });

    // Keyboard support for collapsible headers (Enter / Space)
    $(document).on('keydown', '.booking-modal-module .booking-collapsible-header', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    // Calendar icon click helper for date pickers
    $(document).on('click', '.booking-modal-module .booking-date-icon', function(e) {
        const input = $(this).siblings('input[type="date"]')[0];
        if (input && !input.disabled && !input.readOnly) {
            if (typeof input.showPicker === 'function') {
                try { input.showPicker(); } catch (err) { input.focus(); }
            } else {
                input.focus();
            }
        }
    });

    // Reset form, validation states, and previews on modal close
    $('#seatAllotmentModal').on('hidden.bs.modal', function() {
        // Reset raw form fields
        if ($('#seatAllotmentForm').length && $('#seatAllotmentForm')[0]) {
            $('#seatAllotmentForm')[0].reset();
        }
        $('#seatAllotmentForm .is-invalid').removeClass('is-invalid');
        $('#seatAllotmentForm .invalid-feedback').remove();
        $('#error-message, #validation-error-message').hide().text('');

        // Reset Profile Avatar
        $('#profile_picture').val('');
        $('#bookingAvatarPreview').attr('src', '').hide();
        $('#bookingAvatarDefaultIcon').show();
        $('#bookingAvatarTitle').text('Profile Photo');
        $('#bookingAvatarSubtext').text('Click avatar to upload photo (optional).');

        // Reset ID Proof dropzone
        $('#id_proof_file_input').val('');
        $('#bookingDocDropContent').show();
        $('#bookingDocDropPreview').hide();
        $('#bookingDocFileName').text('Document Attached');
        $('.preview-img.one').removeAttr('src');

        // Reset seat fields
        $('#seat_no').val('');
        $('#seat_id').val('').prop('disabled', true);
        $('#general_seat').val('yes');

        // Reset collapsible states
        $('#bookingAddonCollapse').hide();
        $('.booking-collapsible-header[data-target="#bookingAddonCollapse"] .edit-header-toggle').removeClass('expanded');
        $('#bookingOtherOptionalCollapse').hide();
        $('.booking-collapsible-header[data-target="#bookingOtherOptionalCollapse"] .edit-header-toggle').removeClass('expanded');
    });

    // Reset Renew modal on close
    $('#seatAllotmentModal3').on('hidden.bs.modal', function() {
        if ($('#upgradeForm').length && $('#upgradeForm')[0]) {
            $('#upgradeForm')[0].reset();
        }
        $('#upgradeForm .is-invalid').removeClass('is-invalid');
        $('#upgradeForm .invalid-feedback').remove();
        $('#seatAllotmentModal3 .alert').hide().text('');
        $('#renewAddonCollapse').hide();
        $('.booking-collapsible-header[data-target="#renewAddonCollapse"] .edit-header-toggle').removeClass('expanded');
    });
});
</script>
@endcan



@can('has-permission', 'Renew Seat')
<div class="modal fade booking-modal-module" id="seatAllotmentModal3" tabindex="-1" aria-labelledby="seat_number_upgrades" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="seat_number_upgrades">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    <span>Renew Plan</span>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="upgradeForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-success success-message mb-3 mt-0" style="display:none;"></div>
                    <div class="alert alert-danger error-message mb-3 mt-0" style="display:none;"></div>
                    <div class="detailes">
                        <input type="hidden" id="hidden_plan">

                        {{-- Notice Banner --}}
                        <div class="booking-notice-banner mb-3 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-info"></i>
                            <span><strong>Note:</strong> Your upcoming plan starts after your current plan expires.</span>
                        </div>

                        {{-- 1. LEARNER & PLAN DETAILS CARD --}}
                        <div class="edit-card booking-main-card mb-3">
                            <div class="booking-caps-header no-collapse">
                                <div class="caps-title">
                                    <i class="fa-regular fa-user me-1"></i>
                                    <span>LEARNER & CURRENT PLAN DETAILS</span>
                                </div>
                                <div class="caps-right">
                                    <span class="caps-badge badge-required">Required</span>
                                </div>
                            </div>
                            <div class="edit-card-body">
                                {{-- Learner Summary Mini Box --}}
                                <div class="renew-learner-summary mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div class="summary-item">
                                        <span>Learner UID</span>
                                        <strong id="learner_uid" class="text-uppercase">NA</strong>
                                    </div>
                                    <div class="summary-item">
                                        <span>Seat Owner Name</span>
                                        <strong id="learner_name" class="text-uppercase">NA</strong>
                                    </div>
                                    <div class="summary-item">
                                        <span>Mobile Number</span>
                                        <strong id="learner_mobilepop">NA</strong>
                                    </div>
                                </div>

                                {{-- Plan Info Inputs --}}
                                <div class="row g-3">
                                    <div class="col-lg-4">
                                        <label for="plan_id2" class="form-label">Select Plan <span class="required-star">*</span></label>
                                        <select id="plan_id2" class="form-control" name="plan_id" @readonly(true)></select>
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="plan_type_id_renew" class="form-label">Plan Type <span class="required-star">*</span></label>
                                        <select id="plan_type_id_renew" class="form-control" name="plan_type_id" @readonly(true)></select>
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="plan_price_id2" class="form-label">Plan Price <span class="required-star">*</span></label>
                                        <input id="plan_price_id2" class="form-control" placeholder="Plan Price" name="plan_price_id" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 2. PLAN ADD-ONS (COLLAPSIBLE WITH VERY SMALL HEADING IN CAPS) --}}
                        @if(!in_array('3', toggleHideField()) || !in_array('6', toggleHideField()))
                        <div class="booking-caps-collapse-card mb-3">
                            <div class="booking-caps-header booking-collapsible-header" data-target="#renewAddonCollapse" role="button" tabindex="0">
                                <div class="caps-title">
                                    <i class="fa-solid fa-layer-group me-1"></i>
                                    <span>ADD-ONS</span>
                                </div>
                                <div class="caps-right">
                                    <span class="caps-badge badge-optional">Optional</span>
                                    <i class="fa-solid fa-chevron-down edit-header-toggle" id="renewAddonToggleIcon"></i>
                                </div>
                            </div>
                            <div class="booking-card-collapse" id="renewAddonCollapse" style="display: none;">
                                <div class="booking-caps-body">
                                    @if(!in_array('3', toggleHideField()))
                                    <div class="row g-3">
                                        <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}">
                                            <label for="locker" class="form-label">Need a Locker ?</label>
                                            <select name="locker" id="locker" class="form-select">
                                                <option value="no">No</option>
                                                <option value="yes">Yes, I Need a Locker</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}">
                                            <label for="locker_amount2" class="form-label">Locker Amount <span class="required-star">*</span></label>
                                            <input type="text" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" id="locker_amount2" readonly>
                                        </div>
                                        <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                            <label for="locker_no2" class="form-label">Locker No.</label>
                                            <input type="text" class="form-control digit-only" name="locker_no" id="locker_no2" placeholder="Enter Locker No." readonly>
                                        </div>
                                    </div>
                                    @endif

                                    @if(!in_array('6', toggleHideField()))
                                    @if(!in_array('3', toggleHideField()))
                                    <hr class="booking-section-divider my-3">
                                    @endif
                                    <div class="row g-3">
                                        <div class="col-lg-6">
                                            <label for="discount_type" class="form-label">Discount Type</label>
                                            <select id="discount_type" class="form-select" name="discountType">
                                                <option value="">Select Discount Type</option>
                                                <option value="amount">Amount</option>
                                                <option value="percentage">Percentage</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-6">
                                            <label for="discount_amount3" class="form-label">Discount Amount ( <span id="typeVal2">INR / %</span> )</label>
                                            <input type="text" class="form-control @error('discount_amount') is-invalid @enderror" name="discount_amount" id="discount_amount3" placeholder="Enter Discount Amount" value="">
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- 3. PAYMENT & SETTINGS (WHITE BACKGROUND BOX) --}}
                        <div class="edit-card booking-payment-card mb-3">
                            <div class="edit-card-body">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label for="previous_pending" class="form-label">Previous Pending Amount <span class="required-star">*</span></label>
                                        <input type="text" class="form-control @error('previous_pending') is-invalid @enderror" name="previous_pending" id="previous_pending" readonly>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="new_plan_price2" class="form-label">Total Amt (Plan Price + Locker Amt. - Discount) <span class="required-star">*</span></label>
                                        <input type="text" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount" id="new_plan_price2" placeholder="Example : 00 Rs" value="">
                                        <span id="pending_amt2" class="text-danger info-hint"></span>
                                        <span id="chargeable_days_renew" class="text-info info-hint"></span>
                                    </div>
                                    <div class="col-lg-6 form-group">
                                        <label for="due_date2" class="form-label">Choose Due Date <span class="required-star" id="due_date_star_renew" style="display: none;">*</span></label>
                                        <div class="booking-date-group position-relative">
                                            <input type="date" class="form-control duedate" placeholder="Enter Due Date" name="due_date" id="due_date2" readonly>
                                            <i class="fa-regular fa-calendar-days booking-date-icon"></i>
                                        </div>
                                        <span class="invalid-feedback d-block booking-date-error-msg" id="due_date2_error" style="display: none !important;"></span>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="payment_mode" class="form-label">Payment Mode <span class="required-star">*</span></label>
                                        <select name="payment_mode" id="payment_mode" class="form-select">
                                            <option value="">Select Payment Mode</option>
                                            <option value="1">Online</option>
                                            <option value="2">Offline</option>
                                            <option value="3">Pay Later</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-12">
                                        <label for="no_expiry_renew" class="form-label">No Expiry Seat (Optional)</label>
                                        <select name="no_expiry" id="no_expiry_renew" class="form-select">
                                            <option value="0">No</option>
                                            <option value="1">Yes, Make it non expired seat.</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" class="form-control" name="seat_no" value="" id="update_seat_no">
                        <input type="hidden" class="form-control" name="learner_id" value="" id="update_user_id">
                    </div>
                </div>

                {{-- MODAL FOOTER ACTIONS (FIXED AT BOTTOM OF MODAL) --}}
                <div class="modal-footer booking-modal-footer">
                    <button type="button" class="btn btn-cancel-booking" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-book-seat" id="renewSeatSubmitBtn">
                        <i class="fa-solid fa-arrows-rotate"></i> Renew Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<style>
    .reminder-choice-grid { display: flex; gap: 16px; }
    .reminder-choice-btn { flex: 1; border: 1px solid #e4e7ed; border-radius: 10px; background: #fff; padding: 22px 14px; display: flex; flex-direction: column; align-items: center; gap: 8px; cursor: pointer; transition: .15s ease; text-decoration: none; color: inherit; }
    .reminder-choice-btn:hover { border-color: #07156f; box-shadow: 0 4px 14px rgba(0,0,0,.08); transform: translateY(-2px); }
    .reminder-choice-icon { width: 54px; height: 54px; border-radius: 50%; display: grid; place-items: center; font-size: 1.5rem; }
    .reminder-choice-icon.whatsapp { background: #e7f9ee; color: #25D366; }
    .reminder-choice-icon.text { background: #e8f0ff; color: #0d6efd; }
    .reminder-choice-label { font-weight: 700; color: #222; font-size: 1rem; }
    .reminder-choice-sub { color: #777; font-size: .8rem; text-align: center; }
</style>

<div class="modal fade" id="sendReminderChooserModal" tabindex="-1" aria-labelledby="sendReminderChooserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title px-2 fs-5" id="sendReminderChooserLabel">Send Reminder</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="reminder-choice-grid">
                    <button type="button" class="reminder-choice-btn open-waba chooser-action" data-bs-dismiss="modal" data-target-modal="#wabaSendModel">
                        <span class="reminder-choice-icon whatsapp"><i class="fab fa-whatsapp"></i></span>
                        <span class="reminder-choice-label">WhatsApp</span>
                        <span class="reminder-choice-sub">Send reminder via WhatsApp</span>
                    </button>
                    <button type="button" class="reminder-choice-btn open-text chooser-action" data-bs-dismiss="modal" data-target-modal="#textSendModel">
                        <span class="reminder-choice-icon text"><i class="fa-solid fa-message"></i></span>
                        <span class="reminder-choice-label">Text</span>
                        <span class="reminder-choice-sub">Send reminder via SMS text</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="sendReminderChooserFreeModal" tabindex="-1" aria-labelledby="sendReminderChooserFreeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title px-2 fs-5" id="sendReminderChooserFreeLabel">Send Reminder</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="reminder-choice-grid">
                    <a href="javascript:;" id="freeWabaChoiceLink" target="_blank" class="reminder-choice-btn" data-bs-dismiss="modal">
                        <span class="reminder-choice-icon whatsapp"><i class="fab fa-whatsapp"></i></span>
                        <span class="reminder-choice-label">WhatsApp</span>
                        <span class="reminder-choice-sub">Send reminder via WhatsApp</span>
                    </a>
                    <a href="javascript:;" id="freeTextChoiceLink" class="reminder-choice-btn" data-bs-dismiss="modal">
                        <span class="reminder-choice-icon text"><i class="fa-solid fa-message"></i></span>
                        <span class="reminder-choice-label">Text</span>
                        <span class="reminder-choice-sub">Send reminder via SMS text</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

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
                                    @if(isset($wabaTemplates) && !empty($wabaTemplates))
                                    @foreach($wabaTemplates as $t)
                                    <option value="{{ $t->id }}">
                                        {{ $t->operation_name }} - {{ $t->template_name }}
                                    </option>
                                    @endforeach
                                    @endif
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
                <form>
                    @csrf
                    <div class="detailes">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="">Operation Name<span>*</span></label>
                                <select id="text_template_select" class="form-select" name="template_id">
                                    <option value="">Select Template</option>
                                    @if(isset($textTemplates) && !empty($textTemplates))
                                    @foreach($textTemplates as $t)
                                    <option value="{{ $t->id }}">
                                        {{ $t->operation_name }} - {{ $t->template_name }}
                                    </option>
                                    @endforeach
                                    @endif
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
