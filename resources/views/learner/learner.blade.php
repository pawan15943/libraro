@extends('layouts.library')
@section('content')

<!-- Content Header (Page header) -->
<div class="modal fade" id="cf-modal" tabindex="-1" aria-labelledby="cf-modal-label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="cf-modal-label">Transactions</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="transactionPendingBadge" class="small text-muted mb-2 d-none"></p>
        <div class="table-responsive mb-4">
          <table class="table table-bordered table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Plan Price</th>
                <th>Other Addon Cost <span class="fw-normal text-muted small">(Locker | miscellaneous | token)</span></th>
                <th>Discount on Plan Price</th>
                <th>Paid Amt</th>
                <th>Pending Amt</th>
                <th>Payment Date</th>
                <th>Payment Mode</th>
                <th>Download Receipt</th>
              </tr>
            </thead>
            <tbody id="transactionTableBody">
              <tr>
                <td colspan="9" class="text-center text-muted">Open from a learner row to load data.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <h6 class="mb-2">All activity for this learner</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Particular</th>
                <th>Payment Type</th>
                <th>Payment Mode</th>
                <th>Amt.</th>
                <th>Payment Date</th>
                <th>Dr / Cr</th>
              </tr>
            </thead>
            <tbody id="activityTableBody">
              <tr>
                <td colspan="6" class="text-center text-muted">—</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
{{-- Large profile preview — keep display:flex for centering (jQuery fadeIn sets display:block and breaks layout) --}}
<div id="imageViewModal" class="image-modal" style="display:none;opacity:0;" aria-hidden="true">
    <div class="image-modal-content">
        <span class="close-modal" role="button" tabindex="0" aria-label="Close">&times;</span>
        <img src="" id="modalImage" alt="Profile photo preview">
    </div>
</div>

@php
$hasActiveFilters = request()->filled('search') || request()->filled('plan_id') || request()->filled('status')
    || request()->filled('seat_no') || request()->filled('payment_filter') || request()->filled('is_paid');
// Computed once per page load — these are branch/library-level settings,
// not per-learner, so they were previously being re-queried on every use
// (including once per row inside the loop below).
$hiddenFields = toggleHideField();
$currentBranchName = getCurrentBranchName();
$isNotificationActive = notificationActive();
$isWabaNotificationActive = $isNotificationActive && wabaNotificationActive();
$isTextNotificationActive = $isNotificationActive && textNotificationActive();
@endphp

<div class="row">
    <div class="col-lg-12 text-end">
        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Filter" id="filter"><i class="fa-solid fa-filter"></i></a>

        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Counts" id="counts"><i class="fa-solid fa-star"></i></a>
        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export"><i class="fa-solid fa-file-export"></i> Export All Data in CSV</a>

        <a href="{{ route('learners.list.pdf', request()->query()) }}" class="btn btn-primary export"
            target="_blank" data-bs-toggle="tooltip" data-bs-placement="bottom"
            data-bs-title="Download the currently filtered learner list as a PDF"><i
                class="fa-solid fa-file-pdf"></i> Download Learner List (PDF)</a>

        @can('has-permission', 'Export Library Seats')
        @if(!in_array('22', $hiddenFields))
        <a href="{{ route('learners.export-csv') }}" class="btn btn-primary export" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Export Learners Data to CSV"><i class="fa-solid fa-file-export"></i></a>
        @endif
        @endcan

        @can('has-permission', 'Import Library Seats')
        @if(!in_array('11', $hiddenFields))
        <a href="{{ route('library.upload.form') }}" class="btn btn-primary export bg-4" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Import Learners Data to Portal"><i class="fa-solid fa-file-import"></i></a>
        @endif
        @endcan
    </div>
</div>

@can('has-permission', 'Filter')
<div class="row mb-3" id="filterContainer">
    <div class="col-lg-12">
        <div class="filter p-3 bg-white">
            <form action="{{ route('learners') }}" method="GET">
                <div class="row g-3">
                    <div class="col-lg-3">
                        <input type="text" class="form-control" name="search" placeholder="Enter Name, Mobile or Email" value="{{ request()->get('search') }}">
                    </div>

                    <!-- Filter By Plan -->
                    <div class="col-lg-2">
                        <select name="plan_id" id="plan_id2" class="form-select">
                            <option value="">Choose Plan</option>
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ request()->get('plan_id') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter By Payment Status -->
                    {{-- <div class="col-lg-2">
                        <select name="is_paid" id="is_paid" class="form-select">
                            <option value="">Choose Status</option>
                            <option value="1" {{ request()->get('is_paid') == '1' ? 'selected' : '' }}>Paid</option>
                    <option value="0" {{ request()->get('is_paid') == '0' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div> --}}
                <!-- Filter By Active/Expired Status -->
                <div class="col-lg-2">
                    <select name="status" id="status" class="form-select">
                        <option value="">Choose Status</option>
                        <option value="active" {{ request()->get('status') == 'active' ? 'selected' : '' }}>Active
                        </option>
                        <option value="expired" {{ request()->get('status') == 'expired' ? 'selected' : '' }}>Expired
                        </option>
                        <option value="about_to_expire" {{ request()->get('status') == 'about_to_expire' ? 'selected' : '' }}>About to Expire
                        </option>
                        <option value="extended" {{ request()->get('status') == 'extended' ? 'selected' : '' }}>Extended
                        </option>
                    </select>
                </div>

                <!-- Filter By Pending Payment -->
                <div class="col-lg-2">
                    <select name="payment_filter" id="payment_filter" class="form-select">
                        <option value="">Choose Payment</option>
                        <option value="pending_payment" {{ request()->get('payment_filter') == 'pending_payment' ? 'selected' : '' }}>Pending Payment
                        </option>
                    </select>
                </div>

                    <!-- Filter By Seat No -->
                    <div class="col-lg-3">
                        <select name="seat_no" id="seat_search" class="form-select">
                            <option value="">Seat No</option>
                            @for($seatNo = 1; $seatNo <= $totalSeats; $seatNo++)
                            <option value="{{ $seatNo }}" {{ request()->get('seat_no') == $seatNo ? 'selected' : '' }}>
                                {{getSeatDisplayShortFloorName($seatNo)}}
                                </option>
                            @endfor
                        </select>
                    </div>

                <!-- Search Button -->
                <div class="col-lg-2 align-self-end">
                    <button class="btn btn-primary button" data-bs-toggle="tooltip" data-bs-placement="bottom"
                        data-bs-title="Search">
                        Search
                    </button>
                </div>
                <div class="col-lg-2 align-self-end">
                    <button type="button" id="clearFilter" class="btn btn-secondary button" data-bs-toggle="tooltip"
                        data-bs-placement="bottom" data-bs-title="Clear Filter">
                        Clear
                    </button>
                </div>


                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@if ( $learners->total()==0)
<div class="no-data-found">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>

    <dotlottie-wc src="https://lottie.host/2bd4f1dd-bce9-44cb-b8a4-f5acd681c123/sHuYyTQ6uD.lottie"
        style="width: 200px;height: 200px" autoplay loop></dotlottie-wc>
    @if($hasActiveFilters)
    <h4>No Learners Found</h4>
    <span>No learners match the selected filters. Try adjusting or clearing the filters above.</span>
    @else
    <h4>No Learner Added Yet</h4>
    <span> You haven’t added any learners to your library yet. Start adding learners by clicking the button
        below.</span>
    <!-- Masters -->
    <div class="heading-list justify-content-end mb-1">
        @if(getCurrentBranch() !=0)
        <a href="javascript:;" class="btn btn-primary export noseat_popup">
            <i class="fa-solid fa-plus "></i> Book Seat
        </a>
        @else
        <h4>To add Plan Prices, first select your Branch.</h4>
        <span> Plan names remain the same across all branches, but prices can be different. That’s why you need to
            choose the branch before adding plan prices.</span>
        @endif
    </div>
    @endif
</div>

@else
@if(!in_array('24', $hiddenFields))
<div class="col-lg-12 mb-4" id="countsContainer">
    <div class="records">

        <p class="mb-2 text-dark"><b>Total Seats : {{$total_seats}} | Available Seats : {{$availble_seats}} | Booked Seats: {{$booked_seats}} | General Seats: {{$genral_seat ?? 0}}</b></p>

        <span class="text-success">Total Available Slots ({{$availble_seats}})</span> <span class="text-success">Total Booked Slots ({{$active_seat_count}})</span> <span class="text-success">Total General Booked Slots ({{$genral_seat}})</span><span class="text-danger">Total Expired Slots({{$expired_seat}})</span> <span class="text-danger">Extended Slots({{$extended_seats}})</span>
        @foreach($planTypeCounts as $plan)
        <span class="text-danger">{{ $plan['abbr'] }}: {{ $plan['name'] }} ({{ $plan['count'] }})</span>
        @endforeach

    </div>
</div>
@endif



<div class="mb-3 set-table">
    <p class="m-0"><b>{{ $learners->total() }} Records for {{ $learners->perPage() }} per page</b></p>
    <a class="sort" href="{{ request()->fullUrlWithQuery([
        'sort_by' => 'seat_no',
        'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'
    ]) }}">
        Sort by Seat No.
        @if(request('sort_by') == 'seat_no')
        ({{ request('sort_order') == 'asc' ? '↑' : '↓' }})
        @endif
    </a>
</div>

@foreach($learners as $key => $value)

@php
$learner_detail_id=$value->learner_detail_id;
$planStatus = getPlanStatusDetails($value->plan_end_date);
// Batched in LearnerController::learnerList() / LearnerService::buildLearnerListRowContext()
// to avoid the N+1 queries each of these used to run per row.
$rowContextData = $rowContext[$learner_detail_id] ?? [];
$transaction = $rowContextData['transaction'] ?? null;
$totalPendingAmt = $rowContextData['total_pending'] ?? 0;
$totalExtraAmt = $rowContextData['total_extra'] ?? 0;
$paylaterFlag = $rowContextData['paylater'] ?? false;
$hasPendingAmtFlag = $rowContextData['has_pending_amt'] ?? false;
$paybleRefundAmt = $rowContextData['payble_refund'] ?? 0;
$overdueFlag = $rowContextData['overdue'] ?? false;
$isRenewUpdateFlag = $rowContextData['is_renew_update'] ?? false;
$canRenewFlag = $rowContextData['can_renew'] ?? true;
$statusPrecomputed = $rowContextData['status_precomputed'] ?? null;

$oneWeekLater = \Carbon\Carbon::parse($value->plan_start_date)->addWeek();


$due_date = $rowContextData['due_date'] ?? ($transaction->due_date ?? null);

$today = \Carbon\Carbon::now();
$threeDaysAfterStart = \Carbon\Carbon::parse($value->plan_start_date)->addDays(3);
$operationRow = $rowContextData['operation'] ?? null;
$operation = optional($operationRow)->operation;
$operationDate = optional($operationRow)->created_at;
$learner_id=$value->id;
@endphp

<!-- Modal -->
 
       

<div class="row">
    <div class="col-lg-12">
        <div class="seat-info bg-white">
            <div class="seat-no">

                @if($value->seat_no )
                <span> Seat No. : {{$value->seat_no ? getSeatDisplayShortFloorName($value->seat_no) : 'GEN'}} </span>
                @else
                <span> Seat No. : {{$value->seat_no ? getSeatDisplayShortFloorName($value->seat_no) : 'GEN'}} </span>
                @endif
                @if($operation == 'closeSeat')
                <span class="extended"> Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                @elseif($operation == 'deleteSeat' && $value->deleted_at !=null)
                <span class="extended"> Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                @else
                {!! getUserStatusWithSpan($value->plan_end_date,$learner_id,$statusPrecomputed) !!}
                @endif



            </div>
            <div class="seat-actions">
                <ul>
                    <li><a href="https://wa.me/+91{{ $value->mobile }}?text={{ urlencode('Hello! Please click the link below to access the Attendance App and mark your attendance securely. ' . route('qr.attendance.link')) }}"
                        target="_blank"
                        class="w-auto px-2">
                        <i class="fa-solid fa-share" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="bottom" 
                            data-bs-title="Share Attendance Link on WhatsApp">
                        </i>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('attendance.summary',$learner_id) }}" title="View Attendace Detials">
                            <i class="fa-solid fa-clipboard-user"></i>
                        </a>
                    </li>
                    {{-- @can('has-permission', 'Activity') --}}
                    <li>
                        <a href="{{ route('activities.all', ['learner_id' => $learner_id]) }}" title="View Learner Activity">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </a>
                    </li>
                    {{-- @endcan --}}
                    <!-- Edit Seat Info -->
                    @if($planStatus['diff_extend_day'] >= 0)

                        {{-- <li><a href="{{route('learner.expire',$value->id)}}" title="Custom Seat Expire"><i class="fas fa-calendar"></i></a></li> --}}

                        @if($overdueFlag && ($totalPendingAmt > 0 || ($transaction && (float)($transaction->pending_amount ?? 0) > 0)) && !empty($due_date))
                        <li>
                            <a class="" target="_blank"
                                data-bs-placement="bottom"
                                data-bs-toggle="tooltip"
                                data-bs-title="Send Pending Payment Reminder"
                                href="https://wa.me/+91{{ $value->mobile }}?text={{ rawurlencode(
                                    'Dear ' . $value->name . ",\n\n" .
                                    'This is a gentle reminder that your library seat payment is still pending.' . "\n\n" .
                                    'Your due date was ' . \Carbon\Carbon::parse($due_date)->format('d-m-Y') . '. To avoid seat cancellation, please complete the payment at the earliest.' . "\n\n" .
                                    'Pending Amount: ₹' . number_format($totalPendingAmt > 0 ? $totalPendingAmt : (float)($transaction->pending_amount ?? 0), 2) . "\n\n" .
                                    'If you have already made the payment, kindly ignore this message.' . "\n\n" .
                                    'For any assistance, feel free to contact our support team.' . "\n\n" .
                                    '– Team ' . $currentBranchName
                                ) }}">
                                    <i class="fa-solid fa-business-time"></i>
                            </a>
                        </li>
                        @endif

                        @if($canRenewFlag && $value->frozen_status != 1 && $planStatus['diff_in_days'] <= 5)
                            @can('has-permission','Renew Seat')
                            <li>
                                <a class="renew_extend w-auto px-2" data-seat_no="{{$value->seat_no}}"  data-user="{{$learner_id}}" data-end_date="{{$value->plan_end_date}}" data-learner_detail="{{$learner_detail_id}}">Renew</a>
                                {{-- <a href="{{route('learner.renew.plan',$value->id)}}" title="Renew Plan" >Renew</a> --}}
                            </li>
                            @endcan
                        @endif


                        {{-- @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']> 0 ) --}}
                            <!-- Sent Mail -->

                            @can('has-permission', 'WhatsApp Notification')
                                @php
                                    $sendPref = $value->sended_message_type ?? 'no';
                                    $seatNo = (!empty($value->seat_no) && $value->seat_no != 0) ? $value->seat_no : 'GEN';
                                    if ($planStatus['class']=='extedned') {
                                        $reminderMessage = "Dear {$value->name}(Seat No-{$seatNo}),\n\nYour plan expired on".changeFormate($value->plan_end_date).".\n\nPlease renew it as soon as possible to continue uninterrupted access to your library seat.\nYou are currently in the extension period — after this, your seat may be allotted to another learner.\n\nFor help, feel free to contact our support team.\n\n– Team " . $currentBranchName;
                                    } else {
                                        $reminderMessage = "Dear {$value->name}(Seat No-{$seatNo}),\n\nYour plan expired on ".changeFormate($value->plan_end_date).".\n\nPlease renew it as soon as possible to continue uninterrupted access to your library seat.\n\nFor help, feel free to contact our support team.\n\n– Team " . $currentBranchName;
                                    }

                                    $freeWabaLink = "https://wa.me/+91{$value->mobile}?text=" . rawurlencode($reminderMessage);
                                    $freeTextLink = "sms:+91{$value->mobile}?body=" . rawurlencode($reminderMessage);
                                @endphp

                               

                                {{-- Paid (Twilio-backed) reminder icon(s) — additive, only while the library has active credits --}}
                                @if($isNotificationActive)
                                    @if($sendPref == 'both' && $isWabaNotificationActive && $isTextNotificationActive)
                                        <li>
                                            <a href="javascript:;" class="w-auto px-2 open-reminder-chooser"
                                                data-learner_id="{{$learner_id}}"
                                                data-bs-toggle="modal" data-bs-target="#sendReminderChooserModal"
                                                data-bs-placement="bottom" title="" data-original-title="Send Reminder (Paid)">
                                                <i class="fa fa-ellipsis-v" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Send Reminder (Paid)"></i>
                                            </a>
                                        </li>
                                    @else
                                        @if($isWabaNotificationActive && in_array($sendPref, ['whatsapp', 'both']))
                                            <li>
                                                <a target="_blank" href="javascript:;"
                                                    data-bs-toggle="modal" class="open-waba"

                                                    data-learner_id="{{$learner_id}}"
                                                    data-bs-target="#wabaSendModel" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="WhatsApp Reminders (Paid)">
                                                    <i class="fab fa-whatsapp" style="color:#25D366" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Send Reminder (Paid)"></i>
                                                </a>
                                            </li>
                                        @endif

                                        @if($isTextNotificationActive && in_array($sendPref, ['text', 'both']))
                                            <li>
                                                <a target="_blank" href="javascript:;" data-bs-toggle="modal"
                                                    data-learner_id="{{$learner_id}}" class="open-text"
                                                    data-bs-target="#textSendModel" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Text Message Reminders (Paid)">
                                                    <i class="fa fa-message" style="color:#0d6efd" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Send Text Reminder (Paid)"></i>
                                                </a>
                                            </li>
                                        @endif
                                    @endif
                                @else
                                     {{-- Free (no-API) reminder icon(s) — driven by the learner's own "Send Reminders Via" choice --}}
                                    
                                    
                                    @if($sendPref == 'text')
                                        <li>
                                            <a class="w-auto px-2" href="{{ $freeTextLink }}">
                                                <i class="fa fa-message" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Send Text Reminder"></i>
                                            </a>
                                        </li>
                                    @elseif($sendPref == 'both')
                                        <li>
                                            <a href="javascript:;" class="w-auto px-2 open-reminder-chooser-free"
                                                data-waba-link="{{ $freeWabaLink }}"
                                                data-text-link="{{ $freeTextLink }}"
                                                data-bs-toggle="modal" data-bs-target="#sendReminderChooserFreeModal"
                                                data-bs-placement="bottom" title="" data-original-title="Send Reminder">
                                                <i class="fa fa-comment-dots" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Send Reminder"></i>
                                            </a>
                                        </li>
                                    @else
                                        <li>
                                            <a class="w-auto px-2" target="_blank" href="{{ $freeWabaLink }}">
                                                <i class="fab fa-whatsapp" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Send Reminder"></i>
                                            </a>
                                        </li>
                                    @endif
                                @endif
                            @endcan

                            <!-- Sent Mail -->
                            {{-- @can('has-permission', 'Email Notification')
                                <li><a href="mailto:{{$value->email }}?subject=Library Seat Renewal Reminder&body=Hey!%20🌟%0D%0A%0D%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0D%0A%0D%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="11" onclick="incrementMessageCount({{ $value->id }}, 'email')" class="message" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Send Email Reminders"><i class="fas fa-envelope"></i> Send Reminder</a></li>
                            @endcan --}}
                        {{-- @endif --}}
                            <!-- Swap Seat-->

                        @can('has-permission', 'Swap Seat')
                            @if($value->frozen_status != 1)
                            
                            <li><a href="{{route('learners.swap',$value->id)}}" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Swap Seat"><i class="fa-solid fa-arrow-right-arrow-left"></i></a></li>
                            @endif
                        @endcan


                        @can('has-permission', 'Change Plan')
                            @if(!in_array('14', $hiddenFields) && !$today->greaterThanOrEqualTo($oneWeekLater) && $value->frozen_status != 1)
                            <li><a href="{{route('learner.change.plan',$value->id)}}" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Change Plan"><i class="fa fa-arrow-up-short-wide"></i></a></li>
                            @endif

                        @endcan
                        <!---ID Card generate-->
                        {{-- @if(!in_array('15', $hiddenFields))

                        <li data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Genrate ID Card">
                        <form action="{{ route('generateIdCard') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="custId" name="detail_id" value="{{ $learner_detail_id }}">
                        <input type="hidden" name="learner_id" value="{{ $value->id }}">
                        <button type="submit"><i class="fa-solid fa-id-card-clip"></i></button>
                        </form>
                        </li>
                        @endif --}}

                        @can('has-permission', 'Genrate ID Card')
                            @if(!in_array('15', $hiddenFields))
                            <li><a target="_blank" href="{{ route('idCard',  $learner_detail_id) }}" class="" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Genrate ID Card"><i class="fa-solid fa-id-card-clip"></i> </a></li>
                            @endif
                        @endcan
                        <!-- upgrade Seat-->
                        <!-- (&& $planStatus['diff_extend_day'] <= 5) we remove this block -->
                        @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']>= 0 )
                            @can('has-permission', 'Upgrade Seat Plan' )
                                @if(!in_array('13', $hiddenFields) && $value->frozen_status != 1)
                                <li><a href="{{route('learners.upgrade',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Upgrade Plan"><i class="fa-solid fa-circle-up"></i></a></li>
                                @endif
                            @endcan

                        @endif
                        <!-- Close Seat -->

                        @can('has-permission', 'Close Seat')
                            @if(!in_array('16', $hiddenFields) && $value->frozen_status != 1) 
                            <li><a href="javascript:void(0);" class="link-close-plan" data-id="{{$value->id}}" data-learnerDetail="{{ $learner_detail_id }}" data-learner_detail_id="{{$learner_detail_id}}" data-payblerefund="{{ $paybleRefundAmt }}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Close Plan" data-plan_end_date="{{$value->plan_end_date}}"><i class="fas fa-times"></i></a></li>
                            @endif
                        @endcan
                    @endif

                    @can('has-permission', 'Reactive Seat')
                    @if($value->status==0 && $value->frozen_status != 1)
                    <li><a href="{{route('learners.reactive',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Reactivate Learner"><i class="fa-solid fa-arrows-rotate"></i></a></li>
                    @endif
                    @endcan

                    @can('has-permission', 'Add Misllaneous Payment')
                    <li><a href="{{route('learner.other.payment',$learner_detail_id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Other Payment" class="payment-learner"><i class="fa-solid fa-money-bill"></i></a></li>
                    @endcan
                    <li>
                        <a href="{{ route('learners.transactions', $learner_id) }}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Transactions">
                            <i class="fa-solid fa-wallet"></i>
                        </a>
                    </li>
                    @if ($totalPendingAmt > 0 || $totalExtraAmt > 0 || ($transaction && ((float)($transaction->pending_amount ?? 0) > 0 || (float)($transaction->refund ?? 0) > 0)))
                    <li><a href="#" data-id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Settlement" class="settlement-learner"><i class="fa-solid fa-scale-balanced"></i></a></li>
                    @endif
                    @can('has-permission', 'Gift Days')
                    @if(!in_array('33', $hiddenFields) && $value->frozen_status != 1)
                    <li><a href="javascript:;" class="giftDaysBtn" data-learner_id="{{$learner_id}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Gift Days"><i class="fa-solid fa-gift"></i></a></li>
                    @endif
                    @endcan

                    @can('has-permission', 'Freez Days')
                    @if(!in_array('34', $hiddenFields))
                    @if($value->frozen_status == 1 || $planStatus['diff_in_days'] >= 0)
                    <li><a href="javascript:;" class="freezDaysBtn" data-status="{{$value->frozen_status}}" data-learner_id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" > 
                        @if($value->frozen_status == 1)
                        <i class="fa-solid fa-pause" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Unfreeze Plan"></i>
                        @else
                        
                        <i class="fa-solid fa-snowflake" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Freeze Plan"></i>
                        @endif 
                        </a>   
                    </li>
                    @endif
                    @endif
                    @endcan
                    <!-- View Seat Info -->
                    @can('has-permission', 'View Seat')
                    <li><a href="{{route('learners.show',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="View Seat Booking Full Details"><i class="fas fa-eye"></i></a></li>
                    @endcan

                    <!-- Deletr Seat -->

                    @can('has-permission', 'Edit Seat')
                    @if(!in_array('17', $hiddenFields) && $value->frozen_status != 1)
                    
                    <li><a href="{{route('learners.edit',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Edit Seat Booking Details"><i class="fas fa-edit"></i></a></li>
                    @if($today->lessThanOrEqualTo($threeDaysAfterStart))
                        <li><a href="{{route('learners.edit.plan',$value->id)}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Edit Plan Details"><i class="fa-solid fa-calendar-days"></i></a></li>
                    @endif
                    @endif
                    @endcan

                    @can('has-permission', 'Delete Seat')
                    <li><a href="#" data-id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{$value->seat_no}}" data-payblerefund="{{ $paybleRefundAmt }}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Delete Lerners" class="delete-customer"><i class="fas fa-trash"></i></a></li>
                    @endcan
                    {{-- @can('has-permission', 'Delete Seat')
                    @if($today->lessThanOrEqualTo($threeDaysAfterStart))
                        <li><a href="#" data-id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" data-permanent="1" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Permanent Delete Lerners" class="delete-permanent-customer"><i class="fas fa-trash text-danger"></i></a></li>
                    @endif
                    @endcan --}}
                    <li>
                        <a target="_blank"
                            href="https://wa.me/+91{{ $value->mobile }}?text={{ whatsappReceiptMessage($value, $transaction, $currentBranchName) }}">
                            <i class="fa-solid fa-receipt"
                                data-bs-toggle="tooltip"
                                data-bs-title="Send Receipt"></i>
                        </a>
                    </li>

                </ul>
            </div>
            <div class="seat-informarion">
               
                @if($value->profile_picture)
                <a href="{{ asset($value->profile_picture) }}" class="view-image learner-list-profile-photo" title="View profile photo">
                    <img src="{{ asset($value->profile_picture) }}" alt="profile">
                </a>
                @else
                <img src="{{ asset('public/img/student_profile.jpeg') }}" alt="profile">
                @endif

                <div class="information">
                    <h4>{{$value->name}}
                        @if($operation == 'closeSeat')
                        <span class="extended">Closed</span>
                        @elseif($operation == 'deleteSeat' && $value->deleted_at !=null)
                        <span class="extended">Deleted</span>
                        @elseif((int) $value->no_expiry === 1 && (int) $value->status === 1 && ($planStatus['status']=='About to Expire' || $planStatus['status']=='In Extension' || $planStatus['status']=='Extension ends today'))
                         <span class="actives">Active</span>
                        @elseif((!empty($value->plan_start_date) && \Carbon\Carbon::parse($value->plan_start_date)->isFuture() && (int)($value->status ?? 0) === 0) || (($statusPrecomputed['has_future_start'] ?? false) && !($statusPrecomputed['has_past_plan'] ?? false)))
                        <span style="color: #C09600; font-weight: 600;" class="ps-1">Upcoming</span>
                        @else
                        <span class="{{$planStatus['class']}} ps-1">{{$planStatus['status']}}</span>
                        @endif
                    </h4>
                    <span>UID : <a href="{{route('learners.show',$value->id)}}">{{$value->learner_no}}</a> &nbsp; | &nbsp; M : <a href="tel:+91-{{$value->mobile}}">+91-{{ display_learner_mobile($value->mobile) }}</a> </span>
                    <span class="d-block">E: <a href="mailto:{{$value->email}}"> @if($value->email) {{ display_learner_email($value->email) }} @else <i class="fa-solid fa-times text-danger"></i> Email ID Not Available @endif </a></span>
                </div>
            </div>
            <div class="plan-info">
                <ul>
                    <!-- Plan Details -->
                    <li>
                        <span>Plan</span>
                        <p>{{$value->plan_name??''}}</p>
                    </li>
                    <li>
                        <span>Plan Type</span>
                        <p>{{$value->plan_type_name ?? ''}}</p>
                    </li>
                    <li>
                        <span>Plan Start Date</span>
                        <p>{{ $value->plan_start_date ? date('j M Y', strtotime($value->plan_start_date)) : '' }}</p>

                    </li>
                    <li>
                        <span>Plan End Date</span>
                        <p>
                        @if($value->frozen_status != 1)
                            {{ $value->plan_end_date ? date('j M Y', strtotime($value->plan_end_date)) : '' }}
                        @endif
                        </p>
                    </li>
                    <li>
                        <span>Payment Status</span>

                        <div class="d-flex g-1">
                            @if($paylaterFlag && $transaction?->pending_amount!=0)
                            <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="text-danger d-block settlement-learner">
                                <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">
                                    PayLater  {{ rtrim(rtrim(number_format(   ($totalPendingAmt), 2, '.', ''), '0'), '.') }}
                                </span>
                            </a> &nbsp;<a href="javascript:;" class="open-transaction-modal" data-learner_id="{{ $learner_id }}" data-bs-toggle="modal" data-bs-target="#cf-modal" style="font-size: .8rem;"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                            @elseif(!empty($transaction?->pending_amount) && $transaction?->pending_amount==0)
                            <span class="payment" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Fully Paid</span>


                            @elseif(empty($transaction?->pending_amount))
                            <span></span>

                            @elseif( $hasPendingAmtFlag)
                            <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="text-danger d-block settlement-learner">
                                 @if( !empty($due_date) && $overdueFlag)
                                <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Due {{ rtrim(rtrim(number_format(   ($totalPendingAmt), 2, '.', ''), '0'), '.') }} ({{date('j M', strtotime($due_date))}})</span>
                                @else
                                <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">
                                    Due {{ rtrim(rtrim(number_format(($totalPendingAmt), 2, '.', ''), '0'), '.') }} ({{ !empty($due_date) ? date('j M', strtotime($due_date)) : ''}})

                                </span>

                                @endif
                            </a> &nbsp;<a href="javascript:;" class="open-transaction-modal" data-learner_id="{{ $learner_id }}" data-bs-toggle="modal" data-bs-target="#cf-modal" style="font-size: .8rem;"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                            @endif

                            @if ($transaction?->id)
                            <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data" target="_blank">
                                @csrf
                                <input type="hidden" name="learner_id" value="{{$learner_id}}">
                                <input type="hidden" name="id" value="{{($transaction->id ?? 0)}}">
                                <input type="hidden" name="learner_detail_id" value="{{$learner_detail_id}}">
                                <input type="hidden" name="type" value="learner">
                                <button type="submit" class="noLoader">
                                    <i class="fa fa-download receipt"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </li>
                    <li>
                        <span>Locker</span>
                        @if($transaction && $transaction->locker_amount)
                        <p>Yes – #{{ $transaction->locker_amount }} Paid</p>
                        @else
                        <p>No</p>
                        @endif

                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endforeach

{{-- Pagination --}}


@if ($learners->lastPage() > 1)
<ul class="paginations mt-4">
    {{-- Prev --}}
    <li>
        <a href="{{ $learners->onFirstPage() ? '#' : $learners->previousPageUrl() }}" class="w-auto px-3 text-muted">Prev</a>
    </li>

    {{-- Page Numbers (shortened: 1 ... current ... last) --}}
    @if ($learners->currentPage() > 3)
    <li><a href="{{ $learners->url(1) }}">1</a></li>
    <li><span>...</span></li>
    @endif

    @for ($i = max(1, $learners->currentPage() - 2); $i <= min($learners->lastPage(), $learners->currentPage() + 2); $i++)
        <li>
            <a href="{{ $learners->url($i) }}" class="{{ $learners->currentPage() == $i ? 'active' : '' }}">
                {{ $i }}
            </a>
        </li>
    @endfor

        @if ($learners->currentPage() < $learners->lastPage() - 2)
            <li><span>...</span></li>
            <li><a href="{{ $learners->url($learners->lastPage()) }}">{{ $learners->lastPage() }}</a></li>
        @endif

            {{-- Next --}}
            <li>
                <a href="{{ $learners->hasMorePages() ? $learners->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted">Next</a>
            </li>
</ul>
@endif

@endif
<!-- Modal Popup end for Configration -->

<!-- /.content -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable', {
            searching: false, // This option hides the search bar
            ordering: false
        });
        var url = window.location.href;

        // Check if there are any URL parameters
        if (url.includes('?')) {
            // Redirect to the URL without parameters
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });

    var learnerTransactionsDataBase = @json(url('library/learners/transactions-data'));

    $(document).on('click', '.open-transaction-modal', function () {
        var learnerId = $(this).data('learner_id');
        var requestUrl = learnerTransactionsDataBase.replace(/\/+$/, '') + '/' + encodeURIComponent(learnerId);

        $('#transactionPendingBadge').addClass('d-none').text('');
        $('#transactionTableBody').html(
            '<tr><td colspan="9" class="text-center">Loading...</td></tr>'
        );
        $('#activityTableBody').html(
            '<tr><td colspan="6" class="text-center">Loading...</td></tr>'
        );

        $.ajax({
            url: requestUrl,
            type: 'GET',
            success: function (res) {
                if (!res.status) {
                    $('#transactionTableBody').html(
                        '<tr><td colspan="9" class="text-center text-danger">Unable to load transactions.</td></tr>'
                    );
                    $('#activityTableBody').html(
                        '<tr><td colspan="6" class="text-center text-danger">—</td></tr>'
                    );
                    return;
                }

                var badgeText = '';
                if (!res.transactions || res.transactions.length === 0) {
                    badgeText = 'No transaction records for this learner.';
                } else if (res.has_pending) {
                    badgeText = 'Showing rows with pending balance.';
                } else {
                    badgeText = 'No pending balance — showing latest transaction summary.';
                }
                $('#transactionPendingBadge').removeClass('d-none').text(badgeText);

                if (res.transactions && res.transactions.length > 0) {
                    var rows = '';
                    res.transactions.forEach(function (item, index) {
                        var receiptCell = item.receipt_url
                            ? '<a href="' + item.receipt_url + '" target="_blank" rel="noopener">Download</a>'
                            : '<span class="text-muted">—</span>';
                        rows += '<tr>' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td>' + (item.plan_price ?? '—') + '</td>' +
                            '<td>' + (item.other_addon_label ?? '—') + '</td>' +
                            '<td>' + (item.discount_amount ?? '—') + '</td>' +
                            '<td>' + (item.paid_amount ?? '—') + '</td>' +
                            '<td>' + (item.pending_amount ?? '—') + '</td>' +
                            '<td>' + (item.paid_date ?? '—') + '</td>' +
                            '<td>' + (item.payment_mode ?? '—') + '</td>' +
                            '<td>' + receiptCell + '</td>' +
                            '</tr>';
                    });
                    $('#transactionTableBody').html(rows);
                } else {
                    $('#transactionTableBody').html(
                        '<tr><td colspan="9" class="text-center">No transactions found.</td></tr>'
                    );
                }

                if (res.activities && res.activities.length > 0) {
                    var aRows = '';
                    res.activities.forEach(function (a) {
                        aRows += '<tr>' +
                            '<td>' + (a.particular ?? '—') + '</td>' +
                            '<td>' + (a.payment_type ?? '—') + '</td>' +
                            '<td>' + (a.payment_mode ?? '—') + '</td>' +
                            '<td>' + (a.amount_display ?? '—') + '</td>' +
                            '<td>' + (a.payment_date ?? '—') + '</td>' +
                            '<td>' + (a.dr_cr ?? '—') + '</td>' +
                            '</tr>';
                    });
                    $('#activityTableBody').html(aRows);
                } else {
                    $('#activityTableBody').html(
                        '<tr><td colspan="6" class="text-center">No activity recorded.</td></tr>'
                    );
                }
            },
            error: function () {
                $('#transactionTableBody').html(
                    '<tr><td colspan="9" class="text-center text-danger">Failed to load. Please try again.</td></tr>'
                );
                $('#activityTableBody').html(
                    '<tr><td colspan="6" class="text-center text-danger">—</td></tr>'
                );
            }
        });
    });

    function closeProfileImageModal() {
        var $m = $('#imageViewModal');
        $m.animate({ opacity: 0 }, 150, function () {
            $m.css({ display: 'none', opacity: 1 });
            $m.attr('aria-hidden', 'true');
            $('#modalImage').attr('src', '');
        });
    }

    function openProfileImageModal(imageUrl) {
        var $m = $('#imageViewModal');
        $('#modalImage').attr('src', imageUrl);
        $m.attr('aria-hidden', 'false');
        $m.css({ display: 'flex', opacity: 0 }).animate({ opacity: 1 }, 200);
    }

    // Profile photo: open large preview (extension may be before ?query)
    $(document).on('click', 'a.view-image', function (e) {
        var imageUrl = $(this).attr('href');
        if (!imageUrl || !imageUrl.match(/\.(jpg|jpeg|png|webp)(\?|#|$)/i)) {
            return;
        }
        e.preventDefault();
        openProfileImageModal(imageUrl);
    });

    $('#imageViewModal .close-modal').on('click', function (e) {
        e.stopPropagation();
        closeProfileImageModal();
    });

    $('#imageViewModal').on('click', function (e) {
        if ($(e.target).is(this)) {
            closeProfileImageModal();
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#imageViewModal').css('display') === 'flex') {
            closeProfileImageModal();
        }
    });
</script>

@endsection
