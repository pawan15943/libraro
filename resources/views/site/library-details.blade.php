@extends('sitelayouts.layout')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<div class="modal fade" id="libraryEnquiry" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
               
                <form  method="POST" id="submitlibraryEnquiry">
                    @csrf
                    <div class="detailes">
                        <input type="hidden" name="library_id" value="{{$library->id}}" id="library_id">
                      

                        <div class="row g-4">
                            <div class="col-lg-6">
                                <label for="">Full Name <span>*</span></label>
                                <input type="text" class="form-control char-only" name="name" id="name">
                            </div>
                         
                            <div class="col-lg-6">
                                <label for="">Mobile Number <span>*</span></label>
                                <input type="text" class="form-control digit-only" maxlength="10" minlength="10" name="mobile" id="mobile">
                            </div>
                     
                            <div class="col-lg-6">
                                <label for="">Shift time</label>
                                <select id="shift_time" class="form-select" name="shift_time">
                                    <option value="">Select Plan Type</option>
                                    @foreach($libraryplantype as $key => $value)
                                    <option value="{{$key}}">{{$value}}</option>
                                    @endforeach

                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label for="enquiry">Enquiry <span>*</span></label>
                                <textarea id="enquiry" name="enquiry" class="form-control" rows="4" placeholder="Write your enquiry here..."></textarea>
                            </div>
                            
                        
                     
                        </div>
                 

                        <div class="row mt-2">
                            <div class="col-lg-4">
                                <input type="submit" class="btn btn-primary btn-block button" id="submit"
                                    value="Enquiry Now" autocomplete="off">
                            </div>
                        </div>

                    </div>
                </form>
            </div>

        </div>
    </div>
</div> 

<section class="libraryDetailedHeader">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="m-0">
                    {{ $library->name ?? 'N/A' }} <span>Libraro Verified</span>
                </h1>

                <h5>
                    {{ $library->library_address ?? 'Address not available' }} - {{ $library->library_zip ?? '' }}
                </h5>

                <h5>
                    {{ optional($library->city)->city_name ?? 'City not available' }}, {{ optional($library->state)->state_name ?? 'State not available' }}
                </h5>


                <ul class="controls">
                    <li>
                        <a href="#" id="enquireNow">
                            <i class="fa fa-envelope"></i>
                            <span>Enquire Now</span>
                        </a>
                    </li>
                    <li>
                        <a href="#reviewForm">
                            <i class="fa fa-edit"></i>
                            <span>Write a Review</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://wa.me/{{$library->mobile}}" target="_blank">
                            <i class="fab fa-whatsapp"></i>
                            <span>WhatsApp</span>
                        </a>
                    </li>

                </ul>
            </div>


        </div>
    </div>
</section>

<section class="libraryDetials">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <!-- Library Description -->
                @if(!empty($library->description))
                
                <h4 class="mt-0">Library Description </h4>

                <p class="m-0">{{$library->description}}</p>
      
                @endif
                <div class="row mt-4">
                    <div class="col-lg-12">
                        <ul class="library-anmities">
                            <li>
                                <span>Library Type</span>
                                <p>{{$library->library_category ?? ''}}</p>
                            </li>
                            <li>
                                <span>Library Capacity</span>
                                <p>{{$total_seat ?? ''}} Seats</p>
                            </li>
                            <li>
                                <span>Lockers</span>
                                <p>{{$library->locker_amount !=0 ? 'Yes' : 'Currently Unavailable'}}</p>
                               
                            </li>
                            <li>
                                <span>Operations Hourse</span>
                                @if($operating)
                                <p>{{ \Carbon\Carbon::parse($operating->start_time)->format('h:i A') }} to {{ \Carbon\Carbon::parse($operating->end_time)->format('h:i A') }}</p>
 
                                @else
                                   <p>NA</p> 
                                @endif

                            </li>
                            <li>
                                <span>Operating Days</span>
                                <p>{{$library->working_days ?? ''}}</p>
                            </li>
                        </ul>
                    </div>
                </div>


                <!-- Features -->
                @if($library->features)
              
                <h4 class="mt-5">Library Features</h4>
                @php
                $selectedFeatures = $library->features ? json_decode($library->features, true) : [];
                @endphp
                <ul class="libraryFeatures">
                    @foreach ($features as $feature)
                    @if (in_array($feature->id, $selectedFeatures ?? []))
                    <li>
                        <img src="{{ asset('public/'.$feature->image) }}" alt="Feature Image" width="50">
                        <span>{{ $feature->name }}</span>
                    </li>
                    @endif
                    @endforeach
                </ul>
         
                @endif
                <!-- Library Plans -->
                <h4 class="mt-5">Our Library Packages</h4>
                <div class="row g-4">
                    @foreach($our_package as $key => $value)  
                        <div class="col-lg-4">
                            <div class="plans-box">
                                <h4>{{$value->plan_type_name}}</h4>
                                <ul>
                                    <li>
                                        <span>Plan Type</span>
                                        <p>{{$value->plan_id == 12 ? 'Yearly' : 'Monthly'}}</p>
                                    </li>
                                    <li>
                                        <span>Validity</span>
                                        <p>{{$value->plan_name}}</p>
                                    </li>
                                    <li>
                                        <span>Plan Price</span>
                                        <p>{{$value->price}} INR</p>
                                    </li>
                                    <li>
                                        <span>Duration</span>
                                        <p>{{$value->slot_hours}} Hours</p>
                                    </li>
                                    <li class="w-100">
                                        <p class="opening-hours">{{ \Carbon\Carbon::parse($value->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($value->end_time)->format('h:i A') }}
                                        </p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endforeach
                  
                </div>

                <!-- Write a review -->
                <h4 class="mt-5">Write a Review</h4>
                
                <form  method="POST" id="reviewForm">
                        @csrf
                    <input type="hidden" name="library_id" value="{{$library->id}}">
                    <div class="row g-4 justify-content-center">
                        <div class="col-lg-6">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control char-only" placeholder="Enter Your Name" >
                        </div>
                        <div class="col-lg-6">
                            <label for="rating">Provide Rating</label>
                            <select id="rating" class="form-control form-select" name="rating" >
                                <option value="">Select Rating</option>
                                <option value="5">5 Star</option>
                                <option value="4">4 Star</option>
                                <option value="3">3 Star</option>
                                <option value="2">2 Star</option>
                                <option value="1">1 Star</option>
                            </select>
                        </div>
                        <div class="col-lg-12">
                            <label for="review">Write a Review</label>
                            <textarea id="review" class="form-control" name="comments"></textarea>
                        </div>
                        <div class="col-lg-12">
                            <input type="submit" class="nav-link button" value="Add My Review">
                        </div>
                    </div>
                </form>

                <!-- Write a review -->
                @if($learnerFeedback->isNotEmpty())
               
                <h4 class="mt-5">Learners Reviews</h4>
                <div class="row g-4 " >
                    @foreach($learnerFeedback as $key => $value)
                    <div class="col-lg-6">
                        <div class="review-box">
                            <img src="{{url('public/img/comma.png')}}" alt="comma" class="comma">
                            <p>{{$value->comments}}</p>
                            <ul class="customer-ratings">
                                @for($i = 0; $i < $value->rating; $i++)
                                <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                @endfor
                               
                            </ul>
                            <div class="d-flex mt-4">
                                <img src="" alt="">
                                <div class="leaner-info">
                                    <h4 class="m-0">{{$value->learner ? $value->learner->name : $value->name}}</h4>
                                    <span>Library Learner</span>
                                </div>
                            </div>

                        </div>
                    </div>
                    @endforeach
              
                </div>
                @else
                <div class="row g-4 justify-content-center">
                </div>
                @endif
            </div>

            <!-- Library Image -->
            <div class="col-lg-4">
                <div class="library-images">
                    <div class="main-image">
                        <img id="mainImage" src="{{url('public/img/library-image.jpg')}}" alt="libraryImage">
                    </div>
                    <ul class="thumb">
                        @if(!empty($library->library_images) && is_array(json_decode($library->library_images, true)))
                        @foreach(json_decode($library->library_images) as $key => $value)
                        <li><img class="thumb-img" src="{{ url('public/' . $value) }}" alt="Library Image"></li>
                        @endforeach
                       @else

                        <li><img class="thumb-img" src="{{url('public/img/library-image.jpg')}}" alt="libraryImage"></li>

                        <li><img class="thumb-img" src="{{url('public/img/02.jpg')}}" alt="libraryImage"></li>
                        <li><img class="thumb-img" src="{{url('public/img/library-image.jpg')}}" alt="libraryImage"></li>
                        <li><img class="thumb-img" src="{{url('public/img/library-image.jpg')}}" alt="libraryImage"></li> 
                        @endif
                    </ul>
                </div>

                <!-- location on Map -->
                @if (!empty($library->google_map) && Str::startsWith($library->google_map, 'https://www.google.com/maps/embed?'))
                <h4>Location On Map</h4>
                <div class="location">
                    <iframe src="{{ $library->google_map }}" width="100%" class="rounded" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            @endif
            
            </div>
        </div>
    </div>
</section>
<!-- Features Library -->
<section class="popular py-5">
    <input type="hidden" id="cityId" value="{{$library->city_id}}">
    <div class="container">
        <div class="heading mb-5">
            <h2>Featured & Popular Libraries</h2>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="owl-carousel" id="library-list">
                </div>
            </div>

        </div>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    $(document).ready(function() {
        // Set the first thumbnail as the default main image
        var firstImage = $(".thumb-img").first().attr("src");
        $("#mainImage").attr("src", firstImage);

        // On thumbnail click, change main image
        $(".thumb-img").click(function() {
            var imgSrc = $(this).attr("src");
            $("#mainImage").attr("src", imgSrc);
        });
    });
    $('#enquireNow').on('click', function() {
           
         
           $('#libraryEnquiry').modal('show');
         
       });
</script>
<script>
    $(document).ready(function() {
        $("#reviewForm").on('submit', function (e) {
            e.preventDefault(); 
    
            var formData = new FormData(this);
            formData.append('_token', '{{ csrf_token() }}');
            $.ajax({
                url: '{{ route('submit.review') }}',
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
                        $('#reviewForm')[0].reset(); 
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

        let city=$("#cityId").val();
        fetchLibrariesByCity(city) ;
        var baseUrl = "{{ url('/') }}";
        function fetchLibrariesByCity(city) {
                $.ajax({
                    url: '{{ route("get-libraries") }}', 
                    method: 'GET',
                    data: {
                        city: city
                    },
                    success: function(data) {
                        
                        $('#library-list').empty(); 
    
                        if (data.length > 0) {
                           
                            if ($('#library-list').hasClass('owl-carousel')) {
                                $('#library-list').trigger('destroy.owl.carousel').removeClass('owl-carousel owl-loaded');
                                $('#library-list').find('.owl-stage-outer').children().unwrap();
                            }
    
                            // Add Owl Carousel class
                            $('#library-list').addClass('owl-carousel');
    
                            // Loop through each library and append it as a carousel item
                            $.each(data, function(index, library) {
                                console.log("totalseata",library.hour.seats);
                                let libraryHTML = `
                                    <div class="item">
                                        <div class="featured-library">
                                            <h4>${library.library_name}</h4>
                                            <span>${library.library_address}</span>
                                            <ul class="star-ratings">
                                                <li><i class="fa fa-star"></i></li>
                                                <li><i class="fa fa-star"></i></li>
                                                <li><i class="fa fa-star"></i></li>
                                                <li><i class="fa fa-star"></i></li>
                                                <li><i class="fa fa-star"></i></li>
                                            </ul>
    
                                            <ul class="library-feature">
                                                <li>
                                                    <span>Pricing Plans</span>
                                                    <h5>${library.moonth==12 ? 'Yearly' : 'Monthly'}</h5>
                                                </li>
                                                <li>
                                                    <span>Library Type</span>
                                                    <h5>Public</h5>
                                                </li>
                                                <li>
                                                    <span>Avaialble Seats</span>
                                                    <h5 class="text-success">${library.hour.seats}</h5>
                                                </li>
                                                <li>
                                                    <h5 class="text-success">Verified</h5>
                                                </li>
                                            </ul>
                                            <a href="${baseUrl}/library-detail/${library.slug}" class="view-library city-item">View Details <i class="fa fa-long-arrow-right"></i></a>
    
                                        </div>
                                        
                                    </div>
                                    `;
                                $('#library-list').append(libraryHTML);
                            });
    
                            // Re-initialize Owl Carousel after appending items
                            $('#library-list').owlCarousel({
                                loop: true,
                                margin: 30,
                                nav: true,
                                dots: true,
                                autoplay: true,
                                autoplayTimeout: 3000,
                                autoplayHoverPause: true,
                                responsive: {
                                    0: {
                                        items: 1
                                    },
                                    600: {
                                        items: 2
                                    },
                                    1000: {
                                        items: 3
                                    }
                                }
                            });
                        } else {
                            $('#library-list').append('<p>No libraries found.</p>');
                        }
                    }
                });
            }
        });
    </script>

<script>
    $(document).ready(function () {
    
        $('#submitlibraryEnquiry').on('submit', function (e) {
            e.preventDefault();
           
            var formData = new FormData(this);
            $.ajax({
                url: '{{ route('submit.library.inquiry') }}',
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
                        $('#submitlibraryEnquiry')[0].reset(); 
                        $("#error-message").hide();
                        $('#libraryEnquiry').modal('hide');
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