<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Branch QR Code</title>
    <style>
        body {
            text-align: center;
            font-family: sans-serif;
            margin: 50px;
        }
        .qr-code svg {
            width: 350px;
            height: 350px;
        }
    </style>
</head>
<body>
    <h2>Branch QR Code</h2>
   
    <div class="qr-code">
         <img src="data:image/png;base64,{{ $qrCode }}" alt="Branch QR Code">
    </div>
    
</body>
</html>
