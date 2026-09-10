@extends('layouts.library')
@section('content')

<link rel="stylesheet" href="{{ asset('public/css/library-expense.css') }}?v={{ time() }}">

<!-- Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="modalExpenseTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExpenseTitle">
                    <i class="fa-solid fa-receipt"></i> Add New Expense
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="expenseForm">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <input type="hidden" id="expense_id" name="id" value="">

                        <div class="col-lg-12">
                            <label>Expense Date <span>*</span></label>
                            <input type="date" class="form-control" name="date" id="dateInput" required>
                        </div>

                        <div class="col-lg-6">
                            <label>Expense Category <span>*</span></label>
                            <select class="form-select" name="expense_id" id="expenseNameSelect" required>
                                <option value="">Select Category</option>
                                @foreach($data as $key => $value)
                                <option value="{{$value->id}}">{{$value->name}}</option>
                                @endforeach
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="col-lg-6">
                            <label>Amount (₹) <span>*</span></label>
                            <input type="number" class="form-control" name="amount" min="1" step="any" placeholder="0.00" required>
                        </div>

                        <div class="col-lg-12">
                            <label>Payment Mode <span>*</span></label>
                            <select name="payment_mode" class="form-select" required>
                                <option value="">Select Mode</option>
                                <option value="1">Online</option>
                                <option value="2">Offline</option>
                                <option value="3">Pay Later</option>
                            </select>
                        </div>

                        <div class="col-lg-12" id="remarkGroup" style="display:none;">
                            <label>Description / Particulars <span>*</span></label>
                            <textarea name="remark" class="form-control" placeholder="Enter specific description for this expense..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-submit-expense noLoader">
                        <i class="fa-solid fa-floppy-disk"></i> Save Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="library-expense-module">
    <div id="expensePageDynamic">
        @if($showEmptyState)
            @include('master.partials.expense-list-empty-state')
        @else
            @include('master.partials.expense-list-non-empty-body', [
                'expences' => $expences, 
                'data' => $data,
                'totalExpenseAmount' => $totalExpenseAmount ?? 0,
                'thisMonthExpense' => $thisMonthExpense ?? 0
            ])
        @endif
    </div>
</div>

<script>
    document.getElementById('dateInput').value = new Date().toISOString().split('T')[0];

    var expenseListPageUrl = "{{ route('add.expense.list') }}";

    function expenseGetFilterParams(extra) {
        var data = {};
        var $form = $('#expenseFilterForm');
        if ($form.length) {
            $.each($form.serializeArray(), function (_, field) {
                data[field.name] = field.value;
            });
        }
        var loc = new URL(window.location.href);
        if (loc.searchParams.has('page')) {
            data.page = loc.searchParams.get('page');
        }
        if (extra && typeof extra === 'object') {
            $.extend(data, extra);
        }
        return data;
    }

    function expenseReplaceHistory(params) {
        var u = new URL(expenseListPageUrl, window.location.origin);
        u.search = '';
        Object.keys(params || {}).forEach(function (k) {
            var v = params[k];
            if (v !== '' && v !== undefined && v !== null) {
                u.searchParams.set(k, String(v));
            }
        });
        if (!u.searchParams.get('page') || u.searchParams.get('page') === '1') {
            u.searchParams.delete('page');
        }
        history.replaceState({}, '', u.pathname + u.search);
    }

    function refreshExpensePage(extra) {
        var data = expenseGetFilterParams(extra);
        $.get("{{ route('add.expense.list.page') }}", data)
            .done(function (res) {
                if (res && res.html) {
                    $('#expensePageDynamic').html(res.html);
                    expenseReplaceHistory(data);
                }
            })
            .fail(function () {
                toastr.error('Could not load expense list.');
            });
    }

    function expenseFormSetSubmitLoading(isLoading) {
        var $btn = $('#expenseForm button[type="submit"]');
        if (!$btn.length) {
            return;
        }
        if (isLoading) {
            if ($btn.data('expenseBtnHtml') === undefined) {
                $btn.data('expenseBtnHtml', $btn.html());
            }
            $btn.prop('disabled', true);
            $btn.css({ 'pointer-events': 'none', opacity: '0.7' });
            $btn.html(
                $btn.data('expenseBtnHtml') +
                ' <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
            );
        } else {
            var html = $btn.data('expenseBtnHtml');
            if (html !== undefined) {
                $btn.html(html);
            }
            $btn.prop('disabled', false);
            $btn.css({ 'pointer-events': '', opacity: '' });
        }
    }

    $(document).on('click', '#expensePageDynamic .expense-toolbar-filter-toggle', function (e) {
        e.preventDefault();
        $('#expensePageDynamic #filterContainer').slideToggle(200);
        $(this).toggleClass('active');
    });

    $(document).on('submit', '#expensePageDynamic #expenseFilterForm', function (e) {
        e.preventDefault();
        refreshExpensePage({ page: 1 });
    });

    $(document).on('click', '#expensePageDynamic #expenseClearFilter', function (e) {
        e.preventDefault();
        var $form = $('#expenseFilterForm');
        if ($form.length) {
            $form[0].reset();
        }
        refreshExpensePage({ page: 1, expense: '', from: '', to: '' });
    });

    $(document).on('click', '#expenseListAjaxWrapper .expense-page-link', function (e) {
        var href = $(this).attr('href');
        if (!href || href === '#') {
            return;
        }
        e.preventDefault();
        try {
            var url = new URL(href, window.location.origin);
            var params = Object.fromEntries(url.searchParams.entries());
            refreshExpensePage(params);
        } catch (err) {
            window.location.href = href;
        }
    });

    function expenseToggleRemark() {
        var selectedText = $('#expenseNameSelect option:selected').text().trim().toLowerCase();
        if (selectedText === 'other') {
            $('#remarkGroup').slideDown(150);
            $('#remarkGroup textarea').prop('required', true);
        } else {
            $('#remarkGroup').slideUp(150);
            $('#remarkGroup textarea').prop('required', false).val('');
        }
    }

    $(document).on('change', '#expenseNameSelect', expenseToggleRemark);

    // Reset modal state on opening
    $(document).on('click', '[data-bs-target="#expenseModal"]', function() {
        if (!$(this).hasClass('editExpense')) {
            $('#modalExpenseTitle').html('<i class="fa-solid fa-receipt"></i> Add New Expense');
            $('#expenseForm')[0].reset();
            $('#expense_id').val('');
            document.getElementById('dateInput').value = new Date().toISOString().split('T')[0];
            expenseToggleRemark();
        }
    });

    $(document).on('submit', '#expenseForm', function (e) {
        e.preventDefault();
        var $btn = $('#expenseForm button[type="submit"]');
        if ($btn.data('expenseSubmitBusy')) {
            return;
        }
        $btn.data('expenseSubmitBusy', true);
        expenseFormSetSubmitLoading(true);

        var formData = $(this).serialize();
        var url = "{{ route('daily.expense.store') }}";

        $.ajax({
            url: url,
            method: "POST",
            data: formData,
            complete: function () {
                $btn.data('expenseSubmitBusy', false);
                expenseFormSetSubmitLoading(false);
            },
            success: function (response) {
                $(".is-invalid").removeClass("is-invalid");
                $(".invalid-feedback").remove();

                if (response.success) {
                    $('#expenseModal').modal('hide');
                    toastr.success(response.message);
                    $('#expenseForm')[0].reset();
                    document.getElementById('dateInput').value = new Date().toISOString().split('T')[0];
                    $('#expense_id').val('');
                    expenseToggleRemark();
                    refreshExpensePage(expenseGetFilterParams({ page: 1 }));
                } else if (response.errors) {
                    $.each(response.errors, function (key, value) {
                        var element = $("[name='" + key + "']");
                        element.addClass("is-invalid");
                        element.after('<span class="invalid-feedback d-block mt-1 font-12" role="alert">' + value + '</span>');
                    });
                } else {
                    toastr.error(response.message || 'Could not save expense.');
                }
            },
            error: function (xhr) {
                $(".is-invalid").removeClass("is-invalid");
                $(".invalid-feedback").remove();

                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function (key, value) {
                        var field = $(`[name="${key}"]`);
                        if (key.includes('.')) {
                            const parts = key.split('.');
                            field = $(`[name="${parts[0]}[]"]`).eq(parts[1]);
                        }
                        field.addClass('is-invalid');
                        field.after(`<span class="invalid-feedback d-block mt-1 font-12" role="alert"><strong>${value[0]}</strong></span>`);
                    });
                } else {
                    toastr.error('An unexpected error occurred.');
                }
            }
        });
    });
</script>
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete Expense Record?',
            text: "This expense record will be permanently deleted.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('/add/expense') }}/" + id,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function () {
                        toastr.success('Expense deleted successfully!');
                        refreshExpensePage(expenseGetFilterParams());
                    },
                    error: function () {
                        toastr.error('Could not delete expense.');
                    }
                });
            }
        });
    }
</script>
@endsection
