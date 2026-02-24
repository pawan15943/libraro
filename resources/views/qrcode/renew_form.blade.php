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

    .invalid-feedback{
        font-weight: 500;
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
                        @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                        @endif
                        <div class="row g-4 ">
                            <input type="hidden" value="{{$branch->id}}" name="branch">
                            <div class="col-lg-12">
                                <select class="form-select form-control @error('login_with') is-invalid @enderror" id="login_with" name="login_with">
                                    <option value="">Choose</option>
                                    <option value="dob" {{ old('login_with') == 'dob' ? 'selected' : '' }}>Proceed with DOB</option>
                                    <option value="email" {{ old('login_with') == 'email' ? 'selected' : '' }}>Proceed with Email ID</option>
                                    <option value="learner_no" {{ old('login_with') == 'learner_no' ? 'selected' : '' }}>Proceed with Member UID</option>
                                </select>
                                @error('login_with')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                         
                            <div class="col-lg-12">
                                <input type="text" class="form-control @error('learner_no') is-invalid @enderror" id="learner_no_uid" placeholder="Enter your Member UID" name="learner_no" value="{{old('learner_no')}}">
                                @error('learner_no')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-12">
                                <input type="text" class="form-control @error('mobile') is-invalid @enderror" placeholder="Enter your mobile Number" name="mobile" value="{{old('mobile')}}" maxlength="10">
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        let dobPicker = null;
    $('#login_with').on('change', function () {
       let value = $(this).val();
        let input = $('#learner_no_uid');

        if (dobPicker) {
            dobPicker.destroy();
            dobPicker = null;
        }

        if (value === 'dob') {
            input.attr('type', 'text').attr('placeholder', 'DD/MM/YYYY');
            dobPicker = flatpickr(input[0], {
                dateFormat: "d/m/Y",
                allowInput: true
            });
        } else if (value === 'email') {
            input.attr('placeholder', 'Enter Email ID').attr('type', 'email');
        } else {
            input.attr('placeholder', 'Enter Member UID').attr('type', 'text');
        }
    });
</script>
@endsection
