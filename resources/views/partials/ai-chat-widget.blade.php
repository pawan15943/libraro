<link rel="stylesheet" href="{{ asset('public/css/ai-chat-widget.css') }}?v=13">

@php($limoProblems = collect(\App\Services\Ai\SupportPlaybooks::all())->map(fn ($p, $id) => ['id' => $id, 'cat' => $p['cat'], 'en' => $p['title'][0], 'hi' => $p['title'][1]])->values())
<div class="custom-libraro-ai-widget" id="lbAi"
     data-send="{{ route('ai.chat.send') }}"
     data-history="{{ route('ai.chat.history') }}"
     data-clear="{{ route('ai.chat.clear') }}"
     data-feedback="{{ route('ai.chat.feedback') }}"
     data-alerts="{{ route('ai.chat.alerts') }}"
     data-export="{{ route('ai.chat.export', ['id' => '__ID__']) }}"
     data-csrf="{{ csrf_token() }}">

    {{-- Floating trigger (hidden when the right rail has its own AI button) --}}
    <button type="button" class="lai-fab" id="libraroAiTrigger" onclick="toggleLibraroAiBox()" aria-label="Ask Limo, the Libraro AI assistant" title="Ask Limo" aria-controls="libraroAiBox" aria-expanded="false">
        <span class="lai-fab-av">@include('partials.limo-avatar', ['uid' => 'fab'])</span>
        <span class="lai-fab-t" aria-hidden="true"><b>Ask me</b><small>Limo · AI help</small></span>
    </button>

    <section class="lai-panel" id="libraroAiBox" role="dialog" aria-modal="false" aria-labelledby="laiTitle" aria-hidden="true">
        <header class="lai-head">
            <div class="lai-avatar" aria-hidden="true">@include('partials.limo-avatar', ['uid' => 'head'])<span class="lai-live"></span></div>
            <div class="lai-head-text">
                <strong id="laiTitle">Limo<em>AI</em></strong>
                <small id="laiSub">Your Libraro assistant · English &amp; हिंदी</small>
            </div>
            <div class="lai-head-actions">
                <button type="button" class="lai-icon-btn lai-wide-btn" id="laiWide" title="Full screen" aria-label="Full screen" aria-pressed="false"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></button>
                <button type="button" class="lai-icon-btn lai-icon-danger" id="laiClear" title="Clear chat history" aria-label="Clear chat history" aria-haspopup="dialog" disabled><i class="fa-regular fa-trash-can"></i></button>
                <button type="button" class="lai-icon-btn" id="laiClose" title="Close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </header>

        {{-- Two ways to use Limo: ask about the library, or get step-by-step support for a problem --}}
        <div class="lai-modebar" role="tablist" aria-label="Limo mode">
            <button type="button" role="tab" class="lai-mode" data-mode="ask" aria-selected="true"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><span>Ask Limo</span></button>
            <button type="button" role="tab" class="lai-mode" data-mode="support" aria-selected="false"><i class="fa-solid fa-life-ring" aria-hidden="true"></i><span>Support</span></button>
        </div>

        <div class="lai-langbar">
            <span class="lai-langbar-label"><i class="fa-solid fa-language" aria-hidden="true"></i><span id="laiLangLabel">Answer in</span></span>
            <div class="lai-seg" role="radiogroup" aria-label="Answer language">
                <button type="button" role="radio" data-lang="auto">Auto</button>
                <button type="button" role="radio" data-lang="en">English</button>
                <button type="button" role="radio" data-lang="hi">हिंदी</button>
            </div>
        </div>

        <div class="lai-body" id="libraroAiBody" aria-live="polite">
            <div class="lai-welcome" id="laiWelcome"></div>
            <div class="lai-thread" id="laiThread"></div>
        </div>

        <div class="lai-compose-wrap">
            <div class="lai-typeahead" id="laiTypeahead" role="listbox" hidden></div>
            <div class="lai-popular" id="laiPopular" role="dialog" aria-labelledby="laiPopularTitle" hidden>
                <div class="lai-popular-head">
                    <strong id="laiPopularTitle"><i class="fa-solid fa-fire" aria-hidden="true"></i> <span>Popular questions</span></strong>
                    <button type="button" class="lai-popular-x" id="laiPopularClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="lai-popular-body" id="laiPopularBody"></div>
            </div>
            <form class="lai-compose" id="libraroAiForm" autocomplete="off">
                <button type="button" class="lai-pop-btn" id="laiPopBtn" aria-controls="laiPopular" aria-expanded="false" title="Popular questions" aria-label="Popular questions"><i class="fa-solid fa-fire"></i></button>
                <textarea id="libraroAiInput" rows="1" maxlength="500" placeholder="Ask Limo anything…" aria-label="Your question"></textarea>
                <button type="button" class="lai-mic" id="laiMic" aria-label="Speak your question" title="Speak" hidden><i class="fa-solid fa-microphone"></i></button>
                <button type="submit" class="lai-send" id="libraroAiSend" aria-label="Send" disabled><i class="fa-solid fa-arrow-up"></i></button>
            </form>
            <div class="lai-hint" id="laiHint">Enter to send · Shift + Enter for a new line</div>
        </div>
        {{-- Clear-chat confirmation (inside the panel) --}}
        <div class="lai-confirm" id="laiConfirm" hidden>
            <div class="lai-confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="laiConfirmTitle" aria-describedby="laiConfirmText">
                <span class="lai-confirm-ic" aria-hidden="true"><i class="fa-regular fa-trash-can"></i></span>
                <h4 id="laiConfirmTitle">Clear chat history?</h4>
                <p id="laiConfirmText">All old questions and answers will be deleted. This cannot be undone.</p>
                <p class="lai-confirm-err" id="laiConfirmErr" role="alert" hidden></p>
                <div class="lai-confirm-actions">
                    <button type="button" class="lai-btn" id="laiConfirmNo">Cancel</button>
                    <button type="button" class="lai-btn lai-btn-danger" id="laiConfirmYes"><span>Yes, clear</span></button>
                </div>
            </div>
        </div>

        <div class="lai-toast" id="laiToast" role="status" aria-live="polite" hidden></div>
    </section>

    {{-- Copy source for the small Limo used in the welcome screen and "thinking" row --}}
    <template id="laiLimoTpl">@include('partials.limo-avatar', ['uid' => 'tpl'])</template>
</div>

<script>
(function () {
    if (window.libraroAiWidgetInitialized) return;
    window.libraroAiWidgetInitialized = true;

    /* ------------------------------------------------------------------ *
     * Question bank (shown on the welcome screen and as type-ahead).
     * Each item: [English, Hindi]
     * ------------------------------------------------------------------ */
    var TOPICS = [
        { key: 'today', icon: 'fa-sun', en: 'Today', hi: 'आज', q: [
            ["Today's summary", 'आज का सारांश'],
            ["Today's collection", 'आज का कलेक्शन कितना है?'],
            ['Who came today?', 'आज कौन आया?'],
            ['Who is absent today?', 'आज कौन नहीं आया?'],
            ['New admissions today', 'आज कितने नए एडमिशन हुए?'],
            ["Today's birthdays", 'आज किसका जन्मदिन है?']
        ]},
        { key: 'money', icon: 'fa-indian-rupee-sign', en: 'Money', hi: 'पैसा', q: [
            ['Total pending dues', 'कुल बकाया कितना है?'],
            ['Show due students list', 'बकाया छात्रों की सूची दिखाओ'],
            ["Yesterday's collection", 'कल का कलेक्शन'],
            ['This month collection', 'इस महीने का कलेक्शन'],
            ['This month vs last month', 'इस महीने बनाम पिछला महीना'],
            ['This month expenses', 'इस महीने का खर्च'],
            ['Profit this month', 'इस महीने का मुनाफा'],
            ['Refunds this month', 'इस महीने के रिफंड'],
            ['Plan fees', 'प्लान की फीस बताओ']
        ]},
        { key: 'learners', icon: 'fa-users', en: 'Learners', hi: 'छात्र', q: [
            ['How many active learners?', 'कितने सक्रिय छात्र हैं?'],
            ['Shift wise learners', 'शिफ्ट वाइज छात्र'],
            ['Whose plan expires in next 7 days?', 'अगले 7 दिन में किसका प्लान खत्म होगा?'],
            ['Recently expired learners', 'हाल ही में एक्सपायर हुए छात्र'],
            ['Renewals this month', 'इस महीने के रिन्यूअल'],
            ['New admissions this month', 'इस महीने कितने नए एडमिशन हुए?'],
            ['Frozen learners', 'फ्रीज़ वाले छात्र'],
            ['Locker list', 'लॉकर की सूची'],
            ['Learner feedback', 'छात्रों का फीडबैक']
        ]},
        { key: 'seats', icon: 'fa-chair', en: 'Seats', hi: 'सीटें', q: [
            ['Show vacant seats', 'खाली सीटें दिखाओ'],
            ['What is the occupancy?', 'ऑक्यूपेंसी कितनी है?'],
            ['Who is on seat 1?', 'सीट 1 पर कौन है?'],
            ['Show floors', 'फ्लोर दिखाओ'],
            ['Shift timings', 'शिफ्ट का समय बताओ']
        ]},
        { key: 'library', icon: 'fa-building', en: 'Library', hi: 'लाइब्रेरी', q: [
            ['Enquiries and online bookings', 'पूछताछ और ऑनलाइन बुकिंग'],
            ['My branches', 'मेरी ब्रांच दिखाओ'],
            ['Staff list', 'स्टाफ की सूची'],
            ['Branch details', 'ब्रांच की जानकारी'],
            ['My Libraro plan', 'मेरा सब्सक्रिप्शन प्लान']
        ]},
        { key: 'grow', icon: 'fa-seedling', en: 'Grow', hi: 'बढ़ाएं', q: [
            ['How to get more admissions?', 'एडमिशन कैसे बढ़ाएं?'],
            ['How to collect fees on time?', 'बकाया कैसे वसूलें?'],
            ['How to fill empty seats?', 'खाली सीटें कैसे भरें?'],
            ['How should I decide fees?', 'फीस कितनी रखें?'],
            ['How to keep learners renewing?', 'रिन्यूअल कैसे बढ़ाएं?'],
            ['Library rules', 'लाइब्रेरी के नियम'],
            ['Facilities students want', 'कौन सी सुविधाएं होनी चाहिए?'],
            ['How to start a new library?', 'नई लाइब्रेरी कैसे खोलें?']
        ]},
        { key: 'howto', icon: 'fa-book-open', en: 'How to', hi: 'कैसे करें', q: [
            ['How to book a seat?', 'सीट कैसे बुक करें?'],
            ['How to renew a seat?', 'सीट रिन्यू कैसे करें?'],
            ['How to swap seat?', 'सीट कैसे बदलें?'],
            ['How to change plan or shift?', 'प्लान या शिफ्ट कैसे बदलें?'],
            ['How to generate ID card?', 'ID कार्ड कैसे बनाएं?'],
            ['How to send WhatsApp reminder?', 'व्हाट्सएप रिमाइंडर कैसे भेजें?'],
            ['How to freeze a plan?', 'प्लान फ्रीज कैसे करें?'],
            ['How to assign a locker?', 'लॉकर कैसे अलॉट करें?'],
            ['How to give discount?', 'डिस्काउंट कैसे दें?'],
            ['How to take attendance?', 'हाजिरी कैसे लें?'],
            ['How to import learners?', 'छात्र इम्पोर्ट कैसे करें?'],
            ['How to add expense?', 'खर्च कैसे जोड़ें?'],
            ['How to switch branch?', 'ब्रांच कैसे बदलें?'],
            ['What is the VIP plan?', 'VIP प्लान क्या है?'],
            ['What is the extended period?', 'Extended period क्या होता है?']
        ]}
    ];

    /* One-row, swipeable chips on the welcome screen: [icon, tone, label, question] */
    var CHIPS = [
        { icon: 'fa-gauge-high', tone: 'info', label: ["Today's summary", 'आज का सारांश'], q: ["Today's summary", 'आज का सारांश'] },
        { icon: 'fa-indian-rupee-sign', tone: 'good', label: ["Today's collection", 'आज का कलेक्शन'], q: ["Today's collection", 'आज का कलेक्शन कितना है?'] },
        { icon: 'fa-hand-holding-dollar', tone: 'bad', label: ['Pending dues', 'बकाया फीस'], q: ['Show due students list', 'बकाया छात्रों की सूची दिखाओ'] },
        { icon: 'fa-chair', tone: 'info', label: ['Vacant seats', 'खाली सीटें'], q: ['Show vacant seats', 'खाली सीटें दिखाओ'] },
        { icon: 'fa-hourglass-half', tone: 'warn', label: ['Expiring soon', 'जल्द एक्सपायर'], q: ['Whose plan expires in next 7 days?', 'अगले 7 दिन में किसका प्लान खत्म होगा?'] },
        { icon: 'fa-user-check', tone: 'good', label: ['Who came today', 'आज कौन आया'], q: ['Who came today?', 'आज कौन आया?'] },
        { icon: 'fa-chart-column', tone: 'info', label: ['This month vs last', 'इस महीने बनाम पिछला'], q: ['This month vs last month', 'इस महीने बनाम पिछला महीना'] }
    ];

    var UI = {
        en: { sub: 'Your Libraro assistant · English & हिंदी', ph: 'Ask Limo anything…', hint: 'Enter to send · Shift + Enter for a new line', lang: 'Answer in',
              hello: 'Hi, I\'m Limo', intro: 'Ask about your library in English or हिंदी.', quick: 'Try asking', popular: 'Popular questions', prevChat: 'Show previous chat', more: 'More', less: 'Show less',
              topics: 'Browse by topic', thinking: 'Limo is checking your data…', you: 'You', err: 'Could not reach the server. Check your internet and try again.',
              retry: 'Try again', copied: 'Copied', copy: 'Copy answer', listening: 'Listening… speak now', micErr: 'Could not hear you. Please type instead.', seeHi: 'हिंदी में देखें', seeEn: 'View in English', clearBtn: 'Clear chat history', clearTitle: 'Clear chat history?', clearText: 'All your old questions and answers will be deleted. This cannot be undone.', clearNo: 'Cancel', clearYes: 'Yes, clear', clearing: 'Clearing…', cleared: 'Chat cleared', clearErr: 'Could not clear the chat. Please try again.', helpful: 'Helpful', notHelpful: 'Not helpful', thanks: 'Thanks for the feedback!', thanksBad: 'Thanks — we will improve this answer.', listen: 'Listen', pdf: 'Download PDF', excel: 'Download Excel', openWa: 'Open WhatsApp', attention: 'Needs attention', wideOn: 'Full screen', wideOff: 'Exit full screen', modeAsk: 'Ask Limo', modeSupport: 'Support', phSupport: 'Describe your problem… e.g. renew button not showing', supportHello: 'Limo Support', supportIntro: 'Tell me the problem in your own words — I will check your data and solve it step by step.', supportCommon: 'Common problems', supportTopics: 'Or pick a topic', supportBrowse: 'All problems by topic', supportAll: 'All problems', supportOther: 'Something else — talk to Libraro support', related: 'You can also ask' },
        hi: { sub: 'आपका Libraro सहायक · हिंदी व English', ph: 'Limo से कुछ भी पूछें…', hint: 'भेजने के लिए Enter · नई लाइन के लिए Shift + Enter', lang: 'जवाब की भाषा',
              hello: 'नमस्ते, मैं Limo हूँ', intro: 'अपनी लाइब्रेरी के बारे में हिंदी या English में पूछें।', quick: 'ऐसे पूछें', popular: 'लोकप्रिय सवाल', prevChat: 'पिछली चैट देखें', more: 'और देखें', less: 'कम दिखाएं',
              topics: 'विषय के अनुसार', thinking: 'Limo आपका डेटा देख रहा है…', you: 'आप', err: 'सर्वर से जुड़ नहीं पाया। इंटरनेट जांचें और फिर कोशिश करें।',
              retry: 'फिर कोशिश करें', copied: 'कॉपी हो गया', copy: 'जवाब कॉपी करें', listening: 'सुन रहा हूँ… बोलिए', micErr: 'आवाज़ नहीं सुन पाया। कृपया टाइप करें।', seeHi: 'हिंदी में देखें', seeEn: 'View in English', clearBtn: 'चैट हिस्ट्री मिटाएं', clearTitle: 'पुरानी चैट मिटाएं?', clearText: 'आपके सभी पुराने सवाल और जवाब मिट जाएंगे। इसे वापस नहीं लाया जा सकता।', clearNo: 'रद्द करें', clearYes: 'हाँ, मिटाएं', clearing: 'मिटा रहा हूँ…', cleared: 'चैट मिटा दी गई', clearErr: 'चैट मिटा नहीं पाया। कृपया फिर कोशिश करें।', helpful: 'मददगार', notHelpful: 'मददगार नहीं', thanks: 'फीडबैक के लिए धन्यवाद!', thanksBad: 'धन्यवाद — हम इस जवाब को बेहतर करेंगे।', listen: 'सुनें', pdf: 'PDF डाउनलोड', excel: 'Excel डाउनलोड', openWa: 'WhatsApp खोलें', attention: 'ध्यान दें', wideOn: 'पूरी स्क्रीन', wideOff: 'छोटी विंडो', modeAsk: 'Limo से पूछें', modeSupport: 'सपोर्ट', phSupport: 'अपनी समस्या लिखें… जैसे: रिन्यू का बटन नहीं दिख रहा', supportHello: 'Limo सपोर्ट', supportIntro: 'अपनी समस्या अपने शब्दों में बताइए — मैं आपका डेटा देखकर स्टेप-बाय-स्टेप हल करूँगा।', supportCommon: 'आम समस्याएं', supportTopics: 'या विषय चुनें', supportBrowse: 'विषय के अनुसार सभी समस्याएं', supportAll: 'सभी समस्याएं', supportOther: 'कुछ और — Libraro सपोर्ट से बात करें', related: 'आप यह भी पूछ सकते हैं' }
    };

    var root = document.getElementById('lbAi');
    var panel = document.getElementById('libraroAiBox');
    var body = document.getElementById('libraroAiBody');
    var thread = document.getElementById('laiThread');
    var welcome = document.getElementById('laiWelcome');
    var form = document.getElementById('libraroAiForm');
    var input = document.getElementById('libraroAiInput');
    var sendBtn = document.getElementById('libraroAiSend');
    var typeahead = document.getElementById('laiTypeahead');
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || root.dataset.csrf;

    var lang = store('get') || 'auto';
    var wideInit = false;
    var mode = store('get', null, 'lb-ai-mode') === 'support' ? 'support' : 'ask';
    var activeTopic = 'today';
    var historyLoaded = false;
    var savedHistory = [];   // old messages, shown only on request
    var alertsData = [];     // "needs attention" items for the welcome screen
    var alertsAt = 0;
    var busy = false;
    var lastQuery = '';
    var lastShown = null;

    function store(op, v, key) {
        key = key || 'lb-ai-lang';
        try {
            if (op === 'get') return localStorage.getItem(key);
            localStorage.setItem(key, v);
        } catch (e) { return null; }
    }

    /* ------------------------------ Full screen (remembered per browser) ------------------------------ */

    var wideBtn = document.getElementById('laiWide');

    function setWide(on, remember) {
        panel.classList.toggle('is-wide', on);
        root.classList.toggle('is-wide', on);
        wideBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
        var label = on ? L('wideOff') : L('wideOn');
        wideBtn.title = label;
        wideBtn.setAttribute('aria-label', label);
        wideBtn.textContent = '';
        wideBtn.appendChild(icon(on ? 'fa-down-left-and-up-right-to-center' : 'fa-up-right-and-down-left-from-center'));
        if (remember) store('set', on ? '1' : '0', 'lb-ai-wide');
    }

    wideBtn.addEventListener('click', function () { setWide(!panel.classList.contains('is-wide'), true); });
    function uiLang() { return lang === 'hi' ? 'hi' : 'en'; }
    function L(key) { return UI[uiLang()][key]; }
    function pick(pair) { return uiLang() === 'hi' ? pair[1] : pair[0]; }
    function el(tag, cls, text) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text !== undefined && text !== null) n.textContent = text;
        return n;
    }
    /** A fresh copy of Limo with its own gradient ids. */
    var limoCount = 0;
    function limo(cls) {
        var tpl = document.getElementById('laiLimoTpl');
        var wrap = document.createElement('span');
        wrap.className = 'lai-limo ' + (cls || '');
        wrap.innerHTML = tpl ? tpl.innerHTML.replace(/limo-tpl/g, 'limo-c' + (++limoCount)) : '';
        return wrap;
    }

    function icon(name) { var i = el('i', (/^fa-(regular|brands|solid) /.test(name) ? '' : 'fa-solid ') + name); i.setAttribute('aria-hidden', 'true'); return i; }

    /* ------------------------------ Open / close ------------------------------ */

    function isOpen() { return panel.classList.contains('is-open'); }

    function setOpen(open) {
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.querySelectorAll('#libraroAiTrigger, #libraroAiTriggerSidebar').forEach(function (b) {
            b.setAttribute('aria-expanded', open ? 'true' : 'false');
            b.classList.toggle('is-active', open);
        });
        if (open) {
            if (!historyLoaded) loadHistory();
            if (Date.now() - alertsAt > 60000) loadAlerts();
            setTimeout(function () { input.focus({ preventScroll: true }); }, 180);
        } else {
            hideTypeahead();
            setPopular(false);
        }
    }

    window.toggleLibraroAiBox = function () { setOpen(!isOpen()); };
    window.sendAiQuery = function (q) { if (!isOpen()) setOpen(true); ask(q); };

    document.getElementById('laiClose').addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen()) {
            if (typeof isConfirmOpen === 'function' && isConfirmOpen()) { closeConfirm(); return; }
            if (!typeahead.hidden) { hideTypeahead(); return; }
            if (popular && isPopularOpen()) { setPopular(false); popBtn.focus(); return; }
            setOpen(false);
        }
    });

    /* ------------------------------ Language ------------------------------ */

    function applyLang() {
        document.querySelectorAll('#lbAi .lai-seg button').forEach(function (b) {
            var on = b.dataset.lang === lang;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-checked', on ? 'true' : 'false');
        });
        document.getElementById('laiSub').textContent = L('sub');
        document.getElementById('laiHint').textContent = L('hint');
        document.getElementById('laiLangLabel').textContent = L('lang');
        var cb = document.getElementById('laiClear');
        if (cb) { cb.title = L('clearBtn'); cb.setAttribute('aria-label', L('clearBtn')); }
        setWide(panel.classList.contains('is-wide') || (!wideInit && store('get', null, 'lb-ai-wide') === '1'), false);
        wideInit = true;
        setMode(mode, false);
    }

    document.querySelectorAll('#lbAi .lai-seg button').forEach(function (b) {
        b.addEventListener('click', function () {
            lang = b.dataset.lang;
            store('set', lang);
            applyLang();
            if (isOpen()) loadAlerts();
        });
    });

    /* ------------------------------ Welcome ------------------------------ */

    /* ------------------------------ Mode: Ask Limo / Support ------------------------------ */

    /* Support topics — the key is understood by the server ("#cat key") */
    var SUPPORT_CATS = [
        { key: 'booking', icon: 'fa-chair', en: 'Seat booking', hi: 'सीट बुकिंग' },
        { key: 'plan', icon: 'fa-arrows-rotate', en: 'Renew & plan change', hi: 'रिन्यू व प्लान बदलना' },
        { key: 'learner', icon: 'fa-user-pen', en: 'Learner profile & status', hi: 'छात्र प्रोफाइल व स्थिति' },
        { key: 'payment', icon: 'fa-indian-rupee-sign', en: 'Payments & dues', hi: 'भुगतान व बकाया' },
        { key: 'attendance', icon: 'fa-qrcode', en: 'Attendance & QR', hi: 'हाजिरी व QR' },
        { key: 'messages', icon: 'fa-brands fa-whatsapp', en: 'WhatsApp & SMS', hi: 'WhatsApp व SMS' },
        { key: 'documents', icon: 'fa-id-card', en: 'ID card & receipt', hi: 'ID कार्ड व रसीद' },
        { key: 'access', icon: 'fa-user-lock', en: 'Login & staff access', hi: 'लॉगिन व स्टाफ एक्सेस' },
        { key: 'setup', icon: 'fa-sliders', en: 'Plans, shifts & seats setup', hi: 'प्लान, शिफ्ट व सीट सेटअप' },
        { key: 'online', icon: 'fa-globe', en: 'Online booking & website', hi: 'ऑनलाइन बुकिंग व वेबसाइट' },
        { key: 'data', icon: 'fa-file-import', en: 'Import, reports & data', hi: 'इम्पोर्ट, रिपोर्ट व डेटा' },
        { key: 'account', icon: 'fa-crown', en: 'Libraro subscription', hi: 'Libraro सब्सक्रिप्शन' }
    ];

    /* Every problem Limo Support knows, by topic (from SupportPlaybooks) */
    var SUPPORT_PROBLEMS = @json($limoProblems);
    var activeSupportCat = 'booking';

    /* Problems owners report most — sent as normal text so Limo reads them like any question */
    var SUPPORT_COMMON = [
        ['Renew button is not showing', 'रिन्यू का बटन नहीं दिख रहा'],
        ['Seat is not getting booked', 'सीट बुक नहीं हो रही'],
        ['Deleted a learner by mistake', 'गलती से छात्र डिलीट हो गया'],
        ['WhatsApp reminder is not going', 'व्हाट्सएप रिमाइंडर नहीं जा रहा'],
        ['Staff cannot see a menu', 'स्टाफ को मेन्यू नहीं दिख रहा'],
        ['ID card is not printing', 'ID कार्ड प्रिंट नहीं हो रहा'],
        ['Attendance QR is not working', 'हाजिरी का QR काम नहीं कर रहा'],
        ['Dues amount looks wrong', 'बकाया राशि गलत दिख रही है']
    ];

    var modeTabs = document.querySelectorAll('#lbAi .lai-mode');

    function setMode(m, remember) {
        mode = m === 'support' ? 'support' : 'ask';
        root.classList.toggle('is-support', mode === 'support');
        modeTabs.forEach(function (t) {
            var on = t.dataset.mode === mode;
            t.classList.toggle('is-on', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        modeTabs[0].querySelector('span').textContent = L('modeAsk');
        modeTabs[1].querySelector('span').textContent = L('modeSupport');
        input.placeholder = mode === 'support' ? L('phSupport') : L('ph');
        if (remember) store('set', mode, 'lb-ai-mode');
        renderWelcome();
    }

    modeTabs.forEach(function (t) {
        t.addEventListener('click', function () {
            setMode(t.dataset.mode, true);
            if (!thread.children.length) body.scrollTop = 0;
            input.focus();
        });
    });

    function renderSupportWelcome() {
        var hero = el('div', 'lai-hero is-support');
        var badge = el('span', 'lai-support-badge');
        badge.appendChild(icon('fa-solid fa-life-ring'));
        hero.appendChild(badge);
        var heroTx = el('div', 'lai-hero-tx');
        heroTx.appendChild(el('h3', 'lai-hello', L('supportHello')));
        heroTx.appendChild(el('p', 'lai-intro', L('supportIntro')));
        hero.appendChild(heroTx);
        welcome.appendChild(hero);

        welcome.appendChild(el('div', 'lai-section-title', L('supportCommon')));
        var row = el('div', 'lai-chiprow');
        SUPPORT_COMMON.forEach(function (p) {
            var b = el('button', 'lai-qchip tone-info');
            b.type = 'button';
            b.appendChild(icon('fa-circle-exclamation'));
            b.appendChild(el('span', null, pick(p)));
            b.addEventListener('click', function () { if (!row.dataset.dragged) ask(pick(p)); });
            row.appendChild(b);
        });
        makeSwipeable(row);
        welcome.appendChild(row);

        var all = el('button', 'lai-browse');
        all.type = 'button';
        all.appendChild(icon('fa-solid fa-fire'));
        all.appendChild(el('span', null, L('supportBrowse')));
        all.appendChild(icon('fa-chevron-right'));
        all.addEventListener('click', function (e) { e.stopPropagation(); setPopular(true); });
        welcome.appendChild(all);

        renderPopular();
    }

    function renderWelcome() {
        welcome.textContent = '';
        if (mode === 'support') {
            renderSupportWelcome();
            return;
        }
        var hero = el('div', 'lai-hero');
        hero.appendChild(limo('is-hero'));
        var heroTx = el('div', 'lai-hero-tx');
        heroTx.appendChild(el('h3', 'lai-hello', L('hello')));
        heroTx.appendChild(el('p', 'lai-intro', L('intro')));
        hero.appendChild(heroTx);
        welcome.appendChild(hero);

        // Old conversation is never shown by default — only a quiet link to bring it back
        if (savedHistory.length) {
            var prev = el('button', 'lai-prev');
            prev.type = 'button';
            prev.appendChild(icon('fa-clock-rotate-left'));
            prev.appendChild(el('span', null, L('prevChat')));
            prev.addEventListener('click', showSavedHistory);
            welcome.appendChild(prev);
        }

        // Things that need attention today (from the server, permission-aware)
        if (alertsData.length) {
            welcome.appendChild(el('div', 'lai-section-title', L('attention')));
            var al = el('div', 'lai-alerts');
            alertsData.forEach(function (a) {
                var b = el('button', 'lai-alert tone-' + a.tone);
                b.type = 'button';
                var ic = el('span', 'lai-alert-ic'); ic.appendChild(icon(a.icon));
                b.appendChild(ic);
                b.appendChild(el('span', 'lai-alert-tx', a.text));
                b.appendChild(icon('fa-chevron-right'));
                b.addEventListener('click', function () { ask(a.query); });
                al.appendChild(b);
            });
            welcome.appendChild(al);
        }

        // One swipeable row of question chips
        welcome.appendChild(el('div', 'lai-section-title', L('quick')));
        var row = el('div', 'lai-chiprow');
        row.setAttribute('role', 'list');
        CHIPS.forEach(function (c) {
            var b = el('button', 'lai-qchip tone-' + c.tone);
            b.type = 'button';
            b.setAttribute('role', 'listitem');
            b.appendChild(icon(c.icon));
            b.appendChild(el('span', null, pick(c.label)));
            b.addEventListener('click', function () { if (!row.dataset.dragged) ask(pick(c.q)); });
            row.appendChild(b);
        });
        makeSwipeable(row);
        welcome.appendChild(row);

        renderPopular();
    }

    /** Mouse wheel and click-drag scroll the chip row sideways (touch swipes natively). */
    function makeSwipeable(row) {
        row.addEventListener('wheel', function (e) {
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX) && row.scrollWidth > row.clientWidth) {
                row.scrollLeft += e.deltaY;
                e.preventDefault();
            }
        }, { passive: false });
        var startX = 0, startLeft = 0, down = false;
        row.addEventListener('mousedown', function (e) {
            down = true; startX = e.pageX; startLeft = row.scrollLeft; delete row.dataset.dragged;
        });
        window.addEventListener('mousemove', function (e) {
            if (!down) return;
            var dx = e.pageX - startX;
            if (Math.abs(dx) > 4) { row.dataset.dragged = '1'; row.classList.add('is-dragging'); }
            row.scrollLeft = startLeft - dx;
        });
        window.addEventListener('mouseup', function () {
            if (!down) return;
            down = false;
            row.classList.remove('is-dragging');
            setTimeout(function () { delete row.dataset.dragged; }, 0);
        });
    }

    /* ------------------------------ Popular questions (toggle) ------------------------------ */

    var popular = document.getElementById('laiPopular');
    var popBtn = document.getElementById('laiPopBtn');
    var popBody = document.getElementById('laiPopularBody');

    function isPopularOpen() { return !popular.hidden; }

    function setPopular(open) {
        if (open === isPopularOpen()) return;
        if (open) {
            hideTypeahead();
            popular.hidden = false;
            requestAnimationFrame(function () { popular.classList.add('is-open'); });
        } else {
            popular.classList.remove('is-open');
            setTimeout(function () { if (!popular.classList.contains('is-open')) popular.hidden = true; }, 180);
        }
        popBtn.classList.toggle('is-on', open);
        popBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    popBtn.addEventListener('click', function () { setPopular(!isPopularOpen()); });
    document.getElementById('laiPopularClose').addEventListener('click', function () { setPopular(false); popBtn.focus(); });
    document.addEventListener('click', function (e) {
        if (isPopularOpen() && !popular.contains(e.target) && !popBtn.contains(e.target)) setPopular(false);
    });

    function renderPopular() {
        var label = mode === 'support' ? L('supportAll') : L('popular');
        document.querySelector('#laiPopularTitle span').textContent = label;
        popBtn.title = label;
        popBtn.setAttribute('aria-label', label);
        popBody.textContent = '';
        if (mode === 'support') return renderSupportSheet();
        var tabs = el('div', 'lai-tabs');
        tabs.setAttribute('role', 'tablist');
        var list = el('div', 'lai-qlist');
        TOPICS.forEach(function (tp) {
            var tb = el('button', 'lai-tab' + (tp.key === activeTopic ? ' is-on' : ''));
            tb.type = 'button';
            tb.setAttribute('role', 'tab');
            tb.setAttribute('aria-selected', tp.key === activeTopic ? 'true' : 'false');
            tb.appendChild(icon(tp.icon));
            tb.appendChild(document.createTextNode(' ' + tp[uiLang()]));
            tb.addEventListener('click', function () {
                activeTopic = tp.key;
                tabs.querySelectorAll('.lai-tab').forEach(function (x) { x.classList.remove('is-on'); x.setAttribute('aria-selected', 'false'); });
                tb.classList.add('is-on');
                tb.setAttribute('aria-selected', 'true');
                fillList(list, tp);
            });
            tabs.appendChild(tb);
        });
        popBody.appendChild(tabs);
        fillList(list, TOPICS.filter(function (t) { return t.key === activeTopic; })[0]);
        popBody.appendChild(list);
    }

    /** Support: topic tabs, then every known problem of that topic. */
    function renderSupportSheet() {
        var tabs = el('div', 'lai-tabs');
        tabs.setAttribute('role', 'tablist');
        var list = el('div', 'lai-qlist');
        var fill = function () {
            list.textContent = '';
            SUPPORT_PROBLEMS.filter(function (p) { return p.cat === activeSupportCat; }).forEach(function (p) {
                var b = el('button', 'lai-qitem');
                b.type = 'button';
                b.appendChild(el('span', null, p[uiLang()]));
                b.appendChild(icon('fa-arrow-right'));
                b.addEventListener('click', function () { ask('#pb ' + p.id, p[uiLang()], 'support'); });
                list.appendChild(b);
            });
            var other = el('button', 'lai-qitem is-other');
            other.type = 'button';
            other.appendChild(el('span', null, L('supportOther')));
            other.appendChild(icon('fa-headset'));
            other.addEventListener('click', function () { ask('#human', L('supportOther'), 'support'); });
            list.appendChild(other);
            requestAnimationFrame(function () { list.classList.add('is-in'); });
        };
        SUPPORT_CATS.forEach(function (c) {
            var tb = el('button', 'lai-tab' + (c.key === activeSupportCat ? ' is-on' : ''));
            tb.type = 'button';
            tb.setAttribute('role', 'tab');
            tb.setAttribute('aria-selected', c.key === activeSupportCat ? 'true' : 'false');
            tb.appendChild(icon(c.icon));
            tb.appendChild(document.createTextNode(' ' + c[uiLang()]));
            tb.addEventListener('click', function () {
                activeSupportCat = c.key;
                tabs.querySelectorAll('.lai-tab').forEach(function (x) { x.classList.remove('is-on'); x.setAttribute('aria-selected', 'false'); });
                tb.classList.add('is-on');
                tb.setAttribute('aria-selected', 'true');
                fill();
            });
            tabs.appendChild(tb);
        });
        popBody.appendChild(tabs);
        popBody.appendChild(list);
        fill();
    }

    function fillList(list, tp) {
        list.textContent = '';
        list.classList.remove('is-in');
        tp.q.forEach(function (pair) {
            var b = el('button', 'lai-qitem');
            b.type = 'button';
            b.appendChild(el('span', null, pick(pair)));
            b.appendChild(icon('fa-arrow-right'));
            b.addEventListener('click', function () { ask(pick(pair)); });
            list.appendChild(b);
        });
        requestAnimationFrame(function () { list.classList.add('is-in'); });
    }

    function showWelcome(show) {
        welcome.hidden = !show;
        root.classList.toggle('has-thread', !show);
    }

    /* ------------------------------ Type-ahead ------------------------------ */

    var ALL = [];
    TOPICS.forEach(function (t) { t.q.forEach(function (p) { ALL.push(p[0], p[1]); }); });
    var SUPPORT_ALL = [];
    SUPPORT_COMMON.forEach(function (p) { SUPPORT_ALL.push(p[0], p[1]); });
    var taIndex = -1;

    function hideTypeahead() { typeahead.hidden = true; typeahead.textContent = ''; taIndex = -1; }

    function showTypeahead() {
        var v = input.value.trim().toLowerCase();
        if (v.length < 2) return hideTypeahead();
        var words = v.split(/\s+/);
        var source = mode === 'support' ? SUPPORT_ALL : ALL;
        var hits = source.filter(function (s) {
            var l = s.toLowerCase();
            return l !== v && words.every(function (w) { return l.indexOf(w) !== -1; });
        }).slice(0, 5);
        if (!hits.length) return hideTypeahead();
        setPopular(false);
        typeahead.textContent = '';
        taIndex = -1;
        hits.forEach(function (s) {
            var b = el('button', 'lai-ta-item');
            b.type = 'button';
            b.setAttribute('role', 'option');
            b.appendChild(icon('fa-magnifying-glass'));
            b.appendChild(el('span', null, s));
            b.addEventListener('mousedown', function (e) { e.preventDefault(); ask(s); });
            typeahead.appendChild(b);
        });
        typeahead.hidden = false;
    }

    function moveTypeahead(d) {
        var items = typeahead.querySelectorAll('.lai-ta-item');
        if (!items.length) return false;
        taIndex = (taIndex + d + items.length) % items.length;
        items.forEach(function (x, i) { x.classList.toggle('is-on', i === taIndex); x.setAttribute('aria-selected', i === taIndex ? 'true' : 'false'); });
        return true;
    }

    /* ------------------------------ Composer ------------------------------ */

    function autosize() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        sendBtn.disabled = busy || !input.value.trim();
    }

    input.addEventListener('input', function () { autosize(); showTypeahead(); });
    input.addEventListener('blur', function () { setTimeout(hideTypeahead, 120); });
    input.addEventListener('keydown', function (e) {
        if (!typeahead.hidden && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
            if (moveTypeahead(e.key === 'ArrowDown' ? 1 : -1)) e.preventDefault();
            return;
        }
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            var sel = typeahead.querySelector('.lai-ta-item.is-on');
            ask(sel ? sel.textContent : input.value);
        }
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); ask(input.value); });

    /* ------------------------------ Conversation ------------------------------ */

    function scrollDown(smooth) {
        requestAnimationFrame(function () {
            body.scrollTo({ top: body.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
        });
    }

    function addUser(text) {
        var row = el('div', 'lai-row is-user');
        row.appendChild(el('div', 'lai-bubble', text));
        thread.appendChild(row);
        return row;
    }

    function addTyping() {
        var row = el('div', 'lai-row is-bot lai-typing-row');
        var card = el('div', 'lai-card lai-typing');
        card.appendChild(limo('is-thinking'));
        var dots = el('span', 'lai-dots');
        dots.appendChild(el('i')); dots.appendChild(el('i')); dots.appendChild(el('i'));
        card.appendChild(dots);
        card.appendChild(el('span', 'lai-typing-tx', L('thinking')));
        row.appendChild(card);
        thread.appendChild(row);
        return row;
    }

    /** q is what Limo receives; shown (optional) is what the chat shows, e.g. a button label. */
    function ask(q, shown, forceMode) {
        q = (q || '').trim();
        if (!q || busy) return;
        if (forceMode && forceMode !== mode) setMode(forceMode, false);
        lastQuery = q;
        lastShown = shown || null;
        setPopular(false);
        busy = true;
        root.classList.add('is-busy');
        input.value = '';
        autosize();
        hideTypeahead();
        showWelcome(false);
        addUser(shown || q);
        var typing = addTyping();
        scrollDown(true);

        fetch(root.dataset.send, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ query: q, shown: shown || null, lang: lang, mode: mode })
        })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (data) {
                typing.remove();
                if (data && data.success && data.reply) addReply(data.reply, true);
                else if (data && data.success && data.message) addLegacy(data.message);
                else addError();
            })
            .catch(function () { typing.remove(); addError(); })
            .finally(function () { busy = false; root.classList.remove('is-busy'); autosize(); scrollToLatest(); });
    }

    function scrollToLatest() {
        var last = thread.lastElementChild;
        if (!last) return;
        // Long answers: keep the start of the answer in view instead of jumping to its end
        var prev = last.previousElementSibling;
        var target = prev ? prev.offsetTop - 8 : last.offsetTop;
        body.scrollTo({ top: Math.min(target, body.scrollHeight), behavior: 'smooth' });
    }

    function addError() {
        var row = el('div', 'lai-row is-bot');
        var card = el('div', 'lai-card lai-card-error');
        var p = el('p', 'lai-note tone-bad');
        p.appendChild(icon('fa-wifi'));
        p.appendChild(el('span', null, L('err')));
        card.appendChild(p);
        var retry = el('button', 'lai-chip', L('retry'));
        retry.type = 'button';
        var q = lastQuery, qs = lastShown;
        retry.addEventListener('click', function () { row.remove(); var u = thread.lastElementChild; if (u && u.classList.contains('is-user')) u.remove(); ask(q, qs); });
        card.appendChild(retry);
        row.appendChild(card);
        thread.appendChild(row);
    }

    /** Old plain-text answers stored before the structured format. */
    function addLegacy(text) {
        var row = el('div', 'lai-row is-bot');
        var card = el('div', 'lai-card');
        var tmp = document.createElement('div');
        tmp.innerHTML = String(text).replace(/<br\s*\/?>/gi, '\n');
        var clean = (tmp.textContent || '').replace(/\*\*/g, '').replace(/\*/g, '');
        clean.split('\n').forEach(function (line) {
            if (line.trim()) card.appendChild(el('p', 'lai-text', line.trim()));
        });
        row.appendChild(card);
        thread.appendChild(row);
    }

    function addReply(r, animate) {
        var row = el('div', 'lai-row is-bot' + (animate ? '' : ' no-anim'));
        var card = el('article', 'lai-card intent-' + (r.intent || 'x') + (r.mode === 'support' ? ' is-support' : ''));

        var head = el('header', 'lai-card-head');
        var hi = el('span', 'lai-card-ic'); hi.appendChild(icon(r.icon || 'fa-robot'));
        head.appendChild(hi);
        head.appendChild(el('h4', 'lai-card-title', r.title || ''));
        card.appendChild(head);

        var copy = el('button', 'lai-copy');
        copy.type = 'button';
        copy.title = L('copy');
        copy.setAttribute('aria-label', L('copy'));
        copy.appendChild(icon('fa-regular fa-copy'));
        copy.addEventListener('click', function () {
            var text = card.innerText.replace(/\n{3,}/g, '\n\n').trim();
            var done = function () {
                copy.classList.add('is-done');
                copy.textContent = ''; copy.appendChild(icon('fa-check'));
                copy.title = L('copied');
                setTimeout(function () { copy.classList.remove('is-done'); copy.textContent = ''; copy.appendChild(icon('fa-regular fa-copy')); copy.title = L('copy'); }, 1600);
            };
            if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(done).catch(function () {});
            else {
                var ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); done(); } catch (e) {}
                ta.remove();
            }
        });
        card.appendChild(copy);

        (r.blocks || []).forEach(function (b) {
            var node = renderBlock(b, r.lang || uiLang());
            if (node) card.appendChild(node);
        });

        if (r.actions && r.actions.length) {
            var acts = el('div', 'lai-actions');
            r.actions.forEach(function (a) {
                var link = el('a', 'lai-action');
                link.href = a.url;
                link.appendChild(el('span', null, a.label));
                link.appendChild(icon('fa-arrow-up-right-from-square'));
                acts.appendChild(link);
            });
            card.appendChild(acts);
        }

        if (r.choices && r.choices.length) {
            var ch = el('div', 'lai-choices');
            r.choices.forEach(function (c) {
                var b;
                if (c.url) {
                    b = el('a', 'lai-choice tone-' + (c.tone || 'neutral'));
                    b.href = c.url;
                    if (/^https?:\/\/(wa\.me|api\.whatsapp)/.test(c.url)) { b.target = '_blank'; b.rel = 'noopener'; }
                } else {
                    b = el('button', 'lai-choice tone-' + (c.tone || 'neutral'));
                    b.type = 'button';
                    if (!animate) b.disabled = true; // old conversation: its steps are over
                    b.addEventListener('click', function () {
                        ch.querySelectorAll('button').forEach(function (x) { x.disabled = true; });
                        b.classList.add('is-picked');
                        ask(c.query, c.label, 'support');
                    });
                }
                if (c.icon) b.appendChild(icon(c.icon));
                b.appendChild(el('span', null, c.label));
                ch.appendChild(b);
            });
            card.appendChild(ch);
        }

        card.appendChild(replyFooter(r, card));
        row.appendChild(card);

        if (r.suggestions && r.suggestions.length) {
            var sug = el('div', 'lai-suggestions');
            sug.setAttribute('aria-label', L('related'));
            r.suggestions.forEach(function (s) {
                var c = el('button', 'lai-chip', s);
                c.type = 'button';
                c.addEventListener('click', function () { ask(s); });
                sug.appendChild(c);
            });
            row.appendChild(sug);
        }
        thread.appendChild(row);
    }

    /* ------------------------------ Answer footer: 👍 👎 · 🔊 · ⬇ ------------------------------ */

    function footBtn(iconName, label) {
        var b = el('button', 'lai-foot-btn');
        b.type = 'button';
        b.title = label;
        b.setAttribute('aria-label', label);
        b.appendChild(icon(iconName));
        return b;
    }

    function replyFooter(r, card) {
        var foot = el('div', 'lai-card-foot');

        // 👍 / 👎 — tells the Libraro team which answers to improve
        if (r.id) {
            var up = footBtn('fa-regular fa-thumbs-up', L('helpful'));
            var down = footBtn('fa-regular fa-thumbs-down', L('notHelpful'));
            var state = r.feedback || 0;
            var paint = function () {
                up.classList.toggle('is-on', state === 1);
                down.classList.toggle('is-on', state === -1);
                up.setAttribute('aria-pressed', state === 1 ? 'true' : 'false');
                down.setAttribute('aria-pressed', state === -1 ? 'true' : 'false');
            };
            var vote = function (v) {
                var next = state === v ? 0 : v;
                var prev = state;
                state = next; paint();
                fetch(root.dataset.feedback, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ id: r.id, value: next })
                }).then(function (res) {
                    if (!res.ok) throw new Error(res.status);
                    if (next !== 0) toast(next === 1 ? L('thanks') : L('thanksBad'));
                }).catch(function () { state = prev; paint(); });
            };
            up.addEventListener('click', function () { vote(1); });
            down.addEventListener('click', function () { vote(-1); });
            paint();
            foot.appendChild(up);
            foot.appendChild(down);
            foot.appendChild(el('span', 'lai-foot-sep'));
        }

        // 🔊 read the answer aloud (Hindi or English voice)
        if ('speechSynthesis' in window) {
            var speak = footBtn('fa-solid fa-volume-high', L('listen'));
            speak.addEventListener('click', function () {
                var synth = window.speechSynthesis;
                if (speak.classList.contains('is-on')) { synth.cancel(); return; }
                synth.cancel();
                document.querySelectorAll('#lbAi .lai-foot-btn.is-speaking').forEach(function (b) { b.classList.remove('is-on', 'is-speaking'); });
                var text = speakableText(card);
                var u = new SpeechSynthesisUtterance(text);
                var want = (r.lang === 'hi' || /[ऀ-ॿ]/.test(text)) ? 'hi' : 'en';
                u.lang = want === 'hi' ? 'hi-IN' : 'en-IN';
                var voice = synth.getVoices().filter(function (v) { return v.lang && v.lang.toLowerCase().indexOf(want) === 0; })[0];
                if (voice) u.voice = voice;
                u.rate = 0.95;
                u.onend = u.onerror = function () { speak.classList.remove('is-on', 'is-speaking'); };
                speak.classList.add('is-on', 'is-speaking');
                synth.speak(u);
            });
            foot.appendChild(speak);
        }

        // ⬇ PDF (server, Hindi-safe) and Excel (CSV of the tables)
        var tables = (r.blocks || []).filter(function (b) { return b.type === 'table'; });
        if (r.id) {
            var pdf = el('a', 'lai-foot-btn');
            pdf.href = root.dataset.export.replace('__ID__', r.id);
            pdf.title = L('pdf');
            pdf.setAttribute('aria-label', L('pdf'));
            pdf.appendChild(icon('fa-regular fa-file-pdf'));
            foot.appendChild(pdf);
        }
        if (tables.length) {
            var xls = footBtn('fa-regular fa-file-excel', L('excel'));
            xls.addEventListener('click', function () { downloadCsv(r, tables); });
            foot.appendChild(xls);
        }
        return foot;
    }

    /** Card text without button labels, for the voice. */
    function speakableText(card) {
        var clone = card.cloneNode(true);
        clone.querySelectorAll('button, .lai-card-foot, .lai-actions, .lai-row-acts, th').forEach(function (n) { n.remove(); });
        return clone.innerText.replace(/₹/g, ' rupees ').replace(/\s+/g, ' ').trim().slice(0, 1200);
    }

    /** Excel-friendly CSV (UTF-8 with BOM so Hindi opens correctly). */
    function downloadCsv(r, tables) {
        var esc = function (v) { v = (v && typeof v === 'object') ? (v.text || '') : (v == null ? '' : String(v)); return '"' + v.replace(/"/g, '""') + '"'; };
        var lines = [esc(r.title || 'Limo')];
        tables.forEach(function (t) {
            lines.push('');
            if (t.caption) lines.push(esc(t.caption));
            lines.push(t.columns.map(esc).join(','));
            t.rows.forEach(function (row) { lines.push(row.map(esc).join(',')); });
        });
        var blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'limo-' + (r.intent || 'answer') + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
    }

    /* ------------------------------ Action buttons (WhatsApp / Renew / Collect) ------------------------------ */

    function actionButton(a, compact) {
        var b = el(a.kind === 'whatsapp' ? 'button' : 'a', 'lai-act lai-act-' + a.kind + (compact ? ' is-compact' : ''));
        b.appendChild(icon(a.icon));
        if (!compact) b.appendChild(el('span', null, a.label));
        b.title = a.label;
        b.setAttribute('aria-label', a.label);
        if (a.kind === 'whatsapp') {
            b.type = 'button';
            b.addEventListener('click', function () {
                openConfirm({
                    title: a.confirm || a.label,
                    text: a.preview || '',
                    yes: L('openWa'),
                    icon: 'fa-brands fa-whatsapp',
                    tone: 'go',
                    returnFocus: b,
                    onYes: function () { window.open(a.url, '_blank', 'noopener'); }
                });
            });
        } else {
            b.href = a.url;
        }
        return b;
    }

    function cell(td, c) {
        if (c && typeof c === 'object') {
            if (c.url) {
                var a = el('a', 'lai-cell-link', c.text);
                a.href = c.url;
                td.appendChild(a);
            } else {
                td.textContent = c.text;
            }
            if (c.tone) td.classList.add('tone-' + c.tone);
        } else {
            td.textContent = c == null ? '' : c;
        }
    }

    function renderBlock(b, replyLang) {
        var n, i;
        switch (b.type) {
            case 'text':
                return el('p', 'lai-text', b.text);

            case 'note':
                n = el('p', 'lai-note tone-' + (b.tone || 'info'));
                n.appendChild(icon(b.tone === 'good' ? 'fa-circle-check' : (b.tone === 'bad' || b.tone === 'warn' ? 'fa-circle-exclamation' : 'fa-circle-info')));
                n.appendChild(el('span', null, b.text));
                return n;

            case 'stats':
                n = el('div', 'lai-stats' + (b.items.length === 1 ? ' is-single' : ''));
                b.items.forEach(function (s) {
                    var t = el('div', 'lai-stat tone-' + (s.tone || 'neutral'));
                    t.appendChild(el('span', 'lai-stat-label', s.label));
                    t.appendChild(el('strong', 'lai-stat-value', s.value));
                    if (s.sub) t.appendChild(el('span', 'lai-stat-sub', s.sub));
                    n.appendChild(t);
                });
                return n;

            case 'table':
                n = el('div', 'lai-table-wrap');
                if (b.caption) n.appendChild(el('div', 'lai-caption', b.caption));
                var scroller = el('div', 'lai-table-scroll');
                var table = el('table', 'lai-table');
                var thead = el('thead'), tr = el('tr');
                var hasActs = !!(b.rowActions && b.rowActions.some(function (x) { return x && x.length; }));
                b.columns.forEach(function (c) { tr.appendChild(el('th', null, c)); });
                if (hasActs) tr.appendChild(el('th', 'lai-th-acts', ''));
                thead.appendChild(tr); table.appendChild(thead);
                var tbody = el('tbody');
                b.rows.forEach(function (row, ri) {
                    var r = el('tr');
                    row.forEach(function (c) { var td = el('td'); cell(td, c); r.appendChild(td); });
                    if (hasActs) {
                        var at = el('td', 'lai-row-acts');
                        (b.rowActions[ri] || []).forEach(function (a) { at.appendChild(actionButton(a, true)); });
                        r.appendChild(at);
                    }
                    tbody.appendChild(r);
                });
                table.appendChild(tbody);
                scroller.appendChild(table);
                n.appendChild(scroller);
                if (b.more && b.moreLabel) n.appendChild(el('div', 'lai-more', b.moreLabel));
                return n;

            case 'chips':
                n = el('div', 'lai-seatchips');
                if (b.caption) n.appendChild(el('div', 'lai-caption', b.caption));
                var wrap = el('div', 'lai-seatchips-list');
                b.items.forEach(function (s) { wrap.appendChild(el('span', 'lai-seatchip', s)); });
                if (b.more) wrap.appendChild(el('span', 'lai-seatchip is-more', b.more));
                n.appendChild(wrap);
                return n;

            case 'bullets':
                n = el('ul', 'lai-bullets');
                b.items.forEach(function (s) { n.appendChild(el('li', null, s)); });
                return n;

            case 'guide':
                return renderGuide(b, replyLang);

            case 'checks': // what Limo checked in the data, and what it found
                n = el('div', 'lai-check');
                if (b.caption) n.appendChild(el('div', 'lai-caption', b.caption));
                b.items.forEach(function (c) {
                    var it = el('div', 'lai-check-item tone-' + (c.tone || 'info'));
                    it.appendChild(icon(c.tone === 'good' ? 'fa-circle-check' : (c.tone === 'bad' ? 'fa-circle-xmark' : (c.tone === 'warn' ? 'fa-triangle-exclamation' : 'fa-circle-info'))));
                    it.appendChild(el('span', null, c.text));
                    n.appendChild(it);
                });
                return n;

            case 'buttons':
                n = el('div', 'lai-btnrow');
                b.items.forEach(function (a) { n.appendChild(actionButton(a, false)); });
                return n;

            case 'bars':
                n = el('figure', 'lai-bars');
                if (b.caption) n.appendChild(el('figcaption', 'lai-caption', b.caption));
                var max = Math.max.apply(null, b.items.map(function (x) { return x.value; })) || 1;
                var many = b.items.length > 8;
                var list = el('div', 'lai-bars-list' + (many ? ' is-columns' : ''));
                b.items.forEach(function (x) {
                    var item = el('div', 'lai-bar-item');
                    item.title = x.label + ': ' + x.display;
                    var pct = Math.max(x.value > 0 ? 3 : 0, Math.round(x.value / max * 100));
                    if (many) {
                        var col = el('span', 'lai-bar-col');
                        var fill = el('span', 'lai-bar-fill');
                        fill.style.height = pct + '%';
                        col.appendChild(fill);
                        item.appendChild(col);
                        item.appendChild(el('span', 'lai-bar-x', x.label));
                    } else {
                        item.appendChild(el('span', 'lai-bar-label', x.label));
                        var track = el('span', 'lai-bar-track');
                        var f = el('span', 'lai-bar-fill');
                        f.style.width = pct + '%';
                        track.appendChild(f);
                        item.appendChild(track);
                        item.appendChild(el('span', 'lai-bar-val', x.display));
                    }
                    list.appendChild(item);
                });
                n.appendChild(list);
                return n;
        }
        return null;
    }

    function renderGuide(b, startLang) {
        var cur = (b[startLang] && b[startLang].length) ? startLang : (startLang === 'hi' ? 'en' : 'hi');
        var wrap = el('div', 'lai-guide');
        var list = el('ol', 'lai-steps');
        var toggle = el('button', 'lai-guide-toggle');
        toggle.type = 'button';

        function draw() {
            list.textContent = '';
            (b[cur] || []).forEach(function (s) { list.appendChild(el('li', null, s)); });
            list.classList.toggle('is-single', (b[cur] || []).length === 1);
            var other = cur === 'hi' ? 'en' : 'hi';
            toggle.hidden = !(b[other] && b[other].length);
            toggle.textContent = '';
            toggle.appendChild(icon('fa-language'));
            toggle.appendChild(document.createTextNode(' ' + (other === 'hi' ? UI.en.seeHi : UI.en.seeEn)));
            wrap.setAttribute('lang', cur);
        }
        toggle.addEventListener('click', function () { cur = cur === 'hi' ? 'en' : 'hi'; draw(); });
        draw();
        wrap.appendChild(list);
        wrap.appendChild(toggle);
        return wrap;
    }

    /* ------------------------------ History ------------------------------ */

    /**
     * The window always opens clean. Old messages are only fetched to offer a
     * "Show previous chat" link; they are drawn only when the user asks for them.
     */
    function loadHistory() {
        historyLoaded = true;
        fetch(root.dataset.history, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success || !data.history || !data.history.length) return;
                savedHistory = data.history;
                if (!thread.children.length) renderWelcome();
                syncClearBtn();
            })
            .catch(function () {});
    }

    function loadAlerts() {
        alertsAt = Date.now();
        fetch(root.dataset.alerts + '?lang=' + uiLang(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                alertsData = (data && data.success && data.alerts) ? data.alerts : [];
                if (!thread.children.length) renderWelcome();
            })
            .catch(function () {});
    }

    function showSavedHistory() {
        if (!savedHistory.length) return;
        var current = Array.prototype.slice.call(thread.children);
        thread.textContent = '';
        savedHistory.forEach(function (m) {
            if (m.sender === 'user') addUser(m.message || '').classList.add('no-anim');
            else if (m.reply) addReply(m.reply, false);
            else if (m.message) addLegacy(m.message);
        });
        current.forEach(function (n) { thread.appendChild(n); }); // keep anything asked in this session after it
        savedHistory = [];
        showWelcome(false);
        scrollDown(false);
    }

    /* ------------------------------ Clear chat (with confirmation) ------------------------------ */

    var clearBtn = document.getElementById('laiClear');
    var confirmEl = document.getElementById('laiConfirm');
    var confirmYes = document.getElementById('laiConfirmYes');
    var confirmNo = document.getElementById('laiConfirmNo');
    var confirmErr = document.getElementById('laiConfirmErr');
    var toastEl = document.getElementById('laiToast');
    var clearing = false;
    var toastTimer = null;

    /** The clear button is only active when there is something to clear. */
    function syncClearBtn() {
        clearBtn.disabled = !thread.children.length && !savedHistory.length;
    }
    new MutationObserver(syncClearBtn).observe(thread, { childList: true });

    function isConfirmOpen() { return !confirmEl.hidden; }

    /**
     * One confirmation box for everything: clearing the chat (default) or an action
     * such as opening WhatsApp — opts: {title, text, yes, icon, tone, onYes, returnFocus}.
     */
    var confirmOpts = null;

    function openConfirm(opts) {
        if (!opts || opts instanceof Event) {
            if (!thread.children.length && !savedHistory.length) return;
            opts = { title: L('clearTitle'), text: L('clearText'), yes: L('clearYes'), icon: 'fa-regular fa-trash-can', tone: 'danger', onYes: null, returnFocus: clearBtn };
        }
        confirmOpts = opts;
        document.getElementById('laiConfirmTitle').textContent = opts.title;
        document.getElementById('laiConfirmText').textContent = opts.text || '';
        document.getElementById('laiConfirmText').hidden = !opts.text;
        var ic = confirmEl.querySelector('.lai-confirm-ic');
        ic.className = 'lai-confirm-ic tone-' + (opts.tone || 'danger');
        ic.textContent = '';
        ic.appendChild(icon(opts.icon || 'fa-circle-question'));
        confirmYes.classList.toggle('lai-btn-danger', (opts.tone || 'danger') === 'danger');
        confirmYes.classList.toggle('lai-btn-go', (opts.tone || 'danger') !== 'danger');
        confirmNo.textContent = L('clearNo');
        confirmYes.firstElementChild.textContent = opts.yes;
        confirmErr.hidden = true;
        confirmEl.hidden = false;
        requestAnimationFrame(function () { confirmEl.classList.add('is-open'); });
        hideTypeahead();
        confirmNo.focus();
    }

    function closeConfirm() {
        if (clearing) return;
        confirmEl.classList.remove('is-open');
        setTimeout(function () { confirmEl.hidden = true; }, 180);
        var back = confirmOpts && confirmOpts.returnFocus;
        if (back && !back.disabled) back.focus(); else input.focus();
    }

    function toast(text) {
        toastEl.textContent = '';
        toastEl.appendChild(icon('fa-circle-check'));
        toastEl.appendChild(el('span', null, text));
        toastEl.hidden = false;
        requestAnimationFrame(function () { toastEl.classList.add('is-open'); });
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            toastEl.classList.remove('is-open');
            setTimeout(function () { toastEl.hidden = true; }, 220);
        }, 2200);
    }

    clearBtn.addEventListener('click', openConfirm);
    confirmNo.addEventListener('click', closeConfirm);
    confirmEl.addEventListener('click', function (e) { if (e.target === confirmEl) closeConfirm(); });
    confirmEl.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { e.stopPropagation(); closeConfirm(); return; }
        if (e.key === 'Tab') { // keep focus inside the dialog
            var first = confirmNo, last = confirmYes;
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    });

    confirmYes.addEventListener('click', function () {
        if (clearing) return;
        if (confirmOpts && confirmOpts.onYes) { // an action (e.g. open WhatsApp)
            var run = confirmOpts.onYes;
            closeConfirm();
            run();
            return;
        }
        clearing = true;
        confirmYes.disabled = confirmNo.disabled = true;
        confirmYes.classList.add('is-busy');
        confirmYes.firstElementChild.textContent = L('clearing');
        confirmErr.hidden = true;

        fetch(root.dataset.clear, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (data) {
                if (!data || !data.success) throw new Error('fail');
                // Only wipe the screen once the server has really deleted the history
                thread.textContent = '';
                savedHistory = [];
                renderWelcome();
                showWelcome(true);
                syncClearBtn();
                body.scrollTop = 0;
                clearing = false;
                closeConfirm();
                toast(L('cleared'));
                input.focus();
            })
            .catch(function () {
                clearing = false;
                confirmErr.textContent = L('clearErr');
                confirmErr.hidden = false;
                confirmYes.firstElementChild.textContent = L('clearYes');
            })
            .finally(function () {
                confirmYes.disabled = confirmNo.disabled = false;
                confirmYes.classList.remove('is-busy');
            });
    });

    /* ------------------------------ Voice input ------------------------------ */

    (function () {
        var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        var mic = document.getElementById('laiMic');
        if (!SR || !mic) return;
        mic.hidden = false;
        var rec = null;
        var listening = false;

        function stop() {
            listening = false;
            mic.classList.remove('is-on');
            input.placeholder = L('ph');
        }

        mic.addEventListener('click', function () {
            if (listening && rec) { rec.stop(); return; }
            rec = new SR();
            // Auto: Hindi recogniser also understands Hinglish and Indian-English well
            rec.lang = lang === 'en' ? 'en-IN' : 'hi-IN';
            rec.interimResults = true;
            rec.maxAlternatives = 1;
            var finalText = '';
            rec.onresult = function (e) {
                var interim = '';
                for (var i = e.resultIndex; i < e.results.length; i++) {
                    if (e.results[i].isFinal) finalText += e.results[i][0].transcript;
                    else interim += e.results[i][0].transcript;
                }
                input.value = (finalText + interim).trim();
                autosize();
            };
            rec.onerror = function () { stop(); input.placeholder = L('micErr'); };
            rec.onend = function () {
                var said = input.value.trim();
                stop();
                if (said) ask(said);
            };
            try {
                rec.start();
                listening = true;
                mic.classList.add('is-on');
                input.value = '';
                input.placeholder = L('listening');
            } catch (e) { stop(); }
        });
    })();

    applyLang();
    showWelcome(true);
})();
</script>
