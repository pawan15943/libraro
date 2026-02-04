@extends('layouts.library')

@section('title', 'Admin Dashboard')

@section('content')

<!-- FLATPICKR CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<div class="add-shift-page">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card d-flex">
                <div class="heading">
                    <h4>Operating Hour: {{$operatingHour->hour}}</h4>
                    <p class="m-0 text-danger pt-1">Note: Shift hours can't be outside the library's opening hours. Make sure your times fit within them.</p>
                </div>
            </div>

        </div>
    </div>


    <form method="POST" action="{{ route('master.configuration.store') }}" id="configure" class="mb-4" autocomplete="off">
        @csrf
        <div id="planTypeWrapper">
            <div class="row g-4" id="planRowContainer">
               @php
               
                    $rows = $planTypes->isNotEmpty()
                        ? $planTypes
                        : collect([ (object)[
                            'id' => null,
                            'day_type_id' => null,
                            'name' => null,
                            'start_time' => null,
                            'end_time' => null,
                            'slot_hours' => null,
                            'price' => null,
                        ] ]);
                       
                @endphp

                @foreach($rows as $index => $row)
               
                   
                <!-- SHIFT 1 -->
                <div class="col-lg-6 plan-row-wrapper">
                    <div class="plan-row">
                        <h4 class="shift-title">Shift {{$index+1}}</h4>

                        @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                        @endif
                          {{-- hidden ids for edit --}}
                        <input type="hidden" name="plan_types[{{ $index }}][plan_type_id]" value="{{ $row->id ?? '' }}">
                        <input type="hidden" name="plan_types[{{ $index }}][plan_price_id]" value="{{ $row->price->id ?? '' }}">
                        <div class="row g-3 align-items-end mt-2">
                            
                            <div class="col-lg-6">
                                <label>Plan Type Name *</label>
                                <select class="form-select plan-type @error('plan_types.0.day_type_id') is-invalid @enderror" name="plan_types[{{$index}}][day_type_id]">
                                    <option value="">Select</option>
                                    
                                    @can('has-permission', 'Full Day')
                                    <option value="1"  @selected($row?->day_type_id==1)>Full Day</option>
                                    @endcan
                                    @can('has-permission', 'First Half')
                                    <option value="2"  @selected($row?->day_type_id==2)>First Half</option>
                                    @endcan
                                    @can('has-permission', 'Second Half')
                                    <option value="3"  @selected($row?->day_type_id==3)>Second Half</option>
                                    @endcan
                                    @if($total_hour =='24' || $total_hour ==24)
                                    @can('has-permission', 'All Day')
                                    <option value="8"  @selected($row?->day_type_id==8)>All Day</option>
                                    @endcan
                                    @can('has-permission', 'Full Night')
                                    <option value="9"  @selected($row?->day_type_id==9)>Full Night</option>
                                    @endcan
                                    @endif
                                    @can('has-permission', 'Custom Plan')
                                    <option value="0"  @selected((string)$row?->day_type_id == '0')>Custom</option>
                                    @endcan
                                </select>
                                    
                                @error('plan_types.0.day_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>Custom Plan Type Name *</label>
                                <input type="text" name="plan_types[{{$index}}][custom_plan_type]" value="{{ $row?->day_type_id==0 ? $row->name : '' }}" class="form-control custom-plan @error('plan_types.0.custom_plan_type') is-invalid @enderror" autocomplete="off">
                                @error('plan_types.0.custom_plan_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>Start Time *</label>
                                <input type="text" name="plan_types[{{$index}}][start_time]" value="{{ $row->start_time ?? '' }}" class="form-control start_time @error('plan_types.0.start_time') is-invalid @enderror" maxlength="4" autocomplete="off">
                                @error('plan_types.0.start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>End Time *</label>
                                <input type="text" name="plan_types[{{$index}}][end_time]" value="{{ $row->end_time ?? '' }}" class="form-control end_time @error('plan_types.0.end_time') is-invalid @enderror" maxlength="4" autocomplete="off">
                                @error('plan_types.0.end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>Slot Duration *</label>
                                <input type="text" name="plan_types[{{$index}}][slot_hours]" value="{{ $row->slot_hours ?? '' }}" class="form-control slot_hours @error('plan_types.0.slot_hours') is-invalid @enderror" readonly>
                                @error('plan_types.0.slot_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>Price *</label>
                                <input type="text" inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="5" name="plan_types[{{$index}}][price]" value="{{ $row->price->price ?? '' }}" class="form-control @error('plan_types.0.price') is-invalid @enderror" max="4" min="1">
                                @error('plan_types.0.price')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-12 d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm add-plan-row add-delete"><i class="fa fa-plus"></i></button>
                                {{-- <button type="button" class="btn btn-danger btn-sm remove-plan-row d-none add-delete {{ $index === 0 ? 'd-none' : '' }}">
                                    <i class="fa fa-minus"></i>
                                </button> --}}
                                <button type="button"
                                        class="btn btn-danger btn-sm remove-plan-row add-delete
                                        {{ $index > 0 ? '' : 'd-none' }}"
                                        data-id="{{ $row->id ?? '' }}">
                                    <i class="fa fa-minus"></i>
                                </button>

                            </div>

                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-lg-2">
                <button class="btn btn-primary button mt-3" type="submit">Submit</button>
            </div>
        </div>
    </form>
</div>
<!-- jQuery (ONLY ONCE) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- FLATPICKR JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    $(document).ready(function() {

        /* =======================
           FLATPICKR INIT
        ======================= */
        function initFlatpickr(wrapper) {

            wrapper.find('.start_time, .end_time').each(function() {
                if (this._flatpickr) {
                    this._flatpickr.destroy();
                }
            });

            wrapper.find('.start_time').flatpickr({
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                allowInput: true,
                onChange: function() {
                    calculateSlot(wrapper);
                }
            });

            wrapper.find('.end_time').flatpickr({
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                allowInput: true,
                onChange: function() {
                    calculateSlot(wrapper);
                }
            });
        }

        /* =======================
           SLOT CALCULATION
        ======================= */
        function calculateSlot(wrapper) {
            let start = wrapper.find('.start_time').val();
            let end = wrapper.find('.end_time').val();

            if (!start || !end) return;

            let s = new Date("1970-01-01T" + start + ":00");
            let e = new Date("1970-01-01T" + end + ":00");

            let diff = (e - s) / 60000;
            if (diff <= 0) diff += 1440;

            wrapper.find('.slot_hours').val(Math.floor(diff / 60));
        }

        /* =======================
           CUSTOM PLAN TOGGLE
        ======================= */
    

        function initCustomPlan(wrapper) {

            let select = wrapper.find('.plan-type');
            let input  = wrapper.find('.custom-plan');

            // Disable by default
            input.prop('disabled', true);

            // Enable only when Custom selected
            select.off('change').on('change', function () {
                if ($(this).val() === '0') {
                    input.prop('disabled', false).focus();
                } else {
                    input.prop('disabled', true).val('');
                }
            });

            // EDIT MODE (if already Custom)
            if (select.val() === '0') {
                input.prop('disabled', false);
            }
        }

        /* =======================
           INIT FIRST ROW
        ======================= */
        initFlatpickr($('.plan-row-wrapper').first());
        initCustomPlan($('.plan-row-wrapper').first());

        /* =======================
           ADD ROW
        ======================= */


        

        function reindexPlanRows() {
            $('.plan-row-wrapper').each(function (index) {

                $(this).find('.shift-title')
                    .text('Shift ' + (index + 1));

                $(this).find('input, select').each(function () {
                    let name = $(this).attr('name');
                    if (!name) return;

                    $(this).attr(
                        'name',
                        name.replace(/plan_types\[\d+]/, 'plan_types[' + index + ']')
                    );
                });
            });
        }

        $(document).on('click', '.add-plan-row', function() {

            let clone = $('.plan-row-wrapper:first').clone(false);

            // Reset values
            clone.find('input').val('');
            clone.find('select.plan-type').val('');


            // Remove validation UI
            clone.find('.is-invalid').removeClass('is-invalid');
            clone.find('.invalid-feedback').remove();

            clone.find('.custom-plan').prop('disabled', true);
            clone.find('.remove-plan-row').removeClass('d-none');

            $('#planRowContainer').append(clone);

            reindexPlanRows();
            initFlatpickr(clone);
            initCustomPlan(clone);
        });

        $('.plan-row-wrapper').each(function () {
            initCustomPlan($(this));
            initFlatpickr($(this));
        });


      $(document).on('click', '.remove-plan-row', function () {

            let rowWrapper = $(this).closest('.plan-row-wrapper');
            let planTypeId = $(this).data('id'); // DB id if exists

            Swal.fire({
                title: 'Are you sure?',
                text: "This shift will be removed permanently.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then((result) => {

                if (!result.isConfirmed) {
                    return;
                }

                // EXISTING SHIFT → DELETE FROM DB
                if (planTypeId) {

                    $.ajax({
                        url: "{{ route('plan-type.delete') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: planTypeId,
                        },
                        success: function (res) {

                            if (res.status) {

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'Shift has been deleted successfully.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });

                                rowWrapper.remove();
                                reindexPlanRows();
                                hideFirstDeleteButton();

                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Failed',
                                    text: res.message || 'Unable to delete shift'
                                });
                            }
                        },
                        error: function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'Server Error',
                                text: 'Something went wrong while deleting the shift.'
                            });
                        }
                    });

                } 
                // NEWLY ADDED (NOT SAVED YET)
                else {

                    rowWrapper.remove();
                    reindexPlanRows();

                    $('.plan-row-wrapper').first()
                        .find('.remove-plan-row')
                        .addClass('d-none');

                    Swal.fire({
                        icon: 'success',
                        title: 'Removed',
                        text: 'Shift removed successfully.',
                        timer: 1200,
                        showConfirmButton: false
                    });
                }
            });
        });

        function updateShiftTitles() {
            $('.shift-title').each(function(i) {
                $(this).text('Shift ' + (i + 1));
            });
        }

    });
</script>


<script>
    $(document).ready(function() {

        function handleCustomPlanToggle(row) {
            const select = row.find('select[name="day_type_id"]');
            const customInput = row.find('input[name="custom_plan_type"]');

            // Default state
            customInput.prop('disabled', true).val('');

            // On change
            select.on('change', function() {
                if ($(this).val() === '0') {
                    customInput.prop('disabled', false).focus();
                } else {
                    customInput.prop('disabled', true).val('');
                }
            });

            // For edit page (preselected Custom)
            if (select.val() === '0') {
                customInput.prop('disabled', false);
            }
        }

        // Init for existing rows
        $('.plan-row').each(function() {
            handleCustomPlanToggle($(this));
        });

        // Init for dynamically added rows
        $(document).on('click', '.add-plan-row', function() {
            setTimeout(function() {
                handleCustomPlanToggle($('.plan-row').last());
            }, 0);
        });

    });
</script>

{{-- <script>
    $('#configure').on('submit', function(e) {
        console.log($(this).serializeArray());
        e.preventDefault();

        // Clear old errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('.form-error').remove();

        let form = $(this);
        let submitBtn = form.find('button[type="submit"]');
        if (submitBtn.prop('disabled')) {
            return; // already submitting
        }
        submitBtn.prop('disabled', true);
        submitBtn.text('Saving...');

        // Clear old errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('.form-error').remove();

        const fieldLabels = {
            day_type_id: 'Plan Type',
            custom_plan_type: 'Custom Plan Type',
            start_time: 'Start Time',
            end_time: 'End Time',
            slot_hours: 'Slot Duration',
            price: 'Price'
        };

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),

            success: function(res) {
                console.log(res);
                if (res.status === true && res.redirect) {
                    window.location.href = res.redirect;
                }
            },

            error: function(xhr) {

                /* =========================
                   VALIDATION ERRORS (422)
                ========================= */
                if (xhr.status === 422) {

                    let errors = xhr.responseJSON.errors;

                    $.each(errors, function(key, messages) {

                        // key: plan_types.2.start_time
                        let match = key.match(/plan_types\.(\d+)\.(.+)/);
                        if (!match) return;

                        let index = parseInt(match[1]); // 0,1,2
                        let field = match[2]; // start_time

                        let shiftNo = index + 1;
                        let label = fieldLabels[field] ?? field;

                        let inputName = `plan_types[${index}][${field}]`;
                        let input = $(`[name="${inputName}"]`);

                        if (input.length) {
                            input.addClass('is-invalid');

                            input.after(
                                `<div class="invalid-feedback d-block">
                                Shift ${shiftNo} – ${label} is required
                             </div>`
                            );
                        }
                    });
                }

                /* =========================
                   BUSINESS ERRORS (400)
                ========================= */
                if (xhr.status === 400 || xhr.status === 409) {
                    form.prepend(
                        `<div class="alert alert-danger form-error">
                        ${xhr.responseJSON.message}
                     </div>`
                    );
                }
            }
        });
    });
</script> --}}

<script>
$('#configure').on('submit', function (e) {
    e.preventDefault();

    let form = $(this);
    let submitBtn = form.find('button[type="submit"], .button').first();

    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),

        success: function (res) {
            if (res.status === true) {

                toastr.success(res.message);

                if (res.redirect) {
                    // keep button disabled
                    window.location.href = res.redirect;
                }
            }
        },

        error: function (xhr) {

            // 🔓 Re-enable ONLY on error
            if (submitBtn.length && submitBtn.data('original-text')) {
                submitBtn.prop('disabled', false);
                submitBtn.html(submitBtn.data('original-text'));
            }

            // Clear old errors
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $('.form-error').remove();

            /* ========= Validation errors (422) ========= */
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;

                $.each(errors, function (key) {
                    let match = key.match(/plan_types\.(\d+)\.(.+)/);
                    if (!match) return;

                    let index = match[1];
                    let field = match[2];

                    let input = $(`[name="plan_types[${index}][${field}]"]`);

                    if (input.length) {
                        input.addClass('is-invalid');
                        input.after(
                            `<div class="invalid-feedback d-block">
                                Shift ${parseInt(index) + 1} is invalid
                             </div>`
                        );
                    }
                });
            }

            /* ========= Business errors ========= */
            if (xhr.status === 400 || xhr.status === 409) {
                form.prepend(
                    `<div class="alert alert-danger form-error">
                        ${xhr.responseJSON.message}
                     </div>`
                );
            }
        }
    });
});

$(window).on('pageshow', function () {
    $('.loader').remove();
});
</script>

@endsection