@php($lbHome = request()->routeIs('/') ? '' : url('/'))
<footer class="lb-footer">
    <div class="lb-footer-grid">
        <div class="lb-footer-brand">
            @include('lb.partials.logo')
            <p>Library management software for study libraries across India. Seats, fees, attendance and reports in one place.</p>
            <div class="lb-footer-social">
                <a href="https://www.facebook.com/libraro.in" target="_blank" rel="noopener" aria-label="Libraro on Facebook"><x-lb-brand name="facebook" /></a>
                <a href="https://www.instagram.com/libraro.in/" target="_blank" rel="noopener" aria-label="Libraro on Instagram"><x-lb-brand name="instagram" /></a>
                <a href="https://www.linkedin.com/in/libraro/" target="_blank" rel="noopener" aria-label="Libraro on LinkedIn"><x-lb-brand name="linkedin" /></a>
                <a href="https://www.youtube.com/@Libraroindia" target="_blank" rel="noopener" aria-label="Libraro on YouTube"><x-lb-brand name="youtube" /></a>
                <a href="https://wa.me/918114479678" target="_blank" rel="noopener" aria-label="Chat with Libraro on WhatsApp"><x-lb-brand name="whatsapp" /></a>
            </div>
        </div>
        <div>
            <h4>Product</h4>
            <ul>
                <li><a href="{{ $lbHome }}#features">Features</a></li>
                <li><a href="{{ $lbHome }}#pricing">Pricing</a></li>
                <li><a href="{{ $lbHome }}#compare">Compare plans</a></li>
                <li><a href="{{ route('find-my-library') }}">Find my library</a></li>
            </ul>
        </div>
        <div>
            <h4>Company</h4>
            <ul>
                <li><a href="{{ route('about-us') }}">About us</a></li>
                <li><a href="{{ route('blog') }}">Blog</a></li>
                <li><a href="{{ $lbHome }}#faq">FAQs</a></li>
                <li><a href="{{ route('contact-us') }}">Contact us</a></li>
            </ul>
        </div>
        <div>
            <h4>Account</h4>
            <ul>
                <li><a href="{{ route('login.library') }}">Library login</a></li>
                <li><a href="{{ route('login.learner') }}">Learner login</a></li>
                <li><a href="{{ route('register') }}">Register library</a></li>
                <li><a href="tel:+918114479678">+91 81144 79678</a></li>
            </ul>
        </div>
    </div>
    <div class="lb-footer-bottom">
        <span>Copyright © {{ date('Y') }} Libraro.in. All rights reserved.</span>
        <div>
            <a href="{{ route('privacy-policy') }}">Privacy policy</a>
            <a href="{{ route('term-and-condition') }}">Terms of use</a>
            <a href="{{ route('refund-policy') }}">Refund policy</a>
        </div>
    </div>
</footer>
