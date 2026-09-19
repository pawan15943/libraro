@extends('layouts.library')

@section('content')

{{-- Dedicated Scoped Stylesheet for CSV Upload Module --}}
<link rel="stylesheet" href="{{ asset('public/css/csv-upload.css') }}?v={{ time() }}" />

{{-- Data Import Guidelines Modal --}}
<div class="modal fade" tabindex="-1" id="guidelines" aria-labelledby="guidelinesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="guidelinesModalLabel">
                    <i class="fa-solid fa-circle-info"></i> Data Import Guidelines
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ol class="guidelines-ol">
                    <li class="guidelines-li">
                        <span class="guidelines-num">1</span>
                        <div>
                            <strong>Ensure Accuracy:</strong> Double-check that the Plan Name, Plan Type, and Plan Price entered in the sheet are correct.
                        </div>
                    </li>
                    <li class="guidelines-li">
                        <span class="guidelines-num">2</span>
                        <div>
                            <strong>Separate Entries for Different Shifts:</strong> If you are uploading data for a learner enrolled in both the first half and second half, create separate entries for each. The same applies to hourly shifts—each shift must have its own entry.
                        </div>
                    </li>
                    <li class="guidelines-li">
                        <span class="guidelines-num">3</span>
                        <div>
                            <strong>Price Column & Payment Status:</strong> If you enter an amount in the Price column, the system will assume that the learner has paid.
                        </div>
                    </li>
                    <li class="guidelines-li">
                        <span class="guidelines-num">4</span>
                        <div>
                            <strong>Extended Period Learners:</strong> If a learner is in an extended period, ensure their Start Date is entered correctly. The system will automatically recognize it as an extension based on your library's extension policy.
                        </div>
                    </li>
                    <li class="guidelines-li">
                        <span class="guidelines-num">5</span>
                        <div>
                            <strong>Expired Learners:</strong> If you are importing a previously expired learner for record-keeping, make sure to enter their Start Date correctly.
                        </div>
                    </li>
                    <li class="guidelines-li">
                        <span class="guidelines-num">6</span>
                        <div>
                            <strong>Date Format Compliance:</strong> Use the date format provided in the sample file to ensure proper data import.
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="csv-upload-module">
    @can('has-permission','Import Student')

    {{-- Page Header --}}
    <div class="csv-page-header">
        <div class="csv-header-left">
            <h4 class="csv-header-title">
                <i class="fa-solid fa-file-csv" style="color: #34939F;"></i> Learner CSV Import
            </h4>
            <p class="csv-header-subtitle">Bulk import learners, assign seat shifts, and configure plans seamlessly</p>
        </div>
        <div class="csv-header-right">
            <button type="button" class="btn-guidelines-header" data-bs-toggle="modal" data-bs-target="#guidelines">
                <i class="fa-solid fa-circle-info"></i> Import Guidelines
            </button>
        </div>
    </div>

    {{-- Mobile Section Quick Navigation (Sticky on mobile screens) --}}
    <div class="mobile-section-nav">
        <button type="button" class="mobile-nav-btn" data-target="#stepGuideCard">
            <i class="fa-regular fa-file-lines"></i> <span>1. Guide</span>
        </button>
        <button type="button" class="mobile-nav-btn active" data-target="#uploadActionCard">
            <i class="fa-solid fa-arrow-up-from-bracket"></i> <span>2. Upload</span>
        </button>
        <button type="button" class="mobile-nav-btn" data-target="#planInfoCard">
            <i class="fa-solid fa-receipt"></i> <span>3. Shifts & Prices</span>
        </button>
    </div>

    {{-- Error Feedback --}}
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
        <h6 class="fw-bold mb-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Please fix the following errors:</h6>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Success Feedback --}}
    @if(session('successCount'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i>
        <strong>Success!</strong> {{ session('successCount') }} records imported successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- 3-Column Layout Grid --}}
    <div class="upload-grid-row">

        {{-- Card 1: How to Upload Data --}}
        <div class="upload-card" id="stepGuideCard">
            <div class="card-header-strip">
                <div class="card-header-badge badge-blue">
                    <i class="fa-regular fa-file-lines"></i>
                </div>
                <div class="card-header-info">
                    <h3 class="card-header-title">How to Upload Data</h3>
                    <p class="card-header-subtitle">Follow these simple steps to import learners correctly.</p>
                </div>
            </div>

            <div class="steps-list-wrap">
                <div class="step-item">
                    <div class="step-number-pill">1</div>
                    <div class="step-content">
                        <h5 class="step-title">Download Sample File</h5>
                        <p class="step-desc">Download the provided sample file to ensure correct formatting.</p>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number-pill">2</div>
                    <div class="step-content">
                        <h5 class="step-title">Fill in Data</h5>
                        <p class="step-desc">Enter the required details in the file while following the given guidelines.</p>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number-pill">3</div>
                    <div class="step-content">
                        <h5 class="step-title">Upload the File</h5>
                        <p class="step-desc">Click on the upload button and select the completed file from your computer.</p>
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number-pill">4</div>
                    <div class="step-content">
                        <h5 class="step-title">That's All!</h5>
                        <p class="step-desc">Your learners will be imported and added to the system.</p>
                    </div>
                </div>
            </div>

            {{-- Accepted Format Callout --}}
            <div class="accepted-format-banner">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <h6 class="format-banner-title">Accepted Format</h6>
                    <p class="format-banner-text">Only CSV (.csv) files are supported.</p>
                </div>
            </div>
        </div>

        {{-- Card 2: Upload Data --}}
        <div class="upload-card" id="uploadActionCard">
            <div class="card-header-strip">
                <div class="card-header-badge badge-purple">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                </div>
                <div class="card-header-info">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <h3 class="card-header-title">Upload Data</h3>
                        <a href="javascript:;" class="help-link-btn" data-bs-toggle="modal" data-bs-target="#guidelines" title="View Guidelines">
                            <i class="fa-regular fa-circle-question"></i> Need Help?
                        </a>
                    </div>
                    <p class="card-header-subtitle">Select your completed CSV file and import learners into the system.</p>
                </div>
            </div>

            {{-- Important Guidelines Callout --}}
            <div class="guidelines-callout">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div>
                    <a href="javascript:;" class="guidelines-title" data-bs-toggle="modal" data-bs-target="#guidelines">
                        Important Guidelines
                    </a>
                    <p class="guidelines-text">Please read the guidelines before uploading your file.</p>
                </div>
            </div>

            {{-- Upload Form with Drag & Drop Zone --}}
            <form action="{{ route('library.csv.upload') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf
                <input type="file" name="csv_file" id="csvFileInput" class="d-none" accept=".csv, .txt, .xlsx, .xls" required>

                <div class="dropzone-box" id="dropzoneBox">
                    <div class="dropzone-icon-cloud">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <h5 class="dropzone-title">Choose a CSV file or drag and drop</h5>
                    <p class="dropzone-subtitle">Only .csv files (Max size: 5 MB)</p>

                    <button type="button" class="btn-choose-file" id="browseFileBtn">
                        <i class="fa-regular fa-file"></i> Choose File
                    </button>

                    {{-- Selected File Box --}}
                    <div class="file-selected-box d-none" id="fileSelectedBox">
                        <div class="file-info-text">
                            <i class="fa-solid fa-file-csv fs-5 text-success"></i>
                            <span id="selectedFileName">filename.csv</span>
                        </div>
                        <button type="button" class="file-remove-btn" id="removeFileBtn" title="Remove selected file">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                {{-- Import Submit Button (Visible when file selected) --}}
                <button type="submit" class="btn btn-import-submit d-none" id="importSubmitBtn">
                    <i class="fa-solid fa-file-import"></i> Import Data
                </button>
            </form>

            {{-- Divider --}}
            <div class="upload-or-divider">
                <span>OR</span>
            </div>

            {{-- Download Sample CSV File --}}
            <a href="{{ asset('public/sample/learners-data-sample.csv') }}" class="btn-download-sample" download="learners-data-sample.csv">
                <i class="fa-solid fa-download"></i> Download Sample Learner CSV File
            </a>

            {{-- Progress bar --}}
            <div id="export-progress-container" class="export-progress-wrap {{ session('autoExportCsv') ? '' : 'd-none' }}">
                <progress id="export-progress-bar" class="export-progress-bar-custom" value="0" max="100"></progress>
                <span id="export-progress-text" class="export-progress-label">Preparing download... 0%</span>
            </div>
        </div>

        {{-- Card 3: Your Library Information --}}
        <div class="upload-card" id="planInfoCard">
            <div class="card-header-strip">
                <div class="card-header-badge badge-green">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div class="card-header-info">
                    <h3 class="card-header-title">Your Library Information</h3>
                    <p class="card-header-subtitle">Plan types and their prices (for reference while filling the file)</p>
                </div>
            </div>

            {{-- Tab Switcher Pills --}}
            <div class="tab-pills-container">
                <button type="button" class="tab-pill-btn active" id="btnTabShift">
                    Plan (Shift)
                </button>
                <button type="button" class="tab-pill-btn" id="btnTabPlan">
                    Plan Types
                </button>
            </div>

            {{-- Tab 1 Content: Plan (Shift) with Price and Hours --}}
            <div class="info-table-wrap" id="tabContentShift">
                <table class="info-custom-table">
                    <thead>
                        <tr>
                            <th>Plan Type (Shift)</th>
                            <th>Time</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($plantypes) && count($plantypes) > 0)
                            @foreach($plantypes as $value)
                            <tr>
                                <td><b>{{ $value->plan_type }}</b></td>
                                <td>
                                    <span class="time-range-text">
                                        {{ !empty($value->start_time) && !empty($value->end_time) ? $value->start_time . ' - ' . $value->end_time : '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="price-tag">INR {{ $value->plan_price }}</span>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No shifts configured</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Tab 2 Content: Plan Types --}}
            <div class="info-table-wrap d-none" id="tabContentPlan">
                <table class="info-custom-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">S.No.</th>
                            <th>Plan Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($plans) && count($plans) > 0)
                            @foreach($plans as $key => $value)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td><b>{{ $value->name }}</b></td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">No plans configured</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Bottom Note Callout --}}
            <div class="note-callout-bottom">
                <i class="fa-solid fa-circle-info"></i>
                <span><b>Note:</b> Before adding data to the CSV, make sure to check your plan details here.</span>
            </div>
        </div>

    </div>

    {{-- Display Invalid Records --}}
    @if(session('invalidRecords') && count(session('invalidRecords')) > 0)
    <div class="invalid-records-card" id="invalid-records-section">
        <div class="invalid-header">
            <i class="fa-solid fa-circle-xmark fs-4"></i>
            <h5>Oops! Something went wrong with the upload. Please check the error messages below and try again.</h5>
        </div>
        <div class="mobile-scroll-hint d-md-none">
            <i class="fa-solid fa-arrows-left-right me-1"></i> Swipe horizontally to see complete error details
        </div>
        <div class="invalid-table-wrap table-responsive">
            <table class="invalid-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Plan Type</th>
                        <th>Start Date</th>
                        <th style="width: 35%; min-width: 220px;">Error Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (session('invalidRecords') as $record)
                    <tr>
                        <td><b>{{ $record['name'] ?? 'N/A' }}</b></td>
                        <td>{{ $record['email'] ?? 'N/A' }}</td>
                        <td>{{ $record['plan_type'] ?? 'N/A' }}</td>
                        <td>{{ $record['start_date'] ?? 'N/A' }}</td>
                        <td class="text-danger fw-semibold">
                            {{ array_key_exists('error', $record) ? (is_array($record['error']) ? implode(', ', $record['error']) : $record['error']) : 'No error provided' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="btn-clear-invalid" id="clearInvalidRecordsButton">
            <i class="fa-solid fa-trash-can me-1"></i> Clear Invalid Records
        </button>
    </div>

    {{-- Trigger CSV Download Automatically --}}
    @if(session('autoExportCsv'))
    <script type="text/javascript">
        let exportProgressBar = document.getElementById('export-progress-bar');
        let exportProgressText = document.getElementById('export-progress-text');
        let exportrecordurl = "{{ Auth::guard('library')->check() ? route('library.export.invalid.records') : route('web.export.invalid.records') }}";

        let progress = 0;
        let interval = setInterval(function() {
            progress += 10;
            if (exportProgressBar) exportProgressBar.value = progress;
            if (exportProgressText) exportProgressText.textContent = `Preparing download: ${progress}%`;

            if (progress >= 100) {
                clearInterval(interval);
                window.location.href = exportrecordurl;
            }
        }, 100);
    </script>
    @endif
    @endif

    @else
    <div class="upload-card text-center py-5">
        <i class="fa-solid fa-lock text-danger fs-1 mb-3"></i>
        <h5 class="fw-bold text-danger">Permission Denied</h5>
        <p class="text-muted mb-0">You don't have permission to import learners in this library.</p>
    </div>
    @endcan
</div>

{{-- Interactive Scripts --}}
<script>
$(document).ready(function() {
    // Mobile Section Navigation Smooth Scroll
    $('.mobile-nav-btn').on('click', function(e) {
        e.preventDefault();
        $('.mobile-nav-btn').removeClass('active');
        $(this).addClass('active');

        const targetSelector = $(this).data('target');
        const targetElement = $(targetSelector);
        if (targetElement.length) {
            $('html, body').animate({
                scrollTop: targetElement.offset().top - 120
            }, 250);
        }
    });

    // Sync active mobile nav button on scroll
    $(window).on('scroll', function() {
        if ($('.mobile-section-nav').is(':visible')) {
            const scrollPos = $(window).scrollTop() + 160;
            const cardIds = ['#stepGuideCard', '#uploadActionCard', '#planInfoCard'];

            cardIds.forEach(function(id) {
                const el = $(id);
                if (el.length) {
                    const top = el.offset().top;
                    const bottom = top + el.outerHeight();
                    if (scrollPos >= top && scrollPos <= bottom) {
                        $('.mobile-nav-btn').removeClass('active');
                        $('.mobile-nav-btn[data-target="' + id + '"]').addClass('active');
                    }
                }
            });
        }
    });

    // Tab switching in Card 3
    $('#btnTabShift').on('click', function() {
        $(this).addClass('active');
        $('#btnTabPlan').removeClass('active');
        $('#tabContentShift').removeClass('d-none');
        $('#tabContentPlan').addClass('d-none');
    });

    $('#btnTabPlan').on('click', function() {
        $(this).addClass('active');
        $('#btnTabShift').removeClass('active');
        $('#tabContentPlan').removeClass('d-none');
        $('#tabContentShift').addClass('d-none');
    });

    // File Input & Drag and Drop Handling
    const dropzone = $('#dropzoneBox');
    const fileInput = $('#csvFileInput');
    const fileSelectedBox = $('#fileSelectedBox');
    const selectedFileName = $('#selectedFileName');
    const browseFileBtn = $('#browseFileBtn');
    const importSubmitBtn = $('#importSubmitBtn');
    const removeFileBtn = $('#removeFileBtn');

    browseFileBtn.on('click', function(e) {
        e.stopPropagation();
        fileInput.trigger('click');
    });

    dropzone.on('click', function() {
        if (fileInput[0].files.length === 0) {
            fileInput.trigger('click');
        }
    });

    dropzone.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.addClass('drag-over');
    });

    dropzone.on('dragleave dragend drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.removeClass('drag-over');
    });

    dropzone.on('drop', function(e) {
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            fileInput[0].files = files;
            updateFileDisplay(files[0]);
        }
    });

    fileInput.on('change', function() {
        if (this.files.length > 0) {
            updateFileDisplay(this.files[0]);
        }
    });

    function updateFileDisplay(file) {
        selectedFileName.text(file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)');
        fileSelectedBox.removeClass('d-none');
        browseFileBtn.addClass('d-none');
        importSubmitBtn.removeClass('d-none');
    }

    removeFileBtn.on('click', function(e) {
        e.stopPropagation();
        fileInput.val('');
        fileSelectedBox.addClass('d-none');
        browseFileBtn.removeClass('d-none');
        importSubmitBtn.addClass('d-none');
    });

    // Clear Invalid Records AJAX Handler
    $('#clearInvalidRecordsButton').on('click', function() {
        $('#invalid-records-section').fadeOut(250);

        let clearSessionRoute = "{{ Auth::guard('library')->check() ? route('library.clear.session') : route('web.clear.session') }}";
        fetch(clearSessionRoute, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({})
        }).catch(function(err) {
            console.error('Error clearing session:', err);
        });
    });
});
</script>
@endsection