{{--
  Label + any control (editor, tag input, file picker, custom markup) with the same spacing and error handling as <x-ui.input>:
  <x-ui.field label="Tags" for="tags" name="tags" hint="Press Enter after each tag" required>
      <input id="tags" name="tags" class="ui-input">
  </x-ui.field>
  name → reads the validation error for that field.
--}}
@props(['label' => null, 'for' => null, 'name' => null, 'hint' => null, 'required' => false])
@php $error = $name ? $errors->first(str_replace(['[', ']'], ['.', ''], $name)) : null; @endphp
<div {{ $attributes->merge(['class' => 'ui-field']) }}>
    @if($label)<label class="ui-label" @if($for) for="{{ $for }}" @endif>{{ $label }}@if($required)<span class="req">*</span>@endif</label>@endif
    {{ $slot }}
    @if($error)<div class="ui-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $error }}</div>@elseif($hint)<div class="ui-hint">{{ $hint }}</div>@endif
</div>
