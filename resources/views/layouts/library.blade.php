<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <title>Libraro : Library Management Software</title>


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('public/css/style.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Include DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css"
        rel="stylesheet" />
    <!-- Bootstrap Toggle CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/css/library-style.css') }}">
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="format-detection" content="telephone=no">
</head>

<body>
    @include('learner.popup')
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>
    <div id="loaderone">
        <dotlottie-player src="https://lottie.host/db22cec8-bed8-4ce9-8993-e2c88bff2231/qJmiiH5Orw.lottie" background="transparent" speed="1" style="width: 150px; height: 150px" loop autoplay></dotlottie-player>
    </div>
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
    <!-- New Design Dahsbard Library -->
    <div class="support-container">
        <div class="support-icon" onclick="toggleSupportCard()">
            <i class="fa-solid fa-phone-volume"></i>
        </div>
        <div class="support-card" id="supportCard" style="display:none;">
            <p><strong><i class="fa-solid fa-phone-volume"></i> Contact Libraro At:</strong></p>
            <p>Phone: <a href="tel:+91-8114479678">+91-8114479678</a></p>
            <p>Email: <a href="mailto:info@libraro.com">info@libraro.com</a></p>
        </div>
    </div>
    @php
    $current_route = Route::currentRouteName();
    @endphp
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
                    <div id="success-message-show" class="alert alert-success" style="display:none;"></div>
                    <div id="error-message-show" class="alert alert-danger" style="display:none;"></div>
                    @yield('content')
                    <script>
                        const sessionLifetime = @json(config('session.lifetime') * 60); // convert to seconds
                        const warningTime = sessionLifetime - 60; // popup 1 min before session ends

                        // console.log("Session lifetime:", sessionLifetime);
                        // console.log("Warning in:", warningTime, "seconds");

                        setTimeout(function() {
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


        @if(getLibrary()->is_paid == 1 && getLibrary()->status == 1)
        <div class="right-sidebar">
            <h4> QUICK ACTION</h4>
            <ul>

                @can('has-permission', 'Book Seat')
                <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Book Seat"
                    class="{{ $current_route == 'seat.book' ? 'active' : '' }}">
                    <a href="javascript:;" class="noseat_popup">
                        <i class="fa fa-chair fa-2x"></i>
                    </a>
                </li>
                @endcan

                @can('has-permission', 'Search Learner')
                <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Search Seat"
                    class="{{ $current_route == 'learner.search' ? 'active' : '' }}">
                    <a href="{{ route('learner.search') }}">
                        <i class="fa fa-search fa-2x"></i>
                    </a>
                </li>
                @endcan

                @can('has-permission','QR Seat Booking')
                @if(getBranch()?->uuid && getBranch()?->upi_id)
                <li>
                    <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#branchQR" data-bs-title="Seat Booking QR"><i class="fa fa-qrcode fa-2x"></i></a>
                </li>
                @endif
                @endcan

                @can('has-permission', 'Add Daily Expense')
                <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Add Expense"
                    class="{{ $current_route == 'add.expense.list' ? 'active' : '' }}">
                    <a href="{{ route('add.expense.list') }}">
                        <i class="fa fa-plus fa-2x"></i>
                    </a>
                </li>
                @endcan

                <!-- <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Library Learner List"
                    class="{{ $current_route == 'seats.history' ? 'active' : '' }}">
                    <a href="{{ route('seats.history') }}">
                        <i class="fa fa-list-check fa-2x"></i>
                    </a>
                </li> -->

                @can('has-permission', 'Genrate ID Card')
                <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Print Bulk ID CARD"
                    class="{{ $current_route == 'learner.checklist' ? 'active' : '' }}">
                    <a href="{{ route('learner.checklist') }}">
                        <i class="fa fa-id-card-clip fa-2x"></i>
                    </a>
                </li>
                @endcan

                <li data-bs-toggle="tooltip" data-bs-placement="left"
                    data-bs-title="{{ videoGet()->title ?? 'Video Tutorial' }}"
                    class="{{ $current_route == 'library.video-training' ? 'active' : '' }}">
                    <a href="{{ route('library.video-training') }}">
                        <i class="fa fa-video fa-2x"></i>
                    </a>
                </li>

                @if(!in_array('28', toggleHideField()))
                <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Give Your Feedback"
                    class="{{ $current_route == 'library.feedback' ? 'active' : '' }}">
                    <a href="{{ route('library.feedback') }}">
                        <i class="fa fa-comment fa-2x"></i>
                    </a>
                </li>
                @endif

                @if(!in_array('21', toggleHideField()))
                <li data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Library Settings"
                    class="{{ $current_route == 'library.settings' ? 'active' : '' }}">
                    <a href="{{ route('library.settings') }}">
                        <i class="fa fa-cog fa-2x fa-spin"></i>
                    </a>
                </li>
                @endif
            </ul>

            <div class="control-right-sidebar">
                <i class="fa fa-angle-right" id="sidebar_mob"></i>
            </div>
        </div>
        @endif

        <style>
            /* Highlight active quick action */
            .right-sidebar ul li.active a {
                color: #0d6efd;
                /* Bootstrap primary */
            }

            .right-sidebar ul li.active i {
                color: #0d6efd;
                transform: scale(1.1);
                transition: all 0.3s ease;
            }
        </style>

    </div>




    @php
    $video = videoGet();
    @endphp

    @if($video)
    <div class="modal fade" id="videoModal{{ $video->id }}" tabindex="-1" aria-labelledby="videoModalLabel{{ $video->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $video->video_titel ?? 'Untitled Video' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    @if(!empty($video->video))
                    <video width="100%" height="auto" controls>
                        <source src="{{ asset('public/uploade/' . $video->video) }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    @else
                    <p>No video uploaded.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif


    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script> <!-- Keep jQuery first -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.21.0/jquery.validate.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js" defer></script>
    {{-- <script src="https://cdn.datatables.net/2.1.6/js/dataTables.js" defer></script> --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js" defer></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <!-- DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="{{ url('public/js/main-scripts.js') }}" defer></script>
    <script src="{{ url('public/js/main-validation.js') }}" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>


    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        flatpickr(".dob", {
            maxDate: "2010-01-01",
            disableMobile: "true",
            allowInput: true
        });

        flatpickr(".datepicker", {
            disableMobile: "true",
            allowInput: true
        });
        flatpickr(".duedate", {
            disableMobile: "true",
            minDate: "today",
            allowInput: true
        });
    </script>

    <script>
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
                $('#toggleIcon').toggleClass('fa-plus fa-minus');
            });
            $('.idProofFields1').hide(); // smooth fade in/out
            $('.toggleIcon1').click(function() {
                $('.idProofFields1').fadeToggle(300); // smooth fade in/out
                $('.toggleIcon1').toggleClass('fa-plus fa-minus');
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
                if ($(window).width() <= 840) {
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
        function toggleSupportCard() {
            $('#supportCard').toggle();
        }
    </script>

    <!-- Right Sidebar -->
    <script>
        $(document).ready(function() {
            const isMobile = window.innerWidth <= 840;
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
        document.addEventListener('touchstart', function(e) {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, {
            passive: false
        });

        // Prevent double-tap zoom
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(e) {
            const now = new Date().getTime();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
    @include('learner.script')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#filterContainer').hide(); // Initially hide the filter container
            $('#countsContainer').hide(); // Initially hide the counts container

            $('#filter').on('click', function(e) {

                e.preventDefault(); // Prevent default action
                $('#filterContainer').toggle(); // Toggle visibility
            });

            $('#counts').on('click', function(e) {
                e.preventDefault(); // Prevent default action
                $('#countsContainer').toggle(); // Toggle visibility
            });
        });

        // clear filter button js
        document.getElementById('clearFilter').addEventListener('click', function() {
            let form = this.closest('form');
            form.reset(); // reset form fields
            window.location.href = form.action; // reload without filters
        });
    </script>



    <script>
        document.addEventListener("DOMContentLoaded", function() {

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
                    return {
                        width: 350,
                        height: 200,
                        ratio: 350 / 200
                    };
                }
                return {
                    width: 200,
                    height: 200,
                    ratio: 1
                };
            }

            document.querySelectorAll(".image-cropper").forEach(input => {

                input.addEventListener("change", function() {

                    const file = this.files[0];
                    if (!file) return;

                    activeInput = this;
                    activePreview = findPreview(this);

                    const reader = new FileReader();
                    reader.onload = function() {

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

            cropBtn.addEventListener("click", function() {
                if (!cropper || !activeInput) return;

                const cfg = getCropConfig(activeInput);

                const canvas = cropper.getCroppedCanvas({
                    width: cfg.width,
                    height: cfg.height,
                    imageSmoothingQuality: "high",
                });

                canvas.toBlob(blob => {

                    const file = new File([blob], "cropped.jpg", {
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

            cancelCrop.addEventListener("click", function() {
                modal.style.display = "none";
                if (cropper) cropper.destroy();
                cropper = null;
                activeInput = null;
                activePreview = null;
            });

        });
    </script>




</body>

</html>