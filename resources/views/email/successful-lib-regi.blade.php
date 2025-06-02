<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revolutionize Your Library with Libraro</title>
</head>

<body style="margin: 0; padding: 0; font-family: 'Mulish', sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 650px; margin: 0 auto; border-radius: 1px solid #ababab;">
        <!-- Header -->
        <div style="margin-top: 1.5rem;">
            
           <img src="{{url('/public/img/001.webp')}}" alt="img" style="width: 100%; border-radius: 1.5rem;">
        </div>

        <!-- Content -->
        <div style="padding: 20px; color: #333333;">
            <!-- Welcome Message -->
            <div style="text-align: center; margin-bottom: 30px;">
                <h2
                    style="font-size: 26px; font-weight: 600; font-family: 'Outfit', sans-serif; color: #000; margin-bottom: 15px;">
                    Dear {{ $name }},</h2>
                <p style="font-size: 16px; line-height: 1.8; font-family: 'Mulish', sans-serif;">We’re excited to
                    introduce you to <strong>Libraro</strong>—the ultimate solution to transform your library into a
                    modern, efficient, and visitor-friendly space. Say goodbye to manual processes and hello to seamless
                    library management!</p>
                <a href="{{route('register')}}"
                    style="display: inline-block; margin: 20px 0; padding: 12px 30px; background-color: #000; color: #ffffff; font-size: 16px; text-decoration: none; border-radius: 5px; font-family: 'Mulish', sans-serif;">Get
                    Started Today</a>
            </div>

            
            <!-- Final CTA -->
            <div style="text-align: center; margin-bottom: 30px;">
                <h2
                    style="font-size: 26px; font-weight: 600; font-family: 'Outfit', sans-serif; color: #000; margin-bottom: 15px;">
                    Ready to Transform Your Library?</h2>
                <p style="font-size: 16px; line-height: 1.8; font-family: 'Mulish', sans-serif;">Join hundreds of
                    libraries already using Libraro to streamline operations and enhance visitor experiences. Don’t
                    wait—start your journey today!</p>
                <a href="{{route('register')}}"
                    style="display: inline-block; margin: 20px 0; padding: 12px 30px; background-color: #000; color: #ffffff; font-size: 16px; text-decoration: none; border-radius: 5px; font-family: 'Mulish', sans-serif;">Sign
                    Up Now</a>
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
