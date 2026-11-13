{{-- Plan pages (upgrade, change plan, reactivate, edit): timing for a duration/free main shift and
     fixed shifts added to the booking (docs/ADVANCED_SHIFTS.md), driven by public/js/lb-opshift.js.
     The plan price field stays the booking total: the loaded price plus the added shifts.
     Params: $learnerId; $seatNo (booking seat) or $seatInput (selector of the seat select, reactivate);
             $available (fixed shifts free for this learner, optional); $timing (false on the edit form). --}}
@php
    $osFmt = function ($t) { if (!$t) return ''; try { $c = \Carbon\Carbon::parse($t); return $c->format('i') === '00' ? $c->format('g A') : $c->format('g:i A'); } catch (\Throwable $e) { return (string) $t; } };
    $osSeat = $seatNo ?? null;
    $osFree = collect($available ?? ($osSeat ? filterPlantypeFromseat($osSeat, $learnerId) : []))
        ->map(fn ($p) => (int) (is_array($p) ? $p['id'] : $p->id))->all();
    // every shift's timing (timing rows included) for overlap checks; fixed listable shifts are the ones that can be added
    $osAll = \App\Models\PlanType::where('branch_id', getCurrentBranch())->get(['id', 'name', 'start_time', 'end_time', 'booking_mode', 'parent_id']);
    $osTimes = $osAll->mapWithKeys(fn ($p) => [$p->id => [$p->start_time, $p->end_time]]);
    $osCandidates = $osAll->filter(fn ($p) => $p->parent_id === null && ($p->booking_mode ?: 'fixed') === 'fixed')
        ->sortBy('start_time')->values()
        ->map(fn ($p) => [
            'id' => (int) $p->id, 'name' => $p->name,
            'time' => ($p->start_time && $p->end_time) ? $osFmt($p->start_time) . ' – ' . $osFmt($p->end_time) : '',
            'free' => in_array((int) $p->id, $osFree, true),
        ]);
@endphp
<div class="los" data-op-shifts
     data-select="#plan_type_id10" data-plan="#plan_id10" data-start="#start_date10" data-price="#plan_price10"
     data-price-url="{{ route('getPricePlanwise') }}" data-seats-url="{{ route('gettypeSeatwise') }}"
     @if(!empty($seatInput)) data-seat-input="{{ $seatInput }}" @else data-seat="{{ $osSeat }}" @endif
     data-times='@json($osTimes)' data-candidates='@json($osCandidates)'>
    @if($timing ?? true)
    <div class="los-timing" data-os-timing hidden>
        <div class="los-row">
            <div>
                <label class="form-label" for="osShiftStart">Shift starts at <span class="required-star">*</span></label>
                <input type="text" class="form-control" name="shift_start_time" id="osShiftStart" data-lb-time value="{{ old('shift_start_time') }}" disabled>
            </div>
            <div>
                <label class="form-label" for="osShiftEnd">Shift ends at <span class="required-star">*</span></label>
                <input type="text" class="form-control" name="shift_end_time" id="osShiftEnd" data-lb-time value="{{ old('shift_end_time') }}" disabled>
            </div>
        </div>
        <small class="los-note" data-os-timing-note></small>
        @error('shift_start_time')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
    </div>
    @endif
    <div class="los-add" data-os-add hidden>
        <span class="form-label">Add more shifts <em class="los-opt">Optional · same seat</em></span>
        <div class="lop-chips is-grid" data-os-list role="group" aria-label="Add more shifts"></div>
        @error('extra_shifts')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
    </div>
    <div data-os-inputs></div>
</div>
@once
<link rel="stylesheet" href="{{ asset('public/css/custom-plan.css') }}?v={{ @filemtime(public_path('css/custom-plan.css')) }}">
<script src="{{ asset('public/js/lb-opshift.js') }}?v={{ @filemtime(public_path('js/lb-opshift.js')) }}" defer></script>
@endonce
