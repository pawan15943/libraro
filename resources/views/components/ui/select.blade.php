{{--
  <x-ui.select name="stage_id" label="Stage" :options="[1 => 'New', 2 => 'Won']" :value="$x" placeholder="Any stage" />
  or put <option>s in the slot.
  fieldClass="sm:col-span-2" → class on the wrapping .ui-field (grid spans). Hint with markup: <x-slot:hint>…</x-slot:hint>.
--}}
@props(['name', 'label' => null, 'options' => null, 'value' => null, 'placeholder' => null, 'hint' => null, 'size' => null, 'id' => null, 'fieldClass' => null])
@php
    $id = $id ?? 'f_'.str_replace(['[', ']', '.'], '_', $name);
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $error = $errors->first($key);
    $val = old($key, $value);
@endphp
<div class="ui-field{{ $fieldClass ? ' '.$fieldClass : '' }}">
    @if($label)<label class="ui-label" for="{{ $id }}">{{ $label }}@if($attributes->has('required'))<span class="req">*</span>@endif</label>@endif
    <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'ui-select'.($size ? ' ui-select--'.$size : '').($error ? ' is-invalid' : '')]) }}>
        @if(!is_null($placeholder))<option value="">{{ $placeholder }}</option>@endif
        @if(is_iterable($options))
            @foreach($options as $k => $v)<option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $v }}</option>@endforeach
        @endif
        {{ $slot }}
    </select>
    @if($error)<div class="ui-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $error }}</div>@elseif($hint)<div class="ui-hint">{{ $hint }}</div>@endif
</div>
