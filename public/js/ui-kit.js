/*
 * Libraro UI kit — the behaviour the admin portal used to take from Bootstrap's JavaScript,
 * in plain JS (≈ 8 KB): Modal (dialog / phone bottom sheet), Dropdown, Collapse, Tooltip and
 * dismissable alerts.
 *
 * It keeps the Bootstrap data-API and class API so existing markup and scripts keep working:
 *   data-bs-toggle="modal|dropdown|collapse|tooltip", data-bs-target, data-bs-dismiss="modal|alert",
 *   window.bootstrap.Modal.getOrCreateInstance(el).show(), new bootstrap.Tooltip(el), and the
 *   show/shown/hide/hidden.bs.* events (native CustomEvents, so jQuery .on() hears them too).
 * New code can use window.UI (same objects).
 */
(function () {
    'use strict';

    var instances = new WeakMap();
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    function emit(el, name, detail) {
        var ev = new CustomEvent(name, { bubbles: true, cancelable: true, detail: detail || {} });
        el.dispatchEvent(ev);
        return !ev.defaultPrevented;
    }

    function store(cls, el, factory) {
        var map = instances.get(el) || {};
        if (!map[cls]) { map[cls] = factory(); instances.set(el, map); }
        return map[cls];
    }

    function getStored(cls, el) { var m = instances.get(el); return m ? m[cls] || null : null; }

    function after(el, ms, fn) {
        if (reduced) { fn(); return; }
        var done = false;
        var finish = function () { if (done) return; done = true; el.removeEventListener('transitionend', onEnd); fn(); };
        var onEnd = function (e) { if (e.target === el) finish(); };
        el.addEventListener('transitionend', onEnd);
        setTimeout(finish, ms + 60);
    }

    function targetOf(trigger) {
        var sel = trigger.getAttribute('data-bs-target') || trigger.getAttribute('href');
        if (!sel || sel === '#' || sel.charAt(0) !== '#') return null;
        try { return document.querySelector(sel); } catch (e) { return null; }
    }

    /* ------------------------------------------------------------------ Modal */
    var openModals = [];

    /* lock page scroll under a modal without the page jumping sideways (classic scrollbars) */
    function lockScroll() {
        var body = document.body;
        if (body.classList.contains('modal-open')) return;
        var bar = window.innerWidth - document.documentElement.clientWidth;
        if (bar > 0) {
            body.style.paddingRight = bar + 'px';
            document.querySelectorAll('.ax-tabbar, #toast-container').forEach(function (n) { n.style.paddingRight = bar + 'px'; });
        }
        body.classList.add('modal-open');
    }

    function unlockScroll() {
        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = '';
        document.querySelectorAll('.ax-tabbar, #toast-container').forEach(function (n) { n.style.paddingRight = ''; });
    }

    function Modal(el, opts) {
        this.el = el;
        this.opts = Object.assign({
            backdrop: el.getAttribute('data-bs-backdrop') || true,
            keyboard: el.getAttribute('data-bs-keyboard') !== 'false'
        }, opts || {});
        this.isShown = false;
        this.backdrop = null;
        this.returnFocus = null;
        var self = this;
        this._onKey = function (e) {
            if (openModals[openModals.length - 1] !== self) return;
            if (e.key === 'Escape' && self.opts.keyboard) { e.preventDefault(); self.hide(); }
            if (e.key === 'Tab') self._trap(e);
        };
        this._downOnBackdrop = false;
        this._onDown = function (e) { self._downOnBackdrop = e.target === self.el; };
        this._onClick = function (e) {
            if (e.target.closest('[data-bs-dismiss="modal"]')) { e.preventDefault(); self.hide(); return; }
            if (e.target === self.el && self._downOnBackdrop && self.opts.backdrop !== 'static') self.hide();
            if (e.target === self.el && self.opts.backdrop === 'static') self._bounce();
        };
    }

    Modal.prototype.show = function (relatedTarget) {
        if (this.isShown) return;
        if (!emit(this.el, 'show.bs.modal', { relatedTarget: relatedTarget })) return;
        this.isShown = true;
        this.returnFocus = relatedTarget || document.activeElement;
        openModals.push(this);

        this.backdrop = document.createElement('div');
        this.backdrop.className = 'modal-backdrop fade';
        document.body.appendChild(this.backdrop);
        lockScroll();

        var el = this.el;
        el.style.display = 'block';
        el.removeAttribute('aria-hidden');
        el.setAttribute('aria-modal', 'true');
        el.setAttribute('role', 'dialog');
        el.addEventListener('mousedown', this._onDown);
        el.addEventListener('click', this._onClick);
        document.addEventListener('keydown', this._onKey);
        void el.offsetHeight; // start the transition from the closed state
        el.classList.add('show');
        this.backdrop.classList.add('show');

        if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '-1');
        var dialog = el.querySelector('.modal-dialog') || el;
        after(dialog, 340, function () {
            var first = el.querySelector('[autofocus]') || (window.innerWidth > 575 ? el.querySelector('.modal-body ' + FOCUSABLE) : null);
            (first || el).focus({ preventScroll: true });
            emit(el, 'shown.bs.modal', { relatedTarget: relatedTarget });
        });
    };

    Modal.prototype.hide = function () {
        if (!this.isShown) return;
        if (!emit(this.el, 'hide.bs.modal')) return;
        this.isShown = false;
        var el = this.el, self = this;
        openModals = openModals.filter(function (m) { return m !== self; });
        el.classList.remove('show');
        if (this.backdrop) this.backdrop.classList.remove('show');
        el.removeEventListener('mousedown', this._onDown);
        el.removeEventListener('click', this._onClick);
        document.removeEventListener('keydown', this._onKey);

        var dialog = el.querySelector('.modal-dialog') || el;
        after(dialog, 340, function () {
            el.style.display = 'none';
            el.setAttribute('aria-hidden', 'true');
            el.removeAttribute('aria-modal');
            if (self.backdrop) { self.backdrop.remove(); self.backdrop = null; }
            if (!openModals.length) unlockScroll();
            if (self.returnFocus && self.returnFocus.focus && document.contains(self.returnFocus)) self.returnFocus.focus({ preventScroll: true });
            emit(el, 'hidden.bs.modal');
        });
    };

    /* a static backdrop does not close: nudge the dialog instead (like iOS / Bootstrap) */
    Modal.prototype._bounce = function () {
        var d = this.el.querySelector('.modal-dialog');
        if (!d || reduced || !d.animate) return;
        d.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.02)' }, { transform: 'scale(1)' }], { duration: 220, easing: 'ease-out' });
    };

    Modal.prototype.toggle = function (rel) { this.isShown ? this.hide() : this.show(rel); };
    Modal.prototype.dispose = function () { this.hide(); var m = instances.get(this.el); if (m) delete m.Modal; };
    Modal.prototype._trap = function (e) {
        var items = Array.prototype.filter.call(this.el.querySelectorAll(FOCUSABLE), function (n) { return n.offsetParent !== null; });
        if (!items.length) { e.preventDefault(); return; }
        var first = items[0], last = items[items.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    };
    Modal.getInstance = function (el) { return getStored('Modal', el); };
    Modal.getOrCreateInstance = function (el, opts) { return store('Modal', el, function () { return new Modal(el, opts); }); };

    /* --------------------------------------------------------------- Dropdown */
    var openDropdown = null;

    function Dropdown(trigger) {
        this.trigger = trigger;
        var parent = trigger.closest('.dropdown, .dropup, .dropend, .dropstart, .btn-group') || trigger.parentElement;
        this.menu = parent.querySelector(':scope > .dropdown-menu') || trigger.nextElementSibling;
        this.parent = parent;
    }

    Dropdown.prototype.isShown = function () { return !!this.menu && this.menu.classList.contains('show') && !this.menu.classList.contains('is-closing'); };

    Dropdown.prototype.show = function () {
        if (!this.menu || this.isShown()) return;
        if (openDropdown && openDropdown !== this) openDropdown.hide();
        if (!emit(this.trigger, 'show.bs.dropdown')) return;
        if (this._closing) this._closing(true); // re-opened while it was fading out
        // data-ui-portal: the menu is shown from <body>, out of a toolbar whose backdrop-filter /
        // view-transition-name would clip its blur and re-base its fixed position
        if (this.menu.hasAttribute('data-ui-portal') && this.menu.parentNode !== document.body) {
            this._home = document.createComment('dropdown-menu');
            this.menu.parentNode.insertBefore(this._home, this.menu);
            document.body.appendChild(this.menu);
        }
        this.menu.classList.add('show');
        this.trigger.setAttribute('aria-expanded', 'true');
        this.trigger.classList.add('show');
        this.place();
        openDropdown = this;
        emit(this.trigger, 'shown.bs.dropdown');
    };

    Dropdown.prototype.hide = function () {
        if (!this.isShown()) return;
        if (!emit(this.trigger, 'hide.bs.dropdown')) return;
        var m = this.menu, self = this;
        this.trigger.setAttribute('aria-expanded', 'false');
        this.trigger.classList.remove('show');
        if (openDropdown === this) openDropdown = null;
        // fade out quickly (CSS .is-closing), then hide
        var finish = function (now) {
            if (self._closing !== finish) return;
            self._closing = null;
            m.removeEventListener('animationend', onEnd);
            m.classList.remove('is-closing');
            if (now) return;
            m.classList.remove('show');
            if (self._home && self._home.parentNode) { self._home.parentNode.insertBefore(m, self._home); self._home.remove(); }
            self._home = null;
        };
        var onEnd = function (e) { if (e.target === m) finish(); };
        this._closing = finish;
        if (reduced) { finish(); } else {
            m.classList.add('is-closing');
            m.addEventListener('animationend', onEnd);
            setTimeout(finish, 220);
        }
        emit(this.trigger, 'hidden.bs.dropdown');
    };

    Dropdown.prototype.toggle = function () { this.isShown() ? this.hide() : this.show(); };

    /* fixed positioning from the trigger, so menus inside scrolling tables are never clipped */
    Dropdown.prototype.place = function () {
        var m = this.menu, r = this.trigger.getBoundingClientRect(), gap = 6;
        m.style.top = '0px'; m.style.left = '0px'; m.style.right = 'auto'; m.style.bottom = 'auto';
        // an ancestor with transform / filter / backdrop-filter makes "fixed" relative to itself:
        // measure where 0,0 really is (without the opening animation's transform), then restart it
        m.style.animation = 'none';
        var o = m.getBoundingClientRect();
        var mw = m.offsetWidth, mh = m.offsetHeight, vw = document.documentElement.clientWidth || window.innerWidth, vh = window.innerHeight;
        var alignEnd = m.classList.contains('dropdown-menu-end') || r.left + mw > vw - 8;
        var left = alignEnd ? r.right - mw : r.left;
        left = Math.max(8, Math.min(left, vw - mw - 8));
        var up = this.parent.classList.contains('dropup') || (r.bottom + gap + mh > vh - 8 && r.top - gap - mh > 8);
        var top = up ? r.top - gap - mh : r.bottom + gap;
        m.style.left = Math.round(left - o.left) + 'px';
        m.style.top = Math.round(top - o.top) + 'px';
        void m.offsetWidth;
        m.style.animation = '';
        m.style.transformOrigin = (up ? 'bottom ' : 'top ') + (alignEnd ? 'right' : 'left');
    };

    Dropdown.prototype.focusItem = function (dir) {
        var items = Array.prototype.filter.call(this.menu.querySelectorAll('.dropdown-item:not(.disabled):not(:disabled)'), function (n) { return n.offsetParent !== null; });
        if (!items.length) return;
        var i = items.indexOf(document.activeElement);
        items[(i + dir + items.length) % items.length].focus();
    };

    Dropdown.getInstance = function (el) { return getStored('Dropdown', el); };
    Dropdown.getOrCreateInstance = function (el) { return store('Dropdown', el, function () { return new Dropdown(el); }); };

    /* --------------------------------------------------------------- Collapse */
    function Collapse(el) { this.el = el; }
    Collapse.prototype.isShown = function () { return this.el.classList.contains('show'); };
    Collapse.prototype.show = function () {
        var el = this.el;
        if (this.isShown() || el.classList.contains('collapsing')) return;
        if (!emit(el, 'show.bs.collapse')) return;
        el.classList.remove('collapse');
        el.classList.add('collapsing');
        el.style.height = '0px';
        var h = el.scrollHeight;
        requestAnimationFrame(function () { el.style.height = h + 'px'; });
        after(el, 340, function () {
            el.classList.remove('collapsing');
            el.classList.add('collapse', 'show');
            el.style.height = '';
            emit(el, 'shown.bs.collapse');
        });
        this.sync(true);
    };
    Collapse.prototype.hide = function () {
        var el = this.el;
        if (!this.isShown() || el.classList.contains('collapsing')) return;
        if (!emit(el, 'hide.bs.collapse')) return;
        el.style.height = el.getBoundingClientRect().height + 'px';
        void el.offsetHeight;
        el.classList.add('collapsing');
        el.classList.remove('collapse', 'show');
        requestAnimationFrame(function () { el.style.height = '0px'; });
        after(el, 340, function () {
            el.classList.remove('collapsing');
            el.classList.add('collapse');
            el.style.height = '';
            emit(el, 'hidden.bs.collapse');
        });
        this.sync(false);
    };
    Collapse.prototype.toggle = function () { this.isShown() ? this.hide() : this.show(); };
    Collapse.prototype.sync = function (open) {
        var id = this.el.id;
        if (!id) return;
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function (t) {
            if (targetOf(t) && targetOf(t).id === id) { t.setAttribute('aria-expanded', open ? 'true' : 'false'); t.classList.toggle('collapsed', !open); }
        });
    };
    Collapse.getInstance = function (el) { return getStored('Collapse', el); };
    Collapse.getOrCreateInstance = function (el) { return store('Collapse', el, function () { return new Collapse(el); }); };

    /* ---------------------------------------------------------------- Tooltip */
    var tipEl = null;

    function Tooltip(el) {
        this.el = el;
        var t = el.getAttribute('data-bs-title') || el.getAttribute('title') || el.getAttribute('data-original-title') || '';
        this.text = t;
        if (el.getAttribute('title')) { el.setAttribute('data-bs-title', t); el.removeAttribute('title'); }
        if (!el.getAttribute('aria-label') && t && !el.textContent.trim()) el.setAttribute('aria-label', t);
        var self = this;
        this._show = function () { self.show(); };
        this._hide = function () { self.hide(); };
        el.addEventListener('mouseenter', this._show);
        el.addEventListener('focus', this._show);
        el.addEventListener('mouseleave', this._hide);
        el.addEventListener('blur', this._hide);
        el.addEventListener('click', this._hide);
    }
    Tooltip.prototype.show = function () {
        if (!this.text || window.matchMedia('(hover: none)').matches) return;
        if (!tipEl) { tipEl = document.createElement('div'); tipEl.className = 'ui-tooltip'; tipEl.setAttribute('role', 'tooltip'); document.body.appendChild(tipEl); }
        tipEl.textContent = this.text;
        var r = this.el.getBoundingClientRect();
        tipEl.style.left = '0px'; tipEl.style.top = '0px';
        var w = tipEl.offsetWidth, h = tipEl.offsetHeight;
        var below = (this.el.getAttribute('data-bs-placement') || 'top') === 'bottom' || r.top - h - 8 < 4;
        tipEl.style.left = Math.round(Math.max(6, Math.min(r.left + r.width / 2 - w / 2, window.innerWidth - w - 6))) + 'px';
        tipEl.style.top = Math.round(below ? r.bottom + 8 : r.top - h - 8) + 'px';
        tipEl.classList.add('show');
    };
    Tooltip.prototype.hide = function () { if (tipEl) tipEl.classList.remove('show'); };
    Tooltip.prototype.setContent = function (o) { this.text = typeof o === 'string' ? o : (o && o['.tooltip-inner']) || this.text; };
    Tooltip.prototype.dispose = function () {
        this.hide();
        ['mouseenter', 'focus'].forEach(function (n) { this.el.removeEventListener(n, this._show); }, this);
        ['mouseleave', 'blur', 'click'].forEach(function (n) { this.el.removeEventListener(n, this._hide); }, this);
        if (this.text && !this.el.getAttribute('title')) this.el.setAttribute('title', this.text);
        var m = instances.get(this.el); if (m) delete m.Tooltip;
    };
    Tooltip.getInstance = function (el) { return getStored('Tooltip', el); };
    Tooltip.getOrCreateInstance = function (el) { return store('Tooltip', el, function () { return new Tooltip(el); }); };
    // `new bootstrap.Tooltip(el)` must also register the instance
    function TooltipCtor(el) { return Tooltip.getOrCreateInstance(el); }
    TooltipCtor.getInstance = Tooltip.getInstance;
    TooltipCtor.getOrCreateInstance = Tooltip.getOrCreateInstance;
    function ModalCtor(el, o) { return Modal.getOrCreateInstance(el, o); }
    ModalCtor.getInstance = Modal.getInstance;
    ModalCtor.getOrCreateInstance = Modal.getOrCreateInstance;
    function DropdownCtor(el) { return Dropdown.getOrCreateInstance(el); }
    DropdownCtor.getInstance = Dropdown.getInstance;
    DropdownCtor.getOrCreateInstance = Dropdown.getOrCreateInstance;
    function CollapseCtor(el, o) { var c = Collapse.getOrCreateInstance(el); if (!o || o.toggle !== false) c.toggle(); return c; }
    CollapseCtor.getInstance = Collapse.getInstance;
    CollapseCtor.getOrCreateInstance = Collapse.getOrCreateInstance;

    /* -------------------------------------------------------------- data-API */
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-bs-toggle]');
        if (t) {
            var kind = t.getAttribute('data-bs-toggle');
            if (kind === 'modal') {
                var m = targetOf(t);
                if (m) { e.preventDefault(); Modal.getOrCreateInstance(m).show(t); }
                return;
            }
            if (kind === 'dropdown') { e.preventDefault(); Dropdown.getOrCreateInstance(t).toggle(); return; }
            if (kind === 'collapse') {
                var c = targetOf(t);
                if (c) { e.preventDefault(); Collapse.getOrCreateInstance(c).toggle(); }
                return;
            }
        }
        var dismiss = e.target.closest('[data-bs-dismiss="alert"]');
        if (dismiss) { var a = dismiss.closest('.alert, .ui-banner'); if (a) a.remove(); return; }

        // outside click / item click closes the open dropdown
        if (openDropdown) {
            var inMenu = openDropdown.menu.contains(e.target);
            if (!inMenu && !openDropdown.trigger.contains(e.target)) openDropdown.hide();
            else if (inMenu && e.target.closest('.dropdown-item') && openDropdown.trigger.getAttribute('data-bs-auto-close') !== 'outside') openDropdown.hide();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (!openDropdown) {
            var t = e.target.closest && e.target.closest('[data-bs-toggle="dropdown"]');
            if (t && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) { e.preventDefault(); var d = Dropdown.getOrCreateInstance(t); d.show(); d.focusItem(e.key === 'ArrowDown' ? 1 : -1); }
            return;
        }
        if (e.key === 'Escape') { var trig = openDropdown.trigger; openDropdown.hide(); trig.focus(); e.stopPropagation(); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); openDropdown.focusItem(1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); openDropdown.focusItem(-1); }
        else if (e.key === 'Tab') openDropdown.hide();
    });

    window.addEventListener('resize', function () { if (openDropdown) openDropdown.hide(); });
    document.addEventListener('scroll', function (e) {
        if (openDropdown && !openDropdown.menu.contains(e.target)) openDropdown.hide();
    }, true);

    // modals that ship with class "show" (server-rendered open) are opened properly
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.modal.show').forEach(function (m) { m.classList.remove('show'); Modal.getOrCreateInstance(m).show(); });
    });

    var api = { Modal: ModalCtor, Dropdown: DropdownCtor, Collapse: CollapseCtor, Tooltip: TooltipCtor };
    window.UI = api;
    if (!window.bootstrap) window.bootstrap = api; // compatibility with scripts written for Bootstrap 5
})();
