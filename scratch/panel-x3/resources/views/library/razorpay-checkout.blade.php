<!DOCTYPE html>
<html lang="en">
<head>
    @include('lb.partials.head-common')
    <title>Secure payment | Libraro</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="{{ asset('public/css/lb-onboard.css') }}?v={{ @filemtime(public_path('css/lb-onboard.css')) }}">
    <style>
        .pay { min-height: 100vh; min-height: 100dvh; display: grid; place-items: center; padding: 24px; }
        .pay-card { width: min(440px, 100%); padding: 34px 28px; border-radius: 28px; background: var(--ob-card); box-shadow: var(--ob-shadow); text-align: center; animation: lb-rise .6s var(--lb-ease) both; }
        .pay-ring { width: 72px; height: 72px; margin: 0 auto 18px; border-radius: 50%; display: grid; place-items: center; background: var(--ob-tint); color: var(--ob-teal); position: relative; }
        .pay-ring::after { content: ''; position: absolute; inset: -6px; border-radius: 50%; border: 3px solid transparent; border-top-color: var(--ob-teal); animation: lb-spin 1s linear infinite; }
        .pay-ring .lb-i { width: 30px; height: 30px; }
        .pay.is-stopped .pay-ring::after { display: none; }
        .pay-card h1 { margin: 0; font-size: 24px; letter-spacing: -0.02em; }
        .pay-card p { margin: 8px 0 0; color: var(--lb-muted); line-height: 1.5; }
        .pay-amt { margin: 18px 0 0; padding: 14px; border-radius: 18px; background: var(--ob-soft); display: flex; justify-content: space-between; align-items: center; font-weight: 600; }
        .pay-amt b { font-size: 22px; letter-spacing: -0.02em; }
        .pay-act { display: grid; gap: 10px; margin-top: 20px; }
        .pay-act[hidden] { display: none; }
        .pay-safe { display: flex; justify-content: center; align-items: center; gap: 6px; margin-top: 18px; font-size: 13px; color: var(--lb-muted); }
        .pay-safe .lb-i { width: 15px; height: 15px; color: var(--lb-success); }
    </style>
</head>
<body class="lb ob">
<main class="pay" id="pay">
    <div class="pay-card">
        @include('lb.partials.logo', ['height' => 30])
        <div class="pay-ring" style="margin-top: 24px"><x-lb-icon name="lock" /></div>
        <h1 id="pay-title">Opening secure payment…</h1>
        <p id="pay-sub">Complete the payment in the Razorpay window. Please don't close this page.</p>
        <div class="pay-amt"><span>{{ $name }}</span><b>₹{{ number_format(((int) $amount) / 100, ((int) $amount) % 100 ? 2 : 0) }}</b></div>
        <div class="pay-act" id="pay-act" hidden>
            <button type="button" class="lb-btn lb-btn--lg lb-btn--block" id="pay-retry">Try payment again</button>
            <a href="{{ route('subscriptions.choosePlan') }}" class="lb-btn lb-btn--ghost lb-btn--block">Choose another plan</a>
        </div>
        <div class="pay-safe"><x-lb-icon name="shield-check" />100% secure payment by Razorpay</div>
    </div>
</main>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        const options = {
            "key": "{{ $key }}", // Your Razorpay Key
            "amount": "{{ $amount }}", // Amount in paise
            "currency": "{{ $currency }}",
            "order_id": "{{ $order_id }}", // Razorpay Order ID
            "name": "{{ $name }}",
            "description": "{{ $description }}",
            "handler": function (response) {
                // Send payment details to server for verification
                fetch("{{ route('library.payment.success') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature,
                        library_transaction_id: "{{ $library_transaction_id }}" // Add library transaction ID if needed
                    })
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('pay-title').textContent = data.success ? 'Payment successful!' : 'Checking payment…';
                    if (data.success) {
                        window.location.href = data.redirect_url; // Success page
                    } else if (data.error_url) {
                        window.location.href = data.error_url; // Redirect to error page
                    } else {
                        console.error('Error:', data.message || 'An unknown error occurred.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.location.href = "{{ route('library.payment.error') }}"; // Fallback to error page
                });
            },
            "prefill": {
                "name": "",
                "email": "",
                "contact": ""
            },
            "theme": {
                "color": "#1d3c86"
            },
            "modal": {
                "ondismiss": function () {
                    document.getElementById('pay').classList.add('is-stopped');
                    document.getElementById('pay-title').textContent = 'Payment not completed';
                    document.getElementById('pay-sub').textContent = 'The payment window was closed. No money was taken. You can try again.';
                    document.getElementById('pay-act').hidden = false;
                }
            }
        };
        const rzp = new Razorpay(options);
        document.getElementById('pay-retry').addEventListener('click', function () {
            document.getElementById('pay').classList.remove('is-stopped');
            document.getElementById('pay-title').textContent = 'Opening secure payment…';
            document.getElementById('pay-sub').textContent = 'Complete the payment in the Razorpay window. Please don\'t close this page.';
            document.getElementById('pay-act').hidden = true;
            rzp.open();
        });
        rzp.open();
    </script>
</body>
</html>
