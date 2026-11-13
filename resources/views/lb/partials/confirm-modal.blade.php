{{--
    One confirm popup for destructive actions (delete a branch, remove a shift).
    lbConfirm({ title, text, confirm: 'Delete', cancel: 'Cancel', icon: 'trash', tone: 'danger' })
    → Promise<boolean>. Esc, Cancel or a click outside resolves false. Include once per page.
--}}
@once
<div class="lbc" id="lbConfirm" role="dialog" aria-modal="true" aria-labelledby="lbcTitle" aria-describedby="lbcText" hidden>
    <div class="lbc-card">
        <span class="lbc-ic" id="lbcIcon"><x-lb-icon name="trash" /></span>
        <h3 id="lbcTitle">Are you sure?</h3>
        <p id="lbcText"></p>
        <div class="lbc-acts">
            <button type="button" class="lbc-btn" data-lbc="no">Cancel</button>
            <button type="button" class="lbc-btn is-danger" data-lbc="yes">Delete</button>
        </div>
    </div>
</div>
<style>
    .lbc { position: fixed; inset: 0; z-index: 2000; display: grid; place-items: center; padding: 20px; background: rgba(12, 16, 28, .55); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); animation: lbc-fade .18s ease; }
    .lbc[hidden] { display: none; }
    .lbc-card { width: 100%; max-width: 400px; padding: 28px 24px 22px; border-radius: 24px; background: var(--lp-card, var(--lb-surface, #fff)); color: var(--lp-ink, var(--lb-ink, #16181d)); text-align: center; box-shadow: 0 30px 80px -30px rgba(10, 14, 30, .6); animation: lbc-pop .22s cubic-bezier(.2, .9, .3, 1.2); }
    .lbc-ic { width: 60px; height: 60px; margin: 0 auto 14px; border-radius: 50%; display: grid; place-items: center; background: rgba(208, 69, 58, .1); color: #c0362c; box-shadow: 0 0 0 8px rgba(208, 69, 58, .05); }
    .lbc-ic .lb-i { width: 26px; height: 26px; }
    .lbc.is-info .lbc-ic { background: rgba(29, 60, 134, .1); color: #1d3c86; box-shadow: 0 0 0 8px rgba(29, 60, 134, .05); }
    .lbc-card h3 { margin: 0 0 8px; font-size: 19px; font-weight: 800; letter-spacing: -.015em; line-height: 1.3; color: inherit; text-transform: none; }
    .lbc-card p { margin: 0 0 22px; font-size: 14.5px; line-height: 1.5; color: var(--lp-mut, var(--lb-muted, #6b6f78)); }
    .lbc-card p b { color: var(--lp-ink, var(--lb-ink, #16181d)); }
    .lbc-acts { display: flex; gap: 10px; }
    .lbc-btn { flex: 1; height: 46px; border-radius: 980px; border: 1.5px solid var(--lp-line2, var(--lb-line, #dcdcd8)); background: var(--lp-card, var(--lb-surface, #fff)); color: var(--lp-ink, var(--lb-ink, #16181d)); font-family: inherit; font-size: 15px; font-weight: 600; line-height: 1; cursor: pointer; transition: background .15s, transform .1s; }
    .lbc-btn:hover { background: var(--lp-soft, #f3f4f6); }
    .lbc-btn:active { transform: scale(.98); }
    .lbc-btn.is-danger { border-color: #c0362c; background: #c0362c; color: #fff; }
    .lbc-btn.is-danger:hover { background: #a92c23; }
    .lbc-btn.is-primary { border-color: #1d3c86; background: #1d3c86; color: #fff; }
    .lbc-btn:focus-visible { outline: 0; box-shadow: 0 0 0 4px rgba(29, 60, 134, .2); }
    @keyframes lbc-fade { from { opacity: 0; } }
    @keyframes lbc-pop { from { opacity: 0; transform: translateY(10px) scale(.96); } }
    @media (prefers-reduced-motion: reduce) { .lbc, .lbc-card { animation: none; } }
</style>
<script>
(function () {
    var box = document.getElementById('lbConfirm');
    var yes = box.querySelector('[data-lbc="yes"]'), no = box.querySelector('[data-lbc="no"]');
    var done = null, lastFocus = null;
    function close(answer) {
        if (!done) return;
        box.hidden = true;
        document.removeEventListener('keydown', onKey);
        var d = done; done = null;
        if (lastFocus && lastFocus.focus) lastFocus.focus();
        d(answer);
    }
    function onKey(e) { if (e.key === 'Escape') close(false); }
    yes.addEventListener('click', function () { close(true); });
    no.addEventListener('click', function () { close(false); });
    box.addEventListener('click', function (e) { if (e.target === box) close(false); });
    window.lbConfirm = function (o) {
        o = o || {};
        if (done) close(false);
        document.getElementById('lbcTitle').textContent = o.title || 'Are you sure?';
        document.getElementById('lbcText').innerHTML = o.html || '';
        if (!o.html) document.getElementById('lbcText').textContent = o.text || '';
        yes.textContent = o.confirm || 'Delete';
        no.textContent = o.cancel || 'Cancel';
        var info = o.tone === 'info';
        box.classList.toggle('is-info', info);
        yes.classList.toggle('is-danger', !info);
        yes.classList.toggle('is-primary', info);
        lastFocus = document.activeElement;
        box.hidden = false;
        document.addEventListener('keydown', onKey);
        setTimeout(function () { no.focus(); }, 30);
        return new Promise(function (resolve) { done = resolve; });
    };
})();
</script>
@endonce
