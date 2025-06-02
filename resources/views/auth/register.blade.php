<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">

    <title>Register Library on Libraro | Grow with Library Booking Software</title>

    <meta name="description" content="Register your library today with Libraro and start managing seat bookings, payments, expenses, and reports from one intuitive dashboard.">

    <meta name="keywords" content="Library Software Registration, Library Sign Up, Seat Booking Software, Library Management Software Join">

    <meta name="author" content="TECHITO India Pvt. Ltd.">

    <meta property="og:title" content="Library Sign Up | Get Started with Libraro Today" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('public/css/style.css') }}" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        {
            "@context": "https://schema.org",
            "@type": "RegisterAction",
            "name": "Library Sign Up",
            "target": "https://www.libraro.in/library/register"
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
                <h2>Get Started with Us!</h2>
                <small>Register Now and Unlock Exciting Opportunities!</small>
                <form action="{{ route('library.store') }}" method="POST" class="validateForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3 mt-1">
                        <div class="col-lg-12">
                            <label>Library Name<span>*</span></label>
                            <input type="text" class="form-control char-only @error('library_name') is-invalid @enderror" name="library_name"
                                value="{{ old('library_name') }}">
                            @error('library_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror

                        </div>
                        <div class="col-lg-12">
                            <label for="">Email Id <span>*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                                value="{{ old('email') }}">
                            <!-- <span class="text-secondary">Please provide a correct email ID for future communications.</span> -->
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-12">
                            <label for="">Contact No. (WhatsApp Number) <span>*</span></label>
                            <input type="text" class="form-control digit-only @error('library_mobile') is-invalid @enderror" name="library_mobile" maxlength="10"
                                value="{{ old('library_mobile') }}">
                            <!-- <span class="text-secondary">Provide a correct WhatsApp number for future communications and reminders.</span> -->
                            @error('library_mobile')

                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-12">
                            <label for="">Password <span>*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" name="password">
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        {{-- <div class="col-lg-12">
                            <label for="">Confirm Password <span>*</span></label>
                            <input type="password" class="form-control confirm-password @error('password_confirmation') is-invalid @enderror" name="password_confirmation">
                                @error('password_confirmation')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div> --}}
                    <div class="col-lg-12 form-group">
                        <input type="checkbox" class="me-2 form-check-input @error('terms') is-invalid @enderror" name="terms" id="terms">
                        <label class="form-check-label" for="terms" style="margin-top:.25rem;">
                            I agree to the Library Manager <a href="#">Terms and Conditions.</a><sup class="text-danger">*</sup>
                        </label>

                        @error('terms')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                        <div class="error-msg"></div>
                    </div>

                    <div class="col-lg-12">
                        <button type="submit" value="Login" class="btn btn-primary button">Register Now</button>
                    </div>
                    <div class="col-lg-12 text-center">
                        <p>Already a member? <a href="{{route('login.library')}}" class="links d-inline"><em>Log in now.</em></a>
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
                '{{url("public/img/login-slider/library-slide-3.webp")}}',
                '{{url("public/img/login-slider/register-slide-1.webp")}}',
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