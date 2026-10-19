{{--
    Website notices — same data source and rules as sitelayouts/notification-banner
    (notifications table, guard "web", active + within start/end date, grouped by batch).
    Super admin sends them exactly as before; this only changes how they look.

    @include('lb.partials.notices', ['variant' => 'hero'])  → rotating pill inside the home hero
    @include('lb.partials.notices', ['variant' => 'bar'])   → slim dismissible bar under the nav (other pages)
--}}
@php
    $lbVariant = $variant ?? 'bar';
    try {
        $lbToday = now()->toDateString();
        $lbNotices = \Illuminate\Support\Facades\DB::table('notifications')
            ->select(
                'batch_id', 'guard', 'data',
                \Illuminate\Support\Facades\DB::raw('MIN(status) as status'),
                \Illuminate\Support\Facades\DB::raw('MIN(start_date) as start_date'),
                \Illuminate\Support\Facades\DB::raw('MAX(end_date) as end_date'),
                \Illuminate\Support\Facades\DB::raw('MIN(created_at) as created_at')
            )
            ->where('guard', 'web')
            ->where(fn ($q) => $q->where('status', 1)->orWhereNull('status'))
            ->whereDate('start_date', '<=', $lbToday)
            ->whereDate('end_date', '>=', $lbToday)
            ->groupBy('batch_id', 'guard', 'data')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($n) {
                $d = json_decode($n->data, true) ?? [];
                $type = strtolower($d['notification_type'] ?? 'important');
                $link = $d['link'] ?? '';
                // Only allow http(s) and site-relative links.
                if ($link && !preg_match('#^(https?://|/)#i', $link)) { $link = ''; }
                return [
                    'type'  => $type,
                    'icon'  => match ($type) { 'offers' => 'tag', 'wishes' => 'gift', 'maintenance' => 'wrench', default => 'megaphone' },
                    'title' => trim($d['title'] ?? ''),
                    'desc'  => trim($d['description'] ?? ''),
                    'link'  => $link,
                    'batch' => $n->batch_id,
                ];
            })
            ->filter(fn ($n) => $n['title'] !== '' || $n['desc'] !== '')
            ->values();
    } catch (\Throwable $e) {
        $lbNotices = collect();
    }

    // Hero always shows something useful: real notices first, then evergreen highlights.
    if ($lbVariant === 'hero' && $lbNotices->isEmpty()) {
        $lbNotices = collect([
            ['type' => 'new', 'icon' => 'headset', 'title' => 'Free setup help', 'desc' => 'Our team sets up your seats, shifts and plans with you on a call.', 'link' => '#demo', 'batch' => 'd1'],
            ['type' => 'new', 'icon' => 'shield-check', 'title' => '7-day refund', 'desc' => 'Try any plan. Not happy? Get your full money back.', 'link' => '#refund', 'batch' => 'd2'],
            ['type' => 'new', 'icon' => 'map-pin', 'title' => 'Free listing', 'desc' => 'Every plan lists your library on Find my library.', 'link' => route('find-my-library'), 'batch' => 'd3'],
        ]);
    }
    $lbBatchKey = substr(md5($lbNotices->pluck('batch')->implode('_')), 0, 12);
    $lbLabel = fn ($t) => match ($t) { 'offers' => 'Offer', 'wishes' => 'Wishes', 'maintenance' => 'Maintenance', 'new' => 'New', default => 'Notice' };
@endphp

@if($lbNotices->isNotEmpty())
    @if($lbVariant === 'hero')
        <div class="lb-notice" data-lb-notice role="region" aria-label="Latest updates" aria-roledescription="carousel">
            <div class="lb-notice-viewport" aria-live="polite">
                @foreach($lbNotices as $i => $n)
                    <div class="lb-notice-item lb-notice--{{ $n['type'] }} {{ $i === 0 ? 'is-active' : '' }}" @if($i) aria-hidden="true" @endif>
                        <span class="lb-notice-tag"><x-lb-icon :name="$n['icon']" />{{ $lbLabel($n['type']) }}</span>
                        <span class="lb-notice-text"><b>{{ $n['title'] }}</b>@if($n['title'] && $n['desc']) · @endif{{ $n['desc'] }}</span>
                        @if($n['link'])
                            <a class="lb-notice-link" href="{{ $n['link'] }}" @if(str_starts_with($n['link'], 'http')) target="_blank" rel="noopener" @endif tabindex="{{ $i === 0 ? '0' : '-1' }}">View <x-lb-icon name="chevron-right" size="14" /></a>
                        @endif
                    </div>
                @endforeach
            </div>
            @if($lbNotices->count() > 1)
                <div class="lb-notice-dots" aria-hidden="true">
                    @foreach($lbNotices as $i => $n)<i class="{{ $i === 0 ? 'is-active' : '' }}"></i>@endforeach
                </div>
            @endif
        </div>
    @else
        <div class="lb-noticebar" data-lb-noticebar="{{ $lbBatchKey }}" hidden role="region" aria-label="Announcements">
            <div class="lb-noticebar-in">
                <div class="lb-noticebar-track" data-lb-notice>
                    <div class="lb-notice-viewport" aria-live="polite">
                        @foreach($lbNotices as $i => $n)
                            <div class="lb-notice-item lb-notice--{{ $n['type'] }} {{ $i === 0 ? 'is-active' : '' }}" @if($i) aria-hidden="true" @endif>
                                <span class="lb-notice-tag"><x-lb-icon :name="$n['icon']" />{{ $lbLabel($n['type']) }}</span>
                                <span class="lb-notice-text"><b>{{ $n['title'] }}</b>@if($n['title'] && $n['desc']) · @endif{{ $n['desc'] }}</span>
                                @if($n['link'])
                                    <a class="lb-notice-link" href="{{ $n['link'] }}" @if(str_starts_with($n['link'], 'http')) target="_blank" rel="noopener" @endif tabindex="{{ $i === 0 ? '0' : '-1' }}">Learn more <x-lb-icon name="chevron-right" size="14" /></a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                <button type="button" class="lb-noticebar-close" data-lb-noticebar-close aria-label="Dismiss announcements"><x-lb-icon name="x" size="16" /></button>
            </div>
        </div>
    @endif
@endif
