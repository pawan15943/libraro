@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    // "Operation : Book Seat" → "Book Seat"
    $htTitle = fn ($t) => trim(preg_replace('/^\s*operation\s*:\s*/i', '', (string) $t));
@endphp

<div class="lpg lpg--narrow lhu" id="lhu">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>How Libraro works</span></div>
            <h1>How Libraro works</h1>
        </div>
        <div class="lpg-seg lhu-lang" role="group" aria-label="Language">
            <button type="button" class="lpg-seg-b is-on" data-lang="en" aria-pressed="true">English</button>
            <button type="button" class="lpg-seg-b" data-lang="hi" aria-pressed="false">हिन्दी</button>
        </div>
    </header>

    @if($howtoUseContent->count())
        <label class="lpg-search lhu-search">
            <x-lb-icon name="search" />
            <input type="search" id="lhuSearch" placeholder="Search — e.g. renew, swap, attendance" autocomplete="off">
        </label>

        <div class="lhu-list" id="lhuList">
            @foreach($howtoUseContent as $i => $row)
                @php $title = $htTitle($row->operation_name); @endphp
                <details class="lhu-item" data-q="{{ strtolower($title . ' ' . strip_tags($row->usage_english) . ' ' . strip_tags($row->usage_hindi)) }}">
                    <summary>
                        <span class="lhu-no">{{ $loop->iteration }}</span>
                        <b>{{ $title }}</b>
                        <x-lb-icon name="chevron-down" class="lhu-chev" />
                    </summary>
                    <div class="lhu-body">
                        <p lang="en" data-lang="en">{!! nl2br(e(trim($row->usage_english))) !!}</p>
                        <p lang="hi" data-lang="hi" hidden>{!! nl2br(e(trim($row->usage_hindi ?: $row->usage_english))) !!}</p>
                    </div>
                </details>
            @endforeach
        </div>

        <div class="lpg-empty" id="lhuNone" hidden>
            <span><x-lb-icon name="search" /></span>
            <b>Nothing found</b>
            <p>Try another word.</p>
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

        function setLang(lang) {
            root.querySelectorAll('[data-lang]').forEach(function (el) {
                if (el.tagName === 'BUTTON') {
                    var on = el.dataset.lang === lang;
                    el.classList.toggle('is-on', on);
                    el.setAttribute('aria-pressed', String(on));
                } else {
                    el.hidden = el.dataset.lang !== lang;
                }
            });
            try { localStorage.setItem('lhu-lang', lang); } catch (e) {}
        }
        root.querySelectorAll('.lhu-lang button').forEach(function (b) {
            b.addEventListener('click', function () { setLang(b.dataset.lang); });
        });
        try { if (localStorage.getItem('lhu-lang') === 'hi') setLang('hi'); } catch (e) {}

        // one answer open at a time
        items.forEach(function (d) {
            d.addEventListener('toggle', function () {
                if (d.open) items.forEach(function (o) { if (o !== d) o.open = false; });
            });
        });

        var search = document.getElementById('lhuSearch');
        if (search) search.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase(), n = 0;
            items.forEach(function (d) {
                var hit = !q || d.dataset.q.indexOf(q) !== -1;
                d.hidden = !hit;
                if (hit) n++;
            });
            document.getElementById('lhuNone').hidden = n > 0;
        });
    });
</script>

@endsection
