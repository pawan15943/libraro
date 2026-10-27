@extends((($iscomp ?? false) && ($isBranch ?? false)) ? 'lb.layouts.onboard-panel' : 'lb.layouts.onboard')

{{--
    Choose plan (after OTP) + upgrade / renew from "My Plan".
    Same backend: prices from route('subscriptions.getSubscriptionPrice') (GET plan_mode),
    each plan posts to route('payment.store') with library_id, subscription_id, plan_mode, price.
    Data: $subscriptions, $features, $month (plan_mode => label), $action.
--}}

@php
    $libUser = getLibrary();
    $hasPaidBefore = $libUser ? \App\Models\LibraryTransaction::where('library_id', $libUser->id)->where('is_paid', 1)->exists() : false;
    $isFirstTime = !$hasPaidBefore && (optional($libUser)->is_paid != 1);
    $currentType = $libUser->library_type ?? (Auth::user()->library_type ?? null);
    $isExpired = optional($libUser)->status == 0 || (Auth::user()->status ?? 1) == 0;
    $modeLabel = function ($label) {
        $p = preg_split('/\s+/', trim($label));
        $n = (int) ($p[0] ?? 1); $yr = str_starts_with(strtoupper($p[1] ?? ''), 'YEAR');
        if ($yr) return $n > 1 ? $n . ' years' : 'Yearly';
        return $n > 1 ? $n . ' months' : 'Monthly';
    };
    $modeKeys = array_keys($month);
    $defaultMode = in_array(1, $modeKeys) ? 1 : $modeKeys[0];
    $allFeatureNames = $features->pluck('name')->unique()->values();
    $tagline = fn ($s) => match ((int) $s->id) {
        1 => 'Up to 100 seats & 1 branch with essential features',
        2 => 'Up to 200 seats & 2 branches with smart features',
        3 => 'Unlimited seats & 3 branches with all pro features',
        default => $s->plan_description ?? '',
    };
@endphp

@section('title', $isFirstTime ? 'Choose your plan' : (($action ?? 'upgrade') === 'renew' ? 'Renew your plan' : 'Upgrade your plan'))
@section('ob_step', 'plan')
@if(!$isFirstTime) @section('ob_stepper', '0') @endif

@section('ob_content')
<div class="ob-wrap ob-plans">
    @unless($isFirstTime)
        <a href="{{ route('library.myplan') }}" class="ob-back"><x-lb-icon name="arrow-left" />Back to My Plan</a>
    @endunless

    <header class="ob-head">
        @if($isFirstTime)
            <span class="ob-eyebrow ob-eyebrow--step"><x-lb-icon name="sparkles" />Step 2 of 5 · Your email is verified</span>
            <h1>Pick the plan that fits your library.</h1>
            <p>Simple, transparent pricing. Every plan includes a free listing on Find my library and a 7-day refund.</p>
        @elseif(($action ?? 'upgrade') === 'renew')
            <span class="ob-eyebrow"><x-lb-icon name="rotate-ccw" />Renew</span>
            <h1>Renew your plan.</h1>
            <p>Choose how long you want to renew for and keep your library running without a break.</p>
        @else
            <span class="ob-eyebrow"><x-lb-icon name="trending-up" />Upgrade</span>
            <h1>Unlock more with a bigger plan.</h1>
            <p>More seats, more branches and advanced features. Your data stays exactly as it is.</p>
        @endif
    </header>

    <div class="ob-period" role="radiogroup" aria-label="Billing period" id="ob-period">
        @foreach($month as $key => $value)
            <button type="button" role="radio" data-mode="{{ $key }}" data-label="{{ $value }}" aria-checked="{{ $key == $defaultMode ? 'true' : 'false' }}">
                {{ $modeLabel($value) }}@if(str_contains(strtoupper($value), 'YEAR'))<small>Save more</small>@endif
            </button>
        @endforeach
    </div>

    <div class="ob-plan-grid ob-plan-grid--{{ min(count($subscriptions), 3) }}">
        @foreach($subscriptions as $subscription)
            @php
                $inc = $features->where('subscription_id', $subscription->id)->whereNull('deleted_at')->pluck('name')->unique()->values();
                $exc = $allFeatureNames->diff($inc)->values();
                $isPopular = (int) $subscription->id === 2 && count($subscriptions) > 1;
                $isCurrent = $currentType && (int) $subscription->id === (int) $currentType;
            @endphp
            <article class="ob-plan {{ $isPopular ? 'is-popular' : '' }}">
                <div class="ob-plan-top">
                    <div class="ob-plan-name">
                        <h2>{{ $subscription->name }}</h2>
                        @if($isPopular)<span class="ob-chip ob-chip--gold"><x-lb-icon name="star" />Most popular</span>@endif
                        @if($isCurrent)<span class="ob-chip {{ $isExpired ? 'ob-chip--err' : 'ob-chip--ok' }}">Current · {{ $isExpired ? 'Expired' : 'Active' }}</span>@endif
                    </div>
                    <p class="ob-plan-tag">{{ $tagline($subscription) }}</p>
                    <div class="ob-price">
                        <b id="subscription_fees_{{ $subscription->id }}" class="is-loading">₹—</b>
                        <span id="plan_period_{{ $subscription->id }}"></span>
                    </div>
                    <div class="ob-slash" id="before_discount_fees_{{ $subscription->id }}"></div>

                    <form id="payment-form-{{ $subscription->id }}" action="{{ route('payment.store') }}" method="POST" enctype="multipart/form-data" class="ob-plan-form">
                        @csrf
                        <input type="hidden" name="library_id" value="{{ Auth::user()->id }}">
                        <input type="hidden" name="subscription_id" id="subscription_id_{{ $subscription->id }}" value="{{ $subscription->id }}">
                        <input type="hidden" name="plan_mode" id="plan_mode_{{ $subscription->id }}" value="{{ $defaultMode }}">
                        <input type="hidden" name="price" id="price_{{ $subscription->id }}">
                        <button type="submit" class="lb-btn lb-btn--lg lb-btn--block ob-plan-cta" data-loading-text="Please wait…">
                            <span class="lb-btn-label">Choose {{ \Illuminate\Support\Str::of($subscription->name)->replace(' Plan', '') }}</span><x-lb-icon name="arrow-right" size="18" />
                        </button>
                    </form>
                </div>

                <div class="ob-plan-feat">
                    <div class="ob-plan-feat-head"><span>What's included</span><b>{{ $inc->count() }} features</b></div>
                    <ul>
                        @foreach($inc->take(7) as $f)<li><i><x-lb-icon name="check" /></i>{{ $f }}</li>@endforeach
                    </ul>
                    @if($inc->count() > 7 || $exc->isNotEmpty())
                        <details class="ob-more">
                            <summary><span class="ob-more-open">See all features</span><span class="ob-more-close">Show less</span><x-lb-icon name="chevron-down" /></summary>
                            <ul>
                                @foreach($inc->slice(7) as $f)<li><i><x-lb-icon name="check" /></i>{{ $f }}</li>@endforeach
                                @foreach($exc as $f)<li class="is-off"><i><x-lb-icon name="minus" /></i><span><span class="lb-sr-only">Not included: </span>{{ $f }}</span></li>@endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    <ul class="ob-assure">
        <li><x-lb-icon name="shield-check" /><span><b>Secure payment</b>Razorpay · UPI, cards, net banking</span></li>
        <li><x-lb-icon name="rotate-ccw" /><span><b>7-day refund</b>Not right for you? Get your money back</span></li>
        <li><x-lb-icon name="headset" /><span><b>Free setup help</b>We help you set up on a call</span></li>
    </ul>
    <p class="ob-foot-note">Prices exclude GST where applicable.</p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var url = @json(route('subscriptions.getSubscriptionPrice'));
    var group = document.getElementById('ob-period');
    var req = 0;
    function money(v) { return Number(v).toLocaleString('en-IN'); }
    function period(label) {
        var p = String(label).trim().split(/\s+/), n = parseInt(p[0], 10) || 1, yr = (p[1] || '').toLowerCase().indexOf('year') === 0;
        return '/ ' + (n > 1 ? n + ' ' : '') + (yr ? (n > 1 ? 'years' : 'year') : (n > 1 ? 'months' : 'month'));
    }
    function load(mode, label) {
        var my = ++req;
        document.querySelectorAll('.ob-price b').forEach(function (b) { b.classList.add('is-loading'); });
        fetch(url + '?plan_mode=' + encodeURIComponent(mode), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (res) {
                if (my !== req) return;
                (res.subscription_prices || []).forEach(function (s) {
                    var fee = document.getElementById('subscription_fees_' + s.id); if (!fee) return;
                    var per = document.getElementById('plan_period_' + s.id), slash = document.getElementById('before_discount_fees_' + s.id);
                    fee.classList.remove('is-loading');
                    if (Number(s.fees) === 0) { fee.textContent = 'FREE'; per.textContent = ''; slash.innerHTML = ''; }
                    else {
                        fee.textContent = '₹' + money(s.fees); per.textContent = period(label);
                        var diff = Number(s.slash_price) - Number(s.fees);
                        slash.innerHTML = (s.slash_price && diff > 0) ? '<s>₹' + money(s.slash_price) + '</s><em>You save ₹' + money(diff) + '</em>' : '';
                    }
                    var m = document.getElementById('plan_mode_' + s.id), pr = document.getElementById('price_' + s.id);
                    if (m) m.value = mode; if (pr) pr.value = s.fees;
                });
            })
            .catch(function () { if (my === req) window.obToast('Could not load prices. Please check your internet and try again.', 'err'); });
    }
    group.addEventListener('click', function (e) {
        var b = e.target.closest('[data-mode]'); if (!b) return;
        group.querySelectorAll('[data-mode]').forEach(function (x) { x.setAttribute('aria-checked', x === b ? 'true' : 'false'); });
        load(b.getAttribute('data-mode'), b.getAttribute('data-label'));
    });
    var first = group.querySelector('[aria-checked="true"]') || group.querySelector('[data-mode]');
    if (first) load(first.getAttribute('data-mode'), first.getAttribute('data-label'));
    // One click only (the payment page creates a Razorpay order)
    document.querySelectorAll('.ob-plan-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            var p = f.querySelector('[name=price]');
            if (!p.value) { e.preventDefault(); window.obToast('Prices are still loading. Please wait a moment.', 'err'); return; }
            var b = f.querySelector('button'); b.disabled = true; b.classList.add('is-loading');
            b.querySelector('.lb-btn-label').textContent = b.getAttribute('data-loading-text');
        });
    });
    window.addEventListener('pageshow', function (e) { if (e.persisted) document.querySelectorAll('.ob-plan-form button').forEach(function (b) { b.disabled = false; b.classList.remove('is-loading'); }); });
})();
</script>
@endpush
