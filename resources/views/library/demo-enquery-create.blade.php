@extends('layouts.library')
@section('content')
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css"
        rel="stylesheet" />
    <style>
        header,
        footer {
            display: none;
        }

        .online-qr-booking {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #efefff;
            
        }

        .online-booking{
            border: none !important;
        }

        .logo {
            width: 180px;
            padding: .5rem 0;
            margin: 0 auto;
            display: block;
            margin-bottom: 1rem;
        }

        
        /* Modal background */
        .cropper-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.24);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1 !important;
        }

        /* Modal box */
        .cropper-box {
            background: #fff;
            width: 90%;
            max-width: 400px;
            border-radius: 10px;
            padding: 20px;
            box-sizing: border-box;
            text-align: center;
        }

        /* Cropper area */


        .cropper-area {
            width: 100%;
            max-height: 300px;
            overflow: hidden;
            margin: 15px 0;
        }

        /* Buttons */
        .cropper-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .cropper-actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        #cancelCrop {
            background: #e5e7eb;
            color: #111;
        }

        .cropbtn {
            background: navy;
            color: #fff;
        }

        .cropper-modal {
            background-color: #000000a3 !important;
            opacity: .5;
        }

        .py-4 {
        padding-top: 1.5rem !important;
        padding-bottom: 1.5rem !important;
        font-size: 1rem;
        color: #00677c ! IMPORTANT;
    }

    </style>
 <!-- Cropper Modal -->
<div class="cropper-modal" id="cropperModal">
    <div class="cropper-box">
        <h5>Crop Profile Photo</h5>

        <div class="cropper-area">
            <img id="cropperImage" style="max-width:100%; display:block;">
        </div>

        <div class="cropper-actions">
            <button class="cancelcrop">Cancel</button>
            <button class="cropbtn">Crop & Save</button>
        </div>
    </div>
</div>
 

        <!-- resources/views/booking/form.blade.php -->
        
            @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
            @endif
            @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            @endif
              
        <form action="{{ route('demo-users.store') }}" method="POST" enctype="multipart/form-data">
            <div class="row justify-content-center mb-4">
                <div class="col-lg-8">
                    <div class="online-booking bg-white p-4 rounded-4">
                        
                        <h4 class="mb-4 text-center">Daily Inquiry</h4>
                        <div class="row g-3">
                            @csrf
                            @php
                                $__demo_branch_max_h = (int) (\App\Models\Hour::withoutGlobalScopes()->where('branch_id', $branch->id)->value('hour') ?? 0);
                            @endphp
                            <input type="hidden" id="branch_id" value="{{ $branch->id }}" data-max-slot-hours="{{ $__demo_branch_max_h }}">
                           

                            <div class="col-lg-6">
                                <label for="general_seat">Assign Seat No?</label>
                                <select name="general_seat" id="general_seat2" class="form-select @error('general_seat') is-invalid @enderror">
                                    <option value="yes" {{ old('general_seat') == 'yes' ? 'selected' : '' }}>
                                        No
                                    </option>
                                    <option value="no" {{ old('general_seat') == 'no' ? 'selected' : '' }}>
                                        Yes, Allot a Seat No.
                                    </option>

                                </select>
                                 @error('general_seat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-lg-6">
                                <label for="seat_id">Choose Seat No. <span>*</span></label>
                                <select name="seat_no" class="form-select @error('seat_no') is-invalid @enderror" id="seat_id2">
                                    <option value="">Choose Seat No</option>
                                 
                                    @foreach($newAvailableSeat  as $key => $value)

                                    <option value="{{ $value['main'] }}" {{ old('seat_no') == $value['main'] ? 'selected' : '' }}>{{ $value['display'] }}</option>
                                    @endforeach
                                </select>
                                @error('seat_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>Name <span>*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control char-only @error('name') is-invalid @enderror">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-lg-6">
                                <label>Mobile (WhatsApp No)<span>*</span></label>
                                <input type="text" name="mobile" value="{{ old('mobile') }}" class="form-control digit-only @error('mobile') is-invalid @enderror" maxlength="10" minlength="8">
                                @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                             @if(!in_array('2', toggleHideField()))
                            <div class="col-lg-6">
                                <label for="">DOB (optional)</label>
                                <input type="date" class="form-control dob" value="{{ old('dob') }}" name="dob" id="dob" max="<?php echo date('Y-m-d', strtotime('-10 years')); ?>">
                            </div>
                            @endif
                            @if(!in_array('1', toggleHideField()))
                            <div class="col-lg-6">
                                <label for="">Email Id (optional)</label>
                                <input type="text" class="form-control" name="email" value="{{ old('email') }}" id="email">
                                <span class="text-danger" id="email-error"></span>
                            </div>
                            @endif
                            
                           
                          
                            <div class="col-lg-6">
                                <label for="">Plan <span>*</span></label>
                                <select name="plan_id" id="plan_id4" class="form-select @error('plan_id') is-invalid @enderror" name="plan_id">
                                    <option value="">Choose</option>
                                    @foreach($plans as $key => $value)
                                    <option value="{{ $value->id }}" {{ old('plan_id') == $value->id ? 'selected' : '' }}>{{$value->name}}</option>
                                    @endforeach
                                </select>
                                @error('plan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6">
                                <label for="">Plan Type / Shift <span>*</span></label>
                                <select id="temp_plan_type_id" class="form-select choices shift-choices-multiple @error('plan_type_id') is-invalid @enderror" name="plan_type_id[]" multiple></select>
                                <small class="text-muted">Select one or more shifts.</small>
                                @error('plan_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            

                            <div class="col-lg-6">
                                <label for="">Plan Starts On <span>*</span></label>
                               <input type="date"
                                    class="form-control datepicker @error('plan_start_date') is-invalid @enderror"
                                    name="plan_start_date"
                                    id="plan_start_date"
                                    value="{{ old('plan_start_date', now()->format('Y-m-d')) }}">
                                @error('plan_start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="col-lg-6">
                                <label for="">Final Payble Amount (INR)<span>*</span></label>
                                <input id="plan_price" type="text" class="form-control digit-only @error('plan_price_id') is-invalid @enderror" name="plan_price_id" placeholder="Example : 00" value="{{ old('plan_price_id') }}" readonly>
                                @error('plan_price_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <span id="chargeable_day_book" class="text-info"></span>
                            </div>
                             @if(!in_array('8', toggleHideField()))
                                <div class="col-lg-6">
                                    <label>Upload Profile Photo (optional)</label>
                                    <input
                                        type="file"
                                        class="form-control image-cropper"
                                        name="profile_picture" id="profile_picture" autocomplete="off" accept=".jpeg, .jpg, .png, .webp" />
                                    <img class="preview-img" style="display:none; max-width:100px; margin-top:1rem;">
                                </div>
                            @endif
                            <div class="col-lg-6">
                                <label for="">Payment Mode <span>*</span></label>
                                <select name="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                    <option value="">Select Payment Mode</option>
                                    <option value="paylater" {{ old('payment_mode') == 'paylater' ? 'selected' : '' }}>Pay Later</option>

                                </select>
                                @error('payment_mode')
                                <div class="invalid-feedback" role="alert">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                        

                            @if(!in_array('7', toggleHideField()))
                                <h4 class="py-4 m-0">Other Optional Fields <i class="fa fa-plus qr_toggleIcon" style="cursor: pointer;"></i><h4>

                                <div class="qr_idProofFields" style="display: none;">
                                    <div class="row g-3">
                                       
                                    
                            
                                        {{-- ================= ID PROOF ================= --}}
                                        @if(!in_array('5', toggleHideField()))
                                        <div class="col-lg-4">
                                            <label>Id Proof Received</label>
                                            <select class="form-select" name="id_proof_name">
                                                <option value="">Select Id Proof</option>
                                                <option value="1"
                                                    {{ (old('id_proof_name') ?? '') == '1' ? 'selected' : '' }}>
                                                    Aadhar Card
                                                </option>
                                                <option value="2"
                                                    {{ (old('id_proof_name') ?? '') == '2' ? 'selected' : '' }}>
                                                    Driving License
                                                </option>
                                                <option value="4"
                                                    {{ (old('id_proof_name') ?? '') == '4' ? 'selected' : '' }}>
                                                    Pan Card
                                                </option>
                                                <option value="5"
                                                    {{ (old('id_proof_name') ?? '') == '5' ? 'selected' : '' }}>
                                                   Voter Id
                                                </option>
                                                <option value="3"
                                                    {{ (old('id_proof_name') ?? '') == '3' ? 'selected' : '' }}>
                                                    Other
                                                </option>
                                            </select>
                                            <span class="text-danger">Uploading ID proof is optional do it later.</span>
                                        </div>
                                        <div class="col-lg-4">
                                            <label for="address">ID Proof No.</label>
                                             <input type="text" class="form-control  @error('id_proof_number') is-invalid @enderror" name="id_proof_number" placeholder="Enter ID proof no." maxlength="12" value="{{ old('id_proof_number', $customer->id_proof_number ?? '') }}">

                                           
                                        </div>
                                        <div class="col-lg-4">
                                            <label for="id_proof_file">Upload Scan Copy of Proof</label>
                                            

                                            <input type="file" class="form-control id_proof_file image-cropper @error('id_proof_file') is-invalid @enderror" name="id_proof_file" autocomplete="off">
                                           
                                            <img class="preview-img one"  data-src="{{ isset($customer->id_proof_file) ? asset('storage/'.$customer->id_proof_file) : '' }}" style="display:none; max-width:250px; margin-top:1rem;">
                                            @error('id_proof_file')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                            
                                        </div>
                                         
                                        
                                        @endif
                                        
                                        {{-- ================= ADDRESS ================= --}}
                                        @if(!in_array('32', toggleHideField()))
                                        <div class="col-lg-12">
                                            <label for="address">Address</label>
                                            <textarea class="form-control"
                                                    name="address"
                                                    rows="3"
                                                    placeholder="Enter address">{{ old('address') ??  '' }}</textarea>
                                        </div>
                                        @endif

                                    </div>
                                </div>

                            @endif

                        
                        <div class="row mt-4">
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-primary button">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
             </div>
        </form>
   

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
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
    $(document).ready(function() {
        var $demoBranchCap = $('#branch_id');
        if ($demoBranchCap.length && $demoBranchCap.attr('data-max-slot-hours')) {
            window.BRANCH_MAX_SLOT_HOURS = parseInt($demoBranchCap.attr('data-max-slot-hours'), 10) || 0;
        }
        let oldPlanTypeIds = @json(old('plan_type_id', []));
        if (!Array.isArray(oldPlanTypeIds)) {
            oldPlanTypeIds = oldPlanTypeIds ? [oldPlanTypeIds] : [];
        }
        function loadPlanTypes() {
            const generalSeat = $('#general_seat2').val();
            const seatId = $('#seat_id2').val();
            const branch_id = $('#branch_id').val();
            console.log("branchwith", branch_id);
            if (generalSeat === 'yes') {
                // General seat → no seat-wise filter
                $('#seat_id2').prop('disabled', true).val('');
                getTypeSeatwise('', branch_id); // show general plan types
            } else if (generalSeat === 'no') {
                // Specific seat → enable seat selection
                $('#seat_id2').prop('disabled', false);

                if (seatId) {
                    // If seat already selected
                    getTypeSeatwise(seatId, branch_id); // show seat-wise plan types
                } else {
                    if (typeof destroyShiftChoices === 'function') {
                        destroyShiftChoices($('#temp_plan_type_id'));
                    }
                    $('#temp_plan_type_id').empty();
                    if (typeof initShiftChoices === 'function') {
                        initShiftChoices($('#temp_plan_type_id'));
                    }
                }
            } else {
                $('#seat_id2').prop('disabled', true).val('');
                if (typeof destroyShiftChoices === 'function') {
                    destroyShiftChoices($('#temp_plan_type_id'));
                }
                $('#temp_plan_type_id').empty();
                if (typeof initShiftChoices === 'function') {
                    initShiftChoices($('#temp_plan_type_id'));
                }
            }
        }
        loadPlanTypes();

        // On change of general seat
        $('#general_seat2').on('change', function() {
            loadPlanTypes();
        });

        function demoShiftIds() {
            var $pt = $('#temp_plan_type_id');
            if (typeof collectMultiSelectValues === 'function') {
                return collectMultiSelectValues($pt);
            }
            var el = $pt[0];
            if (el && el.multiple && el.selectedOptions && el.selectedOptions.length) {
                return $.map(el.selectedOptions, function (o) { return o.value; }).filter(Boolean);
            }
            var v = $pt.val();
            return $.isArray(v) ? v.filter(Boolean) : (v ? [v] : []);
        }

        $('#plan_id4, #temp_plan_type_id').on('change', function() {
            var $pt = $('#temp_plan_type_id');
            if (this.id === 'temp_plan_type_id' && typeof window.enforceShiftHoursCap === 'function') {
                window.enforceShiftHoursCap($pt);
            }
            let plan_id = $('#plan_id4').val();
            let plan_type_id = demoShiftIds();
            let branch_id = $('#branch_id').val();
            let plan_start_date = $('#plan_start_date').val();
            console.log("plan_id",plan_id);
            console.log("plan_type_id",plan_type_id);
            console.log("branch_id",branch_id);
            console.log("plan_start_date",plan_start_date);
           
            if (plan_id && plan_type_id.length && branch_id && plan_start_date) {
                $.ajax({
                    url: "{{ route('get.plan.price') }}"
                    , type: "POST"
                    , traditional: true
                    , data: {
                        _token: "{{ csrf_token() }}"
                        , plan_id: plan_id
                        , plan_type_id: plan_type_id
                        , branch_id: branch_id
                        , plan_start_date: plan_start_date
                    }
                    , success: function(response) {
                        console.log("heena price",response);
                        if (response.success) {
                            $('#plan_price').val(response.price);
                        } else {
                            $('#plan_price').val('');
                        }
                    }
                });
            }
            if(plan_id && plan_start_date){
                    $.ajax({
                    url: "{{ route('getChargeableDays') }}",
                    type: "GET",
                    data: {
                        plan_id: plan_id,
                        plan_start_date: plan_start_date,
                        branch_id:branch_id
                    },
                    success: function (res) {
                            console.log(res);
                        if (res.fixedBillingDate == 'true') {
                            $('#chargeable_day_book').text('Billed for ' + res.chargeable_days + ' Days');
                        }
                    }
                });
            }
        });

        $('#plan_start_date').on('change', function () {
            $('#plan_id4').trigger('change');
        });

        function getTypeSeatwise(seatId, branchId) {
            if (typeof destroyShiftChoices === 'function') {
                destroyShiftChoices($('#temp_plan_type_id'));
            }
            $('#temp_plan_type_id').empty();
            $.ajax({
                url: '{{ route('getPlantypeSeatwise') }}'
                , type: 'GET'
                , data: {
                    "_token": "{{ csrf_token() }}"
                    , "seatNo": seatId
                    , "branchId": branchId
                , }
                , dataType: 'json'
                , success: function(html) {
                    console.log('HEENAPLANTYPE',html);
                    if (typeof window.clearShiftHoursCapFeedback === 'function') {
                        window.clearShiftHoursCapFeedback($('#temp_plan_type_id'));
                    }
                    if (html) {
                     if (html.length === 0) {
                        $("#temp_plan_type_id").empty().append(
                            '<option value="">No added plan type</option>'
                        );
                        if (typeof initShiftChoices === 'function') {
                            initShiftChoices($('#temp_plan_type_id'));
                        }
                        setTimeout(function () {
                            $('#temp_plan_type_id').trigger('change');
                        }, 0);
                        return;
                    }

                    $("#temp_plan_type_id").empty();

                    const oldSelected = oldPlanTypeIds.map(String);
                    $.each(html, function(index, planType) {
                        const idStr = String(planType.id);
                        const sel = oldSelected.includes(idStr) ? ' selected' : '';
                        const sh = planType.slot_hours != null ? planType.slot_hours : 0;
                        $("#temp_plan_type_id").append(
                            '<option value="' + planType.id + '" data-slot-hours="' + sh + '"' + sel + '>' +
                            planType.name +
                            '</option>'
                        );
                    });

                    oldPlanTypeIds = [];
                }else {
                        $("#temp_plan_type_id").empty();
                        $("#temp_plan_type_id").append('<option value="">Select Plan Type</option>');
                    }
                    if (typeof initShiftChoices === 'function') {
                        initShiftChoices($('#temp_plan_type_id'));
                    }
                    setTimeout(function () {
                        $('#temp_plan_type_id').trigger('change');
                    }, 0);
                }
                , error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error); // Log any errors
                    if (typeof window.clearShiftHoursCapFeedback === 'function') {
                        window.clearShiftHoursCapFeedback($('#temp_plan_type_id'));
                    }
                    if (typeof initShiftChoices === 'function') {
                        initShiftChoices($('#temp_plan_type_id'));
                    }
                    setTimeout(function () {
                        $('#temp_plan_type_id').trigger('change');
                    }, 0);
                }
            });

        }

        $('#seat_id2').on('change', function() {
            const generalSeat = $('#general_seat2').val();
            if (generalSeat === 'no') {
                const seatId = $(this).val();
                const branch_id = $('#branch_id').val();
                if (seatId) {
                    getTypeSeatwise(seatId, branch_id);
                } else {
                    if (typeof destroyShiftChoices === 'function') {
                        destroyShiftChoices($('#temp_plan_type_id'));
                    }
                    $('#temp_plan_type_id').empty();
                    if (typeof initShiftChoices === 'function') {
                        initShiftChoices($('#temp_plan_type_id'));
                    }
                    setTimeout(function () {
                        $('#temp_plan_type_id').trigger('change');
                    }, 0);
                }
            }
        });

        if (typeof initShiftChoices === 'function') {
            initShiftChoices($('#temp_plan_type_id'));
        }

    });

</script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    let cropper = null;
    let activeInput = null;
    let activePreview = null;

    const modal = document.getElementById("cropperModal");
    const cropperImage = document.getElementById("cropperImage");
    const cropBtn = document.querySelector(".cropbtn");
    const cancelCrop = document.querySelector(".cancelcrop");

    // 🔥 Find preview after input (robust)
    function findPreview(input) {
        let el = input.nextElementSibling;
        while (el) {
            if (el.classList.contains("preview-img")) return el;
            el = el.nextElementSibling;
        }
        return null;
    }

    // 🔥 Dynamic crop size
    function getCropConfig(input) {
        if (input.classList.contains("id_proof_file")) {
            return { width: 350, height: 200, ratio: 350 / 200 };
        }
        return { width: 200, height: 200, ratio: 1 };
    }

    document.querySelectorAll(".image-cropper").forEach(input => {

        input.addEventListener("change", function () {

            const file = this.files[0];
            if (!file) return;

            activeInput = this;
            activePreview = findPreview(this);

            const reader = new FileReader();
            reader.onload = function () {

                cropperImage.src = reader.result;
                modal.style.display = "flex";

                cropperImage.onload = () => {
                    if (cropper) cropper.destroy();

                    const cfg = getCropConfig(activeInput);

                    cropper = new Cropper(cropperImage, {
                        aspectRatio: cfg.ratio,
                        viewMode: 1,
                        autoCropArea: 1,
                        responsive: true,
                    });
                };
            };
            reader.readAsDataURL(file);
        });
    });

    cropBtn.addEventListener("click", function () {
        if (!cropper || !activeInput) return;

        const cfg = getCropConfig(activeInput);

        const canvas = cropper.getCroppedCanvas({
            width: cfg.width,
            height: cfg.height,
            imageSmoothingQuality: "high",
        });

        canvas.toBlob(blob => {

            const uniqueName = `profile_${Date.now()}.jpg`;

            const file = new File([blob], uniqueName, {
                type: "image/jpeg",
                lastModified: Date.now(),
            });
            const dt = new DataTransfer();
            dt.items.add(file);
            activeInput.files = dt.files;

            if (activePreview) {
                activePreview.src = URL.createObjectURL(blob);
                activePreview.style.display = "block";
            }

            modal.style.display = "none";
            cropper.destroy();
            cropper = null;

        }, "image/jpeg", 0.8);
    });

    cancelCrop.addEventListener("click", function () {
        modal.style.display = "none";
        if (cropper) cropper.destroy();
        cropper = null;
        activeInput = null;
        activePreview = null;
    });

    document.querySelectorAll(".image-cropper").forEach(input => {

        const preview = input.closest('.col-lg-4').querySelector('.preview-img');

        // ✅ If edit mode (existing image)
        if (preview && preview.getAttribute('data-src')) {
            preview.src = preview.getAttribute('data-src');
            preview.style.display = 'block';
        }

    });

});
</script>


@endsection
