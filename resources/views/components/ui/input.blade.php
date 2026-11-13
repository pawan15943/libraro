{{--
  <x-ui.input name="mobile" label="Mobile" type="tel" required prefix="+91" icon="fa-solid fa-phone" hint="10 digits" :value="$lead->mobile" />
  Errors for "name" are read from the validation bag automatically. old() wins over :value.
  fieldClass="sm:col-span-2" → class on the wrapping .ui-field (grid spans). Hint with markup: <x-slot:hint>…</x-slot:hint>.
--}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'prefix' => null, 'icon' => null, 'size' => null, 'id' => null, 'fieldClass' => null])
@php
    $id = $id ?? 'f_'.str_replace(['[', ']', '.'], '_', $name);
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $error = $errors->first($key);
    $val = $type === 'password' ? null : old($key, $value);
@endphp
<div class="ui-field{{ $fieldClass ? ' '.$fieldClass : '' }}">
    @if($label)<label class="ui-label" for="{{ $id }}">{{ $label }}@if($attributes->has('required'))<span class="req">*</span>@endif</label>@endif
    <div class="ui-input-wrap">
        @if($icon)<i class="ui-input-icon {{ $icon }}" aria-hidden="true"></i>@elseif($prefix)<span class="ui-input-prefix">{{ $prefix }}</span>@endif
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @if(!is_null($val)) value="{{ $val }}" @endif
            {{ $attributes->merge(['class' => 'ui-input'.($size ? ' ui-input--'.$size : '').($error ? ' is-invalid' : '')]) }}
            @if($error) aria-invalid="true" aria-describedby="{{ $id }}_err" @endif>
    </div>
    @if($error)<div class="ui-error" id="{{ $id }}_err"><i class="fa-solid fa-circle-exclamation"></i>{{ $error }}</div>@elseif($hint)<div class="ui-hint">{{ $hint }}</div>@endif
</div>
