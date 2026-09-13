@extends('layouts.library')

@section('title', 'How to Use Libraro - Operational Guide')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('public/css/how-to-use.css') }}?v={{ time() }}">

<div class="how-to-use-module">

    <!-- ====================================================================
         Hero Section & Instant Search Banner
         ==================================================================== -->
    <div class="guide-hero-card">
        <div class="hero-badge-tag">
            <i class="fa-solid fa-book-bookmark"></i>
            <span>LIBRARO OPERATIONAL MANUAL</span>
        </div>

        <h1 class="guide-hero-title">Library Operations &amp; User Guide</h1>
        <p class="guide-hero-subtitle">
            Master every aspect of Libraro. Follow verified step-by-step instructions for learner admissions, seat allocations, plan renewals, fee collections, attendance QR kiosks, and daily account management.
        </p>

        <!-- Live Instant Search Bar -->
        <div class="guide-search-wrapper">
            <i class="fa-solid fa-magnifying-glass search-icon-left"></i>
            <input type="text"
                   id="guideSearchInput"
                   class="guide-search-input"
                   placeholder="Search any operation (e.g. seat allot, renew, expense, attendance, refund)..."
                   autocomplete="off">
            <button type="button" id="searchClearBtn" class="search-clear-btn" title="Clear Search">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Quick Topic Filter Pills -->
        <div class="topic-pills-row">
            <span class="topic-pill active" data-filter="all">All Topics</span>
            <span class="topic-pill" data-filter="seat">Seat Allotment</span>
            <span class="topic-pill" data-filter="renew">Plan Renewal</span>
            <span class="topic-pill" data-filter="payment">Payment &amp; Dues</span>
            <span class="topic-pill" data-filter="attendance">Attendance &amp; QR</span>
            <span class="topic-pill" data-filter="expense">Expenses</span>
            <span class="topic-pill" data-filter="swap">Swap &amp; Upgrade</span>
            <span class="topic-pill" data-filter="id card">ID Cards</span>
        </div>
    </div>

    <!-- ====================================================================
         5-Phase Operational Overview Roadmap Card
         ==================================================================== -->
    <div class="roadmap-overview-card">
        <div class="roadmap-header">
            <i class="fa-solid fa-route text-teal fs-5"></i>
            <h5>Core Operating Architecture (Quick Overview)</h5>
        </div>

        <div class="roadmap-steps-grid">
            <div class="roadmap-step-item">
                <span class="roadmap-step-number">Phase 1</span>
                <div class="roadmap-step-title">Library Setup</div>
                <p class="roadmap-step-desc">Configure your floors, shifts (FD/FH/SH/FN), and monthly fee pricing in Library Master.</p>
            </div>

            <div class="roadmap-step-item">
                <span class="roadmap-step-number">Phase 2</span>
                <div class="roadmap-step-title">Learner Onboarding</div>
                <p class="roadmap-step-desc">Register walk-in students, assign designated or flexible seats, and collect admission fees.</p>
            </div>

            <div class="roadmap-step-item">
                <span class="roadmap-step-number">Phase 3</span>
                <div class="roadmap-step-title">Daily Attendance</div>
                <p class="roadmap-step-desc">Run the live QR kiosk or webcam scanner at the reception desk for automated entry/exit logging.</p>
            </div>

            <div class="roadmap-step-item">
                <span class="roadmap-step-number">Phase 4</span>
                <div class="roadmap-step-title">Renewals &amp; Dues</div>
                <p class="roadmap-step-desc">Track seats expiring in next 5 days and send automated 1-click WhatsApp payment reminders.</p>
            </div>

            <div class="roadmap-step-item">
                <span class="roadmap-step-number">Phase 5</span>
                <div class="roadmap-step-title">Accounts &amp; Audit</div>
                <p class="roadmap-step-desc">Log daily utility expenses, review real-time profit &amp; loss, and check monthly activity streams.</p>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         Language Switcher & Accordion Controls Bar
         ==================================================================== -->
    <div class="lang-selector-bar">
        <!-- Language Tabs -->
        <div class="lang-tabs-nav" role="tablist">
            <button class="lang-tab-btn active" id="english-tab" data-bs-toggle="tab" data-bs-target="#english" type="button" role="tab">
                <i class="fa-solid fa-language"></i>
                <span>English Guide</span>
            </button>
            <button class="lang-tab-btn" id="hindi-tab" data-bs-toggle="tab" data-bs-target="#hindi" type="button" role="tab">
                <i class="fa-solid fa-om"></i>
                <span>हिन्दी गाइड (Hindi)</span>
            </button>
        </div>

        <!-- Global Accordion Expand / Collapse Buttons -->
        <div class="accordion-control-actions">
            <button type="button" class="btn-action-ghost" id="btnExpandAll">
                <i class="fa-solid fa-angles-down"></i> Expand All
            </button>
            <button type="button" class="btn-action-ghost" id="btnCollapseAll">
                <i class="fa-solid fa-angles-up"></i> Collapse All
            </button>
        </div>
    </div>

    <!-- ====================================================================
         Tab Contents (English & Hindi)
         ==================================================================== -->
    <div class="tab-content" id="guideTabContent">

        <!-- ==========================
             ENGLISH TAB
             ========================== -->
        <div class="tab-pane fade show active" id="english" role="tabpanel">

            <div class="alert alert-info border-0 rounded-3 d-flex align-items-center gap-3 p-3 mb-3" style="background: #eff6ff; color: #1e40af;">
                <i class="fa-solid fa-circle-info fs-5 flex-shrink-0"></i>
                <div style="font-size: 0.9rem;">
                    <strong>Notice:</strong> All instructions below represent standard operating procedures for Libraro Web &amp; Mobile. Click any operation to view detailed steps.
                </div>
            </div>

            <!-- Empty Search Notice -->
            <div class="search-empty-state" id="searchEmptyEng">
                <div class="search-empty-icon"><i class="fa-solid fa-file-circle-xmark"></i></div>
                <div class="search-empty-title">No Matching Operations Found</div>
                <p class="search-empty-desc">We couldn't find any guide topics matching your search. Try different keywords like "seat", "renew", "payment", or "expense".</p>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 reset-search-btn">Reset Search</button>
            </div>

            <!-- Accordion Container -->
            <div class="guide-accordion-container" id="engAccordion">
                @foreach($howtoUseContent as $content)
                @php
                    $uid = 'eng'.$content->id;
                    $opLower = strtolower($content->operation_name ?? '');

                    // Contextual Icon mapping
                    $icon = 'fa-circle-info';
                    $category = 'General Operation';
                    if (str_contains($opLower, 'seat') || str_contains($opLower, 'allot') || str_contains($opLower, 'book')) {
                        $icon = 'fa-chair';
                        $category = 'Seat Management';
                    } elseif (str_contains($opLower, 'renew')) {
                        $icon = 'fa-rotate-right';
                        $category = 'Plan Renewal';
                    } elseif (str_contains($opLower, 'payment') || str_contains($opLower, 'fee') || str_contains($opLower, 'due')) {
                        $icon = 'fa-receipt';
                        $category = 'Billing & Fees';
                    } elseif (str_contains($opLower, 'attendance') || str_contains($opLower, 'qr')) {
                        $icon = 'fa-qrcode';
                        $category = 'Attendance & QR';
                    } elseif (str_contains($opLower, 'expense')) {
                        $icon = 'fa-wallet';
                        $category = 'Daily Accounts';
                    } elseif (str_contains($opLower, 'swap')) {
                        $icon = 'fa-arrows-rotate';
                        $category = 'Seat Swapping';
                    } elseif (str_contains($opLower, 'upgrade') || str_contains($opLower, 'change')) {
                        $icon = 'fa-arrow-up-right-dots';
                        $category = 'Plan Upgrades';
                    } elseif (str_contains($opLower, 'id card') || str_contains($opLower, 'card')) {
                        $icon = 'fa-id-card';
                        $category = 'ID Cards';
                    }
                @endphp

                <div class="guide-item-card" data-title="{{ $opLower }}" data-content="{{ strtolower($content->usage_english ?? '') }}">
                    <div class="guide-accordion-header" id="heading-{{ $uid }}">
                        <button class="guide-accordion-btn collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapse-{{ $uid }}"
                                aria-expanded="false"
                                aria-controls="collapse-{{ $uid }}">
                            <div class="guide-btn-left">
                                <div class="guide-op-icon">
                                    <i class="fa-solid {{ $icon }}"></i>
                                </div>
                                <div>
                                    <h6 class="guide-op-title">{{ $content->operation_name }}</h6>
                                    <span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 500;">
                                        {{ $category }}
                                    </span>
                                </div>
                            </div>
                            <div class="guide-chevron">
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                        </button>
                    </div>

                    <div id="collapse-{{ $uid }}" class="accordion-collapse collapse" data-bs-parent="#engAccordion">
                        <div class="guide-accordion-body">
                            <!-- Body Toolbar -->
                            <div class="guide-body-toolbar">
                                <div class="guide-tags-row">
                                    <span class="guide-category-tag"><i class="fa-solid fa-tag me-1"></i>{{ $category }}</span>
                                    <span>&bull; Step-by-Step Guide</span>
                                </div>
                                <button type="button" class="btn-copy-steps" data-target="content-{{ $uid }}">
                                    <i class="fa-regular fa-copy"></i>
                                    <span>Copy Steps</span>
                                </button>
                            </div>

                            <!-- Content -->
                            <pre class="guide-instruction-content" id="content-{{ $uid }}">{{ trim($content->usage_english) }}</pre>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

        </div>

        <!-- ==========================
             HINDI TAB
             ========================== -->
        <div class="tab-pane fade" id="hindi" role="tabpanel">

            <div class="alert alert-info border-0 rounded-3 d-flex align-items-center gap-3 p-3 mb-3" style="background: #eff6ff; color: #1e40af; font-family: 'Noto Sans Devanagari', sans-serif;">
                <i class="fa-solid fa-circle-info fs-5 flex-shrink-0"></i>
                <div style="font-size: 0.95rem;">
                    <strong>महत्वपूर्ण सूचना:</strong> नीचे दिए गए निर्देश Libraro वेब और मोबाइल ऐप की सभी मुख्य प्रक्रियाओं को सरल भाषा में समझाते हैं। किसी भी प्रक्रिया के चरण देखने के लिए उस पर क्लिक करें।
                </div>
            </div>

            <!-- Empty Search Notice -->
            <div class="search-empty-state" id="searchEmptyHin">
                <div class="search-empty-icon"><i class="fa-solid fa-file-circle-xmark"></i></div>
                <div class="search-empty-title">कोई परिणाम नहीं मिला</div>
                <p class="search-empty-desc">आपकी खोज से संबंधित कोई निर्देश नहीं मिला। कृपया अन्य शब्द जैसे सीट, रिन्यू, फीस या उपस्थिति खोजें।</p>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 reset-search-btn">सर्च रीसेट करें</button>
            </div>

            <!-- Accordion Container -->
            <div class="guide-accordion-container" id="hinAccordion">
                @foreach($howtoUseContent as $content)
                @php
                    $uid = 'hin'.$content->id;
                    $opLower = strtolower($content->operation_name ?? '');

                    // Contextual Icon mapping
                    $icon = 'fa-circle-info';
                    $categoryHin = 'सामान्य प्रक्रिया';
                    if (str_contains($opLower, 'seat') || str_contains($opLower, 'allot') || str_contains($opLower, 'book')) {
                        $icon = 'fa-chair';
                        $categoryHin = 'सीट प्रबंधन';
                    } elseif (str_contains($opLower, 'renew')) {
                        $icon = 'fa-rotate-right';
                        $categoryHin = 'प्लान रिन्यूअल';
                    } elseif (str_contains($opLower, 'payment') || str_contains($opLower, 'fee') || str_contains($opLower, 'due')) {
                        $icon = 'fa-receipt';
                        $categoryHin = 'फीस व बकाया';
                    } elseif (str_contains($opLower, 'attendance') || str_contains($opLower, 'qr')) {
                        $icon = 'fa-qrcode';
                        $categoryHin = 'उपस्थिति व QR';
                    } elseif (str_contains($opLower, 'expense')) {
                        $icon = 'fa-wallet';
                        $categoryHin = 'खर्च प्रबंधन';
                    } elseif (str_contains($opLower, 'swap')) {
                        $icon = 'fa-arrows-rotate';
                        $categoryHin = 'सीट बदलना (स्वैप)';
                    } elseif (str_contains($opLower, 'upgrade') || str_contains($opLower, 'change')) {
                        $icon = 'fa-arrow-up-right-dots';
                        $categoryHin = 'प्लान अपग्रेड';
                    } elseif (str_contains($opLower, 'id card') || str_contains($opLower, 'card')) {
                        $icon = 'fa-id-card';
                        $categoryHin = 'आईडी कार्ड';
                    }
                @endphp

                <div class="guide-item-card" data-title="{{ $opLower }}" data-content="{{ strtolower($content->usage_hindi ?? '') }}">
                    <div class="guide-accordion-header" id="heading-{{ $uid }}">
                        <button class="guide-accordion-btn collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapse-{{ $uid }}"
                                aria-expanded="false"
                                aria-controls="collapse-{{ $uid }}">
                            <div class="guide-btn-left">
                                <div class="guide-op-icon">
                                    <i class="fa-solid {{ $icon }}"></i>
                                </div>
                                <div>
                                    <h6 class="guide-op-title">{{ $content->operation_name }}</h6>
                                    <span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 500;">
                                        {{ $categoryHin }}
                                    </span>
                                </div>
                            </div>
                            <div class="guide-chevron">
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                        </button>
                    </div>

                    <div id="collapse-{{ $uid }}" class="accordion-collapse collapse" data-bs-parent="#hinAccordion">
                        <div class="guide-accordion-body">
                            <!-- Body Toolbar -->
                            <div class="guide-body-toolbar">
                                <div class="guide-tags-row">
                                    <span class="guide-category-tag"><i class="fa-solid fa-tag me-1"></i>{{ $categoryHin }}</span>
                                    <span>&bull; चरणबद्ध निर्देश</span>
                                </div>
                                <button type="button" class="btn-copy-steps" data-target="content-{{ $uid }}">
                                    <i class="fa-regular fa-copy"></i>
                                    <span>निर्देश कॉपी करें</span>
                                </button>
                            </div>

                            <!-- Content -->
                            <pre class="guide-instruction-content font-hindi" id="content-{{ $uid }}">{{ trim($content->usage_hindi) }}</pre>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

        </div>

    </div>

    <!-- ====================================================================
         Help & Support Dedicated Dock
         ==================================================================== -->
    <div class="guide-support-card">
        <div class="support-left">
            <div class="support-icon">
                <i class="fa-solid fa-headset"></i>
            </div>
            <div>
                <div class="support-title">Need Hands-on Help or Training?</div>
                <p class="support-desc">Our dedicated library onboarding team is always ready to guide you and your staff.</p>
            </div>
        </div>

        <div class="support-actions">
            <a href="{{ route('library.video-training') }}" class="btn-video-guide">
                <i class="fa-solid fa-play-circle"></i>
                <span>Watch Video Tutorials</span>
            </a>

            <a href="https://wa.me/919999999999?text=Hello%20Libraro%20Team%2C%20I%20need%20assistance%20with%20my%20library%20operations." target="_blank" class="btn-support-wa">
                <i class="fab fa-whatsapp"></i>
                <span>WhatsApp Support</span>
            </a>
        </div>
    </div>

</div>

<!-- Interactive Search, Filtering & Copy Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('guideSearchInput');
    const clearBtn = document.getElementById('searchClearBtn');
    const topicPills = document.querySelectorAll('.topic-pill');
    const resetBtns = document.querySelectorAll('.reset-search-btn');

    function performSearch(query) {
        const q = query.trim().toLowerCase();
        if (q.length > 0) {
            clearBtn.style.display = 'flex';
        } else {
            clearBtn.style.display = 'none';
        }

        ['eng', 'hin'].forEach(lang => {
            const containerId = lang === 'eng' ? 'engAccordion' : 'hinAccordion';
            const emptyId = lang === 'eng' ? 'searchEmptyEng' : 'searchEmptyHin';
            const container = document.getElementById(containerId);
            const emptyState = document.getElementById(emptyId);

            if (!container) return;

            const cards = container.querySelectorAll('.guide-item-card');
            let matchCount = 0;

            cards.forEach(card => {
                const title = card.getAttribute('data-title') || '';
                const content = card.getAttribute('data-content') || '';

                if (q === '' || title.includes(q) || content.includes(q)) {
                    card.style.display = '';
                    matchCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (emptyState) {
                emptyState.style.display = matchCount === 0 ? 'block' : 'none';
            }
        });
    }

    // Input event
    searchInput.addEventListener('input', function () {
        performSearch(this.value);
    });

    // Clear search
    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        performSearch('');
        searchInput.focus();
        topicPills.forEach(p => p.classList.remove('active'));
        document.querySelector('.topic-pill[data-filter="all"]')?.classList.add('active');
    });

    // Reset buttons
    resetBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            searchInput.value = '';
            performSearch('');
            topicPills.forEach(p => p.classList.remove('active'));
            document.querySelector('.topic-pill[data-filter="all"]')?.classList.add('active');
        });
    });

    // Topic Pills filter
    topicPills.forEach(pill => {
        pill.addEventListener('click', function () {
            topicPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');
            if (filter === 'all') {
                searchInput.value = '';
                performSearch('');
            } else {
                searchInput.value = filter;
                performSearch(filter);
            }
        });
    });

    // Expand All / Collapse All
    const btnExpandAll = document.getElementById('btnExpandAll');
    const btnCollapseAll = document.getElementById('btnCollapseAll');

    btnExpandAll.addEventListener('click', function () {
        const activeTab = document.querySelector('.tab-pane.active');
        if (!activeTab) return;
        const collapses = activeTab.querySelectorAll('.accordion-collapse');
        collapses.forEach(c => {
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(c, { toggle: false });
            bsCollapse.show();
        });
    });

    btnCollapseAll.addEventListener('click', function () {
        const activeTab = document.querySelector('.tab-pane.active');
        if (!activeTab) return;
        const collapses = activeTab.querySelectorAll('.accordion-collapse');
        collapses.forEach(c => {
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(c, { toggle: false });
            bsCollapse.hide();
        });
    });

    // Copy Instructions Handler
    document.querySelectorAll('.btn-copy-steps').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const targetEl = document.getElementById(targetId);
            if (!targetEl) return;

            const text = targetEl.textContent || targetEl.innerText;
            navigator.clipboard.writeText(text).then(() => {
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="fa-solid fa-check text-success"></i> <span>Copied!</span>';
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                }, 2000);
            }).catch(err => {
                console.error('Copy failed:', err);
            });
        });
    });
});
</script>
@endsection