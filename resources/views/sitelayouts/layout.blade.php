<!DOCTYPE html>
<html lang="en">

<head>
    @php
    $current_route = Route::currentRouteName();
    $page=App\Models\Page::where('route',$current_route)->first();
    @endphp
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">

    <title>{{$page->meta_title ?? ''}}</title>

    {!! !empty($page->meta_og) ? $page->meta_og : '' !!}
    <meta name="description" content="{{ $page->meta_description ?? '' }}">

    <meta name="keywords" content="{{$page->meta_keyword ?? ''}}">


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/line-awesome/css/line-awesome.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="{{ asset('public/css/home-style.css')}}">

    <!-- Libraru Schema -->

    @php
    $schema = !empty($page->page_schema) ? html_entity_decode($page->page_schema) : '';
    @endphp

    {!! $schema !!}



</head>


<body>

    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>
    <div id="loader">
        <dotlottie-player src="https://lottie.host/db22cec8-bed8-4ce9-8993-e2c88bff2231/qJmiiH5Orw.lottie" background="transparent" speed="1" style="width: 150px; height: 150px" loop autoplay></dotlottie-player>
    </div>


    @include('sitelayouts.header')
    @yield('content')
    @include('sitelayouts.footer')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.21.0/jquery.validate.min.js" defer></script>
    <script src="{{ url('public/js/main-validation.js') }}" defer></script>

    <script>
        $(document).on('click', '.showmore', function() {
            var $planFeatures = $(this).closest('.plan-box').find('.plan-features');

            // Toggle the overflow-auto class
            $planFeatures.toggleClass('overflow-auto');

            // Change the button text between "Show More" and "Show Less"
            if ($planFeatures.hasClass('overflow-auto')) {
                $(this).text('Show Less');
            } else {
                $(this).text('Show More');
            }
        });
        $(document).ready(function() {

            var plan_mode = $('#plan_mode').find(":selected").val();
            $(".buy-now-btn").attr("data-plan_mode", plan_mode);
            subscription_price(plan_mode);

            $('#plan_mode').on('change', function() {
                var plan_mode = $(this).val();
                $(".buy-now-btn").attr("data-plan_mode", plan_mode);
                subscription_price(plan_mode);

            });

            function subscription_price(plan_mode) {

                if (plan_mode) {
                    $.ajax({
                        url: '{{ route('subscriptions.getSubscriptionPrice') }}',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                        },
                        type: 'GET',
                        data: {
                            "plan_mode": plan_mode
                        },
                        dataType: 'json',
                        success: function(response) {
                            // console.log('plan', response);
                            // Loop through each subscription price and dynamically update the HTML
                            response.subscription_prices.forEach(function(subscription) {
                                let modeText = plan_mode == 1 ? "<span>/month</span>" : plan_mode == 2 ? "<span>/year</span>" : "";

                                $('#subscription_fees_' + subscription.id).html('₹ ' + subscription.fees + modeText);

                                $('#plan_mode_' + subscription.id).val(plan_mode);
                                $('#price_' + subscription.id).val(subscription.fees);
                                let diff = subscription.slash_price - subscription.fees;
                                let slash_price = subscription.slash_price ? '<span class="slash">₹ ' + subscription.slash_price + '</span> <span class="save">(You Save ₹ ' + diff + ')</span>' : '';
                                $('#before_discount_fees_' + subscription.id).html(slash_price);
                                $('#planDescription_' + subscription.id).html(subscription.plan_description);


                            });

                        },
                        error: function(xhr) {
                            toastr.error('An error occurred. Please try again.');
                        }
                    });
                }
            }
        });
    </script>

    <script>
        $(window).on('load', function() {
            $('#loader').fadeOut('slow', function() {
                $(this).remove(); // Remove the loader from the DOM after fading out
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $(".buy-now-btn").click(function() {
                var planId = $(this).data("id");
                var planMode = $("#plan_mode").val(); // Get the selected plan mode from dropdown

                $.ajax({
                    url: "{{ route('store.selected.plan') }}", // Route to store in session
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        plan_id: planId,
                        plan_mode: planMode
                    },
                    success: function(response) {
                        console.log(response);
                        if (response.success) {
                            window.location.href = "{{ route('register') }}"; // Redirect to registration page
                        }
                    }
                });
            });
        });
    </script>


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
    </script>

    <script>
        $(document).ready(function() {

            let selectedSuggestion = '';
            let selectedCity = '';
            // Trigger search when user types in the search field
            $('#library-search').on('keyup', function() {
                let query = $(this).val();
                selectedCity = $('#city-item').val();
                if (query.length > 2) {
                    showSuggestions(query, selectedCity);
                    // fetchLibraries(query);
                } else {
                    // fetchLibraries(selectedSuggestion);
                    $('#suggestions').empty(); // Clear suggestions
                }
            });

            // Show suggestions based on the query input
            function showSuggestions(query, selectedCity) {
                $.ajax({
                    url: '{{ route("get-libraries") }}', // Laravel route for library search
                    method: 'GET',
                    data: {
                        query: query,
                        city: selectedCity,
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
                // fetchLibraries(selectedSuggestion); 
            });



            // Show Library Default Data
            function fetchLibraries(query = '', city = '') {
                var baseUrl = "{{ url('/') }}";
                $.ajax({
                    url: '{{ route("get-libraries") }}', // Laravel route to get libraries
                    method: 'GET',
                    data: {
                        query: query,
                        suggestion: selectedSuggestion,
                        city: city,
                    },
                    success: function(data) {
                        
                        $('#library-list1').empty(); // Clear the previous library results

                        if (data.length > 0) {
                            // Initialize Owl Carousel (destroy if already initialized)
                            if ($('#library-list1').hasClass('owl-carousel')) {
                                $('#library-list1').trigger('destroy.owl.carousel').removeClass('owl-carousel owl-loaded');
                                $('#library-list1').find('.owl-stage-outer').children().unwrap();
                            }

                            // Add Owl Carousel class
                            $('#library-list1').addClass('owl-carousel');

                            // Loop through each library and append it as a carousel item
                            $.each(data, function(index, library) {
                               
                                let libraryHTML = `
                                    <div class="item">
                                        
                                        <div class="featured-library">
                                            <img src="{{url('public/img/libraryImg.jpg')}}" class="library-image">
                                            <h4>${library.library_name}</h4>
                                            <span>${library.library_address || ''}</span>
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
                                            <a href="${baseUrl}/library-detail/${library.slug}" class="view-library " >View Details <i class="fa fa-long-arrow-right"></i></a>
    
                                        </div>
                                        
                                    </div>
                                    `;
                                $('#library-list1').append(libraryHTML);
                            });

                            // Re-initialize Owl Carousel after appending items
                            $('#library-list1').owlCarousel({
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
                                        items: 3,
                                        dots: true,
                                    }
                                }
                            });
                        } else {
                            letblankhtml =
                                `<div class="item">
                                <div class="featured-library">
                                    <h4>No library Found</h4>
                                </div>
                            </div>`;
                            $('#library-list1').append(letblankhtml);
                        }
                    }
                });
            }


            // Initial load of libraries (if no search/query)

            $(document).on('click', '#search-click', function() {
                let selectedCity = $('#city-item').val();
                let query = $('#library-search').val();
                fetchLibraries(query, selectedCity);
            });
            fetchLibraries('');

        });
    </script>



    <script>
        $(document).ready(function() {
            let counterRun = false; // Make sure it runs only once

            function runCounter() {
                $('.counter').each(function() {
                    var $this = $(this),
                        countTo = parseInt($this.attr('data-count'));

                    $({
                        countNum: 0
                    }).animate({
                        countNum: countTo
                    }, {
                        duration: 2000,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.floor(this.countNum) + '+');
                        },
                        complete: function() {
                            $this.text(countTo + '+');
                        }
                    });
                });
            }

            function isElementInView(el) {
                var elementTop = $(el).offset().top;
                var elementBottom = elementTop + $(el).outerHeight();
                var viewportTop = $(window).scrollTop();
                var viewportBottom = viewportTop + $(window).height();
                return elementBottom > viewportTop && elementTop < viewportBottom;
            }

            // Check on scroll and initial load
            function checkAndRunCounter() {
                if (!counterRun && isElementInView('.counter')) {
                    runCounter();
                    counterRun = true;
                }
            }

            // Run on load and scroll
            $(window).on('scroll load', checkAndRunCounter);
        });
    </script>




    <!-- Home Page Scripts -->
    <script>
        $('#mainSlider').owlCarousel({
            loop: true,
            nav: false,
            margin: 8,
            stagePadding: 250,
            autoplay: true,
            autoplaySpeed: 2000,
            smartSpeed: 2000,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            responsive: {
                0: {
                    items: 1,
                    nav: false,
                    stagePadding: 0,
                    margin: 20
                },
                768: {
                    items: 1,
                    nav: false
                },
                992: {
                    items: 1
                },
                1200: {
                    items: 1
                },
                1920: {
                    items: 1
                }
            }
        });


        $('#featureSlider').owlCarousel({
            loop: true,
            nav: false,
            margin: 20,
            autoplay: true,
            autoplaySpeed: 2000,
            smartSpeed: 2000,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            responsive: {
                0: {
                    items: 1,
                    nav: false
                },
                768: {
                    items: 2,
                    nav: false
                },
                992: {
                    items: 3
                },
                1200: {
                    items: 3
                },
                1920: {
                    items: 4
                }
            }
        });
    </script>

    <script>
        $('#clientsFeedbacks').owlCarousel({
            loop: true,
            nav: false,
            dots: true,
            margin: 20,
            pagination: true,
            autoplay: true,
            autoPlaySpeed: 2000,
            smartSpeed: 2000,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            responsive: {
                0: {
                    items: 1,
                },
                768: {
                    items: 2,
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
            $('#demoRequest').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);

                $.ajax({
                    url: '{{ route("demo-request") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log(response);

                        if (response.status === 'success') {
                            toastr.success(response.message);

                            // Clear error messages and reset form
                            $(".is-invalid").removeClass("is-invalid");
                            $(".invalid-feedback").remove();

                            // Optionally, reset the form after success
                            $('#demoRequest')[0].reset();
                            $("#error-message").hide();
                        } else {
                            $("#error-message").text(response.message).show();
                            $("#success-message").hide();
                        }
                    },
                    error: function(xhr) {
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
                            console.error('AJAX Error:', xhr.responseText);
                            alert('There was an error processing the request. Please try again.');
                        }
                    }
                });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            function updateCountdown() {
                var targetDate = new Date("April 25, 2025 23:59:59").getTime();
                var now = new Date().getTime();
                var timeLeft = targetDate - now;

                if (timeLeft <= 0) {
                    $('#countdown-timer').html("⏳ Offer Expired!");
                    clearInterval(timerInterval); // Stop countdown when expired
                    return;
                }

                var days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                var hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                $('#countdown-timer').html(
                    `⏳ Offer Ends In: <strong>${days}d ${hours}h ${minutes}m ${seconds}s</strong>`
                );
            }

            // Update every second
            var timerInterval = setInterval(updateCountdown, 1000);
            updateCountdown(); // Run immediately on page load
        });
    </script>
    <script>
        $(function() {
            var text = "Effortlessly manage your library from seat bookings to reporting all in one place!";
            var i = 0;

            function type() {
                if (i < text.length) {
                    $('#typing-text').append(text.charAt(i));
                    i++;
                    setTimeout(type, 50); // speed of typing
                }
            }

            type();
        });
    </script>
</body>

</html>