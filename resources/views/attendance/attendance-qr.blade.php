@extends('layouts.library')

@section('title', 'Student Attendance Kiosk')

@section('content')
<script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.11/dist/dotlottie-wc.js" type="module"></script>
<link rel="stylesheet" href="{{ asset('public/css/attendance-kiosk.css') }}?v={{ time() }}">

<div class="attendance-kiosk-module" id="kioskRoot">

    <!-- ====================================================================
         Top Header & Kiosk Control Bar
         ==================================================================== -->
    <div class="kiosk-top-bar">
        <div class="kiosk-header-left">
            <div class="kiosk-icon-badge">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <div class="kiosk-title-area">
                <h4>Desk Attendance Kiosk</h4>
                <p>
                    <i class="fa-solid fa-building-columns text-teal me-1"></i>
                    <strong>{{ getCurrentBranchName() ?? 'Main Branch' }}</strong> &bull; Live Punch &amp; Verification Terminal
                </p>
            </div>
        </div>

        <div class="kiosk-header-right">
            <!-- Digital Clock with live pulsing dot -->
            <div class="kiosk-live-clock">
                <span class="kiosk-live-dot"></span>
                <span id="kioskDigitalClock">--:--:-- --</span>
            </div>

            <!-- Fullscreen Kiosk Toggle -->
            <button type="button" class="btn-fullscreen-toggle" id="toggleFullscreen" title="Toggle Fullscreen Mode for Reception Desks / Tablets">
                <i class="fa-solid fa-expand" id="fsIcon"></i>
                <span id="fsText">Fullscreen Kiosk</span>
            </button>
        </div>
    </div>

    <!-- ====================================================================
         Segmented Mode Switcher Tabs
         ==================================================================== -->
    <div class="kiosk-tab-wrapper">
        <div class="kiosk-tab-nav" role="tablist">
            <button class="kiosk-tab-btn active" data-bs-toggle="pill" data-bs-target="#qrTab" id="stopScanner" type="button" role="tab">
                <i class="fa-solid fa-mobile-screen-button"></i>
                <span>Student Phone QR Mode</span>
            </button>

            <button class="kiosk-tab-btn" data-bs-toggle="pill" data-bs-target="#scannerTab" id="startScanner" type="button" role="tab">
                <i class="fa-solid fa-id-card-clip"></i>
                <span>ID Card Camera Scanner</span>
            </button>
        </div>
    </div>

    <!-- ====================================================================
         Main Display Container
         ==================================================================== -->
    <div class="tab-content">

        <!-- ==========================
             TAB 1: QR ATTENDANCE
             ========================== -->
        <div class="tab-pane fade show active" id="qrTab" role="tabpanel">
            <div class="kiosk-main-card">
                <div class="kiosk-card-body qr-kiosk-container">

                    <!-- Title & Subtitle -->
                    <h5 class="fw-bold mb-1" style="color: #18225f; font-size: 1.25rem;">
                        Scan QR Code to Mark Attendance
                    </h5>
                    <p class="text-muted mb-3" style="font-size: 0.9rem; max-width: 540px;">
                        Point your mobile camera or open the Libraro Learner portal to punch your entry / exit in real time.
                    </p>

                    <!-- Frame with Accents -->
                    <div class="qr-frame-wrapper">
                        <span class="qr-corner-accent qr-corner-tl"></span>
                        <span class="qr-corner-accent qr-corner-tr"></span>
                        <span class="qr-corner-accent qr-corner-bl"></span>
                        <span class="qr-corner-accent qr-corner-br"></span>

                        <img id="qrImg" class="qr-img-tag" alt="Loading Attendance QR Code...">
                    </div>

                    <!-- 30-Second Refresh Countdown Pill -->
                    <div class="qr-timer-pill">
                        <span class="progress-dot"></span>
                        <span>Next QR refresh in: <strong id="qrCountdown">30s</strong></span>
                        <button type="button" class="btn-refresh-manual" id="btnManualRefresh" title="Fetch a new dynamic QR token immediately">
                            <i class="fa-solid fa-arrows-rotate"></i> Refresh Now
                        </button>
                    </div>

                    <!-- Anti-Proxy Security Callout -->
                    <div class="qr-security-banner">
                        <i class="fa-solid fa-shield-halved"></i>
                        <p>
                            <strong>Anti-Proxy Rolling Security:</strong> This QR code automatically refreshes every 30 seconds. Screenshots and forwarded photos are automatically invalidated to prevent false proxy attendance.
                        </p>
                    </div>

                    <!-- 3-Step Student Guidance Cards -->
                    <div class="kiosk-steps-row">
                        <div class="kiosk-step-card">
                            <div class="step-number-badge">1</div>
                            <div class="step-content">
                                <h6>Open Phone Camera / Portal</h6>
                                <p>Open your smartphone camera or log into the Libraro Learner dashboard on your mobile device.</p>
                            </div>
                        </div>

                        <div class="kiosk-step-card">
                            <div class="step-number-badge">2</div>
                            <div class="step-content">
                                <h6>Scan the Screen QR</h6>
                                <p>Frame the live QR code inside your phone screen. Keep your phone steady within 1 to 2 feet.</p>
                            </div>
                        </div>

                        <div class="kiosk-step-card">
                            <div class="step-number-badge">3</div>
                            <div class="step-content">
                                <h6>Instant Punch Recorded</h6>
                                <p>Your IN / OUT timestamp is logged immediately. Your seat status and validity are displayed on your phone.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ==========================
             TAB 2: ID CARD CAMERA SCANNER
             ========================== -->
        <div class="tab-pane fade" id="scannerTab" role="tabpanel">
            <div class="kiosk-main-card">
                <div class="kiosk-card-body scanner-kiosk-container">

                    <h5 class="fw-bold mb-1" style="color: #18225f; font-size: 1.25rem;">
                        Libraro ID Card Barcode / QR Scanner
                    </h5>
                    <p class="text-muted mb-3" style="font-size: 0.9rem; max-width: 580px;">
                        Hold the physical student ID card steady in front of the camera. The system will automatically detect the learner code, verify validity, and announce confirmation.
                    </p>

                    <!-- Camera Viewport Box -->
                    <div class="scanner-viewport-box">
                        <div id="scanner-wrapper">
                            <div id="reader"></div>
                        </div>

                        <!-- Targeting Reticle with moving laser line -->
                        <div class="scanner-reticle-overlay" id="scannerReticle">
                            <div class="laser-scan-line"></div>
                        </div>
                    </div>

                    <!-- Lottie Animations on Scan Result -->
                    <div id="successAnimation" style="display:none; text-align:center;" class="mb-3">
                        <dotlottie-wc src="https://lottie.host/79d3a6d1-4651-47a2-8204-6780dff68b52/BS5YmTvc3K.lottie" style="width: 260px; margin: 0 auto;" autoplay loop></dotlottie-wc>
                    </div>

                    <div id="failedAnimation" style="display:none; text-align:center;" class="mb-3">
                        <dotlottie-wc src="https://lottie.host/b8f1b3ee-de1b-4b39-ba80-4f3bc61b8f6b/GWE4EE0dMM.lottie" style="width: 260px; margin: 0 auto;" autoplay loop></dotlottie-wc>
                    </div>

                    <div id="errorAnimation" style="display:none; text-align:center;" class="mb-3">
                        <dotlottie-wc src="https://lottie.host/767cd45c-30a6-4317-b53b-e756f423efd8/7B9WsqgVFT.lottie" style="width: 260px; margin: 0 auto;" autoplay loop></dotlottie-wc>
                    </div>

                    <!-- Real-time Scan Result Message -->
                    <div id="scanMsg" class="scan-feedback-banner waiting">
                        <i class="fa-solid fa-camera me-1"></i> Waiting for ID Card scan...
                    </div>

                    <!-- Audio Status Legend for Reception Staff -->
                    <div class="audio-legend-card">
                        <div class="audio-legend-title">
                            <i class="fa-solid fa-volume-high text-teal"></i>
                            <span>Audio Confirmation Indicators</span>
                        </div>
                        <div class="audio-legend-grid">
                            <div class="audio-legend-item">
                                <span class="audio-dot green"></span>
                                <span><strong>Success Chime:</strong> Valid Entry/Exit</span>
                            </div>
                            <div class="audio-legend-item">
                                <span class="audio-dot amber"></span>
                                <span><strong>Extension Tone:</strong> Grace Period Active</span>
                            </div>
                            <div class="audio-legend-item">
                                <span class="audio-dot red"></span>
                                <span><strong>Warning Buzz:</strong> Plan Expired / Dues</span>
                            </div>
                            <div class="audio-legend-item">
                                <span class="audio-dot gray"></span>
                                <span><strong>Error Alert:</strong> Unregistered Card</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3-Step Receptionist Guidance Cards -->
                    <div class="kiosk-steps-row">
                        <div class="kiosk-step-card">
                            <div class="step-number-badge">1</div>
                            <div class="step-content">
                                <h6>Allow Camera Access</h6>
                                <p>Ensure the browser has permission to use your web camera or tablet back-facing camera.</p>
                            </div>
                        </div>

                        <div class="kiosk-step-card">
                            <div class="step-number-badge">2</div>
                            <div class="step-content">
                                <h6>Align Card in Target Box</h6>
                                <p>Hold the ID card barcode or QR code approximately 15–20 cm away from the camera lens inside the box.</p>
                            </div>
                        </div>

                        <div class="kiosk-step-card">
                            <div class="step-number-badge">3</div>
                            <div class="step-content">
                                <h6>Listen for Audio Chime</h6>
                                <p>The system auto-submits, records punch times, and resumes scanning after a 5-second confirmation.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- ====================================================================
         Kiosk Bottom Utility Dock
         ==================================================================== -->
    <div class="kiosk-dock-bar">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <a href="{{ route('attendance.instructions.pdf') }}" class="kiosk-dock-link" target="_blank">
                <i class="fa-solid fa-file-pdf text-danger"></i>
                <span>Download Student Attendance Guidelines (PDF)</span>
            </a>
            <span class="text-muted d-none d-md-inline">&bull;</span>
            <a href="{{ route('attendance.report') }}" class="kiosk-dock-link">
                <i class="fa-solid fa-chart-line text-primary"></i>
                <span>View Attendance Logs &amp; Reports</span>
            </a>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="toggleSoundBtn" title="Toggle audio feedback">
                <i class="fa-solid fa-volume-high me-1" id="soundIcon"></i>
                <span id="soundText">Sound: ON</span>
            </button>
        </div>
    </div>

</div>

<!-- Dependencies -->
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    /* ====================================================================
       1. DIGITAL CLOCK & FULLSCREEN TOGGLE
       ==================================================================== */
    function updateDigitalClock() {
        const now = new Date();
        const clockEl = document.getElementById('kioskDigitalClock');
        if (clockEl) {
            clockEl.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) +
                ' • ' + now.toLocaleDateString([], { weekday: 'short', day: 'numeric', month: 'short' });
        }
    }
    setInterval(updateDigitalClock, 1000);
    updateDigitalClock();

    // Fullscreen Kiosk Mode Handler
    const fsBtn = document.getElementById('toggleFullscreen');
    const fsIcon = document.getElementById('fsIcon');
    const fsText = document.getElementById('fsText');
    const kioskRoot = document.getElementById('kioskRoot');

    fsBtn.addEventListener('click', function() {
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
            if (kioskRoot.requestFullscreen) {
                kioskRoot.requestFullscreen();
            } else if (kioskRoot.webkitRequestFullscreen) {
                kioskRoot.webkitRequestFullscreen();
            }
            fsIcon.className = 'fa-solid fa-compress';
            fsText.textContent = 'Exit Fullscreen';
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }
            fsIcon.className = 'fa-solid fa-expand';
            fsText.textContent = 'Fullscreen Kiosk';
        }
    });

    document.addEventListener('fullscreenchange', function() {
        if (!document.fullscreenElement) {
            fsIcon.className = 'fa-solid fa-expand';
            fsText.textContent = 'Fullscreen Kiosk';
        }
    });

    /* ====================================================================
       2. AUDIO PLAYBACK & MUTE TOGGLE
       ==================================================================== */
    let isSoundMuted = false;
    const audioSuccess = new Audio("{{ asset('public/audio/success.mp3') }}");
    const audioExpired = new Audio("{{ asset('public/audio/expired.mp3') }}");
    const audioError = new Audio("{{ asset('public/audio/error.mpeg') }}");
    const audioExtension = new Audio("{{ asset('public/audio/extension.mp3') }}");

    audioSuccess.preload = 'auto';
    audioExpired.preload = 'auto';
    audioError.preload = 'auto';
    audioExtension.preload = 'auto';

    function playAudio(audioObj) {
        if (!isSoundMuted && audioObj) {
            audioObj.currentTime = 0;
            audioObj.play().catch(e => console.log('Audio autoplay prevented:', e));
        }
    }

    document.getElementById('toggleSoundBtn').addEventListener('click', function() {
        isSoundMuted = !isSoundMuted;
        const sIcon = document.getElementById('soundIcon');
        const sText = document.getElementById('soundText');
        if (isSoundMuted) {
            sIcon.className = 'fa-solid fa-volume-xmark me-1';
            sText.textContent = 'Sound: OFF';
            this.classList.replace('btn-outline-secondary', 'btn-outline-danger');
        } else {
            sIcon.className = 'fa-solid fa-volume-high me-1';
            sText.textContent = 'Sound: ON';
            this.classList.replace('btn-outline-danger', 'btn-outline-secondary');
        }
    });

    /* ====================================================================
       3. DYNAMIC QR CODE & 30-SECOND COUNTDOWN
       ==================================================================== */
    let backupQR = null;
    let qrCountdownVal = 30;
    let qrTimerInterval = null;

    function loadQR() {
        qrCountdownVal = 30;
        updateCountdownDisplay();

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

    function updateCountdownDisplay() {
        const cdEl = document.getElementById('qrCountdown');
        if (cdEl) {
            cdEl.textContent = qrCountdownVal + 's';
        }
    }

    // Tick countdown every second
    qrTimerInterval = setInterval(function() {
        qrCountdownVal--;
        if (qrCountdownVal <= 0) {
            loadQR();
        } else {
            updateCountdownDisplay();
        }
    }, 1000);

    // Initial QR Load
    loadQR();

    document.getElementById('btnManualRefresh').addEventListener('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Refreshing...');
        loadQR();
        setTimeout(() => {
            btn.prop('disabled', false).html('<i class="fa-solid fa-arrows-rotate"></i> Refresh Now');
        }, 1200);
    });

    /* ====================================================================
       4. HTML5 CAMERA SCANNER (ID CARD MODE)
       ==================================================================== */
    let scanner = null;
    let scanDone = false;

    function stopScanner() {
        if (scanner) {
            return scanner.stop()
                .then(() => {
                    scanner.clear();
                    scanner = null;
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
        setScanFeedback('Waiting for ID Card scan...', 'waiting');
        document.getElementById('scannerReticle').style.display = 'block';

        scanner = new Html5Qrcode("reader");

        scanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            (decodedText) => {
                if (scanDone) return;
                scanDone = true;
                setScanFeedback('ID detected! Verifying attendance...', 'waiting');
                submitScan(decodedText);
            }
        ).catch(err => {
            console.error('Camera error:', err);
            setScanFeedback('Camera access failed. Please enable camera permissions.', 'error');
            scanner = null;
        });
    }

    function setScanFeedback(message, type = 'waiting') {
        const msgEl = document.getElementById('scanMsg');
        msgEl.className = 'scan-feedback-banner ' + type;

        let icon = '<i class="fa-solid fa-camera me-1"></i> ';
        if (type === 'success') {
            icon = '<i class="fa-solid fa-circle-check text-success me-1"></i> ';
        } else if (type === 'error') {
            icon = '<i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> ';
        }

        msgEl.innerHTML = icon + message;
    }

    function submitScan(qrText) {
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
                const statusType = res.type || res.status;
                const isSuccess = res.status === true || statusType === 'success' || statusType === 'extension';

                if (isSuccess) {
                    setScanFeedback(res.message || 'Attendance punched successfully!', 'success');
                } else {
                    setScanFeedback(res.message || 'Attendance validation failed.', 'error');
                }

                // Hide all animations first
                const successAnim = document.getElementById('successAnimation');
                const failedAnim = document.getElementById('failedAnimation');
                const errorAnim = document.getElementById('errorAnimation');

                successAnim.style.display = 'none';
                failedAnim.style.display  = 'none';
                errorAnim.style.display   = 'none';

                let animation = errorAnim;
                let audio = audioError;

                if (statusType === 'success' || (res.status === true && statusType !== 'extension')) {
                    animation = successAnim;
                    audio = audioSuccess;
                } else if (statusType === 'expired') {
                    animation = failedAnim;
                    audio = audioExpired;
                } else if (statusType === 'extension') {
                    animation = successAnim;
                    audio = audioExtension;
                } else {
                    animation = errorAnim;
                    audio = audioError;
                }

                playAudio(audio);

                // Hide scanner UI and show Lottie animation
                document.getElementById('scanner-wrapper').style.display = 'none';
                document.getElementById('scannerReticle').style.display = 'none';
                animation.style.display = 'block';

                // Restart scanner AFTER 5-second animation delay
                setTimeout(() => {
                    animation.style.display = 'none';
                    document.getElementById('scanner-wrapper').style.display = 'block';
                    document.getElementById('scannerReticle').style.display = 'block';
                    scanDone = false;
                    startScanner();
                }, 5000);
            })
            .catch(() => {
                scanDone = false;
                playAudio(audioError);
                setScanFeedback('Network error while processing scan. Please try again.', 'error');
                startScanner();
            });
        });
    }

    /* ====================================================================
       5. TAB EVENT LISTENERS
       ==================================================================== */
    document.getElementById('startScanner').addEventListener('click', () => {
        stopScanner().then(startScanner);
    });

    document.getElementById('stopScanner').addEventListener('click', () => {
        stopScanner();
    });

    // Cleanup scanner when navigating away from Scanner tab
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).data('bs-target');
        if (target === '#qrTab' && scanner) {
            stopScanner();
        }
    });
</script>
@endsection