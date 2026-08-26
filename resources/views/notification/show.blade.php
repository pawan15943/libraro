@php
    $layout = 'layouts.admin';
    if (Auth::guard('library')->check()) {
        $layout = 'layouts.library';
    } elseif (Auth::guard('learner')->check()) {
        $layout = 'layouts.learner';
    }
@endphp
@extends($layout)

@section('title', 'Notifications List')

@section('content')

<!-- External Strictly Scoped Stylesheet -->
<link rel="stylesheet" href="{{ asset('public/css/notifications-page.css') }}">

<div class="libraro-notifications-center-page container-fluid px-0 py-2">
    <!-- Top Header Block (Breadcrumbs & Action Button Right Aligned, No Main Heading) -->
    <div class="notif-page-header d-flex justify-content-between align-items-center mb-3">
        <!-- Left Filter Pills Section -->
        <div class="notif-filter-nav mb-0">
            <a href="{{ route('list.notification', ['filter' => 'all']) }}" class="notif-filter-pill {{ ($filter ?? 'all') === 'all' ? 'active' : '' }}">
                <i class="fa-solid fa-list"></i> All <span class="badge rounded-pill bg-secondary">{{ $totalCount ?? 0 }}</span>
            </a>
            <a href="{{ route('list.notification', ['filter' => 'unread']) }}" class="notif-filter-pill {{ ($filter ?? '') === 'unread' ? 'active' : '' }}">
                <i class="fa-solid fa-envelope me-1"></i> Unread <span class="badge rounded-pill bg-danger">{{ $unreadCount ?? 0 }}</span>
            </a>
            <a href="{{ route('list.notification', ['filter' => 'read']) }}" class="notif-filter-pill {{ ($filter ?? '') === 'read' ? 'active' : '' }}">
                <i class="fa-solid fa-envelope-open me-1"></i> Read <span class="badge rounded-pill bg-success">{{ $readCount ?? 0 }}</span>
            </a>
        </div>
        
        <!-- Right Block: Top Breadcrumb via AppServiceProvider + Action Button -->
        <div class="text-end">
            <div class="notif-top-breadcrumb mb-2">
                @if(!empty($breadcrumb) && is_array($breadcrumb))
                    @php $i = 0; $total = count($breadcrumb); @endphp
                    @foreach($breadcrumb as $label => $url)
                        @php $i++; @endphp
                        @if($i < $total)
                            <a href="{{ $url }}">{{ $label }}</a> / 
                        @else
                            <span>{{ $label }}</span>
                        @endif
                    @endforeach
                @else
                    <a href="{{ Route::has('library.home') ? route('library.home') : url('/library/home') }}">Dashboard</a> / <span>Notifications List</span>
                @endif
            </div>
            
            @if(($unreadCount ?? 0) > 0)
                <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="notif-mark-all-btn">
                        <i class="fa-solid fa-check-double"></i> Mark All Read
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Horizontal Card Rows List Container -->
    <div class="row">
        <div class="col-12">
            @forelse($notifications as $notification)
                @php
                    $nData = json_decode($notification->data ?? '{}', true);
                    $isUnread = empty($notification->read_at);
                    $type = $nData['notification_type'] ?? 'important';
                    
                    $iconClass = match($type) {
                        'wishes' => 'fa-gift',
                        'maintenance' => 'fa-wrench',
                        'offers' => 'fa-tag',
                        default => 'fa-bell',
                    };
                @endphp
                
                <div class="notif-row-card {{ $isUnread ? 'unread-item' : 'read-item' }}" id="notif-card-{{ $notification->id }}">
                    <!-- Left Avatar Icon -->
                    <div class="notif-avatar-circle">
                        <i class="fa-solid {{ $iconClass }}"></i>
                    </div>

                    <!-- Column 1: Title -->
                    <div style="flex: 2; min-width: 160px;">
                        <div class="notif-col-label">Title</div>
                        <div class="notif-col-value text-truncate" style="max-width: 220px;">
                            {{ $nData['title'] ?? 'System Notification' }}
                        </div>
                    </div>

                    <!-- Column 2: Message Description -->
                    <div style="flex: 3; min-width: 200px;">
                        <div class="notif-col-label">Message</div>
                        <div class="notif-col-subtext text-truncate" style="max-width: 320px;">
                            {{ $nData['description'] ?? '-' }}
                        </div>
                    </div>

                    <!-- Column 3: Created Time -->
                    <div style="flex: 1.5; min-width: 130px;">
                        <div class="notif-col-label">Created</div>
                        <div class="notif-col-subtext">
                            {{ \Carbon\Carbon::parse($notification->created_at)->format('d-m-Y H:i') }}
                        </div>
                    </div>

                    <!-- Column 4: Status -->
                    <div style="flex: 1; min-width: 90px;">
                        <div class="notif-col-label">Status</div>
                        <div class="notif-col-value {{ $isUnread ? 'status-unread-text' : 'status-read-text' }}" style="font-size: 0.8rem;">
                            {{ $isUnread ? 'Unread' : 'Read' }}
                        </div>
                    </div>

                    <!-- Column 5: Action Icons -->
                    <div class="d-flex align-items-center gap-2">
                        @if(!empty($nData['link']))
                            <a href="{{ $nData['link'] }}" class="notif-icon-action-btn" target="_blank" title="Open Link">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        @endif
                        
                        @if($isUnread)
                            <button type="button" class="notif-icon-action-btn toggle-read-btn" data-id="{{ $notification->id }}" data-action="read" title="Mark as Read">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        @else
                            <button type="button" class="notif-icon-action-btn toggle-read-btn" data-id="{{ $notification->id }}" data-action="unread" title="Mark as Unread">
                                <i class="fa-solid fa-envelope"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <!-- Empty State Card -->
                <div class="notif-page-empty">
                    <div class="notif-empty-illustration">
                        <i class="fa-solid fa-bell-slash"></i>
                    </div>
                    <h5 class="notif-empty-heading">No Notifications Found</h5>
                    <p class="notif-empty-subtext">You're all caught up! There are no notifications matching your current filter.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).on('click', '.toggle-read-btn', function() {
            var btn = $(this);
            var id = btn.data('id');
            var action = btn.data('action');
            var url = action === 'read' ? "{{ route('notifications.markAsRead') }}" : "{{ route('notifications.markAsUnread') }}";

            btn.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                data: { notification_id: id },
                success: function(res) {
                    location.reload();
                },
                error: function() {
                    btn.prop('disabled', false);
                }
            });
        });
    });
</script>

@endsection