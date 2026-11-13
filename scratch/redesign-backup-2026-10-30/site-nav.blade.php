@php
    $lbIsHome = request()->routeIs('/');
    $lbHash = fn ($id) => $lbIsHome ? '#' . $id : url('/') . '#' . $id;
    $lbNav = [
        ['Home', route('/'), $lbIsHome],
        ['About', route('about-us'), request()->routeIs('about-us')],
        ['Find my library', route('find-my-library'), request()->routeIs('find-my-library', 'libraryDetail')],
        ['Pricing', $lbHash('pricing'), false],
        ['Blog', route('blog'), request()->routeIs('blog', 'blog-detail')],
        ['Contact', route('contact-us'), request()->routeIs('contact-us')],
    ];
@endphp
<header class="lb-nav">
    <nav class="lb-nav-bar" aria-label="Main">
        @include('lb.partials.logo')
        <div class="lb-nav-links">
            @foreach($lbNav as [$label, $href, $active])
                <a href="{{ $href }}" @if($active) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </div>
        <div class="lb-nav-right">
            <a href="{{ route('login.library') }}" class="lb-nav-login">Log in</a>
            <a href="{{ route('register') }}" class="lb-btn lb-btn--sm">Get started</a>
            @include('lb.partials.theme-toggle')
            <button type="button" class="lb-nav-burger" aria-label="Menu" aria-expanded="false" aria-controls="lb-nav-menu">
                <x-lb-icon name="menu" class="lb-i-menu" /><x-lb-icon name="x" class="lb-i-x" />
            </button>
        </div>
    </nav>
    <div class="lb-nav-menu" id="lb-nav-menu">
        @foreach($lbNav as [$label, $href, $active])
            <a href="{{ $href }}">{{ $label }}</a>
        @endforeach
        <a href="{{ route('login.learner') }}">Learner login</a>
        <div class="lb-nav-menu-cta">
            <a href="{{ route('login.library') }}" class="lb-btn lb-btn--ghost">Log in</a>
            <a href="{{ route('register') }}" class="lb-btn">Get started</a>
        </div>
    </div>
</header>
