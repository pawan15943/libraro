{{--
    Member card shown at the top of every operation / transaction page (same look as Swap seat, Upgrade, …):
    photo or initials · name + seat · UID + plan status · plan, shift and end date chips.

    @include('learner.partials.member-card', ['mcLearner' => $learner, 'mcDetail' => $detail])
      mcLearner : Learner model, or a fetchCustomerData() row (has name, learner_no, profile_picture, …)
      mcDetail  : LearnerDetail (or any row with plan_end_date / seat_no); defaults to mcLearner
    Optional: mcStatus + mcTone ('ok' | 'warn' | 'bad') to override the plan status, mcChips (extra [icon, text, link?] chips),
              mcIds (true: keep the ids learnerEdit updates live).
--}}
@php
    $mcL = $mcLearner ?? null;
    $mcD = $mcDetail ?? $mcL;
    // a LearnerDetail was passed: the person is its learner
    if ($mcL instanceof \App\Models\LearnerDetail) { $mcD = $mcDetail ?? $mcL; $mcL = $mcL->learner ?? $mcL; }
    $mcName = trim((string) ($mcL->name ?? 'Learner')) ?: 'Learner';
    $mcInitials = collect(explode(' ', $mcName))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'L';
    $mcPic = $mcL->profile_picture ?? null;
    $mcPhoto = ($mcPic && file_exists(public_path($mcPic))) ? asset($mcPic) : null;
    $mcSeat = $mcD->seat_no ?? ($mcL->seat_no ?? null);
    $mcSeatLabel = (!empty($mcSeat) && $mcSeat != 0) ? (getSeatDisplayShortFloorName($mcSeat) ?: $mcSeat) : null;
    $mcUid = $mcL->learner_no ?? ('#' . ($mcL->learner_id ?? $mcL->id ?? ''));
    $mcEnd = $mcD->plan_end_date ?? null;

    // plan status (same wording as the operation pages)
    if (isset($mcStatus)) {
        $mcText = $mcStatus; $mcT = $mcTone ?? 'ok';
    } elseif ((int) ($mcL->no_expiry ?? 0) === 1) {
        $mcText = 'Non-expiry seat'; $mcT = 'ok';
    } elseif ((int) ($mcL->frozen_status ?? 0) === 1) {
        $mcText = 'Frozen'; $mcT = 'warn';
    } elseif ($mcEnd) {
        $mcPs = getPlanStatusDetails($mcEnd);
        $mcDays = (int) $mcPs['diff_in_days'];
        $mcExt = (int) $mcPs['diff_extend_day'];
        [$mcText, $mcT] = match (true) {
            $mcDays < 0 && $mcExt < 0 => ['Expired ' . abs($mcDays) . ' days ago', 'bad'],
            $mcDays < 0 && $mcExt > 0 => ['Extension: ' . $mcExt . ' days left', 'warn'],
            $mcDays < 0 => ['Extension ends today', 'warn'],
            $mcDays === 0 => ['Expires today', 'warn'],
            $mcDays === 1 => ['Expires in 1 day', 'warn'],
            $mcDays <= 5 => ['Expires in ' . $mcDays . ' days', 'warn'],
            default => ['Active (Expires in ' . $mcDays . ' days)', 'ok'],
        };
    } else {
        $mcText = (int) ($mcL->status ?? 1) === 1 ? 'Active' : 'Inactive';
        $mcT = (int) ($mcL->status ?? 1) === 1 ? 'ok' : 'bad';
    }

    $mcPlan = $mcD->plan_name ?? (optional($mcD->plan ?? null)->name ?? null);
    $mcShift = $mcD->plan_type_name ?? (optional($mcD->planType ?? null)->name ?? (optional($mcD->plantype ?? null)->name ?? null));
    $mcIdsOn = !empty($mcIds);
    // seat status on its own strip above the learner: "Active · Expires in 23 days"
    if (preg_match('/^(.*?)\s*\((.*)\)$/', (string) $mcText, $mcM)) { $mcHead = $mcM[1]; $mcSub = $mcM[2]; }
    else { $mcHead = $mcText; $mcSub = ''; }
@endphp
@once
@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-swap.css') }}?v={{ @filemtime(public_path('css/lb-swap.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-opui.css') }}?v={{ @filemtime(public_path('css/lb-opui.css')) }}">
@endpush
@endonce
<section class="lsw-member lmc">
    <div class="lmc-status-row"><span class="lmc-status is-{{ $mcT }}" role="status">
        <x-lb-icon name="{{ $mcT === 'ok' ? 'circle-check' : ($mcT === 'bad' ? 'circle-x' : 'clock') }}" />
        <b>{{ $mcHead }}</b>@if($mcSub)<span>{{ $mcSub }}</span>@endif
    </span></div>
    <div class="lsw-member-top">
        <span class="lsw-ava" @if($mcIdsOn) id="lswAvatar" @endif>@if($mcPhoto)<img src="{{ $mcPhoto }}" alt="{{ $mcName }}" @if($mcIdsOn) id="topSeatAvatarImg" @endif>@else<span @if($mcIdsOn) id="lswAvatarTxt" @endif>{{ $mcInitials }}</span>@endif</span>
        <div class="lsw-member-id">
            <h3><span class="lsw-nm{{ $mcIdsOn ? ' seat-title' : '' }}" @if($mcIdsOn) id="lswLiveName" @endif>{{ $mcName }}</span> <span class="lsw-name-seat"><x-lb-icon name="armchair" />{{ $mcSeatLabel ? 'Seat ' . $mcSeatLabel : 'General seat' }}</span></h3>
            <div class="lsw-member-tags">
                <span class="lsw-uid">UID: {{ $mcUid }}</span>
            </div>
        </div>
    </div>
    <div class="lsw-chips">
        @if($mcPlan)<span class="lsw-chip"><x-lb-icon name="calendar-days" />{{ $mcPlan }}</span>@endif
        @if($mcShift)<span class="lsw-chip"><x-lb-icon name="clock" />{{ $mcShift }}</span>@endif
        @if($mcEnd && (int) ($mcL->no_expiry ?? 0) !== 1)<span class="lsw-chip"><x-lb-icon name="calendar-check" />Till {{ \Carbon\Carbon::parse($mcEnd)->format('d M Y') }}</span>@endif
        @foreach(($mcChips ?? []) as $mcChip)
            @if(!empty($mcChip[2]))
            <a href="{{ $mcChip[2] }}" class="lsw-chip lmc-link"><x-lb-icon name="{{ $mcChip[0] }}" />{{ $mcChip[1] }}</a>
            @else
            <span class="lsw-chip"><x-lb-icon name="{{ $mcChip[0] }}" />{{ $mcChip[1] }}</span>
            @endif
        @endforeach
    </div>
</section>
