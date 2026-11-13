<?php
/*
 * Home page copy in three languages: en (default), hi (Hindi), hinglish.
 * Loaded by resources/views/site/home.blade.php. Every language has the same keys.
 * Icons are names from public/img/lb-icons.svg.
 */

$painIcons    = ['indian-rupee', 'armchair', 'calendar-x', 'qr-code', 'building', 'users-round', 'notebook-pen', 'trending-down'];
$fixIcons     = ['bell-ring', 'layout-grid', 'clock-alert', 'scan-line', 'arrow-left-right', 'user-cog', 'database', 'map-pin'];
$featureIcons = ['building', 'armchair', 'user-plus', 'indian-rupee', 'bell-ring', 'qr-code', 'user-cog', 'chart-line', 'id-card', 'shield-check'];
$benefitIcons = ['clock-3', 'wallet', 'scale', 'layout-dashboard', 'trending-up', 'thumbs-up'];

return [

/* ======================================================================
 * ENGLISH (default)
 * ==================================================================== */
'en' => [
    'meta' => [
        'title' => 'Libraro: Multi-Branch Library Management Software for India',
        'description' => 'Run every study library you own from one dashboard. Seats, fees, QR attendance, WhatsApp reminders, staff and branch reports. Free setup and a 7-day full refund.',
        'og_title' => 'Libraro: run every library you own from one dashboard',
    ],
    'lang_label' => 'Language',
    'nav' => ['home' => 'Home', 'features' => 'Features', 'pricing' => 'Pricing', 'blog' => 'Blog', 'contact' => 'Contact', 'find' => 'Find my library', 'login' => 'Log in', 'add' => 'Add your library', 'call' => 'Call us', 'learner' => 'Learner login'],

    'hero' => [
        'eyebrow' => 'For owners who run 2, 5 or 20 libraries',
        'title' => 'Every library you own.<br>One calm dashboard.',
        'lead' => 'Libraro is the management system serious study-library owners use to run seats, fees, staff and attendance across every branch, from a laptop or a phone.',
        'cta' => 'Add your library',
        'call' => 'Book a call',
        'refund' => '7-day full refund',
        'refund_sub' => 'No questions asked',
        'trust' => ['Setup done with you', 'Learner data encrypted', 'Works on any phone'],
        'stats' => [['3+', 'branches, one login'], ['30 sec', 'to admit a learner'], ['24×7', 'live numbers']],
        'marquee' => ['Multi-branch control', 'Live seat maps', 'WhatsApp reminders', 'QR attendance', 'Staff permissions', 'Encrypted learner data', 'Instant receipts', '7-day full refund'],
        'screen_alt' => 'Libraro dashboard on a laptop showing seats, collections and dues',
        'phone_alt' => 'Libraro on a mobile phone',
        'float1' => ['Fee received', '₹1,200 · UPI'],
        'float2' => ['Seats filled', '84% today'],
        'float3' => 'Fee reminder sent',
    ],

    'pains' => [
        'eyebrow' => 'The honest truth',
        'title' => 'Running a library is not easy.',
        'lead' => 'The study hall is quiet. The work behind it never stops, and it multiplies with every branch you open.',
        'prev' => 'Previous problem', 'next' => 'Next problem',
        'label' => 'Problem',
        'items' => [
            ['Fees slip through the cracks', 'Dues pile up quietly. You find out at month-end, after the learner has already left.'],
            ['Two learners, one seat', 'Registers can\'t show live shifts, so double bookings and morning arguments happen.'],
            ['Expired plans, still attending', 'Memberships end but learners keep coming. Nobody notices for weeks.'],
            ['Attendance on trust', 'Paper sign-in sheets get skipped, faked or lost. You never really know who came.'],
            ['Branches you can\'t see', 'You can be in one library at a time. The rest run on phone calls and hope.'],
            ['Staff and cash, unclear', 'Money is collected at the desk. How much, by whom, and was it all deposited?'],
            ['Registers, Excel and chaos', 'Five registers, three sheets and a WhatsApp group. None of them agree.'],
            ['Empty seats, no enquiries', 'Learners nearby can\'t find you online, so seats stay empty for months.'],
        ],
    ],

    'why' => [
        'eyebrow' => 'Why you need a management system',
        'title' => 'Registers worked for one room. Not for a library business.',
        'lead' => 'The moment you run more than one library, manual methods stop scaling. Every new branch multiplies the mess.',
        'cards' => [
            ['notebook-pen', 'Paper registers', 'No search, no backup, no reminders. One spilled cup of chai and a month of records is gone.'],
            ['file-spreadsheet', 'Excel sheets', 'Lives on one laptop. Breaks when two people edit it. Cannot send a receipt or a reminder.'],
            ['message-square-text', 'WhatsApp groups', 'Messages get buried. No record of who paid, who was told, or what was promised.'],
        ],
        'bad' => 'Breaks at scale',
        'quote' => 'Owners who grow run their libraries like businesses: one system, live numbers, clear accountability.',
    ],

    'solve' => [
        'eyebrow' => 'How Libraro solves it',
        'title' => 'Every problem, answered.',
        'lead' => 'Each pain on that list has a clear, built-in fix inside Libraro.',
        'before' => 'Before', 'after' => 'With Libraro',
        'items' => [
            ['Automatic fee reminders', 'WhatsApp and SMS reminders go out before the due date. Receipts are sent the moment you collect.'],
            ['Live seat map for every shift', 'See every seat in every shift and floor. A double booking simply can\'t happen.'],
            ['Expiry alerts every morning', 'Expiring and expired learners are listed for you, with one-tap renew.'],
            ['QR check-in at the door', 'Learners scan once. Attendance is marked and saved on its own.'],
            ['Switch branches in one click', 'Every branch\'s seats, money and staff, from a single login.'],
            ['Staff logins with permissions', 'Each staff member gets their own access. Every rupee is recorded with a name.'],
            ['One source of truth', 'Learners, seats, fees and expenses in one secure place, always backed up.'],
            ['Free listing and QR booking', 'Learners nearby find you on Find my library and book a seat themselves.'],
        ],
    ],

    'show' => [
        'eyebrow' => 'The real product',
        'title' => 'See the portal your team will use every day.',
        'lead' => 'Real screens from Libraro. Click through the modules.',
        'tabs' => [
            'dashboard' => ['Dashboard', 'Seats, collections and dues across your branches, at a glance.'],
            'learners' => ['Learner list', 'Search, filter and act on every learner: renew, swap, upgrade or settle.'],
            'attendance' => ['Attendance', 'Who is coming, who has stopped, and who to call today.'],
            'fees' => ['Fees & payments', 'Every payment, receipt and pending due, with full history.'],
            'reports' => ['Reports', 'Monthly collection, expenses and profit in one tap.'],
        ],
    ],

    'mobile' => [
        'eyebrow' => 'Desktop and mobile',
        'title' => 'Use Libraro easily on your mobile or desktop.',
        'lead' => 'Check collections from home, admit a learner at the desk, approve a seat swap on the go. Same data on every device.',
        'points' => [['smartphone', 'No app to install'], ['zap', 'Fast even on 4G'], ['monitor-smartphone', 'One login, every device']],
        'alts' => ['Libraro dashboard on mobile', 'Libraro learner list on mobile', 'Libraro seat map on mobile'],
    ],

    'ops' => [
        'eyebrow' => 'Daily operations',
        'title' => 'Every daily task, a few taps away.',
        'lead' => 'Real operation screens from the Libraro portal, in light and dark mode.',
        'light' => 'Light', 'dark' => 'Dark',
        'tabs' => [
            'profile' => ['Member profile', 'Seat, shifts, plan, dues and contact for every learner on one screen.'],
            'swap' => ['Seat swap', 'Move a learner to another seat in seconds, with the fee worked out for you.'],
            'plan' => ['Change plan', 'Upgrade or change a plan with a live bill summary. No calculator needed.'],
            'attend' => ['Learner attendance', 'A calendar of every visit, study hours and attendance rate.'],
            'wallet' => ['Payments & wallet', 'Every rupee a learner has paid, what is pending, and the next due date.'],
        ],
    ],

    'day' => [
        'eyebrow' => 'How it works',
        'title' => 'A day at your library.',
        'step' => 'Step', 'prev' => 'Previous', 'next' => 'Next', 'track' => 'Daily workflow steps',
        'items' => [
            ['Admit', 'Add a learner in 30 seconds with photo and ID proof.', '8:00 AM'],
            ['Allot a seat', 'Pick a free seat from the live seat map, for any shift.', '8:05 AM'],
            ['Collect fees', 'Cash, UPI or online. The receipt goes out on WhatsApp.', '10:30 AM'],
            ['Mark attendance', 'Learners scan a QR code at the door. That\'s it.', 'All day'],
            ['Send reminders', 'Dues and seat expiry alerts go out on their own.', '6:00 PM'],
            ['Review the day', 'See money in, seats filled and dues pending.', '9:00 PM'],
        ],
    ],

    'features' => [
        'eyebrow' => 'Major features',
        'title' => 'Built for serious library businesses.',
        'lead' => 'Ten powerful tools that replace registers, sheets and phone calls.',
        'items' => [
            ['Multi-branch control', 'Run every branch from one login, each with its own seats, plans and money.'],
            ['Live seat & shift map', 'Custom shifts, floors and seat types with real-time availability.'],
            ['30-second admissions', 'Photo, ID proof, plan and seat on one screen. Import all learners from Excel.'],
            ['Fees, UPI & online payments', 'Cash, UPI or online. Receipts go out on WhatsApp and as PDF.'],
            ['Automatic reminders', 'Fee due, expiry and renewal messages that send themselves.'],
            ['QR attendance', 'Scan-to-check-in at the door, with daily and monthly logs.'],
            ['Staff roles & permissions', 'Give each staff member only the access they need.'],
            ['Reports & expenses', 'Collections, expenses, balance sheet and profit. Export to Excel.'],
            ['ID cards & receipts', 'Print learner ID cards one by one or in bulk.'],
            ['Encrypted learner data', 'Phone numbers and emails stay encrypted and private.'],
        ],
        'cta' => 'Compare plans',
    ],

    'videos' => [
        'eyebrow' => 'Libraro Shorts',
        'title' => 'Watch. Learn. Grow your library.',
        'lead' => 'Short #Mentorship videos for library owners. Tap any video to watch it with sound.',
        'play' => 'Play video', 'close' => 'Close video',
        'prev' => 'Previous video', 'next' => 'Next video',
        'channel' => 'More videos on our YouTube channel',
        'sub' => 'For library owners',
        'items' => ['#Mentorship · Episode 6', '#Mentorship · Episode 7', 'Libraro #Mentorship', '#Mentorship · Episode 8'],
    ],

    'benefits' => [
        'eyebrow' => 'What changes for you',
        'title' => 'More revenue. Less running around.',
        'lead' => 'What owners notice in the first month on Libraro.',
        'items' => [
            ['Hours back every week', 'No more counting registers or adding up sheets late at night.'],
            ['Revenue recovered', 'Timely reminders mean fewer forgotten dues and more renewals.'],
            ['Fewer disputes', 'Every seat, payment and check-in is on record, with a receipt.'],
            ['Every branch, visible', 'Know what is happening in each library without a phone call.'],
            ['Room to grow', 'Open your next branch on the same system, in minutes.'],
            ['Happier learners', 'Instant receipts, fair seat allotment and a professional experience.'],
        ],
    ],

    'pricing' => [
        'eyebrow' => 'Plans & pricing',
        'title' => 'Simple plans. Serious value.',
        'lead' => 'Every plan includes free setup help, a free library listing and our 7-day full refund.',
        'monthly' => 'Monthly', 'yearly' => 'Yearly', 'save' => 'Save more',
        'popular' => 'Recommended',
        'choose' => 'Choose',
        'all' => 'See all :n features',
        'plus' => 'Everything in :p, plus:',
        'key' => 'Key features',
        'per_month' => '/month',
        'billed' => 'Billed monthly',
        'refund' => '7-day full refund on every plan',
        'gst' => 'GST extra as applicable.',
        'compare' => 'Compare all features',
        'taglines' => [1 => 'For a single library getting organised', 2 => 'For growing owners with 2 branches', 3 => 'For multi-branch library businesses'],
        'dialog' => ['eyebrow' => 'All features', 'choose' => 'Choose plan', 'foot' => '7-day full refund · GST extra as applicable', 'new' => 'New'],
        'js' => ['month' => '/month', 'year' => '/year', 'mo' => '/mo', 'yr' => '/yr', 'billedM' => 'Billed monthly', 'billedY' => 'Billed yearly', 'save' => 'You save', 'included' => 'features included', 'newNote' => 'items marked New are added in this plan'],
    ],

    'compare' => [
        'title' => 'Compare plans side by side.',
        'lead' => 'Every feature, every plan. No fine print.',
        'expand' => 'Expand all', 'collapse' => 'Collapse all',
        'yes' => 'Included', 'no' => 'Not included',
        'groups' => [
            ['Capacity & branches', [
                ['Learner capacity', 'Up to 100 seats', 'Up to 200 seats', 'Unlimited seats'],
                ['Branches', '1 branch', '2 branches', '3+ branches'],
                ['Custom shifts', 'Up to 3', 'Up to 6', 'Unlimited'],
                ['Floors & rooms', 'Single floor', 'Multi-floor', 'Multi-floor'],
            ]],
            ['Seats & admissions', [
                ['Live visual seat map', true, true, true],
                ['QR code digital admission', true, true, true],
                ['Seat swap & day freezing', true, true, true],
                ['Learner ID cards', 'Single print', 'Bulk print', 'Batch print'],
            ]],
            ['Fees & reminders', [
                ['Automatic fee due reminders', 'SMS / add-on', 'Included', 'Priority'],
                ['WhatsApp fee receipts', true, true, true],
                ['Expired & extended learner lists', true, true, true],
            ]],
            ['Reports & money', [
                ['Collection reports', 'Standard', 'Advanced', 'Full suite'],
                ['Expense manager & balance sheet', true, true, true],
                ['Excel / CSV export', true, true, true],
            ]],
            ['Security & support', [
                ['Learner data privacy', 'Encrypted', 'Encrypted', 'Encrypted'],
                ['Data migration help', 'Self import', 'Assisted', 'Done for you'],
                ['Customer support', 'Email', 'Phone & WhatsApp', 'Priority, 24×7'],
                ['Money-back guarantee', '7 days', '7 days', '7 days'],
            ]],
        ],
    ],

    'refund' => [
        'day' => 'day refund',
        'title' => 'Try Libraro for 7 days. Not right? Get every rupee back.',
        'text' => 'Use any plan with your real library for a full week. If it doesn\'t fit, ask for a refund and we return the full amount. No questions, no forms, no follow-up calls.',
        'link' => 'Read the refund policy',
        'chips' => [['rotate-ccw', 'Full refund'], ['layers', 'Any plan'], ['badge-check', 'No reason needed']],
    ],

    'reviews' => [
        'eyebrow' => 'Owner stories',
        'title' => 'Trusted by library owners.',
        'lead' => 'What owners say after moving their libraries to Libraro.',
        'more' => 'Read more',
        'prev' => 'Previous review', 'next' => 'Next review',
        'stars' => ':n out of 5 stars',
        'owner' => 'Library owner',
    ],

    'faq' => [
        'title' => 'Questions, answered.',
        'items' => [
            ['Can I manage several libraries with one login?', 'Yes. Libraro is built for multi-branch owners. Each branch has its own seats, shifts, plans and money, and you switch between them in one click.'],
            ['How long does setup take?', 'Most owners are live the same day. Our team sets up your seats, shifts and plans with you on a call, and imports your learners from Excel.'],
            ['Does it work on my phone?', 'Yes. Libraro works on any phone, tablet or laptop in the browser. No app install needed.'],
            ['Is my learners\' data safe?', 'Yes. Mobile numbers and emails are encrypted, and staff only see what you allow them to see.'],
            ['How does the 7-day refund work?', 'Use any plan for up to 7 days. If it is not right for your library, ask for a refund and we return the full amount. No questions asked.'],
            ['How do I get help?', 'Call or WhatsApp us at +91 81144 79678, or email support@libraro.in. Real people, no bots.'],
        ],
    ],

    'love' => ['Made with :heart for Indian library owners.', 'Thank you for your trust.'],
    'final' => [
        'title' => 'Ready to run every branch from one place?',
        'lead' => 'Add your library today. We set it up with you on a call, and you are covered by a 7-day full refund.',
        'cta' => 'Add your library',
        'call' => 'Call +91 81144 79678',
        'wa' => 'WhatsApp us',
    ],
],

/* ======================================================================
 * HINDI
 * ==================================================================== */
'hi' => [
    'meta' => [
        'title' => 'Libraro: भारत का मल्टी-ब्रांच लाइब्रेरी मैनेजमेंट सॉफ्टवेयर',
        'description' => 'अपनी सभी स्टडी लाइब्रेरी एक डैशबोर्ड से चलाएं। सीट, फीस, QR हाज़िरी, WhatsApp रिमाइंडर, स्टाफ और ब्रांच रिपोर्ट। फ्री सेटअप और 7 दिन में पूरा रिफंड।',
        'og_title' => 'Libraro: अपनी हर लाइब्रेरी एक डैशबोर्ड से चलाएं',
    ],
    'lang_label' => 'भाषा',
    'nav' => ['home' => 'होम', 'features' => 'फीचर्स', 'pricing' => 'प्लान', 'blog' => 'ब्लॉग', 'contact' => 'संपर्क', 'find' => 'लाइब्रेरी खोजें', 'login' => 'लॉग इन', 'add' => 'अपनी लाइब्रेरी जोड़ें', 'call' => 'कॉल करें', 'learner' => 'लर्नर लॉग इन'],

    'hero' => [
        'eyebrow' => '2, 5 या 20 लाइब्रेरी चलाने वाले मालिकों के लिए',
        'title' => 'आपकी हर लाइब्रेरी।<br>एक आसान डैशबोर्ड।',
        'lead' => 'Libraro वह मैनेजमेंट सिस्टम है जिससे गंभीर लाइब्रेरी मालिक अपनी हर ब्रांच की सीट, फीस, स्टाफ और हाज़िरी लैपटॉप या फ़ोन से संभालते हैं।',
        'cta' => 'अपनी लाइब्रेरी जोड़ें',
        'call' => 'कॉल बुक करें',
        'refund' => '7 दिन में पूरा रिफंड',
        'refund_sub' => 'कोई सवाल नहीं',
        'trust' => ['सेटअप हम साथ में करते हैं', 'लर्नर डेटा एन्क्रिप्टेड', 'हर फ़ोन पर चलता है'],
        'stats' => [['3+', 'ब्रांच, एक लॉग इन'], ['30 सेकंड', 'में नया एडमिशन'], ['24×7', 'लाइव आंकड़े']],
        'marquee' => ['मल्टी-ब्रांच कंट्रोल', 'लाइव सीट मैप', 'WhatsApp रिमाइंडर', 'QR हाज़िरी', 'स्टाफ परमिशन', 'एन्क्रिप्टेड डेटा', 'तुरंत रसीद', '7 दिन में पूरा रिफंड'],
        'screen_alt' => 'लैपटॉप पर Libraro डैशबोर्ड जिसमें सीट, कलेक्शन और बकाया दिख रहे हैं',
        'phone_alt' => 'मोबाइल फ़ोन पर Libraro',
        'float1' => ['फीस मिली', '₹1,200 · UPI'],
        'float2' => ['भरी हुई सीटें', 'आज 84%'],
        'float3' => 'फीस रिमाइंडर भेजा गया',
    ],

    'pains' => [
        'eyebrow' => 'सच्चाई यह है',
        'title' => 'लाइब्रेरी चलाना आसान नहीं है।',
        'lead' => 'स्टडी हॉल शांत रहता है, पर उसके पीछे का काम कभी नहीं रुकता। हर नई ब्रांच के साथ यह काम दोगुना हो जाता है।',
        'prev' => 'पिछली समस्या', 'next' => 'अगली समस्या',
        'label' => 'समस्या',
        'items' => [
            ['फीस हाथ से निकल जाती है', 'बकाया चुपचाप बढ़ता है। महीने के आख़िर में पता चलता है, जब लर्नर जा चुका होता है।'],
            ['एक सीट, दो लर्नर', 'रजिस्टर में लाइव शिफ्ट नहीं दिखती, इसलिए डबल बुकिंग और सुबह-सुबह बहस होती है।'],
            ['प्लान ख़त्म, फिर भी आ रहे हैं', 'मेंबरशिप ख़त्म हो जाती है, पर लर्नर आते रहते हैं। हफ़्तों तक किसी को पता नहीं चलता।'],
            ['हाज़िरी भरोसे पर', 'काग़ज़ की शीट पर साइन छूट जाते हैं, नकली होते हैं या शीट खो जाती है।'],
            ['ब्रांच जो दिखती नहीं', 'आप एक समय में एक ही लाइब्रेरी में हो सकते हैं। बाकी फ़ोन कॉल और भरोसे पर चलती हैं।'],
            ['स्टाफ और कैश का हिसाब नहीं', 'काउंटर पर पैसा आता है। कितना, किसने लिया, और क्या पूरा जमा हुआ?'],
            ['रजिस्टर, Excel और उलझन', 'पाँच रजिस्टर, तीन शीट और एक WhatsApp ग्रुप। किसी का हिसाब आपस में नहीं मिलता।'],
            ['ख़ाली सीटें, कोई पूछताछ नहीं', 'आसपास के लर्नर आपको ऑनलाइन ढूंढ नहीं पाते, इसलिए सीटें महीनों ख़ाली रहती हैं।'],
        ],
    ],

    'why' => [
        'eyebrow' => 'मैनेजमेंट सिस्टम क्यों ज़रूरी है',
        'title' => 'रजिस्टर एक कमरे के लिए ठीक था। लाइब्रेरी बिज़नेस के लिए नहीं।',
        'lead' => 'जैसे ही आप एक से ज़्यादा लाइब्रेरी चलाते हैं, हाथ से किया काम संभलता नहीं। हर नई ब्रांच उलझन बढ़ाती है।',
        'cards' => [
            ['notebook-pen', 'काग़ज़ के रजिस्टर', 'न खोज, न बैकअप, न रिमाइंडर। एक कप चाय गिरी और महीने भर का रिकॉर्ड गया।'],
            ['file-spreadsheet', 'Excel शीट', 'एक ही लैपटॉप में रहती है। दो लोग एडिट करें तो बिगड़ जाती है। रसीद या रिमाइंडर नहीं भेज सकती।'],
            ['message-square-text', 'WhatsApp ग्रुप', 'मैसेज दब जाते हैं। किसने पैसे दिए, किसे बताया गया, कोई रिकॉर्ड नहीं।'],
        ],
        'bad' => 'बड़े पैमाने पर टूट जाता है',
        'quote' => 'जो मालिक आगे बढ़ते हैं, वे लाइब्रेरी को बिज़नेस की तरह चलाते हैं: एक सिस्टम, लाइव आंकड़े, साफ़ ज़िम्मेदारी।',
    ],

    'solve' => [
        'eyebrow' => 'Libraro इसे कैसे हल करता है',
        'title' => 'हर समस्या का जवाब।',
        'lead' => 'ऊपर की हर परेशानी का Libraro में एक साफ़, बना-बनाया हल है।',
        'before' => 'पहले', 'after' => 'Libraro के साथ',
        'items' => [
            ['अपने-आप फीस रिमाइंडर', 'ड्यू डेट से पहले WhatsApp और SMS रिमाइंडर जाते हैं। फीस लेते ही रसीद पहुँच जाती है।'],
            ['हर शिफ्ट का लाइव सीट मैप', 'हर शिफ्ट और फ्लोर की हर सीट दिखती है। डबल बुकिंग हो ही नहीं सकती।'],
            ['हर सुबह एक्सपायरी अलर्ट', 'जिनका प्लान ख़त्म हो रहा है या हो गया है, उनकी लिस्ट तैयार मिलती है, एक टैप में रिन्यू।'],
            ['दरवाज़े पर QR चेक-इन', 'लर्नर एक बार स्कैन करता है। हाज़िरी अपने-आप लगती और सेव होती है।'],
            ['एक क्लिक में ब्रांच बदलें', 'हर ब्रांच की सीटें, पैसा और स्टाफ, एक ही लॉग इन से।'],
            ['परमिशन के साथ स्टाफ लॉग इन', 'हर स्टाफ का अपना एक्सेस। हर रुपया नाम के साथ दर्ज होता है।'],
            ['एक ही भरोसेमंद रिकॉर्ड', 'लर्नर, सीट, फीस और ख़र्च एक सुरक्षित जगह, हमेशा बैकअप के साथ।'],
            ['फ्री लिस्टिंग और QR बुकिंग', 'आसपास के लर्नर आपको "Find my library" पर ढूंढते हैं और ख़ुद सीट बुक करते हैं।'],
        ],
    ],

    'show' => [
        'eyebrow' => 'असली प्रोडक्ट',
        'title' => 'वह पोर्टल देखिए जो आपकी टीम रोज़ इस्तेमाल करेगी।',
        'lead' => 'Libraro की असली स्क्रीन। मॉड्यूल पर क्लिक करके देखें।',
        'tabs' => [
            'dashboard' => ['डैशबोर्ड', 'सभी ब्रांच की सीटें, कलेक्शन और बकाया एक नज़र में।'],
            'learners' => ['लर्नर लिस्ट', 'हर लर्नर को खोजें, फ़िल्टर करें और रिन्यू, स्वैप, अपग्रेड या सेटल करें।'],
            'attendance' => ['हाज़िरी', 'कौन आ रहा है, कौन रुक गया, और आज किसे कॉल करना है।'],
            'fees' => ['फीस और पेमेंट', 'हर पेमेंट, रसीद और बकाया, पूरी हिस्ट्री के साथ।'],
            'reports' => ['रिपोर्ट', 'महीने का कलेक्शन, ख़र्च और मुनाफ़ा एक टैप में।'],
        ],
    ],

    'mobile' => [
        'eyebrow' => 'डेस्कटॉप और मोबाइल',
        'title' => 'Libraro को अपने मोबाइल या डेस्कटॉप पर आसानी से चलाएं।',
        'lead' => 'घर से कलेक्शन देखें, काउंटर पर एडमिशन करें, चलते-फिरते सीट स्वैप मंज़ूर करें। हर डिवाइस पर वही डेटा।',
        'points' => [['smartphone', 'कोई ऐप इंस्टॉल नहीं'], ['zap', '4G पर भी तेज़'], ['monitor-smartphone', 'एक लॉग इन, हर डिवाइस']],
        'alts' => ['मोबाइल पर Libraro डैशबोर्ड', 'मोबाइल पर Libraro लर्नर लिस्ट', 'मोबाइल पर Libraro सीट मैप'],
    ],

    'ops' => [
        'eyebrow' => 'रोज़ के काम',
        'title' => 'रोज़ का हर काम, बस कुछ टैप में।',
        'lead' => 'Libraro पोर्टल की असली स्क्रीन, लाइट और डार्क मोड में।',
        'light' => 'लाइट', 'dark' => 'डार्क',
        'tabs' => [
            'profile' => ['लर्नर प्रोफ़ाइल', 'हर लर्नर की सीट, शिफ्ट, प्लान, बकाया और संपर्क एक स्क्रीन पर।'],
            'swap' => ['सीट बदलें', 'लर्नर को सेकंडों में दूसरी सीट पर भेजें, फीस का हिसाब अपने-आप।'],
            'plan' => ['प्लान बदलें', 'लाइव बिल समरी के साथ प्लान अपग्रेड या बदलें। कैलकुलेटर की ज़रूरत नहीं।'],
            'attend' => ['लर्नर हाज़िरी', 'हर विज़िट का कैलेंडर, पढ़ाई के घंटे और हाज़िरी दर।'],
            'wallet' => ['पेमेंट और वॉलेट', 'लर्नर ने कितना दिया, कितना बाकी है और अगली ड्यू डेट।'],
        ],
    ],

    'day' => [
        'eyebrow' => 'यह कैसे काम करता है',
        'title' => 'आपकी लाइब्रेरी का एक दिन।',
        'step' => 'स्टेप', 'prev' => 'पिछला', 'next' => 'अगला', 'track' => 'रोज़ के काम के स्टेप',
        'items' => [
            ['एडमिशन', 'फ़ोटो और ID प्रूफ़ के साथ 30 सेकंड में लर्नर जोड़ें।', 'सुबह 8:00'],
            ['सीट दें', 'लाइव सीट मैप से किसी भी शिफ्ट के लिए ख़ाली सीट चुनें।', 'सुबह 8:05'],
            ['फीस लें', 'कैश, UPI या ऑनलाइन। रसीद WhatsApp पर चली जाती है।', 'सुबह 10:30'],
            ['हाज़िरी लगाएं', 'लर्नर दरवाज़े पर QR कोड स्कैन करते हैं। बस।', 'पूरे दिन'],
            ['रिमाइंडर भेजें', 'बकाया और सीट एक्सपायरी के अलर्ट अपने-आप जाते हैं।', 'शाम 6:00'],
            ['दिन का हिसाब', 'आया पैसा, भरी सीटें और बकाया एक नज़र में।', 'रात 9:00'],
        ],
    ],

    'features' => [
        'eyebrow' => 'मुख्य फीचर्स',
        'title' => 'गंभीर लाइब्रेरी बिज़नेस के लिए बना।',
        'lead' => 'दस दमदार टूल जो रजिस्टर, शीट और फ़ोन कॉल की जगह लेते हैं।',
        'items' => [
            ['मल्टी-ब्रांच कंट्रोल', 'हर ब्रांच एक लॉग इन से, हर एक की अपनी सीटें, प्लान और पैसा।'],
            ['लाइव सीट और शिफ्ट मैप', 'अपनी शिफ्ट, फ्लोर और सीट टाइप, रियल-टाइम उपलब्धता के साथ।'],
            ['30 सेकंड में एडमिशन', 'फ़ोटो, ID प्रूफ़, प्लान और सीट एक स्क्रीन पर। Excel से सारे लर्नर इम्पोर्ट करें।'],
            ['फीस, UPI और ऑनलाइन पेमेंट', 'कैश, UPI या ऑनलाइन। रसीद WhatsApp और PDF में जाती है।'],
            ['अपने-आप रिमाइंडर', 'फीस, एक्सपायरी और रिन्यूअल के मैसेज जो ख़ुद चले जाते हैं।'],
            ['QR हाज़िरी', 'दरवाज़े पर स्कैन करके चेक-इन, रोज़ाना और मासिक रिकॉर्ड के साथ।'],
            ['स्टाफ रोल और परमिशन', 'हर स्टाफ को सिर्फ़ उतना एक्सेस दें जितना ज़रूरी है।'],
            ['रिपोर्ट और ख़र्च', 'कलेक्शन, ख़र्च, बैलेंस शीट और मुनाफ़ा। Excel में एक्सपोर्ट।'],
            ['ID कार्ड और रसीद', 'लर्नर ID कार्ड एक-एक करके या एक साथ प्रिंट करें।'],
            ['एन्क्रिप्टेड लर्नर डेटा', 'मोबाइल नंबर और ईमेल एन्क्रिप्टेड और निजी रहते हैं।'],
        ],
        'cta' => 'प्लान की तुलना करें',
    ],

    'videos' => [
        'eyebrow' => 'Libraro Shorts',
        'title' => 'देखिए। सीखिए। लाइब्रेरी बढ़ाइए।',
        'lead' => 'लाइब्रेरी मालिकों के लिए छोटे #Mentorship वीडियो। आवाज़ के साथ देखने के लिए किसी भी वीडियो पर टैप करें।',
        'play' => 'वीडियो चलाएं', 'close' => 'वीडियो बंद करें',
        'prev' => 'पिछला वीडियो', 'next' => 'अगला वीडियो',
        'channel' => 'हमारे YouTube चैनल पर और वीडियो',
        'sub' => 'लाइब्रेरी मालिकों के लिए',
        'items' => ['#Mentorship · एपिसोड 6', '#Mentorship · एपिसोड 7', 'Libraro #Mentorship', '#Mentorship · एपिसोड 8'],
    ],

    'benefits' => [
        'eyebrow' => 'आपके लिए क्या बदलेगा',
        'title' => 'ज़्यादा कमाई। कम भाग-दौड़।',
        'lead' => 'Libraro पर पहले महीने में मालिक ये बदलाव देखते हैं।',
        'items' => [
            ['हर हफ़्ते घंटों की बचत', 'रात को रजिस्टर गिनना और शीट जोड़ना बंद।'],
            ['रुका हुआ पैसा वापस', 'समय पर रिमाइंडर से कम बकाया और ज़्यादा रिन्यूअल।'],
            ['कम झगड़े', 'हर सीट, पेमेंट और चेक-इन रसीद के साथ रिकॉर्ड में।'],
            ['हर ब्रांच नज़र में', 'बिना फ़ोन किए जानिए हर लाइब्रेरी में क्या हो रहा है।'],
            ['आगे बढ़ने की जगह', 'अगली ब्रांच उसी सिस्टम पर मिनटों में शुरू करें।'],
            ['ख़ुश लर्नर', 'तुरंत रसीद, सही सीट और प्रोफ़ेशनल अनुभव।'],
        ],
    ],

    'pricing' => [
        'eyebrow' => 'प्लान और कीमत',
        'title' => 'आसान प्लान। पूरी वैल्यू।',
        'lead' => 'हर प्लान में फ्री सेटअप मदद, फ्री लाइब्रेरी लिस्टिंग और 7 दिन में पूरा रिफंड।',
        'monthly' => 'मासिक', 'yearly' => 'सालाना', 'save' => 'ज़्यादा बचत',
        'popular' => 'सुझाया गया',
        'choose' => 'चुनें',
        'all' => 'सभी :n फीचर्स देखें',
        'plus' => ':p का सब कुछ, और:',
        'key' => 'मुख्य फीचर्स',
        'per_month' => '/महीना',
        'billed' => 'हर महीने बिल',
        'refund' => 'हर प्लान पर 7 दिन में पूरा रिफंड',
        'gst' => 'GST अलग से लागू।',
        'compare' => 'सभी फीचर्स की तुलना करें',
        'taglines' => [1 => 'एक लाइब्रेरी को व्यवस्थित करने के लिए', 2 => '2 ब्रांच वाले बढ़ते मालिकों के लिए', 3 => 'मल्टी-ब्रांच लाइब्रेरी बिज़नेस के लिए'],
        'dialog' => ['eyebrow' => 'सभी फीचर्स', 'choose' => 'प्लान चुनें', 'foot' => '7 दिन में पूरा रिफंड · GST अलग से', 'new' => 'नया'],
        'js' => ['month' => '/महीना', 'year' => '/साल', 'mo' => '/माह', 'yr' => '/साल', 'billedM' => 'हर महीने बिल', 'billedY' => 'हर साल बिल', 'save' => 'आपकी बचत', 'included' => 'फीचर्स शामिल', 'newNote' => '"नया" वाले फीचर इस प्लान में जुड़े हैं'],
    ],

    'compare' => [
        'title' => 'प्लान की आमने-सामने तुलना।',
        'lead' => 'हर फीचर, हर प्लान। कोई छिपी शर्त नहीं।',
        'expand' => 'सब खोलें', 'collapse' => 'सब बंद करें',
        'yes' => 'शामिल', 'no' => 'शामिल नहीं',
        'groups' => [
            ['क्षमता और ब्रांच', [
                ['लर्नर क्षमता', '100 सीट तक', '200 सीट तक', 'असीमित सीटें'],
                ['ब्रांच', '1 ब्रांच', '2 ब्रांच', '3+ ब्रांच'],
                ['अपनी शिफ्ट', '3 तक', '6 तक', 'असीमित'],
                ['फ्लोर और कमरे', 'एक फ्लोर', 'कई फ्लोर', 'कई फ्लोर'],
            ]],
            ['सीट और एडमिशन', [
                ['लाइव विज़ुअल सीट मैप', true, true, true],
                ['QR कोड डिजिटल एडमिशन', true, true, true],
                ['सीट स्वैप और डे फ्रीज़', true, true, true],
                ['लर्नर ID कार्ड', 'एक-एक प्रिंट', 'बल्क प्रिंट', 'बैच प्रिंट'],
            ]],
            ['फीस और रिमाइंडर', [
                ['अपने-आप फीस रिमाइंडर', 'SMS / ऐड-ऑन', 'शामिल', 'प्राथमिकता'],
                ['WhatsApp फीस रसीद', true, true, true],
                ['एक्सपायर्ड और एक्सटेंडेड लर्नर लिस्ट', true, true, true],
            ]],
            ['रिपोर्ट और पैसा', [
                ['कलेक्शन रिपोर्ट', 'स्टैंडर्ड', 'एडवांस्ड', 'पूरा सेट'],
                ['ख़र्च मैनेजर और बैलेंस शीट', true, true, true],
                ['Excel / CSV एक्सपोर्ट', true, true, true],
            ]],
            ['सुरक्षा और सपोर्ट', [
                ['लर्नर डेटा प्राइवेसी', 'एन्क्रिप्टेड', 'एन्क्रिप्टेड', 'एन्क्रिप्टेड'],
                ['डेटा माइग्रेशन मदद', 'ख़ुद इम्पोर्ट', 'मदद के साथ', 'हम करेंगे'],
                ['कस्टमर सपोर्ट', 'ईमेल', 'फ़ोन और WhatsApp', 'प्राथमिकता, 24×7'],
                ['पैसे वापसी गारंटी', '7 दिन', '7 दिन', '7 दिन'],
            ]],
        ],
    ],

    'refund' => [
        'day' => 'दिन का रिफंड',
        'title' => '7 दिन Libraro आज़माइए। पसंद न आए तो पूरा पैसा वापस।',
        'text' => 'अपनी असली लाइब्रेरी के साथ कोई भी प्लान पूरे हफ़्ते चलाइए। ठीक न लगे तो रिफंड माँगिए, हम पूरी रकम लौटा देंगे। न सवाल, न फ़ॉर्म, न बार-बार कॉल।',
        'link' => 'रिफंड पॉलिसी पढ़ें',
        'chips' => [['rotate-ccw', 'पूरा रिफंड'], ['layers', 'कोई भी प्लान'], ['badge-check', 'कारण बताने की ज़रूरत नहीं']],
    ],

    'reviews' => [
        'eyebrow' => 'मालिकों की बात',
        'title' => 'लाइब्रेरी मालिकों का भरोसा।',
        'lead' => 'Libraro पर आने के बाद मालिक क्या कहते हैं।',
        'more' => 'और पढ़ें',
        'prev' => 'पिछला रिव्यू', 'next' => 'अगला रिव्यू',
        'stars' => '5 में से :n स्टार',
        'owner' => 'लाइब्रेरी मालिक',
    ],

    'faq' => [
        'title' => 'आपके सवाल, हमारे जवाब।',
        'items' => [
            ['क्या मैं एक लॉग इन से कई लाइब्रेरी संभाल सकता हूँ?', 'हाँ। Libraro मल्टी-ब्रांच मालिकों के लिए बना है। हर ब्रांच की अपनी सीटें, शिफ्ट, प्लान और पैसा होता है, और आप एक क्लिक में ब्रांच बदल सकते हैं।'],
            ['सेटअप में कितना समय लगता है?', 'ज़्यादातर मालिक उसी दिन शुरू कर देते हैं। हमारी टीम कॉल पर आपके साथ सीट, शिफ्ट और प्लान सेट करती है और Excel से लर्नर इम्पोर्ट करती है।'],
            ['क्या यह मेरे फ़ोन पर चलेगा?', 'हाँ। Libraro किसी भी फ़ोन, टैबलेट या लैपटॉप के ब्राउज़र में चलता है। कोई ऐप इंस्टॉल नहीं करना।'],
            ['क्या मेरे लर्नर का डेटा सुरक्षित है?', 'हाँ। मोबाइल नंबर और ईमेल एन्क्रिप्टेड रहते हैं, और स्टाफ को सिर्फ़ वही दिखता है जिसकी आप इजाज़त दें।'],
            ['7 दिन का रिफंड कैसे काम करता है?', 'कोई भी प्लान 7 दिन तक इस्तेमाल करें। ठीक न लगे तो रिफंड माँगें, हम पूरी रकम लौटा देंगे। कोई सवाल नहीं।'],
            ['मदद कैसे मिलेगी?', '+91 81144 79678 पर कॉल या WhatsApp करें, या support@libraro.in पर ईमेल करें। असली लोग, कोई बॉट नहीं।'],
        ],
    ],

    'love' => ['भारत के लाइब्रेरी मालिकों के लिए :heart से बनाया गया।', 'आपके भरोसे के लिए धन्यवाद।'],
    'final' => [
        'title' => 'हर ब्रांच एक जगह से चलाने के लिए तैयार हैं?',
        'lead' => 'आज ही अपनी लाइब्रेरी जोड़ें। हम कॉल पर साथ में सेटअप करेंगे, और आपको 7 दिन के पूरे रिफंड का भरोसा है।',
        'cta' => 'अपनी लाइब्रेरी जोड़ें',
        'call' => 'कॉल करें +91 81144 79678',
        'wa' => 'WhatsApp करें',
    ],
],

/* ======================================================================
 * HINGLISH
 * ==================================================================== */
'hinglish' => [
    'meta' => [
        'title' => 'Libraro: Multi-Branch Library Management Software (Hinglish)',
        'description' => 'Apni saari study libraries ek dashboard se chalaiye. Seats, fees, QR attendance, WhatsApp reminders, staff aur branch reports. Free setup aur 7 din mein full refund.',
        'og_title' => 'Libraro: apni har library ek dashboard se chalaiye',
    ],
    'lang_label' => 'Bhasha',
    'nav' => ['home' => 'Home', 'features' => 'Features', 'pricing' => 'Pricing', 'blog' => 'Blog', 'contact' => 'Contact', 'find' => 'Library dhundhein', 'login' => 'Log in', 'add' => 'Apni library jodein', 'call' => 'Call karein', 'learner' => 'Learner login'],

    'hero' => [
        'eyebrow' => '2, 5 ya 20 libraries chalane wale owners ke liye',
        'title' => 'Aapki har library.<br>Ek simple dashboard.',
        'lead' => 'Libraro woh management system hai jisse serious library owners apni har branch ki seats, fees, staff aur attendance laptop ya phone se sambhalte hain.',
        'cta' => 'Apni library jodein',
        'call' => 'Call book karein',
        'refund' => '7 din mein full refund',
        'refund_sub' => 'Koi sawal nahi',
        'trust' => ['Setup hum saath mein karte hain', 'Learner data encrypted', 'Har phone par chalta hai'],
        'stats' => [['3+', 'branches, ek login'], ['30 sec', 'mein naya admission'], ['24×7', 'live numbers']],
        'marquee' => ['Multi-branch control', 'Live seat maps', 'WhatsApp reminders', 'QR attendance', 'Staff permissions', 'Encrypted data', 'Turant receipt', '7 din mein full refund'],
        'screen_alt' => 'Laptop par Libraro dashboard jismein seats, collection aur dues dikh rahe hain',
        'phone_alt' => 'Mobile phone par Libraro',
        'float1' => ['Fee mili', '₹1,200 · UPI'],
        'float2' => ['Seats bhari', 'aaj 84%'],
        'float3' => 'Fee reminder bhej diya',
    ],

    'pains' => [
        'eyebrow' => 'Sach yeh hai',
        'title' => 'Library chalana bilkul aasan nahi hai.',
        'lead' => 'Study hall shaant rehta hai, par uske peeche ka kaam kabhi nahi rukta. Har nayi branch ke saath yeh kaam double ho jata hai.',
        'prev' => 'Pichhli problem', 'next' => 'Agli problem',
        'label' => 'Problem',
        'items' => [
            ['Fees haath se nikal jaati hai', 'Dues chupchaap badhte hain. Mahine ke end mein pata chalta hai, jab learner ja chuka hota hai.'],
            ['Ek seat, do learners', 'Register mein live shift nahi dikhti, isliye double booking aur subah-subah behas hoti hai.'],
            ['Plan khatam, phir bhi aa rahe hain', 'Membership khatam ho jaati hai, par learners aate rehte hain. Hafton tak kisi ko pata nahi chalta.'],
            ['Attendance bharose par', 'Kaagaz ki sheet par sign chhoot jaate hain, fake hote hain ya sheet kho jaati hai.'],
            ['Branches jo dikhti nahi', 'Aap ek time par ek hi library mein ho sakte hain. Baaki phone calls aur bharose par chalti hain.'],
            ['Staff aur cash ka hisaab nahi', 'Counter par paisa aata hai. Kitna, kisne liya, aur kya poora jama hua?'],
            ['Register, Excel aur confusion', 'Paanch register, teen sheets aur ek WhatsApp group. Kisi ka hisaab aapas mein nahi milta.'],
            ['Khaali seats, koi enquiry nahi', 'Aas-paas ke learners aapko online dhundh nahi paate, isliye seats mahino khaali rehti hain.'],
        ],
    ],

    'why' => [
        'eyebrow' => 'Management tool kyun zaroori hai',
        'title' => 'Register ek room ke liye theek tha. Library business ke liye nahi.',
        'lead' => 'Jaise hi aap ek se zyada library chalate hain, manual kaam sambhalta nahi. Har nayi branch confusion badhaati hai.',
        'cards' => [
            ['notebook-pen', 'Kaagaz ke register', 'Na search, na backup, na reminder. Ek cup chai giri aur mahine bhar ka record gaya.'],
            ['file-spreadsheet', 'Excel sheets', 'Ek hi laptop mein rehti hai. Do log edit karein to bigad jaati hai. Receipt ya reminder nahi bhej sakti.'],
            ['message-square-text', 'WhatsApp groups', 'Messages dab jaate hain. Kisne pay kiya, kise bataya, koi record nahi.'],
        ],
        'bad' => 'Scale par toot jaata hai',
        'quote' => 'Jo owners grow karte hain, woh library ko business ki tarah chalate hain: ek system, live numbers, clear accountability.',
    ],

    'solve' => [
        'eyebrow' => 'Libraro ise kaise solve karta hai',
        'title' => 'Har problem ka jawab.',
        'lead' => 'Upar ki har pareshani ka Libraro mein ek clear, built-in solution hai.',
        'before' => 'Pehle', 'after' => 'Libraro ke saath',
        'items' => [
            ['Automatic fee reminders', 'Due date se pehle WhatsApp aur SMS reminder jaate hain. Fee lete hi receipt pahunch jaati hai.'],
            ['Har shift ka live seat map', 'Har shift aur floor ki har seat dikhti hai. Double booking ho hi nahi sakti.'],
            ['Har subah expiry alert', 'Jinka plan khatam ho raha hai ya ho gaya, unki list ready milti hai, ek tap mein renew.'],
            ['Darwaze par QR check-in', 'Learner ek baar scan karta hai. Attendance apne-aap lagti aur save hoti hai.'],
            ['Ek click mein branch switch', 'Har branch ki seats, paisa aur staff, ek hi login se.'],
            ['Permissions ke saath staff login', 'Har staff ka apna access. Har rupaya naam ke saath record hota hai.'],
            ['Ek hi bharosemand record', 'Learners, seats, fees aur kharche ek secure jagah, hamesha backup ke saath.'],
            ['Free listing aur QR booking', 'Aas-paas ke learners aapko "Find my library" par dhundhte hain aur khud seat book karte hain.'],
        ],
    ],

    'show' => [
        'eyebrow' => 'Asli product',
        'title' => 'Woh portal dekhiye jo aapki team roz use karegi.',
        'lead' => 'Libraro ki asli screens. Modules par click karke dekhiye.',
        'tabs' => [
            'dashboard' => ['Dashboard', 'Saari branches ki seats, collection aur dues ek nazar mein.'],
            'learners' => ['Learner list', 'Har learner ko search, filter karein aur renew, swap, upgrade ya settle karein.'],
            'attendance' => ['Attendance', 'Kaun aa raha hai, kaun ruk gaya, aur aaj kise call karna hai.'],
            'fees' => ['Fees & payments', 'Har payment, receipt aur pending due, poori history ke saath.'],
            'reports' => ['Reports', 'Mahine ka collection, kharcha aur profit ek tap mein.'],
        ],
    ],

    'mobile' => [
        'eyebrow' => 'Desktop aur mobile',
        'title' => 'Libraro ko apne mobile ya desktop par aasani se use karein.',
        'lead' => 'Ghar se collection dekhiye, counter par admission kijiye, chalte-chalte seat swap approve kijiye. Har device par same data.',
        'points' => [['smartphone', 'Koi app install nahi'], ['zap', '4G par bhi fast'], ['monitor-smartphone', 'Ek login, har device']],
        'alts' => ['Mobile par Libraro dashboard', 'Mobile par Libraro learner list', 'Mobile par Libraro seat map'],
    ],

    'ops' => [
        'eyebrow' => 'Roz ke kaam',
        'title' => 'Roz ka har kaam, bas kuch taps mein.',
        'lead' => 'Libraro portal ki asli screens, light aur dark mode mein.',
        'light' => 'Light', 'dark' => 'Dark',
        'tabs' => [
            'profile' => ['Member profile', 'Har learner ki seat, shifts, plan, dues aur contact ek screen par.'],
            'swap' => ['Seat swap', 'Learner ko seconds mein doosri seat par shift karein, fee ka hisaab apne-aap.'],
            'plan' => ['Change plan', 'Live bill summary ke saath plan upgrade ya change karein. Calculator ki zaroorat nahi.'],
            'attend' => ['Learner attendance', 'Har visit ka calendar, padhai ke ghante aur attendance rate.'],
            'wallet' => ['Payments & wallet', 'Learner ne kitna diya, kitna baaki hai aur agli due date.'],
        ],
    ],

    'day' => [
        'eyebrow' => 'Yeh kaise kaam karta hai',
        'title' => 'Aapki library ka ek din.',
        'step' => 'Step', 'prev' => 'Pichhla', 'next' => 'Agla', 'track' => 'Roz ke kaam ke steps',
        'items' => [
            ['Admission', 'Photo aur ID proof ke saath 30 second mein learner jodiye.', '8:00 AM'],
            ['Seat allot karein', 'Live seat map se kisi bhi shift ke liye khaali seat chuniye.', '8:05 AM'],
            ['Fees collect karein', 'Cash, UPI ya online. Receipt WhatsApp par chali jaati hai.', '10:30 AM'],
            ['Attendance lagayein', 'Learners darwaze par QR code scan karte hain. Bas.', 'Poore din'],
            ['Reminders bhejein', 'Dues aur seat expiry ke alerts apne-aap jaate hain.', '6:00 PM'],
            ['Din ka hisaab', 'Aaya paisa, bhari seats aur pending dues ek nazar mein.', '9:00 PM'],
        ],
    ],

    'features' => [
        'eyebrow' => 'Major features',
        'title' => 'Serious library business ke liye bana.',
        'lead' => 'Das powerful tools jo register, sheets aur phone calls ki jagah lete hain.',
        'items' => [
            ['Multi-branch control', 'Har branch ek login se, har ek ki apni seats, plans aur paisa.'],
            ['Live seat & shift map', 'Apni shifts, floors aur seat types, real-time availability ke saath.'],
            ['30 second mein admission', 'Photo, ID proof, plan aur seat ek screen par. Excel se saare learners import karein.'],
            ['Fees, UPI & online payment', 'Cash, UPI ya online. Receipt WhatsApp aur PDF mein jaati hai.'],
            ['Automatic reminders', 'Fee due, expiry aur renewal ke messages jo khud chale jaate hain.'],
            ['QR attendance', 'Darwaze par scan karke check-in, daily aur monthly record ke saath.'],
            ['Staff roles & permissions', 'Har staff ko sirf utna access dein jitna zaroori hai.'],
            ['Reports & expenses', 'Collection, kharche, balance sheet aur profit. Excel mein export.'],
            ['ID cards & receipts', 'Learner ID card ek-ek karke ya bulk mein print karein.'],
            ['Encrypted learner data', 'Mobile number aur email encrypted aur private rehte hain.'],
        ],
        'cta' => 'Plans compare karein',
    ],

    'videos' => [
        'eyebrow' => 'Libraro Shorts',
        'title' => 'Dekhiye. Seekhiye. Library badhaiye.',
        'lead' => 'Library owners ke liye chhote #Mentorship videos. Awaaz ke saath dekhne ke liye kisi bhi video par tap karein.',
        'play' => 'Video chalayein', 'close' => 'Video band karein',
        'prev' => 'Pichhla video', 'next' => 'Agla video',
        'channel' => 'Hamare YouTube channel par aur videos',
        'sub' => 'Library owners ke liye',
        'items' => ['#Mentorship · Episode 6', '#Mentorship · Episode 7', 'Libraro #Mentorship', '#Mentorship · Episode 8'],
    ],

    'benefits' => [
        'eyebrow' => 'Aapke liye kya badlega',
        'title' => 'Zyada kamaai. Kam bhaag-daud.',
        'lead' => 'Libraro par pehle mahine mein owners yeh badlav dekhte hain.',
        'items' => [
            ['Har hafte ghanton ki bachat', 'Raat ko register ginna aur sheets jodna band.'],
            ['Ruka hua paisa wapas', 'Time par reminders se kam dues aur zyada renewals.'],
            ['Kam jhagde', 'Har seat, payment aur check-in receipt ke saath record mein.'],
            ['Har branch nazar mein', 'Bina phone kiye jaaniye har library mein kya ho raha hai.'],
            ['Grow karne ki jagah', 'Agli branch usi system par minutes mein shuru karein.'],
            ['Khush learners', 'Turant receipt, sahi seat aur professional experience.'],
        ],
    ],

    'pricing' => [
        'eyebrow' => 'Plans & pricing',
        'title' => 'Simple plans. Poori value.',
        'lead' => 'Har plan mein free setup help, free library listing aur 7 din mein full refund.',
        'monthly' => 'Monthly', 'yearly' => 'Yearly', 'save' => 'Zyada bachat',
        'popular' => 'Recommended',
        'choose' => 'Chunein',
        'all' => 'Saare :n features dekhein',
        'plus' => ':p ka sab kuch, aur:',
        'key' => 'Key features',
        'per_month' => '/mahina',
        'billed' => 'Har mahine bill',
        'refund' => 'Har plan par 7 din mein full refund',
        'gst' => 'GST alag se lagu.',
        'compare' => 'Saare features compare karein',
        'taglines' => [1 => 'Ek library ko organise karne ke liye', 2 => '2 branch wale growing owners ke liye', 3 => 'Multi-branch library business ke liye'],
        'dialog' => ['eyebrow' => 'Saare features', 'choose' => 'Plan chunein', 'foot' => '7 din mein full refund · GST alag se', 'new' => 'Naya'],
        'js' => ['month' => '/mahina', 'year' => '/saal', 'mo' => '/mo', 'yr' => '/yr', 'billedM' => 'Har mahine bill', 'billedY' => 'Har saal bill', 'save' => 'Aapki bachat', 'included' => 'features included', 'newNote' => '"Naya" wale features is plan mein jude hain'],
    ],

    'compare' => [
        'title' => 'Plans ka side-by-side comparison.',
        'lead' => 'Har feature, har plan. Koi chhupi shart nahi.',
        'expand' => 'Sab kholein', 'collapse' => 'Sab band karein',
        'yes' => 'Included', 'no' => 'Included nahi',
        'groups' => [
            ['Capacity & branches', [
                ['Learner capacity', '100 seats tak', '200 seats tak', 'Unlimited seats'],
                ['Branches', '1 branch', '2 branches', '3+ branches'],
                ['Custom shifts', '3 tak', '6 tak', 'Unlimited'],
                ['Floors & rooms', 'Ek floor', 'Multi-floor', 'Multi-floor'],
            ]],
            ['Seats & admissions', [
                ['Live visual seat map', true, true, true],
                ['QR code digital admission', true, true, true],
                ['Seat swap & day freeze', true, true, true],
                ['Learner ID cards', 'Single print', 'Bulk print', 'Batch print'],
            ]],
            ['Fees & reminders', [
                ['Automatic fee due reminders', 'SMS / add-on', 'Included', 'Priority'],
                ['WhatsApp fee receipts', true, true, true],
                ['Expired & extended learner list', true, true, true],
            ]],
            ['Reports & paisa', [
                ['Collection reports', 'Standard', 'Advanced', 'Full suite'],
                ['Expense manager & balance sheet', true, true, true],
                ['Excel / CSV export', true, true, true],
            ]],
            ['Security & support', [
                ['Learner data privacy', 'Encrypted', 'Encrypted', 'Encrypted'],
                ['Data migration help', 'Khud import', 'Help ke saath', 'Hum karenge'],
                ['Customer support', 'Email', 'Phone & WhatsApp', 'Priority, 24×7'],
                ['Money-back guarantee', '7 din', '7 din', '7 din'],
            ]],
        ],
    ],

    'refund' => [
        'day' => 'din ka refund',
        'title' => '7 din Libraro try kijiye. Pasand na aaye to poora paisa wapas.',
        'text' => 'Apni asli library ke saath koi bhi plan poore hafte chalaiye. Theek na lage to refund maangiye, hum poori amount lauta denge. Na sawal, na form, na baar-baar call.',
        'link' => 'Refund policy padhein',
        'chips' => [['rotate-ccw', 'Full refund'], ['layers', 'Koi bhi plan'], ['badge-check', 'Reason batane ki zaroorat nahi']],
    ],

    'reviews' => [
        'eyebrow' => 'Owners ki baat',
        'title' => 'Library owners ka bharosa.',
        'lead' => 'Libraro par aane ke baad owners kya kehte hain.',
        'more' => 'Aur padhein',
        'prev' => 'Pichhla review', 'next' => 'Agla review',
        'stars' => '5 mein se :n stars',
        'owner' => 'Library owner',
    ],

    'faq' => [
        'title' => 'Aapke sawal, hamare jawab.',
        'items' => [
            ['Kya main ek login se kai libraries sambhal sakta hoon?', 'Haan. Libraro multi-branch owners ke liye bana hai. Har branch ki apni seats, shifts, plans aur paisa hota hai, aur aap ek click mein branch switch kar sakte hain.'],
            ['Setup mein kitna time lagta hai?', 'Zyadatar owners usi din start kar dete hain. Hamari team call par aapke saath seats, shifts aur plans set karti hai aur Excel se learners import karti hai.'],
            ['Kya yeh mere phone par chalega?', 'Haan. Libraro kisi bhi phone, tablet ya laptop ke browser mein chalta hai. Koi app install nahi karna.'],
            ['Kya mere learners ka data safe hai?', 'Haan. Mobile number aur email encrypted rehte hain, aur staff ko sirf wahi dikhta hai jiski aap permission dein.'],
            ['7 din ka refund kaise kaam karta hai?', 'Koi bhi plan 7 din tak use karein. Theek na lage to refund maangiye, hum poori amount lauta denge. Koi sawal nahi.'],
            ['Help kaise milegi?', '+91 81144 79678 par call ya WhatsApp karein, ya support@libraro.in par email karein. Asli log, koi bot nahi.'],
        ],
    ],

    'love' => ['Indian library owners ke liye :heart se banaya gaya.', 'Aapke bharose ke liye shukriya.'],
    'final' => [
        'title' => 'Har branch ek jagah se chalane ke liye ready hain?',
        'lead' => 'Aaj hi apni library jodiye. Hum call par saath mein setup karenge, aur aapko 7 din ke full refund ka bharosa hai.',
        'cta' => 'Apni library jodein',
        'call' => 'Call karein +91 81144 79678',
        'wa' => 'WhatsApp karein',
    ],
],

'_icons' => [
    'pains' => $painIcons,
    'fixes' => $fixIcons,
    'features' => $featureIcons,
    'benefits' => $benefitIcons,
],
];
