<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
    <title>Library Login | Access Libraro’s Dashboard & Manage Your Library</title>

    <meta name="description" content="Login to your Libraro dashboard and manage seat bookings, payments, and all your library operations in one place. Smart library software starts here.">

    <meta name="keywords" content="Library Software Login, Libraro Dashboard, Library Management Login, Seat Booking Login">

    <meta name="author" content="TECHITO India Pvt. Ltd.">

    <meta property="og:title" content="Library Login | Manage Your Library with Libraro" />
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">

    <link href="{{ asset('public/css/style.css') }}" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebPage",
            "name": "Library Login",
            "url": "https://www.libraro.in/library/login",
            "description": "Login to Libraro to access your library dashboard and manage all library functions like bookings, expenses, and more."
        }
    </script>
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
                <h5>Welcome Back, </h5>
                <h2>Library Admin!</h2>
                <small>We’re glad to see you again! 🎉</small>
                @if(session('success'))
                <div class="alert alert-success mb-0 mt-1">
                    {{ session('success') }}
                </div>
                @elseif(session('info'))
                <div class="alert alert-info mb-0 mt-1">
                    {{ session('info') }}
                </div>
                @elseif($errors->has('error'))
                <div class="alert alert-danger mb-0 mt-1">
                    {{ $errors->first('error') }}
                </div>
                @endif
                <form method="POST" action="{{ route('login.store') }}" class="validateForm">
                    @csrf
                    <input type="hidden" name="user_type" value="admin">
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
                        <div class="col-lg-12">
                            <label for="">Password</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="current-password">
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <!-- Remember Me checkbox -->
                        <div class="col-lg-12 ">
                            <div class="form-group">

                                <input class="form-check-input no-validate" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-label">Remember Me</label>
                                <div class="error-msg"></div>
                            </div>

                        </div>
                        <div class="col-lg-12">
                            <button type="submit" class="btn btn-primary button">Let’s get started! </button>
                        </div>
                        <div class="col-lg-12 text-center">
                            <small><a href="{{ route('password.request.library') }}" class="forgot-password">Forgot Password ?</a></small>
                            <p>If you are Not Register ? <a href="{{route('register')}}" class="links d-inline"><em>Register Now</em></a>
                            </p>
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
                '{{url("public/img/login-slider/library-slide-1.webp")}}',
                '{{url("public/img/login-slider/library-slide-2.webp")}}',
                '{{url("public/img/login-slider/library-slide-3.webp")}}',
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