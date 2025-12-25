@extends('sitelayouts.layout')
@section('content')

<section class="py-3">
    <div class="container">
        <form action="{{ route('booking.upload.screenshot', $booking->id) }}" method="POST" enctype="multipart/form-data">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="online-booking">
                        <span class="steps">Step-2</span>
                        <h4 class="mb-4 text-center">Scan QR Code to complete payment</h4>
                        <div class="QR-code p-3 text-center">
                            <a href="{{ $upiLink }}">
                                <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(300)->generate($upiLink)) }}">
                            </a>
                            <div class="mt-2">
                                <a href="{{ $upiLink }}" target="_blank" class="action_pay">
                                    Scan or Click to Pay
                                </a>
                            </div>
                        </div>
                        <p class="text-center">If you want to pay at the library, please visit us to complete your registration.</p>

                        @csrf
                        <label>Upload Payment screenshot <span class="text-danger">*</span></label>
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