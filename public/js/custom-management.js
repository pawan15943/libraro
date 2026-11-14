/*
 * Custom Management (single page for library masters).
 * One master per page (grid or list view), add/edit happens in modals and every write goes through the
 * existing master endpoints (master.store / master.delete / branch.update.hidefield).
 * After a write the page HTML is fetched again and only the list is swapped, so
 * locks and dropdowns stay in sync without a full reload.
 *
 * Loaded before the layout's deferred scripts (Bootstrap, SweetAlert, toastr), so
 * everything starts on DOMContentLoaded, which fires after those have run.
 */
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('customMgmt');
    if (!root || !window.jQuery) {
        return;
    }
    var $ = window.jQuery;
    var csrf = root.dataset.csrf;

    // Modals are moved to <body> so no parent overflow/transform can clip them.
    var modals = {};
    root.querySelectorAll('.cm-modal').forEach(function (el) {
        document.body.appendChild(el);
        modals[el.id.replace('cmModal-', '')] = el;
    });

    /* ---------- feedback helpers ---------- */

    /*
     * Apple-style toast (replaces toastr on this page). Stacks in the top-right corner,
     * pauses while hovered, and is announced to screen readers through aria-live.
     */
    var toastStack = document.createElement('div');
    toastStack.className = 'cm-toast-stack';
    toastStack.setAttribute('aria-live', 'polite');
    document.body.appendChild(toastStack);

    var TOASTS = {
        success: { icon: 'fa-check', title: 'Done' },
        error: { icon: 'fa-exclamation', title: 'Something went wrong' },
        info: { icon: 'fa-info', title: 'Note' }
    };

    function notify(type, message) {
        var kind = TOASTS[type] ? type : 'info';
        var toast = document.createElement('div');
        toast.className = 'cm-toast is-' + kind;
        toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');
        toast.innerHTML =
            '<span class="cm-toast-icon"><i class="fa-solid ' + TOASTS[kind].icon + '"></i></span>' +
            '<div class="cm-toast-body"><p class="cm-toast-title"></p><p class="cm-toast-text"></p></div>' +
            '<button type="button" class="cm-toast-close" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>' +
            '<span class="cm-toast-timer"></span>';
        toast.querySelector('.cm-toast-title').textContent = TOASTS[kind].title;
        toast.querySelector('.cm-toast-text').textContent = message;

        var remove = function () {
            if (toast.classList.contains('is-leaving')) {
                return;
            }
            toast.classList.add('is-leaving');
            setTimeout(function () { toast.remove(); }, 250);
        };
        // Errors stay a little longer so they can be read.
        var duration = kind === 'error' ? 6000 : 3500;
        var timer = setTimeout(remove, duration);
        toast.style.setProperty('--cm-toast-duration', duration + 'ms');
        toast.addEventListener('mouseenter', function () {
            clearTimeout(timer);
            toast.classList.add('is-paused');
        });
        toast.addEventListener('mouseleave', function () {
            toast.classList.remove('is-paused');
            timer = setTimeout(remove, 1500);
        });
        toast.querySelector('.cm-toast-close').addEventListener('click', remove);

        toastStack.appendChild(toast);
        // Keep at most three on screen.
        while (toastStack.children.length > 3) {
            toastStack.firstElementChild.remove();
        }
        requestAnimationFrame(function () { toast.classList.add('is-in'); });
    }

    /*
     * Confirm sheet. The action runs while the sheet stays open (spinner on the button);
     * a failure is shown inside the sheet so the user can read it and retry or cancel.
     */
    var CONFIRM_TONES = {
        danger: 'fa-trash',
        warning: 'fa-ban',
        success: 'fa-rotate-left'
    };
    function confirmAction(options) {
        var sheet = modals.confirm;
        var button = sheet.querySelector('.cm-confirm-btn');
        var alertBox = sheet.querySelector('.cm-form-alert');
        var instance = bootstrap.Modal.getOrCreateInstance(sheet);

        sheet.dataset.tone = options.tone;
        sheet.querySelector('.cm-confirm-icon i').className = 'fa-solid ' + CONFIRM_TONES[options.tone];
        sheet.querySelector('.cm-confirm-title').textContent = options.title;
        sheet.querySelector('.cm-confirm-text').textContent = options.text;
        alertBox.classList.add('d-none');
        alertBox.textContent = '';
        button.disabled = false;
        button.textContent = options.confirmLabel;

        button.onclick = function () {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> ' + options.busyLabel;
            alertBox.classList.add('d-none');
            options.run().then(function (message) {
                instance.hide();
                notify('success', message);
                refreshPanes();
            }, function (message) {
                alertBox.textContent = message;
                alertBox.classList.remove('d-none');
                button.disabled = false;
                button.textContent = 'Try again';
            });
        };
        instance.show();
    }

    function firstMessage(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.message) {
            return json.message;
        }
        if (json && json.errors) {
            var first = Object.values(json.errors)[0];
            return Array.isArray(first) ? first[0] : first;
        }
        return fallback;
    }

    /* ---------- tooltips, grid / list view, search, tabs ---------- */

    function initPane(scope) {
        if (window.bootstrap && bootstrap.Tooltip) {
            scope.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                bootstrap.Tooltip.getOrCreateInstance(el);
            });
        }
    }

    function disposePane(scope) {
        if (window.bootstrap && bootstrap.Tooltip) {
            scope.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                var tip = bootstrap.Tooltip.getInstance(el);
                if (tip) {
                    tip.dispose();
                }
            });
        }
    }

    // Grid is the default; the viewer's last choice is remembered on this device only.
    var VIEW_KEY = 'libraro.customManagement.view';
    function setView(view) {
        root.dataset.view = view === 'list' ? 'list' : 'grid';
        root.querySelectorAll('[data-cm-view]').forEach(function (button) {
            button.setAttribute('aria-pressed', button.dataset.cmView === root.dataset.view ? 'true' : 'false');
        });
    }
    try {
        setView(window.localStorage.getItem(VIEW_KEY) || 'grid');
    } catch (e) {
        setView('grid');
    }
    root.addEventListener('click', function (event) {
        var button = event.target.closest('[data-cm-view]');
        if (!button) {
            return;
        }
        setView(button.dataset.cmView);
        try {
            window.localStorage.setItem(VIEW_KEY, root.dataset.view);
        } catch (e) {
            // Storage blocked (private mode): the view still switches for this visit.
        }
    });

    function applySearch(pane) {
        var input = pane.querySelector('.cm-search-input');
        var term = input ? input.value.trim().toLowerCase() : '';
        var shown = 0;
        pane.querySelectorAll('.cm-item').forEach(function (item) {
            var match = !term || item.dataset.search.indexOf(term) !== -1;
            item.classList.toggle('d-none', !match);
            shown += match ? 1 : 0;
        });
        var empty = pane.querySelector('.cm-no-results');
        if (empty) {
            empty.classList.toggle('d-none', shown > 0 || !term);
        }
    }
    root.addEventListener('input', function (event) {
        if (event.target.classList.contains('cm-search-input')) {
            applySearch(event.target.closest('.cm-pane-body'));
        }
    });

    root.querySelectorAll('.cm-pane-body').forEach(initPane);

    // Re-render the panes from fresh server HTML (DOMParser never runs the page's scripts).
    function refreshPanes() {
        return $.get(window.location.pathname).then(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            root.querySelectorAll('.cm-pane-body').forEach(function (pane) {
                var fresh = doc.querySelector('.cm-pane-body[data-pane="' + pane.dataset.pane + '"]');
                if (!fresh) {
                    return;
                }
                var search = pane.querySelector('.cm-search-input');
                var term = search ? search.value : '';
                disposePane(pane);
                pane.innerHTML = fresh.innerHTML;
                setView(root.dataset.view);
                var freshSearch = pane.querySelector('.cm-search-input');
                if (freshSearch && term) {
                    freshSearch.value = term;
                    applySearch(pane);
                }
                initPane(pane);
            });
            // Price dropdowns depend on plans / shifts that may have just changed.
            ['cmPricePlan', 'cmPriceShift'].forEach(function (id) {
                var current = document.getElementById(id);
                var fresh = doc.getElementById(id);
                if (current && fresh) {
                    current.innerHTML = fresh.innerHTML;
                }
            });
        }, function () {
            window.location.reload();
        });
    }

    /* ---------- form helpers ---------- */

    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.cm-field-error').forEach(function (el) {
            el.remove();
        });
        var alertBox = form.querySelector('.cm-form-alert');
        alertBox.classList.add('d-none');
        alertBox.textContent = '';
    }

    function showFormError(form, message) {
        var alertBox = form.querySelector('.cm-form-alert');
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function showFieldErrors(form, errors) {
        var unplaced = [];
        Object.keys(errors).forEach(function (key) {
            var message = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
            var field = form.querySelector('[name="' + key + '"]');
            if (!field || field.type === 'hidden') {
                unplaced.push(message);
                return;
            }
            field.classList.add('is-invalid');
            var feedback = document.createElement('div');
            feedback.className = 'invalid-feedback cm-field-error';
            feedback.textContent = message;
            field.insertAdjacentElement('afterend', feedback);
        });
        if (unplaced.length) {
            showFormError(form, unplaced.join(' '));
        }
    }

    function setBusy(form, busy) {
        var button = form.querySelector('.cm-submit');
        if (busy) {
            button.dataset.label = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Saving...';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.label || button.innerHTML;
        }
    }

    function resetForm(form) {
        form.reset();
        form.querySelector('[name="id"]').value = '';
        clearErrors(form);
    }

    function fillForm(form, record) {
        Object.keys(record).forEach(function (key) {
            var field = form.querySelector('[name="' + key + '"]');
            if (field && record[key] !== null && record[key] !== undefined) {
                field.value = record[key];
            }
        });
    }

    function openModal(key, record) {
        var modalEl = modals[key];
        if (!modalEl) {
            return;
        }
        var form = modalEl.querySelector('form');
        resetForm(form);
        form.dataset.mode = record ? 'edit' : 'add';
        modalEl.querySelector('.modal-title').textContent = record ? form.dataset.titleEdit : form.dataset.titleAdd;
        if (record) {
            fillForm(form, record);
        }
        modalEl.dispatchEvent(new CustomEvent('cm:open', { detail: { record: record || null } }));
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    /* ---------- open / save / status / delete ---------- */

    root.addEventListener('click', function (event) {
        var addBtn = event.target.closest('[data-cm-add]');
        if (addBtn) {
            openModal(addBtn.dataset.cmAdd, null);
            return;
        }
        var editBtn = event.target.closest('[data-cm-edit]');
        if (editBtn) {
            hideTooltip(editBtn);
            openModal(editBtn.dataset.cmEdit, $(editBtn).data('record'));
            return;
        }
        var statusBtn = event.target.closest('[data-cm-status]');
        if (statusBtn) {
            hideTooltip(statusBtn);
            var name = statusBtn.dataset.name || 'this record';
            var activating = statusBtn.dataset.state === 'inactive';
            confirmAction(activating ? {
                tone: 'success',
                title: 'Activate \u201c' + name + '\u201d?',
                text: 'It will be available again in bookings and selection lists.',
                confirmLabel: 'Activate',
                busyLabel: 'Activating...',
                run: function () { return sendDelete(statusBtn.dataset.cmStatus, statusBtn.dataset.id, 'soft'); }
            } : {
                tone: 'warning',
                title: 'Deactivate \u201c' + name + '\u201d?',
                text: 'It will be hidden from bookings and selection lists. Existing records stay as they are, and you can activate it again anytime.',
                confirmLabel: 'Deactivate',
                busyLabel: 'Deactivating...',
                run: function () { return sendDelete(statusBtn.dataset.cmStatus, statusBtn.dataset.id, 'soft'); }
            });
            return;
        }
        var deleteBtn = event.target.closest('[data-cm-delete]');
        if (deleteBtn) {
            hideTooltip(deleteBtn);
            confirmAction({
                tone: 'danger',
                title: 'Delete \u201c' + (deleteBtn.dataset.name || 'this record') + '\u201d?',
                text: 'This permanently removes it and can not be undone. If learners or bookings use it, deactivate it instead.',
                confirmLabel: 'Delete',
                busyLabel: 'Deleting...',
                run: function () { return sendDelete(deleteBtn.dataset.cmDelete, deleteBtn.dataset.id, 'permanent'); }
            });
        }
    });

    function hideTooltip(el) {
        var tip = window.bootstrap && bootstrap.Tooltip.getInstance(el);
        if (tip) {
            tip.hide();
        }
    }

    // Resolves with the success message, rejects with the error message.
    function sendDelete(table, id, deleteType) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: root.dataset.deleteUrl.replace(':id', encodeURIComponent(id)),
                type: 'DELETE',
                data: { _token: csrf, table: table, deleteType: deleteType }
            }).done(function (response) {
                if (response && response.status === 'success') {
                    resolve(response.message);
                } else {
                    reject((response && response.message) || 'Something went wrong.');
                }
            }).fail(function (xhr) {
                reject(firstMessage(xhr, 'Something went wrong. Please try again.'));
            });
        });
    }

    Object.keys(modals).forEach(function (key) {
        var form = modals[key].querySelector('form');
        if (!form) {
            return; // the confirm sheet has no form
        }
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            clearErrors(form);

            var missing = Array.prototype.filter.call(form.querySelectorAll('[required]'), function (field) {
                return !field.closest('.d-none') && !String(field.value).trim();
            });
            if (missing.length) {
                missing.forEach(function (field) {
                    var label = form.querySelector('label[for="' + field.id + '"]');
                    var errors = {};
                    errors[field.name] = (label ? label.textContent.replace('*', '').trim() : 'This field') + ' is required.';
                    showFieldErrors(form, errors);
                });
                missing[0].focus();
                return;
            }

            var formData = new FormData(form);
            formData.append('_token', csrf);
            setBusy(form, true);

            $.ajax({
                url: root.dataset.storeUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            }).done(function (response) {
                if (response && response.success) {
                    bootstrap.Modal.getOrCreateInstance(modals[key]).hide();
                    notify('success', form.dataset.mode === 'edit' ? 'Updated successfully.' : 'Added successfully.');
                    refreshPanes();
                } else if (response && response.errors) {
                    showFieldErrors(form, response.errors);
                } else {
                    showFormError(form, (response && response.message) || 'Something went wrong.');
                }
            }).fail(function (xhr) {
                var json = xhr.responseJSON || {};
                if (xhr.status === 422 && json.errors) {
                    showFieldErrors(form, json.errors);
                } else if (xhr.status === 419) {
                    showFormError(form, 'Your session has expired. Please refresh the page.');
                } else {
                    showFormError(form, firstMessage(xhr, 'Something went wrong. Please try again.'));
                }
            }).always(function () {
                setBusy(form, false);
            });
        });
    });

    // Digit / decimal only inputs.
    document.addEventListener('input', function (event) {
        var field = event.target;
        if (field.classList.contains('cm-digits')) {
            field.value = field.value.replace(/[^0-9]/g, '');
        } else if (field.classList.contains('cm-decimal')) {
            field.value = field.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');
        }
    });

    /* ---------- module specific behaviour ---------- */

    // Floor: live total seats.
    if (modals.floor) {
        var floorForm = modals.floor.querySelector('form');
        var updateFloorTotal = function () {
            var from = parseInt(floorForm.from_seat.value, 10);
            var to = parseInt(floorForm.to_seat.value, 10);
            document.getElementById('cmFloorTotal').value = from > 0 && to >= from ? (to - from + 1) : '';
        };
        floorForm.from_seat.addEventListener('input', updateFloorTotal);
        floorForm.to_seat.addEventListener('input', updateFloorTotal);
        modals.floor.addEventListener('cm:open', updateFloorTotal);
    }

    // Plan: billing-day choices only for MONTH plans (28 x n, 30 x n or calendar wise).
    if (modals.plan) {
        var planForm = modals.plan.querySelector('form');
        var renderPlanDays = function (selected) {
            var wrap = document.getElementById('cmPlanDaysWrap');
            var select = document.getElementById('cmPlanDays');
            var months = parseInt(planForm.plan_id.value, 10);
            if (planForm.type.value !== 'MONTH') {
                wrap.classList.add('d-none');
                select.innerHTML = '<option value=""></option>';
                return;
            }
            wrap.classList.remove('d-none');
            months = months > 0 ? months : 1;
            select.innerHTML =
                '<option value="">' + (months === 1 ? 'Calendar wise' : months + ' Months (Calendar wise)') + '</option>' +
                '<option value="' + (28 * months) + '">' + (28 * months) + ' Days</option>' +
                '<option value="' + (30 * months) + '">' + (30 * months) + ' Days</option>';
            // older plans keep one month's days (28 / 30) on a longer plan: show it as the plan total
            if (months > 1 && (String(selected) === '28' || String(selected) === '30')) {
                selected = parseInt(selected, 10) * months;
            }
            select.value = selected && select.querySelector('option[value="' + selected + '"]') ? String(selected) : '';
        };
        planForm.type.addEventListener('change', function () {
            renderPlanDays(planForm.monthdays.value);
        });
        planForm.plan_id.addEventListener('input', function () {
            renderPlanDays('');
        });
        modals.plan.addEventListener('cm:open', function (event) {
            var record = event.detail.record;
            renderPlanDays(record ? record.monthdays : '');
        });
    }

    // Shift: time pickers, custom name and duration.
    if (modals.shift) {
        var shiftForm = modals.shift.querySelector('form');
        var calcShiftHours = function () {
            var start = shiftForm.start_time.value;
            var end = shiftForm.end_time.value;
            if (!/^\d{2}:\d{2}$/.test(start) || !/^\d{2}:\d{2}$/.test(end)) {
                shiftForm.slot_hours.value = '';
                return;
            }
            var toMinutes = function (time) {
                var parts = time.split(':');
                return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
            };
            var diff = toMinutes(end) - toMinutes(start);
            if (diff === 0) {
                shiftForm.slot_hours.value = 24;
                return;
            }
            if (diff < 0) {
                diff += 24 * 60;
            }
            shiftForm.slot_hours.value = Math.floor(diff / 60);
        };
        // Booking type: only custom shifts can take their timing (and price) at booking.
        var toggleMode = function () {
            var custom = shiftForm.day_type_id.value === '0';
            if (!custom) {
                shiftForm.booking_mode.value = 'fixed';
            }
            var mode = shiftForm.booking_mode.value;
            var needsHours = mode === 'duration' || mode === 'flexible';
            var help = document.getElementById('cmShiftModeHelp');
            document.getElementById('cmShiftModeWrap').classList.toggle('d-none', !custom);
            help.classList.toggle('d-none', mode === 'fixed');
            help.textContent = mode === 'flexible' ? help.dataset.general : help.dataset.seat;
            document.getElementById('cmShiftDurationWrap').classList.toggle('d-none', !needsHours);
            shiftForm.duration_hours.required = needsHours;
        };
        var toggleCustom = function () {
            var custom = shiftForm.day_type_id.value === '0';
            document.getElementById('cmShiftCustomWrap').classList.toggle('d-none', !custom);
            shiftForm.custom_plan_type.required = custom;
            toggleMode();
        };
        shiftForm.booking_mode.addEventListener('change', toggleMode);
        shiftForm.day_type_id.addEventListener('change', toggleCustom);
        shiftForm.start_time.addEventListener('input', calcShiftHours);
        shiftForm.end_time.addEventListener('input', calcShiftHours);
        modals.shift.addEventListener('cm:open', function (event) {
            var record = event.detail.record;
            // An existing shift whose type is no longer offered still shows its own option.
            if (record && record.day_type_id !== null && shiftForm.day_type_id.value !== String(record.day_type_id)) {
                var option = document.createElement('option');
                option.value = record.day_type_id;
                option.textContent = record.custom_plan_type || 'Current shift';
                option.dataset.temporary = '1';
                shiftForm.day_type_id.appendChild(option);
                shiftForm.day_type_id.value = String(record.day_type_id);
            }
            shiftForm.booking_mode.value = (record && record.booking_mode) || 'fixed';
            toggleCustom();
            calcShiftHours();
        });
        modals.shift.addEventListener('hidden.bs.modal', function () {
            shiftForm.querySelectorAll('option[data-temporary]').forEach(function (option) {
                option.remove();
            });
        });
    }

    // Price: suggested price from the 1 Month price, VIP shifts are always free.
    if (modals.price) {
        var priceForm = modals.price.querySelector('form');
        var applyVipRule = function () {
            var option = priceForm.plan_type_id.selectedOptions[0];
            var isVip = option && option.dataset.dayType === '11';
            priceForm.price.readOnly = isVip;
            if (isVip) {
                priceForm.price.value = 0;
            }
            return isVip;
        };
        var suggestPrice = function () {
            if (applyVipRule() || priceForm.dataset.mode === 'edit') {
                return;
            }
            var planId = priceForm.plan_id.value;
            var shiftId = priceForm.plan_type_id.value;
            if (!planId || !shiftId) {
                return;
            }
            $.get(root.dataset.priceUrl, { plan_id: planId, plan_type_id: shiftId }).done(function (price) {
                if (price !== null && price !== '' && Number(price) > 0) {
                    priceForm.price.value = price;
                }
            });
        };
        priceForm.plan_id.addEventListener('change', suggestPrice);
        priceForm.plan_type_id.addEventListener('change', suggestPrice);
        modals.price.addEventListener('cm:open', function (event) {
            // Inactive plans / shifts can't get new prices, only keep their existing one.
            var editing = !!event.detail.record;
            priceForm.querySelectorAll('option[data-inactive]').forEach(function (option) {
                option.hidden = !editing;
            });
            applyVipRule();
        });
    }

    // Show / hide options: save the full hidden list of the branch on every switch.
    function paintToggle(input) {
        var card = input.closest('.cm-toggle-card');
        var state = card.querySelector('.cm-toggle-state');
        card.classList.toggle('is-hidden', input.checked);
        state.querySelector('i').className = 'fa-solid ' + (input.checked ? 'fa-eye-slash' : 'fa-eye');
        state.querySelector('span').textContent = input.checked ? 'Hidden' : 'Visible';
        var section = card.closest('.cm-toggle-section');
        var total = section.querySelectorAll('.cm-toggle-input').length;
        var hiddenCount = section.querySelectorAll('.cm-toggle-input:checked').length;
        section.querySelector('.cm-toggle-count').textContent = total + ' options \u00b7 ' + hiddenCount + ' hidden';
    }
    root.addEventListener('change', function (event) {
        var input = event.target;
        if (!input.classList.contains('cm-toggle-input')) {
            return;
        }
        paintToggle(input);
        var hidden = Array.prototype.map.call(root.querySelectorAll('.cm-toggle-input:checked'), function (el) {
            return el.value;
        });
        var revert = function (message) {
            input.checked = !input.checked;
            paintToggle(input);
            notify('error', message);
        };
        input.disabled = true;
        $.ajax({
            url: root.dataset.toggleUrl,
            type: 'POST',
            data: { _token: csrf, hidden_ids: hidden }
        }).done(function (response) {
            if (response && response.status) {
                notify('success', response.message || 'Updated successfully.');
            } else {
                revert((response && response.message) || 'Could not update.');
            }
        }).fail(function (xhr) {
            revert(firstMessage(xhr, 'Could not update.'));
        }).always(function () {
            input.disabled = false;
        });
    });

    /* ---------- deep links from the old create/edit URLs (?add=1 / ?edit=ID) ---------- */

    var params = new URLSearchParams(window.location.search);
    var activePane = root.querySelector('.cm-pane-body[data-pane="' + root.dataset.activeTab + '"]');
    if (activePane && params.has('edit')) {
        var target = activePane.querySelector('[data-cm-edit][data-id="' + CSS.escape(params.get('edit')) + '"]');
        if (target) {
            target.click();
        } else {
            notify('error', 'This record can not be edited right now.');
        }
    } else if (activePane && params.has('add')) {
        var addButton = activePane.querySelector('[data-cm-add]');
        if (addButton) {
            addButton.click();
        }
    }
    if ((params.has('edit') || params.has('add')) && window.history && history.replaceState) {
        history.replaceState(null, '', window.location.pathname);
    }
});
