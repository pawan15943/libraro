@php
    use Carbon\Carbon;
    $today = Carbon::today();
    $branchName = getCurrentBranchName();
    $filter = request()->get('filter', 'all');
    $titles = ['all' => 'Everyone with a balance', 'pending' => 'Pending — not due yet', 'overdue' => 'Overdue', 'expired' => 'Expired members with dues', 'adjusted' => 'Adjusted', 'received' => 'Received'];
    $tones = ['overdue' => 'is-bad', 'pending' => 'is-warn', 'paid' => 'is-ok', 'received' => 'is-ok', 'adjusted' => 'is-acc', 'expired' => ''];
    $pageSum = collect($learners->items())->sum(fn ($l) => (float) ($l['payment']['pending_amount'] ?? 0));
@endphp

<div class="rpt-sec">{{ $titles[$filter] ?? 'Pending payments' }} <span class="lpg-n">{{ $learners->total() }}</span>
    @if($pageSum > 0)<span class="rpt-sec-r">{{ rptInr($pageSum) }} on this page</span>@endif
</div>

@if($learners->isEmpty())
    <div class="lpg-empty">
        <span><x-lb-icon name="circle-check" /></span>
        <b>Nothing here</b>
        <p>No member matches this status or search. Try “All” or clear the filters.</p>
    </div>
@else
    <div class="rpt-list">
        @foreach($learners as $i => $l)
            @php
                $pay = $l['payment'] ?? [];
                $st = strtolower($pay['status'] ?? 'pending');
                $st = $st === 'paid' ? 'received' : $st;
                $amt = (float) ($pay['pending_amount'] ?? 0);
                $due = !empty($pay['due_date']) ? Carbon::parse($pay['due_date']) : null;
                $end = !empty($l['plan_end_date']) ? Carbon::parse($l['plan_end_date']) : null;
                $dueDays = $due ? (int) $today->diffInDays($due, false) : null;
                $name = ucwords(strtolower($l['name'] ?? 'Member'));
                $phone = rptPhone($l['mobile'] ?? '');
                $seat = $l['seat_no'] ?: null;
                $dueText = !$due ? null : ($dueDays < 0 ? abs($dueDays) . 'd overdue' : ($dueDays === 0 ? 'Due today' : 'Due in ' . $dueDays . 'd'));
                $msg = "Hi {$name},\n\nA balance of " . rptInr($amt) . " is pending for your library seat" . ($seat ? " ({$seat})" : '') . ($due ? ', due on ' . $due->format('j M Y') : '') . ".\nPlease clear it at the desk or pay online.\n\n– {$branchName}";
            @endphp
            <article class="rpt-row" data-csv="{{ json_encode(['Name' => $name, 'UID' => $l['learner_no'] ?? '', 'Seat' => $seat, 'Mobile' => $phone, 'Plan' => $l['plan'] ?? '', 'Shift' => $l['plan_type'] ?? '', 'Plan ends' => optional($end)->format('d-m-Y'), 'Status' => ucfirst($st), 'Pending' => $amt, 'Due date' => optional($due)->format('d-m-Y')]) }}">
                <span class="lp-face c{{ $i % 5 + 1 }}">{{ rptInitials($name) }}</span>
                <div class="rpt-who">
                    <div class="rpt-who-t">
                        <a href="{{ route('learners.show', $l['id']) }}">{{ $name }}</a>
                        <span class="rpt-seat"><x-lb-icon name="armchair" />{{ $seat ?: 'General' }}</span>
                        <span class="lpg-tag {{ $tones[$st] ?? '' }}">{{ $st === 'overdue' && $dueText ? $dueText : ucfirst($st) }}</span>
                    </div>
                    <small>
                        <span>{{ $l['learner_no'] ?? '' }}</span>
                        @if(!empty($l['plan']))<span>{{ $l['plan'] }}@if(!empty($l['plan_type'])) · {{ $l['plan_type'] }}@endif</span>@endif
                        @if($end)<span>Plan ends {{ $end->format('j M Y') }}</span>@endif
                        @if($phone)<span class="is-ph">+91 {{ $phone }}</span>@endif
                    </small>
                </div>
                <div class="rpt-amt">
                    @if($st === 'received')
                        <b class="is-ok">Cleared</b>
                    @else
                        <b class="{{ $amt > 0 ? 'is-bad' : '' }}">{{ rptInr($amt) }}</b>
                    @endif
                    @if($due)<small>{{ $st === 'overdue' ? 'was due ' . $due->format('j M') : 'due ' . $due->format('j M Y') }}</small>@endif
                </div>
                <div class="rpt-acts">
                    @if($phone)
                        <a href="tel:+91{{ $phone }}" class="lpg-round" title="Call" aria-label="Call {{ $name }}"><x-lb-icon name="phone" /></a>
                        @if($amt > 0)
                            <a href="https://wa.me/91{{ $phone }}?text={{ rawurlencode(appendPayLink($msg, learnerPayLink($l['id'] ?? null, 'due'), 'due')) }}" target="_blank" rel="noopener" class="lpg-round is-wa noLoader" title="Send reminder on WhatsApp" aria-label="Remind {{ $name }} on WhatsApp"><x-lb-icon name="message-circle" /></a>
                        @endif
                    @endif
                    @if(!empty($l['receipt_url']) && $st === 'received')
                        <a href="{{ $l['receipt_url'] }}" target="_blank" rel="noopener" class="lpg-round noLoader" title="Receipt" aria-label="Receipt for {{ $name }}"><x-lb-icon name="receipt" /></a>
                    @endif
                    @if($amt > 0)
                        <button type="button" class="lpg-round is-pri settlement-learner" data-id="{{ $l['id'] }}" data-learnerdetail="{{ $l['learner_detail_id'] ?? '' }}" title="Collect payment" aria-label="Collect from {{ $name }}"><x-lb-icon name="indian-rupee" /></button>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    @if($learners->lastPage() > 1)
        @php $cur = $learners->currentPage(); $last = $learners->lastPage(); @endphp
        <nav class="lpg-pages pp-pages" aria-label="Pages">
            <span class="pp-of">{{ $learners->firstItem() }}–{{ $learners->lastItem() }} of {{ $learners->total() }}</span>
            <a href="#" data-rpt-page="{{ $cur - 1 }}" class="{{ $cur <= 1 ? 'is-off' : '' }}" aria-label="Previous page"><x-lb-icon name="chevron-left" /></a>
            @for($p = 1; $p <= $last; $p++)
                @if($p === 1 || $p === $last || abs($p - $cur) <= 1)
                    <a href="#" data-rpt-page="{{ $p }}" class="{{ $p === $cur ? 'is-on' : '' }}" @if($p === $cur) aria-current="page" @endif>{{ $p }}</a>
                @elseif(abs($p - $cur) === 2)
                    <span>…</span>
                @endif
            @endfor
            <a href="#" data-rpt-page="{{ $cur + 1 }}" class="{{ $cur >= $last ? 'is-off' : '' }}" aria-label="Next page"><x-lb-icon name="chevron-right" /></a>
        </nav>
    @endif
@endif
