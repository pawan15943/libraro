@extends('layouts.library')
@section('content')
<div class="container mt-4">

    <h4 class="text-center mb-2">QR Based Attendance</h4>
    <p class="text-center text-muted">
        Apni attendance mark karne ke liye QR scan karein aur “Thank You” message aane tak wait karein.
    </p>

    <!-- Tabs -->
    <ul class="nav nav-pills justify-content-center mb-3">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#qrTab">
                QR
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#scannerTab">
                Scanner
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">

        <!-- QR TAB -->
        <div class="tab-pane fade show active text-center" id="qrTab">
            <img id="qrImg" class="img-fluid mb-2" style="max-width:260px;">
            <p class="text-muted small">QR refreshes every 5 seconds</p>
        </div> 

        <!-- SCANNER TAB -->
        <div class="tab-pane fade text-center" id="scannerTab">
            <button class="btn btn-primary" id="startScanner">
                Start Scanner
            </button>
            <div id="reader" style="width:300px;height:300px;margin:auto;"></div>
            <p id="scanMsg" class="mt-2"></p>
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
const audioSuccess = new Audio("{{ asset('audio/success.mp3') }}");

const audioExpired = new Audio("{{asset('public/audio/expired.mp3')}}");
const audioError   = new Audio("{{asset('public/audio/error.mp3')}}");
audioSuccess.preload = 'auto';
audioExpired.preload = 'auto';
audioError.preload   = 'auto';

/* ===============================
   QR TAB – jQuery AJAX
=================================*/

function loadQR() {
    $.ajax({
        url: '{{ route('attendance.qrcode') }}',
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            
            backupQR = data.fallback;
            showQR(data.primary);
        },
        error: function () {
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
document.getElementById('startScanner').addEventListener('click', function () {

    alert('Starting scanner');
    console.log('audioSuccess',audioSuccess);

    if (scanner) return;

    scanDone = false;
    document.getElementById('scanMsg').innerText = '';

    scanner = new Html5Qrcode("reader");

    scanner.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: 250
        },
        function (decodedText) {

            if (scanDone) return; // ✅ one scan only
            scanDone = true;

            console.log('SCANNED:', decodedText);

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
        body: JSON.stringify({ qr: qrText })
    })
    .then(res => res.json())
    .then(res => {

        // ✅ Show message
        document.getElementById('scanMsg').innerText = res.message;
         // 🔊 PLAY SOUND BASED ON MESSAGE
        if (res.message.toLowerCase().includes('attendance') ||
            res.message.toLowerCase().includes('punch')) {

            audioSuccess.play();

        } else if (res.message.toLowerCase().includes('expired')) {

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

$('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
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
