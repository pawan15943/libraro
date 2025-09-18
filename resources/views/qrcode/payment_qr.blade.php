@extends('sitelayouts.layout')
@section('content')

<style>
    .process-step-1,
    .process-step-2 {
        display: flex;
        align-items: center;
        flex-direction: column;
        gap: 1rem;
        justify-content: space-between;
    }

    .sacnd-data {
        background: linear-gradient(2deg, #d6faff, transparent);
    }

    .action-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .action-content span.text-message {
        color: #a1a1a1;
        font-size: .9rem;
    }

    .sacnd-data span.footer {
        font-size: .8rem;
    }

    input.btn.btn-primary {
        background: #18225f;
        border-color: #18225f;
    }

    ul.action-list {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        list-style: none;
        padding: 0;
        justify-content: space-between;
    }

    ul.action-list li {
        width: calc(100% / 2 - .75rem);

    }

    ul.action-list li a {
        text-decoration: none;
        display: block;
        text-align: center;
        padding: 2rem 2rem;
        background: #fff;
        box-shadow: 1px 0 5px #00000021;
        border-radius: 1rem;
        font-weight: 700;
    }

</style>



<div class="sacnd-data py-5" style="min-height: 500px; display:flex; align-items:center;">
    <div>
        <a href="{{ $upiLink }}">
            <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(200)->generate($upiLink)) }}">
        </a>
    </div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4">
                <h3 class="text-center">Make Payment</h3>
                <p class="text-center">Scan the QR code below to make the payment</p>
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
