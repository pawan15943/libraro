<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
    <title>Login Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">

    <link href="{{ asset('public/css/style.css') }}" rel="stylesheet">
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
                <h2>Library Learner</h2>
                @if(session('success'))
                <div class="alert alert-success mb-0 mt-1">
                    {{ session('success') }}
                </div>
                @elseif($errors->has('error'))
                <div class="alert alert-danger mb-0 mt-1">
                    {{ $errors->first('error') }}
                </div>
                @endif
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <input type="hidden" name="user_type" value="learner">
                    <div class="row g-3 mt-1">
                        <div class="col-lg-12">
                            <label for="">Learner No.</label>
                            <input id="learner_no" type="text" class="form-control @error('learner_no') is-invalid @enderror" name="learner_no" value="{{ old('learner_no') }}" autocomplete="learner_no" autofocus>
                            @error('learner_no')
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
                        <div class="col-lg-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    Remember Me
                                </label>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <button type="submit" class="btn btn-primary button">Login Now <i class="bi bi-arrow-right"></i></button>
                        </div>

                    </div>
                </form>
            </div>
            <div class="bottom"></div>
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
                '{{url("public/img/login-slider/learner-slide-2.webp")}}',
                '{{url("public/img/login-slider/learner-slide-1.webp")}}',
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
    <script>
        // Prevent pinch zoom
        document.addEventListener('touchstart', function(e) {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, {
            passive: false
        });

        // Prevent double-tap zoom
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(e) {
            const now = new Date().getTime();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
</body>

</html>