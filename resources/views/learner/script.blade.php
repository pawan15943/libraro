<link rel="stylesheet" href="{{ asset('public/css/settlement-modal.css') }}?v={{ time() }}">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function openLearnerSettlement(learnerId, learnerDetailId) {
        const trigger = $('<a href="javascript:void(0)" class="settlement-learner"></a>');
        trigger.attr('data-id', learnerId);
        trigger.attr('data-learnerdetail', learnerDetailId || '');
        trigger.data('id', learnerId);
        trigger.data('learnerdetail', learnerDetailId || '');
        $('body').append(trigger);
        trigger.trigger('click');
        setTimeout(() => trigger.remove(), 1000);
    }

    function getAjaxMessage(xhr, fallback) {
        return xhr?.responseJSON?.message || xhr?.responseJSON?.error || fallback;
    }

    function showSettlementRequired(xhr, learnerId, learnerDetailId, actionText) {
        const message = getAjaxMessage(xhr, `Please settle this learner before ${actionText}.`);
        const needsSettlement = /pending amount|extra amount|settle/i.test(message || '');

        if (!needsSettlement) {
            Swal.fire('Error!', message, 'error');
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: `Settlement required before ${actionText}`,
            html: `
                <div style="text-align:left;">
                    <div style="background:#ffe8e8;color:#d80000;border-radius:8px;padding:12px 14px;font-weight:600;margin-bottom:12px;">
                        ${message}
                    </div>
                    <p style="margin:0;color:#555;">
                        Please settle the pending or extra amount first. After settlement, you can continue the ${actionText} process as usual.
                    </p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Settle Now',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#050a78',
            cancelButtonColor: '#d00000'
        }).then((result) => {
            if (result.isConfirmed) {
                openLearnerSettlement(learnerId, learnerDetailId);
            }
        });
    }

    $(document).on('click', '.settlement-learner', async function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        const learnerId = $(this).data('id');
        const fallbackDetailId = parseInt($(this).data('learnerdetail'), 10) || null;
        const postUrl = '{{ route("learners.settlement", ":id") }}'.replace(':id', learnerId);
        const detailsUrl = '{{ route("learners.settlement.details", ":id") }}'.replace(':id', learnerId);
        const toNumber = (value) => {
            const parsed = parseFloat(String(value ?? 0).replace(/,/g, '').trim());
            return Number.isFinite(parsed) ? parsed : 0;
        };

        // Smooth modern initial loader
        Swal.fire({
            html: `
                <div class="settlement-smooth-loader">
                    <div class="smooth-spinner"></div>
                    <p class="smooth-loader-text">Loading settlement details...</p>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: false,
            allowOutsideClick: false,
            customClass: {
                popup: 'settlement-popup-modal settlement-loader-popup'
            }
        });

        let detailsResponse = null;
        try {
            detailsResponse = await $.ajax({ url: detailsUrl, type: 'GET' });
        } catch (xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: xhr?.responseJSON?.error || 'Unable to load learner details.',
                confirmButtonColor: '#18225f',
                customClass: { popup: 'settlement-popup-modal', confirmButton: 'settlement-confirm-btn' }
            });
            return false;
        }

        const detailRows = Array.isArray(detailsResponse?.details) ? detailsResponse.details : [];
        if (!detailRows.length) {
            Swal.fire({
                icon: 'info',
                title: 'No Records',
                text: 'No active transaction details found for this learner.',
                confirmButtonColor: '#18225f',
                customClass: { popup: 'settlement-popup-modal', confirmButton: 'settlement-confirm-btn' }
            });
            return false;
        }

        const learnerName = detailsResponse?.learner?.name || '';
        const cardsHtml = detailRows.map((row) => {
            const total = toNumber(row.total_amount || row.paid_amount);
            const paid = toNumber(row.paid_amount);
            const pending = toNumber(row.pending_amount);
            const extra = toNumber(row.extra_amount);
            const checked = detailRows.length > 1
                ? 'checked'
                : (fallbackDetailId && Number(row.id) === Number(fallbackDetailId) ? 'checked' : '');

            return `
                <div class="settlement-plan-card ${checked ? 'selected' : ''}" data-id="${row.id}">
                    <input type="checkbox" class="v2DetailSelector d-none" value="${row.id}" ${checked}>
                    <div class="settlement-card-header">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="settlement-plan-badge">${row.plan_name || 'Plan'}</span>
                            ${row.seat_no ? `<span class="settlement-seat-badge"><i class="fa-solid fa-chair me-1"></i>Seat ${row.seat_no}</span>` : ''}
                            ${row.plan_type_name && row.plan_type_name !== 'N/A' ? `<span class="badge bg-white text-dark border font-outfit px-2 py-1" style="font-size:0.72rem;">${row.plan_type_name}</span>` : ''}
                            <span class="settlement-date-badge font-outfit">
                                <i class="fa-regular fa-calendar me-1"></i>${row.plan_start_date || ''} &rarr; ${row.plan_end_date || ''}
                            </span>
                        </div>
                        <div class="settlement-check-circle">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </div>
                    <div class="settlement-card-metrics">
                        <div class="metric-item">
                            <span class="metric-label">Plan Price</span>
                            <span class="metric-value">₹${total.toFixed(0)}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Paid</span>
                            <span class="metric-value text-dark">₹${paid.toFixed(0)}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Pending</span>
                            <span class="metric-value ${pending > 0 ? 'text-danger fw-bold' : ''}">₹${pending.toFixed(0)}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Extra</span>
                            <span class="metric-value ${extra > 0 ? 'text-success fw-bold' : ''}">₹${extra.toFixed(0)}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        const result = await Swal.fire({
            title: '',
            customClass: {
                popup: 'settlement-popup-modal',
                confirmButton: 'settlement-confirm-btn',
                cancelButton: 'settlement-cancel-btn'
            },
            showCancelButton: true,
            confirmButtonText: 'Pay the pending amount.',
            cancelButtonText: 'Cancel',
            buttonsStyling: false,
            html: `
                <div class="settlement-popup">
                    <div class="settlement-modal-title">
                        <i class="fa-solid fa-scale-balanced" style="color: #18225f; font-size: 1.25rem;"></i>
                        <span>Settlement</span>
                    </div>
                    ${learnerName ? `<div class="settlement-modal-subtitle">${learnerName}</div>` : ''}

                    <!-- Interactive Transaction Cards -->
                    <div class="settlement-cards-container text-start">
                        ${cardsHtml}
                    </div>

                    <!-- Select All Toggle -->
                    <div class="d-flex align-items-center justify-content-between mt-2 px-1 ${detailRows.length > 1 ? '' : 'd-none'}">
                        <label class="form-check d-inline-flex align-items-center gap-2 cursor-pointer mb-0 user-select-none">
                            <input type="checkbox" class="form-check-input" id="v2FullLearnerDelete" style="width:16px;height:16px;cursor:pointer;">
                            <span class="small font-outfit fw-bold text-dark">Select all transactions</span>
                        </label>
                        <span class="small text-muted font-outfit">${detailRows.length} transaction${detailRows.length > 1 ? 's' : ''}</span>
                    </div>

                    <!-- Dynamic Summary Grid -->
                    <div class="row g-2 settlement-summary-grid text-start">
                        <div class="col-6 col-md-3">
                            <div class="settlement-stat-card">
                                <span class="stat-label"><i class="fa-solid fa-receipt me-1"></i> Total Paid</span>
                                <strong class="stat-val v2Paid">₹0</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="settlement-stat-card card-pending">
                                <span class="stat-label"><i class="fa-solid fa-clock-rotate-left me-1"></i> Pending</span>
                                <strong class="stat-val v2Pending">₹0</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="settlement-stat-card card-extra">
                                <span class="stat-label"><i class="fa-solid fa-circle-plus me-1"></i> Extra</span>
                                <strong class="stat-val v2Extra">₹0</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="settlement-stat-card card-net">
                                <span class="stat-label"><i class="fa-solid fa-wallet me-1"></i> Net Amount</span>
                                <strong class="stat-val v2NetAmount">₹0</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Note -->
                    <div class="settlement-note-banner text-start">
                        <i class="fa-solid fa-circle-info text-primary"></i>
                        <span>The above amounts change according to the selected transaction. Please check carefully before settling.</span>
                    </div>

                    <input type="hidden" class="v2SettlementCase" value="settled">
                    <div class="mt-2 text-start">
                        <!-- Settle with Amount vs Adjust full Amount -->
                        <div class="settlement-choice-row mb-2 v2SettlementOptionWrap" style="display:none;">
                            <label class="settlement-choice-card active" for="v2SettleWithAmount">
                                <input class="form-check-input v2SettlementOption" type="radio" name="v2SettlementOption" value="amount" id="v2SettleWithAmount" checked>
                                <span>Settle with Amount</span>
                            </label>
                            <label class="settlement-choice-card" for="v2AdjustFullAmount">
                                <input class="form-check-input v2SettlementOption" type="radio" name="v2SettlementOption" value="adjust_full" id="v2AdjustFullAmount">
                                <span>Adjust full Amount</span>
                            </label>
                        </div>

                        <!-- Pending Panel -->
                        <div class="v2PendingPanel settlement-action-panel pending" style="display:none;">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" checked>
                                <label class="form-check-label fw-bold font-outfit text-danger">Pay pending amount</label>
                            </div>
                            <div class="settlement-amount-input-wrap mb-2">
                                <span class="currency-symbol">₹</span>
                                <input type="text" class="form-control form-control-sm v2PayAmount" placeholder="0">
                            </div>
                            <div class="form-check mt-2 mb-1">
                                <input class="form-check-input v2PendingMode" type="radio" name="v2PendingMode" value="future" id="v2PendingFuture" checked>
                                <label class="form-check-label font-outfit" for="v2PendingFuture">The remaining amount will be collected in the future.</label>
                            </div>
                            <div class="form-check mt-1">
                                <input class="form-check-input v2PendingMode" type="radio" name="v2PendingMode" value="adjust" id="v2PendingAdjust">
                                <label class="form-check-label font-outfit" for="v2PendingAdjust">Or, adjust / settle remaining amt. now</label>
                            </div>
                            <div class="small text-muted mt-2 font-outfit fw-medium v2PendingHelp"></div>
                        </div>

                        <!-- Extra Panel -->
                        <div class="v2ExtraPanel settlement-action-panel extra" style="display:none;">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" checked>
                                <label class="form-check-label fw-bold font-outfit text-success">Settle extra ₹<span class="v2ExtraTitleAmount">0</span></label>
                            </div>
                            <div class="settlement-amount-input-wrap mb-2">
                                <span class="currency-symbol">₹</span>
                                <input type="text" class="form-control form-control-sm v2RefundAmount" placeholder="0">
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

                        <!-- Already Settled Panel -->
                        <div class="v2SettledPanel settlement-action-panel" style="display:none;background:#eff6ff;border:1.5px solid #bfdbfe;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check text-success fs-5"></i>
                                <span class="fw-bold font-outfit" style="color:#18225f;">No pending, no extra. Selected transactions are already settled.</span>
                            </div>
                        </div>

                        <!-- Payment Mode Box -->
                        <div class="mt-2 v2PaymentModeWrap settlement-mode-box" style="display:none;">
                            <label class="form-label mb-1">Payment Mode</label>
                            <select class="form-select form-select-sm v2PaymentMode font-outfit">
                                <option value="">Choose payment mode</option>
                                <option value="1">Online</option>
                                <option value="2">Offline</option>
                            </select>
                        </div>
                    </div>
                </div>
            `,
            didOpen: () => {
                const popup = Swal.getPopup();
                if (popup) {
                    popup.style.width = '';
                }

                const selectedTotals = () => {
                    const ids = $(popup).find('.v2DetailSelector:checked').map(function () { return Number($(this).val()); }).get();
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
                    const payAmount = toNumber($(popup).find('.v2PayAmount').val());
                    const remaining = Math.max(net - payAmount, 0);
                    const help = remaining > 0
                        ? `After this payment, ₹${remaining.toFixed(0)} will remain pending.`
                        : 'After this payment, the account will be fully settled.';
                    $(popup).find('.v2PendingHelp').text(help);
                };

                const updateSettlementOption = () => {
                    const option = $(popup).find('.v2SettlementOption:checked').val() || 'amount';
                    const caseType = $(popup).find('.v2SettlementCase').val();

                    if (caseType === 'pending') {
                        $(popup).find('.v2PendingPanel').toggle(option === 'amount');
                        $(popup).find('.v2PaymentModeWrap').toggle(option === 'amount');
                        Swal.getConfirmButton().textContent = option === 'amount'
                            ? 'Pay the pending amount.'
                            : 'Adjust Payment';
                    } else if (caseType === 'extra') {
                        $(popup).find('.v2ExtraPanel').toggle(option === 'amount');
                        $(popup).find('.v2PaymentModeWrap').toggle(option === 'amount');
                        Swal.getConfirmButton().textContent = option === 'amount'
                            ? 'Settle extra amount.'
                            : 'Adjust Payment';
                    }
                };

                const recalc = () => {
                    const totals = selectedTotals();
                    const net = totals.pending - totals.extra;
                    const netAbs = Math.abs(net);

                    $(popup).find('#v2FullLearnerDelete').prop('checked', detailRows.length > 0 && totals.ids.length === detailRows.length);
                    $(popup).find('.v2Total').text('₹' + totals.total.toFixed(0));
                    $(popup).find('.v2Paid').text('₹' + totals.paid.toFixed(0));
                    $(popup).find('.v2Pending').text('₹' + totals.pending.toFixed(0));
                    $(popup).find('.v2Extra').text('₹' + totals.extra.toFixed(0));
                    $(popup).find('.v2NetAmount').text('₹' + netAbs.toFixed(0));
                    $(popup).find('.v2PendingPanel,.v2ExtraPanel,.v2SettledPanel,.v2SettlementOptionWrap').hide();

                    if (totals.ids.length === 0) {
                        $(popup).find('.v2SettlementCase').val('none');
                        $(popup).find('.v2SettledPanel').hide();
                        $(popup).find('.v2PaymentModeWrap').hide();
                        const confirmBtn = Swal.getConfirmButton();
                        if (confirmBtn) {
                            confirmBtn.textContent = 'Select a record to settle';
                            confirmBtn.disabled = true;
                        }
                    } else if (net > 0) {
                        const confirmBtn = Swal.getConfirmButton();
                        if (confirmBtn) confirmBtn.disabled = false;
                        $(popup).find('.v2SettlementCase').val('pending');
                        $(popup).find('.v2SettlementOptionWrap,.v2PendingPanel,.v2PaymentModeWrap').show();
                        $(popup).find('.v2PayAmount').val(net.toFixed(0));
                        Swal.getConfirmButton().textContent = `Pay the pending amount.`;
                        updatePendingHelp();
                        updateSettlementOption();
                    } else if (net < 0) {
                        const confirmBtn = Swal.getConfirmButton();
                        if (confirmBtn) confirmBtn.disabled = false;
                        $(popup).find('.v2SettlementCase').val('extra');
                        $(popup).find('.v2SettlementOptionWrap,.v2ExtraPanel,.v2PaymentModeWrap').show();
                        $(popup).find('.v2ExtraTitleAmount').text(netAbs.toFixed(0));
                        $(popup).find('.v2RefundAmount').val(netAbs.toFixed(0));
                        Swal.getConfirmButton().textContent = `Settle extra amount.`;
                        updateSettlementOption();
                    } else {
                        const confirmBtn = Swal.getConfirmButton();
                        if (confirmBtn) confirmBtn.disabled = false;
                        $(popup).find('.v2SettlementCase').val('settled');
                        $(popup).find('.v2SettledPanel').show();
                        $(popup).find('.v2PaymentModeWrap').hide();
                        Swal.getConfirmButton().textContent = 'Already settled';
                    }
                };

                // Click on card toggles selection
                $(popup).on('click', '.settlement-plan-card', function (e) {
                    if ($(e.target).is('input, select, textarea, label')) return;
                    const $card = $(this);
                    const $checkbox = $card.find('.v2DetailSelector');
                    const isChecked = !$checkbox.prop('checked');
                    $checkbox.prop('checked', isChecked).trigger('change');
                });

                $(popup).on('change', '.v2DetailSelector', function () {
                    const isChecked = $(this).is(':checked');
                    $(this).closest('.settlement-plan-card').toggleClass('selected', isChecked);
                    const totalRows = $(popup).find('.v2DetailSelector').length;
                    const selectedRows = $(popup).find('.v2DetailSelector:checked').length;
                    $(popup).find('#v2FullLearnerDelete').prop('checked', totalRows > 0 && totalRows === selectedRows);
                    recalc();
                });

                $(popup).on('change', '#v2FullLearnerDelete', function () {
                    const isAll = $(this).is(':checked');
                    $(popup).find('.v2DetailSelector').prop('checked', isAll).each(function () {
                        $(this).closest('.settlement-plan-card').toggleClass('selected', isAll);
                    });
                    recalc();
                });

                $(popup).on('click', '.settlement-choice-card', function () {
                    $(this).find('.v2SettlementOption').prop('checked', true).trigger('change');
                    $(popup).find('.settlement-choice-card').removeClass('active');
                    $(this).addClass('active');
                });

                $(popup).on('input', '.v2PayAmount', updatePendingHelp);
                $(popup).on('change', '.v2SettlementOption', updateSettlementOption);
                recalc();
            },
            preConfirm: () => {
                const selectedIds = $('.v2DetailSelector:checked').map(function () { return Number($(this).val()); }).get();
                if (!selectedIds.length) {
                    Swal.showValidationMessage('Please select at least one transaction card.');
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
                    Swal.showValidationMessage('Please choose payment mode.');
                    return false;
                }

                if (caseType === 'settled') {
                    Swal.showValidationMessage('Selected transactions are already settled.');
                    return false;
                }

                if (!isAdjustFull && caseType === 'pending' && (payAmount < 0 || payAmount > net)) {
                    Swal.showValidationMessage('Please enter a valid pending amount.');
                    return false;
                }

                if (!isAdjustFull && caseType === 'extra' && (refundAmount < 0 || refundAmount > Math.abs(net))) {
                    Swal.showValidationMessage('Please enter a valid refund amount.');
                    return false;
                }

                const adjust = isAdjustFull ? 1 : (caseType === 'extra'
                    ? (($('.v2ExtraMode:checked').val() || 'refund_pending_future') === 'adjust' ? 1 : 0)
                    : (($('.v2PendingMode:checked').val() || 'future') === 'adjust' ? 1 : 0));

                return {
                    selectedIds,
                    adjust,
                    paymentMode: paymentMode || '1',
                    pendingAmount: !isAdjustFull && caseType === 'pending' ? payAmount : 0,
                    refundAmount: !isAdjustFull && caseType === 'extra' ? refundAmount : 0,
                    isRefund: !isAdjustFull && caseType === 'extra' && refundAmount > 0 ? 1 : 0,
                    extra: extraTotal,
                };
            }
        });

        if (!result.isConfirmed) {
            return false;
        }

        // Smooth processing animation on submission
        Swal.fire({
            html: `
                <div class="settlement-smooth-loader">
                    <div class="smooth-spinner"></div>
                    <p class="smooth-loader-text">Processing settlement payment...</p>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: false,
            allowOutsideClick: false,
            customClass: {
                popup: 'settlement-popup-modal settlement-loader-popup'
            }
        });

        $.ajax({
            url: postUrl,
            type: 'POST',
            data: $.extend({ _token: '{{ csrf_token() }}' }, {
                learner_id: learnerId,
                learner_detail_id: result.value.selectedIds[0] || null,
                learner_detail_ids: result.value.selectedIds,
                adjust: result.value.adjust,
                refund_amount: result.value.refundAmount,
                pending_amount: result.value.pendingAmount,
                pay_amount: result.value.pendingAmount,
                extra: result.value.extra,
                payment_mode: result.value.paymentMode,
                remark: ''
            }),
            success: function (response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Settlement Complete!',
                    text: response.message || 'Settlement processed successfully.',
                    confirmButtonText: 'Great, Done',
                    confirmButtonColor: '#18225f',
                    customClass: { popup: 'settlement-popup-modal', confirmButton: 'settlement-confirm-btn' }
                }).then(() => location.reload());
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Settlement Failed',
                    text: xhr?.responseJSON?.error || 'Settlement failed. Please try again.',
                    confirmButtonColor: '#18225f',
                    customClass: { popup: 'settlement-popup-modal', confirmButton: 'settlement-confirm-btn' }
                });
            }
        });

        return false;
    });
    // soft delete Learner 
    $(document).on('click', '.delete-customer', async function () {
        var id = $(this).data('id');
        var learnerDetail = $(this).data('learnerdetail');
        var seat = $(this).data('seat');
        var paybleRefund = parseFloat($(this).data('payblerefund'));

        var url = '{{ route('learners.destroy', ':id') }}';
        url = url.replace(':id', id);
        var detailsUrl = '{{ route("learners.settlement.details", ":id") }}'.replace(':id', id);

        var formId = 'deleteSeat';
        var fieldName = 'seat';
        var newValue = seat;
        var oldValue = seat;

        const toDeleteNumber = (value) => {
            const parsed = parseFloat(String(value ?? 0).replace(/,/g, '').trim());
            return Number.isFinite(parsed) ? parsed : 0;
        };

        try {
            const detailsResponse = await $.ajax({ url: detailsUrl, type: 'GET' });
            const detailRows = Array.isArray(detailsResponse?.details) ? detailsResponse.details : [];
            const pendingTotal = detailRows.reduce((amount, row) => amount + toDeleteNumber(row.pending_amount), 0);
            const extraTotal = detailRows.reduce((amount, row) => amount + toDeleteNumber(row.extra_amount), 0);
            const netAmount = pendingTotal - extraTotal;

            if (netAmount > 0) {
                showSettlementRequired({
                    responseJSON: {
                        message: `This member has a pending amount(${netAmount}). Please settle it before delete`
                    }
                }, id, learnerDetail, 'delete');
                return false;
            }

            if (netAmount < 0) {
                showSettlementRequired({
                    responseJSON: {
                        message: `This member has an extra amount(${Math.abs(netAmount)}). Please settle it before delete`
                    }
                }, id, learnerDetail, 'delete');
                return false;
            }
        } catch (xhr) {
            Swal.fire('Error!', getAjaxMessage(xhr, 'Unable to check learner settlement details.'), 'error');
            return false;
        }

        Swal.fire({
            title: 'Are you sure you want to delete this Record?',
           
            html: `
            <p style="margin-bottom:10px;">
                Deleting this seat will not remove it permanently. It will remain visible in the learner’s history.
            </p>
            <div style="background:#ffe8e8;color:#d80000;border-radius:8px;padding:12px 14px;font-weight:600;margin-bottom:14px;text-align:left;">
                This is a Temporary delete operation and can be reverted later from Learner History. Once deleted, this learner will be moved to Learner History.
            </div>
                <div class="row g-4 delete">
                    <div class="col-lg-12 text-start">
                        <label>Delete Option</label>
                        <select class="form-control refundChoice">
                            <option value="">Choose</option>
                            <option value="with_refund">Proceed with Refund</option>
                            <option value="without_refund">Proceed without Refund</option>
                        </select>
                    </div>
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Total Amt.</label>
                        <input type="text" placeholder="Refund Amount" class="form-control paybleRefund" value="${paybleRefund ?? ''}" readonly>
                    </div>
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Pay Refund Amt.</label>
                        <input type="text" placeholder="Enter Amount" class="form-control refundAmount digit-only" maxlength='4'>
                    </div>
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Pending Refund Amt.</label>
                        <input type="text" placeholder="Enter Amount" class="form-control digit-only pendingRefund" maxlength='4'>
                    </div>
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Payment Mode</label>
                        <select class="form-control refundPaymentMode">
                            <option value="">Select Payment Mode</option>
                            <option value="1">Online</option>
                            <option value="2">Offline</option>
                            <option value="3">Pay Later</option>
                        </select>
                    </div>
                    <div class="col-lg-12 refundAmountDiv" style="display:none;">
                        <label>Remark</label>
                        <textarea class="form-control refundRemark" cols="30" rows="3"></textarea>
                    </div>
                </div>
            `,
            iconHtml: '<i class="fas fa-trash-alt fa-3x" style="color:red;font-size:40px;"></i>',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            didOpen: () => {

                let popup = Swal.getPopup();

                $(popup).find('.refundType').on('change', function () {

                    // Make checkboxes exclusive
                    if ($(this).is(':checked')) {
                        $(popup).find('.refundType').not(this).prop('checked', false);
                    }

                    // If Refund selected → show all refundAmountDiv
                    if ($(popup).find('.isRefund').is(':checked')) {
                        $(popup).find('.refundAmountDiv').css('display', 'block');
                    } else {
                        // Otherwise hide
                        $(popup).find('.refundAmountDiv').css('display', 'none');
                    }
                });

                $(popup).find('.refundChoice').on('change', function () {
                    if ($(this).val() === 'with_refund') {
                        $(popup).find('.refundAmountDiv').css('display', 'block');
                    } else {
                        $(popup).find('.refundAmountDiv').css('display', 'none');
                    }
                });

            },

            preConfirm: () => {
                const refundChoice = $('.refundChoice').val();
                const isRefund = refundChoice === 'with_refund';

                // ⭐ REQUIRED VALIDATION — at least ONE must be selected
                if (!refundChoice) {
                    Swal.showValidationMessage('Please choose delete option');
                    return false;
                }
                let refundValue = $('.refundAmount').val();
                let refundAmount = parseFloat(refundValue);

                const remark = $('.refundRemark').val();
                const pendingRefund = parseFloat($('.pendingRefund').val()) || 0;
                const paymentMode = $('.refundPaymentMode').val();


                if (isRefund && (refundValue === "" || isNaN(refundAmount) || refundAmount < 0 || refundAmount > paybleRefund)) {
                    Swal.showValidationMessage('Please enter a valid refund amount');
                    return false;
                }
                if (isRefund && refundAmount !== paybleRefund && (pendingRefund < 0 || (pendingRefund+refundAmount) > paybleRefund)) {
                    Swal.showValidationMessage('Please enter a valid pending refund amount');
                    return false;
                }
                if (isRefund && !paymentMode) {
                    Swal.showValidationMessage('Please choose payment mode');
                    return false;
                }

                return {
                    isRefund: isRefund,
                    paybleRefund: $('.paybleRefund').val(),
                    refundAmount: refundAmount,
                    pendingRefund: $('.pendingRefund').val(),
                    paymentMode: paymentMode,
                    remark: remark,
                };
            }
        }).then((result) => {

            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}',
                        learnerDetail: learnerDetail,
                        payment_mode: result.value.paymentMode,
                        isRefund: result.value.isRefund ? 1 : 0,
                        paybleRefund: result.value.paybleRefund,
                        refundAmount: result.value.refundAmount,
                        pendingRefund: result.value.pendingRefund,
                        remark: result.value.remark
                    },
                    success: function (response) {
                        console.log(response);
                        Swal.fire('Deleted!', 'Learner has been deleted.', 'success').then(() => {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        showSettlementRequired(xhr, id, learnerDetail, 'delete');
                    }
                });
            }
        });
    });
    // permanent delete Learner 
    $(document).on('click', '.delete-permanent-customer', function () {
        let id = $(this).data('id');
        let learnerDetail = $(this).data('learnerdetail');
        let permanent = '1';
        var seat = $(this).data('seat');
        let url = '{{ route("learners.destroy", ":id") }}'.replace(':id', id);
         var formId = 'deleteSeat';
        var fieldName = 'seat';
        var newValue = seat;
        var oldValue = seat;
        Swal.fire({
            title: 'Are you sure you want to permanently delete this Record?',
             html: `
            <p style="margin-bottom:10px;">Proceeding will permanently remove the learner record from the system.</p>
            
            <div style="text-align:left;">
                <div class="form-check">
                    <input class="form-check-input delete-all-yes" type="checkbox" id="deleteAllYes">
                    <label class="form-check-label" for="deleteAllYes"> Delete all past transactions with their revenue records. </label>
                </div>

                <div class="form-check">
                    <input class="form-check-input delete-all-no" type="checkbox" id="deleteAllNo">
                    <label class="form-check-label" for="deleteAllNo">Delete all past transactions without deleting the revenue records.</label>
                </div>

                <small class="text-danger required-msg" style="display:none;">
                    Please select one option.
                </small>
            </div>
        `,
            iconHtml: '<i class="fas fa-trash-alt fa-3x" style="color:red;font-size:40px;"></i>',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            didOpen: () => {

                // Make checkboxes mutually exclusive
                $('.delete-all-yes').change(function () {
                    if ($(this).is(':checked')) {
                        $('.delete-all-no').prop('checked', false);
                    }
                });

                $('.delete-all-no').change(function () {
                    if ($(this).is(':checked')) {
                        $('.delete-all-yes').prop('checked', false);
                    }
                });
            },

            preConfirm: () => {

                let yesChecked = $('.delete-all-yes').is(':checked');
                let noChecked = $('.delete-all-no').is(':checked');

                if (!yesChecked && !noChecked) {
                    $('.required-msg').show();
                    Swal.showValidationMessage('You must choose YES or NO');
                    return false;
                }

                return {
                    deleteAll: yesChecked ? 1 : 0,
                    learnerDetail: learnerDetail
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}',
                        learnerDetail: result.value.learnerDetail,
                        permanent: permanent,
                        deleteAll: result.value.deleteAll
                    },
                    success: function (response) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Learner has been Permanent deleted successfully.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload(); // Hard refresh after success
                        });
                    },
                    error: function (xhr) {
                        Swal.fire('Error!', 'An error occurred while deleting the learner.', 'error');
                    }
                });
            }
        });
    });

    // Learner Close Plan Form
    $(document).on('click', '.link-close-plan', async function() {
       var learner_id = $(this).data('id');
        var learnerDetail = $(this).data('learnerdetail');
        var paybleRefund = parseFloat($(this).data('payblerefund'));
        var url = '{{ route('learners.close') }}'; // Adjust the route as necessary
        var detailsUrl = '{{ route("learners.settlement.details", ":id") }}'.replace(':id', learner_id);
        var oldValue=this.getAttribute('data-plan_end_date');
        var formId='closeSeat';
        var fieldName='plan_end_date';
        var today = new Date();
        var year = today.getFullYear();
        var month = String(today.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
        var day = String(today.getDate()).padStart(2, '0');
        var newValue = `${year}-${month}-${day}`;

        const toCloseNumber = (value) => {
            const parsed = parseFloat(String(value ?? 0).replace(/,/g, '').trim());
            return Number.isFinite(parsed) ? parsed : 0;
        };

        try {
            const detailsResponse = await $.ajax({ url: detailsUrl, type: 'GET' });
            const detailRows = Array.isArray(detailsResponse?.details) ? detailsResponse.details : [];
            const pendingTotal = detailRows.reduce((amount, row) => amount + toCloseNumber(row.pending_amount), 0);
            const extraTotal = detailRows.reduce((amount, row) => amount + toCloseNumber(row.extra_amount), 0);
            const netAmount = pendingTotal - extraTotal;

            if (netAmount > 0) {
                showSettlementRequired({
                    responseJSON: {
                        message: `This member has a pending amount(${netAmount}). Please settle it before close`
                    }
                }, learner_id, learnerDetail, 'close');
                return false;
            }

            if (netAmount < 0) {
                showSettlementRequired({
                    responseJSON: {
                        message: `This member has an extra amount(${Math.abs(netAmount)}). Please settle it before close`
                    }
                }, learner_id, learnerDetail, 'close');
                return false;
            }
        } catch (xhr) {
            Swal.fire('Error!', getAjaxMessage(xhr, 'Unable to check learner settlement details.'), 'error');
            return false;
        }
       
        Swal.fire({
            title: 'Are you sure you want to close this Seat?',
            html: `
                <div class="row g-4 delete">
                    <div class="col-lg-12">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input refundType isRefund" type="checkbox" id="refundYes">
                            <label class="form-check-label" for="refundYes">Proceed with Refund</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input refundType refundNo" type="checkbox" value="without_refund" id="refundNo">
                            <label class="form-check-label" for="refundNo">Proceed without Refund</label>
                        </div>
                    </div>
                
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Total Amt.</label>
                        <input type="text" placeholder="Refund Amount" class="form-control paybleRefund digit-only" value="${paybleRefund ?? ''}" readonly >
                    </div>
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Pay Refund Amt.</label>
                        <input type="text" placeholder="Enter Amount" class="form-control refundAmount digit-only" maxlength="4" >
                    </div>
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Pending Refund Amt.</label>
                        <input type="text" placeholder="Enter Amount" class="form-control pendingRefund digit-only" maxlength="4" >
                    </div>
                    <div class="col-lg-6 refundAmountDiv" style="display:none;">
                        <label>Payment Mode</label>
                        <select class="form-control refundPaymentMode">
                            <option value="">Select Payment Mode</option>
                            <option value="1">Online</option>
                            <option value="2">Offline</option>
                            <option value="3">Pay Later</option>
                        </select>
                    </div>
                    <div class="col-lg-12 refundAmountDiv" style="display:none;">
                        <label>Remark</label>
                        <textarea class="form-control refundRemark" cols="30" rows="3" style="height:auto !important;"></textarea>
                    </div>
                </div>
            `,
            iconHtml: '<i class="fas fa-times fa-3x" style="color:red;font-size:40px;"></i>',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Close it!',
           didOpen: () => {

                let popup = Swal.getPopup();

                $(popup).find('.refundType').on('change', function () {

                    // Make checkboxes exclusive
                    if ($(this).is(':checked')) {
                        $(popup).find('.refundType').not(this).prop('checked', false);
                    }

                    // If Refund selected → show all refundAmountDiv
                    if ($(popup).find('.isRefund').is(':checked')) {
                        $(popup).find('.refundAmountDiv').css('display', 'block');
                    } else {
                        // Otherwise hide
                        $(popup).find('.refundAmountDiv').css('display', 'none');
                    }
                });
            },
            preConfirm: () => {
               const isRefund = $('.isRefund').is(':checked');
               const withoutRefundSelected = $('.refundNo').is(':checked');
                let refundValue = $('.refundAmount').val();
                let refundAmount = parseFloat(refundValue);
                const remark = $('.refundRemark').val();
                const pendingRefund = parseFloat($('.pendingRefund').val()) || 0;
                const paymentMode = $('.refundPaymentMode').val();

                 // ⭐ REQUIRED VALIDATION — at least ONE must be selected
                if (!isRefund && !withoutRefundSelected) {
                    Swal.showValidationMessage('Please select one from Refund or Without Refund');
                    return false;
                }
                if (isRefund && (refundValue === "" || isNaN(refundAmount) || refundAmount < 0 || refundAmount > paybleRefund)) {
                    Swal.showValidationMessage('Please enter a valid refund amount');
                    return false;
                }
                if (isRefund && refundAmount !== paybleRefund && (pendingRefund < 0 || (pendingRefund+refundAmount) > paybleRefund)) {
                    Swal.showValidationMessage('Please enter a valid pending refund amount');
                    return false;
                }
                if (isRefund && !paymentMode) {
                    Swal.showValidationMessage('Please choose payment mode');
                    return false;
                }

                return {
                    isRefund: isRefund,
                    paybleRefund: $('.paybleRefund').val(),
                    refundAmount: refundAmount,
                    pendingRefund: $('.pendingRefund').val(),
                    paymentMode: paymentMode,
                    remark: remark,
                    learner_id: learner_id,
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        learner_id: result.value.learner_id,
                        learnerDetail: learnerDetail,
                        payment_mode: result.value.paymentMode,
                        isRefund: result.value.isRefund ? 1 : 0,
                        paybleRefund: result.value.paybleRefund,
                        refundAmount: result.value.refundAmount,
                        pendingRefund: result.value.pendingRefund,
                        remark: result.value.remark
                    },
                    success: function (response) {
                         Swal.fire('Closed!', response.success, 'success').then(() => {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        showSettlementRequired(xhr, learner_id, learnerDetail, 'close');
                    }
                });
            }
        });
    });

    $(document).on('click', '.restore-customer', function (e) {
   
        e.preventDefault();
        var learnerDetail = $(this).data('learnerdetail');
        var id = $(this).data('id');
        var formId = 'restoreSeat';
        var fieldName = 'seat';
        var seat = $(this).data('seat');
        var newValue = seat;
        var oldValue = seat;
        var learnerDetailId = $(this).data('learnerdetail');
        var url = "{{ route('learners.restore') }}"; // POST route for restore

        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to restore this learner record.",
            iconHtml: '<i class="fas fa-trash-restore fa-3x" style="color:#3085d6;font-size:40px;"></i>',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, restore it!',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        learner_detail_id: learnerDetailId
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Restored!',
                                text: response.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload(); // Hard refresh (you can also update row dynamically)
                            });
                        } else {
                            Swal.fire({
                                title: 'Warning!',
                                text: response.message,
                                icon: 'warning'
                            });
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Error!', 'An error occurred while restoring the learner.', 'error');
                    }
                });
            }
        });
    });

        // Get Plan Type seatwise at All Forms wherever is needed
        function getTypeSeatwise(seatId, selectedPlanTypeId = null) {
            
            $('#plan_type_id').empty().append('<option value="">Choose Shift</option>');
            $.ajax({
                url: '{{ route('gettypeSeatwise') }}',
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "seatNo": seatId,
                },
                dataType: 'json',
                success: function (html) {
                    
                    if (html) {
                        let selectedVal = selectedPlanTypeId || $("#plan_type_id").find("option:selected").val();

                        $("#plan_type_id").empty();
                        $("#plan_type_id").append('<option value="">Choose Shift</option>');

                        $.each(html, function(index, planType) {
                            if (planType && planType.id) {
                                let isSelected = (selectedVal && String(planType.id) === String(selectedVal)) ? ' selected' : '';
                                $("#plan_type_id").append('<option value="'+planType.id+'"'+isSelected+'>'+planType.name+'</option>');
                            }
                        });

                        if (selectedVal) {
                            $("#plan_type_id").val(selectedVal).trigger('change');
                        }
                    } else {
                        $("#plan_type_id").empty();
                        $("#plan_type_id").append('<option value="">Select Plan Type</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error); // Log any errors
                }
            });
           
        }


        // auto calculate amount and used at multiple places
    function autoCalculatePaidAmount() {
        var planPrice = parseFloat($('#plan_price_id').val()) || 0;
        var lockerAmount = parseFloat($('#locker_amount_book').val()) || 0;
        var discountRaw = parseFloat($('#discount_amount').val()) || 0;
        var discountType = $('#discountType').val();
        var discountAmt = parseFloat($('#discount_amount2').val()) || 0;
        
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmount = discountRaw;
        }

        if (discountType !== 'percentage' && discountType !== 'amount') {
            $('#discount_amount').val("");
        }
        
        var autoPaid = planPrice + lockerAmount - discountAmount;
        $('#paid_amount').val(autoPaid);

        var autoPaidnew = 0;
        if (planPrice) {
            autoPaidnew = planPrice;

            if (lockerAmount) {
                autoPaidnew += lockerAmount;
            }

            if (discountAmt) {
                autoPaidnew -= discountAmt;
            } else if (discountAmount) {
                autoPaidnew -= discountAmount;
            }
        }

        
        // console.log('autoPaidnew',autoPaidnew);
        $('#new_plan_price').val(autoPaidnew);
    
        calculatePendingAmount();
    }

        // Calculate Pending Amount on BOOKING FORM
    function calculatePaylaterVisibility(pendingAmount) {
        const $paymentMode = $('#payment_mode');
        if (!$paymentMode.length) return;

        const hasPayLaterOption = $paymentMode.find('option[value="3"]').length > 0;
        const canShowPayLater = Number(pendingAmount) === 0;

        if (!canShowPayLater) {
            if ($paymentMode.val() === '3') {
                $paymentMode.val('');
            }
            $paymentMode.find('option[value="3"]').remove();
            return;
        }

        if (!hasPayLaterOption) {
            $paymentMode.append('<option value="3">Pay Later</option>');
        }
    }

    function calculatePaylaterVisibilityForOperationForm(pendingAmount) {
        let $paymentMode = $('#payment_mode10');
        if (!$paymentMode.length) {
            $paymentMode = $('#payment_mode');
        }
        if (!$paymentMode.length) return;

        // "Now" is restricted to Online/Offline only (see
        // syncPaymentModeOptionsForTiming) - Pay Later must never come back,
        // even when the pending amount happens to be 0.
        const timing = $('#refund_pay_timing10').val();
        const hasPayLaterOption = $paymentMode.find('option[value="3"]').length > 0;
        const canShowPayLater = timing !== 'now' && Number(pendingAmount) === 0;

        if (!canShowPayLater) {
            if ($paymentMode.val() === '3') {
                $paymentMode.val('');
            }
            $paymentMode.find('option[value="3"]').remove();
            return;
        }

        if (!hasPayLaterOption) {
            $paymentMode.append('<option value="3">Pay Later</option>');
        }
    }

    // Renew/Upgrade/Change-Plan/Reactive forms use #payment_mode10, except reactive.blade.php
    // which reuses the plain #payment_mode id (see calculatePaylaterVisibilityForOperationForm).
    function getOperationPaymentMode() {
        let $paymentMode = $('#payment_mode10');
        if (!$paymentMode.length) {
            $paymentMode = $('#payment_mode');
        }
        return $paymentMode.val();
    }

    function calculatePendingAmount() {
        const planPrice = parseFloat($('#plan_price_id').val()) || 0;
        const paidAmount = parseFloat($('#paid_amount').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount_book').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount').val()) || 0;
        const discountType = $('#discountType').val();
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else {
            discountAmount = discountRaw;
        }

        const effectivePaid = planPrice+lockerAmount - discountAmount;
        const pendingAmount = effectivePaid-paidAmount;
    
        

        if(pendingAmount > 0){
            $('#pending_amt').html('Pending Amount: ' + pendingAmount);
        }else if (pendingAmount < 0) {
            $('#pending_amt').html('High price not allowed.' + pendingAmount);
        }else{
            $('#pending_amt').html('');
        }

        calculatePaylaterVisibility(pendingAmount);



        // Pay Later always needs a due date, regardless of pending amount.
        if (pendingAmount > 0 || $('#seatAllotmentForm select[name="payment_mode"]').val() === '3') {
            $('#due_date').removeAttr('readonly');
            $('#due_date_star_booking').show();
        } else {
            $('#due_date').attr('readonly', true);
            $('#due_date_star_booking').hide();
            $('#due_date').removeClass('is-invalid');
            $('#due_date_error').text('').hide();
        }
    }

    const _planPriceCache = {};
    const _chargeableDaysCache = {};

    // Get Plan Price at All Forms wherever is needed [booking form,]
    function getPlanPrice(plan_type_id, plan_id, start_date = null) {
        if (!plan_type_id || !plan_id) return;

        // Auto-detect start date if not passed
        if (!start_date) {
            if ($('#plan_start_date').length && $('#plan_start_date').val()) {
                start_date = $('#plan_start_date').val();
            }
        }

        const cacheKey = `${plan_id}_${plan_type_id}_${start_date || ''}`;

        function applyPrice(html) {
            if (html !== undefined && html !== null && html !== '') {
                $('#pending_amt3').html('');
                if ($("#plan_price_id").length) {
                    $("#plan_price_id").val(html);
                    autoCalculatePaidAmount();
                    $("#error-message").hide();
                }
                $("#error-message").hide();
            } else {
                $("#plan_price_id").val("");
                $("#pending_amt").html("No Plan Price Added Yet.");
                $("#paid_amount").val("");
            }
        }

        if (_planPriceCache.hasOwnProperty(cacheKey)) {
            applyPrice(_planPriceCache[cacheKey]);
            return;
        }

        let data = {
            "_token": "{{ csrf_token() }}",
            "plan_type_id": plan_type_id,
            "plan_id": plan_id
        };

        if (start_date) {
            data.plan_start_date = start_date;
        }

        $.ajax({
            url: '{{ route('getPricePlanwise') }}',
            type: 'GET',
            data: data,
            dataType: 'json',
            success: function(html) {
                _planPriceCache[cacheKey] = html;
                applyPrice(html);
            }
        });
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { day: '2-digit', month: 'short', year: 'numeric' };
        return date.toLocaleDateString('en-GB', options);
    }

    function addChargeableDays(plan_id, plan_start_date) {
        if (!plan_id || !plan_start_date) return;

        const cacheKey = `${plan_id}_${plan_start_date}`;

        function applyDays(res) {
            if (res.fixedBillingDate == 'true') {
                $('#chargeable_days').text('Billed for ' + res.chargeable_days + ' Days');
                $('#chargeable_days10').text('Billed for ' + res.chargeable_days + ' Days');
            }
            if (res.fixedBillingDate == 'false') {
                $('#plan_end_date_edit').val(res.end_date);
                $('#end_date_show').text('End date ' + res.end_date );
            }
        }

        if (_chargeableDaysCache.hasOwnProperty(cacheKey)) {
            applyDays(_chargeableDaysCache[cacheKey]);
            return;
        }

        $.ajax({
            url: "{{ route('getChargeableDays') }}",
            type: "GET",
            data: {
                plan_id: plan_id,
                plan_start_date: plan_start_date
            },
            success: function (res) {
                _chargeableDaysCache[cacheKey] = res;
                applyDays(res);
            }
        });
    }

    // change plan and upgrade
    function getPlanPriceAmount(plan_type_id10, plan_id10, plan_start_date10){
        if (!plan_type_id10 || !plan_id10) {
            $("#plan_price10").empty();
            $("#total_amount10").empty();
            return;
        }

        const cacheKey = `op_${plan_id10}_${plan_type_id10}_${plan_start_date10 || ''}`;

        function applyOperationPrice(html) {
            if (html !== undefined && html !== null && html !== '') {
                $('#pending_amt10').html('');
                $("#plan_price10").val(html);
                calculatePaidAmount(); 
                $("#error-message").hide();
            } else {
                $("#plan_price10").val("");
                $("#pending_amt10").html("No Plan Price Added Yet.");
                $("#total_amount10").val("");
            }
        }

        if (_planPriceCache.hasOwnProperty(cacheKey)) {
            applyOperationPrice(_planPriceCache[cacheKey]);
            return;
        }

        $.ajax({
            url: '{{ route('getPricePlanwise') }}',
            type: 'GET',
            data: {
                "_token": "{{ csrf_token() }}",
                "plan_type_id": plan_type_id10,
                "plan_id": plan_id10,
                "plan_start_date": plan_start_date10,
            },
            dataType: 'json',
            success: function(html) {
                _planPriceCache[cacheKey] = html;
                applyOperationPrice(html);
            }
        });
    }

    function lockerAmountGet(plan_id10){
        $.get("{{ route('locker.price') }}", { plan_id: plan_id10 })
        .done(function(json) {
            $('#locker_amount10').val(json.price);
            calculatePaidAmount();
        })
        .fail(function() {
            $('#locker_amount10').val('').prop('readonly', true);
            calculatePaidAmount();
        });
    }

    function calculatePaidAmount() {
        const planPrice = parseFloat($('#plan_price10').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount10').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount10').val()) || 0;
        const discountType = $('#discountType10').val();
        const previous_pending = parseFloat($('#previous_pending10').val()) || 0; 
        
    
        
        var discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmount = discountRaw;
        }

        if (discountType !== 'percentage' && discountType !== 'amount') {
            $('#discount_amount10').val("");
        }
            
        const autoPaid = planPrice + lockerAmount - discountAmount;
 
        $('#total_amount10').val(Math.round(autoPaid ?? 0));

        // -------- Different Logic for CHANGE PLAN vs RENEW/UPGRADE ----------
        const paymentType = $('input[name="payment_type"]').val(); // hidden field already in form

        if (paymentType === 'CHANGE PLAN' || paymentType === 'EDIT') {
            // CHANGE PLAN and EDIT share the exact same diff/pending/refund math in
            // LearnerOperationService, so both get the same "show positive, submit signed" UI.
            const previous_amount = parseFloat($('#previous_amount10').val()) || 0;
            const difference = autoPaid - previous_amount;

            const sign = difference < 0 ? -1 : 1;
            $('#diffrence_amount10')
                .attr('data-sign', sign)
                .attr('data-full-diff', difference)
                .val(Math.round(Math.abs(difference)));
            $('#diffrence_amount_label10').text((difference < 0 ? "Amount to Refund" : "Amount to pay") + " *");

            // Pay Later ignores the diffrence amount and defers the whole gap (see
            // LearnerOperationService), so keep Pending in sync even though the
            // diffrence field is hidden and can't fire its own 'input' event.
            if ($('#refund_pay_timing10').val() === 'later') {
                applyPayLaterPending(difference);
            }
        } else {
            // For RENEW / UPGRADE -> always fresh total, no difference calc
            $('#diffrence_amount10').val('');
        }
    }

    // When refund_pay_timing = 'later', the entire difference is owed/refunded later, so Pending
    // must equal the full difference. Pay Later also hides the diffrence_amount input
    // entirely - unlike the "Now" path, which nets the entered diffrence_amount against effective.
    function applyPayLaterPending(difference) {
        $('#pending_amt10').val(Math.round(Math.abs(difference)));
        $('#pending_amt_error').html('');
        // Pay Later always needs a due date, regardless of pending amount.
        const shouldBeReadonly = (difference == 0 && getOperationPaymentMode() !== '3');
        if (shouldBeReadonly) {
            $('#due_date10').attr('readonly', 'readonly').prop('readonly', true);
            if ($('#due_date10')[0] && $('#due_date10')[0]._flatpickr) {
                $('#due_date10')[0]._flatpickr.set('clickOpens', false);
            }
        } else {
            $('#due_date10').removeAttr('readonly').prop('readonly', false);
            if ($('#due_date10')[0] && $('#due_date10')[0]._flatpickr) {
                $('#due_date10')[0]._flatpickr.set('clickOpens', true);
            }
        }

        if (difference < 0) {
            $('#pending_amt10').prev('label').text("Pending Refund Amount *");
            $('#refund_pay_timing_label10').text("When do you want to refund this amount *");
        } else {
            $('#pending_amt10').prev('label').text("Pending Amount *");
            $('#refund_pay_timing_label10').text("When do you want to pay this amount *");
        }
    }

    function calculatePending(paid_val) {
        const planPrice = parseFloat($('#plan_price10').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount10').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount10').val()) || 0;
        const discountType = $('#discountType10').val();
        const previous_amount10 = parseFloat($('#previous_amount10').val()) || 0;
        const previous_pending = parseFloat($('#previous_pending10').val()) || 0; 

        discountAmount =0;
        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmount = discountRaw;
        }

        const effectivePaid = planPrice+lockerAmount - discountAmount + previous_pending;


        let pendingAmount;
        let overLimit = false;
        const paymentType = $('input[name="payment_type"]').val();
        if (paymentType === 'CHANGE PLAN' || paymentType === 'EDIT') {
            // Mirrors LearnerOperationService (same branch handles both CHANGE PLAN and EDIT):
            // the new total is compared against what was already paid - previous_pending
            // belonged to the old, now-superseded transaction and isn't carried forward.
            // diffrence_amount (paid_val) only settles part of that gap right now; whatever's
            // left over is the pending/pending-refund amount.
            const newTotal = planPrice + lockerAmount - discountAmount;
            const totalDifference = newTotal - previous_amount10;

            if (totalDifference < 0) {
                const totalRefundOwed = Math.abs(totalDifference);
                const refundedNow = Math.min(Math.abs(paid_val), totalRefundOwed);
                pendingAmount = -(totalRefundOwed - refundedNow);
                overLimit = Math.abs(paid_val) > totalRefundOwed;
            } else {
                const paidNow = Math.max(0, paid_val);
                pendingAmount = totalDifference - paidNow;
                overLimit = paidNow > totalDifference;
            }
            $('#pending_amt10').val(Math.round(Math.abs(pendingAmount)));
        } else {
            // RENEW / UPGRADE / REACTIVE keep their original display untouched.
            pendingAmount = effectivePaid - paid_val;
            overLimit = paid_val > effectivePaid;
            $('#pending_amt10').val(pendingAmount);
        }

        if (overLimit) {
            $('#pending_amt_error').html('High price not allowed.' + pendingAmount);
            $('#due_date10').attr('readonly', 'readonly').prop('readonly', true);
            if ($('#due_date10')[0] && $('#due_date10')[0]._flatpickr) {
                $('#due_date10')[0]._flatpickr.set('clickOpens', false);
            }
        }else{
            $('#pending_amt_error').html('');
            if(pendingAmount != 0 || getOperationPaymentMode() === '3'){
                $('#due_date10').removeAttr('readonly').prop('readonly', false);
                if ($('#due_date10')[0] && $('#due_date10')[0]._flatpickr) {
                    $('#due_date10')[0]._flatpickr.set('clickOpens', true);
                }
            } else {
                $('#due_date10').attr('readonly', 'readonly').prop('readonly', true);
                if ($('#due_date10')[0] && $('#due_date10')[0]._flatpickr) {
                    $('#due_date10')[0]._flatpickr.set('clickOpens', false);
                }
            }
        }
        if (pendingAmount < 0) {
        $('#pending_amt10').prev('label').text("Pending Refund Amount *");
        $('#refund_pay_timing_label10').text("When do you want to refund this amount *");
        } else {
            $('#pending_amt10').prev('label').text("Pending Amount *");
            $('#refund_pay_timing_label10').text("When do you want to pay this amount *");
        }
        calculatePaylaterVisibilityForOperationForm(pendingAmount);


    }
    // Show Form Errors
    function showFormErrors(errors) {
        $(".is-invalid").removeClass("is-invalid");
        $(".invalid-feedback").remove();

        $.each(errors, function(key, value) {
            const field = $("[name='" + key + "']");
            field.addClass("is-invalid");
            field.after('<div class="invalid-feedback">' + value[0] + '</div>');
        });
    }
   
    $(document).ready(function() {
        const toggleHiddenFields = @json(toggleHideField());
         // Swap Seat Check Seat Booking Status On Swap Seat Page
        $('#new_seat_id').on('change', function(event) {
            event.preventDefault();
            var new_seat_id = $(this).val();
            var user_id = $('#user_id').val();
            var plan_type_id = $('#swap_plan_type_id').val();
            $('#swap_status').html('');
            
            if (new_seat_id && user_id) {
                $.ajax({
                    url: '{{ route('getSeatStatus') }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    type: 'GET',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "new_seat_id": new_seat_id,
                        "user_id": user_id,
                        "plan_type_id": plan_type_id
                    },
                    dataType: 'json',
                    success: function(html) {
                        if(html == 1) {
                            $('#swap_status').html('<h4 style="color:green !important;">Seat is Available to swap.</h4>');
                            $("#swapsubmit").prop('disabled', false); 
                        }else if(html == 2) {
                            $('#swap_status').html('<h4 style="color:red !important;">Seat is already booked for future, currently not available to swap.</h4>');
                            $("#swapsubmit").prop('disabled', true); 
                        }else {
                            $('#swap_status').html('<h4 style="color:red !important;">Not Available</h4>');
                            $("#swapsubmit").prop('disabled', true); 
                        }
                    }
                });
            }
        });
       
         // Set a Default Payment Date in Dob Field in Booking form
        document.addEventListener("DOMContentLoaded", function() {
            var paidDateInput = document.getElementById('paid_date');
            if (paidDateInput && !paidDateInput.value) { 
                var today = new Date().toISOString().split('T')[0]; 
                paidDateInput.value = today;
            }
        });

         // Set Default Date in DOB

        if (!$('#dob').val()) {
            $('#dob').val('2010-01-01');
        }

         // Set Default Date in DOB end
        var today = new Date();
        var formattedDate = today.toISOString().split('T')[0]; // Format as YYYY-MM-DD
        $('#plan_start_date').val(formattedDate); 


        // For Booking Popup form

        // In Booking form manage Genral or Normal Seat 
        $('.noseat_popup, .first_popup').on('click', function (e) {
            var currentBranch = @json(getCurrentBranch());
          
            if (!currentBranch || currentBranch == 0) {
                alert("Please select a branch first.");
                return false; 
            }
            
            var seatId = $(this).data('id') || $(this).attr('data-id');
            var seatNo = $(this).data('seat_no') || $(this).attr('data-seat_no');
            var planTypeId = $(this).data('plan_type_id') || $(this).attr('data-plan_type_id') || $('#shift_id').val() || '';
            var seatDisplayMap = @json(
                collect(generateSeatNumbers())->mapWithKeys(function($seat) {
                    // If floor info exists, show "floor-seat (floor name)"
                    if (!empty($seat['floor']) && !empty($seat['floor_name'])) {
                        return [$seat['main'] => $seat['floor'] . ' (' . $seat['floor_name'] . ')'];
                    } else {
                        // Fallback: show main seat number
                        return [$seat['main'] => $seat['main']];
                    }
                })
            );
               
  
            if (seatNo || seatId) {
                var seatDisplay = seatDisplayMap[seatNo] ?? seatNo;
                $('#seat_no').val(seatNo);
                $('#seat_id').val(seatId);
                $('#seat_no_head').text('Book Seat No.: ' + seatDisplay);
                $('#general_seat').val('no').trigger('change');
                // Hide the seat select fields (visually only)
                $('#seat_id').closest('.col-lg-6').hide();
                $('#general_seat').closest('.col-lg-6').hide();

            } else if (toggleHiddenFields.includes('12')) {

                $('#seat_no_head').text('Booking Form');
                $('#general_seat').val('no').trigger('change');

            } else {

                $('#seat_no_head').text('Booking Form');
                @can('has-permission', 'General Seat Booking')
                    $('#general_seat').val('yes').trigger('change');
                @else
                    // User does NOT have permission → force NO and hide YES option
                    $('#general_seat').val('no').trigger('change');

                    // Hide the "yes" option from the dropdown
                    $('#general_seat option[value="yes"]').hide();
                @endcan

                // Show seat fields
                $('#seat_id').closest('.col-lg-6').show();
                $('#general_seat').closest('.col-lg-6').show();
            }


            
            $('#seatAllotmentModal').modal('show');
            if ($('#general_seat').val() === 'yes') {
                getTypeSeatwise('', planTypeId); 
                $('#general_seat').val('yes');
            } else if (seatId) {
                getTypeSeatwise(seatId, planTypeId); 
            } else {
                getTypeSeatwise($('#seat_id').val() || '', planTypeId);
            }         
        });
       
        // Enable / Disable Seat No Field on Booking Form
        $('#general_seat').on('change', function () {
            if ($(this).val() === 'no') {
                $('#seat_id').prop('disabled', false);
                $('#seat_no').val($('#seat_id').val() || '');
            } else {
                $('#seat_id').val('').prop('disabled', true);
                $('#seat_no').val('');
                getTypeSeatwise('');
            }
        });


        // OnChange of Seat No Dropdown get PlanType in Booking Form
        $('#seat_id').on('change', function () {
            let newSeatId = $(this).val();
            $('#seat_no').val(newSeatId);
            getTypeSeatwise(newSeatId);
            $('#paid_amount').val("");
        });

        const _lockerPriceCache = {};

        // Manage Locker in Booking Form
        $('#toggleFieldCheckbox2, #plan_id3').on('change', function () {
            var needLocker = $('#toggleFieldCheckbox2').val();
            var planId     = $('#plan_id3').val();
            var planTypeID = $('#plan_type_id').val();
            
            if (needLocker === 'yes') {
                $('#locker_no').removeAttr('readonly');
                var cacheKey = `${planId}_${planTypeID}`;
                if (_lockerPriceCache.hasOwnProperty(cacheKey)) {
                    $('#locker_amount_book').val(_lockerPriceCache[cacheKey]);
                    autoCalculatePaidAmount();
                } else {
                    $.get("{{ route('locker.price') }}", { plan_id: planId, plan_type_id: planTypeID })
                    .done(function(json) {
                        _lockerPriceCache[cacheKey] = json.price;
                        $('#locker_amount_book').val(json.price);
                        autoCalculatePaidAmount(); 
                    })
                    .fail(function() {
                        $('#locker_amount_book').val('').prop('readonly', true);
                        autoCalculatePaidAmount(); 
                    });
                }
            } else {
                $('#locker_amount_book').attr('readonly', true);
                $('#locker_no').attr('readonly', true);
                $('#discount_amount').val('');
                $('#locker_amount_book').val('');
                $('#locker_no').val('');
                autoCalculatePaidAmount(); 
            }
        });

        // Onchange of Plantype get Plan Price in Booking Form
        $('#plan_type_id').on('change', function(event) {
            var plan_type_id = $(this).val();
            var plan_id = $('#plan_id3').val() || $('#plan_id').val() || $('#plan_id2').val() || $('#plan_id4').val() || $('#change_plan_plan_id').val();
            
            if (plan_type_id && plan_id) {
                getPlanPrice(plan_type_id, plan_id);
            } else {
                $("#plan_price_id").val('');
                autoCalculatePaidAmount();
            }
        });

        $('#plan_start_date').on('change', function(event) {
            var plan_start_date = $(this).val();
            var plan_id = $('#plan_id3').val() || $('#plan_id').val();
            var plan_type_id = $('#plan_type_id').val();
            if (plan_type_id && plan_id) {
                getPlanPrice(plan_type_id, plan_id, plan_start_date);
            }
            if (plan_id && plan_start_date) {
                addChargeableDays(plan_id, plan_start_date);
            }
        });

        // If user manually updates paid_amount, update pending as well [booking form]
        $('#paid_amount').on('input', calculatePendingAmount);

        $('#discountType').on('change', function () {
            const type = $(this).val();
            if (type === 'percentage') {
                $('#typeVal').text('%');
            } else if (type === 'amount') {
                $('#typeVal').text('INR');
            } else {
                $('#typeVal').text('INR / %');
            }
            autoCalculatePaidAmount(); // Recalculate if type changes
        });

        // Used in various Booking form
        $('#discount_amount').on('input', function () {
            autoCalculatePaidAmount(); // Recalculate if amount changes
        });

        // Select Discount Type in Booking Form nad enable/disable amout field

        function toggleDiscountAmount() {
            if ($('#discountType').val()) {
                $('#discount_amount').prop('disabled', false);
            } else {
                $('#discount_amount').prop('disabled', true).val('');
            }
        }

        function toggleIdProofFile() {
            if ($('#id_proof_name').val()) {
                $('#id_proof_file').prop('disabled', false);
            } else {
                $('#id_proof_file').prop('disabled', true).val('');
            }
        }

        // Bind events
        $('#discountType').on('change', toggleDiscountAmount);
        $('#id_proof_name').on('change', toggleIdProofFile);

        // Initial state check
        toggleDiscountAmount();
        toggleIdProofFile();

    });

    // Unlock the due date the moment Pay Later is picked, even if there's no pending amount.
    $(document).on('change', '#seatAllotmentForm select[name="payment_mode"]', function() {
        calculatePendingAmount();
    });

    // Auto-clear due_date validation errors on input/change for Booking and Renew
    $(document).on('input change', '#due_date', function() {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
            $('#due_date_error').text('').hide();
        }
    });

    $(document).on('input change', '#due_date2', function() {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
            $('#due_date2_error').text('').hide();
        }
    });

    // Calendar icon click triggers the date picker
    $(document).on('click', '.booking-date-icon', function(e) {
        var $input = $(this).closest('.booking-date-group').find('input.duedate, input[type="date"]');
        if ($input.length && !$input.prop('readonly') && !$input.prop('disabled')) {
            if (typeof $input[0].showPicker === 'function') {
                try {
                    $input[0].showPicker();
                } catch (err) {
                    $input.focus();
                }
            } else {
                $input.focus();
            }
        }
    });

    // Same fix for Renew/Upgrade/Change-Plan/Reactive forms (due_date10).
    $(document).on('change', '#payment_mode10, #payment_mode', function() {
        if (!$('#due_date10').length) {
            return;
        }
        if (getOperationPaymentMode() === '3') {
            $('#due_date10').removeAttr('readonly').prop('readonly', false);
            if ($('#due_date10')[0] && $('#due_date10')[0]._flatpickr) {
                $('#due_date10')[0]._flatpickr.set('clickOpens', true);
            }
        } else {
            var currentPending = parseFloat($('#pending_amt10').val()) || 0;
            if (currentPending > 0) {
                $('#due_date10').removeAttr('readonly').prop('readonly', false);
                if ($('#due_date10')[0] && $('#due_date10')[0]._flatpickr) {
                    $('#due_date10')[0]._flatpickr.set('clickOpens', true);
                }
            } else {
                $('#due_date10').attr('readonly', 'readonly').prop('readonly', true);
                if ($('#due_date10')[0] && $('#due_date10')[0]._flatpickr) {
                    $('#due_date10')[0]._flatpickr.set('clickOpens', false);
                }
            }
        }
    });
  
     // Book Learner Seat Form 
    $(document).on('submit', '#seatAllotmentForm', function(event) {
        event.preventDefault();
        var formData = new FormData(this);
        var seat_no = $('#seat_no').val();
        var seat_id = $('#seat_id').val();
        var name = $('#name').val();
        var mobile = $('#mobile').val();
        var email = $('#email').val();
        var dob = $('#dob').val();
        var plan_id = $('#plan_id3').val();
        var plan_type_id = $('#plan_type_id').val();
        var plan_start_date = $('#plan_start_date').val();
        var id_proof_name = $('#id_proof_name').val();
        var payment_mode = $('#payment_mode').val();
        var id_proof_file = $('#id_proof_file').length ? $('#id_proof_file')[0].files[0] : null;
        var plan_price_value = parseFloat($('#plan_price_id').val()) || 0;
        var paidAmountRaw = ($('#paid_amount').val() || '').trim();
        var paid_amount = paidAmountRaw === '' ? NaN : parseFloat(paidAmountRaw);
        var locker_amount = parseFloat($('#locker_amount_book').val()) || 0;
        var due_date = $('#due_date').val();
        var locker_no = $('#locker_no').val();
        var sended_message_type = $('#sended_message_type').val();
        var errors = {};
        var discountRaw = parseFloat($('#discount_amount').val()) || 0;
        var discountType = $('#discountType').val();
        var discount_amount = 0; 

        if (discountType === 'percentage') {
            discount_amount = ((plan_price_value + locker_amount) * discountRaw) / 100; // This assigns to `discount_amount`, which is NOT defined above
        } else if(discountType === 'amount'){
            discount_amount = discountRaw;
        }


        if (!name) {
            errors.name = 'Full Name is required.';
        }

        if (!mobile) {
            errors.mobile = 'Mobile number is required.';
        } else if (!/^\d{10}$/.test(mobile)) {
            errors.mobile = 'Mobile number must be exactly 10 digits.';
        }

        if (email) {
            if (!/^[\w.-]+@([\w-]+\.)+[\w-]{2,4}$/.test(email)) {
                errors.email = 'Please enter a valid email address.';
            }
        }

        if (!plan_id) {
            errors.plan_id3 = 'Plan is required.';
        }

        if (!plan_type_id) {
            errors.plan_type_id = 'Plan Type is required.';
        }

        if (!plan_start_date) {
            errors.plan_start_date = 'Plan Start Date is required.';
        }

        if (!payment_mode) {
            errors.payment_mode = 'Payment Mode is required.';
        }

        const effectiveTotal = (plan_price_value + locker_amount - discount_amount);
        const pendingFromUi = effectiveTotal - (isNaN(paid_amount) ? 0 : paid_amount);

        if (payment_mode === '3') {
            if (pendingFromUi !== 0) {
                errors.pending_amt = 'For Pay Later, pending amount must not be sent from request.';
            }

            if (!due_date) {
                errors.due_date = 'Due Date is required when Payment Mode is Pay Later.';
            }
        } else {
            if (paidAmountRaw === '' || isNaN(paid_amount)) {
                errors.paid_amount = 'Final Payable Amount is required.';
            }

            if (!errors.paid_amount && paid_amount > effectiveTotal) {
                errors.paid_amount = 'Paid amount should not be greater than the total amount.';
            }

            if (!errors.paid_amount && !due_date && (paid_amount != effectiveTotal)) {
                errors.due_date = 'Due Date is required.';
            }
        }
        
        // Remove previous errors
        $(".is-invalid").removeClass("is-invalid");
        $(".invalid-feedback:not(.booking-date-error-msg)").remove();
        $("#due_date_error").text('').hide();
        
        // Show new errors
        if (Object.keys(errors).length > 0) {
            $.each(errors, function(key, value) {
                var inputField = $("#" + key);
                inputField.addClass("is-invalid");
                if (key === 'due_date') {
                    $('#due_date_error').text(value).show();
                } else {
                    inputField.after('<div class="invalid-feedback">' + value + '</div>');
                }
            });
            return;
        }
        // console.log('formData');
        var general_seat = $('#general_seat').val();
        const toggleVal = $('#toggleFieldCheckbox3').val();
        if (toggleVal !== undefined) {
            formData.append('toggleFieldCheckbox', toggleVal);
        }

        $.ajax({
            url: '{{ route('learners.store') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Form submission successful',
                        icon: 'success',
                        timer: 2000,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload(true); // Force reload from the server
                    });
                } else if (response.errors) {
                    $(".is-invalid").removeClass("is-invalid");
                    $(".invalid-feedback").remove();
                    $("#error-message").hide();
                    $.each(response.errors, function(key, value) {
                        var inputField = $("input[name='" + key + "'], select[name='" + key + "']");
                        inputField.addClass("is-invalid");
                        inputField.after('<div class="invalid-feedback">' + value[0] + '</div>');
                    });
                }else if (response.error) {
                    $("#error-message").text(response.message).show();
                    $("#success-message").hide();
                } else {
                    $("#error-message").text(response.message).show();
                    $("#success-message").hide();
                }
            },
            error: function(xhr, status, error) {
    
                if (xhr.status === 422) {
                    var response = xhr.responseJSON;
                    
                    if (response.error) {
                        $("#error-message").text(response.message).show();
                        $("#success-message").hide();
                    }else if (response.errors.email){
                        $('#email-error').text(errors.email[0]);
                    } else if (response.errors) {
                        $(".is-invalid").removeClass("is-invalid");
                        $(".invalid-feedback").remove();
                        $("#error-message").hide();

                        $.each(response.errors, function(key, value) {
                            var inputField = $("input[name='" + key + "'], select[name='" + key + "']");
                            inputField.addClass("is-invalid");
                            inputField.after('<div class="invalid-feedback">' + value[0] + '</div>');
                        });
                    }
                } else {
                    $("#error-message").text('Something went wrong. Please try again.').show();
                    $("#success-message").hide();
                }
            }
        });
    });


    // change plan and plan type(upgrade) and reactive and Edit

    $(document).ready(function() {
    
        const plan_id10 = $('#plan_id10').val();
        const plan_type_id10 = $('#plan_type_id10').val();
        var plan_start_date10=$('#start_date10').val();
        var payment_type_operation=$('#payment_type_operation').val();
        if(payment_type_operation =='REACTIVE' || payment_type_operation =='UPGRADE'){
            getPlanPriceAmount(plan_type_id10,plan_id10,plan_start_date10);
            // calculatePaidAmount();
        }
        
        if(payment_type_operation =='CHANGE PLAN' || payment_type_operation =='REACTIVE' || payment_type_operation =='EDIT' || payment_type_operation =='UPGRADE'){
            addChargeableDays(plan_id10,plan_start_date10);
        }
        
        var lockerCheck= $('#toggleFieldCheckbox10').val();
        
    
        if(lockerCheck== 'yes'){
            $('#locker_no10').attr('readonly', false);
        
        }

        if($('#discountType10').val() == 'percentage' || $('#discountType10').val() == 'amount'){
            $('#discount_amount10').attr('readonly', false);
        }else{
            $('#discount_amount10').attr('readonly', true);
            
        }


   
    });

    // start new according change plan and plan type(upgrade) and reactive and edit
    // on plan change-total change,price change, locker amount change
    // on plan type change-total change,price change
    // on locker yes -total change, locker amount ,locker no
    // on discount type change -total change, red text change
    // on total input change-pending get, due date on, 

    // diffrence amount - hidden , change plan show
    // diffrence amount on change-change plan


    $('#plan_id10').on('change', function(event) {
        event.preventDefault();
        const plan_id10 = $(this).val();
        const plan_type_id10 = $('#plan_type_id10').val();
        var lockerCheck= $('#toggleFieldCheckbox10').val();
        var plan_start_date10=$('#start_date10').val();
        console.log('plan_id10',plan_id10);
        console.log('plan_type_id10',plan_type_id10);
        console.log('plan_start_date10',plan_start_date10);
        if(plan_type_id10 && plan_id10){
            getPlanPriceAmount(plan_type_id10,plan_id10,plan_start_date10);
            calculatePaidAmount();
            if(lockerCheck== 'yes'){
                lockerAmountGet(plan_id10);
            }
            
        }else{
            $("#plan_price10").val('');
        }
        addChargeableDays(plan_id10,plan_start_date10);
    });
    $('#plan_type_id10').on('change', function(event) {
        
        event.preventDefault();
    
        const plan_type_id10 = $(this).val();
        const plan_id10 = $('#plan_id10').val();
        var lockerCheck= $('#toggleFieldCheckbox10').val();
        var plan_start_date10=$('#start_date10').val();
        if(plan_type_id10 && plan_id10){
            getPlanPriceAmount(plan_type_id10,plan_id10,plan_start_date10);
            calculatePaidAmount();
        if(lockerCheck== 'yes'){
                lockerAmountGet(plan_id10);
            }
        }else{
            $("#plan_price10").val('');
        }
        addChargeableDays(plan_id10,plan_start_date10);
    });
    $('#toggleFieldCheckbox10').on('change', function () {
        
        var needLocker = $(this).val();
        const plan_id10 = $('#plan_id10').val();

        if (needLocker === 'yes') {
            $('#locker_no10').removeAttr('readonly');
            lockerAmountGet(plan_id10)
            
        } else {
            $('#locker_amount10').attr('readonly', true);
            $('#locker_no10').attr('readonly', true);
            $('#locker_amount10').val(0);
            
            
        }
        calculatePaidAmount();
        $('#pending_amt10').val("");
    });
    $('#discountType10').on('change', function (){
        const type = $(this).val();
        if (type === 'percentage') {
            $('#typeVal10').text('%');
            $('#discount_amount10').attr('readonly', false);
        } else if (type === 'amount') {
            $('#typeVal10').text('INR');
            $('#discount_amount10').attr('readonly', false);
        } else {
            $('#typeVal10').text('INR / %');
            $('#discount_amount10').attr('readonly', true);
        }
        calculatePaidAmount(); 
        $('#pending_amt10').val("");
        
    });
    $('#discount_amount10').on('input', function () {
        calculatePaidAmount(); 
    });
    $('#total_amount10').on('input', function () {
        calculatePending($(this).val());   
    });

    $('#diffrence_amount10').on('input', function () {
        const paymentType = $('input[name="payment_type"]').val();
        if (paymentType === 'CHANGE PLAN' || paymentType === 'EDIT') {
            const sign = parseFloat($(this).attr('data-sign')) || 1;
            const absVal = Math.abs(parseFloat($(this).val()) || 0);
            calculatePending(sign * absVal);
        } else {
            calculatePending($(this).val());
        }
    });

    // Now -> Payment Mode restricted to Online/Offline, Amount to pay/refund field shown.
    // Later -> Payment Mode restricted to Pay Later only, Amount to pay/refund field hidden
    // and the full difference is folded into Pending/Pending Refund Amount instead (Pay
    // Later ignores diffrence_amount server-side, see LearnerOperationService).
    function syncPaymentModeOptionsForTiming(timing) {
        const $paymentMode = $('#payment_mode10');
        if (!$paymentMode.length) {
            return;
        }

        if (timing === 'later') {
            $paymentMode.html('<option value="3" selected>Pay Later</option>');
        } else if (timing === 'now') {
            // Keep the current selection if it's still valid for "Now"
            // (Online/Offline) - e.g. on page load after a validation
            // error redisplay where old('payment_mode') was already 1/2.
            const currentMode = $paymentMode.val();
            const preserved = (currentMode === '1' || currentMode === '2') ? currentMode : '';
            $paymentMode.html(
                '<option value="">Select Payment Mode</option>' +
                '<option value="1"' + (preserved === '1' ? ' selected' : '') + '>Online</option>' +
                '<option value="2"' + (preserved === '2' ? ' selected' : '') + '>Offline</option>'
            );
        }
    }

    // Sync once on load in case refund_pay_timing was already selected via
    // old() on a validation-error redisplay - otherwise Payment Mode would
    // keep its full unrestricted option list until the user re-touches
    // the timing dropdown. Only the option list is synced here; amount
    // recalculation stays change-only since it's already correct from PHP.
    syncPaymentModeOptionsForTiming($('#refund_pay_timing10').val());

    $('#refund_pay_timing10').on('change', function () {
        const timing = $(this).val();
        const $diffField = $('#diffrence_amount10');
        const $diffCol = $diffField.closest('.diff-amount-col, .form-group, .col-lg-4');

        syncPaymentModeOptionsForTiming(timing);

        if (timing === 'later') {
            $diffCol.hide();
            const fullDiff = parseFloat($diffField.attr('data-full-diff')) || 0;
            applyPayLaterPending(fullDiff);
        } else if (timing === 'now') {
            $diffCol.show();
            const sign = parseFloat($diffField.attr('data-sign')) || 1;
            const absVal = Math.abs(parseFloat($diffField.val()) || 0);
            calculatePending(sign * absVal);
        }
    });

    $('#start_date10').on('change', function(event) {
        var plan_start_date10 = $(this).val();
        var plan_id10 = $('#plan_id10').val();
        var plan_type_id10 = $('#plan_type_id10').val();
         var lockerCheck= $('#toggleFieldCheckbox10').val();

         if(plan_type_id10 && plan_id10){
            getPlanPriceAmount(plan_type_id10,plan_id10,plan_start_date10);
            calculatePaidAmount();
            if(lockerCheck== 'yes'){
                lockerAmountGet(plan_id10);
            }
            
        }else{
            $("#plan_price10").val('');
        }
        addChargeableDays(plan_id10,plan_start_date10);
        
    });

    // end

    // Get Plan Type at All Forms wherever is needed { renew dashboard}
    function fetchPlanTypesRenew(seat_no, user_id,learner_detail_id) {
        
        if ((seat_no && user_id) || learner_detail_id) {
            $.ajax({
                url: '{{ route('gettypePlanwise') }}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "seat_no": seat_no,
                    "user_id": user_id,
                    "learner_detail_id": learner_detail_id,
                },
                dataType: 'json',
                success: function (html) {
                   
                    $("#plan_type_id_renew").empty(); 
                    $("#plan_id2").empty(); 

                    if (html[0]) {
                        $.each(html[0], function (key, value) {
                            $("#plan_type_id_renew").append('<option value="' + key + '">' + value + '</option>');
                        });
                    } else {
                        $("#plan_type_id_renew").append('<option value="">Choose</option>');
                    }
                    

                    if (html[1]) {
                            $.each(html[1], function (key, value) {
                            $("#plan_id2").append('<option value="' + key + '">' + value + '</option>');
                        });
                    }

                    if (html[5]){
                        $("#plan_price_id2").val(html[5]);      
                    }
                    if (html[6] && html[6].fixedBillingDate==true) {
                        
                        $("#chargeable_days_renew").text('Billed for ' + html[6].chargeable_days + ' Days');  
                    }

                    if(html[3]){
                        $("#locker_amount2").val(html[3].locker_amount);  
                        $("#discount_amount3").val(html[3].discount_amount);  
                        $("#new_plan_price").val(html[3].discount_amount);  

                        if (html[3].locker_amount && parseFloat(html[3].locker_amount) > 0) {
                            $("#locker").val('yes');
                            $("#locker_amount2").val(html[3].locker_amount);
                            
                        } else {
                            $("#locker").val('no');
                            $("#locker_amount2").val('');
                            
                        }

                        if (html[3].discount_amount && parseFloat(html[3].discount_amount) > 0) {
                            $("#discount_type").val('amount');
                            $("#discount_amount3").val(html[3].discount_amount);
                        } else {
                            $("#discount_type").val('');
                            $("#discount_amount3").val('');
                        }
                    }
                    if (html[4]){
                        $("#locker_no2").val(html[4].locker_no);
                        if(html[4].locker_no){
                        $("#locker_no2").removeAttr('readonly');
                        }      
                    }
                    if (html[7]) {
                        let amount = parseFloat(html[7]); // convert to number
                        $("#previous_pending").val(parseInt(amount)); // remove decimals
                    }

                  
                    
                    popupautoCalculatePaidAmount(); 
                },
                error: function (xhr, status, error) {
                    console.error("AJAX error:", status, error); // Log any errors
                }
            });
        } else {
            $("#plan_type_id_renew").empty();
            $("#plan_type_id_renew").append('<option value="">Choose Shift</option>');
        }
    }
    // Used in View Details Popup on Seat Assignment Page
    $(document).on('click', '.second_popup', function() {
        $('#upgrade, #modalBtnRenew, #modalBtnUpgradePlan, #modalBtnChangePlan, #modalBtnEditPlan, #headerEditPlanBtn, #modalBtnSettlement, #modalBtnReactive').hide();
        $('#modalOpContainer').html('<div class="py-2 text-center text-muted small w-100" id="modalOpLoadingPlaceholder"><i class="fa-solid fa-spinner fa-spin me-1"></i> Loading actions...</div>');
        var userId = $(this).data('userid');
        var seatId = $(this).data('id');
        var seatNo=$(this).data('seat_no');
        $('#user_id').val(userId);
        $('#seatAllotmentModal2').modal('show');
        
        if (userId) {
            $.ajax({
                url: '{{ route('learners.show')}}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": userId,
                },
                dataType: 'json',
                success: function(html) {
                    $('#learner_detail_id').val(html.learner_detail_id);
                    $('#owner').text(html.name);
                    $('#learner_dob').text(html.dob);

                    if(html.email){
                        $('#learner_email').text(html.email);
                    }
                    
                    $('#learner_mobile').text(html.mobile);

                    if (html.id_proof_name == 1) {
                        var proof = 'Aadhar';
                    } else if (html.id_proof_name == 2) {
                        var proof = 'Driving License';
                    } else {
                        var proof = 'Other';
                    }

                    if (html.payment_mode == 1) {
                        var paymentmode = 'Online';
                    } else if (html.payment_mode == 2) {
                        var paymentmode = 'Offline';
                    } else {
                        var paymentmode = 'Pay Later';
                    }
                    
                    $('#paymentmode').text(paymentmode);
                    $('#proof').text(proof);
                    $('#planName').text(html.plan_name);
                    $('#planTypeName').text(html.plan_type_name);
                    $('#joinOn').text(formatDate(html.join_date));
                    $('#startOn').text(formatDate(html.plan_start_date));
                    $('#endOn').text(formatDate(html.plan_end_date));

                    $('#price').text(html.plan_price_id);
                    $('#seat_name').text(html.seat_no);
                    $('#planTiming').text(html.hours+' Hours ('+html.start_time+' to '+html.end_time+")");

                    if(html.seat_no){
                        $('#seat_details_info').html(
                            'Booking Details of Seat No. : ' +
                            html.floor_seat_no + 
                            ' <span class="badge rounded-pill bg-danger">' + html.overdue + '</span> ' +
                            '<span class="badge rounded-pill" style="background-color: #18225f; color: #ffffff;">' + html.pending + '</span>'
                        );
                    }else{
                        $('#seat_details_info').text('Booking Details of Seat No. : General');
                    }
                    
                    $('#extendday').html(html.seat_status);

                    // Apply Seat Map Action Menu Conditional Rules
                    if (typeof window.setupSeatMapModalActionRules === 'function') {
                        window.setupSeatMapModalActionRules(html);
                    } else if (typeof setupSeatMapModalActionRules === 'function') {
                        setupSeatMapModalActionRules(html);
                    }
                }
            });
        }

    });

    // For those Seats that are in extend period to re-new that  
    $('#upgrade').on('click', function() {
        $("#update_plan_id").trigger('change');
        var user_id = $('#user_id').val();
        var learner_detail_id = $('#learner_detail_id').val();
        var seat_no = $('#seat_name').text().trim();
        var endOnDate = $('#endOn').text().trim();
        var plan_id=$('#update_plan_id').val();
        
        // Hide the first modal
        $('#seatAllotmentModal2').modal('hide');

        // Update the fields in the second modal
        $('#update_plan_end_date').val(endOnDate);
        $('#update_seat_no').val(seat_no);
        $('#update_user_id').val(user_id);
        var seatDisplayMap = @json(
            collect(generateSeatNumbers())->mapWithKeys(function($seat) {
                // If floor info exists, show "floor-seat (floor name)"
                if (!empty($seat['floor']) && !empty($seat['floor_name'])) {
                    return [$seat['main'] => $seat['floor'] . ' (' . $seat['floor_name'] . ')'];
                } else {
                    // Fallback: show main seat number
                    return [$seat['main'] => $seat['main']];
                }
            })
        );
        if(seat_no){
            const seatDisplay = seatDisplayMap[seat_no] ?? seat_no;
                $('#seat_number_upgrades').text('Renew Seat No.: '  + seatDisplay);
        }else{
                $('#seat_number_upgrades').text('Renew Seat No.: GEN');
        }
        
        $.ajax({
            url: '{{ route('learners.show')}}',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            },
            type: 'GET',
            data: {
                "_token": "{{ csrf_token() }}",
                "id": user_id,
            },
            dataType: 'json',
            success: function(html) {
                console.log(html);
                $('#learner_uid').text(html.learner_no);
                $('#learner_name').text(html.name);
                $('#learner_mobilepop').text(html.mobile);
                // $('#learner_email').text(html.email);
                $('#no_expiry_renew').val(html.no_expiry ? '1' : '0');

            }
        });
        // Show the second modal
        $('#seatAllotmentModal3').modal('show');
        fetchPlanTypesRenew(seat_no,user_id,learner_detail_id);
    });


    // For those Seats that are in extend period to re-new that  
    $(document).on('click', '.renew_extend', function(){
        var user_id = $(this).data('user');
        var seat_no = $(this).data('seat_no');
        var end_date = $(this).data('end_date');
        var learner_detail_id = $(this).data('learner_detail');
        console.log("uuser",user_id);
        console.log("seat_no",seat_no);
        console.log("end_date",end_date);
        console.log("learner_detail_id",learner_detail_id);
        // learner detail fetch
            $.ajax({
                url: '{{ route('learners.show')}}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": user_id,
                },
                dataType: 'json',
                success: function(html) {
                    console.log(html);
                    $('#learner_uid').text(html.learner_no);
                    $('#learner_name').text(html.name);
                    $('#learner_mobilepop').text(html.mobile);
                    // $('#learner_email').text(html.email);
                    $('#no_expiry_renew').val(html.no_expiry ? '1' : '0');

                }
            });
        //learner detail fetch end
        $('#seatAllotmentModal2').modal('hide');
        $('#seatAllotmentModal3').modal('show');
        $('#update_seat_no').val(seat_no);
        $('#update_user_id').val(user_id);
        $('#update_plan_end_date').val(end_date);
            var seatDisplayMap = @json(
            collect(generateSeatNumbers())->mapWithKeys(function($seat) {
                // If floor info exists, show "floor-seat (floor name)"
                if (!empty($seat['floor']) && !empty($seat['floor_name'])) {
                    return [$seat['main'] => $seat['floor'] . ' (' . $seat['floor_name'] . ')'];
                } else {
                    // Fallback: show main seat number
                    return [$seat['main'] => $seat['main']];
                }
            })
        );
        if(seat_no){
                const seatDisplay = seatDisplayMap[seat_no] ?? seat_no;
                $('#seat_number_upgrades').text('Renew Seat No.: '  + seatDisplay);
        }else{
                $('#seat_number_upgrades').text('Renew Seat No.: GEN');
        }
        
        fetchPlanTypesRenew(seat_no, user_id,learner_detail_id);
    });

     // RENEW FORM SUBMIT
    $(document).on('submit', '#upgradeForm', function(event) {
       
        event.preventDefault();
        var formData = new FormData(this);
        var learner_id = $('#update_user_id').val();
        var user_id = $('#update_user_id').val();
        var plan_id = $('#plan_id2').val();
        var plan_type_id = $('#plan_type_id_renew').val();
        var plan_price_id = $('#plan_price_id2').val();
        var errors = {};

        if (!plan_id) {
            // errors.plan_id = 'Plan is required.';
            errors.plan_id2 = 'Plan is required.';
        }

        if (!plan_type_id) {
            errors.plan_type_id_renew = 'Plan Type is required.';
            // errors.plan_type_id = 'Plan Type is required.';
        }

        if (!plan_price_id) {
            errors.plan_price_id2 = 'Price is required.';
            // errors.plan_price_id = 'Price is required.';

        }

        var renewPaymentMode = $(this).find('select[name="payment_mode"]').val();
        var renewDueDate = $(this).find('input[name="due_date"]').val();

        // Check if there is a pending amount in Renew modal
        const renewPlanPrice = parseFloat($('#plan_price_id2').val()) || 0;
        const renewPaidAmount = parseFloat($('#new_plan_price2').val()) || 0;
        const renewLockerAmount = parseFloat($('#locker_amount2').val()) || 0;
        const renewDiscountRaw = parseFloat($('#discount_amount3').val()) || 0;
        const renewDiscountType = $('#discount_type').val();
        const renewPrevPending = parseFloat($('#previous_pending').val()) || 0;
        let renewDiscountAmt = 0;
        if (renewDiscountType === 'percentage') {
            renewDiscountAmt = ((renewPlanPrice + renewLockerAmount) * renewDiscountRaw) / 100;
        } else {
            renewDiscountAmt = renewDiscountRaw;
        }
        const renewEffectiveTotal = renewPlanPrice + renewLockerAmount - renewDiscountAmt + renewPrevPending;
        const renewHasPending = (renewEffectiveTotal - renewPaidAmount) > 0;

        if (renewPaymentMode === '3' && !renewDueDate) {
            errors.due_date2 = 'Due Date is required when Payment Mode is Pay Later.';
        } else if (renewHasPending && !renewDueDate) {
            errors.due_date2 = 'Due Date is required when there is a pending amount.';
        }

        if (Object.keys(errors).length > 0) {
            $(".is-invalid").removeClass("is-invalid");
            $(".invalid-feedback:not(.booking-date-error-msg)").remove();
            $("#due_date2_error").text('').hide();

            $.each(errors, function(key, value) {
                var inputField = $("#" + key);
                inputField.addClass("is-invalid");
                if (key === 'due_date2') {
                    $('#due_date2_error').text(value).show();
                } else {
                    inputField.after('<div class="invalid-feedback">' + value + '</div>');
                }
            });
            return; 
        }

        formData.append('_token', '{{ csrf_token() }}');
        var formId='renewSeat';
        var fieldName='plan';
        var newValue=plan_id ;
        var oldValue=$('#hidden_plan').val();


        $.ajax({
            url: '{{ route('learner.upgrade.renew.store') }}', 
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
               
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Renew successful',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        
                        $('#seatAllotmentModal3').modal('hide');
                        $('#seatAllotmentModal3').one('hidden.bs.modal', function () {
                            if (result.isConfirmed) {
                                window.location.href = '{{ route('seats') }}';
                                location.reload(true);
                            }

                            window.location.reload();
                        });
                    });

                   
                } else if (response.errors) {
                   
                    showFormErrors(response.errors);
                }  else {
                    
                    $(".error-message").text(response.message).show();
                    $(".success-message").hide();
                    // Swal.fire({
                    //     icon: 'error',
                    //     title: 'Error!',
                    //     text: response.message || 'Something went wrong. Please try again.'
                    //     }).then((result) => {
                        
                    //     $('#seatAllotmentModal3').modal('hide');
                    // });
                }
            },
            error: function(xhr, status, error) {
                            
                if (xhr.status === 422) {
                   
                    const response = xhr.responseJSON;
                    
                    // showFormErrors(response); 
                    if (response.error) {
                        $(".error-message").text(response.message).show();
                        $(".success-message").hide();
                    }                      
                } else {
                   if (xhr.status === 409) {
                  
                        Swal.fire({
                            icon: 'warning',
                            title: 'Renewal Blocked',
                            text: xhr.responseJSON.message
                            }).then((result) => {
                        
                                $('#seatAllotmentModal3').modal('hide');
                      
                            });
                    } else {
                   
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Something went wrong. Please try again.'
                            }).then((result) => {
                        
                                $('#seatAllotmentModal3').modal('hide');
                            });
                    }
                }
        }

        });
    });



    


    $(document).ready(function() {
        

         
      

        let table = new DataTable('#datatable');
        //learner edit page 
        var edit_seat_id=$("#edit_seat").val();
        if(edit_seat_id){
            getTypeSeatwise(edit_seat_id);
            $('#plan_type_id').trigger('change');
        }

          // Get Plan Type at All Forms wherever is needed
        function fetchPlanTypes(seat_no, user_id,learner_detail_id) {
           
            if ((seat_no && user_id) || learner_detail_id) {
                $.ajax({
                    url: '{{ route('gettypePlanwise') }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    type: 'GET',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "seat_no": seat_no,
                        "user_id": user_id,
                        "learner_detail_id": learner_detail_id,
                    },
                    dataType: 'json',
                    success: function (html) {
                        console.log("renew",html);
                        $("#plan_type_id2").empty(); 
                        $("#plan_id2").empty(); 

                        if (html[0]) {
                            $.each(html[0], function (key, value) {
                                $("#plan_type_id2").append('<option value="' + key + '">' + value + '</option>');
                            });
                        } else {
                            $("#plan_type_id2").append('<option value="">Choose</option>');
                        }
                       

                        if (html[1]) {
                             $.each(html[1], function (key, value) {
                                $("#plan_id2").append('<option value="' + key + '">' + value + '</option>');
                            });
                        }

                        if (html[2]){
                           $("#plan_price_id2").val(html[2].plan_price_id);      
                        }

                        if(html[3]){
                            $("#locker_amount2").val(html[3].locker_amount);  
                            $("#discount_amount3").val(html[3].discount_amount);  
                            $("#new_plan_price").val(html[3].discount_amount);  

                            if (html[3].locker_amount && parseFloat(html[3].locker_amount) > 0) {
                                $("#locker").val('yes');
                                $("#locker_amount2").val(html[3].locker_amount);
                            } else {
                                $("#locker").val('no');
                                $("#locker_amount2").val('');
                            }

                            if (html[3].discount_amount && parseFloat(html[3].discount_amount) > 0) {
                                $("#discount_type").val('amount');
                                $("#discount_amount3").val(html[3].discount_amount);
                            } else {
                                $("#discount_type").val('');
                                $("#discount_amount3").val('');
                            }
                        }
                        
                        popupautoCalculatePaidAmount(); 
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX error:", status, error); // Log any errors
                    }
                });
            } else {
                $("#plan_type_id2").empty();
                $("#plan_type_id2").append('<option value="">Choose Shift</option>');
            }
        }

       

       

          // Get Plan Price at All Forms wherever is needed
        function getPlanPrice2(plan_type_id,plan_id){
          
            if (plan_type_id && plan_id) {
                    $.ajax({
                        url: '{{ route('getPricePlanwise') }}',
                        type: 'GET',
                        data: {
                            "_token": "{{ csrf_token() }}",
                            "plan_type_id": plan_type_id,
                            "plan_id": plan_id,
                        },
                        dataType: 'json',
                        success: function(html) {
                           if (html !== undefined && html !== null && html !== '') {
                                $('#pending_amt3').html('');
                                if ($("#plan_price").length) {
                                   
                                    $("#plan_price").val(html);
                                    autoCalculatePaidAmount2();
                                    $("#error-message").hide();
                                }
                                $("#error-message").hide();
                            } else {
                                
                                 $("#plan_price").val("");
                                $("#pending_amt").html("No Plan Price Added Yet.");
                                $("#paid_amount").val("");
                            }
                        }

                    });
            } else {
               
                $("#plan_price").empty();
                $("#paid_amount").empty();
            
            }
        }

           // Get Plan Price at Renew popup Forms wherever is needed
        function getPlanPriceRenew(plan_type_id,plan_id){
          
            if (plan_type_id && plan_id) {
                    $.ajax({
                        url: '{{ route('getPricePlanwise') }}',
                        type: 'GET',
                        data: {
                            "_token": "{{ csrf_token() }}",
                            "plan_type_id": plan_type_id,
                            "plan_id": plan_id,
                        },
                        dataType: 'json',
                        success: function(html) {
                            if (html !== undefined && html !== null && html !== '') {

                                if ($("#plan_price_id2").length) {
                                   
                                    $("#plan_price_id2").val(html);
                                    autoCalculatePaidAmount2();
                                    $("#error-message").hide();
                                }
                                $("#error-message").hide();
                            } else {
                                
                                 $("#plan_price_id2").val("");
                                $("#pending_amt").html("No Plan Price Added Yet.");
                                $("#paid_amount").val("");
                            }
                        }

                    });
            } else {
               
                $("#plan_price").empty();
                $("#paid_amount").empty();
            
            }
        }

        // Auto calculate paid amount when plan price, locker or discount changes
        $('#plan_price_id, #locker_amount').on('change', autoCalculatePaidAmount);
        $('#plan_price_id, #locker_amount_book').on('change', autoCalculatePaidAmount);
   
          // If Discount amt is enter it can change the paid amt on RE-NEW Popup
        $('#discount_type').on('change', function (){
            const type = $(this).val();
            if (type === 'percentage') {
                $('#typeVal').text('%');
            } else if (type === 'amount') {
                $('#typeVal').text('INR');
            } else {
                $('#typeVal').text('INR / %');
            }
          popupautoCalculatePaidAmount();
        });
          // If Discount amt is enter it can change the paid amt on RE-NEW FORM
        $('#discount_amount2').on('input', function () {
            autoCalculatePaidAmount2(); // Recalculate if amount changes
        });
        $('#discountType2').on('change', function (){
           const type = $(this).val();
            if (type === 'percentage') {
                $('#typeVal3').text('%');
            } else if (type === 'amount') {
                $('#typeVal3').text('INR');
            } else {
                $('#typeVal3').text('INR / %');
            }
            autoCalculatePaidAmount2(); 
          
        });
        

         // If user manually updates paid_amount in RENEW, update pending as well
        $('#new_plan_price2').on('input', calculatePendingAmountRenew);

        // Unlock the due date the moment Pay Later is picked, even if there's no pending amount.
        $(document).on('change', '#upgradeForm select[name="payment_mode"]', calculatePendingAmountRenew);

        // If user manually updates paid_amount in RENEW upgrade, update pending as well
        $('#new_plan_price').on('input', calculatePendingAmountRenewUpgrade);
         // If user manually updates paid_amount in RENEW upgrade, update pending as well
        $('#diffrence_amount').on('input', calculatePendingAmountChangePlan);

       
        
         // Manage Locaker in Other Form
        $('#toggleFieldCheckbox, #plan_id').on('change', function () {
           
            var needLocker = $('#toggleFieldCheckbox').val();
            var planId     = $('#plan_id').val();
            const locker_user_id     = $('#user_id').val();
           
            if (needLocker === 'yes') {
                
                $('#locker_no2').removeAttr('readonly');
                $('#locker_no3').removeAttr('readonly');
                $('#locker_no').removeAttr('readonly');
                $.get("{{ route('locker.price') }}", { plan_id: planId })
                .done(function(json) {
                    $('#locker_amount').val(json.price);
                    // ✅ call here AFTER value is set
                    autoCalculatePaidAmount2(); 
                })
                .fail(function() {
                    $('#locker_amount').val('').prop('readonly', true);
                    autoCalculatePaidAmount2(); 
                });

                
                //locker no get
                getLockerNo(locker_user_id,'locker_no3');
            } else {
                $('#locker_amount').attr('readonly', true);
                $('#locker_no').attr('readonly', true);
                $('#locker_no2').attr('readonly', true);
                $('#locker_no3').attr('readonly', true);
                $('#locker_amount').val(0);
                $('#locker_no').val('');
                $('#locker_no2').val('');
                $('#locker_no3').val('');
                // ✅ call here when locker is disabled
                autoCalculatePaidAmount2(); 
            }
        });

      

       
    
     

       
        
       
        
        $('#plan_type_id2').on('change', function(event) {
            var plan_type_id = $(this).val();
            var plan_id = $('#plan_id2').val() || $('#plan_id').val() || $('#plan_id3').val() || $('#plan_id4').val() || $('#change_plan_plan_id').val();
            
            if (plan_type_id && plan_id) {
                getPlanPrice2(plan_type_id, plan_id);
            } else {
                $("#plan_price").val('');
            }
        });

        $('#plan_type_id_renew').on('change', function(event) {
            var plan_type_id = $(this).val();
            var plan_id2 = $('#plan_id2').val();
            if (plan_type_id && plan_id2) {
                getPlanPriceRenew(plan_type_id, plan_id2);
            } else {
                $("#plan_price").val('');
            }
        });

        // Onchange of Plan get Plan Price and use at each form wherever is needed 
        $('#plan_id,#plan_id2,#plan_id3').on('change', function(event) {
            event.preventDefault();
            var plan_id = $(this).val();
            var plan_type_id = $('#plan_type_id').val();
            var plan_type_id2 = $('#plan_type_id2').val();
            var plan_start_date = $('#plan_start_date').val();
          
            if (plan_type_id && plan_id) {
                getPlanPrice(plan_type_id, plan_id);
            } else if (plan_type_id2 && plan_id) {
                getPlanPrice(plan_type_id2, plan_id);
            }

            if (plan_start_date && plan_id) {
                addChargeableDays(plan_id, plan_start_date);
            }
        });


        // Get Price form Plan Type and Plan in All Form Wherever is needed
        $('#update_plan_id, #updated_plan_type_id').on('change', function (event) {
            event.preventDefault();
            var update_plan_type_id = $('#updated_plan_type_id').val();
            var update_plan_id =$('#update_plan_id').val();
       
            if (update_plan_id && update_plan_type_id) {
                $.ajax({
                    url: '{{ route('getPricePlanwiseUpgrade') }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    type: 'GET',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "update_plan_type_id": update_plan_type_id,
                        "update_plan_id": update_plan_id,
                    },
                    dataType: 'json',
                    success: function(html) {
                       
                        $.each(html, function(key, value) {
                            $("#updated_plan_price_id").val(value);
                        });
                    }
                });
            } else {
                $("#updated_plan_price_id").empty();
                $("#updated_plan_price_id").append('<option value="">Select Plan Price</option>');
            }
        });


       

      
       

      


        

       
       

        
        // Get Transaction Information show at View Details Page
        $('#transaction_id').on('change', function(event) {
          event.preventDefault();
          var transaction_id = $(this).val();
         
          if (transaction_id) {
              $.ajax({
                  url: '{{ route('getTransactionDetail') }}',
                  headers: {
                      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                  },
                  type: 'GET',
                  data: {
                      "_token": "{{ csrf_token() }}",
                      "transaction_id": transaction_id,
                     
                  },
                  dataType: 'json',
                  success: function(response) {
                if (response.error) {
                    alert(response.error);
                } else {
                        $('#plan_name').val(response.plan.name);
                        $('#plan_type_name').val(response.plantype.name);
                        $('#plan_price').val(response.plan_price_id );
                        $('#plan_start_date ').val(response.plan_start_date );
                        $('#plan_end_date ').val(response.plan_end_date );
                    }
                },
                error: function(xhr) {
                    alert('Error fetching transaction details.');
                }
              });
          }
        });


        // Manage Locker Function on RE-NEW FORM
        $('#locker').on('change', function () {
            var needLocker = $(this).val();
            var planId     = $('#plan_id2').val();
            var locker_user_id     = $('#update_user_id').val();
            if (needLocker === 'yes') {
                $('#locker_no2').removeAttr('readonly');
             
              
                $.get("{{ route('locker.price') }}", { plan_id: planId })
                .done(function(json) {
                    $('#locker_amount2').val(json.price);
                     
                    popupautoCalculatePaidAmount(); 
                    
                })
                .fail(function() {
                    $('#locker_amount2').val('').prop('readonly', true);
                    popupautoCalculatePaidAmount(); 
                });

                //locker no get
                getLockerNo(locker_user_id,'locker_no2');
            } else {
                $('#locker_amount2').attr('readonly', true);
                $('#locker_amount2').val('');
                $('#locker_no2').val('');
                popupautoCalculatePaidAmount(); 
            }
        });

        function getLockerNo(learner_id, addid) {
            // locker no. get
            $.get("{{ route('locker.no') }}", { learner_id: learner_id })
            .done(function (json) {
                $('#' + addid).val(json.learner.locker_no); // if you're passing an element ID
                // or use $('.' + addid) if you're passing a class name
            })
            .fail(function () {
                $('#' + addid).val('').prop('readonly', true);
            });
        }

      


        // If Discount amt is enter it can change the paid amt on RE-NEW FORM
        $('#discount_amount3').on('input', function () {
            popupautoCalculatePaidAmount();
        });

        // View Booked Seat Details on Seat Assignment Page
        $(document).on('click', '.second_popup_without_seat', function() {
            $('#upgrade, #modalBtnRenew, #modalBtnUpgradePlan, #modalBtnChangePlan, #modalBtnEditPlan, #headerEditPlanBtn, #modalBtnSettlement, #modalBtnReactive').hide();
            $('#modalOpContainer').html('<div class="py-2 text-center text-muted small w-100" id="modalOpLoadingPlaceholder"><i class="fa-solid fa-spinner fa-spin me-1"></i> Loading actions...</div>');
            var userId = $(this).data('userid');
            $('#user_id').val(userId);
            $('#seatAllotmentModal2').modal('show');
           
            if (userId) {
                $.ajax({
                    url: '{{ route('learners.show')}}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    type: 'GET',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "id": userId,
                    },
                    dataType: 'json',
                    success: function(html) {
                        $('#learner_detail_id').val(html.learner_detail_id);
                        $('#owner').text(html.name);
                        $('#learner_dob').text(html.dob);
                        $('#learner_email').text(html.email);
                        $('#learner_mobile').text(html.mobile);
                        if (html.id_proof_name == 1) {
                            var proof = 'Aadhar';
                        } else if (html.id_proof_name == 2) {
                            var proof = 'Driving License';
                        } else {
                            var proof = 'Other';
                        }
                        if (html.payment_mode == 1) {
                            var paymentmode = 'Online';
                        } else if (html.payment_mode == 2) {
                            var paymentmode = 'Offline';
                        } else {
                            var paymentmode = 'Pay Later';
                        }
                        
                        $('#paymentmode').text(paymentmode);
                        $('#proof').text(proof);
                        $('#planName').text(html.plan_name);
                        $('#planTypeName').text(html.plan_type_name);
                        $('#joinOn').text(html.join_date);
                        $('#startOn').text(html.plan_start_date);
                        $('#endOn').text(html.plan_end_date);
                        $('#price').text(html.plan_price_id);
                        $('#seat_name').text(html.seat_no);
                        $('#planTiming').text(html.hours+' Hours ('+html.start_time+' to '+html.end_time+")");
                       
                        if(html.seat_no){
                             $('#seat_details_info').html(
                                'Booking Details of Seat No. : ' +
                                html.seat_no + 
                                ' <span class="badge rounded-pill bg-danger">' + html.overdue + '</span> ' +
                                '<span class="badge rounded-pill" style="background-color: #18225f; color: #ffffff;">' + html.pending + '</span>'
                            );
                        }else{
                            $('#seat_details_info').text('Booking Details of Seat No. : General');
                        }

                        $('#extendday').html(html.seat_status);

                        // Apply Seat Map Action Menu Conditional Rules
                        if (typeof window.setupSeatMapModalActionRules === 'function') {
                            window.setupSeatMapModalActionRules(html);
                        } else if (typeof setupSeatMapModalActionRules === 'function') {
                            setupSeatMapModalActionRules(html);
                        }
                    }
                });
            }

        });

        // Explicit click handlers for Seat Map View Details Modal action items
        $(document).on('click', '#modalBtnEditProfile, #headerEditProfileBtn', function(e) {
            e.preventDefault();
            var learnerId = $('#user_id').val();
            if (learnerId) {
                window.location.href = '{{ route("learners.edit", ":id") }}'.replace(':id', learnerId);
            }
        });

        $(document).on('click', '#modalBtnEditPlan, #headerEditPlanBtn', function(e) {
            e.preventDefault();
            var learnerId = $('#user_id').val();
            if (learnerId) {
                window.location.href = '{{ route("learners.edit.plan", ":id") }}'.replace(':id', learnerId);
            }
        });

        $(document).on('click', '#modalBtnChangePlan', function(e) {
            e.preventDefault();
            var learnerId = $('#user_id').val();
            if (learnerId) {
                window.location.href = '{{ route("learner.change.plan", ":id") }}'.replace(':id', learnerId);
            }
        });

        $(document).on('click', '#modalBtnUpgradePlan', function(e) {
            e.preventDefault();
            var learnerId = $('#user_id').val();
            if (learnerId) {
                window.location.href = '{{ route("learners.upgrade", ":id") }}'.replace(':id', learnerId);
            }
        });

        $(document).on('click', '#modalBtnSwap', function(e) {
            e.preventDefault();
            var learnerId = $('#user_id').val();
            if (learnerId) {
                window.location.href = '{{ route("learners.swap", ":id") }}'.replace(':id', learnerId);
            }
        });

        $(document).on('click', '#modalBtnTransactions', function(e) {
            e.preventDefault();
            var learnerId = $('#user_id').val();
            if (learnerId) {
                window.location.href = '{{ route("learners.transactions", ":id") }}'.replace(':id', learnerId);
            }
        });

        $(document).on('click', '#modalBtnProfile', function(e) {
            e.preventDefault();
            var learnerId = $('#user_id').val();
            if (learnerId) {
                window.location.href = '{{ route("learners.show", ":id") }}'.replace(':id', learnerId);
            }
        });

        $(document).on('click', '#modalBtnHistory', function(e) {
            e.preventDefault();
            var learnerId = $('#user_id').val();
            if (learnerId) {
                window.location.href = '{{ route("seats.history.show", ":id") }}'.replace(':id', learnerId);
            }
        });

        $(document).on('click', '#modalBtnMiscPayment', function(e) {
            e.preventDefault();
            var learnerDetailId = $('#learner_detail_id').val();
            if (learnerDetailId) {
                window.location.href = '{{ route("learner.other.payment", ":id") }}'.replace(':id', learnerDetailId);
            }
        });

        $(document).on('click', '#modalBtnIdCard', function(e) {
            e.preventDefault();
            var learnerDetailId = $('#learner_detail_id').val();
            if (learnerDetailId) {
                window.open('{{ route("idCard", ":id") }}'.replace(':id', learnerDetailId), '_blank');
            }
        });

        $(document).on('click', '#modalBtnReceipt', function(e) {
            e.preventDefault();
            var learnerDetailId = $('#learner_detail_id').val();
            if (learnerDetailId) {
                window.open('{{ route("idCard", ":id") }}'.replace(':id', learnerDetailId), '_blank');
            }
        });

        $(document).on('click', '#modalBtnWhatsapp', function(e) {
            e.preventDefault();
            var mobile = $('#learner_mobile').text().trim();
            if (mobile) {
                var cleanMobile = mobile.replace(/\D/g, '');
                window.open('https://wa.me/91' + cleanMobile, '_blank');
            }
        });

        // Left & Right Scroll Arrow Click Handlers for Seat Map Action Slider
        $(document).on('click', '#opScrollLeftBtn', function(e) {
            e.preventDefault();
            var $container = $('#modalOpContainer');
            $container.animate({ scrollLeft: $container.scrollLeft() - 220 }, 250);
        });

        $(document).on('click', '#opScrollRightBtn', function(e) {
            e.preventDefault();
            var $container = $('#modalOpContainer');
            $container.animate({ scrollLeft: $container.scrollLeft() + 220 }, 250);
        });

        // Helper: Robust Date Parser for YYYY-MM-DD, DD-MM-YYYY, DD/MM/YYYY, ISO, etc.
        function parseSeatModalSafeDate(dateStr) {
            if (!dateStr) return null;
            if (typeof dateStr !== 'string') dateStr = String(dateStr);
            dateStr = dateStr.trim();
            if (!dateStr || dateStr === 'NA') return null;

            // Check DD-MM-YYYY or DD/MM/YYYY
            var dmyMatch = dateStr.match(/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/);
            if (dmyMatch) {
                var day = parseInt(dmyMatch[1], 10);
                var month = parseInt(dmyMatch[2], 10) - 1;
                var year = parseInt(dmyMatch[3], 10);
                var d = new Date(year, month, day);
                d.setHours(0, 0, 0, 0);
                return isNaN(d.getTime()) ? null : d;
            }

            // Check YYYY-MM-DD or YYYY/MM/DD
            var ymdMatch = dateStr.match(/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/);
            if (ymdMatch) {
                var year = parseInt(ymdMatch[1], 10);
                var month = parseInt(ymdMatch[2], 10) - 1;
                var day = parseInt(ymdMatch[3], 10);
                var d = new Date(year, month, day);
                d.setHours(0, 0, 0, 0);
                return isNaN(d.getTime()) ? null : d;
            }

            var parsed = new Date(dateStr);
            if (!isNaN(parsed.getTime())) {
                parsed.setHours(0, 0, 0, 0);
                return parsed;
            }
            return null;
        }

        // Helper function for Seat Map Action Menu Conditional Rules & Route Data Assignment
        function setupSeatMapModalActionRules(html) {
            var learnerId = html.learner_id || html.id || $('#user_id').val();
            var learnerDetailId = html.learner_detail_id || $('#learner_detail_id').val();

            // When server returns actions_html matching exact learner list conditions
            if (html.actions_html) {
                $('#modalOpContainer').html(html.actions_html);
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('#modalOpContainer [data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                }
                if (html.can_renew_membership) {
                    $('#upgrade').show();
                } else {
                    $('#upgrade').hide();
                }
                if (learnerId) {
                    $('#headerEditProfileBtn').attr('href', '{{ route("learners.edit", ":id") }}'.replace(':id', learnerId));
                }
                if (parseInt(html.frozen_status || 0) === 1) {
                    $('#headerEditProfileBtn').hide();
                } else {
                    $('#headerEditProfileBtn').show();
                }
                return;
            }

            // Populate links & attributes for modal action items
            if (learnerId) {
                var editUrl = '{{ route("learners.edit", ":id") }}'.replace(':id', learnerId);
                var changePlanUrl = '{{ route("learner.change.plan", ":id") }}'.replace(':id', learnerId);
                var upgradePlanUrl = '{{ route("learners.upgrade", ":id") }}'.replace(':id', learnerId);
                var swapUrl = '{{ route("learners.swap", ":id") }}'.replace(':id', learnerId);
                var transactionsUrl = '{{ route("learners.transactions", ":id") }}'.replace(':id', learnerId);
                var profileUrl = '{{ route("learners.show", ":id") }}'.replace(':id', learnerId);
                var expireUrl = '{{ route("learner.expire", ":id") }}'.replace(':id', learnerId);
                var activityUrl = '{{ route("activities.all") }}' + '?learner_id=' + learnerId;
                var historyUrl = '{{ route("seats.history.show", ":id") }}'.replace(':id', html.seat_id || learnerId);

                $('#modalBtnEditProfile, #headerEditProfileBtn').attr('href', editUrl);
                $('#modalBtnEditPlan, #headerEditPlanBtn').attr('href', '{{ route("learners.edit.plan", ":id") }}'.replace(':id', learnerId));
                $('#modalBtnChangePlan').attr('href', changePlanUrl);
                $('#modalBtnUpgradePlan').attr('href', upgradePlanUrl);
                $('#modalBtnSwap').attr('href', swapUrl);
                $('#modalBtnTransactions').attr('href', transactionsUrl);
                $('#modalBtnProfile').attr('href', profileUrl);
                $('#modalBtnExpire').attr('href', expireUrl);
                $('#modalBtnActivity').attr('href', activityUrl);
                $('#modalBtnHistory').attr('href', historyUrl);
            }

            if (learnerDetailId) {
                var miscPaymentUrl = '{{ route("learner.other.payment", ":id") }}'.replace(':id', learnerDetailId);
                var idCardUrl = '{{ route("idCard", ":id") }}'.replace(':id', learnerDetailId);
                var receiptUrl = '{{ route("idCard", ":id") }}'.replace(':id', learnerDetailId);

                $('#modalBtnMiscPayment').attr('href', miscPaymentUrl);
                $('#modalBtnIdCard').attr('href', idCardUrl);
                $('#modalBtnReceipt').attr('href', receiptUrl);
            }

            // WhatsApp link
            if (html.mobile) {
                var cleanMobile = String(html.mobile).replace(/\D/g, '');
                $('#modalBtnWhatsapp').attr('href', 'https://wa.me/91' + cleanMobile);
            } else {
                $('#modalBtnWhatsapp').attr('href', 'javascript:void(0)');
            }

            // Gift button data attributes
            $('#modalBtnGift')
                .attr('data-learner_id', learnerId)
                .data('learner_id', learnerId);

            // Freeze button data attributes & label toggle
            var isFrozen = parseInt(html.frozen_status || 0) === 1;
            $('#modalBtnFreeze')
                .attr('data-learner_id', learnerId)
                .data('learner_id', learnerId)
                .attr('data-learnerdetail', learnerDetailId)
                .data('learnerdetail', learnerDetailId)
                .attr('data-status', isFrozen ? 1 : 0)
                .data('status', isFrozen ? 1 : 0);

            if (isFrozen) {
                $('#modalBtnFreeze .op-icon-label').text('Unfreeze');
                $('#modalBtnFreeze .op-icon-circle').html('<i class="fa-solid fa-pause"></i>');
                $('#modalBtnFreeze').attr('title', 'Unfreeze Plan');
            } else {
                $('#modalBtnFreeze .op-icon-label').text('Freeze');
                $('#modalBtnFreeze .op-icon-circle').html('<i class="fa-solid fa-snowflake"></i>');
                $('#modalBtnFreeze').attr('title', 'Freeze Plan');
            }

            // Settlement button data attributes
            $('#modalBtnSettlement')
                .attr('data-id', learnerId)
                .data('id', learnerId)
                .attr('data-learnerdetail', learnerDetailId)
                .data('learnerdetail', learnerDetailId);

            // Delete button data attributes
            $('#modalBtnDelete')
                .attr('data-id', learnerId)
                .data('id', learnerId)
                .attr('data-learnerdetail', learnerDetailId)
                .data('learnerdetail', learnerDetailId)
                .attr('data-seat', html.seat_no || '')
                .data('seat', html.seat_no || '')
                .attr('data-payblerefund', 0)
                .data('payblerefund', 0);

            // Close seat data attributes
            $('#modalBtnCloseSeat')
                .attr('data-id', learnerId)
                .data('id', learnerId)
                .attr('data-learnerdetail', learnerDetailId)
                .data('learnerdetail', learnerDetailId)
                .attr('data-learner_detail_id', learnerDetailId)
                .data('learner_detail_id', learnerDetailId)
                .attr('data-plan_end_date', html.plan_end_date || '')
                .data('plan_end_date', html.plan_end_date || '')
                .attr('data-payblerefund', 0)
                .data('payblerefund', 0);

            // Bind Renew button click on modal icon
            $('#modalBtnRenew').off('click').on('click', function(e) {
                e.preventDefault();
                $('#upgrade').trigger('click');
            });

            // Days & status calculations
            var daysRemaining = (html.diff_in_days !== undefined && html.diff_in_days !== null) ? parseInt(html.diff_in_days) : 999;
            var extendDaysRemaining = (html.diff_extend_day !== undefined && html.diff_extend_day !== null) ? parseInt(html.diff_extend_day) : 999;

            var todayDate = new Date();
            todayDate.setHours(0, 0, 0, 0);

            if (isNaN(daysRemaining) || daysRemaining === 999) {
                var parsedEndDate = parseSeatModalSafeDate(html.plan_end_date);
                if (parsedEndDate) {
                    daysRemaining = Math.ceil((parsedEndDate - todayDate) / (1000 * 3600 * 24));
                    if (isNaN(extendDaysRemaining) || extendDaysRemaining === 999) {
                        extendDaysRemaining = daysRemaining;
                    }
                }
            }

            // Calculate exact days since booking/start (using minimum non-negative difference)
            var diffList = [];

            if (html.created_at) {
                var cDate = parseSeatModalSafeDate(html.created_at);
                if (cDate) {
                    var d1 = Math.floor((todayDate - cDate) / (1000 * 3600 * 24));
                    if (d1 >= 0) diffList.push(d1);
                }
            }

            if (html.join_date) {
                var jDate = parseSeatModalSafeDate(html.join_date);
                if (jDate) {
                    var d2 = Math.floor((todayDate - jDate) / (1000 * 3600 * 24));
                    if (d2 >= 0) diffList.push(d2);
                }
            }

            if (html.plan_start_date) {
                var pDate = parseSeatModalSafeDate(html.plan_start_date);
                if (pDate) {
                    var d3 = Math.floor((todayDate - pDate) / (1000 * 3600 * 24));
                    if (d3 >= 0) diffList.push(d3);
                }
            }

            if (html.days_since_start !== undefined && html.days_since_start !== null) {
                var backendDiff = parseInt(html.days_since_start);
                if (!isNaN(backendDiff) && backendDiff >= 0) diffList.push(backendDiff);
            }

            var daysSinceStart = diffList.length > 0 ? Math.min.apply(null, diffList) : 0;

            var pendingAmount = parseFloat(html.pending_amount_num !== undefined ? html.pending_amount_num : (html.pending || 0)) || 0;
            var isLearnerActive = parseInt(html.status !== undefined ? html.status : 1) !== 0;

            // --- STRICT LEARNER LIST & REQUESTED CONDITIONAL RULES ---

            // 1. Edit Profile: Shown when not frozen
            if (!isFrozen) {
                $('#modalBtnEditProfile').css('display', 'inline-flex').show();
            } else {
                $('#modalBtnEditProfile').hide();
            }

            // 2. Edit Plan: Seat booking ke 3 days me Edit Plan wala Icons Show krwao (daysSinceStart <= 3) & not frozen
            if (daysSinceStart >= 0 && daysSinceStart <= 3 && !isFrozen) {
                $('#modalBtnEditPlan, #headerEditPlanBtn').css('display', 'inline-flex').show();
            } else {
                $('#modalBtnEditPlan, #headerEditPlanBtn').hide();
            }

            // 3. Swap Seat: Shown when not frozen
            if (!isFrozen) {
                $('#modalBtnSwap').css('display', 'inline-flex').show();
            } else {
                $('#modalBtnSwap').hide();
            }

            // 4. Change Plan: Seat booking ke 7 days me Change Plan ka icon Aayega (daysSinceStart <= 7) & not frozen
            if (daysSinceStart >= 0 && daysSinceStart <= 7 && !isFrozen) {
                $('#modalBtnChangePlan').css('display', 'inline-flex').show();
            } else {
                $('#modalBtnChangePlan').hide();
            }

            // 5. Upgrade Plan: Seat expire se 5 days me upgrade plan ka icon show kro (daysRemaining <= 5) & not frozen
            if (daysRemaining <= 5 && !isFrozen) {
                $('#modalBtnUpgradePlan').css('display', 'inline-flex').show();
            } else {
                $('#modalBtnUpgradePlan').hide();
            }

            // 6. Close Seat / Close Plan: Shown when not frozen
            if (!isFrozen) {
                $('#modalBtnCloseSeat').css('display', 'inline-flex').show();
            } else {
                $('#modalBtnCloseSeat').hide();
            }

            // 7. Reactivate: Shown ONLY if learner is inactive (status == 0) and not frozen
            if (!isLearnerActive && !isFrozen) {
                $('#modalBtnReactive').css('display', 'inline-flex').show();
            } else {
                $('#modalBtnReactive').hide();
            }

            // 8. Miscellaneous Payment, Transactions, ID Card, Profile, Expire, Activity, History, Receipt, WhatsApp: Always shown
            $('#modalBtnMiscPayment, #modalBtnTransactions, #modalBtnIdCard, #modalBtnProfile, #modalBtnExpire, #modalBtnActivity, #modalBtnHistory, #modalBtnReceipt, #modalBtnWhatsapp')
                .css('display', 'inline-flex').show();

            // 9. Gift Days: Shown when not frozen
            if (!isFrozen) {
                $('#modalBtnGift').css('display', 'inline-flex').show();
            } else {
                $('#modalBtnGift').hide();
            }

            // 10. Freeze Days: Shown if frozen OR active plan (diff_in_days >= 0)
            if (isFrozen || daysRemaining >= 0) {
                $('#modalBtnFreeze').css('display', 'inline-flex').show();
            } else {
                $('#modalBtnFreeze').hide();
            }

            // 11. Settlement: Shown if pending amount > 0
            if (pendingAmount > 0) {
                $('#modalBtnSettlement').css('display', 'inline-flex').show();
            } else {
                $('#modalBtnSettlement').hide();
            }

            // 12. Delete Learner: Always shown for seat map action
            if (learnerId) {
                $('#modalBtnDelete').css('display', 'inline-flex').show();
            } else {
                $('#modalBtnDelete').hide();
            }

            // 13. Renew Button: Shown 5 days before expiration until extension
            if (daysRemaining <= 5 && !isFrozen && parseInt(html.is_renew || 0) == 0 && isLearnerActive) {
                $('#modalBtnRenew').css('display', 'inline-flex').show();
                $('#upgrade').show();
            } else {
                $('#modalBtnRenew').hide();
                $('#upgrade').hide();
            }
        }

        // Close View Detail Modal when any action triggering sweetalert/sub-modal is clicked inside modal action strip
        $('#seatAllotmentModal2').on('click', '#modalOpContainer .settlement-learner, #modalOpContainer .giftDaysBtn, #modalOpContainer .freezDaysBtn, #modalOpContainer .delete-customer, #modalOpContainer .link-close-plan, #modalOpContainer .renew_extend, #modalOpContainer .open-reminder-chooser, #modalOpContainer .open-waba, #modalOpContainer .open-text, #modalOpContainer .open-reminder-chooser-free', function() {
            $('#seatAllotmentModal2').modal('hide');
        });

        window.parseSeatModalSafeDate = parseSeatModalSafeDate;
        window.setupSeatMapModalActionRules = setupSeatMapModalActionRules;
      
    });
        

</script>


<script>
    // Function to handle changes Activity and show that on Dashboard Page
    function handleFormChanges(formId, learnerId) {
        const form = document.getElementById(formId);
        if (!form) {
            console.error('Form not found:', formId);
            return;
        }
        const changes = {}; 
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.dataset.initialValue = input.value;
            input.addEventListener('change', function() {
                const fieldName = this.name;
                const oldValue = this.dataset.initialValue;
                const newValue = this.value;
                // console.log(`Field changed: ${fieldName}, Old Value: ${oldValue}, New Value: ${newValue}`);
                if (oldValue !== newValue) {
                    changes[fieldName] = { oldValue, newValue };
                    this.dataset.initialValue = newValue; 
                }
            });
        });

        // Add submit event listener to the form
        form.addEventListener('submit', function(event) {
            const paymentMode10 = form.querySelector('#payment_mode10');
            const pendingAmount10 = form.querySelector('#pending_amt10');
            if (paymentMode10 && pendingAmount10 && paymentMode10.value === '3') {
                pendingAmount10.value = '';
            }

            for (const fieldName in changes) {
                const { oldValue, newValue } = changes[fieldName];

                const skipClientLogging = [
                    'swapseat', 'renewSeat', 'learnerUpgrade', 'changePlan', 'reactive',
                    'editPlanForm', 'edit', 'deleteSeat', 'closeSeat', 'restoreSeat',
                    'other-payment_page', 'pendingPayment', 'payment_page'
                ];

                if (skipClientLogging.includes(formId) || (typeof formId === 'string' && formId.toLowerCase().includes('payment'))) {
                    // LearnerOperationService, LearnerSeatSwapService & LearnerLifecycleService
                    // already log operations server-side inside DB transactions with exact snapshots.
                    // Payments are tracked in transactions, not learner_operations_log.
                    // Skipping client-side logging prevents duplicate and race-condition log entries.
                } else {
                    // For other operations, log changes for all fields
                    logFieldChange(learnerId, formId, fieldName, oldValue, newValue);
                }
            }
        });
    }

    // Function to log the field changes
    function logFieldChange(learnerId, formId, fieldName, oldValue, newValue) {
       
        console.log('Logging change for learner:', learnerId, formId, fieldName, oldValue, newValue);
        fetch("{{ route('learner.log') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
            },
            body: JSON.stringify({
                learner_id: learnerId,
                field_updated: fieldName,
                old_value: oldValue,
                new_value: newValue,
                operation: formId,
                updated_by: {{ getAuthenticatedUser()->id }},
                created_at: new Date().toISOString(),
            }),
        })
        .then(response => response.json())
        .then(data => console.log('Change logged successfully:', data))
        .catch(error => console.error('Error logging change:', error));
    }

    // Increase Message Send Count and store it in DB to show on Dashboard Counts
    function incrementMessageCount(id, type) {
        fetch(`increment-message-count`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                id: id,
                type: type
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                console.log(`${type} message count updated for user ID: ${id}`);
            } else {
                console.error('Failed to update message count');
            }
        })
        .catch(error => console.error('Error:', error));
    }


    

     // auto calculate amount and used at multiple places
    function autoCalculatePaidAmount2() {
        var planPrice = parseFloat($('#plan_price').val()) || 0;
        var lockerAmount = parseFloat($('#locker_amount').val()) || 0;
        var discountType = $('#discountType2').val();
        var discountAmt = parseFloat($('#discount_amount2').val()) || 0;
        var totalAmount = parseFloat($('#total_amount2').val()) || 0;

        if (discountType === 'percentage' ) {
            discountAmount = ((planPrice + lockerAmount) * discountAmt) / 100;
        } else {
            discountAmount = discountAmt;
        }

        if (discountAmount==0 && discountType !== 'percentage' && discountType !== 'amount') {
            $('#discount_amount2').val(0);
        }
         
        var autoPaid = planPrice + lockerAmount - discountAmount;

        
       
        $('#new_plan_price').val(autoPaid);
        
       
        var difference = autoPaid - totalAmount;
        
        $('#diffrence_amount').val(difference);
        $('#diffrence_amount').removeAttr('readonly');
        $('#discount_amount2').removeAttr('readonly');
        calculatePendingAmountRenewUpgrade();
    }


    // Auto Calculate for Re-New
    function popupautoCalculatePaidAmount() {
        const planPrice = parseFloat($('#plan_price_id2').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount2').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount3').val()) || 0;
        const discountType = $('#discount_type').val();
        const previous_pending = parseFloat($('#previous_pending').val()) || 0; 
        
        let discountAmountt = 0;
       
        if (discountType === 'percentage') {
            discountAmountt = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else if (discountType === 'amount') {
            discountAmountt = discountRaw;
        }

        if (discountType !== 'percentage' && discountType !== 'amount') {
            $('#discount_amount3').val("");
        }
      
        var autoPaidnew;
        if(planPrice && lockerAmount && discountAmountt){
            autoPaidnew = planPrice + lockerAmount - discountAmountt +previous_pending;
        } else if (planPrice && lockerAmount) {
            autoPaidnew = planPrice + lockerAmount + previous_pending;
        }else if (planPrice && discountAmountt) {
            autoPaidnew = planPrice - discountAmountt + previous_pending;
        } else {
            autoPaidnew = planPrice + previous_pending;
        }
        // console.log('planPrice',planPrice);
        // console.log('lockerAmount',lockerAmount);
        // console.log('discountRaw',discountRaw);
        // console.log('discountType',discountType);
        // console.log('discountAmountt',discountAmountt);
        console.log('autoPaidnew',autoPaidnew);
        
        $('#new_plan_price2').val(autoPaidnew);
        calculatePendingAmountRenew();
    }
   

    // Calculate Pending Amount on Renew Popup FORM
    function calculatePendingAmountRenew() {
        const planPrice = parseFloat($('#plan_price_id2').val()) || 0;
        const paidAmount = parseFloat($('#new_plan_price2').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount2').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount3').val()) || 0;
        const discountType = $('#discount_type').val();
        const previous_pending = parseFloat($('#previous_pending').val()) || 0; 
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else {
            discountAmount = discountRaw;
        }

        const effectivePaid = planPrice + lockerAmount - discountAmount + previous_pending;
        const pendingAmount = effectivePaid - paidAmount;

        if (pendingAmount > 0) {
            $('#pending_amt2').html('Pending Amount: ' + pendingAmount);
        } else if (pendingAmount < 0) {
            $('#pending_amt2').html('High price not allowed.' + pendingAmount);
        } else {
            $('#pending_amt2').html('');
        }

        // Hide 'Pay Later' mode on partial payment (when paidAmount > 0 and pendingAmount > 0)
        const $paymentMode = $('#upgradeForm select[name="payment_mode"], #seatAllotmentModal3 select[name="payment_mode"]');
        const $payLaterOption = $paymentMode.find('option[value="3"]');

        if (paidAmount > 0 && pendingAmount > 0) {
            $payLaterOption.hide();
            if ($paymentMode.val() === '3') {
                $paymentMode.val('');
            }
        } else {
            $payLaterOption.show();
        }

        // Pay Later always needs a due date, regardless of pending amount.
        if (pendingAmount > 0 || $paymentMode.val() === '3') {
            $('#due_date2').removeAttr('readonly');
            $('#due_date_star_renew').show();
        } else {
            $('#due_date2').attr('readonly', true);
            $('#due_date_star_renew').hide();
            $('#due_date2').removeClass('is-invalid');
            $('#due_date2_error').text('').hide();
        }
    }

    // Calculate Pending Amount on Renew FORM
    function calculatePendingAmountRenewUpgrade() {
        
        const planPrice = parseFloat($('#plan_price').val()) || 0;
        const paidAmount = parseFloat($('#new_plan_price').val()) || 0;
        const lockerAmount = parseFloat($('#locker_amount').val()) || 0;
        const discountRaw = parseFloat($('#discount_amount2').val()) || 0;
        const discountType = $('#discountType2').val();
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = ((planPrice + lockerAmount) * discountRaw) / 100;
        } else {
            discountAmount = discountRaw;
        }

        const effectivePaid = planPrice+lockerAmount - discountAmount;
        const pendingAmount = effectivePaid-paidAmount;
       
        

        if(pendingAmount > 0){
            $('#pending_amt3').html('Pending Amount: ' + pendingAmount);
        }else if (pendingAmount < 0) {
            $('#pending_amt3').html('High price not allowed.' + pendingAmount);
        }else{
            $('#pending_amt3').html('');
        }

        console.log('lockerAmount',lockerAmount);
        console.log('discountAmount',discountAmount);
        //console.log('planPrice',planPrice); 
        console.log('effectivePaid',effectivePaid);
        console.log('pendingAmount',pendingAmount);

        if (pendingAmount > 0) {
            $('#due_date3').removeAttr('readonly');
        } else {
            $('#due_date3').attr('readonly', true);
        }
    }
     // Calculate Pending Amount on Change plan
    function calculatePendingAmountChangePlan() {

        const planPrice2 = parseFloat($('#plan_price').val()) || 0;
        const lockerAmount2 = parseFloat($('#locker_amount').val()) || 0;
        const discountType2 = $('#discountType2').val();
        const discountAmt2 = parseFloat($('#discount_amount2').val()) || 0;
        const totalAmount2 = parseFloat($('#total_amount2').val()) || 0;
        const autoPaid2 = parseFloat($('#new_plan_price').val()) || 0;

        if (discountType2 === 'percentage' ) {
            discountAmount2 = ((planPrice2 + lockerAmount2) * discountAmt2) / 100;
        } else {
            discountAmount2 = discountAmt2;
        }

        const effectivePaid2 = planPrice2 + lockerAmount2 - discountAmount2 - totalAmount2;

        const inputamt = $(this).val();
        const pendingAmount2 = effectivePaid2-inputamt;
      
        if(pendingAmount2 > 0){
            $('#pending_amt4').html('Pending Amount: ' + pendingAmount2);
        }else if (pendingAmount2 < 0) {
            $('#pending_amt4').html('High price not allowed.' + pendingAmount2);
        }else{
            $('#pending_amt4').html('');
        }

      
        if (pendingAmount2 > 0) {
            $('#due_date3').removeAttr('readonly');
        } else {
            $('#due_date3').attr('readonly', true);
        }
    }



</script>


<script>
// for Waba send all function
function loadLearnerMobiles(learnerId,mobileId) {
    

    $.ajax({
        url: "{{ route('notification.getLearnerMobiles') }}",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            learner_id: learnerId
        },
        success: function (res) {
            console.log(res.mobiles);
             let mobileSelect = $('#' + mobileId); 
            mobileSelect.empty();   // clear previous
            mobileSelect.prop('disabled', false); // enable by default

            // --- CASE 1: No mobile found ---
            if (!res.mobiles || res.mobiles.length === 0) {
                mobileSelect.append(`<option value="">No Mobile Found</option>`);
                mobileSelect.prop('disabled', true);
                return;
            }

            // --- CASE 2: Only 1 mobile number ---
            if (res.mobiles.length === 1) {
                let single = res.mobiles[0];

                mobileSelect.append(`<option value="${single}" selected>${'+91-'+single}</option>`);
                mobileSelect.prop('disabled', true);  // disable the dropdown
                return;
            }

            // --- CASE 3: Multiple numbers available ---
            mobileSelect.append(`<option value="">Select Mobile</option>`);
            var x = 0;
            res.mobiles.forEach(function (m) {
                x++;
                if(x===1){
                mobileSelect.append(`<option value="${m}">${'+91-'+m+' (primary-mobile)'}</option>`);
                } else{
                mobileSelect.append(`<option value="${m}">${'+91-'+m+' (parent-number)'}</option>`);
                }
            });
        }
    });
}
// Show Form Errors
function showFormErrors2(errors) {
    $(".is-invalid").removeClass("is-invalid");
    $(".invalid-feedback").remove();

    $.each(errors, function(key, value) {
        const field = $("[name='" + key + "']");
        field.addClass("is-invalid");
        field.after('<div class="invalid-feedback">' + value[0] + '</div>');
    });
}

// Ellipsis icon (learner has both WhatsApp + Text reminders active) opens a
// chooser modal instead of a dropdown; sync the learner_id onto both choice
// buttons before Bootstrap shows it (data-bs-toggle/target already open it).
$(document).on('click', '.open-reminder-chooser', function () {
    const learnerId = $(this).data('learner_id');
    // .data() (not .attr()) so jQuery's own data cache stays in sync - the
    // .open-waba/.open-text handlers below read via $(this).data(...).
    $('#sendReminderChooserModal').find('.chooser-action').data('learner_id', learnerId);
});

// Chooser option clicked -> let it close (data-bs-dismiss="modal" on the
// button), then open the matching send modal once the chooser has fully
// hidden, so Bootstrap doesn't stack two modal backdrops at once.
$(document).on('click', '.chooser-action', function () {
    const targetModal = $(this).data('target-modal');

    $('#sendReminderChooserModal').one('hidden.bs.modal', function () {
        $(targetModal).modal('show');
    });
});

// Free (no-API) "both" reminder icon opens a chooser modal too; the two
// options are plain wa.me/sms: links, so just point them at this row's
// already-built links before Bootstrap shows the modal.
$(document).on('click', '.open-reminder-chooser-free', function () {
    $('#freeWabaChoiceLink').attr('href', $(this).data('waba-link'));
    $('#freeTextChoiceLink').attr('href', $(this).data('text-link'));
});

$(document).on('click', '.open-waba', function () {

    let learnerId = $(this).data('learner_id');
    $('#modal_learner_id').val(learnerId);

    // Load mobiles in dropdown
    loadLearnerMobiles(learnerId,'learner_mobile_select');
});
// When template changes → get both values and render final message
$('#waba_template_select').on('change', function () {

    let learner_idm = $('#modal_learner_id').val();
    let template_id = $(this).val();

     let errors = {};

    if (!template_id) errors.template_id = ["Please select a template."];
    if (!learner_idm) errors.learner_idm = ["Invalid learner ID."];

    if (Object.keys(errors).length > 0) {
        showFormErrors2(errors);
        return; // stop here
    }

    $.ajax({
        url: "{{ route('notification.renderMessage') }}",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            template_id: template_id,
            learner_id: learner_idm
        },
        success: function (res) {
            $('#waba_final_message').val(res.message);
        }
    });

});
$('#sendWabaMessage').on('click', function (e) {
    e.preventDefault();    
    e.stopPropagation();   
    let templateId = $('#waba_template_select').val();
    let message = $('#waba_final_message').val();
     let learner_id = $('#modal_learner_id').val();
     let mobileNo = $('#learner_mobile_select').val();
   

    let errors = {};

    if (!templateId) errors.template_id = ["Please select a template."];
    if (!mobileNo) errors.mobileNo = ["Please select mobile number."];
    if (!message) errors.message = ["Message cannot be empty."];
    if (!learner_id) errors.learner_id = ["Invalid learner ID."];

    if (Object.keys(errors).length > 0) {
        showFormErrors2(errors);
        return; // stop here
    }

    $.ajax({
        url: "{{ route('notification.sendMessage') }}",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            learner_id: learner_id,
            template_id: templateId,
            message: message,
            mobileNo: mobileNo,
        },
        success: function (res) {
            toastr.success("Message sent successfully!");

            $('#wabaSendModel').modal('hide');

            // Reset form
            $('#modal_learner_id').val('');
            $('#waba_template_select').val('').trigger('change');
            $('#learner_mobile_select').val('').trigger('change');
            $('#waba_final_message').val('');
        },

        error: function (xhr) {
            if (xhr.status === 422) {
                showFormErrors2(xhr.responseJSON.errors);
            } else {
                toastr.error("Something went wrong!");
            }
        }
    });
});

// for text message
$(document).on('click', '.open-text', function () {

    let learnerId = $(this).data('learner_id');
    $('#modal_learner_id2').val(learnerId);

    // Load mobiles in dropdown
    loadLearnerMobiles(learnerId,'learner_mobile_select2');
});
// When template changes → get both values and render final message
$('#text_template_select').on('change', function () {

    let learner_id = $('#modal_learner_id2').val();
    let template_id = $(this).val();

     let errors = {};

    if (!template_id) errors.template_id = ["Please select a template."];
    if (!learner_id) errors.learner_id = ["Invalid learner ID."];

    if (Object.keys(errors).length > 0) {
        showFormErrors2(errors);
        return; // stop here
    }

    $.ajax({
        url: "{{ route('notification.renderMessage') }}",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            template_id: template_id,
            learner_id: learner_id
        },
        success: function (res) {
            $('#text_final_message').val(res.message);
        }
    });

});
$('#sendTextMessage').on('click', function (e) {
    e.preventDefault();    
    e.stopPropagation();   
    let templateId = $('#text_template_select').val();
    let message = $('#text_final_message').val();
     let learner_id = $('#modal_learner_id2').val();
     let mobileNo = $('#learner_mobile_select2').val();

    let errors = {};

    if (!templateId) errors.template_id = ["Please select a template."];
    if (!mobileNo) errors.mobileNo = ["Please select mobile number."];
    if (!message) errors.message = ["Message cannot be empty."];
    if (!learner_id) errors.learner_id = ["Invalid learner ID."];

    if (Object.keys(errors).length > 0) {
        showFormErrors2(errors);
        return; // stop here
    }

    $.ajax({
        url: "{{ route('notification.sendMessage') }}",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            learner_id: learner_id,
            template_id: templateId,
            message: message,
            mobileNo: mobileNo,
        },
        success: function (res) {
            toastr.success("Message sent successfully!");

            $('#textSendModel').modal('hide');

            // Reset form
            $('#modal_learner_id2').val('');
            $('#text_template_select').val('').trigger('change');
            $('#learner_mobile_select2').val('').trigger('change');
            $('#text_final_message').val('');
        },

        error: function (xhr) {
            if (xhr.status === 422) {
                showFormErrors(xhr.responseJSON.errors);
            } else {
                toastr.error("Something went wrong!");
            }
        }
    });
});
//Gift Days Functionality
$(document).on('click', '.giftDaysBtn', function () {

    let learner_id = $(this).data('learner_id');

    // First fetch existing gift days
    $.ajax({
        url: "{{ route('get.gift.days') }}",
        type: "POST",
        data: {
            learner_id: learner_id,
            _token: "{{ csrf_token() }}"
        },
        success: function (res) {

            let existingDays = res.total_gift_days ?? 0;

            Swal.fire({
                title: "Assign Gift Days",
                input: 'number',
                inputLabel: 'Enter number of gift days (+allowed)',
                inputValue: existingDays,   // PREFILL VALUE HERE
                inputPlaceholder: 'e.g. 5',
                showCancelButton: true,
                confirmButtonText: 'Save',
                cancelButtonText: 'Cancel',
                inputAttributes: {
                    min: 1, 
                    step: 1
                },
                iconHtml: '<i class="fas fa-gift fa-3x" style="color:red;font-size:40px;"></i>',
                preConfirm: (value) => {
                     if (value === "" || isNaN(value)) {
                        Swal.showValidationMessage('Please enter a valid number');
                        return false;
                    }

                    if (parseInt(value) <= 0) {
                        Swal.showValidationMessage('Gift days must be greater than 0');
                        return false;
                    }

                    return parseInt(value);
                }
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: "{{ route('assign.gift.days') }}",
                        type: "POST",
                        data: {
                            learner_id: learner_id,
                            gift_days: result.value,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (response) {
                            Swal.fire({
                                icon: "success",
                                title: "Gift Days Updated!",
                                text: response.message
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function () {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: "Something went wrong!"
                            });
                        }
                    });

                }
            });

        }
    });

});

//Frozen
$(document).on('click', '.freezDaysBtn', function () {

    let status = $(this).data('status'); // 0 = Active,1=freez 2 = unfreez
    let learnerDetail = $(this).data('learnerdetail');
    let learner_id = $(this).data('learner_id');

    let title = status == 0 
        ? "Freeze Plan?"
        : "Unfreeze Plan?";

    let text = status == 0 
        ? "Are you sure you want to freeze this learner's plan? Today's date will be saved as freeze start date."
        : "Are you sure you want to unfreeze? Frozen days will be added to plan end date.";

    Swal.fire({
        title: title,
        text: text,
        iconHtml: '<i class="fa-solid fa-snowflake fa-3x" style="color:red;font-size:40px;"></i>',
        showCancelButton: true,
        confirmButtonText: status == 0 ? "Yes, Freeze" : "Yes, Unfreeze",
        cancelButtonText: "Cancel"
    }).then((result) => {

        if (result.isConfirmed) {

            $.ajax({
                 url: "{{ route('freeze.unfreeze') }}",
                type: "POST",
                data: {
                    learnerDetail: learnerDetail,
                    learner_id: learner_id,
                     status: status,
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: response.message
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function () {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Something went wrong!"
                    });
                }
            });

        }
    });

});


//  end 
</script>
