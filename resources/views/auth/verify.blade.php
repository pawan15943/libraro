<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('public/css/style.css') }}" rel="stylesheet">
</head>

<body>
    <div class="adminstrator-login">
        <div class="left">
            <div class="top">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('public/img/libraro-white.svg') }}" alt="Libraro Logo" class="logo"></a>
            </div>
            <div class="content">
                <h2>Empower Learning,<br>
                    Shape Futures: <br>
                    Your Library, Your Legacy.</h2>
            </div>
        </div>
        <div class="right">

            <div class="middle">
                <h2>Verify Email id via OTP</h2>
                <p>Check OTP in you Email Id and Verify your OTP Here and go next </p>
                <form method="POST" action="{{ route('verify.otp') }}" class="validateForm">
                    @csrf
                    <div class="row g-4">
                        <div class="col-lg-12">
                            <label for="">Enter OTP <span>*</span></label>
                            <input type="hidden" name="email" value="{{ session('library_email') ?? session('email') }}">
                            <input type="hidden" name="user_type" value="admin">
                            <input type="text" class="form-control digit-only @error('email_otp') is-invalid @enderror" name="email_otp" maxlength="10"
                                value="{{ old('email_otp') }}" placeholder="Enter OTP">
                            @error('email_otp')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror

                        </div>
                        <div class="col-lg-12">
                            <button type="submit" class="btn btn-primary button">Verify OTP</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js"></script>

    <script src="{{ url('public/js/main-scripts.js') }}"></script>
    <script src="{{ url('public/js/main-validation.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('input').attr('autocomplete', 'off');
        });
    </script>
</body>

</html>