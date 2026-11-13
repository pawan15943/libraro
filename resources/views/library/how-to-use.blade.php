@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    // "Operation : Book Seat" → "Book Seat" (older rows still carry a prefix)
    $htTitle = fn ($t) => trim(preg_replace('/^\s*(operation|masters|utility|premium operation|analytics)\s*:\s*/i', '', (string) $t));
    // One step per line → a numbered list; a single paragraph stays a paragraph
    $htSteps = fn ($t) => array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $t)), fn ($l) => $l !== ''));
    $groups = $howtoUseContent->groupBy(fn ($r) => $r->category ?: 'other');
@endphp

<div class="lpg lpg--narrow lhu" id="lhu">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>How Libraro works</span></div>
            <h1><span data-lang="en">How Libraro works</span><span data-lang="hi" hidden>Libraro कैसे चलाएं</span></h1>
            <p class="lhu-sub"><span data-lang="en">Every feature of your panel, step by step.</span><span data-lang="hi" hidden>आपके पैनल का हर फ़ीचर, स्टेप-बाय-स्टेप।</span></p>
        </div>
        <div class="lpg-seg lhu-lang" role="group" aria-label="Language">
            <button type="button" class="lpg-seg-b is-on" data-lang-btn="en" aria-pressed="true">English</button>
            <button type="button" class="lpg-seg-b" data-lang-btn="hi" aria-pressed="false">हिन्दी</button>
        </div>
    </header>

    @if($howtoUseContent->count())
        <label class="lpg-search lhu-search">
            <x-lb-icon name="search" />
            <input type="search" id="lhuSearch" placeholder="Search — e.g. renew, swap, attendance, Limo" data-ph-en="Search — e.g. renew, swap, attendance, Limo" data-ph-hi="खोजें — जैसे रिन्यू, सीट बदलना, हाजिरी, Limo" autocomplete="off">
        </label>

        {{-- Topic chips: one swipeable row --}}
        <div class="lhu-cats" id="lhuCats" role="tablist" aria-label="Topics">
            <button type="button" class="lhu-cat is-on" data-cat="all" role="tab" aria-selected="true">
                <span data-lang="en">All</span><span data-lang="hi" hidden>सभी</span><em>{{ $howtoUseContent->count() }}</em>
            </button>
            @foreach($categories as $key => $c)
                @if($groups->has($key))
                    <button type="button" class="lhu-cat" data-cat="{{ $key }}" role="tab" aria-selected="false">
                        <span data-lang="en">{{ $c[0] }}</span><span data-lang="hi" hidden>{{ $c[1] }}</span><em>{{ $groups[$key]->count() }}</em>
                    </button>
                @endif
            @endforeach
        </div>

        <div class="lhu-list" id="lhuList">
            @php $no = 0; @endphp
            @foreach($categories + ['other' => ['More', 'और']] as $key => $c)
                @continue(! $groups->has($key))
                <section class="lhu-group" data-cat="{{ $key }}">
                    <h2 class="lhu-group-t"><span data-lang="en">{{ $c[0] }}</span><span data-lang="hi" hidden>{{ $c[1] }}</span></h2>
                    @foreach($groups[$key] as $row)
                        @php
                            $no++;
                            $title = $htTitle($row->operation_name);
                            $titleHi = $row->hindi_question ? trim(preg_replace('/^Libraro में\s*/u', '', $row->hindi_question)) : $title;
                            $en = $htSteps($row->usage_english);
                            $hi = $htSteps($row->usage_hindi ?: $row->usage_english);
                        @endphp
                        <details class="lhu-item" data-cat="{{ $key }}" data-q="{{ mb_strtolower($title.' '.$titleHi.' '.strip_tags($row->usage_english).' '.strip_tags($row->usage_hindi)) }}">
                            <summary>
                                <span class="lhu-no">{{ $no }}</span>
                                <b><span data-lang="en">{{ $title }}</span><span data-lang="hi" hidden>{{ $titleHi }}</span></b>
                                <x-lb-icon name="chevron-down" class="lhu-chev" />
                            </summary>
                            <div class="lhu-body">
                                @foreach(['en' => $en, 'hi' => $hi] as $lang => $steps)
                                    <div data-lang="{{ $lang }}" lang="{{ $lang }}" @if($lang === 'hi') hidden @endif>
                                        @if(count($steps) > 1)
                                            <ol class="lhu-steps">@foreach($steps as $s)<li>{{ $s }}</li>@endforeach</ol>
                                        @else
                                            <p>{{ $steps[0] ?? '' }}</p>
                                        @endif
                                    </div>
                                @endforeach
                                <button type="button" class="lhu-ask" data-q-en="How to {{ $title }}?" data-q-hi="{{ $titleHi }}">
                                    <x-lb-icon name="bot" /><span data-lang="en">Ask Limo about this</span><span data-lang="hi" hidden>इसके बारे में Limo से पूछें</span>
                                </button>
                            </div>
                        </details>
                    @endforeach
                </section>
            @endforeach
        </div>

        <div class="lpg-empty" id="lhuNone" hidden>
            <span><x-lb-icon name="search" /></span>
            <b><span data-lang="en">Nothing found</span><span data-lang="hi" hidden>कुछ नहीं मिला</span></b>
            <p><span data-lang="en">Try another word — or ask Limo.</span><span data-lang="hi" hidden>कोई और शब्द आज़माएं — या Limo से पूछें।</span></p>
        </div>
    @else
        <div class="lpg-empty">
            <span><x-lb-icon name="book-open" /></span>
            <b>No guides yet</b>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('lhu');
        if (!root) return;
        var items = root.querySelectorAll('.lhu-item');
        var groups = root.querySelectorAll('.lhu-group');
        var search = document.getElementById('lhuSearch');
        var cat = 'all';
        var lang = 'en';

        function setLang(l) {
            lang = l;
            root.querySelectorAll('[data-lang]').forEach(function (el) { el.hidden = el.dataset.lang !== l; });
            root.querySelectorAll('[data-lang-btn]').forEach(function (b) {
                var on = b.dataset.langBtn === l;
                b.classList.toggle('is-on', on);
                b.setAttribute('aria-pressed', String(on));
            });
            if (search) search.placeholder = search.dataset['ph' + (l === 'hi' ? 'Hi' : 'En')];
            try { localStorage.setItem('lhu-lang', l); } catch (e) {}
        }
        root.querySelectorAll('[data-lang-btn]').forEach(function (b) {
            b.addEventListener('click', function () { setLang(b.dataset.langBtn); });
        });
        try { if (localStorage.getItem('lhu-lang') === 'hi') setLang('hi'); } catch (e) {}

        // one answer open at a time
        items.forEach(function (d) {
            d.addEventListener('toggle', function () {
                if (d.open) items.forEach(function (o) { if (o !== d) o.open = false; });
            });
        });

        function filter() {
            var q = search ? search.value.trim().toLowerCase() : '', n = 0;
            items.forEach(function (d) {
                var hit = (cat === 'all' || d.dataset.cat === cat) && (!q || d.dataset.q.indexOf(q) !== -1);
                d.hidden = !hit;
                if (hit) n++;
            });
            groups.forEach(function (g) { g.hidden = !g.querySelector('.lhu-item:not([hidden])'); });
            document.getElementById('lhuNone').hidden = n > 0;
        }
        if (search) search.addEventListener('input', filter);

        root.querySelectorAll('.lhu-cat').forEach(function (b) {
            b.addEventListener('click', function () {
                cat = b.dataset.cat;
                root.querySelectorAll('.lhu-cat').forEach(function (x) {
                    x.classList.toggle('is-on', x === b);
                    x.setAttribute('aria-selected', x === b ? 'true' : 'false');
                });
                filter();
            });
        });

        // "Ask Limo" opens the assistant with this question
        root.querySelectorAll('.lhu-ask').forEach(function (b) {
            b.addEventListener('click', function () {
                if (typeof window.sendAiQuery === 'function') window.sendAiQuery(lang === 'hi' ? b.dataset.qHi : b.dataset.qEn);
            });
        });

        // deep link: how-to-use#renew opens the first match
        if (location.hash.length > 1 && search) {
            search.value = decodeURIComponent(location.hash.slice(1)).replace(/-/g, ' ');
            filter();
            var first = root.querySelector('.lhu-item:not([hidden])');
            if (first) first.open = true;
        }
    });
</script>

@endsection
