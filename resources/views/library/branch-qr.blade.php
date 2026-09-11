<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ strtoupper($branch->display_name ?? $branch->name) }} - Branch Information & Seat Booking Poster</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 6mm 8mm 6mm 8mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            color: #1e293b;
            background-color: #ffffff;
            font-size: 9.5px;
            line-height: 1.3;
        }

        /* Outer Poster Frame */
        .poster-container {
            border: 2px solid #18225f;
            border-radius: 10px;
            padding: 9px 11px;
            background: #ffffff;
        }

        /* Top Header Row */
        .top-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .top-header-table td {
            vertical-align: middle;
        }

        .branch-icon-box {
            display: inline-block;
            width: 28px;
            height: 28px;
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-align: center;
            vertical-align: middle;
            margin-right: 6px;
            line-height: 28px;
        }

        .branch-name-title {
            font-size: 16px;
            font-weight: bold;
            color: #18225f;
            display: inline-block;
            vertical-align: middle;
            margin-right: 6px;
        }

        .status-badge-active {
            display: inline-block;
            background-color: #ecfdf5;
            color: #16a34a;
            border: 1px solid #bbf7d0;
            border-radius: 20px;
            font-size: 8px;
            font-weight: bold;
            padding: 1px 7px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            vertical-align: middle;
        }

        .libraro-brand-badge {
            background-color: #18225f;
            color: #ffffff;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-align: right;
            display: inline-block;
        }

        /* Library Info Banner Box */
        .info-banner-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 7px;
        }

        .banner-left-cell {
            width: 72%;
            padding: 8px 10px;
            vertical-align: middle;
        }

        .banner-right-cell {
            width: 28%;
            padding: 6px 8px;
            text-align: center;
            vertical-align: middle;
            border-left: 1.5px dashed #cbd5e1;
            background-color: #ffffff;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .library-heading {
            font-size: 15px;
            font-weight: 800;
            color: #18225f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .library-address {
            font-size: 9px;
            color: #475569;
            margin-bottom: 6px;
            line-height: 1.25;
        }

        .library-address strong {
            color: #18225f;
        }

        .contact-pills-table {
            border-collapse: collapse;
        }

        .contact-pills-table td {
            padding-right: 6px;
            vertical-align: middle;
        }

        .contact-pill {
            display: inline-block;
            border-radius: 5px;
            padding: 2px 7px;
            font-size: 8px;
            font-weight: 600;
        }

        .contact-pill.phone {
            color: #16a34a;
            border: 1px solid #bbf7d0;
            background-color: #f0fdf4;
        }

        .contact-pill.email {
            color: #2563eb;
            border: 1px solid #bfdbfe;
            background-color: #eff6ff;
        }

        .qr-image {
            width: 90px;
            height: 90px;
            display: block;
            margin: 0 auto 2px auto;
            border-radius: 4px;
        }

        .qr-caption {
            font-size: 8px;
            font-weight: bold;
            color: #18225f;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /* 4-Box Stats Overview */
        .stats-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px 0;
            margin-bottom: 7px;
        }

        .stat-card {
            width: 25%;
            padding: 5px 7px;
            border-radius: 7px;
            vertical-align: middle;
        }

        .stat-card-seats {
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
        }

        .stat-card-extend {
            background-color: #ecfdf5;
            border: 1px solid #bbf7d0;
        }

        .stat-card-locker {
            background-color: #fff1f2;
            border: 1px solid #fecdd3;
        }

        .stat-card-token {
            background-color: #faf5ff;
            border: 1px solid #e9d5ff;
        }

        .stat-inner-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stat-inner-table td {
            vertical-align: middle;
        }

        .stat-icon-col {
            width: 24px;
            font-size: 13px;
            text-align: center;
        }

        .stat-label {
            font-size: 7.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }

        .stat-value {
            font-size: 12px;
            font-weight: bold;
            color: #18225f;
            line-height: 1.1;
        }

        .stat-unit {
            font-size: 8px;
            font-weight: normal;
            color: #64748b;
        }

        /* Section Containers */
        .section-box {
            border-radius: 7px;
            margin-bottom: 6px;
            padding: 6px 8px;
        }

        .plans-section-box {
            background-color: #ffffff;
            border: 1.5px solid #dbeafe;
        }

        .shifts-section-box {
            background-color: #ffffff;
            border: 1.5px solid #fed7aa;
        }

        .section-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .plans-section-box .section-title {
            color: #1d4ed8;
        }

        .shifts-section-box .section-title {
            color: #b45309;
        }

        .section-count {
            font-size: 8.5px;
            font-weight: normal;
            color: #64748b;
        }

        /* Two-Column Grid Tables */
        .grid-2col-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px;
        }

        .grid-cell {
            width: 50%;
            padding: 4px 7px;
            border-radius: 5px;
            vertical-align: middle;
        }

        /* Plan Item */
        .plan-item {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .plan-name {
            font-size: 9px;
            font-weight: bold;
            color: #18225f;
        }

        .plan-desc {
            font-size: 7.5px;
            color: #64748b;
        }

        .plan-badge {
            display: inline-block;
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 7.5px;
            font-weight: bold;
        }

        /* Shift Item */
        .shift-item {
            background-color: #f8fafc;
            border: 1px solid #f1f5f9;
        }

        .shift-name {
            font-size: 9px;
            font-weight: bold;
            color: #18225f;
        }

        .shift-time {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 1px;
        }

        .shift-price-badge {
            display: inline-block;
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 2px 7px;
            font-size: 8.5px;
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
        }

        /* Student Guide Poster Section */
        .guide-box {
            background: #fafafa;
            border: 1.5px solid #18225f;
            border-radius: 7px;
            padding: 6px 8px;
            margin-bottom: 5px;
        }

        .guide-title-bar {
            background-color: #18225f;
            color: #ffffff;
            padding: 3px 6px;
            border-radius: 4px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .guide-steps-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px 0;
            margin-bottom: 4px;
        }

        .step-cell {
            width: 25%;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 4px 5px;
            vertical-align: top;
            text-align: left;
        }

        .step-num-badge {
            display: inline-block;
            background-color: #18225f;
            color: #ffffff;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            text-align: center;
            line-height: 15px;
            font-size: 7.5px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .step-title {
            font-size: 8px;
            font-weight: bold;
            color: #18225f;
            margin-bottom: 1px;
        }

        .step-desc {
            font-size: 7px;
            color: #64748b;
            line-height: 1.2;
        }

        /* Notice / Rules strip */
        .notice-strip {
            background-color: #fefce8;
            border: 1px solid #fef08a;
            border-radius: 4px;
            padding: 2px 5px;
            font-size: 7px;
            color: #854d0e;
            text-align: center;
        }

        /* Footer */
        .poster-footer-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px dashed #cbd5e1;
            padding-top: 4px;
            margin-top: 3px;
        }

        .poster-footer-table td {
            font-size: 7px;
            color: #64748b;
            vertical-align: middle;
        }
    </style>
</head>
<body>

<div class="poster-container">

    <!-- Top Header Row -->
    <table class="top-header-table">
        <tr>
            <td style="width: 70%;">
                <div class="branch-icon-box">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="#18225f" style="vertical-align: middle;">
                        <path d="M19 2H9c-1.1 0-2 .9-2 2v2H5c-1.1 0-2 .9-2 2v14h18V4c0-1.1-.9-2-2-2zm-8 2h8v16h-2v-2h-4v2H9V4zM5 8h2v12H5V8zm6 2h2v2h-2v-2zm0 4h2v2h-2v-2zm4-4h2v2h-2v-2zm0 4h2v2h-2v-2z"/>
                    </svg>
                </div>
                <div class="branch-name-title">{{ $branch->name }}</div>
                <div class="status-badge-active">&#9679; ACTIVE</div>
            </td>
            <td style="width: 30%; text-align: right;">
                <div class="libraro-brand-badge">LIBRARO WEGUARD &bull; VERIFIED</div>
            </td>
        </tr>
    </table>

    <!-- Branch Info Banner Box -->
    <table class="info-banner-table">
        <tr>
            <td class="banner-left-cell">
                <div class="library-heading">{{ strtoupper($branch->display_name ?? $branch->name) }}</div>
                <div class="library-address">
                    <strong>Address :</strong> {{ $branch->library_address ?? 'Branch Address Not Updated' }}
                </div>
                <table class="contact-pills-table">
                    <tr>
                        <td>
                            <div class="contact-pill phone">
                                &#9742; +91-{{ $branch->mobile ?? '—' }}
                            </div>
                        </td>
                        @if(!empty($branch->email))
                        <td>
                            <div class="contact-pill email">
                                &#9993; {{ $branch->email }}
                            </div>
                        </td>
                        @endif
                    </tr>
                </table>
            </td>
            <td class="banner-right-cell">
                <img src="data:image/png;base64,{{ $qrCode }}" alt="Branch QR Code" class="qr-image"/>
                <div class="qr-caption">Scan to Book Seat</div>
            </td>
        </tr>
    </table>

    <!-- 4-Box Stats Grid (Seats, Extend Days, Locker, Token) -->
    <table class="stats-table">
        <tr>
            <!-- Total Seats -->
            <td class="stat-card stat-card-seats">
                <table class="stat-inner-table">
                    <tr>
                        <td class="stat-icon-col">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#6366f1">
                                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                            </svg>
                        </td>
                        <td>
                            <div class="stat-label">Total Seats</div>
                            <div class="stat-value">{{ $branch->hour->seats ?? $branch->seats ?? 0 }}</div>
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Extend Days -->
            <td class="stat-card stat-card-extend">
                <table class="stat-inner-table">
                    <tr>
                        <td class="stat-icon-col">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#16a34a">
                                <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/>
                            </svg>
                        </td>
                        <td>
                            <div class="stat-label">Extend Days</div>
                            <div class="stat-value">{{ $branch->extend_days ?? 0 }} <span class="stat-unit">Days</span></div>
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Locker Price -->
            <td class="stat-card stat-card-locker">
                <table class="stat-inner-table">
                    <tr>
                        <td class="stat-icon-col">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#e11d48">
                                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                            </svg>
                        </td>
                        <td>
                            <div class="stat-label">Locker Price</div>
                            <div class="stat-value">&#8377;{{ number_format($branch->locker_amount ?? 0) }} <span class="stat-unit">/ mo</span></div>
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Token Money -->
            <td class="stat-card stat-card-token">
                <table class="stat-inner-table">
                    <tr>
                        <td class="stat-icon-col">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#9333ea">
                                <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                            </svg>
                        </td>
                        <td>
                            <div class="stat-label">Token Money</div>
                            <div class="stat-value">&#8377;{{ number_format($branch->token_money ?? 0) }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Available Study Plans Section -->
    @if(isset($plans) && $plans->isNotEmpty())
    <div class="section-box plans-section-box">
        <table class="section-header-table">
            <tr>
                <td>
                    <span class="section-title">&#10022; Available Study Plans</span>
                    <span class="section-count">({{ $plans->count() }} {{ $plans->count() == 1 ? 'Plan' : 'Plans' }})</span>
                </td>
            </tr>
        </table>
        <table class="grid-2col-table">
            @foreach($plans->chunk(2) as $planPair)
            <tr>
                @foreach($planPair as $plan)
                <td class="grid-cell plan-item">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="vertical-align: middle;">
                                <div class="plan-name">{{ $plan->name }}</div>
                                <div class="plan-desc">
                                    {{ $plan->monthdays ? $plan->monthdays . ' Days' : ($plan->plan_id . ' ' . ucfirst(strtolower($plan->type))) }}
                                </div>
                            </td>
                            <td style="width: 30%; text-align: right; vertical-align: middle;">
                                <div class="plan-badge">{{ strtoupper($plan->type) }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                @endforeach
                @if($planPair->count() == 1)
                <td class="grid-cell" style="border: none; background: transparent;"></td>
                @endif
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <!-- Shift Details & Timings Section -->
    <div class="section-box shifts-section-box">
        <table class="section-header-table">
            <tr>
                <td>
                    <span class="section-title">&#9200; Shift Details & Timings</span>
                    <span class="section-count">({{ $branch->planTypes->count() }} {{ $branch->planTypes->count() == 1 ? 'Shift' : 'Shifts' }})</span>
                </td>
            </tr>
        </table>
        <table class="grid-2col-table">
            @forelse($branch->planTypes->chunk(2) as $shiftPair)
            <tr>
                @foreach($shiftPair as $shift)
                @php
                    $start = !empty($shift->start_time) ? \Carbon\Carbon::parse($shift->start_time)->format('h:i A') : '—';
                    $end = !empty($shift->end_time) ? \Carbon\Carbon::parse($shift->end_time)->format('h:i A') : '—';
                    $price = $shift->price->price ?? 0;
                @endphp
                <td class="grid-cell shift-item">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="vertical-align: middle;">
                                <div class="shift-name">{{ $shift->name }}</div>
                                <div class="shift-time">{{ $start }} &ndash; {{ $end }}</div>
                            </td>
                            <td style="width: 32%; text-align: right; vertical-align: middle;">
                                <div class="shift-price-badge">&#8377;{{ number_format($price) }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                @endforeach
                @if($shiftPair->count() == 1)
                <td class="grid-cell" style="border: none; background: transparent;"></td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="2" style="text-align: center; color: #94a3b8; font-size: 8px; padding: 5px;">
                    No shifts configured for this branch
                </td>
            </tr>
            @endforelse
        </table>
    </div>

    <!-- Student Self-Booking Guideline Box (Poster Section for Library Affixing) -->
    <div class="guide-box">
        <div class="guide-title-bar">
            &#9881; HOW TO BOOK YOUR SEAT & ACCESS STUDENT APP &bull; 4 SIMPLE STEPS
        </div>

        <table class="guide-steps-table">
            <tr>
                <td class="step-cell">
                    <div class="step-num-badge">1</div>
                    <div class="step-title">Scan QR Code</div>
                    <div class="step-desc">
                        Open your Phone Camera, Google Lens, or Paytm/Scanner & scan the QR above.
                    </div>
                </td>
                <td class="step-cell">
                    <div class="step-num-badge">2</div>
                    <div class="step-title">Launch Student App</div>
                    <div class="step-desc">
                        Tap the link to launch the instant Libraro Portal &mdash; no app download required.
                    </div>
                </td>
                <td class="step-cell">
                    <div class="step-num-badge">3</div>
                    <div class="step-title">Select Shift & Seat</div>
                    <div class="step-desc">
                        Choose your preferred study plan, shift timing, and pick your available seat.
                    </div>
                </td>
                <td class="step-cell">
                    <div class="step-num-badge">4</div>
                    <div class="step-title">Confirm & Get Pass</div>
                    <div class="step-desc">
                        Pay online via UPI or at counter to receive your confirmed Digital Entry Pass.
                    </div>
                </td>
            </tr>
        </table>

        <div class="notice-strip">
            &#9888; <strong>Notice for Students:</strong> Please maintain discipline, silence, and hygiene inside the reading hall. Digital passes are strictly non-transferable.
        </div>
    </div>

    <!-- Poster Bottom Info / Footer -->
    <table class="poster-footer-table">
        <tr>
            <td style="width: 60%;">
                Helpdesk & Support: <strong>+91-{{ $branch->mobile ?? '—' }}</strong> &bull; <strong>{{ $branch->email ?? 'libraro.in' }}</strong>
            </td>
            <td style="width: 40%; text-align: right;">
                Powered by <strong>Libraro WebGuard</strong> &bull; Smart Library Cloud
            </td>
        </tr>
    </table>

</div>

</body>
</html>
