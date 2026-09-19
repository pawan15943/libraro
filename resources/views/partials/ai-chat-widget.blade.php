<link rel="stylesheet" href="{{ asset('public/css/ai-chat-widget.css') }}">

<div class="custom-libraro-ai-widget">
    <!-- Floating Trigger Button (Fallback when right sidebar is not present) -->
    <button type="button" class="ai-trigger-btn ai-floating-trigger" id="libraroAiTrigger" onclick="toggleLibraroAiBox()" title="Libraro AI Assistant">
        <i class="fa-solid fa-robot"></i>
        <span class="ai-trigger-badge">AI</span>
    </button>

    <!-- Chat Box Drawer -->
    <div class="ai-chat-box" id="libraroAiBox">
        <!-- Header -->
        <div class="ai-chat-header">
            <div class="ai-chat-header-title">
                <i class="fa-solid fa-robot fs-5"></i>
                <div>
                    <h5 class="m-0">Libraro AI Assistant</h5>
                    <small class="ai-header-sub">Guided Question & Answer</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="ai-header-btn" id="libraroAiToggleWizard" onclick="toggleAiWizardBar()" title="Toggle Question Selector">
                    <i class="fa-solid fa-chevron-up" id="aiWizardToggleIcon"></i>
                </button>
                <button type="button" class="ai-header-btn" id="libraroAiClear" title="Clear Chat">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
                <button type="button" class="ai-header-btn" onclick="toggleLibraroAiBox()" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Top Sleek Guided Wizard Bar (Collapsible Dropdown Toggle) -->
        <div class="ai-wizard-bar" id="aiWizardBar">
            <!-- Step Indicator Header -->
            <div class="ai-wizard-step-header" id="aiWizardStepHeader">
                <span class="ai-wizard-step-title" id="aiStepTitle">
                    <i class="fa-solid fa-circle-question me-1 text-primary"></i>
                    If you want help, ask here! / अगर आप सहायता चाहते हैं, तो यहाँ पूछें
                </span>
            </div>

            <!-- Step 1: Category Selection Dropdown -->
            <div class="ai-wizard-step" id="aiStepCategory">
                <label class="ai-wizard-label"><i class="fa-solid fa-list-check me-1"></i> 1. Choose Topic / श्रेणी चुनें:</label>
                <select class="form-select form-select-sm ai-wizard-select" id="aiCatSelect" onchange="onCategoryChange(this.value)">
                    <option value="" selected disabled>-- Select Topic Category / श्रेणी चुनें --</option>
                    <option value="overview">Overview & Revenue (आज का विवरण व कलेक्शन)</option>
                    <option value="dues">Dues & Student List (बकाया राशि व छात्र सूची)</option>
                    <option value="seats">Seats & Occupancy (सीटें व ऑक्यूपेंसी)</option>
                    <option value="shifts">Shifts & Subscription (शिफ्ट व सब्सक्रिप्शन)</option>
                    <option value="howto">How to Use / Guide (सॉफ्टवेयर उपयोग निर्देशिका)</option>
                </select>
            </div>

            <!-- Step 2: Language Selection -->
            <div class="ai-wizard-step" id="aiStepLanguage" style="display:none;">
                <label class="ai-wizard-label"><i class="fa-solid fa-language me-1"></i> 2. Select Language / भाषा चुनें:</label>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary ai-lang-wizard-btn w-50" onclick="onLanguageSelect('en')">
                        <i class="fa-solid fa-earth-americas me-1"></i> English
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary ai-lang-wizard-btn w-50" onclick="onLanguageSelect('hi')">
                        <i class="fa-solid fa-om me-1"></i> हिंदी (Hindi)
                    </button>
                </div>
            </div>

            <!-- Step 3: Question Selection -->
            <div class="ai-wizard-step" id="aiStepQuestion" style="display:none;">
                <label class="ai-wizard-label" id="aiQuestionSelectLabel"><i class="fa-solid fa-comments me-1"></i> 3. Choose Question / प्रश्न चुनें:</label>
                <select class="form-select form-select-sm ai-wizard-select" id="aiQuestionSelect" onchange="onQuestionSelect(this.value)">
                    <!-- Populated dynamically -->
                </select>
            </div>
        </div>

        <!-- Chat Body (Messages Area) -->
        <div class="ai-chat-body" id="libraroAiBody">
            <div class="ai-msg assistant">
                <i class="fa-solid fa-hands-clapping text-warning me-1"></i>
                <strong>If you want help, ask here! / अगर आप सहायता चाहते हैं, तो यहाँ पूछें।</strong><br><br>
                Upar diye gaye <strong>Dropdown</strong> se topic aur question select karein — jawab yahan dikhega!
            </div>
        </div>

        <!-- Input Footer Area -->
        <form id="libraroAiForm" class="ai-chat-footer">
            @csrf
            <input type="text" id="libraroAiInput" class="ai-input" placeholder="Type a custom question..." autocomplete="off">
            <button type="submit" class="ai-send-btn" id="libraroAiSend">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
    const AI_CATEGORIES = {
        overview: {
            title_en: "Overview & Revenue",
            title_hi: "विवरण व कलेक्शन",
            questions: {
                en: [
                    { text: "What is today's total revenue and collection?", query: "Aaj ka collection aur revenue kitna hai?" },
                    { text: "How many new admissions today?", query: "Aaj kitne naye admission hue?" },
                    { text: "How many plans will expire in the next 7 days?", query: "7 dino me kitne expire honge?" },
                    { text: "Show total library summary today", query: "Library summary" }
                ],
                hi: [
                    { text: "आज का कुल कलेक्शन और रेवेन्यू कितना है?", query: "आज का कुल कलेक्शन और रेवेन्यू कितना है?" },
                    { text: "आज कितने नए एडमिशन हुए हैं?", query: "आज कितने नए एडमिशन हुए हैं?" },
                    { text: "अगले 7 दिनों में कितने प्लान एक्सपायर होंगे?", query: "अगले 7 दिनों में कितने प्लान एक्सपायर होंगे?" },
                    { text: "लाइब्रेरी का आज का कुल सारांश (Summary) दिखाएं", query: "आज का लाइब्रेरी समरी" }
                ]
            }
        },
        dues: {
            title_en: "Dues & Student List",
            title_hi: "बकाया राशि व छात्र सूची",
            questions: {
                en: [
                    { text: "Show due student names and seat numbers", query: "Aaj ke due student ke name and seat no batao" },
                    { text: "What is the total pending dues amount?", query: "Total pending dues kitna hai?" },
                    { text: "How many students have pending dues?", query: "Bakaya student count" }
                ],
                hi: [
                    { text: "बकाया छात्र के नाम और सीट नंबर बताएं", query: "बकाया छात्र की सूची और नाम सीट नंबर" },
                    { text: "कुल बकाया राशि कितनी है?", query: "कुल बकाया राशि कितनी है?" },
                    { text: "कितने छात्रों की फीस बकाया है?", query: "बकाया छात्रों की संख्या" }
                ]
            }
        },
        seats: {
            title_en: "Seats & Occupancy",
            title_hi: "सीटें व ऑक्यूपेंसी",
            questions: {
                en: [
                    { text: "What is the library occupancy percentage?", query: "Library occupancy kitni hai?" },
                    { text: "How many total seats and floors are configured?", query: "Total seat aur floor kitne hain?" },
                    { text: "Show vacant vs occupied seats details", query: "Vacant seat kitni hai" }
                ],
                hi: [
                    { text: "लाइब्रेरी ऑक्यूपेंसी प्रतिशत (%) कितना है?", query: "ऑक्यूपेंसी प्रतिशत कितना है" },
                    { text: "कुल सीटें और मंज़िलें (Floors) कितनी हैं?", query: "कुल सीटें और मंजिलें कितनी हैं" },
                    { text: "खाली और भरी हुई सीटों का विवरण दिखाएं", query: "खाली सीटें कितनी हैं" }
                ]
            }
        },
        shifts: {
            title_en: "Shifts & Subscription",
            title_hi: "शिफ्ट व सब्सक्रिप्शन",
            questions: {
                en: [
                    { text: "Show library shift timing and plans", query: "Library shift timing aur plans" },
                    { text: "Show subscription plan details", query: "Subscription plan information" }
                ],
                hi: [
                    { text: "लाइब्रेरी शिफ्ट टाइमिंग और प्लान बताएं", query: "शिफ्ट टाइमिंग और प्लान" },
                    { text: "सब्सक्रिप्शन प्लान विवरण (Subscription Details) दिखाएं", query: "सब्सक्रिप्शन प्लान विवरण" }
                ]
            }
        },
        howto: {
            title_en: "How to Use Guide",
            title_hi: "सॉफ्टवेयर उपयोग निर्देशिका",
            questions: {
                en: [
                    { text: "How to add a new learner admission?", query: "Add learner kaise karein?" },
                    { text: "How to perform a seat swap?", query: "Seat Swap kaise karein?" },
                    { text: "How to generate ID Card?", query: "ID card kaise banayein?" },
                    { text: "How to send WhatsApp reminders?", query: "WhatsApp reminder kaise bhein?" },
                    { text: "How to freeze or unfreeze plan?", query: "Freeze Unfreeze kaise karein?" },
                    { text: "How to assign a locker?", query: "Locker assign kaise karein?" }
                ],
                hi: [
                    { text: "नया एडमिशन (Add Learner) कैसे करें?", query: "नया एडमिशन कैसे करें?" },
                    { text: "सीट कैसे बदलें (Seat Swap)?", query: "सीट कैसे बदलें?" },
                    { text: "ID कार्ड कैसे बनाएं (Generate ID Card)?", query: "ID कार्ड कैसे बनाएं?" },
                    { text: "व्हाट्सएप रिमाइंडर कैसे भेजें?", query: "व्हाट्सएप रिमाइंडर कैसे भेजें?" },
                    { text: "प्लाॉन फ्रीज़ / अनफ्रीज़ (Freeze Plan) कैसे करें?", query: "प्लाॉन फ्रीज कैसे करें?" },
                    { text: "लॉकर अलॉट (Assign Locker) कैसे करें?", query: "लॉकर कैसे अलॉट करें?" }
                ]
            }
        }
    };

    let wizardCategory = null;
    let wizardLanguage = null;

    function toggleLibraroAiBox() {
        const box = document.getElementById("libraroAiBox");
        if (!box) return;
        
        if (box.classList.contains("active")) {
            box.classList.remove("active");
            box.style.display = "none";
        } else {
            box.classList.add("active");
            box.style.display = "flex";
            const input = document.getElementById("libraroAiInput");
            if (input) input.focus();
            if (typeof loadChatHistory === "function") loadChatHistory();
        }
    }

    function toggleAiWizardBar() {
        const wizard = document.getElementById("aiWizardBar");
        const icon = document.getElementById("aiWizardToggleIcon");
        if (!wizard) return;

        if (wizard.classList.contains("collapsed")) {
            wizard.classList.remove("collapsed");
            if (icon) icon.className = "fa-solid fa-chevron-up";
        } else {
            wizard.classList.add("collapsed");
            if (icon) icon.className = "fa-solid fa-chevron-down";
        }
    }

    function resetWizardSteps() {
        wizardCategory = null;
        wizardLanguage = null;
        document.getElementById("aiCatSelect").value = "";
        document.getElementById("aiStepCategory").style.display = "block";
        document.getElementById("aiStepLanguage").style.display = "none";
        document.getElementById("aiStepQuestion").style.display = "none";
        document.getElementById("aiStepTitle").innerHTML = '<i class="fa-solid fa-circle-question me-1 text-primary"></i> If you want help, ask here! / अगर आप सहायता चाहते हैं, तो यहाँ पूछें';
        
        const wizard = document.getElementById("aiWizardBar");
        if (wizard && wizard.classList.contains("collapsed")) {
            toggleAiWizardBar();
        }
    }

    function onCategoryChange(catKey) {
        if (!AI_CATEGORIES[catKey]) return;
        wizardCategory = catKey;
        const catData = AI_CATEGORIES[catKey];
        document.getElementById("aiStepTitle").innerHTML = '<i class="fa-solid fa-folder-open me-1 text-primary"></i> ' + catData.title_en + ' / ' + catData.title_hi;
        
        document.getElementById("aiStepCategory").style.display = "none";
        document.getElementById("aiStepLanguage").style.display = "block";
        document.getElementById("aiStepQuestion").style.display = "none";
    }

    function onLanguageSelect(langCode) {
        if (!wizardCategory || !AI_CATEGORIES[wizardCategory]) return resetWizardSteps();
        wizardLanguage = langCode;
        const catData = AI_CATEGORIES[wizardCategory];
        const qList = catData.questions[langCode] || [];

        const select = document.getElementById("aiQuestionSelect");
        select.innerHTML = '<option value="" selected disabled>-- Select Question / प्रश्न चुनें --</option>';

        qList.forEach(function(item, index) {
            const opt = document.createElement("option");
            opt.value = index;
            opt.innerText = item.text;
            select.appendChild(opt);
        });

        const langText = langCode === 'hi' ? 'हिंदी (Hindi)' : 'English';
        document.getElementById("aiQuestionSelectLabel").innerHTML = '<i class="fa-solid fa-comments me-1"></i> 3. Choose Question (' + langText + '):';

        document.getElementById("aiStepCategory").style.display = "none";
        document.getElementById("aiStepLanguage").style.display = "none";
        document.getElementById("aiStepQuestion").style.display = "block";
    }

    function onQuestionSelect(qIndex) {
        if (!wizardCategory || !wizardLanguage || qIndex === "") return;
        const catData = AI_CATEGORIES[wizardCategory];
        const qList = catData.questions[wizardLanguage] || [];
        const selectedObj = qList[qIndex];

        if (selectedObj) {
            sendAiQuery(selectedObj.query, selectedObj.text);
            // Reset dropdown & auto-collapse wizard bar for clean reading
            document.getElementById("aiQuestionSelect").value = "";
            const wizard = document.getElementById("aiWizardBar");
            if (wizard && !wizard.classList.contains("collapsed")) {
                toggleAiWizardBar();
            }
        }
    }

    if (!window.libraroAiWidgetInitialized) {
        window.libraroAiWidgetInitialized = true;

        document.addEventListener("DOMContentLoaded", function () {
            const clearBtn = document.getElementById("libraroAiClear");
            const form = document.getElementById("libraroAiForm");
            const input = document.getElementById("libraroAiInput");
            const body = document.getElementById("libraroAiBody");

            // Clear Chat
            clearBtn.addEventListener("click", function () {
                if (confirm("Clear AI conversation history?")) {
                    fetch("{{ route('ai.chat.clear') }}", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Content-Type": "application/json"
                        }
                    }).then(() => {
                        body.innerHTML = `
                            <div class="ai-msg assistant">
                                <i class="fa-solid fa-hands-clapping text-warning me-1"></i>
                                <strong>If you want help, ask here! / अगर आप सहायता चाहते हैं, तो यहाँ पूछें।</strong><br><br>
                                Conversation reset ho gayi hai. Aap firse Dropdown se question chun sakte hain!
                            </div>`;
                        resetWizardSteps();
                    });
                }
            });

            // Global Send Function
            window.sendAiQuery = function(queryText, displayText) {
                if (!queryText) return;
                appendMessage("user", displayText || queryText);
                if (input) input.value = "";

                // Show Typing Indicator
                const typingElem = document.createElement("div");
                typingElem.className = "ai-typing";
                typingElem.id = "aiTypingElem";
                typingElem.innerHTML = "<span></span><span></span><span></span>";
                body.appendChild(typingElem);
                scrollToBottom();

                fetch("{{ route('ai.chat.send') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({ query: queryText })
                })
                .then(res => res.json())
                .then(data => {
                    const typing = document.getElementById("aiTypingElem");
                    if (typing) typing.remove();

                    if (data.success && data.message) {
                        appendMessage("assistant", data.message);
                        appendQuickPills();
                    } else {
                        appendMessage("assistant", "⚠️ Error: Request process nahi ho paya. Please firse try karein.");
                    }
                })
                .catch(err => {
                    const typing = document.getElementById("aiTypingElem");
                    if (typing) typing.remove();
                    appendMessage("assistant", "⚠️ Server error. Please check network connection.");
                });
            };

            // Form Submit Handler
            form.addEventListener("submit", function (e) {
                e.preventDefault();
                const text = input.value.trim();
                if (!text) return;
                sendAiQuery(text, text);
            });

            function appendMessage(sender, text) {
                const msg = document.createElement("div");
                msg.className = "ai-msg " + sender;
                msg.innerHTML = formatMarkdown(text);
                body.appendChild(msg);
                scrollToBottom();
            }

            function appendQuickPills() {
                const pillsDiv = document.createElement("div");
                pillsDiv.className = "ai-action-pills";
                pillsDiv.innerHTML = `
                    <button type="button" class="ai-action-pill-btn" onclick="resetWizardSteps()">
                        <i class="fa-solid fa-circle-plus me-1"></i> Ask Another Question / दूसरा प्रश्न
                    </button>
                    <button type="button" class="ai-action-pill-btn" onclick="toggleAiWizardBar()">
                        <i class="fa-solid fa-chevron-down me-1"></i> Toggle Dropdown / प्रश्न लिस्ट
                    </button>
                `;
                body.appendChild(pillsDiv);
                scrollToBottom();
            }

            function scrollToBottom() {
                body.scrollTop = body.scrollHeight;
            }

            function formatMarkdown(text) {
                if (!text) return "";
                if (text.includes('class="ai-op-card"')) {
                    return text;
                }
                let html = text
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/\n/g, '<br>');
                return html;
            }

            window.loadChatHistory = function() {
                fetch("{{ route('ai.chat.history') }}")
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.history && data.history.length > 0) {
                            body.innerHTML = "";
                            data.history.forEach(item => {
                                appendMessage(item.sender, item.message);
                            });
                        }
                    })
                    .catch(() => {});
            };
        });
    }
</script>
