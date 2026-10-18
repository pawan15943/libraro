@php
    use Carbon\Carbon;
    $allFlatRows = isset($groupedTransactions) ? $groupedTransactions->flatten() : collect();
    $inflowCount = $inflowCount ?? $allFlatRows->where('dr_cr', 'Cr')->count();
    $expenseCount = $expenseCount ?? $allFlatRows->where('dr_cr', 'Dr')->where('payment_type', 'EXPENSE')->count();
    $refundCount = $refundCount ?? $allFlatRows->where('dr_cr', 'Dr')->where('payment_type', 'REFUND')->count();
    $cashCollection = $cashCollection ?? $allFlatRows->where('dr_cr', 'Cr')->filter(function ($r) {
        $m = strtolower(trim((string)($r->payment_mode ?? '')));
        return str_contains($m, 'cash') || str_contains($m, 'offline');
    })->sum('amount');
    $onlineCollection = $onlineCollection ?? $allFlatRows->where('dr_cr', 'Cr')->filter(function ($r) {
        $m = strtolower(trim((string)($r->payment_mode ?? '')));
        return !str_contains($m, 'cash') && !str_contains($m, 'offline');
    })->sum('amount');
@endphp

{{-- Clean, High-Density Executive Financial Passbook Table --}}
<div class="monthly-ledger-table-wrapper">
    <div class="table-responsive">
        <table class="table text-center align-middle monthly-ledger-table mb-0" id="monthlyLedgerTable">
            <thead>
                <tr>
                    <th class="th-date">Payment Date</th>
                    <th class="th-seat">Seat No</th>
                    <th class="th-name text-start">Name</th>
                    <th class="th-amount text-end">Amount</th>
                    <th class="th-particulars text-start">Transaction</th>
                    <th class="th-flow">Payment / Expense</th>
                    <th class="th-mode">Payment Mode</th>
                    <th class="th-balance text-end">Balance</th>
                </tr>
            </thead>

            @forelse($groupedTransactions as $date => $rows)
                @php
                    $dayCr = $rows->where('dr_cr', 'Cr')->sum('amount');
                    $dayDr = $rows->where('dr_cr', 'Dr')->sum('amount');
                    $dayBalance = $dayCr - $dayDr;
                    $dateObj = Carbon::parse($date);
                    $rowCount = count($rows);
                @endphp
                <tbody class="date-group-tbody" data-day-group="{{ $date }}" data-row-count="{{ $rowCount }}">
                    @foreach($rows as $index => $row)
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
                                $modeLabel = 'Online';
                                $modeCategory = 'online';
                            } elseif (str_contains($rawMode, 'upi') || str_contains($rawMode, 'gpay') || str_contains($rawMode, 'phonepe') || str_contains($rawMode, 'paytm')) {
                                $modeLabel = 'UPI';
                                $modeCategory = 'online';
                            } elseif (str_contains($rawMode, 'cash') || str_contains($rawMode, 'offline')) {
                                $modeLabel = 'Cash';
                                $modeCategory = 'cash';
                            } else {
                                $modeLabel = !empty($row->payment_mode) ? ucfirst($row->payment_mode) : 'Offline';
                                $modeCategory = 'offline';
                            }

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

                        <tr class="ledger-table-row"
                            data-search="{{ $searchKeywords }}"
                            data-flow="{{ $isCredit ? 'credit' : 'debit' }}"
                            data-mode-cat="{{ $modeCategory }}"
                            data-tab-cat="{{ $tabCategory }}"
                            data-day-ref="{{ $date }}">

                            {{-- Col 1: Payment Date --}}
                            @if($index === 0)
                                <td rowspan="{{ $rowCount }}" class="td-date-span text-center">
                                    <div class="fw-semibold text-navy">{{ $dateObj->format('d/m/Y') }}</div>
                                    <div class="text-muted small">({{ $dateObj->format('l') }})</div>
                                </td>
                            @endif

                            {{-- Col 2: Seat No --}}
                            <td class="td-seat text-center">
                                @if($isExpense)
                                    <span class="text-danger fw-semibold">EXPENSE</span>
                                @elseif($isRefund)
                                    <span class="text-warning fw-semibold">REFUND</span>
                                @elseif(!empty($seatDisplay) && $seatDisplay !== 'General')
                                    <span class="fw-semibold text-teal">{{ strtoupper($seatDisplay) }}</span>
                                @else
                                    <span class="text-muted">GEN</span>
                                @endif
                            </td>

                            {{-- Col 3: Name --}}
                            <td class="td-name text-start">
                                @if(!$isExpense && !empty($row->learner_id) && Route::has('learners.edit'))
                                    <a href="{{ route('learners.edit', $row->learner_id) }}" class="ledger-name-link text-uppercase fw-semibold" title="View Profile">
                                        {{ strtoupper($displayName) }}
                                    </a>
                                @else
                                    <span class="text-uppercase fw-semibold text-dark">
                                        {{ strtoupper($displayName) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Col 4: Amount --}}
                            <td class="td-amount text-end fw-semibold {{ $isCredit ? 'text-success' : 'text-danger' }}">
                                {{ $isCredit ? '+' : '-' }}{{ number_format($amount, 0) }}
                            </td>

                            {{-- Col 5: Transaction --}}
                            <td class="td-particulars text-start text-uppercase">
                                {{ strtoupper($particularsText) }}
                            </td>

                            {{-- Col 6: Payment / Expense --}}
                            <td class="td-flow text-center">
                                <span class="fw-semibold {{ $isCredit ? 'text-success' : 'text-danger' }}">
                                    {{ $isCredit ? 'CREDIT' : 'DEBIT' }}
                                </span>
                            </td>

                            {{-- Col 7: Payment Mode --}}
                            <td class="td-mode text-center text-uppercase">
                                {{ strtoupper($modeLabel) }}
                            </td>

                            {{-- Col 8: Balance --}}
                            @if($index === 0)
                                <td rowspan="{{ $rowCount }}" class="td-balance-span text-end fw-semibold fs-6 {{ $dayBalance >= 0 ? 'text-dark' : 'text-danger' }}">
                                    {{ $dayBalance >= 0 ? '' : '-' }}{{ number_format(abs($dayBalance), 0) }}
                                </td>
                            @endif

                        </tr>
                    @endforeach
                </tbody>
            @empty
                <tbody>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-receipt fa-2x mb-2 d-block text-secondary"></i>
                            No records found
                        </td>
                    </tr>
                </tbody>
            @endforelse

            {{-- Summary Ledger Footer --}}
            @if(isset($totalCollection))
                <tfoot class="ledger-tfoot">
                    <tr class="summary-row-collection">
                        <td colspan="7" class="text-end fw-semibold">Total Collection</td>
                        <td class="text-end fw-semibold text-success">+{{ number_format($totalCollection, 0) }}</td>
                    </tr>
                    <tr class="summary-row-expense">
                        <td colspan="7" class="text-end fw-semibold">Total Expense</td>
                        <td class="text-end fw-semibold text-danger">-{{ number_format($totalExpense, 0) }}</td>
                    </tr>
                    <tr class="summary-row-refund">
                        <td colspan="7" class="text-end fw-semibold">Total Revenue (Refund)</td>
                        <td class="text-end fw-semibold text-danger">-{{ number_format($totalRevenue, 0) }}</td>
                    </tr>
                    <tr class="summary-row-grand">
                        <td colspan="7" class="text-end fw-semibold fs-6">Grand Total</td>
                        <td class="text-end fw-semibold fs-6 {{ ($grandTotal ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ ($grandTotal ?? 0) >= 0 ? '+' : '-' }}{{ number_format(abs($grandTotal ?? 0), 0) }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
