<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        var today = new Date();
        var formattedDate = today.toISOString().split('T')[0]; // Format as YYYY-MM-DD
        $('#plan_start_date').val(formattedDate); 
        
    });
  

   
    // jQuery script
    $(document).ready(function() {
        let table = new DataTable('#datatable');
        // $('#datatable').DataTable();
        /**customer edit page**/
        var edit_seat_id=$("#edit_seat").val();
       
        if(edit_seat_id){
           
            getTypeSeatwise(edit_seat_id);
            $('#plan_type_id').trigger('change');
        }
            // Auto calculate paid amount when plan price, locker or discount changes
        $('#plan_price_id, #locker_amount').on('change', autoCalculatePaidAmount);
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

        $('#discount_amount').on('input', function () {
            autoCalculatePaidAmount(); // Recalculate if amount changes
        });

        // If user manually updates paid_amount, update pending as well
        $('#paid_amount').on('input', calculatePendingAmount);

   
        $('#toggleFieldCheckbox, #plan_id').on('change', function () {
           
            var needLocker = $('#toggleFieldCheckbox').val();
            var planId     = $('#plan_id').val();

            if (needLocker === 'yes') {
                $('#locker_no').removeAttr('readonly');
            

                $.get("{{ route('locker.price') }}", { plan_id: planId })
                .done(function(json) {
                    console.log(json.price);
                    $('#locker_amount').val(json.price);
                    autoCalculatePaidAmount(); // ✅ call here AFTER value is set
                })
                .fail(function() {
                    $('#locker_amount').val('').prop('readonly', true);
                    autoCalculatePaidAmount(); // optional if needed even on fail
                });
            } else {
                $('#locker_amount').attr('readonly', true);
                $('#locker_no').attr('readonly', true);
                $('#discount_amount').val('');
                $('#locker_amount').val('');
                $('#locker_no').val('');
                autoCalculatePaidAmount(); // ✅ call here when locker is disabled
            }
        });



        $('.noseat_popup, .first_popup').on('click', function (e) {
        
            var currentBranch = @json(getCurrentBranch());
             
                
            if (!currentBranch || currentBranch == 0) {
                alert("Please select a branch first.");
                return false; 
            }

            
                var seatId = $(this).data('id');
                var seatNo=$(this).data('seat_no');

               
                if(seatNo || seatId){
                    $('#seat_no').val(seatNo);
                    $('#seat_id').val(seatId);
                    $('#seat_no_head').text('Book Seat No ' + seatNo);
                    $('#general_seat').val('no').trigger('change');
                       // Hide the seat select fields (visually only)
                    $('#seat_id').closest('.col-lg-6').hide();
                    $('#general_seat').closest('.col-lg-6').hide();
                }else{
                    $('#seat_no_head').text('Booking Form');
                    $('#general_seat').val('yes').trigger('change');
                     // Show seat fields
                    $('#seat_id').closest('.col-lg-6').show();
                    $('#general_seat').closest('.col-lg-6').show();
                    
                }
                
                $('#seatAllotmentModal').modal('show');
              
                if ($('#general_seat').val() === 'yes') {
                 
                    getTypeSeatwise(''); 
                } else if (seatId) {
                    getTypeSeatwise(seatId); 
                }
          
          
        });
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
                          console.log('learner_detail_id',html.learner_detail_id);
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
                            $('#seat_details_info').text('Booking Details of Seat No. : ' + html.seat_no);
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
            $('#seat_number_upgrades').text('Renew Library Membership for Seat No '  + seat_no);
          
           
            // Show the second modal
            $('#seatAllotmentModal3').modal('show');
            
            fetchPlanTypes(seat_no,user_id,learner_detail_id);
        });

        $('.renew_extend').on('click', function(){
            var user_id = $(this).data('user');
            var seat_no = $(this).data('seat_no');
            var end_date = $(this).data('end_date');
            var learner_detail_id = $(this).data('learner_detail');
            
            $('#seatAllotmentModal3').modal('show');
            $('#update_seat_no').val(seat_no);
            $('#update_user_id').val(user_id);
            $('#update_plan_end_date').val(end_date);
            fetchPlanTypes(seat_no, user_id,learner_detail_id);
        });
     
       
        $('#plan_type_id, #plan_type_id2').on('change', function(event) {
           
            var plan_type_id = $(this).val();
            var plan_id = $('#plan_id').val();
            var change_plan_plan_id = $('#change_plan_plan_id').val();
            var plan_id2 = $('#plan_id2').val();
            var plan_id3 = $('#plan_id3').val();
          
            if((plan_type_id && plan_id)||(plan_type_id && plan_id2)||(plan_type_id && plan_id3)|(plan_type_id && change_plan_plan_id)){
            
                getPlanPrice(plan_type_id,plan_id);
                getPlanPrice(plan_type_id,plan_id2);
                getPlanPrice(plan_type_id,plan_id3);
                getPlanPrice(plan_type_id,change_plan_plan_id);
            }else{
                $("#plan_price_id").val('');
            }
           
        });
        $('#plan_id,#plan_id2').on('change', function(event) {
        
            event.preventDefault();
            var plan_id = $(this).val();
            var plan_type_id = $('#plan_type_id').val();
          
            if(plan_type_id && plan_id){
                getPlanPrice(plan_type_id,plan_id);
            }else{
                $("#plan_price_id").val('');
            }
        });

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
                       console.log('price',html);
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
        /**customer store page**/
        $(document).on('submit', '#seatAllotmentForm', function(event) {
            event.preventDefault();


            var formData = new FormData(this);
            var seat_no = $('#seat_no').val();
            var seat_id = $('#seat_id').val();
            var name = $('#name').val();
            var mobile = $('#mobile').val();
            var email = $('#email').val();
            var dob = $('#dob').val();
            var plan_id = $('#plan_id').val();
            var plan_type_id = $('#plan_type_id').val();
            var plan_start_date = $('#plan_start_date').val();
            var id_proof_name = $('#id_proof_name').val();
            var payment_mode = $('#payment_mode').val();
            var id_proof_file = $("#id_proof_file")[0].files[0];
            var plan_price_value = parseFloat($('#plan_price_id').val()) || 0;
            var paid_amount = parseFloat($('#paid_amount').val()) || 0;
            var locker_amount = parseFloat($('#locker_amount').val()) || 0;
           
            var due_date = $('#due_date').val();
            var locker_no = $('#locker_no').val();
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

            if (!email) {
                errors.email = 'Email Id is required.';
            } else if (!/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/.test(email)) {
                errors.email = 'Please enter a valid email address.';
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
           console.log('paid_amount',paid_amount);
           console.log('plan_price_value',plan_price_value);
           console.log('locker_amount',locker_amount);
           console.log('discount_amount',discount_amount);
            
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
            console.log('formData');
            var general_seat = $('#general_seat').val();
            formData.append('toggleFieldCheckbox', $('#toggleFieldCheckbox').val());

            formData.append('_token', '{{ csrf_token() }}');
            formData.append('plan_id', plan_id);
            formData.append('plan_type_id', plan_type_id);
            formData.append('id_proof_name', id_proof_name);
            formData.append('plan_start_date', plan_start_date);
            formData.append('paid_amount', paid_amount);
            formData.append('general_seat', general_seat);
            formData.append('discount_amount', discount_amount);
            
      
            $.ajax({
                url: '{{ route('learners.store') }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    console.log(response);
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

        // $(document).on('submit', '#upgradeForm', function(event) {
           
        //     event.preventDefault();
        //     var formData = new FormData(this);
        //     var seat_no = $('#update_seat_no').val();
        //     var user_id = $('#update_user_id').val();
        //     var plan_id = $('#update_plan_id').val();
       
          
        //     var plan_type_id = $('#updated_plan_type_id').val();
        //     var plan_price_id = $('#updated_plan_price_id').val();
        //     var errors = {};
        //     if (!plan_id) {
        //         errors.plan_id = 'Plan is required.';
        //     }
        //     if (!plan_type_id) {
        //         errors.plan_type_id = 'Plan Type is required.';
        //     }

        //     if (!plan_price_id) {
        //         errors.plan_price_id = 'Price is required.';
        //     }
   
        //     if (Object.keys(errors).length > 0) {
        //         $(".is-invalid").removeClass("is-invalid");
        //         $(".invalid-feedback").remove();

        //         $.each(errors, function(key, value) {
        //             var inputField = $("#" + key);
        //             inputField.addClass("is-invalid");
        //             inputField.after('<div class="invalid-feedback">' + value + '</div>');
        //         });
        //         return; // Exit the function if there are validation errors
        //     }

        //     formData.append('_token', '{{ csrf_token() }}');
        //     formData.append('seat_no', seat_no);
        //     formData.append('user_id', user_id);
        //     formData.append('plan_id', plan_id);
        //     formData.append('plan_type_id', plan_type_id);
        //     formData.append('plan_price_id', plan_price_id);
        //     var formId='renewSeat';
        //     var fieldName='plan';
        //     var newValue=plan_id ;
        //     var oldValue=$('#hidden_plan').val();


        //     $.ajax({
        //         url: '{{ route('learners.renew') }}', 
        //         type: 'POST',
        //         data: formData,
        //         processData: false,
        //         contentType: false,
        //         dataType: 'json',
        //         success: function(response) {
                    
        //             if (response.success) {
        //                 logFieldChange(user_id, formId, fieldName, oldValue, newValue); 
        //                 $("#success-message").text('Form submission successful').show();
        //                 $("#error-message").hide();
        //                 setTimeout(function() {
        //                     window.location.href = '{{ route('seats') }}';
        //                     location.reload(true); // Force reload from the server
        //                 }, 2000); // Delay for 2 seconds before redirecting
        //             } else if (response.errors) {
        //                 $(".is-invalid").removeClass("is-invalid");
        //                 $(".invalid-feedback").remove();
        //                 $("#error-message").hide();

        //                 $.each(response.errors, function(key, value) {
        //                     var inputField = $("input[name='" + key + "'], select[name='" + key + "']");
        //                     inputField.addClass("is-invalid");
        //                     inputField.after('<div class="invalid-feedback">' + value[0] + '</div>');
        //                 });
        //             } else {
        //                 $("#error-message").text(response.message).show();
        //                 $("#success-message").hide();
        //             }
        //         },
        //         error: function(xhr, status, error) {

        //             if (xhr.status === 422) {
        //                 var response = xhr.responseJSON;
        //                 $(".is-invalid").removeClass("is-invalid");
        //                 $(".invalid-feedback").remove();
        //                 $("#error-message").hide();

        //                 $.each(response.errors, function(key, value) {
        //                     var inputField = $("input[name='" + key + "'], select[name='" + key + "']");
        //                     inputField.addClass("is-invalid");
        //                     inputField.after('<div class="invalid-feedback">' + value[0] + '</div>');
        //                 });
        //             } else {
        //                 $("#error-message").text('Something went wrong. Please try again.').show();
        //                 $("#success-message").hide();
        //             }
        //         }
        //     });
        // });
         $(document).on('submit', '#upgradeForm', function(event) {
           
            event.preventDefault();
            var formData = new FormData(this);
         
            var user_id = $('#update_user_id').val();
            var plan_id = $('#plan_id2').val();
       
          
            var plan_type_id = $('#plan_type_id2').val();
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
                return; // Exit the function if there are validation errors
            }

            formData.append('_token', '{{ csrf_token() }}');
            // formData.append('seat_no', seat_no);
            // formData.append('user_id', user_id);
            // formData.append('plan_id', plan_id);
            // formData.append('plan_type_id', plan_type_id);
            // formData.append('plan_price_id', plan_price_id);
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
                        $("#success-message").text('Form submission successful').show();
                        $("#error-message").hide();
                        setTimeout(function() {
                            window.location.href = '{{ route('seats') }}';
                            location.reload(true); // Force reload from the server
                        }, 2000); // Delay for 2 seconds before redirecting
                    } else if (response.errors) {
                        $(".is-invalid").removeClass("is-invalid");
                        $(".invalid-feedback").remove();
                        $("#error-message").hide();

                        $.each(response.errors, function(key, value) {
                            var inputField = $("input[name='" + key + "'], select[name='" + key + "']");
                            inputField.addClass("is-invalid");
                            inputField.after('<div class="invalid-feedback">' + value[0] + '</div>');
                        });
                    } else {
                        $("#error-message").text(response.message).show();
                        $("#success-message").hide();
                    }
                },
                error: function(xhr, status, error) {

                    if (xhr.status === 422) {
                        var response = xhr.responseJSON;
                        $(".is-invalid").removeClass("is-invalid");
                        $(".invalid-feedback").remove();
                        $("#error-message").hide();

                        $.each(response.errors, function(key, value) {
                            var inputField = $("input[name='" + key + "'], select[name='" + key + "']");
                            inputField.addClass("is-invalid");
                            inputField.after('<div class="invalid-feedback">' + value[0] + '</div>');
                        });
                    } else {
                        $("#error-message").text('Something went wrong. Please try again.').show();
                        $("#success-message").hide();
                    }
                }
            });
        });
        $('#general_seat').on('change', function () {
           
            if ($(this).val() === 'no') {
                $('#seat_id').prop('disabled', false);
            } else {
                $('#seat_id').prop('disabled', true).val('yes');
                
                getTypeSeatwise('');
            }
           
        });
        $('#seat_id').on('change', function () {
            let newSeatId = $(this).val();
           
            getTypeSeatwise(newSeatId);
            $('#paid_amount').val("");
        });

       
        $('#new_seat_id').on('change', function(event) {
          
            event.preventDefault();
            var new_seat_id = $(this).val();
            var user_id = $('#user_id').val();
            var plan_type_id = $('#plan_type_id').val();
           
            // Clear previous status
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
                      console.log(html);
                        if(html == 1) {
                            $('#swap_status').append("Available");
                            $("#swapsubmit").prop('disabled', false); // Enable the submit button
                        } else {
                            $('#swap_status').append("Not Available");
                            $("#swapsubmit").prop('disabled', true); // Disable the submit button
                        }
                    }
                });
            }
        });
       
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
                    console.log(response);
                       
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

         $('#locker').on('change', function () {
           
            var needLocker = $(this).val();
            var planId     = $('#plan_id2').val();

            if (needLocker === 'yes') {
                
                $.get("{{ route('locker.price') }}", { plan_id: planId })
                .done(function(json) {
                    console.log(json.price);
                    $('#locker_amount2').val(json.price);
                    popupautoCalculatePaidAmount(); // ✅ call here AFTER value is set
                })
                .fail(function() {
                    $('#locker_amount2').val('').prop('readonly', true);
                    popupautoCalculatePaidAmount(); // optional if needed even on fail
                });
            } else {
                $('#locker_amount2').attr('readonly', true);
                $('#locker_amount2').val('');
               
                popupautoCalculatePaidAmount(); // ✅ call here when locker is disabled
            }
        });

         $('#discount_type').on('change', function (){
          popupautoCalculatePaidAmount();
         });
         $('#discount_amount3').on('input', function () {
            popupautoCalculatePaidAmount(); // Recalculate if amount changes
        });

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
                          console.log('learner_detail_id',html);
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
                            $('#seat_details_info').text('Booking Details of Seat No. : ' + html.seat_no);
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

        //  function fetchPlanTypes(seat_no, user_id,learner_detail_id) {
           
        //     if (seat_no && user_id) {
        //         $.ajax({
        //             url: '{{ route('gettypePlanwise') }}',
        //             headers: {
        //                 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        //             },
        //             type: 'GET',
        //             data: {
        //                 "_token": "{{ csrf_token() }}",
        //                 "seat_no": seat_no,
        //                 "user_id": user_id,
        //                 "learner_detail_id": learner_detail_id,
        //             },
        //             dataType: 'json',
        //             success: function (html) {
                     
        //                 $("#updated_plan_type_id").empty(); // Clear existing options
        //                 if (html[0]) {
        //                     $.each(html[0], function (key, value) {
        //                         $("#updated_plan_type_id").append('<option value="' + key + '">' + value + '</option>');
        //                     });
        //                 } else {
        //                     $("#updated_plan_type_id").append('<option value="">Select Plan Type</option>');
        //                 }
                       

        //                 if (html[1]) {
        //                     $.each(html[1], function (key, value) {
        //                         $('#update_plan_id > option[value="'+ key +'"]').prop('selected', true);
        //                         $("#hidden_plan").val(key);
        //                     });
        //                     $('#update_plan_id').trigger('change');
        //                 }



        //             },
        //             error: function (xhr, status, error) {
        //                 console.error("AJAX error:", status, error); // Log any errors
        //             }
        //         });
        //     } else {
        //         $("#updated_plan_type_id").empty();
        //         $("#updated_plan_type_id").append('<option value="">Select Plan Type</option>');
        //     }
        // }
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
                    
                        $("#plan_type_id2").empty(); 
                        $("#plan_id2").empty(); 
                        if (html[0]) {
                            $.each(html[0], function (key, value) {
                                $("#plan_type_id2").append('<option value="' + key + '">' + value + '</option>');
                            });
                        } else {
                            $("#plan_type_id2").append('<option value="">Select Plan Type</option>');
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
                $("#plan_type_id2").append('<option value="">Select Plan Type</option>');
            }
        }
        function getPlanPrice(plan_type_id,plan_id){
          
            if (plan_type_id && plan_id) {
                    $.ajax({
                        url: '{{ route('getPricePlanwise') }}',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                        },
                        type: 'GET',
                        data: {
                            "_token": "{{ csrf_token() }}",
                            "plan_type_id": plan_type_id,
                            "plan_id": plan_id,
                        },
                        dataType: 'json',
                        success: function(html) {
                            

                            if (html && html !== undefined) {
                            
                                $("#plan_price_id").val(html);
                                
                                autoCalculatePaidAmount(); 
                              
                                $("#error-message").hide();
                            } else {
                                $("#plan_price_id").val("");
                                $("#paid_amount").val("");
                            }
                        }

                    });
            } else {
                $("#plan_price_id").empty();
                $("#paid_amount").empty();
            
            }
        }
       
        function getTypeSeatwise(seatId) {
            
            $('#plan_type_id').empty().append('<option value="">Select Plan Type</option>');
            $.ajax({
                url: '{{ route('gettypeSeatwise') }}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "seatNo": seatId,
                },
                dataType: 'json',
                success: function (html) {
                    console.log('planType',html)
                    if (html) {
                        
                        // Keep the existing selected option
                        let selectedOption = $("#plan_type_id").find("option:selected");

                        // Empty the dropdown but keep the default option
                        $("#plan_type_id").empty();
                        $("#plan_type_id").append('<option value="">Select Plan Type</option>');

                        // Re-add the previously selected option
                        if (selectedOption.val() !== "") {
                            $("#plan_type_id").append('<option value="'+selectedOption.val()+'" selected>'+selectedOption.text()+'</option>');
                        }

                        // Append new options from the AJAX response
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
    });
        

</script>

<script>
    // From learner.blade.php
    function confirmSwap(customerId) {
        const form = document.getElementById(`swap-seat-form-${customerId}`);
        const oldSeat = document.getElementById(`old-seat-${customerId}`).value;
        const newSeatSelect = document.getElementById(`new-seat-${customerId}`);
        const newSeat = newSeatSelect.options[newSeatSelect.selectedIndex].text;

        // Confirm message with old seat and new seat details
        const confirmation = confirm(`Are you sure you want to swap from seat ${oldSeat} to seat ${newSeat}?`);

        if (confirmation) {
            form.submit();
        } else {
            // Reset the dropdown to prevent accidental changes
            newSeatSelect.value = '';
        }
    }

    $(document).on('click', '.delete-customer', function() {
        var id = $(this).data('id');
        var url = '{{ route('learners.destroy', ':id') }}';
    
        url = url.replace(':id', id);  
        var formId='deleteSeat';
        var fieldName='deleted_at';
        var newValue = new Date().toISOString()
        var oldValue = null;
       
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        logFieldChange(id, formId, fieldName, oldValue, newValue);
                        Swal.fire(
                            'Deleted!',
                            'User has been deleted.',
                            'success'
                        ).then(() => {
                            location.reload(); // Optionally, you can refresh the page
                        });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire(
                            'Error!',
                            'An error occurred while deleting the student.',
                            'error'
                        );
                    }
                });
            }
        });
    });

    $(document).on('click', '.link-close-plan', function() {
        const learner_id = this.getAttribute('data-id');
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
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, close it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST', // Use POST or PATCH for this type of operation
                    data: {
                        _token: '{{ csrf_token() }}',
                        learner_id: learner_id
                    },
                    success: function(response) {
                        logFieldChange(learner_id, formId, fieldName, oldValue, newValue);
                        Swal.fire(
                            'Closed!',
                            'The user plan has been closed.',
                            'success'
                        ).then(() => {
                            location.reload(); // Optionally reload the page after closing the plan
                        });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire(
                            'Error!',
                            'An error occurred while closing the plan.',
                            'error'
                        );
                    }
                });
            }
        });
    });
 
   
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var paidDateInput = document.getElementById('paid_date');
        if (paidDateInput && !paidDateInput.value) { // If no value is already set
            var today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format
            paidDateInput.value = today;
        }
    });
</script>
<script>
    // Function to handle changes in a specific form
    function handleFormChanges(formId, learnerId) {
       
         console.log('handleFormChanges called with formId:', formId, 'learnerId:', learnerId);

        const form = document.getElementById(formId);
        if (!form) {
            console.error('Form not found:', formId);
            return;
        }

        const changes = {}; // Object to store changes

        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            // Store initial value in a dataset attribute
            input.dataset.initialValue = input.value;

            input.addEventListener('change', function() {
                const fieldName = this.name;
                const oldValue = this.dataset.initialValue;
                const newValue = this.value;
                
                console.log(`Field changed: ${fieldName}, Old Value: ${oldValue}, New Value: ${newValue}`);
                
                // Only record the change if the value has actually changed
                if (oldValue !== newValue) {
                    changes[fieldName] = { oldValue, newValue };
                    this.dataset.initialValue = newValue; // Update initial value
                }
            });
        });

        // Add submit event listener to the form
       
        form.addEventListener('submit', function(event) {
            // Log all field changes at once before the form is submitted
            for (const fieldName in changes) {
                const { oldValue, newValue } = changes[fieldName];
                const swap_old_value=$('#swap_old_value').val();
               
                if (formId === 'reactive') {
                // Log changes only for the "seat_id" field
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

    
</script>
<script>
    function incrementMessageCount(id, type) {
        // Send AJAX request to the server to update the count
        fetch(`increment-message-count`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}' // Laravel CSRF token for security
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
</script>
<script>
    
  function autoCalculatePaidAmount() {
       
        const planPrice = parseFloat($('#plan_price_id').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount').val()) || 0;
        const discountType = $('#discountType').val();
         var discountAmt = parseFloat($('#discount_amount2').val()) || 0;
        var totalAmount = parseFloat($('#total_amount2').val()) || 0;
        let discountAmount = 0;
       
        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmount = discountRaw;
        }
         if (discountType !== 'percentage' && discountType !== 'amount') {
            $('#discount_amount').val("");
        }
      
         
        const autoPaid = planPrice + lockerAmount - discountAmount;
        $('#paid_amount').val(autoPaid.toFixed(2));

         var autoPaidnew;
        if (planPrice && lockerAmount && discountAmt) {
            autoPaidnew = planPrice + lockerAmount - discountAmt;
        }else if(planPrice && lockerAmount && discountAmount){
            autoPaidnew = planPrice + lockerAmount - discountAmount;
        } else if (planPrice && lockerAmount) {
            autoPaidnew = planPrice + lockerAmount;
        }else if (planPrice && discountAmt) {
            autoPaidnew = planPrice - discountAmt;
        } else {
            autoPaidnew = planPrice;
        }
        var difference =   autoPaidnew - totalAmount;
         $('#new_plan_price').val(autoPaidnew.toFixed(2));
        $('#diffrence_amount').val(difference.toFixed(2));
        calculatePendingAmount();
    }

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
        console.log('planPrice',planPrice);
        console.log('lockerAmount',lockerAmount);
        console.log('discountRaw',discountRaw);
        console.log('discountType',discountType);
        console.log('discountAmountt',discountAmountt);
        console.log('autoPaidnew',autoPaidnew);
        
         $('#new_plan_price2').val(autoPaidnew.toFixed(2));
        calculatePendingAmount();
    }


    function calculatePendingAmount() {
        const planPrice = parseFloat($('#plan_price_id').val()) || 0;
        const paidAmount = parseFloat($('#paid_amount').val()) || 0;
    
        const lockerAmount = parseFloat($('#locker_amount').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount').val()) || 0;
        const discountType = $('#discountType').val();

        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else {
            discountAmount = discountRaw;
        }

        const effectivePaid = planPrice+lockerAmount - discountAmount;
        const pendingAmount =effectivePaid-paidAmount;

        $('#pending_amt').html('Pending Amount: ' + pendingAmount.toFixed(2));

        if (pendingAmount > 0) {
            $('#due_date').removeAttr('readonly');
        } else {
            $('#due_date').attr('readonly', true);
        }
    }






</script>





