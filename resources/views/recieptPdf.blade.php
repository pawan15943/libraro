<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Receipt</title>

    <style>
        @page {
            size: A4;
            margin: 0;
        }

        /* FORCE SYSTEM FONT – FIXES TIMES NEW ROMAN */
        * {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI",
                Roboto, Helvetica, Arial, sans-serif !important;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0 16px 16px;
            font-size: 13px;
            color: #1f2937;
        }

        /* ================= TITLE ================= */
        .receipt-title {
            text-align: center;
            padding: 14px 0;
        }

        .receipt-title h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.4px;
            color: #0f172a;
        }

        /* ================= HEADER ================= */
        .header {
            background: #f1f5fb;
            border: 1px solid #d6e0f0;
            border-radius: 10px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            overflow: hidden;
            border: 1px solid #d6e0f0;
            background: #fff;
        }

        .logo img {
            width: 100%;
            height: 100%;
        }

        .company-info h2 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }

        .company-info p {
            margin: 2px 0;
            font-size: 12.5px;
        }

        /* ================= TABLE ================= */
        .table-wrap {
            background: #f1f5fb;
            border-radius: 10px;
            padding: 6px;
            margin-top: 18px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px 14px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
            text-align: left;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* ================= STATUS ================= */
        .status-success {
            color: #15803d;
            font-weight: 600;
        }

        /* ================= TERMS ================= */
        .terms {
            margin-top: 18px;
        }

        .terms h4 {
            margin: 12px 0 6px;
            font-size: 14px;
        }

        .terms ul {
            margin: 0;
            padding-left: 18px;
        }

        .terms li {
            line-height: 22px;
            font-size: 12.5px;
        }

        /* ================= FOOTER ================= */
        .footer {
            margin-top: 22px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="receipt-title">
        <h1>Payment Receipt</h1>
    </div>

    @php
    if($branch_logo && file_exists(public_path($branch_logo))){
    $logo = base64_encode(file_get_contents(public_path($branch_logo)));
    } else {
    $logo = base64_encode(file_get_contents(public_path('img/logo-socials.png')));
    }
    @endphp

    <!-- ================= HEADER ================= -->
    <div class="header">
        <div class="logo mb-2">
            <img src="data:image/png;base64,{{ $logo }}">
        </div>
        <br>

        <div class="company-info">
            <h2>{{ $library_name ?? '' }}</h2>
            <p><b>Address:</b> {{ $library_address ?? '' }}</p>
            <p><b>Email:</b> {{ $library_email ?? '' }}</p>
            <p><b>Mobile:</b> {{ $library_mobile ?? '' }}</p>
            <p>
                <b>Website:</b>
                <a href="{{ $branch_slug ? url('library-detail/'.$branch_slug) : 'https://www.libraro.in/' }}">
                    click here
                </a>
            </p>
        </div>
    </div>

    <!-- ================= SUBSCRIBER ================= -->
    <div class="table-wrap">
        <table>
            <tr>
                <th colspan="4" class="section-title">Subscriber Details</th>
            </tr>
            <tr>
                <th style="width:25%">Name</th>
                <td colspan="3">{{ $name ?? '' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td colspan="3">{{ $email ?? 'Not Updated Yet' }}</td>
            </tr>
        </table>
    </div>

    <!-- ================= SUBSCRIPTION ================= -->
    <div class="table-wrap">
        <table>
            <tr>
                <th colspan="4" class="section-title">Subscription Details</th>
            </tr>
            <tr>
                <th style="width:25%">Plan</th>
                <td style="width:25%">{{ $subscription ?? '' }}</td>
                <th style="width:25%">Duration</th>
                <td style="width:25%">{{ $month ?? '' }} Month(s)</td>
            </tr>
            <tr>
                <th>Start Date</th>
                <td>{{ $transactiondate ?? '' }}</td>
                <th>End Date</th>
                <td>{{ $end_date ?? '' }}</td>
            </tr>
            <tr>
                <th>Shift Timing</th>
                <td colspan="3">{{ $shift_timing ?? 'Not Available' }}</td>
            </tr>
        </table>
    </div>

    <!-- ================= PAYMENT ================= -->
    <div class="table-wrap">
        <table>
            <tr>
                <th colspan="6" class="section-title">Payment Summary</th>
            </tr>
            <tr>
                <th>Invoice No</th>
                <td>{{ $invoice_ref_no ?? '' }}</td>
                <th>Payment Date</th>
                <td>{{ $transactiondate ?? '' }}</td>
                <th>Status</th>
                <td style="color: #15803d; font-weight: 700;">SUCCESS</td>
            </tr>
            <tr>
                <th>Payment Mode</th>
                <td>
                    @if($payment_mode == 1)
                    Online
                    @elseif($payment_mode == 2)
                    Offline
                    @else
                    Pay Later
                    @endif
                </td>
                <th>Total Amount</th>
                <td>{{ $monthly_amount ?? '' }}</td>
                <th>Amount Paid</th>
                <td style="color: #15803d; font-weight: 700;">SUCCESS</td>
            </tr>
        </table>
    </div>

    <!-- ================= TERMS ================= -->
    <div class="terms">
        <h4>Terms & Conditions</h4>
        <ul class="pdf_descContent">
            <li>This receipt is not a VAT Invoice.</li>
            <li>VAT Invoice will be provided upon request within 30 days.</li>
            <li>This is a computer-generated receipt; no signature is required.</li>
            <li>All subscription plans (Basic, Standard, and Premium) are non-refundable and non-transferable.</li>
            <li>Plan upgrades are available at any time with additional charges applied.</li>
        </ul>
        <h4>Refund Policy</h4>
        <ul class="pdf_descContent">
            <li>No refunds will be issued once the subscription is activated. Please review your plan carefully before making a purchase.</li>
        </ul>
    </div>

    <!-- ================= FOOTER ================= -->
    <div class="footer">
        <p><b>Website:</b> www.libraro.in | <b>Call Us:</b> +91-8114479678</p>
        <p><b>Head Office:</b> Kota, Rajasthan – 324005</p>
    </div>

</body>

</html>