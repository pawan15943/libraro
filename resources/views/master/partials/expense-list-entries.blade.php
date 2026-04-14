<div class="row">
    <div class="col-lg-12 ">
        <p>
            <b><span id="expenseRecordsMeta">{{ $expences->total() }} Records — showing {{ $expences->perPage() }} per page</span></b>
        </p>
    </div>
</div>

<div class="row g-2 mb-4" id="expenseRowsContainer">
    @forelse($expences as $exp)
    <div class="col-lg-12">
        <div class="revenue-info">
            <ul>
                <li style="width: 5%;">
                    <div class="icon">
                        <i class="fa fa-long-arrow-left text-danger"></i>
                    </div>
                </li>
                <li style="width: 20%;">
                    <span>Expense Name</span>
                    <p class="uppercase truncate">{{ $exp->particular }}</p>
                </li>
                <li>
                    <span>Amount</span>
                    <p>{{ number_format($exp->amount, 2) }}</p>
                </li>
                <li>
                    <span>Payment Mode</span>
                    <p>{{ $exp->payment_mode }}</p>
                </li>
                <li>
                    <span>Paid On</span>
                    <p>{{ \Carbon\Carbon::parse($exp->date)->format('d M, Y') }}</p>
                </li>
                <li style="width:8%;">
                    <span>Action</span>
                    <p>
                    <form id="delete-form-{{ $exp->id }}"
                        action="{{ route('add.expenses.destroy', $exp->id) }}"
                        method="POST"
                        style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>

                    <a type="button" href="javascript:;"
                        onclick="confirmDelete({{ $exp->id }})">
                        Delete
                    </a>

                    </p>
                </li>

            </ul>
        </div>
    </div>

    @empty

    <div class="col-lg-12 text-center">
        <p>No expense records found.</p>
    </div>

    @endforelse
</div>

@if ($expences->lastPage() > 1)
<ul class="paginations mt-4 expense-pagination">
    {{-- Prev --}}
    <li>
        <a href="{{ $expences->onFirstPage() ? '#' : $expences->previousPageUrl() }}" class="w-auto px-3 text-muted expense-page-link">Prev</a>
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

    <li>
        <a href="{{ $expences->hasMorePages() ? $expences->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted expense-page-link">Next</a>
    </li>
</ul>
@endif
