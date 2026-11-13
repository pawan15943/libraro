<?php

namespace App\Support;

/**
 * Content of the "How Libraro works" guide — every feature of the library panel, step by step,
 * in English and Hindi. Written to the `how-to-use` table by migration
 * 2026_11_07_000002_refresh_how_to_use_guide (re-run that logic to publish edits).
 *
 * Each entry: cat (HowToUseGuide::categories key), title, title_hi, was (pattern of the old
 * operation_name this replaces — keeps the row id), en / hi (one step per line).
 * Titles keep the words Limo matches on (see LibraroAiService::GUIDE_SYNONYMS).
 */
class HowToUseGuideContent
{
    public static function entries(): array
    {
        return [
            /* ------------------------------ Getting started ------------------------------ */
            ['cat' => 'start', 'title' => 'First-time setup of your library', 'title_hi' => 'लाइब्रेरी का पहली बार सेटअप', 'was' => null,
                'en' => [
                    'Choose your Libraro plan and pay — the panel opens the setup steps one by one.',
                    'Branch: add your branch name, WhatsApp number, address, seats and opening hours.',
                    'Shift: add the shifts you sell (Full Day, First Half, Second Half, Custom…) with their monthly fee.',
                    'Optional: add Floors, more Plans (3 months, 6 months…) and their prices.',
                    'Bring your existing members with Import Data, then start booking seats.',
                ],
                'hi' => [
                    'अपना Libraro प्लान चुनें और भुगतान करें — पैनल एक-एक करके सेटअप के स्टेप खोलता है।',
                    'Branch: ब्रांच का नाम, WhatsApp नंबर, पता, सीटें और खुलने का समय डालें।',
                    'Shift: जो शिफ्ट बेचते हैं (Full Day, First Half, Second Half, Custom…) उनकी मासिक फीस के साथ जोड़ें।',
                    'चाहें तो Floors, और प्लान (3 महीने, 6 महीने…) व उनकी कीमतें जोड़ें।',
                    'Import Data से पुराने सदस्य जोड़ें, फिर सीट बुकिंग शुरू करें।',
                ]],
            ['cat' => 'start', 'title' => 'Dashboard', 'title_hi' => 'डैशबोर्ड', 'was' => '/dashboard/i',
                'en' => [
                    'Overview: active members, seats filled today, seats free now and expired seats.',
                    'Live attendance: who is inside the library right now.',
                    'Money today: collection, other income, expense, refund and dues — tap any figure to see its entries.',
                    'Needs action: collect dues, renew seats in extension and send reminders in one tap.',
                    'Highlights: today\'s birthdays and festivals with a ready WhatsApp wish, online booking requests and recent activity.',
                ],
                'hi' => [
                    'ओवरव्यू: सक्रिय सदस्य, आज भरी सीटें, अभी खाली सीटें और एक्सपायर सीटें।',
                    'लाइव हाजिरी: अभी लाइब्रेरी में कौन है।',
                    'आज का पैसा: कलेक्शन, अन्य आय, खर्च, रिफंड और बकाया — किसी भी आंकड़े पर टैप करके एंट्री देखें।',
                    'Needs action: बकाया लेना, extension वाली सीटें रिन्यू करना और रिमाइंडर — एक टैप में।',
                    'हाइलाइट्स: आज के जन्मदिन व त्योहार (तैयार WhatsApp बधाई के साथ), ऑनलाइन बुकिंग रिक्वेस्ट और हाल की गतिविधि।',
                ]],
            ['cat' => 'start', 'title' => 'Quick bar on the right', 'title_hi' => 'दाईं ओर की क्विक बार', 'was' => null,
                'en' => [
                    'Book seat · Search a member · Seat booking QR · Add expense · Print ID cards · Video tutorials · Feedback · Settings · Limo AI.',
                    'Click the small arrow to hide or show the bar; your choice is remembered.',
                ],
                'hi' => [
                    'सीट बुक करें · सदस्य खोजें · सीट बुकिंग QR · खर्च जोड़ें · ID कार्ड प्रिंट · वीडियो ट्यूटोरियल · फीडबैक · सेटिंग · Limo AI।',
                    'बार छुपाने या दिखाने के लिए छोटे तीर पर क्लिक करें; आपकी पसंद याद रहती है।',
                ]],
            ['cat' => 'start', 'title' => 'Switch branch', 'title_hi' => 'ब्रांच बदलें', 'was' => null,
                'en' => [
                    'Use the branch selector at the top of the panel and pick the branch.',
                    'Every page — dashboard, members, reports and Limo — then shows only that branch.',
                ],
                'hi' => [
                    'पैनल के ऊपर ब्रांच सिलेक्टर से ब्रांच चुनें।',
                    'इसके बाद हर पेज — डैशबोर्ड, सदस्य, रिपोर्ट और Limo — सिर्फ उसी ब्रांच का डेटा दिखाता है।',
                ]],
            ['cat' => 'start', 'title' => 'Search Learner', 'title_hi' => 'सदस्य खोजें', 'was' => '/search learner/i',
                'en' => [
                    'Click Search in the right quick bar (Find a member).',
                    'Type a name, mobile number, member UID or seat number.',
                    'Tick “Include expired & closed” to find old members too.',
                ],
                'hi' => [
                    'दाईं क्विक बार में Search (Find a member) पर क्लिक करें।',
                    'नाम, मोबाइल नंबर, मेंबर UID या सीट नंबर लिखें।',
                    'पुराने सदस्य भी खोजने के लिए “Include expired & closed” चुनें।',
                ]],
            ['cat' => 'start', 'title' => 'Dark mode and language', 'title_hi' => 'डार्क मोड और भाषा', 'was' => null,
                'en' => [
                    'Click the sun / moon button in the top bar to switch between light and dark mode.',
                    'Guides, Limo and many pages have an English / हिंदी switch.',
                ],
                'hi' => [
                    'लाइट और डार्क मोड के लिए ऊपर की पट्टी में सूरज / चांद वाले बटन पर क्लिक करें।',
                    'गाइड, Limo और कई पेज पर English / हिंदी का स्विच है।',
                ]],

            /* ------------------------------ Seat booking ------------------------------ */
            ['cat' => 'booking', 'title' => 'Book Seat', 'title_hi' => 'सीट बुक करें', 'was' => '/book seat/i',
                'en' => [
                    'Click a free seat on the Seat Map, or Book seat in the right quick bar.',
                    'Step 1 — Seat & shift: choose “Assign Seat No” Yes (numbered seat) or No (General seat), pick the shift, plan and start date.',
                    'Step 2 — Details & payment: photo, name, 10-digit mobile, then payment mode and amount.',
                    'Save — the booking-done screen lets you share the confirmation on WhatsApp / SMS and open the receipt.',
                ],
                'hi' => [
                    'Seat Map पर खाली सीट पर क्लिक करें, या दाईं क्विक बार में Book seat दबाएं।',
                    'स्टेप 1 — सीट व शिफ्ट: “Assign Seat No” में Yes (नंबर वाली सीट) या No (General सीट) चुनें, फिर शिफ्ट, प्लान और शुरू की तारीख।',
                    'स्टेप 2 — जानकारी व भुगतान: फोटो, नाम, 10 अंकों का मोबाइल, फिर पेमेंट मोड और राशि।',
                    'सेव करें — Booking done स्क्रीन से WhatsApp / SMS पर पुष्टि भेजें और रसीद खोलें।',
                ]],
            ['cat' => 'booking', 'title' => 'Seat Map', 'title_hi' => 'सीट मैप', 'was' => '/seat map/i',
                'en' => [
                    'Library → Seat Map shows every seat; filter by shift or floor.',
                    'Colours show Booked, Free, Payment due, Extension due, Expiring soon, Future booked, Non-expiring and Birthday today.',
                    'Click a free seat to book it; click a booked seat to open its details (WhatsApp, documents, transactions).',
                    'General seats (no number) are listed in their own section.',
                ],
                'hi' => [
                    'Library → Seat Map में हर सीट दिखती है; शिफ्ट या फ्लोर से फ़िल्टर करें।',
                    'रंग बताते हैं: Booked, Free, Payment due, Extension due, Expiring soon, Future booked, Non-expiring और आज जन्मदिन।',
                    'खाली सीट पर क्लिक करके बुक करें; भरी सीट पर क्लिक करके उसकी जानकारी खोलें (WhatsApp, दस्तावेज़, ट्रांज़ैक्शन)।',
                    'General सीटें (बिना नंबर) अलग सेक्शन में दिखती हैं।',
                ]],
            ['cat' => 'booking', 'title' => 'General seat vs numbered seat', 'title_hi' => 'General सीट और नंबर वाली सीट', 'was' => null,
                'en' => [
                    'Numbered seat (Assign Seat No → Yes): the learner gets a fixed seat, e.g. Seat 12.',
                    'General seat (Assign Seat No → No): the learner sits on any free seat and does not block a number.',
                    'Use General seats when all numbered seats are full.',
                ],
                'hi' => [
                    'नंबर वाली सीट (Assign Seat No → Yes): छात्र को एक तय सीट मिलती है, जैसे सीट 12।',
                    'General सीट (Assign Seat No → No): छात्र किसी भी खाली सीट पर बैठता है और कोई नंबर नहीं रुकता।',
                    'जब सारी नंबर वाली सीटें भरी हों तो General सीट दें।',
                ]],
            ['cat' => 'booking', 'title' => 'Book more than one shift (extra shifts)', 'title_hi' => 'एक से ज़्यादा शिफ्ट बुक करें', 'was' => null,
                'en' => [
                    'On a numbered seat, use “Add extra fixed shifts” in Step 1 of the booking form.',
                    'All shifts go into one booking and one payment; overlapping shifts are greyed out.',
                    'On the seat map the extra shift shows as “· Extra shift”.',
                ],
                'hi' => [
                    'नंबर वाली सीट पर बुकिंग फॉर्म के स्टेप 1 में “Add extra fixed shifts” का उपयोग करें।',
                    'सारी शिफ्ट एक ही बुकिंग और एक भुगतान में जाती हैं; overlap वाली शिफ्ट बंद दिखती हैं।',
                    'सीट मैप पर अतिरिक्त शिफ्ट “· Extra shift” के रूप में दिखती है।',
                ]],
            ['cat' => 'booking', 'title' => 'Future Bookings', 'title_hi' => 'आगे की बुकिंग (Future Booking)', 'was' => '/future/i',
                'en' => [
                    'Book as usual but set “Starts on” to a future date — it becomes a future booking.',
                    'Find them in Learner List → Future bookings, and in gold on the seat map.',
                    'A future booking has its own menu: View, Edit, Transactions, Activity, Receipt, Delete booking.',
                ],
                'hi' => [
                    'सामान्य तरीके से बुक करें पर “Starts on” में आगे की तारीख डालें — यह future booking बन जाती है।',
                    'इन्हें Learner List → Future bookings में और सीट मैप पर सुनहरे रंग में देखें।',
                    'Future booking का अपना मेन्यू है: View, Edit, Transactions, Activity, Receipt, Delete booking।',
                ]],
            ['cat' => 'booking', 'title' => 'Custom Plan & custom dates', 'title_hi' => 'कस्टम प्लान व कस्टम तारीखें', 'was' => '/custom plan/i',
                'en' => [
                    'In the plan list choose “Custom dates” and pick the “Valid till” date.',
                    'The price is worked out as 1-month price ÷ 30 × days — you can still change it.',
                    'For permanent custom plans and shifts, create them in Setup → Plans / Plan Types / Plan Prices.',
                ],
                'hi' => [
                    'प्लान लिस्ट में “Custom dates” चुनें और “Valid till” तारीख डालें।',
                    'कीमत 1 महीने की कीमत ÷ 30 × दिन से बनती है — आप इसे बदल भी सकते हैं।',
                    'हमेशा के लिए कस्टम प्लान व शिफ्ट Setup → Plans / Plan Types / Plan Prices में बनाएं।',
                ]],
            ['cat' => 'booking', 'title' => 'Non-expiring seat and VIP shift', 'title_hi' => 'कभी खत्म न होने वाली सीट और VIP शिफ्ट', 'was' => null,
                'en' => [
                    'In Step 2 set “No expiry seat” to Yes — the seat keeps running and you collect fees later (e.g. a learner who will pay in parts).',
                    'A VIP shift is free and never expires; it has no locker charge.',
                    'Find these members in Learner List → Non-expiry.',
                ],
                'hi' => [
                    'स्टेप 2 में “No expiry seat” को Yes करें — सीट चलती रहती है और फीस बाद में ले सकते हैं (जैसे जो छात्र किस्तों में देगा)।',
                    'VIP शिफ्ट फ्री होती है और कभी खत्म नहीं होती; इसमें लॉकर चार्ज नहीं लगता।',
                    'ऐसे सदस्य Learner List → Non-expiry में मिलते हैं।',
                ]],
            ['cat' => 'booking', 'title' => 'Assign Locker', 'title_hi' => 'लॉकर दें', 'was' => '/loca?ker/i',
                'en' => [
                    'In the booking or renew form open “Need a Locker?” and enter the locker number.',
                    'The fee comes from your branch locker fee per month and is charged for every month of the plan.',
                    'Set the locker fee in Setup → Branch → Locker per month.',
                ],
                'hi' => [
                    'बुकिंग या रिन्यू फॉर्म में “Need a Locker?” खोलें और लॉकर नंबर डालें।',
                    'फीस ब्रांच की मासिक लॉकर फीस से आती है और प्लान के हर महीने के लिए लगती है।',
                    'लॉकर फीस Setup → Branch → Locker per month में सेट करें।',
                ]],
            ['cat' => 'booking', 'title' => 'Apply Discount', 'title_hi' => 'छूट (डिस्काउंट) दें', 'was' => '/discount/i',
                'en' => [
                    'In the booking or renew form, enter a discount as an Amount or a Percentage.',
                    'A percentage cannot be more than 100% and an amount cannot be more than the plan price.',
                    'The discount shows in the learner\'s transactions and receipt.',
                ],
                'hi' => [
                    'बुकिंग या रिन्यू फॉर्म में छूट Amount या Percentage में डालें।',
                    'प्रतिशत 100% से ज़्यादा और राशि प्लान की कीमत से ज़्यादा नहीं हो सकती।',
                    'छूट छात्र के ट्रांज़ैक्शन और रसीद में दिखती है।',
                ]],

            /* ------------------------------ Learner operations ------------------------------ */
            ['cat' => 'learner', 'title' => 'Learner List (Members list)', 'title_hi' => 'सदस्य सूची (Learner List)', 'was' => null,
                'en' => [
                    'Library → Learner List. Views: By shift, General, Non-expiry.',
                    'Tabs: All current, Active, About to expire, In extension, Expired, Closed, Deleted, All history, Future bookings.',
                    'Filter by plan, payment status or seat; switch between card and list layout.',
                    'Each card\'s main button changes by state: Restore, Collect ₹X, Renew, Reactivate or View details.',
                ],
                'hi' => [
                    'Library → Learner List। व्यू: By shift, General, Non-expiry।',
                    'टैब: All current, Active, About to expire, In extension, Expired, Closed, Deleted, All history, Future bookings।',
                    'प्लान, भुगतान स्थिति या सीट से फ़िल्टर करें; कार्ड और लिस्ट लेआउट बदलें।',
                    'हर कार्ड का मुख्य बटन स्थिति के अनुसार बदलता है: Restore, Collect ₹X, Renew, Reactivate या View details।',
                ]],
            ['cat' => 'learner', 'title' => 'Re-New Seat', 'title_hi' => 'सीट रिन्यू करें', 'was' => '/re-?new seat/i',
                'en' => [
                    'Renew appears 5 days before the plan ends and stays through the extension (grace) days.',
                    'Open the learner → Renew, choose plan / shift, locker or discount if needed, and the payment.',
                    'Renewing early puts the new plan “in queue” — it starts the day after the current plan ends.',
                    'Renew is hidden while a plan is frozen; after the extension days use Reactivate.',
                ],
                'hi' => [
                    'Renew प्लान खत्म होने से 5 दिन पहले आता है और extension (grace) दिनों तक रहता है।',
                    'छात्र खोलें → Renew, प्लान / शिफ्ट, ज़रूरत हो तो लॉकर या छूट, और भुगतान चुनें।',
                    'जल्दी रिन्यू करने पर नया प्लान “queue” में रहता है — मौजूदा प्लान खत्म होने के अगले दिन शुरू होता है।',
                    'प्लान फ्रीज़ हो तो Renew छुपा रहता है; extension दिनों के बाद Reactivate करें।',
                ]],
            ['cat' => 'learner', 'title' => 'Undo a renewal', 'title_hi' => 'गलत रिन्यूअल हटाएं', 'was' => null,
                'en' => [
                    'Open Settings → Delete Renewed Trxn.',
                    'Search the learner and delete the renewal — the previous booking comes back as it was.',
                ],
                'hi' => [
                    'Settings → Delete Renewed Trxn खोलें।',
                    'छात्र खोजें और रिन्यूअल हटाएं — पिछली बुकिंग पहले जैसी वापस आ जाती है।',
                ]],
            ['cat' => 'learner', 'title' => 'Swap Seat (change seat number)', 'title_hi' => 'सीट बदलें (Swap Seat)', 'was' => '/swap/i',
                'en' => [
                    'Open the learner → Swap and pick a free seat in the same shift.',
                    'Works while the plan or its extension days are running and the plan is not frozen.',
                ],
                'hi' => [
                    'छात्र खोलें → Swap और उसी शिफ्ट में खाली सीट चुनें।',
                    'यह तब होता है जब प्लान या उसके extension दिन चल रहे हों और प्लान फ्रीज़ न हो।',
                ]],
            ['cat' => 'learner', 'title' => 'Change Plan & Plan Type (Shift)', 'title_hi' => 'प्लान व शिफ्ट बदलें (Change Plan)', 'was' => '/change plan/i',
                'en' => [
                    'Open the learner → Change Plan to move to another plan or shift.',
                    'Available only within 7 days of the plan start date and not while frozen.',
                    'Any price difference is settled on the same page.',
                ],
                'hi' => [
                    'दूसरे प्लान या शिफ्ट में ले जाने के लिए छात्र खोलें → Change Plan।',
                    'यह सिर्फ प्लान शुरू होने की तारीख से 7 दिन तक और फ्रीज़ न होने पर मिलता है।',
                    'कीमत का अंतर उसी पेज पर बराबर किया जाता है।',
                ]],
            ['cat' => 'learner', 'title' => 'Upgrade Plan & Plan Type (Shift)', 'title_hi' => 'प्लान अपग्रेड करें', 'was' => '/upgrade/i',
                'en' => [
                    'Upgrade opens 5 days before the plan ends (and in the extension days).',
                    'Open the learner → Upgrade and choose the bigger plan or longer shift.',
                    'In the first 7 days of a plan use Change Plan instead.',
                ],
                'hi' => [
                    'Upgrade प्लान खत्म होने से 5 दिन पहले (और extension दिनों में) खुलता है।',
                    'छात्र खोलें → Upgrade और बड़ा प्लान या लंबी शिफ्ट चुनें।',
                    'प्लान के पहले 7 दिनों में इसकी जगह Change Plan करें।',
                ]],
            ['cat' => 'learner', 'title' => 'Edit Learner\'s Profile', 'title_hi' => 'सदस्य की जानकारी बदलें', 'was' => '/edit learner/i',
                'en' => [
                    'Open the learner → Edit to change name, mobile, photo, ID proof and other details.',
                    'Edit is hidden while the plan is frozen.',
                    'Plan dates and price are changed from Edit Plan (see below).',
                ],
                'hi' => [
                    'नाम, मोबाइल, फोटो, ID प्रूफ और बाकी जानकारी बदलने के लिए छात्र खोलें → Edit।',
                    'प्लान फ्रीज़ हो तो Edit छुपा रहता है।',
                    'प्लान की तारीखें और कीमत Edit Plan से बदलती हैं (नीचे देखें)।',
                ]],
            ['cat' => 'learner', 'title' => 'Edit Plan & End Date', 'title_hi' => 'प्लान व खत्म की तारीख बदलें', 'was' => '/end date/i',
                'en' => [
                    'Open the learner → Edit Plan to change the plan dates and price.',
                    'Allowed only within 3 days of the plan start date.',
                    'Changing the end date turns the booking into a custom plan. After 3 days, add days with Gift Days.',
                ],
                'hi' => [
                    'प्लान की तारीखें और कीमत बदलने के लिए छात्र खोलें → Edit Plan।',
                    'यह सिर्फ प्लान शुरू होने के 3 दिन तक होता है।',
                    'खत्म की तारीख बदलने पर बुकिंग कस्टम प्लान बन जाती है। 3 दिन बाद दिन जोड़ने के लिए Gift Days दें।',
                ]],
            ['cat' => 'learner', 'title' => 'Freeze / Unfreeze', 'title_hi' => 'प्लान फ्रीज़ / अनफ्रीज़ करें', 'was' => '/freeze/i',
                'en' => [
                    'Open the learner → Freeze when the learner will be away; the plan pauses.',
                    'Freeze works only on an active plan, before its end date (not in the extension days).',
                    'Unfreeze adds the frozen days to the end date. Most options hide while a plan is frozen.',
                ],
                'hi' => [
                    'छात्र कुछ दिन नहीं आएगा तो छात्र खोलें → Freeze; प्लान रुक जाता है।',
                    'Freeze सिर्फ चालू प्लान में, उसकी खत्म की तारीख से पहले होता है (extension दिनों में नहीं)।',
                    'Unfreeze करने पर फ्रीज़ के दिन खत्म की तारीख में जुड़ जाते हैं। फ्रीज़ के दौरान ज़्यादातर ऑप्शन छुप जाते हैं।',
                ]],
            ['cat' => 'learner', 'title' => 'Gift Days', 'title_hi' => 'गिफ्ट डेज़ (फ्री दिन)', 'was' => '/gift/i',
                'en' => [
                    'Open the learner → Gift and enter the number of free days.',
                    'The days are added to the learner\'s last booking (also a queued renewal).',
                    'Not allowed while the plan is frozen.',
                ],
                'hi' => [
                    'छात्र खोलें → Gift और फ्री दिनों की संख्या डालें।',
                    'दिन छात्र की आखिरी बुकिंग में जुड़ते हैं (queue वाले रिन्यूअल में भी)।',
                    'प्लान फ्रीज़ हो तो नहीं दे सकते।',
                ]],
            ['cat' => 'learner', 'title' => 'Close Seat (with or without refund)', 'title_hi' => 'सीट क्लोज़ करें', 'was' => '/close seat/i',
                'en' => [
                    'Open the learner → Close to end the seat today, with or without a refund.',
                    'Settle any pending or extra amount first — the seat closes only when the money is balanced.',
                    'Closed learners move to the Closed tab and can be reactivated later.',
                ],
                'hi' => [
                    'आज ही सीट खत्म करने के लिए छात्र खोलें → Close, रिफंड के साथ या बिना।',
                    'पहले बकाया या अतिरिक्त राशि बराबर करें — हिसाब बराबर होने पर ही सीट क्लोज़ होती है।',
                    'Closed छात्र Closed टैब में जाते हैं और बाद में Reactivate हो सकते हैं।',
                ]],
            ['cat' => 'learner', 'title' => 'Delete Seat, Restore and Delete for good', 'title_hi' => 'सीट डिलीट, रिस्टोर और हमेशा के लिए हटाना', 'was' => '/delete seat/i',
                'en' => [
                    'Open the learner → Delete (with or without refund) after settling the money; the learner moves to the Deleted tab.',
                    'Deleted by mistake? Open Learner List → Deleted and click Restore. Restore is not allowed if the plan has already ended — use Reactivate.',
                    '“Delete for good” works only on deleted or closed learners; choose to delete payments and their revenue, or keep the revenue.',
                ],
                'hi' => [
                    'हिसाब बराबर करके छात्र खोलें → Delete (रिफंड के साथ या बिना); छात्र Deleted टैब में चला जाता है।',
                    'गलती से डिलीट हुआ? Learner List → Deleted खोलें और Restore दबाएं। प्लान खत्म हो चुका हो तो Restore नहीं होगा — Reactivate करें।',
                    '“Delete for good” सिर्फ deleted या closed छात्रों पर होता है; भुगतान और उनकी कमाई दोनों हटाएं, या कमाई रखें।',
                ]],
            ['cat' => 'learner', 'title' => 'Reactivate Existing Learner', 'title_hi' => 'पुराने सदस्य को दोबारा चालू करें (Reactivate)', 'was' => '/reactive/i',
                'en' => [
                    'Reactivate shows for expired, closed and deleted learners.',
                    'Open the learner → Reactivate seat, choose the new plan, seat and payment — no need to add their details again.',
                    'The new start date must be on or after the last plan\'s end date.',
                ],
                'hi' => [
                    'Reactivate एक्सपायर, क्लोज़ और डिलीट छात्रों में दिखता है।',
                    'छात्र खोलें → Reactivate seat, नया प्लान, सीट और भुगतान चुनें — जानकारी दोबारा भरने की ज़रूरत नहीं।',
                    'नई शुरू की तारीख पिछले प्लान की खत्म की तारीख या उसके बाद की होनी चाहिए।',
                ]],
            ['cat' => 'learner', 'title' => 'Learner activity and history', 'title_hi' => 'सदस्य की गतिविधि और हिस्ट्री', 'was' => null,
                'en' => [
                    'Open the learner → Activity to see every renewal, swap, plan change and edit, with who did it.',
                    'Profile / View shows the full booking with links to Activity and Transactions.',
                    'Reports → Activity log shows the same for the whole branch.',
                ],
                'hi' => [
                    'हर रिन्यूअल, सीट बदलना, प्लान बदलना और एडिट — किसने किया, यह देखने के लिए छात्र खोलें → Activity।',
                    'Profile / View में पूरी बुकिंग, Activity और Transactions के लिंक के साथ।',
                    'पूरी ब्रांच के लिए Reports → Activity log देखें।',
                ]],

            /* ------------------------------ Payments ------------------------------ */
            ['cat' => 'payments', 'title' => 'Payment Options: Online, Offline and Pay Later', 'title_hi' => 'भुगतान के तरीके: ऑनलाइन, ऑफलाइन और पे-लेटर', 'was' => '/payment options/i',
                'en' => [
                    'Online = UPI / bank, Offline = cash, Pay Later = the learner pays afterwards.',
                    'Pay Later and part payments need a “Balance due on” date so the learner shows as overdue on time.',
                    'Pay-later money is not counted in collection until it is collected.',
                ],
                'hi' => [
                    'Online = UPI / बैंक, Offline = नकद, Pay Later = छात्र बाद में देगा।',
                    'Pay Later और आंशिक भुगतान में “Balance due on” तारीख डालें, ताकि छात्र समय पर overdue दिखे।',
                    'Pay-later का पैसा मिलने तक कलेक्शन में नहीं गिना जाता।',
                ]],
            ['cat' => 'payments', 'title' => 'Get Partial Payment', 'title_hi' => 'आंशिक भुगतान लें', 'was' => '/partial/i',
                'en' => [
                    'In the booking form, enter a smaller amount in “Paying now”.',
                    'Set “Balance due on” — the rest shows as dues and on the Dues to collect report.',
                ],
                'hi' => [
                    'बुकिंग फॉर्म में “Paying now” में कम राशि डालें।',
                    '“Balance due on” तारीख डालें — बाकी पैसा बकाया में और Dues to collect रिपोर्ट में दिखेगा।',
                ]],
            ['cat' => 'payments', 'title' => 'Collect dues (Settlement)', 'title_hi' => 'बकाया लें (Settlement)', 'was' => null,
                'en' => [
                    'Click “Collect ₹X” on the member card, or the learner → Settlement.',
                    'Take the payment now, keep it for later, or waive it off.',
                    'Dashboard → Needs action and Reports → Dues to collect list everyone who has to pay.',
                ],
                'hi' => [
                    'सदस्य कार्ड पर “Collect ₹X” दबाएं, या छात्र → Settlement।',
                    'अभी भुगतान लें, बाद के लिए रखें, या माफ़ करें।',
                    'Dashboard → Needs action और Reports → Dues to collect में सभी बकाया वाले दिखते हैं।',
                ]],
            ['cat' => 'payments', 'title' => 'Take miscellaneous Payment (Other Pay)', 'title_hi' => 'अन्य भुगतान लें (टोकन, रिफंड, मिसलेनियस)', 'was' => '/miscellaneous/i',
                'en' => [
                    'Open the learner → Other Pay.',
                    'Choose Token Money, Refund amount to pay, or Miscellaneous fee (registration, ID card, deposit…), and the mode.',
                    'Other payments get their own receipt.',
                ],
                'hi' => [
                    'छात्र खोलें → Other Pay।',
                    'Token Money, Refund amount to pay, या Miscellaneous fee (रजिस्ट्रेशन, ID कार्ड, डिपॉज़िट…) और मोड चुनें।',
                    'अन्य भुगतान की अपनी अलग रसीद बनती है।',
                ]],
            ['cat' => 'payments', 'title' => 'Transactions', 'title_hi' => 'ट्रांज़ैक्शन', 'was' => null,
                'en' => [
                    'Open the learner → Transactions to see payments, all transactions and the next due.',
                    'Each transaction can be edited or deleted, and the change is logged with who made it.',
                ],
                'hi' => [
                    'भुगतान, सभी ट्रांज़ैक्शन और अगली देय तारीख देखने के लिए छात्र खोलें → Transactions।',
                    'हर ट्रांज़ैक्शन एडिट या डिलीट हो सकता है, और बदलाव किसने किया यह दर्ज होता है।',
                ]],
            ['cat' => 'payments', 'title' => 'Download Payment Receipt', 'title_hi' => 'भुगतान की रसीद डाउनलोड करें', 'was' => '/receipt/i',
                'en' => [
                    'Use the receipt button on the member card, or the learner → Receipt to send a receipt link on WhatsApp.',
                    'For a renewal receipt, open the learner → Transactions and download it from that entry.',
                    'Your branch name, address and logo print on the receipt.',
                ],
                'hi' => [
                    'सदस्य कार्ड पर रसीद बटन दबाएं, या छात्र → Receipt से WhatsApp पर रसीद का लिंक भेजें।',
                    'रिन्यूअल की रसीद के लिए छात्र खोलें → Transactions और उसी एंट्री से डाउनलोड करें।',
                    'रसीद पर आपकी ब्रांच का नाम, पता और लोगो छपता है।',
                ]],
            ['cat' => 'payments', 'title' => 'Add Library Expenses', 'title_hi' => 'लाइब्रेरी का खर्च जोड़ें', 'was' => '/add library expenses/i',
                'en' => [
                    'Click Add expense in the right quick bar.',
                    'Enter amount, expense name (or Other), date, how it was paid and a note.',
                    'Expenses show in Cash book and Profit & loss; export the list any time.',
                ],
                'hi' => [
                    'दाईं क्विक बार में Add expense पर क्लिक करें।',
                    'राशि, खर्च का नाम (या Other), तारीख, कैसे दिया और नोट डालें।',
                    'खर्च Cash book और Profit & loss में दिखते हैं; सूची कभी भी एक्सपोर्ट करें।',
                ]],

            /* ------------------------------ Attendance ------------------------------ */
            ['cat' => 'attendance', 'title' => 'Take & View Attendance', 'title_hi' => 'हाजिरी लें और देखें', 'was' => '/attendance/i',
                'en' => [
                    'Attendance → Via QR / Scanner opens the desk kiosk: learners scan the live QR (it refreshes every 30 seconds) or show their ID card to the camera.',
                    'Print the fixed QR poster for the wall and the student guidelines PDF from the same page.',
                    'Attendance → Manual: mark In / Out by hand for any day, or undo a punch.',
                    'Attendance → Summary and Reports → Attendance show daily punches, monthly hours and who has stopped coming.',
                ],
                'hi' => [
                    'Attendance → Via QR / Scanner से डेस्क कियोस्क खुलता है: छात्र लाइव QR स्कैन करते हैं (हर 30 सेकंड में बदलता है) या कैमरे को ID कार्ड दिखाते हैं।',
                    'दीवार के लिए स्थायी QR पोस्टर और छात्रों के निर्देश PDF उसी पेज से प्रिंट करें।',
                    'Attendance → Manual: किसी भी दिन के लिए हाथ से In / Out लगाएं, या पंच हटाएं।',
                    'Attendance → Summary और Reports → Attendance में रोज़ के पंच, महीने के घंटे और कौन आना बंद कर चुका है, दिखता है।',
                ]],
            ['cat' => 'attendance', 'title' => 'Attendance App for learners', 'title_hi' => 'छात्रों के लिए हाजिरी ऐप', 'was' => null,
                'en' => [
                    'Share the Attendance App link (learner → Share, or the Seat booking QR window).',
                    'The learner verifies once with their mobile number plus date of birth, email or member UID; the phone is remembered for 30 days.',
                    'Then they scan the QR to mark IN / OUT, and can see their plan and validity.',
                ],
                'hi' => [
                    'हाजिरी ऐप का लिंक भेजें (छात्र → Share, या सीट बुकिंग QR विंडो से)।',
                    'छात्र एक बार मोबाइल नंबर और जन्मतिथि, ईमेल या मेंबर UID से वेरिफाई करता है; फोन 30 दिन याद रहता है।',
                    'फिर वह QR स्कैन करके IN / OUT लगाता है और अपना प्लान व वैधता देख सकता है।',
                ]],

            /* ------------------------------ WhatsApp & notifications ------------------------------ */
            ['cat' => 'messages', 'title' => 'Free whatsApp reminder send', 'title_hi' => 'फ्री WhatsApp रिमाइंडर भेजें', 'was' => '/free whatsapp/i',
                'en' => [
                    'Open the learner → Reminder: it opens WhatsApp with a ready message you can edit and send — free of cost.',
                    'Plan reminders show from 5 days before expiry and in the extension days; the overdue-payment reminder shows when a payment is past its due date.',
                    'The learner\'s “Send reminders via” setting (WhatsApp / Text / Both / No) decides the channel.',
                ],
                'hi' => [
                    'छात्र खोलें → Reminder: यह WhatsApp में तैयार मैसेज खोलता है, जिसे बदलकर भेज सकते हैं — बिल्कुल फ्री।',
                    'प्लान रिमाइंडर एक्सपायरी से 5 दिन पहले से और extension दिनों में दिखता है; overdue भुगतान रिमाइंडर तब दिखता है जब भुगतान की तारीख निकल जाए।',
                    'छात्र की “Send reminders via” सेटिंग (WhatsApp / Text / Both / No) से चैनल तय होता है।',
                ]],
            ['cat' => 'messages', 'title' => 'Text and WhatsApp Notifications (paid, automatic)', 'title_hi' => 'अपने आप जाने वाले WhatsApp व SMS (paid)', 'was' => '/text and whatsapp/i',
                'en' => [
                    'Buy WhatsApp (WABA) or text credits at /library/notifications/subscription — credits have no expiry.',
                    'At /library/notifications/settings choose, per operation, WhatsApp and/or text, the template and when to send (1–5 days before or after).',
                    'See what was sent at /library/notification/dashboard. Without credits, reminders use free WhatsApp instead.',
                ],
                'hi' => [
                    '/library/notifications/subscription पर WhatsApp (WABA) या SMS क्रेडिट खरीदें — क्रेडिट की कोई समय-सीमा नहीं।',
                    '/library/notifications/settings में हर ऑपरेशन के लिए WhatsApp और/या SMS, टेम्पलेट और कब भेजना है (1–5 दिन पहले या बाद) चुनें।',
                    'क्या भेजा गया, /library/notification/dashboard पर देखें। क्रेडिट न हो तो रिमाइंडर फ्री WhatsApp से जाते हैं।',
                ]],
            ['cat' => 'messages', 'title' => 'Message Templates', 'title_hi' => 'मैसेज टेम्पलेट', 'was' => null,
                'en' => [
                    'Library → Message Templates: edit the Renewal reminder, Pending payment reminder and Birthday wishes.',
                    'Tap a variable (learner_name, seat_no, plan_end_date, due_date, pending_amount, library_name) to insert it, and check the live preview.',
                ],
                'hi' => [
                    'Library → Message Templates: रिन्यूअल रिमाइंडर, बकाया भुगतान रिमाइंडर और जन्मदिन बधाई बदलें।',
                    'कोई वेरिएबल (learner_name, seat_no, plan_end_date, due_date, pending_amount, library_name) टैप करके जोड़ें और लाइव प्रीव्यू देखें।',
                ]],
            ['cat' => 'messages', 'title' => 'Birthday and festival wishes', 'title_hi' => 'जन्मदिन और त्योहार की बधाई', 'was' => null,
                'en' => [
                    'The dashboard highlights show today\'s birthdays and festivals.',
                    'Tap “Wish them” / “Send wishes” to open WhatsApp with a ready greeting.',
                ],
                'hi' => [
                    'डैशबोर्ड हाइलाइट्स में आज के जन्मदिन और त्योहार दिखते हैं।',
                    'तैयार बधाई के साथ WhatsApp खोलने के लिए “Wish them” / “Send wishes” दबाएं।',
                ]],

            /* ------------------------------ Masters ------------------------------ */
            ['cat' => 'masters', 'title' => 'What are masters in Libraro?', 'title_hi' => 'Libraro में मास्टर क्या हैं?', 'was' => '/masters in/i',
                'en' => [
                    'Masters are the basic settings everything else uses: Branch, Floors, Shifts (Plan Types), Plans, Plan Prices, Expense names and Exam names.',
                    'Set them once; bookings, reports and Limo use them everywhere.',
                    'A master that has active learners cannot be removed — deactivate it instead.',
                ],
                'hi' => [
                    'मास्टर वे बुनियादी सेटिंग हैं जिन पर सब चलता है: Branch, Floors, Shifts (Plan Types), Plans, Plan Prices, खर्च के नाम और परीक्षा के नाम।',
                    'एक बार सेट करें; बुकिंग, रिपोर्ट और Limo इन्हें हर जगह उपयोग करते हैं।',
                    'जिस मास्टर में सक्रिय छात्र हैं वह हटाया नहीं जा सकता — उसे बंद (deactivate) करें।',
                ]],
            ['cat' => 'masters', 'title' => 'Branch Master', 'title_hi' => 'ब्रांच मास्टर', 'was' => '/branch master/i',
                'en' => [
                    'Setup → Branch. Each branch card shows Seats, Open daily hours, Grace (extension) days, Locker per month, Token money and Shifts — tap a tile to edit it.',
                    'Seats can only be added, never removed. Open hours are whole hours, at least 10. Grace days 0–30.',
                    'Edit branch for name, WhatsApp, UPI ID (needed for QR booking), address, timings, amenities, map and up to 4 photos.',
                    'Download or print the branch QR from the card. Each branch works like a separate library.',
                ],
                'hi' => [
                    'Setup → Branch। हर ब्रांच कार्ड में Seats, रोज़ खुलने के घंटे, Grace (extension) दिन, मासिक लॉकर फीस, टोकन मनी और शिफ्ट दिखती हैं — बदलने के लिए टाइल पर टैप करें।',
                    'सीटें सिर्फ बढ़ाई जा सकती हैं, घटाई नहीं। खुलने के घंटे पूरे घंटों में, कम से कम 10। Grace दिन 0–30।',
                    'Edit branch से नाम, WhatsApp, UPI ID (QR बुकिंग के लिए ज़रूरी), पता, समय, सुविधाएं, मैप और 4 तक फोटो।',
                    'कार्ड से ब्रांच QR डाउनलोड या प्रिंट करें। हर ब्रांच एक अलग लाइब्रेरी की तरह चलती है।',
                ]],
            ['cat' => 'masters', 'title' => 'Floor Master', 'title_hi' => 'फ्लोर मास्टर', 'was' => '/floor master/i',
                'en' => [
                    'Setup → Floors: add floor number, name and seat range (from / to).',
                    'Seat numbers restart on every floor; all floors together cannot have more seats than the branch.',
                ],
                'hi' => [
                    'Setup → Floors: फ्लोर नंबर, नाम और सीट रेंज (से / तक) जोड़ें।',
                    'हर फ्लोर पर सीट नंबर फिर से शुरू होते हैं; सारे फ्लोर मिलाकर ब्रांच की सीटों से ज़्यादा नहीं हो सकते।',
                ]],
            ['cat' => 'masters', 'title' => 'Plan Type / Shift Master', 'title_hi' => 'शिफ्ट (Plan Type) मास्टर', 'was' => '/plan type \/ shift/i',
                'en' => [
                    'Setup → Shift (Shifts & fees). First set the library open and close time.',
                    'Basic shifts: Full Day, First Half, Second Half, Custom time; All Day and Full Night for 24-hour libraries; Reserved and VIP cover the full library hours.',
                    'Each shift has a monthly fee. A shift with learners on it cannot be removed.',
                ],
                'hi' => [
                    'Setup → Shift (Shifts & fees)। पहले लाइब्रेरी के खुलने और बंद होने का समय सेट करें।',
                    'बेसिक शिफ्ट: Full Day, First Half, Second Half, Custom समय; 24 घंटे वाली लाइब्रेरी के लिए All Day और Full Night; Reserved और VIP पूरे समय की होती हैं।',
                    'हर शिफ्ट की मासिक फीस होती है। जिस शिफ्ट में छात्र हैं वह हटाई नहीं जा सकती।',
                ]],
            ['cat' => 'masters', 'title' => 'Advanced shifts: fixed hours, flexible hours and direct booking', 'title_hi' => 'एडवांस शिफ्ट: फिक्स्ड घंटे, फ्लेक्सिबल घंटे और डायरेक्ट बुकिंग', 'was' => null,
                'en' => [
                    'Fixed hours: the learner buys N hours a day and you pick their start time at booking (numbered seats).',
                    'Flexible hours: the learner comes any time in library hours and stays N hours (general seat); attendance counts overtime.',
                    'Direct booking: you type the start, end and fee at booking.',
                    'Their hours per day cannot be more than the library is open. Renew keeps the same shift; QR booking offers fixed shifts only.',
                ],
                'hi' => [
                    'Fixed hours: छात्र रोज़ N घंटे लेता है और बुकिंग के समय आप उसका शुरू का समय चुनते हैं (नंबर वाली सीट)।',
                    'Flexible hours: छात्र लाइब्रेरी के समय में कभी भी आकर N घंटे रुकता है (General सीट); हाजिरी में ज़्यादा समय गिना जाता है।',
                    'Direct booking: बुकिंग के समय शुरू, खत्म का समय और फीस आप लिखते हैं।',
                    'इनके रोज़ के घंटे लाइब्रेरी के खुलने के घंटों से ज़्यादा नहीं हो सकते। रिन्यू में वही शिफ्ट रहती है; QR बुकिंग में सिर्फ फिक्स्ड शिफ्ट मिलती हैं।',
                ]],
            ['cat' => 'masters', 'title' => 'Plan Master', 'title_hi' => 'प्लान मास्टर', 'was' => '/^\s*plan master/i',
                'en' => [
                    'Setup → Plans: add plans like 1 Month, 3 Months, 6 Months.',
                    'Choose Day / Week / Month / Year and the number; billing can be by days or by calendar month.',
                    'Prices for longer plans are suggested from the 1-month price.',
                ],
                'hi' => [
                    'Setup → Plans: 1 महीना, 3 महीने, 6 महीने जैसे प्लान जोड़ें।',
                    'Day / Week / Month / Year और संख्या चुनें; बिलिंग दिनों से या कैलेंडर महीने से हो सकती है।',
                    'लंबे प्लान की कीमतें 1 महीने की कीमत से सुझाई जाती हैं।',
                ]],
            ['cat' => 'masters', 'title' => 'Plan Price Master', 'title_hi' => 'प्लान प्राइस मास्टर', 'was' => '/plan price/i',
                'en' => [
                    'Setup → Plan Prices: set the price of every plan for every shift.',
                    'A shift shows in booking only when it has a price.',
                ],
                'hi' => [
                    'Setup → Plan Prices: हर शिफ्ट के लिए हर प्लान की कीमत सेट करें।',
                    'बुकिंग में शिफ्ट तभी दिखती है जब उसकी कीमत सेट हो।',
                ]],
            ['cat' => 'masters', 'title' => 'Expense Master', 'title_hi' => 'खर्च के नाम (Expense Master)', 'was' => '/expense master/i',
                'en' => ['Setup → Expense Names: add names like Rent, Electricity, Internet, Salary to use when adding expenses.'],
                'hi' => ['Setup → Expense Names: किराया, बिजली, इंटरनेट, सैलरी जैसे नाम जोड़ें, जो खर्च जोड़ते समय काम आएं।']],
            ['cat' => 'masters', 'title' => 'Exam Master', 'title_hi' => 'परीक्षा के नाम (Exam Master)', 'was' => '/exam/i',
                'en' => ['Setup → Exam Names: add the exams your learners prepare for; pick them in the booking form and in quizzes.'],
                'hi' => ['Setup → Exam Names: छात्र जिन परीक्षाओं की तैयारी करते हैं वे जोड़ें; बुकिंग फॉर्म और क्विज़ में इन्हें चुनें।']],
            ['cat' => 'masters', 'title' => 'Show / Hide Options', 'title_hi' => 'ऑप्शन दिखाएं / छुपाएं', 'was' => null,
                'en' => [
                    'Settings → Show / Hide Options, saved per branch and applied at once. Switching one on hides that item.',
                    'Booking form fields: email, date of birth, locker, discount, photo, ID proof, exam, father name, alternate mobile, address, remark.',
                    'Options: Upgrade, Change Plan, ID card, Close, Edit, Gift days, Freeze, plan-wise counts. Dashboard: monthly counts, bookings & activity.',
                ],
                'hi' => [
                    'Settings → Show / Hide Options, हर ब्रांच के लिए अलग और तुरंत लागू। स्विच चालू करने पर वह चीज़ छुप जाती है।',
                    'बुकिंग फॉर्म के फील्ड: ईमेल, जन्मतिथि, लॉकर, छूट, फोटो, ID प्रूफ, परीक्षा, पिता का नाम, दूसरा मोबाइल, पता, टिप्पणी।',
                    'ऑप्शन: Upgrade, Change Plan, ID कार्ड, Close, Edit, Gift days, Freeze, प्लान-वार गिनती। डैशबोर्ड: मासिक गिनती, बुकिंग व गतिविधि।',
                ]],
            ['cat' => 'masters', 'title' => 'Import Learner', 'title_hi' => 'सदस्य इम्पोर्ट करें', 'was' => '/import learner/i',
                'en' => [
                    'Library → Import Data: get the sample file, fill it, choose the file, then Check & import.',
                    'Plan, shift and price must match your setup; one row per shift; dates DD-MM-YYYY; mobile 10 digits.',
                    'Use the real start date for expired or extended learners. Download the rows to fix, correct them and import again.',
                ],
                'hi' => [
                    'Library → Import Data: सैंपल फाइल लें, भरें, फाइल चुनें, फिर Check & import।',
                    'प्लान, शिफ्ट और कीमत आपके सेटअप से मेल खानी चाहिए; हर शिफ्ट की एक लाइन; तारीख DD-MM-YYYY; मोबाइल 10 अंक।',
                    'एक्सपायर या extension वाले छात्रों की असली शुरू की तारीख डालें। गलती वाली लाइनें डाउनलोड करें, ठीक करें और फिर इम्पोर्ट करें।',
                ]],
            ['cat' => 'masters', 'title' => 'Setup Library via CSV', 'title_hi' => 'CSV से लाइब्रेरी सेटअप', 'was' => '/csv/i',
                'en' => ['Settings → Setup via CSV: add branches, hours, seats and fees in one file instead of one by one.'],
                'hi' => ['Settings → Setup via CSV: ब्रांच, घंटे, सीटें और फीस एक-एक करके नहीं, एक ही फाइल से जोड़ें।']],

            /* ------------------------------ Reports ------------------------------ */
            ['cat' => 'reports', 'title' => 'Analytics : Reports', 'title_hi' => 'रिपोर्ट', 'was' => '/reports/i',
                'en' => [
                    'Reports → Overview shows this month\'s collection, net, dues, occupancy and what needs your attention.',
                    'Money: Collections, Cash book, Profit & loss, Dues to collect. Members: Renewals (expiring, in grace, expired), Members, Growth & retention.',
                    'Operations: Seat occupancy, Attendance and the Activity log. Every report can be exported.',
                ],
                'hi' => [
                    'Reports → Overview में इस महीने का कलेक्शन, शुद्ध आय, बकाया, ऑक्यूपेंसी और जिन पर ध्यान देना है, दिखते हैं।',
                    'पैसा: Collections, Cash book, Profit & loss, Dues to collect। सदस्य: Renewals (खत्म होने वाले, grace में, एक्सपायर), Members, Growth & retention।',
                    'ऑपरेशन: Seat occupancy, Attendance और Activity log। हर रिपोर्ट एक्सपोर्ट हो सकती है।',
                ]],
            ['cat' => 'reports', 'title' => 'Export learner data (CSV / PDF)', 'title_hi' => 'सदस्यों का डेटा एक्सपोर्ट करें (CSV / PDF)', 'was' => null,
                'en' => [
                    'Learner List → Export: “All data in CSV” downloads every member of the branch.',
                    '“Current list as PDF” prints the list with the filters you applied.',
                ],
                'hi' => [
                    'Learner List → Export: “All data in CSV” से ब्रांच के सभी सदस्य डाउनलोड होते हैं।',
                    '“Current list as PDF” लगाए गए फ़िल्टर के साथ सूची प्रिंट करता है।',
                ]],
            ['cat' => 'reports', 'title' => 'Generate ID Card', 'title_hi' => 'ID कार्ड बनाएं', 'was' => '/id card/i',
                'en' => [
                    'One card: open the learner → ID Card.',
                    'Many cards: Print ID cards in the right quick bar — pick active members, choose both sides or front only, and print.',
                    'Cards are made for members whose plan (or extension) is running.',
                ],
                'hi' => [
                    'एक कार्ड: छात्र खोलें → ID Card।',
                    'कई कार्ड: दाईं क्विक बार में Print ID cards — सक्रिय सदस्य चुनें, दोनों तरफ या सिर्फ आगे चुनें और प्रिंट करें।',
                    'कार्ड उन्हीं सदस्यों के बनते हैं जिनका प्लान (या extension) चल रहा है।',
                ]],

            /* ------------------------------ Online ------------------------------ */
            ['cat' => 'online', 'title' => 'Get Online Seat Bookings (QR)', 'title_hi' => 'QR से ऑनलाइन सीट बुकिंग', 'was' => '/online seat bookings/i',
                'en' => [
                    'Add your UPI ID in Edit branch, then open Seat booking QR in the right quick bar.',
                    'Download the poster, share on WhatsApp or copy the link. Learners book or renew a seat and pay by UPI (with a screenshot) or at the branch.',
                    'Approve requests from Dashboard → Bookings & activity: verify, allot a seat and confirm.',
                ],
                'hi' => [
                    'Edit branch में अपनी UPI ID डालें, फिर दाईं क्विक बार में Seat booking QR खोलें।',
                    'पोस्टर डाउनलोड करें, WhatsApp पर भेजें या लिंक कॉपी करें। छात्र सीट बुक या रिन्यू करके UPI से (स्क्रीनशॉट के साथ) या ब्रांच पर भुगतान करते हैं।',
                    'Dashboard → Bookings & activity से रिक्वेस्ट मंज़ूर करें: जांचें, सीट दें और पुष्टि करें।',
                ]],
            ['cat' => 'online', 'title' => 'Demo Inquiry (trial bookings)', 'title_hi' => 'डेमो इन्क्वायरी (ट्रायल बुकिंग)', 'was' => null,
                'en' => [
                    'Library → Demo Inquiry → Add inquiry to hold a seat for a trial; the learner can pay now or later.',
                    'Approve, view, WhatsApp or delete each inquiry; filter by Unpaid or Today.',
                ],
                'hi' => [
                    'ट्रायल के लिए सीट रोकने को Library → Demo Inquiry → Add inquiry; छात्र अभी या बाद में भुगतान कर सकता है।',
                    'हर इन्क्वायरी को मंज़ूर करें, देखें, WhatsApp करें या हटाएं; Unpaid या Today से फ़िल्टर करें।',
                ]],
            ['cat' => 'online', 'title' => 'Separate Website Listing with SEO', 'title_hi' => 'वेबसाइट लिस्टिंग और SEO', 'was' => '/website/i',
                'en' => [
                    'Each branch gets its own public page, also found in “Find my library”.',
                    'Its content comes from Edit branch (about, days, amenities, photos, map).',
                    'Account → Setting: favicon, colour, page title (about 60 characters) and description (150–160 characters).',
                ],
                'hi' => [
                    'हर ब्रांच का अपना पब्लिक पेज बनता है, जो “Find my library” में भी मिलता है।',
                    'इसकी जानकारी Edit branch से आती है (परिचय, दिन, सुविधाएं, फोटो, मैप)।',
                    'Account → Setting: favicon, रंग, पेज टाइटल (लगभग 60 अक्षर) और विवरण (150–160 अक्षर)।',
                ]],
            ['cat' => 'online', 'title' => 'Library Enquiry', 'title_hi' => 'लाइब्रेरी पूछताछ (Enquiry)', 'was' => null,
                'en' => ['Library Enquiry lists seat enquiries from your public page; filter by Today or This month, see the most-asked shift and call them back.'],
                'hi' => ['Library Enquiry में आपके पब्लिक पेज से आई सीट पूछताछ दिखती है; Today या This month से फ़िल्टर करें, सबसे ज़्यादा पूछी गई शिफ्ट देखें और कॉल करें।']],

            /* ------------------------------ Learner app, chat & study ------------------------------ */
            ['cat' => 'learners_app', 'title' => 'Learner login (learner portal)', 'title_hi' => 'छात्र लॉगिन (लर्नर पोर्टल)', 'was' => null,
                'en' => [
                    'Learners log in at /learner/login with their member number (UID); the first password is their mobile number.',
                    'They see their subscription, attendance, payments and ID card, chat with the library, take quizzes, and send requests, complaints and feedback.',
                ],
                'hi' => [
                    'छात्र /learner/login पर अपने मेंबर नंबर (UID) से लॉगिन करते हैं; पहला पासवर्ड उनका मोबाइल नंबर है।',
                    'वे अपना सब्सक्रिप्शन, हाजिरी, भुगतान और ID कार्ड देखते हैं, लाइब्रेरी से चैट करते हैं, क्विज़ देते हैं, और रिक्वेस्ट, शिकायत व फीडबैक भेजते हैं।',
                ]],
            ['cat' => 'learners_app', 'title' => 'Student Chat', 'title_hi' => 'स्टूडेंट चैट', 'was' => null,
                'en' => [
                    'Student Chat (sidebar, owner only): start a chat with any student; messages are end-to-end encrypted.',
                    'The student must open Chat in their portal once before you can message them. Files up to 10 MB.',
                    'Use Key backup to read old messages on another device.',
                ],
                'hi' => [
                    'Student Chat (साइडबार, सिर्फ मालिक): किसी भी छात्र से चैट शुरू करें; मैसेज end-to-end encrypted रहते हैं।',
                    'मैसेज भेजने से पहले छात्र को एक बार अपने पोर्टल में Chat खोलना होगा। 10 MB तक की फाइल।',
                    'दूसरे डिवाइस पर पुराने मैसेज पढ़ने के लिए Key backup का उपयोग करें।',
                ]],
            ['cat' => 'learners_app', 'title' => 'Student Quiz', 'title_hi' => 'स्टूडेंट क्विज़', 'was' => null,
                'en' => [
                    'Settings → Student Quiz → New quiz: name, exam (optional), time limit and up to 4 subjects / 40 questions.',
                    'Each question needs options A and B (C, D optional), the correct answer and an optional explanation.',
                    'Turn on “Show this quiz to students” to publish it in their portal.',
                ],
                'hi' => [
                    'Settings → Student Quiz → New quiz: नाम, परीक्षा (वैकल्पिक), समय-सीमा और 4 विषय / 40 सवाल तक।',
                    'हर सवाल में A और B विकल्प (C, D वैकल्पिक), सही जवाब और चाहें तो समझाना।',
                    'छात्रों के पोर्टल में दिखाने के लिए “Show this quiz to students” चालू करें।',
                ]],

            /* ------------------------------ Limo AI ------------------------------ */
            ['cat' => 'limo', 'title' => 'Limo AI assistant — ask about your library', 'title_hi' => 'Limo AI सहायक — लाइब्रेरी के बारे में पूछें', 'was' => null,
                'en' => [
                    'Click Limo at the bottom of the right quick bar and type in English, Hinglish or हिंदी (or tap the mic and speak).',
                    'Ask anything: today\'s collection, dues list, vacant seats, who is on seat 12, expiring plans, profit, this month vs last month, any learner by name.',
                    'Answers come with charts, tables and buttons — WhatsApp reminder, Renew, Collect — and can be downloaded as PDF or Excel.',
                    'Use ⤢ for full screen, 👍 / 👎 to rate an answer, 🔊 to listen, and 🗑 to clear the chat. Limo also shows what needs attention today.',
                ],
                'hi' => [
                    'दाईं क्विक बार में नीचे Limo पर क्लिक करें और English, Hinglish या हिंदी में लिखें (या माइक दबाकर बोलें)।',
                    'कुछ भी पूछें: आज का कलेक्शन, बकाया सूची, खाली सीटें, सीट 12 पर कौन है, खत्म होने वाले प्लान, मुनाफा, इस महीने बनाम पिछला महीना, नाम से कोई भी छात्र।',
                    'जवाब में चार्ट, टेबल और बटन आते हैं — WhatsApp रिमाइंडर, Renew, Collect — और PDF या Excel में डाउनलोड हो सकते हैं।',
                    '⤢ से पूरी स्क्रीन, 👍 / 👎 से जवाब को रेटिंग, 🔊 से सुनें और 🗑 से चैट मिटाएं। Limo आज ध्यान देने वाली बातें भी दिखाता है।',
                ]],
            ['cat' => 'limo', 'title' => 'Limo Support — solve a problem step by step', 'title_hi' => 'Limo सपोर्ट — समस्या स्टेप-बाय-स्टेप हल करें', 'was' => null,
                'en' => [
                    'Open Limo → Support and describe the problem in your words, e.g. “Rahul ka renew button nahi dikh raha”, or tap a common problem.',
                    'Limo checks your data (the learner\'s plan dates, freeze, queued renewal, your settings and permissions) and tells you the exact reason and the fix.',
                    'Tap “Still not working” for more checks; if it is still not solved, Limo sends a ready summary to the Libraro team on WhatsApp.',
                ],
                'hi' => [
                    'Limo → Support खोलें और अपनी समस्या अपने शब्दों में लिखें, जैसे “राहुल का रिन्यू बटन नहीं दिख रहा”, या कोई आम समस्या चुनें।',
                    'Limo आपका डेटा जांचता है (छात्र की प्लान तारीखें, फ्रीज़, queue वाला रिन्यूअल, आपकी सेटिंग और अनुमतियां) और सही वजह व हल बताता है।',
                    'और जांच के लिए “अभी भी नहीं हो रहा” दबाएं; फिर भी हल न हो तो Limo तैयार सारांश WhatsApp पर Libraro टीम को भेजता है।',
                ]],

            /* ------------------------------ Account ------------------------------ */
            ['cat' => 'account', 'title' => 'Users Master (staff and permissions)', 'title_hi' => 'स्टाफ और उनकी अनुमतियां', 'was' => '/users master/i',
                'en' => [
                    'Staff Management → Users → Add staff: name, WhatsApp mobile, email (used to log in), role, branches and password (6+ characters).',
                    'Then set what each person may do (permissions). Staff can never get a permission your Libraro plan does not include.',
                    'Turn staff on or off any time, and share their login on WhatsApp. Staff log in at Library login.',
                ],
                'hi' => [
                    'Staff Management → Users → Add staff: नाम, WhatsApp मोबाइल, ईमेल (लॉगिन के लिए), रोल, ब्रांच और पासवर्ड (6+ अक्षर)।',
                    'फिर हर व्यक्ति क्या कर सकता है (अनुमतियां) सेट करें। स्टाफ को वह अनुमति कभी नहीं मिलती जो आपके Libraro प्लान में नहीं है।',
                    'स्टाफ को कभी भी चालू या बंद करें और उनका लॉगिन WhatsApp पर भेजें। स्टाफ Library login पर लॉगिन करते हैं।',
                ]],
            ['cat' => 'account', 'title' => 'My plan, renew and upgrade Libraro', 'title_hi' => 'मेरा Libraro प्लान, रिन्यू और अपग्रेड', 'was' => null,
                'en' => [
                    'Subscription → Plan shows your current plan, validity and what is included; Transactions has your bills and invoices.',
                    'Renew / Upgrade open 5 days before your plan ends (a pop-up and the plan pill in the header remind you).',
                    'If the plan ends and the grace days are over, only the Dashboard stays open until you renew.',
                ],
                'hi' => [
                    'Subscription → Plan में आपका मौजूदा प्लान, वैधता और क्या-क्या शामिल है; Transactions में आपके बिल और इनवॉइस।',
                    'Renew / Upgrade आपका प्लान खत्म होने से 5 दिन पहले खुलते हैं (पॉप-अप और हेडर का प्लान पिल याद दिलाते हैं)।',
                    'प्लान खत्म होकर grace दिन भी बीत जाएं तो रिन्यू करने तक सिर्फ डैशबोर्ड खुला रहता है।',
                ]],
            ['cat' => 'account', 'title' => 'Referral rewards', 'title_hi' => 'रेफरल रिवॉर्ड', 'was' => null,
                'en' => [
                    'Account → Referral: share your code or link with other library owners.',
                    'You get 10 points when a referred owner signs up and pays (up to 10 referrals).',
                    'At 30 points, redeem 30 free days on your Libraro plan.',
                ],
                'hi' => [
                    'Account → Referral: अपना कोड या लिंक दूसरे लाइब्रेरी मालिकों को भेजें।',
                    'रेफर किया मालिक जुड़कर भुगतान करे तो 10 पॉइंट मिलते हैं (10 रेफरल तक)।',
                    '30 पॉइंट पर अपने Libraro प्लान में 30 फ्री दिन लें।',
                ]],
            ['cat' => 'account', 'title' => 'Profile, password and settings', 'title_hi' => 'प्रोफाइल, पासवर्ड और सेटिंग', 'was' => null,
                'en' => [
                    'Account → Profile: your owner name (printed on receipts). Name, email and WhatsApp are verified — contact support to change them.',
                    'Change password from the profile menu at the top right; forgot it? Use “Forgot password” on the login page.',
                    'Account → Setting: website favicon, colour, title and description.',
                ],
                'hi' => [
                    'Account → Profile: मालिक का नाम (रसीद पर छपता है)। नाम, ईमेल और WhatsApp वेरिफाइड हैं — बदलने के लिए सपोर्ट से संपर्क करें।',
                    'पासवर्ड ऊपर दाईं ओर प्रोफाइल मेन्यू से बदलें; भूल गए? लॉगिन पेज पर “Forgot password” दबाएं।',
                    'Account → Setting: वेबसाइट का favicon, रंग, टाइटल और विवरण।',
                ]],
            ['cat' => 'account', 'title' => 'Premium Operation : End to end Data Security', 'title_hi' => 'डेटा सुरक्षा', 'was' => '/security/i',
                'en' => [
                    'Each library sees only its own data, and each branch only its own members.',
                    'Learner mobile numbers and emails are stored encrypted; student chat is end-to-end encrypted.',
                    'Give every staff member their own login and only the permissions they need — every change is logged.',
                ],
                'hi' => [
                    'हर लाइब्रेरी सिर्फ अपना डेटा देखती है, और हर ब्रांच सिर्फ अपने सदस्य।',
                    'छात्रों के मोबाइल नंबर और ईमेल encrypted रखे जाते हैं; स्टूडेंट चैट end-to-end encrypted है।',
                    'हर स्टाफ को अपना लॉगिन और सिर्फ ज़रूरी अनुमतियां दें — हर बदलाव दर्ज होता है।',
                ]],
            ['cat' => 'account', 'title' => 'Video Tutorial, feedback and help', 'title_hi' => 'वीडियो ट्यूटोरियल, फीडबैक और मदद', 'was' => '/video/i',
                'en' => [
                    'Video tutorials (right quick bar): searchable videos for every feature.',
                    'Give feedback (right quick bar): rate Libraro, describe the issue and attach a screenshot (up to 2 MB).',
                    'Need help now? Open Limo → Support, or call / WhatsApp Libraro support at +91 81144 79678.',
                ],
                'hi' => [
                    'वीडियो ट्यूटोरियल (दाईं क्विक बार): हर फ़ीचर के खोजने लायक वीडियो।',
                    'फीडबैक दें (दाईं क्विक बार): Libraro को रेटिंग दें, समस्या लिखें और स्क्रीनशॉट (2 MB तक) लगाएं।',
                    'अभी मदद चाहिए? Limo → Support खोलें, या Libraro सपोर्ट को +91 81144 79678 पर कॉल / WhatsApp करें।',
                ]],
        ];
    }
}
