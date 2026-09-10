<div class="pp-table-card">
    <div class="table-responsive">
        <table class="table align-middle pp-custom-table mb-0">
            <thead>
                <tr>
                    <th style="width: 10%;" class="text-center">Seat No</th>
                    <th style="width: 25%;">Learner</th>
                    <th style="width: 18%;">Plan &amp; Shift</th>
                    <th style="width: 12%;">Plan End</th>
                    <th style="width: 10%;" class="text-center">Status</th>
                    <th style="width: 12%;">Pending Amt</th>
                    <th style="width: 11%;">Due Date</th>
                    <th style="width: 12%;" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($learners as $learner)
                @php
                    $payStatus = strtolower($learner['payment']['status'] ?? 'pending');
                    $statusBadgeClass = 'bg-warning text-dark';
                    $statusLabel = 'Pending';
                    if ($payStatus === 'overdue') {
                        $statusBadgeClass = 'bg-danger text-white';
                        $statusLabel = 'Overdue';
                    } elseif ($payStatus === 'paid' || $payStatus === 'received') {
                        $statusBadgeClass = 'bg-success text-white';
                        $statusLabel = 'Received';
                    } elseif ($payStatus === 'adjusted') {
                        $statusBadgeClass = 'bg-primary text-white';
                        $statusLabel = 'Adjusted';
                    } elseif ($payStatus === 'expired') {
                        $statusBadgeClass = 'bg-secondary text-white';
                        $statusLabel = 'Expired';
                    }
                    
                    $pendingAmt = (float) ($learner['payment']['pending_amount'] ?? 0);
                    $formattedPendingAmt = rtrim(rtrim(number_format($pendingAmt, 2, '.', ''), '0'), '.');
                    $seatDisplay = !empty($learner['seat_no']) ? $learner['seat_no'] : 'GEN';
                @endphp
                <tr class="pp-table-row">
                    <!-- Seat No -->
                    <td class="text-center">
                        <span class="badge rounded-pill pp-seat-badge">
                            {{ $seatDisplay }}
                        </span>
                    </td>

                    <!-- Learner -->
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="pp-avatar-circle">
                                {{ strtoupper(substr($learner['name'] ?? 'U', 0, 1)) }}
                            </div>
                            <div class="overflow-hidden">
                                <a href="{{ route('learners.show', $learner['id']) }}" class="pp-learner-name text-truncate d-block text-decoration-none">
                                    {{ $learner['name'] }}
                                </a>
                                <div class="pp-learner-meta small text-muted font-outfit" style="font-size: 0.78rem;">
                                    <span>UID: {{ $learner['learner_no'] }}</span>
                                    @if(!empty($learner['mobile']))
                                        <span class="mx-1">•</span>
                                        <span><i class="fa-solid fa-phone me-1" style="font-size: 0.7rem;"></i>{{ $learner['mobile'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Plan & Shift -->
                    <td>
                        <div class="text-dark font-outfit" style="font-size: 0.86rem; font-weight: 500;">{{ $learner['plan'] ?? '—' }}</div>
                        <div class="small text-muted font-outfit" style="font-size: 0.78rem;">{{ $learner['plan_type'] ?? '' }}</div>
                    </td>

                    <!-- Plan End Date -->
                    <td>
                        @if(!empty($learner['plan_end_date']))
                            <div class="d-flex align-items-center gap-1 font-outfit" style="font-size: 0.84rem; color: #475569;">
                                <i class="fa-regular fa-calendar" style="color: #64748b; font-size: 0.78rem;"></i>
                                <span>{{ date('j M Y', strtotime($learner['plan_end_date'])) }}</span>
                            </div>
                        @else
                            <span class="text-muted small font-outfit">—</span>
                        @endif
                    </td>

                    <!-- Status -->
                    <td class="text-center">
                        <span class="badge rounded-pill px-2.5 py-1 font-outfit {{ $statusBadgeClass }}" style="font-size: 0.74rem; font-weight: 500; letter-spacing: 0.2px;">
                            {{ $statusLabel }}
                        </span>
                    </td>

                    <!-- Pending Amount -->
                    <td>
                        <div class="pp-amount-val font-outfit" style="font-size: 0.92rem; font-weight: 600; color: #dc2626;">
                            ₹ {{ $formattedPendingAmt }}
                        </div>
                    </td>

                    <!-- Due Date -->
                    <td>
                        @if(!empty($learner['payment']['due_date']))
                            <div class="d-flex align-items-center gap-1 font-outfit" style="font-size: 0.84rem; color: #475569;">
                                <i class="fa-regular fa-clock" style="color: #64748b; font-size: 0.78rem;"></i>
                                <span>{{ date('j M Y', strtotime($learner['payment']['due_date'])) }}</span>
                            </div>
                        @else
                            <span class="text-muted small font-outfit">—</span>
                        @endif
                    </td>

                    <!-- Action -->
                    <td class="text-end">
                        @if(!empty($learner['transaction_id']) || $pendingAmt > 0)
                        <button type="button" 
                                data-id="{{ $learner['id'] }}" 
                                data-learnerdetail="{{ $learner['learner_detail_id'] ?? '' }}"
                                class="btn btn-sm btn-primary pp-pay-btn settlement-learner font-outfit shadow-sm">
                            <i class="fa-solid fa-credit-card me-1"></i> Pay Due
                        </button>
                        @else
                        <span class="text-muted small font-outfit">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="no-data-found py-3">
                            <div class="mb-2">
                                <i class="fa-solid fa-file-invoice-dollar" style="font-size: 3rem; color: #cbd5e1;"></i>
                            </div>
                            <h5 class="fw-bold text-dark font-outfit mb-1">No Pending Payment Records</h5>
                            <p class="text-muted small mb-0 font-outfit">No learners match the current filter or search criteria.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination Links --}}
@if($learners->lastPage() > 1)
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4 px-1">
    <div class="small text-muted font-outfit">
        Showing <b>{{ $learners->firstItem() ?? 0 }}</b> to <b>{{ $learners->lastItem() ?? 0 }}</b> of <b>{{ $learners->total() }}</b> records
    </div>
    <ul class="paginations mb-0">
        <li>
            <a href="{{ $learners->onFirstPage() ? '#' : $learners->appends(request()->all())->previousPageUrl() }}" 
               class="ajax-page-link w-auto px-3 {{ $learners->onFirstPage() ? 'disabled text-muted pe-none' : '' }}" 
               data-page="{{ $learners->currentPage() - 1 }}">
                <i class="fa-solid fa-chevron-left me-1"></i> Prev
            </a>
        </li>

        @for ($i = 1; $i <= $learners->lastPage(); $i++)
            @if ($i == 1 || $i == $learners->lastPage() || ($i >= $learners->currentPage() - 2 && $i <= $learners->currentPage() + 2))
                <li>
                    <a href="{{ $learners->appends(request()->all())->url($i) }}" 
                       class="ajax-page-link {{ $learners->currentPage() == $i ? 'active' : '' }}" 
                       data-page="{{ $i }}">
                        {{ $i }}
                    </a>
                </li>
            @elseif ($i == $learners->currentPage() - 3 || $i == $learners->currentPage() + 3)
                <li><span class="px-2 text-muted">...</span></li>
            @endif
        @endfor

        <li>
            <a href="{{ $learners->hasMorePages() ? $learners->appends(request()->all())->nextPageUrl() : '#' }}" 
               class="ajax-page-link w-auto px-3 {{ !$learners->hasMorePages() ? 'disabled text-muted pe-none' : '' }}" 
               data-page="{{ $learners->currentPage() + 1 }}">
                Next <i class="fa-solid fa-chevron-right ms-1"></i>
            </a>
        </li>
    </ul>
</div>
@endif
