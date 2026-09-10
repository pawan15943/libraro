@extends('layouts.library')

@section('title', 'Activities Logs')

@section('content')

<!-- External Strictly Scoped Stylesheet -->
<link rel="stylesheet" href="{{ asset('public/css/activities-page.css') }}?v={{ time() }}">

<div class="libraro-activities-page container-fluid px-0 py-1">

    @if($filterLearnerId)
        <div class="activity-filter-note d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-user-tag text-primary" style="font-size: 14px;"></i>
                <span>Activity for: <strong>{{ $filterLearnerName ?? ('Learner #'.$filterLearnerId) }}</strong></span>
            </div>
            <a href="{{ route('activities.all', request()->except(['learner_id', 'page'])) }}" class="btn-clear-filter">
                <i class="fa-solid fa-xmark"></i> Clear
            </a>
        </div>
    @endif

    <!-- Compact Mobile-First Filter Bar -->
    <div class="activity-filter-card">
        <form action="{{ route('activities.all') }}" method="GET">
            <input type="hidden" name="learner_id" value="{{ $filterLearnerId }}">
            <div class="row g-2 align-items-center filter-row-desktop">
                <div class="col-12 col-md-5 filter-search-col">
                    <div class="activity-input-group">
                        <i class="fa-solid fa-magnifying-glass activity-input-icon"></i>
                        <input type="text" class="activity-input-control" name="search" id="search"
                            placeholder="Search Name, Phone, Seat..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-6 col-md-3 filter-op-col">
                    <div class="activity-input-group">
                        <i class="fa-solid fa-filter activity-input-icon"></i>
                        <select name="operation" id="operation" class="activity-input-control">
                            <option value="">All Activities</option>
                            @foreach($operationOptions as $key => $label)
                                <option value="{{ $key }}" {{ request('operation') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-6 col-md-2 filter-date-col">
                    <div class="activity-input-group">
                        <i class="fa-regular fa-calendar activity-input-icon"></i>
                        <input type="date" class="activity-input-control" name="date" id="date" value="{{ request('date') }}">
                    </div>
                </div>

                <div class="col-12 col-md-auto filter-btn-col">
                    <div class="d-flex align-items-center gap-1 w-100">
                        <button type="submit" class="btn-filter-submit">
                            <i class="fa-solid fa-filter"></i>
                            <span>Filter</span>
                        </button>
                        @if(request()->hasAny(['search', 'operation', 'date', 'learner_id']))
                            <a href="{{ route('activities.all') }}" class="btn-filter-reset" title="Reset Filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Timeline & Log Items List -->
    <div class="row">
        <div class="col-12">
            @forelse($activities as $dateHeader => $items)
                <div class="mb-3">
                    <div class="activity-date-divider">
                        <span class="activity-date-pill">
                            <i class="fa-regular fa-calendar-days me-1" style="color: #34939F;"></i> {{ $dateHeader }}
                        </span>
                    </div>

                    @foreach($items as $item)
                        <div class="activity-card" style="border-left-color: {{ $item['color_code'] }} !important;">
                            <!-- Single-Line Header: Operation, Seat, Learner & Time -->
                            <div class="activity-card-header">
                                <div class="activity-labels-inline">
                                    <span class="activity-operation-badge" style="background-color: {{ $item['color_code'] }}15; color: {{ $item['color_code'] }}; border: 1px solid {{ $item['color_code'] }}30;">
                                        <i class="fa-solid fa-circle" style="font-size: 6px;"></i>
                                        <span>{{ $item['operation_type'] }}</span>
                                    </span>
                                    <span class="activity-chip seat-chip">
                                        <i class="fa-solid fa-chair me-1"></i> Seat {{ $item['seat'] }}
                                    </span>
                                    <span class="activity-chip learner-chip">
                                        <i class="fa-regular fa-user me-1"></i> {{ $item['learner_name'] }}
                                    </span>
                                </div>
                                <span class="activity-time-pill">
                                    <i class="fa-regular fa-clock me-1"></i> {{ $item['time'] }}
                                </span>
                            </div>

                            <!-- Single-Line Body: Message & Operator -->
                            <div class="activity-card-body">
                                <span class="activity-message-text">{!! $item['message'] !!}</span>
                                <span class="activity-operator-dot">•</span>
                                <span class="activity-operator-info">
                                    <i class="fa-solid fa-user-shield text-muted"></i> By <strong>{{ $item['updated_by_name'] }}</strong>
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <!-- Empty State Card -->
                <div class="activity-empty-card">
                    <div class="activity-empty-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <h6 class="fw-bold font-outfit mb-1" style="color: #18225f;">No Activity Logs Found</h6>
                    <p class="text-muted font-outfit small mb-3">There are no activity records matching your current filter criteria.</p>
                    @if(request()->hasAny(['search', 'operation', 'date', 'learner_id']))
                        <a href="{{ route('activities.all') }}" class="btn btn-sm btn-primary button" style="background: #18225f !important; border-radius: 8px;">
                            <i class="fa-solid fa-rotate-left me-1"></i> Clear Filters
                        </a>
                    @endif
                </div>
            @endforelse

            <div class="d-flex justify-content-center mt-3">
                {{ $logs->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

@endsection
