# Learner advance (extra paid)

A learner sometimes pays more than the bill — ₹1000 for an ₹800 plan, "keep the rest for next month".
The extra is the learner's **advance**. It is used on the next renewal, and refunded (or waived) when
the learner leaves.

Nothing new is stored: the advance is `learner_transactions.refund` (the column the settlement page
calls "extra"). All logic lives in `App\Services\LearnerAdvanceService`.

## Flow

| When | What happens | Where |
|---|---|---|
| Booking | Paying more than the total is allowed (online/offline; renew / upgrade / reactivate too). The bill is saved as paid; the extra goes to `refund`. The form shows "₹200 advance · adjusts on the next renewal"; the booking-done page shows it too. | `LearnerService` (new booking), `TransactionActivityService::learnerTransactionAddUpdate`, `lb-booking.js`, `learner/script.blade.php` |
| Renew / Upgrade / Reactivate | A **Use advance** checkbox (ticked by default) appears when the learner has one. Ticked: the amount to collect drops by the advance. After the bill is saved, `LearnerAdvanceService::apply()` moves the advance onto the dues (oldest first) and logs **"Advance adjusted"**. Unticked (`use_advance=0`): the advance stays for later. An advance bigger than the bill carries forward. | `LearnerOperationService::process` / `calculateBilling`, renew popup (`learner/popup.blade.php`), `learner/partials/advance-toggle.blade.php` (upgradePlan, reactive) |
| Close / delete seat | The dialog shows "Advance ₹X" with **Refund** (online/offline) or **Waive**. `settle()` first pays any dues, then refunds (**"Advance refunded"**) or waives (**"Advance waived"**) the rest, then the seat closes as usual. | `LearnerLifecycleService::closedelete`, `learner/script.blade.php` (close / delete dialogs) |
| Change plan / Edit | Not touched: these re-bill the same booking and already handle their own refund. | |

## Advance or a higher price (branch switch)

The extra can instead be charged as a **higher price for this bill** (₹800 plan, ₹1000 taken → the plan
costs ₹1000, no advance). Same everywhere: booking, renew, upgrade, reactivate.

- **Branches page → each branch card → "Higher price on extra payment"** switch (`branches.allow_higher_price`,
  default off; saved at once by `POST branch/{id}/higher-price`, `BranchController::toggleHigherPrice`). The switch shows
  only to someone with the **"Charge Above Plan Price"** permission (Learner Operations; in the Basic and
  Pro plans; staff need it granted). Plan feature line: "Extra payment as advance or a higher price".
- When it's on and the form has an extra, a choice appears: **Advance** (default) / **Higher price**
  (`learner/partials/extra-as.blade.php`, `window.lpExtraAs()` in `learner/script.blade.php`).
- `extra_as=price` (online / offline only): the plan price grows by the extra, the discount stays the same
  rupees, so the bill equals what was paid (`BillingAmountService::extraAsPrice()` for booking,
  `LearnerOperationService::calculateBilling()` for renew / upgrade / reactivate). The server checks
  `extraAsPriceAllowed()` (switch + permission); otherwise the extra stays an advance.
- Only that bill: the next renewal starts again from the plan price.
- Migration `2026_11_15_000005_extra_payment_as_advance_or_price`; live SQL section 12 of
  `db/libraro_live_db_changes_2026-11-15.sql`.

## Activity rows (reports)

| particular | payment_type | dr_cr | In reports |
|---|---|---|---|
| Advance adjusted | SETTLED | Settle | not counted (no new money; the cash was counted when received) |
| Advance waived | SETTLED | Settle | not counted |
| Advance refunded | REFUND | Dr | refund (money given back) |

"Advance adjusted" / "Advance waived" rows can't be deleted on their own (they moved money between bills);
delete the transaction instead.

## Where the owner sees it

- Learner card: **Advance ₹X** in place of "All paid".
- Learner profile → Payment: an **Advance** row.
- Transactions → Overview: **Advance** stat and an "₹X paid in advance" note; each bill shows **Advance**
  instead of "Extra"; activity lines read "Advance Used for This Bill", "Advance Refunded on Leaving",
  "Advance Waived on Leaving".

## API

`POST /api/v1/library/learners/operation` takes `use_advance` (default on), and
`POST /api/v1/library/learners/close-delete` takes `advance_action` (refund|waive) + `advance_mode` (1|2).
`seat-book` accepts `paid_amount` above the bill. See `docs/API.md`.

Tests: `tests/Feature/LearnerAdvanceTest.php`.
