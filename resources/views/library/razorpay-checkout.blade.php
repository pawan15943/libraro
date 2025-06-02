<!DOCTYPE html>
<html>
<head>
    <title>Razorpay Payment</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <h1>Redirecting to Razorpay payment gateway</h1>

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
                "color": "#3399cc"
            }
        };
        const rzp = new Razorpay(options);
        rzp.open();
    </script>
</body>
</html>
