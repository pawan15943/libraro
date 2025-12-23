@extends('layouts.library')
@section('content')

<script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.11/dist/dotlottie-wc.js" type="module"></script>

<style>
    #reader {
        max-width: 300px;
        margin: auto;
        border-radius: 12px;
        overflow: hidden;
    }

    #successOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: #ffffff;
        z-index: 9999;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
</style>

<div class="container mt-4">

    <h4 class="text-center mb-4">Student QR Attendance</h4>

    <!-- Tabs -->
    <ul class="nav nav-pills justify-content-center mb-3">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#qrTab" id="stopScanner">
                QR Attendance
            </button>
        </li>

        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#scannerTab" id="startScanner">
                ID Card Attendance
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">

        <!-- QR TAB -->
        <div class="tab-pane fade show active text-center" id="qrTab">
            <p class="text-muted">Scan the QR code below to mark your attendance</p>
            <img id="qrImg" class="img-fluid mb-2" style="max-width:260px;">
            <p id="qrMsg" class="mt-2">
                <dotlottie-wc src="https://lottie.host/79d3a6d1-4651-47a2-8204-6780dff68b52/BS5YmTvc3K.lottie"
                    style="width: 300px;height: 300px" autoplay loop>
                </dotlottie-wc>
            </p>
        </div>

        <!-- SCANNER TAB -->
        <div class="tab-pane fade text-center" id="scannerTab">
            <p class="text-muted">
                Please present your ID card to the scanner to mark your attendance
            </p>

            <div id="scanner-wrapper">
                <div id="reader"></div>
            </div>

            <p id="scanMsg" class="mt-2">
                <dotlottie-wc src="https://lottie.host/79d3a6d1-4651-47a2-8204-6780dff68b52/BS5YmTvc3K.lottie"
                    style="width: 300px;height: 300px" autoplay loop>
                </dotlottie-wc>
            </p>
        </div>

    </div>
</div>

<!-- SUCCESS OVERLAY -->
<div id="successOverlay">
    <dotlottie-wc
        src="https://lottie.host/4c3bdb24-0a6f-47c4-9e92-0dbe7a0fbc7a/success.lottie"
        style="width:220px;height:220px"
        autoplay>
    </dotlottie-wc>
    <h5 class="mt-3 text-success fw-semibold">
        Attendance Marked Successfully
    </h5>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    let backupQR = null;
    let qrInterval = null;
    let scanner = null;
    let scanDone = false;

    const audioSuccess = new Audio("{{ asset('public/audio/success.mp3') }}");
    const audioExpired = new Audio("{{ asset('public/audio/expired.mp3') }}");
    const audioError   = new Audio("{{ asset('public/audio/error.mp3') }}");

    audioSuccess.preload = 'auto';
    audioExpired.preload = 'auto';
    audioError.preload   = 'auto';

    function stopAllAudio() {
        [audioSuccess, audioExpired, audioError].forEach(a => {
            a.pause();
            a.currentTime = 0;
        });
    }

    function showSuccessAnimation(callback) {
        const overlay = document.getElementById('successOverlay');
        overlay.style.display = 'flex';

        setTimeout(() => {
            overlay.style.display = 'none';
            if (typeof callback === 'function') callback();
        }, 2000);
    }

    /* ===============================
       QR TAB
    =============================== */
    function loadQR() {
        $.ajax({
            url: "{{ route('attendance.qrcode') }}",
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

    loadQR();
    qrInterval = setInterval(loadQR, 5000);

    /* ===============================
       STOP SCANNER
    =============================== */
    document.getElementById('stopScanner').addEventListener('click', function() {
        if (scanner) {
            scanner.stop().then(() => {
                scanner.clear();
                scanner = null;
                document.getElementById('reader').innerHTML = '';
            });
        }
    });

    /* ===============================
       START SCANNER
    =============================== */
    document.getElementById('startScanner').addEventListener('click', function() {

        if (scanner) return;

        scanDone = false;
        document.getElementById('scanMsg').innerText = '';

        scanner = new Html5Qrcode("reader");

        scanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            function(decodedText) {

                if (scanDone) return;
                scanDone = true;

                document.getElementById('scanMsg').innerText =
                    'QR detected. Please wait...';

                submitScan(decodedText);
            }
        ).catch(err => {
            alert('Camera error: ' + err);
            scanner = null;
        });
    });

    /* ===============================
       SUBMIT SCAN
    =============================== */
    function submitScan(qrText) {

        fetch("{{ route('library.attendance.scan') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ qr: qrText })
        })
        .then(res => res.json())
        .then(res => {

            stopAllAudio();

            if (res.status === 'success') {
                audioSuccess.play();

                showSuccessAnimation(() => {
                    document.getElementById('scanMsg').innerText = res.message;
                });

            } else if (res.status === 'expired') {
                audioExpired.play();
                document.getElementById('scanMsg').innerText = res.message;
            } else {
                audioError.play();
                document.getElementById('scanMsg').innerText = res.message;
            }

            if (scanner) {
                scanner.stop().then(() => {
                    scanner.clear();
                    scanner = null;
                    document.getElementById('reader').innerHTML = '';
                });
            }
        })
        .catch(() => {

            stopAllAudio();
            audioError.play();

            document.getElementById('scanMsg').innerText =
                'Network issue detected. Please try again.';

            if (scanner) {
                scanner.stop().then(() => {
                    scanner.clear();
                    scanner = null;
                    document.getElementById('reader').innerHTML = '';
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
                document.getElementById('reader').innerHTML = '';
            });
        }
    });
</script>
@endsection
