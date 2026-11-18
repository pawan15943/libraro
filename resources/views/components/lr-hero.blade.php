{{-- Navy page hero for the learner portal (same look as the dashboard goal card).
     <x-lr-hero icon="timer" kicker="Study" title="Stopwatch" sub="…" :chips="[['flame', '3 day streak']]" :art="true">right side</x-lr-hero> --}}
@props(['icon' => 'sparkles', 'kicker' => null, 'title', 'sub' => null, 'chips' => [], 'art' => true])
<section {{ $attributes->merge(['class' => 'lh-goal lx-hero']) }}>
    <div class="lh-goal-stars" aria-hidden="true"></div>
    <div class="lh-goal-main">
        @if ($kicker)<span class="lh-k"><x-lb-icon :name="$icon" />{{ $kicker }}</span>@endif
        <h1>{{ $title }}</h1>
        @if ($sub)<p>{!! $sub !!}</p>@endif
        @if (count($chips))
            <div class="lh-goal-meta">
                @foreach ($chips as [$cIcon, $cText])
                    <span><x-lb-icon :name="$cIcon" />{{ $cText }}</span>
                @endforeach
            </div>
        @endif
    </div>
    @if ($art)
        @include('learner.partials.portal-study-art', ['class' => 'is-hero'])
    @endif
    @if (trim($slot) !== '')
        <div class="lx-hero-side">{{ $slot }}</div>
    @endif
</section>
