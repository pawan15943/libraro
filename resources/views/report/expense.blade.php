@extends('layouts.library')
@section('content')
<div class="card">
    <div class="row mb-4">
        <div class="col-lg-6">
            @php
            $dt = DateTime::createFromFormat('!m', $month);
            @endphp
            <h4>Current Month Revenue for {{ $dt->format('F') }}, {{ $year }}</h4>
        </div>
        <div class="col-lg-6">
            <h4><i class="fa fa-inr"></i> {{ number_format($library_revenue->monthly_revenue ?? 0, 2) }}</h4>

        </div>
    </div>
    <form id="expense-form" action="{{ route('report.expense.store') }}" method="POST">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">

        @if($monthlyExpenses->isEmpty())
        <!-- No existing expenses, show empty form fields with plus button -->
        <div class="row mt-3">
            <div class="col-lg-6">
                <select class="form-select " name="expense_id[]">
                    <option value="">Select Expense</option>
                    @foreach($expenses as $expense)
                    <option value="{{ $expense->id }}">{{ $expense->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-5">
                <input type="text" placeholder="Amount" class="form-control" name="amount[]">
            </div>
            <div class="col-lg-1 d-inline">
                <button id="add_row" class="btn btn-primary button" type="button"><i class="fa fa-plus"></i></button>
            </div>
        </div>
        @else
        <!-- Populate form with existing expenses -->
        @foreach($monthlyExpenses as $key => $expense)
        <div class="row mt-3">
            <div class="col-lg-6">
                <select class="form-select" name="expense_id[]">
                    <option value="">Select Expense</option>
                    @foreach($expenses as $exp)
                    <option value="{{ $exp->id }}" {{ $exp->id == $expense->expense_id ? 'selected' : '' }}>
                        {{ $exp->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-5">
                <input type="text" placeholder="Amount" class="form-control" name="amount[]" value="{{ $expense->amount }}">
            </div>
            <div class="col-lg-1 d-inline">
                @if($key == count($monthlyExpenses) - 1)
                <!-- Plus button on the last row -->
                <button id="add_row" class="btn btn-primary button" type="button"><i class="fa fa-plus"></i></button>
                @else
                <!-- Delete button on all other rows -->
                <button class="btn btn-danger delete-row" type="button"><i class="fa fa-trash text-white"></i></button>
                @endif
            </div>
        </div>
        @endforeach
        @endif

        <div class="row-container"></div>

        <div class="row mt-3">
            <div class="col-lg-3">
                <input type="submit" class="btn btn-primary button" value="Submit">
            </div>
        </div>
    </form>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Function to add a new row
        $('#add_row').on('click', function() {
            var expenseOptions = `
            @foreach($expenses as $expense)
                <option value="{{ $expense->id }}">{{ $expense->name }}</option>
            @endforeach
        `;

            var newRow = `
        <div class="row mt-3">
            <div class="col-lg-6">
                <select class="form-select" name="expense_id[]">
                    <option value="">Select Expense</option>
                    ${expenseOptions}
                </select>
            </div>
            <div class="col-lg-5">
                <input type="text" placeholder="Amount" class="form-control" name="amount[]">
            </div>
            <div class="col-lg-1 d-inline">
                <button class="btn btn-danger delete-row" type="button"><i class="fa fa-trash text-white"></i></button>
            </div>
        </div>
        `;
            // Append the new row to the row-container
            $('.row-container').append(newRow);
        });

        // Function to delete a row
        $(document).on('click', '.delete-row', function() {
            $(this).closest('.row').remove();
        });
    });
</script>
<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable');
       
    });
</script>
@endsection