@extends('layouts.library')
@section('content')


<div class="heading-list justify-content-end mb-1">
    <a href="javascript:;" class="btn btn-primary export" data-bs-toggle="modal" data-bs-target="#expenseModal">
        <i class="fa-solid fa-plus "></i> Add Expense
    </a>
</div>
  <!-- Modal -->
  <div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="exampleModalLabel">Add Expense</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="expenseForm">
             @csrf
        <div class="modal-body">
          <div class="row g-4">
      
            <input type="hidden" id="expense_id" name="expense_id">

            <div class="col-lg-12">
                <label>Date  <span>*</span></label>
                <input type="date" class="form-control" name="date" >
            </div>
            <div class="col-lg-6">
                <label>Expense Name <span>*</span></label>
                <select class="form-select" name="name">
                <option value="">Choose</option>
                @foreach($data as $key => $value)
                   <option value="{{$value->name}}">{{$value->name}}</option>
                @endforeach
                    <option value="other">Other</option>
                 </select>
                
            </div>
            <div class="col-lg-6">
                <label>Amount  <span>*</span></label>
                <input type="number" class="form-control" name="amount" >
            </div>
            <div class="col-lg-12">
                <label>Payment Mode  <span>*</span></label>
                <select name="payment_mode" class="form-control form-select" >
                <option value="">Choose</option>
                <option value="1">Online</option>
                <option value="2">Offline</option>
                <option value="3">Pay Later</option>
                </select>
            </div>
            <div class="col-lg-12">
                <label>Remark</label>
                <textarea name="remark" class="form-control"></textarea>
            </div>
          

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary button">Add Expense</button>
        </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Expense -->



<div class="row mb-4">
  <div class="col-lg-12">
    <div class="filter p-3 bg-white">
      <h4><i class="fa fa-filter"></i> Filter Expenses</h4>
      <form method="GET" action="{{ route('add.expense.list') }}">
        <div class="row g-4">
          <div class="col-lg-4">
            <label>Choose Expense Type</label>
            <select name="expense" class="form-control form-select">
              <option value="">All Types</option>
              @foreach($data as $expType)
                <option value="{{ $expType->name }}" {{ request('expense') == $expType->name ? 'selected' : '' }}>
                  {{ $expType->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-lg-4">
            <label>From</label>
            <input type="date" name="from" class="form-control" value="{{ request('from') }}">
          </div>

          <div class="col-lg-4">
            <label>To</label>
            <input type="date" name="to" class="form-control" value="{{ request('to') }}">
          </div>

          <div class="col-lg-3">
            <button type="submit" class="btn btn-primary button">Search</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="row">
    <div class="col-lg-12 ">
        <p>
            <b>{{ $expences->total() }} Records — showing {{ $expences->perPage() }} per page</b>
        </p>
    </div>
</div>

<div class="row g-4 mb-4">
@forelse($expences as $exp)

    <div class="col-lg-12">
        <div class="revenue-info">
            <ul>
                <li style="width: 5%;">
                    <div class="icon">
                        <i class="fa fa-long-arrow-left text-danger"></i>
                    </div>
                </li>
                <li>
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
                {{-- <li>
                    <span>Remark</span>
                    <p>{{ $exp->remark ?? '-' }}</p>
                </li> --}}
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
{{-- Pagination --}}
@if ($expences->lastPage() > 1)
<ul class="paginations mt-4">
    {{-- Prev --}}
    <li>
        <a href="{{ $expences->onFirstPage() ? '#' : $expences->previousPageUrl() }}" class="w-auto px-3 text-muted">Prev</a>
    </li>

    {{-- Page Numbers (shortened: 1 ... current ... last) --}}
    @if ($expences->currentPage() > 3)
        <li><a href="{{ $expences->url(1) }}">1</a></li>
        <li><span>...</span></li>
    @endif

    @for ($i = max(1, $expences->currentPage() - 2); $i <= min($expences->lastPage(), $expences->currentPage() + 2); $i++)
        <li>
            <a href="{{ $expences->url($i) }}" class="{{ $expences->currentPage() == $i ? 'active' : '' }}">
                {{ $i }}
            </a>
        </li>
    @endfor

    @if ($expences->currentPage() < $expences->lastPage() - 2)
        <li><span>...</span></li>
        <li><a href="{{ $expences->url($expences->lastPage()) }}">{{ $expences->lastPage() }}</a></li>
    @endif

    {{-- Next --}}
    <li>
        <a href="{{ $expences->hasMorePages() ? $expences->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted">Next</a>
    </li>
</ul>
@endif

  <script>
   
   $(document).on('submit', '#expenseForm', function (e) {
   
         event.preventDefault(); 
    var formData = $(this).serialize();
    var formId = $(this).attr('id');
    var  url = "{{ route('daily.expense.store') }}";


    $.ajax({
        url: url,
        method: "POST",
        data: formData,
        success: function (response) {

            // Clear old validation errors
            $(".is-invalid").removeClass("is-invalid");
            $(".invalid-feedback").remove();
            $("#error-message").hide().text('');
            $("#success-message").hide().text('');

            if (response.success) {
                $('#expenseModal').modal('hide');
                toastr.success(response.message);
                location.reload();
            }
            else if (response.errors) {
                $.each(response.errors, function(key, value) {
                    var element = $("[name='" + key + "']");
                    element.addClass("is-invalid");
                    element.after('<span class="invalid-feedback" role="alert">' + value + '</span>');
                });
            } else {
                $("#error-message").text(response.message).show();
            }
        },
       error: function (xhr) {
        // Clear old validation errors
        $(".is-invalid").removeClass("is-invalid");
        $(".invalid-feedback").remove();
        $("#error-message").hide().text('');
        $("#success-message").hide().text('');

        if (xhr.status === 422) {
            var errors = xhr.responseJSON.errors;

            $.each(errors, function (key, value) {
                let field = $(`[name="${key}"]`);

                // Handle fields like name.0, name.1, etc.
                if (key.includes('.')) {
                    const [baseKey, index] = key.split('.');
                    field = $(`[name="${baseKey}[]"]`).eq(index);
                }

                field.addClass('is-invalid');
                field.after(`<span class="invalid-feedback" role="alert"><strong>${value[0]}</strong></span>`);
            });
        } else {
            alert('An unexpected error occurred.');
        }
    }
    });
});

// Prefill modal for edit
$(document).on('click', '.editExpense', function () {
    let expense = $(this).data('expense');
    $('#expense_id').val(expense.id);
    $('[name="date"]').val(expense.date);
    $('[name="name"]').val(expense.name);
    $('[name="amount"]').val(expense.amount);
    $('[name="payment_mode"]').val(expense.payment_mode);
    $('[name="remark"]').val(expense.remark);
    $('#expenseModal').modal('show');
});

  </script>
  <script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This expense will be permanently deleted.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + id).submit();
        }
    })
}
</script>
@endsection