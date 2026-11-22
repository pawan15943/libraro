# Pay-to-library link (renewal / pending fee)

Reminder messages carry a link where the learner pays the **branch UPI ID** directly and sends the
screenshot. The library verifies it in **Dashboard → Online / QR bookings** and approves.

## Flow

1. **Reminder** — the free WhatsApp / SMS reminders add one line at the end:
   - plan ending / grace period → `Renew & pay online: <link>` (purpose `renew`)
   - pending fee → `Pay online: <link>` (purpose `due`)

   Where: dashboard (about to expire, extension, pending dues), learner card actions, pending-payment
   table, reports (expiring, grace, dues). The *Pending Payment Reminder* template (Message templates)
   can place `{{pay_link}}` itself; otherwise the line is appended. No UPI ID on the branch → no link,
   message unchanged. Paid WABA / SMS templates are not changed.
2. **Pay page** — `GET /pay/{learner}/{renew|due}` (`learner.paylink`), signed, valid 15 days, no login.
   Mobile first: library card (`qrcode/partials/branch-hero`), learner, amount, UPI QR + "Pay with UPI
   app" (phones), screenshot upload (`POST`, same URL, throttle 6/min).
   - renew: today's plan + shift price less the running offer, **plus old dues**; period = day after
     the current plan ends → `getEndDate()`. Allowed from 7 days before the end, only for an active
     (or extension) plan, not after delete / close, not when a future plan exists.
   - due: all pending (`learner_transactions.pending_amount`, this branch).
   - A screenshot already waiting → the page says "Waiting for verification" (no second request).
3. **Verification** — the screenshot becomes a pending `bookings` row (`payment_mode` online):
   - renew → `type = qr_renew` (light blue row, "Renewal", period chip). Approve = the existing QR
     approve. The amount above the plan fee pays old dues first (`learnerTransactionAddUpdate`).
   - due → `type = qr_due_payment`, no seat (orange row, "Dues"). Approve with the amount actually
     received (editable) → `booking.due.approve` → same as Learners → Collect pending (online).
     The seat-booking approve, the booking detail page and the app API refuse this type; the app's
     QR booking list leaves it out.

## Code

| Part | File |
|---|---|
| Amounts, rules, link, save | `app/Services/LearnerPayLinkService.php` |
| Pay page, submit, approve due | `app/Http/Controllers/LearnerPayLinkController.php` |
| Views | `resources/views/qrcode/pay_link.blade.php`, `qrcode/partials/branch-hero.blade.php` |
| Message helpers | `learnerPayLink()`, `appendPayLink()` in `app/Helpers/message_helper.php` |
| Dashboard rows | `resources/views/dashboard/admin.blade.php`, `public/css/lb-dash.css` |

## DB

`bookings.type` ENUM → `VARCHAR(30)` (keeps every old value, allows `qr_due_payment` and the app's
`learner_book`). Migration `2026_11_22_000001_bookings_type_allow_due_payment`; live:
`db/libraro_live_db_changes_2026-11-22.sql` section 1 — run before deploying.
