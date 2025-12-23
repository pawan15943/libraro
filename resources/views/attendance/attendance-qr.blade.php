@extends('layouts.library')
@section('content')
<script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.11/dist/dotlottie-wc.js" type="module"></script>
<div class="container mt-4">

    <h4 class="text-center mb-4">Student QR Attendance</h4>

    <!-- Tabs -->
    <ul class="nav nav-pills justify-content-center mb-3">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#qrTab">
                Attendance Via QR
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#scannerTab" id="startScanner">
                Attendeance Via ID Card
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">

        <!-- QR TAB -->
        <div class="tab-pane fade show active text-center" id="qrTab">
            <p class="text-center text-muted">Scan this QR to Mark your Attendance</p>
            <img id="qrImg" class="img-fluid mb-2" style="max-width:260px;">
            <p id="qrMsg" class="mt-2">
                <dotlottie-wc src="https://lottie.host/79d3a6d1-4651-47a2-8204-6780dff68b52/BS5YmTvc3K.lottie" style="width: 300px;height: 300px" autoplay loop></dotlottie-wc>
            </p>
        </div>

        <!-- SCANNER TAB -->
        <div class="tab-pane fade text-center" id="scannerTab">
            <p class="text-center text-muted">Show your ID card to scanner to mark your Attendance</p>
            <div id="scanner-wrapper">
                <div id="reader"></div>
            </div>
            <!-- <div id="reader" style="width:300px;height:300px;margin:auto;"></div>
            <button class="btn btn-primary" id="startScanner"> Close Scanner </button> -->
            <p id="scanMsg" class="mt-2">
                <dotlottie-wc src="https://lottie.host/79d3a6d1-4651-47a2-8204-6780dff68b52/BS5YmTvc3K.lottie" style="width: 300px;height: 300px" autoplay loop></dotlottie-wc>
            </p>
        </div>

    </div>
</div>


<script src="https://unpkg.com/html5-qrcode"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


<script>
    let backupQR = null;
    let qrInterval = null;
    let scanner = null;
    let scanDone = false;
    const audioSuccess = new Audio("{{ asset('public/audio/success.mp3') }}");
    const audioExpired = new Audio("{{asset('public/audio/expired.mp3')}}");
    const audioError = new Audio("{{asset('public/audio/error.mp3')}}");
    audioSuccess.preload = 'auto';
    audioExpired.preload = 'auto';
    audioError.preload = 'auto';

    /* ===============================
       QR TAB – jQuery AJAX
    =================================*/

    function loadQR() {
        $.ajax({
            url: '{{ route('
            attendance.qrcode ') }}',
            type: 'GET',
            dataType: 'json',
            success: function(data) {

                backupQR = data.fallback;
                showQR(data.primary);
            },
            error: function() {
                if (backupQR) showQR(backupQR);
            }
        });
    }

    function showQR(token) {
        $('#qrImg').attr(
            'src',
            'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' + encodeURIComponent(token)
        );
    }

    // Start QR refresh
    loadQR();
    qrInterval = setInterval(loadQR, 5000);


    /* ============================
       START SCANNER
    ============================ */
    document.getElementById('startScanner').addEventListener('click', function() {

        if (scanner) return;
        scanDone = false;
        document.getElementById('scanMsg').innerText = '';

        scanner = new Html5Qrcode("reader");

        scanner.start({
                facingMode: "environment"
            }, {
                fps: 10,
                qrbox: 250
            },
            function(decodedText) {

                if (scanDone) return; // ✅ one scan only
                scanDone = true;
                document.getElementById('scanMsg').innerText =
                    'QR detected. Processing...';
                submitScan(decodedText);
            }
        ).catch(err => {
            alert('Camera error: ' + err);
            scanner = null;
        });
    });

    /* ============================
       SUBMIT SCAN (SINGLE VERSION)
    ============================ */
    function submitScan(qrText) {
        alert('submit scanner');
        fetch("{{ route('library.attendance.scan') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    qr: qrText
                })
            })
            .then(res => res.json())
            .then(res => {

                // ✅ Show message
                document.getElementById('scanMsg').innerText = res.message;
                // 🔊 PLAY SOUND BASED ON MESSAGE
                if (res.status === 'success') {
                    audioSuccess.play();
                } else if (res.status === 'expired') {
                    audioExpired.play();
                } else {
                    audioError.play();
                }


                // ✅ Stop scanner properly
                if (scanner) {
                    scanner.stop().then(() => {
                        scanner.clear();
                        scanner = null;
                    });
                }
            })
            .catch(() => {

                document.getElementById('scanMsg').innerText =
                    'Network error. Try again.';
                audioError.play();

                if (scanner) {
                    scanner.stop().then(() => {
                        scanner.clear();
                        scanner = null;
                    });
                }
            });
    }

    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
        let target = $(e.target).data('bs-target');

        if (target === '#qrTab' && scanner) {
            scanner.stop().then(() => {
                scanner.clear();
                scanner = null;
            });
        }
    });
</script>
@endsection