@extends('layouts.admin')

@section('content')

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif
<div>
    @foreach($subscriptions as $subscription)
    <h4 class="py-4">Permissions for {{ $subscription->name }}</h4>
    @php
    // Group permissions by category
    $groupedPermissions = $subscription->permissions->groupBy('permission_category_id');
    @endphp

    @if($groupedPermissions->isEmpty())
    <p>No permissions available.</p>
    @else
    <div class="card">

        @foreach($groupedPermissions as $categoryId => $permissions)
        <div class="row">
            <div class="col-lg-12">
                <h4 class="permission-heading">
                    {{ $categoryId ? \App\Models\PermissionCategory::find($categoryId)->name : 'No Category' }}
                </h4>
            </div>
        </div>

        <div class="row">
            @foreach($permissions as $permission)
            <div class="col-lg-3">
                <p><i class="fa-solid fa-check text-success me-2"></i>{{ $permission->name }}</p>
            </div>
            @endforeach
        </div>
        <div class="py-1"></div>
        @endforeach

    </div>
    @endif
    @endforeach

</div>

<script>
    $(document).ready(function() {
        // Select or Deselect All Checkboxes
        $('#select-all').on('click', function() {
            // Check if #select-all is checked or not
            var isChecked = $(this).prop('checked');

            // Set all checkboxes to match the state of #select-all
            $('input[type="checkbox"]').prop('checked', isChecked);
        });

        // Fetch Permissions for Selected Subscription
        $('#subscription-select').on('change', function() {
            let subscriptionId = $(this).val();
            console.log(subscriptionId);

            if (subscriptionId) {

                let url = '{{ route("subscriptions.getPermissions", ":id") }}';
                url = url.replace(':id', subscriptionId);

                $.ajax({
                    url: url, // Use the dynamically generated URL
                    type: 'GET',
                    success: function(data) {
                        console.log(data);

                        // Uncheck all checkboxes first
                        $('input[type="checkbox"]').prop('checked', false);

                        // Loop through the permissions and check the ones that are assigned
                        $('input[type="checkbox"]').each(function() {
                            // Convert checkbox value to an integer for comparison
                            var checkboxValue = parseInt($(this).val(), 10);

                            // Compare the checkbox value with the permissions array
                            if (data.permissions.includes(checkboxValue)) {
                                $(this).prop('checked', true);
                            }
                        });
                    },
                    error: function() {
                        console.error('Failed to fetch permissions');
                    }
                });
            } else {
                // If no subscription is selected, uncheck all checkboxes
                $('input[type="checkbox"]').prop('checked', false);
            }
        });
    });
</script>

@endsection