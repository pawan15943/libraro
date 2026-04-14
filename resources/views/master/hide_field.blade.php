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
        <div class="alert alert-info small mb-3" role="alert">
            Use the switches below to show or hide options across the library. Under <strong>Privacy</strong>, you can turn <strong>learner mobile &amp; email masking</strong> on or off for lists, history, search, and operation pages (full contact still appears on learner view/edit).
        </div>
        <div class="table-responsive mt-4">
            @php
                $groupedData = $data->groupBy('category');
            @endphp

            <table class="table text-center border-bottom">
                <thead>
                    <tr>
                        <th>S.N</th>
                        <th>Option Name</th>
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
                                <td style="width: 10%;">{{ $index + 1 }}</td>
                                <td style="text-align: left !important;">{{ $value->name }}<br>
                                    <code>{{ $value->description }}</code>
                                </td>
                                <td style="width: 10%;">
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
                url: "{{ route('branch.update.hidefield') }}",
                type: "POST",
                data: {
                    _token: '{{ csrf_token() }}',
                    hidden_ids: selectedIds
                },
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Changed!',
                        text: response.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Something went wrong. Please try again.'
                    });
                }
            });
        });
    });
</script>




@endsection