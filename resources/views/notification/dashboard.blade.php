@extends('layouts.library')
@section('content')
<style>
    .stat-card {
        padding: 1rem;
        border: none;
        border-radius: 16px;
        transition: transform 0.2s, box-shadow 0.2s;
        background: #fff;
        border: 1px solid #dedede;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin: 0.5rem 0;
    }

    .stat-label {
        color: #6c757d;
        font-size: 0.875rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .chart-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        height: 100%;
    }

    .table-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        margin-top: 2rem;
    }

    .nav-tabs .nav-link {
        color: #495057;
        font-weight: 500;
        border: none;
        border-radius: 8px 8px 0 0;
        padding: 0.75rem 1.5rem;
    }

    .nav-tabs .nav-link.active {
        background-color: white;
        border-bottom: 3px solid #667eea;
        color: #667eea;
    }

    .badge-status {
        padding: 0.35rem 0.65rem;
        font-weight: 500;
        font-size: 0.75rem;
    }

    .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        white-space: nowrap;
        font-family: 'Outfit', 'sans-sarif';
        text-align:center;
    }

    .table thead td {
        text-align: left !important;
        font-family: 'Outfit', 'sans-sarif'
    }

    .table tbody tr {
        transition: background-color 0.2s;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .message-content {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .overview-icon {
        font-size: 2.5rem;
        opacity: 0.2;
        position: absolute;
        right: 1rem;
        top: 1rem;
    }

    .percentage-badge {
        font-size: 0.75rem;
        margin-top: 0.5rem;
    }

</style>

<div class="row">
    <div class="col-lg-12 text-end">
        <a href="{{route('notifications.settings')}}" class="btn btn-primary export" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Notification Settings"><i class="fa-solid fa-cog"></i></a>
        <a href="{{route('notifications.subscription')}}" class="btn btn-primary export" data-bs-placement="bottom" data-bs-toggle="tooltip" data-bs-title="Buy More Messages"><i class="fa-solid fa-bag-shopping"></i></a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-12">
        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
            @if(wabaNotificationActive())
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="whatsapp-tab" data-bs-toggle="tab" data-bs-target="#whatsapp" type="button">
                    WhatsApp Dashboard
                </button>
            </li>
            @endif
            @if(textNotificationActive())
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sms-tab" data-bs-toggle="tab" data-bs-target="#sms" type="button">
                    Text Message Dashboard
                </button>
            </li>
            @endif
            {{-- <li class="nav-item" role="presentation">
                <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button">
                    Email Dashboard
                </button>
            </li> --}}

        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="dashboardTabsContent">
            @php
                $total = $wabaRemaining + $wabaUsed;
                $wabapercentage = $total > 0 ? ($wabaUsed * 100) / $total : 0;
                $wabapercentageRem = $total > 0 ? ($wabaRemaining * 100) / $total : 0;
                $textTotal=$textRemaining+$textUsed;
                $textPercentage=$textTotal > 0 ? ($textUsed * 100) / $textTotal : 0;
                $textPerRemain=$textTotal > 0 ? ($textRemaining * 100) / $textTotal : 0;
            @endphp



            <!-- WhatsApp Dashboard -->
            <div class="tab-pane fade show active" id="whatsapp" role="tabpanel">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="card-body text-center">
                                <p class="stat-label mb-2">Total Messages Purchased</p>
                                <h2 class="stat-value text-dark">{{$wabaRemaining+$wabaUsed}}</h2>
                                <span class="badge bg-primary-subtle text-primary percentage-badge">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="card-body text-center">
                                <p class="stat-label mb-2">Utilized</p>
                                <h2 class="stat-value text-primary">{{$wabaUsed}}</h2>
                                <span class="badge bg-primary-subtle text-primary percentage-badge">{{ number_format($wabapercentage, 2) }}%</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="card-body text-center">
                                <p class="stat-label mb-2">Remaining</p>
                                <h2 class="stat-value text-success">{{$wabaRemaining}}</h2>
                                <span class="badge bg-success-subtle text-success percentage-badge">{{ number_format($wabapercentageRem, 2) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Unique ID</th>
                                <th>Status</th>
                                <th>Message</th>
                                <th>Sent At</th>
                                <th>Delivery</th>
                                <th>Seat No</th>
                                <th>Activity</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($logs as $log)
                           
                            <tr>

                                <td class="fw-semibold">#{{ $log->unique_id }}</td>

                                <td>
                                    <span class="badge bg-success">
                                        {{ ucfirst($log->message_status) }}
                                    </span>
                                </td>

                                <td style="max-width: 260px;">
                                    <div class="text-muted small">
                                        {!! nl2br(e($log->message_content)) !!}
                                    </div>
                                </td>

                                <td>
                                    <span class="text-secondary small">
                                        {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y h:i A') }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge bg-info text-dark">
                                        {{ $log->delivery_label }}
                                    </span>
                                </td>

                                <td class="fw-bold">{{ $log->seat_no ?? 'GEN' }}</td>

                                <td>
                                    <span class="badge bg-primary">
                                        {{ $log->operation_name }}
                                    </span>
                                </td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted p-4">
                                    No WhatsApp logs found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                
            </div>

            <!-- SMS Dashboard -->
            <div class="tab-pane fade" id="sms" role="tabpanel">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="card-body text-center">
                                <p class="stat-label mb-2">Total Messages Purchased</p>
                                <h2 class="stat-value text-dark">{{$textRemaining+$textUsed}}</h2>
                                <span class="badge bg-primary-subtle text-primary percentage-badge">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="card-body text-center">
                                <p class="stat-label mb-2">Utilized</p>
                                <h2 class="stat-value text-primary">{{$textUsed}}</h2>
                                <span class="badge bg-primary-subtle text-primary percentage-badge">{{ number_format($textPercentage, 2) }}%</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="card-body text-center">
                                <p class="stat-label mb-2">Remaining</p>
                                <h2 class="stat-value text-success">{{$textRemaining}}</h2>
                                <span class="badge bg-success-subtle text-success percentage-badge">{{ number_format($textPerRemain, 2) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Unique ID</th>
                                <th>Status</th>
                                <th>Message</th>
                                <th>Sent At</th>
                                <th>Delivery</th>
                                <th>Seat No</th>
                                <th>Activity</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($textlogs as $log)
                            <tr>

                                <td class="fw-semibold">#{{ $log->unique_id }}</td>

                                <td>
                                    <span class="badge bg-success">
                                        {{ ucfirst($log->message_status) }}
                                    </span>
                                </td>

                                <td style="max-width: 260px;">
                                    <div class="text-muted small">
                                        {!! nl2br(e($log->message_content)) !!}
                                    </div>
                                </td>

                                <td>
                                    <span class="text-secondary small">
                                        {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y h:i A') }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge bg-info text-dark">
                                        {{ $log->delivery_label }}
                                    </span>
                                </td>

                                <td class="fw-bold">{{ $log->seat_no ?? 'GEN'}}</td>

                                <td>
                                    <span class="badge bg-primary">{{ $log->operation_name }}</span>
                                </td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted p-4">
                                    No SMS logs found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>


            </div>
            <!-- Email Dashboard -->
            <div class="tab-pane fade" id="email" role="tabpanel">

            </div>
        </div>
    </div>
</div>



@endsection
