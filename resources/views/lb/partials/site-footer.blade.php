@php($lbHome = request()->routeIs('/') ? '' : url('/'))
@php($t = $t ?? fn ($s, $r = []) => strtr($s, $r))
<footer class="lb-footer">
    <div class="lb-footer-grid">
        <div class="lb-footer-brand">
            @include('lb.partials.logo')
            <p>{{ $t('Library management software for study libraries across India. Seats, fees, attendance and reports in one place.') }}</p>
            <div class="lb-footer-social">
                <a href="https://www.facebook.com/libraro.in" target="_blank" rel="noopener" aria-label="{{ $t('Libraro on :site', [':site' => 'Facebook']) }}"><x-lb-brand name="facebook" /></a>
                <a href="https://www.instagram.com/libraro.in/" target="_blank" rel="noopener" aria-label="{{ $t('Libraro on :site', [':site' => 'Instagram']) }}"><x-lb-brand name="instagram" /></a>
                <a href="https://www.linkedin.com/in/libraro/" target="_blank" rel="noopener" aria-label="{{ $t('Libraro on :site', [':site' => 'LinkedIn']) }}"><x-lb-brand name="linkedin" /></a>
                <a href="https://www.youtube.com/@Libraroindia" target="_blank" rel="noopener" aria-label="{{ $t('Libraro on :site', [':site' => 'YouTube']) }}"><x-lb-brand name="youtube" /></a>
                <a href="https://wa.me/918114479678" target="_blank" rel="noopener" aria-label="{{ $t('Chat with Libraro on WhatsApp') }}"><x-lb-brand name="whatsapp" /></a>
            </div>
        </div>
        <div>
            <h4>{{ $t('Product') }}</h4>
            <ul>
                <li><a href="{{ $lbHome }}#operations">{{ $t('Features') }}</a></li>
                <li><a href="{{ $lbHome }}#pricing">{{ $t('Pricing') }}</a></li>
                <li><a href="{{ $lbHome }}#compare">{{ $t('Compare plans') }}</a></li>
                <li><a href="{{ route('find-my-library') }}">{{ $t('Find my library') }}</a></li>
            </ul>
        </div>
        <div>
            <h4>{{ $t('Company') }}</h4>
            <ul>
                <li><a href="{{ route('about-us') }}">{{ $t('About us') }}</a></li>
                <li><a href="{{ route('blog') }}">{{ $t('Blog') }}</a></li>
                <li><a href="{{ $lbHome }}#faq">{{ $t('FAQs') }}</a></li>
                <li><a href="{{ route('contact-us') }}">{{ $t('Contact us') }}</a></li>
            </ul>
        </div>
        <div>
            <h4>{{ $t('Account') }}</h4>
            <ul>
                <li><a href="{{ route('login.library') }}">{{ $t('Library login') }}</a></li>
                <li><a href="{{ route('login.learner') }}">{{ $t('Learner login') }}</a></li>
                <li><a href="{{ route('register') }}">{{ $t('Register library') }}</a></li>
                <li><a href="tel:+918114479678">+91 81144 79678</a></li>
            </ul>
        </div>
    </div>
    <div class="lb-footer-bottom">
        <span>{{ $t('Copyright © :year Libraro.in. All rights reserved.', [':year' => date('Y')]) }}</span>
        <div>
            <a href="{{ route('privacy-policy') }}">{{ $t('Privacy policy') }}</a>
            <a href="{{ route('term-and-condition') }}">{{ $t('Terms of use') }}</a>
            <a href="{{ route('refund-policy') }}">{{ $t('Refund policy') }}</a>
        </div>
    </div>
</footer>
