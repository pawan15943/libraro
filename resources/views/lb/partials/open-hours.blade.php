{{--
    Library open – close time (BranchHoursService). Posts open_time, close_time ("HH:MM") and
    all_day (1 = open 24 hours); the server works out the hour count. #hour carries the same
    count for page scripts (summary, checks).
    Params: $note (false hides the line under it), $skin 'ob' (setup pages) | 'lef' (panel forms), $open, $close ("HH:MM" or null),
            $allDay (bool), $canAllDay (plan has 24 hours), $errors (optional bag)
--}}
@php
    $skin = $skin ?? 'ob';
    $ohOpen = old('open_time', $open ?? '');
    $ohClose = old('close_time', $close ?? '');
    $ohAllDay = (bool) old('all_day', $allDay ?? false) && ($canAllDay ?? false);
    $ohErr = isset($errors) ? ($errors->first('open_time') ?: $errors->first('close_time') ?: $errors->first('hour')) : '';
    $ohMin = \App\Services\BranchHoursService::MIN_HOURS;
@endphp
<div class="lboh lboh--{{ $skin }}{{ $ohErr ? ' is-bad' : '' }}" data-lboh data-min="{{ $ohMin }}">
    <div class="lboh-row">
        @foreach([['open_time', 'Opens at', $ohOpen, 'sunrise'], ['close_time', 'Closes at', $ohClose, 'sunset']] as [$ohName, $ohLabel, $ohVal, $ohIcon])
            @if($skin === 'lef')
                <div class="lef-f lboh-f">
                    <label for="{{ $ohName }}">{{ $ohLabel }} <i>*</i></label>
                    <div class="lef-in">
                        <x-lb-icon :name="$ohIcon" />
                        <input type="text" id="{{ $ohName }}" name="{{ $ohName }}" value="{{ $ohVal }}" data-lb-time data-lb-time-step="30" data-lb-time-icon="0" data-lb-time-class="lboh-pick" data-lb-time-clear="0" placeholder="Select time" @disabled($ohAllDay)>
                    </div>
                </div>
            @else
                <div class="ob-field lboh-f">
                    <label for="{{ $ohName }}">{{ $ohLabel }} <em>*</em></label>
                    <input type="text" id="{{ $ohName }}" name="{{ $ohName }}" value="{{ $ohVal }}" data-lb-time data-lb-time-step="30" data-lb-time-class="ob-select" data-lb-time-clear="0" placeholder="Select time" @disabled($ohAllDay)>
                </div>
            @endif
        @endforeach
        <div class="lboh-total" aria-live="polite">
            <small>Open</small>
            <b data-lboh-total>—</b>
        </div>
    </div>
    @if($canAllDay ?? false)
        <label class="lboh-24">
            <input type="checkbox" name="all_day" value="1" @checked($ohAllDay)>
            <span class="lboh-24-box" aria-hidden="true"></span>
            <span>Open 24 hours</span>
        </label>
    @endif
    <input type="hidden" name="hour" id="hour" value="" data-lboh-hour>
    <p class="lboh-err" data-lboh-err @if(!$ohErr) hidden @endif>{{ $ohErr }}</p>
    @if($note ?? true)<p class="lboh-note" data-lboh-note>Every shift has to fit in these hours, and advanced shifts are booked only inside them.</p>@endif
</div>

@once
<style>
    .lboh { display: grid; gap: 10px; }
    .lboh-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto; gap: 12px; align-items: end; }
    .lboh-row .lboh-f { margin: 0; min-width: 0; }
    .lboh-total { display: flex; flex-direction: column; justify-content: center; min-width: 104px; height: 48px; padding: 0 14px; border-radius: 14px; background: var(--lp-soft2, #f3f4f6); line-height: 1.15; }
    .lboh-total small { font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--lp-mut2, #8a8f98); }
    .lboh-total b { font-size: 15px; font-weight: 800; color: var(--lp-ink, #16181d); white-space: nowrap; text-transform: none; }
    .lboh.is-bad .lboh-total { background: rgba(208, 69, 58, .08); }
    .lboh.is-bad .lboh-total b { color: #c0362c; }
    .lboh-24 { display: inline-flex; align-items: center; gap: 10px; width: fit-content; margin: 0; font-size: 14.5px; font-weight: 600; color: var(--lp-ink, #16181d); cursor: pointer; user-select: none; }
    .lboh .lboh-24 span:not(.lboh-24-box) { color: var(--lp-ink, #16181d) !important; font-size: 14.5px; font-weight: 600; }
    .lboh-24 input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .lboh-24-box { width: 20px; height: 20px; flex: none; border-radius: 6px; border: 1.5px solid var(--lp-line2, #dcdcd8); background: var(--lp-card, #fff); display: grid; place-items: center; transition: background .15s, border-color .15s; }
    .lboh-24 input:checked + .lboh-24-box { background: var(--lp-acc, #1d3c86); border-color: var(--lp-acc, #1d3c86); }
    .lboh-24 input:checked + .lboh-24-box::after { content: ''; width: 5px; height: 10px; margin-top: -2px; border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg); }
    .lboh-24 input:focus-visible + .lboh-24-box { box-shadow: 0 0 0 4px rgba(29, 60, 134, .18); }
    .lboh-err { margin: 0; font-size: 13px; font-weight: 600; color: #c0362c; }
    .lboh-note { margin: 0; font-size: 13px; line-height: 1.45; color: var(--lp-mut, #6b6f78); }
    /* panel forms: the picker fills the field box */
    body.lp .lef-in .lbt-field.lboh-pick { flex: 1; height: 100%; min-height: 0; padding: 0; border: 0; border-radius: 0; background: transparent; box-shadow: none; font-size: 15px; color: var(--lp-ink); }
    body.lp .lef-in:has(.lbt-field.lboh-pick:disabled) { background: var(--lp-soft2); cursor: not-allowed; }
    body.lp .lef-in .lbt-field.lboh-pick:disabled { opacity: .6; background: transparent; }
    .lboh.is-bad .lef-in, .lboh.is-bad .lbt-field.ob-select { border-color: #d0453a; }
    .lboh.is-bad .lbt-field.ob-select { box-shadow: inset 0 0 0 1.5px #d0453a; }
    @media (max-width: 560px) {
        .lboh-row { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .lboh-total { grid-column: 1 / -1; flex-direction: row; align-items: center; justify-content: space-between; height: 42px; }
    }
</style>
<script>
/* Works out the hours from open / close; window.lbOpenHours(box).check() → error text or '' */
(function () {
    function toMin(v) { var m = String(v || '').match(/^(\d{1,2}):(\d{2})/); return m ? (+m[1] % 24) * 60 + +m[2] : null; }
    function init(box) {
        if (box.lboh) return box.lboh;
        var open = box.querySelector('[name="open_time"]'), close = box.querySelector('[name="close_time"]');
        var all = box.querySelector('[name="all_day"]'), hour = box.querySelector('[data-lboh-hour]');
        var total = box.querySelector('[data-lboh-total]'), err = box.querySelector('[data-lboh-err]'), min = +box.getAttribute('data-min') || 10;
        function hours() {
            if (all && all.checked) return 24;
            var a = toMin(open.value), b = toMin(close.value);
            if (a === null || b === null || a === b) return null;
            return ((b - a + 1440) % 1440) / 60;
        }
        function paint() {
            var on = !!(all && all.checked);
            open.disabled = on; close.disabled = on;
            var h = hours();
            hour.value = h !== null && h % 1 === 0 ? h : '';
            total.textContent = on ? '24 hrs' : (h === null ? '—' : (Math.round(h * 100) / 100) + ' hrs a day');
        }
        function check() {
            var h = hours();
            if (all && all.checked) return '';
            if (!open.value) return 'Choose when the library opens.';
            if (!close.value) return 'Choose when the library closes.';
            if (h === null) return all ? 'Open and close can\'t be the same time. Tick "Open 24 hours" for a library that never closes.' : 'Open and close can\'t be the same time.';
            if (h % 1 !== 0) return 'Keep the library open for full hours, e.g. 6:30 AM – 10:30 PM.';
            if (h < min) return 'The library must be open at least ' + min + ' hours a day (now ' + h + ' hrs).';
            return '';
        }
        function showErr(msg) { err.textContent = msg || ''; err.hidden = !msg; box.classList.toggle('is-bad', !!msg); }
        box.addEventListener('change', function () { showErr(''); paint(); });
        box.addEventListener('input', paint);
        paint();
        box.lboh = { check: check, showErr: showErr, hours: hours, paint: paint };
        return box.lboh;
    }
    window.lbOpenHours = init;
    function boot() { document.querySelectorAll('[data-lboh]').forEach(init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
</script>
@endonce
