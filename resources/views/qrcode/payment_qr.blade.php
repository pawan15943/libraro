@extends('sitelayouts.layout')
@section('content')
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

    .online-booking{
        border: none !important;
    }

    .logo {
        width: 180px;
        padding: .5rem 0;
        margin: 0 auto;
        display: block;
        margin-bottom: 1rem;
    }

    .text-online{
        font-family: 'Outfit','sans-sarif';
        margin-bottom: 1rem;
    }

</style>
<section class="py-3 online-qr-booking">
    <div class="container">
        <form action="{{ route('booking.upload.screenshot', $booking->id) }}" method="POST" enctype="multipart/form-data">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <a href="/"><img src="{{ asset('public/img/libraro.webp') }}" alt="logo" class="logo"></a>
                    <div class="online-booking">
                        <span class="steps">Verification</span>
                        <h4 class="mb-4 text-center">Scan QR Code to complete payment</h4>
                        <div class="QR-code p-3 text-center">
                            <a href="{{ $upiLink }}">
                                <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(200)->generate($upiLink)) }}">
                            </a>
                            <div class="mt-2">
                                <a href="{{ $upiLink }}" target="_blank" class="action_pay">
                                    Scan or Click to Pay
                                </a>
                            </div>
                        </div>
                        <p class="text-center text-online">If you want to pay at the library, please visit us to complete your registration.</p>

                        @csrf
                        <label class="text-center d-block">Upload Payment screenshot <span class="text-danger">*</span></label>
                        <input type="file" name="payment_screenshot" class="form-control @error('payment_screenshot') is-invalid @enderror">
                        @error('payment_screenshot')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                        <button type="submit" class="btn btn-primary mt-3">Submit</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection