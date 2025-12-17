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
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
    <link href="https://www.richtexteditor.com/rte/themes/default/rte.css" rel="stylesheet" />
    <meta name="format-detection" content="telephone=no">
    <script type="text/javascript">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "umcz2tm3mc");
    </script>
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

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

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
        // $(document).ready(function() {
        //     $(document).on('selectstart', function(e) {
        //         if (!$(e.target).is('input, select, textarea, [contenteditable="true"], .cke_editable, .ck-editor__editable')) {
        //             e.preventDefault();
        //         }
        //     });

        //     $(document).on('mousedown', function(e) {
        //         if (!$(e.target).is('input, select, textarea, [contenteditable="true"], .cke_editable, .ck-editor__editable')) {
        //             e.preventDefault();
        //         }
        //     });
        // });

    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Function to show a popup
            function showOfflinePopup() {
                Swal.fire({
                    title: 'No Internet Connection'
                    , text: 'Your internet connection is lost. Please check your connection.'
                    , icon: 'error'
                    , confirmButtonText: 'OK'
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
                    title: 'Back Online'
                    , text: 'Your internet connection has been restored.'
                    , icon: 'success'
                    , confirmButtonText: 'OK'
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
        function toggleSupportCard() {
            $('#supportCard').toggle();
        }
    </script>

</body>

</html>
