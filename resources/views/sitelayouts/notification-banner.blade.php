@php
    $todayDate = now()->toDateString();
    $activeWebNotifs = \Illuminate\Support\Facades\DB::table('notifications')
        ->select(
            'batch_id',
            'guard',
            'data',
            \Illuminate\Support\Facades\DB::raw('MIN(status) as status'),
            \Illuminate\Support\Facades\DB::raw('MIN(start_date) as start_date'),
            \Illuminate\Support\Facades\DB::raw('MAX(end_date) as end_date'),
            \Illuminate\Support\Facades\DB::raw('MIN(created_at) as created_at')
        )
        ->where('guard', 'web')
        ->where(function($q) {
            $q->where('status', 1)->orWhereNull('status');
        })
        ->whereDate('start_date', '<=', $todayDate)
        ->whereDate('end_date', '>=', $todayDate)
        ->groupBy('batch_id', 'guard', 'data')
        ->orderByDesc('created_at')
        ->get();

    $notifCount = $activeWebNotifs->count();
@endphp

@if($notifCount > 0)
    <link rel="stylesheet" href="{{ asset('public/css/website-notification.css') }}?v={{ time() }}">
    @php
        $batchKey = $activeWebNotifs->pluck('batch_id')->implode('_');
    @endphp

    @if($notifCount > 1)
        {{-- ==========================================================================
             MULTI-NOTIFICATION TEXT TICKER
             ========================================================================== --}}
        <div id="libraro-web-ticker-bar" class="website-notification-banner" style="display: none;">
            <div class="container-fluid">
                <div class="website-notification-ticker-wrapper">
                    {{-- Fixed label --}}
                    <div class="ticker-static-label">
                        <span class="ticker-static-badge">
                            <i class="fa-solid fa-bullhorn"></i>
                            <span>Updates</span>
                        </span>
                    </div>

                    {{-- Horizontal Marquee Viewport --}}
                    <div class="ticker-viewport" id="libraroTickerViewport">
                        <div class="ticker-track" id="libraroTickerTrack">
                            {{-- Set 1 --}}
                            @foreach($activeWebNotifs as $notif)
                                @php
                                    $d = json_decode($notif->data, true) ?? [];
                                    $t = strtolower($d['notification_type'] ?? 'important');
                                    $icon = match($t) {
                                        'offers' => 'fa-tag',
                                        'wishes' => 'fa-gift',
                                        'maintenance' => 'fa-wrench',
                                        default => 'fa-bullhorn',
                                    };
                                @endphp
                                <div class="ticker-item">
                                    <span class="ticker-item-badge"><i class="fa-solid {{ $icon }}"></i> {{ ucfirst($t) }}</span>
                                    <span class="ticker-item-title">{{ $d['title'] ?? '' }}:</span>
                                    <span class="ticker-item-desc">{{ $d['description'] ?? '' }}</span>
                                    @if(!empty($d['link']))
                                        <a href="{{ $d['link'] }}" target="_blank" class="ticker-item-link">
                                            Learn More <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    @endif
                                    <span class="ticker-item-sep"><i class="fa-solid fa-circle"></i></span>
                                </div>
                            @endforeach

                            {{-- Set 2 (for continuous infinite loop) --}}
                            @foreach($activeWebNotifs as $notif)
                                @php
                                    $d = json_decode($notif->data, true) ?? [];
                                    $t = strtolower($d['notification_type'] ?? 'important');
                                    $icon = match($t) {
                                        'offers' => 'fa-tag',
                                        'wishes' => 'fa-gift',
                                        'maintenance' => 'fa-wrench',
                                        default => 'fa-bullhorn',
                                    };
                                @endphp
                                <div class="ticker-item">
                                    <span class="ticker-item-badge"><i class="fa-solid {{ $icon }}"></i> {{ ucfirst($t) }}</span>
                                    <span class="ticker-item-title">{{ $d['title'] ?? '' }}:</span>
                                    <span class="ticker-item-desc">{{ $d['description'] ?? '' }}</span>
                                    @if(!empty($d['link']))
                                        <a href="{{ $d['link'] }}" target="_blank" class="ticker-item-link">
                                            Learn More <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    @endif
                                    <span class="ticker-item-sep"><i class="fa-solid fa-circle"></i></span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Controls --}}
                    <div class="ticker-controls">
                        <button type="button" class="btn-ticker-toggle" id="tickerPlayPauseBtn" title="Pause ticker" aria-label="Pause / Play Ticker">
                            <i class="fa-solid fa-pause" id="tickerPlayPauseIcon"></i>
                        </button>
                        <button type="button" class="btn-ticker-close" onclick="dismissWebsiteNotice('{{ $batchKey }}')" title="Dismiss" aria-label="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- ==========================================================================
             SINGLE NOTIFICATION BANNER
             ========================================================================== --}}
        @php
            $singleNotif = $activeWebNotifs->first();
            $wData = json_decode($singleNotif->data, true) ?? [];
            $wType = strtolower($wData['notification_type'] ?? 'important');
            $wIcon = match($wType) {
                'offers' => 'fa-tag',
                'wishes' => 'fa-gift',
                'maintenance' => 'fa-wrench',
                default => 'fa-bullhorn',
            };
        @endphp

        <div id="libraro-web-ticker-bar" class="website-notification-banner" style="display: none;">
            <div class="container">
                <div class="website-notification-single-wrapper">
                    <div class="single-notice-content">
                        <span class="single-notice-badge">
                            <i class="fa-solid {{ $wIcon }}"></i> {{ ucfirst($wType) }}
                        </span>
                        <span class="single-notice-title" title="{{ $wData['title'] ?? '' }}">
                            {{ $wData['title'] ?? '' }}
                        </span>
                        <span class="single-notice-divider d-none d-md-inline">—</span>
                        <span class="single-notice-desc d-none d-md-inline" title="{{ $wData['description'] ?? '' }}">
                            {{ $wData['description'] ?? '' }}
                        </span>
                    </div>

                    <div class="single-notice-actions">
                        @if(!empty($wData['link']))
                            <a href="{{ $wData['link'] }}" target="_blank" class="btn-notice-action">
                                Learn More <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        @endif
                        <button type="button" class="btn-notice-close" onclick="dismissWebsiteNotice('{{ $batchKey }}')" title="Dismiss" aria-label="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        (function() {
            var storageKey = 'dismissed_web_notif_{{ $batchKey }}';
            var banner = document.getElementById('libraro-web-ticker-bar');
            if (banner && !sessionStorage.getItem(storageKey)) {
                banner.style.display = 'block';
            }

            window.dismissWebsiteNotice = function(key) {
                if (banner) {
                    banner.style.display = 'none';
                    sessionStorage.setItem('dismissed_web_notif_' + key, 'true');
                }
            };

            var track = document.getElementById('libraroTickerTrack');
            var toggleBtn = document.getElementById('tickerPlayPauseBtn');
            var toggleIcon = document.getElementById('tickerPlayPauseIcon');

            if (track && toggleBtn && toggleIcon) {
                var isManuallyPaused = false;
                toggleBtn.addEventListener('click', function() {
                    isManuallyPaused = !isManuallyPaused;
                    if (isManuallyPaused) {
                        track.classList.add('paused');
                        toggleIcon.className = 'fa-solid fa-play';
                        toggleBtn.setAttribute('title', 'Play ticker');
                    } else {
                        track.classList.remove('paused');
                        toggleIcon.className = 'fa-solid fa-pause';
                        toggleBtn.setAttribute('title', 'Pause ticker');
                    }
                });
            }
        })();
    </script>
@endif
