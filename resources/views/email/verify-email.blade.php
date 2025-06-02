<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email OTP Verification - Libraro</title>
</head>

<body style="margin: 0; padding: 0; font-family: 'Mulish', sans-serif; background-color: #fff;">
    <div style="max-width: 650px; margin: 1.5rem auto; border: 1px solid #c9c9c9 !important; border-radius: 1.5rem;">
        <!-- Header -->
        <div>
            <img src="{{url('/public/img/opt-head.png')}}" alt="OTP Verification" style="width: 100%; border-radius: 1.5rem;">
        </div>

        <!-- Content -->
        <div style="padding: 20px; color: #333333;">
            <!-- OTP Message -->
            <div style="text-align: center; margin-bottom: 30px;">
                <h2
                    style="font-size: 26px; font-weight: 600; font-family: 'Outfit', sans-serif; color: #000; margin-bottom: 15px;">
                    Verify Your Email Address</h2>
                <p style="font-size: 16px; line-height: 1.8; font-family: 'Mulish', sans-serif;">
                    Hello {{ $name }},
                    <br>
                    Use the One-Time Password (OTP) below to verify your email address. This code is valid for the next
                    10 minutes.
                </p>

                <!-- OTP Code -->
                <div
                    style="display: inline-block; padding: 15px 30px; background-color: #000; color: #ffffff; font-size: 24px; font-weight: bold; border-radius: 5px; margin: 20px 0;">
                    {{$otp}}</div>

                <p style="font-size: 16px; line-height: 1.8; font-family: 'Mulish', sans-serif;">
                    If you didn’t request this email, please ignore it. Your account is secure.
                </p>
            </div>

            <!-- Final CTA -->
            <div style="text-align: center; margin-bottom: 30px;">
                <p style="font-size: 16px; line-height: 1.8; font-family: 'Mulish', sans-serif;">
                    Need assistance? Feel free to reach out to us anytime at <a href="mailto:support@libraro.in"
                        style="color: #000; text-decoration: none; font-weight: bold;">support@libraro.in</a> or call us at <a href="tel:8114479678" style="color: #000; text-decoration: none; font-weight: bold;">+91-8114479678</a>, <a href="tel:7737918848" style="color: #000; text-decoration: none; font-weight: bold;">+91-7737918848 </a>.We’re here to help!
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div
            style="background-color: #000000; color: #ffffff; text-align: center; padding: 20px; font-size: 14px; font-family: 'Mulish', sans-serif; border-radius: 1rem;">
            <!-- Logo -->
            <img src="{{url('/public/img/libraro-white.svg')}}" alt="Libraro Logo" style="margin-bottom: 15px; width: 150px;">

            <!-- Social Links -->
            <p style="margin: 0; margin-bottom: 1rem; font-weight: 800;">Follow us on:</p>
            <a href="https://www.facebook.com/profile.php?id=61574493811848" target="_blank"
                style="margin: 0 10px; text-decoration: none; color: #ffffff;">Facebook</a>
            <a href="https://x.com/libraroindia" target="_blank"
                style="margin: 0 10px; text-decoration: none; color: #ffffff;">Twitter</a>
            <a href="https://www.linkedin.com/in/libraro-india-081580357/" target="_blank"
                style="margin: 0 10px; text-decoration: none; color: #ffffff;">LinkedIn</a>
            <a href="https://www.instagram.com/libraroindia/" target="_blank"
                style="margin: 0 10px; text-decoration: none; color: #ffffff;">Instagram</a>

            <!-- Address -->
            <p style="margin: 15px 0; font-size: .7rem;">Office : H.No. 955, Vinoba Bhave Nagar | KOTA, RAJASTHAN | INDIA</p>

            <!-- Legal Links -->
            <p style="margin: 15px 0; font-size: .7rem;">This email was sent to noreply@libraro.in.</p>
            <p style="margin: 15px 0; font-size: .7rem;">
                <a href="{{route('privacy-policy')}}" style="text-decoration: none; color: #ffffff; margin: 0 10px;">Privacy Policy</a> |
                <a href="{{route('term-and-condition')}}" style="text-decoration: none; color: #ffffff; margin-left: 10px;">Terms of Service</a>
            </p>

            <!-- Copyright -->
            <p style="margin: 15px 0; font-size: .7rem;">&copy; 2021-2025 All rights reserved - Libraro.in®</p>
        </div>
    </div>
</body>

</html>
