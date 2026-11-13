@extends('lb.layouts.site')
@php
    extract(require resource_path('views/lb/i18n/boot.php')); // website language: lb/i18n/boot.php
@endphp

@section('content')
    @include('lb.partials.page-hero', [
        'heading' => $t('We’re here to help.'),
        'lead'  => $t('Questions, a demo, or a partnership? Call, WhatsApp or send us a message and our team will get back to you.'),
        'crumb' => $t('Contact us'),
    ])

    <section class="lb-contact" aria-label="{{ $t('Contact details') }}">
        <div class="lb-contact-cards">
            <a class="lb-contact-card" href="tel:+918114479678">
                <i><x-lb-icon name="phone" /></i>
                <b>{{ $t('Call us') }}</b>
                <span>+91 81144 79678<br>+91 83860 07688</span>
            </a>
            <a class="lb-contact-card" href="https://wa.me/918114479678" target="_blank" rel="noopener">
                <i><x-lb-brand name="whatsapp" /></i>
                <b>{{ $t('WhatsApp') }}</b>
                <span>{{ $t('Chat with our team') }}</span>
            </a>
            <a class="lb-contact-card" href="mailto:support@libraro.in">
                <i><x-lb-icon name="mail" /></i>
                <b>{{ $t('Email') }}</b>
                <span>support@libraro.in</span>
            </a>
        </div>

        <div class="lb-contact-grid">
            <div class="lb-contact-office">
                <h2>{{ $t('Corporate office') }}</h2>
                <p><x-lb-icon name="map-pin" />{{ $t('955, Vinoba Bhave Nagar, Kota, Rajasthan') }}<br>{{ $t('Landmark: New Balaji Computer Classes') }}</p>
                <h3>{{ $t('Partner of Techito India') }}</h3>
                <p class="lb-muted">{{ $t('We are proud to be a part of Techito India, delivering innovative IT solutions across various industries.') }}</p>
                <div class="lb-map">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3631.3765925056978!2d75.8345118!3d25.116712!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x396f845f15555555%3A0x638a21618de8530!2s955%2C%20Vinoba%20Bhave%20Nagar%2C%20Kota%2C%20Rajasthan%20324005!5e1!3m2!1sen!2sin!4v1737828277134!5m2!1sen!2sin" title="{{ $t('Libraro office on Google Maps') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                </div>
            </div>

            <div class="lb-demo-card" data-lb-form-card>
                <h2 class="lb-form-title">{{ $t('Request a callback') }}</h2>
                <p class="lb-muted lb-form-sub">{{ $t('Leave your details and we’ll call you back.') }}</p>
                <form id="inqueryForm" class="lb-form lb-contact-form" data-lb-ajax="{{ route('submit.inquiry') }}" novalidate>
                    @csrf
                    <div class="lb-field">
                        <label class="lb-label" for="c_name">{{ $t('Full name') }}</label>
                        <div class="lb-input"><x-lb-icon name="user-round" /><input type="text" id="c_name" name="full_name" class="char-only" placeholder="{{ $t('Your name') }}" autocomplete="name" maxlength="255" required></div>
                    </div>
                    <div class="lb-field">
                        <label class="lb-label" for="c_mobile">{{ $t('Mobile number') }}</label>
                        <div class="lb-input"><x-lb-icon name="smartphone" /><input type="tel" id="c_mobile" name="mobile_number" class="digit-only" data-maxlength="10" minlength="8" inputmode="numeric" placeholder="98765 43210" autocomplete="tel-national" required></div>
                    </div>
                    <div class="lb-field">
                        <label class="lb-label" for="c_email">{{ $t('Email') }}</label>
                        <div class="lb-input"><x-lb-icon name="mail" /><input type="email" id="c_email" name="email" placeholder="you@example.com" autocomplete="email" required></div>
                    </div>
                    <div class="lb-field">
                        <label class="lb-label" for="c_message">{{ $t('Message') }}</label>
                        <div class="lb-input lb-input--area"><textarea id="c_message" name="message" rows="4" maxlength="1000" placeholder="{{ $t('How can we help?') }}" required></textarea></div>
                    </div>
                    <div class="lb-field">
                        <label class="lb-check" for="c_terms">
                            <input type="checkbox" name="terms" id="c_terms" required>
                            <span>{!! $t('I agree to the Libraro :terms.', [':terms' => '<a href="' . e(route('term-and-condition')) . '" target="_blank" rel="noopener">' . e($t('Terms and Conditions')) . '</a>']) !!}</span>
                        </label>
                    </div>
                    <button type="submit" class="lb-btn lb-btn--lg lb-btn--block" data-loading-text="{{ $t('Sending…') }}"><span class="lb-btn-label">{{ $t('Request callback') }}</span><x-lb-icon name="send" size="18" /></button>
                </form>
                <div class="lb-demo-success lb-form-success" hidden>
                    <div class="lb-ok"><x-lb-icon name="circle-check-big" size="30" /></div>
                    <h3>{{ $t('Thank you!') }}</h3>
                    <p>{{ $t('We’ve got your message and will call you back soon.') }}</p>
                </div>
            </div>
        </div>
    </section>
@endsection
