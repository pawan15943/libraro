@extends('sitelayouts.layout')
@section('content')

<div class="min-vh-100 d-flex align-items-center bg-light">

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-xl-4 col-lg-5 col-md-6 col-11">

                {{-- PAYMENT CARD --}}
                <div class="card shadow-lg border-0">

                    {{-- HEADER --}}
                    <div class="card-header text-center bg-white border-0 pt-4">
                        <span class="badge bg-success px-3 py-2 mb-2">
                            💳 SECURE PAYMENT
                        </span>
                        <h4 class="fw-bold mt-2 mb-1">Make Payment</h4>
                        <p class="text-muted small mb-0">
                            Scan or tap the QR code to complete payment
                        </p>
                    </div>

                    {{-- BODY --}}
                    <div class="card-body px-4">

                        {{-- QR CODE --}}
                        <div class="text-center mb-4">
                            <div class="border rounded-4 p-3 d-inline-block bg-white shadow-sm">
                                <a href="{{ $upiLink }}" target="_blank">
                                    <img
                                        src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(220)->generate($upiLink)) }}"
                                        class="img-fluid"
                                        alt="UPI QR Code">
                                </a>
                            </div>

                            <div class="mt-3">
                                <a href="{{ $upiLink }}"
                                   target="_blank"
                                   class="btn btn-success btn-lg w-100">
                                    📲 Scan / Click to Pay
                                </a>
                            </div>
                        </div>

                        {{-- INFO --}}
                        <div class="alert alert-warning text-center small mb-4">
                            If you prefer to pay in person, please visit the library to complete your registration.
                        </div>

                        {{-- UPLOAD SECTION --}}
                        <form action="{{ route('booking.upload.screenshot', $booking->id) }}"
                              method="POST"
                              enctype="multipart/form-data">

                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Upload Payment Screenshot <span class="text-danger">*</span>
                                </label>
                                <input type="file"
                                       name="payment_screenshot"
                                       class="form-control @error('payment_screenshot') is-invalid @enderror">

                                @error('payment_screenshot')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <button type="submit"
                                    class="btn btn-primary btn-lg w-100 shadow">
                                ✅ Submit Payment Proof
                            </button>

                        </form>
                    </div>

                    {{-- FOOTER --}}
                    <div class="card-footer bg-white border-0 text-center small text-muted">
                        🔒 Your payment is safe and encrypted
                    </div>

                </div>

            </div>
        </div>
    </div>

</div>

@endsection
