<div id="loader">
    <div class="spinner"></div>
</div>
@php
$user = getAuthenticatedUser();

@endphp
<style>
        @php  if(!empty($primary_color)) @endphp
        :root {
            --c1: {{ $primary_color ? $primary_color : '#151F38'  }};
        }
        </style>




<div class="header learner">
    <div class="d-flex" style="gap:1rem">
        <div class="conatent flex" style="flex: 1;">
            <i class="fa fa-bars mr-2" id="sidebar"></i>
         
               <!-- learner  -->   

            @if(isset($diffExtendDay) && Auth::guard('learner')->check() && !$learner_is_renew )
                   
              
                @if ($diffInDays < 0 && $diffExtendDay>0)
                <h5 class="text-danger fs-10 d-block ">Enjoy your plan in extend {{ abs($diffExtendDay) }} days.</h5>
                @elseif ($diffInDays < 0 && $diffExtendDay==0)
                <small class="text-danger ml-2"> <i class="fa fa-clock"></i> Plan expires today </small>
                @elseif($diffExtendDay > 0)
                <small class="text-success ml-2"> <i class="fa fa-clock"></i> Enjoy your plan for the next {{$diffExtendDay}} days!</small>
                @else
                <small class="text-danger ml-2"><i class="fa fa-clock"></i> Plan expired {{ abs($diffExtendDay) }} days ago </small>

                @endif
            @endif

        </div>

      
        <!-- old popup position -->
        
        
        <!-- Notifications Dropdown -->
        @php
            $authUser = Auth::guard('learner')->user() ?? Auth::guard('library')->user() ?? Auth::user() ?? getAuthenticatedUser();
            $unreadNotifications = collect();
            $unreadCount = 0;
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
            }
        @endphp

        <!-- Notifications Dropdown -->
        @php
            $authUser = Auth::guard('learner')->user() ?? Auth::guard('library')->user() ?? Auth::user() ?? getAuthenticatedUser();
            $unreadNotifications = collect();
            $unreadCount = 0;
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
            }
        @endphp

        <div class="notification me-2">
            <div class="dropdown">
                <a class="btn btn-link position-relative p-2 text-decoration-none shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: #18225f;">
                    <i class="fas fa-bell fs-5"></i>
                    @if($unreadCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger border border-2 border-white p-1" style="width: 10px; height: 10px;">
                            <span class="visually-hidden">Unread notifications</span>
                        </span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-0 overflow-hidden mt-2" style="width: 360px; z-index: 1050; background: #ffffff;">
                    <!-- Dropdown Executive Header -->
                    <div class="px-3 py-3 text-white d-flex align-items-center justify-content-between" style="background-color: #18225f; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(255,255,255,0.12);">
                                <i class="fa-solid fa-bell text-white" style="font-size: 13px;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold font-outfit text-white" style="font-size: 0.95rem; letter-spacing: -0.2px;">Notifications</h6>
                                <small class="text-white-50" style="font-size: 11px;">
                                    {{ $unreadCount > 0 ? $unreadCount . ' unread alert' . ($unreadCount == 1 ? '' : 's') : 'All notifications read' }}
                                </small>
                            </div>
                        </div>
                        @if($unreadCount > 0)
                            <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm text-white border-0 font-outfit px-2.5 py-1 rounded-pill" style="font-size: 11px; background: rgba(255, 255, 255, 0.15); transition: background 0.2s ease;">
                                    <i class="fa-solid fa-check-double me-1" style="font-size: 10px;"></i> Mark all read
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Notifications List Group -->
                    <div class="list-group list-group-flush" style="max-height: 320px; overflow-y: auto;">
                        @forelse($unreadNotifications as $n)
                            @php
                                $nData = json_decode($n->data ?? '{}', true);
                            @endphp
                            <a href="{{ $nData['link'] ?? route('list.notification') }}" class="list-group-item list-group-item-action px-3 py-2.5 border-bottom text-decoration-none transition-all" style="background-color: #ffffff;">
                                <div class="d-flex align-items-start gap-2.5">
                                    <!-- Icon Box -->
                                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background-color: #e6f5f7; color: #34939F;">
                                        <i class="fa-solid fa-envelope-open-text" style="font-size: 14px;"></i>
                                    </div>
                                    
                                    <!-- Content -->
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-center justify-content-between gap-1 mb-0.5">
                                            <div class="fw-bold font-outfit text-truncate" style="color: #18225f; font-size: 13px; max-width: 210px;">
                                                {{ $nData['title'] ?? 'New Notification' }}
                                            </div>
                                            <span class="badge rounded-circle bg-danger p-1 flex-shrink-0" title="Unread" style="width: 6px; height: 6px;"></span>
                                        </div>
                                        <div class="text-secondary text-truncate" style="font-size: 11.5px; color: #64748b; line-height: 1.35; max-width: 230px;">
                                            {{ $nData['description'] ?? '' }}
                                        </div>
                                        <div class="d-flex align-items-center gap-1 mt-1" style="font-size: 10.5px; color: #34939F; font-weight: 600;">
                                            <i class="fa-regular fa-clock" style="font-size: 10px;"></i>
                                            <span>{{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <!-- Executive Empty State -->
                            <div class="text-center py-4 px-3" style="background: #fafafa;">
                                <div class="mx-auto mb-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background-color: #e6f5f7; color: #34939F;">
                                    <i class="fa-solid fa-bell-slash fs-5"></i>
                                </div>
                                <h6 class="fw-bold font-outfit mb-1" style="color: #18225f; font-size: 0.92rem;">All Caught Up!</h6>
                                <p class="text-muted mb-0 font-outfit" style="font-size: 11px;">You have no unread notifications right now.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Dropdown Executive Footer -->
                    <div class="p-2.5 text-center border-top" style="background-color: #f8fafc;">
                        <a href="{{ route('list.notification') }}" class="fw-bold font-outfit text-decoration-none d-inline-flex align-items-center gap-1.5" style="color: #18225f; font-size: 12px; transition: color 0.2s ease;">
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
   
    @if(!$learnerupdates->isEmpty() && Auth::guard('learner')->check())

   
    <div class="latest-notification">
        <b>Updates :</b>
        @foreach($learnerupdates as $key => $value)
        <marquee behavior="" direction="left" class="m-0" scrollamount="5">{{$value->message}}</marquee>
   
        @endforeach
        <button onclick="closeNotification()" class="close">&times;</button>
    </div>
          
    @endif
   
    
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