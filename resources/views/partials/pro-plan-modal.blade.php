{{--
    "Available in Pro" modal (library panel, Basic plan). Every option the plan does not include is
    shown greyed (learner options) or with a small Pro tag (menus, chips) and data-pro-lock="<option>"
    (see planLocked()); a click on it — or picking an <option data-pro-lock> — opens this: one icon,
    the option's name and "This option is available in the Pro plan", with Upgrade (owner) or a note
    to ask the owner (staff).
--}}
@php
    $ppPitch = true; // the modal names the option and points to Pro
    $ppOwner = Auth::guard('library')->check();
@endphp
@if($ppPitch)
<div class="pp-overlay" id="ppModal" hidden role="dialog" aria-modal="true" aria-labelledby="ppTitle">
    <div class="pp-sheet">
        <button type="button" class="pp-x" data-pp-close aria-label="Close"><x-lb-icon name="x" /></button>
        <span class="pp-gem"><x-lb-icon name="gem" /></span>
        <h3 id="ppTitle" class="pp-title" data-pp-feature>This option</h3>
        <p class="pp-text">This option is available in the <b>Pro plan</b>.</p>
        @if($ppOwner)
            <a href="{{ route('subscriptions.choosePlan', ['action' => 'upgrade']) }}" class="pp-cta"><x-lb-icon name="gem" />Upgrade to Pro</a>
        @else
            <p class="pp-staff">Ask your library owner to upgrade to Pro.</p>
        @endif
        <button type="button" class="pp-later" data-pp-close>Maybe later</button>
    </div>
</div>
<style>
    /* sidebar / chips: small Pro tag */
    .lp-pro-badge { display: inline-flex !important; align-items: center; gap: 2px; height: 15px !important; padding: 0 5px !important; margin-left: 6px; border-radius: 980px; background: linear-gradient(135deg, #f6c453, #e8932f); color: #3d2600 !important; font-size: 9px !important; font-weight: 800; letter-spacing: .04em; line-height: 1 !important; text-transform: uppercase; vertical-align: middle; flex: none; }
    .lp-pro-badge .lb-i { width: 8px !important; height: 8px !important; margin: 0 !important; }
    [data-pro-lock] { cursor: pointer; }
    .lp-nav-kids a.is-pro-locked, .lp-nav-item.is-pro-locked { color: var(--lp-mut3, #9aa0aa) !important; }
    .lp-nav-kids a.is-pro-locked:hover, .lp-nav-item.is-pro-locked:hover { color: var(--lp-mut, #4b4f58) !important; }
    /* learner options the plan does not include: light grey, a tap explains */
    .is-pro-locked .op-icon-circle { background: #f1f2f4 !important; color: #b8bcc4 !important; box-shadow: none !important; }
    .is-pro-locked .op-icon-label, .is-pro-locked .mobile-action-label { color: #a6abb3 !important; }
    a.action-btn.is-pro-locked, .is-pro-locked > a.action-btn { background: #f4f5f7 !important; color: #b8bcc4 !important; border-color: #eceef1 !important; box-shadow: none !important; }
    a.action-btn.is-pro-locked i, .is-pro-locked i { color: #b8bcc4 !important; }

    .pp-overlay { position: fixed; inset: 0; z-index: 30000; display: grid; place-items: center; padding: 16px; background: rgba(15, 20, 35, .5); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); animation: ppFade .16s ease; }
    .pp-overlay[hidden] { display: none; }
    .pp-sheet { position: relative; width: min(380px, 100%); padding: 30px 26px 20px; border-radius: 26px; background: var(--lp-card, #fff); text-align: center; box-shadow: 0 34px 80px -28px rgba(10, 20, 60, .55); animation: ppRise .24s cubic-bezier(.2, .8, .2, 1); }
    .pp-x { position: absolute; top: 12px; right: 12px; width: 34px; height: 34px; border: 0; border-radius: 50%; display: grid; place-items: center; background: var(--lp-soft, #f2f3f5); color: var(--lp-mut, #4b4f58); cursor: pointer; }
    .pp-x .lb-i { width: 16px; height: 16px; }
    .pp-gem { width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 20px; display: grid; place-items: center; background: linear-gradient(135deg, #f6c453, #e8932f); color: #3d2600; box-shadow: 0 14px 30px -12px rgba(232, 147, 47, .9); }
    .pp-gem .lb-i { width: 30px; height: 30px; }
    .pp-sheet .pp-title { margin: 0 0 6px !important; font-size: 20px !important; font-weight: 800 !important; letter-spacing: -.02em; color: var(--lp-ink, #16181d) !important; }
    .pp-sheet .pp-text { margin: 0 0 20px; font-size: 15px; line-height: 1.5; color: var(--lp-mut, #4b4f58); }
    .pp-sheet .pp-text b { color: #b8650f; }
    .pp-cta { display: flex; align-items: center; justify-content: center; gap: 8px; height: 48px; border-radius: 980px; background: linear-gradient(135deg, #f6c453, #e8932f); color: #3d2600 !important; font-weight: 800; font-size: 15px; text-decoration: none !important; box-shadow: 0 14px 28px -14px rgba(232, 147, 47, .95); }
    .pp-cta:hover { filter: brightness(1.04); }
    .pp-cta .lb-i { width: 18px; height: 18px; }
    .pp-staff { margin: 0; padding: 12px; border-radius: 14px; background: var(--lp-soft, #f2f3f5); font-size: 14px; font-weight: 600; color: var(--lp-mut, #4b4f58); }
    .pp-later { margin-top: 10px; border: 0; background: none; font-size: 14px; font-weight: 600; color: var(--lp-mut2, #6b6f78); cursor: pointer; }
    @keyframes ppFade { from { opacity: 0; } }
    @keyframes ppRise { from { opacity: 0; transform: translateY(14px) scale(.98); } }
    @media (prefers-reduced-motion: reduce) { .pp-overlay, .pp-sheet { animation: none; } }
</style>
<script>
(function () {
    var modal = document.getElementById('ppModal');
    if (!modal) return;
    var featureEl = modal.querySelector('[data-pp-feature]');
    var lastFocus = null;
    function open(feature) {
        featureEl.textContent = feature || 'This option';
        lastFocus = document.activeElement;
        modal.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        var cta = modal.querySelector('.pp-cta, .pp-later');
        if (cta) cta.focus({ preventScroll: true });
    }
    function close() {
        modal.hidden = true;
        document.documentElement.style.overflow = '';
        if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
    }
    window.lpProLock = open;
    // a Pro-only option: open the modal instead of doing anything (runs before every other handler)
    document.addEventListener('click', function (e) {
        var el = e.target.closest && e.target.closest('[data-pro-lock]');
        if (!el || el.tagName === 'OPTION' || el.tagName === 'SELECT') return;
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        open(el.getAttribute('data-pro-lock'));
    }, true);
    // a Pro-only <option> picked in a select: go back to the previous choice and explain
    document.addEventListener('focusin', function (e) {
        if (e.target.tagName === 'SELECT') e.target.dataset.ppPrev = e.target.value;
    }, true);
    document.addEventListener('change', function (e) {
        var sel = e.target;
        if (sel.tagName !== 'SELECT') return;
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.hasAttribute('data-pro-lock')) return;
        e.stopImmediatePropagation();
        sel.value = sel.dataset.ppPrev || '';
        open(opt.getAttribute('data-pro-lock'));
    }, true);
    modal.addEventListener('click', function (e) { if (e.target === modal || e.target.closest('[data-pp-close]')) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });
})();
</script>
@endif
