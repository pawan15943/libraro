{{-- Custom plan (docs/CUSTOM_PLAN.md), driven by public/js/lb-custom-plan.js.
     Params:
       $select       selector of the plan <select> (or the edit form's hidden plan input)
       $start        selector of the form's start date input, or $startDate: a fixed start date (Y-m-d)
       $end          selector of the form's own end date input; without it an end date input is shown here
       $endDisplay   selector of the read-only "valid till" text that $end stands in for (booking popup)
       $price        selector of the plan price input made editable for a custom plan (optional)
       $priceInputId id of a price input shown here (booking popup, whose price input is hidden)
       $endValue     end date to show for a booking already on a custom plan (optional)
       $startValue   start date shown read-only next to the end date, for forms whose start date is fixed and hidden
       $locked       true when the plan can't change here (renew)
       $currentDays  days of the booking's current custom plan (edit form) --}}
@php
    $cpEndId = 'lcpEnd' . substr(md5($select), 0, 6);
    $cpOwnEnd = empty($end);
    $cpAlwaysShown = !empty($currentDays) || (!empty($end) && empty($endDisplay));
@endphp
<div class="lcp-box" data-custom-plan-box
     data-select="{{ $select }}"
     data-url="{{ route('customPlan.resolve') }}"
     @isset($start) data-start="{{ $start }}" @endisset
     @isset($startDate) data-start-date="{{ $startDate }}" @endisset
     @isset($price) data-price="{{ $price }}" @endisset
     @isset($end) data-end="{{ $end }}" @endisset
     @isset($endDisplay) data-end-display="{{ $endDisplay }}" @endisset
     @if(!empty($currentDays)) data-current-days="{{ $currentDays }}" @endif
     @if(!empty($locked)) data-locked @endif
     @if(!$cpAlwaysShown) hidden @endif>
    @if($cpOwnEnd || !empty($priceInputId))
        <div class="lcp-row">
            @if($cpOwnEnd && !empty($startValue))
            <div>
                <label class="form-label" for="{{ $cpEndId }}s">Plan Starts On</label>
                <input type="date" class="form-control" id="{{ $cpEndId }}s" value="{{ $startValue }}" readonly tabindex="-1">
            </div>
            @endif
            @if($cpOwnEnd)
            <div>
                <label class="form-label" for="{{ $cpEndId }}">Plan Ends On <span class="required-star">*</span></label>
                <input type="date" class="form-control" id="{{ $cpEndId }}" value="{{ $endValue ?? '' }}" data-custom-end @readonly(!empty($locked))>
            </div>
            @endif
            @if(!empty($priceInputId))
            <div>
                <label class="form-label" for="{{ $priceInputId }}">Total price (₹) <span class="required-star">*</span></label>
                <input type="text" inputmode="decimal" class="form-control" id="{{ $priceInputId }}" maxlength="10" placeholder="All shifts, from 1 Month price">
            </div>
            @endif
        </div>
    @endif
    <small class="lcp-note" data-custom-note></small>
</div>
@once
<link rel="stylesheet" href="{{ asset('public/css/custom-plan.css') }}?v={{ @filemtime(public_path('css/custom-plan.css')) }}">
<script src="{{ asset('public/js/lb-custom-plan.js') }}?v={{ @filemtime(public_path('js/lb-custom-plan.js')) }}" defer></script>
@endonce
