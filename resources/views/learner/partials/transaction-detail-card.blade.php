{{--
    Collapsible subscription transaction card.
    Shared by the Overview tab's "Last subscription transaction" and the All transactions tab.
--}}
@php
    $relatedActivities = collect($transaction['activity'] ?? []);
    $isPending = (float) ($transaction['pending_amount'] ?? 0) > 0;
    $hasRefund = (float) ($transaction['extra_amount'] ?? 0) > 0 || $relatedActivities->contains(fn ($activity) => strtoupper((string) ($activity['payment_type'] ?? '')) === 'REFUND');
    $cardDate = $transaction['paid_date'] ?: ($transaction['transaction_date'] ?? '');
    $isCardOpen = isset($isOpen) ? (bool) $isOpen : true;
    $paymentMode = !empty($transaction['payment_mode']) ? $transaction['payment_mode'] : 'Online';
    $transactionType = $typeLabel($transaction['transaction_type'] ?? 'BOOK SEAT');
    $receiptUrl = $transaction['subscription_download_receipt_link'] ?? (!empty($transaction['id']) ? \Illuminate\Support\Facades\URL::signedRoute('receipt.signed', ['transactionId' => $transaction['id']]) : null);
    $deleteUrl = !empty($transaction['id']) ? route('learners.transactions.destroy', $transaction['id']) : ($transaction['delete_url'] ?? null);
    $receipts = $relatedActivities->isNotEmpty()
        ? $relatedActivities->map(fn ($a) => [
            'ref' => $a['transaction_id'] ?: ($transaction['transaction_ref'] ?? $a['id']),
            'note' => $a['trxn_message'] ?: ($typeLabel($a['payment_type'] ?? '') . ' (' . ($a['payment_mode'] ?? 'Online') . ')'),
            'amount' => $a['paid_amount'] ?? 0,
            'debit' => strtolower((string) ($a['dr_cr'] ?? '')) === 'dr',
            'date' => $a['updated_date'] ?: ($a['transaction_date'] ?? $cardDate),
            'by' => $a['updated_by_name'] ?: ($a['added_by_name'] ?? 'Admin'),
        ])
        : collect([[
            'ref' => $transaction['transaction_ref'] ?? $transaction['id'],
            'note' => 'Collected ' . ($transaction['transaction_type'] ?? 'Seat Booking') . ' payment (' . ($transaction['payment_mode'] ?? 'Online') . ')',
            'amount' => $transaction['total_paid_amount'] ?? 0,
            'debit' => false,
            'date' => $transaction['updated_date'] ?: ($transaction['transaction_date'] ?? $cardDate),
            'by' => $transaction['updated_by_name'] ?: ($transaction['added_by_name'] ?? 'Admin'),
        ]]);
@endphp

<article class="ltx-tx" {!! $cardAttrs ?? '' !!}>
    <button class="ltx-tx-h {{ $isCardOpen ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $isCardOpen ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
        <span class="ltx-item-ic {{ $hasRefund ? 'is-bad' : 'is-ok' }}"><x-lb-icon name="arrow-up-right" /></span>
        <span class="ltx-item-t">
            <b>{{ $transactionType }}</b>
            <small><span>{{ $dateFmt($cardDate) }}</span><span>{{ $transaction['added_by_name'] ?? 'Admin' }}</span></small>
        </span>
        <span class="ltx-item-r">
            <strong>₹{{ $fmt($transaction['total_paid_amount'] ?? 0) }}</strong>
            <span class="ltx-mode is-{{ $modeTone($paymentMode) }}">{{ $paymentMode }}</span>
        </span>
        <span class="ltx-chev"><x-lb-icon name="chevron-down" /></span>
    </button>

    <div class="collapse {{ $isCardOpen ? 'show' : '' }}" id="{{ $collapseId }}">
        <div class="ltx-tx-b">
            <div class="ltx-grid4">
                <div class="ltx-cell"><span><x-lb-icon name="tag" />Plan price</span><b>₹{{ $fmt($transaction['plan_price'] ?? 0) }}</b></div>
                <div class="ltx-cell"><span><x-lb-icon name="lock" />Locker</span><b>₹{{ $fmt($transaction['locker_amount'] ?? 0) }}</b></div>
                <div class="ltx-cell"><span><x-lb-icon name="gift" />Discount</span><b>− ₹{{ $fmt($transaction['discount_amount'] ?? 0) }}</b></div>
                <div class="ltx-cell"><span><x-lb-icon name="wallet" />Total</span><b>₹{{ $fmt($transaction['total_amount'] ?? 0) }}</b></div>
            </div>

            <div class="ltx-receipts">
                @foreach($receipts as $r)
                    <div class="ltx-rc">
                        <span class="ltx-rc-ic"><x-lb-icon name="receipt-text" /></span>
                        <div class="ltx-rc-t">
                            <span class="ltx-ref">#{{ ltrim((string) $r['ref'], '#') }}</span>
                            <p>{{ $r['note'] }}</p>
                            <small>Modified {{ $dateFmt($r['date']) }} · by {{ $r['by'] }}</small>
                        </div>
                        <strong class="{{ $r['debit'] ? 'is-bad' : '' }}">{{ $r['debit'] ? '−' : '' }}₹{{ $fmt($r['amount']) }}</strong>
                    </div>
                @endforeach
            </div>

            <div class="ltx-sum">
                <div><span>Final payable</span><b>₹{{ $fmt($transaction['final_payable_amount'] ?? 0) }}</b></div>
                <div><span>Paid</span><b class="is-ok">₹{{ $fmt($transaction['total_paid_amount'] ?? 0) }}</b></div>
                <div><span>{{ $isPending ? 'Pending' : 'Extra' }}</span><b class="{{ $isPending ? 'is-bad' : '' }}">₹{{ $isPending ? $fmt($transaction['pending_amount'] ?? 0) : $fmt($transaction['extra_amount'] ?? 0) }}</b></div>
            </div>

            <div class="ltx-actions">
                @if($receiptUrl)
                    <a href="{{ $receiptUrl }}" target="_blank" class="ltx-btn"><x-lb-icon name="download" /><span>Receipt</span></a>
                @else
                    <span class="ltx-btn is-off"><x-lb-icon name="download" /><span>Receipt</span></span>
                @endif

                @if(!empty($transaction['id']))
                    <a href="{{ route('learners.transactions.edit', $transaction['id']) }}" class="ltx-btn"><x-lb-icon name="square-pen" /><span>Edit</span></a>
                @endif

                @if($deleteUrl)
                    <form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="learner_id" value="{{ $learner->id }}">
                        <button type="submit" class="ltx-btn ltx-btn--bad"><x-lb-icon name="trash" /><span>Delete</span></button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</article>
