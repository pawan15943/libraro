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
| 4 | Replacements: suggest, grouped learner approval, amount adjust | — |
| 5 | `orders:sweep` (reminder / escalation / replacement timeout), notifications, live alerts | — |
| 6 | Reports | — |
| 7 | API (`routes/api/v1.php`) + `docs/API.md` | — |

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

## Payment

Online = the branch's existing **UPI QR + screenshot** flow (`branches.upi_id`, same as QR booking); hidden when
the branch has no UPI ID. A cart only offers methods that are on in settings **and** allowed by every category in it.

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
