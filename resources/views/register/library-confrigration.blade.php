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



    <form method="POST" action="{{ route('master.configuration.store') }}" id="configure" class="mb-4">
        @csrf
        <div id="planTypeWrapper">
            <div class="row g-4" id="planRowContainer">

                <!-- SHIFT 1 -->
                <div class="col-lg-6 plan-row-wrapper">
                    <div class="plan-row">
                        <h4 class="shift-title">Shift 1</h4>

                        @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                        @endif

                        <div class="row g-3 align-items-end mt-2">

                            <div class="col-lg-6">
                                <label>Plan Type Name *</label>
                                <select class="form-select plan-type @error('plan_types.0.day_type_id') is-invalid @enderror" name="plan_types[0][day_type_id]">
                                    <option value="">Select</option>
                                    <option value="1">Full Day</option>
                                    <option value="2">First Half</option>
                                    <option value="3">Second Half</option>
                                    <option value="8">All Day</option>
                                    <option value="9">Full Night</option>
                                    <option value="0">Custom</option>
                                </select>
                                @error('plan_types.0.day_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>Custom Plan Type Name *</label>
                                <input type="text" name="plan_types[0][custom_plan_type]" class="form-control custom-plan @error('plan_types.0.custom_plan_type') is-invalid @enderror">
                                @error('plan_types.0.custom_plan_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>Start Time *</label>
                                <input type="text" name="plan_types[0][start_time]" class="form-control start_time @error('plan_types.0.start_time') is-invalid @enderror">
                                @error('plan_types.0.start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>End Time *</label>
                                <input type="text" name="plan_types[0][end_time]" class="form-control end_time @error('plan_types.0.end_time') is-invalid @enderror">
                                @error('plan_types.0.end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>Slot Duration *</label>
                                <input type="text" name="plan_types[0][slot_hours]" class="form-control slot_hours @error('plan_types.0.slot_hours') is-invalid @enderror" readonly>
                                @error('plan_types.0.slot_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>Price *</label>
                                <input type="number" name="plan_types[0][price]" class="form-control @error('plan_types.0.price') is-invalid @enderror">
                                @error('plan_types.0.price')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-12 d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm add-plan-row add-delete"><i class="fa fa-plus"></i></button>
                                <button type="button" class="btn btn-danger btn-sm remove-plan-row d-none add-delete">
                                    <i class="fa fa-minus"></i>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

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
            let input = wrapper.find('.custom-plan');

            input.prop('disabled', true).val('');

            select.off('change').on('change', function() {
                if ($(this).val() === '0') {
                    input.prop('disabled', false).focus();
                } else {
                    input.prop('disabled', true).val('');
                }
            });
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
            $('.plan-row-wrapper').each(function(index) {

                // Update shift title
                $(this).find('.shift-title').text('Shift ' + (index + 1));

                // Update all name attributes
                $(this).find('input, select').each(function() {
                    let name = $(this).attr('name');
                    if (!name) return;

                    // plan_types[0][start_time] → plan_types[index][start_time]
                    let newName = name.replace(/plan_types\[\d+\]/, 'plan_types[' + index + ']');
                    $(this).attr('name', newName);
                });
            });
        }

        $(document).on('click', '.add-plan-row', function() {

            let clone = $('.plan-row-wrapper:first').clone(false);

            // Reset values
            clone.find('input').val('');
            clone.find('select').val('');

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

        $(document).on('click', '.remove-plan-row', function() {
            $(this).closest('.plan-row-wrapper').remove();
            reindexPlanRows();
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

<script>
    $('#configure').on('submit', function(e) {
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
            },
            complete: function() {
                /* =========================
                   RE-ENABLE BUTTON
                ========================= */
                submitBtn.prop('disabled', false);
                submitBtn.text('Save');
            }
        });
    });
</script>




@endsection