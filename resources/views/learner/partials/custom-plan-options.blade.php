{{-- Custom plan options for a plan <select> (docs/CUSTOM_PLAN.md): the hidden plan the booking is on
     (or was picked before a failed save), then "Custom dates". Params: $currentPlanId (optional). --}}
@php
    $cpIds = array_values(array_unique(array_filter([old('plan_id'), $currentPlanId ?? null], fn ($v) => is_numeric($v))));
    $cpPlans = $cpIds
        ? \App\Models\Plan::withoutGlobalScopes()->whereIn('id', $cpIds)->where('is_custom', 1)->get()
        : collect();
    $cpSelected = old('plan_id', $currentPlanId ?? null);
@endphp
@foreach($cpPlans as $cpPlan)
<option value="{{ $cpPlan->id }}" data-custom-plan="1" data-days="{{ $cpPlan->plan_id }}" @selected((string) $cpSelected === (string) $cpPlan->id)>{{ $cpPlan->name }}</option>
@endforeach
<option value="" data-custom-trigger>Custom dates</option>
