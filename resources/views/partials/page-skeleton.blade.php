{{--
    Page shimmer: covers the page with a skeleton of its layout while it loads, then fades out.
    The real page renders underneath (only made invisible), so charts and sliders get their real size.
    Include as the first thing in @section('content'):  @include('partials.page-skeleton', ['type' => 'dashboard'])
    Types: dashboard | list | seatmap
--}}
@php $skType = $type ?? 'list'; @endphp
@once
<style>
    body.lp-pskel-on .content > .container-fluid { position: relative; }
    body.lp-pskel-on .content > .container-fluid > :not(#lpPageSkel) { visibility: hidden; }
    #loaderone { display: none !important; }
    .lp-pskel { position: absolute; inset: 0; z-index: 30; overflow: hidden; padding: inherit; background: var(--lp-page, #f4f4f2); transition: opacity .25s ease; }
    .lp-pskel.is-out { opacity: 0; pointer-events: none; }
    .lp-pskel .sk { display: block; border-radius: 12px; background: linear-gradient(90deg, var(--lp-soft2, #ececea) 0%, var(--lp-soft, #f6f6f4) 50%, var(--lp-soft2, #ececea) 100%); background-size: 200% 100%; animation: lpShimmer 1.2s linear infinite; }
    .lp-pskel .sk-card { padding: 16px; border-radius: 22px; border: 1px solid var(--lp-line, #e8e8e5); background: var(--lp-card, #fff); }
    .lp-pskel .sk-head { display: flex; flex-direction: column; gap: 10px; margin: 4px 2px 18px; }
    .lp-pskel .sk-row { display: flex; align-items: center; gap: 12px; }
    .lp-pskel .sk-col { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 8px; }
    .lp-pskel .sk-grid { display: grid; gap: 12px; }
    .lp-pskel .sk-av { width: 44px; height: 44px; flex: none; border-radius: 50%; }
    .lp-pskel .sk-btn { width: 84px; height: 36px; flex: none; border-radius: 980px; }
    .lp-pskel .sk-t1 { height: 30px; width: min(320px, 70%); border-radius: 10px; }
    .lp-pskel .sk-t2 { height: 14px; width: min(460px, 90%); }
    .lp-pskel .sk-l { height: 13px; }
    .lp-pskel .w30 { width: 30%; } .lp-pskel .w45 { width: 45%; } .lp-pskel .w60 { width: 60%; } .lp-pskel .w80 { width: 80%; }
    .lp-pskel .sk-chips { display: flex; gap: 8px; overflow: hidden; }
    .lp-pskel .sk-chip { width: 120px; height: 64px; flex: none; border-radius: 18px; }
    /* dashboard */
    .lp-pskel .sk-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 12px; }
    .lp-pskel .sk-kpi { height: 108px; border-radius: 22px; }
    .lp-pskel .sk-two { grid-template-columns: minmax(0, 1fr); margin-top: 12px; }
    .lp-pskel .sk-list { display: flex; flex-direction: column; gap: 14px; margin-top: 14px; }
    /* list */
    .lp-pskel .sk-tools { display: flex; gap: 8px; margin: 12px 0; }
    .lp-pskel .sk-search { flex: 1; height: 44px; border-radius: 14px; }
    .lp-pskel .sk-sq { width: 44px; height: 44px; flex: none; border-radius: 14px; }
    .lp-pskel .sk-members { grid-template-columns: minmax(0, 1fr); }
    /* seat map */
    .lp-pskel .sk-seats { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 12px; }
    .lp-pskel .sk-seat { height: 128px; border-radius: 18px; }
    @media (min-width: 768px) {
        .lp-pskel .sk-kpis { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .lp-pskel .sk-two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .lp-pskel .sk-members { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .lp-pskel .sk-seats { grid-template-columns: repeat(auto-fill, minmax(132px, 1fr)); }
    }
    @media (min-width: 1200px) { .lp-pskel .sk-members { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    /* members list (matches lb-members.css) */
    .lp-pskel .skm-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin: 4px 2px 0; }
    .lp-pskel .skm-head .sk-col { gap: 12px; }
    .lp-pskel .skm-head-r { display: flex; gap: 10px; align-items: center; }
    .lp-pskel .skm-ib { width: 48px; height: 48px; flex: none; border-radius: 14px; }
    .lp-pskel .skm-pill { display: block; height: 48px; border-radius: 980px; }
    .lp-pskel .skm-tiles { display: flex; gap: 8px; margin-top: 18px; overflow: hidden; }
    .lp-pskel .skm-tile { flex: 1 0 168px; display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 18px; border: 1.5px solid var(--lp-line, #e8e8e5); background: var(--lp-card, #fff); }
    .lp-pskel .skm-tile .sk-col { gap: 6px; }
    .lp-pskel .skm-tile-ic { width: 38px; height: 38px; flex: none; border-radius: 12px; }
    .lp-pskel .skm-tools { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; align-items: center; }
    .lp-pskel .skm-search { flex: 1 1 180px; height: 44px; border-radius: 980px; }
    .lp-pskel .skm-status { width: 260px; height: 44px; }
    .lp-pskel .skm-seg { width: 104px; height: 44px; }
    .lp-pskel .skm-add { width: 170px; height: 44px; }
    .lp-pskel .skm-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap: 12px; }
    .lp-pskel .skm-card { border-radius: 22px; border: 1px solid var(--lp-line, #e8e8e5); background: var(--lp-card, #fff); overflow: hidden; }
    .lp-pskel .skm-top { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-bottom: 1px solid var(--lp-soft2, #ececea); }
    .lp-pskel .skm-acts { display: none; margin-left: auto; gap: 6px; }
    .lp-pskel .skm-acts .sk { width: 30px; height: 30px; border-radius: 9px; }
    .lp-pskel .skm-body { display: flex; flex-direction: column; gap: 14px; padding: 14px 16px 16px; }
    .lp-pskel .skm-who { display: flex; align-items: center; gap: 12px; }
    .lp-pskel .skm-who .sk-av { width: 48px; height: 48px; }
    .lp-pskel .skm-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .lp-pskel .skm-btns { display: flex; gap: 8px; align-items: center; }
    .lp-pskel .skm-btns .skm-pill:first-child { flex: 1; }
    /* list view: one row per member, like the inline cards */
    .lp-pskel.is-list .skm-list { grid-template-columns: minmax(0, 1fr); gap: 10px; }
    @media (min-width: 992px) {
        .lp-pskel.is-list .skm-acts { display: flex; }
        .lp-pskel.is-list .skm-body { flex-direction: row; align-items: center; gap: 24px; padding: 16px 20px; }
        .lp-pskel.is-list .skm-who { flex: 0 0 220px; }
        .lp-pskel.is-list .skm-cols { flex: 1; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .lp-pskel.is-list .skm-btns .skm-pill:first-child { flex: none; }
    }
    @media (max-width: 767.98px) {
        .lp-pskel .skm-head-r .skm-pill, .lp-pskel .skm-status { display: none; }
        .lp-pskel .skm-search { flex-basis: 100%; }
        .lp-pskel .skm-add { flex: 1; width: auto; }
        .lp-pskel .skm-tile { flex-basis: 150px; }
    }
    @media (prefers-reduced-motion: reduce) { .lp-pskel .sk { animation: none; } }
</style>
@endonce

<div id="lpPageSkel" class="lp-pskel type-{{ $skType }}" aria-hidden="true">
    @if($skType !== 'list')
    <div class="sk-head"><span class="sk sk-t1"></span><span class="sk sk-t2"></span></div>
    @endif

    @if($skType === 'dashboard')
        <div class="sk-grid sk-kpis">
            @for($i = 0; $i < 4; $i++)<span class="sk sk-kpi"></span>@endfor
        </div>
        <div class="sk-card">
            <div class="sk-row"><span class="sk sk-l w30"></span></div>
            <div class="sk-chips" style="margin-top:14px">@for($i = 0; $i < 6; $i++)<span class="sk sk-chip" style="width:240px;height:96px"></span>@endfor</div>
        </div>
        <div class="sk-grid sk-two">
            @for($c = 0; $c < 2; $c++)
            <div class="sk-card">
                <span class="sk sk-l w45"></span>
                <div class="sk-list">
                    @for($i = 0; $i < 4; $i++)
                    <div class="sk-row"><span class="sk sk-av"></span><div class="sk-col"><span class="sk sk-l w60"></span><span class="sk sk-l w30"></span></div><span class="sk sk-sq" style="width:36px;height:36px;border-radius:50%"></span></div>
                    @endfor
                </div>
            </div>
            @endfor
        </div>

    @elseif($skType === 'seatmap')
        <div class="sk-chips">@for($i = 0; $i < 8; $i++)<span class="sk sk-chip" style="height:56px"></span>@endfor</div>
        <div class="sk-tools"><span class="sk sk-search"></span><span class="sk sk-sq"></span><span class="sk sk-sq"></span></div>
        <div class="sk-row"><span class="sk sk-btn" style="width:150px;height:40px"></span><span class="sk sk-btn" style="width:150px;height:40px"></span></div>
        <div class="sk-grid sk-seats">
            @for($i = 0; $i < 24; $i++)<span class="sk sk-seat"></span>@endfor
        </div>

    @else
        {{-- members list: same blocks as learner/learner.blade.php (head, tiles, toolbar, count, cards) --}}
        <div class="skm-head">
            <div class="sk-col"><span class="sk sk-l" style="width:150px"></span><span class="sk sk-t1" style="width:min(280px,60%);height:40px"></span></div>
            <div class="skm-head-r"><span class="sk skm-ib"></span><span class="sk skm-ib"></span><span class="sk skm-ib"></span><span class="sk skm-pill" style="width:140px"></span></div>
        </div>
        <div class="skm-tiles">
            @for($i = 0; $i < 7; $i++)
            <div class="skm-tile"><span class="sk skm-tile-ic"></span><span class="sk-col"><span class="sk" style="height:20px;width:38px;border-radius:7px"></span><span class="sk sk-l" style="width:80%"></span></span></div>
            @endfor
        </div>
        <div class="skm-tools">
            <span class="sk skm-search"></span>
            <span class="sk skm-pill skm-status"></span>
            <span class="sk skm-pill skm-seg"></span>
            <span class="sk skm-ib" style="border-radius:50%"></span>
            <span class="sk skm-pill skm-add"></span>
        </div>
        <span class="sk sk-l" style="width:90px;margin:18px 4px 12px"></span>
        <div class="skm-list">
            @for($i = 0; $i < 6; $i++)
            <div class="skm-card">
                <div class="skm-top"><span class="sk" style="width:10px;height:10px;border-radius:50%"></span><span class="sk sk-l" style="width:150px"></span><span class="skm-acts">@for($j = 0; $j < 12; $j++)<span class="sk"></span>@endfor</span></div>
                <div class="skm-body">
                    <div class="skm-who"><span class="sk sk-av"></span><span class="sk-col"><span class="sk sk-l" style="width:40%"></span><span class="sk sk-l" style="width:75%;height:15px"></span><span class="sk sk-l" style="width:50%"></span></span></div>
                    <div class="skm-cols">@for($j = 0; $j < 4; $j++)<span class="sk-col"><span class="sk sk-l" style="width:45%"></span><span class="sk sk-l" style="width:80%;height:15px"></span></span>@endfor</div>
                    <div class="skm-btns"><span class="sk skm-pill" style="width:140px;height:42px"></span><span class="sk skm-pill" style="width:110px;height:42px"></span><span class="sk skm-ib" style="border-radius:50%"></span></div>
                </div>
            </div>
            @endfor
        </div>
    @endif
</div>
<script>
(function () {
    var d = document, b = d.body, s = d.getElementById('lpPageSkel');
    if (!s) return;
    b.classList.add('lp-pskel-on');
    // members list: show the layout the user picked (card / list view, lb-members.js)
    try { if (s.classList.contains('type-list') && localStorage.getItem('lml-view') === 'list') s.classList.add('is-list'); } catch (e) {}
    var l = d.getElementById('loaderone'); if (l) l.remove();
    function done() {
        if (!s || s.dataset.gone) return;
        s.dataset.gone = '1';
        b.classList.remove('lp-pskel-on');
        s.classList.add('is-out');
        setTimeout(function () { s.remove(); }, 260);
    }
    // page fully loaded, or 2s after the HTML is ready (slow images / third-party scripts), whichever comes first
    window.addEventListener('load', done);
    d.addEventListener('DOMContentLoaded', function () { setTimeout(done, 2000); });
    window.addEventListener('pageshow', function (e) { if (e.persisted) done(); });
    setTimeout(done, 8000);
})();
</script>
