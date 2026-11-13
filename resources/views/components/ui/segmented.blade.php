{{--
  Radio version:  <x-ui.segmented name="priority" :options="['hot' => 'Hot', 'warm' => 'Warm']" :value="$lead->priority" block />
  Link version:   <x-ui.segmented :links="[['label' => 'Open', 'href' => '...', 'active' => true, 'count' => 12], ...]" />
--}}
@props(['name' => null, 'options' => [], 'value' => null, 'links' => null, 'block' => false, 'size' => null])
<div {{ $attributes->merge(['class' => 'ui-seg'.($block ? ' ui-seg--block' : '').($size ? ' ui-seg--'.$size : '')]) }} role="{{ $links ? 'tablist' : 'radiogroup' }}">
    @if($links)
        @foreach($links as $l)
            <a href="{{ $l['href'] }}" class="{{ !empty($l['active']) ? 'is-active' : '' }}" @if(!empty($l['active'])) aria-current="page" @endif>{{ $l['label'] }}@isset($l['count'])<span class="ui-seg__count">{{ $l['count'] }}</span>@endisset</a>
        @endforeach
    @else
        @foreach($options as $k => $v)
            <label><input type="radio" name="{{ $name }}" value="{{ $k }}" @checked((string) old($name, $value) === (string) $k)><span class="ui-seg__item">{!! $v !!}</span></label>
        @endforeach
    @endif
</div>
