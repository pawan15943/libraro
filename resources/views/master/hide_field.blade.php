@extends('layouts.library')
@section('content')

<style>
    div#datatable_wrapper input,
    div#datatable_wrapper select {
        height: auto !important;
        margin: .5rem;
        border-color: #e7e7e7;
    }

    .toggle-info-card {
        background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 50%, #f0fdfa 100%) !important;
        border: 1px solid #cbd5e1 !important;
        border-left: 5px solid #18225f !important;
        border-radius: 12px !important;
        padding: 1.1rem 1.35rem !important;
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 1rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 3px 12px rgba(24, 34, 95, 0.06) !important;
    }

    .toggle-info-card .info-icon-badge {
        width: 42px !important;
        height: 42px !important;
        min-width: 42px !important;
        border-radius: 10px !important;
        background: linear-gradient(135deg, #18225f 0%, #34939F 100%) !important;
        color: #ffffff !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.15rem !important;
        box-shadow: 0 4px 10px rgba(24, 34, 95, 0.25) !important;
        flex-shrink: 0 !important;
    }

    .toggle-info-card .info-content {
        flex: 1 1 auto !important;
        display: block !important;
        font-family: 'Outfit', sans-serif !important;
    }

    .toggle-info-card .info-title {
        color: #18225f !important;
        font-weight: 700 !important;
        font-size: 0.95rem !important;
        margin-bottom: 0.25rem !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
    }

    .toggle-info-card .info-desc {
        color: #334155 !important;
        font-size: 0.86rem !important;
        line-height: 1.55 !important;
        margin: 0 !important;
    }

    .toggle-info-card .info-badge {
        background-color: #18225f !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        font-size: 0.75rem !important;
        padding: 0.15rem 0.5rem !important;
        border-radius: 4px !important;
        display: inline-block !important;
        vertical-align: middle !important;
    }

    .toggle-info-card .info-highlight {
        color: #18225f !important;
        font-weight: 700 !important;
    }
</style>

<link rel="stylesheet" href="{{ asset('public/css/toggle-feature.css') }}?v={{ time() }}">

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="toggle-info-card">
            <div class="info-icon-badge">
                <i class="fa-solid fa-sliders"></i>
            </div>
            <div class="info-content">
                <div class="info-title">
                    <span>Feature Visibility &amp; Privacy Controls</span>
                </div>
                <p class="info-desc">
                    Use the switches below to show or hide options across the library. Under <span class="info-badge">Privacy</span>, you can turn <span class="info-highlight">learner mobile &amp; email masking</span> on or off for lists, history, search, and operation pages (full contact still appears on learner view/edit).
                </p>
            </div>
        </div>
        <div class="table-responsive mt-3">
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
                                            data-name="{{ $value->name }}"
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
        function showToast(type, msg) {
            if (typeof toastr !== 'undefined') {
                toastr.options = {
                    "closeButton": true,
                    "progressBar": true,
                    "positionClass": "toast-top-right",
                    "timeOut": "2500",
                    "preventDuplicates": false
                };
                if (type === 'success') {
                    toastr.success(msg);
                } else if (type === 'info') {
                    toastr.info(msg);
                } else {
                    toastr.error(msg);
                }
            } else if (typeof Swal !== 'undefined') {
                Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true
                }).fire({
                    icon: type === 'info' ? 'info' : (type === 'error' ? 'error' : 'success'),
                    title: msg
                });
            }
        }

        $('.toggle_hide').on('change', function () {
            const $switch = $(this);
            const isChecked = $switch.prop('checked');
            const featureName = $switch.data('name') || 'Option';

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
                    if (isChecked) {
                        showToast('success', featureName + ' turned ON (Hidden)');
                    } else {
                        showToast('info', featureName + ' turned OFF (Visible)');
                    }
                },
                error: function () {
                    $switch.prop('checked', !isChecked);
                    showToast('error', 'Failed to update ' + featureName + '. Please try again.');
                }
            });
        });
    });
</script>

@endsection