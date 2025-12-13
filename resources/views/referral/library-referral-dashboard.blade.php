@extends('layouts.library')

@section('content')
<div class="container">
    <h4 class="mb-4">Refer Another Library</h4>

    {{-- Referral Summary --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card p-3">Total Referrals <br><b>{{ $total }}</b></div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">Completed <br><b>{{ $completed }}</b></div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">Pending <br><b>{{ $pending }}</b></div>
        </div>
    </div>

    {{-- Referral Code --}}
    <div class="card mb-3">
        <div class="card-body">
            <h6>Referral Code</h6>
            <input type="text" class="form-control" value="{{ auth()->user()->referral_code }}" readonly>
        </div>
    </div>

    {{-- Referral Link --}}
    <div class="card mb-3">
        <div class="card-body">
            <h6>Referral Link</h6>
            <input type="text" class="form-control" value="{{ url('/library/register?ref='.auth()->user()->referral_code) }}" readonly>
        </div>
    </div>

    {{-- QR Code --}}
    <div class="card mb-3">
        <div class="card-body text-center">
            <h6>Referral QR Code</h6>
            {!! QrCode::size(180)->generate(url('/library/register?ref='.auth()->user()->referral_code)) !!}
        </div>
    </div>
</div>
@endsection
