@php
    /* Labels via $t (lb/i18n); the home page may pass its own $lbNavT. Language switcher: $lbLangUrls + $lbLang (shared by lb/i18n/boot.php). */
    $t = $t ?? fn ($s) => $s;
    $nt = array_merge([
        'home' => $t('Home'), 'features' => $t('Features'), 'pricing' => $t('Pricing'), 'blog' => $t('Blog'), 'contact' => $t('Contact'),
        'find' => $t('Find my library'), 'login' => $t('Log in'), 'add' => $t('Add your library'), 'call' => $t('Call us'), 'learner' => $t('Learner login'),
    ], $lbNavT ?? []);
    $lbIsHome = request()->routeIs('/');
    $lbQs = (isset($lbLang) && $lbLang !== 'en') ? '?lang=' . $lbLang : '';
    $lbHash = fn ($id) => $lbIsHome ? '#' . $id : url('/') . '#' . $id;
    $lbNav = [
        [$nt['home'], url('/') . $lbQs, $lbIsHome],
        [$nt['features'], $lbHash('operations'), false],
        [$nt['pricing'], $lbHash('pricing'), false],
        [$nt['find'], route('find-my-library'), request()->routeIs('find-my-library', 'libraryDetail')],
        [$nt['blog'], route('blog'), request()->routeIs('blog', 'blog-detail')],
        [$nt['contact'], route('contact-us'), request()->routeIs('contact-us')],
    ];
    $lbLangSwitch = isset($lbLangUrls) && is_array($lbLangUrls);
    $lbLangNames = $lbLangNames ?? ['en' => 'English', 'hi' => 'हिंदी', 'hinglish' => 'Hinglish'];
    $lbLangShort = ['en' => 'EN', 'hi' => 'हि', 'hinglish' => 'HG'];
@endphp
<header class="lb-nav">
    <nav class="lb-nav-bar" aria-label="{{ $t('Main') }}">
        @include('lb.partials.logo')
        <div class="lb-nav-links">
            @foreach($lbNav as [$label, $href, $active])
                <a href="{{ $href }}" @if($active) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </div>
        <div class="lb-nav-right">
            @if($lbLangSwitch)
                <div class="lh-lang" data-lh-lang>
                    <button type="button" aria-haspopup="true" aria-expanded="false" aria-label="{{ $lbLangLabel ?? 'Language' }}: {{ $lbLangNames[$lbLang] }}">
                        <x-lb-icon name="message-square-text" />{{ $lbLangShort[$lbLang] }}<x-lb-icon name="chevron-down" />
                    </button>
                    <div class="lh-lang-menu" hidden>
                        @foreach($lbLangUrls as $code => $href)
                            <a href="{{ $href }}" hreflang="{{ ['en' => 'en-IN', 'hi' => 'hi-IN', 'hinglish' => 'hi-Latn-IN'][$code] }}" lang="{{ $code === 'hi' ? 'hi' : 'en' }}" @if($code === $lbLang) aria-current="true" @endif>{{ $lbLangNames[$code] }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
            <a href="tel:+918114479678" class="lh-nav-call" aria-label="{{ $nt['call'] }}" title="{{ $nt['call'] }}: +91 81144 79678"><x-lb-icon name="phone" /></a>
            <a href="{{ route('register') }}" class="lb-btn lb-btn--sm">{{ $nt['add'] }}</a>
            @include('lb.partials.theme-toggle')
            <button type="button" class="lb-nav-burger" aria-label="{{ $t('Menu') }}" aria-expanded="false" aria-controls="lb-nav-menu">
                <x-lb-icon name="menu" class="lb-i-menu" /><x-lb-icon name="x" class="lb-i-x" />
            </button>
        </div>
    </nav>
    <div class="lb-nav-menu" id="lb-nav-menu">
        @foreach($lbNav as [$label, $href, $active])
            <a href="{{ $href }}">{{ $label }}</a>
        @endforeach
        <a href="tel:+918114479678">{{ $nt['call'] }} · +91 81144 79678</a>
        @if($lbLangSwitch)
            <div class="lh-lang-row">
                @foreach($lbLangUrls as $code => $href)
                    <a href="{{ $href }}" @if($code === $lbLang) aria-current="true" @endif>{{ $lbLangNames[$code] }}</a>
                @endforeach
            </div>
        @endif
        <div class="lb-nav-menu-cta lb-nav-menu-cta--single">
            <a href="{{ route('register') }}" class="lb-btn">{{ $nt['add'] }}</a>
        </div>
    </div>
</header>
