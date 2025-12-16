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
let isScanning = false;
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
   START SCANNER (SAFE WAY)
============================ */
document.getElementById('startScanner').addEventListener('click', function () {

    alert('Start Scanner clicked');

    // Ensure tab is visible
    const tab = new bootstrap.Tab(
        document.querySelector('button[data-bs-target="#scannerTab"]')
    );
    tab.show();

    // Delay is REQUIRED
    setTimeout(startScannerCamera, 300);
});

/* ============================
   CAMERA START
============================ */
function startScannerCamera() {

    alert('Starting camera');

    // Safety reset
    if (scanner) {
        scanner.stop().then(() => {
            scanner.clear();
            scanner = null;
            initScanner();
        });
    } else {
        initScanner();
    }
}

function initScanner() {

    scanner = new Html5Qrcode("reader");

    scanner.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            disableFlip: true
        },
        function (qrText) {

            if (isScanning) return;
            isScanning = true;

            document.getElementById('scanMsg').innerText =
                'QR detected. Processing...';

            submitScan(qrText);

            setTimeout(() => isScanning = false, 2000);
        }
    ).catch(err => {
        alert('Camera error: ' + err);
        scanner = null;
    });
}

/* ============================
   SUBMIT SCAN
============================ */
function submitScan(qrText) {
    alert("submitscan start");
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
        document.getElementById('scanMsg').innerText = res.message;
    })
    .catch(() => {
        document.getElementById('scanMsg').innerText =
            'Network error. Try again.';
    });
}

/* ============================
   STOP CAMERA ON TAB CHANGE
============================ */
document.querySelectorAll('button[data-bs-toggle="pill"]').forEach(btn => {
    btn.addEventListener('shown.bs.tab', function (e) {
        if (e.target.dataset.bsTarget !== '#scannerTab' && scanner) {
            scanner.stop().then(() => {
                scanner.clear();
                scanner = null;
            });
        }
    });
});

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
