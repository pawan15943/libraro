@extends('layouts.admin')

@section('title', 'Notification List')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/admin-notification.css') }}?v={{ time() }}">

<div class="custom-notification-module">
    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Action Top Bar --}}
    <div class="d-flex justify-content-end align-items-center mb-3">
        <a href="{{ route('create.notification') }}" class="btn btn-primary button">
            <i class="fa-solid fa-plus"></i> Add Notification
        </a>
    </div>

    {{-- Desktop Table Block --}}
    <div class="desktop-table-wrapper card-box p-3">
        <div class="table-responsive mb-0">
            <table class="table text-center" id="datatable">
                <thead>
                    <tr>
                        <th style="width: 5%;">S.No.</th>
                        <th style="width: 15%;">Type</th>
                        <th style="width: 20%;">Title</th>
                        <th style="width: 25%;">Description</th>
                        <th style="width: 10%;">Target</th>
                        <th style="width: 15%;">Schedule</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 10%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $index => $item)
                        @php
                            $data = json_decode($item->data, true) ?? [];
                            $now = now();
                            $startDate = \Carbon\Carbon::parse($item->start_date)->startOfDay();
                            $endDate = \Carbon\Carbon::parse($item->end_date)->endOfDay();
                            
                            $isActive = $now->between($startDate, $endDate);
                            $isScheduled = $now->lt($startDate);
                            $isExpired = $now->gt($endDate);

                            $notifType = strtolower($data['notification_type'] ?? 'important');
                            $badgeClass = match($notifType) {
                                'wishes' => 'badge-wishes',
                                'maintenance' => 'badge-maintenance',
                                'offers' => 'badge-offers',
                                default => 'badge-important',
                            };

                            $iconClass = match($notifType) {
                                'wishes' => 'fa-gift',
                                'maintenance' => 'fa-wrench',
                                'offers' => 'fa-tag',
                                default => 'fa-bell',
                            };

                            $guardLabel = match($item->guard) {
                                'library' => 'Library Owners',
                                'learner' => 'Learners',
                                default => 'Website / Admin',
                            };
                            $guardIcon = match($item->guard) {
                                'library' => 'fa-building-columns',
                                'learner' => 'fa-user-graduate',
                                default => 'fa-globe',
                            };
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <span class="badge-type {{ $badgeClass }}">
                                    <i class="fa-solid {{ $iconClass }}"></i> {{ ucfirst($notifType) }}
                                </span>
                            </td>
                            <td class="text-start">
                                <span class="fw-semibold text-dark">{{ $data['title'] ?? 'N/A' }}</span>
                                @if(!empty($data['link']))
                                    <div class="mt-1">
                                        <a href="{{ $data['link'] }}" target="_blank" class="text-primary small text-decoration-none">
                                            <i class="fa-solid fa-link me-1"></i>Link
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td class="text-start">
                                <span class="text-muted small d-inline-block text-truncate" style="max-width: 260px;" title="{{ $data['description'] ?? '' }}">
                                    {{ $data['description'] ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge-guard">
                                    <i class="fa-solid {{ $guardIcon }} me-1"></i>{{ $guardLabel }}
                                </span>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">
                                    {{ \Carbon\Carbon::parse($item->start_date)->format('d M Y') }}
                                </div>
                                <div class="text-muted small">
                                    to {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }}
                                </div>
                            </td>
                            <td>
                                @if(isset($item->status) && (string)$item->status === '0')
                                    <span class="status-pill status-inactive">
                                        <i class="fa-solid fa-circle-xmark" style="font-size: 8px;"></i> Inactive
                                    </span>
                                @else
                                    <span class="status-pill status-active">
                                        <i class="fa-solid fa-circle" style="font-size: 6px;"></i> Active
                                    </span>
                                @endif
                            </td>
                            <td>
                                <ul class="actionalbls">
                                    <li>
                                        <a href="{{ route('notifications.edit', $item->batch_id) }}" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <form action="{{ route('notifications.destroy', $item->batch_id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this notification?');" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="delete-btn" data-bs-toggle="tooltip" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-bell-slash fa-2x mb-2 d-block text-secondary"></i>
                                No notifications found. Click <strong>Add Notification</strong> to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile Card Block (Visible only on screens <= 768px) --}}
    <div class="mobile-notif-cards">
        @forelse($notifications as $index => $item)
            @php
                $data = json_decode($item->data, true) ?? [];
                $now = now();
                $startDate = \Carbon\Carbon::parse($item->start_date)->startOfDay();
                $endDate = \Carbon\Carbon::parse($item->end_date)->endOfDay();
                $isActive = $now->between($startDate, $endDate);
                $isScheduled = $now->lt($startDate);

                $notifType = strtolower($data['notification_type'] ?? 'important');
                $badgeClass = match($notifType) {
                    'wishes' => 'badge-wishes',
                    'maintenance' => 'badge-maintenance',
                    'offers' => 'badge-offers',
                    default => 'badge-important',
                };
                $iconClass = match($notifType) {
                    'wishes' => 'fa-gift',
                    'maintenance' => 'fa-wrench',
                    'offers' => 'fa-tag',
                    default => 'fa-bell',
                };
                $guardLabel = match($item->guard) {
                    'library' => 'Library Owners',
                    'learner' => 'Learners',
                    default => 'Website / Admin',
                };
            @endphp
            <div class="notif-card-item">
                <div class="notif-card-header">
                    <span class="badge-type {{ $badgeClass }}">
                        <i class="fa-solid {{ $iconClass }}"></i> {{ ucfirst($notifType) }}
                    </span>
                    @if(isset($item->status) && (string)$item->status === '0')
                        <span class="status-pill status-inactive">Inactive</span>
                    @else
                        <span class="status-pill status-active">Active</span>
                    @endif
                </div>

                <div class="notif-card-title">{{ $data['title'] ?? 'N/A' }}</div>
                <div class="notif-card-desc">{{ $data['description'] ?? '-' }}</div>

                <div class="mb-2">
                    <span class="badge-guard">
                        <i class="fa-solid fa-users me-1"></i>{{ $guardLabel }}
                    </span>
                    @if(!empty($data['link']))
                        <a href="{{ $data['link'] }}" target="_blank" class="ms-2 small text-primary text-decoration-none">
                            <i class="fa-solid fa-link me-1"></i>View Link
                        </a>
                    @endif
                </div>

                <div class="notif-card-footer">
                    <div>
                        <i class="fa-regular fa-calendar me-1"></i>
                        {{ \Carbon\Carbon::parse($item->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }}
                    </div>
                    <ul class="actionalbls">
                        <li>
                            <a href="{{ route('notifications.edit', $item->batch_id) }}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </li>
                        <li>
                            <form action="{{ route('notifications.destroy', $item->batch_id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this notification?');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="delete-btn" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white rounded-3 p-4">
                <i class="fa-solid fa-bell-slash fa-2x mb-2 d-block text-secondary"></i>
                <p class="text-muted mb-0">No notifications created yet.</p>
            </div>
        @endforelse
    </div>
</div>

<script>
    $(document).ready(function() {
        if ($('#datatable tbody tr').length > 1) {
            $('#datatable').DataTable({
                pageLength: 10,
                order: [[0, 'asc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search notifications..."
                }
            });
        }
    });
</script>
@endsection
