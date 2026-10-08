@extends('layouts.library')
@section('content')

<link rel="stylesheet" href="{{ asset('public/css/seat-view-list.css') }}?v={{ time() }}" />

@php
    use Carbon\Carbon;
    use App\Helpers\HelperService;

    $typeTitles = [
        'total_booking'       => 'Total Slots Bookings',
        'active_booking'      => 'Active Slots',
        'expired_seats'       => 'Expired Slots',
        'thisbooking_slot'    => 'This Month Total Slots',
        'booing_slot'         => 'This Month Booked Slots',
        'till_previous_book'  => 'Previous Month Booked Slots',
        'expire_booking_slot' => 'This Month Expired Slots',
        'expired_in_five'     => 'Expired in 5 Days',
        'extended_seat'       => 'Extended Seats',
        'online_paid'         => 'Online Paid Learners',
        'offline_paid'        => 'Offline Paid Learners',
        'other_paid'          => 'Pay Later Learners',
        'swap_seat'           => 'Swap Seats History',
        'learnerUpgrade'      => 'Upgrade Seats History',
        'reactive_seat'       => 'Reactive Seats History',
        'renew_seat'          => 'Renew Seats History',
        'close_seat'          => 'Close Seats History',
        'delete_seat'         => 'Delete Seats History',
        'change_plan_seat'    => 'Change Plan History',
    ];

    $typeKey = request('type');
    $text = $typeTitles[$typeKey] ?? (ucwords(str_replace('_', ' ', $typeKey ?: 'Learners Seat View')));

    $monthNum = request('month');
    $yearNum  = request('year') ?? date('Y');
    $monthName = $monthNum ? date('F', mktime(0, 0, 0, (int)$monthNum, 10)) : date('F');
    $periodText = $monthName . ' ' . $yearNum;

    // Counts for KPI Summary Cards
    $totalCount = $result->count();
    $activeCount = 0;
    $paidCount = 0;
    $unpaidCount = 0;

    foreach ($result as $item) {
        $st = 0;
        $pd = 0;
        if (isset($item->operation_date)) {
            $ld = App\Models\LearnerDetail::withTrashed()->where('id', $item->learner_detail_id)->first();
            $st = ($ld && $ld->status == 1) ? 1 : 0;
            $pd = ($ld && $ld->is_paid == 1) ? 1 : 0;
        } elseif (isset($item->learner)) {
            $st = ($item->status == 1 || ($item->learner && $item->learner->status == 1)) ? 1 : 0;
            $pd = ($item->is_paid == 1) ? 1 : 0;
        } elseif (isset($item->max_plan_start_date)) {
            $ld = App\Models\LearnerDetail::where('learner_id', $item->learner_id)->where('plan_start_date', $item->max_plan_start_date)->first();
            $st = ($ld && $ld->status == 1) ? 1 : 0;
            $pd = ($ld && $ld->is_paid == 1) ? 1 : 0;
        } else {
            $st = ($item->status == 1) ? 1 : 0;
            $pd = (isset($item->is_paid) && $item->is_paid == 1) ? 1 : 0;
        }
        if ($st == 1) $activeCount++;
        if ($pd == 1) $paidCount++; else $unpaidCount++;
    }
@endphp

<div class="seat-view-list-module">
    <!-- Header Section -->
    <div class="heading-list py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('library.home') }}" class="btn-back-dashboard" data-bs-toggle="tooltip" title="Back to Dashboard">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h4 class="title-heading mb-0">{{ $text }}</h4>
                <div class="subtitle-text">
                    <i class="fa-regular fa-calendar-days text-muted"></i>
                    <span>Period: <strong class="text-navy">{{ $periodText }}</strong></span>
                    <span class="mx-1">•</span>
                    <span>Total: <strong class="text-navy">{{ $totalCount }} Learners</strong></span>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('library.home') }}" class="btn-dashboard-action">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="{{ route('learners') }}" class="btn-seats-action">
                <i class="fa-solid fa-users"></i> All Learners
            </a>
        </div>
    </div>

    <!-- KPI Metric Summary Cards -->
    <div class="kpi-summary-grid">
        <div class="kpi-metric-card card-total">
            <div class="kpi-icon-badge">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="kpi-data">
                <span class="kpi-label">Total Records</span>
                <span class="kpi-value">{{ $totalCount }}</span>
                <span class="kpi-sub">In current view filter</span>
            </div>
        </div>

        <div class="kpi-metric-card card-active">
            <div class="kpi-icon-badge">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="kpi-data">
                <span class="kpi-label">Active Learners</span>
                <span class="kpi-value">{{ $activeCount }}</span>
                <span class="kpi-sub">{{ $totalCount > 0 ? round(($activeCount / $totalCount) * 100) : 0 }}% of total records</span>
            </div>
        </div>

        @if(request('type') === 'extended_seat')
            <div class="kpi-metric-card card-extended">
                <div class="kpi-icon-badge">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div class="kpi-data">
                    <span class="kpi-label">Grace Extension</span>
                    <span class="kpi-value">+{{ $extendDay ?? 0 }} Days</span>
                    <span class="kpi-sub">Active seat grace window</span>
                </div>
            </div>
        @else
            <div class="kpi-metric-card card-extended">
                <div class="kpi-icon-badge">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <div class="kpi-data">
                    <span class="kpi-label">Paid / Unpaid</span>
                    <span class="kpi-value">{{ $paidCount }} <small style="font-size: 0.9rem; color: #94a3b8; font-weight: 500;">/ {{ $unpaidCount }}</small></span>
                    <span class="kpi-sub">{{ $paidCount }} Paid &bull; {{ $unpaidCount }} Unpaid</span>
                </div>
            </div>
        @endif

        <div class="kpi-metric-card card-period">
            <div class="kpi-icon-badge">
                <i class="fa-regular fa-calendar-check"></i>
            </div>
            <div class="kpi-data">
                <span class="kpi-label">Report Period</span>
                <span class="kpi-value">{{ $monthName }}</span>
                <span class="kpi-sub">Year {{ $yearNum }}</span>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="table-card-container">
        <div class="table-card-header">
            <div class="d-flex align-items-center gap-2">
                <span class="record-badge-count">
                    <i class="fa-solid fa-list-check text-navy"></i>
                    <span>Records:</span>
                    <span class="count-pill">{{ $totalCount }}</span>
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table seat-data-table" id="datatable">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="col-align-center">S.No.</th>
                        <th style="width: 24%;" class="col-align-left">Learner Info</th>
                        <th style="width: 22%;" class="col-align-left">Contact Info</th>
                        <th style="width: 17%;" class="col-align-left">Plan & Slot</th>
                        <th style="width: 15%;" class="col-align-left">Timeline Dates</th>
                        <th style="width: 12%;" class="col-align-left">Status</th>
                        <th style="width: 110px;" class="col-align-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($result as $data)
                        @php
                            // Data Normalization across the 4 query shapes
                            if ($data->operation_date) {
                                $learner = App\Models\Learner::withTrashed()->where('id', $data->learner_id)->first();
                                $learner_detail = App\Models\LearnerDetail::withTrashed()->where('id', $data->learner_detail_id)->with(['plan','planType'])->first();
                                $operation = DB::table('learner_operations_log')
                                    ->where('learner_id', $data->learner_id)
                                    ->where('learner_detail_id', $data->learner_detail_id)
                                    ->where('operation', $data->operation)
                                    ->whereDate('created_at', $data->operation_date)
                                    ->first();
                                if ($operation) {
                                    $operation->learner_name = $learner->name ?? 'Learner';
                                    $operation->learner_seat_no = $learner->seat_no ?? null;
                                }
                                $operationDetails = HelperService::getOperationDetails($operation);

                                $learnerId = $learner->id ?? $data->learner_id ?? null;
                                $seatNo = $learner->seat_no ?? 'GEN';
                                $name = $learner->name ?? 'Learner';
                                $dob = $learner->dob ?? '';
                                $email = $learner->email ?? '';
                                $mobile = $data->mobile ?? ($learner->mobile ?? '');
                                $planName = $learner_detail->plan->name ?? 'N/A';
                                $planTypeName = $learner_detail->planType->name ?? 'N/A';
                                $startDate = $learner_detail->plan_start_date ?? 'N/A';
                                $endDate = $learner_detail->plan_end_date ?? 'N/A';
                                $isActive = ($learner_detail && $learner_detail->status == 1);
                                $isPaid = ($learner_detail && $learner_detail->is_paid == 1);
                                $operationMsg = $operationDetails['message'] ?? '';
                                $operationType = $operationDetails['operation_type'] ?? '';
                            } elseif ($data->learner) {
                                $learnerId = $data->learner->id ?? null;
                                $seatNo = $data->learner->seat_no ?? 'GEN';
                                $name = $data->learner->name ?? 'Learner';
                                $dob = $data->dob ?? ($data->learner->dob ?? '');
                                $email = $data->email ?? ($data->learner->email ?? '');
                                $mobile = $data->mobile ?? ($data->learner->mobile ?? '');
                                $planName = $data->plan->name ?? 'N/A';
                                $planTypeName = $data->planType->name ?? 'N/A';
                                $startDate = $data->plan_start_date ?? 'N/A';
                                $endDate = $data->plan_end_date ?? ($data->learner->plan_end_date ?? 'N/A');
                                $isActive = ($data->status == 1 || ($data->learner && $data->learner->status == 1));
                                $isPaid = ($data->is_paid == 1);
                                $operationMsg = '';
                                $operationType = '';
                            } elseif ($data->max_plan_start_date) {
                                $learner_detail = App\Models\LearnerDetail::where('learner_id', $data->learner_id)->where('plan_start_date', $data->max_plan_start_date)->first();
                                $plan = $learner_detail ? App\Models\Plan::where('id', $learner_detail->plan_id)->first() : null;
                                $planType = $learner_detail ? App\Models\planType::where('id', $learner_detail->plan_type_id)->first() : null;

                                $learnerId = $data->learner_id ?? null;
                                $seatNo = $data->seat_no ?? 'GEN';
                                $name = $data->name ?? 'Learner';
                                $dob = $data->dob ?? '';
                                $email = $data->email ?? '';
                                $mobile = $data->mobile ?? '';
                                $planName = $plan->name ?? 'N/A';
                                $planTypeName = $planType->name ?? 'N/A';
                                $startDate = $data->max_plan_start_date ?? 'N/A';
                                $endDate = $data->max_plan_end_date ?? 'N/A';
                                $isActive = ($learner_detail && $learner_detail->status == 1);
                                $isPaid = ($learner_detail && $learner_detail->is_paid == 1);
                                $operationMsg = '';
                                $operationType = '';
                            } else {
                                $learnerId = $data->learner_id ?? $data->id ?? null;
                                $seatNo = $data->seat_no ?? 'GEN';
                                $name = $data->name ?? 'Learner';
                                $dob = $data->dob ?? '';
                                $email = $data->email ?? '';
                                $mobile = $data->mobile ?? '';
                                $planName = $data->plan->name ?? 'N/A';
                                $planTypeName = $data->planType->name ?? 'N/A';
                                $startDate = $data->plan_start_date ?? 'N/A';
                                $endDate = $data->plan_end_date ?? ($data->learner->plan_end_date ?? 'N/A');
                                $isActive = ($data->status == 1);
                                $isPaid = (isset($data->is_paid) && $data->is_paid == 1);
                                $operationMsg = '';
                                $operationType = '';
                            }

                            $cleanPhone = preg_replace('/[^0-9]/', '', $mobile ?? '');
                            if (strlen($cleanPhone) > 10 && str_starts_with($cleanPhone, '91')) {
                                $cleanPhone = substr($cleanPhone, 2);
                            }
                        @endphp
                        <tr>
                            <!-- 1. Serial Number (Centered) -->
                            <td class="col-align-center">
                                <span class="sno-tag">{{ $loop->iteration }}</span>
                            </td>

                            <!-- 2. Learner Info (Left-Aligned Stack) -->
                            <td class="col-align-left">
                                <div class="learner-col-cell">
                                    <span class="seat-no-tag">
                                        <i class="fa-solid fa-chair me-1"></i>SEAT {{ $seatNo }}
                                    </span>
                                    @if($learnerId)
                                        <a href="{{ route('learners.show', $learnerId) }}" class="learner-name" data-bs-toggle="tooltip" title="{{ $name }}">
                                            {{ $name }}
                                        </a>
                                    @else
                                        <span class="learner-name">{{ $name }}</span>
                                    @endif

                                    @if(!empty($dob))
                                        <span class="learner-dob">
                                            <i class="fa-regular fa-calendar me-1"></i>DOB: {{ $dob }}
                                        </span>
                                    @endif

                                    @if(!empty($operationMsg))
                                        <div class="operation-note mt-1">
                                            {!! $operationMsg !!}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- 3. Contact Info (Left-Aligned Stack) -->
                            <td class="col-align-left">
                                <div class="contact-col-cell">
                                    @if(!empty($cleanPhone))
                                        <a href="https://wa.me/91{{ $cleanPhone }}" target="_blank" class="contact-phone-link" data-bs-toggle="tooltip" title="Chat on WhatsApp">
                                            <i class="fa-brands fa-whatsapp text-success"></i>
                                            <span>+91-{{ display_learner_mobile($cleanPhone) }}</span>
                                        </a>
                                    @else
                                        <span class="text-muted" style="font-size: 0.8rem;">Mobile N/A</span>
                                    @endif

                                    <div class="contact-email" data-bs-toggle="tooltip" title="{{ !empty($email) ? display_learner_email($email) : 'Email ID Not Available' }}">
                                        @if(!empty($email))
                                            <i class="fa-regular fa-envelope text-muted"></i>
                                            <span>{{ display_learner_email($email) }}</span>
                                        @else
                                            <i class="fa-solid fa-circle-xmark text-danger"></i>
                                            <span class="text-muted">Email N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- 4. Plan & Slot (Left-Aligned Stack) -->
                            <td class="col-align-left">
                                <div class="plan-slot-col-cell">
                                    <span class="plan-name-badge">
                                        <i class="fa-solid fa-bookmark"></i>
                                        <span>{{ $planName }}</span>
                                    </span>
                                    <span class="slot-type-badge">
                                        <i class="fa-regular fa-clock"></i>
                                        <span>{{ $planTypeName }}</span>
                                    </span>
                                </div>
                            </td>

                            <!-- 5. Timeline Dates (Aligned Fixed-Label Grid) -->
                            <td class="col-align-left">
                                <div class="dates-col-cell">
                                    <div class="date-row">
                                        <span class="date-label">START:</span>
                                        <span class="date-val">{{ $startDate }}</span>
                                    </div>
                                    <div class="date-row">
                                        <span class="date-label">END:</span>
                                        <span class="date-val">{{ $endDate }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- 6. Status (Left-Aligned Stack) -->
                            <td class="col-align-left">
                                <div class="status-col-cell">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="status-badge {{ $isActive ? 'status-active' : 'status-inactive' }}">
                                            <i class="fa-solid fa-circle" style="font-size: 6px;"></i>
                                            {{ $isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                        <span class="paid-badge {{ $isPaid ? 'paid-yes' : 'paid-no' }}">
                                            {{ $isPaid ? 'Paid' : 'Unpaid' }}
                                        </span>
                                    </div>

                                    @if(!empty($operationType))
                                        <span class="badge bg-light text-dark border" style="font-size: 0.7rem; font-weight: 600;">
                                            {{ $operationType }}
                                        </span>
                                    @endif

                                    @if(!empty($endDate) && $endDate !== 'N/A' && !empty($learnerId))
                                        <div class="status-extension-wrap">
                                            {!! getUserStatusWithSpan($endDate, $learnerId) !!}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- 7. Action Icons (Centered) -->
                            <td class="col-align-center">
                                <ul class="actionalbls">
                                    @if(!empty($cleanPhone))
                                        <li>
                                            <a href="https://wa.me/91{{ $cleanPhone }}" target="_blank" data-bs-toggle="tooltip" title="Chat on WhatsApp" class="btn-wa">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </a>
                                        </li>
                                    @endif
                                    @if($learnerId)
                                        <li>
                                            <a href="{{ route('learners.show', $learnerId) }}" data-bs-toggle="tooltip" title="View Learner Profile">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('learners.edit.plan', $learnerId) }}" data-bs-toggle="tooltip" title="Edit Plan">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('learner.script')
@endsection