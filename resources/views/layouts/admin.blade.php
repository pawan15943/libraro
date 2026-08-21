<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libraro : Library Management Software</title>
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('public/css/style.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.css" />
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Include DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
    <link href="https://www.richtexteditor.com/rte/themes/default/rte.css" rel="stylesheet" />
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/css/notification-header.css') }}">

    <script type="text/javascript">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).function(){(c[a].q=c[a].q||[])};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "umcz2tm3mc");
    </script>

    <!-- Root Level Whitespace Fix CSS -->
    <style>
        html, body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            background-color: #18225f !important;
        }

        .library-dashbaord {
            display: flex !important;
            height: 100vh !important;
            max-height: 100vh !important;
            width: 100vw !important;
            overflow: hidden !important;
        }

        .library-dashbaord .content-area {
            flex: 1 !important;
            height: 100vh !important;
            max-height: 100vh !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            background-color: #f5f5ff !important;
        }

        .content-area .content {
            flex: 1 0 auto !important;
        }

        .content-area .footer {
            flex-shrink: 0 !important;
            margin-top: auto !important;
        }

        @media (max-width: 768px) {
            html, body {
                overflow: auto !important;
                height: auto !important;
            }
            .library-dashbaord {
                height: auto !important;
                max-height: none !important;
            }
            .library-dashbaord .content-area {
                height: auto !important;
                max-height: none !important;
                overflow-y: visible !important;
            }
        }

        /* Professional DataTables & Bootstrap 5 Pagination Custom Styling */
        .pagination {
            margin-bottom: 0 !important;
            display: flex !important;
            padding-left: 0 !important;
            list-style: none !important;
            gap: 4px !important;
            align-items: center !important;
        }

        .pagination .page-item .page-link {
            font-family: 'Outfit', sans-serif !important;
            font-weight: 600 !important;
            font-size: 0.83rem !important;
            color: #18225f !important;
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 0.4rem 0.8rem !important;
            transition: all 0.2s ease-in-out !important;
            text-decoration: none !important;
        }

        .pagination .page-item.active .page-link {
            background-color: #18225f !important;
            border-color: #18225f !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(24, 34, 95, 0.2) !important;
        }

        .pagination .page-item .page-link:hover {
            background-color: #34939F !important;
            border-color: #34939F !important;
            color: #ffffff !important;
        }

        .pagination .page-item.disabled .page-link {
            color: #94a3b8 !important;
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
            cursor: not-allowed !important;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px !important;
            border: 1px solid #cbd5e1 !important;
            padding: 0.4rem 0.8rem !important;
            font-size: 0.85rem !important;
            font-family: 'Mulish', sans-serif !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            border: 1px solid #cbd5e1 !important;
            background: #ffffff !important;
            color: #18225f !important;
            font-family: 'Outfit', sans-serif !important;
            font-weight: 600 !important;
            padding: 0.4rem 0.8rem !important;
            margin: 0 2px !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #18225f !important;
            color: #ffffff !important;
            border-color: #18225f !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #34939F !important;
            color: #ffffff !important;
            border-color: #34939F !important;
        }

        /* Universal Button UI Standardization (Except Sidebar Menus) */
        .content-area .btn:not(.btn-toggle),
        .heading-list .btn,
        .main-content .btn,
        button.btn:not(.sidebar *),
        a.btn:not(.sidebar *):not(.btn-toggle),
        input[type="submit"].btn {
            width: auto !important;
            max-width: fit-content !important;
            white-space: nowrap !important;
            font-family: 'Outfit', sans-serif !important;
            font-weight: 700 !important;
            border-radius: 30px !important;
            padding: 0.5rem 1.25rem !important;
            font-size: 0.88rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.4rem !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05) !important;
            text-decoration: none !important;
        }

        .content-area .btn:not(.btn-toggle):hover,
        button.btn:not(.sidebar *):hover,
        a.btn:not(.sidebar *):not(.btn-toggle):hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1) !important;
        }

        .btn-sm {
            padding: 0.35rem 0.95rem !important;
            font-size: 0.8rem !important;
            border-radius: 20px !important;
        }

        .btn-lg {
            padding: 0.7rem 1.8rem !important;
            font-size: 1rem !important;
            border-radius: 35px !important;
        }

        /* Primary Button (Navy Blue #18225f) */
        .btn-primary:not(.sidebar *), .btn-primary.button:not(.sidebar *), button.btn-primary:not(.sidebar *), a.btn-primary:not(.sidebar *), .bg-primary {
            background-color: #18225f !important;
            border: 1px solid #18225f !important;
            color: #ffffff !important;
        }
        .btn-primary:not(.sidebar *):hover, .btn-primary.button:not(.sidebar *):hover, button.btn-primary:not(.sidebar *):hover, a.btn-primary:not(.sidebar *):hover {
            background-color: #0f1743 !important;
            border-color: #0f1743 !important;
            color: #ffffff !important;
        }

        /* Secondary Button */
        .btn-secondary:not(.sidebar *), button.btn-secondary:not(.sidebar *), a.btn-secondary:not(.sidebar *) {
            background-color: #64748b !important;
            border: 1px solid #64748b !important;
            color: #ffffff !important;
        }
        .btn-secondary:not(.sidebar *):hover, button.btn-secondary:not(.sidebar *):hover, a.btn-secondary:not(.sidebar *):hover {
            background-color: #475569 !important;
            border-color: #475569 !important;
            color: #ffffff !important;
        }

        /* Outline Secondary Button */
        .btn-outline-secondary, button.btn-outline-secondary, a.btn-outline-secondary {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #475569 !important;
        }
        .btn-outline-secondary:hover, button.btn-outline-secondary:hover, a.btn-outline-secondary:hover {
            background-color: #f1f5f9 !important;
            border-color: #94a3b8 !important;
            color: #1e293b !important;
        }

        /* Outline Primary Button */
        .btn-outline-primary, button.btn-outline-primary, a.btn-outline-primary {
            background-color: #ffffff !important;
            border: 1px solid #18225f !important;
            color: #18225f !important;
        }
        .btn-outline-primary:hover, button.btn-outline-primary:hover, a.btn-outline-primary:hover {
            background-color: #18225f !important;
            border-color: #18225f !important;
            color: #ffffff !important;
        }

        /* Success Button */
        .btn-success, button.btn-success, a.btn-success {
            background-color: #16a34a !important;
            border: 1px solid #16a34a !important;
            color: #ffffff !important;
        }
        .btn-success:hover, button.btn-success:hover, a.btn-success:hover {
            background-color: #15803d !important;
            border-color: #15803d !important;
            color: #ffffff !important;
        }

        /* Danger Button */
        .btn-danger, button.btn-danger, a.btn-danger {
            background-color: #dc3545 !important;
            border: 1px solid #dc3545 !important;
            color: #ffffff !important;
        }
        .btn-danger:hover, button.btn-danger:hover, a.btn-danger:hover {
            background-color: #b02a37 !important;
            border-color: #b02a37 !important;
            color: #ffffff !important;
        }

        /* Info / Teal Button */
        .btn-info, button.btn-info, a.btn-info {
            background-color: #34939F !important;
            border: 1px solid #34939F !important;
            color: #ffffff !important;
        }
        .btn-info:hover, button.btn-info:hover, a.btn-info:hover {
            background-color: #28747f !important;
            border-color: #28747f !important;
            color: #ffffff !important;
        }

        /* Warning Button */
        .btn-warning, button.btn-warning, a.btn-warning {
            background-color: #d97706 !important;
            border: 1px solid #d97706 !important;
            color: #ffffff !important;
        }
        .btn-warning:hover, button.btn-warning:hover, a.btn-warning:hover {
            background-color: #b45309 !important;
            border-color: #b45309 !important;
            color: #ffffff !important;
        }

        .text-primary {
            color: #18225f !important;
        }

        .border-primary {
            border-color: #18225f !important;
        }

        /* Global Table Header Contrast Fix - Pure White Text on Navy Background */
        .table th,
        .table thead th,
        .table > thead > tr > th,
        #datatable th,
        #datatable thead th,
        .table-responsive table thead th {
            color: #ffffff !important;
            background-color: #18225f !important;
            font-family: 'Outfit', sans-serif !important;
            font-weight: 700 !important;
            font-size: 0.82rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            border-bottom: 2px solid #18225f !important;
            padding: 0.85rem 1rem !important;
        }

        /* Sidebar Menu Rules for Expanded and Collapsed (.w-120) States */
        .sidebar a.btn-toggle,
        .sidebar .btn-toggle,
        .sidebar li a,
        .sidebar .btn-toggle span,
        .sidebar i,
        .sidebar .fa-angle-right,
        .sidebar .fa-angle-down {
            color: #ffffff !important;
        }

        /* Expanded Sidebar Mode (Default) */
        .sidebar:not(.w-120) .btn-toggle,
        .sidebar:not(.w-120) a.btn-toggle {
            box-shadow: none !important;
            border-radius: 0 !important;
            transform: none !important;
            width: 100% !important;
            max-width: 100% !important;
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            font-family: 'Outfit', sans-serif !important;
            font-weight: 500 !important;
            padding: 0.6rem 0.8rem !important;
            background: transparent !important;
            color: #ffffff !important;
            border: none !important;
            text-align: left !important;
        }

        .sidebar:not(.w-120) .btn-toggle span,
        .sidebar:not(.w-120) a.btn-toggle span {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.6rem !important;
        }

        .sidebar:not(.w-120) .btn-toggle .fa-angle-right,
        .sidebar:not(.w-120) a.btn-toggle .fa-angle-right,
        .sidebar:not(.w-120) .btn-toggle .fa-angle-down,
        .sidebar:not(.w-120) a.btn-toggle .fa-angle-down {
            margin-left: auto !important;
            margin-right: 0 !important;
            display: inline-block !important;
        }

        .sidebar .btn-toggle:hover,
        .sidebar a.btn-toggle:hover,
        .sidebar > ul > li > a:hover {
            box-shadow: none !important;
            transform: none !important;
            background: transparent !important;
            background-color: transparent !important;
            color: #ffffff !important;
        }

        .sidebar .btn-toggle-nav a {
            font-weight: 400 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            transform: none !important;
            color: #ffffff !important;
        }

        /* Collapsed Sidebar Mode (.w-120) */
        .sidebar.w-120 .btn-toggle,
        .sidebar.w-120 a.btn-toggle,
        .sidebar.w-120 ul.list-unstyled.ps-0 > li > a {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
            padding: 0.75rem 0.2rem !important;
            width: 100% !important;
        }

        .sidebar.w-120 .btn-toggle span,
        .sidebar.w-120 a.btn-toggle span,
        .sidebar.w-120 ul.list-unstyled.ps-0 > li > a span {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            text-align: center !important;
            font-size: 0.78rem !important;
            line-height: 1.25 !important;
        }

        .sidebar.w-120 .btn-toggle span i,
        .sidebar.w-120 a.btn-toggle span i,
        .sidebar.w-120 ul.list-unstyled.ps-0 > li > a i {
            font-size: 1.25rem !important;
            margin-right: 0 !important;
            margin-bottom: 0.35rem !important;
        }

        .sidebar.w-120 .fa-angle-right,
        .sidebar.w-120 .fa-angle-down {
            display: none !important;
        }

        /* Remove Padding From Main Sidebar Menu Container */
        .sidebar,
        .library-dashbaord .sidebar,
        .sidebar-wrapper,
        #sidebar {
            padding: 0 !important;
        }

        .sidebar ul,
        .sidebar .list-unstyled {
            padding-left: 0 !important;
            padding-right: 0 !important;
            margin-bottom: 0 !important;
        }

        /* Remove Navbar / Header Padding */
        .header,
        .content-area .header,
        #header,
        .navbar {
            padding: 0 !important;
        }

        .header .d-flex {
            padding-top: 0.25rem !important;
            padding-bottom: 0.25rem !important;
        }
    </style>
</head>

<body>

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

    <div class="library-dashbaord">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <div class="content-area">
            <!-- Header -->
            @include('partials.header')

            <!-- Begin Page Content -->
            <div class="content">
                <div class="container-fluid">
                    @include('partials.breadcrumbs')
                    @yield('content')
                </div>
            </div>

            <!-- Footer  -->
            @include('partials.footer')
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
    <script src="{{ url('public/js/main-scripts.js') }}"></script>
    <script src="{{ url('public/js/main-validation.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>

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

            $('#sidebar').on('click', function() {
                $('.sidebar').toggleClass('w-120');
            });

            $('#sidebar_mob').on('click', function() {
                $('.sidebar').toggleClass('w-120');
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function showOfflinePopup() {
                Swal.fire({
                    title: 'No Internet Connection',
                    text: 'Your internet connection is lost. Please check your connection.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }

            if (!navigator.onLine) {
                showOfflinePopup();
            }

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

            $(window).resize(function() {
                addClassOnResize();
            });

            addClassOnResize();
        });
    </script>

    <script>
        function toggleSupportCard() {
            $('#supportCard').toggle();
        }
    </script>
</body>
</html>
