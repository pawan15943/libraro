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

        <!--Notifications -->
        @if(isset(auth()->user()->unreadNotifications))


        <div class="notification">
            <div class="dropdown">
                @php
                $guard = null;
                if (Auth::guard('web')->check()) {
                $guard = 'web';
                } elseif (Auth::guard('library')->check()) {
                $guard = 'library';
                } elseif (Auth::guard('learner')->check()) {
                $guard = 'learner';
                }

                $unreadNotifications = auth()->user()->unreadNotifications->where('data.guard', $guard);

                @endphp
                <a class="dropdown-toggle uppercase" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fa-fw"></i>
                    <!-- Counter - Alerts -->
                    <span class="badge badge-danger badge-counter">{{ $unreadNotifications->count() }}</span>
                </a>
                <ul class="dropdown-menu notificcation">
                    <li>
                        <!-- Dropdown - Alerts -->
                        <div class="dropdown-menu-1" aria-labelledby="alertsDropdown">
                            <h6 class="dropdown-header">Notification Center</h6>

                            @forelse($unreadNotifications as $notification)
                            <a class="dropdown-item d-flex align-items-center" data-notification-id="{{ $notification->id }}" href="{{ $notification->data['link'] ?? '#' }}">
                                <div class="mr-3">
                                    <div class="icon-circle bg-primary">
                                        <i class="fas fa-file-alt text-white"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="small text-gray-500">{{ $notification->data['title'] ?? 'No Title' }}</div>
                                </div>
                            </a>
                            @empty
                            <a class="dropdown-item text-center small text-gray-500">No new notifications</a>
                            @endforelse
                            <a class="dropdown-item text-center small text-gray-500" href="{{route('list.notification')}}">Show All Alerts</a>
                        </div>
                    </li>

                </ul>
            </div>

        </div>
        @else

        @endif
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
        @if($today_renew)
        <script>
            window.onload = function() {
                setTimeout(function() {
                    var modal = new bootstrap.Modal(document.getElementById('todayrenew'));
                    modal.show();
                }, 1000);
            };

            // Function to call renewConfigration via AJAX
            function renewPlan() {
                // Disable the button to avoid multiple clicks
                document.getElementById('renewButton').disabled = true;

                // Call the renew configuration function via AJAX
                $.ajax({
                    url: "{{ route('renew.configration') }}",
                    type: 'GET', // Change this to 'POST' if using POST method
                    success: function(response) {
                        // Show success message
                        alert("Plan successfully renewed!");

                        // Optionally close the modal after success
                        var modal = bootstrap.Modal.getInstance(document.getElementById('todayrenew'));
                        modal.hide();
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        // Handle error
                        console.error("Error renewing plan:", error);
                        alert("Failed to renew the plan. Please try again later.");
                    },
                    complete: function() {
                        // Re-enable the button
                        document.getElementById('renewButton').disabled = false;
                    }
                });
            }
        </script>
        @endif

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