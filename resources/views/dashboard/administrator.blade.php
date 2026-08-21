@extends('layouts.admin')

@section('title', 'Admin Executive Dashboard')

@section('content')

<style>
    :root {
        --navy-primary: #18225f;
        --navy-dark: #0f1743;
        --teal-accent: #34939F;
        --teal-light: #e6f5f7;
        --card-border: #e2e8f0;
    }

    /* Executive Hero Welcome Banner - High Density */
    .hero-banner {
        background: linear-gradient(135deg, #18225f 0%, #1e2b7a 50%, #2a3c9e 100%) !important;
        border-radius: 16px !important;
        padding: 1.15rem 1.5rem !important;
        color: #ffffff !important;
        box-shadow: 0 8px 24px rgba(24, 34, 95, 0.12) !important;
        position: relative !important;
        overflow: hidden !important;
        margin-bottom: 1.25rem !important;
    }

    .hero-banner::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(52, 147, 159, 0.3) 0%, rgba(24, 34, 95, 0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .hero-banner h2, .hero-banner .hero-title, .hero-title {
        color: #ffffff !important;
        font-family: 'Outfit', sans-serif !important;
        font-weight: 800 !important;
        font-size: 1.35rem !important;
        letter-spacing: -0.4px !important;
        margin-bottom: 0.2rem !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
    }

    .hero-banner p, .hero-banner .hero-subtitle, .hero-subtitle {
        color: #f8fafc !important;
        font-family: 'Mulish', sans-serif !important;
        font-size: 0.85rem !important;
        opacity: 1 !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
    }

    /* Compact Metric Cards */
    .metric-card {
        background: #ffffff !important;
        border-radius: 14px !important;
        border: 1px solid var(--card-border) !important;
        padding: 0.9rem 1.1rem !important;
        box-shadow: 0 3px 15px rgba(24, 34, 95, 0.03) !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        position: relative !important;
        overflow: hidden !important;
        height: 100% !important;
    }

    .metric-card:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 22px rgba(24, 34, 95, 0.08) !important;
        border-color: #cbd5e1 !important;
    }

    .metric-icon-box {
        width: 42px !important;
        height: 42px !important;
        border-radius: 12px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.15rem !important;
        transition: transform 0.2s ease !important;
    }

    .metric-card:hover .metric-icon-box {
        transform: scale(1.06) !important;
    }

    .metric-label {
        font-family: 'Outfit', sans-serif !important;
        font-weight: 700 !important;
        font-size: 0.72rem !important;
        color: #64748b !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 0.25rem !important;
    }

    .metric-value {
        font-family: 'Outfit', sans-serif !important;
        font-weight: 800 !important;
        font-size: 1.45rem !important;
        color: var(--navy-primary) !important;
        line-height: 1.15 !important;
    }

    /* Section Cards */
    .dashboard-card {
        background: #ffffff !important;
        border-radius: 16px !important;
        border: 1px solid var(--card-border) !important;
        box-shadow: 0 3px 15px rgba(24, 34, 95, 0.03) !important;
        margin-bottom: 1.25rem !important;
        overflow: hidden !important;
    }

    .dashboard-card-header {
        background: #ffffff !important;
        border-bottom: 1px solid #f1f5f9 !important;
        padding: 0.85rem 1.2rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
    }

    .dashboard-card-title {
        font-family: 'Outfit', sans-serif !important;
        font-weight: 700 !important;
        color: var(--navy-primary) !important;
        font-size: 0.98rem !important;
        margin-bottom: 0 !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
    }

    /* Plan Performance Grid */
    .plan-perf-card {
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        background: #ffffff !important;
        padding: 0.85rem 1rem !important;
        transition: all 0.2s ease !important;
        position: relative !important;
    }

    .plan-perf-card:hover {
        border-color: var(--teal-accent) !important;
        box-shadow: 0 4px 15px rgba(52, 147, 159, 0.08) !important;
    }

    .plan-title {
        font-family: 'Outfit', sans-serif !important;
        font-weight: 700 !important;
        color: var(--navy-primary) !important;
        font-size: 0.9rem !important;
    }

    .revenue-pill {
        background: linear-gradient(135deg, #18225f 0%, #34939F 100%) !important;
        color: #ffffff !important;
        font-family: 'Outfit', sans-serif !important;
        font-weight: 700 !important;
        font-size: 0.78rem !important;
        padding: 0.25rem 0.75rem !important;
        border-radius: 20px !important;
    }

    /* Monthly Progress Bars */
    .month-card {
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        padding: 0.75rem 0.85rem !important;
        transition: all 0.2s ease !important;
    }

    .month-card:hover {
        background: #ffffff !important;
        border-color: #cbd5e1 !important;
        box-shadow: 0 3px 12px rgba(24, 34, 95, 0.04) !important;
    }

    .progress-bar-bg {
        height: 6px !important;
        border-radius: 10px !important;
        background: #e2e8f0 !important;
        overflow: hidden !important;
        margin-top: 0.4rem !important;
    }

    .progress-bar-fill {
        height: 100% !important;
        border-radius: 10px !important;
        background: linear-gradient(90deg, #18225f 0%, #34939F 100%) !important;
    }

    /* High-Density Compact Table Styling */
    .table th, .table thead th {
        font-size: 11px !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        background-color: #18225f !important;
        border-bottom: 2px solid #18225f !important;
        padding: 0.55rem 0.75rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.4px !important;
    }

    .table td {
        font-size: 0.82rem !important;
        vertical-align: middle !important;
        padding: 0.55rem 0.75rem !important;
        color: #334155 !important;
    }
</style>

<div class="container-fluid px-0 py-2">

    <!-- Executive Hero Welcome Banner -->
    <div class="hero-banner">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill bg-white text-dark px-3 py-1 fw-bold font-outfit" style="font-size: 11px; letter-spacing: 0.5px;">SYSTEM OVERVIEW</span>
                    <span class="badge rounded-pill px-3 py-1 font-outfit" style="background: rgba(255,255,255,0.2); font-size: 11px;"><i class="fa-solid fa-clock me-1"></i> Live Metrics</span>
                </div>
                <h2 class="hero-title">Welcome back, Administrator 👋</h2>
                <p class="hero-subtitle mb-0">Here is your live real-time subscription performance, revenue breakdown, and registration progress.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <div class="d-inline-flex flex-column align-items-lg-end bg-white bg-opacity-10 backdrop-blur rounded-4 p-3 border border-white border-opacity-25">
                    <small class="text-white-50 font-outfit text-uppercase fw-bold" style="font-size: 11px;">Current Date</small>
                    <span class="fw-bold font-outfit fs-5 text-white">{{ date('F d, Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Cards (4 Columns Grid) -->
    <div class="row g-3 mb-4">
        <!-- Total Registrations -->
        <div class="col-xl-3 col-md-6">
            <div class="metric-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Total Libraries</div>
                    <div class="metric-value">{{ number_format($totalregistration) }}</div>
                    <div class="small text-muted mt-1"><i class="fa-solid fa-building-columns text-primary me-1"></i> Registered System Libraries</div>
                </div>
                <div class="metric-icon-box" style="background: #eef2ff; color: #18225f;">
                    <i class="fa-solid fa-landmark"></i>
                </div>
            </div>
        </div>

        <!-- Total Paid -->
        <div class="col-xl-3 col-md-6">
            <div class="metric-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Active Paid Accounts</div>
                    <div class="metric-value" style="color: #16a34a;">{{ number_format($paidregistration) }}</div>
                    <div class="small text-success mt-1"><i class="fa-solid fa-shield-check me-1"></i> Verified Active Subscriptions</div>
                </div>
                <div class="metric-icon-box" style="background: #f0fdf4; color: #16a34a;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>

        <!-- Total Unpaid -->
        <div class="col-xl-3 col-md-6">
            <div class="metric-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Pending / Unpaid</div>
                    <div class="metric-value" style="color: #dc3545;">{{ number_format($unpaidregistration) }}</div>
                    <div class="small text-danger mt-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Requires Payment Action</div>
                </div>
                <div class="metric-icon-box" style="background: #fef2f2; color: #dc3545;">
                    <i class="fa-solid fa-circle-exmark"></i>
                </div>
            </div>
        </div>

        <!-- Overall Revenue -->
        <div class="col-xl-3 col-md-6">
            <div class="metric-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Overall Revenue</div>
                    <div class="metric-value" style="color: #18225f;">₹{{ number_format($overallRevenue, 2) }}</div>
                    <div class="small text-muted mt-1"><i class="fa-solid fa-vault text-primary me-1"></i> Total Subscription Collections</div>
                </div>
                <div class="metric-icon-box" style="background: #f0fdfa; color: #34939F;">
                    <i class="fa-solid fa-indian-rupee-sign"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 1: PLAN-WISE OVERALL REVENUE & REGISTRATION SUMMARY -->
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <h5 class="dashboard-card-title">
                <i class="fa-solid fa-cubes text-primary"></i> Plan-wise Overall Revenue & Registration Summary
            </h5>
            <a href="{{ route('admin.subscriptions') }}" class="btn btn-sm btn-primary button px-3 rounded-pill fw-bold font-outfit">
                <i class="fa-solid fa-gear me-1"></i> Manage Subscription Plans
            </a>
        </div>
        <div class="p-4">
            <!-- Cards Grid for Each Subscription Plan -->
            <div class="row g-3 mb-4">
                @foreach($planwiseData as $pData)
                    <div class="col-lg-4 col-md-6">
                        <div class="plan-perf-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="plan-title">
                                    <i class="fa-solid fa-box-open me-2 text-primary"></i> {{ $pData['plan']->name }}
                                </div>
                                <span class="revenue-pill">₹{{ number_format($pData['revenue'], 2) }}</span>
                            </div>

                            <div class="row g-2 text-center">
                                <div class="col-4">
                                    <div class="bg-light p-2 rounded-3 border">
                                        <div class="text-muted fw-bold" style="font-size: 10px;">TOTAL</div>
                                        <div class="fw-bold text-dark fs-6">{{ $pData['total_registrations'] }}</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-light p-2 rounded-3 border">
                                        <div class="text-success fw-bold" style="font-size: 10px;">ACTIVE</div>
                                        <div class="fw-bold text-success fs-6">{{ $pData['active_libraries'] }}</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-light p-2 rounded-3 border">
                                        <div class="text-primary fw-bold" style="font-size: 10px;">PAID</div>
                                        <div class="fw-bold text-primary fs-6">{{ $pData['paid_libraries'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Full Plan Summary Table -->
            <div class="table-responsive rounded-3 border">
                <table class="table text-center table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th class="text-start">Subscription Plan</th>
                            <th>Monthly Fee</th>
                            <th>Yearly Fee</th>
                            <th>Total Libraries</th>
                            <th>Active Count</th>
                            <th>Paid Count</th>
                            <th class="text-end">Overall Collections</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($planwiseData as $index => $pData)
                            <tr>
                                <td class="fw-bold text-muted">{{ $index + 1 }}</td>
                                <td class="fw-bold text-dark text-start" style="font-family: 'Outfit', sans-serif;">
                                    <i class="fa-solid fa-cube me-2 text-primary"></i> {{ $pData['plan']->name }}
                                </td>
                                <td class="fw-bold text-success">₹{{ number_format($pData['plan']->monthly_fees, 2) }}</td>
                                <td class="fw-bold text-primary">₹{{ number_format($pData['plan']->yearly_fees, 2) }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border px-3 py-1 rounded-pill fw-bold">{{ $pData['total_registrations'] }} Libraries</span>
                                </td>
                                <td>
                                    <span class="badge bg-success text-white rounded-pill px-3 py-1">{{ $pData['active_libraries'] }} Active</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary text-white rounded-pill px-3 py-1">{{ $pData['paid_libraries'] }} Paid</span>
                                </td>
                                <td class="fw-bold text-end fs-6" style="color: #18225f;">
                                    ₹{{ number_format($pData['revenue'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECTION 2: MONTHLY REGISTRATION PROGRESS -->
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <h5 class="dashboard-card-title">
                <i class="fa-solid fa-chart-line text-primary"></i> Monthly Registration Progress (Year {{ $selectedYear }})
            </h5>

            <!-- Year Selector Filter Form -->
            <form action="{{ route('home') }}" method="GET" class="d-flex align-items-center gap-2">
                <label for="year" class="small fw-bold text-muted mb-0 font-outfit">Filter Year:</label>
                <select name="year" id="year" class="form-select form-select-sm rounded-pill px-3 fw-bold" onchange="this.form.submit()" style="width: 120px; border-color: #cbd5e1;">
                    @foreach([2024, 2025, 2026, 2027] as $yr)
                        <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>Year {{ $yr }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="p-4">
            <div class="row g-3">
                @foreach($monthlyProgress as $mProg)
                    <div class="col-xl-3 col-md-4 col-sm-6">
                        <div class="month-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold" style="color: #18225f; font-family: 'Outfit', sans-serif;">{{ $mProg['month_name'] }}</span>
                                <span class="badge rounded-pill px-3 py-1" style="background-color: #18225f; color: #fff;">{{ $mProg['count'] }} Registrations</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: {{ $mProg['percentage'] }}%;"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- SECTION 3: RECENT REGISTRATIONS & UPCOMING RENEWALS -->
    <div class="row g-3 align-items-stretch">
        <!-- Recent Registrations -->
        <div class="col-lg-6 d-flex">
            <div class="dashboard-card h-100 w-100 d-flex flex-column mb-0">
                <div class="dashboard-card-header">
                    <h5 class="dashboard-card-title">
                        <i class="fa-solid fa-user-plus text-primary"></i> Recent Registrations (Last 5)
                    </h5>
                    <a href="{{ route('library') }}" class="btn btn-sm btn-primary button px-3 rounded-pill fw-bold">
                        View All <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="p-3 flex-grow-1 d-flex flex-column">
                    <div class="table-responsive my-auto">
                        <table class="table text-center table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th class="text-start">Library Name</th>
                                    <th>Plan</th>
                                    <th>Status</th>
                                    <th style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_registrations as $key => $reg)
                                    <tr>
                                        <td class="fw-bold text-muted">{{ $key + 1 }}</td>
                                        <td class="fw-bold text-dark text-start" style="font-family: 'Outfit', sans-serif;">
                                            {{ $reg->library_name ?? $reg->name ?? 'Library' }}
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill">{{ $reg->subscription->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            @if(($reg->status ?? 0) == 1)
                                                <small class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Active</small>
                                            @else
                                                <small class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Deactive</small>
                                            @endif
                                        </td>
                                        <td>
                                            <ul class="actionalbls justify-content-center">
                                                <li>
                                                    <a href="{{ route('library.show', $reg->id) }}" title="View Details"><i class="fas fa-eye"></i></a>
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No recent registrations found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Renewals -->
        <div class="col-lg-6 d-flex">
            <div class="dashboard-card h-100 w-100 d-flex flex-column mb-0">
                <div class="dashboard-card-header">
                    <h5 class="dashboard-card-title">
                        <i class="fa-solid fa-clock-rotate-left text-primary"></i> Upcoming Renewals
                    </h5>
                    <a href="{{ route('library') }}" class="btn btn-sm btn-outline-primary button px-3 rounded-pill fw-bold">
                        View All <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="p-3 flex-grow-1 d-flex flex-column justify-content-center">
                    @if(count($upcoming_registration) > 0)
                        <div class="table-responsive my-auto">
                            <table class="table text-center table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th class="text-start">Library Name</th>
                                        <th>Plan</th>
                                        <th>Payment Status</th>
                                        <th style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcoming_registration as $key => $up)
                                        <tr>
                                            <td class="fw-bold text-muted">{{ $key + 1 }}</td>
                                            <td class="fw-bold text-dark text-start" style="font-family: 'Outfit', sans-serif;">
                                                {{ $up->library_name ?? $up->name ?? 'Library' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border px-2 py-1 rounded-pill">{{ $up->subscription->name ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                @if(($up->is_paid ?? 0) == 1)
                                                    <small class="text-success fw-bold">Paid</small>
                                                @else
                                                    <small class="text-danger fw-bold">Unpaid</small>
                                                @endif
                                            </td>
                                            <td>
                                                <ul class="actionalbls justify-content-center">
                                                    <li>
                                                        <a href="{{ route('library.show', $up->id) }}" title="View Details"><i class="fas fa-eye"></i></a>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <!-- Centered Placeholder Illustration & Message for Empty State -->
                        <div class="text-center py-5 my-auto">
                            <div class="mb-3">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 72px; height: 72px; background-color: #f1f5f9;">
                                    <i class="fa-solid fa-calendar-check" style="font-size: 2rem; color: #34939F;"></i>
                                </div>
                            </div>
                            <h6 class="fw-bold font-outfit mb-1" style="color: #18225f; font-size: 1.05rem;">No Upcoming Renewals</h6>
                            <p class="text-muted small mb-0" style="max-width: 280px; margin: 0 auto; line-height: 1.5;">
                                All active library subscriptions are fully up to date for the next 10 days.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

@endsection