@extends('layouts.library')

@section('title', 'Website Enquiries')

@section('content')

{{-- Suppress legacy global blocking loader --}}
<style>
    #loaderone, #loader {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }
</style>
<script>
    try {
        var el1 = document.getElementById('loaderone'); if (el1) el1.remove();
        var el0 = document.getElementById('loader'); if (el0) el0.remove();
    } catch(e) {}
</script>

<link rel="stylesheet" href="{{ asset('public/css/library-enquiry.css') }}?v={{ time() }}" />

@php
    $totalEnquiries = $datas->count();
    $todayEnquiries = $datas->filter(function($item) {
        return !empty($item->created_at) && \Carbon\Carbon::parse($item->created_at)->isToday();
    })->count();
    $monthEnquiries = $datas->filter(function($item) {
        return !empty($item->created_at) && \Carbon\Carbon::parse($item->created_at)->isCurrentMonth() && \Carbon\Carbon::parse($item->created_at)->isCurrentYear();
    })->count();
    $topShift = $datas->pluck('planType.name')->filter()->countBy()->sortDesc()->keys()->first() ?? 'None';
@endphp

<div class="library-enquiry-module">
    <!-- Success & Error Alerts -->
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="fa-solid fa-circle-xmark me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Standard Page Header Block per GEMINI.md -->
    <div class="heading-list py-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="mb-1 fw-bold page-title">
                <i class="fa-solid fa-headset me-2 text-teal"></i>Website Enquiries
            </h4>
            <p class="page-subtitle mb-0">Prospective student inquiries submitted from your public library website</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="enquiry-count-badge">
                <i class="fa-solid fa-clipboard-list me-1"></i> Total Records: {{ $totalEnquiries }}
            </span>
        </div>
    </div>

    <!-- Executive Summary KPI Cards -->
    <div class="enquiry-kpi-grid">
        <div class="enquiry-kpi-card">
            <div class="kpi-icon-wrap kpi-icon-navy">
                <i class="fa-solid fa-clipboard-question"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Enquiries</span>
                <span class="kpi-val">{{ $totalEnquiries }}</span>
            </div>
        </div>

        <div class="enquiry-kpi-card">
            <div class="kpi-icon-wrap kpi-icon-teal">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Today's Enquiries</span>
                <span class="kpi-val" style="color: #34939F;">{{ $todayEnquiries }}</span>
            </div>
        </div>

        <div class="enquiry-kpi-card">
            <div class="kpi-icon-wrap kpi-icon-green">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">This Month</span>
                <span class="kpi-val" style="color: #16a34a;">{{ $monthEnquiries }}</span>
            </div>
        </div>

        <div class="enquiry-kpi-card">
            <div class="kpi-icon-wrap kpi-icon-amber">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Top Shift</span>
                <span class="kpi-val text-truncate" style="color: #d97706; font-size: 1.25rem;" title="{{ $topShift }}">{{ $topShift }}</span>
            </div>
        </div>
    </div>

    <!-- Main Table Card Container -->
    <div class="enquiry-card-wrapper mb-4">
        @if($datas->isEmpty())
            <div class="enquiry-empty-state">
                <div class="empty-icon-wrap">
                    <i class="fa-regular fa-comments"></i>
                </div>
                <h5 class="empty-title">No Enquiries Received Yet</h5>
                <p class="empty-subtitle">When prospective learners submit an inquiry from your public website or landing page, they will appear here in real-time.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table text-center" id="datatable">
                    <thead>
                        <tr>
                            <th style="width: 5%;">S.No.</th>
                            <th style="width: 22%;" class="text-start">Enquirer Name</th>
                            <th style="width: 18%;">Contact Details</th>
                            <th style="width: 18%;">Requested Shift</th>
                            <th style="width: 22%;">Message / Query</th>
                            <th style="width: 15%;">Received On</th>
                            <th style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datas as $key => $value)
                            @php
                                $cleanMobile = preg_replace('/[^0-9]/', '', (string)$value->mobile);
                                $waText = urlencode("Hello " . strtoupper($value->name) . ", thank you for inquiring about library admission. We received your request for the " . ($value->planType?->name ?? 'selected') . " shift. How can we assist you today?");
                                $shift = $value->planType;
                                $initials = strtoupper(substr(trim($value->name ?? 'U'), 0, 2));
                                
                                $shiftTiming = '';
                                if ($shift && !empty($shift->start_time) && !empty($shift->end_time)) {
                                    try {
                                        $startTime = \Carbon\Carbon::parse($shift->start_time)->format('h:i A');
                                        $endTime = \Carbon\Carbon::parse($shift->end_time)->format('h:i A');
                                        $shiftTiming = $startTime . ' - ' . $endTime;
                                    } catch (\Throwable $e) {}
                                }
                            @endphp
                            <tr>
                                <td>
                                    <span class="sno-badge">{{ $key + 1 }}</span>
                                </td>
                                <td class="text-start">
                                    <div class="enquirer-profile">
                                        <div class="enquirer-avatar">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <h6 class="enquirer-name">{{ strtoupper($value->name) }}</h6>
                                            <small class="text-muted"><i class="fa-solid fa-id-badge me-1"></i>Enquiry #{{ $value->id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="contact-box">
                                        <a href="tel:{{ $value->mobile }}" class="contact-phone" title="Call">
                                            <i class="fa-solid fa-phone-volume me-1 text-muted"></i>{{ $value->mobile }}
                                        </a>
                                        <a href="https://wa.me/91{{ $cleanMobile }}?text={{ $waText }}" target="_blank" class="quick-contact-btn quick-wa-btn" data-bs-toggle="tooltip" title="WhatsApp Message">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    @if($shift)
                                        <span class="shift-pill">
                                            <i class="fa-regular fa-clock"></i> {{ $shift->name }}
                                        </span>
                                        @if($shiftTiming)
                                            <span class="shift-time-text">{{ $shiftTiming }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">General Enquiry</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="message-bubble" title="{{ $value->enquiry }}">
                                        <i class="fa-solid fa-quote-left text-muted me-1 small"></i>{{ $value->enquiry ?: 'No message provided' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="datetime-box">
                                        <span class="date-primary">
                                            <i class="fa-regular fa-calendar-days text-muted me-1"></i>{{ \Carbon\Carbon::parse($value->created_at)->format('d M Y') }}
                                        </span>
                                        <span class="time-secondary">
                                            {{ \Carbon\Carbon::parse($value->created_at)->format('h:i A') }} ({{ \Carbon\Carbon::parse($value->created_at)->diffForHumans() }})
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <ul class="actionalbls">
                                        <li>
                                            <a href="https://wa.me/91{{ $cleanMobile }}?text={{ $waText }}" target="_blank" class="wa-action" data-bs-toggle="tooltip" title="Chat on WhatsApp">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="tel:{{ $value->mobile }}" class="call-action" data-bs-toggle="tooltip" title="Call Learner">
                                                <i class="fa-solid fa-phone"></i>
                                            </a>
                                        </li>
                                        <li>
                                            <form action="{{ route('library.enquiry.destroy', $value->id) }}" method="POST" class="d-inline m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="delete-btn" data-bs-toggle="tooltip" title="Delete Enquiry" onclick="return confirm('Are you sure you want to delete this enquiry from {{ strtoupper($value->name) }}?');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.jQuery && $.fn.DataTable && $('#datatable').length) {
            $('#datatable').DataTable({
                pageLength: 10,
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search enquiries...",
                    lengthMenu: "Show _MENU_ records",
                    info: "Showing _START_ to _END_ of _TOTAL_ enquiries",
                    paginate: {
                        next: '<i class="fa-solid fa-chevron-right"></i>',
                        previous: '<i class="fa-solid fa-chevron-left"></i>'
                    }
                },
                order: [[5, 'desc']] // Sort by Received On date descending
            });
        }
    });
</script>

@endsection