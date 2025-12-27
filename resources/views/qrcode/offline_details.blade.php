@extends('sitelayouts.layout')
@section('content')
<script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.11/dist/dotlottie-wc.js" type="module"></script>

<section class="py-3">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4 text-center">
                <div class="online-booking">
                    <span class="steps">Step-3</span>
                    <h4 class="mb-4 text-success">Booking Confirmed!</h4>
                    <dotlottie-wc src="https://lottie.host/79d3a6d1-4651-47a2-8204-6780dff68b52/BS5YmTvc3K.lottie" style="width: 300px; margin: 0 auto;"  autoplay loop></dotlottie-wc>

                    <p class="mb-3">Please visit the branch to confirm your booking.</p>

                    <div class="border p-3 bg-white rounded-4">
                        <h4 class="mb-3">{{ $booking->branch->name }}</h4>
                        <p class="m-">Address : {{ $booking->branch->library_address }}</p>
                        <p class="m-"> Call At : 91-{{ $booking->branch->mobile }}</p>
                    </div>

                    <a href="">Go Back to Website</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection