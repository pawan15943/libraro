<!DOCTYPE html>
<html lang="en" class="lp-html">

<head>
    <link rel="preload" href="{{ asset('public/fonts/geist/Geist-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('public/css/lb-font.css') }}?v={{ @filemtime(public_path('css/lb-font.css')) }}">
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
    <link rel="preload" href="{{ asset('public/fonts/geist/Geist-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>


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
    @include('lb.partials.timepicker-assets')
    @stack('lp-styles')
    {{-- clean pages: explanatory lines hidden, counts/shifts/filters behind icons --}}
    <link rel="stylesheet" href="{{ asset('public/css/lb-clean.css') }}?v={{ @filemtime(public_path('css/lb-clean.css')) }}">
    <script src="{{ asset('public/js/lb-viewtoggle.js') }}?v={{ @filemtime(public_path('js/lb-viewtoggle.js')) }}" defer></script>
</head>

<body class="lp">
    <script>
        /* Restore the saved sidebar / quick-rail state before anything paints, so a collapsed
           sidebar never flashes open while the page loads (desktop only, same keys as lb-panel.js). */
        try { if (window.innerWidth > 991 && localStorage.getItem('lp-rail') === 'closed') document.body.classList.add('lp-rail-closed'); } catch (e) {}
    </script>
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
                    @unless (request()->routeIs('library.home', 'home'))
                    @include('partials.breadcrumbs')
                    @endunless
                    @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                    @endif
                    @if (session('success'))
                    {{-- shown as a toast (see #lpToastHost); kept in the DOM, hidden, for any script that reads it --}}
                    <div class="alert alert-success lp-flash-to-toast" hidden>{{ session('success') }}</div>
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
        <aside class="right-sidebar libraro-right-sidebar lp-rail" id="lpRail" aria-label="Quick actions">
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
                <button type="button" class="lp-ai ai-trigger-btn" id="libraroAiTriggerSidebar" onclick="toggleLibraroAiBox()" title="Ask Limo" aria-label="Ask Limo, the Libraro AI assistant">
                    @include('partials.limo-avatar', ['uid' => 'rail'])<small>Limo</small>
                </button>
            </div>
        </aside>
        {{-- Show / hide the quick rail (desktop: remembered; phones: slides in over the page) --}}
        <button type="button" class="lp-rail-toggle" id="lpRailToggle" aria-controls="lpRail" aria-expanded="true" aria-label="Hide quick actions" title="Hide quick actions">
            <x-lb-icon name="chevron-right" />
        </button>
        <div class="lp-rail-backdrop" id="lpRailBackdrop" aria-hidden="true"></div>
        <script>
            try { if (window.innerWidth > 991 && localStorage.getItem('lp-rail') === 'closed') document.body.classList.add('lp-rail-closed'); } catch (e) {}
        </script>
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

    <div class="modal fade lqr" id="branchQR" tabindex="-1" aria-labelledby="branchQRLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="lqr-head">
                    <span class="lqr-ic"><x-lb-icon name="qr-code" /></span>
                    <div>
                        <h2 id="branchQRLabel">Seat booking QR</h2>
                        <small>{{ getBranch()?->display_name ?: (getBranch()?->name ?? 'Your library') }}</small>
                    </div>
                    <button type="button" class="lqr-x" data-bs-dismiss="modal" aria-label="Close"><x-lb-icon name="x" /></button>
                </div>

                <div class="lqr-body">
                    @if(getBranch()?->uuid)
                        @php
                            $lqrUrl = route('qr.branch', getBranch()->uuid);
                            $lqrApp = route('qr.attendance.link');
                        @endphp
                        <div class="lqr-card">
                            <div id="qrPreview">
                                {!! QrCode::size(220)->margin(0)->generate($lqrUrl) !!}
                            </div>
                            <span class="lqr-badge"><x-lb-icon name="armchair" />Book &amp; Re-new</span>
                        </div>

                        <p class="lqr-msg">With scanning of this QR you can <b>Book &amp; Re-new Seat</b> — no login needed.</p>

                        <div class="lqr-acts">
                            <a href="{{ route('branch.qr.pdf', getBranch()->uuid) }}" target="_blank" class="lp-btn noLoader"><x-lb-icon name="download" />Download poster</a>
                            <a href="https://wa.me/?text={{ urlencode('Book or renew your seat at ' . (getBranch()->display_name ?: getBranch()->name) . ': ' . $lqrUrl) }}" target="_blank" rel="noopener" class="lp-btn lp-btn--ghost noLoader"><x-lb-icon name="message-circle" />Share</a>
                            <button type="button" class="lp-btn lp-btn--ghost lqr-copy" data-copy="{{ $lqrUrl }}" title="Copy booking link"><x-lb-icon name="copy" /><span>Copy link</span></button>
                        </div>

                        <div class="lqr-app">
                            <span class="lqr-app-ic"><x-lb-icon name="smartphone" /></span>
                            <div>
                                <b>Attendance App</b>
                                <small>Members mark entry &amp; exit from their phone.</small>
                            </div>
                            <a href="https://wa.me/?text={{ urlencode('Download the Attendance App and mark your attendance: ' . $lqrApp) }}" target="_blank" rel="noopener" class="lqr-copy noLoader" title="Share on WhatsApp" aria-label="Share Attendance App on WhatsApp"><x-lb-icon name="share-2" /></a>
                            <button type="button" class="lqr-copy" data-copy="{{ $lqrApp }}" title="Copy app link" aria-label="Copy Attendance App link"><x-lb-icon name="copy" /></button>
                        </div>
                    @else
                        <p class="lqr-msg">QR code is not available for this branch.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('click', function (e) {
            var b = e.target.closest('.lqr-copy[data-copy]');
            if (!b) return;
            var t = b.dataset.copy, done = function () { b.classList.add('is-done'); setTimeout(function () { b.classList.remove('is-done'); }, 1400); };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(t).then(done); return; }
            var a = document.createElement('textarea'); a.value = t; a.style.cssText = 'position:fixed;opacity:0';
            document.body.appendChild(a); a.select();
            try { document.execCommand('copy'); done(); } catch (err) {}
            a.remove();
        });
    </script>


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
                    // PDFs and other non-images are uploaded as-is (they cannot be cropped)
                    if (file.type && !/^image\//i.test(file.type)) return;

                    activeInput = this;
                    const cropTitle = modal.querySelector('h5');
                    if (cropTitle) cropTitle.textContent = this.classList.contains('id_proof_file') ? 'Crop ID proof' : 'Crop profile photo';
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

            /*
             * lpCrop(file, { width, height, title }) → Promise<File|null>: the same crop popup for
             * pages that manage their own files (branch logo, library photos). Resolves with the
             * file cut to exactly width × height, or null when cancelled.
             */
            let pendingCrop = null;
            window.lpCrop = function(file, opts) {
                opts = opts || {};
                return new Promise(function(resolve) {
                    if (!file || !/^image\//i.test(file.type || '')) { resolve(null); return; }
                    if (pendingCrop) pendingCrop.resolve(null);
                    pendingCrop = { resolve: resolve, opts: opts, type: file.type };
                    activeInput = null; activePreview = null;
                    const cropTitle = modal.querySelector('h5');
                    if (cropTitle) cropTitle.textContent = opts.title || 'Crop image';
                    const reader = new FileReader();
                    reader.onload = function() {
                        cropperImage.onload = () => {
                            if (cropper) cropper.destroy();
                            cropper = new Cropper(cropperImage, { aspectRatio: opts.width / opts.height, viewMode: 1, autoCropArea: 1, responsive: true });
                        };
                        cropperImage.src = reader.result;
                        modal.style.display = "flex";
                    };
                    reader.readAsDataURL(file);
                });
            };
            function closeCrop() {
                modal.style.display = "none";
                if (cropper) cropper.destroy();
                cropper = null;
                if (document.querySelector('.modal.show')) document.body.classList.add('modal-open');
            }

            cropBtn.addEventListener("click", function() {
                if (cropper && pendingCrop) {
                    const job = pendingCrop; pendingCrop = null;
                    // PNG keeps a transparent logo background; everything else becomes JPEG
                    const type = job.type === 'image/png' ? 'image/png' : 'image/jpeg';
                    const canvas = cropper.getCroppedCanvas({ width: job.opts.width, height: job.opts.height, imageSmoothingQuality: "high", fillColor: type === 'image/jpeg' ? '#fff' : 'transparent' });
                    canvas.toBlob(blob => {
                        closeCrop();
                        job.resolve(blob ? new File([blob], 'cropped.' + (type === 'image/png' ? 'png' : 'jpg'), { type: type, lastModified: Date.now() }) : null);
                    }, type, job.opts.quality || 0.85);
                    return;
                }
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
                if (pendingCrop) { const job = pendingCrop; pendingCrop = null; closeCrop(); job.resolve(null); return; }
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
    <div id="lpToastHost" class="lp-toast-host" aria-live="polite" aria-atomic="true"></div>
    <script>
    (function () {
        var host = document.getElementById('lpToastHost');
        // window.lpToast('Saved', 'success' | 'error' | 'info', ms)
        window.lpToast = function (msg, type, ms) {
            if (!host || !msg) return;
            type = type || 'success';
            var t = document.createElement('div');
            t.className = 'lp-toast is-' + type;
            t.setAttribute('role', type === 'error' ? 'alert' : 'status');
            var ic = type === 'error' ? '<path d="M18 6 6 18M6 6l12 12"/>' : (type === 'info' ? '<path d="M12 16v-4M12 8h.01"/>' : '<path d="M20 6 9 17l-5-5"/>');
            t.innerHTML = '<span class="lp-toast-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">' + ic + '</svg></span><span class="lp-toast-msg"></span><button type="button" class="lp-toast-x" aria-label="Dismiss"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>';
            t.querySelector('.lp-toast-msg').textContent = msg;
            host.appendChild(t);
            requestAnimationFrame(function () { requestAnimationFrame(function () { t.classList.add('is-in'); }); });
            var gone = false, timer = setTimeout(close, ms || 4200);
            function close() { if (gone) return; gone = true; clearTimeout(timer); t.classList.remove('is-in'); t.classList.add('is-out'); setTimeout(function () { t.remove(); }, 320); }
            t.querySelector('.lp-toast-x').addEventListener('click', close);
            t.addEventListener('mouseenter', function () { clearTimeout(timer); });
            t.addEventListener('mouseleave', function () { if (!gone) timer = setTimeout(close, 2000); });
            return close;
        };
        @if (session('success'))
        window.lpToast(@json((string) session('success')), 'success');
        @endif

        // One toast style everywhere: toastr messages and plain "success" alerts (no question,
        // no cancel, no input) show as lpToast at the top centre; dialogs that ask something stay.
        document.addEventListener('DOMContentLoaded', function () {
            if (window.toastr) {
                ['success', 'error', 'info', 'warning'].forEach(function (k) {
                    window.toastr[k] = function (msg, title) { window.lpToast(String(msg || title || ''), k === 'warning' ? 'info' : k); };
                });
            }
            if (window.Swal && !window.Swal.__lpToast) {
                var fire = window.Swal.fire.bind(window.Swal);
                window.Swal.fire = function (a, b, c) {
                    var o = a && typeof a === 'object' ? a : { title: a, text: b, icon: c };
                    var plain = o.icon === 'success' && !o.showCancelButton && !o.showDenyButton && !o.input && !o.preConfirm && !o.html && !o.toast;
                    if (plain) {
                        window.lpToast(String(o.text || o.title || 'Done'), 'success');
                        return Promise.resolve({ isConfirmed: true, isDenied: false, isDismissed: false, value: true });
                    }
                    return fire.apply(null, arguments);
                };
                window.Swal.__lpToast = true;
            }
        });
    })();
    </script>
@if (Auth::guard('library')->check() && Route::has('chat.unread'))
    <link rel="stylesheet" href="{{ asset('public/css/lb-chat.css') }}?v={{ @filemtime(public_path('css/lb-chat.css')) }}">
    <script src="{{ asset('public/js/lb-chat-notify.js') }}?v={{ @filemtime(public_path('js/lb-chat-notify.js')) }}"></script>
    <script>
        LibraroNotify.start({ url: @json(route('chat.unread')), scope: 'library:{{ Auth::guard('library')->id() }}', who: 'library', chatUrl: @json(route('chat.index')), onChatPage: {{ request()->routeIs('chat.*') ? 'true' : 'false' }}, interval: 5000 });
    </script>
@endif
</body>

</html>