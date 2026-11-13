/*
 * Libraro admin portal UI (layouts/admin.blade.php)
 *  - sidebar: collapsible groups, remembered open state, menu search
 *  - tables: row actions. Up to 3 actions stay as icon buttons; more than 3
 *    become one "⋯" dropdown menu. Works for server rendered tables and for
 *    rows DataTables draws later.
 */
(function () {
    'use strict';

    var MAX_INLINE = 3;

    // DataTables defaults for every admin list (pages set pageLength 10 or 15)
    if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
        jQuery.extend(true, jQuery.fn.dataTable.defaults, {
            lengthMenu: [10, 15, 25, 50, 100],
            language: { search: '', searchPlaceholder: 'Search', lengthMenu: '_MENU_ per page', emptyTable: 'Nothing here yet', zeroRecords: 'No matching records' }
        });
    }
    var store = {
        get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
        set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
    };

    /* ---------------- loading bar ----------------
     * A thin navy bar at the top: while the page loads, when a link or a form
     * opens another page, and while AJAX calls (DataTables, saves) run.
     * A page opened from a prerender (Speculation Rules) is already loaded, so it
     * never shows the bar; a click shows it only if the next page takes > 150 ms.
     */
    var progress = (function () {
        var el = document.getElementById('axProgress');
        var bar = el && el.firstElementChild;
        var value = 0, trickle = null, active = 0, navigating = false, pending = null;

        function set(v) { value = v; if (bar) bar.style.width = (v * 100) + '%'; }

        function start() {
            if (!el || document.prerendering) return;
            if (!el.classList.contains('is-on')) { set(0); el.classList.add('is-on'); }
            requestAnimationFrame(function () { set(Math.max(value, .12)); });
            clearInterval(trickle);
            trickle = setInterval(function () {
                // slows down as it gets closer to the end, never reaches it on its own
                set(value + (0.9 - value) * 0.08);
            }, 220);
        }

        function done() {
            clearTimeout(pending);
            if (!el || navigating) return;
            clearInterval(trickle);
            if (!el.classList.contains('is-on')) return;
            set(1);
            setTimeout(function () { el.classList.remove('is-on'); setTimeout(function () { set(0); }, 300); }, 260);
        }

        return {
            start: start,
            done: done,
            navigate: function () {
                navigating = true;
                clearTimeout(pending);
                pending = setTimeout(start, 150);
                // a download or a slow server keeps us on this page: never leave the bar running
                setTimeout(function () { if (navigating) { navigating = false; done(); } }, 12000);
            },
            reset: function () { navigating = false; active = 0; done(); },
            ajaxStart: function () { active++; start(); },
            ajaxStop: function () { active = Math.max(0, active - 1); if (!active) done(); }
        };
    })();

    window.axProgress = progress;
    if (document.readyState !== 'complete') progress.start(); // the page itself is loading
    window.addEventListener('load', function () { progress.done(); });
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        progress.reset(); // back/forward cache: the page we left is shown again
        document.body.classList.remove('ax-nav-open');
    });

    function isPlainLink(a) {
        var href = a.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return false;
        if (a.target && a.target !== '_self') return false;
        if (a.hasAttribute('download') || a.getAttribute('data-bs-toggle')) return false;
        if (a.origin !== window.location.origin) return false;
        if (a.pathname === window.location.pathname && a.search === window.location.search && a.hash) return false;
        return true;
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (isPlainLink(a)) progress.navigate();
    });

    document.addEventListener('submit', function (e) {
        // wait one tick: validation or AJAX handlers may cancel the submit
        setTimeout(function () {
            if (!e.defaultPrevented && !(e.target.getAttribute('target') || '').match(/_blank/)) progress.navigate();
        }, 0);
    });

    /* ---------------- prefetch on hover (browsers without Speculation Rules) ----------------
     * Chrome / Edge prerender links from the <script type="speculationrules"> in the layout.
     * Elsewhere: <link rel=prefetch> once per URL, after a short hover or on touch.
     */
    (function () {
        var cfg = window.axConfig || {};
        if (window.HTMLScriptElement && HTMLScriptElement.supports && HTMLScriptElement.supports('speculationrules')) return;
        if (navigator.connection && (navigator.connection.saveData || /2g/.test(navigator.connection.effectiveType || ''))) return;
        var link = document.createElement('link');
        if (!(link.relList && link.relList.supports && link.relList.supports('prefetch'))) return;
        var words = (cfg.noPrefetch || []).map(function (w) { return String(w).toLowerCase(); });
        var done = {}, timer = null;

        function eligible(a) {
            if (!isPlainLink(a) || a.hasAttribute('onclick') || a.hasAttribute('data-no-prefetch')) return false;
            var url = a.href.split('#')[0];
            if (done[url] || url === window.location.href.split('#')[0]) return false;
            var low = url.toLowerCase();
            return !words.some(function (w) { return low.indexOf(w) > -1; });
        }

        function prefetch(a) {
            var url = a.href.split('#')[0];
            done[url] = true;
            var l = document.createElement('link');
            l.rel = 'prefetch';
            l.href = url;
            document.head.appendChild(l);
        }

        document.addEventListener('pointerover', function (e) {
            if (e.pointerType && e.pointerType !== 'mouse') return;
            var a = e.target.closest && e.target.closest('a[href]');
            if (!a || !eligible(a)) return;
            clearTimeout(timer);
            timer = setTimeout(function () { prefetch(a); }, 80);
            a.addEventListener('pointerout', function () { clearTimeout(timer); }, { once: true });
        }, { passive: true });
        document.addEventListener('touchstart', function (e) {
            var a = e.target.closest && e.target.closest('a[href]');
            if (a && eligible(a)) prefetch(a);
        }, { passive: true });
    })();

    /* ---------------- SweetAlert on demand ----------------
     * The layout loads SweetAlert only on pages whose HTML uses it. If some other code
     * calls Swal anyway (HTML loaded later by AJAX …), load it then and run the call.
     */
    if (!window.Swal && window.axConfig && window.axConfig.swal) {
        (function () {
            var loading = null, shim;
            function real() { return window.Swal && window.Swal !== shim ? window.Swal : null; }
            function load() {
                if (!loading) {
                    loading = new Promise(function (resolve, reject) {
                        var s = document.createElement('script');
                        s.src = window.axConfig.swal;
                        s.onload = function () { real() ? resolve(real()) : reject(new Error('SweetAlert did not load')); };
                        s.onerror = reject;
                        document.head.appendChild(s);
                    });
                }
                return loading;
            }
            function later(method) {
                return function () {
                    var args = arguments;
                    return load().then(function (S) { return S[method].apply(S, args); });
                };
            }
            shim = {
                fire: later('fire'),
                mixin: function (opts) {
                    return { fire: function () { var args = arguments; return load().then(function (S) { var m = S.mixin(opts); return m.fire.apply(m, args); }); } };
                },
                isVisible: function () { return real() ? real().isVisible() : false; },
                isLoading: function () { return real() ? real().isLoading() : false; },
                close: function () { if (real()) real().close.apply(real(), arguments); },
                DismissReason: { cancel: 'cancel', backdrop: 'backdrop', close: 'close', esc: 'esc', timer: 'timer' }
            };
            ['showLoading', 'hideLoading', 'update', 'showValidationMessage', 'resetValidationMessage', 'clickConfirm', 'clickDeny', 'clickCancel'].forEach(function (m) {
                shim[m] = function () { if (real() && real()[m]) return real()[m].apply(real(), arguments); };
            });
            window.Swal = shim;
        })();
    }

    /* ---------------- offline / online notice ---------------- */
    window.addEventListener('offline', function () {
        if (window.toastr) toastr.error('Check your internet connection.', 'You are offline', { timeOut: 0, extendedTimeOut: 0, closeButton: true, preventDuplicates: true });
    });
    window.addEventListener('online', function () {
        if (window.toastr) { toastr.clear(); toastr.success('', 'Back online', { timeOut: 1800 }); }
    });

    if (window.jQuery) {
        jQuery(document).on('ajaxSend', progress.ajaxStart).on('ajaxComplete', progress.ajaxStop);
    }

    /* ---------------- shell: sidebar toggle, phone sheet, Cmd/Ctrl+K ----------------
     * Desktop: the toolbar button collapses / expands the sidebar (remembered).
     * Phone / tablet (≤ 991px): the sidebar is a sheet that slides in over a scrim; focus
     * moves into it, Tab stays inside, Esc / scrim / a link closes it and focus returns.
     */
    function initShell() {
        var body = document.body;
        var sidebar = document.getElementById('axSidebar');
        var toggle = document.getElementById('axNavToggle');
        var scrim = document.getElementById('axScrim');
        var more = document.getElementById('axTabMore');
        var search = document.getElementById('axNavSearch');
        if (!sidebar) return;
        var returnTo = null;

        function mobile() { return window.innerWidth <= 991; }
        function isOpen() { return body.classList.contains('ax-nav-open'); }
        function sync() {
            if (toggle) toggle.setAttribute('aria-expanded', (mobile() ? isOpen() : !body.classList.contains('ax-collapsed')) ? 'true' : 'false');
            if (more) more.setAttribute('aria-expanded', isOpen() ? 'true' : 'false');
        }

        function openSheet(from, focusEl) {
            returnTo = from || document.activeElement;
            body.classList.add('ax-nav-open');
            sync();
            // focus the sheet itself (no ring on touch); Tab then walks its links
            var target = focusEl || sidebar;
            requestAnimationFrame(function () { target.focus({ preventScroll: true }); });
        }

        function closeSheet(restore) {
            if (!isOpen()) return;
            body.classList.remove('ax-nav-open');
            sync();
            if (restore !== false && returnTo && document.contains(returnTo) && sidebar.contains(document.activeElement)) returnTo.focus({ preventScroll: true });
            returnTo = null;
        }

        function toggleNav() {
            if (mobile()) { isOpen() ? closeSheet() : openSheet(toggle); return; }
            body.classList.toggle('ax-collapsed');
            store.set('axSidebar', body.classList.contains('ax-collapsed') ? 'collapsed' : 'open');
            sync();
        }

        if (toggle) toggle.addEventListener('click', toggleNav);
        if (more) more.addEventListener('click', function () { openSheet(more); });
        if (scrim) scrim.addEventListener('click', function () { closeSheet(); });

        // a link in the phone sheet: let the sheet slide away while the next page loads
        sidebar.addEventListener('click', function (e) {
            var a = e.target.closest('a[href]');
            if (a && mobile() && isOpen() && !e.metaKey && !e.ctrlKey) closeSheet(false);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen() && !document.querySelector('.modal.show')) { closeSheet(); return; }
            if (e.key === 'Tab' && isOpen() && mobile()) {
                var items = Array.prototype.filter.call(sidebar.querySelectorAll('a[href], button:not([disabled]), input'), function (n) { return n.offsetParent !== null; });
                if (!items.length) return;
                var first = items[0], last = items[items.length - 1];
                if (!sidebar.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
                else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                return;
            }
            // Cmd/Ctrl + K focuses the menu search
            if ((e.metaKey || e.ctrlKey) && !e.altKey && !e.shiftKey && (e.key || '').toLowerCase() === 'k') {
                e.preventDefault();
                if (mobile()) { if (!isOpen()) openSheet(document.activeElement, search); else if (search) search.focus(); return; }
                if (body.classList.contains('ax-collapsed')) { body.classList.remove('ax-collapsed'); store.set('axSidebar', 'open'); sync(); }
                if (search) { search.focus(); search.select(); }
            }
        });

        var wasMobile = mobile();
        window.addEventListener('resize', function () {
            if (mobile() === wasMobile) return;
            wasMobile = mobile();
            if (!wasMobile) closeSheet(false);
            sync();
        });

        // the ⌘K hint says Ctrl K off Apple devices
        var kbd = sidebar.querySelector('.ax-kbd');
        if (kbd && !/Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent)) kbd.textContent = 'Ctrl K';
        sync();
    }

    initShell();

    /* ---------------- sidebar groups ---------------- */
    function initNav() {
        var nav = document.getElementById('axNav');
        if (!nav) return;
        // Accordion: one group open at a time. On load the group holding the current page is
        // the open one; otherwise the group the admin opened last.
        var groups = Array.prototype.slice.call(nav.querySelectorAll('.ax-group'));
        var activeGroup = groups.filter(function (g) { return g.classList.contains('has-active'); })[0];
        var lastOpen = store.get('axNavOpen');
        groups.forEach(function (g) {
            var keep = activeGroup ? g === activeGroup : g.getAttribute('data-group') === lastOpen;
            setOpen(g, keep);
            g.querySelector('.ax-group-btn').addEventListener('click', function () {
                var open = !g.classList.contains('is-open');
                groups.forEach(function (other) { if (other !== g) setOpen(other, false); });
                setOpen(g, open);
                store.set('axNavOpen', open ? g.getAttribute('data-group') : '');
            });
        });

        function setOpen(g, open) {
            g.classList.toggle('is-open', open);
            g.querySelector('.ax-group-btn').setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        var search = document.getElementById('axNavSearch');
        if (!search) return;
        search.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase(), any = false;
            nav.classList.toggle('is-searching', !!q);
            nav.querySelectorAll('.ax-top .ax-item').forEach(function (li) {
                var hit = !q || li.textContent.toLowerCase().indexOf(q) > -1;
                li.style.display = hit ? '' : 'none';
                if (hit) any = true;
            });
            nav.querySelectorAll('.ax-group').forEach(function (g) {
                var title = g.querySelector('.ax-group-btn .ax-label').textContent.toLowerCase();
                var titleHit = q && title.indexOf(q) > -1, shown = 0;
                g.querySelectorAll('.ax-sub .ax-item').forEach(function (li) {
                    var hit = !q || titleHit || li.textContent.toLowerCase().indexOf(q) > -1;
                    li.style.display = hit ? '' : 'none';
                    if (hit) shown++;
                });
                g.style.display = shown ? '' : 'none';
                g.classList.toggle('is-search-open', !!q && shown > 0);
                if (shown) any = true;
            });
            document.getElementById('axNavEmpty').style.display = any ? 'none' : 'block';
        });
        search.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                var link = Array.prototype.find.call(nav.querySelectorAll('.ax-item'), function (li) {
                    return li.style.display !== 'none' && li.offsetParent !== null;
                });
                if (link) window.location = link.querySelector('a').href;
            }
            if (e.key === 'Escape') { this.value = ''; this.dispatchEvent(new Event('input')); this.blur(); }
        });

        var active = nav.querySelector('.is-active');
        if (active) active.scrollIntoView({ block: 'nearest' });
    }

    /* ---------------- row actions ---------------- */
    var ICON_LABELS = [
        ['fa-eye', 'View'], ['fa-pen-to-square', 'Edit'], ['fa-edit', 'Edit'], ['fa-pen', 'Edit'], ['fa-pencil', 'Edit'],
        ['fa-trash', 'Delete'], ['fa-trash-can', 'Delete'], ['fa-trash-alt', 'Delete'], ['fa-building', 'Branches'],
        ['fa-key', 'Permissions'], ['fa-credit-card', 'Payment'], ['fa-print', 'Print'], ['fa-download', 'Download'],
        ['fa-phone', 'Call'], ['fa-whatsapp', 'WhatsApp'], ['fa-envelope', 'Email'], ['fa-copy', 'Copy'],
        ['fa-toggle-on', 'Turn off'], ['fa-toggle-off', 'Turn on'], ['fa-ban', 'Disable'], ['fa-check', 'Approve'],
        ['fa-circle-check', 'Activate'], ['fa-rotate', 'Renew'], ['fa-arrow-up', 'Upgrade'], ['fa-lock', 'Access'],
        ['fa-power-off', 'Deactivate'], ['fa-share', 'Share'], ['fa-history', 'History'], ['fa-clock-rotate-left', 'History']
    ];

    function iconOf(el) { return el.querySelector('i, svg'); }

    function labelOf(el) {
        var t = el.getAttribute('data-bs-title') || el.getAttribute('data-original-title') || el.getAttribute('title') ||
            el.getAttribute('aria-label') || '';
        var btn = el.matches('form') ? el.querySelector('button, [type=submit]') : null;
        if (!t && btn) t = btn.getAttribute('data-bs-title') || btn.getAttribute('data-original-title') || btn.getAttribute('title') || '';
        if (!t) t = (el.textContent || '').trim();
        if (!t) {
            var i = iconOf(el), cls = i ? (i.getAttribute('class') || '') : '';
            for (var k = 0; k < ICON_LABELS.length; k++) {
                if ((' ' + cls + ' ').indexOf(' ' + ICON_LABELS[k][0] + ' ') > -1) { t = ICON_LABELS[k][1]; break; }
            }
        }
        return (t || 'Action').replace(/\s+/g, ' ').trim();
    }

    function isDanger(el, label) {
        var i = iconOf(el), cls = i ? (i.getAttribute('class') || '') : '';
        return /fa-trash|fa-ban|fa-power-off|fa-xmark|fa-times/.test(cls) || /delete|remove|deactivate/i.test(label) ||
            /btn-danger|text-danger|delete/.test(el.className || '');
    }

    /* drop a tooltip so it does not stick to a moved element */
    function killTooltip(el) {
        [el].concat(Array.prototype.slice.call(el.querySelectorAll('[data-bs-toggle="tooltip"]'))).forEach(function (n) {
            if (n.getAttribute('data-bs-toggle') !== 'tooltip') return;
            if (window.bootstrap && bootstrap.Tooltip) {
                var tip = bootstrap.Tooltip.getInstance(n);
                if (tip) tip.dispose();
            }
            if (!n.getAttribute('title') && n.getAttribute('data-bs-title')) n.setAttribute('title', n.getAttribute('data-bs-title'));
            n.removeAttribute('data-bs-toggle');
        });
    }

    /* the clickable things of one action cell, in order */
    function collectActions(container) {
        var list = [];
        var nodes = container.matches('ul') ? Array.prototype.map.call(container.children, function (li) {
            return li.firstElementChild ? (li.children.length === 1 ? li.firstElementChild : li) : null;
        }) : Array.prototype.slice.call(container.children);
        nodes.forEach(function (n) {
            if (!n) return;
            if (n.matches('a, button, form, i, li')) list.push(n);
        });
        return list;
    }

    function buildMenu(container, actions) {
        var wrap = document.createElement('div');
        wrap.className = 'dropdown ax-row-menu';
        wrap.innerHTML =
            '<button type="button" class="ax-kebab" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions" ' +
            'data-bs-popper-config=\'{"strategy":"fixed"}\'><i class="fa-solid fa-ellipsis"></i></button>' +
            '<ul class="dropdown-menu dropdown-menu-end ax-action-menu"></ul>';
        var menu = wrap.querySelector('ul'), danger = [];

        actions.forEach(function (node) {
            var label = labelOf(node);
            killTooltip(node);
            var li = document.createElement('li');
            node.classList.add('ax-action-item');
            if (node.matches('form')) {
                var b = node.querySelector('button, [type=submit]');
                if (b) { b.classList.add('ax-action-item'); if (!(b.textContent || '').trim()) b.setAttribute('data-ax-label', label); }
                node.classList.add('ax-action-form');
            } else if (!(node.textContent || '').trim()) {
                node.setAttribute('data-ax-label', label);
            }
            li.appendChild(node);
            if (isDanger(node, label)) { li.classList.add('is-danger'); danger.push(li); } else { menu.appendChild(li); }
        });

        if (danger.length) {
            if (menu.children.length) {
                var hr = document.createElement('li');
                hr.innerHTML = '<hr class="dropdown-divider">';
                menu.appendChild(hr);
            }
            danger.forEach(function (li) { menu.appendChild(li); });
        }

        container.innerHTML = '';
        if (container.matches('ul')) {
            var holder = document.createElement('li');
            holder.className = 'ax-menu-holder';
            holder.appendChild(wrap);
            container.appendChild(holder);
        } else {
            container.appendChild(wrap);
        }
        container.classList.add('ax-actions', 'is-menu');
    }

    function enhanceCell(container) {
        if (container.getAttribute('data-ax-done')) return;
        container.setAttribute('data-ax-done', '1');
        var actions = collectActions(container);
        if (!actions.length) return;
        if (actions.length > MAX_INLINE) {
            buildMenu(container, actions);
        } else {
            container.classList.add('ax-actions', 'is-inline');
            actions.forEach(function (n) {
                var target = n.matches('form') ? (n.querySelector('button, [type=submit]') || n) : n;
                if (target.matches('li')) target = target.querySelector('a, button') || target;
                target.classList.add('ax-act');
                if (isDanger(n, labelOf(n))) target.classList.add('is-danger');
                if (!target.getAttribute('title') && !target.getAttribute('data-bs-title')) target.setAttribute('title', labelOf(n));
            });
        }
    }

    /* icon-only links/buttons sitting together in the last cell of a row */
    function looseActionCell(td) {
        if (td.querySelector('.ax-actions, ul.actionalbls, .la-btn')) return null;
        var kids = Array.prototype.filter.call(td.querySelectorAll('a, button, form'), function (n) {
            if (n.closest('form') && n.closest('form') !== n) return false; // count forms, not their buttons
            if (n.matches('form')) return true;
            return !!iconOf(n) && (n.textContent || '').replace(/\s+/g, '').length <= 2;
        });
        if (kids.length < 2) return null;
        var parent = kids[0].parentElement;
        if (!kids.every(function (k) { return k.parentElement === parent; })) return null;
        return parent;
    }

    function enhanceActions(root) {
        (root || document).querySelectorAll('body.ax ul.actionalbls').forEach(enhanceCell);
        // pages built with <x-ui.*> components (ax-custom-page) style their own row buttons:
        // only explicit ul.actionalbls cells are enhanced there
        if (document.body.classList.contains('ax-custom-page')) return enhanceTooltips(root);
        (root || document).querySelectorAll('body.ax .ax-page table tbody tr').forEach(function (tr) {
            var td = tr.lastElementChild;
            if (!td || td.tagName !== 'TD') return;
            var box = looseActionCell(td);
            if (box) enhanceCell(box);
        });
        enhanceTooltips(root);
    }

    function enhanceTooltips(root) {
        // tooltips for whatever is new
        if (window.bootstrap && bootstrap.Tooltip) {
            (root || document).querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                if (!bootstrap.Tooltip.getInstance(el)) new bootstrap.Tooltip(el, { container: 'body' });
            });
        }
    }

    var timer = null;
    function scheduleEnhance() {
        clearTimeout(timer);
        timer = setTimeout(function () { enhanceActions(document); placeholders(); }, 60);
    }

    /* ---------------- one page structure for every admin screen ----------------
     * The layout already prints the page title, subtitle and breadcrumb. Old pages
     * print their own title row / breadcrumb as well, and some forms sit in a card
     * without padding or in no card at all. Here we:
     *  1. take the page's own title row (if it is the first thing on the page), move its
     *     buttons next to the big title and drop the duplicate heading;
     *  2. put loose forms in a card;
     *  3. give headings inside forms one "section" style.
     */
    var CARDS = '.card, .form-card, .filter-box, .ax-card, .ax-panel, .actions, .la-card, .modal, .custom-notification-module, .dashboard-card';
    var CONTENT = 'form, table, .card, .form-card, .filter-box, .table-responsive, .actions, .la-card, .ax-card, .row.g-4, .custom-notification-module';

    function textWithout(el, sel) {
        var c = el.cloneNode(true);
        c.querySelectorAll(sel).forEach(function (n) { n.remove(); });
        return (c.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function isTitleRow(el) {
        if (!el || el.closest('.modal')) return false;
        if (el.querySelector('input, select, textarea, table, form, img, canvas')) return false;
        var h = el.matches('h1,h2,h3,h4,h5') ? el : el.querySelector('h1,h2,h3,h4,h5');
        if (!h) return false;
        return textWithout(el, 'a, button, nav, .breadcrumb').length <= 140;
    }

    function firstContent(root) {
        return root.querySelector(CONTENT);
    }

    function comesBefore(a, b) {
        return !a.contains(b) && !b.contains(a) && !!(a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING);
    }

    /* the row is the first visible block of its container (e.g. a card that starts with a title) */
    function isLeadingChild(container, row) {
        var n = container.firstElementChild;
        while (n && (n.matches("style, script, meta, link, .alert, input[type=hidden]") || !n.textContent.trim())) n = n.nextElementSibling;
        return !!n && (n === row || (n.contains(row) && n.firstElementChild === row));
    }

    function moveTitleRow(root) {
        var first = firstContent(root);
        var candidates = root.querySelectorAll(".bradcrumb, .heading-list, .la-head, .upper-box, .d-flex.justify-content-between, h2, h3, h4");
        for (var i = 0; i < candidates.length; i++) {
            var row = candidates[i];
            if (!isTitleRow(row)) continue;
            // a heading that belongs to a row we look at anyway
            if (row.matches("h2,h3,h4") && row.parentElement.closest(".bradcrumb, .heading-list, .la-head, .upper-box, .d-flex")) continue;
            // headings inside cards are section titles, except the title bars of detail cards
            if (!row.matches(".upper-box, .la-head") && row.parentElement.closest(CARDS)) continue;
            // only the row at the very top of the page
            var top = !first || comesBefore(row, first) || (first.contains(row) && isLeadingChild(first, row)) ||
                (row.matches(".upper-box") && row.closest(".actions") && isLeadingChild(row.closest(".actions"), row));
            if (!top) return;
            if (row.matches(".upper-box") && textWithout(row, "a, button").length > 80) return; // a data panel, not a title

            var actions = document.getElementById("axPageActions");
            row.querySelectorAll("a.btn, button.btn, a.go-back, a.la-btn, button.la-btn").forEach(function (b) {
                if (b.closest(".breadcrumb, nav")) return;
                normaliseButton(b);
                if (actions) actions.appendChild(b);
            });

            // drop the empty shells left behind (wrappers holding only this row)
            var gone = row, up = row.parentElement;
            while (up && up !== root && up.children.length === 1 && !up.matches("form, table")) { gone = up; up = up.parentElement; }
            gone.remove();
            return;
        }
    }

    function normaliseButton(b) {
        var txt = (b.textContent || '').trim();
        var back = /back|go back|cancel/i.test(txt) || b.matches('.go-back') || !!b.querySelector('.fa-arrow-left, .fa-backward');
        b.className = 'btn ' + (back ? 'btn-light' : 'btn-primary');
        if (back) {
            b.innerHTML = '<i class="fa-solid fa-arrow-left"></i> ' + (txt.replace(/\s+/g, ' ').replace(/^go back$/i, 'Back') || 'Back');
        }
        b.removeAttribute('style');
    }

    function wrapLooseForms(root) {
        root.querySelectorAll('form').forEach(function (f) {
            if (f.closest(CARDS) || f.closest('.la-head, .heading-list, .dt-container, table')) return;
            if (f.querySelectorAll('input:not([type=hidden]), select, textarea').length < 1) return;
            var box = document.createElement('div');
            box.className = 'ax-panel';
            f.parentNode.insertBefore(box, f);
            box.appendChild(f);
        });
    }

    function sectionHeadings(root) {
        root.querySelectorAll('form h3, form h4, form h5, form h6, .ax-panel h4, .ax-panel h5, .ax-panel h6, .card h4, .card h5, .card h6, .actions h4').forEach(function (h) {
            if (h.closest('.card-header, .modal, .ax-card-head, .form-card-header, .heading-list, .upper-box, table, .dropdown-menu')) return;
            h.textContent = h.textContent.replace(/\s*:\s*$/, '');
            h.classList.add('ax-sec-title');
            if (h.parentElement && isLeadingChild(h.parentElement, h)) h.classList.add('is-first');
        });
    }

    function normalizePage() {
        var root = document.getElementById('axContent');
        if (!root || document.body.classList.contains('ax-custom-page')) return;
        moveTitleRow(root);
        wrapLooseForms(root);
        sectionHeadings(root);
        var hasList = !!root.querySelector('table, .ax-card, .la-card');
        var hasForm = !!root.querySelector('form input:not([type=hidden]), form textarea, form select');
        document.body.classList.toggle('ax-form-page', hasForm && !hasList);
    }

    function placeholders() {
        document.querySelectorAll('body.ax .dt-search input, body.ax .dataTables_filter input').forEach(function (i) {
            if (!i.getAttribute('placeholder')) i.setAttribute('placeholder', 'Search');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initNav();
        try { normalizePage(); } catch (e) { if (window.console) console.warn('normalizePage', e); }
        enhanceActions(document);
        if (window.jQuery) jQuery(document).on('draw.dt init.dt', scheduleEnhance);
        var page = document.querySelector('.ax-page');
        if (page && window.MutationObserver) {
            new MutationObserver(function (muts) {
                for (var i = 0; i < muts.length; i++) {
                    if (muts[i].addedNodes.length && muts[i].target.closest && muts[i].target.closest('tbody, table')) { scheduleEnhance(); return; }
                }
            }).observe(page, { childList: true, subtree: true });
        }
    });

    window.axEnhanceActions = enhanceActions;
})();

/* ---------------- Appearance: Auto (system) / Light / Dark ----------------
 * The choice is stored in localStorage "axTheme" and applied as <html data-theme>
 * before first paint (inline script in the layout). "system" removes the attribute so
 * the prefers-color-scheme tokens in ds.css decide.
 */
(function () {
    'use strict';
    var KEY = 'axTheme';

    function current() {
        try { var t = localStorage.getItem(KEY); return t === 'light' || t === 'dark' ? t : 'system'; } catch (e) { return 'system'; }
    }

    function apply(theme) {
        var root = document.documentElement;
        if (theme === 'light' || theme === 'dark') root.setAttribute('data-theme', theme); else root.removeAttribute('data-theme');
        var dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.querySelectorAll('meta[data-ax-theme-color]').forEach(function (m) {
            m.setAttribute('content', theme === 'system' ? (m.getAttribute('media') === '(prefers-color-scheme: dark)' ? '#000000' : '#f5f5f7') : (dark ? '#000000' : '#f5f5f7'));
        });
    }

    function set(theme) {
        try { if (theme === 'system') localStorage.removeItem(KEY); else localStorage.setItem(KEY, theme); } catch (e) {}
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (document.startViewTransition && !reduce) document.startViewTransition(function () { apply(theme); });
        else apply(theme);
    }

    function sync() {
        var t = current();
        document.querySelectorAll('[data-ax-theme-picker] input[name="ax_theme"]').forEach(function (r) { r.checked = r.value === t; });
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.name === 'ax_theme' && e.target.closest('[data-ax-theme-picker]')) set(e.target.value);
    });
    // keep the menu open while picking (ui-kit closes menus on .dropdown-item clicks only)
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', sync); else sync();
    // another tab changed it
    window.addEventListener('storage', function (e) { if (e.key === KEY) { apply(current()); sync(); } });

    window.axTheme = { get: current, set: set };
})();
