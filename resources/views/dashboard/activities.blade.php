@extends('layouts.library')
@section('content')

@if (session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif

<div class="row">
    <div class="col-lg-12 d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h4 class="mb-0">All Activities</h4>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="col-lg-12">
        @if($filterLearnerId)
            <div class="alert alert-info d-flex align-items-center justify-content-between py-2">
                <span>Showing activity for <strong>{{ $filterLearnerName ?? ('Learner #'.$filterLearnerId) }}</strong> only.</span>
                <a href="{{ route('activities.all', request()->except(['learner_id', 'page'])) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        @endif

        <div class="filter-box mb-4">
            <form action="{{ route('activities.all') }}" method="GET">
                <input type="hidden" name="learner_id" value="{{ $filterLearnerId }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <label for="search">Search by Name, Mobile or Seat</label>
                        <input type="text" class="form-control" name="search" id="search" placeholder="Search activity"
                            value="{{ request('search') }}">
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label for="operation">Activity Type</label>
                        <select name="operation" id="operation" class="form-select">
                            <option value="">All Activities</option>
                            @foreach($operationOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('operation') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label for="date">Date</label>
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
        <h6 class="text-muted mb-3">{{ $dateHeader }}</h6>
        <div class="mb-4">
            @foreach($items as $item)
            <div class="card mb-2">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                        <strong style="color: {{ $item['color_code'] }}">{{ $item['operation_type'] }}</strong>
                        <small class="text-muted"><i class="fa fa-clock-o"></i> {{ $item['time'] }}</small>
                    </div>
                    <div class="mt-1">
                        <span class="badge bg-light text-dark border">Seat {{ $item['seat'] }}</span>
                        <span class="fw-semibold">{{ $item['learner_name'] }}</span>
                        <span class="text-muted">{!! $item['message'] !!}</span>
                    </div>
                    <div class="mt-1">
                        <small class="text-muted"><i class="fa fa-user-o"></i> By {{ $item['updated_by_name'] }}</small>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @empty
        <div class="card text-center">
            <div class="card-body">No Activity Found</div>
        </div>
        @endforelse

        <div class="d-flex justify-content-center">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@endsection
