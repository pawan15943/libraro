@extends('layouts.library')

@section('content')
@php
    $r = $receipt;
    $inr = fn ($v) => '₹' . number_format((float) $v, ((float) $v == (int) $v) ? 0 : 2);
    $first = \Illuminate\Support\Str::of($r['name'] ?: 'there')->explode(' ')->first();
    $mobile = substr(preg_replace('/\D/', '', (string) $r['mobile']), -10);
    $isPartly = $r['pending'] > 0;
    $message = implode("\n", array_filter([
        'Hi ' . $first . ', your seat' . ($r['library'] ? ' at ' . $r['library'] : '') . ' is booked.',
        '',
        $r['seat'] . ($r['shift'] ? ' · ' . $r['shift'] : ''),
        'Plan: ' . $r['plan'] . ($r['start'] ? ' (' . $r['start'] . ($r['end'] ? ' – ' . $r['end'] : '') . ')' : ''),
        'Paid: ' . $inr($r['paid']) . ($isPartly ? ' · Pay later: ' . $inr($r['pending']) . ($r['due_date'] ? ' by ' . $r['due_date'] : '') : ''),
        $r['receipt_url'] ? 'Receipt: ' . $r['receipt_url'] : null,
        '',
        'Thank you!' . ($r['library'] ? ' – Team ' . $r['library'] : ''),
    ], fn ($l) => $l !== null));
@endphp

<link rel="stylesheet" href="{{ asset('public/css/lb-swap.css') }}?v={{ @filemtime(public_path('css/lb-swap.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-bookdone.css') }}?v={{ @filemtime(public_path('css/lb-bookdone.css')) }}" />

<div class="lsw lbd" id="lbdPage" data-mobile="{{ $mobile }}" data-receipt="{{ $r['receipt_url'] }}">
    <div class="lsw-sheet">
        <header class="lsw-head">
            <span class="lsw-head-ic lbd-head-ic"><x-lb-icon name="badge-check" /></span>
            <div class="lsw-head-t">
                <span class="lsw-kicker lbd-kicker">New booking</span>
                <h2>Booking confirmed</h2>
            </div>
            <a href="{{ route('learners') }}" class="lsw-close" title="Close" aria-label="Close"><x-lb-icon name="x" /></a>
        </header>

        <div class="lsw-body">
            {{-- Hero --}}
            <section class="lbd-hero">
                <span class="lbd-tick"><x-lb-icon name="check" /></span>
                <h3>{{ $r['name'] }} is all set</h3>
                <p>{{ $r['seat'] }}@if($r['shift']) · {{ $r['shift'] }}@endif · {{ $r['plan'] }}</p>
                @if($r['start'])<span class="lbd-hero-chip"><x-lb-icon name="calendar-check" />{{ $r['start'] }}@if($r['end']) – {{ $r['end'] }}@endif</span>@endif
            </section>

            {{-- Receipt --}}
            <section class="lbd-rc" aria-label="Receipt">
                <div class="lbd-rc-h">
                    <span><small>Receipt</small><b>{{ $r['receipt_no'] ? '#' . $r['receipt_no'] : 'New booking' }}</b></span>
                    <span class="lbd-pill {{ $isPartly ? 'is-due' : '' }}"><x-lb-icon name="{{ $isPartly ? 'clock-alert' : 'circle-check' }}" />{{ $isPartly ? 'Partly paid' : 'Paid' }}</span>
                </div>
                <dl class="lbd-rows">
                    <div><dt>Learner</dt><dd>{{ $r['name'] }}</dd></div>
                    <div><dt>Learner ID</dt><dd>{{ $r['uid'] }}</dd></div>
                    @if($r['mobile'])<div><dt>Mobile</dt><dd>+91 {{ $mobile }}</dd></div>@endif
                    <div><dt>Seat</dt><dd>{{ $r['seat'] }}</dd></div>
                    <div><dt>Plan</dt><dd>{{ $r['plan'] }}@if($r['shift']) · {{ $r['shift'] }}@endif</dd></div>
                    @if($r['booked_at'])<div><dt>Booked on</dt><dd>{{ $r['booked_at'] }}</dd></div>@endif
                </dl>
                <div class="lbd-cut" aria-hidden="true"></div>
                <dl class="lbd-rows lbd-money">
                    <div><dt>Total</dt><dd>{{ $inr($r['total']) }}</dd></div>
                    <div class="is-ok"><dt>Paid now{{ $r['mode'] ? ' · ' . $r['mode'] : '' }}</dt><dd>{{ $inr($r['paid'] + ($r['advance'] ?? 0)) }}</dd></div>
                    @if(($r['advance'] ?? 0) > 0)
                    <div class="is-ok"><dt>Advance · used on the next renewal</dt><dd>{{ $inr($r['advance']) }}</dd></div>
                    @endif
                    @if($isPartly)
                    <div class="is-due"><dt>Pay later{{ $r['due_date'] ? ' · by ' . $r['due_date'] : '' }}</dt><dd>{{ $inr($r['pending']) }}</dd></div>
                    @endif
                </dl>
            </section>

            {{-- Share --}}
            <section class="lef-sec lbd-share">
                <div class="lbd-share-h">
                    <span class="lbd-share-ic"><x-lb-icon name="send" /></span>
                    <div><h4>Share with {{ $first }}</h4><p>Edit the message if you like, then pick how to send it.</p></div>
                </div>
                <textarea class="lbd-msg" id="lbdMsg" rows="7" aria-label="Message to the learner">{{ $message }}</textarea>
                <div class="lbd-acts">
                    <a href="#" class="lbd-act is-wa" data-share="wa"><span><x-lb-brand name="whatsapp" /></span><b>WhatsApp</b><small>{{ $mobile ? '+91 ' . $mobile : 'Choose a chat' }}</small></a>
                    <a href="#" class="lbd-act" data-share="sms"><span><x-lb-icon name="message-square-text" /></span><b>Text message</b><small>{{ $mobile ? 'SMS to ' . $mobile : 'Open messages' }}</small></a>
                    <button type="button" class="lbd-act" data-share="copy"><span><x-lb-icon name="copy" /></span><b data-copy-label>Copy message</b><small>Paste it anywhere</small></button>
                    @if($r['receipt_url'])
                    <a href="{{ $r['receipt_url'] }}" target="_blank" rel="noopener" class="lbd-act"><span><x-lb-icon name="receipt-text" /></span><b>Open receipt</b><small>View, print or save PDF</small></a>
                    @endif
                </div>
            </section>
        </div>

        <footer class="lsw-foot lbd-foot">
            <a href="{{ route('learners.show', $learnerId) }}" class="lsw-btn-ghost"><x-lb-icon name="user-round" />&nbsp;Learner profile</a>
            <span class="lbd-grow"></span>
            <a href="{{ route('seats') }}" class="lsw-btn-ghost">Book another</a>
            <a href="{{ route('learners') }}" class="lsw-btn-primary"><x-lb-icon name="check" /><span>Done</span></a>
        </footer>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var page = document.getElementById('lbdPage');
    var msg = document.getElementById('lbdMsg');
    var mobile = page.dataset.mobile;
    page.addEventListener('click', function (e) {
        var b = e.target.closest('[data-share]');
        if (!b) return;
        var text = msg.value.trim();
        if (b.dataset.share === 'wa') {
            e.preventDefault();
            window.open('https://wa.me/' + (mobile ? '91' + mobile : '') + '?text=' + encodeURIComponent(text), '_blank', 'noopener');
        } else if (b.dataset.share === 'sms') {
            e.preventDefault();
            window.location.href = 'sms:' + (mobile ? '+91' + mobile : '') + '?&body=' + encodeURIComponent(text);
        } else if (b.dataset.share === 'copy') {
            var done = function () {
                var l = b.querySelector('[data-copy-label]');
                l.textContent = 'Copied';
                b.classList.add('is-done');
                setTimeout(function () { l.textContent = 'Copy message'; b.classList.remove('is-done'); }, 1800);
            };
            if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(done, function () { msg.select(); document.execCommand('copy'); done(); });
            else { msg.select(); document.execCommand('copy'); done(); }
        }
    });
});
</script>
@endsection
