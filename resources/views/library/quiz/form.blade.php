@extends('layouts.library')

@section('title', $quiz ? 'Edit quiz' : 'New quiz')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-quiz.css') }}?v={{ @filemtime(public_path('css/lb-quiz.css')) }}">
@endpush

@section('content')
@php
    $max = \App\Models\Quiz::MAX_QUESTIONS;
    $maxSubjects = \App\Models\Quiz::MAX_SUBJECTS;
    $oldQ = old('q');
    $initial = $oldQ ? array_values($oldQ) : $questions->all();
    $initialSubjects = array_values(old('subjects', $subjects));
    // a new quiz starts with one subject so the owner can go straight to the questions
    if (!collect($initialSubjects)->filter(fn ($s) => trim((string) $s) !== '')->count()) {
        $initialSubjects[0] = 'General';
    }
    $initialSubjects = array_pad(array_slice($initialSubjects, 0, $maxSubjects), $maxSubjects, '');
    $moreOpen = old('exam_id', $quiz->exam_id ?? null) || old('description', $quiz->description ?? null);
@endphp

<div class="lpg lpg--narrow lqz lqz-form">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb">
                <a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" />
                <a href="{{ route('quiz.index') }}">Student quizzes</a><x-lb-icon name="chevron-right" />
                <span>{{ $quiz ? 'Edit' : 'New' }}</span>
            </div>
            <h1>{{ $quiz ? 'Edit quiz' : 'New quiz' }}</h1>
            <p>Type a question, fill the options and tap the letter of the right answer. That's it.</p>
        </div>
    </header>

    @if ($errors->any())
        <div class="lqz-alert" role="alert">
            <x-lb-icon name="circle-alert" />
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $quiz ? route('quiz.update', $quiz->id) : route('quiz.store') }}" id="quizForm" novalidate>
        @csrf
        @if ($quiz) @method('PUT') @endif

        {{-- 1. Details --}}
        <section class="lef-sec">
            <div class="lef-sec-h">
                <span class="lef-sec-ic is-blue"><x-lb-icon name="notebook-pen" /></span>
                <div><h4>Quiz details</h4><p>Students see the name and the time limit before they start.</p></div>
            </div>
            <div class="lef-grid">
                <div class="lef-f is-full">
                    <label for="qz_title">Quiz name <i>*</i></label>
                    <div class="lef-in"><x-lb-icon name="pen-line" /><input type="text" name="title" id="qz_title" value="{{ old('title', $quiz->title ?? '') }}" maxlength="150" placeholder="e.g. SSC GK practice test 1" required></div>
                </div>
                <div class="lef-f is-full">
                    <label>Time limit</label>
                    @php $dur = (int) old('duration_minutes', $quiz->duration_minutes ?? 0); @endphp
                    <div class="lqz-chips" role="radiogroup" aria-label="Time limit">
                        @foreach ([0 => 'No limit', 10 => '10 min', 20 => '20 min', 30 => '30 min', 60 => '1 hour'] as $m => $label)
                            <button type="button" class="lqz-chip {{ $dur === $m ? 'is-on' : '' }}" data-min="{{ $m }}" role="radio" aria-checked="{{ $dur === $m ? 'true' : 'false' }}">{{ $label }}</button>
                        @endforeach
                        <span class="lqz-chip lqz-chip--in {{ !in_array($dur, [0, 10, 20, 30, 60], true) ? 'is-on' : '' }}">
                            <input type="number" name="duration_minutes" id="qz_dur" min="0" max="600" inputmode="numeric" value="{{ $dur }}" aria-label="Minutes"><small>min</small>
                        </span>
                    </div>
                </div>
            </div>

            <details class="lqz-more" @if($moreOpen) open @endif>
                <summary><x-lb-icon name="chevron-right" />More options <span>Optional</span></summary>
                <div class="lef-grid">
                    <div class="lef-f">
                        <label for="qz_exam">For exam</label>
                        <div class="lef-in is-select"><x-lb-icon name="graduation-cap" />
                            <select name="exam_id" id="qz_exam">
                                <option value="">Any exam</option>
                                @foreach ($exams as $e)<option value="{{ $e->id }}" @selected(old('exam_id', $quiz->exam_id ?? '') == $e->id)>{{ $e->name }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="lef-f">
                        <label for="qz_desc">Note for students</label>
                        <div class="lef-in"><x-lb-icon name="message-square-text" /><input type="text" name="description" id="qz_desc" maxlength="1000" value="{{ old('description', $quiz->description ?? '') }}" placeholder="e.g. Based on last week's class"></div>
                    </div>
                </div>
            </details>
        </section>

        {{-- 2. Subjects --}}
        <section class="lef-sec">
            <div class="lef-sec-h">
                <span class="lef-sec-ic is-amber"><x-lb-icon name="book-open" /></span>
                <div><h4>Subjects</h4><p>One is enough. Add more (up to {{ $maxSubjects }}) to get a score per subject.</p></div>
            </div>
            <div class="lqz-subjects" id="subjectList">
                @for ($i = 0; $i < $maxSubjects; $i++)
                    <span class="lqz-subj" data-i="{{ $i }}" @if(trim((string) $initialSubjects[$i]) === '') hidden @endif>
                        <input type="text" name="subjects[{{ $i }}]" class="subj" data-i="{{ $i }}" maxlength="60" placeholder="Subject name" value="{{ $initialSubjects[$i] }}" aria-label="Subject {{ $i + 1 }}">
                        <button type="button" class="lqz-subj-x" title="Remove subject" aria-label="Remove subject"><x-lb-icon name="x" /></button>
                    </span>
                @endfor
                <button type="button" class="lqz-chip lqz-chip--add" id="addSubject"><x-lb-icon name="plus" />Subject</button>
            </div>
        </section>

        {{-- 3. Questions --}}
        <section class="lef-sec">
            <div class="lef-sec-h">
                <span class="lef-sec-ic is-green"><x-lb-icon name="list-checks" /></span>
                <div><h4>Questions <span class="lqz-count"><b id="qCount">0</b> / {{ $max }}</span></h4><p>Tap the letter next to the right answer. Options C and D are optional.</p></div>
            </div>

            <div id="qList" class="lqz-qlist"></div>

            <div class="lqz-addbar">
                <button type="button" class="lp-btn" id="addQ"><x-lb-icon name="plus" />Add question</button>
                <button type="button" class="lp-btn lp-btn--ghost" id="pasteToggle" aria-expanded="false" aria-controls="pasteBox"><x-lb-icon name="copy" />Paste many</button>
            </div>

            <div class="lqz-paste" id="pasteBox" hidden>
                <p><b>Paste questions from WhatsApp, Word or a PDF.</b> Write each one like this:</p>
<pre>1. Capital of India?
A) Mumbai
B) New Delhi
C) Kolkata
D) Chennai
Answer: B</pre>
                <textarea id="pasteText" rows="8" placeholder="Paste your questions here"></textarea>
                <p class="lqz-paste-msg" id="pasteMsg" role="status"></p>
                <div class="lqz-paste-act">
                    <button type="button" class="lp-btn lp-btn--ghost" id="pasteCancel">Close</button>
                    <button type="button" class="lp-btn" id="pasteAdd"><x-lb-icon name="plus" />Add these questions</button>
                </div>
            </div>
        </section>

        <div class="lpg-save lqz-save">
            <label class="lqz-pub">
                <input type="checkbox" name="is_published" value="1" id="pub" @checked(old('is_published', $quiz->is_published ?? true))>
                <span class="lqz-switch" aria-hidden="true"></span>
                <span><b>Show to students</b><small id="pubHint">{{ old('is_published', $quiz->is_published ?? true) ? 'Visible as soon as you save' : 'Saved as a draft' }}</small></span>
            </label>
            <a href="{{ route('quiz.index') }}" class="lp-btn lp-btn--ghost lqz-cancel">Cancel</a>
            <button type="submit" class="lp-btn"><x-lb-icon name="circle-check" />Save quiz</button>
        </div>
    </form>
</div>

<template id="qTpl">
    <article class="lqz-q">
        <div class="lqz-q-h">
            <b class="lqz-q-no"></b>
            <label class="lqz-q-subj"><x-lb-icon name="book-open" /><select class="q-subject" aria-label="Subject"></select></label>
            <span class="lqz-q-tools">
                <button type="button" class="lqz-ibtn q-dup" title="Copy question" aria-label="Copy question"><x-lb-icon name="copy" /></button>
                <button type="button" class="lqz-ibtn is-danger q-del" title="Remove question" aria-label="Remove question"><x-lb-icon name="trash" /></button>
            </span>
        </div>
        <textarea class="lqz-q-text q-text" rows="2" placeholder="Type the question" maxlength="1000"></textarea>
        <div class="lqz-opts" role="radiogroup">
            @foreach (['a', 'b', 'c', 'd'] as $o)
                <label class="lqz-opt">
                    <input type="radio" class="q-correct" value="{{ $o }}">
                    <span class="lqz-opt-l" title="Mark as the right answer">{{ strtoupper($o) }}<x-lb-icon name="check" /></span>
                    <input type="text" class="q-opt" data-o="{{ $o }}" maxlength="255" placeholder="Option {{ strtoupper($o) }}{{ in_array($o, ['c', 'd']) ? ' (optional)' : '' }}">
                </label>
            @endforeach
        </div>
        <p class="lqz-q-err" hidden></p>
        <button type="button" class="lqz-why-t"><x-lb-icon name="plus" />Add explanation</button>
        <div class="lqz-why" hidden>
            <input type="text" class="q-why" maxlength="1000" placeholder="Why is this the right answer? Students see it after the quiz">
        </div>
    </article>
</template>

<script>
(function () {
    var MAX = {{ $max }}, MAXS = {{ $maxSubjects }};
    var form = document.getElementById('quizForm'), list = document.getElementById('qList'), tpl = document.getElementById('qTpl');
    var data = @json($initial);
    var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]; }); };
    var items = function () { return list.querySelectorAll('.lqz-q'); };

    /* ---------- time limit chips ---------- */
    var dur = document.getElementById('qz_dur');
    document.querySelectorAll('.lqz-chip[data-min]').forEach(function (b) {
        b.addEventListener('click', function () { dur.value = b.dataset.min; syncDur(); });
    });
    function syncDur() {
        var v = String(parseInt(dur.value || '0', 10) || 0), hit = false;
        document.querySelectorAll('.lqz-chip[data-min]').forEach(function (b) {
            var on = b.dataset.min === v; hit = hit || on;
            b.classList.toggle('is-on', on); b.setAttribute('aria-checked', on ? 'true' : 'false');
        });
        dur.parentNode.classList.toggle('is-on', !hit);
    }
    dur.addEventListener('input', syncDur);

    /* ---------- subjects ---------- */
    var subjBoxes = [].slice.call(document.querySelectorAll('.lqz-subj'));
    function subjects() { return subjBoxes.map(function (b) { return b.hidden ? '' : b.querySelector('input').value.trim(); }); }
    function shownSubjects() { return subjBoxes.filter(function (b) { return !b.hidden; }); }
    function fillSubjects(sel, keep) {
        var s = subjects(), html = '', first = null;
        s.forEach(function (n, i) { if (n) { html += '<option value="' + i + '">' + esc(n) + '</option>'; if (first === null) first = i; } });
        sel.innerHTML = html || '<option value="0">General</option>';
        var want = (keep !== undefined && keep !== null && keep !== '' && s[keep]) ? String(keep) : String(first === null ? 0 : first);
        sel.value = want;
        // with one subject there's nothing to choose
        sel.closest('.lqz-q-subj').hidden = s.filter(Boolean).length < 2;
    }
    function refreshSubjects() {
        list.querySelectorAll('.q-subject').forEach(function (sel) { fillSubjects(sel, sel.value); });
        var shown = shownSubjects();
        document.getElementById('addSubject').hidden = shown.length >= MAXS;
        shown.forEach(function (b) { b.querySelector('.lqz-subj-x').hidden = shown.length < 2; });
    }
    document.getElementById('addSubject').addEventListener('click', function () {
        var b = subjBoxes.filter(function (x) { return x.hidden; })[0];
        if (!b) return;
        b.hidden = false; b.querySelector('input').value = ''; b.querySelector('input').focus(); refreshSubjects();
    });
    subjBoxes.forEach(function (b) {
        b.querySelector('input').addEventListener('input', refreshSubjects);
        b.querySelector('.lqz-subj-x').addEventListener('click', function () {
            b.hidden = true; b.querySelector('input').value = ''; refreshSubjects();
        });
    });

    /* ---------- questions ---------- */
    function renumber() {
        var all = items();
        all.forEach(function (el, n) {
            el.querySelector('.lqz-q-no').textContent = 'Q' + (n + 1);
            el.querySelector('.q-subject').name = 'q[' + n + '][subject]';
            el.querySelector('.q-text').name = 'q[' + n + '][question]';
            el.querySelector('.q-why').name = 'q[' + n + '][explanation]';
            el.querySelectorAll('.q-opt').forEach(function (o) { o.name = 'q[' + n + '][' + o.dataset.o + ']'; });
            el.querySelectorAll('.q-correct').forEach(function (r) { r.name = 'q[' + n + '][correct]'; });
            el.querySelector('.q-del').hidden = all.length < 2;
        });
        document.getElementById('qCount').textContent = all.length;
        document.getElementById('addQ').disabled = all.length >= MAX;
    }
    function setWhy(el, open) {
        el.querySelector('.lqz-why').hidden = !open;
        el.querySelector('.lqz-why-t').hidden = open;
    }
    function add(v, after, focus) {
        if (items().length >= MAX) return null;
        var el = tpl.content.firstElementChild.cloneNode(true);
        fillSubjects(el.querySelector('.q-subject'), v ? v.subject : null);
        if (v) {
            el.querySelector('.q-text').value = v.question || '';
            el.querySelectorAll('.q-opt').forEach(function (o) { o.value = v[o.dataset.o] || ''; });
            el.querySelectorAll('.q-correct').forEach(function (r) { r.checked = r.value === v.correct; });
            el.querySelector('.q-why').value = v.explanation || '';
            setWhy(el, !!v.explanation);
        }
        el.querySelector('.q-del').addEventListener('click', function () { el.remove(); renumber(); });
        el.querySelector('.q-dup').addEventListener('click', function () { add(read(el), el, true); });
        el.querySelector('.lqz-why-t').addEventListener('click', function () { setWhy(el, true); el.querySelector('.q-why').focus(); });
        el.addEventListener('change', function (e) { if (e.target.classList.contains('q-correct')) clearErr(el); });
        el.addEventListener('input', function () { clearErr(el); });
        if (after && after.nextSibling) list.insertBefore(el, after.nextSibling); else list.appendChild(el);
        renumber();
        if (focus) { el.scrollIntoView({behavior: 'smooth', block: 'center'}); el.querySelector('.q-text').focus({preventScroll: true}); }
        return el;
    }
    function read(el) {
        var v = {subject: el.querySelector('.q-subject').value, question: el.querySelector('.q-text').value, explanation: el.querySelector('.q-why').value, correct: ''};
        el.querySelectorAll('.q-opt').forEach(function (o) { v[o.dataset.o] = o.value; });
        el.querySelectorAll('.q-correct').forEach(function (r) { if (r.checked) v.correct = r.value; });
        return v;
    }
    function isBlank(v) { return !v.question.trim() && !['a', 'b', 'c', 'd'].some(function (o) { return (v[o] || '').trim(); }); }
    function showErr(el, msg) { var p = el.querySelector('.lqz-q-err'); p.textContent = msg; p.hidden = false; el.classList.add('is-err'); }
    function clearErr(el) { el.querySelector('.lqz-q-err').hidden = true; el.classList.remove('is-err'); }

    document.getElementById('addQ').addEventListener('click', function () { add(null, null, true); });

    /* ---------- paste many ---------- */
    var pasteBox = document.getElementById('pasteBox'), pasteToggle = document.getElementById('pasteToggle'), pasteMsg = document.getElementById('pasteMsg');
    function openPaste(open) { pasteBox.hidden = !open; pasteToggle.setAttribute('aria-expanded', open ? 'true' : 'false'); if (open) document.getElementById('pasteText').focus(); }
    pasteToggle.addEventListener('click', function () { openPaste(pasteBox.hidden); });
    document.getElementById('pasteCancel').addEventListener('click', function () { openPaste(false); });

    var RX_OPT = /^\(?([A-Da-d])\s*[).:\-]\s*(.+)$/, RX_ANS = /^(?:ans(?:wer)?|correct(?:\s*answer)?|right\s*answer|uttar)\s*[:\-.]?\s*\(?([A-Da-d])\b/i,
        RX_WHY = /^(?:explanation|why|reason|solution)\s*[:\-]\s*(.+)$/i, RX_NUM = /^(?:Q(?:ues(?:tion)?)?\s*\.?\s*)?\d+\s*[.):\-]\s*/i;
    function parse(text) {
        var out = [], cur = null;
        function push() { if (cur && cur.question.trim()) out.push(cur); cur = null; }
        text.replace(/\r/g, '').split('\n').forEach(function (raw) {
            var line = raw.trim();
            if (!line) return;
            // several options on one line: "A) x  B) y  C) z  D) w"
            var inline = line.match(/\(?[A-Da-d]\s*[).]\s*[^]+?(?=\s+\(?[A-Da-d]\s*[).]\s|$)/g);
            var m;
            if ((m = line.match(RX_ANS)) && cur) { cur.correct = m[1].toLowerCase(); return; }
            if ((m = line.match(RX_WHY)) && cur) { cur.explanation = m[1]; return; }
            if (cur && inline && inline.length > 1 && RX_OPT.test(inline[0].trim())) {
                inline.forEach(function (part) { var p = part.trim().match(RX_OPT); if (p) cur[p[1].toLowerCase()] = p[2].trim(); });
                return;
            }
            if ((m = line.match(RX_OPT)) && cur && !RX_NUM.test(line)) { cur[m[1].toLowerCase()] = m[2].trim(); return; }
            var hasOpts = cur && (cur.a || cur.b);
            if (!cur || RX_NUM.test(line) || hasOpts) {
                push();
                cur = {question: line.replace(RX_NUM, ''), a: '', b: '', c: '', d: '', correct: '', explanation: ''};
            } else {
                cur.question += ' ' + line; // question running onto a second line
            }
        });
        push();
        return out;
    }
    document.getElementById('pasteAdd').addEventListener('click', function () {
        var found = parse(document.getElementById('pasteText').value);
        if (!found.length) { pasteMsg.textContent = 'No question found. Check the format above.'; pasteMsg.className = 'lqz-paste-msg is-bad'; return; }
        // replace a single empty starter question
        var all = items();
        if (all.length === 1 && isBlank(read(all[0]))) all[0].remove();
        var room = MAX - items().length, added = 0, noAns = 0, first = null;
        found.slice(0, room).forEach(function (v) {
            var el = add(v); if (!first) first = el; added++;
            if (!v.correct) { noAns++; showErr(el, 'Tap the letter of the right answer.'); }
        });
        renumber();
        var msg = added + (added === 1 ? ' question' : ' questions') + ' added.';
        if (found.length > room) msg += ' ' + (found.length - room) + ' left out: a quiz can have ' + MAX + '.';
        if (noAns) msg += ' Mark the right answer on ' + noAns + ' of them.';
        pasteMsg.textContent = msg; pasteMsg.className = 'lqz-paste-msg ' + (noAns ? 'is-warn' : 'is-ok');
        document.getElementById('pasteText').value = '';
        if (first) first.scrollIntoView({behavior: 'smooth', block: 'center'});
    });

    /* ---------- publish switch + save ---------- */
    var pub = document.getElementById('pub');
    pub.addEventListener('change', function () { document.getElementById('pubHint').textContent = pub.checked ? 'Visible as soon as you save' : 'Saved as a draft'; });

    form.addEventListener('submit', function (e) {
        var title = document.getElementById('qz_title'), firstBad = null;
        // drop untouched empty questions
        items().forEach(function (el) { if (items().length > 1 && isBlank(read(el))) el.remove(); });
        renumber();
        if (!title.value.trim()) { firstBad = title; title.closest('.lef-f').classList.add('is-bad'); }
        items().forEach(function (el) {
            var v = read(el), msg = '';
            if (!v.question.trim()) msg = 'Type the question.';
            else if (!v.a.trim() || !v.b.trim()) msg = 'Fill at least options A and B.';
            else if (!v.correct) msg = 'Tap the letter of the right answer.';
            else if (!(v[v.correct] || '').trim()) msg = 'The right answer is an empty option.';
            if (msg) { showErr(el, msg); firstBad = firstBad || el; }
        });
        if (firstBad) {
            e.preventDefault();
            firstBad.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
    });
    document.getElementById('qz_title').addEventListener('input', function () { this.closest('.lef-f').classList.remove('is-bad'); });

    (data.length ? data : [null]).forEach(function (v) { add(v); });
    refreshSubjects();
    syncDur();
})();
</script>
@endsection
