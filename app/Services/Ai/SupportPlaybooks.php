<?php

namespace App\Services\Ai;

/**
 * Limo Support — every known problem a library owner may hit, with how to recognise it,
 * which data check finds the cause, and the fix in English and Hindi.
 *
 * Rules follow the panel's behaviour (see the `guidelines` table and the learner action code):
 * Edit profile 3 days after booking · Change plan first 7 days · Renew / Upgrade / reminders from
 * 5 days before expiry through the extension days (default 5) · Freeze & Gift days only on an
 * active plan · Reactivate only for expired / deleted / closed · Restore in the Deleted list.
 *
 * Fields: cat, icon, keys (phrases, any language), title [en, hi], needs ('learner'|null),
 * check (SupportChecks method), perm (staff permission that hides the option), steps / more
 * ([[en, hi], ...]), why [en, hi], links ([[route, en, hi, 'learner'?], ...]), related (ids).
 */
class SupportPlaybooks
{
    /** key => [icon, English, Hindi] */
    public static function categories(): array
    {
        return [
            'booking' => ['fa-chair', 'Seat booking', 'सीट बुकिंग'],
            'plan' => ['fa-arrows-rotate', 'Renew & plan change', 'रिन्यू व प्लान बदलना'],
            'learner' => ['fa-user-pen', 'Learner profile & status', 'छात्र प्रोफाइल व स्थिति'],
            'payment' => ['fa-indian-rupee-sign', 'Payments & dues', 'भुगतान व बकाया'],
            'attendance' => ['fa-qrcode', 'Attendance & QR', 'हाजिरी व QR'],
            'messages' => ['fa-brands fa-whatsapp', 'WhatsApp & SMS', 'WhatsApp व SMS'],
            'documents' => ['fa-id-card', 'ID card & receipt', 'ID कार्ड व रसीद'],
            'access' => ['fa-user-lock', 'Login & staff access', 'लॉगिन व स्टाफ एक्सेस'],
            'setup' => ['fa-sliders', 'Plans, shifts & seats setup', 'प्लान, शिफ्ट व सीट सेटअप'],
            'online' => ['fa-globe', 'Online booking & website', 'ऑनलाइन बुकिंग व वेबसाइट'],
            'data' => ['fa-file-import', 'Import, reports & data', 'इम्पोर्ट, रिपोर्ट व डेटा'],
            'account' => ['fa-crown', 'Libraro subscription', 'Libraro सब्सक्रिप्शन'],
        ];
    }

    public static function all(): array
    {
        return [
            /* ------------------------------ Seat booking ------------------------------ */
            'seat_not_booking' => [
                'cat' => 'booking', 'icon' => 'fa-chair', 'needs' => null, 'check' => 'checkSeatBooking', 'perm' => 'Book Seat',
                'keys' => ['seat not booking', 'seat is not getting booked', 'cannot book seat', 'can t book seat', 'unable to book', 'booking not working', 'booking error', 'seat book nahi', 'seat book nahi ho rahi', 'seat book nhi', 'booking nahi ho rahi', 'seat allot nahi', 'सीट बुक नहीं', 'बुकिंग नहीं हो रही', 'सीट अलॉट नहीं'],
                'title' => ['Seat is not getting booked', 'सीट बुक नहीं हो रही'],
                'steps' => [
                    ['Check the seat is free in the shift you are booking — the same seat can be taken in an overlapping shift, or already booked for a future date.', 'देखें कि जिस शिफ्ट में बुक कर रहे हैं उसमें सीट खाली है — वही सीट overlap वाली शिफ्ट में, या आगे की तारीख के लिए पहले से बुक हो सकती है।'],
                    ['A seat can hold shifts only up to your daily library hours — “Seat hours limit exceeded” means the shifts on that seat already use those hours.', 'एक सीट पर सिर्फ लाइब्रेरी के रोज़ के घंटों तक की शिफ्ट आ सकती हैं — “Seat hours limit exceeded” का मतलब है उस सीट की शिफ्ट पहले से वे घंटे ले चुकी हैं।'],
                    ['Fill every required field (name, mobile, plan, shift, start date) — the form will not save if one is missing.', 'हर ज़रूरी फील्ड भरें (नाम, मोबाइल, प्लान, शिफ्ट, शुरू की तारीख) — कोई छूटा तो फॉर्म सेव नहीं होगा।'],
                    ['If the shift you want is not in the list, its price is not set — add it in Plan Price master.', 'अगर चाही हुई शिफ्ट लिस्ट में नहीं है तो उसकी कीमत सेट नहीं है — Plan Price मास्टर में जोड़ें।'],
                    ['No numbered seat free? Book a General seat (Assign Seat No → No) so the learner can still join.', 'कोई नंबर वाली सीट खाली नहीं? General सीट बुक करें (Assign Seat No → No), ताकि छात्र फिर भी जुड़ सके।'],
                ],
                'more' => [
                    ['If the mobile number is already used by another learner, open that learner instead of adding a new one.', 'अगर मोबाइल नंबर पहले से किसी छात्र का है, तो नया जोड़ने के बजाय उसी छात्र को खोलें।'],
                    ['Refresh the page (Ctrl + Shift + R) and try once more — an old page can keep stale seat data.', 'पेज रिफ्रेश करें (Ctrl + Shift + R) और फिर कोशिश करें — पुराना पेज पुरानी सीट जानकारी रख सकता है।'],
                    ['Check your Libraro plan is active — booking is locked when the subscription has expired.', 'देखें कि आपका Libraro प्लान चालू है — सब्सक्रिप्शन खत्म होने पर बुकिंग बंद हो जाती है।'],
                ],
                'links' => [['seats', 'Open seat map', 'सीट मैप खोलें']],
                'related' => ['no_vacant_seat', 'shift_missing', 'subscription_expired'],
            ],
            'no_vacant_seat' => [
                'cat' => 'booking', 'icon' => 'fa-chair', 'needs' => null, 'check' => 'checkVacantSeats', 'perm' => null,
                'keys' => ['no vacant seat', 'no seat available', 'seats full', 'all seats full', 'seat available nahi', 'seat khali nahi', 'koi seat khali nahi', 'seat nahi dikh rahi', 'कोई सीट खाली नहीं', 'सीट उपलब्ध नहीं', 'सीटें फुल'],
                'title' => ['No vacant seat is showing', 'कोई खाली सीट नहीं दिख रही'],
                'steps' => [
                    ['A seat shows as booked if any active learner holds it in an overlapping shift — try a different shift for the same seat.', 'कोई सक्रिय छात्र overlap वाली शिफ्ट में सीट रखता है तो वह भरी दिखती है — उसी सीट के लिए दूसरी शिफ्ट आज़माएं।'],
                    ['Expired learners stay on their seat during the extension days — renew or close them to free the seat.', 'एक्सपायर छात्र extension के दिनों में अपनी सीट पर बने रहते हैं — सीट खाली करने के लिए उन्हें रिन्यू या क्लोज़ करें।'],
                    ['If you added seats, update the total in Branch → seats / Floors so the new numbers appear.', 'अगर नई सीटें लगाई हैं, तो Branch → seats / Floors में कुल सीटें बदलें ताकि नए नंबर दिखें।'],
                ],
                'more' => [
                    ['Book a General seat when every numbered seat is taken.', 'सभी नंबर वाली सीटें भरी हों तो General सीट बुक करें।'],
                    ['Use Future Booking to reserve a seat that frees up later.', 'बाद में खाली होने वाली सीट के लिए Future Booking का उपयोग करें।'],
                ],
                'links' => [['seats', 'Open seat map', 'सीट मैप खोलें']],
                'related' => ['seat_not_booking', 'seat_count_wrong'],
            ],

            /* ------------------------------ Renew & plan ------------------------------ */
            'renew_missing' => [
                'cat' => 'plan', 'icon' => 'fa-arrows-rotate', 'needs' => 'learner', 'check' => 'checkRenew', 'perm' => 'Renew Seat',
                'keys' => ['renew button', 'renew option', 'renew not showing', 'renew not visible', 'cannot renew', 'can t renew', 'unable to renew', 'renew nahi', 'renew nahi ho raha', 'renew ka button', 'renew ka option', 'renew nahi dikh', 'रिन्यू का बटन', 'रिन्यू नहीं', 'रिन्यू नहीं दिख', 'रिन्यू का ऑप्शन'],
                'title' => ['Renew button is not showing', 'रिन्यू का बटन नहीं दिख रहा'],
                'steps' => [
                    ['Renew appears 5 days before the plan ends and stays through the extension days after it (the number of extension days is your branch setting).', 'Renew प्लान खत्म होने से 5 दिन पहले आता है और उसके बाद के extension दिनों तक रहता है (extension के दिन आपकी ब्रांच सेटिंग से तय होते हैं)।'],
                    ['Renew is hidden while the plan is frozen — Unfreeze first.', 'प्लान फ्रीज़ हो तो Renew छुपा रहता है — पहले Unfreeze करें।'],
                    ['If the learner already renewed early, the new plan waits as “Plan in Queue” — no second renew is needed.', 'अगर छात्र पहले ही रिन्यू कर चुका है, तो नया प्लान “Plan in Queue” में रहता है — दोबारा रिन्यू की ज़रूरत नहीं।'],
                    ['After the extension days are over, use Reactivate instead of Renew.', 'Extension के दिन खत्म होने के बाद Renew की जगह Reactivate करें।'],
                ],
                'more' => [
                    ['Open the learner from Learner List → View Details and check the plan end date shown there.', 'Learner List → View Details से छात्र खोलें और वहां दिखी प्लान खत्म की तारीख देखें।'],
                    ['Make sure you are in the right branch (top of the panel) — learners of another branch do not show here.', 'देखें कि आप सही ब्रांच में हैं (पैनल के ऊपर) — दूसरी ब्रांच के छात्र यहां नहीं दिखते।'],
                ],
                'links' => [['learner.renew.plan', 'Open renew page', 'रिन्यू पेज खोलें', 'learner']],
                'related' => ['plan_in_queue', 'reactivate_missing', 'upgrade_missing'],
            ],
            'plan_in_queue' => [
                'cat' => 'plan', 'icon' => 'fa-list-ol', 'needs' => 'learner', 'check' => 'checkQueue', 'perm' => null,
                'keys' => ['plan in queue', 'in queue', 'queue', 'queued plan', 'plan queue me', 'क्यू', 'प्लान इन क्यू'],
                'title' => ['It says “Plan in Queue”', '“Plan in Queue” लिखा आ रहा है'],
                'steps' => [
                    ['This is normal: the learner renewed before the current plan ended.', 'यह सामान्य है: छात्र ने मौजूदा प्लान खत्म होने से पहले रिन्यू कर दिया।'],
                    ['The new plan starts on its own the day after the current plan ends — you do not need to do anything.', 'मौजूदा प्लान खत्म होने के अगले दिन नया प्लान अपने आप शुरू हो जाता है — आपको कुछ नहीं करना है।'],
                ],
                'more' => [
                    ['If the queued plan was added by mistake, delete that renewal from the learner\'s Transaction / renew-delete page.', 'अगर क्यू वाला प्लान गलती से जुड़ा है, तो छात्र के Transaction / renew-delete पेज से वह रिन्यूअल हटाएं।'],
                ],
                'links' => [['create.renew.delete.index', 'Renew delete page', 'रिन्यू डिलीट पेज']],
                'related' => ['renew_missing'],
            ],
            'upgrade_missing' => [
                'cat' => 'plan', 'icon' => 'fa-arrow-up', 'needs' => 'learner', 'check' => 'checkUpgrade', 'perm' => 'Upgrade Seat Plan', 'toggle' => 13,
                'keys' => ['upgrade button', 'upgrade option', 'upgrade not showing', 'cannot upgrade', 'upgrade nahi', 'upgrade nahi dikh', 'upgrade ka option', 'अपग्रेड नहीं', 'अपग्रेड का ऑप्शन'],
                'title' => ['Upgrade plan option is not showing', 'Upgrade प्लान का ऑप्शन नहीं दिख रहा'],
                'steps' => [
                    ['Upgrade appears 5 days before the plan ends and stays through the extension days.', 'Upgrade प्लान खत्म होने से 5 दिन पहले आता है और extension दिनों तक रहता है।'],
                    ['Within the first 7 days after booking, use Change Plan instead.', 'बुकिंग के पहले 7 दिन में इसकी जगह Change Plan का उपयोग करें।'],
                ],
                'more' => [['Check the plan you want to move to has a price for that shift (Plan Price master).', 'देखें कि जिस प्लान में ले जाना है उसकी उस शिफ्ट की कीमत सेट है (Plan Price मास्टर)।']],
                'links' => [['learners.upgrade', 'Open upgrade page', 'अपग्रेड पेज खोलें', 'learner']],
                'related' => ['change_plan_missing', 'renew_missing'],
            ],
            'change_plan_missing' => [
                'cat' => 'plan', 'icon' => 'fa-shuffle', 'needs' => 'learner', 'check' => 'checkChangePlan', 'perm' => 'Change Plan', 'toggle' => 14,
                'keys' => ['change plan', 'change plan not showing', 'change shift', 'shift change nahi', 'plan change nahi', 'plan badal nahi', 'shift badal nahi', 'प्लान बदल नहीं', 'शिफ्ट बदल नहीं', 'चेंज प्लान'],
                'title' => ['Change plan / shift option is not showing', 'प्लान / शिफ्ट बदलने का ऑप्शन नहीं दिख रहा'],
                'steps' => [
                    ['Change Plan shows only for 7 days from the plan start date, and not while the plan is frozen.', 'Change Plan सिर्फ प्लान शुरू होने की तारीख से 7 दिन तक दिखता है, और फ्रीज़ के दौरान नहीं।'],
                    ['After 7 days, wait for the renewal window and use Upgrade or Renew with the new plan / shift.', '7 दिन के बाद रिन्यूअल समय का इंतज़ार करें और नए प्लान / शिफ्ट के साथ Upgrade या Renew करें।'],
                ],
                'more' => [['To move the learner to another seat now, use Swap Seat — it works any time during an active plan.', 'छात्र को अभी दूसरी सीट पर ले जाने के लिए Swap Seat करें — यह चालू प्लान में कभी भी होता है।']],
                'related' => ['upgrade_missing', 'swap_seat'],
            ],
            'wrong_end_date' => [
                'cat' => 'plan', 'icon' => 'fa-calendar-xmark', 'needs' => 'learner', 'check' => 'checkEndDate', 'perm' => 'Edit Seat', 'toggle' => 17,
                'keys' => ['wrong end date', 'end date wrong', 'expiry date wrong', 'change end date', 'plan end date galat', 'expiry galat', 'end date badalni', 'तारीख गलत', 'एंड डेट गलत', 'समाप्ति तिथि गलत'],
                'title' => ['Plan end date is wrong', 'प्लान खत्म होने की तारीख गलत है'],
                'steps' => [
                    ['The end date is worked out from the start date and the plan length — fix the start date in Edit plan (allowed within 3 days of the start).', 'खत्म की तारीख शुरू की तारीख और प्लान की अवधि से बनती है — Edit plan में शुरू की तारीख ठीक करें (शुरू होने के 3 दिन तक)।'],
                    ['If days were added by Gift Days or Unfreeze, the end date moves forward on purpose.', 'अगर Gift Days या Unfreeze से दिन जुड़े हैं, तो तारीख जानबूझकर आगे खिसकती है।'],
                    ['After 3 days, add missing days with Gift Days.', '3 दिन के बाद, छूटे दिन Gift Days से जोड़ें।'],
                ],
                'more' => [['Check the plan length (month days) in Plan master — a wrong plan length gives a wrong end date for every new booking.', 'Plan मास्टर में प्लान की अवधि (महीने के दिन) देखें — गलत अवधि से हर नई बुकिंग की तारीख गलत आती है।']],
                'related' => ['freeze_not_working', 'gift_days_not_working'],
            ],
            'freeze_not_working' => [
                'cat' => 'plan', 'icon' => 'fa-snowflake', 'needs' => 'learner', 'check' => 'checkFreeze', 'perm' => 'Freez Days', 'toggle' => 34,
                'keys' => ['freeze', 'unfreeze', 'freeze not working', 'cannot freeze', 'freeze nahi', 'freeze nahi ho raha', 'pause plan', 'फ्रीज नहीं', 'फ्रीज़ नहीं', 'अनफ्रीज'],
                'title' => ['Freeze / Unfreeze is not working', 'फ्रीज़ / अनफ्रीज़ नहीं हो रहा'],
                'steps' => [
                    ['Freeze works only before the plan end date — not in the extension days or after expiry.', 'Freeze सिर्फ प्लान खत्म होने की तारीख से पहले होता है — extension दिनों में या एक्सपायरी के बाद नहीं।'],
                    ['To end a freeze early, use Unfreeze on the same learner; the remaining days are added back.', 'फ्रीज़ जल्दी खत्म करने के लिए उसी छात्र पर Unfreeze करें; बचे दिन वापस जुड़ जाते हैं।'],
                ],
                'more' => [['After the plan expires, renew it first and then freeze the new plan.', 'प्लान एक्सपायर होने के बाद पहले रिन्यू करें, फिर नए प्लान को फ्रीज़ करें।']],
                'related' => ['wrong_end_date', 'gift_days_not_working'],
            ],
            'gift_days_not_working' => [
                'cat' => 'plan', 'icon' => 'fa-gift', 'needs' => 'learner', 'check' => 'checkActivePlan', 'perm' => 'Gift Days', 'toggle' => 33,
                'keys' => ['gift days', 'gift day', 'free days', 'extra days', 'gift nahi', 'extra din', 'गिफ्ट', 'फ्री दिन', 'एक्स्ट्रा दिन'],
                'title' => ['Gift days are not working', 'गिफ्ट डेज़ नहीं जुड़ रहे'],
                'steps' => [
                    ['Gift Days can be given on an active plan that is not frozen.', 'Gift Days ऐसे चालू प्लान पर दिए जा सकते हैं जो फ्रीज़ न हो।'],
                    ['Open the learner → Gift Days, enter the number of days and save — the end date moves forward by that many days.', 'छात्र खोलें → Gift Days, दिन डालें और सेव करें — खत्म की तारीख उतने दिन आगे हो जाएगी।'],
                ],
                'more' => [['If the plan has expired, renew it first, then give gift days on the new plan.', 'अगर प्लान एक्सपायर हो गया है, पहले रिन्यू करें, फिर नए प्लान पर गिफ्ट डेज़ दें।']],
                'related' => ['wrong_end_date'],
            ],

            /* ------------------------------ Learner ------------------------------ */
            'edit_profile_missing' => [
                'cat' => 'learner', 'icon' => 'fa-user-pen', 'needs' => 'learner', 'check' => 'checkEditProfile', 'perm' => 'Edit Seat', 'toggle' => 17,
                'keys' => ['edit profile', 'edit button', 'edit option', 'cannot edit', 'can t edit', 'edit nahi', 'edit nahi ho raha', 'edit icon', 'name change nahi', 'mobile change nahi', 'एडिट नहीं', 'एडिट का आइकन', 'प्रोफाइल एडिट'],
                'title' => ['Edit profile is not showing', 'Edit Profile नहीं दिख रहा'],
                'steps' => [
                    ['Open the learner → actions → “Edit Seat Booking Details” to change name, mobile and other details.', 'छात्र खोलें → actions → “Edit Seat Booking Details” से नाम, मोबाइल और बाकी जानकारी बदलें।'],
                    ['It is hidden while the plan is frozen, or when “Hide Edit Seat Plan Option” is on for the branch.', 'प्लान फ्रीज़ हो, या ब्रांच में “Hide Edit Seat Plan Option” चालू हो, तो यह छुपा रहता है।'],
                    ['The plan start date can be changed only within 3 days of the start.', 'प्लान की शुरू की तारीख सिर्फ शुरू होने के 3 दिन तक बदली जा सकती है।'],
                ],
                'more' => [['Plan or shift changes are not done from Edit Profile — use Change Plan (first 7 days) or Upgrade / Renew.', 'प्लान या शिफ्ट Edit Profile से नहीं बदलते — Change Plan (पहले 7 दिन) या Upgrade / Renew करें।']],
                'related' => ['change_plan_missing'],
            ],
            'deleted_by_mistake' => [
                'cat' => 'learner', 'icon' => 'fa-trash-arrow-up', 'needs' => 'learner', 'check' => 'checkDeleted', 'perm' => null,
                'keys' => ['deleted by mistake', 'deleted a learner', 'delete ho gaya', 'galti se delete', 'delete kar diya', 'restore', 'wapas lana', 'undo delete', 'गलती से डिलीट', 'डिलीट हो गया', 'रिस्टोर', 'वापस लाना'],
                'title' => ['Deleted a learner by mistake', 'गलती से छात्र डिलीट हो गया'],
                'steps' => [
                    ['Open the learner list and switch the filter to Deleted learners.', 'Learner list खोलें और फ़िल्टर में Deleted learners चुनें।'],
                    ['Find the learner and click Restore — they come back with their history.', 'छात्र ढूंढें और Restore पर क्लिक करें — वे अपनी पूरी हिस्ट्री के साथ वापस आ जाएंगे।'],
                    ['If the plan has already ended, Restore is not allowed — use Reactivate to start a new plan instead.', 'अगर प्लान पहले ही खत्म हो गया है, तो Restore नहीं होगा — इसकी जगह Reactivate से नया प्लान शुरू करें।'],
                ],
                'more' => [['Permanent Delete cannot be undone — if the learner was permanently deleted, add them again as a new learner.', 'Permanent Delete वापस नहीं होता — अगर छात्र हमेशा के लिए डिलीट हुआ है, तो उसे नए छात्र की तरह फिर से जोड़ें।']],
                'links' => [['seats', 'Open learner list', 'छात्र सूची खोलें']],
                'related' => ['reactivate_missing', 'learner_not_found'],
            ],
            'reactivate_missing' => [
                'cat' => 'learner', 'icon' => 'fa-user-check', 'needs' => 'learner', 'check' => 'checkReactivate', 'perm' => 'Reactive Seat',
                'keys' => ['reactivate', 'reactive', 'activate again', 'reactivate not showing', 'wapas chalu', 'dobara chalu', 'reactivate nahi', 'दोबारा चालू', 'रीएक्टिवेट', 'फिर से चालू'],
                'title' => ['Reactivate option is not showing', 'Reactivate का ऑप्शन नहीं दिख रहा'],
                'steps' => [
                    ['Reactivate shows only for Expired, Deleted and Closed learners.', 'Reactivate सिर्फ Expired, Deleted और Closed छात्रों में दिखता है।'],
                    ['If the learner is still active or in the extension days, use Renew instead.', 'अगर छात्र अभी चालू है या extension दिनों में है, तो Renew करें।'],
                    ['The new plan must start on or after the last plan\'s end date, and the seat must be free for that shift.', 'नया प्लान पिछले प्लान की खत्म की तारीख या उसके बाद शुरू होना चाहिए, और उस शिफ्ट में सीट खाली होनी चाहिए।'],
                ],
                'more' => [['Use the Expired / Closed / Deleted filter in the learner list to find them.', 'उन्हें ढूंढने के लिए learner list में Expired / Closed / Deleted फ़िल्टर लगाएं।']],
                'related' => ['renew_missing', 'deleted_by_mistake'],
            ],
            'learner_not_found' => [
                'cat' => 'learner', 'icon' => 'fa-magnifying-glass', 'needs' => 'learner', 'check' => 'checkLearnerFound', 'perm' => 'Search Learner',
                'keys' => ['learner not found', 'student not found', 'learner not showing', 'cannot find learner', 'learner missing', 'student nahi mil raha', 'learner nahi dikh raha', 'naam nahi aa raha', 'छात्र नहीं मिल रहा', 'छात्र नहीं दिख रहा', 'स्टूडेंट नहीं मिल रहा'],
                'title' => ['A learner is not showing', 'कोई छात्र नहीं दिख रहा'],
                'steps' => [
                    ['Check the branch selected at the top — each branch shows only its own learners.', 'ऊपर चुनी हुई ब्रांच देखें — हर ब्रांच में सिर्फ उसके अपने छात्र दिखते हैं।'],
                    ['Clear the filters in the learner list, then search by name, mobile or seat number.', 'Learner list के फ़िल्टर हटाएं, फिर नाम, मोबाइल या सीट नंबर से खोजें।'],
                    ['Expired, Closed and Deleted learners are in their own filters.', 'Expired, Closed और Deleted छात्र अपने अलग फ़िल्टर में मिलते हैं।'],
                ],
                'more' => [['If you imported learners, check the invalid-records file — rows with errors were not added.', 'अगर छात्र इम्पोर्ट किए थे, तो invalid-records फाइल देखें — गलती वाली लाइनें नहीं जुड़ीं।']],
                'links' => [['seats', 'Open learner list', 'छात्र सूची खोलें']],
                'related' => ['deleted_by_mistake', 'branch_data_missing', 'import_errors'],
            ],
            'swap_seat' => [
                'cat' => 'learner', 'icon' => 'fa-right-left', 'needs' => 'learner', 'check' => 'checkActivePlan', 'perm' => 'Swap Seat',
                'keys' => ['swap seat', 'swap not working', 'seat change nahi', 'seat swap nahi', 'seat badal nahi', 'change seat number', 'सीट बदल नहीं', 'सीट स्वैप नहीं'],
                'title' => ['Cannot swap / change the seat', 'सीट बदल नहीं पा रहे'],
                'steps' => [
                    ['Swap Seat works while the plan or its extension days are running and the plan is not frozen — open Learner List → actions → Swap Seat.', 'Swap Seat तब होता है जब प्लान या उसके extension दिन चल रहे हों और प्लान फ्रीज़ न हो — Learner List → actions → Swap Seat खोलें।'],
                    ['The seat number must exist (1 to your total seats).', 'सीट नंबर मौजूद होना चाहिए (1 से आपकी कुल सीटों तक)।'],
                    ['Pick a seat that is free in the learner\'s shift; seats taken in an overlapping shift cannot be chosen.', 'छात्र की शिफ्ट में खाली सीट चुनें; overlap वाली शिफ्ट में भरी सीट नहीं चुनी जा सकती।'],
                ],
                'more' => [['To move between a General seat and a numbered seat, swap to the numbered seat directly from the seat map.', 'General सीट से नंबर वाली सीट पर ले जाने के लिए सीट मैप से सीधे नंबर वाली सीट पर स्वैप करें।']],
                'links' => [['seats', 'Open seat map', 'सीट मैप खोलें']],
                'related' => ['no_vacant_seat'],
            ],

            'close_delete_blocked' => [
                'cat' => 'learner', 'icon' => 'fa-user-xmark', 'needs' => 'learner', 'check' => 'checkDues', 'perm' => 'Close Seat', 'toggle' => 16,
                'keys' => ['cannot close seat', 'cannot delete seat', 'close seat not working', 'delete seat not working', 'settle it before', 'pending amount please settle', 'close nahi ho raha', 'delete nahi ho raha', 'seat close nahi', 'seat delete nahi', 'सीट क्लोज नहीं', 'सीट डिलीट नहीं', 'क्लोज नहीं हो रहा'],
                'title' => ['Cannot close or delete a seat', 'सीट क्लोज़ या डिलीट नहीं हो रही'],
                'steps' => [
                    ['Close and Delete work only when the learner\'s money is settled: pending amount minus refund must be exactly 0.', 'Close और Delete तभी होते हैं जब छात्र का हिसाब बराबर हो: बकाया − रिफंड बिल्कुल 0 होना चाहिए।'],
                    ['First collect the pending amount (or record the refund), then close / delete again.', 'पहले बकाया लें (या रिफंड दर्ज करें), फिर दोबारा क्लोज़ / डिलीट करें।'],
                    ['Close is hidden while the plan is frozen, after the extension days, or when “Hide Close Plan Option” is on.', 'प्लान फ्रीज़ हो, extension दिन बीत गए हों, या “Hide Close Plan Option” चालू हो, तो Close छुपा रहता है।'],
                ],
                'more' => [['Permanent delete is allowed only for learners already deleted or closed.', 'Permanent delete सिर्फ पहले से deleted या closed छात्रों के लिए है।']],
                'related' => ['collect_payment', 'deleted_by_mistake'],
            ],

            /* ------------------------------ Payments ------------------------------ */
            'dues_wrong' => [
                'cat' => 'payment', 'icon' => 'fa-hand-holding-dollar', 'needs' => 'learner', 'check' => 'checkDues', 'perm' => 'Pending Payment Report',
                'keys' => ['dues wrong', 'wrong dues', 'pending amount wrong', 'due amount wrong', 'dues galat', 'bakaya galat', 'baki galat', 'pending galat', 'galat bakaya', 'बकाया गलत', 'बाकी राशि गलत', 'गलत बकाया'],
                'title' => ['Dues amount looks wrong', 'बकाया राशि गलत दिख रही है'],
                'steps' => [
                    ['Open the learner → Transaction: every payment, partial payment and discount is listed with who entered it.', 'छात्र खोलें → Transaction: हर भुगतान, आंशिक भुगतान और छूट, किसने दर्ज किया उसके साथ दिखता है।'],
                    ['Dues = plan price − paid − discount. A missing discount or a pay-later booking makes dues look higher.', 'बकाया = प्लान की कीमत − जमा − छूट। छूट न लगने या pay-later बुकिंग से बकाया ज़्यादा दिखता है।'],
                    ['Record the money received with Collect pending payment — do not create a new booking for it.', 'मिला हुआ पैसा Collect pending payment से दर्ज करें — इसके लिए नई बुकिंग न बनाएं।'],
                ],
                'more' => [['If a wrong payment was entered, edit or delete that transaction from the Transaction page and enter it again.', 'अगर गलत भुगतान दर्ज हुआ है, तो Transaction पेज से उसे एडिट या डिलीट करके दोबारा दर्ज करें।']],
                'links' => [['pending.payment.report', 'Pending payment report', 'बकाया रिपोर्ट']],
                'related' => ['collection_mismatch'],
            ],
            'collection_mismatch' => [
                'cat' => 'payment', 'icon' => 'fa-indian-rupee-sign', 'needs' => null, 'check' => 'checkCollection', 'perm' => 'Payment Collection Report',
                'keys' => ['collection wrong', 'collection mismatch', 'cash not matching', 'collection not matching', 'collection galat', 'hisab nahi mil raha', 'cash match nahi', 'paisa kam', 'कलेक्शन गलत', 'हिसाब नहीं मिल रहा', 'कैश मैच नहीं'],
                'title' => ['Collection does not match the cash', 'कलेक्शन कैश से मेल नहीं खा रहा'],
                'steps' => [
                    ['Pay-later bookings are not counted as collection until the money is collected.', 'Pay-later बुकिंग पैसे मिलने तक कलेक्शन में नहीं गिनी जाती।'],
                    ['Check the payment mode of each entry — cash and UPI / online are shown separately.', 'हर एंट्री का पेमेंट मोड देखें — कैश और UPI / ऑनलाइन अलग दिखते हैं।'],
                    ['Expenses and refunds are not taken out of collection; see Profit for collection minus expenses.', 'खर्च और रिफंड कलेक्शन से नहीं घटते; कलेक्शन − खर्च के लिए Profit देखें।'],
                ],
                'more' => [['Open the Activity report to see who entered or changed each payment.', 'हर भुगतान किसने दर्ज या बदला, यह Activity रिपोर्ट में देखें।']],
                'links' => [['payment.collection.report', 'Collection report', 'कलेक्शन रिपोर्ट'], ['activity.report', 'Activity report', 'एक्टिविटी रिपोर्ट']],
                'related' => ['dues_wrong'],
            ],
            'collect_payment' => [
                'cat' => 'payment', 'icon' => 'fa-money-bill-wave', 'needs' => 'learner', 'check' => 'checkDues', 'perm' => 'Pending Payment',
                'keys' => ['collect payment', 'collect dues', 'take pending payment', 'pending payment kaise', 'bakaya kaise le', 'payment kaise le', 'paisa kaise jama', 'बकाया कैसे लें', 'पेमेंट कैसे लें', 'पैसा कैसे जमा'],
                'title' => ['How to collect a pending payment', 'बकाया भुगतान कैसे लें'],
                'steps' => [
                    ['Open Pending Payment report (or the learner) and click Collect / Pending payment.', 'Pending Payment रिपोर्ट (या छात्र) खोलें और Collect / Pending payment पर क्लिक करें।'],
                    ['Enter the amount received and the payment mode, then save — the dues go down at once.', 'मिली राशि और पेमेंट मोड डालें, फिर सेव करें — बकाया तुरंत कम हो जाएगा।'],
                    ['For part payment, set the next due date so the learner shows as overdue on time.', 'आंशिक भुगतान में अगली देय तिथि डालें, ताकि छात्र समय पर overdue दिखे।'],
                ],
                'more' => [['Other money (registration, locker, ID card) goes in Misc Payment, not in pending payment.', 'बाकी पैसा (रजिस्ट्रेशन, लॉकर, ID कार्ड) Misc Payment में जाता है, pending payment में नहीं।']],
                'links' => [['pending.payment.report', 'Pending payment report', 'बकाया रिपोर्ट']],
                'related' => ['dues_wrong'],
            ],

            /* ------------------------------ Attendance ------------------------------ */
            'qr_not_working' => [
                'cat' => 'attendance', 'icon' => 'fa-qrcode', 'needs' => 'learner', 'check' => 'checkAttendance', 'perm' => null,
                'keys' => ['qr not working', 'attendance qr', 'qr scan not working', 'scan not working', 'attendance not marking', 'qr kaam nahi', 'qr scan nahi', 'attendance nahi lag rahi', 'hazri nahi lag rahi', 'हाजिरी नहीं लग रही', 'क्यूआर काम नहीं', 'स्कैन नहीं हो रहा'],
                'title' => ['Attendance QR is not working', 'हाजिरी का QR काम नहीं कर रहा'],
                'steps' => [
                    ['Print the QR again from Attendance → QR code for this branch — a QR of another branch will not work here.', 'इसी ब्रांच के लिए Attendance → QR code से QR दोबारा प्रिंट करें — दूसरी ब्रांच का QR यहां काम नहीं करेगा।'],
                    ['The learner must have an active plan; expired learners cannot mark attendance.', 'छात्र का प्लान चालू होना चाहिए; एक्सपायर छात्र हाजिरी नहीं लगा सकते।'],
                    ['Ask the learner to allow camera access and scan in good light; they verify with their registered mobile number.', 'छात्र से कैमरा की अनुमति देने और अच्छी रोशनी में स्कैन करने को कहें; वे अपने रजिस्टर्ड मोबाइल नंबर से वेरिफाई करते हैं।'],
                ],
                'more' => [
                    ['You can always mark attendance by hand from Attendance → Manage attendance.', 'आप Attendance → Manage attendance से हाथ से भी हाजिरी लगा सकते हैं।'],
                    ['Check the learner\'s mobile number in their profile is correct — verification uses it.', 'छात्र की प्रोफाइल में मोबाइल नंबर सही है या नहीं, देखें — वेरिफिकेशन उसी से होता है।'],
                ],
                'links' => [['attendance.qrcode', 'Attendance QR', 'हाजिरी QR'], ['attendance.report', 'Attendance report', 'हाजिरी रिपोर्ट']],
                'related' => ['attendance_not_showing'],
            ],
            'attendance_not_showing' => [
                'cat' => 'attendance', 'icon' => 'fa-user-clock', 'needs' => 'learner', 'check' => 'checkAttendance', 'perm' => 'Attendance Report',
                'keys' => ['attendance not showing', 'attendance missing', 'present not showing', 'attendance report wrong', 'hazri nahi dikh', 'attendance nahi dikh', 'हाजिरी नहीं दिख', 'उपस्थिति नहीं दिख'],
                'title' => ['Attendance is not showing', 'हाजिरी नहीं दिख रही'],
                'steps' => [
                    ['Live attendance on the dashboard shows only today; past days are in the Attendance report.', 'डैशबोर्ड पर लाइव हाजिरी सिर्फ आज की दिखती है; पिछले दिन Attendance रिपोर्ट में हैं।'],
                    ['Check the date filter and the branch in the report.', 'रिपोर्ट में तारीख का फ़िल्टर और ब्रांच देखें।'],
                ],
                'more' => [['A learner who punched in but not out is shown as inside; the next scan marks them out.', 'जो छात्र अंदर आया पर बाहर स्कैन नहीं किया, वह अंदर दिखता है; अगला स्कैन उसे बाहर कर देता है।']],
                'links' => [['attendance.report', 'Attendance report', 'हाजिरी रिपोर्ट']],
                'related' => ['qr_not_working'],
            ],

            /* ------------------------------ WhatsApp & SMS ------------------------------ */
            'reminder_not_sending' => [
                'cat' => 'messages', 'icon' => 'fa-brands fa-whatsapp', 'needs' => 'learner', 'check' => 'checkReminder', 'perm' => null,
                'keys' => ['reminder not sending', 'reminder not going', 'whatsapp not going', 'whatsapp not sending', 'whatsapp reminder', 'sms not going', 'message not going', 'reminder nahi ja', 'whatsapp nahi ja', 'message nahi ja', 'sms nahi ja', 'reminder button', 'रिमाइंडर नहीं जा', 'व्हाट्सएप नहीं जा', 'मैसेज नहीं जा', 'एसएमएस नहीं'],
                'title' => ['WhatsApp / SMS reminder is not going', 'WhatsApp / SMS रिमाइंडर नहीं जा रहा'],
                'steps' => [
                    ['The due reminder button shows from 5 days before expiry and during the extension days.', 'Due reminder का बटन एक्सपायरी से 5 दिन पहले से और extension दिनों में दिखता है।'],
                    ['Use “Always on WhatsApp” to send a reminder any time — it opens WhatsApp with the message ready to edit and send.', '“Always on WhatsApp” से कभी भी रिमाइंडर भेजें — यह WhatsApp में तैयार मैसेज खोलता है, बदलकर भेज सकते हैं।'],
                    ['The learner\'s mobile number must be a correct 10-digit number.', 'छात्र का मोबाइल नंबर सही 10 अंकों का होना चाहिए।'],
                ],
                'more' => [
                    ['Automatic WhatsApp / SMS need the notification channel switched on for the branch and message credits — see Text & WhatsApp notifications.', 'अपने आप जाने वाले WhatsApp / SMS के लिए ब्रांच में नोटिफिकेशन चालू और मैसेज क्रेडिट होने चाहिए — Text & WhatsApp notifications देखें।'],
                    ['On a computer, WhatsApp Web or the WhatsApp app must be logged in for the free reminder to open.', 'कंप्यूटर पर फ्री रिमाइंडर खुलने के लिए WhatsApp Web या WhatsApp ऐप लॉगिन होना चाहिए।'],
                ],
                'related' => ['renew_missing'],
            ],

            /* ------------------------------ Documents ------------------------------ */
            'idcard_not_printing' => [
                'cat' => 'documents', 'icon' => 'fa-id-card', 'needs' => null, 'check' => 'checkIdCard', 'perm' => null,
                'keys' => ['id card', 'id card not printing', 'id card error', 'id card not generating', 'icard', 'id card nahi', 'id card print nahi', 'id card ban nahi', 'आईडी कार्ड', 'id कार्ड', 'कार्ड प्रिंट नहीं', 'कार्ड नहीं बन'],
                'title' => ['ID card is not printing', 'ID कार्ड प्रिंट नहीं हो रहा'],
                'steps' => [
                    ['Open Learner List → actions → Generate ID Card for one learner, or select many for bulk print.', 'Learner List → actions → Generate ID Card से एक छात्र का, या कई चुनकर एक साथ प्रिंट करें।'],
                    ['ID cards are made only for learners whose plan (or extension days) is running; bulk print takes only active learners.', 'ID कार्ड सिर्फ उन छात्रों के बनते हैं जिनका प्लान (या extension दिन) चल रहा है; एक साथ प्रिंट में सिर्फ चालू छात्र आते हैं।'],
                    ['Check “Hide ID Card Generate Option” is off for the branch.', 'देखें कि ब्रांच में “Hide ID Card Generate Option” बंद है।'],
                    ['Allow pop-ups for the panel so the print / PDF window can open.', 'पैनल के लिए pop-ups की अनुमति दें, ताकि प्रिंट / PDF विंडो खुल सके।'],
                ],
                'more' => [['Add the learner\'s photo in their profile for a complete card.', 'पूरे कार्ड के लिए छात्र की प्रोफाइल में फोटो लगाएं।']],
                'related' => ['receipt_download'],
            ],
            'receipt_download' => [
                'cat' => 'documents', 'icon' => 'fa-receipt', 'needs' => 'learner', 'check' => null, 'perm' => 'Download Receipt',
                'keys' => ['receipt', 'receipt not downloading', 'receipt download', 'bill download', 'raseed', 'receipt nahi', 'renewal receipt', 'रसीद', 'रसीद डाउनलोड', 'बिल नहीं'],
                'title' => ['Receipt is not downloading', 'रसीद डाउनलोड नहीं हो रही'],
                'steps' => [
                    ['Use the Receipt option on the learner for the latest booking.', 'नई बुकिंग के लिए छात्र पर Receipt ऑप्शन का उपयोग करें।'],
                    ['For a renewal receipt, open the learner → Transaction and download it from that entry.', 'रिन्यूअल की रसीद के लिए छात्र खोलें → Transaction और उसी एंट्री से डाउनलोड करें।'],
                    ['Allow pop-ups / downloads for the panel in your browser.', 'ब्राउज़र में पैनल के लिए pop-ups / downloads की अनुमति दें।'],
                ],
                'more' => [['Fill your branch name, address and logo in Branch settings — they print on the receipt.', 'Branch सेटिंग में ब्रांच का नाम, पता और लोगो भरें — ये रसीद पर छपते हैं।']],
                'related' => ['idcard_not_printing'],
            ],

            /* ------------------------------ Access ------------------------------ */
            'staff_menu_missing' => [
                'cat' => 'access', 'icon' => 'fa-user-lock', 'needs' => null, 'check' => 'checkStaffAccess', 'perm' => null,
                'keys' => ['staff cannot see', 'staff can t see', 'staff menu', 'menu not showing', 'option not showing for staff', 'staff ko', 'staff ko menu', 'staff ko nahi dikh', 'menu nahi dikh', 'permission', 'access nahi', 'स्टाफ को', 'मेन्यू नहीं दिख', 'अनुमति', 'एक्सेस नहीं'],
                'title' => ['Staff cannot see a menu or option', 'स्टाफ को मेन्यू या ऑप्शन नहीं दिख रहा'],
                'steps' => [
                    ['Staff see only what the owner allowed. Owner: open Staff → Access for that staff member.', 'स्टाफ को वही दिखता है जिसकी मालिक ने अनुमति दी है। मालिक: उस स्टाफ के लिए Staff → Access खोलें।'],
                    ['Tick the permissions they need (e.g. Book Seat, Renew Seat, Pending Payment) and save.', 'ज़रूरी अनुमतियां चुनें (जैसे Book Seat, Renew Seat, Pending Payment) और सेव करें।'],
                    ['Ask the staff to log out and log in again so the new access loads.', 'स्टाफ से लॉग आउट करके फिर लॉगिन करने को कहें, ताकि नई अनुमति लोड हो।'],
                ],
                'more' => [
                    ['Check the staff account is Active and allowed for this branch.', 'देखें कि स्टाफ अकाउंट Active है और इस ब्रांच के लिए अनुमति है।'],
                    ['Some features come with your Libraro plan — if the owner cannot see it either, it is not in the current plan.', 'कुछ फ़ीचर Libraro प्लान के साथ आते हैं — अगर मालिक को भी नहीं दिखता, तो वह मौजूदा प्लान में नहीं है।'],
                ],
                'links' => [['library-users.index', 'Staff list', 'स्टाफ सूची']],
                'related' => ['feature_locked', 'login_problem'],
            ],
            'login_problem' => [
                'cat' => 'access', 'icon' => 'fa-key', 'needs' => null, 'check' => null, 'perm' => null,
                'keys' => ['login problem', 'cannot login', 'can t login', 'login not working', 'forgot password', 'password reset', 'login nahi', 'login nahi ho raha', 'password bhool', 'password yaad nahi', 'लॉगिन नहीं', 'पासवर्ड भूल', 'पासवर्ड याद नहीं'],
                'title' => ['Cannot log in', 'लॉगिन नहीं हो रहा'],
                'steps' => [
                    ['Owner: use “Forgot password” on the library login page and follow the email link.', 'मालिक: लाइब्रेरी लॉगिन पेज पर “Forgot password” से ईमेल लिंक द्वारा नया पासवर्ड बनाएं।'],
                    ['Staff: the owner can set a new password from Staff → Edit.', 'स्टाफ: मालिक Staff → Edit से नया पासवर्ड सेट कर सकता है।'],
                    ['Use the right login page — library owners and staff log in at Library login, learners at Learner login.', 'सही लॉगिन पेज चुनें — मालिक व स्टाफ Library login पर, छात्र Learner login पर।'],
                ],
                'more' => [['A deactivated staff account is logged out at once — the owner must set it Active again.', 'बंद किया गया स्टाफ अकाउंट तुरंत लॉगआउट हो जाता है — मालिक को उसे फिर से Active करना होगा।']],
                'related' => ['staff_menu_missing'],
            ],

            /* ------------------------------ Setup ------------------------------ */
            'shift_missing' => [
                'cat' => 'setup', 'icon' => 'fa-clock', 'needs' => null, 'check' => 'checkPrices', 'perm' => null,
                'keys' => ['shift not showing', 'shift missing', 'plan not showing', 'plan missing in booking', 'price not showing', 'shift nahi dikh', 'plan nahi dikh', 'price nahi dikh', 'शिफ्ट नहीं दिख', 'प्लान नहीं दिख', 'कीमत नहीं दिख'],
                'title' => ['A plan or shift is not showing in booking', 'बुकिंग में प्लान या शिफ्ट नहीं दिख रही'],
                'steps' => [
                    ['A shift shows in booking only when it has a price — set it in Plan Price master for every plan you sell.', 'बुकिंग में शिफ्ट तभी दिखती है जब उसकी कीमत सेट हो — Plan Price मास्टर में हर बिकने वाले प्लान के लिए सेट करें।'],
                    ['Create the shift first in Plan Type / Shift master, then its price.', 'पहले Plan Type / Shift मास्टर में शिफ्ट बनाएं, फिर उसकी कीमत।'],
                ],
                'more' => [['Check the shift belongs to the branch you are in — shifts are set per branch.', 'देखें कि शिफ्ट उसी ब्रांच की है जिसमें आप हैं — शिफ्ट हर ब्रांच के लिए अलग होती है।']],
                'related' => ['seat_not_booking', 'seat_count_wrong'],
            ],
            'seat_count_wrong' => [
                'cat' => 'setup', 'icon' => 'fa-layer-group', 'needs' => null, 'check' => 'checkSeatSetup', 'perm' => null,
                'keys' => ['seat count wrong', 'total seats wrong', 'total seats galat', 'seats galat', 'seat galat dikh', 'wrong seat numbers', 'seat number wrong', 'seats kam dikh', 'seat count galat', 'total seat galat', 'floor wrong', 'सीट गिनती गलत', 'कुल सीटें गलत', 'सीट नंबर गलत'],
                'title' => ['Seat count or seat numbers look wrong', 'सीटों की गिनती या नंबर गलत दिख रहे'],
                'steps' => [
                    ['The total seats of the branch come from the branch seat setting; floors split them into ranges.', 'ब्रांच की कुल सीटें ब्रांच की सीट सेटिंग से आती हैं; फ्लोर उन्हें हिस्सों में बांटते हैं।'],
                    ['Each floor\'s seat range must add up to the total seats — fix the ranges in Floor master.', 'हर फ्लोर की सीट रेंज मिलाकर कुल सीटों के बराबर होनी चाहिए — Floor मास्टर में रेंज ठीक करें।'],
                ],
                'more' => [['After changing seats, open the seat map again to see the new numbers.', 'सीटें बदलने के बाद नए नंबर देखने के लिए सीट मैप फिर से खोलें।']],
                'related' => ['no_vacant_seat'],
            ],

            /* ------------------------------ Online ------------------------------ */
            'online_booking_missing' => [
                'cat' => 'online', 'icon' => 'fa-globe', 'needs' => null, 'check' => 'checkOnlineBookings', 'perm' => null,
                'keys' => ['online booking not showing', 'online booking missing', 'qr booking', 'booking request', 'online booking nahi', 'booking request nahi dikh', 'website booking', 'ऑनलाइन बुकिंग नहीं', 'बुकिंग रिक्वेस्ट'],
                'title' => ['Online bookings are not showing', 'ऑनलाइन बुकिंग नहीं दिख रही'],
                'steps' => [
                    ['Online / QR bookings wait for your approval — open the booking requests and approve or reject them.', 'ऑनलाइन / QR बुकिंग आपकी मंज़ूरी का इंतज़ार करती हैं — बुकिंग रिक्वेस्ट खोलें और मंज़ूर या रद्द करें।'],
                    ['Add your UPI ID in Branch settings so learners can pay while booking.', 'Branch सेटिंग में अपनी UPI ID डालें, ताकि छात्र बुकिंग के समय भुगतान कर सकें।'],
                    ['Share the branch booking QR / link — learners book from it.', 'ब्रांच का बुकिंग QR / लिंक शेयर करें — छात्र उसी से बुक करते हैं।'],
                ],
                'more' => [['Check plan prices are set — learners can only book plans that have a price.', 'देखें कि प्लान की कीमतें सेट हैं — छात्र सिर्फ कीमत वाले प्लान बुक कर सकते हैं।']],
                'links' => [['library.enquiry', 'Enquiries', 'पूछताछ']],
                'related' => ['shift_missing'],
            ],

            /* ------------------------------ Data ------------------------------ */
            'import_errors' => [
                'cat' => 'data', 'icon' => 'fa-file-import', 'needs' => null, 'check' => null, 'perm' => 'Import Learners',
                'keys' => ['import error', 'import not working', 'csv error', 'excel upload error', 'import failed', 'import nahi', 'csv upload nahi', 'excel upload nahi', 'इम्पोर्ट नहीं', 'एक्सेल अपलोड नहीं', 'सीएसवी'],
                'title' => ['Learner import is failing', 'छात्र इम्पोर्ट नहीं हो रहे'],
                'steps' => [
                    ['Download the sample file from Import Learner and keep its columns exactly the same.', 'Import Learner से सैंपल फाइल डाउनलोड करें और उसके कॉलम बिल्कुल वैसे ही रखें।'],
                    ['Dates must be in the format of the sample, mobile numbers 10 digits, and plan / shift names exactly as in your masters.', 'तारीखें सैंपल जैसे फॉर्मेट में, मोबाइल 10 अंक, और प्लान / शिफ्ट के नाम बिल्कुल मास्टर जैसे होने चाहिए।'],
                    ['Rows with errors are skipped — download the invalid-records file, fix those rows and import them again.', 'गलती वाली लाइनें छूट जाती हैं — invalid-records फाइल डाउनलोड करें, उन्हें ठीक करके फिर से इम्पोर्ट करें।'],
                ],
                'more' => [['Save the file as CSV (UTF-8) so Hindi names stay correct.', 'फाइल को CSV (UTF-8) में सेव करें ताकि हिंदी नाम सही रहें।']],
                'links' => [['library.export.invalid.records', 'Download invalid records', 'invalid records डाउनलोड करें']],
                'related' => ['learner_not_found'],
            ],
            'branch_data_missing' => [
                'cat' => 'data', 'icon' => 'fa-code-branch', 'needs' => null, 'check' => 'checkBranch', 'perm' => null,
                'keys' => ['data missing', 'data not showing', 'everything is empty', 'dashboard empty', 'wrong branch', 'data nahi dikh', 'sab khali', 'data gayab', 'डेटा नहीं दिख', 'सब खाली', 'डेटा गायब'],
                'title' => ['Data is missing or looks empty', 'डेटा नहीं दिख रहा या खाली है'],
                'steps' => [
                    ['Check the branch selected at the top of the panel — every page shows only that branch.', 'पैनल के ऊपर चुनी हुई ब्रांच देखें — हर पेज सिर्फ उसी ब्रांच का डेटा दिखाता है।'],
                    ['Clear date filters on reports — they may be set to another month.', 'रिपोर्ट के तारीख फ़िल्टर हटाएं — वे किसी और महीने पर हो सकते हैं।'],
                ],
                'more' => [['Refresh with Ctrl + Shift + R; if it is still empty, send it to Libraro support.', 'Ctrl + Shift + R से रिफ्रेश करें; फिर भी खाली हो तो Libraro सपोर्ट को भेजें।']],
                'related' => ['learner_not_found'],
            ],

            /* ------------------------------ Account ------------------------------ */
            'subscription_expired' => [
                'cat' => 'account', 'icon' => 'fa-crown', 'needs' => null, 'check' => 'checkSubscription', 'perm' => null,
                'keys' => ['subscription expired', 'plan expired', 'panel locked', 'software locked', 'account locked', 'renew subscription', 'libraro plan', 'subscription khatam', 'panel band', 'software band', 'सब्सक्रिप्शन खत्म', 'पैनल बंद', 'सॉफ्टवेयर बंद'],
                'title' => ['Libraro subscription / panel locked', 'Libraro सब्सक्रिप्शन / पैनल बंद'],
                'steps' => [
                    ['When your Libraro plan ends, the panel limits booking and other actions until you renew.', 'आपका Libraro प्लान खत्म होने पर रिन्यू करने तक पैनल बुकिंग और दूसरे काम सीमित कर देता है।'],
                    ['Open Choose plan, pick a plan and pay — the panel opens again right after payment.', 'Choose plan खोलें, प्लान चुनें और भुगतान करें — भुगतान के तुरंत बाद पैनल फिर से खुल जाता है।'],
                ],
                'more' => [['If you paid and it is still locked, send the payment ID to Libraro support.', 'अगर भुगतान कर दिया और फिर भी बंद है, तो पेमेंट ID Libraro सपोर्ट को भेजें।']],
                'links' => [['subscriptions.choosePlan', 'Choose plan', 'प्लान चुनें']],
                'related' => ['feature_locked'],
            ],
            'feature_locked' => [
                'cat' => 'account', 'icon' => 'fa-lock', 'needs' => null, 'check' => 'checkSubscription', 'perm' => null,
                'keys' => ['feature locked', 'feature not available', 'premium feature', 'option missing', 'feature nahi dikh', 'premium', 'upgrade libraro', 'फ़ीचर नहीं दिख', 'प्रीमियम'],
                'title' => ['A feature is locked or missing', 'कोई फ़ीचर बंद है या नहीं दिख रहा'],
                'steps' => [
                    ['Premium features come with higher Libraro plans — see which plan includes it on Choose plan.', 'प्रीमियम फ़ीचर ऊंचे Libraro प्लान के साथ आते हैं — Choose plan पर देखें कौन सा प्लान इसे देता है।'],
                    ['Some options also depend on dates (Renew, Upgrade, Edit) — tell me which button and I will check.', 'कुछ ऑप्शन तारीख पर भी निर्भर हैं (Renew, Upgrade, Edit) — बताइए कौन सा बटन, मैं जांच दूंगा।'],
                ],
                'more' => [['Staff accounts also need the owner\'s permission for each feature.', 'स्टाफ अकाउंट को हर फ़ीचर के लिए मालिक की अनुमति भी चाहिए।']],
                'links' => [['subscriptions.choosePlan', 'Choose plan', 'प्लान चुनें']],
                'related' => ['staff_menu_missing', 'subscription_expired'],
            ],
        ];
    }
}
