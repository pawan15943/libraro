{{-- <x-ui.check name="ids[]" value="5" :checked="..." type="checkbox|radio">Label <small>help</small></x-ui.check>
  class → on the label; inputClass → on the <input> (for script hooks); every other attribute goes on the <input>. --}}
@props(['name', 'value' => 1, 'checked' => false, 'type' => 'checkbox', 'inputClass' => null])
<label {{ $attributes->only('class')->merge(['class' => 'ui-check']) }}>
    <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" @checked($checked) @if($inputClass) class="{{ $inputClass }}" @endif {{ $attributes->except('class') }}>
    @if($slot->isNotEmpty())<span>{{ $slot }}</span>@endif
</label>
