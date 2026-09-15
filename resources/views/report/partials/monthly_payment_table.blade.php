@php
    use Carbon\Carbon;
@endphp

<div class="collection-records-grid">
    @forelse($groupedTransactions as $date => $rows)
        @php
            $dayCr = $rows->where('dr_cr', 'Cr')->sum('amount');
            $dayDr = $rows->where('dr_cr', 'Dr')->sum('amount');
            $dayBalance = $dayCr - $dayDr;
            $dateObj = Carbon::parse($date);
        @endphp

        {{-- Date Group Divider Ribbon --}}
        <div class="date-group-divider" data-day-group="{{ $date }}">
            <div class="divider-date">
                <i class="fa-regular fa-calendar-days me-1 text-muted"></i>
                <span>{{ $dateObj->format('d M Y') }}</span>
                <span class="text-muted fw-normal ms-1">({{ $dateObj->format('l') }})</span>
                <span class="badge bg-light text-dark ms-2 border">{{ count($rows) }} {{ count($rows) == 1 ? 'record' : 'records' }}</span>
            </div>
            <div class="divider-summary">
                <span class="text-success" title="Day Inflow / Collections">
                    <i class="fa-solid fa-arrow-down-left"></i> +₹{{ number_format($dayCr, 0) }}
                </span>
                @if($dayDr > 0)
                    <span class="text-danger" title="Day Outflow / Expenses">
                        <i class="fa-solid fa-arrow-up-right"></i> -₹{{ number_format($dayDr, 0) }}
                    </span>
                @endif
                <span class="badge-day-net" title="Day Net Closing Balance">
                    Net: {{ $dayBalance >= 0 ? '+' : '-' }}₹{{ number_format(abs($dayBalance), 0) }}
                </span>
            </div>
        </div>

        {{-- Individual Transaction Cards for this Date --}}
        @foreach($rows as $row)
            @php
                $isExpense = ($row->payment_type == 'EXPENSE');
                $isRefund = ($row->payment_type == 'REFUND');
                $isCredit = ($row->dr_cr == 'Cr');
                $amount = (float) $row->amount;

                $seatDisplay = null;
                if (!$isExpense && !empty($row->seat_no)) {
                    $seatDisplay = function_exists('getSeatDisplayByMainNo') ? getSeatDisplayByMainNo($row->seat_no) : $row->seat_no;
                }

                $displayName = $isExpense ? ($row->particular ?: 'Operational Expense') : ($row->name ?: ($row->learner_id ? 'Learner #' . $row->learner_id : 'General'));
                $particularsText = $row->particular ?: ($isExpense ? 'Operational Expense' : ($isRefund ? 'Fee Refund' : 'Learner Fee'));

                // Normalized payment mode
                $rawMode = strtolower(trim((string)($row->payment_mode ?? '')));
                if (str_contains($rawMode, 'online') || str_contains($rawMode, 'netbanking') || str_contains($rawMode, 'card')) {
                    $modeClass = 'mode-online';
                    $modeIcon = 'fa-globe';
                    $modeLabel = 'Online';
                    $modeCategory = 'online';
                } elseif (str_contains($rawMode, 'upi') || str_contains($rawMode, 'gpay') || str_contains($rawMode, 'phonepe') || str_contains($rawMode, 'paytm')) {
                    $modeClass = 'mode-upi';
                    $modeIcon = 'fa-qrcode';
                    $modeLabel = 'UPI';
                    $modeCategory = 'online';
                } elseif (str_contains($rawMode, 'cash') || str_contains($rawMode, 'offline')) {
                    $modeClass = 'mode-cash';
                    $modeIcon = 'fa-money-bill-wave';
                    $modeLabel = 'Cash';
                    $modeCategory = 'cash';
                } else {
                    $modeClass = 'mode-offline';
                    $modeIcon = 'fa-receipt';
                    $modeLabel = !empty($row->payment_mode) ? ucfirst($row->payment_mode) : 'Offline';
                    $modeCategory = 'offline';
                }

                // Operator & Time
                $doneBy = $row->created_by ? (DB::table('library_users')->where('id', $row->created_by)->value('name') ?? $row->created_by) : 'Admin';
                $timeStr = $row->created_at ? Carbon::parse($row->created_at)->format('h:i A') : '';

                // Search keywords (NO mobile number)
                $searchKeywords = strtolower(
                    $displayName . ' ' .
                    ($seatDisplay ? 'seat ' . $seatDisplay : '') . ' ' .
                    $particularsText . ' ' .
                    $modeLabel . ' ' .
                    $row->dr_cr . ' ' .
                    $date . ' ' .
                    ($row->transaction_id ?? '')
                );

                $tabCategory = $isExpense ? 'expense' : ($isRefund ? 'refund' : ($isCredit ? 'credit' : 'debit'));
            @endphp

            <div class="collection-record-card"
                 data-search="{{ $searchKeywords }}"
                 data-flow="{{ $isCredit ? 'credit' : 'debit' }}"
                 data-mode-cat="{{ $modeCategory }}"
                 data-tab-cat="{{ $tabCategory }}"
                 data-day-ref="{{ $date }}">

                {{-- Mobile-Only Top Bar (< 992px) --}}
                <div class="mobile-top-bar d-flex d-lg-none">
                    <span class="record-date-text">
                        <i class="fa-regular fa-calendar me-1"></i>{{ $dateObj->format('d M Y') }}
                    </span>
                    <span class="badge-payment-mode {{ $modeClass }}">
                        <i class="fa-solid {{ $modeIcon }}"></i> {{ $modeLabel }}
                    </span>
                </div>

                {{-- Col 1: Date & Time (Desktop) --}}
                <div class="col-date d-none d-lg-flex">
                    <span class="record-date-text">{{ $dateObj->format('d M Y') }}</span>
                    <span class="record-time-tag">
                        <i class="fa-regular fa-clock me-1"></i>{{ $timeStr ?: $dateObj->format('D') }}
                    </span>
                </div>

                {{-- Col 2: Learner & Seat (GEMINI.md rule: Seat Tag directly above student name) --}}
                <div class="col-learner">
                    {{-- Seat Tag directly above student name --}}
                    @if($isExpense)
                        <div class="record-seat-tag seat-expense">
                            <i class="fa-solid fa-receipt me-1"></i>EXPENSE
                        </div>
                    @elseif($isRefund)
                        <div class="record-seat-tag seat-refund">
                            <i class="fa-solid fa-rotate-left me-1"></i>REFUND
                        </div>
                    @elseif(!empty($seatDisplay) && $seatDisplay !== 'General')
                        <div class="record-seat-tag">
                            <i class="fa-solid fa-chair me-1"></i>SEAT {{ strtoupper($seatDisplay) }}
                        </div>
                    @else
                        <div class="record-seat-tag seat-general">
                            <i class="fa-solid fa-chair me-1"></i>GENERAL
                        </div>
                    @endif

                    @if(!$isExpense && !empty($row->learner_id) && Route::has('learners.edit'))
                        <a href="{{ route('learners.edit', $row->learner_id) }}" class="record-learner-name" title="View Learner Profile">
                            {{ $displayName }}
                        </a>
                    @else
                        <span class="record-learner-name text-dark">
                            {{ $displayName }}
                        </span>
                    @endif

                    <span class="record-staff-tag">
                        <i class="fa-regular fa-user me-1"></i>{{ $doneBy }}
                    </span>
                </div>

                {{-- Col 3: Particulars Description (NO Payment Type pill) --}}
                <div class="col-particulars">
                    {{ $particularsText }}
                </div>

                {{-- Col 4: Flow (Credit / Debit) --}}
                <div class="col-flow d-none d-lg-flex">
                    @if($isCredit)
                        <span class="flow-pill flow-credit" title="Inflow (Credit)">
                            <i class="fa-solid fa-arrow-down-left"></i> Credit
                        </span>
                    @else
                        <span class="flow-pill flow-debit" title="Outflow (Debit)">
                            <i class="fa-solid fa-arrow-up-right"></i> Debit
                        </span>
                    @endif
                </div>

                {{-- Col 5: Amount --}}
                <div class="col-amount d-none d-lg-flex">
                    <div class="fin-amount {{ $isCredit ? 'text-success' : 'text-danger' }}">
                        {{ $isCredit ? '+' : '-' }}₹{{ number_format($amount, 0) }}
                    </div>
                </div>

                {{-- Col 6: Payment Mode --}}
                <div class="col-mode d-none d-lg-flex">
                    <span class="badge-payment-mode {{ $modeClass }}" title="Payment Mode: {{ $modeLabel }}">
                        <i class="fa-solid {{ $modeIcon }}"></i> {{ $modeLabel }}
                    </span>
                </div>

                {{-- Mobile-Only Bottom Row (< 992px) --}}
                <div class="mobile-bottom-row d-flex d-lg-none">
                    @if($isCredit)
                        <span class="flow-pill flow-credit">
                            <i class="fa-solid fa-arrow-down-left"></i> Credit
                        </span>
                    @else
                        <span class="flow-pill flow-debit">
                            <i class="fa-solid fa-arrow-up-right"></i> Debit
                        </span>
                    @endif

                    <div class="fin-amount {{ $isCredit ? 'text-success' : 'text-danger' }}">
                        {{ $isCredit ? '+' : '-' }}₹{{ number_format($amount, 0) }}
                    </div>
                </div>

            </div>
        @endforeach
    @empty
        <div class="report-empty-state">
            <div class="empty-state-icon"><i class="fa-solid fa-receipt"></i></div>
            <h6 class="empty-state-title">No Transactions Recorded</h6>
            <p class="empty-state-desc">No collections, expenses, or refunds were found for this selected period.</p>
        </div>
    @endforelse
</div>

{{-- Monthly Closing Summary Box at bottom --}}
@if(isset($totalCollection))
    <div class="monthly-closing-summary-card">
        <div class="closing-summary-title">
            <i class="fa-solid fa-calculator me-2"></i> Monthly Financial Closing Summary
        </div>
        <div class="closing-summary-grid">
            <div class="closing-item">
                <span class="closing-label">Total Collections (Inflow)</span>
                <span class="closing-value text-success">+₹ {{ number_format($totalCollection, 0) }}</span>
            </div>
            <div class="closing-item">
                <span class="closing-label">Total Expenses (Outflow)</span>
                <span class="closing-value text-danger">-₹ {{ number_format($totalExpense, 0) }}</span>
            </div>
            <div class="closing-item">
                <span class="closing-label">Total Refunds</span>
                <span class="closing-value text-warning">-₹ {{ number_format($totalRevenue, 0) }}</span>
            </div>
            <div class="closing-item">
                <span class="closing-label">Net Closing Revenue</span>
                <span class="closing-value {{ $grandTotal >= 0 ? 'text-navy' : 'text-danger' }}">
                    {{ $grandTotal >= 0 ? '+' : '-' }}₹ {{ number_format(abs($grandTotal), 0) }}
                </span>
            </div>
        </div>
    </div>
@endif
