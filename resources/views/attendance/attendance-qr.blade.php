@extends('layouts.library')
@section('content')
<script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.11/dist/dotlottie-wc.js" type="module"></script>
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
            <p class="text-center text-muted">Scan this QR to Mark your Attendance</p>
            <img id="qrImg" class="img-fluid mb-2" style="max-width:300px;">
            <p id="qrMsg" class="mt-2">
              
            </p>
        </div>

        <!-- SCANNER TAB -->
        <div class="tab-pane fade text-center" id="scannerTab">
            <p class="text-center text-muted">Show your ID card to scanner to mark your Attendance</p>
            <div id="scanner-wrapper">
                <div id="reader"></div>
              
            </div>
            <div id="successAnimation" style="display:none; text-align:center;" class="mb-4">
                <dotlottie-wc src="https://lottie.host/79d3a6d1-4651-47a2-8204-6780dff68b52/BS5YmTvc3K.lottie" style="width: 300px; margin: 0 auto;"  autoplay loop></dotlottie-wc>
            </div>
            <div id="failedAnimation" style="display:none; text-align:center;" class="mb-4">
                <dotlottie-wc src="https://lottie.host/b8f1b3ee-de1b-4b39-ba80-4f3bc61b8f6b/GWE4EE0dMM.lottie" style="width: 300px; margin: 0 auto;" autoplay loop></dotlottie-wc>
            </div>
            <div id="errorAnimation" style="display:none; text-align:center;" class="mb-4">
                <dotlottie-wc src="https://lottie.host/767cd45c-30a6-4317-b53b-e756f423efd8/7B9WsqgVFT.lottie" style="width: 300px; margin: 0 auto;" autoplay loop></dotlottie-wc>
            </div>
           
            <p id="scanMsg" class="mt-2" ></p>
            

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
    const audioError = new Audio("{{asset('public/audio/error.mpeg')}}");
    audioSuccess.preload = 'auto';
    audioExpired.preload = 'auto';
    audioError.preload = 'auto';

    /* ===============================
       QR TAB – jQuery AJAX
    =================================*/

    function loadQR() {
        $.ajax({
            url: "{{ route( 'attendance.qrcode' ) }}",
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
    qrInterval = setInterval(loadQR, 30000);


    /* ============================
       START SCANNER
    ============================ */
    function stopScanner() {
        if (scanner) {
            return scanner.stop()
                .then(() => {
                    scanner.clear();
                    scanner = null;
                    console.log('Scanner stopped');
                })
                .catch(err => {
                    console.error('Stop error:', err);
                    scanner = null;
                });
        }
        return Promise.resolve();
    }

    function startScanner() {

        scanDone = false;
        document.getElementById('scanMsg').innerText = '';

        scanner = new Html5Qrcode("reader");

        scanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            (decodedText) => {

                if (scanDone) return;

                scanDone = true;
                document.getElementById('scanMsg').innerText = 'QR detected. Processing...';

                submitScan(decodedText);
            }
        ).catch(err => {
            console.error('Camera error:', err);
            scanner = null;
        });
    }
    function setScanMessage(message, type = 'success') {
        const msgEl = document.getElementById('scanMsg');

        msgEl.innerText = message;

        // Remove old classes
        msgEl.classList.remove('text-success', 'text-danger');

        // Add new class
        if (type === 'success') {
            msgEl.classList.add('text-success');
        } else {
            msgEl.classList.add('text-danger');
        }
    }


    /* ============================
    SUBMIT SCAN (SAFE FLOW)
    ============================ */
    function submitScan(qrText) {

        // 🚨 MUST stop camera before API
        stopScanner().then(() => {

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

                const scanMsg = document.getElementById('scanMsg');
                if (res.status === 'success') {
                    setScanMessage(res.message, 'success');
                } else {
                    setScanMessage(res.message, 'danger');
                }


                // Hide all animations
                successAnimation.style.display = 'none';
                failedAnimation.style.display  = 'none';
                errorAnimation.style.display   = 'none';

                let animation;
                let audio;

                if (res.status === 'success') {
                    animation = successAnimation;
                    audio = audioSuccess;
                } 
                else if (res.status === 'expired') {
                    animation = failedAnimation;
                    audio = audioExpired;
                } 
                else {
                    animation = errorAnimation;
                    audio = audioError;
                }

                audio.play();

                // Hide scanner UI
                document.getElementById('scanner-wrapper').style.display = 'none';
                animation.style.display = 'block';

                // 🔁 Restart scanner AFTER animation
                setTimeout(() => {
                    animation.style.display = 'none';
                    document.getElementById('scanner-wrapper').style.display = 'block';
                    scanDone = false;
                    startScanner();
                }, 5000);

            })
            .catch(() => {
                scanDone = false;
                audioError.play();
                startScanner();
            });

        });
    }

    /* ============================
    TAB BUTTON HANDLERS
    ============================ */
    document.getElementById('startScanner').addEventListener('click', () => {
        stopScanner().then(startScanner);
    });

    document.getElementById('stopScanner').addEventListener('click', () => {
        stopScanner();
    });

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