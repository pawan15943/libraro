@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-opui.css') }}?v={{ @filemtime(public_path('css/lb-opui.css')) }}">
@endpush

@section('content')

@php
    use Carbon\Carbon;

    $c = $customer;
    $name = $c->name ?? ($c->learner->name ?? 'Learner');
    $initials = collect(explode(' ', trim($name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'L';
    $pic = $c->profile_picture ?? ($c->learner->profile_picture ?? null);
    $photo = $pic && file_exists(public_path($pic)) ? asset($pic) : null;
    $uid = $c->learner_no ?? ($c->learner->learner_no ?? ('#' . $c->id));
    $fmt = fn ($d) => $d ? Carbon::parse($d)->format('d M Y') : '—';
    $inr = fn ($v) => '₹' . number_format((float) $v, 0, '.', ',');

    // seat + floor
    $seatNo = $c->seat_no ?? ($c->learner->seat_no ?? null);
    $seatTxt = $seatNo ? getSeatDisplayShortFloorName($seatNo) : null;
    $floor = null;
    if ($seatNo && is_numeric($seatNo)) {
        $floorObj = \App\Models\Floor::withoutGlobalScopes()->where('branch_id', $c->branch_id ?? getCurrentBranch())
            ->where('from_seat', '<=', (int) $seatNo)->where('to_seat', '>=', (int) $seatNo)->whereNull('deleted_at')->first();
        $floor = $floorObj->name ?? null;
        if ($floor && !str_ends_with(strtolower($floor), 'floor')) $floor .= ' floor';
    }
    $branchName = \App\Models\Branch::where('id', $c->branch_id)->value('name');

    // plan status + progress
    $start = $c->plan_start_date ? Carbon::parse($c->plan_start_date)->startOfDay() : null;
    $end = $c->plan_end_date ? Carbon::parse($c->plan_end_date)->startOfDay() : null;
    $today = Carbon::today();
    $daysLeft = $end ? $today->diffInDays($end, false) : null;
    $totalDays = ($start && $end) ? max(1, $start->diffInDays($end)) : null;
    $usedPct = $totalDays ? max(0, min(100, round($start->diffInDays($today, false) / $totalDays * 100))) : 0;
    $isFrozen = (int) ($c->frozen_status ?? 0) === 1;
    $inGrace = $daysLeft !== null && $daysLeft < 0 && (int) ($c->diff_extend_day ?? -1) >= 0;
    [$stTone, $stText, $stIcon] = match (true) {
        $isFrozen => ['blue', 'Frozen', 'snowflake'],
        $end === null => ['ok', 'No expiry', 'badge-check'],
        $daysLeft < 0 && $inGrace => ['warn', 'In grace period', 'hourglass'],
        $daysLeft < 0 => ['bad', 'Plan ended', 'calendar-x'],
        $daysLeft <= 5 => ['warn', $daysLeft === 0 ? 'Ends today' : 'Ends in ' . $daysLeft . ' ' . ($daysLeft === 1 ? 'day' : 'days'), 'alarm-clock'],
        default => ['ok', 'Active', 'circle-check'],
    };

    // payment
    $total = (float) ($transaction->total_amount ?? 0);
    $paid = (float) ($transaction->paid_amount ?? 0);
    $pending = (float) ($transaction->pending_amount ?? 0);
    $isPaid = ((int) ($transaction->is_paid ?? $c->is_paid ?? 0) === 1) && $pending <= 0;
    $paidPct = $total > 0 ? max(0, min(100, round($paid / $total * 100))) : ($isPaid ? 100 : 0);
    $overdue = !empty($c['overdue']);
    // advance: paid more than the bills (docs/LEARNER_ADVANCE.md)
    $advance = app(\App\Services\LearnerAdvanceService::class)->balance((int) $c->id);
    $orderDues = \App\Services\Ordering\OrderPaymentService::learnerDues((int) $c->id); // unpaid cafeteria orders

    $idProof = match ((int) ($c->id_proof_name ?? 0)) { 1 => 'Aadhaar card', 2 => 'Driving licence', 3 => 'Other', 4 => 'PAN card', 5 => 'Voter ID', default => null };
    $wa = $c->alternate_mobile ?: $c->mobile;
    $canRenew = !empty($c['can_renew_membership']);
    $modeTxt = fn ($m) => match ((string) $m) { '1' => 'Online', '2' => 'Cash', '3' => 'Pay later', default => '—' };

    // Multi-shift booking (docs/ADVANCED_SHIFTS.md): every shift with seat, timing and fee
    $shifts = collect($c['shifts'] ?? []);
    $isMulti = $shifts->count() > 1;
    $seatsLabel = $isMulti ? $shifts->pluck('seat')->filter()->unique()->implode(', ') : $seatTxt;
@endphp

<div class="lpg lbi lbi--narrow lou">

    {{-- ===== Hero ===== --}}
    <section class="lbi-hero is-{{ $stTone }}">
        <div class="lbi-hero-top">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('learners') }}" class="lbi-back" title="Back" aria-label="Back"><x-lb-icon name="arrow-left" /></a>
            <nav class="lbi-crumb" aria-label="Breadcrumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><a href="{{ route('learners') }}">Members</a><x-lb-icon name="chevron-right" /><span aria-current="page">Profile</span></nav>
            @can('has-permission', 'Edit Seat')
            <a href="{{ route('learners.edit', $c->id) }}" class="lbi-edit"><x-lb-icon name="square-pen" /><span>Edit</span></a>
            @endcan
        </div>

        <div class="lbi-id">
            <span class="lbi-ava">@if($photo)<img src="{{ $photo }}" alt="{{ $name }}">@else{{ $initials }}@endif<i class="lbi-ava-dot" title="{{ $stText }}"></i></span>
            <div class="lbi-id-t">
                <h1>{{ $name }}</h1>
                <div class="lbi-tags">
                    <button type="button" class="lbi-uid" data-copy="{{ $uid }}" title="Copy UID">{{ $uid }}<x-lb-icon name="copy" /></button>
                    <span class="lbi-pill is-{{ $stTone }}"><x-lb-icon :name="$stIcon" />{{ $stText }}</span>
                    @if($pending > 0)
                        <span class="lbi-pill is-bad"><x-lb-icon name="indian-rupee" />{{ $inr($pending) }} due{{ $overdue ? ' · overdue' : '' }}</span>
                    @elseif($isPaid)
                        <span class="lbi-pill is-ok"><x-lb-icon name="badge-check" />Fees paid</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="lbi-facts">
            <div><x-lb-icon name="armchair" /><span><small>{{ $isMulti && str_contains((string) $seatsLabel, ',') ? 'Seats' : 'Seat' }}</small><b>{{ $seatsLabel ? 'Seat ' . $seatsLabel : 'General seat' }}</b></span></div>
            <div><x-lb-icon name="layers" /><span><small>Floor</small><b>{{ $floor ?: '—' }}</b></span></div>
            <div><x-lb-icon name="clock-3" /><span><small>{{ $isMulti ? 'Shifts' : 'Shift' }}</small><b>{{ $isMulti ? $shifts->count() . ' shifts' : ($c->plan_type_name ?: '—') }}</b></span></div>
            <div><x-lb-icon name="building" /><span><small>Branch</small><b>{{ $branchName ?: '—' }}</b></span></div>
        </div>

        <div class="lbi-quick">
            @if($c->mobile)
                <a href="tel:+91{{ $c->mobile }}" class="lbi-q"><span><x-lb-icon name="phone" /></span>Call</a>
            @endif
            @if($wa)
                <a href="https://wa.me/91{{ $wa }}" target="_blank" rel="noopener" class="lbi-q is-wa noLoader"><span><x-lb-icon name="message-circle" /></span>WhatsApp</a>
            @endif
            @if($canRenew)
                <a href="{{ route('learner.renew.plan', $c->id) }}" class="lbi-q is-pri"><span><x-lb-icon name="refresh-cw" /></span>Renew</a>
            @endif
            @can('has-permission', 'Learner Transactions')
            <a href="{{ route('learners.transactions', $c->id) }}" class="lbi-q"><span><x-lb-icon name="receipt-text" /></span>Payments</a>
            @endcan
            <a href="{{ route('attendance.summary', $c->id) }}" class="lbi-q"><span><x-lb-icon name="calendar-check" /></span>Attendance</a>
            @can('has-permission', 'Edit Seat')
            <a href="{{ route('learners.edit', $c->id) }}" class="lbi-q"><span><x-lb-icon name="square-pen" /></span>Edit</a>
            @endcan
        </div>
    </section>

    <div class="lbi-grid">
        <div class="lbi-main">

            {{-- ===== Current plan ===== --}}
            <section class="lpg-card lbi-plan">
                <div class="lpg-card-h">
                    <span class="lpg-ic is-ok"><x-lb-icon name="calendar-range" /></span>
                    <div><h2>{{ $c->plan_name ?: 'Current plan' }}</h2><p>@if($isMulti){{ $shifts->pluck('shift')->implode(' + ') }}@else{{ $c->plan_type_name ?: 'Shift' }}@if($c->hours) · {{ $c->start_time }} – {{ $c->end_time }}@endif @endif</p></div>
                    <span class="lpg-tag is-{{ $stTone === 'ok' ? 'ok' : ($stTone === 'bad' ? 'bad' : ($stTone === 'blue' ? 'acc' : 'warn')) }}">{{ $stText }}</span>
                </div>
                @if($start && $end)
                    <div class="lbi-track">
                        <div class="lbi-track-bar is-{{ $stTone }}"><i style="width: {{ $usedPct }}%"></i></div>
                        <div class="lbi-track-l">
                            <span><small>Started</small><b>{{ $fmt($start) }}</b></span>
                            <span class="lbi-left">
                                @if($isFrozen)<b>Paused</b><small>plan is frozen</small>
                                @elseif($daysLeft >= 0)<b>{{ $daysLeft }}</b><small>{{ $daysLeft === 1 ? 'day left' : 'days left' }}</small>
                                @else<b>{{ abs($daysLeft) }}</b><small>days since it ended</small>@endif
                            </span>
                            <span class="is-r"><small>Ends</small><b>{{ $fmt($end) }}</b></span>
                        </div>
                    </div>
                @endif
                @if($isMulti)
                    <div class="lbi-shifts">
                        <div class="lbi-shifts-h"><b>Shifts in this booking</b><small>{{ $shifts->count() }} shifts · one plan and payment</small></div>
                        @foreach($shifts as $i => $sh)
                            <div class="lbi-shift">
                                <span class="lbi-shift-no">{{ $i + 1 }}</span>
                                <span class="lbi-shift-t"><b>{{ $sh['shift'] }}</b><small>Seat {{ $sh['seat'] }}{{ !empty($sh['time']) ? ' · ' . $sh['time'] : '' }}</small></span>
                                <b class="lbi-shift-p">{{ $inr($sh['price']) }}</b>
                            </div>
                        @endforeach
                    </div>
                @endif
                <dl class="lbi-dl">
                    <div><dt>Member since</dt><dd>{{ $fmt($c->join_date ?? $c->created_at) }}</dd></div>
                    <div><dt>Locker</dt><dd>{{ !empty($c->locker_no) ? 'No. ' . $c->locker_no : 'No locker' }}</dd></div>
                    <div><dt>Timings</dt><dd>@if($isMulti){{ $shifts->pluck('time')->filter()->implode(', ') }}@else{{ $c->hours ? $c->start_time . ' – ' . $c->end_time . ' (' . $c->hours . ' hrs)' : '—' }}@endif</dd></div>
                    <div><dt>Days with us</dt><dd>{{ max(0, (int) ($c['days_since_start'] ?? 0)) }} days</dd></div>
                </dl>
            </section>

            {{-- ===== Details ===== --}}
            <section class="lpg-card">
                <div class="lpg-card-h">
                    <span class="lpg-ic is-violet"><x-lb-icon name="user-round" /></span>
                    <div><h2>Personal &amp; contact</h2><p>How to reach {{ \Illuminate\Support\Str::of($name)->explode(' ')->first() }}</p></div>
                </div>
                <dl class="lbi-dl lbi-dl--2">
                    <div><dt>Mobile</dt><dd>@if($c->mobile)<a href="tel:+91{{ $c->mobile }}">+91 {{ $c->mobile }}</a>@else — @endif</dd></div>
                    <div><dt>WhatsApp</dt><dd>@if($wa)<a href="https://wa.me/91{{ $wa }}" target="_blank" rel="noopener" class="noLoader">+91 {{ $wa }}</a>@else — @endif</dd></div>
                    <div><dt>Email</dt><dd>@if($c->email)<a href="mailto:{{ $c->email }}">{{ $c->email }}</a>@else — @endif</dd></div>
                    <div><dt>Date of birth</dt><dd>{{ $c->dob ? $fmt($c->dob) : '—' }}</dd></div>
                    <div><dt>Father / guardian</dt><dd>{{ $c->father_name ?: '—' }}</dd></div>
                    <div><dt>ID proof</dt><dd>{{ $idProof ?: '—' }}{{ $idProof && $c->id_proof_number ? ' · ' . $c->id_proof_number : '' }}</dd></div>
                    @if($c->address)<div class="is-wide"><dt>Address</dt><dd>{{ $c->address }}</dd></div>@endif
                    @if($c->remark)<div class="is-wide"><dt>Note</dt><dd>{{ $c->remark }}</dd></div>@endif
                </dl>
            </section>

            {{-- ===== Plan history ===== --}}
            @if(!empty($renew_detail) && count($renew_detail))
            <section class="lpg-card">
                <div class="lpg-card-h">
                    <span class="lpg-ic"><x-lb-icon name="list-tree" /></span>
                    <div><h2>Plan history</h2><p>Every plan {{ \Illuminate\Support\Str::of($name)->explode(' ')->first() }} has taken, newest first</p></div>
                    <span class="lpg-n">{{ count($renew_detail) }}</span>
                </div>
                <ol class="lbi-tl">
                    @foreach($renew_detail->sortByDesc('plan_start_date') as $rd)
                        @php
                            $rt = App\Models\LearnerTransaction::where('learner_detail_id', $rd->id)->where('is_paid', 1)->first();
                            $isNow = $rd->id == $c->learner_detail_id;
                            // a multi-shift plan lists all its shifts
                            $rdShifts = collect([$rd->planType->name ?? null])
                                ->merge(\App\Models\LearnerDetailSlot::where('learner_detail_id', $rd->id)->with('planType')->orderBy('id')->get()->map(fn ($s) => optional($s->planType)->name))
                                ->filter()->implode(' + ');
                        @endphp
                        <li class="{{ $isNow ? 'is-now' : '' }}">
                            <i class="lbi-tl-dot"></i>
                            <div class="lbi-tl-t">
                                <b>{{ $rd->plan->name ?? 'Plan' }} <small>{{ $rdShifts }}</small>@if($isNow)<span class="lpg-tag is-ok">Current</span>@endif</b>
                                <small>{{ $fmt($rd->plan_start_date) }} → {{ $fmt($rd->plan_end_date) }} · {{ $modeTxt($rd->payment_mode) }}@if($rt && $rt->transaction_id) · #{{ $rt->transaction_id }}@endif</small>
                            </div>
                            <div class="lbi-tl-r">
                                <b>{{ $rt ? $inr($rt->total_amount) : '—' }}</b>
                                @can('has-permission', 'Receipt Generation')
                                @if($rt)
                                <form action="{{ route('learner.receipt.download') }}" method="POST" target="_blank">
                                    @csrf
                                    <input type="hidden" name="learner_id" value="{{ $rd->learner_id }}">
                                    <input type="hidden" name="learner_detail_id" value="{{ $rd->id }}">
                                    <input type="hidden" name="id" value="{{ $rt->id }}">
                                    <input type="hidden" name="type" value="learner">
                                    <button type="submit" class="lpg-round noLoader" title="Download receipt" aria-label="Download receipt"><x-lb-icon name="download" /></button>
                                </form>
                                @endif
                                @endcan
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
            @endif

            {{-- ===== Seat history ===== --}}
            @if(!is_null($seat_history) && $seat_history->isNotEmpty())
            <section class="lpg-card">
                <div class="lpg-card-h">
                    <span class="lpg-ic is-warn"><x-lb-icon name="armchair" /></span>
                    <div><h2>Earlier on seat {{ $seatTxt }}</h2><p>Members who sat here before</p></div>
                    <span class="lpg-n">{{ $seat_history->count() }}</span>
                </div>
                <div class="lbi-prev">
                    @foreach($seat_history as $ln)
                        @php $fd = $ln->learnerDetails->first(); @endphp
                        <div class="lbi-prev-i">
                            <span class="lbi-prev-ava">{{ mb_strtoupper(mb_substr($ln->name ?? 'L', 0, 1)) }}</span>
                            <span class="lbi-prev-t">
                                @can('has-permission', 'View Seat')
                                    <a href="{{ route('learners.show', $ln->id) }}"><b>{{ $ln->name }}</b></a>
                                @else
                                    <b>{{ $ln->name }}</b>
                                @endcan
                                <small>{{ $fd->plan->name ?? '' }}{{ !empty($fd->planType->name) ? ' · ' . $fd->planType->name : '' }} · {{ $fmt($fd->plan_start_date ?? null) }} → {{ $fmt($fd->plan_end_date ?? null) }}</small>
                            </span>
                            @if($ln->mobile)<a href="tel:+91{{ $ln->mobile }}" class="lpg-round" title="Call" aria-label="Call {{ $ln->name }}"><x-lb-icon name="phone" /></a>@endif
                        </div>
                    @endforeach
                </div>
            </section>
            @endif
        </div>

        <aside class="lbi-side">
            {{-- ===== Payment ===== --}}
            <section class="lpg-card lbi-pay {{ $pending > 0 ? 'is-due' : '' }}">
                <div class="lpg-card-h">
                    <span class="lpg-ic {{ $pending > 0 ? 'is-bad' : 'is-ok' }}"><x-lb-icon name="wallet" /></span>
                    <div><h2>Payment</h2><p>Current plan</p></div>
                </div>
                <div class="lbi-pay-big">
                    @if($pending > 0)
                        <small>Still to collect</small><b class="is-bad">{{ $inr($pending) }}</b>
                    @else
                        <small>{{ $isPaid ? 'All paid' : 'Paid' }}</small><b class="is-ok">{{ $inr($paid ?: $total) }}</b>
                    @endif
                </div>
                <div class="lbi-meter"><i style="width: {{ $paidPct }}%"></i></div>
                <dl class="lbi-dl lbi-dl--tight">
                    <div><dt>Plan total</dt><dd>{{ $inr($total) }}</dd></div>
                    <div><dt>Paid</dt><dd class="is-ok">{{ $inr($paid) }}</dd></div>
                    <div><dt>Pending</dt><dd class="{{ $pending > 0 ? 'is-bad' : '' }}">{{ $inr($pending) }}</dd></div>
                    @if($advance > 0)<div title="Paid more than the bills: used on the next renewal, or refunded when the learner leaves"><dt>Advance</dt><dd class="is-ok">{{ $inr($advance) }} <small>· next renewal</small></dd></div>@endif
                    @if($orderDues['amount'] > 0)<div title="Tea, snacks, print… ordered and not paid yet (Orders)"><dt>Orders unpaid</dt><dd class="is-bad">{{ $inr($orderDues['amount']) }} <small>· {{ $orderDues['count'] }} {{ \Illuminate\Support\Str::plural('order', $orderDues['count']) }}@if (Route::has('orders.reports') && Gate::allows('has-permission', 'Manage Orders')) · <a href="{{ route('orders.reports', ['range' => '30d']) }}#dues">collect</a>@endif</small></dd></div>@endif
                    @if(!empty($transaction->due_date) && $pending > 0)<div><dt>Due on</dt><dd>{{ $fmt($transaction->due_date) }}</dd></div>@endif
                    @if(!empty($transaction->paid_date))<div><dt>Paid on</dt><dd>{{ $fmt($transaction->paid_date) }}</dd></div>@endif
                </dl>
                <div class="lbi-pay-acts">
                    @if($pending > 0)
                        <a href="javascript:void(0)" class="lp-btn settlement-learner" data-id="{{ $c->id }}" data-learnerDetail="{{ $c->learner_detail_id }}"><x-lb-icon name="indian-rupee" />Collect {{ $inr($pending) }}</a>
                    @endif
                    @if(isset($transaction->id) && $paid > 0)
                        <form action="{{ route('learner.receipt.download') }}" method="POST" target="_blank">
                            @csrf
                            <input type="hidden" name="learner_id" value="{{ $c->id }}">
                            <input type="hidden" name="learner_detail_id" value="{{ $c->learner_detail_id }}">
                            <input type="hidden" name="id" value="{{ $transaction->id }}">
                            <input type="hidden" name="type" value="learner">
                            <button type="submit" class="lp-btn lp-btn--ghost noLoader"><x-lb-icon name="printer" />Receipt</button>
                        </form>
                    @endif
                    @can('has-permission', 'Learner Transactions')
                    <a href="{{ route('learners.transactions', $c->id) }}" class="lp-link">All payments<x-lb-icon name="arrow-right" /></a>
                    @endcan
                </div>
            </section>

            {{-- ===== Activity ===== --}}
            {{-- a learner's activity: Pro plan --}}
            @if(isset($learnerlog) && $learnerlog->count() && auth()->user()->can('has-permission', 'Learner Activity'))
            <section class="lpg-card lbi-log">
                <div class="lpg-card-h">
                    <span class="lpg-ic is-blue"><x-lb-icon name="activity" /></span>
                    <div><h2>Activity</h2><p>Bookings, renewals and changes</p></div>
                    <span class="lpg-n">{{ $learnerlog->count() }}</span>
                </div>
                <ol class="lbi-log-l">
                    @foreach($learnerlog->take(8) as $item)
                        <li>
                            <i></i>
                            <div>
                                @if(!empty($item['operation_type']))<b>{{ $item['operation_type'] }}</b>@endif
                                <p>{!! $item['message'] !!}</p>
                                <small>{{ $item['date'] }} · {{ $item['time'] }}</small>
                            </div>
                        </li>
                    @endforeach
                </ol>
                @if($learnerlog->count() > 8)
                    <a href="{{ route('activities.all', ['learner_id' => $c->id]) }}" class="lp-link lbi-more">See all {{ $learnerlog->count() }}<x-lb-icon name="arrow-right" /></a>
                @endif
            </section>
            @endif
        </aside>
    </div>
</div>

<script>
    document.addEventListener('click', function (e) {
        var b = e.target.closest('.lbi-uid[data-copy]');
        if (!b) return;
        var v = b.getAttribute('data-copy');
        if (navigator.clipboard) navigator.clipboard.writeText(v).then(function () { if (window.lpToast) window.lpToast('Copied ' + v); });
    });
</script>

@endsection
