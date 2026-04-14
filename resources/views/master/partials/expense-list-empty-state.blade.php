<div class="no-data-found" id="expenseEmptyState">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>

    <dotlottie-wc
        src="https://lottie.host/5d973bf9-2f1d-4dd5-925f-86da95dbd7b1/t7dXaWIroC.lottie"
        style="width: 200px;height: 200px"
        autoplay
        loop></dotlottie-wc>

    @if(getCurrentBranch() != 0)
        <h4>You haven’t added any expense records.</h4>
        <span>Start by creating your first expense to manage it here.</span>
        <a href="javascript:;" class="btn btn-primary export" data-bs-toggle="modal" data-bs-target="#expenseModal">
            <i class="fa-solid fa-plus "></i> Add Expense
        </a>
    @else
        <h4>To add expense, first select your Branch.</h4>
        <span>Floors are branch-specific. Please choose a branch before adding expense. (Choose Branch in Header Dropdown)</span>
    @endif
</div>
