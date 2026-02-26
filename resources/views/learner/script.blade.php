<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // soft delete Learner 
    $(document).on('click', '.delete-customer', function () {
        var id = $(this).data('id');
        var learnerDetail = $(this).data('learnerdetail');
        var seat = $(this).data('seat');
        var paybleRefund = parseFloat($(this).data('payblerefund'));

        var url = '{{ route('learners.destroy', ':id') }}';
        url = url.replace(':id', id);

        var formId = 'deleteSeat';
        var fieldName = 'seat';
        var newValue = seat;
        var oldValue = seat;

        Swal.fire({
            title: 'Are you sure you want to delete this Record?',
           
            html: `
            <p style="margin-bottom:10px;">
                Deleting this seat will not remove it permanently. It will remain visible in the learner’s history.
            </p>
                <div class="row g-4 delete">
                    <div class="col-lg-12">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input refundType isRefund" type="checkbox" id="removeRefund" value="with_refund">
                            <label class="form-check-label" for="removeRefund">Proceed with Refund</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input refundType refundNo" type="checkbox" value="without_refund" id="refundNo">
                            <label class="form-check-label" for="refundNo">Proceed without Refund</label>
                        </div>
                    </div>
                
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Total Amt.</label>
                        <input type="text" placeholder="Refund Amount" class="form-control paybleRefund" value="${paybleRefund ?? ''}" readonly>
                    </div>
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Pay Refund Amt.</label>
                        <input type="text" placeholder="Enter Amount" class="form-control refundAmount digit-only" maxlength='4'>
                    </div>
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Pending Refund Amt.</label>
                        <input type="text" placeholder="Enter Amount" class="form-control digit-only pendingRefund" maxlength='4'>
                    </div>
                    <div class="col-lg-12 refundAmountDiv" style="display:none;">
                        <label>Remark</label>
                        <textarea class="form-control refundRemark" cols="30" rows="3"></textarea>
                    </div>
                </div>
            `,
            iconHtml: '<i class="fas fa-trash-alt fa-3x" style="color:red;font-size:40px;"></i>',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            didOpen: () => {

                let popup = Swal.getPopup();

                $(popup).find('.refundType').on('change', function () {

                    // Make checkboxes exclusive
                    if ($(this).is(':checked')) {
                        $(popup).find('.refundType').not(this).prop('checked', false);
                    }

                    // If Refund selected → show all refundAmountDiv
                    if ($(popup).find('.isRefund').is(':checked')) {
                        $(popup).find('.refundAmountDiv').css('display', 'block');
                    } else {
                        // Otherwise hide
                        $(popup).find('.refundAmountDiv').css('display', 'none');
                    }
                });
            },

            preConfirm: () => {
                const isRefund = $('.isRefund').is(':checked');
                const withoutRefundSelected = $('.refundNo').is(':checked');

                // ⭐ REQUIRED VALIDATION — at least ONE must be selected
                if (!isRefund && !withoutRefundSelected) {
                    Swal.showValidationMessage('Please select one from Refund or Without Refund');
                    return false;
                }
                let refundValue = $('.refundAmount').val();
                let refundAmount = parseFloat(refundValue);

                const remark = $('.refundRemark').val();
                const pendingRefund = parseFloat($('.pendingRefund').val()) || 0;

                
                if (isRefund && (refundValue === "" || isNaN(refundAmount) || refundAmount < 0 || refundAmount > paybleRefund)) {
                    Swal.showValidationMessage('Please enter a valid refund amount');
                    return false;
                }
                if (isRefund && refundAmount !== paybleRefund && (pendingRefund < 0 || (pendingRefund+refundAmount) > paybleRefund)) {
                    Swal.showValidationMessage('Please enter a valid pending refund amount');
                    return false;
                }

                return {
                    isRefund: isRefund,
                    paybleRefund: $('.paybleRefund').val(),
                    refundAmount: refundAmount,
                    pendingRefund: $('.pendingRefund').val(),
                    remark: remark,
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}',
                        learnerDetail: learnerDetail,
                        isRefund: result.value.isRefund ? 1 : 0,
                        paybleRefund: result.value.paybleRefund,
                        refundAmount: result.value.refundAmount,
                        pendingRefund: result.value.pendingRefund,
                        remark: result.value.remark
                    },
                    success: function (response) {
                        logFieldChange(id, formId, fieldName, oldValue, newValue, learnerDetail);
                        Swal.fire('Deleted!', 'Learner has been deleted.', 'success').then(() => {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        Swal.fire('Error!', 'An error occurred while deleting.', 'error');
                    }
                });
            }
        });
    });
    // permanent delete Learner 
    $(document).on('click', '.delete-permanent-customer', function () {
        let id = $(this).data('id');
        let learnerDetail = $(this).data('learnerdetail');
        let permanent = '1';
        var seat = $(this).data('seat');
        let url = '{{ route("learners.destroy", ":id") }}'.replace(':id', id);
         var formId = 'deleteSeat';
        var fieldName = 'seat';
        var newValue = seat;
        var oldValue = seat;
        Swal.fire({
            title: 'Are you sure you want to permanently delete this Record?',
             html: `
            <p style="margin-bottom:10px;">Proceeding will permanently remove the learner record from the system.</p>
            
            <div style="text-align:left;">
                <div class="form-check">
                    <input class="form-check-input delete-all-yes" type="checkbox" id="deleteAllYes">
                    <label class="form-check-label" for="deleteAllYes"> Delete ALL related records (YES) </label>
                </div>

                <div class="form-check">
                    <input class="form-check-input delete-all-no" type="checkbox" id="deleteAllNo">
                    <label class="form-check-label" for="deleteAllNo"> Delete ONLY this main record (NO) </label>
                </div>

                <small class="text-danger required-msg" style="display:none;">
                    Please select one option.
                </small>
            </div>
        `,
            iconHtml: '<i class="fas fa-trash-alt fa-3x" style="color:red;font-size:40px;"></i>',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            didOpen: () => {

                // Make checkboxes mutually exclusive
                $('.delete-all-yes').change(function () {
                    if ($(this).is(':checked')) {
                        $('.delete-all-no').prop('checked', false);
                    }
                });

                $('.delete-all-no').change(function () {
                    if ($(this).is(':checked')) {
                        $('.delete-all-yes').prop('checked', false);
                    }
                });
            },

            preConfirm: () => {

                let yesChecked = $('.delete-all-yes').is(':checked');
                let noChecked = $('.delete-all-no').is(':checked');

                if (!yesChecked && !noChecked) {
                    $('.required-msg').show();
                    Swal.showValidationMessage('You must choose YES or NO');
                    return false;
                }

                return {
                    deleteAll: yesChecked ? 1 : 0,
                    learnerDetail: learnerDetail
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}',
                        learnerDetail: result.value.learnerDetail,
                        permanent: permanent,
                        deleteAll: result.value.deleteAll
                    },
                    success: function (response) {
                        // Optional logging function call
                        logFieldChange(id, formId, fieldName, oldValue, newValue, learnerDetail);
                        
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Learner has been Permanent deleted successfully.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload(); // Hard refresh after success
                        });
                    },
                    error: function (xhr) {
                        Swal.fire('Error!', 'An error occurred while deleting the learner.', 'error');
                    }
                });
            }
        });
    });

    // Learner Close Plan Form
    $(document).on('click', '.link-close-plan', function() {
       var learner_id = $(this).data('id');
        var learnerDetail = $(this).data('learnerdetail');
        var paybleRefund = parseFloat($(this).data('payblerefund'));
        var url = '{{ route('learners.close') }}'; // Adjust the route as necessary
        var oldValue=this.getAttribute('data-plan_end_date');
        var formId='closeSeat';
        var fieldName='plan_end_date';
        var today = new Date();
        var year = today.getFullYear();
        var month = String(today.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
        var day = String(today.getDate()).padStart(2, '0');
        var newValue = `${year}-${month}-${day}`;
       
        Swal.fire({
            title: 'Are you sure you want to close this Seat?',
            html: `
                <div class="row g-4 delete">
                    <div class="col-lg-12">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input refundType isRefund" type="checkbox" id="refundYes">
                            <label class="form-check-label" for="refundYes">Proceed with Refund</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input refundType refundNo" type="checkbox" value="without_refund" id="refundNo">
                            <label class="form-check-label" for="refundNo">Proceed without Refund</label>
                        </div>
                    </div>
                
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Total Amt.</label>
                        <input type="text" placeholder="Refund Amount" class="form-control paybleRefund" value="${paybleRefund ?? ''}" readonly >
                    </div>
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Pay Refund Amt.</label>
                        <input type="text" placeholder="Enter Amount" class="form-control refundAmount" maxlength="4" >
                    </div>
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Pending Refund Amt.</label>
                        <input type="text" placeholder="Enter Amount" class="form-control pendingRefund" maxlength="4" >
                    </div>
                    <div class="col-lg-12 refundAmountDiv" style="display:none;">
                        <label>Remark</label>
                        <textarea class="form-control refundRemark" cols="30" rows="3" style="height:auto !important;"></textarea>
                    </div>
                </div>
            `,
            iconHtml: '<i class="fas fa-times fa-3x" style="color:red;font-size:40px;"></i>',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Close it!',
           didOpen: () => {

                let popup = Swal.getPopup();

                $(popup).find('.refundType').on('change', function () {

                    // Make checkboxes exclusive
                    if ($(this).is(':checked')) {
                        $(popup).find('.refundType').not(this).prop('checked', false);
                    }

                    // If Refund selected → show all refundAmountDiv
                    if ($(popup).find('.isRefund').is(':checked')) {
                        $(popup).find('.refundAmountDiv').css('display', 'block');
                    } else {
                        // Otherwise hide
                        $(popup).find('.refundAmountDiv').css('display', 'none');
                    }
                });
            },
            preConfirm: () => {
               const isRefund = $('.isRefund').is(':checked');
               const withoutRefundSelected = $('.refundNo').is(':checked');
                let refundValue = $('.refundAmount').val();
                let refundAmount = parseFloat(refundValue);
                const remark = $('.refundRemark').val();
                const pendingRefund = parseFloat($('.pendingRefund').val()) || 0;
              
                 // ⭐ REQUIRED VALIDATION — at least ONE must be selected
                if (!isRefund && !withoutRefundSelected) {
                    Swal.showValidationMessage('Please select one from Refund or Without Refund');
                    return false;
                }
                if (isRefund && (refundValue === "" || isNaN(refundAmount) || refundAmount < 0 || refundAmount > paybleRefund)) {
                    Swal.showValidationMessage('Please enter a valid refund amount');
                    return false;
                }
                if (isRefund && refundAmount !== paybleRefund && (pendingRefund < 0 || (pendingRefund+refundAmount) > paybleRefund)) {
                    Swal.showValidationMessage('Please enter a valid pending refund amount');
                    return false;
                }

                return {
                    isRefund: isRefund,
                    paybleRefund: $('.paybleRefund').val(),
                    refundAmount: refundAmount,
                    pendingRefund: $('.pendingRefund').val(),
                    remark: remark,
                    learner_id: learner_id,
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        learner_id: result.value.learner_id,
                        learnerDetail: learnerDetail,
                        isRefund: result.value.isRefund ? 1 : 0,
                        paybleRefund: result.value.paybleRefund,
                        refundAmount: result.value.refundAmount,
                        pendingRefund: result.value.pendingRefund,
                        remark: result.value.remark
                    },
                    success: function (response) {
                        logFieldChange(learner_id, formId, fieldName, oldValue, newValue, learnerDetail);

                         Swal.fire('Closed!', response.success, 'success').then(() => {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        Swal.fire('Error!', 'An error occurred while close.', 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.restore-customer', function (e) {
   
        e.preventDefault();
        var learnerDetail = $(this).data('learnerdetail');
        var id = $(this).data('id');
        var formId = 'restoreSeat';
        var fieldName = 'seat';
        var seat = $(this).data('seat');
        var newValue = seat;
        var oldValue = seat;
        var learnerDetailId = $(this).data('learnerdetail');
        var url = "{{ route('learners.restore') }}"; // POST route for restore

        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to restore this learner record.",
            iconHtml: '<i class="fas fa-trash-restore fa-3x" style="color:#3085d6;font-size:40px;"></i>',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, restore it!',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        learner_detail_id: learnerDetailId
                    },
                    success: function (response) {
                        if (response.success) {
                            logFieldChange(id, formId, fieldName, oldValue, newValue, learnerDetail);
                            Swal.fire({
                                title: 'Restored!',
                                text: response.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload(); // Hard refresh (you can also update row dynamically)
                            });
                        } else {
                            Swal.fire({
                                title: 'Warning!',
                                text: response.message,
                                icon: 'warning'
                            });
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Error!', 'An error occurred while restoring the learner.', 'error');
                    }
                });
            }
        });
    });

    // Get Plan Type seatwise at All Forms wherever is needed
    function getTypeSeatwise(seatId) {
        
        $('#plan_type_id').empty().append('<option value="">Choose Shift</option>');
        $.ajax({
            url: '{{ route('gettypeSeatwise') }}',
            type: 'GET',
            data: {
                "_token": "{{ csrf_token() }}",
                "seatNo": seatId,
            },
            dataType: 'json',
            success: function (html) {
                
                if (html) {
                    
                    let selectedOption = $("#plan_type_id").find("option:selected");

                    $("#plan_type_id").empty();
                    $("#plan_type_id").append('<option value="">Choose Shift</option>');

                    if (selectedOption.val() !== "") {
                        $("#plan_type_id").append('<option value="'+selectedOption.val()+'" selected>'+selectedOption.text()+'</option>');
                    }

                    $.each(html, function(index, planType) {
                        // Avoid adding the option that is already selected
                        if (planType.id != selectedOption.val()) {
                            $("#plan_type_id").append('<option value="'+planType.id+'">'+planType.name+'</option>');
                        }
                    });
                } else {
                    $("#plan_type_id").empty();
                    $("#plan_type_id").append('<option value="">Select Plan Type</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error); // Log any errors
            }
        });
        
    }


        // auto calculate amount and used at multiple places
    function autoCalculatePaidAmount() {
        var planPrice = parseFloat($('#plan_price_id').val()) || 0;
        var lockerAmount = parseFloat($('#locker_amount_book').val()) || 0;
        var discountRaw = parseFloat($('#discount_amount').val()) || 0;
        var discountType = $('#discountType').val();
        var discountAmt = parseFloat($('#discount_amount2').val()) || 0;
        
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmount = discountRaw;
        }

        if (discountType !== 'percentage' && discountType !== 'amount') {
            $('#discount_amount').val("");
        }
        
        var autoPaid = planPrice + lockerAmount - discountAmount;
        $('#paid_amount').val(autoPaid);

        var autoPaidnew = 0;
        if (planPrice) {
            autoPaidnew = planPrice;

            if (lockerAmount) {
                autoPaidnew += lockerAmount;
            }

            if (discountAmt) {
                autoPaidnew -= discountAmt;
            } else if (discountAmount) {
                autoPaidnew -= discountAmount;
            }
        }

        
        // console.log('autoPaidnew',autoPaidnew);
        $('#new_plan_price').val(autoPaidnew);
    
        calculatePendingAmount();
    }

        // Calculate Pending Amount on BOOKING FORM
    function calculatePendingAmount() {
        const planPrice = parseFloat($('#plan_price_id').val()) || 0;
        const paidAmount = parseFloat($('#paid_amount').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount_book').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount').val()) || 0;
        const discountType = $('#discountType').val();
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else {
            discountAmount = discountRaw;
        }

        const effectivePaid = planPrice+lockerAmount - discountAmount;
        const pendingAmount = effectivePaid-paidAmount;
    
        

        if(pendingAmount > 0){
            $('#pending_amt').html('Pending Amount: ' + pendingAmount);
        }else if (pendingAmount < 0) {
            $('#pending_amt').html('High price not allowed.' + pendingAmount);
        }else{
            $('#pending_amt').html('');
        }

    

        if (pendingAmount > 0) {
            $('#due_date').removeAttr('readonly');
        } else {
            $('#due_date').attr('readonly', true);
        }
    }

        // Get Plan Price at All Forms wherever is needed [booking form,]
    function getPlanPrice(plan_type_id, plan_id, start_date = null) {
        if (!plan_type_id || !plan_id) return;

        // ✅ Auto-detect start date if not passed
        if (!start_date) {
            if ($('#plan_start_date').length && $('#plan_start_date').val()) {
                start_date = $('#plan_start_date').val();
            }
        }

        let data = {
            "_token": "{{ csrf_token() }}",
            "plan_type_id": plan_type_id,
            "plan_id": plan_id
        };

        // ✅ Send only when available
        if (start_date) {
            data.plan_start_date = start_date;
        }

       
        if (plan_type_id && plan_id) {
                $.ajax({
                    url: '{{ route('getPricePlanwise') }}',
                    type: 'GET',
                    data: data,
                    dataType: 'json',
                    success: function(html) {
                        console.log("sfpriev",html);
                        if (html && html !== undefined) {
                                $('#pending_amt3').html('');
                            if ($("#plan_price_id").length) {
                               
                                $("#plan_price_id").val(html);
                                autoCalculatePaidAmount();
                                $("#error-message").hide();
                            }
                            $("#error-message").hide();
                        } else {
                            $("#plan_price_id").val("");
                            
                            $("#pending_amt").html("No Plan Price Added Yet.");
                            $("#paid_amount").val("");
                        }
                    }

                });
        } else {
            $("#plan_price_id").empty();
            
            $("#paid_amount").empty();
        
        }
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { day: '2-digit', month: 'short', year: 'numeric' };
        return date.toLocaleDateString('en-GB', options);
    }
    function addChargeableDays(plan_id,plan_start_date) {
        $.ajax({
            url: "{{ route('getChargeableDays') }}",
            type: "GET",
            data: {
                plan_id: plan_id,
                plan_start_date: plan_start_date
            },
            success: function (res) {
                    
                if (res.fixedBillingDate == 'true') {

                     $('#chargeable_days').text('Billed for ' + res.chargeable_days + ' Days');
                    $('#chargeable_days10').text('Billed for ' + res.chargeable_days + ' Days');

                    // if(res.chargeable_days < 31){
                    //     $('#chargeable_days').text('Billed for ' + res.chargeable_days + ' Days');
                    //     $('#chargeable_days10').text('Billed for ' + res.chargeable_days + ' Days');
                    // }else{
                    //     $('#chargeable_days').text('Billed Monthly');
                    // }
 
                }
                if(res.fixedBillingDate == 'false'){
                     $('#plan_end_date_edit').val(res.end_date);
                    
                }
            }
        });
    }

    // change plan and upgrade
    function getPlanPriceAmount(plan_type_id10,plan_id10,plan_start_date10){
        
        if (plan_type_id10 && plan_id10) {
                $.ajax({
                    url: '{{ route('getPricePlanwise') }}',
                    type: 'GET',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "plan_type_id": plan_type_id10,
                        "plan_id": plan_id10,
                        "plan_start_date": plan_start_date10,
                    },
                    dataType: 'json',
                    success: function(html) {
                    
                        if (html && html !== undefined) {
                            
                            $('#pending_amt10').html('');
                            $("#plan_price10").val(html);
                            calculatePaidAmount(); 
                            $("#error-message").hide();
                        } else {
                            $("#plan_price10").val("");
                            
                            $("#pending_amt10").html("No Plan Price Added Yet.");
                            $("#total_amount10").val("");
                        }
                    }

                });
        } else {
            $("#plan_price10").empty();
            
            $("#total_amount10").empty();
        
        }
    }

    function lockerAmountGet(plan_id10){
        $.get("{{ route('locker.price') }}", { plan_id: plan_id10 })
        .done(function(json) {
            $('#locker_amount10').val(json.price);
            calculatePaidAmount();
        })
        .fail(function() {
            $('#locker_amount10').val('').prop('readonly', true);
            calculatePaidAmount();
        });
    }

    function calculatePaidAmount() {
        const planPrice = parseFloat($('#plan_price10').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount10').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount10').val()) || 0;
        const discountType = $('#discountType10').val();
        console.log('planPrice',planPrice);
        console.log('lockerAmount',lockerAmount);
        console.log('discountRaw',discountRaw);
        console.log('discountType',discountType);
    
        
        var discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmount = discountRaw;
        }

        if (discountType !== 'percentage' && discountType !== 'amount') {
            $('#discount_amount10').val("");
        }
            
        const autoPaid = planPrice + lockerAmount - discountAmount;
 
        $('#total_amount10').val(autoPaid ?? 0);

        // -------- Different Logic for CHANGE PLAN vs RENEW/UPGRADE ----------
        const paymentType = $('input[name="payment_type"]').val(); // hidden field already in form

        if (paymentType === 'CHANGE PLAN' || paymentType === 'EDIT') {
            const previous_amount = parseFloat($('#previous_amount10').val()) || 0;
            const difference = autoPaid - previous_amount;
            $('#diffrence_amount10').val(difference);

            if (difference < 0) {
                $('label[for="diffrence_amount10"]').text("Refund Amount *");
            } else {
                $('label[for="diffrence_amount10"]').text("Difference Amount *");
            }
        } else {
            // For RENEW / UPGRADE -> always fresh total, no difference calc
            $('#diffrence_amount10').val('');
        }
    }
    function calculatePending(paid_val) {
        const planPrice = parseFloat($('#plan_price10').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount10').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount10').val()) || 0;
        const discountType = $('#discountType10').val();
        const previous_amount10 = parseFloat($('#previous_amount10').val()) || 0;


        discountAmount =0;
        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmount = discountRaw;
        }

        const effectivePaid = planPrice+lockerAmount - discountAmount;

        
        let pendingAmount;
        const paymentType = $('input[name="payment_type"]').val();
        if (paymentType === 'CHANGE PLAN') {
            pendingAmount = effectivePaid - paid_val - previous_amount10;
        } else {
            pendingAmount = effectivePaid - paid_val;
        }
    
        $('#pending_amt10').val(pendingAmount);
    
        if ((paid_val > effectivePaid)) {
            $('#pending_amt_error').html('High price not allowed.' + pendingAmount);
            $('#due_date10').attr('readonly', true);
        }else{
            $('#pending_amt_error').html('');
        }
        if(pendingAmount != 0){
            $('#due_date10').attr('readonly', false);
        }
        else{
            
            $('#due_date10').attr('readonly', true);
        }
        if (pendingAmount < 0) {
        $('#pending_amt10').prev('label').text("Pending Refund Amount *");
        
        } else {
            $('#pending_amt10').prev('label').text("Pending Amount *");
        
        }


    }
    // Show Form Errors
    function showFormErrors(errors) {
        $(".is-invalid").removeClass("is-invalid");
        $(".invalid-feedback").remove();

        $.each(errors, function(key, value) {
            const field = $("[name='" + key + "']");
            field.addClass("is-invalid");
            field.after('<div class="invalid-feedback">' + value[0] + '</div>');
        });
    }
   
    $(document).ready(function() {
        const toggleHiddenFields = @json(toggleHideField());
         // Swap Seat Check Seat Booking Status On Swap Seat Page
        $('#new_seat_id').on('change', function(event) {
            event.preventDefault();
            var new_seat_id = $(this).val();
            var user_id = $('#user_id').val();
            var plan_type_id = $('#swap_plan_type_id').val();
            $('#swap_status').html('');
            
            if (new_seat_id && user_id) {
                $.ajax({
                    url: '{{ route('getSeatStatus') }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    type: 'GET',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "new_seat_id": new_seat_id,
                        "user_id": user_id,
                        "plan_type_id": plan_type_id
                    },
                    dataType: 'json',
                    success: function(html) {
                        if(html == 1) {
                            $('#swap_status').html('<h4 style="color:green !important;">Seat is Available to swap.</h4>');
                            $("#swapsubmit").prop('disabled', false); 
                        }else if(html == 2) {
                            $('#swap_status').html('<h4 style="color:red !important;">Seat is already booked for future, currently not available to swap.</h4>');
                            $("#swapsubmit").prop('disabled', true); 
                        }else {
                            $('#swap_status').html('<h4 style="color:red !important;">Not Available</h4>');
                            $("#swapsubmit").prop('disabled', true); 
                        }
                    }
                });
            }
        });
       
         // Set a Default Payment Date in Dob Field in Booking form
        document.addEventListener("DOMContentLoaded", function() {
            var paidDateInput = document.getElementById('paid_date');
            if (paidDateInput && !paidDateInput.value) { 
                var today = new Date().toISOString().split('T')[0]; 
                paidDateInput.value = today;
            }
        });

         // Set Default Date in DOB

        if (!$('#dob').val()) {
            $('#dob').val('2010-01-01');
        }

         // Set Default Date in DOB end
        var today = new Date();
        var formattedDate = today.toISOString().split('T')[0]; // Format as YYYY-MM-DD
        $('#plan_start_date').val(formattedDate); 


        // For Booking Popup form

          // In Booking form manage Genral or Normal Seat 
        $('.noseat_popup, .first_popup').on('click', function (e) {
            var currentBranch = @json(getCurrentBranch());
          
            if (!currentBranch || currentBranch == 0) {
                alert("Please select a branch first.");
                return false; 
            }
            
            var seatId = $(this).data('id');
            var seatNo = $(this).data('seat_no');
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
               
  
            if (seatNo || seatId) {
                var seatDisplay = seatDisplayMap[seatNo] ?? seatNo;
                $('#seat_no').val(seatNo);
                $('#seat_id').val(seatId);
                $('#seat_no_head').text('Book Seat No.: ' + seatDisplay);
                $('#general_seat').val('no').trigger('change');
                // Hide the seat select fields (visually only)
                $('#seat_id').closest('.col-lg-6').hide();
                $('#general_seat').closest('.col-lg-6').hide();

            } else if (toggleHiddenFields.includes('12')) {

                $('#seat_no_head').text('Booking Form');
                $('#general_seat').val('no').trigger('change');

            } else {

                $('#seat_no_head').text('Booking Form');
                @can('has-permission', 'General Seat Booking')
                    $('#general_seat').val('yes').trigger('change');
                @else
                    // User does NOT have permission → force NO and hide YES option
                    $('#general_seat').val('no').trigger('change');

                    // Hide the "yes" option from the dropdown
                    $('#general_seat option[value="yes"]').hide();
                @endcan

                // Show seat fields
                $('#seat_id').closest('.col-lg-6').show();
                $('#general_seat').closest('.col-lg-6').show();
            }


            
            $('#seatAllotmentModal').modal('show');
            if ($('#general_seat').val() === 'yes') {
                // getTypeSeatwise(''); 
                $('#general_seat').val('yes');
            } else if (seatId) {
                getTypeSeatwise(seatId); 
            }         
        });
       
        // Enable / Disable Seat No Field on Booking Form
        $('#general_seat').on('change', function () {
            
            if ($(this).val() === 'no') {
                $('#seat_id').prop('disabled', false);
            } else {
                $('#seat_id').val($('#seat_id option:first').val()); 
                $('#seat_id').prop('disabled', true);
                getTypeSeatwise('');
            }
        });


        // OnChange of Seat No Dropdown get PlanType in Booking Form
        $('#seat_id').on('change', function () {
            let newSeatId = $(this).val();
            getTypeSeatwise(newSeatId);
            $('#paid_amount').val("");
        });

         // Manage Locaker in Booking Form
        $('#toggleFieldCheckbox2, #plan_id3').on('change', function () {
           
            var needLocker = $('#toggleFieldCheckbox2').val();
            var planId     = $('#plan_id3').val();

            if (needLocker === 'yes') {
                $('#locker_no').removeAttr('readonly');
                $.get("{{ route('locker.price') }}", { plan_id: planId })
                .done(function(json) {
                    console.log('lockeamount',json.price);
                    $('#locker_amount_book').val(json.price);
                    // ✅ call here AFTER value is set
                    autoCalculatePaidAmount(); 
                })
                .fail(function() {
                    $('#locker_amount_book').val('').prop('readonly', true);
                    autoCalculatePaidAmount(); 
                });

               
            } else {
                $('#locker_amount_book').attr('readonly', true);
                $('#locker_no').attr('readonly', true);
                $('#discount_amount').val('');
                $('#locker_amount_book').val('');
                $('#locker_no').val('');
                // ✅ call here when locker is disabled
                autoCalculatePaidAmount(); 
            }
        });

         // Oncahnge of Plantype get Plan Price and use at each form wherever is needed 
        $('#plan_type_id').on('change', function(event) {
          
            var plan_type_id = $(this).val();
            var plan_id = $('#plan_id').val();
            var change_plan_plan_id = $('#change_plan_plan_id').val();
            var plan_id2 = $('#plan_id2').val();
            var plan_id3 = $('#plan_id3').val();
            var plan_id4 = $('#plan_id4').val();
           
          
            if((plan_type_id && plan_id4)||(plan_type_id && plan_id)||(plan_type_id && plan_id2)||(plan_type_id && plan_id3)||(plan_type_id && change_plan_plan_id)){
             
                getPlanPrice(plan_type_id,plan_id);
                getPlanPrice(plan_type_id,plan_id2);
                getPlanPrice(plan_type_id,plan_id3);
                getPlanPrice(plan_type_id,plan_id4);
                getPlanPrice(plan_type_id,change_plan_plan_id);
            }else{
                $("#plan_price_id").val('');
            }
           
        });
        $('#plan_start_date').on('change', function(event) {
          var plan_start_date = $(this).val();
          var plan_id = $('#plan_id3').val();
          var plan_type_id = $('#plan_type_id').val();
          getPlanPrice(plan_type_id,plan_id,plan_start_date);
          addChargeableDays(plan_id,plan_start_date);
            
        });

        // If user manually updates paid_amount, update pending as well [booking form]
        $('#paid_amount').on('input', calculatePendingAmount);

        $('#discountType').on('change', function () {
            const type = $(this).val();
            if (type === 'percentage') {
                $('#typeVal').text('%');
            } else if (type === 'amount') {
                $('#typeVal').text('INR');
            } else {
                $('#typeVal').text('INR / %');
            }
            autoCalculatePaidAmount(); // Recalculate if type changes
        });

        // Used in various Booking form
        $('#discount_amount').on('input', function () {
            autoCalculatePaidAmount(); // Recalculate if amount changes
        });

        // Select Discount Type in Booking Form nad enable/disable amout field

        function toggleDiscountAmount() {
            if ($('#discountType').val()) {
                $('#discount_amount').prop('disabled', false);
            } else {
                $('#discount_amount').prop('disabled', true).val('');
            }
        }

        function toggleIdProofFile() {
            if ($('#id_proof_name').val()) {
                $('#id_proof_file').prop('disabled', false);
            } else {
                $('#id_proof_file').prop('disabled', true).val('');
            }
        }

        // Bind events
        $('#discountType').on('change', toggleDiscountAmount);
        $('#id_proof_name').on('change', toggleIdProofFile);

        // Initial state check
        toggleDiscountAmount();
        toggleIdProofFile();
  
    });
  
     // Book Learner Seat Form 
    $(document).on('submit', '#seatAllotmentForm', function(event) {
        event.preventDefault();
        var formData = new FormData(this);
        var seat_no = $('#seat_no').val();
        var seat_id = $('#seat_id').val();
        var name = $('#name').val();
        var mobile = $('#mobile').val();
        var email = $('#email').val();
        var dob = $('#dob').val();
        var plan_id = $('#plan_id3').val();
        var plan_type_id = $('#plan_type_id').val();
        var plan_start_date = $('#plan_start_date').val();
        var id_proof_name = $('#id_proof_name').val();
        var payment_mode = $('#payment_mode').val();
        var id_proof_file = $('#id_proof_file').length ? $('#id_proof_file')[0].files[0] : null;
        var plan_price_value = parseFloat($('#plan_price_id').val()) || 0;
        var paid_amount = parseFloat($('#paid_amount').val()) || 0;
        var locker_amount = parseFloat($('#locker_amount_book').val()) || 0;
        var due_date = $('#due_date').val();
        var locker_no = $('#locker_no').val();
        var sended_message_type = $('#sended_message_type').val();
        var errors = {};
        var discountRaw = parseFloat($('#discount_amount').val()) || 0;
        var discountType = $('#discountType').val();
        var discount_amount = 0; 

        if (discountType === 'percentage') {
            discount_amount = ((plan_price_value + locker_amount) * discountRaw) / 100; // This assigns to `discount_amount`, which is NOT defined above
        } else if(discountType === 'amount'){
            discount_amount = discountRaw;
        }


        if (!name) {
            errors.name = 'Full Name is required.';
        }

        if (!mobile) {
            errors.mobile = 'Mobile number is required.';
        } else if (!/^\d{10}$/.test(mobile)) {
            errors.mobile = 'Mobile number must be exactly 10 digits.';
        }

        if (email) {
            if (!/^[\w.-]+@([\w-]+\.)+[\w-]{2,4}$/.test(email)) {
                errors.email = 'Please enter a valid email address.';
            }
        }

        if (!plan_id) {
            errors.plan_id = 'Plan is required.';
        }

        if (!plan_type_id) {
            errors.plan_type_id = 'Plan Type is required.';
        }

        if (!plan_start_date) {
            errors.plan_start_date = 'Plan Start Date is required.';
        }

        if (!payment_mode) {
            errors.payment_mode = 'Payment Mode is required.';
        }

        if (!paid_amount) {
            errors.paid_amount = 'Paid amount is required.';
        }

            
        if(paid_amount > (plan_price_value +locker_amount- discount_amount)){
            errors.paid_amount = 'Paid amount should not be greater than the total amount.';
        }
        
        if(!due_date && (paid_amount != (plan_price_value +locker_amount- discount_amount))){
            errors.due_date ='Due Date is required.';
        }
        
        // Remove previous errors
        $(".is-invalid").removeClass("is-invalid");
        $(".invalid-feedback").remove();
        
        // Show new errors
        if (Object.keys(errors).length > 0) {
            $.each(errors, function(key, value) {
                var inputField = $("#" + key);
                inputField.addClass("is-invalid");
                inputField.after('<div class="invalid-feedback">' + value + '</div>');
            });
            return;
        }
        // console.log('formData');
        var general_seat = $('#general_seat').val();
        const toggleVal = $('#toggleFieldCheckbox3').val();
        if (toggleVal !== undefined) {
            formData.append('toggleFieldCheckbox', toggleVal);
        }

        $.ajax({
            url: '{{ route('learners.store') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Form submission successful',
                        icon: 'success',
                        timer: 2000,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload(true); // Force reload from the server
                    });
                } else if (response.errors) {
                    $(".is-invalid").removeClass("is-invalid");
                    $(".invalid-feedback").remove();
                    $("#error-message").hide();
                    $.each(response.errors, function(key, value) {
                        var inputField = $("input[name='" + key + "'], select[name='" + key + "']");
                        inputField.addClass("is-invalid");
                        inputField.after('<div class="invalid-feedback">' + value[0] + '</div>');
                    });
                }else if (response.error) {
                    $("#error-message").text(response.message).show();
                    $("#success-message").hide();
                } else {
                    $("#error-message").text(response.message).show();
                    $("#success-message").hide();
                }
            },
            error: function(xhr, status, error) {
    
                if (xhr.status === 422) {
                    var response = xhr.responseJSON;
                    
                    if (response.error) {
                        $("#error-message").text(response.message).show();
                        $("#success-message").hide();
                    }else if (response.errors.email){
                        $('#email-error').text(errors.email[0]);
                    } else if (response.errors) {
                        $(".is-invalid").removeClass("is-invalid");
                        $(".invalid-feedback").remove();
                        $("#error-message").hide();

                        $.each(response.errors, function(key, value) {
                            var inputField = $("input[name='" + key + "'], select[name='" + key + "']");
                            inputField.addClass("is-invalid");
                            inputField.after('<div class="invalid-feedback">' + value[0] + '</div>');
                        });
                    }
                } else {
                    $("#error-message").text('Something went wrong. Please try again.').show();
                    $("#success-message").hide();
                }
            }
        });
    });


    // change plan and plan type(upgrade) and reactive and Edit

    $(document).ready(function() {
    
        const plan_id10 = $('#plan_id10').val();
        const plan_type_id10 = $('#plan_type_id10').val();
        var plan_start_date10=$('#start_date10').val();
        var payment_type_operation=$('#payment_type_operation').val();
        if(payment_type_operation =='REACTIVE' || payment_type_operation =='UPGRADE'){
            getPlanPriceAmount(plan_type_id10,plan_id10,plan_start_date10);
            // calculatePaidAmount();
        }
        
        if(payment_type_operation =='CHANGE PLAN' || payment_type_operation =='REACTIVE' || payment_type_operation =='EDIT' || payment_type_operation =='UPGRADE'){
            addChargeableDays(plan_id10,plan_start_date10);
        }
        
        var lockerCheck= $('#toggleFieldCheckbox10').val();
        
    
        if(lockerCheck== 'yes'){
            $('#locker_no10').attr('readonly', false);
        
        }

        if($('#discountType10').val() == 'percentage' || $('#discountType10').val() == 'amount'){
            $('#discount_amount10').attr('readonly', false);
        }else{
            $('#discount_amount10').attr('readonly', true);
            
        }


   
    });

    // start new according change plan and plan type(upgrade) and reactive and edit
    // on plan change-total change,price change, locker amount change
    // on plan type change-total change,price change
    // on locker yes -total change, locker amount ,locker no
    // on discount type change -total change, red text change
    // on total input change-pending get, due date on, 

    // diffrence amount - hidden , change plan show
    // diffrence amount on change-change plan


    $('#plan_id10').on('change', function(event) {
        event.preventDefault();
        const plan_id10 = $(this).val();
        const plan_type_id10 = $('#plan_type_id10').val();
        var lockerCheck= $('#toggleFieldCheckbox10').val();
        var plan_start_date10=$('#start_date10').val();
        console.log('plan_id10',plan_id10);
        console.log('plan_type_id10',plan_type_id10);
        console.log('plan_start_date10',plan_start_date10);
        if(plan_type_id10 && plan_id10){
            getPlanPriceAmount(plan_type_id10,plan_id10,plan_start_date10);
            calculatePaidAmount();
            if(lockerCheck== 'yes'){
                lockerAmountGet(plan_id10);
            }
            
        }else{
            $("#plan_price10").val('');
        }
        addChargeableDays(plan_id10,plan_start_date10);
    });
    $('#plan_type_id10').on('change', function(event) {
        
        event.preventDefault();
    
        const plan_type_id10 = $(this).val();
        const plan_id10 = $('#plan_id10').val();
        var lockerCheck= $('#toggleFieldCheckbox10').val();
        var plan_start_date10=$('#start_date10').val();
        if(plan_type_id10 && plan_id10){
            getPlanPriceAmount(plan_type_id10,plan_id10,plan_start_date10);
            calculatePaidAmount();
        if(lockerCheck== 'yes'){
                lockerAmountGet(plan_id10);
            }
        }else{
            $("#plan_price10").val('');
        }
        addChargeableDays(plan_id10,plan_start_date10);
    });
    $('#toggleFieldCheckbox10').on('change', function () {
        
        var needLocker = $(this).val();
        const plan_id10 = $('#plan_id10').val();

        if (needLocker === 'yes') {
            $('#locker_no10').removeAttr('readonly');
            lockerAmountGet(plan_id10)
            
        } else {
            $('#locker_amount10').attr('readonly', true);
            $('#locker_no10').attr('readonly', true);
            $('#locker_amount10').val(0);
            
            
        }
        calculatePaidAmount();
        $('#pending_amt10').val("");
    });
    $('#discountType10').on('change', function (){
        const type = $(this).val();
        if (type === 'percentage') {
            $('#typeVal10').text('%');
            $('#discount_amount10').attr('readonly', false);
        } else if (type === 'amount') {
            $('#typeVal10').text('INR');
            $('#discount_amount10').attr('readonly', false);
        } else {
            $('#typeVal10').text('INR / %');
            $('#discount_amount10').attr('readonly', true);
        }
        calculatePaidAmount(); 
        $('#pending_amt10').val("");
        
    });
    $('#discount_amount10').on('input', function () {
        calculatePaidAmount(); 
    });
    $('#total_amount10').on('input', function () {
        calculatePending($(this).val());   
    });

    $('#diffrence_amount10').on('input', function () {
        calculatePending($(this).val());  
    });

    $('#start_date10').on('change', function(event) {
        var plan_start_date10 = $(this).val();
        var plan_id10 = $('#plan_id10').val();
        var plan_type_id10 = $('#plan_type_id10').val();
         var lockerCheck= $('#toggleFieldCheckbox10').val();

         if(plan_type_id10 && plan_id10){
            getPlanPriceAmount(plan_type_id10,plan_id10,plan_start_date10);
            calculatePaidAmount();
            if(lockerCheck== 'yes'){
                lockerAmountGet(plan_id10);
            }
            
        }else{
            $("#plan_price10").val('');
        }
        addChargeableDays(plan_id10,plan_start_date10);
        
    });

    // end

    // Get Plan Type at All Forms wherever is needed { renew dashboard}
    function fetchPlanTypesRenew(seat_no, user_id,learner_detail_id) {
        
        if ((seat_no && user_id) || learner_detail_id) {
            $.ajax({
                url: '{{ route('gettypePlanwise') }}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "seat_no": seat_no,
                    "user_id": user_id,
                    "learner_detail_id": learner_detail_id,
                },
                dataType: 'json',
                success: function (html) {
                   
                    $("#plan_type_id_renew").empty(); 
                    $("#plan_id2").empty(); 

                    if (html[0]) {
                        $.each(html[0], function (key, value) {
                            $("#plan_type_id_renew").append('<option value="' + key + '">' + value + '</option>');
                        });
                    } else {
                        $("#plan_type_id_renew").append('<option value="">Choose</option>');
                    }
                    

                    if (html[1]) {
                            $.each(html[1], function (key, value) {
                            $("#plan_id2").append('<option value="' + key + '">' + value + '</option>');
                        });
                    }

                    if (html[5]){
                        $("#plan_price_id2").val(html[5]);      
                    }
                    if (html[6] && html[6].fixedBillingDate==true) {
                        
                        $("#chargeable_days_renew").text('Billed for ' + html[6].chargeable_days + ' Days');  
                    }

                    if(html[3]){
                        $("#locker_amount2").val(html[3].locker_amount);  
                        $("#discount_amount3").val(html[3].discount_amount);  
                        $("#new_plan_price").val(html[3].discount_amount);  

                        if (html[3].locker_amount && parseFloat(html[3].locker_amount) > 0) {
                            $("#locker").val('yes');
                            $("#locker_amount2").val(html[3].locker_amount);
                            
                        } else {
                            $("#locker").val('no');
                            $("#locker_amount2").val('');
                            
                        }

                        if (html[3].discount_amount && parseFloat(html[3].discount_amount) > 0) {
                            $("#discount_type").val('amount');
                            $("#discount_amount3").val(html[3].discount_amount);
                        } else {
                            $("#discount_type").val('');
                            $("#discount_amount3").val('');
                        }
                    }
                    if (html[4]){
                        $("#locker_no2").val(html[4].locker_no);
                        if(html[4].locker_no){
                        $("#locker_no2").removeAttr('readonly');
                        }      
                    }
                    
                    popupautoCalculatePaidAmount(); 
                },
                error: function (xhr, status, error) {
                    console.error("AJAX error:", status, error); // Log any errors
                }
            });
        } else {
            $("#plan_type_id_renew").empty();
            $("#plan_type_id_renew").append('<option value="">Choose Shift</option>');
        }
    }
    // Used in View Details Popup on Seat Assignment Page
    $('.second_popup').on('click', function() {
        $('#upgrade').hide();
        var userId = $(this).data('userid');
        var seatId = $(this).data('id');
        var seatNo=$(this).data('seat_no');
        $('#user_id').val(userId);
        $('#seatAllotmentModal2').modal('show');
        
        if (userId) {
            $.ajax({
                url: '{{ route('learners.show')}}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": userId,
                },
                dataType: 'json',
                success: function(html) {
                    $('#learner_detail_id').val(html.learner_detail_id);
                    $('#owner').text(html.name);
                    $('#learner_dob').text(html.dob);

                    if(html.email){
                        $('#learner_email').text(html.email);
                    }
                    
                    $('#learner_mobile').text(html.mobile);

                    if (html.id_proof_name == 1) {
                        var proof = 'Aadhar';
                    } else if (html.id_proof_name == 2) {
                        var proof = 'Driving License';
                    } else {
                        var proof = 'Other';
                    }

                    if (html.payment_mode == 1) {
                        var paymentmode = 'Online';
                    } else if (html.payment_mode == 2) {
                        var paymentmode = 'Offline';
                    } else {
                        var paymentmode = 'Pay Later';
                    }
                    
                    $('#paymentmode').text(paymentmode);
                    $('#proof').text(proof);
                    $('#planName').text(html.plan_name);
                    $('#planTypeName').text(html.plan_type_name);
                    $('#joinOn').text(formatDate(html.join_date));
                    $('#startOn').text(formatDate(html.plan_start_date));
                    $('#endOn').text(formatDate(html.plan_end_date));

                    $('#price').text(html.plan_price_id);
                    $('#seat_name').text(html.seat_no);
                    $('#planTiming').text(html.hours+' Hours ('+html.start_time+' to '+html.end_time+")");

                    if(html.seat_no){
                        $('#seat_details_info').html(
                            'Booking Details of Seat No. : ' +
                            html.floor_seat_no + 
                            ' <span class="badge rounded-pill bg-danger">' + html.overdue + '</span> ' +
                            '<span class="badge rounded-pill bg-primary">' + html.pending + '</span>'
                        );
                    }else{
                        $('#seat_details_info').text('Booking Details of Seat No. : General');
                    }
                    
                    var planEndDateStr = html.plan_end_date;
                    var isRenew=html.is_renew;
                    var is_renew_update=html.renew_update;
                    var today = new Date();
                    var planEndDate = new Date(planEndDateStr);
                    var timeDiff = planEndDate - today;
                    var daysRemaining = Math.ceil(timeDiff / (1000 * 3600 * 24));
                    
                    if(daysRemaining <= 5 && isRenew==0) {
                        $('#upgrade').show();
                    }else{
                        $('#upgrade').hide();
                    }
                    
                    $('#extendday').html(html.seat_status);
                }
            });
        }

    });

    // For those Seats that are in extend period to re-new that  
    $('#upgrade').on('click', function() {
        $("#update_plan_id").trigger('change');
        var user_id = $('#user_id').val();
        var learner_detail_id = $('#learner_detail_id').val();
        var seat_no = $('#seat_name').text().trim();
        var endOnDate = $('#endOn').text().trim();
        var plan_id=$('#update_plan_id').val();
        
        // Hide the first modal
        $('#seatAllotmentModal2').modal('hide');

        // Update the fields in the second modal
        $('#update_plan_end_date').val(endOnDate);
        $('#update_seat_no').val(seat_no);
        $('#update_user_id').val(user_id);
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
        if(seat_no){
            const seatDisplay = seatDisplayMap[seat_no] ?? seat_no;
                $('#seat_number_upgrades').text('Renew Seat No.: '  + seatDisplay);
        }else{
                $('#seat_number_upgrades').text('Renew Seat No.: GEN');
        }
        
        $.ajax({
            url: '{{ route('learners.show')}}',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            },
            type: 'GET',
            data: {
                "_token": "{{ csrf_token() }}",
                "id": user_id,
            },
            dataType: 'json',
            success: function(html) {
                console.log(html);
                $('#learner_uid').text(html.learner_no);
                $('#learner_name').text(html.name);
                $('#learner_mobilepop').text(html.mobile);
                // $('#learner_email').text(html.email);
            
            }
        });
        // Show the second modal
        $('#seatAllotmentModal3').modal('show');
        fetchPlanTypesRenew(seat_no,user_id,learner_detail_id);
    });


    // For those Seats that are in extend period to re-new that  
    $('.renew_extend').on('click', function(){
        var user_id = $(this).data('user');
        var seat_no = $(this).data('seat_no');
        var end_date = $(this).data('end_date');
        var learner_detail_id = $(this).data('learner_detail');
        console.log("uuser",user_id);
        console.log("seat_no",seat_no);
        console.log("end_date",end_date);
        console.log("learner_detail_id",learner_detail_id);
        // learner detail fetch
            $.ajax({
                url: '{{ route('learners.show')}}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": user_id,
                },
                dataType: 'json',
                success: function(html) {
                    console.log(html);
                    $('#learner_uid').text(html.learner_no);
                    $('#learner_name').text(html.name);
                    $('#learner_mobilepop').text(html.mobile);
                    // $('#learner_email').text(html.email);
                
                }
            });
        //learner detail fetch end
        $('#seatAllotmentModal3').modal('show');
        $('#update_seat_no').val(seat_no);
        $('#update_user_id').val(user_id);
        $('#update_plan_end_date').val(end_date);
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
        if(seat_no){
                const seatDisplay = seatDisplayMap[seat_no] ?? seat_no;
                $('#seat_number_upgrades').text('Renew Seat No.: '  + seatDisplay);
        }else{
                $('#seat_number_upgrades').text('Renew Seat No.: GEN');
        }
        
        fetchPlanTypesRenew(seat_no, user_id,learner_detail_id);
    });

     // RENEW FORM SUBMIT
    $(document).on('submit', '#upgradeForm', function(event) {
        
        event.preventDefault();
        var formData = new FormData(this);
        var user_id = $('#update_user_id').val();
        var plan_id = $('#plan_id2').val();
        var plan_type_id = $('#plan_type_id_renew').val();
        var plan_price_id = $('#plan_price_id2').val();
        var errors = {};

        if (!plan_id) {
            errors.plan_id = 'Plan is required.';
        }

        if (!plan_type_id) {
            errors.plan_type_id = 'Plan Type is required.';
        }

        if (!plan_price_id) {
            errors.plan_price_id = 'Price is required.';
        }

        if (Object.keys(errors).length > 0) {
            $(".is-invalid").removeClass("is-invalid");
            $(".invalid-feedback").remove();

            $.each(errors, function(key, value) {
                var inputField = $("#" + key);
                inputField.addClass("is-invalid");
                inputField.after('<div class="invalid-feedback">' + value + '</div>');
            });
            return; 
        }

        formData.append('_token', '{{ csrf_token() }}');
        var formId='renewSeat';
        var fieldName='plan';
        var newValue=plan_id ;
        var oldValue=$('#hidden_plan').val();


        $.ajax({
            url: '{{ route('learner.upgrade.renew.store') }}', 
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
               
                if (response.success) {
                    logFieldChange(user_id, formId, fieldName, oldValue, newValue); 
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Renew successful',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        
                        $('#seatAllotmentModal3').modal('hide');
                        $('#seatAllotmentModal3').one('hidden.bs.modal', function () {
                            if (result.isConfirmed) {
                                window.location.href = '{{ route('seats') }}';
                                location.reload(true);
                            }

                            window.location.reload();
                        });
                    });

                   
                } else if (response.errors) {
                    showFormErrors(response.errors);
                }  else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Something went wrong. Please try again.'
                        }).then((result) => {
                        
                        $('#seatAllotmentModal3').modal('hide');
                    });
                }
            },
            error: function(xhr, status, error) {
                            
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    showFormErrors(errors);                       
                } else {
                   if (xhr.status === 409) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Renewal Blocked',
                            text: xhr.responseJSON.message
                            }).then((result) => {
                        
                                $('#seatAllotmentModal3').modal('hide');
                      
                            });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Something went wrong. Please try again.'
                            }).then((result) => {
                        
                                $('#seatAllotmentModal3').modal('hide');
                            });
                    }
                }
        }

        });
    });



    


    $(document).ready(function() {
        

         
      

        let table = new DataTable('#datatable');
        //learner edit page 
        var edit_seat_id=$("#edit_seat").val();
        if(edit_seat_id){
            getTypeSeatwise(edit_seat_id);
            $('#plan_type_id').trigger('change');
        }

          // Get Plan Type at All Forms wherever is needed
        function fetchPlanTypes(seat_no, user_id,learner_detail_id) {
           
            if ((seat_no && user_id) || learner_detail_id) {
                $.ajax({
                    url: '{{ route('gettypePlanwise') }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    type: 'GET',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "seat_no": seat_no,
                        "user_id": user_id,
                        "learner_detail_id": learner_detail_id,
                    },
                    dataType: 'json',
                    success: function (html) {
                        console.log("renew",html);
                        $("#plan_type_id2").empty(); 
                        $("#plan_id2").empty(); 

                        if (html[0]) {
                            $.each(html[0], function (key, value) {
                                $("#plan_type_id2").append('<option value="' + key + '">' + value + '</option>');
                            });
                        } else {
                            $("#plan_type_id2").append('<option value="">Choose</option>');
                        }
                       

                        if (html[1]) {
                             $.each(html[1], function (key, value) {
                                $("#plan_id2").append('<option value="' + key + '">' + value + '</option>');
                            });
                        }

                        if (html[2]){
                           $("#plan_price_id2").val(html[2].plan_price_id);      
                        }

                        if(html[3]){
                            $("#locker_amount2").val(html[3].locker_amount);  
                            $("#discount_amount3").val(html[3].discount_amount);  
                            $("#new_plan_price").val(html[3].discount_amount);  

                            if (html[3].locker_amount && parseFloat(html[3].locker_amount) > 0) {
                                $("#locker").val('yes');
                                $("#locker_amount2").val(html[3].locker_amount);
                            } else {
                                $("#locker").val('no');
                                $("#locker_amount2").val('');
                            }

                            if (html[3].discount_amount && parseFloat(html[3].discount_amount) > 0) {
                                $("#discount_type").val('amount');
                                $("#discount_amount3").val(html[3].discount_amount);
                            } else {
                                $("#discount_type").val('');
                                $("#discount_amount3").val('');
                            }
                        }
                        
                        popupautoCalculatePaidAmount(); 
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX error:", status, error); // Log any errors
                    }
                });
            } else {
                $("#plan_type_id2").empty();
                $("#plan_type_id2").append('<option value="">Choose Shift</option>');
            }
        }

       

       

          // Get Plan Price at All Forms wherever is needed
        function getPlanPrice2(plan_type_id,plan_id){
          
            if (plan_type_id && plan_id) {
                    $.ajax({
                        url: '{{ route('getPricePlanwise') }}',
                        type: 'GET',
                        data: {
                            "_token": "{{ csrf_token() }}",
                            "plan_type_id": plan_type_id,
                            "plan_id": plan_id,
                        },
                        dataType: 'json',
                        success: function(html) {
                            if (html && html !== undefined) {
                                $('#pending_amt3').html('');
                                if ($("#plan_price").length) {
                                   
                                    $("#plan_price").val(html);
                                    autoCalculatePaidAmount2();
                                    $("#error-message").hide();
                                }
                                $("#error-message").hide();
                            } else {
                                
                                 $("#plan_price").val("");
                                $("#pending_amt").html("No Plan Price Added Yet.");
                                $("#paid_amount").val("");
                            }
                        }

                    });
            } else {
               
                $("#plan_price").empty();
                $("#paid_amount").empty();
            
            }
        }

           // Get Plan Price at Renew popup Forms wherever is needed
        function getPlanPriceRenew(plan_type_id,plan_id){
          
            if (plan_type_id && plan_id) {
                    $.ajax({
                        url: '{{ route('getPricePlanwise') }}',
                        type: 'GET',
                        data: {
                            "_token": "{{ csrf_token() }}",
                            "plan_type_id": plan_type_id,
                            "plan_id": plan_id,
                        },
                        dataType: 'json',
                        success: function(html) {
                            if (html && html !== undefined) {

                                if ($("#plan_price_id2").length) {
                                   
                                    $("#plan_price_id2").val(html);
                                    autoCalculatePaidAmount2();
                                    $("#error-message").hide();
                                }
                                $("#error-message").hide();
                            } else {
                                
                                 $("#plan_price_id2").val("");
                                $("#pending_amt").html("No Plan Price Added Yet.");
                                $("#paid_amount").val("");
                            }
                        }

                    });
            } else {
               
                $("#plan_price").empty();
                $("#paid_amount").empty();
            
            }
        }

        // Auto calculate paid amount when plan price, locker or discount changes
        $('#plan_price_id, #locker_amount').on('change', autoCalculatePaidAmount);
        $('#plan_price_id, #locker_amount_book').on('change', autoCalculatePaidAmount);
   
          // If Discount amt is enter it can change the paid amt on RE-NEW Popup
        $('#discount_type').on('change', function (){
            const type = $(this).val();
            if (type === 'percentage') {
                $('#typeVal').text('%');
            } else if (type === 'amount') {
                $('#typeVal').text('INR');
            } else {
                $('#typeVal').text('INR / %');
            }
          popupautoCalculatePaidAmount();
        });
          // If Discount amt is enter it can change the paid amt on RE-NEW FORM
        $('#discount_amount2').on('input', function () {
            autoCalculatePaidAmount2(); // Recalculate if amount changes
        });
        $('#discountType2').on('change', function (){
           const type = $(this).val();
            if (type === 'percentage') {
                $('#typeVal3').text('%');
            } else if (type === 'amount') {
                $('#typeVal3').text('INR');
            } else {
                $('#typeVal3').text('INR / %');
            }
            autoCalculatePaidAmount2(); 
          
        });
        

         // If user manually updates paid_amount in RENEW, update pending as well
        $('#new_plan_price2').on('input', calculatePendingAmountRenew);

        // If user manually updates paid_amount in RENEW upgrade, update pending as well
        $('#new_plan_price').on('input', calculatePendingAmountRenewUpgrade);
         // If user manually updates paid_amount in RENEW upgrade, update pending as well
        $('#diffrence_amount').on('input', calculatePendingAmountChangePlan);

       
        
         // Manage Locaker in Other Form
        $('#toggleFieldCheckbox, #plan_id').on('change', function () {
           
            var needLocker = $('#toggleFieldCheckbox').val();
            var planId     = $('#plan_id').val();
            const locker_user_id     = $('#user_id').val();
           
            if (needLocker === 'yes') {
                
                $('#locker_no2').removeAttr('readonly');
                $('#locker_no3').removeAttr('readonly');
                $('#locker_no').removeAttr('readonly');
                $.get("{{ route('locker.price') }}", { plan_id: planId })
                .done(function(json) {
                    $('#locker_amount').val(json.price);
                    // ✅ call here AFTER value is set
                    autoCalculatePaidAmount2(); 
                })
                .fail(function() {
                    $('#locker_amount').val('').prop('readonly', true);
                    autoCalculatePaidAmount2(); 
                });

                
                //locker no get
                getLockerNo(locker_user_id,'locker_no3');
            } else {
                $('#locker_amount').attr('readonly', true);
                $('#locker_no').attr('readonly', true);
                $('#locker_no2').attr('readonly', true);
                $('#locker_no3').attr('readonly', true);
                $('#locker_amount').val(0);
                $('#locker_no').val('');
                $('#locker_no2').val('');
                $('#locker_no3').val('');
                // ✅ call here when locker is disabled
                autoCalculatePaidAmount2(); 
            }
        });

      

       
    
     

       
        
       
        
        $('#plan_type_id2').on('change', function(event) {
            
            var plan_type_id = $(this).val();
            var plan_id = $('#plan_id').val();
            var change_plan_plan_id = $('#change_plan_plan_id').val();
            var plan_id2 = $('#plan_id2').val();
            var plan_id3 = $('#plan_id3').val();
            var plan_id4 = $('#plan_id4').val();
           
          
            if((plan_type_id && plan_id4)||(plan_type_id && plan_id)||(plan_type_id && plan_id2)||(plan_type_id && plan_id3)||(plan_type_id && change_plan_plan_id)){
             
                getPlanPrice2(plan_type_id,plan_id);
                getPlanPrice(plan_type_id,plan_id2);
                getPlanPrice(plan_type_id,plan_id3);
                getPlanPrice(plan_type_id,plan_id4);
                getPlanPrice(plan_type_id,change_plan_plan_id);
            }else{
                $("#plan_price").val('');
            }
           
        });
        $('#plan_type_id_renew').on('change', function(event) {
            
            var plan_type_id = $(this).val();
            var plan_id2 = $('#plan_id2').val();
            if((plan_type_id && plan_id2)){
                getPlanPriceRenew(plan_type_id,plan_id2);
            }else{
                $("#plan_price").val('');
            }
           
        });


        // Oncahnge of Plan get Plan Price and use at each form wherever is needed 
        $('#plan_id,#plan_id2,#plan_id3').on('change', function(event) {
            event.preventDefault();
            var plan_id = $(this).val();
            var plan_type_id = $('#plan_type_id').val();
            var plan_type_id2 = $('#plan_type_id2').val();
            var plan_start_date = $('#plan_start_date').val();
          
            if(plan_type_id && plan_id){
                getPlanPrice(plan_type_id,plan_id);
                
            }
            if(plan_type_id2 && plan_id){
                getPlanPrice(plan_type_id2,plan_id);
            }else{
                $("#plan_price_id").val('');
            }

            if(plan_start_date && plan_id){
                addChargeableDays(plan_id,plan_start_date);
            }

            
        });


        // Get Price form Plan Type and Plan in All Form Wherever is needed
        $('#update_plan_id, #updated_plan_type_id').on('change', function (event) {
            event.preventDefault();
            var update_plan_type_id = $('#updated_plan_type_id').val();
            var update_plan_id =$('#update_plan_id').val();
       
            if (update_plan_id && update_plan_type_id) {
                $.ajax({
                    url: '{{ route('getPricePlanwiseUpgrade') }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    type: 'GET',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "update_plan_type_id": update_plan_type_id,
                        "update_plan_id": update_plan_id,
                    },
                    dataType: 'json',
                    success: function(html) {
                       
                        $.each(html, function(key, value) {
                            $("#updated_plan_price_id").val(value);
                        });
                    }
                });
            } else {
                $("#updated_plan_price_id").empty();
                $("#updated_plan_price_id").append('<option value="">Select Plan Price</option>');
            }
        });


       

      
       

      


        

       
       

        
        // Get Transaction Information show at View Details Page
        $('#transaction_id').on('change', function(event) {
          event.preventDefault();
          var transaction_id = $(this).val();
         
          if (transaction_id) {
              $.ajax({
                  url: '{{ route('getTransactionDetail') }}',
                  headers: {
                      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                  },
                  type: 'GET',
                  data: {
                      "_token": "{{ csrf_token() }}",
                      "transaction_id": transaction_id,
                     
                  },
                  dataType: 'json',
                  success: function(response) {
                if (response.error) {
                    alert(response.error);
                } else {
                        $('#plan_name').val(response.plan.name);
                        $('#plan_type_name').val(response.plantype.name);
                        $('#plan_price').val(response.plan_price_id );
                        $('#plan_start_date ').val(response.plan_start_date );
                        $('#plan_end_date ').val(response.plan_end_date );
                    }
                },
                error: function(xhr) {
                    alert('Error fetching transaction details.');
                }
              });
          }
        });


        // Manage Locker Function on RE-NEW FORM
        $('#locker').on('change', function () {
            var needLocker = $(this).val();
            var planId     = $('#plan_id2').val();
            var locker_user_id     = $('#update_user_id').val();
            if (needLocker === 'yes') {
                $('#locker_no2').removeAttr('readonly');
             
              
                $.get("{{ route('locker.price') }}", { plan_id: planId })
                .done(function(json) {
                    $('#locker_amount2').val(json.price);
                     
                    popupautoCalculatePaidAmount(); 
                    
                })
                .fail(function() {
                    $('#locker_amount2').val('').prop('readonly', true);
                    popupautoCalculatePaidAmount(); 
                });

                //locker no get
                getLockerNo(locker_user_id,'locker_no2');
            } else {
                $('#locker_amount2').attr('readonly', true);
                $('#locker_amount2').val('');
                $('#locker_no2').val('');
                popupautoCalculatePaidAmount(); 
            }
        });

        function getLockerNo(learner_id, addid) {
            // locker no. get
            $.get("{{ route('locker.no') }}", { learner_id: learner_id })
            .done(function (json) {
                $('#' + addid).val(json.learner.locker_no); // if you're passing an element ID
                // or use $('.' + addid) if you're passing a class name
            })
            .fail(function () {
                $('#' + addid).val('').prop('readonly', true);
            });
        }

      


        // If Discount amt is enter it can change the paid amt on RE-NEW FORM
        $('#discount_amount3').on('input', function () {
            popupautoCalculatePaidAmount();
        });


        // View Booked Seat Details on Seat Assignment Page
        $('.second_popup_without_seat').on('click', function() {
            $('#upgrade').hide();
            var userId = $(this).data('userid');
            $('#user_id').val(userId);
            $('#seatAllotmentModal2').modal('show');
           
          
            if (userId) {
                $.ajax({
                    url: '{{ route('learners.show')}}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    type: 'GET',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "id": userId,
                    },
                    dataType: 'json',
                    success: function(html) {
                        $('#learner_detail_id').val(html.learner_detail_id);
                        $('#owner').text(html.name);
                        $('#learner_dob').text(html.dob);
                        $('#learner_email').text(html.email);
                        $('#learner_mobile').text(html.mobile);
                        if (html.id_proof_name == 1) {
                            var proof = 'Aadhar';
                        } else if (html.id_proof_name == 2) {
                            var proof = 'Driving License';
                        } else {
                            var proof = 'Other';
                        }
                        if (html.payment_mode == 1) {
                            var paymentmode = 'Online';
                        } else if (html.payment_mode == 2) {
                            var paymentmode = 'Offline';
                        } else {
                            var paymentmode = 'Pay Later';
                        }
                        
                        $('#paymentmode').text(paymentmode);
                        $('#proof').text(proof);
                        $('#planName').text(html.plan_name);
                        $('#planTypeName').text(html.plan_type_name);
                        $('#joinOn').text(html.join_date);
                        $('#startOn').text(html.plan_start_date);
                        $('#endOn').text(html.plan_end_date);
                        $('#price').text(html.plan_price_id);
                        $('#seat_name').text(html.seat_no);
                        $('#planTiming').text(html.hours+' Hours ('+html.start_time+' to '+html.end_time+")");
                       
                        if(html.seat_no){
                             $('#seat_details_info').html(
                                'Booking Details of Seat No. : ' +
                                html.seat_no + 
                                ' <span class="badge rounded-pill bg-danger">' + html.overdue + '</span> ' +
                                '<span class="badge rounded-pill bg-primary">' + html.pending + '</span>'
                            );
                        }else{
                            $('#seat_details_info').text('Booking Details of Seat No. : General');
                        }
                        var planEndDateStr = html.plan_end_date;
                        var isRenew=html.is_renew;
                        var is_renew_update=html.renew_update;
                        var today = new Date();
                        var planEndDate = new Date(planEndDateStr);
                        var timeDiff = planEndDate - today;
                        var daysRemaining = Math.ceil(timeDiff / (1000 * 3600 * 24));
                       
                        if(daysRemaining <= 5 && isRenew==0) {
                            $('#upgrade').show();
                        }else{
                            $('#upgrade').hide();
                        }
                       

                        var extendDay=html.diffExtendDay;
                        var message = '';
                       
                        // Applying the conditions as per your Laravel blade logic
                        if(is_renew_update == 1){
                            message = `<h5 class="text-success">Plan will Expires in ${daysRemaining} days.</h5><p class="text-info">Notice : You have a new plan in the queue. Once your current plan expires, your new plan will automatically activate.</p>`;
                        }else if (daysRemaining > 0) {
                            message = `<h5 class="text-success">Plan Expires in ${daysRemaining} days</h5>`;
                        } else if (daysRemaining < 0 && extendDay > 0) {
                            message = `<h5 class="text-danger fs-10 d-block">Extend Days are Active Now & Remaining Days are ${Math.abs(extendDay)} days.</h5>`;
                        } else if (daysRemaining < 0 && extendDay == 0) {
                            message = `<h5 class="text-danger extedned fs-10 d-block">Seat Expire Today</h5>`;
                        } else if (daysRemaining == 0 && extendDay > 0) {
                            message = `<h5 class="text-danger extedned fs-10 d-block">Plan Expires Today. Extend Days Starts Today</h5>`;
                        }else {
                            message = `<h5 class="text-warning fs-10 d-block">Plan Expired ${Math.abs(daysRemaining)} days ago</h5>`;
                        }

                        $('#extendday').html(message);
                    }
                });
            }

        });

       
      
    });
        

</script>


<script>
    // Function to handle changes Activity and show that on Dashboard Page
    function handleFormChanges(formId, learnerId) {
        const form = document.getElementById(formId);
        if (!form) {
            console.error('Form not found:', formId);
            return;
        }
        const changes = {}; 
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.dataset.initialValue = input.value;
            input.addEventListener('change', function() {
                const fieldName = this.name;
                const oldValue = this.dataset.initialValue;
                const newValue = this.value;
                // console.log(`Field changed: ${fieldName}, Old Value: ${oldValue}, New Value: ${newValue}`);
                if (oldValue !== newValue) {
                    changes[fieldName] = { oldValue, newValue };
                    this.dataset.initialValue = newValue; 
                }
            });
        });

        // Add submit event listener to the form
        form.addEventListener('submit', function(event) {
            for (const fieldName in changes) {
                const { oldValue, newValue } = changes[fieldName];
                const swap_old_value=$('#swap_old_value').val();
                if(swap_old_value=='swapseat'){
                    swap_old_value='General';
                }
               
                if (formId === 'reactive') {
                    if (fieldName === 'seat_id') {
                        logFieldChange(learnerId, formId, fieldName, oldValue, newValue);
                    }
                }else if(formId === 'swapseat'){
                    logFieldChange(learnerId, formId, fieldName, swap_old_value, newValue);
                } else {
                    // For other operations, log changes for all fields
                    logFieldChange(learnerId, formId, fieldName, oldValue, newValue);
                }
            }
        });
    }

    // Function to log the field changes
    function logFieldChange(learnerId, formId, fieldName, oldValue, newValue) {
       
        console.log('Logging change for learner:', learnerId, formId, fieldName, oldValue, newValue);
        fetch("{{ route('learner.log') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
            },
            body: JSON.stringify({
                learner_id: learnerId,
                field_updated: fieldName,
                old_value: oldValue,
                new_value: newValue,
                operation: formId,
                updated_by: {{ getAuthenticatedUser()->id }},
                created_at: new Date().toISOString(),
            }),
        })
        .then(response => response.json())
        .then(data => console.log('Change logged successfully:', data))
        .catch(error => console.error('Error logging change:', error));
    }

    // Increase Message Send Count and store it in DB to show on Dashboard Counts
    function incrementMessageCount(id, type) {
        fetch(`increment-message-count`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                id: id,
                type: type
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                console.log(`${type} message count updated for user ID: ${id}`);
            } else {
                console.error('Failed to update message count');
            }
        })
        .catch(error => console.error('Error:', error));
    }


    

     // auto calculate amount and used at multiple places
    function autoCalculatePaidAmount2() {
        var planPrice = parseFloat($('#plan_price').val()) || 0;
        var lockerAmount = parseFloat($('#locker_amount').val()) || 0;
        var discountType = $('#discountType2').val();
        var discountAmt = parseFloat($('#discount_amount2').val()) || 0;
        var totalAmount = parseFloat($('#total_amount2').val()) || 0;

        if (discountType === 'percentage' ) {
            discountAmount = ((planPrice + lockerAmount) * discountAmt) / 100;
        } else {
            discountAmount = discountAmt;
        }

        if (discountAmount==0 && discountType !== 'percentage' && discountType !== 'amount') {
            $('#discount_amount2').val(0);
        }
         
        var autoPaid = planPrice + lockerAmount - discountAmount;

        
       
        $('#new_plan_price').val(autoPaid);
        
       
        var difference = autoPaid - totalAmount;
        
        $('#diffrence_amount').val(difference);
        $('#diffrence_amount').removeAttr('readonly');
        $('#discount_amount2').removeAttr('readonly');
        calculatePendingAmountRenewUpgrade();
    }


    // Auto Calculate for Re-New
    function popupautoCalculatePaidAmount() {
        const planPrice = parseFloat($('#plan_price_id2').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount2').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount3').val()) || 0;
        const discountType = $('#discount_type').val();
        
        let discountAmountt = 0;
       
        if (discountType === 'percentage') {
            discountAmountt = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmountt = discountRaw;
        }

        if (discountType !== 'percentage' && discountType !== 'amount') {
            $('#discount_amount3').val("");
        }
      
        var autoPaidnew;
        if(planPrice && lockerAmount && discountAmountt){
            autoPaidnew = planPrice + lockerAmount - discountAmountt;
        } else if (planPrice && lockerAmount) {
            autoPaidnew = planPrice + lockerAmount;
        }else if (planPrice && discountAmountt) {
            autoPaidnew = planPrice - discountAmountt;
        } else {
            autoPaidnew = planPrice;
        }
        // console.log('planPrice',planPrice);
        // console.log('lockerAmount',lockerAmount);
        // console.log('discountRaw',discountRaw);
        // console.log('discountType',discountType);
        // console.log('discountAmountt',discountAmountt);
        // console.log('autoPaidnew',autoPaidnew);
        
        $('#new_plan_price2').val(autoPaidnew);
        calculatePendingAmountRenew();
    }
   

    // Calculate Pending Amount on Renew Popup FORM
    function calculatePendingAmountRenew() {
        const planPrice = parseFloat($('#plan_price_id2').val()) || 0;
        const paidAmount = parseFloat($('#new_plan_price2').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount2').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount3').val()) || 0;
        const discountType = $('#discount_type').val();
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else {
            discountAmount = discountRaw;
        }

        const effectivePaid = planPrice+lockerAmount - discountAmount;
        const pendingAmount = effectivePaid-paidAmount;
       
        

        if(pendingAmount > 0){
            $('#pending_amt2').html('Pending Amount: ' + pendingAmount);
        }else if (pendingAmount < 0) {
            $('#pending_amt2').html('High price not allowed.' + pendingAmount);
        }else{
            $('#pending_amt2').html('');
        }

        console.log('lockerAmount',lockerAmount);
        console.log('discountAmount',discountAmount);
        //console.log('planPrice',planPrice); 
        console.log('effectivePaid',effectivePaid);
        console.log('pendingAmount',pendingAmount);

        if (pendingAmount > 0) {
            $('#due_date2').removeAttr('readonly');
        } else {
            $('#due_date2').attr('readonly', true);
        }
    }

    // Calculate Pending Amount on Renew FORM
    function calculatePendingAmountRenewUpgrade() {
        
        const planPrice = parseFloat($('#plan_price').val()) || 0;
        const paidAmount = parseFloat($('#new_plan_price').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount2').val()) || 0;
        const discountType = $('#discountType2').val();
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else {
            discountAmount = discountRaw;
        }

        const effectivePaid = planPrice+lockerAmount - discountAmount;
        const pendingAmount = effectivePaid-paidAmount;
       
        

        if(pendingAmount > 0){
            $('#pending_amt3').html('Pending Amount: ' + pendingAmount);
        }else if (pendingAmount < 0) {
            $('#pending_amt3').html('High price not allowed.' + pendingAmount);
        }else{
            $('#pending_amt3').html('');
        }

        console.log('lockerAmount',lockerAmount);
        console.log('discountAmount',discountAmount);
        //console.log('planPrice',planPrice); 
        console.log('effectivePaid',effectivePaid);
        console.log('pendingAmount',pendingAmount);

        if (pendingAmount > 0) {
            $('#due_date3').removeAttr('readonly');
        } else {
            $('#due_date3').attr('readonly', true);
        }
    }
     // Calculate Pending Amount on Change plan
    function calculatePendingAmountChangePlan() {

        const planPrice2 = parseFloat($('#plan_price').val()) || 0;
        const lockerAmount2 = parseFloat($('#locker_amount').val()) || 0;
        const discountType2 = $('#discountType2').val();
        const discountAmt2 = parseFloat($('#discount_amount2').val()) || 0;
        const totalAmount2 = parseFloat($('#total_amount2').val()) || 0;
        const autoPaid2 = parseFloat($('#new_plan_price').val()) || 0;

        if (discountType2 === 'percentage' ) {
            discountAmount2 = ((planPrice2 + lockerAmount2) * discountAmt2) / 100;
        } else {
            discountAmount2 = discountAmt2;
        }

        const effectivePaid2 = planPrice2 + lockerAmount2 - discountAmount2 - totalAmount2;

        const inputamt = $(this).val();
        const pendingAmount2 = effectivePaid2-inputamt;
      
        if(pendingAmount2 > 0){
            $('#pending_amt4').html('Pending Amount: ' + pendingAmount2);
        }else if (pendingAmount2 < 0) {
            $('#pending_amt4').html('High price not allowed.' + pendingAmount2);
        }else{
            $('#pending_amt4').html('');
        }

      
        if (pendingAmount2 > 0) {
            $('#due_date3').removeAttr('readonly');
        } else {
            $('#due_date3').attr('readonly', true);
        }
    }



</script>







<script>
// for Waba send all function
function loadLearnerMobiles(learnerId,mobileId) {
    

    $.ajax({
        url: "{{ route('notification.getLearnerMobiles') }}",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            learner_id: learnerId
        },
        success: function (res) {
            console.log(res.mobiles);
             let mobileSelect = $('#' + mobileId); 
            mobileSelect.empty();   // clear previous
            mobileSelect.prop('disabled', false); // enable by default

            // --- CASE 1: No mobile found ---
            if (!res.mobiles || res.mobiles.length === 0) {
                mobileSelect.append(`<option value="">No Mobile Found</option>`);
                mobileSelect.prop('disabled', true);
                return;
            }

            // --- CASE 2: Only 1 mobile number ---
            if (res.mobiles.length === 1) {
                let single = res.mobiles[0];

                mobileSelect.append(`<option value="${single}" selected>${'+91-'+single}</option>`);
                mobileSelect.prop('disabled', true);  // disable the dropdown
                return;
            }

            // --- CASE 3: Multiple numbers available ---
            mobileSelect.append(`<option value="">Select Mobile</option>`);
            var x = 0;
            res.mobiles.forEach(function (m) {
                x++;
                if(x===1){
                mobileSelect.append(`<option value="${m}">${'+91-'+m+' (primary-mobile)'}</option>`);
                } else{
                mobileSelect.append(`<option value="${m}">${'+91-'+m+' (parent-number)'}</option>`);
                }
            });
        }
    });
}
// Show Form Errors
function showFormErrors2(errors) {
    $(".is-invalid").removeClass("is-invalid");
    $(".invalid-feedback").remove();

    $.each(errors, function(key, value) {
        const field = $("[name='" + key + "']");
        field.addClass("is-invalid");
        field.after('<div class="invalid-feedback">' + value[0] + '</div>');
    });
}

$(document).on('click', '.open-waba', function () {

    let learnerId = $(this).data('learner_id');
    $('#modal_learner_id').val(learnerId);

    // Load mobiles in dropdown
    loadLearnerMobiles(learnerId,'learner_mobile_select');
});
// When template changes → get both values and render final message
$('#waba_template_select').on('change', function () {

    let learner_idm = $('#modal_learner_id').val();
    let template_id = $(this).val();

     let errors = {};

    if (!template_id) errors.template_id = ["Please select a template."];
    if (!learner_idm) errors.learner_idm = ["Invalid learner ID."];

    if (Object.keys(errors).length > 0) {
        showFormErrors2(errors);
        return; // stop here
    }

    $.ajax({
        url: "{{ route('notification.renderMessage') }}",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            template_id: template_id,
            learner_id: learner_idm
        },
        success: function (res) {
            $('#waba_final_message').val(res.message);
        }
    });

});
$('#sendWabaMessage').on('click', function (e) {
    e.preventDefault();    
    e.stopPropagation();   
    let templateId = $('#waba_template_select').val();
    let message = $('#waba_final_message').val();
     let learner_id = $('#modal_learner_id').val();
     let mobileNo = $('#learner_mobile_select').val();
   

    let errors = {};

    if (!templateId) errors.template_id = ["Please select a template."];
    if (!mobileNo) errors.mobileNo = ["Please select mobile number."];
    if (!message) errors.message = ["Message cannot be empty."];
    if (!learner_id) errors.learner_id = ["Invalid learner ID."];

    if (Object.keys(errors).length > 0) {
        showFormErrors2(errors);
        return; // stop here
    }

    $.ajax({
        url: "{{ route('notification.sendMessage') }}",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            learner_id: learner_id,
            template_id: templateId,
            message: message,
            mobileNo: mobileNo,
        },
        success: function (res) {
            toastr.success("Message sent successfully!");

            $('#wabaSendModel').modal('hide');

            // Reset form
            $('#modal_learner_id').val('');
            $('#waba_template_select').val('').trigger('change');
            $('#learner_mobile_select').val('').trigger('change');
            $('#waba_final_message').val('');
        },

        error: function (xhr) {
            if (xhr.status === 422) {
                showFormErrors2(xhr.responseJSON.errors);
            } else {
                toastr.error("Something went wrong!");
            }
        }
    });
});

// for text message
$(document).on('click', '.open-text', function () {

    let learnerId = $(this).data('learner_id');
    $('#modal_learner_id2').val(learnerId);

    // Load mobiles in dropdown
    loadLearnerMobiles(learnerId,'learner_mobile_select2');
});
// When template changes → get both values and render final message
$('#text_template_select').on('change', function () {

    let learner_id = $('#modal_learner_id2').val();
    let template_id = $(this).val();

     let errors = {};

    if (!template_id) errors.template_id = ["Please select a template."];
    if (!learner_id) errors.learner_id = ["Invalid learner ID."];

    if (Object.keys(errors).length > 0) {
        showFormErrors2(errors);
        return; // stop here
    }

    $.ajax({
        url: "{{ route('notification.renderMessage') }}",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            template_id: template_id,
            learner_id: learner_id
        },
        success: function (res) {
            $('#text_final_message').val(res.message);
        }
    });

});
$('#sendTextMessage').on('click', function (e) {
    e.preventDefault();    
    e.stopPropagation();   
    let templateId = $('#text_template_select').val();
    let message = $('#text_final_message').val();
     let learner_id = $('#modal_learner_id2').val();
     let mobileNo = $('#learner_mobile_select2').val();

    let errors = {};

    if (!templateId) errors.template_id = ["Please select a template."];
    if (!mobileNo) errors.mobileNo = ["Please select mobile number."];
    if (!message) errors.message = ["Message cannot be empty."];
    if (!learner_id) errors.learner_id = ["Invalid learner ID."];

    if (Object.keys(errors).length > 0) {
        showFormErrors2(errors);
        return; // stop here
    }

    $.ajax({
        url: "{{ route('notification.sendMessage') }}",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            learner_id: learner_id,
            template_id: templateId,
            message: message,
            mobileNo: mobileNo,
        },
        success: function (res) {
            toastr.success("Message sent successfully!");

            $('#textSendModel').modal('hide');

            // Reset form
            $('#modal_learner_id2').val('');
            $('#text_template_select').val('').trigger('change');
            $('#learner_mobile_select2').val('').trigger('change');
            $('#text_final_message').val('');
        },

        error: function (xhr) {
            if (xhr.status === 422) {
                showFormErrors(xhr.responseJSON.errors);
            } else {
                toastr.error("Something went wrong!");
            }
        }
    });
});
//Gift Days Functionality
$(document).on('click', '.giftDaysBtn', function () {

    let learner_id = $(this).data('learner_id');

    // First fetch existing gift days
    $.ajax({
        url: "{{ route('get.gift.days') }}",
        type: "POST",
        data: {
            learner_id: learner_id,
            _token: "{{ csrf_token() }}"
        },
        success: function (res) {

            let existingDays = res.total_gift_days ?? 0;

            Swal.fire({
                title: "Assign Gift Days",
                input: 'number',
                inputLabel: 'Enter number of gift days (+allowed)',
                inputValue: existingDays,   // PREFILL VALUE HERE
                inputPlaceholder: 'e.g. 5',
                showCancelButton: true,
                confirmButtonText: 'Save',
                cancelButtonText: 'Cancel',
                inputAttributes: {
                    min: 1, 
                    step: 1
                },
                iconHtml: '<i class="fas fa-gift fa-3x" style="color:red;font-size:40px;"></i>',
                preConfirm: (value) => {
                     if (value === "" || isNaN(value)) {
                        Swal.showValidationMessage('Please enter a valid number');
                        return false;
                    }

                    if (parseInt(value) <= 0) {
                        Swal.showValidationMessage('Gift days must be greater than 0');
                        return false;
                    }

                    return parseInt(value);
                }
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: "{{ route('assign.gift.days') }}",
                        type: "POST",
                        data: {
                            learner_id: learner_id,
                            gift_days: result.value,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (response) {
                            Swal.fire({
                                icon: "success",
                                title: "Gift Days Updated!",
                                text: response.message
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function () {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: "Something went wrong!"
                            });
                        }
                    });

                }
            });

        }
    });

});

//Frozen
$(document).on('click', '.freezDaysBtn', function () {

    let status = $(this).data('status'); // 0 = Active,1=freez 2 = unfreez
    let learnerDetail = $(this).data('learnerdetail');
    let learner_id = $(this).data('learner_id');

    let title = status == 0 
        ? "Freeze Plan?"
        : "Unfreeze Plan?";

    let text = status == 0 
        ? "Are you sure you want to freeze this learner's plan? Today's date will be saved as freeze start date."
        : "Are you sure you want to unfreeze? Frozen days will be added to plan end date.";

    Swal.fire({
        title: title,
        text: text,
        iconHtml: '<i class="fa-solid fa-snowflake fa-3x" style="color:red;font-size:40px;"></i>',
        showCancelButton: true,
        confirmButtonText: status == 0 ? "Yes, Freeze" : "Yes, Unfreeze",
        cancelButtonText: "Cancel"
    }).then((result) => {

        if (result.isConfirmed) {

            $.ajax({
                 url: "{{ route('freeze.unfreeze') }}",
                type: "POST",
                data: {
                    learnerDetail: learnerDetail,
                    learner_id: learner_id,
                     status: status,
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: response.message
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function () {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Something went wrong!"
                    });
                }
            });

        }
    });

});


//  end 
</script>
