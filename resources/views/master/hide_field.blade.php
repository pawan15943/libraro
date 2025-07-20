@extends('layouts.library')
@section('content')

<style>
    div#datatable_wrapper input,
    div#datatable_wrapper select {
        height: auto !important;
        margin: .5rem;
        border-color: #e7e7e7;
    }
</style>


<div class="row mb-4">
    <div class="col-lg-12">
       
        <div class="table-responsive mt-4">
            @php
                $groupedData = $data->groupBy('category');
            @endphp

            <table class="table text-center border-bottom">
                <thead>
                    <tr>
                        <th>S.N</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Hide</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedData as $category => $items)
                        <tr class="table-secondary">
                            <td colspan="5" class="fw-bold">{{ $category }}</td>
                        </tr>
                        @foreach($items as $index => $value)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $value->name }}</td>
                                <td>{{ $value->category }}</td>
                                <td class="w-25">{{ $value->description }}</td>
                                <td>
                                    <div class="form-check form-switch justify-content-center">
                                        <input class="form-check-input toggle_hide" type="checkbox"
                                            id="myToggle{{ $value->id }}"
                                            data-hide_field="{{ $value->id }}"
                                            {{ in_array($value->id, $hiddenFields) ? 'checked' : '' }}>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.toggle_hide').on('change', function () {
            let selectedIds = [];
            $('.toggle_hide:checked').each(function () {
                selectedIds.push($(this).data('hide_field'));
            });

            $.ajax({
                url: "{{ route('branch.update.hidefield') }}", // update this route
                type: "POST",
                data: {
                    _token: '{{ csrf_token() }}',
                    hidden_ids: selectedIds
                },
                success: function (response) {
                    console.log(response.message);
                },
                error: function () {
                    alert("Something went wrong. Please try again.");
                }
            });
        });
    });
</script>




@endsection