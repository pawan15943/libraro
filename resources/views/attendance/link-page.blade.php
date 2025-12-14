@extends('sitelayouts.layout')
@section('content')
<div class="sacnd-data py-5" style="min-height: 500px; display:flex; align-items:center;">
    <div class="container">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-lg-3">
                <div class="process-step-1">
                    <div class="action-content">
                        <div class="headings text-center">
                            <h4 class="mb-4">What would you like to do?</h4>
                            <h2> Library</h2>
                            <span class="text-message">Please Fill and proceed.</span>
                        </div>
                        <ul class="action-list">
                            <li><input type="text" id="learner_no_uid" placeholder="Learner No."></li>
                            <li><input type="text" id="learner_mobile" placeholder="Mobile Number"></li>
                          
                        </ul>
                        <button id="verifyLearner">Next</button>

                        <div id="verifyMsg"></div>

                        <button id="startScannerBtn" class="btn btn-primary mt-3" style="display:none;">
                            Start Scanner
                        </button>

                        <div id="reader" style="width:300px;height:300px;"></div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    $('#verifyLearner').on('click', function () {
       
        $.ajax({
            url: "{{ route('attendance.verify.learner') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                uid: $('#learner_no_uid').val(),
                mobile: $('#learner_mobile').val()
            },
            success: function (res) {
                localStorage.setItem('verify_token', res.verify_token);
                $('#verifyMsg').text('Verified. Opening scanner...');
                $('#startScannerBtn').show();
            },
            error: function (xhr) {
                $('#verifyMsg').text(xhr.responseJSON.message);
            }
        });
    }); 
    $(document).ready(function () {
        $('#startScannerBtn').on('click', function () {
            alert('Start Scanner button clicked');
            openScanner(); // ✅ allowed on mobile
        });
    });
    
    let scanner = null;

    function openScanner() {
        alert('openScanner() called');

        // 🔴 Ensure library loaded
        if (typeof Html5Qrcode === 'undefined') {
            alert('Html5Qrcode NOT loaded');
            return;
        }

        // 🔴 Ensure container exists & visible
        const reader = document.getElementById('reader');
        if (!reader || reader.offsetHeight === 0) {
            alert('Scanner container not visible');
            return;
        }

        // 🔴 Always reset scanner before start
        if (scanner) {
            scanner.stop().then(() => {
                scanner.clear();
                scanner = null;
                startCamera();
            }).catch(() => {
                scanner = null;
                startCamera();
            });
        } else {
            startCamera();
        }
    }

    function startCamera() {
        alert('Starting camera');

        scanner = new Html5Qrcode("reader");

        scanner.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                disableFlip: true
            },
            function (decodedText) {
                submitScan(decodedText);
            }
        ).catch(err => {
            alert('Camera error: ' + err);
            scanner = null;
        });
    }

    function submitScan(qrText) {
        $.ajax({
            url: "{{ route('store.scan.attendance') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                qr: qrText,
                verify_token: localStorage.getItem('verify_token')
            },
            success: function (res) {
                $('#scanMsg').text(res.message).addClass('text-success');
                scanner.stop();
            },
            error: function (xhr) {
                $('#scanMsg').text(xhr.responseJSON.message).addClass('text-danger');
            }
        });
    }


</script>

@endsection
