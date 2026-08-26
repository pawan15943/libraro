<div id="loader">
    <div class="spinner"></div>
</div>
<style>
    @php if( !empty($primary_color)) @endphp :root {
        --c1: {{ $primary_color ? $primary_color : '#151F38' }};
    }
</style>


<div class="header">
    <div class="d-flex" style="gap:1rem">
        <div class="conatent flex" style="flex: 1;">
            <i class="fa fa-bars mr-2" id="sidebar"></i>
        </div>

        <!-- Header Notification Component (Strictly Scoped under .libraro-header-notification) -->
        @php
            $authUser = Auth::user() ?? getAuthenticatedUser();
            $unreadNotifications = collect();
            if ($authUser) {
                $unreadNotifications = DB::table('notifications')
                    ->where('notifiable_id', $authUser->id)
                    ->whereNull('read_at')
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get();
                $unreadCount = DB::table('notifications')
                    ->where('notifiable_id', $authUser->id)
                    ->whereNull('read_at')
                    ->count();
            } else {
                $unreadCount = 0;
            }
        @endphp

        <div class="libraro-header-notification me-2">
            <div class="dropdown">
                <a class="notif-bell-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="fas fa-bell notif-bell-icon"></i>
                    @if($unreadCount > 0)
                        <span class="notif-count-badge"></span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end notif-dropdown-menu">
                    <!-- Dropdown Header -->
                    <div class="notif-dropdown-header">
                        <div>
                            <h6 class="notif-header-title"><i class="fa-solid fa-bell me-1" style="font-size: 12px;"></i> Notifications</h6>
                            <div class="notif-header-subtitle">
                                {{ $unreadCount > 0 ? $unreadCount . ' unread alert' . ($unreadCount == 1 ? '' : 's') : 'All notifications read' }}
                            </div>
                        </div>
                        @if($unreadCount > 0)
                            <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="notif-mark-read-btn">
                                    <i class="fa-solid fa-check-double me-1" style="font-size: 10px;"></i> Mark all read
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Scrollable Notifications List -->
                    <div class="notif-list-container">
                        @forelse($unreadNotifications as $n)
                            @php
                                $nData = json_decode($n->data ?? '{}', true);
                            @endphp
                            <a href="{{ $nData['link'] ?? route('list.notification') }}" class="notif-item">
                                <div class="notif-icon-box">
                                    <i class="fa-solid fa-envelope-open-text"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-center justify-content-between gap-1">
                                        <div class="notif-title">{{ $nData['title'] ?? 'New Notification' }}</div>
                                        <span class="notif-unread-dot" title="Unread"></span>
                                    </div>
                                    <div class="notif-description">{{ $nData['description'] ?? '' }}</div>
                                    <div class="notif-time">
                                        <i class="fa-regular fa-clock"></i>
                                        <span>{{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <!-- Empty State -->
                            <div class="notif-empty-box">
                                <div class="notif-empty-icon">
                                    <i class="fa-solid fa-bell-slash"></i>
                                </div>
                                <div class="notif-empty-title">All Caught Up!</div>
                                <div class="notif-empty-text">You have no unread notifications right now.</div>
                            </div>
                        @endforelse
                    </div>

                    <!-- Dropdown Footer -->
                    <div class="notif-dropdown-footer">
                        <a href="{{ route('list.notification') }}" class="notif-footer-link">
                            <span>View All Notifications</span>
                            <i class="fa-solid fa-arrow-right-long" style="font-size: 11px;"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="profile">
            <div class="dropdown">

                @if(Auth::user()->library_nam !="")
                <span class="icon">{{ strtoupper(substr(Auth::user()->library_name, 0, 2)) }}</span>
                @endif
                Welcome
                <a class="dropdown-toggle uppercase" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{Auth::user()->library_name}}
                    {{Auth::user()->name}}
                </a>
                <ul class="dropdown-menu">

                    <li>
                        <img src="{{ url('public/img/user.png') }}" alt="profile" class="LibraryProfile">
                    </li>
                    @if(Auth::guard('library')->check())
                    <li>
                        <a class="dropdown-item text-center" href="javascript:;">
                            <small class="text-danger">Library Unique Id</small><br>
                            {{Auth::user()->library_no ?? ''}}</a>
                    </li>
                    <!-- Change Password -->
                    <li>
                        <a class="dropdown-item" href="{{route('change.password')}}">
                            <i class="fas fa-key fa-sm fa-fw mr-2 text-gray-400"></i>
                            Change Library Password
                        </a>
                    </li>
                    @endif


                    <!-- Logout -->
                    <li>
                        <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                            Logout
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </a>
                    </li>
                </ul>
            </div>

        </div>
       

    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.dropdown-item').forEach(function(notificationItem) {
            notificationItem.addEventListener('click', function(e) {
                const notificationId = this.getAttribute('data-notification-id');

                if (notificationId) {
                    fetch('{{ route("notifications.markAsRead") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                notification_id: notificationId
                            })
                        }).then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Notification marked as read.');
                            }
                        }).catch(error => console.error('Error:', error));
                }
            });
        });
    });
</script>