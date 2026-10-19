{{--
    Shared layout for legal pages (Terms, Privacy, Refund).
    Vars: $active ('terms'|'privacy'|'refund'), $heading, $lead, $updated (optional),
          $glance  = [[icon, title, text], ...]   — plain-language summary (not a replacement for the text)
          $sections = [[title, html], ...]       — the policy text itself (trusted, hand-written HTML)
          $intro (optional html), $outro (optional html)
--}}
@php
    $lbLegalTabs = [
        'terms'   => ['Terms & Conditions', route('term-and-condition'), 'file-text'],
        'privacy' => ['Privacy Policy', route('privacy-policy'), 'shield-check'],
        'refund'  => ['Refund Policy', route('refund-policy'), 'rotate-ccw'],
    ];
    $lbSlug = fn ($t, $i) => 's' . ($i + 1) . '-' . \Illuminate\Support\Str::slug($t);
@endphp

<header class="lb-legal-hero">
    <nav class="lb-crumbs-c" aria-label="Breadcrumb">
        <a href="{{ route('/') }}">Home</a><x-lb-icon name="chevron-right" />
        <span>Legal</span><x-lb-icon name="chevron-right" />
        <span aria-current="page">{{ $heading }}</span>
    </nav>
    <span class="lb-legal-badge"><x-lb-icon :name="$lbLegalTabs[$active][2]" /></span>
    <h1 data-reveal>{{ $heading }}</h1>
    @if(!empty($lead))<p class="lb-lead">{{ $lead }}</p>@endif
    <div class="lb-legal-meta">
        @if(!empty($updated))<span><x-lb-icon name="calendar" />{{ $updated }}</span>@endif
        <span><x-lb-icon name="clock-3" />{{ max(1, (int) ceil(str_word_count(strip_tags(collect($sections)->pluck(1)->implode(' ') . ($intro ?? '') . ($outro ?? ''))) / 200)) }} min read</span>
        <span><x-lb-icon name="map-pin" />Governed by the laws of India</span>
    </div>

    <nav class="lb-legal-tabs" aria-label="Legal documents">
        @foreach($lbLegalTabs as $key => [$label, $url, $icon])
            <a href="{{ $url }}" @if($key === $active) aria-current="page" @endif><x-lb-icon :name="$icon" />{{ $label }}</a>
        @endforeach
    </nav>
</header>

<div class="lb-legal">
    @if(!empty($glance))
        <section class="lb-legal-glance" aria-labelledby="lb-glance-title">
            <div class="lb-legal-glance-head">
                <h2 id="lb-glance-title">At a glance</h2>
                <p>A quick summary in plain words. The full text below is what applies.</p>
            </div>
            <ul>
                @foreach($glance as [$gi, $gt, $gx])
                    <li><i><x-lb-icon :name="$gi" /></i><div><b>{{ $gt }}</b><span>{{ $gx }}</span></div></li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="lb-legal-grid {{ count($sections) < 3 ? 'lb-legal-grid--single' : '' }}">
        @if(count($sections) >= 3)
            <aside class="lb-legal-toc" aria-label="On this page">
                <p>On this page</p>
                <ol data-lb-toc>
                    @foreach($sections as $i => [$st])
                        <li><a href="#{{ $lbSlug($st, $i) }}"><span>{{ $i + 1 }}</span>{{ $st }}</a></li>
                    @endforeach
                </ol>
            </aside>
        @endif

        <article class="lb-legal-doc">
            @if(!empty($intro))<div class="lb-legal-intro">{!! $intro !!}</div>@endif
            @foreach($sections as $i => [$st, $sh])
                <section class="lb-legal-sec" id="{{ $lbSlug($st, $i) }}" aria-labelledby="{{ $lbSlug($st, $i) }}-h">
                    <h2 id="{{ $lbSlug($st, $i) }}-h"><span class="lb-legal-num">{{ sprintf('%02d', $i + 1) }}</span>{{ $st }}</h2>
                    <div class="lb-legal-body">{!! $sh !!}</div>
                </section>
            @endforeach
            @if(!empty($outro))<p class="lb-legal-outro"><x-lb-icon name="circle-check" />{!! $outro !!}</p>@endif

            <section class="lb-legal-help" aria-labelledby="lb-legal-help-title">
                <div>
                    <h2 id="lb-legal-help-title">Questions about this policy?</h2>
                    <p>Talk to a real person. We usually reply the same working day.</p>
                </div>
                <div class="lb-legal-help-links">
                    <a href="mailto:support@libraro.in"><x-lb-icon name="mail" /><span><small>Email</small>support@libraro.in</span></a>
                    <a href="tel:+918114479678"><x-lb-icon name="phone" /><span><small>Call</small>+91 81144 79678</span></a>
                    <a href="https://wa.me/918114479678" target="_blank" rel="noopener"><x-lb-brand name="whatsapp" /><span><small>WhatsApp</small>Chat with us</span></a>
                </div>
                <p class="lb-legal-addr"><x-lb-icon name="building" />H.No. 955 Vinoba Bhave Nagar, Kota, Rajasthan 324005</p>
            </section>
        </article>
    </div>
</div>
