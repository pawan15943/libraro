@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/library-style.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="actions mb-4">
    <div class="upper-box">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-3">{{ $branch->display_name ?? $branch->name }} — Plan Types / Shifts</h4>
            <a href="javascript:void(0);" class="go-back" onclick="window.history.back();">Go Back <i class="fa-solid fa-backward pl-2"></i></a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if(!$monthlyPlan)
<div class="alert alert-warning">
    This library has no "1 Month" plan yet. <a href="{{ route('library.branch.plans', $branch->id) }}">Add one on the Plans page</a> before setting shift prices.
</div>
@else

<div class="add-shift-page">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card d-flex">
                <div class="heading">
                    <h4>Operating Hour: {{ $operatingHour->hour ?? 'Not set' }}</h4>
                    <p class="m-0 text-danger pt-1">Note: Shift hours can't be outside the branch's opening hours. Make sure your times fit within them.</p>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('library.branch.plantypes.save', $branch->id) }}" id="configure" class="mb-4" autocomplete="off">
        @csrf
        <div id="planTypeWrapper">
            <div class="row g-4" id="planRowContainer">
                @php
                    $rows = $branchPlanTypes->isNotEmpty()
                        ? $branchPlanTypes
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
                <div class="col-lg-6 plan-row-wrapper">
                    <div class="plan-row card p-3">
                        <h4 class="shift-title">Shift {{ $index + 1 }}</h4>

                        @if(($row->active_learners_count ?? 0) > 0)
                        <p class="text-danger mb-2">Active learners assigned — start/end time and price locked for this shift.</p>
                        @endif

                        <input type="hidden" name="plan_types[{{ $index }}][plan_type_id]" value="{{ $row->id ?? '' }}">
                        @if(($row->active_learners_count ?? 0) > 0)
                        {{-- disabled selects/inputs below are excluded by jQuery's serialize(), so mirror
                             their values in hidden fields to make sure locked rows still submit them. --}}
                        <input type="hidden" name="plan_types[{{ $index }}][day_type_id]" value="{{ $row->day_type_id }}">
                        <input type="hidden" name="plan_types[{{ $index }}][custom_plan_type]" value="{{ (string) $row->day_type_id === '0' ? $row->name : '' }}">
                        @endif
                        <div class="row g-3 align-items-end mt-2">
                            <div class="col-lg-6">
                                <label>Plan Type Name *</label>
                                <select class="form-select plan-type" name="plan_types[{{ $index }}][day_type_id]" {{ ($row->active_learners_count ?? 0) > 0 ? 'disabled' : '' }}>
                                    <option value="">Select</option>
                                    <option value="1" @selected($row->day_type_id == 1)>Full Day</option>
                                    <option value="2" @selected($row->day_type_id == 2)>First Half</option>
                                    <option value="3" @selected($row->day_type_id == 3)>Second Half</option>
                                    <option value="8" @selected($row->day_type_id == 8)>All Day</option>
                                    <option value="9" @selected($row->day_type_id == 9)>Full Night</option>
                                    <option value="0" @selected((string) $row->day_type_id === '0')>Custom</option>
                                    <option value="10" @selected((string) $row->day_type_id === '10')>Reserved</option>
                                    <option value="11" @selected((string) $row->day_type_id === '11')>VIP</option>
                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label>Custom Plan Type Name *</label>
                                <input type="text" name="plan_types[{{ $index }}][custom_plan_type]" value="{{ (string) $row->day_type_id === '0' ? $row->name : '' }}" class="form-control custom-plan" autocomplete="off" {{ ($row->active_learners_count ?? 0) > 0 ? 'disabled' : '' }}>
                            </div>

                            <div class="col-lg-6">
                                <label>Start Time *</label>
                                <input type="text" name="plan_types[{{ $index }}][start_time]" value="{{ $row->start_time ?? '' }}" class="form-control start_time" maxlength="5" autocomplete="off" {{ ($row->active_learners_count ?? 0) > 0 ? 'readonly' : '' }}>
                            </div>

                            <div class="col-lg-6">
                                <label>End Time *</label>
                                <input type="text" name="plan_types[{{ $index }}][end_time]" value="{{ $row->end_time ?? '' }}" class="form-control end_time" maxlength="5" autocomplete="off" {{ ($row->active_learners_count ?? 0) > 0 ? 'readonly' : '' }}>
                            </div>

                            <div class="col-lg-6">
                                <label>Slot Duration *</label>
                                <input type="text" name="plan_types[{{ $index }}][slot_hours]" value="{{ $row->slot_hours ?? '' }}" class="form-control slot_hours" readonly>
                            </div>

                            <div class="col-lg-6">
                                <label>Price (1 Month plan) *</label>
                                <input type="number" min="0" step="0.01" name="plan_types[{{ $index }}][price]" value="{{ optional($row->price ?? null)->price }}" class="form-control price-input" {{ ($row->active_learners_count ?? 0) > 0 ? 'readonly' : '' }}>
                            </div>

                            <div class="col-lg-12 d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm add-plan-row"><i class="fa fa-plus"></i></button>
                                <button type="button" class="btn btn-danger btn-sm remove-plan-row {{ $index > 0 ? '' : 'd-none' }}" data-id="{{ $row->id ?? '' }}" {{ ($row->active_learners_count ?? 0) > 0 ? 'disabled title=Cannot remove: active learners assigned' : '' }}>
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
            <div class="col-lg-3">
                <button class="btn btn-primary button mt-3" type="submit">Submit</button>
            </div>
        </div>
    </form>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
$(document).ready(function(){

    function initFlatpickr(wrapper){
        wrapper.find('.start_time, .end_time').each(function(){
            if (this._flatpickr) this._flatpickr.destroy();
        });

        wrapper.find('.start_time').flatpickr({
            enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, allowInput: true,
            onChange: function(){ calculateSlot(wrapper); }
        });

        wrapper.find('.end_time').flatpickr({
            enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, allowInput: true,
            onChange: function(){ calculateSlot(wrapper); }
        });
    }

    function calculateSlot(wrapper){
        let start = wrapper.find('.start_time').val();
        let end = wrapper.find('.end_time').val();
        if(!start || !end) return;

        let s = new Date("1970-01-01T"+start+":00");
        let e = new Date("1970-01-01T"+end+":00");
        let diff = (e - s)/60000;
        if(diff <= 0) diff += 1440;

        wrapper.find('.slot_hours').val(Math.floor(diff/60));
    }

    function initCustomPlan(wrapper){
        let select = wrapper.find('.plan-type');
        let input  = wrapper.find('.custom-plan');

        input.prop('disabled', select.prop('disabled') || select.val() !== '0');

        select.off('change.custom').on('change.custom', function () {
            if ($(this).val() === '0') {
                input.prop('disabled', false).focus();
            } else {
                input.prop('disabled', true).val('');
            }
        });
    }

    function initVipPrice(wrapper){
        let select = wrapper.find('.plan-type');
        let priceInput = wrapper.find('.price-input');

        function applyVipRule(){
            if(select.val() == '11'){
                priceInput.val(0);
                priceInput.prop('readonly', true);
            } else if (!priceInput.data('locked')) {
                priceInput.prop('readonly', false);
            }
        }

        select.off('change.vip').on('change.vip', applyVipRule);
        applyVipRule();
    }

    function reindexPlanRows(){
        $('.plan-row-wrapper').each(function(index){
            $(this).find('.shift-title').text('Shift ' + (index+1));
            $(this).find('input, select').each(function(){
                let name = $(this).attr('name');
                if(!name) return;
                $(this).attr('name', name.replace(/plan_types\[\d+]/, 'plan_types['+index+']'));
            });
        });
    }

    $('.plan-row-wrapper').each(function(){
        initFlatpickr($(this));
        initCustomPlan($(this));
        initVipPrice($(this));
        if ($(this).find('.price-input').prop('readonly')) {
            $(this).find('.price-input').data('locked', true);
        }
    });

    $(document).on('click','.add-plan-row',function(){
        let clone = $('.plan-row-wrapper:first').clone(false);
        clone.find('input').val('').prop('readonly', false).prop('disabled', false).removeData('locked');
        clone.find('select').val('').prop('disabled', false);
        clone.find('.custom-plan').prop('disabled', true);
        clone.find('.remove-plan-row').removeClass('d-none').prop('disabled', false).removeAttr('title');
        clone.find('p.text-danger').remove();

        $('#planRowContainer').append(clone);

        reindexPlanRows();
        initFlatpickr(clone);
        initCustomPlan(clone);
        initVipPrice(clone);
    });

    $(document).on('click','.remove-plan-row',function(){
        if ($(this).prop('disabled')) return;
        $(this).closest('.plan-row-wrapper').remove();
        reindexPlanRows();
        $('.plan-row-wrapper:first').find('.remove-plan-row').addClass('d-none');
    });

});

$(document).on('submit', '#configure', function(e) {
    e.preventDefault();
    e.stopPropagation();

    let form = $(this);
    let submitBtn = form.find('button[type="submit"]');
    if (submitBtn.prop('disabled')) return false;

    let originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('Saving...');

    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    $('.form-error').remove();

    let hasValidRow = false;
    $('.plan-row-wrapper').each(function(){
        let dayType = $(this).find('.plan-type').val();
        let start = $(this).find('.start_time').val();
        let end = $(this).find('.end_time').val();
        if(dayType || start || end) hasValidRow = true;
    });

    if(!hasValidRow){
        toastr.error('Please fill at least one shift');
        submitBtn.prop('disabled', false).html(originalText);
        return false;
    }

    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: form.serialize(),
        success: function (res) {
            if (res.status === true) {
                toastr.success(res.message);
                if (res.redirect) {
                    window.location.href = res.redirect;
                } else {
                    location.reload();
                }
            }
        },
        error: function(xhr){
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function(key, messages){
                    let match = key.match(/plan_types\.(\d+)\.(.+)/);
                    if (!match) return;
                    let inputName = `plan_types[${match[1]}][${match[2]}]`;
                    let input = $(`[name="${inputName}"]`);
                    if (input.length) {
                        input.addClass('is-invalid');
                        input.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
                    }
                });
            }

            let message = xhr.responseJSON?.message || 'Unable to save shifts. Please try again.';
            toastr.error(message);
        },
        complete: function(){
            submitBtn.prop('disabled', false).html(originalText);
        }
    });

    return false;
});
</script>
@endsection
