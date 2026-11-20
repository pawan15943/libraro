@php
    $todayDate = now()->toDateString();
    $libId = function_exists('getLibraryId') ? getLibraryId() : null;
    $libUser = Auth::guard('library')->user() ?? Auth::guard('library_user')->user() ?? Auth::user();

    $activeLibraryNotif = null;
    if ($libId || $libUser) {
        $candidates = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('guard', 'library')
            ->where(function($q) {
                $q->where('status', 1)->orWhereNull('status');
            })
            ->where(function ($q) use ($libId, $libUser) {
                if ($libId) {
                    $q->where('notifiable_id', $libId);
                }
                if ($libUser) {
                    $q->orWhere('notifiable_id', $libUser->id);
                }
            })
            ->whereDate('start_date', '<=', $todayDate)
            ->whereDate('end_date', '>=', $todayDate)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // an "order is waiting" notice is shown only while that order still waits (it may be accepted already)
        $activeLibraryNotif = $candidates->first(function ($n) {
            $d = json_decode($n->data, true) ?? [];
            if (strtolower($d['notification_type'] ?? '') !== 'order') {
                return true;
            }
            if (! preg_match('#/orders/(\d+)#', (string) ($d['link'] ?? ''), $m)) {
                return false;
            }
            try {
                return \App\Models\Ordering\Order::withoutGlobalScopes()->where('id', (int) $m[1])
                    ->whereIn('status', \App\Services\Ordering\OrderStatusService::INCOMING)->exists();
            } catch (\Throwable $e) {
                return false;
            }
        });
    }
@endphp

@if($activeLibraryNotif)
    @php
        $libData = json_decode($activeLibraryNotif->data, true) ?? [];
        $libType = strtolower($libData['notification_type'] ?? 'important');
        $batchId = $activeLibraryNotif->batch_id ?? $activeLibraryNotif->id ?? 'default';

        // tone + icon per kind of notice
        [$tone, $icon, $label] = match($libType) {
            'offers' => ['teal', 'tag', 'Offer'],
            'wishes' => ['green', 'gift', 'Wishes'],
            'maintenance' => ['amber', 'wrench', 'Maintenance'],
            'order' => ['orange', 'bell-ring', 'Order'],
            default => ['red', 'megaphone', ucfirst($libType)],
        };

        // stored links may be relative ("/library/orders/3"): keep the app's base path (e.g. /libraro)
        $link = $libData['link'] ?? null;
        $isExternal = false;
        if ($link) {
            if (! preg_match('#^https?://#i', $link)) {
                $link = url($link);
            }
            $isExternal = parse_url($link, PHP_URL_HOST) !== parse_url(url('/'), PHP_URL_HOST);
        }
    @endphp

    <div id="libraro-library-announcement-{{ $batchId }}" class="lp-notice is-{{ $tone }}" role="status" hidden>
        <span class="lp-notice-badge"><x-lb-icon :name="$icon" />{{ $label }}</span>
        <p class="lp-notice-t">
            <b>{{ $libData['title'] ?? 'Notice' }}</b>
            @if (!empty($libData['description']))<span>{{ $libData['description'] }}</span>@endif
        </p>
        <div class="lp-notice-a">
            @if ($link)
                <a href="{{ $link }}" class="lp-notice-go" @if ($isExternal) target="_blank" rel="noopener" @endif>{{ $libType === 'order' ? 'Open order' : 'View details' }}<x-lb-icon name="arrow-right" /></a>
            @endif
            <a href="{{ route('list.notification') }}" class="lp-notice-all" title="All notices"><x-lb-icon name="bell" /><span>All notices</span></a>
            <button type="button" class="lp-notice-x" onclick="dismissLibraryNotice('{{ $batchId }}')" aria-label="Hide this notice"><x-lb-icon name="x" /></button>
        </div>
    </div>

    <style>
        .lp-notice { --nt: #ef4444; position: relative; z-index: 100; display: flex; align-items: center; gap: 12px; padding: 10px 16px 10px 20px;
            background: linear-gradient(90deg, #18225f 0%, color-mix(in srgb, var(--nt) 30%, #18225f) 100%); color: #fff; font-family: inherit; }
        .lp-notice[hidden] { display: none; }
        .lp-notice.is-teal { --nt: #34939f; } .lp-notice.is-green { --nt: #16a34a; } .lp-notice.is-amber { --nt: #d97706; } .lp-notice.is-orange { --nt: #ea580c; }
        .lp-notice-badge { flex: none; display: inline-flex; align-items: center; gap: 6px; height: 26px; padding: 0 10px; border-radius: 999px; background: var(--nt);
            font-size: 11.5px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .lp-notice-badge .lb-i { width: 13px; height: 13px; }
        .lp-notice-t { flex: 1; min-width: 0; margin: 0; display: flex; align-items: baseline; gap: 10px; font-size: 14px; line-height: 1.3; white-space: nowrap; overflow: hidden; }
        body.lp .lp-notice .lp-notice-t b { flex: none; max-width: 45%; overflow: hidden; text-overflow: ellipsis; font-size: 14.5px; font-weight: 700; color: #fff; text-transform: none; letter-spacing: normal; }
        body.lp .lp-notice .lp-notice-t span { min-width: 0; overflow: hidden; text-overflow: ellipsis; color: rgba(255, 255, 255, .78); font-size: 13.5px; text-transform: none; letter-spacing: normal; }
        .lp-notice-a { flex: none; display: flex; align-items: center; gap: 6px; }
        body.lp a.lp-notice-go { display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 14px; border-radius: 10px; background: #fff; color: #18225f !important;
            font-size: 13px; font-weight: 700; text-decoration: none; white-space: nowrap; }
        body.lp a.lp-notice-go:hover { background: #eef1fb; }
        .lp-notice-go .lb-i { width: 14px; height: 14px; }
        body.lp a.lp-notice-all { display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 12px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, .3);
            background: transparent; color: #fff !important; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; }
        body.lp a.lp-notice-all:hover { background: rgba(255, 255, 255, .1); }
        .lp-notice-all .lb-i { width: 14px; height: 14px; }
        body.lp button.lp-notice-x { width: 32px; height: 32px; padding: 0; border: 0; border-radius: 10px; background: transparent; color: rgba(255, 255, 255, .8); display: grid; place-items: center; cursor: pointer; }
        body.lp button.lp-notice-x:hover { background: rgba(255, 255, 255, .12); color: #fff; }
        .lp-notice-x .lb-i { width: 16px; height: 16px; }
        @media (max-width: 991px) { body.lp .lp-notice .lp-notice-t span { display: none; } body.lp .lp-notice .lp-notice-t b { max-width: none; } }
        @media (max-width: 640px) {
            .lp-notice { flex-wrap: wrap; gap: 8px 10px; padding: 10px 12px; }
            .lp-notice-t { flex-basis: calc(100% - 120px); }
            .lp-notice-t b { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .lp-notice-a { width: 100%; }
            body.lp a.lp-notice-go { flex: 1; justify-content: center; }
            .lp-notice-all span { display: none; }
            body.lp a.lp-notice-all { width: 32px; padding: 0; justify-content: center; }
        }
    </style>

    <script>
        (function() {
            var bId = "{{ $batchId }}";
            var banner = document.getElementById('libraro-library-announcement-' + bId);
            try {
                if (banner && !sessionStorage.getItem('dismissed_lib_notif_' + bId)) banner.hidden = false;
            } catch (e) { if (banner) banner.hidden = false; }
            window.dismissLibraryNotice = function(id) {
                var el = document.getElementById('libraro-library-announcement-' + id);
                if (el) {
                    el.hidden = true;
                    try { sessionStorage.setItem('dismissed_lib_notif_' + id, 'true'); } catch (e) {}
                }
            };
        })();
    </script>
@endif
