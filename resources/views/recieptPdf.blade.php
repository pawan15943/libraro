<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Library Management Software Subscription Receipt</title>

<!-- Embed Outfit font in Base64 to avoid UFM errors -->
<style>
@php
  $fontRegular = base64_encode(file_get_contents(public_path('fonts/Outfit-Regular.ttf')));
  $fontMedium = base64_encode(file_get_contents(public_path('fonts/Outfit-Medium.ttf')));
  $fontSemiBold = base64_encode(file_get_contents(public_path('fonts/Outfit-SemiBold.ttf')));
  $fontBold = base64_encode(file_get_contents(public_path('fonts/Outfit-Bold.ttf')));
@endphp

@font-face {
  font-family: 'Outfit';
  src: url("data:font/truetype;charset=utf-8;base64,{{ $fontRegular }}") format('truetype');
  font-weight: 400;
  font-style: normal;
}
@font-face {
  font-family: 'Outfit';
  src: url("data:font/truetype;charset=utf-8;base64,{{ $fontMedium }}") format('truetype');
  font-weight: 500;
  font-style: normal;
}
@font-face {
  font-family: 'Outfit';
  src: url("data:font/truetype;charset=utf-8;base64,{{ $fontSemiBold }}") format('truetype');
  font-weight: 600;
  font-style: normal;
}
@font-face {
  font-family: 'Outfit';
  src: url("data:font/truetype;charset=utf-8;base64,{{ $fontBold }}") format('truetype');
  font-weight: 700;
  font-style: normal;
}

@page {
  size: A4;
  margin: 18mm;
}

@media print {
  html, body {
    width: 210mm;
    height: 297mm;
    margin: 0;
    background: #fff;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  .receipt {
    box-shadow: none !important;
    border: none !important;
    margin: 0;
    page-break-inside: avoid;
  }
}

body {
  font-family: 'Outfit', sans-serif;
  background: #f6f7f8;
  color: #1A2B50;
  display: flex;
  justify-content: center;
  margin: 0;
  padding: 0;
}

.receipt {
  width: 100%;
  max-width: 780px;
  background: #fff;
  border: 1px solid #d9dce1;
  box-shadow: 0 4px 16px rgba(0,0,0,0.05);
  border-radius: 8px;
  margin: 25px auto;
}

/* Header */
.header {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 24px 40px;
  border-bottom: 2px solid #244B7B;
  background: linear-gradient(180deg, #f9fafb 0%, #fff 100%);
}

.logo {
  width: 75px;
  height: 75px;
  border-radius: 50%;
  background: #fff;
  border: 1px solid #d9dce1;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.logo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.org-info {
  flex: 1;
}
.org-name {
  font-size: 20px;
  font-weight: 700;
  color: #244B7B;
}
.org-sub {
  font-size: 13px;
  color: #475569;
}

.header-right {
  text-align: right;
  font-size: 13px;
  color: #475569;
}
.header-right .site {
  color: #244B7B;
  font-weight: 600;
}

/* Body */
.body {
  padding: 30px 40px;
}

.title {
  font-size: 18px;
  font-weight: 700;
  color: #244B7B;
  border-bottom: 1px solid #d9dce1;
  padding-bottom: 8px;
  margin-bottom: 15px;
}

table {
  width: 100%;
  border-collapse: collapse;
}

td {
  padding: 10px 8px;
  font-size: 14px;
  vertical-align: top;
}

td:first-child {
  color: #475569;
  width: 30%;
  font-weight: 500;
}

td:last-child {
  color: #1A2B50;
  font-weight: 600;
}

tr {
  border-bottom: 1px solid #eef1f5;
}

.amount-card {
  background: linear-gradient(90deg, rgba(36,75,123,0.05), rgba(0,0,0,0.02));
  border: 1px solid #d9dce1;
  border-radius: 8px;
  text-align: center;
  padding: 18px;
  margin-top: 25px;
}
.amount-card .label {
  font-size: 13px;
  color: #475569;
}
.amount-card .amt {
  font-size: 24px;
  color: #244B7B;
  font-weight: 700;
  margin-top: 4px;
}

/* Terms */
.terms {
  background: #f9fafb;
  border-top: 1px solid #d9dce1;
  padding: 20px 40px;
  font-size: 13px;
  color: #1A2B50;
  line-height: 1.6;
}
.terms strong {
  display: block;
  color: #244B7B;
  margin-bottom: 6px;
}
.terms ul {
  margin: 8px 0 12px 18px;
}
.terms li {
  margin-bottom: 5px;
}

/* Footer */
.footer {
  text-align: center;
  padding: 18px 40px;
  border-top: 1px solid #d9dce1;
  font-size: 13px;
  color: #475569;
}
.footer strong {
  color: #244B7B;
}
.footer a {
  color: #244B7B;
  text-decoration: none;
}
</style>
</head>

<body>

<div class="receipt">

  <!-- Header -->
  <div class="header">
    <div class="logo">
      @php
          if($branch_logo && file_exists(public_path($branch_logo))){
              $logo = base64_encode(file_get_contents(public_path($branch_logo)));
          } else {
              $logo = base64_encode(file_get_contents(public_path('img/logo-socials.png')));
          }
      @endphp
      <img src="data:image/png;base64,{{ $logo }}" alt="Library Logo">
    </div>

    <div class="org-info">
      <div class="org-name">{{ $library_name ?? '' }}</div>
      <div class="org-sub">{{ $library_address ?? '' }}</div>
      <div class="org-sub">Email: {{ $library_email ?? '' }} • Contact: {{ $library_mobile ?? '' }}</div>
      @if($branch_slug)
        <div class="org-sub">Website: {{ url('library-detail/'.$branch_slug) }}</div>
      @else
        <div class="org-sub">Website: https://www.libraro.in/</div>
      @endif
    </div>

    <div class="header-right">
      <div style="font-weight:700;">Transaction Receipt</div>
      <div>Invoice #: <strong>{{ $invoice_ref_no ?? '' }}</strong></div>
      <div class="site">www.libraro.in</div>
    </div>
  </div>

  <!-- Body -->
  <div class="body">
    <div class="title">Receipt Details</div>
    <table>
      <tr>
        <td><b>Plan:</b></td>
        <td>{{ $subscription ?? '' }}</td>
        <td><b>Plan Start Date:</b></td>
        <td>{{ $transactiondate ?? '' }}</td>
      </tr>
      <tr>
        <td><b>Invoice Number:</b></td>
        <td>{{ $invoice_ref_no ?? '' }}</td>
        <td><b>Plan End Date:</b></td>
        <td>{{ $end_date ?? '' }}</td>
      </tr>
      <tr>
        <td><b>Name:</b></td>
        <td colspan="3">{{ $name ?? '' }}</td>
      </tr>
      <tr>
        <td><b>Email Address:</b></td>
        <td colspan="3">{{ $email ?? 'Not Updated Yet' }}</td>
      </tr>
      <tr>
        <td><b>Payment Type:</b></td>
        <td>
          @if(isset($payment_mode))
            @if($payment_mode == 1)
              Online
            @elseif($payment_mode == 2)
              Offline
            @else
              Pay Later
            @endif
          @endif
        </td>
        <td><b>Amount Paid:</b></td>
        <td>{{ $paid_amount ?? '' }}</td>
      </tr>
      <tr>
        <td><b>Total Amount:</b></td>
        <td>{{ $monthly_amount ?? '' }}</td>
        <td><b>Plan Duration:</b></td>
        <td><b>{{ $month ?? '' }} Month</b></td>
      </tr>
    </table>

    <div class="amount-card">
      <div class="label">Amount Paid</div>
      <div class="amt">₹{{ $paid_amount ?? '0.00' }}</div>
      <div style="margin-top:6px; font-size:13px; color:#475569;">Plan Duration: <strong>{{ $month ?? '' }} Month</strong></div>
    </div>
  </div>

  <!-- Terms -->
  <div class="terms">
    <strong>Terms & Conditions</strong>
    <ul>
      <li>This receipt is not a VAT Invoice.</li>
      <li>VAT Invoice will be provided upon request within 30 days.</li>
      <li>This is a computer-generated receipt; no signature is required.</li>
      <li>All subscription plans (Basic, Standard, and Premium) are non-refundable and non-transferable.</li>
      <li>Plan upgrades are available at any time with additional charges applied.</li>
    </ul>

    <strong>Refund Policy</strong>
    <p>No refunds will be issued once the subscription is activated. Please review your plan carefully before making a purchase.</p>
  </div>

  <!-- Footer -->
  <div class="footer">
    Thank you for choosing <strong>Libraro</strong> — Simplifying Library Management for Everyone.<br>
    Generated on: <strong>{{ now()->format('Y-m-d') }}</strong> |
    Need help? <a href="mailto:support@libraro.in">Contact Support</a>
  </div>

</div>

</body>
</html>
