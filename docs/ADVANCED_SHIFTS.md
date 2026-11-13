# Advanced Shifts

Lets a library owner book a numbered seat for a shift whose timing is chosen at booking
time, instead of creating a new shift in the master for every timing, and book a general
seat for a flexible shift (any time in a range, for N hours).

| Seat | Shifts offered on the owner booking form and plan pages (upgrade, change plan, reactivate) |
|---|---|
| Numbered | fixed, duration, free |
| General | fixed, flexible |

Renew keeps the booking's shift. QR booking and other lists keep offering fixed shifts only.

## Shift types (`plan_types.booking_mode`)

| Mode | Created in master as | Entered at booking | Row used by the booking |
|---|---|---|---|
| `fixed` | Any existing shift (default) | Nothing | The shift itself (unchanged behaviour) |
| `duration` | e.g. "4 Hr Fixed": allowed window + hours | Start time (end = start + hours) | Hidden child row |
| `free` | e.g. "Custom Shift": allowed window | Start, end and price | Hidden child row |
| `flexible` | e.g. "4 Hr Flexible": allowed window + hours | Nothing (general seat) | The template itself |
| `multi` | Built from the booking form | Two time windows | Planned (hidden child with `start_time_2`/`end_time_2`) |

### Hidden child rows

When a `duration` or `free` template is booked, `ShiftTimingService` finds or creates a
child `plan_types` row with the real timing (`parent_id` = template id). The template's
`plan_prices` are copied to the child. A `free` booking also writes the entered price for
the chosen plan. The child id is what gets stored in `learner_detail.plan_type_id`.

The same child is reused for every booking with the same template, timing and price.
Because the child has real `start_time`/`end_time`, every existing check (seat map,
`checkAvailability`, `seatHeldByFuture`, renew, swap, attendance) works on it without
changes.

Children never appear in shift lists or dropdowns (`PlanType::scopeListable`).

### Flexible shifts

Booked directly on a general seat, so no seat check applies (as for any general seat).
Attendance uses `slot_hours` as the allowed time: staying longer shows as overtime, and a
missed check-out counts the shift hours instead of running to the window end.

## Database changes

Only `plan_types` changes. No new tables. `learner_detail`, `learners`, `attendances` and
`learner_transactions` are not touched.

| Column | Type | Default | Purpose |
|---|---|---|---|
| `booking_mode` | varchar(20) | `fixed` | fixed / duration / free / flexible / multi |
| `parent_id` | bigint unsigned, nullable | NULL | Template a hidden child belongs to |
| `start_time_2` | varchar(191), nullable | NULL | Second window of a multi shift |
| `end_time_2` | varchar(191), nullable | NULL | Second window of a multi shift |

Index: `idx_plan_types_parent_timing (parent_id, start_time, end_time)`.

Existing rows get `fixed` + NULL, so they behave exactly as before.

### Running the migration

Many older migrations show as *Pending* although their tables exist, so do **not** run a
plain `php artisan migrate`. Run only this file:

```bash
php artisan migrate --path=database/migrations/2026_10_29_000001_add_booking_mode_columns_to_plan_types_table.php
```

Equivalent SQL, for running by hand:

```sql
ALTER TABLE `plan_types`
  ADD COLUMN `booking_mode` VARCHAR(20) NOT NULL DEFAULT 'fixed' AFTER `day_type_id`,
  ADD COLUMN `parent_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `booking_mode`,
  ADD COLUMN `start_time_2` VARCHAR(191) NULL DEFAULT NULL AFTER `end_time`,
  ADD COLUMN `end_time_2` VARCHAR(191) NULL DEFAULT NULL AFTER `start_time_2`,
  ADD INDEX `idx_plan_types_parent_timing` (`parent_id`, `start_time`, `end_time`);
```

Rollback:

```sql
ALTER TABLE `plan_types`
  DROP INDEX `idx_plan_types_parent_timing`,
  DROP COLUMN `end_time_2`,
  DROP COLUMN `start_time_2`,
  DROP COLUMN `parent_id`,
  DROP COLUMN `booking_mode`;
```

## Multiple shifts in one booking

A learner can hold several shifts (any seat, any shift) under one booking and one
transaction. The first shift is the normal `learner_detail` row; every extra shift is a
row in `learner_detail_slots`.

| Column | Purpose |
|---|---|
| `learner_detail_id` | Parent booking: dates, status, freeze, extension and payment come from it |
| `learner_id`, `library_id`, `branch_id` | Scoping |
| `seat_no` | Seat of this shift (may differ from the main seat) |
| `plan_type_id` | Any shift, including a duration/free timing row |
| `price` | This shift's share of the booking total |
| `deleted_at` | Shift removed from the booking |

A slot never has its own dates or status, so freeze, expiry, extension, close and delete of
the booking apply to all its shifts without extra code.

### Seat checks

`App\Services\SeatSlotQuery` runs an existing seat-booking query a second time with
`learner_detail` swapped for a derived table of slot rows (booking dates/status, slot
seat/shift). Seat checks call `SeatSlotQuery::get/exists/count/pluckSum` instead of
`->get()/->exists()/->count()/->pluck()`:

- `checkAvailability`, `checkSeatAvailability`, `checkPlanTypeSeatWise`, `seatHeldByFuture`,
  `filterPlantypeFromseat` (helpers)
- `PlanService::getAvailablePlanTypes`, `SeatAvailabilityService` (seat map, swap checks),
  `ShiftTimingService::bookedWindows`, QR seat-wise check

With no slots the results are identical to before.

Migration (run on its own, see above):

```bash
php artisan migrate --path=database/migrations/2026_10_29_000002_create_learner_detail_slots_table.php
```

```sql
CREATE TABLE `learner_detail_slots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `library_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED NOT NULL,
  `learner_id` BIGINT UNSIGNED NOT NULL,
  `learner_detail_id` BIGINT UNSIGNED NOT NULL,
  `seat_no` VARCHAR(255) NOT NULL,
  `plan_type_id` BIGINT UNSIGNED NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL, `deleted_at` TIMESTAMP NULL,
  KEY `idx_lds_detail` (`learner_detail_id`),
  KEY `idx_lds_branch_seat` (`branch_id`, `seat_no`),
  KEY `idx_lds_learner` (`learner_id`)
);
```

### Booking form and save

- On a numbered seat the booking popup lets the owner pick several **fixed-time** shifts
  on the shift cards (Add). The first is the main shift; a shift that overlaps a picked one
  is disabled. Duration, free and flexible shifts are booked on their own, and other seats
  are not offered yet (the backend already accepts them). Picked shifts post `extra_shifts[k][seat_no|plan_type_id|shift_start_time|shift_end_time|price]`.
- `plan_price_id` is the booking total (main shift + extra shifts), so the existing
  totals, discount, paid and pending logic is unchanged. The main shift's own price is
  `plan_price_id − sum(extra prices)`.
- `StoreLearnerRequest` validates the rows (numbered seat only, no flexible, timing for
  templates, prices within the total) and resolves templates to timing rows.
- `LearnerService::processLearnerStore` creates the booking, then
  `MultiShiftService::attach()` checks each extra shift with `checkAvailability()` (it
  sees the main shift and earlier extra shifts in the same transaction) and stores it.
  Any clash rolls the whole booking back.
- Receipts and the booking-done screen list every seat and shift
  (`MultiShiftService::describe()`); one transaction covers them all.

### Where extra shifts show

- Seat map (`learner/seat.blade.php`): slot rows are added to the seat they occupy, with
  " · Extra shift" on the shift label; View opens the main booking. Seat hours and future
  bookings include slots.
- Seat details drawer (`showLearner` → `multi_seats`, `multi_shifts`, `multi_count`): title
  lists every seat, subtitle every shift.
- Seat history list, occupancy report, dashboard available seats: slots counted on their seat.
- Receipt and booking-done screen: every seat and shift.

Not yet: the past-bookings page of a single seat (`history($id)`) lists only main bookings.

### Operations on a multi-shift booking

All go through `LearnerOperationService::process()`:

| Operation | Extra shifts |
|---|---|
| Renew, Upgrade, Reactivate (new booking row) | `MultiShiftService::carry()` copies the kept shifts to the new booking, priced for the new plan and checked for the new period (`checkAvailability`, plus no overlap with the booking's own shifts). A clash refuses the whole operation. |
| Change Plan, Edit (same booking row) | `MultiShiftService::reprice()` keeps the shifts, removes the dropped ones, re-prices them for the plan and re-checks them for the booking's dates. |
| Freeze, extension, close, delete | Follow the booking, nothing to do. |

- **Price.** Web operation forms send `learner_id` to `getPricePlanwise`, which then returns
  main + kept extra shifts, and post `slot_prices_included=1`. Other callers (mobile app)
  post the main price only, so the server adds the extra shifts' prices; anything not paid
  becomes pending.
- **Removing a shift.** Operation forms list the booking's extra shifts
  (`learner/partials/extra-shifts-carry`, renew popup `#renewCarryShifts`); a ticked one is
  posted in `drop_slot_ids[]` and is not carried / is removed.
- **QR self-renewal** (`QrBookingService`) creates the new booking with the main shift only;
  renew multi-shift bookings from the panel to keep their extra shifts.

### Advanced shifts and added shifts on the plan pages

Upgrade, change plan and reactivate list every shift of the branch
(`learner/partials/plan-shift-options`): free fixed shifts, the duration/free templates
(flexible on a general seat), and the fixed shifts the seat can't take, disabled as
"Not free". `learner/partials/op-shift-extras` + `public/js/lb-opshift.js` add:

- **Timing.** A duration/free main shift shows `shift_start_time` / `shift_end_time`;
  a free shift's price is typed in the plan price field. `LearnerOperationRequest` validates
  it as the booking form does, and `LearnerOperationService::resolveShiftTiming()` swaps the
  template for its hidden timing row (`ShiftTimingService::resolveChild`) before the seat
  checks. A free shift's own price is the total minus the booking's other shifts.
- **Added shifts.** With a fixed main shift on a numbered seat, more fixed shifts on the same
  seat can be ticked (edit too). They post as `extra_shifts[]` and their prices are added to
  the plan price field. `MultiShiftService::attach()` adds them after `carry()` / `reprice()`,
  checking the seat and the booking's own shifts (main, kept and added).
- **Billing.** When the booking holds extra shifts, a free shift or a custom plan, the form's
  plan price (`slot_prices_included=1`) is billed as the booking total.

## Importing learners (CSV)

`Controller::uploadCsv` / `validateAndInsert` (page: `library/csv.blade.php`). Every older
import rule stays as it was; advanced shifts add three optional columns and the same checks
as the booking form:

| `plan_type` is a… | Needs | Booked on |
|---|---|---|
| fixed shift | nothing new (leave the shift columns empty) | the shift itself |
| Fixed hours (`duration`) | numbered `seat_no` + `shift_start_time` | hidden timing row (`ShiftTimingService::resolveChild`) |
| Direct booking (`free`) | numbered `seat_no` + `shift_start_time` + `shift_end_time` + `shift_price` (empty → paid + pending) | hidden timing row with that price |
| Flexible hours (`flexible`) | empty `seat_no` (general seat) | the template |

- Times may be `06:00`, `6 AM`, `6:30pm` or Excel's `6:00:00 AM`; `ShiftTimingService::validateTiming`
  keeps them inside the library hours.
- After the older seat checks, `importSeatClash()` refuses a numbered-seat row whose time overlaps
  a booking on that seat for overlapping dates (`clashOnSeat`, so extra shifts count too). Rows
  earlier in the same file count as booked.
- The file reader accepts a UTF-8 BOM, header cells in any case/spacing (`Plan Type`), blank
  rows, trailing empty cells and files without the optional columns. An `.xlsx`/`.xls` upload is
  refused with a "save as CSV" message.
- Failed rows are listed on the page with their CSV row number (`csv_row`) and reason, grouped
  by reason; "Download rows to fix" gives the same list as a CSV. A clean import clears an older list.

## Library hours (open – close time)

`hour.open_time` (TIME, nullable) is when a branch opens; with `hour.hour` it closes at
open_time + hour (past midnight when needed, e.g. 6 PM + 16 hrs = 10 AM). A 24 hour branch
stores NULL. `App\Services\BranchHoursService` reads and checks it.

| Where | Rule |
|---|---|
| Add branch, Edit branch, Operating hours page | Opens at + Closes at (or "Open 24 hours" on a plan with All Day). The hour count is worked out from them: whole hours, at least 10. Changing them is refused while a saved shift, or a timing a current learner is booked on, would fall outside |
| Shifts page (and mobile shift APIs, admin shift page, Plan Type modal) | Every shift with a time lies inside the library hours. Advanced shifts (fixed hours, flexible hours, direct booking) have no time of their own: the fields show the library hours, locked, and the row is stored as 00:00 – 00:00. Their hours per day can't be more than the library is open. They can't be added until the open time is set (24 hour branches excepted) |
| Booking form, renew / upgrade / change plan | `ShiftTimingService::validateTiming` and `freeGaps` use the library hours as the window of an advanced shift, so a 4 hr shift in a 6 AM – 10 PM library starts between 6 AM and 6 PM, and the free slots shown are inside the hours |

An older advanced shift saved with its own window keeps it while learners are on it; the
next save without learners moves it to the library hours.

### Running it

```bash
php artisan migrate --path=database/migrations/2026_11_05_000004_add_open_time_to_hour_table.php
php artisan branches:fill-open-time --dry-run   # see what would be filled
php artisan branches:fill-open-time
```

Equivalent SQL:

```sql
ALTER TABLE `hour` ADD COLUMN `open_time` TIME NULL DEFAULT NULL AFTER `hour`;
```

`branches:fill-open-time` sets the open time of every branch under 24 hours from its shifts:
the start of its earliest shift in the tightest window of `hour` hours that holds them all
(also across midnight). It lists the branches it can't fill: no shifts yet, or shifts spread
over more hours than the library is open. Their owners see "Set open & close time" on the
Shifts page. Without artisan, this SQL fills the branches whose shifts don't cross midnight
(run the command for the rest):

```sql
UPDATE `hour` h
JOIN (
  SELECT branch_id, MIN(start_time) AS opens, MAX(end_time) AS closes
  FROM plan_types
  WHERE deleted_at IS NULL AND parent_id IS NULL
    AND NOT (start_time = '00:00' AND end_time = '00:00')
  GROUP BY branch_id
  HAVING SUM(end_time <= start_time) = 0
) s ON s.branch_id = h.branch_id
SET h.open_time = s.opens
WHERE h.deleted_at IS NULL AND h.open_time IS NULL AND h.hour < 24
  AND TIME_TO_SEC(TIMEDIFF(s.closes, s.opens)) <= h.hour * 3600;
```

Rollback: `ALTER TABLE `hour` DROP COLUMN `open_time`;`
