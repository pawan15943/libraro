<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>QR Attendance App</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Roboto:wght@400;500&display=swap"
    rel="stylesheet">

  <!-- Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- QR -->
  <script src="https://unpkg.com/html5-qrcode"></script>
  <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.11/dist/dotlottie-wc.js" type="module"></script>
  <style>
    :root {
      --navy: #000050;
      --navy-soft: #000050;
      --bg: #ececff;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --gradient: linear-gradient(135deg, #000050, #000050);
    }

    body {
      font-family: 'Outfit', sans-serif;
      background: var(--bg);
      padding-bottom: 90px;
    }

    h1,
    h2,
    h3,
    h4,
    h5 {
      font-family: 'Outfit', sans-serif;
    }

    p,
    span,
    small,
    a,
    li,
    td,
    div {
      font-family: 'Outfit', sans-serif;
    }

    h6.mt-3 {
      font-weight: 700;
      margin: 0;
      text-transform: uppercase;
    }

    /* HEADER */
    .app-header {
      background: var(--navy-soft);
      color: #fff;
      padding: 14px 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-radius: 0 0 22px 22px;
      flex-direction: column;
    }

    .app-header img {
      width: 150px;
    }

    /* SIDEBAR */
    .sidebar {
      position: fixed;
      inset: 0;
      background: var(--navy);
      color: #fff;
      padding: 24px;
      transform: translateX(-100%);
      transition: .35s ease;
      z-index: 2000;
    }

    .sidebar.active {
      transform: translateX(0);
    }

    .sidebar a+a {
      border-top: 1px solid #ababab47;
    }

    .sidebar a {
      display: flex;
      padding: 16px 0;
      color: #cbd5e1;
      font-size: 18px;
      text-decoration: none;
      gap: .8rem;
      align-items: center;
    }

    /* CARD */
    .app-card {
      background: var(--card);
      border-radius: 24px;
      padding: 18px;
      box-shadow: 0 8px 30px rgba(11, 28, 45, .08);
    }

    /* SECTIONS */
    .section {
      display: none;
    }

    .section.active {
      display: block;
    }

    /* ID CARD */
    .id-wrapper {
      perspective: 1200px;
    }

    .id-card {
      height: 260px;
      border-radius: 26px;
      transform-style: preserve-3d;
      transition: .8s;
    }

    .id-wrapper:hover .id-card {
      transform: rotateY(180deg);
    }

    .id-front,
    .id-back {
      position: absolute;
      inset: 0;
      border-radius: 26px;
      backface-visibility: hidden;
      padding: 20px;
      color: #000;
      height: 58vh;
      box-shadow: 1px 0 5px #00000029 !important;
    }

    .id-front {
      background: #fff;
    }

    .id-back {
      background: linear-gradient(135deg, #000050, #000050);
      transform: rotateY(180deg);
    }

    .status {
      background: rgba(34, 197, 94, .2);
      color: #22c55e;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
    }

    /* PROFILE */
    .profile-header {
      background: linear-gradient(135deg, #1e3a5f, #2563eb);
      color: #fff;
      border-radius: 18px;
      padding: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      flex-direction: column;
      text-align: center;
    }

    .flex {
      display: flex;
      justify-content: space-between;
      width: 100%;
      align-items: center;
    }

    .app-header .profile-list {
      margin-top: 16px;
      padding: 0;
      width: 100%;
      border-top: 1px solid #ffffff24;
      padding-top: .5rem;
      background: transparent;
      margin: 0;
      border-radius: 0;
    }

    .profile-header img {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      border: 2px solid rgba(255, 255, 255, .4);
    }

    .profile-list {
      margin-top: 16px;
      background: #fff;
      padding: 0 .8rem;
      border-radius: .8rem;
    }

    .libr_info {
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      text-align: left;
    }

    .libr_info small {
      font-size: .7rem;
    }

    .profile {
      display: flex;
      gap: .5rem;
      align-items: center;
    }

    .profile .picture {
      width: 44px;
      height: 44px;
      border-radius: 44px;
    }

    strong {
      font-weight: 600 !IMPORTANT;
      text-shadow: none;
      font-family: 'Outfit', 'sans-sarif';
      display: block;
      margin-bottom: -.2rem;
    }

    .profile-item {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      padding: 14px 0;
      border-bottom: 1px solid #e5e7eb;
    }

    .profile-item:last-child {
      border-bottom: none;
    }

    .profile-item i {
      color: #1e3a8a;
    }

    .profile-item small {
      color: var(--muted);
    }

    /* QR */
    #reader {
      width: 100%;
      height: 340px;
      border-radius: 20px;
      overflow: hidden;
    }

    .mt-3.d-flex.justify-content-between {
      flex-wrap: wrap;
      row-gap: 1rem;
    }

    .mt-3.d-flex.justify-content-between div {
      font-weight: 700;
    }

    .mt-3.d-flex.justify-content-between small {
      color: #8d8d8d;
    }

    /* BOTTOM NAV */
    .bottom-nav {
      position: fixed;
      bottom: 0;
      width: 100%;
      height: 60px;
      background: #fff;
      display: flex;
      justify-content: space-around;
      align-items: center;
      box-shadow: 0 -8px 24px rgba(0, 0, 0, .08);
    }

    .fab {
      width: 64px;
      height: 64px;
      background: var(--gradient);
      border-radius: 50%;
      margin-top: -34px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
    }
  </style>
</head>

<body>

  <!-- SIDEBAR -->
  <div class="sidebar" id="sidebar">
    <div class="d-flex justify-content-between mb-4">
      <h4>Menu</h4>
      <i data-lucide="x" onclick="toggleSidebar()"></i>
    </div>
    <a onclick="navigate('dashboard')"><i data-lucide="home"></i> Dashboard</a>
    <a onclick="navigate('profile')"><i data-lucide="user"></i> Profile</a>
    <a onclick="navigate('scan')"><i data-lucide="qr-code"></i> Scan QR</a>
    <a onclick="navigate('support')"><i data-lucide="phone"></i> Support</a>
  </div>

  @yield('content')


  <!-- BOTTOM NAV -->
  <div class="bottom-nav">
    <a onclick="navigate('profile')"><i data-lucide="user"></i></a>
    <a class="fab" onclick="navigate('scan')"><i data-lucide="qr-code"></i></a>
    <a onclick="navigate('support')"><i data-lucide="help-circle"></i></a>
  </div>


  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script>
/* =============================
   GLOBALS
============================= */
let scanner = null;
let scanLock = false;

const audioSuccess = new Audio("{{ asset('public/audio/success.mp3') }}");
const audioExpired = new Audio("{{ asset('public/audio/expired.mp3') }}");
const audioError   = new Audio("{{ asset('public/audio/error.mpeg') }}");

audioSuccess.preload = 'auto';
audioExpired.preload = 'auto';
audioError.preload   = 'auto';

/* =============================
   MESSAGE HANDLER
============================= */
function setScanMessage(message, type = 'success') {
    const el = document.getElementById('scanResult');
    el.innerText = message;
    el.classList.remove('text-success', 'text-danger', 'text-muted');
    el.classList.add(type === 'success' ? 'text-success' : 'text-danger');
}

/* =============================
   STOP SCANNER
============================= */
function stopScanner() {
    if (scanner) {
        return scanner.stop().then(() => {
            scanner.clear();
            scanner = null;
        });
    }
    return Promise.resolve();
}

/* =============================
   START SCANNER
============================= */
function startScanner() {

    scanLock = false;
    document.getElementById('scanResult').innerText = 'Waiting for scan...';
    document.getElementById('scanResult').className = 'text-muted text-center';

    scanner = new Html5Qrcode("reader");

    scanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 250 },
        qrText => {
            if (scanLock) return;
            scanLock = true;
            submitScan(qrText);
        }
    ).catch(err => {
        console.error('Camera error:', err);
        scanner = null;
    });
}

/* =============================
   SUBMIT SCAN
============================= */
function submitScan(qrText) {

    stopScanner().then(() => {

        fetch("{{ route('store.scan.attendance') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ qr: qrText })
        })
        .then(res => res.json())
        .then(res => {

            // hide all animations
            successAnimation.style.display = 'none';
            failedAnimation.style.display  = 'none';
            errorAnimation.style.display   = 'none';

            let animation, audio;

            if (res.status === 'success') {
                setScanMessage(res.message, 'success');
                animation = successAnimation;
                audio = audioSuccess;
            }
            else if (res.status === 'expired') {
                setScanMessage(res.message, 'danger');
                animation = failedAnimation;
                audio = audioExpired;
            }
            else {
                setScanMessage(res.message, 'danger');
                animation = errorAnimation;
                audio = audioError;
            }

            audio.play();

            document.getElementById('scanner-wrapper').style.display = 'none';
            animation.style.display = 'block';

            // restart after animation
            setTimeout(() => {
                animation.style.display = 'none';
                document.getElementById('scanner-wrapper').style.display = 'block';
                startScanner();
            }, 4000);
        })
        .catch(() => {
            setScanMessage('Network error. Try again.', 'danger');
            audioError.play();
            startScanner();
        });

    });
}

/* =============================
   AUTO START
============================= */
window.onload = startScanner;
</script>

</body>
</html>


</body>

</html>