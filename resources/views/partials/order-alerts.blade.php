{{-- Ordering & Services: "new order" alert on every library page (not the Order Board, which rings itself).
     Polls orders.alerts; shows a pill with a chime when a new order comes in. Hidden when the module is not in the
     plan, the user has no "Manage Orders", the owner hid the Orders menu, or the branch never set ordering up. --}}
@php
    $oaOn = false;
    try {
        $oaLib = (int) getLibraryId();
        $oaBranch = (int) getCurrentBranch();
        $oaOn = $oaLib && $oaBranch && Route::has('orders.alerts') && ! request()->routeIs('orders.board')
            && \Illuminate\Support\Facades\Gate::allows('has-permission', 'Manage Orders')
            && \App\Services\Ordering\OrderingVisibility::planAllows($oaLib)
            && ! in_array('orders.board', \App\Services\Ordering\OrderingVisibility::hiddenMenuUrls(), true)
            && \Illuminate\Support\Facades\DB::table('order_settings')->where('branch_id', $oaBranch)->exists();
    } catch (\Throwable $e) {
        $oaOn = false;
    }
@endphp
@if ($oaOn)
<div class="lp-oa" id="lpOrderAlert" role="status" aria-live="polite" hidden>
    <span class="lp-oa-ic"><x-lb-icon name="bell-ring" /></span>
    <a class="lp-oa-t" href="{{ route('orders.board') }}" data-oa-link>
        <b data-oa-title>New order</b>
        <small data-oa-sub>Tap to open the Order Board</small>
    </a>
    <a class="lp-oa-go" href="{{ route('orders.board') }}" data-oa-link>Open</a>
    <button type="button" class="lp-oa-x" data-oa-x aria-label="Hide until the next order"><x-lb-icon name="x" /></button>
</div>
<style>
    .lp-oa { position: fixed; z-index: 1060; top: 78px; left: 50%; transform: translateX(-50%); display: flex; align-items: center; gap: 10px; width: max-content; max-width: calc(100vw - 32px);
        padding: 8px 8px 8px 10px; border-radius: 18px; background: var(--lp-card, #fff); border: 1px solid color-mix(in srgb, #ea580c 35%, var(--lp-line, #e5e7eb));
        box-shadow: 0 18px 40px -16px rgba(0, 0, 0, .35); animation: lpOaIn .25s ease-out; }
    .lp-oa[hidden] { display: none; }
    @keyframes lpOaIn { from { opacity: 0; transform: translate(-50%, -10px); } }
    .lp-oa-ic { flex: none; width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; background: #ea580c; color: #fff; animation: lpOaRing 1.6s ease-in-out infinite; }
    .lp-oa-ic .lb-i { width: 19px; height: 19px; }
    @keyframes lpOaRing { 0%, 60%, 100% { box-shadow: 0 0 0 0 rgba(234, 88, 12, .45); } 30% { box-shadow: 0 0 0 7px rgba(234, 88, 12, 0); } }
    .lp-oa-t { display: flex; flex-direction: column; min-width: 0; text-decoration: none; }
    .lp-oa-t b { font-size: 14px; font-weight: 800; color: var(--lp-ink, #111); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lp-oa-t small { font-size: 12px; color: var(--lp-mut2, #6b7280); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    body.lp a.lp-oa-go { flex: none; height: 36px; padding: 0 14px; border-radius: 11px; display: inline-flex; align-items: center; background: #ea580c; color: #fff !important; font-size: 13.5px; font-weight: 800; text-decoration: none; }
    body.lp .lp-oa-x { flex: none; width: 32px; height: 32px; border: 0; border-radius: 10px; background: transparent; color: var(--lp-mut2, #6b7280); display: grid; place-items: center; cursor: pointer; }
    body.lp .lp-oa-x:hover { background: var(--lp-soft3, #f1f2f4); }
    .lp-oa-x .lb-i { width: 16px; height: 16px; }
    @media (max-width: 560px) { .lp-oa { top: 70px; } .lp-oa-t small { display: none; } }
</style>
<script>
(function () {
    var box = document.getElementById('lpOrderAlert'); if (!box) return;
    var URL = @json(route('orders.alerts')), KEY = 'lp-oa-seen:{{ $oaBranch }}', title = document.title, lastLatest = null;
    function seen() { try { return +sessionStorage.getItem(KEY) || 0; } catch (e) { return 0; } }
    function setSeen(v) { try { sessionStorage.setItem(KEY, String(v)); } catch (e) {} }
    function chime() {
        try {
            var C = window.AudioContext || window.webkitAudioContext; if (!C) return;
            var ctx = new C(), t = ctx.currentTime;
            [880, 1175].forEach(function (f, i) {
                var o = ctx.createOscillator(), g = ctx.createGain();
                o.frequency.value = f; o.type = 'sine'; o.connect(g); g.connect(ctx.destination);
                g.gain.setValueAtTime(0.0001, t + i * .18); g.gain.exponentialRampToValueAtTime(0.25, t + i * .18 + .02); g.gain.exponentialRampToValueAtTime(0.0001, t + i * .18 + .35);
                o.start(t + i * .18); o.stop(t + i * .18 + .4);
            });
        } catch (e) {}
    }
    function paint(j) {
        var n = j.new || 0, p = j.proofs || 0;
        document.title = n ? '(' + n + ') ' + title : title;
        // shown while something waits, unless closed for this exact set of orders
        if ((!n && !p) || (j.latest && j.latest <= seen() && !j.late)) { box.hidden = true; return; }
        box.querySelector('[data-oa-title]').textContent = n
            ? (n === 1 ? '1 new order' : n + ' new orders') + (j.late ? ' — waiting too long' : '')
            : (p === 1 ? '1 payment to check' : p + ' payments to check');
        box.querySelector('[data-oa-sub]').textContent = n && p ? 'Also ' + p + ' UPI ' + (p === 1 ? 'payment' : 'payments') + ' to check' : (n ? 'Accept it on the Order Board' : 'Learners sent UPI screenshots');
        var link = n === 1 && j.first ? j.first : j.board;
        box.querySelectorAll('[data-oa-link]').forEach(function (a) { a.href = link; });
        box.hidden = false;
    }
    function tick() {
        if (document.hidden) return;
        fetch(URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (j) {
                if (!j) return;
                var fresh = lastLatest !== null && (j.latest || 0) > lastLatest; // a new order came in while this page is open
                lastLatest = j.latest || 0;
                paint(j);
                if (fresh && !box.hidden) chime();
            })
            .catch(function () {});
    }
    box.querySelector('[data-oa-x]').addEventListener('click', function () { setSeen(lastLatest || 0); box.hidden = true; document.title = title; });
    setTimeout(tick, 1500);
    setInterval(tick, 15000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) tick(); });
})();
</script>
@endif
