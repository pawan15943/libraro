{{--
    Promotional banner carousel.
    @include('lb.partials.promo-banners', ['variant' => 'wide'])  → full-width carousel (Find my library)
    @include('lb.partials.promo-banners', ['variant' => 'side'])  → small carousel (library details sidebar)

    To change the banners, edit $lbPromos below. Each banner:
      theme  : teal | peach | lavender | navy | mint     (background colour)
      eyebrow, title, text, cta : texts (keep them short)
      href   : link (route or URL). Use '#qc' to open the "Talk to us" panel.
      icon   : any icon name from public/img/lb-icons.svg
      image  : (optional) path under public/, e.g. 'img/promo/diwali.webp' — shown on the right / as background
      side   : true = also show in the details-page sidebar
--}}
@php
    $t = $t ?? fn ($s, $r = []) => strtr($s, $r);
    $lbPromoVariant = $variant ?? 'wide';
    $lbPromos = [
        ['theme' => 'teal', 'eyebrow' => $t('For students'), 'title' => $t('Book your study seat in one minute'), 'text' => $t('See free seats live, pick your shift and pay online or at the library.'), 'cta' => $t('Find a seat'), 'href' => route('find-my-library') . '#directory-explore', 'icon' => 'armchair', 'side' => true],
        ['theme' => 'peach', 'eyebrow' => $t('For library owners'), 'title' => $t('List your library free on Libraro'), 'text' => $t('Get found by students nearby and manage seats, fees and attendance in one app.'), 'cta' => $t('List my library'), 'href' => route('register'), 'icon' => 'library-big', 'side' => true],
        ['theme' => 'lavender', 'eyebrow' => $t('Study smarter'), 'title' => $t('Tips to study better, every week'), 'text' => $t('Read guides on focus, routines and exam preparation on the Libraro blog.'), 'cta' => $t('Read the blog'), 'href' => route('blog'), 'icon' => 'book-open', 'side' => false],
        ['theme' => 'navy', 'eyebrow' => $t('Need help?'), 'title' => $t('Not sure which library to choose?'), 'text' => $t('Tell us your area, budget and shift. Our team will suggest the right library.'), 'cta' => $t('Talk to us'), 'href' => '#qc', 'icon' => 'headset', 'side' => true],
    ];
    if ($lbPromoVariant === 'side') {
        $lbPromos = array_values(array_filter($lbPromos, fn ($p) => !empty($p['side'])));
    }
@endphp

@if(count($lbPromos))
    <div class="pr pr--{{ $lbPromoVariant }}" data-lb-promo aria-roledescription="carousel" aria-label="{{ $t('Offers and updates') }}">
        <div class="pr-track" data-pr-track>
            @foreach($lbPromos as $i => $p)
                @php
                    $isQc = ($p['href'] ?? '') === '#qc';
                    $img = !empty($p['image']) ? asset('public/' . ltrim($p['image'], '/')) : null;
                @endphp
                <article class="pr-slide pr-slide--{{ $p['theme'] }}" aria-roledescription="slide" aria-label="{{ $t(':a of :b', [':a' => $i + 1, ':b' => count($lbPromos)]) }}">
                    <div class="pr-copy">
                        <span class="pr-eyebrow">{{ $p['eyebrow'] }}</span>
                        <h3>{{ $p['title'] }}</h3>
                        @if($lbPromoVariant === 'wide')<p>{{ $p['text'] }}</p>@endif
                        @if($isQc)
                            <button type="button" class="pr-cta" data-lb-qc-open>{{ $p['cta'] }}<x-lb-icon name="arrow-right" /></button>
                        @else
                            <a class="pr-cta" href="{{ $p['href'] }}">{{ $p['cta'] }}<x-lb-icon name="arrow-right" /></a>
                        @endif
                    </div>
                    <div class="pr-art" aria-hidden="true">
                        @if($img)
                            <img src="{{ $img }}" alt="" loading="lazy" decoding="async">
                        @else
                            <span class="pr-orb pr-orb--1"></span><span class="pr-orb pr-orb--2"></span>
                            <span class="pr-icon"><x-lb-icon :name="$p['icon']" /></span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        @if(count($lbPromos) > 1)
            <div class="pr-ctrl">
                <button type="button" class="pr-nav" data-pr="-1" aria-label="{{ $t('Previous') }}"><x-lb-icon name="chevron-left" /></button>
                <div class="pr-dots" data-pr-dots>
                    @foreach($lbPromos as $i => $p)<button type="button" data-pr-go="{{ $i }}" aria-label="{{ $t('Show banner :n', [':n' => $i + 1]) }}" @if($i === 0) aria-current="true" @endif></button>@endforeach
                </div>
                <button type="button" class="pr-nav" data-pr="1" aria-label="{{ $t('Next') }}"><x-lb-icon name="chevron-right" /></button>
            </div>
        @endif
    </div>
@endif
