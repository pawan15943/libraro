@extends('sitelayouts.layout')
@section('content')
<script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.11/dist/dotlottie-wc.js" type="module"></script>
<style>
    header,
    footer {
        display: none;
    }

    .online-qr-booking {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background: #efefff;

    }

    .online-booking {
        border: none !important;
    }

    .logo {
        width: 180px;
        padding: .5rem 0;
        margin: 0 auto;
        display: block;
        margin-bottom: 1rem;
    }

    .online-booking-address {
        font-family: 'Outfit','sans-sarif' !important;
    }

</style>


<section class="py-3 online-qr-booking">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4 text-center">
                <a href="{{'/'}}"><img src="{{ asset('public/img/libraro.webp') }}" alt="logo" class="logo"></a>
                <div class="online-booking">
                    <span class="steps">Step-3</span>
                    <h4 class="mb-4 text-success">Booking Confirmed!</h4>
                    <dotlottie-wc src="https://lottie.host/79d3a6d1-4651-47a2-8204-6780dff68b52/BS5YmTvc3K.lottie" style="width: 300px; margin: 0 auto;" autoplay loop></dotlottie-wc>

                    <p class="mb-3">Please visit the branch to confirm your booking.</p>

                    <div class="border p-3 bg-white rounded-4 online-booking-address">
                        <h4 class="mb-3">{{ $booking->branch->name }}</h4>
                        <p class="m- online-booking-address">Address : {{ $booking->branch->library_address }}</p>
                        <p class="m- online-booking-address">Call : 91-{{ $booking->branch->mobile }}</p>
                    </div>

                    <a href="{{'/'}}" class="mt-3 d-block">Go Back to Website</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
