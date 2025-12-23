<!doctype html>
<html xmlns="https://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('public/img/favicon.ico') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <!-- Icons -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #000050 !important;

        }

        .form-control, .btn {
            height: 45px;
            background: #fff;
            color: #000;
            border-color: #000;
            border-radius: .6rem;
            padding: 0 18px;
        }

        .verify-form {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        img.logo {
            width: 200px;
            display: block;
            margin: 0 auto;
        }
    </style>

</head>

<body>

    <div class="container">
        <div class="row g-4 justify-content-center align-items-center" style="height:100vh;">
            <div class="col-lg-6">
                <div class="verify-form">
                    <div class="col-lg-12">
                        
                        <img src="{{ asset('public/img/logo-whitw.png') }}" alt="logo" class="logo">
                    </div>
                    <div class="login-form">
                        <h4 class="text-center text-white mb-4">Attendance App</h4>
                        <div class="row g-4 px-2">

                            <div class="col-lg-12">
                                <input type="text" id="learner_no_uid" placeholder="Enter Learner Unique ID" class="form-control">
                            </div>
                            <div class="col-lg-12">
                                <input type="text" id="learner_mobile" placeholder="Mobile Number" class="form-control">
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" id="verifyLearner" class="btn btn-primary w-100" >Verify <i class=""></i></button>
                            </div>
                        </div>
                    </div>
                    <small class="text-center text-white">Support : +91-8114479678</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        $('#verifyLearner').on('click', function () {
            let uid = $('#learner_no_uid').val().trim();
            let mobile = $('#learner_mobile').val().trim();

            // ✅ Frontend required validation
            if (!uid) {
                $('#verifyMsg').text('Learner No is required');
                return;
            }

            if (!mobile) {
                $('#verifyMsg').text('Mobile number is required');
                return;
            }

            // Optional: mobile format
            if (!/^[6-9]\d{9}$/.test(mobile)) {
                $('#verifyMsg').text('Enter valid 10 digit mobile number');
                return;
            }
            $.ajax({
                url: "{{ route('attendance.verify.learner') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    uid: $('#learner_no_uid').val(),
                    mobile: $('#learner_mobile').val()
                },
                success: function (res) {
                    localStorage.setItem('verify_token', res.verify_token);
                    $('#learner_no_uid, #learner_mobile, #verifyLearner').hide();
                    window.location.href = "{{ route('attendance.dashboard') }}";

                    
                },
                error: function (xhr) {
                    $('#verifyMsg').text(xhr.responseJSON.message);
                }
            });
        }); 
        $(document).ready(function () {
  
            $.post("{{ url('/attendance/auto-verify') }}", {
                _token: "{{ csrf_token() }}"
            }, function (res) {
                if (res.status) {
                    localStorage.setItem('verify_token', res.verify_token);
                    $('#learner_no_uid, #learner_mobile, #verifyLearner').hide();
                    window.location.href = "{{ route('attendance.dashboard') }}";
                }
            });
        });
    </script>

</body>

</html>