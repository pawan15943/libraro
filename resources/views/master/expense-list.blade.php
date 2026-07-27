@extends('layouts.library')
@section('content')

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

                        <input type="hidden" id="expense_id" name="expense_id" value="">

                        <div class="col-lg-12">
                            <label>Date <span>*</span></label>
                            <input type="date" class="form-control" name="date" id="dateInput">
                        </div>
                        <div class="col-lg-6">
                            <label>Expense Name <span>*</span></label>
                            <select class="form-select" name="expense_id" id="expenseNameSelect">
                                <option value="">Choose</option>
                                @foreach($data as $key => $value)
                                <option value="{{$value->id}}">{{$value->name}}</option>
                                @endforeach
                                <option value="other">Other</option>

                            </select>

                        </div>
                        <div class="col-lg-6">
                            <label>Amount <span>*</span></label>
                            <input type="number" class="form-control" name="amount">
                        </div>
                        <div class="col-lg-12">
                            <label>Payment Mode <span>*</span></label>
                            <select name="payment_mode" class="form-control form-select">
                                <option value="">Choose</option>
                                <option value="1">Online</option>
                                <option value="2">Offline</option>
                                <option value="3">Pay Later</option>
                            </select>
                        </div>
                        <div class="col-lg-12" id="remarkGroup" style="display:none;">
                            <label>Remark</label>
                            <textarea name="remark" class="form-control" style="height: 100px !important;" placeholder="Enter expense description"></textarea>
                        </div>


                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary button noLoader">Add Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="expensePageDynamic">
    @if($showEmptyState)
        @include('master.partials.expense-list-empty-state')
    @else
        @include('master.partials.expense-list-non-empty-body', ['expences' => $expences, 'data' => $data])
    @endif
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
        $('#expensePageDynamic #filterContainer').toggle();
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
            $('#remarkGroup').show();
        } else {
            $('#remarkGroup').hide();
            $('#remarkGroup textarea').val('');
        }
    }

    $(document).on('change', '#expenseNameSelect', expenseToggleRemark);

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
                $("#error-message").hide().text('');
                $("#success-message").hide().text('');

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
                        element.after('<span class="invalid-feedback" role="alert">' + value + '</span>');
                    });
                } else {
                    $("#error-message").text(response.message).show();
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
                        field.after(`<span class="invalid-feedback" role="alert"><strong>${value[0]}</strong></span>`);
                    });
                } else {
                    alert('An unexpected error occurred.');
                }
            }
        });
    });

    $(document).on('click', '.editExpense', function () {
        var expense = $(this).data('expense');
        $('#expense_id').val(expense.id);
        $('[name="date"]').val(expense.date);
        $('[name="name"]').val(expense.name);
        $('[name="amount"]').val(expense.amount);
        $('[name="payment_mode"]').val(expense.payment_mode);
        $('[name="remark"]').val(expense.remark);
        expenseToggleRemark();
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
