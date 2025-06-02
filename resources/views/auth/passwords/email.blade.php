<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">

    <link href="{{ asset('public/css/style.css') }}" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .left {
            position: relative;
            z-index: 1;
        }

        .left::after {
            position: absolute;
            left: 0;
            top: 0;
            background: linear-gradient(0deg, black, transparent);
            content: '';
            z-index: -1;
            width: 100%;
            height: 100%;
        }
    </style>
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
                <h5>Trouble Logging In</h5>
                <h2>Forgot Password?</h2>
                <small>Let’s help you reset it in no time! 🔄</small>
                @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="validateForm">
                    @csrf
                    <div class="row g-3 mt-1">
                        <div class="col-lg-12">
                            <label for="">Email Address</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" autocomplete="email" autofocus>
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-12 text-center">
                            <button type="submit" class="btn btn-primary button mb-2">Send Password Reset Link </button>
                            <small>Check your inbox, and we’ll guide you through! or <a href="{{ route('login.library') }}" class="forgot-password">Login Now ?</a></small>
                        </div>
                        <div class="col-lg-12 text-center">

                        </div>
                    </div>

                </form>

            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.21.0/jquery.validate.min.js"></script>

    <script src="{{ url('public/js/main-scripts.js') }}"></script>
    <script src="{{ url('public/js/main-validation.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('input').attr('autocomplete', 'off');
        });
    </script>
    <script>
        $(document).ready(function() {
            var images = [
                '{{url("public/img/login-slider/forgot-password.webp")}}',
            ];

            var currentIndex = 0;

            function changeBackground() {
                $('.left').css('background-image', 'url(' + images[currentIndex] + ')');
                $('.left').css('background-size', 'cover');
                currentIndex = (currentIndex + 1) % images.length;
            }

            changeBackground();
            setInterval(changeBackground, 5000);
        });
    </script>
</body>

</html>