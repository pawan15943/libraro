# Advanced Shifts

Lets a library owner book a numbered seat for a shift whose timing is chosen at booking
time, instead of creating a new shift in the master for every timing, and book a general
seat for a flexible shift (any time in a range, for N hours).

| Seat | Shifts offered on the owner booking form |
|---|---|
| Numbered | fixed, duration, free |
| General | fixed, flexible |

QR booking, the API and other lists keep offering fixed shifts only.

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
