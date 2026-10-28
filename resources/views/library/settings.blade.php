@extends('layouts.library')

@section('title', 'Library Settings')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    $stFavicon = '';
    if (!empty($library->library_favicon)) {
        if (file_exists(public_path('storage/' . $library->library_favicon))) {
            $stFavicon = asset('storage/' . $library->library_favicon);
        } elseif (file_exists(storage_path('app/public/' . $library->library_favicon))) {
            $stFavicon = asset('storage/app/public/' . $library->library_favicon);
        } else {
            $stFavicon = asset('storage/' . $library->library_favicon);
        }
    }
    $stColor = old('library_primary_color', $library->library_primary_color ?? '#18225f');
    $stTitle = old('library_title', $library->library_title ?? '');
    $stDesc = old('library_meta_description', $library->library_meta_description ?? '');
    $stLang = old('library_language', $library->library_language ?? 'English');
    $stName = $library->library_name ?? 'Your library';
    $stPresets = ['#18225f' => 'Navy', '#1e1b4b' => 'Indigo', '#34939f' => 'Teal', '#065f46' => 'Emerald', '#991b1b' => 'Crimson', '#9a3412' => 'Rust', '#6b21a8' => 'Plum', '#16171b' => 'Graphite'];
@endphp

<div class="lpg">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Settings</span></div>
            <h1>Library settings</h1>
            <p>How your library looks on its public page and in Google search.</p>
        </div>
    </header>

    <form action="{{ route('library.settings.store') }}" method="POST" enctype="multipart/form-data" id="librarySettingsForm" style="--prim: {{ $stColor }}">
        @csrf
        <div class="lst2-layout">
            <div>
                <section class="lef-sec">
                    <div class="lef-sec-h">
                        <span class="lef-sec-ic is-violet"><x-lb-icon name="sparkles" /></span>
                        <div><h4>Branding</h4><p>Icon and colour used on your public page.</p></div>
                    </div>

                    <div class="lef-f {{ $errors->has('library_favicon') ? 'is-bad' : '' }}">
                        <label>Favicon <span>64 × 64 px · PNG, JPG or ICO · up to 2 MB</span></label>
                        @if($stFavicon)
                            <div class="lst2-now"><img src="{{ $stFavicon }}" alt="" onerror="this.style.display='none'"><span><b>Current icon</b> · live on your public page</span></div>
                        @endif
                        <div class="lpg-drop" id="faviconDropzone" role="button" tabindex="0" aria-label="Choose favicon">
                            <input type="file" name="library_favicon" id="library_favicon" accept=".jpg,.jpeg,.png,.ico">
                            <span class="lpg-drop-ic" id="favIc"><x-lb-icon name="upload" /></span>
                            <span class="lpg-drop-t">
                                <b id="favName">Drop a new icon here, or <u>browse</u></b>
                                <small id="favMeta">It shows in the browser tab next to your title</small>
                            </span>
                            <button type="button" class="lpg-round is-bad" id="removeFaviconBtn" hidden title="Remove" aria-label="Remove file"><x-lb-icon name="trash" /></button>
                        </div>
                        @error('library_favicon')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="lef-f {{ $errors->has('library_primary_color') ? 'is-bad' : '' }}">
                        <label for="libraryColorHex">Primary colour <i>*</i></label>
                        <div class="lst2-color">
                            <input type="color" id="libraryColorPicker" name="library_primary_color" value="{{ $stColor }}" aria-label="Pick colour" required>
                            <div class="lef-in"><x-lb-icon name="hash" /><input type="text" id="libraryColorHex" value="{{ strtoupper($stColor) }}" maxlength="7" placeholder="#18225F" autocomplete="off" spellcheck="false"></div>
                        </div>
                        <div class="lst2-swatches" role="group" aria-label="Preset colours">
                            @foreach($stPresets as $hex => $label)
                                <button type="button" class="lst2-sw" style="background: {{ $hex }}; color: {{ $hex }}" data-color="{{ $hex }}" title="{{ $label }}" aria-label="{{ $label }}"></button>
                            @endforeach
                        </div>
                        @error('library_primary_color')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="lef-sec">
                    <div class="lef-sec-h">
                        <span class="lef-sec-ic is-blue"><x-lb-icon name="search" /></span>
                        <div><h4>Search listing</h4><p>What people see when they find you on Google.</p></div>
                    </div>

                    <div class="lef-f {{ $errors->has('library_title') ? 'is-bad' : '' }}">
                        <label for="libraryTitleInput">Page title <i>*</i><span class="lpg-count" data-for="libraryTitleInput" data-max="60"></span></label>
                        <div class="lef-in"><x-lb-icon name="file-text" /><input type="text" name="library_title" id="libraryTitleInput" value="{{ $stTitle }}" placeholder="e.g. Apex Study Library & Reading Hall" required></div>
                        @error('library_title')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint">Shown in the browser tab and as the blue link on Google. Aim for under 60 characters.</p>@enderror
                    </div>

                    <div class="lef-f {{ $errors->has('library_meta_description') ? 'is-bad' : '' }}">
                        <label for="libraryMetaDescInput">Description <i>*</i><span class="lpg-count" data-for="libraryMetaDescInput" data-max="160"></span></label>
                        <div class="lef-in is-area"><textarea name="library_meta_description" id="libraryMetaDescInput" rows="3" placeholder="Air-conditioned reading hall with Wi-Fi, lockers and 24×7 access near…" required>{{ $stDesc }}</textarea></div>
                        @error('library_meta_description')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint">One or two sentences about your facilities. 150–160 characters works best.</p>@enderror
                    </div>
                </section>

                <section class="lef-sec">
                    <div class="lef-sec-h">
                        <span class="lef-sec-ic is-green"><x-lb-icon name="message-square-text" /></span>
                        <div><h4>Language</h4><p>Used on your public page.</p></div>
                    </div>
                    <div class="lpg-radio">
                        <label><input type="radio" name="library_language" value="English" @checked($stLang !== 'Hindi')><span>English</span></label>
                        <label><input type="radio" name="library_language" value="Hindi" @checked($stLang === 'Hindi')><span>हिन्दी <small style="font-size:11.5px;color:var(--lp-mut2);font-weight:600">· coming soon</small></span></label>
                    </div>
                    @error('library_language')<p class="lef-err">{{ $message }}</p>@enderror
                </section>
            </div>

            <aside class="lst2-side">
                <section class="lpg-card lst2-prev" aria-label="Live preview">
                    <span class="lst2-cap">Live preview</span>
                    <div>
                        <div class="lst2-tab"><div class="lst2-tab-in">@if($stFavicon)<img src="{{ $stFavicon }}" alt="" data-fav>@else<i data-fav-ph></i>@endif<span data-pv-title>{{ $stTitle ?: $stName }}</span></div></div>
                        <div class="lst2-site">
                            <div class="lst2-site-bar">{{ $stName }}</div>
                            <span class="lst2-site-btn">Book a seat</span>
                        </div>
                    </div>
                    <span class="lst2-cap">On Google</span>
                    <div class="lst2-serp">
                        <div class="lst2-serp-u">@if($stFavicon)<img src="{{ $stFavicon }}" alt="" data-fav>@else<i data-fav-ph></i>@endif<span>{{ parse_url(url('/'), PHP_URL_HOST) }} › {{ \Illuminate\Support\Str::slug($stName) }}</span></div>
                        <div class="lst2-serp-t" data-pv-title>{{ $stTitle ?: $stName }}</div>
                        <div class="lst2-serp-d" data-pv-desc>{{ $stDesc ?: 'Add a short description so people know what makes your library worth visiting.' }}</div>
                    </div>
                </section>
            </aside>
        </div>

        <div class="lpg-save">
            <p id="stState">Changes apply to your public page as soon as you save.</p>
            <button type="submit" class="lp-btn"><x-lb-icon name="check" />Save settings</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('librarySettingsForm');
        var title = document.getElementById('libraryTitleInput');
        var desc = document.getElementById('libraryMetaDescInput');
        var picker = document.getElementById('libraryColorPicker');
        var hex = document.getElementById('libraryColorHex');
        var name = @json($stName);
        var state = document.getElementById('stState');

        function dirty() { state.textContent = 'You have unsaved changes.'; }

        // counters + preview text
        function counters() {
            form.querySelectorAll('.lpg-count').forEach(function (c) {
                var el = document.getElementById(c.dataset.for), max = +c.dataset.max, n = el.value.length;
                c.textContent = n + ' / ' + max;
                c.classList.toggle('is-warn', n > max * .9 && n <= max);
                c.classList.toggle('is-bad', n > max);
            });
            form.querySelectorAll('[data-pv-title]').forEach(function (t) { t.textContent = title.value.trim() || name; });
            form.querySelector('[data-pv-desc]').textContent = desc.value.trim() || 'Add a short description so people know what makes your library worth visiting.';
        }
        [title, desc].forEach(function (el) { el.addEventListener('input', function () { counters(); dirty(); }); });
        counters();

        // colour
        function setColor(v, fromHex) {
            if (!v) return;
            if (v.charAt(0) !== '#') v = '#' + v;
            if (!/^#[0-9a-f]{6}$/i.test(v)) return;
            picker.value = v.toLowerCase();
            if (!fromHex) hex.value = v.toUpperCase();
            form.style.setProperty('--prim', v);
            form.querySelectorAll('.lst2-sw').forEach(function (b) { b.classList.toggle('is-on', b.dataset.color.toLowerCase() === v.toLowerCase()); });
        }
        picker.addEventListener('input', function () { setColor(this.value); dirty(); });
        hex.addEventListener('input', function () { setColor(this.value, true); dirty(); });
        hex.addEventListener('blur', function () { hex.value = picker.value.toUpperCase(); });
        form.querySelectorAll('.lst2-sw').forEach(function (b) { b.addEventListener('click', function () { setColor(b.dataset.color); dirty(); }); });
        setColor(picker.value);

        // favicon drop
        var drop = document.getElementById('faviconDropzone');
        var file = document.getElementById('library_favicon');
        var ic = document.getElementById('favIc');
        var nm = document.getElementById('favName');
        var meta = document.getElementById('favMeta');
        var rm = document.getElementById('removeFaviconBtn');
        var idle = { ic: ic.innerHTML, nm: nm.innerHTML, meta: meta.textContent };
        var favEls = form.querySelectorAll('[data-fav]');
        var favOrig = Array.prototype.map.call(favEls, function (i) { return i.src; });

        function size(b) { return b < 1024 ? b + ' B' : b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(2) + ' MB'; }
        function show(f) {
            if (!f) {
                drop.classList.remove('has-file');
                ic.innerHTML = idle.ic; nm.innerHTML = idle.nm; meta.textContent = idle.meta; rm.hidden = true;
                favEls.forEach(function (i, k) { i.src = favOrig[k]; });
                return;
            }
            var url = URL.createObjectURL(f);
            drop.classList.add('has-file');
            ic.innerHTML = '<img src="' + url + '" alt="">';
            nm.textContent = f.name;
            meta.textContent = size(f.size) + (f.size > 2097152 ? ' · too large, max 2 MB' : ' · ready to upload');
            rm.hidden = false;
            favEls.forEach(function (i) { i.src = url; });
            form.querySelectorAll('[data-fav-ph]').forEach(function (p) {
                var img = document.createElement('img'); img.src = url; img.alt = ''; img.setAttribute('data-fav', ''); p.replaceWith(img);
            });
            favEls = form.querySelectorAll('[data-fav]');
            dirty();
        }
        drop.addEventListener('click', function (e) { if (!e.target.closest('#removeFaviconBtn') && !drop.classList.contains('has-file')) file.click(); });
        drop.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); file.click(); } });
        file.addEventListener('change', function () { show(this.files[0]); });
        ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-over'); }); });
        drop.addEventListener('drop', function (e) {
            var f = e.dataTransfer && e.dataTransfer.files[0];
            if (!f) return;
            try { var dt = new DataTransfer(); dt.items.add(f); file.files = dt.files; } catch (err) {}
            show(f);
        });
        rm.addEventListener('click', function (e) { e.stopPropagation(); file.value = ''; show(null); });

        form.querySelectorAll('input[name="library_language"]').forEach(function (r) { r.addEventListener('change', dirty); });
        form.addEventListener('submit', function () { form.querySelector('.lpg-save .lp-btn').classList.add('is-busy'); });
    });
</script>

@include('library.script')
@endsection
