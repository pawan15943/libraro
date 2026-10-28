@extends('layouts.library')

@section('title', 'Library Feedback')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    $fbRating = (int) old('rating', 5);
    $fbWords = [1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very good', 5 => 'Excellent'];
@endphp

<div class="lpg lpg--narrow">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Feedback</span></div>
            <h1>Share feedback</h1>
            <p>Tell us what’s working and what isn’t. Every message is read by the Libraro team.</p>
        </div>
    </header>

    @if($is_feedback)
        <section class="lpg-card lfb-done">
            <span class="lfb-done-ic"><x-lb-icon name="party-popper" /></span>
            <b>Thanks — we got your feedback</b>
            <p>Your feedback for this library has been recorded. We use it to decide what to build and fix next.</p>
            <a href="{{ route('library.home') }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="arrow-left" />Back to dashboard</a>
        </section>
    @else
        <form action="{{ route('library.feedback.store') }}" method="POST" enctype="multipart/form-data" id="libraryFeedbackForm">
            @csrf

            <section class="lef-sec">
                <div class="lef-f {{ $errors->has('rating') ? 'is-bad' : '' }}">
                    <label>How would you rate Libraro? <i>*</i></label>
                    <input type="hidden" name="rating" id="ratingValueInput" value="{{ $fbRating }}">
                    <div class="lfb-stars" role="radiogroup" aria-label="Rating">
                        @for($s = 1; $s <= 5; $s++)
                            <button type="button" class="lfb-star {{ $s <= $fbRating ? 'is-on' : '' }}" data-val="{{ $s }}" role="radio" aria-checked="{{ $s === $fbRating ? 'true' : 'false' }}" aria-label="{{ $s }} star{{ $s > 1 ? 's' : '' }} — {{ $fbWords[$s] }}"><x-lb-icon name="star" /></button>
                        @endfor
                        <span class="lfb-rate-t" id="ratingTextHint">{{ $fbWords[$fbRating] ?? '' }}</span>
                    </div>
                    @error('rating')<p class="lef-err">{{ $message }}</p>@enderror
                </div>

                <div class="lef-f {{ $errors->has('feedback_feature_id') ? 'is-bad' : '' }}">
                    <label>Which part is this about? <i>*</i></label>
                    <div class="lpg-radio">
                        @foreach($feedback_features as $feature)
                            <label><input type="radio" name="feedback_feature_id" value="{{ $feature->id }}" @checked(old('feedback_feature_id') == $feature->id) required><span>{{ $feature->name }}</span></label>
                        @endforeach
                    </div>
                    @error('feedback_feature_id')<p class="lef-err">{{ $message }}</p>@enderror
                </div>

                <div class="lef-f {{ $errors->has('description') ? 'is-bad' : '' }}">
                    <label for="feedbackDescriptionText">Your feedback <i>*</i></label>
                    <div class="lef-in is-area"><textarea name="description" id="feedbackDescriptionText" rows="4" placeholder="What do you like? What should we improve or add?" required>{{ old('description') }}</textarea></div>
                    @error('description')<p class="lef-err">{{ $message }}</p>@enderror
                </div>

                <div class="lef-f {{ $errors->has('recommend') ? 'is-bad' : '' }}">
                    <label>Would you recommend Libraro to another library owner? <i>*</i></label>
                    <div class="lpg-radio is-seg" style="max-width:320px">
                        <label><input type="radio" name="recommend" value="Yes" @checked(old('recommend', 'Yes') == 'Yes')><span><x-lb-icon name="thumbs-up" />Yes</span></label>
                        <label><input type="radio" name="recommend" value="No" @checked(old('recommend') == 'No')><span><x-lb-icon name="thumbs-up" style="transform:scaleY(-1)" />Not yet</span></label>
                    </div>
                    @error('recommend')<p class="lef-err">{{ $message }}</p>@enderror
                </div>

                <div class="lef-f {{ $errors->has('attachment') ? 'is-bad' : '' }}">
                    <label>Screenshot <span>Optional · JPG, PNG or PDF · up to 2 MB</span></label>
                    <div class="lpg-drop" id="feedbackDropzone" role="button" tabindex="0" aria-label="Attach a screenshot">
                        <input type="file" name="attachment" id="attachmentInput" accept=".jpg,.jpeg,.png,.pdf">
                        <span class="lpg-drop-ic" id="atIc"><x-lb-icon name="image" /></span>
                        <span class="lpg-drop-t">
                            <b id="atName">Add a screenshot, or <u>browse</u></b>
                            <small id="atMeta">Helps us see exactly what you mean</small>
                        </span>
                        <button type="button" class="lpg-round is-bad" id="removeAttachmentBtn" hidden title="Remove" aria-label="Remove attachment"><x-lb-icon name="trash" /></button>
                    </div>
                    @error('attachment')<p class="lef-err">{{ $message }}</p>@enderror
                </div>
            </section>

            <div class="lpg-save">
                <p>Your library name is sent with the feedback.</p>
                <button type="submit" class="lp-btn"><x-lb-icon name="send" />Send feedback</button>
            </div>
        </form>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('libraryFeedbackForm');
        if (!form) return;

        var words = @json($fbWords);
        var input = document.getElementById('ratingValueInput');
        var hint = document.getElementById('ratingTextHint');
        var stars = form.querySelectorAll('.lfb-star');
        function setStars(v) {
            input.value = v;
            hint.textContent = words[v] || '';
            stars.forEach(function (s) {
                var n = +s.dataset.val;
                s.classList.toggle('is-on', n <= v);
                s.setAttribute('aria-checked', n === v ? 'true' : 'false');
            });
        }
        stars.forEach(function (s) {
            s.addEventListener('click', function () { setStars(+s.dataset.val); });
        });

        var drop = document.getElementById('feedbackDropzone');
        var file = document.getElementById('attachmentInput');
        var ic = document.getElementById('atIc');
        var nm = document.getElementById('atName');
        var meta = document.getElementById('atMeta');
        var rm = document.getElementById('removeAttachmentBtn');
        var idle = { ic: ic.innerHTML, nm: nm.innerHTML, meta: meta.textContent };
        function size(b) { return b < 1024 ? b + ' B' : b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(2) + ' MB'; }
        function show(f) {
            if (!f) {
                drop.classList.remove('has-file');
                ic.innerHTML = idle.ic; nm.innerHTML = idle.nm; meta.textContent = idle.meta; rm.hidden = true;
                return;
            }
            drop.classList.add('has-file');
            if (f.type && f.type.indexOf('image/') === 0) {
                ic.innerHTML = '<img src="' + URL.createObjectURL(f) + '" alt="">';
            }
            nm.textContent = f.name;
            meta.textContent = size(f.size) + (f.size > 2097152 ? ' · too large, max 2 MB' : '');
            rm.hidden = false;
        }
        drop.addEventListener('click', function (e) { if (!e.target.closest('#removeAttachmentBtn') && !drop.classList.contains('has-file')) file.click(); });
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

        form.addEventListener('submit', function () { form.querySelector('.lpg-save .lp-btn').classList.add('is-busy'); });
    });
</script>

@include('library.script')
@endsection
