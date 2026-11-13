<?php

namespace App\Services\Ai;

/**
 * Built-in, bilingual answers for Libraro AI that do not come from the database:
 * running a library business (admissions, fee collection, pricing, rules …) and
 * panel features that are not in the `how-to-use` / `guidelines` tables.
 *
 * Each entry: keys (phrases that identify the question, any language),
 * en / hi => [title, steps[]], optional links => [[route, en label, hi label]].
 */
class LibraroKnowledgeBase
{
    public static function entries(): array
    {
        return [
            /* ------------------------------ Business ------------------------------ */
            [
                'keys' => ['increase admission', 'increase admissions', 'more students', 'more admissions', 'get students', 'attract students', 'marketing', 'promotion', 'promote', 'advertise', 'advertisement', 'grow library', 'growth tips', 'admission badhaye', 'admission kaise badhaye', 'students kaise laye', 'students kaise badhaye', 'naye students kaise', 'prachar', 'एडमिशन कैसे बढ़ाएं', 'एडमिशन बढ़ाएं', 'छात्र कैसे बढ़ाएं', 'छात्र कैसे लाएं', 'प्रचार', 'मार्केटिंग', 'विज्ञापन'],
                'en' => ['How to get more admissions', [
                    'Create a Google Business Profile with real photos, timings and fees, and ask happy learners for reviews — most students search “library near me”.',
                    'Turn on your Libraro website listing and online seat booking so students can book from the QR code or link any time.',
                    'Run a referral offer: a learner who brings a friend gets a few free days (use Gift Days).',
                    'Put posters and pamphlets near coaching institutes, hostels, PGs, book shops and colleges.',
                    'Offer a 1-day free trial or a low-price day pass — once students sit in a quiet, clean place, most of them join.',
                    'Launch offers before exam seasons and at the start of every month.',
                    'Reply to every enquiry the same day and follow up after 2–3 days.',
                ]],
                'hi' => ['ज़्यादा एडमिशन कैसे लाएं', [
                    'Google Business Profile बनाएं — असली फोटो, समय और फीस डालें, और खुश छात्रों से रिव्यू लिखवाएं। ज़्यादातर छात्र “library near me” सर्च करते हैं।',
                    'Libraro की वेबसाइट लिस्टिंग और ऑनलाइन सीट बुकिंग चालू करें, ताकि छात्र QR कोड या लिंक से कभी भी बुक कर सकें।',
                    'रेफरल ऑफर चलाएं: जो छात्र दोस्त को लाए, उसे कुछ दिन फ्री दें (Gift Days से)।',
                    'कोचिंग संस्थान, हॉस्टल, PG, किताबों की दुकान और कॉलेज के पास पोस्टर व पर्चे लगवाएं।',
                    '1 दिन का फ्री ट्रायल या कम कीमत का डे-पास दें — शांत और साफ जगह देखकर ज़्यादातर छात्र जुड़ जाते हैं।',
                    'परीक्षा सीज़न से पहले और हर महीने की शुरुआत में ऑफर निकालें।',
                    'हर पूछताछ (Enquiry) का जवाब उसी दिन दें और 2–3 दिन बाद दोबारा फोन करें।',
                ]],
                'links' => [['library.enquiry', 'Open enquiries', 'पूछताछ देखें']],
            ],
            [
                'keys' => ['fill vacant seats', 'fill empty seats', 'fill seats', 'empty seats fill', 'low occupancy', 'occupancy kam', 'occupancy badhaye', 'seats khali rehti', 'khali seat kaise bhare', 'increase occupancy', 'खाली सीटें कैसे भरें', 'ऑक्यूपेंसी कैसे बढ़ाएं', 'सीटें खाली रहती'],
                'en' => ['How to fill empty seats', [
                    'Check which shift is empty (ask me “shift wise learners”) and offer that shift at a lower price.',
                    'Start short shifts (4–6 hours) for school/college students and working people.',
                    'Give a small discount on 3-month and 6-month plans so seats stay booked longer.',
                    'Call learners whose plan expired in the last 30 days and invite them back with a renewal offer.',
                    'Run a referral offer and share your online booking link on WhatsApp groups of local coaching batches.',
                ]],
                'hi' => ['खाली सीटें कैसे भरें', [
                    'देखें कौन सी शिफ्ट खाली है (मुझसे पूछें “शिफ्ट वाइज छात्र”) और उस शिफ्ट की कीमत थोड़ी कम रखें।',
                    'स्कूल/कॉलेज छात्रों और नौकरी करने वालों के लिए 4–6 घंटे की छोटी शिफ्ट शुरू करें।',
                    '3 और 6 महीने के प्लान पर थोड़ी छूट दें, ताकि सीटें लंबे समय तक बुक रहें।',
                    'पिछले 30 दिन में जिनका प्लान खत्म हुआ, उन्हें फोन करके रिन्यूअल ऑफर दें।',
                    'रेफरल ऑफर चलाएं और अपना ऑनलाइन बुकिंग लिंक कोचिंग बैच के WhatsApp ग्रुप में शेयर करें।',
                ]],
            ],
            [
                'keys' => ['reduce dues', 'recover dues', 'collect dues', 'collect fees on time', 'fees late', 'fee late', 'students not paying', 'fees nahi dete', 'paise nahi dete', 'dues kaise kam', 'bakaya kaise vasoole', 'fees time par', 'payment late', 'बकाया कैसे वसूलें', 'बकाया कैसे कम करें', 'फीस समय पर', 'पैसे नहीं देते', 'फीस नहीं देते'],
                'en' => ['How to collect fees on time', [
                    'Take the fee in advance at booking and renewal — do not start a plan on “pay later” unless needed.',
                    'Send reminders from 5 days before expiry (WhatsApp / text) — Libraro shows the Send Reminder option in that window.',
                    'Open the Pending Payment report every week and call the top dues first.',
                    'For partial payments, always set a due date so the learner shows as overdue on time.',
                    'Keep a UPI QR at the desk so paying takes 10 seconds.',
                    'Write a clear rule (e.g. seat released after the extension days) and follow it for everyone.',
                    'For genuine cases, use Non-Expire Seat and collect the pending amount later.',
                ]],
                'hi' => ['फीस समय पर कैसे लें', [
                    'बुकिंग और रिन्यूअल पर फीस पहले लें — ज़रूरत न हो तो “Pay Later” पर प्लान शुरू न करें।',
                    'प्लान खत्म होने से 5 दिन पहले से रिमाइंडर भेजें (WhatsApp / SMS) — Libraro में इसी समय Send Reminder दिखता है।',
                    'हर हफ्ते Pending Payment रिपोर्ट खोलें और सबसे ज़्यादा बकाया वालों को पहले फोन करें।',
                    'आंशिक भुगतान (partial payment) में हमेशा देय तिथि (due date) डालें, ताकि समय पर बकाया दिखे।',
                    'काउंटर पर UPI QR रखें, ताकि भुगतान 10 सेकंड में हो जाए।',
                    'साफ नियम लिखें (जैसे extension के दिन के बाद सीट खाली होगी) और सब पर एक जैसा लागू करें।',
                    'सच में मजबूर छात्र के लिए Non-Expire Seat का उपयोग करें और बकाया बाद में लें।',
                ]],
                'links' => [['pending.payment.report', 'Pending payment report', 'बकाया रिपोर्ट']],
            ],
            [
                'keys' => ['how much fees', 'what fees should', 'fees kitni rakhe', 'fee kitni rakhe', 'price kitna rakhe', 'pricing', 'decide price', 'decide fees', 'set price', 'rate decide', 'pricing strategy', 'फीस कितनी रखें', 'कीमत तय', 'फीस तय'],
                'en' => ['How to decide your fees', [
                    'Check the rates of 3–4 libraries near you and their facilities (AC, WiFi, lockers).',
                    'Price by hours: full day the highest, half-day shifts about 55–65% of it.',
                    'Give 5–10% off on 3-month and 6-month plans — it locks the seat and cuts reminders.',
                    'Charge locker, registration and security deposit separately (use Misc Payment).',
                    'Review prices every 6 months; raise slightly in exam season when demand is high.',
                    'Set the final prices in Libraro → Plan Price master.',
                ]],
                'hi' => ['फीस कैसे तय करें', [
                    'आसपास की 3–4 लाइब्रेरी की फीस और सुविधाएं (AC, WiFi, लॉकर) देखें।',
                    'घंटों के हिसाब से कीमत रखें: फुल डे सबसे ज़्यादा, हाफ डे शिफ्ट उसका लगभग 55–65%।',
                    '3 और 6 महीने के प्लान पर 5–10% छूट दें — सीट लंबे समय तक बुक रहती है और रिमाइंडर कम भेजने पड़ते हैं।',
                    'लॉकर, रजिस्ट्रेशन और सिक्योरिटी डिपॉज़िट अलग से लें (Misc Payment से)।',
                    'हर 6 महीने में कीमत देखें; परीक्षा सीज़न में मांग ज़्यादा हो तो थोड़ी बढ़ाएं।',
                    'तय कीमत Libraro → Plan Price मास्टर में डालें।',
                ]],
            ],
            [
                'keys' => ['retain students', 'keep learners', 'keep learners renewing', 'learners renewing', 'students leaving', 'students chod rahe', 'chhod rahe', 'renewal badhaye', 'renewal kaise badhaye', 'student retention', 'keep students', 'छात्र छोड़ रहे', 'रिन्यूअल कैसे बढ़ाएं', 'छात्र रुकें'],
                'en' => ['How to keep learners renewing', [
                    'Remind every learner 5 days before expiry and offer a small benefit for renewing on time.',
                    'Keep basics perfect: silence, clean washrooms, drinking water, working WiFi, good light, cooling.',
                    'Ask for feedback (learners can give it from their portal) and fix the top complaint each month.',
                    'Wish learners on birthdays — your dashboard shows them every day.',
                    'Let regular learners keep their favourite seat on renewal.',
                    'Call expired learners within a week; many just forget to renew.',
                ]],
                'hi' => ['छात्र रिन्यू करते रहें, इसके लिए', [
                    'प्लान खत्म होने से 5 दिन पहले हर छात्र को याद दिलाएं और समय पर रिन्यू करने पर छोटा फायदा दें।',
                    'बुनियादी चीज़ें बढ़िया रखें: शांति, साफ वॉशरूम, पीने का पानी, चलता WiFi, अच्छी रोशनी, ठंडक।',
                    'फीडबैक लें (छात्र अपने पोर्टल से दे सकते हैं) और हर महीने सबसे बड़ी शिकायत ठीक करें।',
                    'जन्मदिन पर बधाई दें — डैशबोर्ड पर रोज़ जन्मदिन दिखते हैं।',
                    'पुराने छात्रों को रिन्यूअल पर उनकी पसंदीदा सीट रखने दें।',
                    'एक्सपायर हुए छात्रों को हफ्ते भर में फोन करें; कई बस रिन्यू करना भूल जाते हैं।',
                ]],
                'links' => [['library.learner.feedback', 'Learner feedback', 'छात्र फीडबैक']],
            ],
            [
                'keys' => ['library rules', 'rules', 'rule list', 'niyam', 'discipline', 'code of conduct', 'नियम', 'अनुशासन', 'लाइब्रेरी के नियम'],
                'en' => ['Sample library rules', [
                    'Keep complete silence; take calls and discussions outside.',
                    'Phones on silent; no music without earphones.',
                    'Sit only on your own seat and in your own shift.',
                    'No food at the desk; drinks only in closed bottles.',
                    'Carry your ID card and mark attendance (QR) on entry and exit.',
                    'Keep valuables in your locker; the library is not responsible for lost items.',
                    'Pay the fee before the due date; the seat is released after the extension days.',
                    'Any damage to furniture or equipment will be charged.',
                    'Guests and friends are not allowed inside the study area.',
                ]],
                'hi' => ['लाइब्रेरी के नियम (नमूना)', [
                    'पूरी शांति रखें; फोन और बातचीत बाहर जाकर करें।',
                    'फोन साइलेंट पर रखें; बिना ईयरफोन गाना न चलाएं।',
                    'केवल अपनी सीट पर और अपनी शिफ्ट में ही बैठें।',
                    'डेस्क पर खाना न खाएं; पानी केवल बंद बोतल में।',
                    'ID कार्ड साथ रखें और आते-जाते QR से हाजिरी लगाएं।',
                    'कीमती सामान लॉकर में रखें; खोए सामान की ज़िम्मेदारी लाइब्रेरी की नहीं है।',
                    'देय तिथि से पहले फीस जमा करें; extension के दिन के बाद सीट खाली कर दी जाएगी।',
                    'फर्नीचर या सामान के नुकसान का पैसा लिया जाएगा।',
                    'मेहमान और दोस्तों को पढ़ाई वाले हिस्से में आने की अनुमति नहीं है।',
                ]],
            ],
            [
                'keys' => ['facilities', 'facilities students want', 'what facilities', 'which facilities', 'facility', 'amenities', 'suvidha', 'suvidhaye', 'what to provide', 'kya kya hona chahiye', 'सुविधा', 'सुविधाएं', 'सुविधाएँ'],
                'en' => ['Facilities students look for', [
                    'Comfortable chair and a partitioned desk with its own light.',
                    'Charging point at every seat and power backup (inverter).',
                    'Fast, reliable WiFi.',
                    'AC or good cooling, and proper ventilation.',
                    'RO drinking water and clean washrooms.',
                    'Lockers, CCTV and a separate break / discussion area.',
                    'Newspapers and current-affairs magazines for competitive exams.',
                ]],
                'hi' => ['छात्र किन सुविधाओं को देखते हैं', [
                    'आरामदायक कुर्सी और पार्टीशन वाली डेस्क, अपनी अलग लाइट के साथ।',
                    'हर सीट पर चार्जिंग पॉइंट और पावर बैकअप (इन्वर्टर)।',
                    'तेज़ और भरोसेमंद WiFi।',
                    'AC या अच्छी ठंडक, और हवा का सही इंतज़ाम।',
                    'RO का पीने का पानी और साफ वॉशरूम।',
                    'लॉकर, CCTV और अलग ब्रेक / बातचीत की जगह।',
                    'प्रतियोगी परीक्षा के लिए अखबार और करंट अफेयर्स पत्रिकाएं।',
                ]],
            ],
            [
                'keys' => ['start library', 'open library', 'new library', 'start a library', 'library business', 'library kaise khole', 'library kholna', 'nayi library', 'investment', 'setup cost', 'नई लाइब्रेरी', 'लाइब्रेरी कैसे खोलें', 'लाइब्रेरी खोलनी', 'लागत'],
                'en' => ['Starting a new library — checklist', [
                    'Location: near coaching institutes, colleges or hostels, with easy access and parking.',
                    'Space: plan roughly 25–30 sq ft per seat including walkways.',
                    'Set up furniture, lighting, cooling, WiFi, CCTV, power backup, water and washrooms.',
                    'Complete local registrations (e.g. shop & establishment / trade licence — rules differ by city, check locally).',
                    'Decide shifts and prices, then set up Libraro in this order: Branch → Floors → Plans → Shifts (Plan Types) → Plan Prices.',
                    'Bring in existing learners with Import Learner, print the attendance QR, and start online booking.',
                    'Launch with an opening offer and a referral scheme.',
                ]],
                'hi' => ['नई लाइब्रेरी शुरू करने की चेकलिस्ट', [
                    'जगह: कोचिंग, कॉलेज या हॉस्टल के पास, आसान रास्ता और पार्किंग हो।',
                    'स्पेस: रास्ते मिलाकर लगभग 25–30 वर्ग फीट प्रति सीट रखें।',
                    'फर्नीचर, लाइट, ठंडक, WiFi, CCTV, पावर बैकअप, पानी और वॉशरूम लगवाएं।',
                    'स्थानीय रजिस्ट्रेशन पूरे करें (जैसे shop & establishment / ट्रेड लाइसेंस — नियम शहर के हिसाब से अलग हैं, स्थानीय स्तर पर पता करें)।',
                    'शिफ्ट और कीमत तय करें, फिर Libraro इस क्रम में सेट करें: Branch → Floors → Plans → Shifts (Plan Types) → Plan Prices।',
                    'पुराने छात्रों को Import Learner से जोड़ें, हाजिरी का QR प्रिंट करें और ऑनलाइन बुकिंग शुरू करें।',
                    'ओपनिंग ऑफर और रेफरल स्कीम के साथ शुरुआत करें।',
                ]],
            ],
            [
                'keys' => ['exam season', 'peak season', 'rush', 'high demand', 'waiting list', 'seats full', 'seat full', 'सीज़न', 'भीड़', 'सीटें फुल', 'वेटिंग'],
                'en' => ['Handling exam-season rush', [
                    'Use Future Booking so learners can reserve seats for dates ahead.',
                    'Keep a waiting list from enquiries and call them as soon as a seat frees up.',
                    'Open extra or longer shifts if your timings allow.',
                    'Prefer 2–3 month plans in peak months to reduce churn.',
                ]],
                'hi' => ['परीक्षा सीज़न की भीड़ कैसे संभालें', [
                    'Future Booking का उपयोग करें, ताकि छात्र आगे की तारीख की सीट पहले से बुक कर सकें।',
                    'पूछताछ से वेटिंग लिस्ट बनाएं और सीट खाली होते ही उन्हें फोन करें।',
                    'समय अनुमति दे तो अतिरिक्त या लंबी शिफ्ट खोलें।',
                    'भीड़ वाले महीनों में 2–3 महीने के प्लान को बढ़ावा दें।',
                ]],
            ],
            [
                'keys' => ['security', 'safety', 'cctv', 'theft', 'chori', 'saman chori', 'सुरक्षा', 'चोरी', 'सीसीटीवी'],
                'en' => ['Safety & security tips', [
                    'Cover the entrance, aisles and locker area with CCTV and keep 30 days of recording.',
                    'Ask learners to keep valuables in lockers; put up a “not responsible for lost items” notice.',
                    'Keep a fire extinguisher, first-aid box and a clear emergency exit.',
                    'Collect ID proof at admission — Libraro stores it on the learner profile.',
                    'Let staff log in with their own accounts so every change is tracked.',
                ]],
                'hi' => ['सुरक्षा के सुझाव', [
                    'प्रवेश, रास्तों और लॉकर वाली जगह पर CCTV लगाएं और 30 दिन की रिकॉर्डिंग रखें।',
                    'छात्रों से कीमती सामान लॉकर में रखने को कहें; “खोए सामान की ज़िम्मेदारी नहीं” का नोटिस लगाएं।',
                    'अग्निशामक यंत्र, फर्स्ट-एड बॉक्स और साफ इमरजेंसी रास्ता रखें।',
                    'एडमिशन के समय ID प्रूफ लें — Libraro इसे छात्र की प्रोफाइल में रखता है।',
                    'स्टाफ को अपने-अपने अकाउंट से लॉगिन करवाएं, ताकि हर बदलाव का रिकॉर्ड रहे।',
                ]],
            ],
            [
                'keys' => ['manage staff', 'staff kaise', 'staff permission', 'employee', 'staff account', 'staff login', 'कर्मचारी', 'स्टाफ कैसे', 'स्टाफ अकाउंट'],
                'en' => ['Managing staff in Libraro', [
                    'Create a separate login for each staff member in Users (Staff) master.',
                    'Give only the permissions they need (e.g. booking and attendance, not reports or deletes).',
                    'Match the cash in hand with the day’s collection (ask me “today’s collection”).',
                    'Use the Activity report to see who changed what.',
                ]],
                'hi' => ['Libraro में स्टाफ कैसे संभालें', [
                    'Users (Staff) मास्टर में हर स्टाफ का अलग लॉगिन बनाएं।',
                    'उतनी ही अनुमति (permission) दें जितनी ज़रूरी हो (जैसे बुकिंग और हाजिरी, रिपोर्ट या डिलीट नहीं)।',
                    'दिन के अंत में हाथ के कैश को आज के कलेक्शन से मिलाएं (मुझसे पूछें “आज का कलेक्शन”)।',
                    'किसने क्या बदला, यह Activity रिपोर्ट में देखें।',
                ]],
                'links' => [['library-users.index', 'Staff list', 'स्टाफ सूची'], ['activity.report', 'Activity report', 'एक्टिविटी रिपोर्ट']],
            ],

            /* ------------------------------ Panel features ------------------------------ */
            [
                'keys' => ['switch branch', 'ब्रांच कैसे बदलें', 'change branch', 'other branch', 'dusri branch', 'branch badle', 'branch change', 'ब्रांच बदल', 'दूसरी ब्रांच'],
                'en' => ['Switching branch', [
                    'Use the branch selector at the top of the panel and pick the branch.',
                    'Everything — dashboard, reports and these AI answers — then shows data of the selected branch.',
                ]],
                'hi' => ['ब्रांच कैसे बदलें', [
                    'पैनल के ऊपर दिए गए ब्रांच सिलेक्टर से ब्रांच चुनें।',
                    'इसके बाद डैशबोर्ड, रिपोर्ट और ये AI जवाब — सब उसी ब्रांच का डेटा दिखाएंगे।',
                ]],
            ],
            [
                'keys' => ['export', 'export data', 'export learners', 'learners data', 'student data', 'download data', 'download learners', 'excel download', 'csv download', 'learner list download', 'data backup', 'backup', 'डेटा डाउनलोड', 'एक्सपोर्ट', 'बैकअप'],
                'en' => ['Downloading your learner data', [
                    'Open the learner list and use Export CSV — it downloads every learner of the branch.',
                    'For money, open Reports → Monthly payment and export it.',
                ]],
                'hi' => ['छात्रों का डेटा डाउनलोड करें', [
                    'छात्र सूची खोलें और Export CSV से ब्रांच के सभी छात्रों की फाइल डाउनलोड करें।',
                    'पैसों के लिए Reports → Monthly payment खोलकर एक्सपोर्ट करें।',
                ]],
                'links' => [['learners.export-csv', 'Export learners (CSV)', 'छात्र एक्सपोर्ट (CSV)'], ['reports.hub', 'Reports', 'रिपोर्ट']],
            ],
            [
                'keys' => ['qr attendance', 'attendance qr', 'qr code attendance', 'scan attendance', 'qr poster', 'attendance poster', 'qr kaise lagaye', 'हाजिरी क्यूआर', 'क्यूआर कोड'],
                'en' => ['QR attendance', [
                    'Open Attendance → QR code and print the poster.',
                    'Put it at the entrance; learners scan it on their phone when they come in and when they leave.',
                    'You see who is inside live on the dashboard, and full history in the Attendance report.',
                ]],
                'hi' => ['QR से हाजिरी', [
                    'Attendance → QR code खोलें और पोस्टर प्रिंट करें।',
                    'इसे गेट पर लगाएं; छात्र आते और जाते समय फोन से स्कैन करेंगे।',
                    'कौन अंदर है यह डैशबोर्ड पर लाइव दिखता है, और पूरा रिकॉर्ड Attendance रिपोर्ट में।',
                ]],
                'links' => [['attendance.qrcode', 'Attendance QR', 'हाजिरी QR'], ['attendance.qrcode.poster', 'QR poster', 'QR पोस्टर']],
            ],
            [
                'keys' => ['password', 'forgot password', 'change password', 'reset password', 'login problem', 'पासवर्ड', 'लॉगिन नहीं'],
                'en' => ['Password help', [
                    'Forgot it? On the login page click “Forgot password” and follow the email link.',
                    'Staff passwords are changed by the owner from Users (Staff) master.',
                    'Your own details can be updated from Profile.',
                ]],
                'hi' => ['पासवर्ड से जुड़ी मदद', [
                    'भूल गए? लॉगिन पेज पर “Forgot password” पर क्लिक करें और ईमेल लिंक से नया पासवर्ड बनाएं।',
                    'स्टाफ का पासवर्ड मालिक Users (Staff) मास्टर से बदलता है।',
                    'अपनी जानकारी Profile से बदल सकते हैं।',
                ]],
                'links' => [['profile', 'Profile', 'प्रोफाइल']],
            ],
            [
                'keys' => ['dark mode', 'night mode', 'theme', 'black theme', 'डार्क मोड', 'थीम'],
                'en' => ['Dark mode', ['Click the sun / moon button in the top bar. Your choice is remembered on this device.']],
                'hi' => ['डार्क मोड', ['ऊपर की पट्टी में सूरज / चांद वाले बटन पर क्लिक करें। आपकी पसंद इस डिवाइस पर याद रहती है।']],
            ],
            [
                'keys' => ['referral', 'refer', 'refer and earn', 'reward points', 'redeem points', 'रेफरल', 'रिवॉर्ड'],
                'en' => ['Libraro referral rewards', [
                    'Open the Referral dashboard and share your referral code with other library owners.',
                    'You earn reward points when they join, and can redeem them from the same page.',
                ]],
                'hi' => ['Libraro रेफरल रिवॉर्ड', [
                    'Referral डैशबोर्ड खोलें और अपना रेफरल कोड दूसरे लाइब्रेरी मालिकों को भेजें।',
                    'उनके जुड़ने पर आपको रिवॉर्ड पॉइंट मिलते हैं, जिन्हें उसी पेज से रिडीम कर सकते हैं।',
                ]],
                'links' => [['referral.dashboard', 'Referral dashboard', 'रेफरल डैशबोर्ड']],
            ],
            [
                'keys' => ['learner login', 'student login', 'student app', 'learner portal', 'student portal', 'student ka login', 'छात्र लॉगिन', 'स्टूडेंट लॉगिन'],
                'en' => ['Learner login', [
                    'Each learner gets their own login to the learner portal.',
                    'There they can see their plan and profile, mark attendance by QR and send you feedback.',
                ]],
                'hi' => ['छात्र का लॉगिन', [
                    'हर छात्र को लर्नर पोर्टल का अपना लॉगिन मिलता है।',
                    'वहां वे अपना प्लान और प्रोफाइल देख सकते हैं, QR से हाजिरी लगा सकते हैं और आपको फीडबैक भेज सकते हैं।',
                ]],
            ],
            [
                'keys' => ['contact libraro', 'libraro support', 'support', 'customer care', 'helpline', 'complaint', 'problem report', 'bug', 'सपोर्ट', 'शिकायत', 'संपर्क'],
                'en' => ['Getting help from the Libraro team', [
                    'Send your problem or idea from the Feedback page in the panel — it reaches the Libraro team.',
                    'Watch the video tutorials for step-by-step help on every feature.',
                ]],
                'hi' => ['Libraro टीम से मदद', [
                    'पैनल के Feedback पेज से अपनी समस्या या सुझाव भेजें — यह सीधे Libraro टीम तक पहुंचता है।',
                    'हर फ़ीचर की स्टेप-बाय-स्टेप मदद के लिए वीडियो ट्यूटोरियल देखें।',
                ]],
                'links' => [['library.feedback', 'Send feedback', 'फीडबैक भेजें'], ['library.video-training', 'Video tutorials', 'वीडियो ट्यूटोरियल']],
            ],
            [
                'keys' => ['take upi', 'upi payment', 'online payment kaise', 'upi kaise', 'payment qr', 'qr payment', 'यूपीआई', 'ऑनलाइन पेमेंट कैसे'],
                'en' => ['Taking payments by UPI', [
                    'Add your UPI ID in the branch settings — it is used on the online booking and payment QR pages.',
                    'When a learner books online, the booking waits for your approval with the payment details.',
                    'At the desk, choose the matching payment mode while booking so reports split cash and UPI correctly.',
                ]],
                'hi' => ['UPI से भुगतान कैसे लें', [
                    'ब्रांच सेटिंग में अपनी UPI ID डालें — यह ऑनलाइन बुकिंग और पेमेंट QR पेज पर दिखती है।',
                    'छात्र ऑनलाइन बुक करे तो बुकिंग भुगतान की जानकारी के साथ आपकी मंज़ूरी के लिए रुकती है।',
                    'काउंटर पर बुकिंग करते समय सही पेमेंट मोड चुनें, ताकि रिपोर्ट में कैश और UPI अलग दिखें।',
                ]],
            ],
        ];
    }
}
