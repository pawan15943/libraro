@php
    use Carbon\Carbon;
    $m = $metrics;
    $today = Carbon::today();
    $branchName = getCurrentBranchName();
    $oldestName = $m['oldest'] ? ucwords(strtolower(optional($m['oldest']->learner)->name ?? '')) : null;
@endphp

<div class="rpt-kpis">
    <div class="rpt-kpi is-bad">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="hand-coins" /></span></div>
        <span>Total outstanding</span>
        <b>{{ rptInr($m['outstanding']) }}</b>
        <small>{{ $m['owing'] }} {{ \Illuminate\Support\Str::plural('member', $m['owing']) }} owe money</small>
    </div>
    <div class="rpt-kpi is-warn">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="clock-alert" /></span></div>
        <span>Overdue</span>
        <b>{{ rptInr($m['overdue']) }}</b>
        <small>{{ $m['overdue_count'] }} past due date</small>
    </div>
    <div class="rpt-kpi">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="calendar-clock" /></span></div>
        <span>Due in next 7 days</span>
        <b>{{ rptInr($m['week']) }}</b>
        <small>{{ $m['week_count'] }} {{ \Illuminate\Support\Str::plural('payment', $m['week_count']) }} coming up</small>
    </div>
    <a class="rpt-kpi is-ok" href="{{ route('payment.collection.report') }}">
        <div class="rpt-kpi-top"><span class="rpt-kpi-ic"><x-lb-icon name="wallet" /></span><span class="rpt-kpi-go"><x-lb-icon name="arrow-up-right" /></span></div>
        <span>Collected this month</span>
        <b>{{ rptInr(\App\Models\LearnerTransaction::where('library_id', getLibraryId())->when(getCurrentBranch(), fn ($q) => $q->where('branch_id', getCurrentBranch()))->whereBetween('paid_date', [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()])->sum('paid_amount')) }}</b>
        <small>See collections</small>
    </a>
</div>

@if($m['outstanding'] > 0)
    <p class="rpt-insight {{ $m['overdue'] > 0 ? 'is-warn' : '' }}">
        <x-lb-icon name="sparkles" />
        <span>
            <b>{{ rptInr($m['outstanding']) }}</b> is waiting to be collected.
            @if($m['oldest'])
                Start with <b>{{ $oldestName }}</b> — overdue for {{ $m['oldest_days'] }} {{ \Illuminate\Support\Str::plural('day', $m['oldest_days']) }}.
            @endif
            A friendly WhatsApp reminder usually brings most of it in within a week.
        </span>
    </p>
@endif

<div class="rpt-sec">{{ ['pending' => 'Everyone who owes', 'overdue' => 'Overdue', 'week' => 'Due in the next 7 days', 'cleared' => 'Cleared in the last 30 days'][$due_status] ?? 'Dues' }} <span class="lpg-n">{{ $rows->count() }}</span></div>

@if($rows->isEmpty())
    <div class="lpg-empty">
        <span><x-lb-icon name="circle-check" /></span>
        <b>{{ $due_status === 'cleared' ? 'Nothing cleared recently' : 'No dues here' }}</b>
        <p>{{ $due_status === 'cleared' ? 'Balances settled in the last 30 days will show here.' : 'Everyone in this list has paid in full. Nice!' }}</p>
    </div>
@else
    <div class="rpt-list">
        @foreach($rows as $i => $t)
            @php
                $l = $t->learner;
                $d = $t->learnerDetail;
                $name = ucwords(strtolower($l->name ?? ('Learner #' . $t->learner_id)));
                $seat = $l && $l->seat_no ? getSeatDisplayByMainNo($l->seat_no) : null;
                $phone = rptPhone(optional($l)->mobile);
                $due = $dueOf($t);
                $days = $due ? (int) $today->diffInDays($due, false) : null;
                $pending = (float) $t->pending_amount;
                $state = $due_status === 'cleared' ? ['Cleared', 'is-ok']
                    : ($days === null ? ['No due date', ''] : ($days < 0 ? [abs($days) . 'd overdue', 'is-bad'] : ($days === 0 ? ['Due today', 'is-warn'] : ['Due in ' . $days . 'd', 'is-acc'])));
                $msg = "Hi {$name},\n\nA balance of " . rptInr($pending) . " is pending for your seat" . ($seat ? " ({$seat})" : '') . ($due ? ', due on ' . $due->format('j M Y') : '') . ".\nPlease clear it at the desk or pay online at your convenience.\n\n– {$branchName}";
            @endphp
            <article class="rpt-row" data-q="{{ strtolower($name . ' ' . $seat . ' ' . $phone) }}"
                data-csv="{{ json_encode(['Name' => $name, 'Seat' => $seat, 'Mobile' => $phone, 'Plan' => optional(optional($d)->plan)->name, 'Bill total' => (float) $t->total_amount, 'Paid' => (float) $t->paid_amount, 'Pending' => $pending, 'Due date' => optional($due)->format('d-m-Y'), 'Status' => $state[0]]) }}">
                <span class="lp-face c{{ $i % 5 + 1 }}">{{ rptInitials($name) }}</span>
                <div class="rpt-who">
                    <div class="rpt-who-t">
                        <a href="{{ route('learners.show', $t->learner_id) }}">{{ $name }}</a>
                        @if($seat)<span class="rpt-seat"><x-lb-icon name="armchair" />{{ $seat }}</span>@endif
                        <span class="lpg-tag {{ $state[1] }}">{{ $state[0] }}</span>
                    </div>
                    <small>
                        @if($due)<span>Due {{ $due->format('j M Y') }}</span>@endif
                        <span>Paid {{ rptInr($t->paid_amount) }} of {{ rptInr($t->total_amount) }}</span>
                        @if($phone)<span class="is-ph">+91 {{ $phone }}</span>@endif
                    </small>
                </div>
                <div class="rpt-amt">
                    @if($due_status === 'cleared')
                        <b class="is-ok">{{ rptInr($t->paid_amount) }}</b><small>paid</small>
                    @else
                        <b class="is-bad">{{ rptInr($pending) }}</b><small>to collect</small>
                    @endif
                </div>
                <div class="rpt-acts">
                    @if($phone)
                        <a href="tel:+91{{ $phone }}" class="lpg-round" title="Call" aria-label="Call {{ $name }}"><x-lb-icon name="phone" /></a>
                        @if($due_status !== 'cleared')
                            <a href="https://wa.me/91{{ $phone }}?text={{ rawurlencode($msg) }}" target="_blank" rel="noopener" class="lpg-round is-wa noLoader" title="Send reminder on WhatsApp" aria-label="Remind {{ $name }} on WhatsApp"><x-lb-icon name="message-circle" /></a>
                        @endif
                    @endif
                    @if($due_status !== 'cleared')
                        <a href="javascript:void(0)" class="lpg-round is-pri settlement-learner" data-id="{{ $t->learner_id }}" data-learnerDetail="{{ $t->learner_detail_id }}" title="Collect payment" aria-label="Collect from {{ $name }}"><x-lb-icon name="indian-rupee" /></a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
    <div class="lpg-empty" data-rpt-none hidden><span><x-lb-icon name="search" /></span><b>No match</b><p>Nobody in this list matches your search.</p></div>
@endif
