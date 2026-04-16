/**
 * Choices.js helpers for multi-select shifts (tag-style UI, search, remove chips).
 * Requires: choices.min.js loaded before this file.
 */
(function (window) {
    'use strict';

    var defaultConfig = {
        removeItemButton: true,
        shouldSort: false,
        searchEnabled: true,
        duplicateItemsAllowed: false,
        allowHTML: false,
        placeholder: true,
        placeholderValue: 'Choose shift(s)',
        classNames: {
            containerOuter: ['choices', 'shift-choices-ui'],
        },
    };

    function nativeEl($sel) {
        if (!$sel) {
            return null;
        }
        return $sel.jquery ? $sel[0] : $sel;
    }

    window.destroyShiftChoices = function ($sel) {
        var el = nativeEl($sel);
        if (!el || el.tagName !== 'SELECT') {
            return;
        }
        if (el._shiftChoices) {
            try {
                el._shiftChoices.destroy();
            } catch (e) { /* noop */ }
            el._shiftChoices = null;
        }
    };

    window.initShiftChoices = function ($sel) {
        var el = nativeEl($sel);
        if (!el || el.tagName !== 'SELECT' || !el.multiple) {
            return;
        }
        if (typeof Choices === 'undefined') {
            return;
        }
        destroyShiftChoices(el);
        if (!el._shiftChoicesJqueryBridge) {
            el._shiftChoicesJqueryBridge = function () {
                if (window.jQuery) {
                    window.jQuery(el).trigger('change');
                }
            };
            el.addEventListener('change', el._shiftChoicesJqueryBridge);
        }
        el._shiftChoices = new Choices(el, defaultConfig);
        // Only truly disabled selects should be disabled; HTML "readonly" on <select> is non-standard
        // and was hiding renew / modal multi-shift options when Choices called .disable().
        if (el.disabled) {
            el._shiftChoices.disable();
        }
        // Some Choices builds do not reliably bubble change to jQuery handlers; sync explicitly.
        if (el._shiftChoices && typeof el._shiftChoices.on === 'function') {
            ['addItem', 'removeItem'].forEach(function (evName) {
                el._shiftChoices.on(evName, function () {
                    window.setTimeout(function () {
                        if (window.jQuery) {
                            window.jQuery(el).trigger('change');
                        }
                    }, 0);
                });
            });
        }
    };

    window.initShiftChoicesAll = function (root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('select.shift-choices-multiple[multiple]').forEach(function (el) {
            initShiftChoices(el);
        });
    };

    /**
     * Outer Choices container. The native <select> is moved inside .choices__inner, so we must not insert siblings on the select.
     */
    function shiftChoicesOuter($sel) {
        if (!$sel || !$sel.length) {
            return window.jQuery ? window.jQuery() : null;
        }
        var $ = window.jQuery;
        var $in = $sel.closest('.choices.shift-choices-ui');
        if ($in.length) {
            return $in;
        }
        return $sel.next('.choices.shift-choices-ui');
    }

    function ensureShiftCapFeedback($sel) {
        var $ = window.jQuery;
        var $outer = shiftChoicesOuter($sel);
        var $fb = $outer.length ? $outer.next('.shift-slot-cap-feedback') : $();
        if (!$fb.length) {
            $fb = $('<div class="invalid-feedback shift-slot-cap-feedback mt-1" style="display:none;" role="alert"></div>');
            if ($outer.length) {
                $outer.after($fb);
            } else {
                $sel.after($fb);
            }
        }
        return $fb;
    }

    /**
     * Clear inline “shifts exceeded hours” message and invalid state (no toast).
     */
    window.clearShiftHoursCapFeedback = function ($sel) {
        if (!window.jQuery || !$sel || !$sel.length) {
            return;
        }
        var $ = window.jQuery;
        $sel.removeClass('is-invalid');
        var $outer = shiftChoicesOuter($sel);
        $outer.removeClass('is-invalid');
        var $fb = $outer.length ? $outer.next('.shift-slot-cap-feedback') : $sel.next('.shift-slot-cap-feedback');
        if ($fb.length) {
            $fb.text('').hide().removeClass('d-block');
        }
    };

    function showShiftHoursCapError($sel, message) {
        var $fb = ensureShiftCapFeedback($sel);
        $fb.text(message || '').show().addClass('d-block');
        $sel.addClass('is-invalid');
        shiftChoicesOuter($sel).addClass('is-invalid');
    }

    /**
     * Branch daily cap: global from layout/QR page, or #branch_id data-max-slot-hours (demo / public forms).
     */
    function resolveMaxSlotHours() {
        var maxH = parseInt(window.BRANCH_MAX_SLOT_HOURS || 0, 10);
        if (maxH > 0) {
            return maxH;
        }
        if (!window.jQuery) {
            return 0;
        }
        var $b = window.jQuery('#branch_id');
        if (!$b.length) {
            return 0;
        }
        var fromData = $b.data('maxSlotHours');
        if (fromData !== undefined && fromData !== null && fromData !== '') {
            maxH = parseInt(fromData, 10);
            if (maxH > 0) {
                return maxH;
            }
        }
        var raw = $b.attr('data-max-slot-hours');
        if (raw !== undefined && raw !== null && raw !== '') {
            maxH = parseInt(raw, 10);
            if (maxH > 0) {
                return maxH;
            }
        }
        return 0;
    }

    function optionSlotHours($sel, id) {
        var $opt = $sel.find('option').filter(function () {
            return String(this.value) === String(id);
        }).first();
        return parseInt($opt.attr('data-slot-hours') || 0, 10);
    }

    /**
     * Choices.js often leaves jQuery .val() as a single string for multi-selects; prefer native selectedOptions.
     */
    function multiSelectSelectedValues($sel) {
        if (!window.jQuery || !$sel || !$sel.length) {
            return [];
        }
        var $ = window.jQuery;
        var el = $sel[0];
        if (el && el.multiple && el.selectedOptions && el.selectedOptions.length) {
            var out = [];
            for (var i = 0; i < el.selectedOptions.length; i++) {
                var val = el.selectedOptions[i].value;
                if (val !== null && val !== undefined && String(val) !== '') {
                    out.push(val);
                }
            }
            if (out.length) {
                return out;
            }
        }
        var v = $sel.val();
        if ($.isArray(v)) {
            return v.filter(Boolean);
        }
        return v ? [v] : [];
    }

    /**
     * Trim multi-select so sum of option data-slot-hours does not exceed branch daily cap.
     * Shows one inline invalid-feedback under the control (not toastr). Price refresh relies on the same change handler.
     */
    window.enforceShiftHoursCap = function ($sel) {
        if (!window.jQuery) {
            return;
        }
        var $ = window.jQuery;
        if (!$sel || !$sel.length || !$sel[0].multiple) {
            return;
        }
        var maxH = resolveMaxSlotHours();
        if (!maxH) {
            window.clearShiftHoursCapFeedback($sel);
            return;
        }
        var ids = multiSelectSelectedValues($sel);
        function sumHours(forIds) {
            var s = 0;
            forIds.forEach(function (id) {
                s += optionSlotHours($sel, id);
            });
            return s;
        }
        var sum = sumHours(ids);
        if (sum <= maxH) {
            window.clearShiftHoursCapFeedback($sel);
            return;
        }
        var overSum = sum;
        while (ids.length && sum > maxH) {
            var drop = ids.pop();
            sum -= optionSlotHours($sel, drop);
        }
        destroyShiftChoices($sel);
        $sel.find('option').prop('selected', false);
        ids.forEach(function (id) {
            $sel.find('option').filter(function () {
                return String(this.value) === String(id);
            }).prop('selected', true);
        });
        initShiftChoices($sel);
        var msg = 'Selected shifts total ' + overSum + ' hours; this branch allows at most ' + maxH +
            ' hours per day. Remove a shift or choose shorter slots.';
        showShiftHoursCapError($sel, msg);
    };
})(window);
