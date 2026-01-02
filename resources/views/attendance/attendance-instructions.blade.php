<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>
@page {
    size: A4 landscape;
    margin: 15mm;
}

body {
    font-family: notodeva, sans-serif;
    font-size: 14px;
    margin: 0;
    padding: 0;
    background: #ffffff;
}

.page {
    border: 3px solid #0b4aa2;
    padding: 15px;
}

.header {
    text-align: center;
    margin-bottom: 12px;
}

.header h1 {
    margin: 0;
    font-size: 22px;
    color: #0b4aa2;
}

.header p {
    margin: 4px 0 0;
    font-size: 13px;
    color: #555;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.col {
    width: 33.33%;
    vertical-align: top;
    border: 2px solid #dce3f0;
    padding: 12px;
}

.col h2 {
    margin-top: 0;
    font-size: 16px;
    color: #0b4aa2;
    border-bottom: 2px solid #0b4aa2;
    padding-bottom: 5px;
}

.col ul {
    padding-left: 18px;
    line-height: 1.6;
}
</style>
</head>

<body>
<div class="page">

    <div class="header">
        <h1>Attendance QR App – User Instructions</h1>
        <p>Please read the instructions carefully</p>
    </div>

    <table class="table">
        <tr>
            <td class="col">
                <h2>English Instructions</h2>
                <ul>
                    <li>Download and install the Attendance QR App.</li>
                    <li>Allow camera permission.</li>
                    <li>Scan the QR code at the library.</li>
                    <li>Wait for confirmation.</li>
                    <li>Do not scan multiple times.</li>
                </ul>
            </td>

            <td class="col">
                <h2>हिंदी निर्देश</h2>
                <ul>
                    <li>Attendance QR App डाउनलोड करें।</li>
                    <li>कैमरा की अनुमति दें।</li>
                    <li>लाइब्रेरी में QR कोड स्कैन करें।</li>
                    <li>पुष्टि संदेश की प्रतीक्षा करें।</li>
                    <li>बार-बार QR स्कैन न करें।</li>
                </ul>
            </td>

            <td class="col" style="text-align:center;">
                <h2>Download App</h2>
                {!! QrCode::size(140)->generate(route('qr.attendance.link')) !!}
                <p style="font-size:12px;">Scan to download</p>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
