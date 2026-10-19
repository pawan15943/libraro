@extends('sitelayouts.layout')
@section('content')
<section class="inner-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h1>Contact Us</h1>
            </div>
        </div>
    </div>
</section>

<section class="breadcrumb">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Contact Us</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<section class="page-content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <p>We’re here to help you! Whether you have a question, need assistance, or want to discuss potential partnerships, feel free to reach out to us.</p>
                <h4>Corporate Office Address</h4>
                <p><i class="fa fa-map-marker-alt"></i> 955, Vinoba Bhave Nagar, Kota, <br>Landmark: New Balaji Computer Classes</p>
                <h6>Contact Us at:</h6>
                <p><i class="fa fa-phone me-2"></i> <a href="tel:+91-8114479678">+91-8114479678</a>, <a href="tel:+91-8386007688">+91-8386007688</a></p>
                <p><i class="fa fa-envelope me-2"></i> <a href="mailto:support@libraro.in">support@libraro.in</a></p>
                <h6>Partner of Techito India</h6>
                <p>We are proud to be a part of Techito India, delivering innovative IT solutions across various industries.</p>
            </div>
            <div class="col-lg-6">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3631.3765925056978!2d75.8345118!3d25.116712!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x396f845f15555555%3A0x638a21618de8530!2s955%2C%20Vinoba%20Bhave%20Nagar%2C%20Kota%2C%20Rajasthan%20324005!5e1!3m2!1sen!2sin!4v1737828277134!5m2!1sen!2sin" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
        <div class="row g-4 justify-content-center pt-5">
            @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            @endif
            <div class="col-lg-6">
                <h2 class="mb-4 text-center">Have Any Query ? <br>
                    Request a Callback</h2>
                <form  method="POST" id="inqueryForm">
                    @csrf
                    <div class="form-box">
                        <div class="row g-4">
                            <div class="col-lg-12">
                                <label for="name">Full Name<span class="text-danager">*</span></label>
                                <input type="text"
                                    name="full_name"
                                    class="form-control @error('full_name') is-invalid @enderror char-only"
                                    placeholder="Enter your Name"
                                    value="">
                             
                            </div>
                            <div class="col-lg-12">
                                <label for="mobile_number">Mobile Number<span class="text-danager">*</span></label>
                                <input type="text"
                                    name="mobile_number"
                                    class="form-control @error('mobile_number') is-invalid @enderror digit-only"
                                    placeholder="Enter Mobile Number"
                                    value="" minlength="8" maxlength="10">
                               
                            </div>
                            <div class="col-lg-12">
                                <label for="email">Email Id<span class="text-danager">*</span></label>
                                <input type="email"
                                    name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="Enter Email Address"
                                    value="">
                              
                            </div>
                            <div class="col-lg-12">
                                <label for="message">Message<span class="text-danager">*</span></label>
                                <textarea rows="5"
                                    name="message"
                                    class="form-control @error('message') is-invalid @enderror"
                                    placeholder="Enter Message"></textarea>
                               
                            </div>
                            <div class="col-lg-12 form-group">
                                <input type="checkbox" class="me-2 form-check-input " name="terms" id="terms" autocomplete="off">
                                <label class="form-check-label" for="terms">
                                    I agree to the Libraro <a href="#">Terms and Conditions.</a><sup class="text-danger">*</sup>
                                </label>
                                <div class="error-msg"></div>
                            </div>
                            <div class="col-lg-12">
                                <button class="btn btn-primary button" type="submit">Submit Details</button>
                            </div>
                        </div>
                    </div>
                </form>

            </div>

        </div>
    </div>
</section>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
    
        $('#inqueryForm').on('submit', function (e) {
            e.preventDefault();
           
            var formData = new FormData(this);
            $.ajax({
                url: '{{ route('submit.inquiry') }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    
                    
                    if (response.status === 'success') {
                        toastr.success(response.message);
                        
                        // Clear error messages and reset form
                        $(".is-invalid").removeClass("is-invalid");
                        $(".invalid-feedback").remove();
                        
                        // Optionally, reset the form after success
                        $('#inqueryForm')[0].reset(); 
                        $("#error-message").hide();
                    } else {
                        $("#error-message").text(response.message).show();
                        $("#success-message").hide();
                    }
                },
                error: function (xhr) {
                    var response = xhr.responseJSON;
                    
                    if (xhr.status === 422 && response.errors) { // Validation error check
                        $(".is-invalid").removeClass("is-invalid");
                        $(".invalid-feedback").remove();

                        $.each(response.errors, function(key, value) {
                            var element = $("[name='" + key + "']");
                            element.addClass("is-invalid");
                            element.after('<span class="invalid-feedback" role="alert">' + value[0] + '</span>');
                        });
                    } else {
                        console.log('AJAX Error: ', xhr.responseText);
                        alert('There was an error processing the request. Please try again.');
                    }
                }
            });


        });
    });
</script>

@endsection