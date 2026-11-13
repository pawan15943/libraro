{{-- <x-ui.textarea name="notes" label="Notes" rows="3" :value="$lead->notes" placeholder="…" />
  fieldClass → class on the wrapping .ui-field; hint with markup: <x-slot:hint>…</x-slot:hint> --}}
@props(['name', 'label' => null, 'value' => null, 'hint' => null, 'id' => null, 'fieldClass' => null])
@php
    $id = $id ?? 'f_'.str_replace(['[', ']', '.'], '_', $name);
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $error = $errors->first($key);
@endphp
<div class="ui-field{{ $fieldClass ? ' '.$fieldClass : '' }}">
    @if($label)<label class="ui-label" for="{{ $id }}">{{ $label }}@if($attributes->has('required'))<span class="req">*</span>@endif</label>@endif
    <textarea id="{{ $id }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'ui-textarea'.($error ? ' is-invalid' : ''), 'rows' => 3]) }}>{{ old($key, $value) }}</textarea>
    @if($error)<div class="ui-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $error }}</div>@elseif($hint)<div class="ui-hint">{{ $hint }}</div>@endif
</div>
