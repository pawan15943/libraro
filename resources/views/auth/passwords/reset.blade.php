<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">

    <title>Reset your Password</title>
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
                <h2>Reset your Password</h2>
                <small>We're happy to see you again! 🌟</small>

                @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
                @endif

                <form action="{{ route('password.update.library') }}" method="POST">
                    @csrf
                    <div class="row g-3 mt-1">
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">
                    {{-- <div class="col-lg-12">
                        <label for="">Email Address</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" autocomplete="email" autofocus>
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div> --}}
                    <div class="col-lg-12">
                        <label for="">Password</label>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                        @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-12">
                        <label for="password-confirm"> Confirm Password</label>
                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                    </div>
                    <div class="col-lg-12">
                        <button type="submit" class="btn btn-primary button">Reset Password </button>
                    </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js"></script>
</body>

</html>