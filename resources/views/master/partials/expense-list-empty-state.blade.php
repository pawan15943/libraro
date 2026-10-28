<div class="lpg-empty" id="expenseEmptyState">
    @if(getCurrentBranch() != 0)
        <span><x-lb-icon name="receipt" /></span>
        <b>No expenses recorded yet</b>
        <p>Track rent, electricity, internet, cleaning and every other cost of running your library in one place.</p>
        <a href="javascript:;" class="lp-btn" data-bs-toggle="modal" data-bs-target="#expenseModal"><x-lb-icon name="plus" />Add first expense</a>
    @else
        <span><x-lb-icon name="building" /></span>
        <b>Choose a branch first</b>
        <p>Expenses are kept per branch. Pick a branch from the top bar to see and record them.</p>
    @endif
</div>
