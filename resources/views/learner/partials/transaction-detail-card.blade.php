{{--
    Executive Transaction Card matching Screenshot UI.
    Shared by Overview tab's "Last Subscription Transaction" and All Transactions tab.
--}}
@php
    $relatedActivities = collect($transaction['activity'] ?? []);
    $isPending = (float) ($transaction['pending_amount'] ?? 0) > 0;
    $hasRefund = (float) ($transaction['extra_amount'] ?? 0) > 0 || $relatedActivities->contains(fn ($activity) => strtoupper((string) ($activity['payment_type'] ?? '')) === 'REFUND');
    $cardDate = $transaction['paid_date'] ?: ($transaction['transaction_date'] ?? '');
    $isCardOpen = isset($isOpen) ? (bool)$isOpen : true; // Default open as in screenshot
    $paymentMode = !empty($transaction['payment_mode']) ? strtoupper($transaction['payment_mode']) : 'ONLINE';
    $transactionType = strtoupper($typeLabel($transaction['transaction_type'] ?? 'BOOK SEAT'));
    $receiptUrl = $transaction['subscription_download_receipt_link'] ?? (!empty($transaction['id']) ? \Illuminate\Support\Facades\URL::signedRoute('receipt.signed', ['transactionId' => $transaction['id']]) : null);
    $deleteUrl = !empty($transaction['id']) ? route('learners.transactions.destroy', $transaction['id']) : ($transaction['delete_url'] ?? null);
@endphp

<div class="transaction-history-card" {!! $cardAttrs ?? '' !!}>
    {{-- Header Row --}}
    <button class="transaction-history-main" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $isCardOpen ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
        <div class="trxn-type-icon-box {{ $hasRefund ? 'is-refund' : '' }}">
            @if($hasRefund)
                {{-- Outgoing / Refund Arrow (Up-Right ↗) --}}
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="7" y1="17" x2="17" y2="7"></line>
                    <polyline points="7 7 17 7 17 17"></polyline>
                </svg>
            @else
                {{-- Incoming / Payment Arrow (Down-Left ↙) --}}
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="17" y1="7" x2="7" y2="17"></line>
                    <polyline points="17 17 7 17 7 7"></polyline>
                </svg>
            @endif
        </div>
        <div class="transaction-history-title">
            <h6>{{ $transactionType }}</h6>
            <small>{{ $dateFmt($cardDate) }}, Received By {{ $transaction['added_by_name'] ?? 'Admin' }}</small>
        </div>
        <div class="transaction-history-header-right">
            @if(!empty($paymentMode))
                <span class="trxn-mode-pill">
                    <span class="mode-dot"></span> {{ $paymentMode }}
                </span>
            @endif
            <span class="trxn-header-amount">₹{{ $fmt($transaction['total_paid_amount'] ?? 0) }}</span>
            <span class="transaction-chevron-circle">
                <i class="fa-solid fa-chevron-up"></i>
            </span>
        </div>
    </button>

    {{-- Collapsible Details Body --}}
    <div class="collapse {{ $isCardOpen ? 'show' : '' }}" id="{{ $collapseId }}">
        <div class="transaction-history-body">
            
            {{-- Section 1: 4 Column Pricing Breakdown Bar --}}
            <div class="trxn-breakdown-bar">
                <div class="trxn-bar-col">
                    <div class="trxn-bar-icon-box icon-blue">
                        <i class="fa-solid fa-tag"></i>
                    </div>
                    <div class="trxn-bar-info">
                        <span class="trxn-bar-label">Plan Price</span>
                        <strong class="trxn-bar-val">₹{{ $fmt($transaction['plan_price'] ?? 0) }}</strong>
                    </div>
                </div>

                <div class="trxn-bar-divider"></div>

                <div class="trxn-bar-col">
                    <div class="trxn-bar-icon-box icon-indigo">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <div class="trxn-bar-info">
                        <span class="trxn-bar-label">Locker</span>
                        <strong class="trxn-bar-val">₹{{ $fmt($transaction['locker_amount'] ?? 0) }}</strong>
                    </div>
                </div>

                <div class="trxn-bar-divider"></div>

                <div class="trxn-bar-col">
                    <div class="trxn-bar-icon-box icon-orange">
                        <i class="fa-solid fa-percent"></i>
                    </div>
                    <div class="trxn-bar-info">
                        <span class="trxn-bar-label">Discount</span>
                        <strong class="trxn-bar-val">₹{{ $fmt($transaction['discount_amount'] ?? 0) }}</strong>
                    </div>
                </div>

                <div class="trxn-bar-divider"></div>

                <div class="trxn-bar-col">
                    <div class="trxn-bar-icon-box icon-green">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="trxn-bar-info">
                        <span class="trxn-bar-label">Total Amount</span>
                        <strong class="trxn-bar-val">₹{{ $fmt($transaction['total_amount'] ?? 0) }}</strong>
                    </div>
                </div>
            </div>

            {{-- Section 2: Transaction Receipt Box (Dashed Border) --}}
            @if($relatedActivities->isNotEmpty())
                @foreach($relatedActivities as $activity)
                    <div class="trxn-receipt-dashed-box">
                        <div class="trxn-receipt-left">
                            <div class="trxn-receipt-icon-box">
                                <i class="fa-solid fa-file-invoice"></i>
                            </div>
                            <div class="trxn-receipt-info">
                                <span class="trxn-receipt-label">Transaction ID</span>
                                <strong class="trxn-receipt-id">#{{ $activity['transaction_id'] ?: ($transaction['transaction_ref'] ?? $activity['id']) }}</strong>
                                <p class="trxn-receipt-narration">{{ $activity['trxn_message'] ?: ($typeLabel($activity['payment_type'] ?? '') . ' (' . ($activity['payment_mode'] ?? 'Online') . ')') }}</p>
                            </div>
                        </div>

                        <div class="trxn-receipt-divider"></div>

                        <div class="trxn-receipt-right">
                            <span class="trxn-receipt-amount {{ strtolower((string)($activity['dr_cr'] ?? '')) === 'dr' ? 'debit' : '' }}">
                                ₹{{ $fmt($activity['paid_amount'] ?? 0) }}
                            </span>
                            <div class="trxn-receipt-meta">
                                <span>Modified on : {{ $dateFmt($activity['updated_date'] ?: ($activity['transaction_date'] ?? $cardDate)) }}</span>
                                <span>By {{ $activity['updated_by_name'] ?: ($activity['added_by_name'] ?? 'Admin') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="trxn-receipt-dashed-box">
                    <div class="trxn-receipt-left">
                        <div class="trxn-receipt-icon-box">
                            <i class="fa-solid fa-file-invoice"></i>
                        </div>
                        <div class="trxn-receipt-info">
                            <span class="trxn-receipt-label">Transaction ID</span>
                            <strong class="trxn-receipt-id">#{{ $transaction['transaction_ref'] ?? $transaction['id'] }}</strong>
                            <p class="trxn-receipt-narration">Collected {{ $transaction['transaction_type'] ?? 'Seat Booking' }} Payment ({{ $transaction['payment_mode'] ?? 'Online' }})</p>
                        </div>
                    </div>

                    <div class="trxn-receipt-divider"></div>

                    <div class="trxn-receipt-right">
                        <span class="trxn-receipt-amount">
                            ₹{{ $fmt($transaction['total_paid_amount'] ?? 0) }}
                        </span>
                        <div class="trxn-receipt-meta">
                            <span>Modified on : {{ $dateFmt($transaction['updated_date'] ?: ($transaction['transaction_date'] ?? $cardDate)) }}</span>
                            <span>By {{ $transaction['updated_by_name'] ?: ($transaction['added_by_name'] ?? 'Admin') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Section 3: 3 Column Payable/Paid/Pending Overview Bar --}}
            <div class="trxn-breakdown-bar trxn-overview-bar">
                <div class="trxn-bar-col">
                    <div class="trxn-bar-icon-box icon-blue">
                        <i class="fa-solid fa-calculator"></i>
                    </div>
                    <div class="trxn-bar-info">
                        <span class="trxn-bar-label">Final Payable Amount</span>
                        <strong class="trxn-bar-val">₹{{ $fmt($transaction['final_payable_amount'] ?? 0) }}</strong>
                    </div>
                </div>

                <div class="trxn-bar-divider"></div>

                <div class="trxn-bar-col">
                    <div class="trxn-bar-icon-box icon-green">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="trxn-bar-info">
                        <span class="trxn-bar-label">Paid Amount</span>
                        <strong class="trxn-bar-val text-success">₹{{ $fmt($transaction['total_paid_amount'] ?? 0) }}</strong>
                    </div>
                </div>

                <div class="trxn-bar-divider"></div>

                <div class="trxn-bar-col">
                    <div class="trxn-bar-icon-box icon-amber">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div class="trxn-bar-info">
                        <span class="trxn-bar-label">Pending / Extra Amount</span>
                        <strong class="trxn-bar-val {{ $isPending ? 'text-danger' : '' }}">
                            ₹{{ $isPending ? $fmt($transaction['pending_amount'] ?? 0) : $fmt($transaction['extra_amount'] ?? 0) }}
                        </strong>
                    </div>
                </div>
            </div>

            {{-- Section 4: Action Buttons (Bottom Bar) --}}
            <div class="trxn-card-actions-row">
                @if($receiptUrl)
                    <a href="{{ $receiptUrl }}" target="_blank" class="trxn-btn trxn-btn-receipt" title="Download Receipt">
                        <i class="fa-solid fa-download"></i>
                        <span>Download Receipt</span>
                    </a>
                @else
                    <span class="trxn-btn trxn-btn-receipt disabled opacity-50" title="Receipt Unavailable">
                        <i class="fa-solid fa-download"></i>
                        <span>Download Receipt</span>
                    </span>
                @endif

                @if(!empty($transaction['id']))
                    <a href="{{ route('learners.transactions.edit', $transaction['id']) }}" class="trxn-btn trxn-btn-edit" title="Edit Transaction">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>Edit Transaction</span>
                    </a>
                @endif

                @if($deleteUrl)
                    <form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm('Are you sure you want to delete this transaction?');" class="d-inline m-0">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="learner_id" value="{{ $learner->id }}">
                        <button type="submit" class="trxn-btn trxn-btn-delete" title="Delete Transaction">
                            <i class="fa-solid fa-trash-can"></i>
                            <span>Delete</span>
                        </button>
                    </form>
                @endif
            </div>

        </div>
    </div>
</div>
