{{-- Learner menu (sidebar + phone sheet): Dashboard link, then dropdown groups.
     $groups: [label, icon, items, tone] from layouts/learner. One group open at a time (data-lr-accordion). --}}
<nav class="lr-nav lr-nav-top">
    <a href="{{ route('learner.home') }}" class="{{ $isOn('learner.home') ? 'is-on' : '' }}" @if ($isOn('learner.home')) aria-current="page" @endif>
        <span class="lr-nav-ic"><x-lb-icon name="layout-dashboard" /></span>
        <span class="lr-nav-t">Dashboard</span>
    </a>
</nav>

<div class="lr-dd-list" data-lr-accordion>
    @foreach ($groups as [$gLabel, $gIcon, $gItems, $tone])
        @php $open = collect($gItems)->contains(fn ($i) => $isOn($i[0])); @endphp
        <details class="lr-dd {{ $open ? 'has-on' : '' }}" data-tone="{{ $tone }}" @if ($open) open @endif>
            <summary>
                <span class="lr-dd-ic"><x-lb-icon :name="$gIcon" /></span>
                <span class="lr-dd-t">{{ $gLabel }}</span>
                @if (collect($gItems)->contains(fn ($i) => $i[0] === 'learner.chat'))
                    <em data-lc-badge @if (!$chatUnread) hidden @endif>{{ $chatUnread > 9 ? '9+' : $chatUnread }}</em>
                @endif
                <x-lb-icon name="chevron-down" class="lr-dd-chev" />
            </summary>
            <div class="lr-dd-body">
                <nav class="lr-dd-nav">
                    @foreach ($gItems as [$name, $icon, $label])
                        <a href="{{ route($name) }}" class="{{ $isOn($name) ? 'is-on' : '' }}" @if ($isOn($name)) aria-current="page" @endif>
                            <x-lb-icon :name="$icon" />
                            <span>{{ $label }}</span>
                            @if ($name === 'learner.chat')<em data-lc-badge @if (!$chatUnread) hidden @endif>{{ $chatUnread > 9 ? '9+' : $chatUnread }}</em>@endif
                        </a>
                    @endforeach
                </nav>
            </div>
        </details>
    @endforeach
</div>
