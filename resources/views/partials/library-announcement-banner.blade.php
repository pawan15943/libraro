@php
    $todayDate = now()->toDateString();
    $libId = function_exists('getLibraryId') ? getLibraryId() : null;
    $libUser = Auth::guard('library')->user() ?? Auth::guard('library_user')->user() ?? Auth::user();

    $activeLibraryNotif = null;
    if ($libId || $libUser) {
        $activeLibraryNotif = \Illuminate\Support\Facades\DB::table('notifications')
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
            ->first();
    }
@endphp

@if($activeLibraryNotif)
    @php
        $libData = json_decode($activeLibraryNotif->data, true) ?? [];
        $libType = strtolower($libData['notification_type'] ?? 'important');
        $batchId = $activeLibraryNotif->batch_id ?? 'default';

        $bannerGradient = match($libType) {
            'offers' => 'linear-gradient(90deg, #18225f 0%, #34939F 100%)',
            'wishes' => 'linear-gradient(90deg, #18225f 0%, #16a34a 100%)',
            'maintenance' => 'linear-gradient(90deg, #18225f 0%, #d97706 100%)',
            default => 'linear-gradient(90deg, #18225f 0%, #1e293b 100%)',
        };

        $badgeBg = match($libType) {
            'offers' => '#34939F',
            'wishes' => '#16a34a',
            'maintenance' => '#d97706',
            default => '#ef4444',
        };

        $iconClass = match($libType) {
            'offers' => 'fa-tag',
            'wishes' => 'fa-gift',
            'maintenance' => 'fa-wrench',
            default => 'fa-bullhorn',
        };
    @endphp

    <div id="libraro-library-announcement-{{ $batchId }}" class="libraro-library-announcement-bar" style="background: {{ $bannerGradient }}; color: #ffffff; padding: 0.75rem 1.25rem; border-bottom: 2px solid rgba(255,255,255,0.12); display: none; font-family: 'Outfit', sans-serif; position: relative; z-index: 100;">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                <span class="badge" style="background-color: {{ $badgeBg }}; font-size: 0.72rem; font-weight: 600; padding: 0.35rem 0.65rem; border-radius: 20px; text-transform: uppercase;">
                    <i class="fa-solid {{ $iconClass }} me-1"></i>{{ ucfirst($libType) }}
                </span>
                <span class="fw-semibold text-truncate" style="font-size: 0.88rem; max-width: 280px; letter-spacing: -0.01em;">
                    {{ $libData['title'] ?? 'Notice' }}
                </span>
                <span class="d-none d-lg-inline opacity-75" style="font-size: 0.85rem;">—</span>
                <span class="d-none d-lg-inline opacity-85 text-truncate" style="font-size: 0.84rem; max-width: 500px;">
                    {{ $libData['description'] ?? '' }}
                </span>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if(!empty($libData['link']))
                    <a href="{{ $libData['link'] }}" target="_blank" class="btn btn-sm btn-light py-1 px-3 fw-bold" style="font-size: 0.75rem; border-radius: 20px; color: #18225f; text-decoration: none;">
                        View Details <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                @endif
                <a href="{{ route('list.notification') }}" class="btn btn-sm btn-outline-light py-1 px-2 fw-semibold" style="font-size: 0.72rem; border-radius: 20px; text-decoration: none;" title="Go to Notification Center">
                    <i class="fa-solid fa-bell me-1"></i>All Notices
                </a>
                <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.65rem; opacity: 0.85;" onclick="dismissLibraryNotice('{{ $batchId }}')" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var bId = "{{ $batchId }}";
            var banner = document.getElementById('libraro-library-announcement-' + bId);
            if (banner && !sessionStorage.getItem('dismissed_lib_notif_' + bId)) {
                banner.style.display = 'block';
            }
            window.dismissLibraryNotice = function(id) {
                var el = document.getElementById('libraro-library-announcement-' + id);
                if (el) {
                    el.style.display = 'none';
                    sessionStorage.setItem('dismissed_lib_notif_' + id, 'true');
                }
            };
        })();
    </script>
@endif
