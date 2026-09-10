<div class="expense-table-card mb-4">
    <div class="table-card-header">
        <h4 class="table-card-title">
            <i class="fa-solid fa-list-check"></i> Expense History
        </h4>
        <span class="table-card-meta">
            Showing <b>{{ $expences->firstItem() ?? 0 }} - {{ $expences->lastItem() ?? 0 }}</b> of <b>{{ $expences->total() }}</b> records
        </span>
    </div>

    <div class="table-responsive">
        <table class="table expense-custom-table text-center" id="datatable">
            <thead>
                <tr>
                    <th style="width: 70px;">S.No.</th>
                    <th style="text-align: left; padding-left: 20px;">Expense / Particular</th>
                    <th>Amount</th>
                    <th>Payment Mode</th>
                    <th>Paid On</th>
                    <th style="width: 100px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expences as $key => $exp)
                @php
                    $mode = strtoupper($exp->payment_mode ?? '');
                @endphp
                <tr>
                    {{-- S.No --}}
                    <td data-label="S.No.">
                        <span class="sno-pill">{{ ($expences->currentPage() - 1) * $expences->perPage() + $loop->iteration }}</span>
                    </td>

                    {{-- Particulars --}}
                    <td data-label="Expense / Particular" style="text-align: left; padding-left: 20px;">
                        <div class="expense-particular-wrap">
                            <div class="expense-avatar-icon">
                                <i class="fa-solid fa-arrow-trend-down"></i>
                            </div>
                            <div>
                                <p class="expense-name-text">{{ $exp->particular }}</p>
                                @if(!empty($exp->transaction_id))
                                    <small class="text-muted font-11">Ref: {{ $exp->transaction_id }}</small>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Amount --}}
                    <td data-label="Amount">
                        <span class="expense-amount-badge">
                            ₹{{ number_format($exp->amount, 2) }}
                        </span>
                    </td>

                    {{-- Payment Mode --}}
                    <td data-label="Payment Mode">
                        @if($mode === 'ONLINE' || $mode === '1')
                            <span class="mode-pill-badge mode-badge-online">
                                <i class="fa-solid fa-globe"></i> Online
                            </span>
                        @elseif($mode === 'OFFLINE' || $mode === '2')
                            <span class="mode-pill-badge mode-badge-offline">
                                <i class="fa-solid fa-money-bill-wave"></i> Offline
                            </span>
                        @elseif($mode === 'PAYLATER' || $mode === '3')
                            <span class="mode-pill-badge mode-badge-paylater">
                                <i class="fa-regular fa-clock"></i> Pay Later
                            </span>
                        @else
                            <span class="mode-pill-badge mode-badge-default">
                                {{ $exp->payment_mode }}
                            </span>
                        @endif
                    </td>

                    {{-- Paid On --}}
                    <td data-label="Paid On">
                        <span class="expense-date-text">
                            <i class="fa-regular fa-calendar-days"></i> {{ \Carbon\Carbon::parse($exp->date)->format('d M, Y') }}
                        </span>
                    </td>

                    {{-- Action --}}
                    <td data-label="Action">
                        <ul class="action-btn-group">
                            <li>
                                <button type="button" 
                                    class="action-btn action-btn-delete" 
                                    onclick="confirmDelete({{ $exp->id }})" 
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top"
                                    title="Delete Expense">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </li>
                        </ul>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-5 text-center text-muted">
                        <i class="fa-solid fa-folder-open fa-2x mb-2 d-block text-secondary"></i>
                        <span>No expense records found matching the criteria.</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($expences->lastPage() > 1)
    <ul class="expense-pagination">
        {{-- Prev --}}
        <li>
            <a href="{{ $expences->onFirstPage() ? '#' : $expences->previousPageUrl() }}" 
               class="expense-page-link {{ $expences->onFirstPage() ? 'disabled' : '' }}"
               aria-label="Previous">
               <i class="fa-solid fa-chevron-left"></i>
            </a>
        </li>

        @if ($expences->currentPage() > 3)
            <li><a href="{{ $expences->url(1) }}" class="expense-page-link">1</a></li>
            <li><span>...</span></li>
        @endif

        @for ($i = max(1, $expences->currentPage() - 2); $i <= min($expences->lastPage(), $expences->currentPage() + 2); $i++)
            <li>
                <a href="{{ $expences->url($i) }}" class="expense-page-link {{ $expences->currentPage() == $i ? 'active' : '' }}">
                    {{ $i }}
                </a>
            </li>
        @endfor

        @if ($expences->currentPage() < $expences->lastPage() - 2)
            <li><span>...</span></li>
            <li><a href="{{ $expences->url($expences->lastPage()) }}" class="expense-page-link">{{ $expences->lastPage() }}</a></li>
        @endif

        {{-- Next --}}
        <li>
            <a href="{{ $expences->hasMorePages() ? $expences->nextPageUrl() : '#' }}" 
               class="expense-page-link {{ !$expences->hasMorePages() ? 'disabled' : '' }}"
               aria-label="Next">
               <i class="fa-solid fa-chevron-right"></i>
            </a>
        </li>
    </ul>
    @endif
</div>
