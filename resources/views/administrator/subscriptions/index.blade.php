@extends('layouts.admin')

@section('content')

<div class="row">
    <div class="col-lg-12">
        <!-- Heading List matching Library List UI -->
        <div class="heading-list py-4">
            <h4 class="">Library Subscriptions List</h4>
            <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-primary button w-15"><i class="fa-solid fa-plus"></i> Add Subscription</a>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Table Responsive matching Library List UI -->
        <div class="table-responsive mb-4">
            <table class="table text-center" id="datatable">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Subscription Name</th>
                        <th>Monthly Price</th>
                        <th>Yearly Price</th>
                        <th>Max Seats</th>
                        <th>Max Branches</th>
                        <th>Permissions</th>
                        <th>Status</th>
                        <th style="width:20%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subscriptions as $key => $sub)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>
                            <span class="truncate d-block m-auto fw-bold" data-bs-toggle="tooltip" data-bs-title="{{ $sub->name }}" data-bs-placement="bottom">
                                <i class="fa-solid fa-cubes me-1 text-primary"></i> {{ $sub->name }}
                            </span>
                            @if($sub->plan_description)
                                <small class="text-muted d-block" style="font-size: 11px;">{{ $sub->plan_description }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold text-success">₹{{ number_format($sub->monthly_fees, 2) }}</span>
                        </td>
                        <td>
                            @if($sub->yearly_fees > 0)
                                <span class="fw-bold text-primary">₹{{ number_format($sub->yearly_fees, 2) }}</span>
                            @else
                                <small class="text-muted">N/A</small>
                            @endif
                        </td>
                        <td>
                            <small>{{ $sub->max_seats ? $sub->max_seats . ' Seats' : 'Unlimited' }}</small>
                        </td>
                        <td>
                            <small>{{ $sub->max_branches ? $sub->max_branches . ' Branches' : 'Unlimited' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-primary text-white rounded-pill px-3 py-1">
                                <i class="fa-solid fa-key me-1"></i> {{ $sub->permissions->count() }} Perms
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('subscriptions.toggle-status', $sub->id) }}" class="text-decoration-none" title="Click to toggle status">
                                @if(!$sub->trashed())
                                    <small class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Active</small>
                                @else
                                    <small class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Deactive</small>
                                @endif
                            </a>
                        </td>
                        <td>
                            <ul class="actionalbls">
                                <li>
                                    <a href="{{ route('subscriptions.edit', $sub->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit Subscription & Permissions"><i class="fas fa-edit"></i></a>
                                </li>
                                <li>
                                    <a href="{{ route('subscriptions.destroy', $sub->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete Plan Permanently" onclick="return confirm('Are you sure you want to delete this subscription plan permanently?');"><i class="fas fa-trash"></i></a>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        if ($('#datatable').length && !$.fn.DataTable.isDataTable('#datatable')) {
            $('#datatable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "responsive": true
            });
        }
    });
</script>

@endsection
