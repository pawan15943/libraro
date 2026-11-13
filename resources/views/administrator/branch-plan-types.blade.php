@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('library.branches', $branch->library_id)">Branches</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

@if(!$monthlyPlan)
    <x-ui.banner tone="warning">
        This branch has no "1 Month" plan yet. <a href="{{ route('library.branch.plans', $branch->id) }}" class="font-semibold">Add one on the Plans page</a> before setting shift prices.
    </x-ui.banner>
@else

<x-ui.banner icon="fa-regular fa-clock">
    <b>{{ $branch->display_name ?? $branch->name }}</b> is open <b>{{ $operatingHour->hour ?? 'not set' }}{{ isset($operatingHour->hour) ? ' hours' : '' }}</b> a day. Shift times must fit inside the opening hours.
</x-ui.banner>

<form method="POST" action="{{ route('library.branch.plantypes.save', $branch->id) }}" id="configure" autocomplete="off">
    @csrf
    <div id="planTypeWrapper">
        <div class="grid gap-x-5 lg:grid-cols-2" id="planRowContainer">
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
                $dayTypes = ['1' => 'Full Day', '2' => 'First Half', '3' => 'Second Half', '8' => 'All Day', '9' => 'Full Night', '0' => 'Custom', '10' => 'Reserved', '11' => 'VIP'];
            @endphp

            @foreach($rows as $index => $row)
                @php $locked = ($row->active_learners_count ?? 0) > 0; @endphp
                <div class="plan-row-wrapper">
                    <section class="ui-card">
                        <header class="ui-card__head">
                            <h2 class="ui-card__title"><span class="ui-row__icon"><i class="fa-regular fa-clock"></i></span><span class="shift-title">Shift {{ $index + 1 }}</span></h2>
                            <div class="ui-btn-group">
                                <x-ui.button size="sm" variant="gray" icon="fa-solid fa-plus" class="add-plan-row" label="Add a shift" />
                                <button type="button" class="ui-btn ui-btn--destructive ui-btn--sm ui-btn--icon remove-plan-row {{ $index > 0 ? '' : 'hidden' }}" data-id="{{ $row->id ?? '' }}" aria-label="Remove shift" {!! $locked ? 'disabled title="Cannot remove: active learners assigned"' : 'title="Remove shift"' !!}><i class="fa-solid fa-minus"></i></button>
                            </div>
                        </header>
                        <div class="ui-card__body">
                            @if($locked)
                                <div class="shift-lock ui-banner ui-banner--warning !mb-4"><i class="fa-solid fa-lock"></i><div class="ui-banner__body">Learners are on this shift, so its times and price are locked.</div></div>
                            @endif

                            <input type="hidden" name="plan_types[{{ $index }}][plan_type_id]" value="{{ $row->id ?? '' }}">
                            @if($locked)
                                {{-- disabled selects/inputs below are excluded by jQuery's serialize(), so mirror
                                     their values in hidden fields to make sure locked rows still submit them. --}}
                                <input type="hidden" name="plan_types[{{ $index }}][day_type_id]" value="{{ $row->day_type_id }}">
                                <input type="hidden" name="plan_types[{{ $index }}][custom_plan_type]" value="{{ (string) $row->day_type_id === '0' ? $row->name : '' }}">
                            @endif

                            <div class="ui-fields sm:grid-cols-2">
                                <div class="ui-field">
                                    <label class="ui-label">Shift type<span class="req">*</span></label>
                                    <select class="ui-select plan-type" name="plan_types[{{ $index }}][day_type_id]" {{ $locked ? 'disabled' : '' }}>
                                        <option value="">Select</option>
                                        @foreach($dayTypes as $k => $v)
                                            <option value="{{ $k }}" @selected((string) $row->day_type_id === (string) $k)>{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ui-field">
                                    <label class="ui-label">Custom name</label>
                                    <input type="text" name="plan_types[{{ $index }}][custom_plan_type]" value="{{ (string) $row->day_type_id === '0' ? $row->name : '' }}" class="ui-input custom-plan" placeholder="Only for Custom" autocomplete="off" {{ $locked ? 'disabled' : '' }}>
                                </div>
                                <div class="ui-field">
                                    <label class="ui-label">Starts<span class="req">*</span></label>
                                    <input type="text" name="plan_types[{{ $index }}][start_time]" value="{{ $row->start_time ?? '' }}" class="ui-input start_time" data-lb-time data-lb-time-class="ui-input" maxlength="5" autocomplete="off" {{ $locked ? 'readonly' : '' }}>
                                </div>
                                <div class="ui-field">
                                    <label class="ui-label">Ends<span class="req">*</span></label>
                                    <input type="text" name="plan_types[{{ $index }}][end_time]" value="{{ $row->end_time ?? '' }}" class="ui-input end_time" data-lb-time data-lb-time-class="ui-input" maxlength="5" autocomplete="off" {{ $locked ? 'readonly' : '' }}>
                                </div>
                                <div class="ui-field">
                                    <label class="ui-label">Hours</label>
                                    <input type="text" name="plan_types[{{ $index }}][slot_hours]" value="{{ $row->slot_hours ?? '' }}" class="ui-input slot_hours" readonly placeholder="Worked out from the times">
                                </div>
                                <div class="ui-field">
                                    <label class="ui-label">Price for 1 month<span class="req">*</span></label>
                                    <div class="ui-input-wrap">
                                        <span class="ui-input-prefix">₹</span>
                                        <input type="number" min="0" step="0.01" name="plan_types[{{ $index }}][price]" value="{{ optional($row->price ?? null)->price }}" class="ui-input price-input" {{ $locked ? 'readonly' : '' }}>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end">
        <x-ui.button type="submit" size="lg" icon="fa-solid fa-check" class="w-full sm:w-auto">Save shifts</x-ui.button>
    </div>
</form>
@endif

<script>
$(document).ready(function(){

    // start/end time use the shared picker (data-lb-time); recalculate hours when they change
    function initFlatpickr(wrapper){
        wrapper.find('.start_time, .end_time').off('change.lbt').on('change.lbt', function(){ calculateSlot(wrapper); });
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
        clone.find('.remove-plan-row').removeClass('hidden').prop('disabled', false).removeAttr('title');
        clone.find('.shift-lock').remove();

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
        $('.plan-row-wrapper:first').find('.remove-plan-row').addClass('hidden');
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
    $('.invalid-feedback, .js-field-error').remove();
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
                        let anchor = input.closest('.ui-input-wrap').length ? input.closest('.ui-input-wrap') : (input.next('.lbt-field').length ? input.next('.lbt-field') : input);
                        anchor.after(`<div class="ui-error js-field-error"><i class="fa-solid fa-circle-exclamation"></i>${messages[0]}</div>`);
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
