{{-- Shift <option>s for the plan pages (upgrade, renew, change plan, reactivate): every shift of the
     branch with its timing, so the owner sees them all, plus the advanced shift templates
     (docs/ADVANCED_SHIFTS.md): duration/free on a numbered seat, flexible on a general seat.
     Fixed shifts this seat cannot take are listed disabled as "Not free".
     Params: $available (shifts free for this learner: filterPlantypeFromseat / generalSeatModes),
             $selected (current shift id), $seatNo (numbered seat or null). --}}
@php
    $psFmt = function ($t) { if (!$t) return ''; try { $c = \Carbon\Carbon::parse($t); return $c->format('i') === '00' ? $c->format('g A') : $c->format('g:i A'); } catch (\Throwable $e) { return (string) $t; } };
    $psFree = collect($available)->map(fn ($p) => (int) (is_array($p) ? $p['id'] : $p->id))->all();
    $psTemplateModes = $seatNo ? \App\Models\PlanType::TIMED_TEMPLATE_MODES : [\App\Models\PlanType::MODE_FLEXIBLE];
    // a numbered seat lists the other fixed shifts too (disabled); a general seat only what it can take
    $psShifts = \App\Models\PlanType::where('branch_id', getCurrentBranch())
        ->where(function ($q) use ($psFree, $seatNo, $psTemplateModes) {
            $q->whereIn('id', $psFree)
              ->orWhere(fn ($t) => $t->listable()->whereIn('booking_mode', $psTemplateModes));
            if ($seatNo) $q->orWhere(fn ($f) => $f->fixedMode());
        })
        ->orderBy('start_time')->orderBy('id')
        ->get(['id', 'name', 'branch_id', 'start_time', 'end_time', 'slot_hours', 'booking_mode', 'parent_id'])
        // fixed shifts that are free first, by start time; templates next; the ones this seat can't take last
        ->sortBy(fn ($p) => $p->isTimedTemplate() || $p->isFlexible() ? 1 : (in_array((int) $p->id, $psFree, true) ? 0 : 2), SORT_REGULAR)->values();
    $psSelected = (string) old('plan_type_id', $selected);
@endphp
@foreach($psShifts as $psShift)
@php
    $psTemplate = $psShift->isTimedTemplate() || $psShift->isFlexible();
    // a timing row the learner already holds behaves as a fixed shift
    $psMode = $psTemplate ? $psShift->booking_mode : 'fixed';
    $psOn = $psTemplate || in_array((int) $psShift->id, $psFree, true);
    $psHrs = rtrim(rtrim(number_format((float) $psShift->slot_hours, 1, '.', ''), '0'), '.');
    // an advanced shift without its own time is booked inside the library hours
    [$psS, $psE] = $psShift->bookingTimes();
    $psTime = $psShift->isAnyTime() ? $psShift->timeLabel() : (($psShift->start_time && $psShift->end_time) ? $psFmt($psShift->start_time) . ' – ' . $psFmt($psShift->end_time) : '');
    $psSub = match ($psMode) {
        'duration' => $psHrs . ' hr · pick time',
        'free' => 'Your time & price',
        'flexible' => $psHrs . ' hr · any time',
        default => $psOn ? $psTime : 'Not free',
    };
@endphp
<option value="{{ $psShift->id }}" data-mode="{{ $psMode }}" data-hours="{{ $psHrs }}" data-s="{{ $psS }}" data-e="{{ $psE }}" data-time="{{ $psTime }}" data-sub="{{ $psSub }}" @disabled(!$psOn) @selected($psOn && $psSelected === (string) $psShift->id)>{{ $psShift->name }}</option>
@endforeach
