@extends('layouts.library')
@section('content')

<style>
    .activity-filter-note {
        background: var(--c5);
        border: 1px solid #dedede;
        border-left: 4px solid var(--c8);
        border-radius: .5rem;
        padding: .75rem 1rem;
    }

    .activity-date-header {
        font-size: .85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--c8);
        margin-bottom: .75rem;
        padding-bottom: .35rem;
        border-bottom: 1px solid #e5e5e5;
    }

    .activity-card {
        background: var(--c5);
        border: 1px solid #e5e5e5;
        border-radius: .5rem;
        padding: .85rem 1.15rem;
        margin-bottom: .75rem;
        border-left-width: 4px;
        border-left-style: solid;
    }

    .activity-card .activity-message {
        color: #6c757d;
    }

    .activity-card .badge {
        font-weight: 500;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        @if($filterLearnerId)
        <div class="activity-filter-note d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <span>Showing activity for <strong>{{ $filterLearnerName ?? ('Learner #'.$filterLearnerId) }}</strong> only.</span>
            <a href="{{ route('activities.all', request()->except(['learner_id', 'page'])) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-times"></i> Clear Filter
            </a>
        </div>
        @endif

        <div class="filter-box mb-4">
            <form action="{{ route('activities.all') }}" method="GET">
                <input type="hidden" name="learner_id" value="{{ $filterLearnerId }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <label for="search" class="form-label">Search by Name, Mobile or Seat</label>
                        <input type="text" class="form-control" name="search" id="search" placeholder="Search activity"
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
                        <button class="btn btn-primary w-100"><i class="fa fa-search"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-12">
        @forelse($activities as $dateHeader => $items)
        <div class="mb-4">
            <h6 class="activity-date-header">{{ $dateHeader }}</h6>

            @foreach($items as $item)
            <div class="activity-card" style="border-left-color: {{ $item['color_code'] }}">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                    <strong style="color: {{ $item['color_code'] }}">{{ $item['operation_type'] }}</strong>
                    <small class="text-muted"><i class="fa fa-clock-o"></i> {{ $item['time'] }}</small>
                </div>
                <div class="mt-2 d-flex align-items-center flex-wrap gap-2">
                    <span class="badge bg-light text-dark border">Seat {{ $item['seat'] }}</span>
                    <span class="fw-semibold">{{ $item['learner_name'] }}</span>
                    <span class="activity-message">{!! $item['message'] !!}</span>
                </div>
                <div class="mt-2">
                    <small class="text-muted"><i class="fa fa-user-o"></i> By {{ $item['updated_by_name'] }}</small>
                </div>
            </div>
            @endforeach
        </div>
        @empty
        <div class="card text-center">
            <div class="card-body py-5">
                <i class="fa fa-history fa-2x text-muted mb-2 d-block"></i>
                <p class="text-muted mb-0">No Activity Found</p>
            </div>
        </div>
        @endforelse

        <div class="d-flex justify-content-center mt-4">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@endsection
