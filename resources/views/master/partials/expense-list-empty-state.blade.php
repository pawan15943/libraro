<div class="expense-empty-card" id="expenseEmptyState">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>

    <dotlottie-wc
        src="https://lottie.host/5d973bf9-2f1d-4dd5-925f-86da95dbd7b1/t7dXaWIroC.lottie"
        style="width: 200px;height: 200px; margin: 0 auto;"
        autoplay
        loop></dotlottie-wc>

    @if(getCurrentBranch() != 0)
        <h4>You haven't recorded any expenses yet</h4>
        <span>Track daily library overheads, maintenance, utilities, and more in one centralized dashboard.</span>
        <div>
            <a href="javascript:;" class="btn-expense-add" data-bs-toggle="modal" data-bs-target="#expenseModal">
                <i class="fa-solid fa-plus"></i> Add First Expense
            </a>
        </div>
    @else
        <h4>Please select an active Branch</h4>
        <span>Expenses are tracked per branch. Please choose a branch from the header selector to record expenses.</span>
    @endif
</div>
