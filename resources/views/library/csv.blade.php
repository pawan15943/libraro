@extends('layouts.library')

@section('content')

<!-- Breadcrumb -->

<div class="modal" tabindex="-1" id="guidelines">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Data Import Guidelines</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ol>
                    <li>Ensure Accuracy: Double-check that the Plan Name, Plan Type, and Plan Price entered in the sheet are correct.</li>
                    <li>Separate Entries for Different Shifts: If you are uploading data for a learner enrolled in both the first half and second half, create separate entries for each. The same applies to hourly shifts—each shift must have its own entry.</li>
                    <li>Price Column & Payment Status: If you enter an amount in the Price column, the system will assume that the learner has paid.</li>
                    <li>Extended Period Learners: If a learner is in an extended period, ensure their Start Date is entered correctly. The system will automatically recognize it as an extension based on your library's extension policy.</li>
                    <li>Expired Learners: If you are importing a previously expired learner for record-keeping, make sure to enter their Start Date correctly.</li>
                    <li>Date Format Compliance: Use the date format provided in the sample file to ensure proper data import.</li>
                </ol>
            </div>

        </div>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif


@if(session('successCount'))
<div class="alert alert-success">
    {{ session('successCount') }} records imported successfully.
</div>
@endif

<div class="row justify-content-center mb-4">
    <div class="col-lg-4">
        <div class="how-to-upload">
            <h4>How to Upload data :</h4>
            <p>1. <b>Download Sample File :</b> Download the provided sample file to ensure correct formatting.</p>
            <p>2. <b>Fill in Data : </b> Enter the required details in the file while following the given guidelines.</p>
            <p>3. <b>Upload the File : </b> Click on the Upload button and select the completed file from your computer.</p>
            <p>4. That's All.</p>
        </div>
    </div>
    <div class="col-lg-4">

        <div class="import-data ">
            <h4>Upload Data</h4>
            <p class="m-0 text-center"><a href="javascript:;" class="guideline" data-bs-toggle="modal" data-bs-target="#guidelines">Important Guidelines.</a></p>
            <form action="{{  route('library.csv.upload')}}" method="POST" enctype="multipart/form-data" id="importForm"> @csrf

                <div class="row g-4">
                    <div class="col-lg-12">
                        <label for="">Select File</label>
                        <input type="file" class="form-control " name="csv_file">

                    </div>
                    <div class="col-lg-12">
                        <button type="submit" class="btn btn-primary button">Import Data</button>
                    </div>
                    <a href="{{ asset('public/sample/learners-data-sample.csv') }}" class="sample" download="learners-data-sample.csv">Download Sample learnar CSV File</a>
                </div>
            </form>
            <div id="export-progress-container">
                <progress id="export-progress-bar" value="0" max="100" style="width: 100%;"></progress>
                <span id="export-progress-text">Preparing download: 0%</span>
            </div>
        </div>


    </div>
    <div class="col-lg-4">
        <div class="how-to-upload">
            <h4>Your Library Information</h4>
            <ul>
                <li id="show-plan" class="active">Plan</li>
                <li id="show-plan-type">Plan Types</li>
            </ul>

            <div class="plan">
                <table class="table table-bordered">
                    <tr>
                        <th class="w-50">S.No.</th>
                        <th>Plan Name</th>
                    </tr>
                    @if(!is_null($plans))
                    @foreach($plans as $key => $value)
                    <tr>
                        <td>{{$key+1}}</td>
                        <td>{{$value->name}} </td>
                    </tr> 
                    @endforeach
                    @endif
                   
                    
                </table>
            </div>

            <div class="plan-typ hidden">
                <table class="table table-bordered">
                    <tr>
                        <th class="w-50">Plan Type (Shift)</th>
                        <th>Price</th>
                    </tr>
                    @if(!is_null($plantypes))
                    @foreach($plantypes as $key => $value)
                    
                    <tr>
                        <td>{{$value->plan_type}}</td>
                        <td>INR {{$value->plan_price}}</td>
                    </tr>
                    @endforeach
                    @endif
                </table>
            </div>

            <span><b>Note : </b>Before adding data to the CSV, make sure to check your plan details here.</span>
        </div>
    </div>
</div>


{{-- Display Invalid Records --}}
<div class="row">
    <div class="col-lg-12">
        <div id="invalid-records-section">
            @if(session('invalidRecords') && count(session('invalidRecords')) > 0)
            <h6 class="text-center text-danger mb-4">Oops! Something went wrong with the upload. Please check the error messages below and try again.</h6>
            <div class="table table-responsive">
                <table class="table text-center data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Plan Type</th>
                            <th>Start Date</th>
                            <th>Error Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (session('invalidRecords') as $record)
                        <tr>
                            <td>{{ $record['name'] ?? 'N/A' }}</td>
                            <td>{{ $record['email'] ?? 'N/A' }}</td>
                            <td>{{ $record['plan_type'] ?? 'N/A' }}</td>
                            <td>{{ $record['start_date'] ?? 'N/A' }}</td>
                            <td class="text-danger" style="width:30%;">
                                {{ array_key_exists('error', $record) ? (is_array($record['error']) ? implode(', ', $record['error']) : $record['error']) : 'No error provided' }}
                            </td>
                            
                            
                           
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
            <button class="btn btn-danger btn-sm mt-2 mb-4" id="clearInvalidRecordsButton">Clear Invalid Records</button>
            <br>
            {{-- Trigger CSV Download Automatically --}}
            @if(session('autoExportCsv'))
            <script type="text/javascript">
                let exportProgressBar = document.getElementById('export-progress-bar');
                let exportProgressText = document.getElementById('export-progress-text');
                let exportrecordurl = "{{ Auth::guard('library')->check() ? route('library.export.invalid.records') : route('web.export.invalid.records') }}";

                // Set an interval to update the progress bar every 100 ms
                let progress = 0;
                let interval = setInterval(function() {
                    progress += 10;
                    exportProgressBar.value = progress;
                    exportProgressText.textContent = `Preparing download: ${progress}%`;

                    if (progress >= 100) {
                        clearInterval(interval);
                        window.location.href = exportrecordurl;
                    }
                }, 100); // 100 ms * 10 = 1 second, change as needed
            </script>
            @endif


            @endif
            <script>
                document.getElementById('clearInvalidRecordsButton').addEventListener('click', function() {
                    // Hide the invalid records section
                    document.getElementById('invalid-records-section').style.display = 'none';

                    // Reset the progress bar
                    let exportProgressBar = document.getElementById('export-progress-bar');
                    let exportProgressText = document.getElementById('export-progress-text');

                    if (exportProgressBar && exportProgressText) {
                        exportProgressBar.value = 0; // Reset progress to 0
                        exportProgressText.textContent = ""; // Clear text percentage
                    }

                    let clearSessionRoute = "{{ Auth::guard('library')->check() ? route('library.clear.session') : route('web.clear.session') }}";

                    // Send AJAX request to clear session
                    fetch(clearSessionRoute, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({})
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                console.log('Session cleared successfully.');
                            } else {
                                console.log('Failed to clear session.');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            </script>

        </div>
    </div>
</div>




<script>
    $(document).ready(function() {
        // Show Plan by default
        $(".plan").show();
        $(".plan-typ").hide();

        // Function to toggle sections and active class
        function toggleSection(showElement, hideElement, activeElement) {
            $(showElement).show();
            $(hideElement).hide();
            $("ul li").removeClass("active"); // Remove active class from all
            $(activeElement).addClass("active"); // Add active class to clicked one
        }

        // Event Listeners
        $("#show-plan").click(function() {
            toggleSection(".plan", ".plan-typ", this);
        });

        $("#show-plan-type").click(function() {
            toggleSection(".plan-typ", ".plan", this);
        });
    });
</script>
@endsection