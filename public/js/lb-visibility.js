/* Show / Hide Options (resources/views/master/custom-management/toggles.blade.php).
   A switch ON = shown. Every change posts the full list of hidden keys (branches.hide_field). */
(function () {
    var root = document.getElementById('vzRoot');
    if (!root) return;

    var sws = [].slice.call(root.querySelectorAll('.vz-sw'));
    var saveEl = root.querySelector('[data-vz-save]');
    var saveT = root.querySelector('[data-vz-save-t]');
    var search = root.querySelector('[data-vz-search]');
    var filter = 'all';
    var seq = 0, timer = null, lastGood = state();

    function state() { return sws.map(function (s) { return s.checked; }); }
    function restore(st) { sws.forEach(function (s, i) { s.checked = st[i]; }); paint(); }
    function hidden() { return sws.filter(function (s) { return !s.checked; }).map(function (s) { return s.value; }); }

    function setSave(mode, text) {
        saveEl.setAttribute('data-state', mode);
        saveT.textContent = text;
    }

    function paint() {
        var shown = 0;
        sws.forEach(function (s) {
            var item = s.closest('[data-vz-item]');
            item.classList.toggle('is-off', !s.checked);
            item.querySelector('[data-vz-state]').textContent = s.checked ? 'Shown' : 'Hidden';
            if (s.checked) shown++;
        });
        root.querySelector('[data-vz-shown]').textContent = shown;
        root.querySelector('[data-vz-hidden]').textContent = sws.length - shown;
        [].forEach.call(root.querySelectorAll('[data-vz-group]'), function (g) {
            var all = g.querySelectorAll('.vz-sw'), on = g.querySelectorAll('.vz-sw:checked').length;
            g.querySelector('[data-vz-group-count]').textContent = on + ' of ' + all.length + ' shown';
            g.querySelector('[data-vz-all="1"]').disabled = on === all.length;
            g.querySelector('[data-vz-all="0"]').disabled = on === 0;
            var chip = root.querySelector('[data-vz-cat="' + g.getAttribute('data-vz-group') + '"] [data-vz-cat-count]');
            if (chip) chip.textContent = (all.length - on) || '';
        });
        applyFilter();
    }

    function save() {
        var mine = ++seq;
        setSave('busy', 'Saving…');
        var body = new FormData();
        hidden().forEach(function (k) { body.append('hidden_ids[]', k); });
        fetch(root.getAttribute('data-url'), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': root.getAttribute('data-csrf'), 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: body,
            credentials: 'same-origin'
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (j) { if (!r.ok || !j.status) throw new Error(j.message || 'Could not save'); return j; });
        }).then(function () {
            if (mine !== seq) return;
            lastGood = state();
            setSave('ok', 'Saved');
            clearTimeout(timer);
            timer = setTimeout(function () { setSave('idle', 'All saved'); }, 2200);
        }).catch(function (e) {
            if (mine !== seq) return;
            restore(lastGood);
            setSave('bad', 'Not saved');
            if (window.lpToast) window.lpToast((e && e.message) || 'Could not save. Check your connection and try again.', 'error');
        });
    }

    root.addEventListener('change', function (e) {
        if (!e.target.classList.contains('vz-sw')) return;
        paint();
        save();
    });

    root.addEventListener('click', function (e) {
        var b = e.target.closest('[data-vz-all]');
        if (!b) return;
        var on = b.getAttribute('data-vz-all') === '1';
        var g = b.closest('[data-vz-group]');
        var changed = false;
        [].forEach.call(g.querySelectorAll('.vz-sw'), function (s) {
            if (s.closest('[data-vz-item]').hidden) return;       // only what the search / filter shows
            if (s.checked !== on) { s.checked = on; changed = true; }
        });
        if (changed) { paint(); save(); }
    });

    function applyFilter() {
        var q = (search.value || '').trim().toLowerCase(), any = false;
        [].forEach.call(root.querySelectorAll('[data-vz-group]'), function (g) {
            var vis = 0;
            [].forEach.call(g.querySelectorAll('[data-vz-item]'), function (it) {
                var sw = it.querySelector('.vz-sw');
                var ok = (!q || it.getAttribute('data-text').indexOf(q) !== -1)
                    && (filter !== 'hidden' || !sw.checked)
                    && (filter !== 'learner' || it.getAttribute('data-where') !== 'library');
                it.hidden = !ok;
                if (ok) vis++;
            });
            g.hidden = vis === 0;
            var chip = root.querySelector('[data-vz-cat="' + g.getAttribute('data-vz-group') + '"]');
            if (chip) chip.hidden = vis === 0;
            if (vis) any = true;
        });
        root.querySelector('[data-vz-empty]').hidden = any;
    }

    search.addEventListener('input', applyFilter);
    root.querySelector('.vz-filter').addEventListener('click', function (e) {
        var b = e.target.closest('[data-vz-filter]');
        if (!b) return;
        filter = b.getAttribute('data-vz-filter');
        [].forEach.call(this.querySelectorAll('button'), function (x) { x.classList.toggle('is-on', x === b); });
        var p = root.querySelector('[data-vz-empty] p');
        if (p) p.textContent = filter === 'hidden' && !search.value ? 'Nothing is hidden. Every option is shown.' : 'Try another word, or clear the search.';
        applyFilter();
    });

    // category chips: smooth scroll, and the chip of the section in view lights up
    root.querySelector('.vz-cats').addEventListener('click', function (e) {
        var a = e.target.closest('.vz-cat');
        if (!a) return;
        e.preventDefault();
        var t = document.querySelector(a.getAttribute('href'));
        if (t) window.scrollTo({ top: t.getBoundingClientRect().top + window.pageYOffset - 132, behavior: 'smooth' });
    });
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (ents) {
            ents.forEach(function (en) {
                if (!en.isIntersecting) return;
                var id = en.target.getAttribute('data-vz-group');
                [].forEach.call(root.querySelectorAll('.vz-cat'), function (c) { c.classList.toggle('is-on', c.getAttribute('data-vz-cat') === id); });
            });
        }, { rootMargin: '-140px 0px -60% 0px' });
        [].forEach.call(root.querySelectorAll('[data-vz-group]'), function (g) { io.observe(g); });
    }

    paint();
})();
