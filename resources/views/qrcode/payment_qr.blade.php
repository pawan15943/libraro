@extends('sitelayouts.layout')
@section('content')


<div class="sacnd-data py-5" style="min-height: 500px; display:flex; align-items:center;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4">
                <h3 class="text-center mb-4">Make Payment</h3>
                <p class="text-center">Scan the QR code below to make the payment</p>
                <div class="QR-code p-3 text-center">
                    <a href="{{ $upiLink }}">
                        <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(200)->generate($upiLink)) }}">
                    </a>
                </div>
                <form class="mt-4" action="{{ route('booking.upload.screenshot', $booking->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label>Upload Payment screenshot</label>
                    <input type="file" name="payment_screenshot" class="form-control @error('payment_screenshot') is-invalid @enderror">
                    @error('payment_screenshot')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <button type="submit" class="btn btn-primary mt-3">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection