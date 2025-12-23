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

    .hidden {
        display: none !important;
    }
</style>

<div class="container mt-4 text-center">

    <h4 class="mb-4">Student QR Attendance</h4>

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

    <div class="tab-content">

        <!-- ================= QR TAB ================= -->
        <div class="tab-pane fade show active" id="qrTab">

            <p class="text-muted">Scan the QR code below to mark your attendance</p>

            <div id="qrSection">
                <img id="qrImg" class="img-fluid mb-2" style="max-width:260px;">
            </div>

            <div id="qrResult" class="hidden">
                <dotlottie-wc
                    src="https://lottie.host/4c3bdb24-0a6f-47c4-9e92-0dbe7a0fbc7a/success.lottie"
                    style="width:200px;height:200px"
                    autoplay>
                </dotlottie-wc>
                <h6 class="mt-2 text-success" id="qrResultText"></h6>
            </div>

        </div>

        <!-- ================= SCANNER TAB ================= -->
        <div class="tab-pane fade" id="scannerTab">

            <p class="text-muted">
                Please present your ID card to the scanner
            </p>

            <div id="scannerSection">
                <div id="reader"></div>
                <button class="btn btn-sm btn-outline-danger mt-2" id="manualStop">
                    Stop Scanner
                </button>
            </div>

            <div id="scannerResult" class="hidden">
                <dotlottie-wc
                    src="https://lottie.host/4c3bdb24-0a6f-47c4-9e92-0dbe7a0fbc7a/success.lottie"
                    style="width:200px;height:200px"
                    autoplay>
                </dotlottie-wc>
                <h6 class="mt-2 text-success" id="scannerResultText"></h6>
            </div>

        </div>

    </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    let scanner = null;
    let scanDone = false;
    let scannerTimeout = null;

    /* ================= QR ================= */
    function loadQR() {
        $.get("{{ route('attendance.qrcode') }}", function (data) {
            $('#qrImg').attr(
                'src',
                'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' +
                encodeURIComponent(data.primary)
            );
        });
    }

    loadQR();
    setInterval(loadQR, 5000);

    function showQRResult(message) {
        $('#qrSection').addClass('hidden');
        $('#qrResultText').text(message);
        $('#qrResult').removeClass('hidden');

        setTimeout(() => {
            $('#qrResult').addClass('hidden');
            $('#qrSection').removeClass('hidden');
        }, 3000);
    }

    /* ================= SCANNER ================= */
    function startScanner() {
        if (scanner) return;

        scanDone = false;
        scanner = new Html5Qrcode("reader");

        scanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            function (decodedText) {

                if (scanDone) return;
                scanDone = true;

                submitScan(decodedText);
            }
        );

        scannerTimeout = setTimeout(stopScanner, 120000); // 2 min
    }

    function stopScanner() {
        if (scanner) {
            scanner.stop().then(() => {
                scanner.clear();
                scanner = null;
                $('#reader').html('');
            });
        }
        clearTimeout(scannerTimeout);
    }

    $('#manualStop').on('click', stopScanner);

    function showScannerResult(message) {
        $('#scannerSection').addClass('hidden');
        $('#scannerResultText').text(message);
        $('#scannerResult').removeClass('hidden');

        setTimeout(() => {
            $('#scannerResult').addClass('hidden');
            $('#scannerSection').removeClass('hidden');
            startScanner();
        }, 3000);
    }

    /* ================= SUBMIT ================= */
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

            if ($('#qrTab').hasClass('active')) {
                showQRResult(res.message);
            } else {
                stopScanner();
                showScannerResult(res.message);
            }

        })
        .catch(() => {
            alert('Network error');
        });
    }

    $('#startScanner').on('click', startScanner);
    $('#stopScanner').on('click', stopScanner);
</script>

@endsection
