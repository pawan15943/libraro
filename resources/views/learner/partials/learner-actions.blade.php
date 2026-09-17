@php
    $isMobileAction = $isMobile ?? false;
    $isModalAction = $isModal ?? false;
    $learner_id = $learner_id ?? ($value->id ?? null);
    $learner_detail_id = $learner_detail_id ?? ($value->learner_detail_id ?? null);
    $today = $today ?? \Carbon\Carbon::now();
    $oneWeekLater = $oneWeekLater ?? (!empty($value->plan_start_date) ? \Carbon\Carbon::parse($value->plan_start_date)->addWeek() : \Carbon\Carbon::now()->addWeek());
    $threeDaysAfterStart = $threeDaysAfterStart ?? (!empty($value->plan_start_date) ? \Carbon\Carbon::parse($value->plan_start_date)->addDays(3) : \Carbon\Carbon::now()->addDays(3));
    $hiddenFields = $hiddenFields ?? (function_exists('toggleHideField') ? toggleHideField() : []);
    $currentBranchName = $currentBranchName ?? (function_exists('getCurrentBranchName') ? getCurrentBranchName() : '');
    $isNotificationActive = $isNotificationActive ?? (function_exists('notificationActive') ? notificationActive() : false);
    $isWabaNotificationActive = $isWabaNotificationActive ?? ($isNotificationActive && function_exists('wabaNotificationActive') && wabaNotificationActive());
    $isTextNotificationActive = $isTextNotificationActive ?? ($isNotificationActive && function_exists('textNotificationActive') && textNotificationActive());
    $canRenewFlag = $canRenewFlag ?? true;
    $overdueFlag = $overdueFlag ?? false;
    $planStatus = $planStatus ?? (function_exists('getPlanStatusDetails') && !empty($value->plan_end_date) ? getPlanStatusDetails($value->plan_end_date) : ['class' => '', 'status' => '', 'diff_in_days' => 0, 'diff_extend_day' => 0]);
    $transaction = $transaction ?? (function_exists('learnerTransaction') && !empty($learner_id) && !empty($learner_detail_id) ? learnerTransaction($learner_id, $learner_detail_id) : null);
    $totalPendingAmt = $totalPendingAmt ?? (optional($transaction)->pending_amount ?? 0);
    $totalExtraAmt = $totalExtraAmt ?? 0;
    $due_date = $due_date ?? (optional($transaction)->due_date ?? null);
    $paybleRefundAmt = $paybleRefundAmt ?? (function_exists('paybleRefund') && !empty($learner_detail_id) ? paybleRefund($learner_detail_id) : 0);
@endphp

{{-- 1. Share attendance link --}}
@if($isModalAction)
    <a target="_blank" href="https://wa.me/+91{{ $value->mobile }}?text={{ urlencode('Hello! Please click the link below to access the Attendance App and mark your attendance securely. ' . route('qr.attendance.link')) }}"
        class="modal-op-item" data-bs-toggle="tooltip" title="Share Attendance Link on WhatsApp">
        <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-share"></i></div>
        <span class="op-icon-label">Share</span>
    </a>
@elseif($isMobileAction)
    <div class="mobile-action-item">
        <a href="https://wa.me/+91{{ $value->mobile }}?text={{ urlencode('Hello! Please click the link below to access the Attendance App and mark your attendance securely. ' . route('qr.attendance.link')) }}"
            target="_blank" class="action-btn">
            <i class="fa-solid fa-share"></i>
        </a>
        <span class="mobile-action-label">Share</span>
    </div>
@else
    <li>
        <a href="https://wa.me/+91{{ $value->mobile }}?text={{ urlencode('Hello! Please click the link below to access the Attendance App and mark your attendance securely. ' . route('qr.attendance.link')) }}"
            target="_blank" class="action-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Share Attendance Link on WhatsApp">
            <i class="fa-solid fa-share"></i>
        </a>
    </li>
@endif

{{-- 2. Attendance summary --}}
@if($isModalAction)
    <a href="{{ route('attendance.summary',$learner_id) }}" class="modal-op-item" data-bs-toggle="tooltip" title="View Attendance Details">
        <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-clipboard-user"></i></div>
        <span class="op-icon-label">Attendance</span>
    </a>
@elseif($isMobileAction)
    <div class="mobile-action-item">
        <a href="{{ route('attendance.summary',$learner_id) }}" class="action-btn">
            <i class="fa-solid fa-clipboard-user"></i>
        </a>
        <span class="mobile-action-label">Attendance</span>
    </div>
@else
    <li>
        <a href="{{ route('attendance.summary',$learner_id) }}" class="action-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="View Attendance Details">
            <i class="fa-solid fa-clipboard-user"></i>
        </a>
    </li>
@endif

{{-- 3. Activity --}}
@if($isModalAction)
    <a href="{{ route('activities.all', ['learner_id' => $learner_id]) }}" class="modal-op-item" id="modalBtnActivity" data-bs-toggle="tooltip" title="View Learner Activity">
        <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <span class="op-icon-label">Activity</span>
    </a>
@elseif($isMobileAction)
    <div class="mobile-action-item">
        <a href="{{ route('activities.all', ['learner_id' => $learner_id]) }}" class="action-btn">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </a>
        <span class="mobile-action-label">Activity</span>
    </div>
@else
    <li>
        <a href="{{ route('activities.all', ['learner_id' => $learner_id]) }}" class="action-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="View Learner Activity">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </a>
    </li>
@endif

{{-- 4. Overdue Payment Reminder --}}
@if($planStatus['diff_extend_day'] >= 0)
    @if($overdueFlag && ($totalPendingAmt > 0 || ($transaction && (float)($transaction->pending_amount ?? 0) > 0)) && !empty($due_date))
        @php
            $overdueMsg = rawurlencode(
                'Dear ' . $value->name . ",\n\n" .
                'This is a gentle reminder that your library seat payment is still pending.' . "\n\n" .
                'Your due date was ' . \Carbon\Carbon::parse($due_date)->format('d-m-Y') . '. To avoid seat cancellation, please complete the payment at the earliest.' . "\n\n" .
                'Pending Amount: ₹' . number_format($totalPendingAmt > 0 ? $totalPendingAmt : (float)($transaction->pending_amount ?? 0), 2) . "\n\n" .
                'If you have already made the payment, kindly ignore this message.' . "\n\n" .
                'For any assistance, feel free to contact our support team.' . "\n\n" .
                '– Team ' . $currentBranchName
            );
        @endphp
        @if($isModalAction)
            <a target="_blank" href="https://wa.me/+91{{ $value->mobile }}?text={{ $overdueMsg }}" class="modal-op-item" data-bs-toggle="tooltip" title="Send Pending Payment Reminder">
                <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-business-time"></i></div>
                <span class="op-icon-label">Reminder</span>
            </a>
        @elseif($isMobileAction)
            <div class="mobile-action-item">
                <a href="https://wa.me/+91{{ $value->mobile }}?text={{ $overdueMsg }}" target="_blank" class="action-btn">
                    <i class="fa-solid fa-business-time"></i>
                </a>
                <span class="mobile-action-label">Reminder</span>
            </div>
        @else
            <li>
                <a href="https://wa.me/+91{{ $value->mobile }}?text={{ $overdueMsg }}" target="_blank" class="action-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Send Pending Payment Reminder">
                    <i class="fa-solid fa-business-time"></i>
                </a>
            </li>
        @endif
    @endif

    {{-- 5. Renew Seat --}}
    @if($canRenewFlag && $value->frozen_status != 1 && $planStatus['diff_in_days'] <= 5)
        @can('has-permission','Renew Seat')
            @if($isModalAction)
                <a href="javascript:void(0)" class="modal-op-item renew_extend" id="modalBtnRenew" data-seat_no="{{$value->seat_no}}" data-user="{{$learner_id}}" data-end_date="{{$value->plan_end_date}}" data-learner_detail="{{$learner_detail_id}}" data-bs-toggle="tooltip" title="Renew Plan">
                    <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-rotate"></i></div>
                    <span class="op-icon-label">Renew</span>
                </a>
            @elseif($isMobileAction)
                <div class="mobile-action-item">
                    <a class="action-btn renew_extend" data-seat_no="{{$value->seat_no}}" data-user="{{$learner_id}}" data-end_date="{{$value->plan_end_date}}" data-learner_detail="{{$learner_detail_id}}">
                        <i class="fa-solid fa-rotate"></i>
                    </a>
                    <span class="mobile-action-label">Renew</span>
                </div>
            @else
                <li>
                    <a class="action-btn renew_extend" data-seat_no="{{$value->seat_no}}" data-user="{{$learner_id}}" data-end_date="{{$value->plan_end_date}}" data-learner_detail="{{$learner_detail_id}}" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Renew Plan">
                        <i class="fa-solid fa-rotate"></i>
                    </a>
                </li>
            @endif
        @endcan
    @endif

    {{-- 6. WhatsApp / Message Reminders --}}
    @can('has-permission', 'WhatsApp Notification')
        @php
            $sendPref = $value->sended_message_type ?? 'no';
            $seatNo = (!empty($value->seat_no) && $value->seat_no != 0) ? $value->seat_no : 'GEN';
            if ($planStatus['class']=='extedned') {
                $reminderMessage = "Dear {$value->name}(Seat No-{$seatNo}),\n\nYour plan expired on ".changeFormate($value->plan_end_date).".\n\nPlease renew it as soon as possible to continue uninterrupted access to your library seat.\nYou are currently in the extension period — after this, your seat may be allotted to another learner.\n\nFor help, feel free to contact our support team.\n\n– Team " . $currentBranchName;
            } else {
                $reminderMessage = "Dear {$value->name}(Seat No-{$seatNo}),\n\nYour plan expired on ".changeFormate($value->plan_end_date).".\n\nPlease renew it as soon as possible to continue uninterrupted access to your library seat.\n\nFor help, feel free to contact our support team.\n\n– Team " . $currentBranchName;
            }
            $freeWabaLink = "https://wa.me/+91{$value->mobile}?text=" . rawurlencode($reminderMessage);
            $freeTextLink = "sms:+91{$value->mobile}?body=" . rawurlencode($reminderMessage);
        @endphp

        @if($isNotificationActive)
            @if($sendPref == 'both' && $isWabaNotificationActive && $isTextNotificationActive)
                @if($isModalAction)
                    <a href="javascript:;" class="modal-op-item open-reminder-chooser" data-learner_id="{{$learner_id}}" data-bs-toggle="modal" data-bs-target="#sendReminderChooserModal" data-bs-placement="bottom" data-bs-tooltip="tooltip" title="Send Reminder (Paid)">
                        <div class="op-icon-circle shadow-sm"><i class="fa fa-ellipsis-v"></i></div>
                        <span class="op-icon-label">Reminder</span>
                    </a>
                @elseif($isMobileAction)
                    <div class="mobile-action-item">
                        <a href="javascript:;" class="action-btn open-reminder-chooser" data-learner_id="{{$learner_id}}" data-bs-toggle="modal" data-bs-target="#sendReminderChooserModal">
                            <i class="fa fa-ellipsis-v"></i>
                        </a>
                        <span class="mobile-action-label">Reminder</span>
                    </div>
                @else
                    <li>
                        <a href="javascript:;" class="action-btn open-reminder-chooser" data-learner_id="{{$learner_id}}" data-bs-toggle="modal" data-bs-target="#sendReminderChooserModal" data-bs-placement="bottom" data-bs-tooltip="tooltip" data-bs-title="Send Reminder (Paid)">
                            <i class="fa fa-ellipsis-v"></i>
                        </a>
                    </li>
                @endif
            @else
                @if($isWabaNotificationActive && in_array($sendPref, ['whatsapp', 'both']))
                    @if($isModalAction)
                        <a target="_blank" href="javascript:;" data-bs-toggle="modal" class="modal-op-item open-waba" data-learner_id="{{$learner_id}}" data-bs-target="#wabaSendModel" data-bs-toggle="tooltip" title="WhatsApp Reminders (Paid)">
                            <div class="op-icon-circle shadow-sm"><i class="fab fa-whatsapp" style="color:#25D366"></i></div>
                            <span class="op-icon-label">WhatsApp</span>
                        </a>
                    @elseif($isMobileAction)
                        <div class="mobile-action-item">
                            <a target="_blank" href="javascript:;" data-bs-toggle="modal" class="action-btn open-waba" data-learner_id="{{$learner_id}}" data-bs-target="#wabaSendModel">
                                <i class="fab fa-whatsapp" style="color:#25D366"></i>
                            </a>
                            <span class="mobile-action-label">WhatsApp</span>
                        </div>
                    @else
                        <li>
                            <a target="_blank" href="javascript:;" data-bs-toggle="modal" class="action-btn open-waba" data-learner_id="{{$learner_id}}" data-bs-target="#wabaSendModel" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="WhatsApp Reminders (Paid)">
                                <i class="fab fa-whatsapp" style="color:#25D366"></i>
                            </a>
                        </li>
                    @endif
                @endif

                @if($isTextNotificationActive && in_array($sendPref, ['text', 'both']))
                    @if($isModalAction)
                        <a target="_blank" href="javascript:;" data-bs-toggle="modal" data-learner_id="{{$learner_id}}" class="modal-op-item open-text" data-bs-target="#textSendModel" data-bs-toggle="tooltip" title="Send Text Reminder (Paid)">
                            <div class="op-icon-circle shadow-sm"><i class="fa fa-message" style="color:#18225f"></i></div>
                            <span class="op-icon-label">SMS</span>
                        </a>
                    @elseif($isMobileAction)
                        <div class="mobile-action-item">
                            <a target="_blank" href="javascript:;" data-bs-toggle="modal" data-learner_id="{{$learner_id}}" class="action-btn open-text" data-bs-target="#textSendModel">
                                <i class="fa fa-message" style="color:#18225f"></i>
                            </a>
                            <span class="mobile-action-label">SMS</span>
                        </div>
                    @else
                        <li>
                            <a target="_blank" href="javascript:;" data-bs-toggle="modal" data-learner_id="{{$learner_id}}" class="action-btn open-text" data-bs-target="#textSendModel" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Send Text Reminder (Paid)">
                                <i class="fa fa-message" style="color:#18225f"></i>
                            </a>
                        </li>
                    @endif
                @endif
            @endif
        @else
            @if($sendPref == 'text')
                @if($isModalAction)
                    <a href="{{ $freeTextLink }}" class="modal-op-item" data-bs-toggle="tooltip" title="Send Text Reminder">
                        <div class="op-icon-circle shadow-sm"><i class="fa fa-message"></i></div>
                        <span class="op-icon-label">SMS</span>
                    </a>
                @elseif($isMobileAction)
                    <div class="mobile-action-item">
                        <a href="{{ $freeTextLink }}" class="action-btn">
                            <i class="fa fa-message"></i>
                        </a>
                        <span class="mobile-action-label">SMS</span>
                    </div>
                @else
                    <li>
                        <a href="{{ $freeTextLink }}" class="action-btn" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Send Text Reminder">
                            <i class="fa fa-message"></i>
                        </a>
                    </li>
                @endif
            @elseif($sendPref == 'both')
                @if($isModalAction)
                    <a href="javascript:;" class="modal-op-item open-reminder-chooser-free" data-waba-link="{{ $freeWabaLink }}" data-text-link="{{ $freeTextLink }}" data-bs-toggle="modal" data-bs-target="#sendReminderChooserFreeModal" data-bs-toggle="tooltip" title="Send Reminder">
                        <div class="op-icon-circle shadow-sm"><i class="fa fa-comment-dots"></i></div>
                        <span class="op-icon-label">Reminder</span>
                    </a>
                @elseif($isMobileAction)
                    <div class="mobile-action-item">
                        <a href="javascript:;" class="action-btn open-reminder-chooser-free" data-waba-link="{{ $freeWabaLink }}" data-text-link="{{ $freeTextLink }}" data-bs-toggle="modal" data-bs-target="#sendReminderChooserFreeModal">
                            <i class="fa fa-comment-dots"></i>
                        </a>
                        <span class="mobile-action-label">Reminder</span>
                    </div>
                @else
                    <li>
                        <a href="javascript:;" class="action-btn open-reminder-chooser-free" data-waba-link="{{ $freeWabaLink }}" data-text-link="{{ $freeTextLink }}" data-bs-toggle="modal" data-bs-target="#sendReminderChooserFreeModal" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Send Reminder">
                            <i class="fa fa-comment-dots"></i>
                        </a>
                    </li>
                @endif
            @else
                @if($isModalAction)
                    <a target="_blank" href="{{ $freeWabaLink }}" class="modal-op-item" id="modalBtnWhatsapp" data-bs-toggle="tooltip" title="Send Reminder">
                        <div class="op-icon-circle shadow-sm"><i class="fab fa-whatsapp"></i></div>
                        <span class="op-icon-label">WhatsApp</span>
                    </a>
                @elseif($isMobileAction)
                    <div class="mobile-action-item">
                        <a target="_blank" href="{{ $freeWabaLink }}" class="action-btn">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <span class="mobile-action-label">WhatsApp</span>
                    </div>
                @else
                    <li>
                        <a target="_blank" href="{{ $freeWabaLink }}" class="action-btn" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Send Reminder">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </li>
                @endif
            @endif
        @endif
    @endcan

    {{-- 7. Swap Seat --}}
    @can('has-permission', 'Swap Seat')
        @if($value->frozen_status != 1)
            @if($isModalAction)
                <a href="{{route('learners.swap',$value->id)}}" class="modal-op-item" id="modalBtnSwap" data-bs-toggle="tooltip" title="Swap Seat">
                    <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>
                    <span class="op-icon-label">Swap</span>
                </a>
            @elseif($isMobileAction)
                <div class="mobile-action-item">
                    <a href="{{route('learners.swap',$value->id)}}" class="action-btn">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i>
                    </a>
                    <span class="mobile-action-label">Swap</span>
                </div>
            @else
                <li>
                    <a href="{{route('learners.swap',$value->id)}}" class="action-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Swap Seat">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i>
                    </a>
                </li>
            @endif
        @endif
    @endcan

    {{-- 8. Change Plan --}}
    @can('has-permission', 'Change Plan')
        @if(!in_array('14', $hiddenFields) && !$today->greaterThanOrEqualTo($oneWeekLater) && $value->frozen_status != 1)
            @if($isModalAction)
                <a href="{{route('learner.change.plan',$value->id)}}" class="modal-op-item" id="modalBtnChangePlan" data-bs-toggle="tooltip" title="Change Plan">
                    <div class="op-icon-circle shadow-sm"><i class="fa fa-arrow-up-short-wide"></i></div>
                    <span class="op-icon-label">Change</span>
                </a>
            @elseif($isMobileAction)
                <div class="mobile-action-item">
                    <a href="{{route('learner.change.plan',$value->id)}}" class="action-btn">
                        <i class="fa fa-arrow-up-short-wide"></i>
                    </a>
                    <span class="mobile-action-label">Change Plan</span>
                </div>
            @else
                <li>
                    <a href="{{route('learner.change.plan',$value->id)}}" class="action-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Change Plan">
                        <i class="fa fa-arrow-up-short-wide"></i>
                    </a>
                </li>
            @endif
        @endif
    @endcan

    {{-- 9. ID Card generate --}}
    @can('has-permission', 'Genrate ID Card')
        @if(!in_array('15', $hiddenFields))
            @if($isModalAction)
                <a target="_blank" href="{{ route('idCard', $learner_detail_id) }}" class="modal-op-item" id="modalBtnIdCard" data-bs-toggle="tooltip" title="Generate ID Card">
                    <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-id-card-clip"></i></div>
                    <span class="op-icon-label">ID Card</span>
                </a>
            @elseif($isMobileAction)
                <div class="mobile-action-item">
                    <a target="_blank" href="{{ route('idCard', $learner_detail_id) }}" class="action-btn">
                        <i class="fa-solid fa-id-card-clip"></i>
                    </a>
                    <span class="mobile-action-label">ID Card</span>
                </div>
            @else
                <li>
                    <a target="_blank" href="{{ route('idCard', $learner_detail_id) }}" class="action-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Generate ID Card">
                        <i class="fa-solid fa-id-card-clip"></i>
                    </a>
                </li>
            @endif
        @endif
    @endcan

    {{-- 10. Upgrade Seat --}}
    @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']>= 0 )
        @can('has-permission', 'Upgrade Seat Plan')
            @if(!in_array('13', $hiddenFields) && $value->frozen_status != 1)
                @if($isModalAction)
                    <a href="{{route('learners.upgrade',$value->id)}}" class="modal-op-item" id="modalBtnUpgradePlan" data-bs-toggle="tooltip" title="Upgrade Plan">
                        <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-circle-up"></i></div>
                        <span class="op-icon-label">Upgrade</span>
                    </a>
                @elseif($isMobileAction)
                    <div class="mobile-action-item">
                        <a href="{{route('learners.upgrade',$value->id)}}" class="action-btn">
                            <i class="fa-solid fa-circle-up"></i>
                        </a>
                        <span class="mobile-action-label">Upgrade</span>
                    </div>
                @else
                    <li>
                        <a href="{{route('learners.upgrade',$value->id)}}" class="action-btn" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Upgrade Plan">
                            <i class="fa-solid fa-circle-up"></i>
                        </a>
                    </li>
                @endif
            @endif
        @endcan
    @endif

    {{-- 11. Close Seat --}}
    @can('has-permission', 'Close Seat')
        @if(!in_array('16', $hiddenFields) && $value->frozen_status != 1)
            @if($isModalAction)
                <a href="javascript:void(0);" class="modal-op-item link-close-plan close-seat" id="modalBtnCloseSeat" data-id="{{$value->id}}" data-learnerDetail="{{ $learner_detail_id }}" data-learner_detail_id="{{$learner_detail_id}}" data-payblerefund="{{ $paybleRefundAmt }}" data-plan_end_date="{{$value->plan_end_date}}" data-bs-toggle="tooltip" title="Close Plan">
                    <div class="op-icon-circle shadow-sm"><i class="fas fa-times"></i></div>
                    <span class="op-icon-label">Close</span>
                </a>
            @elseif($isMobileAction)
                <div class="mobile-action-item">
                    <a href="javascript:void(0);" class="action-btn link-close-plan" data-id="{{$value->id}}" data-learnerDetail="{{ $learner_detail_id }}" data-learner_detail_id="{{$learner_detail_id}}" data-payblerefund="{{ $paybleRefundAmt }}" data-plan_end_date="{{$value->plan_end_date}}">
                        <i class="fas fa-times"></i>
                    </a>
                    <span class="mobile-action-label">Close</span>
                </div>
            @else
                <li>
                    <a href="javascript:void(0);" class="action-btn link-close-plan" data-id="{{$value->id}}" data-learnerDetail="{{ $learner_detail_id }}" data-learner_detail_id="{{$learner_detail_id}}" data-payblerefund="{{ $paybleRefundAmt }}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Close Plan" data-plan_end_date="{{$value->plan_end_date}}">
                        <i class="fas fa-times"></i>
                    </a>
                </li>
            @endif
        @endif
    @endcan
@endif

{{-- 12. Reactive Seat --}}
@can('has-permission', 'Reactive Seat')
    @if($value->status==0 && $value->frozen_status != 1)
        @if($isModalAction)
            <a href="{{route('learners.reactive',$value->id)}}" class="modal-op-item" id="modalBtnReactive" data-bs-toggle="tooltip" title="Reactivate Learner">
                <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-arrows-rotate"></i></div>
                <span class="op-icon-label">Reactivate</span>
            </a>
        @elseif($isMobileAction)
            <div class="mobile-action-item">
                <a href="{{route('learners.reactive',$value->id)}}" class="action-btn">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </a>
                <span class="mobile-action-label">Reactivate</span>
            </div>
        @else
            <li>
                <a href="{{route('learners.reactive',$value->id)}}" class="action-btn" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Reactivate Learner">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </a>
            </li>
        @endif
    @endif
@endcan

{{-- 13. Add Miscellaneous Payment --}}
@can('has-permission', 'Add Misllaneous Payment')
    @if($isModalAction)
        <a href="{{route('learner.other.payment',$learner_detail_id)}}" class="modal-op-item payment-learner" id="modalBtnMiscPayment" data-bs-toggle="tooltip" title="Other Payment">
            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-money-bill"></i></div>
            <span class="op-icon-label">Other Pay</span>
        </a>
    @elseif($isMobileAction)
        <div class="mobile-action-item">
            <a href="{{route('learner.other.payment',$learner_detail_id)}}" class="action-btn payment-learner">
                <i class="fa-solid fa-money-bill"></i>
            </a>
            <span class="mobile-action-label">Other Pay</span>
        </div>
    @else
        <li>
            <a href="{{route('learner.other.payment',$learner_detail_id)}}" class="action-btn payment-learner" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Other Payment">
                <i class="fa-solid fa-money-bill"></i>
            </a>
        </li>
    @endif
@endcan

{{-- 14. Transactions --}}
@if($isModalAction)
    <a href="{{ route('learners.transactions', $learner_id) }}" class="modal-op-item" id="modalBtnTransactions" data-bs-toggle="tooltip" title="Transactions">
        <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-wallet"></i></div>
        <span class="op-icon-label">Transactions</span>
    </a>
@elseif($isMobileAction)
    <div class="mobile-action-item">
        <a href="{{ route('learners.transactions', $learner_id) }}" class="action-btn">
            <i class="fa-solid fa-wallet"></i>
        </a>
        <span class="mobile-action-label">Wallet</span>
    </div>
@else
    <li>
        <a href="{{ route('learners.transactions', $learner_id) }}" class="action-btn" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Transactions">
            <i class="fa-solid fa-wallet"></i>
        </a>
    </li>
@endif

{{-- 15. Settlement --}}
@if ($totalPendingAmt > 0 || $totalExtraAmt > 0 || ($transaction && ((float)($transaction->pending_amount ?? 0) > 0 || (float)($transaction->refund ?? 0) > 0)))
    @if($isModalAction)
        <a href="javascript:void(0)" data-id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" class="modal-op-item settlement-learner" id="modalBtnSettlement" data-bs-toggle="tooltip" title="Settlement">
            <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-scale-balanced"></i></div>
            <span class="op-icon-label">Settlement</span>
        </a>
    @elseif($isMobileAction)
        <div class="mobile-action-item">
            <a href="#" data-id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" class="action-btn settlement-learner">
                <i class="fa-solid fa-scale-balanced"></i>
            </a>
            <span class="mobile-action-label">Settlement</span>
        </div>
    @else
        <li>
            <a href="#" data-id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Settlement" class="action-btn settlement-learner">
                <i class="fa-solid fa-scale-balanced"></i>
            </a>
        </li>
    @endif
@endif

{{-- 16. Gift Days --}}
@can('has-permission', 'Gift Days')
    @if(!in_array('33', $hiddenFields) && $value->frozen_status != 1)
        @if($isModalAction)
            <a href="javascript:;" class="modal-op-item giftDaysBtn" id="modalBtnGift" data-learner_id="{{$learner_id}}" data-bs-toggle="tooltip" title="Gift Days">
                <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-gift"></i></div>
                <span class="op-icon-label">Gift</span>
            </a>
        @elseif($isMobileAction)
            <div class="mobile-action-item">
                <a href="javascript:;" class="action-btn giftDaysBtn" data-learner_id="{{$learner_id}}">
                    <i class="fa-solid fa-gift"></i>
                </a>
                <span class="mobile-action-label">Gift Days</span>
            </div>
        @else
            <li>
                <a href="javascript:;" class="action-btn giftDaysBtn" data-learner_id="{{$learner_id}}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Gift Days">
                    <i class="fa-solid fa-gift"></i>
                </a>
            </li>
        @endif
    @endif
@endcan

{{-- 17. Freeze Days --}}
@can('has-permission', 'Freez Days')
    @if(!in_array('34', $hiddenFields))
        @if($value->frozen_status == 1 || $planStatus['diff_in_days'] >= 0)
            @if($isModalAction)
                <a href="javascript:;" class="modal-op-item freezDaysBtn" id="modalBtnFreeze" data-status="{{$value->frozen_status}}" data-learner_id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" data-bs-toggle="tooltip" title="{{ $value->frozen_status == 1 ? 'Unfreeze Plan' : 'Freeze Plan' }}">
                    <div class="op-icon-circle shadow-sm"><i class="{{ $value->frozen_status == 1 ? 'fa-solid fa-pause' : 'fa-solid fa-snowflake' }}"></i></div>
                    <span class="op-icon-label">{{ $value->frozen_status == 1 ? 'Unfreeze' : 'Freeze' }}</span>
                </a>
            @elseif($isMobileAction)
                <div class="mobile-action-item">
                    <a href="javascript:;" class="action-btn freezDaysBtn" data-status="{{$value->frozen_status}}" data-learner_id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}">
                        @if($value->frozen_status == 1)
                            <i class="fa-solid fa-pause"></i>
                        @else
                            <i class="fa-solid fa-snowflake"></i>
                        @endif
                    </a>
                    <span class="mobile-action-label">{{ $value->frozen_status == 1 ? 'Unfreeze' : 'Freeze' }}</span>
                </div>
            @else
                <li>
                    <a href="javascript:;" class="action-btn freezDaysBtn" data-status="{{$value->frozen_status}}" data-learner_id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="{{ $value->frozen_status == 1 ? 'Unfreeze Plan' : 'Freeze Plan' }}">
                        @if($value->frozen_status == 1)
                            <i class="fa-solid fa-pause"></i>
                        @else
                            <i class="fa-solid fa-snowflake"></i>
                        @endif
                    </a>
                </li>
            @endif
        @endif
    @endif
@endcan

{{-- 18. View Seat Info --}}
@can('has-permission', 'View Seat')
    @if($isModalAction)
        <a href="{{route('learners.show',$value->id)}}" class="modal-op-item" id="modalBtnProfile" data-bs-toggle="tooltip" title="View Seat Booking Full Details">
            <div class="op-icon-circle shadow-sm"><i class="fas fa-eye"></i></div>
            <span class="op-icon-label">Profile</span>
        </a>
    @elseif($isMobileAction)
        <div class="mobile-action-item">
            <a href="{{route('learners.show',$value->id)}}" class="action-btn">
                <i class="fas fa-eye"></i>
            </a>
            <span class="mobile-action-label">View</span>
        </div>
    @else
        <li>
            <a href="{{route('learners.show',$value->id)}}" class="action-btn" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="View Seat Booking Full Details">
                <i class="fas fa-eye"></i>
            </a>
        </li>
    @endif
@endcan

{{-- 19. Edit Seat --}}
@can('has-permission', 'Edit Seat')
    @if(!in_array('17', $hiddenFields) && $value->frozen_status != 1)
        @if($isModalAction)
            <a href="{{route('learners.edit',$value->id)}}" class="modal-op-item" id="modalBtnEditProfile" data-bs-toggle="tooltip" title="Edit Seat Booking Details">
                <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-user-pen"></i></div>
                <span class="op-icon-label">Edit</span>
            </a>
        @elseif($isMobileAction)
            <div class="mobile-action-item">
                <a href="{{route('learners.edit',$value->id)}}" class="action-btn">
                    <i class="fas fa-edit"></i>
                </a>
                <span class="mobile-action-label">Edit</span>
            </div>
        @else
            <li>
                <a href="{{route('learners.edit',$value->id)}}" class="action-btn" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Edit Seat Booking Details">
                    <i class="fas fa-edit"></i>
                </a>
            </li>
        @endif

        {{-- 20. Edit Plan Details --}}
        @if($today->lessThanOrEqualTo($threeDaysAfterStart))
            @if($isModalAction)
                <a href="{{route('learners.edit.plan',$value->id)}}" class="modal-op-item" id="modalBtnEditPlan" data-bs-toggle="tooltip" title="Edit Plan Details">
                    <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-pen-to-square"></i></div>
                    <span class="op-icon-label">Edit Plan</span>
                </a>
            @elseif($isMobileAction)
                <div class="mobile-action-item">
                    <a href="{{route('learners.edit.plan',$value->id)}}" class="action-btn">
                        <i class="fa-solid fa-calendar-days"></i>
                    </a>
                    <span class="mobile-action-label">Edit Plan</span>
                </div>
            @else
                <li>
                    <a href="{{route('learners.edit.plan',$value->id)}}" class="action-btn" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Edit Plan Details">
                        <i class="fa-solid fa-calendar-days"></i>
                    </a>
                </li>
            @endif
        @endif
    @endif
@endcan

{{-- 21. Delete Seat --}}
@can('has-permission', 'Delete Seat')
    @if($isModalAction)
        <a href="javascript:void(0)" data-id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{$value->seat_no}}" data-payblerefund="{{ $paybleRefundAmt }}" class="modal-op-item delete-customer" id="modalBtnDelete" data-bs-toggle="tooltip" title="Delete Learners">
            <div class="op-icon-circle shadow-sm"><i class="fas fa-trash"></i></div>
            <span class="op-icon-label">Delete</span>
        </a>
    @elseif($isMobileAction)
        <div class="mobile-action-item">
            <a href="#" data-id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{$value->seat_no}}" data-payblerefund="{{ $paybleRefundAmt }}" class="action-btn btn-danger-hover delete-customer">
                <i class="fas fa-trash"></i>
            </a>
            <span class="mobile-action-label">Delete</span>
        </div>
    @else
        <li>
            <a href="#" data-id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{$value->seat_no}}" data-payblerefund="{{ $paybleRefundAmt }}" class="action-btn btn-danger-hover delete-customer" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Delete Learners">
                <i class="fas fa-trash"></i>
            </a>
        </li>
    @endif
@endcan

{{-- 22. Send Receipt --}}
@if($isModalAction)
    <a target="_blank" href="https://wa.me/+91{{ $value->mobile }}?text={{ whatsappReceiptMessage($value, $transaction, $currentBranchName) }}" class="modal-op-item" id="modalBtnReceipt" data-bs-toggle="tooltip" title="Send Receipt">
        <div class="op-icon-circle shadow-sm"><i class="fa-solid fa-receipt"></i></div>
        <span class="op-icon-label">Receipt</span>
    </a>
@elseif($isMobileAction)
    <div class="mobile-action-item">
        <a target="_blank" href="https://wa.me/+91{{ $value->mobile }}?text={{ whatsappReceiptMessage($value, $transaction, $currentBranchName) }}" class="action-btn">
            <i class="fa-solid fa-receipt"></i>
        </a>
        <span class="mobile-action-label">Receipt</span>
    </div>
@else
    <li>
        <a target="_blank" href="https://wa.me/+91{{ $value->mobile }}?text={{ whatsappReceiptMessage($value, $transaction, $currentBranchName) }}" class="action-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Send Receipt">
            <i class="fa-solid fa-receipt"></i>
        </a>
    </li>
@endif

