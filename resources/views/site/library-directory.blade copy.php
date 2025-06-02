<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css"
        rel="stylesheet">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" />
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="stylesheet" href="{{ asset('public/css/style.css')}}">
</head>

<body>


    <!-- Section 1 -->
    <section class="hero_Section" style="background: url('{{ asset('/public/img/direcotry/bg-head.png') }}') no-repeat; background-size:cover;">
        <div class="container">
            <div class="row align-items-center mb-5">
                <div class="col-lg-6">
                    <img src="{{ asset('public/img/direcotry/logo.png') }}" alt="logo" class="logo">
                </div>
                <div class="col-lg-6">
                    <ul class="">
                        <li class="">
                            <a class="active" aria-current="page" href="#">Home</a>
                        </li>
                        <li class="">
                            <a class="" href="#">Features</a>
                        </li>
                        <li class="">
                            <a class="" href="#">Pricing</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row h-100 align-items-center">
                <div class="col-lg-12">
                    <div class="row align-items-center justify-content-center mt-5">
                        <div class="col-lg-8">
                            <h1>Hi! Can you help me find the nearest library?
                                I'd love to explore one nearby!</h1>

                            <div class="search">
                                <input type="text" id="library-search" class="form-control" placeholder="Enter Location or library name to search near you">
                                <i class="fa fa-search"></i>
                                <ul id="suggestions" class="list-group mt-2"></ul>
                            </div>
                        </div>

                    </div>
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <h4 class="text-white text-center py-3">Search Direct by City</h4>
                            <ul class="locations" id="city-list">

                                @foreach($cities as $key => $value)
                                <li class="city-item" data-city="{{ $key }}">
                                    <div class="location-box">
                                        <i class="fa fa-map-marker-alt"></i>
                                        <p>{{ $value }}</p>
                                    </div>
                                </li>
                                @endforeach

                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2 -->
    <section class="featured-library py-5">
        <div class="container">
            <div class="heading text-center mb-5">
                <h2>Top Libraries in KOTA</h2>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="owl-carousel owl-theme" id="library-list">
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Section 3 -->
    <section class="why-choose-us">
        <div class="container">
            <div class="heading mb-5">
                <span>Benefits of Product</span>
                <h2>Best Place to Search <br>
                    Library in INDIA</h2>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="benefits">
                        <i class="fa fa-check"></i>
                        <div class="content">
                            <p>Check what you
                                need to
                                achieve your goals.</p>
                            <div class="border-thick"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="benefits">
                        <i class="fa fa-check"></i>
                        <div class="content">
                            <p>Check what you
                                need to
                                achieve your goals.</p>
                            <div class="border-thick"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="benefits">
                        <i class="fa fa-check"></i>
                        <div class="content">
                            <p>Check what you
                                need to
                                achieve your goals.</p>
                            <div class="border-thick"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Customer's Feedback -->
    <section class="customer-experience">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about-main">
                        <img src="{{ asset('public/img/direcotry/about.png') }}" alt="about" class="img-fluid">
                        <img src="{{ asset('public/img/direcotry/about-upper.png') }}" alt="upper" class="upper">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="heading mb-5">
                        <span>Customer's Feedback</span>
                        <h2>What is your <br>
                            Learning Experience</h2>
                    </div>

                    <p>A library is more than just a place to study—it's a sanctuary for discovery, inspiration, and
                        personal growth. Whether you're preparing for exams, diving into research, or simply seeking a
                        peaceful escape among the pages of a book, everyone has their own reason for visiting a library.
                    </p>

                    <p>At Book My Library, we believe in breaking the conventional boundaries of what a library can be.
                        Our platform connects you with the best libraries near you, offering a variety of spaces
                        tailored to your needs. From spacious, beautifully designed study halls to cozy reading areas
                        stocked with diverse genres, we ensure you find the perfect environment to focus and thrive.</p>

                    <p>Libraries aren't just for studying; they're also a haven for relaxation and rejuvenation. Whether
                        you're looking to recharge your mind, find inspiration, or escape the daily grind, our libraries
                        provide a unique ambiance where you can concentrate on what truly matters.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="important-counts">
        <div class="container">
            <h2 class="mb-5">Important Factors That Make Us the Right Choice</h2>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6 col-6">
                    <h1>{{$library_count}}+</h1>
                    <p>Library Enrolled</p>
                </div>
                <div class="col-lg-3 col-md-6 col-6">
                    <h1>{{$learner_count}}+</h1>
                    <p>Learner Enrolled</p>
                </div>
                <div class="col-lg-3 col-md-6 col-6">
                    <h1>{{$city_count}}+</h1>
                    <p>Total Cities
                </div>
                <div class="col-lg-3 col-md-6 col-6">
                    <h1>{{$feedback_count}}+</h1>
                    <p>Customers trusts</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Customer's Feedback -->
    <section class="customer-feedback">

        <div class="container">
            <div class="heading mb-5">
                <span>Customer's Feedback</span>
                <h2>What Our <br>
                    Happy Customers Say’s</h2>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="owl-carousel owl-theme" id="feedback">
                        @foreach($happy_customer as $key => $value)
                        <div class="item">
                            <div class="feedback-box">
                                <div class="message">
                                    {{$value->description}}
                                </div>
                                <div class="customer-info">
                                    <img src="{{ asset('public/img/user.png') }}" alt="user" class="profile">
                                    <div class="customer-details">
                                        <h4>{{$value->library_address}}, {{$value->city_name}}</h4>
                                        @php
                                        $createdYear = \Carbon\Carbon::parse($value->created_at)->year;
                                        $currentYear = \Carbon\Carbon::now()->year;
                                        @endphp

                                        <span>
                                            Working from {{ $createdYear == $currentYear ? 'This' : $createdYear }} year{{ $createdYear == $currentYear ? '' : 's' }}
                                        </span>
                                    </div>
                                    <ul class="customer-ratings">
                                        <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                        <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                        <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                        <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                        <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </section>

    <section class="inquiry">


        <div class="container">
            <div class="row g-4 align-items-center">
                @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
                @endif
                <div class="col-lg-6">
                    <h2 class="mb-4">Have Any Query ? <br>
                        Request a Callback</h2>
                    <form action="{{ route('submit.inquiry') }}" method="POST">
                        @csrf
                        <div class="form-box">
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <label for="name">Full Name</label>
                                    <input type="text"
                                        name="full_name"
                                        class="form-control @error('full_name') is-invalid @enderror char-only"
                                        placeholder="Enter your Name"
                                        value="{{ old('full_name') }}">
                                    @error('full_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                                <div class="col-lg-12">
                                    <label for="mobile_number">Mobile Number</label>
                                    <input type="text"
                                        name="mobile_number"
                                        class="form-control @error('mobile_number') is-invalid @enderror digit-only"
                                        placeholder="Enter Mobile Number"
                                        value="{{ old('mobile_number') }}">
                                    @error('mobile_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                                <div class="col-lg-12">
                                    <label for="email">Email Id</label>
                                    <input type="email"
                                        name="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="Enter Email Address"
                                        value="{{ old('email') }}">
                                    @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                                <div class="col-lg-12">
                                    <label for="message">Message</label>
                                    <textarea rows="5"
                                        name="message"
                                        class="form-control @error('message') is-invalid @enderror"
                                        placeholder="Enter Message">{{ old('message') }}</textarea>
                                    @error('message')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                                <div class="col-lg-12">
                                    <button class="btn btn-primary button" type="submit">Submit Details</button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
                <div class="col-lg-6">
                    <div class="main-box">
                        <div class="support">
                            <img src="{{ asset('public/img/direcotry/call.png') }}" alt="call">
                            <h4>We Are Here
                                to Assist you</h4>
                            <p>Call : +91-8114479678</p>
                            <p>Mail : libraro@nbcc.com</p>
                        </div>
                        <img src="{{ asset('public/img/direcotry/support.png') }}" alt="support" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3">
                    <img src="{{ asset('public/img/direcotry/logo.png') }}" alt="logo" class="">
                </div>
                <div class="col-lg-3">
                    <h4>IMPORTANT LINKS</h4>
                    <ul>
                        <li><a href="">HOME</a></li>
                        <li><a href="">ABOUT</a></li>
                        <li><a href="">FAQ</a></li>
                        <li><a href="">BLOG</a></li>
                        <li><a href="">CONTACT</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h4>CONTACT US</h4>
                    <ul>
                        <li><a href="">PRIVACY POLICY</a></li>
                        <li><a href="">TERMS OF USE</a></li>
                        <li><a href="">REFUND POLICY</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h4>IMPORTANT LINKS</h4>
                    <ul class="social">
                        <li><a href=""><i class="fab fa-facebook"></i></a></li>
                        <li><a href=""><i class="fab fa-instagram"></i></a></li>
                        <li><a href=""><i class="fab fa-linkedin"></i></a></li>
                        <li><a href=""><i class="fab fa-youtube"></i></a></li>
                        <li><a href=""><i class="fab fa-twitter"></i></a></li>
                    </ul>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-lg-12">
                    <p class="py-1 text-center text-white m-0">@2025, All rights reserved</p>
                </div>
            </div>
        </div>


    </footer>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script>
        var elements = document.querySelectorAll('.digit-only');
        for (i in elements) {
            elements[i].onkeypress = function(e) {
                this.value = this.value.replace(/^0+/, '');
                if (isNaN(this.value + "" + String.fromCharCode(e.charCode)))
                    return false;
            }
            elements[i].onpaste = function(e) {
                e.preventDefault();
            }
        }
        $('.digit-only').on('keyup', function(e) {
            $(this).val($(this).val().replace(/\s/g, ''));
        });


        $('.char-only').keydown(function(e) {
            if (e.ctrlKey || e.altKey) {
                e.preventDefault();
            } else {
                var key = e.keyCode;
                if (!((key == 8) || (key == 32) || (key == 46) || (key >= 35 && key <= 40) || (key >= 65 && key <= 90))) {
                    e.preventDefault();
                }
            }
        });
        $('#feedback').owlCarousel({
            loop: true,
            nav: true,
            dots: true,
            margin: 20,
            navText: ['<i class="las la-angle-left arrow-left"></i>', '<i class="las la-angle-right arrow-right"></i>'],
            pagination: true,
            autoplay: true,
            autoPlaySpeed: 2000,
            smartSpeed: 2000,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            responsive: {
                0: {
                    items: 1,
                    nav: false,
                },
                768: {
                    items: 2,
                    nav: false,
                },
                992: {
                    items: 3,
                },
                1200: {
                    items: 3,
                },
                1920: {
                    items: 4,
                }
            }
        });
    </script>



    <script>
        $(document).ready(function() {
            let selectedSuggestion = ''; // Global variable to store selected suggestion

            // Trigger search when user types in the search field
            $('#library-search').on('keyup', function() {
                let query = $(this).val();

                // If the query length is more than 2 characters, show suggestions and fetch libraries
                if (query.length > 2) {
                    showSuggestions(query);
                    fetchLibraries(query);
                } else {
                    // If query length is less than 2, show the default libraries
                    fetchLibraries(selectedSuggestion);
                    $('#suggestions').empty(); // Clear suggestions
                }
            });

            // Show suggestions based on the query input
            function showSuggestions(query) {
                $.ajax({
                    url: '{{ route("get-libraries") }}', // Laravel route for library search
                    method: 'GET',
                    data: {
                        query: query
                    },
                    success: function(data) {
                        $('#suggestions').empty(); // Clear previous suggestions
                        if (data.length > 0) {
                            // Append the suggestions to the suggestion list
                            $.each(data, function(index, library) {
                                $('#suggestions').append('<li class="list-group-item suggestion-item" data-suggestion="' + library.library_name + '">' + library.library_name + ' - ' + library.library_address + '</li>');
                            });
                        } else {
                            $('#suggestions').append('<li class="list-group-item">No suggestions found</li>');
                        }
                    }
                });
            }

            // When a suggestion is selected, update the search field and fetch the libraries
            $(document).on('click', '.suggestion-item', function() {
                selectedSuggestion = $(this).data('suggestion'); // Set the selected suggestion (library name)
                $('#library-search').val($(this).text()); // Update search field with selected suggestion
                $('#suggestions').empty(); // Clear suggestions list
                fetchLibraries(selectedSuggestion); // Fetch libraries based on the selected suggestion
            });

            // Show Library Default Data
            function fetchLibraries(query) {
                $.ajax({
                    url: '{{ route("get-libraries") }}', // Laravel route to get libraries
                    method: 'GET',
                    data: {
                        query: query,
                        suggestion: selectedSuggestion
                    },
                    success: function(data) {
                        $('#library-list').empty(); // Clear the previous library results

                        if (data.length > 0) {
                            // Initialize Owl Carousel (destroy if already initialized)
                            if ($('#library-list').hasClass('owl-carousel')) {
                                $('#library-list').trigger('destroy.owl.carousel').removeClass('owl-carousel owl-loaded');
                                $('#library-list').find('.owl-stage-outer').children().unwrap();
                            }

                            // Add Owl Carousel class
                            $('#library-list').addClass('owl-carousel');

                            // Loop through each library and append it as a carousel item
                            $.each(data, function(index, library) {
                                let libraryHTML = `
                                <div class="item">
                                    <div class="library-box">
                                        <div class="image">
                                            <img src="{{ asset('public/img/direcotry/02.png') }}" alt="library">
                                            <ul class="d-flex g-2">
                                                <li><i class="fa fa-star"></i></li>
                                                <li><i class="fa fa-star"></i></li>
                                                <li><i class="fa fa-star"></i></li>
                                                <li><i class="fa fa-star"></i></li>
                                                <li><i class="fa fa-star"></i></li>
                                            </ul>
                                        </div>
                                        <div class="content p-3">
                                            <h4 class="mb-3">${library.library_name}</h4>
                                            <p>${library.library_address}</p>
                                        </div>
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



            $(document).on('click', '.city-item', function() {
                let selectedCity = $(this).data('city'); // Get the city from the data attribute
                console.log("city", selectedCity)
                // Fetch libraries based on the selected city
                fetchLibrariesByCity(selectedCity);
            });

            // Show Library Data According to City

            function fetchLibrariesByCity(city) {
                $.ajax({
                    url: '{{ route("get-libraries") }}', // Laravel route to get libraries
                    method: 'GET',
                    data: {
                        city: city
                    },
                    success: function(data) {
                        $('#library-list').trigger('destroy.owl.carousel'); // Destroy the existing instance
                        $('#library-list').empty(); // Clear the previous library results

                        if (data.length > 0) {
                            console.log("bycity", data);
                            // Append items
                            $.each(data, function(index, library) {
                                let libraryHTML = `
                                            <div class="item">
                                                <div class="library-box">
                                                    <div class="image">
                                                        <img src="{{ asset('public/img/direcotry/02.png') }}" alt="library">
                                                        <ul class="d-flex g-2">
                                                            <li><i class="fa fa-star"></i></li>
                                                            <li><i class="fa fa-star"></i></li>
                                                            <li><i class="fa fa-star"></i></li>
                                                            <li><i class="fa fa-star"></i></li>
                                                            <li><i class="fa fa-star"></i></li>
                                                        </ul>
                                                    </div>
                                                    <div class="content p-3">
                                                        <h4 class="mb-3">${library.library_name}</h4>
                                                        <p>${library.library_address || ''}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
                                $('#library-list').append(libraryHTML);
                            });

                            // Reinitialize the Owl Carousel
                            $('#library-list').owlCarousel({
                                items: 3,
                                loop: true,
                                margin: 30,
                                nav: true,
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
            // Initial load of libraries (if no search/query)
            fetchLibraries('');
        });
    </script>
    <script>
        $('.owl-carousel').trigger('destroy.owl.carousel');
        $('#library-list').owlCarousel({
            loop: true,
            nav: true,
            dots: true,
            margin: 20,
            navText: ['<i class="las la-angle-left arrow-left"></i>', '<i class="las la-angle-right arrow-right"></i>'],
            pagination: true,
            autoplay: true,
            autoPlaySpeed: 2000,
            smartSpeed: 2000,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            responsive: {
                0: {
                    items: 1,
                    nav: false,
                },
                768: {
                    items: 2,
                    nav: false,
                },
                992: {
                    items: 3,
                },
                1200: {
                    items: 3,
                },
                1920: {
                    items: 4,
                }
            }
        });
    </script>

</body>

</html>