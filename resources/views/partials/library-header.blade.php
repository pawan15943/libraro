
@php
$user = getAuthenticatedUser();

@endphp

@if(!empty($primary_color))
<style>
    :root {
        --c1: {{ $primary_color ? $primary_color : '#151F38'}} ;
    }
</style>
@else
<style>
    :root {
        --c1: #151F38;
    }
</style>
@endif


<!-- Expiry Warning -->
<div class="modal" id="planExpiryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="btn-close align-self-right" data-bs-dismiss="modal" aria-label="Close"></button>
                <img src="{{ url('public/img/plan-expire.png') }}" alt="plan-expire" class="plan-expire img-fluid">
                @if(isset($librarydiffInDays) && $librarydiffInDays > 0)
                    <p class="text-success text-center">
                        Your library plan will expire in {{ $librarydiffInDays }} day{{ $librarydiffInDays > 1 ? 's' : '' }}. Please consider renewing your plan!
                    </p>
                @elseif(isset($librarydiffInDays) && $librarydiffInDays == 0)
                    <p class="text-warning text-center text-bold">
                        Your library plan expires today. Please consider renewing your plan!
                    </p>
                @elseif(isset($inExtension_lib) && $inExtension_lib && $diffInExtensionDays > 0)
                    <p class="text-warning text-center">
                        Your plan has expired. Your {{$lib_extenday}}-day extension is active and will end in {{ $diffInExtensionDays }} day{{ $diffInExtensionDays > 1 ? 's' : '' }}.
                    </p>
                @elseif(isset($inExtension_lib) && $inExtension_lib && $diffInExtensionDays == 0)
                    <p class="text-danger text-center">
                        Your plan has expired. Your {{$lib_extenday}}-day extension ends today. Please renew your plan to avoid deactivation.
                    </p>
                @else
                    <p class="text-danger text-center">
                        Your library plan and extension expired {{ abs($diffInExtensionDays) }} day{{ abs($diffInExtensionDays) > 1 ? 's' : '' }} ago. Please renew your plan to regain access.
                    </p>
                @endif

                <button type="button" class="btn btn-primary button m-auto w-100" data-bs-dismiss="modal" aria-label="Close">Renew your Subscription</button>
            </div>
        </div>
    </div>
</div>
<!-- Expiry Warning Ends -->

{{-- <div class="modal" tabindex="-1" id="todayrenew">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- <div class="modal-header">
                
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div> -->
            <div class="modal-body text-center p-4">
                <h5 class="modal-title text-center mb-3">🎉 Congratulations, Library Owner!</h5>
                <p>
                    Your upcoming plan is ready. Activate it now to keep enjoying all our services!</p>
                <button id="renewButton" type="button" class="btn btn-primary button w-50" onclick="renewPlan()">Activate Now</button>
            </div>
           
        </div>
    </div>
</div> --}}

<!-- All learner status update -->
 {{-- @if(!empty($showDailyPopup) && $showDailyPopup)
<div class="modal show d-block" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Daily Confirmation</h5>
            </div>

            <div class="modal-body">
                <p>Please confirm to continue using the library today.</p>
            </div>

            <div class="modal-footer">
                <form method="POST" action="{{ route('library.daily-popup.confirm') }}">
                    @csrf
                    <button class="btn btn-primary w-100">
                        I Understand, Continue
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endif --}}



<div class="header">
    <div class="d-flex" style="gap:1rem">
        <div class="conatent flex" style="flex: 1;">
            <i class="fa fa-bars mr-2" id="sidebar"></i>
           
           

            
            @if ($librarydiffInDays > 0)
                    <small class="text-success ml-2">
                        <i class="fa fa-clock"></i> Enjoy your plan for the next {{ $librarydiffInDays }} day{{ $librarydiffInDays > 1 ? 's' : '' }}!
                    </small>
            @elseif(isset($librarydiffInDays) && $user && !$is_renew && $anyTranLib)
                @if ($librarydiffInDays == 0)
                    <small class="text-warning ml-2">
                        <i class="fa fa-clock"></i> Plan expires today
                    </small>
                @elseif ($inExtension_lib && $diffInExtensionDays > 0)
                    <small class="text-warning ml-2">
                        <i class="fa fa-clock"></i> Library expired. {{$lib_extenday}}-day extension ends in {{ $diffInExtensionDays }} day{{ $diffInExtensionDays > 1 ? 's' : '' }}.
                    </small>
                @elseif ($inExtension_lib && $diffInExtensionDays == 0)
                    <small class="text-danger ml-2">
                        <i class="fa fa-clock"></i> Library expired. {{$lib_extenday}}-day extension ends today.
                    </small>
                @else
                    <small class="text-danger ml-2">
                        {{-- <i class="fa fa-clock"></i> Plan expired {{ abs($diffInExtensionDays) }} day{{ abs($diffInExtensionDays) > 1 ? 's' : '' }} ago --}}
                        <i class="fa fa-clock"></i> Plan expired {{ abs($librarydiffInDays) }} day{{ abs($librarydiffInDays) > 1 ? 's' : '' }} ago

                    </small>
                @endif


               
            @elseif(isset($upcomingdiffInDays) && $user && $is_renew )
                @if($upcomingdiffInDays > 0)
                <small class="text-danger ml-2"> <i class="fa fa-clock"></i>
                    Upcoming Plan after {{$upcomingdiffInDays}} days
                </small>
                @endif

            @endif
             @if(($librarydiffInDays <= 5 && !$is_renew && $is_expire))
               
                <script>
                    window.onload = function() {
                    if (!sessionStorage.getItem("planExpiryModalShown")) {
                    setTimeout(function() {
                    var modal = new bootstrap.Modal(document.getElementById('planExpiryModal'));
                    modal.show();
                    sessionStorage.setItem("planExpiryModalShown", "true");
                    }, 1000);
                    }
                    };
                </script>
                <a href="{{ route('subscriptions.choosePlan') }}" type="button" class="btn btn-primary button">Renew your plan</a>
            @endif
        </div>

        @if(countBranch() > 0)
            <form action="{{ route('branch.switch') }}" method="POST" >
                @csrf
                <select name="branch_id" onchange="this.form.submit()" class="form-control-sm form-select">
               
                    <option>Select Branch</option>
                   @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $user->current_branch == $b->id ? 'selected' : '' }}>
                            {{ trim($b->display_name) !== '' ? $b->display_name : $b->name }}
                        </option>
                    @endforeach

                </select>
            </form>
         
        @endif
      

        @if(isset($user->unreadNotifications))
        <div class="notification">
            <div class="dropdown">
                
                @php
              
              
                if(Auth::guard('library')->user()){
                    $guard='library';
                }elseif(Auth::guard('library_user')->user()){
                    $guard='library_user';
                }else{
                    $guard =null;
                }
                
                $unreadNotifications = $user->unreadNotifications->where('data.guard', $guard);
                
                @endphp
                <a class="dropdown-toggle uppercase" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fa-fw"></i>
                    <span class="badge badge-danger badge-counter">{{ $unreadNotifications->count() }}</span>
                </a>
                <ul class="dropdown-menu notificcation">
                    <li>
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
                            <a class="dropdown-item text-center small text-gray-500" href="{{ route('list.notification') }}">Show All Alerts</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        @endif


        <div class="profile">
            <div class="dropdown">
                
                <div class="dropdown">
                    {{-- Mobile view: icon dropdown --}}
                   
                    <div class="d-block d-md-none">
                        <a class="dropdown-toggle uppercase" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="icon">{{ strtoupper(substr($user->library_name, 0, 2)) }}</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <img src="{{ url('public/img/user.png') }}" alt="profile" class="LibraryProfile">
                            </li>

                            @if(Auth::guard('library')->user() || Auth::guard('library_user')->user())
                            <li>
                                <a class="dropdown-item text-center" href="javascript:;">
                                    <small class="text-danger">Library Unique Id</small><br>
                                    {{ $user->library_no ?? '' }}
                                </a>
                            </li>
                    
                            <li>
                                <a class="dropdown-item" href="{{ route('change.password') }}">
                                    <i class="fas fa-key fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Change Library Password
                                </a>
                            </li>
                            @endif
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

                    {{-- Desktop view: icon and text dropdown --}}
                    <div class="d-none d-md-flex align-items-center gap-2">
                        <span class="icon">{{ strtoupper(substr($user->library_name, 0, 2)) }}
                           @if(Auth::guard('library_user')->user())
                                {{ strtoupper(substr(getLibrary()->library_name, 0, 2)) }}
                            @endif
                        </span>
                        <a class="dropdown-toggle uppercase" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ $user->library_name }} {{ $user->name }}
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                @if(Auth::guard('library_user')->user())
                                <img src="{{ !empty($user->profile_picture) ? asset('storage/app/public/' . $user->profile_picture) : asset('public/img/user.png') }}" alt="profile" class="LibraryProfile">

                                @else
                                    
                                <img src="{{ url('public/img/user.png') }}" alt="profile" class="LibraryProfile">
                                @endif
                            </li>

                            @if(Auth::guard('library')->user() || Auth::guard('library_user')->user())
                            <li>
                                <a class="dropdown-item text-center" href="javascript:;">
                                    <small class="text-danger">Library Unique Id</small><br>
                                    {{ $user->library_no ?? '' }}
                                     @if(Auth::guard('library_user')->user())
                                    {{getLibrary()->library_no}}
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('change.password') }}">
                                    <i class="fas fa-key fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Change Library Password
                                </a>
                            </li>
                            @endif
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
        
        @if(isset($today_renew) && $today_renew==true)
        <script>
            window.onload = function() {
                setTimeout(function() {
                    var modal = new bootstrap.Modal(document.getElementById('todayrenew'));
                    modal.show();
                }, 1000);
            };

            function renewPlan() {
                document.getElementById('renewButton').disabled = true;

                $.ajax({
                    url: "{{ route('renew.configration') }}",
                    type: 'GET',
                    success: function(response) {
                        alert("Plan successfully renewed!");
                        var modal = bootstrap.Modal.getInstance(document.getElementById('todayrenew'));
                        modal.hide();
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error("Error renewing plan:", error);
                        alert("Failed to renew the plan. Please try again later.");
                    },
                    complete: function() {
                        document.getElementById('renewButton').disabled = false;
                    }
                });
            }
        </script>
        @endif
    </div>
</div>
