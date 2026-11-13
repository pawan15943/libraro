@extends('layouts.library')

@section('title', $bulk ? 'Print ID cards' : 'ID Card')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-idcard.css') }}?v={{ @filemtime(public_path('css/lb-idcard.css')) }}">
@endpush

@section('content')
@php
    $bulk = $bulk ?? false;
    $designs = [
        'classic' => ['Classic', 'Wide card, colour band on top', 'land'],
        'modern' => ['Modern', 'Tall card, round photo', 'port'],
        'minimal' => ['Minimal', 'Wide, white with a side stripe', 'land'],
    ];
    $colours = ['#0f766e' => 'Teal', '#1d4ed8' => 'Blue', '#1e293b' => 'Charcoal', '#9f1239' => 'Maroon', '#15803d' => 'Green', '#6d28d9' => 'Purple'];
    $photo = fn ($c, $cls) => $c['photo']
        ? '<img class="' . $cls . '" src="' . e($c['photo']) . '" alt="">'
        : '<span class="' . $cls . ' is-initials">' . e($c['initials']) . '</span>';
    $brand = fn ($c) => $c['logo']
        ? '<img class="ic-logo" src="' . e($c['logo']) . '" alt="">'
        : '<span class="ic-logo is-initials">' . e($c['library_initials']) . '</span>';
    // QR is filled in by the script (one copy per member, not one per design)
    $qr = fn ($i) => '<span class="ic-scan"><span class="ic-qr" data-qr="' . $i . '"></span><small class="ic-scan-t">Scan for attendance</small></span>';
@endphp

<div class="idp" id="idp">
    {{-- ===== controls ===== --}}
    <aside class="idp-panel" aria-label="Card options">
        <section class="idp-box">
            <h3>Design</h3>
            <div class="idp-designs" role="radiogroup" aria-label="Design">
                @foreach($designs as $key => [$label, $hint, $shape])
                <button type="button" class="idp-design" data-design="{{ $key }}" role="radio" aria-checked="false">
                    <span class="idp-thumb is-{{ $key }} is-{{ $shape }}"><i></i><i></i><i></i></span>
                    <span class="idp-design-t"><b>{{ $label }}</b><small>{{ $hint }}</small></span>
                </button>
                @endforeach
            </div>
        </section>

        <section class="idp-box">
            <h3>Colour</h3>
            <div class="idp-swatches" role="radiogroup" aria-label="Colour">
                @foreach($colours as $hex => $name)
                <button type="button" class="idp-swatch" data-colour="{{ $hex }}" style="--sw: {{ $hex }}" role="radio" aria-checked="false" title="{{ $name }}" aria-label="{{ $name }}"></button>
                @endforeach
            </div>
        </section>

        <section class="idp-box">
            <h3>On the A4 sheet</h3>
            @if($bulk)
            <div class="idp-row"><span>Members</span><b class="idp-count">{{ count($cards) }}</b></div>
            <div class="idp-row"><span>A4 sheets</span><b class="idp-count" id="idpSheets">1</b></div>
            @else
            <div class="idp-row">
                <span>Copies</span>
                <div class="idp-seg" role="radiogroup" aria-label="Copies">
                    @foreach([1, 2, 3] as $n)<button type="button" data-copies="{{ $n }}" role="radio" aria-checked="false">{{ $n }}</button>@endforeach
                </div>
            </div>
            @endif
            <label class="idp-switch"><input type="checkbox" id="idpBack" checked><span></span>Back side</label>
            <label class="idp-switch"><input type="checkbox" id="idpCut" checked><span></span>Cut lines</label>
            <p class="idp-note">Cards print at real ID-card size (85.6 × 54 mm). In the print window choose <span class="idp-k">A4</span>, scale <span class="idp-k">100%</span> and turn on <span class="idp-k">Background graphics</span>.</p>
        </section>

        <button type="button" class="lp-btn idp-print" id="idpPrint"><x-lb-icon name="printer" />Print / Save as PDF</button>
    </aside>

    {{-- ===== A4 preview ===== --}}
    <section class="idp-stage" aria-label="A4 preview">
        <div class="idp-fit" id="idpFit">
            {{-- the sheet lives in a shadow root, so page styles can't change the cards --}}
            <div id="idpSheetHost"></div>
        </div>
    </section>
</div>

{{-- card styles, put straight into the shadow root: an inline <style> applies at once, so the
     cards keep their look when the sheet is moved for printing (a <link> would reload first) --}}
<template id="idp-css"><style>{!! file_get_contents(public_path('css/lb-idcard-cards.css')) !!}</style></template>

{{-- ===== card designs (cloned into the sheet by the script below) ===== --}}
<template id="idp-qrs">@foreach($cards as $i => $c)<i data-qr="{{ $i }}">{!! $c['qr'] !!}</i>@endforeach</template>

@foreach(['classic', 'modern', 'minimal'] as $d)
<template id="idp-tpl-{{ $d }}">
    @foreach($cards as $i => $c)
    <div class="ic-pair">
    @if($d === 'classic')
        <article class="ic ic-classic is-land is-front">
            <header class="ic-band">
                {!! $brand($c) !!}
                <span class="ic-brand-t"><b>{{ $c['library'] }}</b><small>Student ID card</small></span>
            </header>
            <div class="ic-body">
                {!! $photo($c, 'ic-photo') !!}
                <div class="ic-main">
                    <h4 class="ic-name">{{ $c['name'] }}</h4>
                    <span class="ic-uid">UID {{ $c['uid'] }}</span>
                    <dl class="ic-facts">
                        <div><dt>Seat</dt><dd>{{ $c['seat'] }}</dd></div>
                        <div><dt>Shift</dt><dd>{{ $c['shift_time'] ?? $c['shift'] ?? '—' }}</dd></div>
                        <div><dt>Valid till</dt><dd>{{ $c['valid_till'] ?? '—' }}</dd></div>
                    </dl>
                </div>
                {!! $qr($i) !!}
            </div>
            <footer class="ic-foot"><span>{{ $c['mobile'] ?? '' }}</span><span>Valid {{ $c['valid_from'] ?? '' }} – {{ $c['valid_till'] ?? '' }}</span></footer>
        </article>
        <article class="ic ic-classic is-land is-back">
            <header class="ic-band is-thin">
                <span class="ic-brand-t"><b>{{ $c['library'] }}</b></span>
            </header>
            <div class="ic-back">
                <p class="ic-k">If found, please return to</p>
                <p class="ic-addr">{{ $c['address'] ?? $c['library'] }}</p>
                <p class="ic-contact">@if($c['phone'])<span>☎ {{ $c['phone'] }}</span>@endif @if($c['email'])<span>✉ {{ $c['email'] }}</span>@endif</p>
                <ul class="ic-rules">
                    <li>Carry this card in the library at all times.</li>
                    <li>Card is personal and not transferable.</li>
                    <li>Scan the QR on the front to mark attendance.</li>
                </ul>
                <div class="ic-sign"><span>Issued {{ $c['issued'] }}</span><span class="ic-sign-line">Authorised signatory</span></div>
            </div>
        </article>
    @elseif($d === 'modern')
        <article class="ic ic-modern is-port is-front">
            <header class="ic-top">
                {!! $brand($c) !!}
                <b class="ic-lib">{{ $c['library'] }}</b>
            </header>
            {!! $photo($c, 'ic-photo') !!}
            <h4 class="ic-name">{{ $c['name'] }}</h4>
            <span class="ic-uid">UID {{ $c['uid'] }}</span>
            <dl class="ic-grid">
                <div><dt>Seat</dt><dd>{{ $c['seat'] }}</dd></div>
                <div><dt>Valid till</dt><dd>{{ $c['valid_till'] ?? '—' }}</dd></div>
                <div class="is-wide"><dt>Shift</dt><dd>{{ $c['shift_time'] ?? $c['shift'] ?? '—' }}</dd></div>
            </dl>
            {!! $qr($i) !!}
            <footer class="ic-foot"><span><b>STUDENT ID</b><i class="ic-dot"></i>{{ $c['mobile'] ?? '' }}</span></footer>
        </article>
        <article class="ic ic-modern is-port is-back">
            <header class="ic-top is-thin">{!! $brand($c) !!}<b class="ic-lib">{{ $c['library'] }}</b></header>
            <div class="ic-back">
                <p class="ic-k">If found, please return to</p>
                <p class="ic-addr">{{ $c['address'] ?? $c['library'] }}</p>
                @if($c['phone'])<p class="ic-contact"><span>☎ {{ $c['phone'] }}</span></p>@endif
                @if($c['email'])<p class="ic-contact"><span>✉ {{ $c['email'] }}</span></p>@endif
                <ul class="ic-rules">
                    <li>Carry this card in the library.</li>
                    <li>Personal, not transferable.</li>
                    <li>Scan the QR to mark attendance.</li>
                </ul>
                <div class="ic-sign"><span class="ic-sign-line">Authorised signatory</span><span>Issued {{ $c['issued'] }}</span></div>
            </div>
            <footer class="ic-foot">Valid {{ $c['valid_from'] ?? '' }} – {{ $c['valid_till'] ?? '' }}</footer>
        </article>
    @else
        <article class="ic ic-minimal is-land is-front">
            <span class="ic-stripe"><i>MEMBER</i></span>
            <div class="ic-in">
                <div class="ic-head">{!! $brand($c) !!}<b class="ic-lib">{{ $c['library'] }}</b></div>
                {!! $qr($i) !!}
                <div class="ic-who">
                    {!! $photo($c, 'ic-photo') !!}
                    <div>
                        <h4 class="ic-name">{{ $c['name'] }}</h4>
                        <span class="ic-uid">{{ $c['uid'] }}</span>
                        <span class="ic-tag">Student ID card</span>
                    </div>
                </div>
                <dl class="ic-line">
                    <div><dt>Seat</dt><dd>{{ $c['seat'] }}</dd></div>
                    <div><dt>Shift</dt><dd>{{ $c['shift_time'] ?? $c['shift'] ?? '—' }}</dd></div>
                    <div><dt>Valid till</dt><dd>{{ $c['valid_till'] ?? '—' }}</dd></div>
                </dl>
            </div>
        </article>
        <article class="ic ic-minimal is-land is-back">
            <span class="ic-stripe"><i>{{ $c['library_initials'] }}</i></span>
            <div class="ic-in ic-back">
                <b class="ic-lib">{{ $c['library'] }}</b>
                <p class="ic-addr">{{ $c['address'] ?? '' }}</p>
                <p class="ic-contact">@if($c['phone'])<span>☎ {{ $c['phone'] }}</span>@endif @if($c['email'])<span>✉ {{ $c['email'] }}</span>@endif</p>
                <ul class="ic-rules">
                    <li>Carry this card in the library at all times.</li>
                    <li>Personal and not transferable. If found, please return.</li>
                </ul>
                <div class="ic-sign"><span>Issued {{ $c['issued'] }}</span><span class="ic-sign-line">Authorised signatory</span></div>
            </div>
        </article>
    @endif
    </div>
    @endforeach
</template>
@endforeach

<script>
(function () {
    var BULK = @json($bulk);
    var KEY = 'lb-idcard';
    var state = { design: 'classic', colour: '#0f766e', copies: 1, back: true, cut: true };
    try { Object.assign(state, JSON.parse(localStorage.getItem(KEY) || '{}')); } catch (e) {}
    @if($bulk) state.back = @json($backDefault ?? true); @endif
    var host = document.getElementById('idpSheetHost'), fit = document.getElementById('idpFit'), root = document.getElementById('idp');
    var shadow = host.attachShadow({ mode: 'open' });
    shadow.appendChild(document.getElementById('idp-css').content.cloneNode(true));
    var pages = document.createElement('div');
    pages.className = 'idp-pages';
    shadow.appendChild(pages);

    // each member's QR, drawn once and copied into whichever design is shown
    var qrs = {};
    Array.prototype.forEach.call(document.getElementById('idp-qrs').content.children, function (i) { qrs[i.dataset.qr] = i.innerHTML; });

    // members per A4 sheet (cards are real size: 85.6 x 54 mm, tall design 54 x 85.6 mm)
    function perSheet() {
        if (state.design === 'modern') return state.back ? 3 : 9;
        return state.back ? 4 : 8;
    }
    function save() { try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {} }
    function mark(sel, attr, val) {
        root.querySelectorAll(sel).forEach(function (b) {
            var on = String(b.getAttribute(attr)) === String(val);
            b.classList.toggle('is-on', on); b.setAttribute('aria-checked', on ? 'true' : 'false');
        });
    }
    function render() {
        var tpl = document.getElementById('idp-tpl-' + state.design) || document.getElementById('idp-tpl-classic');
        var pairs = Array.prototype.slice.call(tpl.content.children);
        if (!BULK) { var one = pairs[0]; pairs = []; for (var k = 0; k < state.copies; k++) pairs.push(one); }
        var n = perSheet(), cls = 'idp-sheet is-' + state.design + (state.back ? '' : ' no-back') + (state.cut ? ' has-cut' : '');
        pages.innerHTML = '';
        for (var i = 0; i < pairs.length; i += n) {
            var sheet = document.createElement('div');
            sheet.className = cls;
            sheet.style.setProperty('--ic', state.colour);
            pairs.slice(i, i + n).forEach(function (p) { sheet.appendChild(p.cloneNode(true)); });
            pages.appendChild(sheet);
        }
        pages.querySelectorAll('.ic-qr[data-qr]').forEach(function (q) { q.innerHTML = qrs[q.dataset.qr] || ''; });
        var count = document.getElementById('idpSheets');
        if (count) count.textContent = pages.children.length;
        mark('.idp-design', 'data-design', state.design);
        mark('.idp-swatch', 'data-colour', state.colour);
        mark('.idp-seg button', 'data-copies', state.copies);
        document.getElementById('idpBack').checked = !!state.back;
        document.getElementById('idpCut').checked = !!state.cut;
        scale();
        save();
    }
    // the sheets are real A4 (210 mm); shrink them to fit the screen
    function scale() {
        if (!pages.offsetWidth) return;
        var w = fit.parentElement.clientWidth, s = Math.min(1, w / pages.offsetWidth);
        fit.style.setProperty('--s', s);
        fit.style.height = (pages.offsetHeight * s) + 'px';
    }
    root.addEventListener('click', function (e) {
        var b = e.target.closest('[data-design],[data-colour],[data-copies]');
        if (!b) return;
        if (b.dataset.design) state.design = b.dataset.design;
        if (b.dataset.colour) state.colour = b.dataset.colour;
        if (b.dataset.copies) state.copies = parseInt(b.dataset.copies, 10);
        render();
    });
    document.getElementById('idpBack').addEventListener('change', function () { state.back = this.checked; render(); });
    document.getElementById('idpCut').addEventListener('change', function () { state.cut = this.checked; render(); });
    document.getElementById('idpPrint').addEventListener('click', function () { window.print(); });
    window.addEventListener('resize', scale);

    // Print only the sheets: move them straight under <body> (everything else is hidden by
    // lb-idcard.css), then put them back. Works for the button and for Ctrl/Cmd + P.
    var home = host.parentNode;
    window.addEventListener('beforeprint', function () {
        document.body.appendChild(host);
        document.body.classList.add('idp-printing');
    });
    window.addEventListener('afterprint', function () {
        home.appendChild(host);
        document.body.classList.remove('idp-printing');
        scale();
    });
    render();
})();
</script>
@endsection
