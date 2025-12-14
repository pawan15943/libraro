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

                        <div id="reader" class="mt-3"></div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
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
    $('#startScannerBtn').on('click', function () {
        openScanner(); // ✅ allowed on mobile
    });

    $(document).ready(function () {
        let scanner = null;

        function openScanner() {
            if (scanner) return;

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
                alert('Camera permission denied or unavailable');
                console.error(err);
            });
        }


    });
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
