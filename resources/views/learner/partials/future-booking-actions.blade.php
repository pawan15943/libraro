@php
    $isMobileAction = $isMobile ?? false;
    $learner_id = $learner_id ?? ($value->id ?? null);
    $learner_detail_id = $learner_detail_id ?? ($value->learner_detail_id ?? null);
    $currentBranchName = $currentBranchName ?? (function_exists('getCurrentBranchName') ? getCurrentBranchName() : '');
    $transaction = $transaction ?? (function_exists('learnerTransaction') && !empty($learner_id) && !empty($learner_detail_id) ? learnerTransaction($learner_id, $learner_detail_id) : null);
    $paybleRefundAmt = $paybleRefundAmt ?? (function_exists('paybleRefund') && !empty($learner_detail_id) ? paybleRefund($learner_detail_id) : 0);
    $hiddenFields = $hiddenFields ?? (function_exists('toggleHideField') ? toggleHideField() : []);
@endphp

{{-- 1. View Profile --}}
@can('has-permission', 'View Seat')
@if($isMobileAction)
    <div class="mobile-action-item">
        <a href="{{ route('learners.show', $learner_id) }}" class="action-btn" title="View Profile">
            <i class="fas fa-eye"></i>
        </a>
        <span class="mobile-action-label">View</span>
    </div>
@else
    <li>
        <a href="{{ route('learners.show', $learner_id) }}" class="action-btn" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="View Profile">
            <i class="fas fa-eye"></i>
        </a>
    </li>
@endif
@endcan

{{-- 2. Edit Profile --}}
@can('has-permission', 'Edit Seat')
@if(!in_array('17', $hiddenFields) && ($value->frozen_status ?? 0) != 1)
@if($isMobileAction)
    <div class="mobile-action-item">
        <a href="{{ route('learners.edit', $learner_id) }}" class="action-btn" title="Edit Profile">
            <i class="fas fa-edit"></i>
        </a>
        <span class="mobile-action-label">Edit</span>
    </div>
@else
    <li>
        <a href="{{ route('learners.edit', $learner_id) }}" class="action-btn" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Edit Profile">
            <i class="fas fa-edit"></i>
        </a>
    </li>
@endif
@endif
@endcan

{{-- 3. Receipt --}}
@if($isMobileAction)
    <div class="mobile-action-item">
        <a target="_blank" href="https://wa.me/+91{{ $value->mobile }}?text={{ whatsappReceiptMessage($value, $transaction, $currentBranchName) }}" class="action-btn" title="Send Receipt">
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

{{-- 4. Transaction --}}
@if($isMobileAction)
    <div class="mobile-action-item">
        <a href="{{ route('learners.transactions', $learner_id) }}" class="action-btn" title="Transactions">
            <i class="fa-solid fa-wallet"></i>
        </a>
        <span class="mobile-action-label">Transaction</span>
    </div>
@else
    <li>
        <a href="{{ route('learners.transactions', $learner_id) }}" class="action-btn" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Transactions">
            <i class="fa-solid fa-wallet"></i>
        </a>
    </li>
@endif

{{-- 5. Delete --}}
@can('has-permission', 'Delete Seat')
@if($isMobileAction)
    <div class="mobile-action-item">
        <a href="#" data-id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{$value->seat_no}}" data-payblerefund="{{ $paybleRefundAmt }}" class="action-btn btn-danger-hover delete-customer" title="Delete Booking">
            <i class="fas fa-trash"></i>
        </a>
        <span class="mobile-action-label">Delete</span>
    </div>
@else
    <li>
        <a href="#" data-id="{{$learner_id}}" data-learnerDetail="{{ $learner_detail_id }}" data-seat="{{$value->seat_no}}" data-payblerefund="{{ $paybleRefundAmt }}" class="action-btn btn-danger-hover delete-customer" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Delete Booking">
            <i class="fas fa-trash"></i>
        </a>
    </li>
@endif
@endcan

{{-- 6. Activity --}}
@if($isMobileAction)
    <div class="mobile-action-item">
        <a href="{{ route('activities.all', ['learner_id' => $learner_id]) }}" class="action-btn" title="View Activity">
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
