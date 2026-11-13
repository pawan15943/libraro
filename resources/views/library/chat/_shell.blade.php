{{-- Messenger layout for the owner: conversations on the left, the open chat (or a prompt) on the right. --}}
@php
    $ago = fn ($d) => $d ? ($d->isToday() ? $d->format('g:i A') : ($d->isYesterday() ? 'Yesterday' : $d->format('j M'))) : '';
    $ini = fn ($n) => mb_strtoupper(mb_substr(trim((string) $n), 0, 1)) . mb_strtoupper(mb_substr(trim((string) (explode(' ', trim((string) $n))[1] ?? '')), 0, 1));
@endphp
<link rel="stylesheet" href="{{ asset('public/css/lb-chat.css') }}?v={{ @filemtime(public_path('css/lb-chat.css')) }}">

<div class="lcx {{ $active ? 'has-active' : '' }}">
    <aside class="lcx-side">
        <div class="lcx-side-h">
            <div>
                <h5>Messages</h5>
                <span class="lcx-status" id="lcxStatus" data-on="{{ $hasKey ? 1 : 0 }}"><i></i><span>{{ $hasKey ? 'Private chat is on' : 'Setting up…' }}</span></span>
            </div>
            <button type="button" class="lcx-new" id="lcxNew"><span aria-hidden="true">+</span> New chat</button>
        </div>
        <form method="GET" action="{{ route('chat.index') }}" class="lcx-search">
            <input type="search" name="q" value="{{ $q }}" placeholder="Search chats" aria-label="Search chats">
        </form>
        <div class="lcx-list">
            @forelse ($threads as $t)
                <a href="{{ route('chat.thread', $t->learner_id) }}" class="lcx-item {{ $active === (int) $t->learner_id ? 'is-on' : '' }}">
                    <span class="lcx-av">{{ $ini($t->learner_name) ?: '?' }}</span>
                    <span class="lcx-item-t"><b>{{ $t->learner_name }}</b><small>ID {{ $t->learner_no }}</small></span>
                    <span class="lcx-item-r">
                        <small>{{ $ago($t->last_message_at) }}</small>
                        @if ($t->unread)<em class="lcx-badge">{{ $t->unread }} new</em>@endif
                    </span>
                </a>
            @empty
                <div class="lcx-none">
                    <p>@if ($q !== '') No chat matches "{{ $q }}". @else No conversations yet. @endif</p>
                    <button type="button" class="lcx-new is-soft" data-lcx-new>Start a chat</button>
                </div>
            @endforelse
        </div>
    </aside>

    <section class="lcx-main">
        @yield('lcx-main')
    </section>
</div>

{{-- New chat: pick any student of this library --}}
<dialog class="lcx-dlg" id="lcxDlg" aria-labelledby="lcxDlgT">
    <div class="lcx-dlg-h">
        <h5 id="lcxDlgT">Start a new chat</h5>
        <button type="button" class="lcx-x" data-lcx-close aria-label="Close">&times;</button>
    </div>
    <input type="search" class="lcx-dlg-q" id="lcxQ" placeholder="Search by name, ID or mobile" autocomplete="off">
    <div class="lcx-dlg-list" id="lcxRes" aria-live="polite"></div>
    <p class="lcx-dlg-note">Students can read your message once they open Chat in their portal.</p>
</dialog>

<script src="{{ asset('public/js/lb-chat-crypto.js') }}?v={{ @filemtime(public_path('js/lb-chat-crypto.js')) }}"></script>
<script>
(function () {
    var dlg = document.getElementById('lcxDlg'), q = document.getElementById('lcxQ'), res = document.getElementById('lcxRes'), t = null;
    function open() { if (dlg.showModal) dlg.showModal(); else dlg.setAttribute('open', ''); q.value = ''; load(); setTimeout(function () { q.focus(); }, 30); }
    function close() { if (dlg.close) dlg.close(); else dlg.removeAttribute('open'); }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function load() {
        res.innerHTML = '<div class="lcx-none"><p>Searching…</p></div>';
        fetch(@json(route('chat.students')) + '?q=' + encodeURIComponent(q.value), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); }).then(function (rows) {
                if (!rows.length) { res.innerHTML = '<div class="lcx-none"><p>No student found.</p></div>'; return; }
                res.innerHTML = rows.map(function (s) {
                    var ini = esc((s.name || '?').trim().charAt(0).toUpperCase());
                    return '<a class="lcx-item" href="' + esc(s.url) + '"><span class="lcx-av">' + ini + '</span><span class="lcx-item-t"><b>' + esc(s.name) + '</b><small>ID ' + esc(s.no) + (s.mobile ? ' · ' + esc(s.mobile) : '') + '</small></span>' + (s.active ? '' : '<span class="lcx-item-r"><small>Inactive</small></span>') + '</a>';
                }).join('');
            }).catch(function () { res.innerHTML = '<div class="lcx-none"><p>Could not load students. Try again.</p></div>'; });
    }
    document.getElementById('lcxNew').addEventListener('click', open);
    document.querySelectorAll('[data-lcx-new]').forEach(function (b) { b.addEventListener('click', open); });
    dlg.querySelector('[data-lcx-close]').addEventListener('click', close);
    dlg.addEventListener('click', function (e) { if (e.target === dlg) close(); });
    q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(load, 250); });

    // turn chat on as soon as this page opens: create the owner's private key in this browser
    var st = document.getElementById('lcxStatus');
    function status(ok, text) { st.dataset.on = ok ? 1 : 0; st.querySelector('span').textContent = text; }
    if (window.LibraroKeys && window.crypto && crypto.subtle && window.indexedDB) {
        LibraroKeys.ensureKey('library:{{ $libraryId }}').then(function (k) {
            return fetch(@json(route('chat.key.own')), {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': @json(csrf_token()) },
                body: JSON.stringify({ jwk: k.publicJwk })
            });
        }).then(function (r) { if (!r.ok) throw 0; status(true, 'Private chat is on'); }).catch(function () { status(false, 'Chat could not start'); });
    } else { status(false, 'Browser not supported (use https)'); }
})();
</script>
