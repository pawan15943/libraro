<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <title>Libraro : Library Management Software</title>

    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('public/css/style.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.css" />
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Include DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body>
    @include('learner.popup')
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>
    <div id="loaderone">
        <dotlottie-player src="https://lottie.host/db22cec8-bed8-4ce9-8993-e2c88bff2231/qJmiiH5Orw.lottie" background="transparent" speed="1" style="width: 150px; height: 150px" loop autoplay></dotlottie-player>
    </div>
    <!-- New Design Dahsbard Library -->
    <div class="support-container">
        <div class="support-icon" onclick="toggleSupportCard()">
            <i class="fa-solid fa-phone-volume"></i>
        </div>
        <div class="support-card" id="supportCard">
            <p style="color: var(--c1);"><strong><i class="fa-solid fa-phone-volume"></i> Contact Libraro At:</strong></p>
            <p>Phone: <a href="tel:+91-8114479678">+91-8114479678</a></p>
            <p>Email: <a href="mailto:info@libraro.com">info@libraro.com</a></p>
        </div>
    </div>

    <div class="library-dashbaord">
        <!-- Sidebar -->
        @include('partials.library-sidebar')

        <div class="content-area">
            <!-- Header -->
            @include('partials.library-header')


            <!-- Begin Page Content -->
            <div class="content">
                <div class="container-fluid">
                    @include('partials.breadcrumbs')
                    
                    @yield('content')
                   <script>
                        const sessionLifetime = @json(config('session.lifetime') * 60); // convert to seconds
                        const warningTime = sessionLifetime - 60; // popup 1 min before session ends

                        console.log("Session lifetime:", sessionLifetime);
                        console.log("Warning in:", warningTime, "seconds");

                        setTimeout(function () {
                            Swal.fire({
                                title: 'Session Expiring Soon',
                                text: 'Your session will expire in 1 minute. Please save your work or stay active.',
                                icon: 'warning',
                                confirmButtonText: 'Stay Logged In'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload(); // refresh session
                                }
                            });
                        }, warningTime * 1000);

                        
                    </script>

                </div>
            </div>


            <!-- Footer  -->
            @include('partials.footer')
        </div>
        @if(getLibrary()->is_paid == 1  && getLibrary()->status == 1)
        
        
        <div class="right-sidebar">
            <h4> QUICK ACTION</h4>
            <ul>
                <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Book Seat">
                    <a href="javascript:;" class=" noseat_popup"><i class="fa fa-chair fa-2x"></i></a>
                </li>
                <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Search Seart">
                    <a href="{{route('learner.search')}}"><i class="fa fa-search fa-2x"></i></a>
                </li>
                <!-- <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Renew Seat">
                    <a href=""><i class="fa fa-rotate-right fa-2x"></i></a>
                </li> -->
                <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Attendence">
                    <a href="{{route('attendance')}}"><i class="fa fa-user-tie fa-2x"></i></a>
                </li>
                <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Library Learner List">
                    <a href="{{ route('seats.history') }}"><i class="fa fa-list-check fa-2x"></i></a>
                </li>
                <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Give Your Feedback">
                    <a href="{{route('library.feedback')}}"><i class="fa fa-comment fa-2x"></i></a>
                </li>
               <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="{{ videoGet()->title }}">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#videoModal{{ videoGet()->id }}">
                        <i class="fa fa-video fa-2x"></i>
                    </a>
                </li>
                <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Library Settings">
                    <a href="{{route('library.settings')}}"><i class="fa fa-cog fa-2x fa-spin"></i></a>
                </li>
            </ul>
            <div class="control-right-sidebar">
                <i class="fa fa-angle-right" id="sidebar_mob"></i>
            </div>
        </div>
        @endif
    </div>


   
    <ul class="mobile-actions d-md-none">
        <li><a href="javascript:;" class=" noseat_popup">Book Seat</a></li>
        <li><a href="{{route('learner.search')}}">Search</a></li>
    </ul>
    <div class="modal fade" id="videoModal{{ videoGet()->id }}" tabindex="-1" aria-labelledby="videoModalLabel{{ videoGet()->id }}" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ videoGet()->video_titel }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            @if(videoGet()->video)
                <video width="100%" height="auto" controls>
                    <source src="{{ asset('public/uploade/' . videoGet()->video) }}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            @else
                <p>No video uploaded.</p>
            @endif
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.21.0/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.js"></script>

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script src="{{ url('public/js/main-scripts.js') }}"></script>
    <script src="{{ url('public/js/main-validation.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        flatpickr(".dob",{
            maxDate: "2010-01-01",
            disableMobile: "true"
        });

        flatpickr(".datepicker",{
            disableMobile: "true"
        });
        flatpickr(".duedate", {
            disableMobile: "true",        
            minDate: "today",             
        });
    </script>

    <script>
// Session Login manager
// document.addEventListener("DOMContentLoaded", function () {
//     // Get session lifetime in seconds
//     let sessionLifetime = @json(config('session.lifetime') * 60); // e.g. 2 min = 120
//     let warningTime = Math.max(sessionLifetime - 60, 0); // 1 min before expiry

//     const timerElement = document.getElementById("timer");

//     // ⏳ Start countdown timer
//     const countdown = setInterval(function () {
//         let minutes = Math.floor(sessionLifetime / 60);
//         let seconds = sessionLifetime % 60;

//         // Pad with zeros
//         timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

//         sessionLifetime--;

//         // ⏰ Show popup 1 minute before expiry
//         if (sessionLifetime === warningTime) {
//             Swal.fire({
//                 title: 'Session Expiring Soon',
//                 text: 'Your session will expire in 1 minute. Click below to stay logged in.',
//                 icon: 'warning',
//                 confirmButtonText: 'Stay Logged In'
//             }).then((result) => {
//                 if (result.isConfirmed) {
//                     location.reload(); // refreshes session
//                 }
//             });
//         }

//         // 🔒 Auto-logout or redirect after session expires
//         if (sessionLifetime < 0) {
//             clearInterval(countdown);
            
//         }
//     }, 1000);
// });




        $(document).ready(function() {
            // Attach event listeners for collapse events once
            $('[data-bs-toggle="collapse"]').each(function() {
                var $btn = $(this);
                var $icon = $btn.find('i.fa-angle-right');
                var targetCollapse = $btn.data('bs-target');

                $(targetCollapse).on('show.bs.collapse', function() {
                    $icon.addClass('rotate');
                });

                $(targetCollapse).on('hide.bs.collapse', function() {
                    $icon.removeClass('rotate');
                });
            });

            // Fix for initial state
            $('[data-bs-toggle="collapse"]').each(function() {
                var $btn = $(this);
                var $icon = $btn.find('i.fa-angle-right');
                var targetCollapse = $btn.data('bs-target');

                if ($(targetCollapse).hasClass('show')) {
                    $icon.addClass('rotate');
                } else {
                    $icon.removeClass('rotate');
                }
            });
        });
    </script>


    <!-- jQuery -->
    <script>
        $(document).ready(function() {
            $('#toggleIcon').click(function() {
                $('#idProofFields').slideToggle();

                if ($('#idProofFields').is(':visible')) {
                    $('#toggleIcon').removeClass('fa-plus').addClass('fa-minus');
                } else {
                    $('#toggleIcon').removeClass('fa-minus').addClass('fa-plus');
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('.info-icon').on('click', function() {
                $(this).next('.info-card').toggle();
            });
        });

        $(document).ready(function() {
            $('#sidebar').on('click', function() {
                $('.sidebar').toggleClass('w-120');
            });


        });
        $(document).ready(function() {
            $('#sidebar_mob').on('click', function() {
                $('.sidebar').toggleClass('w-120');
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $(document).on('selectstart', function(e) {
                if (!$(e.target).is('input, select, textarea')) {
                    e.preventDefault();
                }
            });

            $(document).on('mousedown', function(e) {
                if (!$(e.target).is('input, select, textarea')) {
                    e.preventDefault();
                }
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Function to show a popup
            function showOfflinePopup() {
                Swal.fire({
                    title: 'No Internet Connection',
                    text: 'Your internet connection is lost. Please check your connection.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }

            // Check if already offline on page load
            if (!navigator.onLine) {
                showOfflinePopup();
            }

            // Listen for offline and online events
            window.addEventListener('offline', function() {
                showOfflinePopup();
            });

            window.addEventListener('online', function() {
                Swal.fire({
                    title: 'Back Online',
                    text: 'Your internet connection has been restored.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            });
        });

        $(document).ready(function() {
            function addClassOnResize() {
                if ($(window).width() <= 480) {
                    $('.sidebar').addClass('w-120');
                } else {
                    $('.sidebar').removeClass('w-120');
                }
            }

            // Run the function on window resize
            $(window).resize(function() {
                addClassOnResize();
            });

            // Initial check when the page loads
            addClassOnResize();
        });
    </script>

    <script>
        $(document).ready(function() {
            function toggleSupportCard() {
                $('#supportCard').toggle();
            }

            $('.support-icon').on('click', function() {
                toggleSupportCard();
            });
        });

        $(element).tooltip("show");
    </script>
    <script>
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));
    </script>
    <script>
        
        $(document).ready(function() {
            const isMobile = window.innerWidth <= 768;
            if (isMobile) {
                $('.right-sidebar').addClass('hide-right-sidebar');
            } else {
                $('.right-sidebar').removeClass('hide-right-sidebar');
            }

            $('.control-right-sidebar').on('click', function() {
                $('.right-sidebar').toggleClass('hide-right-sidebar');
                $(this).find('#sidebar_mob').toggleClass('rotate-180');
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Ensure loader is visible first
            $('#loaderone').show();

            // Hide loader after a short delay or once content is ready
            setTimeout(function() {
                $('#loaderone').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 1000); // adjust delay as needed
        });
    </script>

    <script>
        // Prevent pinch zoom
        document.addEventListener('touchstart', function (e) {
            if (e.touches.length > 1) {
            e.preventDefault();
            }
        }, { passive: false });

        // Prevent double-tap zoom
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (e) {
            const now = new Date().getTime();
            if (now - lastTouchEnd <= 300) {
            e.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
    @include('learner.script')

</body>

</html>