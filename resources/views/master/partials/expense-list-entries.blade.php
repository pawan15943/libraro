<div class="records-wrapper">
    <!-- Records Controls Bar: Count badge & Live Instant Search -->
    <div class="records-controls-bar">
        <div class="records-count-info">
            <span>Expense Records:</span>
            <span class="records-count-badge" id="visibleCountBadge">{{ $expences->total() }}</span>
            <span class="text-muted small">entries (Page {{ $expences->currentPage() }} of {{ $expences->lastPage() }})</span>
        </div>
        <div class="records-search-box">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="cardSearchInput" placeholder="Search expense, ref, date, mode, amount..." autocomplete="off" />
            <button type="button" class="btn-clear-search d-none" id="clearSearchBtn">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- Desktop Column Header (Visible on Desktop >= 992px) -->
    <div class="records-header-row">
        <div class="text-center">S.No.</div>
        <div>Expense / Particulars</div>
        <div class="text-center">Payment Mode</div>
        <div class="text-center">Paid Date</div>
        <div class="text-center">Amount (₹)</div>
        <div class="text-center">Action</div>
    </div>

    <!-- Records Grid Container (Mobile-First Cards) -->
    <div class="collection-records-grid" id="expenseCardsContainer">
        @forelse($expences as $key => $exp)
            @php
                $sno = ($expences->currentPage() - 1) * $expences->perPage() + $loop->iteration;
                $mode = strtoupper($exp->payment_mode ?? '');
                $modeType = 'default';
                $modeLabel = $exp->payment_mode ?? 'Other';
                $modeIcon = 'fa-solid fa-credit-card';

                if ($mode === 'ONLINE' || $mode === '1') {
                    $modeType = 'online';
                    $modeLabel = 'Online';
                    $modeIcon = 'fa-solid fa-globe';
                } elseif ($mode === 'OFFLINE' || $mode === '2') {
                    $modeType = 'offline';
                    $modeLabel = 'Offline';
                    $modeIcon = 'fa-solid fa-money-bill-wave';
                } elseif ($mode === 'PAYLATER' || $mode === '3') {
                    $modeType = 'paylater';
                    $modeLabel = 'Pay Later';
                    $modeIcon = 'fa-regular fa-clock';
                }

                $formattedDate = \Carbon\Carbon::parse($exp->date)->format('d M Y');
                $searchContent = strtolower(($exp->particular ?? '') . ' ' . ($exp->transaction_id ?? '') . ' ' . $modeLabel . ' ' . $formattedDate . ' ' . $exp->amount);
            @endphp

            <div class="collection-record-card"
                 data-search="{{ $searchContent }}"
                 data-mode="{{ $modeType }}"
                 data-particular="{{ $exp->particular ?? '' }}"
                 data-ref="{{ $exp->transaction_id ?? '' }}"
                 data-amount="{{ $exp->amount }}"
                 data-date="{{ $formattedDate }}"
                 data-sno="{{ $sno }}">

                <!-- Mobile-Only Top Strip (< 992px) -->
                <div class="d-flex d-lg-none align-items-center justify-content-between flex-wrap gap-2 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="sno-pill">#{{ $sno }}</span>
                        <span class="mode-pill-badge mode-badge-{{ $modeType }}">
                            <i class="{{ $modeIcon }} me-1"></i>{{ $modeLabel }}
                        </span>
                    </div>
                    <div class="record-date-badge">
                        <i class="fa-regular fa-calendar-days me-1"></i>{{ $formattedDate }}
                    </div>
                </div>

                <!-- Desktop Col 1: S.No -->
                <div class="d-none d-lg-flex align-items-center justify-content-center">
                    <span class="sno-pill">{{ $sno }}</span>
                </div>

                <!-- Col 2: Particulars & Category -->
                <div class="record-col-particulars">
                    <div class="expense-avatar-icon">
                        <i class="fa-solid fa-arrow-trend-down"></i>
                    </div>
                    <div class="expense-particular-details">
                        <div class="expense-name-text">{{ $exp->particular ?? 'Expense Item' }}</div>
                        @if(!empty($exp->transaction_id))
                            <span class="badge-ref"><i class="fa-solid fa-hashtag me-1"></i>{{ $exp->transaction_id }}</span>
                        @endif
                    </div>
                </div>

                <!-- Col 3: Payment Mode (Desktop) -->
                <div class="d-none d-lg-flex align-items-center justify-content-center record-col-mode">
                    <span class="mode-pill-badge mode-badge-{{ $modeType }}">
                        <i class="{{ $modeIcon }} me-1"></i>{{ $modeLabel }}
                    </span>
                </div>

                <!-- Col 4: Paid Date (Desktop) -->
                <div class="d-none d-lg-flex align-items-center justify-content-center record-col-date">
                    <span class="expense-date-text">
                        <i class="fa-regular fa-calendar-days me-1 text-muted"></i>{{ $formattedDate }}
                    </span>
                </div>

                <!-- Col 5: Amount (Both Mobile & Desktop) -->
                <div class="record-col-amount">
                    <span class="d-lg-none text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Outflow:</span>
                    <span class="expense-amount-badge">
                        ₹{{ number_format($exp->amount, 2) }}
                    </span>
                </div>

                <!-- Col 6: Actions -->
                <div class="record-col-actions">
                    <button type="button" 
                            class="action-btn action-btn-delete" 
                            onclick="confirmDelete({{ $exp->id }})" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top"
                            title="Delete Expense">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

            </div>
        @empty
            <div class="report-empty-state">
                <i class="fa-solid fa-folder-open empty-state-icon"></i>
                <h6 class="empty-state-title">No Expense Records Found</h6>
                <p class="small text-muted mb-0">No expenses matched your search or selected filter options.</p>
            </div>
        @endforelse
    </div>

    <!-- Empty Search State (for client-side instant search) -->
    <div class="report-empty-state d-none" id="searchEmptyState">
        <i class="fa-solid fa-magnifying-glass empty-state-icon"></i>
        <h6 class="empty-state-title">No Matching Records Found</h6>
        <p class="small text-muted mb-0">No expense records on this page matched your search criteria.</p>
    </div>

    <!-- Pagination Wrapper -->
    @if ($expences->lastPage() > 1)
        <div class="records-pagination-wrapper" id="paginationWrapper">
            <div class="pagination-info">
                Showing {{ $expences->firstItem() ?? 0 }} to {{ $expences->lastItem() ?? 0 }} of {{ $expences->total() }} records
            </div>
            <nav class="pagination-nav">
                <ul class="expense-pagination pagination mb-0">
                    <!-- Prev Button -->
                    <li class="page-item {{ $expences->onFirstPage() ? 'disabled' : '' }}">
                        <a href="{{ $expences->onFirstPage() ? '#' : $expences->previousPageUrl() }}" 
                           class="expense-page-link page-link" aria-label="Previous">
                           <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    </li>

                    @if ($expences->currentPage() > 3)
                        <li class="page-item"><a href="{{ $expences->url(1) }}" class="expense-page-link page-link">1</a></li>
                        <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                    @endif

                    @for ($i = max(1, $expences->currentPage() - 2); $i <= min($expences->lastPage(), $expences->currentPage() + 2); $i++)
                        <li class="page-item {{ $expences->currentPage() == $i ? 'active' : '' }}">
                            <a href="{{ $expences->url($i) }}" class="expense-page-link page-link">
                                {{ $i }}
                            </a>
                        </li>
                    @endfor

                    @if ($expences->currentPage() < $expences->lastPage() - 2)
                        <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                        <li class="page-item"><a href="{{ $expences->url($expences->lastPage()) }}" class="expense-page-link page-link">{{ $expences->lastPage() }}</a></li>
                    @endif

                    <!-- Next Button -->
                    <li class="page-item {{ !$expences->hasMorePages() ? 'disabled' : '' }}">
                        <a href="{{ $expences->hasMorePages() ? $expences->nextPageUrl() : '#' }}" 
                           class="expense-page-link page-link" aria-label="Next">
                           <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    @endif
</div>
