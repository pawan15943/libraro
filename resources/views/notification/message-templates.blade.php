@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    $mtLook = [
        11 => ['bell-ring', 'is-warn'],
        18 => ['hand-coins', 'is-bad'],
        19 => ['cake', 'is-violet'],
    ];
    $mtLibrary = optional(getLibrary())->library_name ?: 'Your Library';
    // Example values for the live preview only — never saved.
    $mtSamples = [
        'learner_name' => 'Rahul Sharma',
        'seat_no' => '12',
        'plan_end_date' => now()->addDays(3)->format('d M Y'),
        'due_date' => now()->addDays(2)->format('d M Y'),
        'pending_amount' => '₹500',
        'library_name' => $mtLibrary,
    ];
    $mtPanes = [
        ['waba', 'WhatsApp', 'message-circle', $wabaMessageTemplates, 700, 'is-wa'],
        ['text', 'SMS', 'message-square-text', $textMessageTemplates, 400, ''],
    ];
@endphp

<div class="lpg">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Message templates</span></div>
            <h1>Message templates</h1>
            <p>Edit the reminder, payment and birthday messages your members get. Tap a variable to drop it into the message.</p>
        </div>
    </header>

    @if($errors->any())
        <div class="lef-alert" role="alert" style="margin-bottom:12px"><x-lb-icon name="circle-alert" /><span>{{ $errors->first() }}</span></div>
    @endif

    <ul class="lpg-seg lmt-tabs" role="tablist">
        @foreach($mtPanes as $i => [$key, $label, $icon, $list])
            <li role="presentation">
                <button class="lpg-seg-b {{ $i === 0 ? 'active' : '' }} {{ $key === 'waba' ? 'is-wa' : '' }}" data-bs-toggle="tab" data-bs-target="#mt-{{ $key }}" type="button" role="tab" aria-selected="{{ $i === 0 ? 'true' : 'false' }}">
                    <x-lb-icon :name="$icon" />{{ $label }}<span class="lpg-n">{{ count($list) }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($mtPanes as $i => [$key, $label, $icon, $list, $max, $paneClass])
            <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }} {{ $key === 'waba' ? 'is-wa-pane' : '' }}" id="mt-{{ $key }}" role="tabpanel">
                <form method="POST" action="{{ route('message.templates.update') }}" class="lmt-form">
                    @csrf
                    @foreach($list as $index => $template)
                        @php [$tIcon, $tTone] = $mtLook[$template->operation_id] ?? ['message-square', '']; @endphp
                        <section class="lpg-card lmt-card">
                            <div class="lmt-card-h">
                                <span class="lpg-ic {{ $tTone }}"><x-lb-icon :name="$tIcon" /></span>
                                <div>
                                    <h2>{{ $template->name }}</h2>
                                    <p>{{ $template->sub_title }}</p>
                                </div>
                                <span class="lpg-tag {{ $key === 'waba' ? 'is-ok' : 'is-acc' }}"><x-lb-icon :name="$icon" />{{ $label }}</span>
                            </div>

                            <input type="hidden" name="templates[{{ $index }}][operation_id]" value="{{ $template->operation_id }}">
                            <input type="hidden" name="templates[{{ $index }}][type]" value="{{ $template->type }}">

                            <div class="lmt-body">
                                <div class="lmt-edit">
                                    <label class="lmt-label" for="mt_{{ $key }}_{{ $index }}">Message <span class="lpg-count" data-count>0 / {{ $max }}</span></label>
                                    <textarea class="lmt-ta" id="mt_{{ $key }}_{{ $index }}" name="templates[{{ $index }}][template_message]" rows="5" maxlength="{{ $max }}" placeholder="Write the message…">{{ old('templates.' . $index . '.template_message', $template->template_message) }}</textarea>
                                    <div class="lmt-meter"><i></i></div>

                                    <div class="lmt-vars-h"><x-lb-icon name="sparkles" />Variables — tap to insert</div>
                                    <div class="lmt-vars">
                                        @foreach($template->variables as $var)
                                            @php $varTag = '{{' . $var . '}}'; @endphp
                                            <button type="button" class="lmt-var" data-var="{{ $varTag }}" title="{{ $template->descriptions[$var] ?? '' }}">
                                                <x-lb-icon name="plus" /><code>{{ $varTag }}</code>
                                                @if(!empty($template->descriptions[$var]))<small>{{ $template->descriptions[$var] }}</small>@endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="lmt-prev" aria-live="polite">
                                    <div class="lmt-prev-h"><x-lb-icon name="eye" />Preview with example details</div>
                                    <div class="lmt-bubble"><span data-preview></span><time>{{ now()->format('h:i A') }}</time></div>
                                </div>
                            </div>
                        </section>
                    @endforeach

                    <div class="lpg-save">
                        <p>{{ $key === 'waba' ? 'Send yourself a test reminder before bulk sending.' : 'SMS messages can be up to ' . $max . ' characters.' }}</p>
                        <button type="submit" class="lp-btn"><x-lb-icon name="check" />Save {{ $label }}</button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var samples = @json($mtSamples);

        function esc(s) {
            return s.replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
        }

        function render(ta) {
            var card = ta.closest('.lmt-card');
            var max = parseInt(ta.getAttribute('maxlength'), 10) || 700;
            var len = ta.value.length;
            var ratio = len / max;
            var count = card.querySelector('[data-count]');
            var meter = card.querySelector('.lmt-meter');
            count.textContent = len + ' / ' + max;
            count.classList.toggle('is-warn', ratio >= .85 && ratio < 1);
            count.classList.toggle('is-bad', ratio >= 1);
            meter.classList.toggle('is-warn', ratio >= .85 && ratio < 1);
            meter.classList.toggle('is-bad', ratio >= 1);
            meter.firstElementChild.style.width = Math.min(100, ratio * 100) + '%';

            var out = esc(ta.value).replace(/\{\{\s*([a-z_]+)\s*\}\}/gi, function (m, name) {
                return '<mark title="' + esc(m) + '">' + esc(samples[name] !== undefined ? samples[name] : m) + '</mark>';
            });
            card.querySelector('[data-preview]').innerHTML = out || '<span class="lmt-empty">Your message will appear here.</span>';
        }

        function fit(ta) {
            ta.style.height = 'auto';
            ta.style.height = (ta.scrollHeight + 4) + 'px';
        }

        document.querySelectorAll('.lmt-ta').forEach(function (ta) {
            render(ta);
            fit(ta);
            ta.addEventListener('input', function () { render(ta); fit(ta); });
        });

        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function (b) {
            b.addEventListener('shown.bs.tab', function () {
                document.querySelectorAll(b.getAttribute('data-bs-target') + ' .lmt-ta').forEach(fit);
            });
        });

        // Insert the variable where the cursor is (and copy it, so it can be pasted elsewhere too)
        document.querySelectorAll('.lmt-var').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var ta = btn.closest('.lmt-card').querySelector('.lmt-ta');
                var tag = btn.dataset.var;
                var max = parseInt(ta.getAttribute('maxlength'), 10) || 700;
                if (ta.value.length + tag.length > max) {
                    if (window.lpToast) window.lpToast('Message is already at the ' + max + ' character limit');
                    return;
                }
                var s = ta.selectionStart != null ? ta.selectionStart : ta.value.length;
                var e = ta.selectionEnd != null ? ta.selectionEnd : ta.value.length;
                var before = ta.value.slice(0, s);
                var pad = before && !/\s$/.test(before) ? ' ' : '';
                ta.value = before + pad + tag + ta.value.slice(e);
                var pos = s + pad.length + tag.length;
                ta.focus();
                ta.setSelectionRange(pos, pos);
                ta.dispatchEvent(new Event('input'));
                try { navigator.clipboard && navigator.clipboard.writeText(tag); } catch (err) {}
                btn.classList.add('is-done');
                setTimeout(function () { btn.classList.remove('is-done'); }, 900);
            });
        });

        document.querySelectorAll('.lmt-form').forEach(function (f) {
            f.addEventListener('submit', function () { f.querySelector('.lpg-save .lp-btn').classList.add('is-busy'); });
        });
    });
</script>

@endsection
