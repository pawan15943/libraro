@extends('layouts.library')
@section('content')
<div id="reader" style="width:300px"></div>
<p id="msg"></p>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
let isScanning = false;

const scanner = new Html5Qrcode("reader");

scanner.start(
    { facingMode: "environment" },
    {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        disableFlip: true
    },
    qrText => {
        if (isScanning) return;
        isScanning = true;
        submitScan(qrText);
        setTimeout(() => isScanning = false, 1500);
    }
);

function submitScan(qr) {
    fetch('/attendance/scan', {
        method: 'POST',
        headers: {
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':'{{ csrf_token() }}'
        },
        body: JSON.stringify({
            qr: qr,
            uid: localStorage.getItem('uid'),
            mobile: localStorage.getItem('mobile')
        })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('msg').innerText = res.message;
    })
    .catch(() => {
        saveOffline(qr);
        document.getElementById('msg').innerText = 'Saved offline';
    });
}
</script>


@endsection