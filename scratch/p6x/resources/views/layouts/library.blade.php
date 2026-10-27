<!DOCTYPE html>
<html lang="en" class="lp-html">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <title>Libraro : Library Management Software</title>
    <script>
        /* Panel theme (light by default) — applied before first paint. */
        (function () {
            try {
                var t = localStorage.getItem('lp-theme') === 'dark' ? 'dark' : 'light';
                document.documentElement.setAttribute('data-lp-theme', t);
                document.documentElement.setAttribute('data-theme', t);
                if (t === 'dark') document.documentElement.setAttribute('data-bs-theme', 'dark');
            } catch (e) {}
            /* Theme button works from the first paint (no wait for scripts) and switches in one frame. */
            document.addEventListener('click', function (e) {
                var b = e.target.closest && e.target.closest('#lpThemeBtn');
                if (!b) return;
                e.preventDefault(); e.stopImmediatePropagation();
                var r = document.documentElement, t = r.getAttribute('data-lp-theme') === 'dark' ? 'light' : 'dark';
                r.classList.add('lp-no-anim');
                r.setAttribute('data-lp-theme', t); r.setAttribute('data-theme', t);
                if (t === 'dark') r.setAttribute('data-bs-theme', 'dark'); else r.removeAttribute('data-bs-theme');
                try { localStorage.setItem('lp-theme', t); } catch (x) {}
                requestAnimationFrame(function () { requestAnimationFrame(function () { r.classList.remove('lp-no-anim'); }); });
                setTimeout(function () { document.dispatchEvent(new CustomEvent('lp:theme', { detail: t })); }, 0);
            }, true);
        })();
    </script>
    <link rel="preload" href="{{ asset('public/fonts/instrument-sans/instrument-sans-latin.woff2') }}" as="font" type="font/woff2" crossorigin>


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('public/vendor/fontawesome-free/css/all.min.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/css/style.css') }}?v={{ @filemtime(public_path('css/style.css')) }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Include DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css"
        rel="stylesheet" />
    <!-- Bootstrap Toggle CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/css/library-style.css') }}">
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="format-detection" content="telephone=no">
    <link rel="stylesheet" href="{{ asset('public/css/settlement-modal.css') }}?v={{ @filemtime(public_path('css/settlement-modal.css')) }}">
    <link rel="stylesheet" href="{{ asset('public/css/booking-modal.css') }}?v={{ @filemtime(public_path('css/booking-modal.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

    <link rel="stylesheet" href="{{ asset('public/css/login-brand-splash.css') }}?v={{ @filemtime(public_path('css/login-brand-splash.css')) }}">
    <link rel="stylesheet" href="{{ asset('public/css/lb-panel.css') }}?v={{ @filemtime(public_path('css/lb-panel.css')) }}">
    @stack('lp-styles')
</head>

<body class="lp">
    @if(session('show_brand_welcome_splash') || request()->has('splash') || request()->has('preview_splash'))
        @include('partials.brand-welcome-splash')
    @endif
    @include('learner.popup')
    <div id="loaderone" class="lp-loader" aria-hidden="true"><span></span></div>
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
    @php
    $current_route = Route::currentRouteName();
    $lib = getLibrary();
    $isPlanExpired = (!$inExtension_lib && (!empty($is_expire) || empty($is_renew_comp) || empty($checkSub) || optional($lib)->status != 1 || optional($lib)->is_paid != 1));
    $hasRightSidebar = ((optional($lib)->is_paid == 1 || $inExtension_lib) && (optional($lib)->status == 1 || $inExtension_lib) && !$isPlanExpired);
    @endphp
    <div class="library-dashbaord {{ $hasRightSidebar ? 'has-right-sidebar' : '' }}">
        <!-- Sidebar -->
        @include('partials.library-sidebar')
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <div class="content-area">
            <!-- Header -->
            @include('partials.library-header')

            <!-- Library Active Announcement Banner Just After Header -->
            @include('partials.library-announcement-banner')


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


        @php
        $lib = getLibrary();
        $isPlanExpired = (!$inExtension_lib && (!empty($is_expire) || empty($is_renew_comp) || empty($checkSub) || optional($lib)->status != 1 || optional($lib)->is_paid != 1));
        @endphp

        @if(((optional($lib)->is_paid == 1 || $inExtension_lib) && (optional($lib)->status == 1 || $inExtension_lib) && !$isPlanExpired))
        <aside class="right-sidebar libraro-right-sidebar lp-rail" aria-label="Quick actions">
            <span class="lp-rail-t">QUICK</span>
            <ul class="lp-rail-list quick-action-list">
                @can('has-permission', 'Book Seat')
                <li class="{{ $current_route == 'seat.book' ? 'active' : '' }}">
                    <a href="javascript:;" class="lp-qa lp-qa--primary noseat_popup" aria-label="Book seat"><x-lb-icon name="armchair" /></a>
                    <span class="lp-qa-tip">Book seat</span>
                </li>
                @endcan

                @can('has-permission', 'Search Learner')
                <li class="{{ $current_route == 'learner.search' ? 'active' : '' }}">
                    <a href="{{ route('learner.search') }}" class="lp-qa" aria-label="Search seat"><x-lb-icon name="search" /></a>
                    <span class="lp-qa-tip">Search seat</span>
                </li>
                @endcan

                @can('has-permission','QR Seat Booking')
                @if(getBranch()?->uuid && getBranch()?->upi_id)
                <li>
                    <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#branchQR" class="lp-qa" aria-label="Seat booking QR"><x-lb-icon name="qr-code" /></a>
                    <span class="lp-qa-tip">Seat booking QR</span>
                </li>
                @endif
                @endcan

                @can('has-permission', 'Add Daily Expense')
                <li class="{{ $current_route == 'add.expense.list' ? 'active' : '' }}">
                    <a href="{{ route('add.expense.list') }}" class="lp-qa" aria-label="Add expense"><x-lb-icon name="receipt" /></a>
                    <span class="lp-qa-tip">Add expense</span>
                </li>
                @endcan

                @can('has-permission', 'Genrate ID Card')
                <li class="{{ $current_route == 'learner.checklist' ? 'active' : '' }}">
                    <a href="{{ route('learner.checklist') }}" class="lp-qa" aria-label="Print bulk ID cards"><x-lb-icon name="id-card" /></a>
                    <span class="lp-qa-tip">Print bulk ID cards</span>
                </li>
                @endcan

                <li class="{{ $current_route == 'library.video-training' ? 'active' : '' }}">
                    <a href="{{ route('library.video-training') }}" class="lp-qa" aria-label="Video tutorial"><x-lb-icon name="video" /></a>
                    <span class="lp-qa-tip">{{ videoGet()->title ?? 'Video tutorial' }}</span>
                </li>

                @if(!in_array('28', toggleHideField()))
                <li class="{{ $current_route == 'library.feedback' ? 'active' : '' }}">
                    <a href="{{ route('library.feedback') }}" class="lp-qa" aria-label="Give feedback"><x-lb-icon name="message-square" /></a>
                    <span class="lp-qa-tip">Give your feedback</span>
                </li>
                @endif

                @if(!in_array('21', toggleHideField()))
                <li class="{{ $current_route == 'library.settings' ? 'active' : '' }}">
                    <a href="{{ route('library.settings') }}" class="lp-qa" aria-label="Library settings"><x-lb-icon name="settings" /></a>
                    <span class="lp-qa-tip">Library settings</span>
                </li>
                @endif
            </ul>

            <div class="lp-rail-foot right-sidebar-bottom">
                <button type="button" class="lp-ai ai-trigger-btn" id="libraroAiTriggerSidebar" onclick="toggleLibraroAiBox()" title="Libraro AI assistant" aria-label="Libraro AI assistant">
                    <x-lb-icon name="bot" /><small>AI</small>
                </button>
            </div>
        </aside>
        @endif

    </div>

    @if(session('branch_error'))
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: 'warning',
            title: 'Branch Changed',
            text: @json(session('branch_error')),
            confirmButtonText: 'Go to Dashboard'
        }).then(function (result) {
            if (result.isConfirmed) {
                window.location.href = @json(route('library.home'));
            }
        });
    });
    </script>
    @endif


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

    <div class="modal fade" id="branchQR" tabindex="-1" aria-labelledby="branchQRLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-sm">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Brnach QR Code</h1> <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if(getBranch()?->uuid)
                    <div class="card border-0 text-center p-4">
                        <div class="card-body">
                            <p>With the help of this QR code you can Book and Re-New Library Seats.</p>
                            <p class="text-muted mb-3">
                                <b>{{ getBranch()->name ?? 'Vikas Library' }}</b>
                            </p>

                            <div class="d-inline-block p-3 bg-light rounded">
                                <div id="qrPreview">
                                    {!! QrCode::size(250)->generate(route('qr.branch', getBranch()->uuid)) !!}
                                </div>
                            </div>

                            <p class="mt-3 mb-0 text-muted small">
                                Scan to join <b>{{ getBranch()->name ?? 'this Library' }}</b>
                            </p>
                        </div>
                        <div class="d-flex align-items-center justify-content-center gap-3">
                            @if(getBranch()?->uuid)
                            <a href="{{ route('branch.qr.pdf', getBranch()->uuid) }}" target="_blank" class="btn btn-sm btn-success d-flex align-items-center button" style="    padding: .5rem 1.2rem;">
                                <i class="bi bi-download me-1"></i>
                                Download
                            </a>
                            @endif
                            <a href="https://wa.me/?text={{ urlencode('Join the library: ' . route('qr.branch', getBranch()->uuid)) }}" target="_blank" class="btn btn-sm btn-outline-secondary d-flex align-items-center" style="    padding: .5rem 1.2rem;">
                                <i class="bi bi-whatsapp me-1"></i>
                                Share
                            </a>
                        </div>
                    </div>


                    @else
                    <p class="text-muted text-center mb-0">
                        QR code is not available for this branch.
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.21.0/jquery.validate.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js" defer></script>
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
    <script src="{{ asset('public/js/lb-panel.js') }}?v={{ @filemtime(public_path('js/lb-panel.js')) }}" defer></script>
    <script src="{{ url('public/js/main-validation.js') }}" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        flatpickr(".dob", {
            maxDate: "2010-01-01",
            disableMobile: "true",
            allowInput: true
        });

        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            disableMobile: "true",
            allowInput: true,
            static: true
        });
        flatpickr(".duedate", {
            dateFormat: "Y-m-d",
            disableMobile: "true",
            minDate: "today",
            allowInput: true,
            static: true
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
            $('#sidebar').on('click', function(e) {
                e.stopPropagation();
                if (window.innerWidth <= 991) {
                    $('.sidebar').toggleClass('sidebar-open-mob');
                    $('#sidebarBackdrop').toggleClass('active', $('.sidebar').hasClass('sidebar-open-mob'));
                } else {
                    $('.sidebar').toggleClass('w-120');
                }
            });

            $('#sidebar_mob, #sidebarBackdrop').on('click', function(e) {
                e.stopPropagation();
                if (window.innerWidth <= 991) {
                    $('.sidebar').removeClass('sidebar-open-mob');
                    $('#sidebarBackdrop').removeClass('active');
                } else {
                    $('.sidebar').addClass('w-120');
                }
            });

            // Close mobile sidebars when clicking outside on content area
            $(document).on('click', function(e) {
                if (window.innerWidth <= 991) {
                    if (!$(e.target).closest('.sidebar, #sidebar').length && $('.sidebar').hasClass('sidebar-open-mob')) {
                        $('.sidebar').removeClass('sidebar-open-mob');
                        $('#sidebarBackdrop').removeClass('active');
                    }
                    if (!$(e.target).closest('.right-sidebar, .control-right-sidebar').length && $('.right-sidebar').hasClass('right-sidebar-open-mob')) {
                        $('.right-sidebar').removeClass('right-sidebar-open-mob').addClass('hide-right-sidebar');
                        $('.control-right-sidebar').find('#sidebar_mob').removeClass('rotate-180');
                    }
                }
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
            function handleSidebarResize() {
                if ($(window).width() <= 991) {
                    $('.sidebar').removeClass('w-120');
                    $('.right-sidebar').addClass('hide-right-sidebar').removeClass('right-sidebar-open-mob');
                } else {
                    $('.sidebar').removeClass('sidebar-open-mob');
                    $('#sidebarBackdrop').removeClass('active');
                    $('.right-sidebar').removeClass('hide-right-sidebar right-sidebar-open-mob');
                }
            }

            // Run the function on window resize
            $(window).resize(function() {
                handleSidebarResize();
            });

            // Initial check when the page loads (do not toggle left sidebar state)
            if ($(window).width() <= 991) {
                $('.right-sidebar').addClass('hide-right-sidebar').removeClass('right-sidebar-open-mob');
            }
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
            const isMobile = window.innerWidth <= 991;
            if (isMobile) {
                $('.right-sidebar').addClass('hide-right-sidebar').removeClass('right-sidebar-open-mob');
            } else {
                $('.right-sidebar').removeClass('hide-right-sidebar right-sidebar-open-mob');
            }

            $('.control-right-sidebar').on('click', function(e) {
                e.stopPropagation();
                if ($('.right-sidebar').hasClass('right-sidebar-open-mob')) {
                    $('.right-sidebar').removeClass('right-sidebar-open-mob').addClass('hide-right-sidebar');
                    $(this).find('#sidebar_mob').removeClass('rotate-180');
                } else {
                    $('.right-sidebar').addClass('right-sidebar-open-mob').removeClass('hide-right-sidebar');
                    $(this).find('#sidebar_mob').addClass('rotate-180');
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Hide the page loader as soon as the page is ready
            $('#loaderone').fadeOut(150, function() {
                $(this).remove();
            });
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
        // clear filter button js
        const clearFilterBtn = document.getElementById('clearFilter');
        if (clearFilterBtn) {
            clearFilterBtn.addEventListener('click', function() {
                let form = this.closest('form');
                if (form) {
                    form.reset(); // reset form fields
                    window.location.href = form.action; // reload without filters
                }
            });
        }
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const generalSeat = document.getElementById('general_seat');
            const seatSelect = document.getElementById('seat_id');

            let seatChoices = null;
            if (seatSelect) {
                seatChoices = new Choices(seatSelect, {
                    removeItemButton: false,
                    shouldSort: false,
                    searchEnabled: true,
                    searchPlaceholderValue: 'Search seat number...',
                    itemSelectText: '',
                    placeholder: true,
                    placeholderValue: 'Choose Seat No.'
                });
                window.seatChoices = seatChoices;
            }

            function toggleSeat() {
                if (!seatChoices) return;
                if (generalSeat.value === 'yes') {
                    seatChoices.disable(); // ✅ disable Choices UI
                    seatChoices.removeActiveItems(); // removes selected value
                    seatSelect.value = '';
                } else {
                    seatChoices.enable(); // ✅ enable Choices UI
                }
            }

            // 🔹 Run on page load
            toggleSeat();

            // 🔹 Run on change
            generalSeat.addEventListener('change', toggleSeat);

        });


        document.addEventListener('DOMContentLoaded', function() {
            // For select
            const selectElement = document.getElementById('stateid');
            const choicesSelect = new Choices(selectElement, {
                removeItemButton: true,
            });

        });

        document.addEventListener('DOMContentLoaded', function() {
            // For select
            const selectElement = document.getElementById('seat_search');
            const choicesSelect = new Choices(selectElement, {
                removeItemButton: true,
            });

        });
        document.addEventListener('DOMContentLoaded', function() {
            // For select
            const selectElement = document.getElementById('state_id');
            const choicesSelect = new Choices(selectElement, {
                removeItemButton: true,
            });

        });
        document.addEventListener('DOMContentLoaded', function() {
            // For select
            const selectElement = document.getElementById('cityid');
            const choicesSelect = new Choices(selectElement, {
                removeItemButton: true,
            });

        });
        document.addEventListener('DOMContentLoaded', function() {
            // For select
            const selectElement = document.getElementById('duration');
            const choicesSelect = new Choices(selectElement, {
                removeItemButton: true,
            });

        });
    </script>
    <script>
        let cityChoices = null;

        document.addEventListener('DOMContentLoaded', function() {

            const citySelect = document.getElementById('city_id');

            // ❌ DO NOT reinitialize again
            cityChoices = new Choices(citySelect, {
                removeItemButton: false,
                searchEnabled: true,
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Select City'
            });

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

                    if (document.querySelector('.modal.show')) {
                        document.body.classList.add('modal-open');
                    }

                }, "image/jpeg", 0.8);
            });

            cancelCrop.addEventListener("click", function() {
                modal.style.display = "none";
                if (cropper) cropper.destroy();
                cropper = null;
                activeInput = null;
                activePreview = null;

                if (document.querySelector('.modal.show')) {
                    document.body.classList.add('modal-open');
                }
            });

        });
    </script>




    {{-- Learner operation pages (swap, upgrade, renew, change plan, edit, payments…): panel design layer, loaded after their own CSS --}}
    @if(request()->routeIs('learners.swap', 'learners.upgrade', 'learner.renew.plan', 'learner.change.plan', 'learners.edit.plan', 'learners.edit', 'learners.reactive', 'learner.payment', 'learner.other.payment', 'learner.pending.payment', 'learners.settlement.page', 'learners.show', 'learners.transactions', 'learner.expire'))
    <link rel="stylesheet" href="{{ asset('public/css/lb-ops.css') }}?v={{ @filemtime(public_path('css/lb-ops.css')) }}">
    @endif
</body>

</html>