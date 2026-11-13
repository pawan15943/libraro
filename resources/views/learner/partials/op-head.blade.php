{{-- Operation sheet header + member card (same look as Swap seat).
     Params: $opIcon, $opKicker, $opTitle, optional $opBack. Uses $customer, $statusText, $statusClass from the page. --}}
@php
    $ohLearner = $customer->learner ?? null;
    $ohName = trim((string) ($customer->name ?? ($ohLearner->name ?? 'Learner')));
    $ohFirst = \Illuminate\Support\Str::of($ohName)->explode(' ')->first() ?: $ohName;
    $ohInitials = collect(explode(' ', $ohName))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'L';
    $ohPic = $customer->profile_picture ?? ($ohLearner->profile_picture ?? null);
    $ohPhoto = ($ohPic && file_exists(public_path($ohPic))) ? asset($ohPic) : null;
    $ohTone = ($statusClass ?? '') === 'status-expired' ? 'bad' : (($statusClass ?? '') === 'status-warning' ? 'warn' : 'ok');
    $ohSeat = $customer->seat_no ?? ($ohLearner->seat_no ?? null);
    $ohSeatLabel = $ohSeat ? (getSeatDisplayShortFloorName($ohSeat) ?? $ohSeat) : null;
    $ohUid = $customer->learner_no ?? ($ohLearner->learner_no ?? ('#' . ($customer->learner_id ?? $customer->id)));
    $ohPlan = $customer->plan_name ?? (optional($customer->plan ?? null)->name ?? 'Plan');
    $ohShift = $customer->plan_type_name ?? (optional($customer->plantype ?? null)->name ?? (optional($customer->planType ?? null)->name ?? 'N/A'));
    $ohBack = $opBack ?? (url()->previous() !== url()->current() ? url()->previous() : route('learners'));
@endphp
<header class="lsw-head">
    <span class="lsw-head-ic"><x-lb-icon name="{{ $opIcon }}" /></span>
    <div class="lsw-head-t">
        <span class="lsw-kicker">{{ $opKicker }}</span>
        <h2>{{ str_replace(':name', $ohFirst, $opTitle) }}</h2>
    </div>
    <a href="{{ $ohBack }}" class="lsw-close" title="Close" aria-label="Close"><x-lb-icon name="x" /></a>
</header>
<div class="lsw-body lop-body-top">
    @if ($errors->any())
    <div class="lef-alert" role="alert"><x-lb-icon name="circle-alert" /><span>Please check the highlighted {{ $errors->count() > 1 ? $errors->count() . ' fields' : 'field' }} below.</span></div>
    @endif
    @include('learner.partials.member-card', ['mcLearner' => $customer] + (isset($statusText) ? ['mcStatus' => $statusText, 'mcTone' => $ohTone] : []))
</div>
