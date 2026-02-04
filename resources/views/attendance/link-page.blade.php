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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #000050 !important;
        }

        .form-control, .btn {
            height: 45px;
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

        div#verifyMsg {
            color: red;
            font-weight: 400 !important;
        }

        button#verifyLearner {
            background: #0a98ca;
            color: #fff;
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

                    <div class="row g-4 px-2 pt-3">
                        <div class="row">
                            <div class="col-lg-12 text-center">
                                <div id="verifyMsg"></div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <select class="form-select form-control" id="login_with" name="login_with">
                                <option value="">Choose</option>
                                <option value="dob">Date of Birth</option>
                                <option value="email">Email</option>
                                <option value="learner_no">Member UID</option>
                            </select>
                        </div>

                        <div class="col-lg-12">
                            <input type="text" name="uid" id="learner_no_uid"
                                   placeholder="Enter Member UID" class="form-control">
                        </div>

                        <div class="col-lg-12">
                            <input type="text" name="mobile" id="learner_mobile"
                                   placeholder="Mobile Number" class="form-control digit-only" maxlength="10">
                        </div>

                        <div class="col-lg-12">
                            <button type="button" id="verifyLearner" class="btn w-100">
                                <span class="btn-text">Verify</span>
                                <span class="spinner-border spinner-border-sm d-none ms-2"
                                      role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <small class="text-center text-white">Support : +91-8114479678</small>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    var elements = document.querySelectorAll('.digit-only');
    for (i in elements) {
        elements[i].onkeypress = function (e) {
            this.value = this.value.replace(/^0+/, '');
            if (isNaN(this.value + "" + String.fromCharCode(e.charCode)))
                return false;
        }
        elements[i].onpaste = function (e) {
            e.preventDefault();
        }
    }
    $('.digit-only').on('keyup', function () {
        $(this).val($(this).val().replace(/\s/g, ''));
    });

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

<script>
    $('#verifyLearner').on('click', function () {

        let btn = $(this);
        btn.prop('disabled', true);
        btn.find('.btn-text').text('Verifying');
        btn.find('.spinner-border').removeClass('d-none');

        $(".is-invalid").removeClass("is-invalid");
        $(".invalid-feedback").remove();
        $('#verifyMsg').text('');

        $.ajax({
            url: "{{ route('attendance.verify.learner') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                login_with: $('#login_with').val(),
                uid: $('#learner_no_uid').val(),
                mobile: $('#learner_mobile').val()
            },
            success: function (res) {
                if (res.success) {
                    localStorage.setItem('verify_token', res.verify_token);
                    window.location.href = "{{ route('attendance.dashboard') }}";
                } else if (res.errors) {
                    btn.prop('disabled', false);
                    btn.find('.btn-text').text('Verify');
                    btn.find('.spinner-border').addClass('d-none');

                    $.each(res.errors, function (key, value) {
                        let element = $("[name='" + key + "']");
                        element.addClass("is-invalid");
                        element.after('<span class="invalid-feedback">' + value + '</span>');
                    });
                } else {
                    btn.prop('disabled', false);
                    btn.find('.btn-text').text('Verify');
                    btn.find('.spinner-border').addClass('d-none');
                    $('#verifyMsg').text(res.message ?? 'Something went wrong');
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                btn.find('.btn-text').text('Verify');
                btn.find('.spinner-border').addClass('d-none');

                if (xhr.status === 422) {
                    $.each(xhr.responseJSON.errors, function (key, value) {
                        let element = $("[name='" + key + "']");
                        element.addClass("is-invalid");
                        element.after('<span class="invalid-feedback">' + value[0] + '</span>');
                    });
                } else {
                    $('#verifyMsg').text(xhr.responseJSON.message ?? 'Something went wrong');
                }
            }
        });
    });

    $(document).ready(function () {
        $.post("{{ url('/attendance/auto-verify') }}", {
            _token: "{{ csrf_token() }}"
        }, function (res) {
            if (res.status) {
                localStorage.setItem('verify_token', res.verify_token);
                window.location.href = "{{ route('attendance.dashboard') }}";
            }
        });
    });
</script>

</body>
</html>
