<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
var options = {
    "key": "{{ $key }}",
    "amount": "{{ $amount * 100 }}", 
    "currency": "INR",
    "name": "Library Notification Subscription",
    "description": "Purchase Notification Credits",
    "order_id": "{{ $orderId }}",
    "handler": function (response){
        // On success → call verify route
        window.location.href = "{{ route('notification.payment.verify') }}?razorpay_order_id=" 
                               + response.razorpay_order_id 
                               + "&razorpay_payment_id=" + response.razorpay_payment_id
                               + "&razorpay_signature=" + response.razorpay_signature
                               + "&db_order_id={{ $dbOrderId }}";
    },
    "prefill": {
        "name": "{{ auth()->user()->name }}",
        "email": "{{ auth()->user()->email }}",
        "contact": "{{ auth()->user()->mobile ?? '' }}"
    },
    "theme": {
        "color": "#3399cc"
    }
};
var rzp = new Razorpay(options);
rzp.open();
</script>
