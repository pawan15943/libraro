<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3 text-center text-md-start">
                <img src="{{ asset('public/img/libraro-white.svg') }}" alt="logo" class="logo">
            </div>
            <div class="col-lg-3">
                <h4 class="text-center text-md-start">Important Links</h4>
                <ul>
                    <li><a href="{{url('/')}}">Home</a></li>
                    <li><a href="{{route('about-us')}}">About Us</a></li>
                    <li><a href="{{url('/#faq')}}">Faq's</a></li>
                    <li><a href="{{route('blog')}}">Blog</a></li>
                    <li><a href="{{route('contact-us')}}">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h4 class="text-center text-md-start">Other Links</h4>
                <ul>
                    <li><a href="{{route('privacy-policy')}}">Privacy Policy</a></li>
                    <li><a href="{{route('term-and-condition')}}">Terms of Use</a></li>
                    <li><a href="{{route('refund-policy')}}">Refund Policy</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h4 class="text-center text-md-start">Follow Us On</h4>
                <ul class="social">
                    <li><a href="https://www.facebook.com/libraro.in" target="_blank"><i class="fab fa-facebook"></i></a></li>
                    <li><a href="https://www.instagram.com/libraro.in/" target="_blank"><i class="fab fa-instagram"></i></a></li>
                    <li><a href="https://www.linkedin.com/in/libraro/" target="_blank"><i class="fab fa-linkedin"></i></a></li>
                    <li><a href="https://www.youtube.com/@Libraroindia" target="_blank"><i class="fab fa-youtube"></i></a></li>
                    <!-- <li><a href="https://x.com/libraroindia" target="_blank"><i class="fab fa-twitter"></i></a></li> -->
                </ul>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-lg-12">
            
                <p class="py-1 text-center text-white m-0">Copyright © {{ date('Y') }} Libraro.in. All Rights Reserved.</p>
            </div>
        </div>
    </div>


</footer>