@extends('layouts.library')

@section('title', 'Activities Logs')

@section('content')

<!-- External Strictly Scoped Stylesheet -->
<link rel="stylesheet" href="{{ asset('public/css/activities-page.css') }}">

<div class="libraro-activities-page container-fluid px-0 py-2">
    <!-- Header Block (Top Right Breadcrumb, No Heading) -->
    <div class="activity-page-header d-flex justify-content-between align-items-center mb-3">
        <div></div>
        
        <div class="activity-top-breadcrumb">
            @if(!empty($breadcrumb) && is_array($breadcrumb))
                @php $i = 0; $total = count($breadcrumb); @endphp
                @foreach($breadcrumb as $label => $url)
                    @php $i++; @endphp
                    @if($i < $total)
                        <a href="{{ $url }}">{{ $label }}</a> / 
                    @else
                        <span>{{ $label }}</span>
                    @endif
                @endforeach
            @else
                <a href="{{ route('library.home') }}">Dashboard</a> / <span>Activities Logs</span>
            @endif
        </div>
    </div>

    @if($filterLearnerId)
        <div class="activity-filter-note d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <span>Showing activity for <strong>{{ $filterLearnerName ?? ('Learner #'.$filterLearnerId) }}</strong> only.</span>
            <a href="{{ route('activities.all', request()->except(['learner_id', 'page'])) }}" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                <i class="fa fa-times me-1"></i> Clear Filter
            </a>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="activity-filter-card">
        <form action="{{ route('activities.all') }}" method="GET">
            <input type="hidden" name="learner_id" value="{{ $filterLearnerId }}">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label for="search" class="form-label">Search by Name, Mobile or Seat</label>
                    <input type="text" class="form-control" name="search" id="search" placeholder="Enter name, phone or seat..."
                        value="{{ request('search') }}">
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="operation" class="form-label">Activity Type</label>
                    <select name="operation" id="operation" class="form-select">
                        <option value="">All Activities</option>
                        @foreach($operationOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('operation') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" id="date" value="{{ request('date') }}">
                </div>

                <div class="col-lg-2 col-md-6">
                    <button class="btn-filter-submit">
                        <i class="fa fa-search"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Timeline & Log Items List -->
    <div class="row">
        <div class="col-12">
            @forelse($activities as $dateHeader => $items)
                <div class="mb-4">
                    <div class="activity-date-header">
                        <i class="fa-regular fa-calendar me-1"></i> {{ $dateHeader }}
                    </div>

                    @foreach($items as $item)
                        <div class="activity-card" style="border-left-color: {{ $item['color_code'] }} !important;">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                <div class="activity-title" style="color: {{ $item['color_code'] }} !important;">
                                    {{ $item['operation_type'] }}
                                </div>
                                <div class="text-secondary small d-flex align-items-center gap-1 font-outfit" style="font-size: 0.78rem;">
                                    <i class="fa-regular fa-clock" style="font-size: 11px;"></i> {{ $item['time'] }}
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                <span class="activity-seat-badge">Seat {{ $item['seat'] }}</span>
                                <span class="activity-learner-name">{{ $item['learner_name'] }}</span>
                                <span class="activity-message">{!! $item['message'] !!}</span>
                            </div>
                            
                            <div class="activity-user-badge">
                                <i class="fa-regular fa-user me-1 text-muted"></i> By {{ $item['updated_by_name'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <!-- Empty State Card -->
                <div class="activity-empty-card">
                    <div class="activity-empty-icon">
                        <i class="fa-solid fa-history"></i>
                    </div>
                    <h5 class="fw-bold font-outfit" style="color: #18225f;">No Activity Logs Found</h5>
                    <p class="text-muted font-outfit small mb-0">There are no activity records matching your current filter criteria.</p>
                </div>
            @endforelse

            <div class="d-flex justify-content-center mt-4">
                {{ $logs->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

@endsection
