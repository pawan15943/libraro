@extends('layouts.library')
@section('content')
<div id="reader" style="width:300px;margin:auto;"></div>
<p id="msg" class="mt-2"></p>

<script src="https://unpkg.com/html5-qrcode"></script>

<script>
let isScanning = false;
let scanner = new Html5Qrcode("reader");

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

        submitScan(qrText);

        setTimeout(() => isScanning = false, 1500);
    }
);

function submitScan(qr) {
    fetch("{{ route('library.attendance.scan') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ qr: qr })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('msg').innerText = res.message;
    })
    .catch(() => {
        document.getElementById('msg').innerText = 'Network issue. Try again.';
    });
}
</script>



@endsection