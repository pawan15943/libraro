# Ordering & Services

Learners order food, beverages, stationery, printing and services from their **seat**, a **cafeteria table**
or for **counter pickup**; staff run the orders from a board. Spec: "LIBRARO – Ordering & Services Module" v1.0.
No inventory / stock accounting — an item is simply *available* or not.

## Build steps

| Step | Scope | Status |
|---|---|---|
| 1 | Tables, models, permissions, library set-up: Menu & Items, Order Settings, cafeteria tables | done |
| 2 | Learner side: browse, combined cart, checkout (mode, payment intersection, UPI QR), track | done |
| 3 | Staff order board: queue, accept / reject, status flow, mark paid | done |
| 4 | Replacements: suggest, grouped learner approval, amount adjust | done |
| 5 | `orders:sweep` (reminder / escalation / replacement timeout), notifications, live alerts | done |
| 6 | Reports | done |
| 7 | API (`routes/api/v1.php`) + `docs/API.md` | done |

## Data (migration `2026_11_18_000001_create_ordering_tables`)

All rows carry `library_id` + `branch_id`; models use `HasBranch`. Controllers still filter by
`library_id` / `branch_id` explicitly (the learner guard skips the branch scope).

| Table | Purpose |
|---|---|
| `order_settings` | One row per branch: on/off, modes, hours, prep time, delivery charge, payment methods, pay-later rule, staff reminder / escalation minutes, replacement rules, learner cancel limit, print uploads |
| `order_categories` | Per-branch categories; `allow_online / allow_offline / allow_pay_later` limit payment per category; `is_service` = request rather than goods |
| `order_items` | Items: price, `price_unit`, `is_enabled` (in menu) vs `is_available` (in stock), `is_featured`, `needs_upload` (printing), `modes` (null = all) |
| `order_tables` | Cafeteria table labels |
| `orders` | `status` and `payment_status` are **independent**; `idempotency_key` (unique per learner) stops double orders; `version` = optimistic lock |
| `order_lines` | Snapshot of item name / price; own `fulfilment_status` |
| `order_replacements` | Staff suggestion for an unavailable line; learner must accept; `price_diff` |
| `order_payments` | Payments and refunds; `(order_id, kind, txn_ref)` unique → no duplicate UTR |
| `order_activities` | Append-only audit: actor, action, from → to status |

First visit to Menu & Items (or Order Settings) creates the branch's `order_settings` row (ordering **off**)
and the ten starter categories from the spec — once (`OrderingSetupService`).

## Permissions & menu (migration `2026_11_18_000002_ordering_permissions_and_menus`)

- `Ordering Setup` — Menu & Items, Order Settings, tables (route guard `library.can:Ordering Setup`)
- `Manage Orders` — the order board (step 3)
- Both are in the **Pro** plan; category "Ordering & Services". Sidebar group **Orders** (order 4).
- Live DB: `db/libraro_live_db_changes_2026-11-18.sql` (has an UNDO block at the end).

## Plan, permissions, show / hide (migration `2026_11_19_000001_orders_summary_permission_and_toggles`)

Two layers, both in `App\Services\Ordering\OrderingVisibility`:

1. **Plan.** The module is on only while the library's plan includes **Manage Orders** (`planAllows()`).
   Without it the learner side is off too (`LearnerOrderingService::context()` → `enabled: false`; the learner
   portal has no Orders menu), even if the branch had switched ordering on under an older plan. The panel menus
   and routes were already plan-gated by `library.can:*` / `planLocked()` (Pro badge).
2. **Owner choice.** Settings → Show / Hide Options, section **Orders** (`toggle_features`, saved per branch in
   `branches.hide_field`, looked up by name — ids differ per database and always come after 35):
   - **Hide Orders Menu**: the whole Orders group in the sidebar + the dashboard card (learners can still order)
   - **Hide Order Reports Menu**: only Orders → Order Reports
   - **Hide Orders Summary on Dashboard**: only the dashboard card

Permission **Orders Summary** (Pro, category "Ordering & Services"): the **Orders** card on the library dashboard
(`OrderDashboardService`), right after Money: orders today / this month (not cancelled or rejected), income
today / this month (verified payments − refunds, counted on the day the money came in), open orders now,
yesterday's income, and dues still to collect. Tiles open the Order Board / Order Reports when the user may.
Staff see it only when the owner grants Orders Summary. Live DB: `db/libraro_live_db_changes_2026-11-19.sql`.
Tests: `tests/Feature/OrderingVisibilityTest.php`.

Order Settings → Hours use the shared time picker (`data-lb-time`, same as the Shift form).

## Payment

Online = the branch's existing **UPI QR + screenshot** flow (`branches.upi_id`, same as QR booking); hidden when
the branch has no UPI ID. A cart only offers methods that are on in settings **and** allowed by every category in it.

The learner's order page shows the branch UPI QR for **every** order with money due (not only "online" ones):
a counter or pay-later order can be paid early the same way (`OrderPaymentService::canSendProof()`); staff then
approve the screenshot on the order page. Staff take counter money from the always-open **Collect payment** box
on the order page (Cash / UPI at counter / Card / Other, amount pre-filled); the summary card has a **Collect ₹X**
button that jumps there. If a screenshot is waiting, the box asks staff to check it first.

## Learner side (step 2)

Menu **Orders** in the learner portal (shown only while the branch has ordering on — `LearnerPortalService` → `ordering`).

| Route | What |
|---|---|
| `GET learner/orders` (`learner.orders`) | Menu, where-to (seat / table / counter), search, category chips, cart sheet, checkout |
| `POST learner/orders` (`learner.orders.place`) | JSON. Places the order — `OrderPlacementService::place()`; 422 `{message, errors}`, 409 `{code: price_changed, total, prices}` |
| `GET learner/orders/history` | All orders, spent / still to pay |
| `GET learner/orders/{orderNo}` | Tracking: steps, items with fulfilment status, payment (UPI QR + proof), cancel |
| `GET learner/orders/{orderNo}/status` | JSON `{status, payment_status, v}` — the page polls every 8 s and reloads when `v` changes |
| `POST learner/orders/{orderNo}/payment` | Screenshot (+ optional UTR) → payment status **Verifying** (`OrderPaymentService::submitProof`) |
| `POST learner/orders/{orderNo}/cancel` | Allowed up to `order_settings.learner_cancel_until`; paid / verifying money → **Refund pending** |

Rules enforced on the server at placement (`LearnerOrderingService::context()` + `OrderPlacementService`):
module on; plan active / grace / on hold (or `expired_can_order`); inside ordering hours; open-order limit;
mode on and location valid (seat from the active plan, or typed when `seat_change_allowed`; table must be an
open table of the branch); every item enabled + available + its category visible + allowed for the mode; latest
prices (client sends `expected_total`); payment = settings ∩ every category, online only with a branch UPI ID,
pay later only for allowed learners and within `pay_later_limit`; print files by type / size.

- **Cart** lives in the learner's browser (`localStorage`, key `lr-cart:<learner>:<branch>`) so it survives a
  lost connection or session; files are re-attached at checkout.
- **Idempotency**: the cart carries one key until an order succeeds; the same key always returns the first order
  (also under a race: unique `(learner_id, idempotency_key)`).
- **Order number**: `OD<branch>-<yymmdd>-<nnn>`, staff call out the last three digits.
- Printing: `quantity` = pages, `options` = `{pages, copies, sides}`, line total = price × pages × copies.
  Files are private (`storage/app/ordering/<branch>/…`), not under `public/`.

## Staff order board (step 3)

Sidebar **Orders → Order Board** (`orders.board`, permission **Manage Orders**, migration `2026_11_18_000003_order_board_menu`).

| Route | What |
|---|---|
| `GET library/orders` | Board: tabs New / Preparing / Ready-on the way / Payments / Done today; "Turn on alerts" (chime + system notification) |
| `GET library/orders/feed` | JSON polled every 5 s: `orders[]` (with allowed `actions` and version `v`), `counts`, `attention` |
| `GET library/orders/{id}` | Order page. Opening a New / Attention order makes it **Under review** (stops reminders) |
| `POST library/orders/{id}/act` | `action` = accept · prepare · ready · dispatch · deliver · attempted · collect · complete · reject · cancel · undo; `v` = version seen (stale → 409); reject / cancel / undo need `reason` |
| `POST library/orders/{id}/payments/{p}/review` | UPI screenshot: `decision` approve / reject |
| `POST library/orders/{id}/payments` | Money taken at the counter (cash / UPI / card / other), never more than due |
| `POST library/orders/{id}/refunds` | Money given back (cancelled / rejected, or paid more than the total) |
| `GET library/orders/{id}/files/{line}` · `…/payments/{p}/proof` | Print file download · payment screenshot (private storage) |

Flow (`OrderStatusService::MOVES`): New / Attention / Under review → **Accepted** → Preparing → **Ready** (counter) or
**Out for delivery** (seat / table) → Delivered / Collected (or "Not at seat" → retry). Delivered / Collected with nothing
due closes to **Completed** by itself; pay later stays Delivered until the money is recorded, then closes.

- Every action locks the order row; a double tap or a second staff member gets 409 with the fresh status.
- `handled_by_*` = whoever opened / accepted first.
- Cancel after preparation started: **owner only** (spec: admin approval). Owner can **undo** a wrong Delivered / Collected
  (reason logged). Rejected / cancelled orders with money paid or being verified → payment **Refund pending**.
- Payment status is computed from amounts (`OrderPaymentService::paymentState`): Paid, Partially paid, Verifying (proof
  waiting), Failed (proof rejected), Refund pending / Refunded / Partially refunded.

## Replacements (step 4) — `OrderReplacementService`

Order page → an item line → **Ran out?** → pick a suggested item (or "no replacement") → *Send to learner*
(`POST library/orders/{id}/unavailable`). The item is also marked out of stock in the menu.

- One `order_replacements` row per suggestion: `price_diff` = new line − old line, `deadline_at` = now + `replacement_timeout_minutes`.
  A newer suggestion on the same line withdraws the older one (fresh approval needed).
- Order → **Replacement pending**, unless the learner allowed partial fulfilment and staff already accepted / started:
  then it stays Confirmed / Preparing and only hand-over steps wait (`staffActions` hides ready / dispatch / deliver / collect).
- Learner (order page or API) answers **all** open suggestions at once: **accept** (new line, old one `replaced`),
  **remove**, or **other** (line `unavailable`, back to staff — only when `replacement_multiple`). Nothing is charged
  or replaced without this. If the suggested item ran out meanwhile, it is not added (note shown).
- After the answer the total is recalculated from the lines still on the order; the payment status follows
  (`paymentState`): cheaper → **Refund pending** if already paid, dearer → **Partially paid** / more due. Every item gone →
  order **Cancelled**. Staff can also take an unavailable line off (`DELETE library/orders/{id}/lines/{line}`).

## Reminders, escalation, timeouts (step 5) — `OrderSweepService`

- New order not opened after `reminder_minutes` → logged reminder (the board keeps ringing every 20 s anyway).
- Still not opened after `escalation_minutes` → **Attention required** + a notification to the owner. Never auto-rejected.
- Replacement past its deadline → `replacement_timeout_action`: **remove** the item, or **escalate** (line unavailable,
  order to Attention for staff).
- Runs from the scheduler (`orders:sweep`, every minute — needs the server cron
  `* * * * * php artisan schedule:run`) **and** from the board feed (at most every 30 s per branch), so it works without cron
  while the board is open.

Learner messages (accepted, ready, on the way, replacement, payment received / not received, refund…) go into the
existing `notifications` table (`OrderNotifier`, hooked in `OrderActivity::log`) — the learner app lists them; a failure
there never breaks the order action.

## Reports (step 6)

**Orders → Order Reports** (`orders.reports`, permission Manage Orders, migration `2026_11_18_000004_order_reports_menu`):
today / 7 / 30 days / this month / custom — orders, sales (rejected & cancelled excluded), money received, still to
collect, refunds, by payment method, by category, top items, staff (accepted / handed over / payments / rejected),
speed (time to accept, to prepare, order → hand-over, % ready on time), and the open dues / refunds lists.

## API (step 7)

Learner app: `api/v1/learner/ordering`, `orders/place`, `orders`, `orders/{no}`, `…/payment`, `…/replacements`, `…/cancel`.
Staff app: `api/v1/library/orders/feed`, `{id}`, `{id}/act`, `{id}/unavailable`, `{id}/lines/{line}`, `{id}/payments…`, `{id}/refunds`.
Full request / response tables: `docs/API.md` → *Learner Ordering* and *Order Board (staff)*.

## Safety checks (19 Nov 2026)

- **New order alert on every library page** (`partials/order-alerts`, polls `GET library/orders/alerts` every 15 s):
  a pill "N new orders" with a short chime when a new one arrives; also "N payments to check" for UPI screenshots.
  Not on the Order Board (it rings itself); only for users with Manage Orders, module in the plan, Orders menu not hidden.
  The poll also runs the reminder sweep, like the board.
- **No double payment:** while a learner's UPI screenshot waits, staff cannot take counter money for that order
  (server rule in `OrderPaymentService::recordPayment`, the order page shows "Check the UPI screenshot above first").
- **Server cron:** `orders:sweep` writes a heartbeat; Order Settings warns when it has not run for 10 minutes.
  Deploy checklist: `* * * * * cd /path/to/libraro && php artisan schedule:run >> /dev/null 2>&1`.
- **Learner leaving with unpaid orders:** the seat close / delete dialog shows "Orders ₹X unpaid" with a link to
  Order Reports → To collect now, and the learner profile shows an "Orders unpaid" row. It is only a reminder:
  close / delete work exactly as before (`OrderPaymentService::learnerDues()`).
