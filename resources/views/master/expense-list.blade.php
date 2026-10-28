@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

{{-- Add expense --}}
<div class="modal fade lpg-modal" id="expenseModal" tabindex="-1" aria-labelledby="modalExpenseTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form id="expenseForm" novalidate>
                @csrf
                <input type="hidden" id="expense_id" name="id" value="">
                <div class="modal-header">
                    <span class="lpg-ic is-bad"><x-lb-icon name="receipt" /></span>
                    <div>
                        <h2 id="modalExpenseTitle">Add expense</h2>
                        <small>Recorded for the current branch</small>
                    </div>
                    <button type="button" class="lpg-close" data-bs-dismiss="modal" aria-label="Close"><x-lb-icon name="x" /></button>
                </div>
                <div class="modal-body">
                    <div class="lef-f">
                        <label for="expenseAmount">Amount <i>*</i></label>
                        <div class="lef-in"><span class="lef-pre">₹</span><input type="number" id="expenseAmount" name="amount" min="1" step="any" inputmode="decimal" placeholder="0" required></div>
                    </div>
                    <div class="lef-grid" style="margin-top:14px">
                        <div class="lef-f">
                            <label for="expenseNameSelect">Category <i>*</i></label>
                            <div class="lef-in is-select"><x-lb-icon name="tag" />
                                <select name="expense_id" id="expenseNameSelect" required>
                                    <option value="">Choose…</option>
                                    @foreach($data as $value)
                                        <option value="{{ $value->id }}">{{ $value->name }}</option>
                                    @endforeach
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="lef-f">
                            <label for="dateInput">Date <i>*</i></label>
                            <div class="lef-in"><x-lb-icon name="calendar-days" /><input type="date" name="date" id="dateInput" required></div>
                        </div>
                    </div>
                    <div class="lef-f" style="margin-top:14px">
                        <label>Paid by <i>*</i></label>
                        <div class="lpg-radio is-seg" role="radiogroup" aria-label="Payment mode">
                            <label><input type="radio" name="payment_mode" value="1" checked><span><x-lb-icon name="smartphone" />Online</span></label>
                            <label><input type="radio" name="payment_mode" value="2"><span><x-lb-icon name="banknote" />Cash</span></label>
                            <label><input type="radio" name="payment_mode" value="3"><span><x-lb-icon name="clock" />Pay later</span></label>
                        </div>
                    </div>
                    <div class="lef-f" id="remarkGroup" style="display:none; margin-top:14px">
                        <label for="expenseRemark">What was it for? <i>*</i></label>
                        <div class="lef-in is-area"><textarea name="remark" id="expenseRemark" rows="2" placeholder="e.g. Plumber visit for washroom"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lp-btn lp-btn--ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="lp-btn noLoader"><x-lb-icon name="check" />Save expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="lpg">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Expenses</span></div>
            <h1>Expenses</h1>
            <p>Everything you spend on running the library — rent, bills, upkeep and more.</p>
        </div>
        @if(getCurrentBranch() != 0)
            <div class="lpg-head-r">
                @unless($showEmptyState)
                    <button type="button" class="lp-btn lp-btn--ghost" id="btnExportExpenseCsv"><x-lb-icon name="download" />Export</button>
                @endunless
                <a href="javascript:;" class="lp-btn" data-bs-toggle="modal" data-bs-target="#expenseModal"><x-lb-icon name="plus" />Add expense</a>
            </div>
        @endif
    </header>

    <div id="expensePageDynamic">
        @if($showEmptyState)
            @include('master.partials.expense-list-empty-state')
        @else
            @include('master.partials.expense-list-non-empty-body', [
                'expences' => $expences,
                'data' => $data,
                'totalExpenseAmount' => $totalExpenseAmount ?? 0,
                'thisMonthExpense' => $thisMonthExpense ?? 0,
                'todayExpense' => $todayExpense ?? 0,
                'metrics' => $metrics ?? []
            ])
        @endif
    </div>
</div>

<script>
    function expenseToday() {
        var d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    document.getElementById('dateInput').value = expenseToday();
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

    var activeTab = 'all';
    function applyClientFilters() {
        var query = ($('#cardSearchInput').val() || '').toLowerCase().trim();
        var $cards = $('#expenseCardsContainer .collection-record-card');
        var matchingCount = 0;
        $cards.each(function () {
            var $c = $(this);
            var matchesTab = (activeTab === 'all') || (activeTab === ($c.attr('data-mode') || ''));
            var matchesSearch = !query || (($c.attr('data-search') || '').indexOf(query) !== -1);
            $c.toggleClass('d-none', !(matchesTab && matchesSearch));
            if (matchesTab && matchesSearch) matchingCount++;
        });
        $('#visibleCountBadge').text(matchingCount);
        $('#searchEmptyState').toggleClass('d-none', !($cards.length > 0 && matchingCount === 0));
    }

    $(document).on('click', '.quick-tab-btn', function () {
        $('.quick-tab-btn').removeClass('active is-on');
        $(this).addClass('active is-on');
        activeTab = $(this).attr('data-tab');
        applyClientFilters();
    });

    $(document).on('input', '#cardSearchInput', function () {
        $('#clearSearchBtn').toggleClass('d-none', !$(this).val().trim());
        applyClientFilters();
    });

    $(document).on('click', '#clearSearchBtn', function () {
        $('#cardSearchInput').val('').trigger('focus');
        $(this).addClass('d-none');
        applyClientFilters();
    });

    // CSV export of the entries currently shown
    $(document).on('click', '#btnExportExpenseCsv', function () {
        var rows = [];
        var headers = ['S.No.', 'Expense Particular', 'Transaction Ref', 'Payment Mode', 'Paid Date', 'Amount (INR)'];
        rows.push(headers.map(function (h) { return '"' + h.replace(/"/g, '""') + '"'; }).join(','));
        var $exportCards = $('#expenseCardsContainer .collection-record-card:not(.d-none)');
        if ($exportCards.length === 0) {
            $exportCards = $('#expenseCardsContainer .collection-record-card');
        }
        $exportCards.each(function () {
            var $c = $(this);
            var row = [
                $c.attr('data-sno') || '',
                $c.attr('data-particular') || '',
                $c.attr('data-ref') || '',
                $c.attr('data-mode') ? $c.attr('data-mode').toUpperCase() : '',
                $c.attr('data-date') || '',
                parseFloat($c.attr('data-amount') || 0).toFixed(2)
            ];
            rows.push(row.map(function (val) { return '"' + String(val).replace(/"/g, '""') + '"'; }).join(','));
        });
        var blob = new Blob(["﻿" + rows.join("\r\n")], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', 'Daily_Expense_Report_' + expenseToday() + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    function refreshExpensePage(extra) {
        var data = expenseGetFilterParams(extra);
        $('#expenseListAjaxWrapper').css('opacity', '0.5');
        $.get("{{ route('add.expense.list.page') }}", data)
            .done(function (res) {
                if (res && res.html) {
                    $('#expensePageDynamic').html(res.html);
                    expenseReplaceHistory(data);
                    activeTab = 'all';
                    applyClientFilters();
                    if (!$('#btnExportExpenseCsv').length && $('#expenseCardsContainer .collection-record-card').length) {
                        $('.lpg-head-r').prepend('<button type="button" class="lp-btn lp-btn--ghost" id="btnExportExpenseCsv">Export</button>');
                    }
                }
            })
            .fail(function () {
                toastr.error('Could not load expenses.');
            })
            .always(function () {
                $('#expenseListAjaxWrapper').css('opacity', '1');
            });
    }

    $(document).on('click', '#expensePageDynamic .expense-toolbar-filter-toggle', function (e) {
        e.preventDefault();
        $('#expensePageDynamic #filterContainer').slideToggle(200);
        $(this).toggleClass('is-on');
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
        refreshExpensePage({ page: 1, expense: '', from: '', to: '', payment_mode: '' });
    });

    $(document).on('click', '#expensePageDynamic .expense-page-link', function (e) {
        var href = $(this).attr('href');
        e.preventDefault();
        if (!href || href === '#') {
            return;
        }
        try {
            var url = new URL(href, window.location.origin);
            refreshExpensePage(Object.fromEntries(url.searchParams.entries()));
        } catch (err) {
            window.location.href = href;
        }
    });

    function expenseToggleRemark() {
        var isOther = $('#expenseNameSelect').val() === 'other';
        $('#remarkGroup')[isOther ? 'slideDown' : 'slideUp'](150);
        $('#remarkGroup textarea').prop('required', isOther);
        if (!isOther) $('#remarkGroup textarea').val('');
    }
    $(document).on('change', '#expenseNameSelect', expenseToggleRemark);

    function expenseClearErrors() {
        $('#expenseForm .lef-f').removeClass('is-bad').find('.lef-err').remove();
    }
    function expenseShowError(name, msg) {
        var $f = $('#expenseForm [name="' + name + '"]').first().closest('.lef-f');
        $f.addClass('is-bad').append($('<p class="lef-err" role="alert"></p>').text(msg));
    }

    // Fresh form each time "Add expense" opens
    $(document).on('click', '[data-bs-target="#expenseModal"]', function () {
        if ($(this).hasClass('editExpense')) return;
        $('#modalExpenseTitle').text('Add expense');
        $('#expenseForm')[0].reset();
        $('#expense_id').val('');
        document.getElementById('dateInput').value = expenseToday();
        expenseClearErrors();
        $('#remarkGroup').hide();
    });
    $('#expenseModal').on('shown.bs.modal', function () { $('#expenseAmount').trigger('focus'); });

    $(document).on('submit', '#expenseForm', function (e) {
        e.preventDefault();
        var $btn = $('#expenseForm button[type="submit"]');
        if ($btn.hasClass('is-busy')) {
            return;
        }
        expenseClearErrors();
        $btn.addClass('is-busy');

        $.ajax({
            url: "{{ route('daily.expense.store') }}",
            method: 'POST',
            data: $(this).serialize(),
            complete: function () {
                $btn.removeClass('is-busy');
            },
            success: function (response) {
                if (response.success) {
                    $('#expenseModal').modal('hide');
                    toastr.success(response.message);
                    $('#expenseForm')[0].reset();
                    document.getElementById('dateInput').value = expenseToday();
                    $('#expense_id').val('');
                    $('#remarkGroup').hide();
                    if ($('#expenseEmptyState').length) {
                        window.location.reload();
                        return;
                    }
                    refreshExpensePage(expenseGetFilterParams({ page: 1 }));
                } else if (response.errors) {
                    $.each(response.errors, function (key, value) {
                        expenseShowError(key, Array.isArray(value) ? value[0] : value);
                    });
                } else {
                    toastr.error(response.message || 'Could not save expense.');
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (key, value) {
                        expenseShowError(key.split('.')[0], value[0]);
                    });
                } else {
                    toastr.error('An unexpected error occurred.');
                }
            }
        });
    });

    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete this expense?',
            text: 'It will be removed from your records permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#de3b3d',
            cancelButtonColor: '#7b7c83',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('/add/expense') }}/" + id,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function () {
                    toastr.success('Expense deleted');
                    refreshExpensePage(expenseGetFilterParams());
                },
                error: function () {
                    toastr.error('Could not delete expense.');
                }
            });
        });
    }
</script>
@endsection
