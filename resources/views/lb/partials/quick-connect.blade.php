{{--
    Quick connect — floating "Talk to us" button on every website page.
    WhatsApp / Call in one tap, or a short call-back form that posts to the existing
    inquiry endpoint (route submit.inquiry → SiteController@Inquerystore, same validation).
    Any element with [data-lb-qc-open] also opens it.
--}}
@php
    $qcWa = 'https://wa.me/918114479678?text=' . rawurlencode('Hi Libraro, I run a library and want to know how Libraro can help me.');
@endphp
<div class="lb-qc" data-lb-qc>
    <button type="button" class="lb-qc-fab" data-lb-qc-toggle aria-expanded="false" aria-controls="lb-qc-panel">
        <span class="lb-qc-fab-icon"><x-lb-icon name="message-circle" class="lb-qc-i-open" /><x-lb-icon name="x" class="lb-qc-i-close" /></span>
        <span class="lb-qc-fab-label">Talk to us</span>
    </button>

    <div class="lb-qc-panel" id="lb-qc-panel" role="dialog" aria-modal="false" aria-labelledby="lb-qc-title" hidden>
        <div class="lb-qc-head">
            <div class="lb-qc-avatar" aria-hidden="true"><x-lb-icon name="headset" /></div>
            <div>
                <h2 id="lb-qc-title">Talk to a Libraro expert</h2>
                <p>Free advice for your library. No obligation.</p>
            </div>
            <button type="button" class="lb-qc-close" data-lb-qc-close aria-label="Close"><x-lb-icon name="x" size="18" /></button>
        </div>

        <div class="lb-qc-quick">
            <a href="{{ $qcWa }}" target="_blank" rel="noopener" class="lb-qc-btn lb-qc-btn--wa"><x-lb-brand name="whatsapp" />WhatsApp</a>
            <a href="tel:+918114479678" class="lb-qc-btn"><x-lb-icon name="phone" />Call now</a>
        </div>

        <div class="lb-qc-or"><span>or get a call back</span></div>

        <div data-lb-form-card>
            <form class="lb-form lb-qc-form" data-lb-ajax="{{ route('submit.inquiry') }}" novalidate>
                @csrf
                <input type="hidden" name="terms" value="1">
                <div class="lb-field">
                    <label class="lb-label" for="qc_name">Your name</label>
                    <div class="lb-input"><x-lb-icon name="user-round" /><input type="text" id="qc_name" name="full_name" class="char-only" placeholder="Your name" autocomplete="name" maxlength="255" required></div>
                </div>
                <div class="lb-field">
                    <label class="lb-label" for="qc_mobile">Mobile number</label>
                    <div class="lb-input"><x-lb-icon name="smartphone" /><input type="tel" id="qc_mobile" name="mobile_number" class="digit-only" data-maxlength="10" minlength="10" pattern="[0-9]{10}" title="Enter a valid 10-digit mobile number." inputmode="numeric" placeholder="98765 43210" autocomplete="tel-national" required></div>
                </div>
                <div class="lb-field">
                    <label class="lb-label" for="qc_email">Email</label>
                    <div class="lb-input"><x-lb-icon name="mail" /><input type="email" id="qc_email" name="email" placeholder="you@example.com" autocomplete="email" required></div>
                </div>
                <div class="lb-field">
                    <label class="lb-label" for="qc_need">What do you need help with?</label>
                    <div class="lb-input"><x-lb-icon name="list-checks" />
                        <select id="qc_need" name="message" required>
                            <option value="Quick connect: I want a free demo of Libraro.">A free demo</option>
                            <option value="Quick connect: I want to know which plan fits my library.">Choosing a plan</option>
                            <option value="Quick connect: I want help moving my data from Excel / registers.">Moving my data</option>
                            <option value="Quick connect: I already use Libraro and need support.">Support for my account</option>
                            <option value="Quick connect: Something else — please call me.">Something else</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="lb-btn lb-btn--block" data-loading-text="Sending…"><span class="lb-btn-label">Call me back</span><x-lb-icon name="phone-call" size="18" /></button>
                <p class="lb-qc-legal">By sending, you agree to our <a href="{{ route('term-and-condition') }}" target="_blank" rel="noopener">Terms</a>. We only use your number to call you.</p>
            </form>
            <div class="lb-form-success lb-qc-success" hidden>
                <div class="lb-ok"><x-lb-icon name="circle-check-big" size="28" /></div>
                <h3>We’ll call you soon</h3>
                <p>Thanks! A Libraro expert will call you on your number.</p>
            </div>
        </div>
    </div>
</div>
