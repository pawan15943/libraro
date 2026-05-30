<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library Receipt</title>
    <style>
        @page { size: A4; margin: 14mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #334e72; font-size: 11px; margin: 0; }
        .wrap { width: 100%; }
        .section { border: 1px solid #d7dfec; border-radius: 10px; margin-bottom: 14px; padding: 14px 16px; page-break-inside: avoid; }
        .title { font-size: 10px; letter-spacing: 1.8px; color: #be123c; font-weight: 700; margin-bottom: 10px; }
        .muted { color: #5b6b86; }
        .header-table, .info-table, .pay-history, .sum-table { width: 100%; border-collapse: collapse; }
        .header-wrap { border-bottom: 1px solid #d7dfec; margin-bottom: 14px; padding-bottom: 10px; }
        .header-table td { vertical-align: top; }
        .logo-circle {
            width: 50px; height: 50px;
            min-width: 50px; min-height: 50px; max-width: 50px; max-height: 50px;
            border-radius: 25px;
            border: 1px solid #fecdd3; background: #ffe4e6; color: #9f1239;
            text-align: center; line-height: 48px; font-size: 26px; font-weight: 700;
            display: inline-block;
            overflow: hidden;
        }
        .lib-name { font-size: 19px; line-height: 1.1; font-weight: 700; color: #0f2747; }
        .invoice-no { color: #be123c; font-weight: 700; font-size: 12px; }
        .right { text-align: right; }
        .due-box {
            border: 1px solid #fecdd3; background: #fff1f2; border-radius: 10px;
            padding: 8px 10px; margin: 8px 0 10px 0; page-break-inside: avoid;
        }
        .due-main { color: #9f1239; font-weight: 700; font-size: 10px; }
        .due-sub { color: #be123c; font-size: 9px; }
        .due-date { color: #be123c; font-weight: 700; font-size: 12px; }
        .info-table tr td { border-bottom: 1px solid #e2e8f0; padding: 7px 0; font-size: 11px; color: #334e72; }
        .info-table tr:last-child td { border-bottom: none; }
        .pay-history th {
            text-align: left; font-size: 11px; letter-spacing: .8px; color: #475569;
            border-bottom: 1px solid #d7dfec; padding: 0 0 10px 0;
        }
        .pay-history td { border-bottom: 1px solid #e2e8f0; padding: 7px 0; font-size: 11px; }
        .pay-history tr:last-child td { border-bottom: none; }
        .pill {
            border: 1px solid #fde68a; background: #fffbeb; color: #92400e;
            border-radius: 999px; padding: 2px 8px; font-size: 10px;
        }
        .green { color: #16a34a; font-weight: 700; }
        .red { color: #e11d48; font-weight: 700; }
        .sum-table td { border-bottom: 1px solid #e2e8f0; padding: 7px 0; font-size: 11px; }
        .sum-table tr:last-child td { border-bottom: none; }
        .sum-table .label { color: #334e72; }
        .sum-table .val { text-align: right; color: #0f2747; }
        .sum-table .val.green { color: #16a34a !important; font-weight: 700; }
        .sum-table .val.red { color: #be123c !important; font-weight: 700; }
        .sum-table .bold td { font-weight: 700; font-size: 12px; color: #0f2747; }
        .terms { margin-top: 8px; }
        .terms li { margin-left: 13px; color: #475569; line-height: 1.4; font-size: 10px; }
        .footer {
            margin-top: 6px; padding-top: 6px; border-top: 1px solid #e2e8f0;
            color: #94a3b8; font-size: 9px;
        }
        .no-break { page-break-inside: avoid; }
    </style>
</head>
<body>
@php
    $invDate = \Carbon\Carbon::parse($invoice_date)->format('d/m/Y');
    $genDate = now()->format('d/m/Y, h:i A');
    $dueDate = \Carbon\Carbon::parse($plan_end_date)->format('d M Y');
    $logoPath = !empty($branch_logo) ? public_path($branch_logo) : '';
    $hasLogo = !empty($logoPath) && file_exists($logoPath);
    $logoBase64 = $hasLogo ? base64_encode(file_get_contents($logoPath)) : '';
@endphp

<div class="wrap">
    <div class="header-wrap">
    <table class="header-table">
        <tr>
            <td style="width:56px;">
                @if($hasLogo)
                    <div class="logo-circle">
                        <img src="data:image/png;base64,{{ $logoBase64 }}" style="width:50px;height:50px;display:block;">
                    </div>
                @else
                    <div class="logo-circle">{{ strtoupper(substr($library_name ?? 'L', 0, 1)) }}</div>
                @endif
            </td>
            <td style="padding-left:12px;">
                <div class="lib-name">{{ $library_name }}</div>
                <div class="muted">ID: {{ $library_no }}</div>
                <div class="muted">{{ $library_address }}</div>
            </td>
            <td class="right" style="width:38%;">
                <div class="invoice-no">INV #{{ $invoice_ref_no }}</div>
                <div class="muted">Invoice Date: {{ $invDate }}</div>
                <div class="muted">Generated: {{ $genDate }}</div>
            </td>
        </tr>
    </table>
    </div>


    <div class="section no-break">
        <div class="title">LEARNER & SUBSCRIPTION</div>
        <table class="info-table">
            <tr><td class="muted">Learner ID</td><td class="right">{{ $learner_no }}</td></tr>
            <tr><td class="muted">Learner Name</td><td class="right">{{ $learner_name }}</td></tr>
            <tr><td class="muted">Seat No.</td><td class="right">{{ $seat_no }}</td></tr>
            <tr><td class="muted">Plan</td><td class="right">{{ $plan_name }} · {{ $plan_type }}</td></tr>
            <tr><td class="muted">Plan Duration</td><td class="right">{{ $plan_start_date }} → {{ $plan_end_date }}</td></tr>
            <tr><td class="muted">Locker Assigned</td><td class="right">{{ ($locker_charge ?? 0) > 0 ? 'Yes · #'. $locker_no : 'No' }}</td></tr>
        </table>
    </div>

    @if(!empty($activities) && count($activities) > 1)
    <div class="section no-break">
        <div class="title">PAYMENT HISTORY</div>
        <table class="pay-history">
            <thead>
                <tr>
                    <th>TRXN</th>
                    <th>DATE & TIME</th>
                    <th>AMOUNT</th>
                    <th>MODE</th>
                    <th >DUE AFTER</th>
                </tr>
            </thead>
            <tbody>
            @php
                $currentTxnId = (int) ($current_transaction_id ?? 0);
                $carryBase = max(0, (float) ($carry_forward ?? 0));
                $planBase = max(0, (float) ($final_amount ?? 0));
                $carryPaid = 0;
                $planPaid = 0;
            @endphp
            @foreach($activities as $i => $a)
                @php
                    $rowAmount = (float) ($a['amount'] ?? 0);
                    $rowTxnId = (int) ($a['learner_transaction_id'] ?? 0);
                    if ($rowTxnId !== 0 && $rowTxnId !== $currentTxnId) {
                        $carryPaid += $rowAmount;
                        $dueAfter = max(0, $carryBase - $carryPaid);
                    } else {
                        $planPaid += $rowAmount;
                        $dueAfter = max(0, $planBase - $planPaid);
                    }
                @endphp
                <tr>
                    <td><em>{{ $a['payment_type'] ?: 'Cash' }}</em></td>
                    <td>{{ $a['date'] }}</td>
                    <td class="green">₹{{ $a['amount'] }}</td>
                    <td><span class="pill">{{ $a['payment_mode'] ?: 'Cash' }}</span></td>
                    <td class=" red">&#8377;{{ rtrim(rtrim(number_format($dueAfter, 2, '.', ''), '0'), '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="section no-break">
        <div class="title">PAYMENT SUMMARY</div>
   
        <table class="sum-table">
            <tr><td class="label">Plan Price ({{ $plan_name }} · {{ $plan_type }})</td><td class="val">₹{{ $plan_price }}</td></tr>
            <tr><td class="label">Locker Charges</td><td class="val">₹{{ $locker_charge }}</td></tr>
            <tr><td class="label">Discount (in Amt.)</td><td class="val">₹{{ $discount_amount }}</td></tr>
            <tr><td class="label">Carryforward / Extra (as of {{ now()->format('d/m/Y') }})</td><td class="val">₹{{ $carry_forward }}</td></tr>
            <tr class="bold"><td>Final Subscription Price</td><td class="val">₹{{ $final_amount }}</td></tr>
            <tr><td class="label">Paid Earlier</td><td class="val green">&#8377;{{ rtrim(rtrim(number_format((float)($paid_earlier ?? 0), 2, '.', ''), '0'), '.') }}</td></tr>
            <tr><td class="label">Paid Amount ({{ \Carbon\Carbon::parse($invoice_date)->format('d M Y') }} � Cash)</td><td class="val green">&#8377;{{ rtrim(rtrim(number_format((float)($current_paid ?? 0), 2, '.', ''), '0'), '.') }}</td></tr>
            <tr class="bold"><td>Pending Amt</td><td class="val red">₹{{ $pending_amount }}</td></tr>
        </table>
    </div>

    <div class="terms">
        <ul>
            <li>This is a computer-generated receipt; no signature is required.</li>
            <li>No refunds will be issued once the subscription is activated. Please review your plan carefully before making a purchase.</li>
        </ul>
    </div>

    <div class="footer">{{ $library_name }} · {{ $learner_no }} · Computer Generated · {{ now()->format('d/m/Y') }}</div>
</div>
</body>
</html>



