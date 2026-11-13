<?php

/*
|--------------------------------------------------------------------------
| Admin portal: on-demand plugin bundles (layouts/admin.blade.php)
|--------------------------------------------------------------------------
|
| Every admin page always gets jQuery, Font Awesome, admin.min.css, ui-kit.js,
| toastr, main-scripts.js and admin-ui.js. The heavier plugins below load only
| on the pages that use them. App\Support\AdminAssets decides that by:
|
|   1. markers — the page's own HTML (content, page_actions, the styles /
|      scripts stacks, and the local public/js files the page links) contains
|      one of the bundle's marker strings, e.g. "DataTable(" or "Swal.";
|   2. routes  — the current route name matches a pattern listed here
|      (use for code the scan cannot see, e.g. HTML loaded later by AJAX);
|   3. the page asks for it: @section('admin_assets', 'datatables,swal').
|
| A bundle is skipped when the page already links that plugin itself
| ("self" substrings), so nothing is loaded twice.
|
| CSS is imported into the lowest cascade layer (vendor), so the design system
| and Tailwind utilities always win. JS is printed at the end of <body>, in the
| order of this array, before admin-ui.js and before @stack('scripts').
| Pin every version.
*/

$cdnjs = 'https://cdnjs.cloudflare.com/ajax/libs';

return [

    'bundles' => [

        'swal' => [
            'markers' => ['Swal.', 'Swal(', 'swal(', 'sweetAlert'],
            'self' => ['sweetalert2'],
            'js' => [$cdnjs.'/limonte-sweetalert2/11.26.25/sweetalert2.all.min.js'],
        ],

        'validate' => [
            'markers' => ['validateForm', '.validate(', '$.validator', 'jQuery.validator', '.valid()', '.rules(', 'digit-only', 'char-only', 'char-with-sps'],
            'self' => ['jquery.validate'],
            'js' => [$cdnjs.'/jquery-validate/1.21.0/jquery.validate.min.js', 'local:js/main-validation.js'],
        ],

        'datatables' => [
            'markers' => ['DataTable', 'dataTable('],
            'self' => ['dataTables.min.js', 'dataTables.js'],
            'css' => [$cdnjs.'/datatables.net-dt/2.1.6/css/dataTables.dataTables.min.css'],
            'js' => [$cdnjs.'/datatables.net/2.1.6/dataTables.min.js'],
        ],

        'flatpickr' => [
            'markers' => ['flatpickr'],
            'self' => ['flatpickr.min.js', 'npm/flatpickr'],
            'css' => [$cdnjs.'/flatpickr/4.6.13/flatpickr.min.css'],
            'js' => [$cdnjs.'/flatpickr/4.6.13/flatpickr.min.js'],
        ],

        'tagify' => [
            'markers' => ['Tagify'],
            'self' => ['@yaireo/tagify', 'tagify.min.js'],
            'css' => [$cdnjs.'/tagify/4.17.9/tagify.min.css'],
            'js' => [$cdnjs.'/tagify/4.17.9/tagify.min.js'],
        ],

        'chart' => [
            'markers' => ['new Chart', 'Chart.register', 'ChartDataLabels'],
            'self' => ['chart.umd', 'npm/chart.js'],
            'js' => [$cdnjs.'/Chart.js/4.4.1/chart.umd.min.js', $cdnjs.'/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js'],
        ],

        'razorpay' => [
            'markers' => ['Razorpay('],
            'self' => ['checkout.razorpay.com'],
            'js' => ['https://checkout.razorpay.com/v1/checkout.js'],
        ],

        // the shared time picker (lb.partials.timepicker-assets)
        'timepicker' => [
            'markers' => ['data-lb-time', 'LbTime'],
            'self' => ['lb-timepicker.js'],
            'include' => 'lb.partials.timepicker-assets',
        ],
    ],

    // route name pattern => bundles always loaded there (request()->routeIs syntax),
    // e.g. 'crm.leads.*' => ['swal']
    'routes' => [],

    /*
    | Links whose URL contains one of these words are never prerendered / prefetched on
    | hover (Speculation Rules + the prefetch fallback in admin-ui.js): logout, files,
    | and the GET routes that change data (…/delete/{id}, …/toggle-status/{id},
    | leads/{lead}/save-contact). Links with target, download, data-bs-toggle,
    | onclick or data-no-prefetch are skipped too. Add a word here for every new GET
    | route that writes something.
    */
    'no_prefetch' => [
        'logout', 'login', 'export', 'delete', 'destroy', 'toggle', 'sync', 'download',
        'template', 'save-contact', 'print', 'pdf', 'csv', 'refresh-csrf', 'follow-ups/due',
    ],
];
