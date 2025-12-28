@extends('sitelayouts.layout')
@section('content')
@if(session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif
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

</style>
<section class="py-3 online-qr-booking">
    <div class="container">
        <form method="POST" action="{{ route('renew.find', $branch->uuid) }}">
            @csrf
            <div class="row justify-content-center align-items-center h-100">
                <div class="col-lg-6">
                    <a href="{{'/'}}"><img src="{{ asset('public/img/libraro.webp') }}" alt="logo" class="logo"></a>
                    <div class="online-booking">
                        <span class="steps">Step-1</span>
                        <h4 class="mb-4 text-center">Re-New your plan</h4>
                        <div class="row g-4 ">
                            <input type="hidden" value="{{$branch->id}}" name="branch">

                            <div class="col-lg-12">
                                <input type="text" class="form-control @error('learner_no') is-invalid @enderror" placeholder="Enter your Member UID" name="learner_no">
                                @error('learner_no')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-12">
                                <input type="text" class="form-control @error('mobile') is-invalid @enderror" placeholder="Enter your mobile Number" name="mobile">
                                @error('mobile')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-12 text-center">
                                <button type="submit" class="btn btn-primary button">Next <i class="fa fa-long-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

@endsection
