@extends('layouts.library')

@section('content')

{{-- Scoped CSS for Learner Settlement Module --}}
<link rel="stylesheet" href="{{ asset('public/css/learner-settlement.css') }}?v={{ time() }}" />

<div class="learner-settlement-module">
    <div class="learner-settlement-wrapper">

        {{-- MAIN SETTLEMENT INTERACTIVE CARD --}}
        <div class="settlement-main-card" id="settlementSection">
            <div class="settlement-card-header">
                <div class="settlement-header-left">
                    <div class="settlement-header-icon">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <div>
                        <h4 class="settlement-header-title">Settlement &bull; {{ $learner->name }}</h4>
                        <p class="settlement-header-subtitle">
                            <span>UID: <strong class="seat-uid-tag">{{ $learner->learner_no ?? ('#' . $learner->id) }}</strong></span>
                            @if(!empty($learner->mobile))
                                <span class="ms-2 text-muted"><i class="fa-solid fa-phone me-1"></i>{{ $learner->mobile }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="settlement-header-right">
                    <a href="{{ route('learners') }}" class="btn-seat-back" title="Go Back">
                        <i class="fa-solid fa-arrow-left"></i> <span>Back</span>
                    </a>
                </div>
            </div>

            <div class="settlement-card-body">

                {{-- Section Heading & Select All Toggle --}}
                <div class="section-title-bar">
                    <h5 class="section-heading">
                        <i class="fa-solid fa-list-check text-muted"></i>
                        <span>Select Transactions to Settle</span>
                        <span class="badge bg-light text-dark border ms-1 font-outfit" style="font-size:0.75rem;">{{ count($detailRows) }} {{ count($detailRows) == 1 ? 'Record' : 'Records' }}</span>
                    </h5>
                    @if(count($detailRows) > 1)
                        <label class="select-all-wrap">
                            <input type="checkbox" id="v2FullLearnerDelete" checked>
                            <span>Select all transactions</span>
                        </label>
                    @endif
                </div>

                {{-- Interactive Transaction Cards Container --}}
                <div class="settlement-cards-container">
                    @forelse($detailRows as $row)
                        @php
                            $isSelected = count($detailRows) > 1 ? true : ($selectedDetailId ? ($row['id'] == $selectedDetailId) : true);
                        @endphp
                        <div class="settlement-plan-card {{ $isSelected ? 'selected' : '' }}" data-id="{{ $row['id'] }}">
                            <input type="checkbox" class="v2DetailSelector d-none" value="{{ $row['id'] }}" {{ $isSelected ? 'checked' : '' }}>
                            <div class="plan-card-top-row">
                                <div class="plan-badges-cluster">
                                    <span class="settlement-plan-badge">{{ $row['plan_name'] ?: 'Plan' }}</span>
                                    @if(!empty($row['seat_no']))
                                        <span class="settlement-seat-badge"><i class="fa-solid fa-chair me-1"></i>Seat {{ $row['seat_no'] }}</span>
                                    @endif
                                    @if(!empty($row['plan_type_name']) && $row['plan_type_name'] !== 'N/A')
                                        <span class="settlement-shift-badge">{{ $row['plan_type_name'] }}</span>
                                    @endif
                                    <span class="settlement-date-badge font-outfit">
                                        <i class="fa-regular fa-calendar me-1"></i>{{ $row['plan_start_date'] ?: '' }} &rarr; {{ $row['plan_end_date'] ?: '' }}
                                    </span>
                                </div>
                                <div class="settlement-check-circle">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                            </div>
                            <div class="settlement-card-metrics">
                                <div class="metric-item">
                                    <span class="metric-label">Plan Price</span>
                                    <span class="metric-value">₹{{ number_format($row['total_amount'] ?: $row['paid_amount'], 0) }}</span>
                                </div>
                                <div class="metric-item">
                                    <span class="metric-label">Paid</span>
                                    <span class="metric-value text-dark">₹{{ number_format($row['paid_amount'], 0) }}</span>
                                </div>
                                <div class="metric-item">
                                    <span class="metric-label">Pending</span>
                                    <span class="metric-value {{ $row['pending_amount'] > 0 ? 'text-danger fw-bold' : '' }}">₹{{ number_format($row['pending_amount'], 0) }}</span>
                                </div>
                                <div class="metric-item">
                                    <span class="metric-label">Extra</span>
                                    <span class="metric-value {{ $row['extra_amount'] > 0 ? 'text-success fw-bold' : '' }}">₹{{ number_format($row['extra_amount'], 0) }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info font-outfit">
                            <i class="fa-solid fa-circle-info me-2"></i>No active transaction records found for this learner.
                        </div>
                    @endforelse
                </div>

                {{-- Dynamic Summary Metric Grid (4 Cards) --}}
                <div class="settlement-summary-grid">
                    <div class="settlement-stat-card">
                        <span class="stat-label"><i class="fa-solid fa-receipt me-1"></i> Total Paid</span>
                        <strong class="stat-val v2Paid">₹0</strong>
                    </div>
                    <div class="settlement-stat-card card-pending">
                        <span class="stat-label"><i class="fa-solid fa-clock-rotate-left me-1"></i> Pending</span>
                        <strong class="stat-val v2Pending">₹0</strong>
                    </div>
                    <div class="settlement-stat-card card-extra">
                        <span class="stat-label"><i class="fa-solid fa-circle-plus me-1"></i> Extra</span>
                        <strong class="stat-val v2Extra">₹0</strong>
                    </div>
                    <div class="settlement-stat-card card-net">
                        <span class="stat-label"><i class="fa-solid fa-wallet me-1"></i> Net Amount</span>
                        <strong class="stat-val v2NetAmount">₹0</strong>
                    </div>
                </div>

                {{-- Dynamic Note Banner --}}
                <div class="settlement-note-banner">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>The above amounts change according to the selected transaction. Please check carefully before settling.</span>
                </div>

                <input type="hidden" class="v2SettlementCase" value="settled">

                {{-- Settle with Amount vs Adjust full Amount Toggle --}}
                <div class="settlement-choice-row v2SettlementOptionWrap" style="display:none;">
                    <label class="settlement-choice-card active" for="v2SettleWithAmount">
                        <input class="form-check-input v2SettlementOption" type="radio" name="v2SettlementOption" value="amount" id="v2SettleWithAmount" checked>
                        <span>Settle with Amount</span>
                    </label>
                    <label class="settlement-choice-card" for="v2AdjustFullAmount">
                        <input class="form-check-input v2SettlementOption" type="radio" name="v2SettlementOption" value="adjust_full" id="v2AdjustFullAmount">
                        <span>Adjust full Amount</span>
                    </label>
                </div>

                {{-- Action Panel 1: Pending Panel --}}
                <div class="v2PendingPanel settlement-action-panel pending" style="display:none;">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" checked>
                        <label class="form-check-label font-outfit text-danger fw-bold">Pay pending amount</label>
                    </div>
                    <div class="settlement-amount-input-wrap mb-2">
                        <span class="currency-symbol">₹</span>
                        <input type="text" class="form-control v2PayAmount" placeholder="0" inputmode="numeric">
                    </div>
                    <div class="form-check mt-2 mb-1">
                        <input class="form-check-input v2PendingMode" type="radio" name="v2PendingMode" value="future" id="v2PendingFuture" checked>
                        <label class="form-check-label font-outfit" for="v2PendingFuture">The remaining amount will be collected in the future.</label>
                    </div>
                    <div class="form-check mt-1">
                        <input class="form-check-input v2PendingMode" type="radio" name="v2PendingMode" value="adjust" id="v2PendingAdjust">
                        <label class="form-check-label font-outfit" for="v2PendingAdjust">Or, adjust / settle remaining amt. now</label>
                    </div>
                    <div class="help-text-banner v2PendingHelp"></div>
                </div>

                {{-- Action Panel 2: Extra Panel --}}
                <div class="v2ExtraPanel settlement-action-panel extra" style="display:none;">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" checked>
                        <label class="form-check-label font-outfit text-success fw-bold">Settle extra ₹<span class="v2ExtraTitleAmount">0</span></label>
                    </div>
                    <div class="settlement-amount-input-wrap mb-2">
                        <span class="currency-symbol">₹</span>
                        <input type="text" class="form-control v2RefundAmount" placeholder="0" inputmode="numeric">
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input v2ExtraMode" type="radio" name="v2ExtraMode" value="refund_pending_future" id="v2ExtraFuture" checked>
                        <label class="form-check-label font-outfit" for="v2ExtraFuture">The remaining amount will be refunded in the future.</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input v2ExtraMode" type="radio" name="v2ExtraMode" value="adjust" id="v2ExtraAdjust">
                        <label class="form-check-label font-outfit" for="v2ExtraAdjust">Or, adjust / settle remaining amount now.</label>
                    </div>
                </div>

                {{-- Action Panel 3: Already Settled Panel --}}
                <div class="v2SettledPanel settlement-action-panel" style="display:none; background:#eff6ff; border:1.5px solid #bfdbfe;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-check text-success fs-5"></i>
                        <span class="font-outfit fw-bold" style="color:#18225f;">No pending, no extra. Selected transactions are already settled.</span>
                    </div>
                </div>

                {{-- Payment Mode Box --}}
                <div class="v2PaymentModeWrap settlement-mode-box" style="display:none;">
                    <label for="settlementPaymentMode">Payment Mode <span class="text-danger">*</span></label>
                    <select class="form-select v2PaymentMode font-outfit" id="settlementPaymentMode">
                        <option value="">Choose payment mode</option>
                        <option value="1">Online</option>
                        <option value="2">Offline</option>
                    </select>
                </div>

                {{-- Bottom Action Buttons --}}
                <div class="settlement-actions-row">
                    <a href="{{ route('learners') }}" class="btn-settle-cancel">
                        <i class="fa-solid fa-xmark me-1"></i> Cancel
                    </a>
                    <button type="button" class="btn-settle-submit" id="btnSubmitSettlement">
                        <span class="submit-text">Pay the pending amount.</span>
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
$(document).ready(function() {
    const detailRows = @json($detailRows);
    const learnerId = {{ $learner->id }};
    const postUrl = '{{ route("learners.settlement", $learner->id) }}';

    const toNumber = (value) => {
        const parsed = parseFloat(String(value ?? 0).replace(/,/g, '').trim());
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const selectedTotals = () => {
        const ids = $('.v2DetailSelector:checked').map(function() { return Number($(this).val()); }).get();
        const selected = detailRows.filter(row => ids.includes(Number(row.id)));
        const sum = (key) => selected.reduce((amount, row) => amount + toNumber(row[key]), 0);

        return {
            ids,
            total: sum('total_amount'),
            paid: sum('paid_amount'),
            pending: sum('pending_amount'),
            extra: sum('extra_amount'),
        };
    };

    const updatePendingHelp = () => {
        const totals = selectedTotals();
        const net = Math.max(totals.pending - totals.extra, 0);
        const payAmount = toNumber($('.v2PayAmount').val());
        const remaining = Math.max(net - payAmount, 0);
        const help = remaining > 0
            ? `After this payment, ₹${remaining.toFixed(0)} will remain pending.`
            : 'After this payment, the account will be fully settled.';
        $('.v2PendingHelp').text(help);
    };

    const updateSettlementOption = () => {
        const option = $('.v2SettlementOption:checked').val() || 'amount';
        const caseType = $('.v2SettlementCase').val();

        if (caseType === 'pending') {
            $('.v2PendingPanel').toggle(option === 'amount');
            $('.v2PaymentModeWrap').toggle(option === 'amount');
            $('#btnSubmitSettlement .submit-text').text(option === 'amount'
                ? 'Pay the pending amount.'
                : 'Adjust Payment');
        } else if (caseType === 'extra') {
            $('.v2ExtraPanel').toggle(option === 'amount');
            $('.v2PaymentModeWrap').toggle(option === 'amount');
            $('#btnSubmitSettlement .submit-text').text(option === 'amount'
                ? 'Settle extra amount.'
                : 'Adjust Payment');
        }
    };

    const recalc = () => {
        const totals = selectedTotals();
        const net = totals.pending - totals.extra;
        const netAbs = Math.abs(net);

        $('#v2FullLearnerDelete').prop('checked', detailRows.length > 0 && totals.ids.length === detailRows.length);
        $('.v2Total').text('₹' + totals.total.toFixed(0));
        $('.v2Paid').text('₹' + totals.paid.toFixed(0));
        $('.v2Pending').text('₹' + totals.pending.toFixed(0));
        $('.v2Extra').text('₹' + totals.extra.toFixed(0));
        $('.v2NetAmount').text('₹' + netAbs.toFixed(0));
        $('.v2PendingPanel, .v2ExtraPanel, .v2SettledPanel, .v2SettlementOptionWrap').hide();

        const $submitBtn = $('#btnSubmitSettlement');
        $submitBtn.prop('disabled', false);

        if (totals.ids.length === 0) {
            $('.v2SettlementCase').val('none');
            $('.v2SettledPanel').hide();
            $('.v2PaymentModeWrap').hide();
            $submitBtn.find('.submit-text').text('Select a record to settle');
            $submitBtn.prop('disabled', true);
        } else if (net > 0) {
            $('.v2SettlementCase').val('pending');
            $('.v2SettlementOptionWrap, .v2PendingPanel, .v2PaymentModeWrap').show();
            $('.v2PayAmount').val(net.toFixed(0));
            $submitBtn.find('.submit-text').text('Pay the pending amount.');
            updatePendingHelp();
            updateSettlementOption();
        } else if (net < 0) {
            $('.v2SettlementCase').val('extra');
            $('.v2SettlementOptionWrap, .v2ExtraPanel, .v2PaymentModeWrap').show();
            $('.v2ExtraTitleAmount').text(netAbs.toFixed(0));
            $('.v2RefundAmount').val(netAbs.toFixed(0));
            $submitBtn.find('.submit-text').text('Settle extra amount.');
            updateSettlementOption();
        } else {
            $('.v2SettlementCase').val('settled');
            $('.v2SettledPanel').show();
            $('.v2PaymentModeWrap').hide();
            $submitBtn.find('.submit-text').text('Already settled');
        }
    };

    // Card click toggles selection
    $(document).on('click', '.settlement-plan-card', function(e) {
        if ($(e.target).is('input, select, textarea, label')) return;
        const $card = $(this);
        const $checkbox = $card.find('.v2DetailSelector');
        const isChecked = !$checkbox.prop('checked');
        $checkbox.prop('checked', isChecked).trigger('change');
    });

    $(document).on('change', '.v2DetailSelector', function() {
        const isChecked = $(this).is(':checked');
        $(this).closest('.settlement-plan-card').toggleClass('selected', isChecked);
        const totalRows = $('.v2DetailSelector').length;
        const selectedRows = $('.v2DetailSelector:checked').length;
        $('#v2FullLearnerDelete').prop('checked', totalRows > 0 && totalRows === selectedRows);
        recalc();
    });

    $(document).on('change', '#v2FullLearnerDelete', function() {
        const isAll = $(this).is(':checked');
        $('.v2DetailSelector').prop('checked', isAll).each(function() {
            $(this).closest('.settlement-plan-card').toggleClass('selected', isAll);
        });
        recalc();
    });

    $(document).on('click', '.settlement-choice-card', function() {
        $(this).find('.v2SettlementOption').prop('checked', true).trigger('change');
        $('.settlement-choice-card').removeClass('active');
        $(this).addClass('active');
    });

    $(document).on('input', '.v2PayAmount', function() {
        this.value = this.value.replace(/\D/g, '');
        updatePendingHelp();
    });

    $(document).on('input', '.v2RefundAmount', function() {
        this.value = this.value.replace(/\D/g, '');
    });

    $(document).on('change', '.v2SettlementOption', updateSettlementOption);

    // Initial calculation
    recalc();

    // Form Submission
    $('#btnSubmitSettlement').on('click', function(e) {
        e.preventDefault();

        const selectedIds = $('.v2DetailSelector:checked').map(function() { return Number($(this).val()); }).get();
        if (!selectedIds.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Selection Required',
                text: 'Please select at least one transaction card.',
                confirmButtonColor: '#18225f'
            });
            return false;
        }

        const selected = detailRows.filter(row => selectedIds.includes(Number(row.id)));
        const sum = (key) => selected.reduce((amount, row) => amount + toNumber(row[key]), 0);
        const pendingTotal = sum('pending_amount');
        const extraTotal = sum('extra_amount');
        const net = pendingTotal - extraTotal;
        const caseType = $('.v2SettlementCase').val();
        const paymentMode = $('.v2PaymentMode').val() || '';
        const payAmount = toNumber($('.v2PayAmount').val());
        const refundAmount = toNumber($('.v2RefundAmount').val());
        const settlementOption = $('.v2SettlementOption:checked').val() || 'amount';
        const isAdjustFull = settlementOption === 'adjust_full';
        const enteredAmount = caseType === 'extra' ? refundAmount : payAmount;

        if (caseType !== 'settled' && !isAdjustFull && !paymentMode && enteredAmount != 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Payment Mode Required',
                text: 'Please choose a payment mode to proceed.',
                confirmButtonColor: '#18225f'
            });
            $('.v2PaymentMode').focus();
            return false;
        }

        if (caseType === 'settled') {
            Swal.fire({
                icon: 'info',
                title: 'Already Settled',
                text: 'Selected transactions are already settled.',
                confirmButtonColor: '#18225f'
            });
            return false;
        }

        if (!isAdjustFull && caseType === 'pending' && (payAmount < 0 || payAmount > net)) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Amount',
                text: 'Please enter a valid pending amount (up to ₹' + net.toFixed(0) + ').',
                confirmButtonColor: '#18225f'
            });
            return false;
        }

        if (!isAdjustFull && caseType === 'extra' && (refundAmount < 0 || refundAmount > Math.abs(net))) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Amount',
                text: 'Please enter a valid refund amount (up to ₹' + Math.abs(net).toFixed(0) + ').',
                confirmButtonColor: '#18225f'
            });
            return false;
        }

        const adjust = isAdjustFull ? 1 : (caseType === 'extra'
            ? (($('.v2ExtraMode:checked').val() || 'refund_pending_future') === 'adjust' ? 1 : 0)
            : (($('.v2PendingMode:checked').val() || 'future') === 'adjust' ? 1 : 0));

        const postData = {
            _token: '{{ csrf_token() }}',
            learner_id: learnerId,
            learner_detail_id: selectedIds[0] || null,
            learner_detail_ids: selectedIds,
            adjust: adjust,
            refund_amount: !isAdjustFull && caseType === 'extra' ? refundAmount : 0,
            pending_amount: !isAdjustFull && caseType === 'pending' ? payAmount : 0,
            pay_amount: !isAdjustFull && caseType === 'pending' ? payAmount : 0,
            extra: extraTotal,
            payment_mode: paymentMode || '1',
            remark: ''
        };

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Processing...');

        $.ajax({
            url: postUrl,
            type: 'POST',
            data: postData,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Settlement Complete!',
                    text: response.message || 'Settlement processed successfully.',
                    confirmButtonText: 'Done',
                    confirmButtonColor: '#18225f'
                }).then(() => {
                    window.location.href = '{{ route("learners") }}';
                });
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<span class="submit-text">' + ($('.v2SettlementCase').val() === 'pending' ? 'Pay the pending amount.' : 'Settle extra amount.') + '</span>');
                Swal.fire({
                    icon: 'error',
                    title: 'Settlement Failed',
                    text: xhr?.responseJSON?.message || xhr?.responseJSON?.error || 'Settlement failed. Please try again.',
                    confirmButtonColor: '#18225f'
                });
            }
        });
    });
});
</script>

@endsection
