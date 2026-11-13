# Global Discount

One library-wide offer, e.g. "Diwali Offer · 10% off, 1–15 Nov". While it runs, every booking
form takes it off by itself and students see a banner on their dashboard. There is no coupon
code and nothing to type.

Managed by the library owner at **Setup → Global Discount**
(`/library/global-discount`, `GlobalDiscountController`, owner only).

## Rules

| Rule | Detail |
|---|---|
| Running | `is_enabled` and today is between `start_date` and `end_date`, both days included. It starts and stops by itself; no cron. |
| One at a time | The master refuses a switched-on offer whose dates overlap another switched-on offer of the library. |
| Base | Worked out on the **plan fee** only. The locker charge is not discounted. A fixed amount is capped at the plan fee. |
| Operations | New booking, renew, upgrade, change plan, reactivate, QR booking (direct and manual verify) and the learner app booking. **Edit** never adds or removes it. |
| With a manual discount | The booking gets the **bigger** of the offer and the manual discount, never both. |
| Date checked | The day the booking is saved, not the plan start date. |
| Turning off / deleting | Booking forms stop applying it. Bookings that already got it keep their price. |

## Code

| Piece | Role |
|---|---|
| `global_discounts` | name, `discount_type` (percentage\|amount), `discount_value`, `start_date`, `end_date`, `is_enabled`, soft deletes. `App\Models\GlobalDiscount` (LibraryScope). |
| `App\Services\GlobalDiscountService` | The only place that decides the offer. `active()`, `amount()`, `apply()`, `payable()`, `overlapping()`. |
| `learner_transactions.global_discount_id` / `global_discount_amount` | Which offer a booking got and how much of `discount_amount` came from it. `discount_amount` stays the full discount, so totals, dues, receipts and reports need no change. |

Every booking path calls `GlobalDiscountService::apply()` and passes the type/value it returns on
to `BillingAmountService`, so the calculation is never duplicated:

| Path | Where |
|---|---|
| Renew, upgrade, change plan, reactivate, edit (web + API) | `LearnerOperationService::calculateBilling()` → `PlanService::calculatePrice(..., $operation)` |
| New booking (web + API) | `LearnerService::processLearnerStore()` |
| QR booking verify | `QrBookingService::verifyBooking()`, `QrEntryController::requestApproveEdit()` |
| QR / learner app booking request | `QrEntryController::store()`, `QrBookingService::createBooking()` store `total_amount` = `payable()` |
| Price API / QR price | `POST /api/v1/plan-price` (`operation`, `offer`, `original_amount`, see `docs/API.md`), `POST /get-plan-price` (`offer`, `payable`) |

`PlanService::calculatePrice()` applies the offer only when `$operation` is passed; plain price
lookups (extra shift prices, the price dropdown) keep the plain plan fee.

### Renewals never carry an old offer

Renew, upgrade and reactivate pre-fill the discount of the last booking. They use
`LearnerTransaction::manualDiscount()` (`discount_amount − global_discount_amount`), so an offer
that has ended is not carried forward as a manual discount. Change plan edits the same booking
and keeps its whole discount. On change plan / edit, the offer part stays recorded as long as the
discount still covers it (`LearnerOperationService::learnerTransactionUpdate()`).

## Screens

- **Booking forms**: `resources/views/partials/global-offer.blade.php` + `public/js/lb-offer.js`.
  The script writes the offer into each form's own discount fields, so the totals, paying-now
  and due amounts each form already works out include it, and locks them while it applies.
  It shows a card with Original price, Discount and Final payable. If the library hides the
  discount fields (Show / Hide Options), hidden ones are added so the offer still posts.
  Included on: new booking and renew popup (`learner/popup`), upgrade / renew page
  (`upgradePlan`), change plan, reactivate and QR verify (`qrcode/verify_request`).
  The public QR booking page shows the offer line from `/get-plan-price`.
- **Student dashboard**: banner from `LearnerDashboardController::learnerDashboard()` (`$offer`).
- **Master**: list with state (Running now / Starts later / Ended / Turned off), bookings and amount
  saved per offer, add / edit form with a live example, turn on / off, delete.

Tests: `tests/Feature/GlobalDiscountTest.php`.
