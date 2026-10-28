<div class="lex-bar">
    <span>Showing <b id="visibleCountBadge">{{ $expences->count() }}</b> of {{ $expences->total() }}@if($expences->lastPage() > 1) · page {{ $expences->currentPage() }} of {{ $expences->lastPage() }}@endif</span>
</div>

<div class="lpg-list">
    <div class="lex-head" aria-hidden="true"><span></span><span>Expense</span><span>Mode</span><span>Date</span><span>Amount</span><span></span></div>
    <div id="expenseCardsContainer">
        @forelse($expences as $exp)
            @php
                $sno = ($expences->currentPage() - 1) * $expences->perPage() + $loop->iteration;
                $mode = strtoupper((string) ($exp->payment_mode ?? ''));
                [$modeType, $modeLabel, $modeTone] = match (true) {
                    in_array($mode, ['ONLINE', '1'], true) => ['online', 'Online', 'is-acc'],
                    in_array($mode, ['OFFLINE', '2'], true) => ['offline', 'Cash', 'is-ok'],
                    in_array($mode, ['PAYLATER', 'PAY LATER', '3'], true) => ['paylater', 'Pay later', 'is-warn'],
                    default => ['default', $exp->payment_mode ?: 'Other', ''],
                };
                $formattedDate = \Carbon\Carbon::parse($exp->date)->format('d M Y');
                $searchContent = strtolower(($exp->particular ?? '') . ' ' . ($exp->transaction_id ?? '') . ' ' . $modeLabel . ' ' . $formattedDate . ' ' . $exp->amount);
            @endphp
            <div class="lex-row collection-record-card"
                 data-search="{{ $searchContent }}"
                 data-mode="{{ $modeType }}"
                 data-particular="{{ $exp->particular ?? '' }}"
                 data-ref="{{ $exp->transaction_id ?? '' }}"
                 data-amount="{{ $exp->amount }}"
                 data-date="{{ $formattedDate }}"
                 data-sno="{{ $sno }}">
                <span class="lex-ic"><x-lb-icon name="trending-down" /></span>
                <span class="lex-t">
                    <b title="{{ $exp->particular }}">{{ $exp->particular ?? 'Expense' }}</b>
                    @if(!empty($exp->transaction_id))<small>#{{ $exp->transaction_id }}</small>@endif
                </span>
                <span class="lex-meta">
                    <span class="lex-mode lpg-tag {{ $modeTone }}">{{ $modeLabel }}</span>
                    <span class="lex-date">{{ $formattedDate }}</span>
                </span>
                <span class="lex-amt">₹{{ number_format($exp->amount, 2) }}</span>
                <span class="lex-act">
                    <button type="button" class="lpg-round is-bad" onclick="confirmDelete({{ $exp->id }})" title="Delete expense" aria-label="Delete expense"><x-lb-icon name="trash" /></button>
                </span>
            </div>
        @empty
            <div class="lpg-empty" style="border:0;border-radius:0">
                <span><x-lb-icon name="funnel" /></span>
                <b>No expenses match these filters</b>
                <p>Try a wider date range or another category.</p>
            </div>
        @endforelse
    </div>
    <div class="lpg-empty d-none" id="searchEmptyState" style="border:0;border-radius:0">
        <span><x-lb-icon name="search" /></span>
        <b>Nothing on this page matches</b>
        <p>Search looks at the entries on this page. Use Filters to search across all dates.</p>
    </div>
</div>

@if($expences->lastPage() > 1)
    <ul class="lpg-pages" id="paginationWrapper">
        <li><a href="{{ $expences->onFirstPage() ? '#' : $expences->previousPageUrl() }}" class="expense-page-link {{ $expences->onFirstPage() ? 'is-off' : '' }}" aria-label="Previous page"><x-lb-icon name="chevron-left" /></a></li>
        @for($i = max(1, $expences->currentPage() - 2); $i <= min($expences->lastPage(), $expences->currentPage() + 2); $i++)
            <li><a href="{{ $expences->url($i) }}" class="expense-page-link {{ $expences->currentPage() == $i ? 'is-on' : '' }}">{{ $i }}</a></li>
        @endfor
        <li><a href="{{ $expences->hasMorePages() ? $expences->nextPageUrl() : '#' }}" class="expense-page-link {{ $expences->hasMorePages() ? '' : 'is-off' }}" aria-label="Next page"><x-lb-icon name="chevron-right" /></a></li>
    </ul>
@endif
