/* ==========================================================================
   Libraro new design — auth page extras (slider, strength meter, OTP)
   ========================================================================== */
(function () {
    'use strict';
    var doc = document;

    /* ---------- Image slider panel (register) ---------- */
    doc.querySelectorAll('[data-lb-slider]').forEach(function (slider) {
        var slides = slider.querySelectorAll('.lb-slide');
        var texts = slider.querySelectorAll('.lb-slide-text');
        var bars = slider.querySelectorAll('.lb-slider-bars button');
        var count = slider.querySelector('.lb-slider-count');
        var n = slides.length, i = 0, timer = null;
        var secs = parseInt(slider.getAttribute('data-seconds') || '5', 10);
        slider.style.setProperty('--lb-slide-dur', secs + 's');
        var pad = function (x) { return (x < 10 ? '0' : '') + x; };

        function show(k) {
            i = (k + n) % n;
            slides.forEach(function (s, j) { s.classList.toggle('is-active', j === i); });
            texts.forEach(function (s, j) { s.classList.toggle('is-active', j === i); });
            bars.forEach(function (b, j) {
                b.classList.toggle('is-done', j < i);
                b.classList.remove('is-active');
                if (j === i) { void b.offsetWidth; b.classList.add('is-active'); }
                b.setAttribute('aria-current', j === i ? 'true' : 'false');
            });
            if (count) count.textContent = pad(i + 1) + ' / ' + pad(n);
            schedule();
        }
        function schedule() {
            clearTimeout(timer);
            if (slider.classList.contains('is-paused')) return;
            timer = setTimeout(function () { show(i + 1); }, secs * 1000);
        }
        bars.forEach(function (b, j) { b.addEventListener('click', function () { show(j); }); });
        var prev = slider.querySelector('[data-prev]'), next = slider.querySelector('[data-next]');
        if (prev) prev.addEventListener('click', function () { show(i - 1); });
        if (next) next.addEventListener('click', function () { show(i + 1); });
        slider.addEventListener('mouseenter', function () { slider.classList.add('is-paused'); clearTimeout(timer); });
        slider.addEventListener('mouseleave', function () { slider.classList.remove('is-paused'); schedule(); });
        if (n > 0) show(0);
    });

    /* ---------- Password strength meter ---------- */
    var LEVELS = [['', ''], ['Weak', '#c0362c'], ['Fair', '#e58212'], ['Good', '#009962'], ['Strong', '#008a48']];
    doc.querySelectorAll('[data-lb-strength]').forEach(function (meter) {
        var input = doc.getElementById(meter.getAttribute('data-lb-strength'));
        if (!input) return;
        var bars = meter.querySelectorAll('.lb-meter-bars span');
        var label = meter.querySelector('.lb-meter-label');
        function update() {
            var p = input.value, s = 0;
            if (p.length >= 8) s++;
            if (/[A-Z]/.test(p) && /[a-z]/.test(p)) s++;
            if (/\d/.test(p)) s++;
            if (/[^A-Za-z0-9]/.test(p) || p.length >= 12) s++;
            if (!p) s = 0; else if (s === 0) s = 1;
            bars.forEach(function (b, k) { b.style.background = k < s ? LEVELS[s][1] : ''; });
            label.textContent = LEVELS[s][0];
            label.style.color = LEVELS[s][1];
        }
        input.addEventListener('input', update);
        update();
    });

    /* ---------- Confirm-password check ---------- */
    doc.querySelectorAll('input[data-lb-match]').forEach(function (confirm) {
        var form = confirm.form;
        var main = doc.getElementById(confirm.getAttribute('data-lb-match'));
        if (!form || !main) return;
        var prevHook = form.lbBeforeValidate;
        form.lbBeforeValidate = function () {
            if (prevHook && prevHook() === false) return false;
            confirm.setCustomValidity(confirm.value && confirm.value !== main.value ? "Passwords don't match." : '');
            confirm.dataset.msg = confirm.value ? "Passwords don't match." : '';
        };
        confirm.addEventListener('input', function () { confirm.setCustomValidity(''); });
    });

    /* ---------- OTP boxes → single hidden field ---------- */
    doc.querySelectorAll('[data-lb-otp]').forEach(function (wrap) {
        var target = doc.getElementById(wrap.getAttribute('data-lb-otp'));
        var boxes = Array.prototype.slice.call(wrap.querySelectorAll('input'));
        var form = target && target.form;
        if (!target) return;

        function sync() {
            var code = boxes.map(function (b) { return b.value; }).join('');
            target.value = code;
            boxes.forEach(function (b) { b.classList.toggle('is-filled', !!b.value); });
            wrap.classList.remove('is-invalid');
            var err = wrap.parentNode.querySelector('.lb-error[data-client]');
            if (err) err.remove();
            return code;
        }
        function fill(str, from, noAuto) {
            var d = (str || '').replace(/\D/g, '').split('');
            for (var k = from; k < boxes.length && d.length; k++) boxes[k].value = d.shift();
            sync();
            var nextEmpty = boxes.find(function (b) { return !b.value; });
            (nextEmpty || boxes[boxes.length - 1]).focus();
            if (!nextEmpty && !noAuto) autoSubmit();
        }
        function autoSubmit() {
            if (!form || form.dataset.autosubmit !== '1') return;
            var btn = form.querySelector('button[type=submit]');
            if (btn && btn.dataset.busy !== '1') btn.click();
        }
        boxes.forEach(function (b, k) {
            b.addEventListener('input', function () {
                var v = b.value.replace(/\D/g, '');
                if (v.length > 1) { b.value = ''; fill(v, k); return; }
                b.value = v;
                sync();
                if (v && k < boxes.length - 1) boxes[k + 1].focus();
                if (sync().length === boxes.length) autoSubmit();
            });
            b.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !b.value && k > 0) { boxes[k - 1].value = ''; boxes[k - 1].focus(); sync(); e.preventDefault(); }
                if (e.key === 'ArrowLeft' && k > 0) { boxes[k - 1].focus(); e.preventDefault(); }
                if (e.key === 'ArrowRight' && k < boxes.length - 1) { boxes[k + 1].focus(); e.preventDefault(); }
            });
            b.addEventListener('paste', function (e) {
                var t = (e.clipboardData || window.clipboardData).getData('text');
                if (!t) return;
                e.preventDefault();
                fill(t, 0);
            });
            b.addEventListener('focus', function () { b.select(); });
        });

        if (target.value) fill(target.value, 0, true); // restore old() value without re-submitting

        if (form) {
            var prevOtpHook = form.lbBeforeValidate;
            form.lbBeforeValidate = function () {
                if (prevOtpHook && prevOtpHook() === false) return false;
                var code = sync();
                if (code.length === boxes.length) return true;
                wrap.classList.add('is-invalid', 'is-shake');
                setTimeout(function () { wrap.classList.remove('is-shake'); }, 500);
                if (!wrap.parentNode.querySelector('.lb-error[data-client]')) {
                    var span = doc.createElement('span');
                    span.className = 'lb-error'; span.setAttribute('data-client', ''); span.setAttribute('role', 'alert');
                    span.textContent = 'Enter all ' + boxes.length + ' digits.';
                    wrap.parentNode.appendChild(span);
                }
                var firstEmpty = boxes.find(function (b) { return !b.value; });
                if (firstEmpty) firstEmpty.focus();
                return false;
            };
        }
        if (!boxes[0].value) setTimeout(function () { boxes[0].focus(); }, 60);
    });
})();
