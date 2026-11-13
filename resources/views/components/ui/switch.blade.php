{{-- <x-ui.switch name="is_active" :checked="$row->is_active">Active</x-ui.switch>  (posts value 1 when on) --}}
@props(['name', 'checked' => false, 'value' => 1])
<label {{ $attributes->only('class')->merge(['class' => 'ui-switch']) }}>
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked(old($name, $checked)) {{ $attributes->except('class') }}>
    <span class="ui-switch__track" aria-hidden="true"></span>
    @if($slot->isNotEmpty())<span>{{ $slot }}</span>@endif
</label>
