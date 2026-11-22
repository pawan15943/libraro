{{--
    One record, rendered once and laid out as a card (grid) or a row (list) purely by CSS,
    so both views always show the same data.
    Params: icon, tone, heading, subheading, meta [label => value], inactive, locked,
            lockDelete (edit stays, deactivate/delete locked), lockDeleteTitle,
            modal, table, id, record
--}}
<article class="cm-item {{ $inactive ? 'is-inactive' : '' }}" data-search="{{ \Illuminate\Support\Str::lower($heading . ' ' . ($subheading ?? '') . ' ' . implode(' ', $meta ?? [])) }}">
    <div class="cm-item-head">
        <span class="cm-item-icon tone-{{ $tone }}"><i class="fa-solid {{ $icon }}"></i></span>
        <div class="cm-item-text">
            <div class="cm-item-title" role="heading" aria-level="3">{{ $heading }}</div>
            @if(!empty($subheading))
                <p class="cm-item-sub">{{ $subheading }}</p>
            @endif
        </div>
        <span class="cm-status {{ $inactive ? 'is-off' : 'is-on' }}">{{ $inactive ? 'Inactive' : 'Active' }}</span>
    </div>

    {{-- Always rendered (even empty) so list-view columns line up for every master. --}}
    <dl class="cm-item-meta {{ empty($meta) ? 'is-empty' : '' }}">
        @foreach($meta ?? [] as $label => $value)
            <div class="cm-meta">
                <dt>{{ $label }}</dt>
                <dd>{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="cm-item-actions">
        @include('master.custom-management._actions', compact('modal', 'table', 'id', 'record', 'inactive', 'locked') + ['name' => $heading, 'lockDelete' => $lockDelete ?? false, 'lockDeleteTitle' => $lockDeleteTitle ?? null])
    </div>
</article>
