# Custom Plan

Lets a library owner book a seat for any start and end date instead of a fixed plan
(1 Month, 3 Months…). The price is worked out from the 1 Month price and the owner can
change it.

Offered on the owner forms: new booking, upgrade, renew (keeps the current custom length),
change plan, reactivate and edit plan. QR booking, the learner app and the owner API keep
offering the normal plans only.

## How it works

A custom booking is stored against a hidden `plans` row:

| Column | Value |
|---|---|
| `type` | `CUSTOM` |
| `plan_id` | Number of days, start and end included |
| `is_custom` | `1` |
| `name` | `Custom · 45 Days` |

`App\Services\CustomPlanService` finds or creates that row, one per library and day count,
reused by every booking of the same length. `learner_detail.plan_id` holds its id, so
everything that works from a plan id works on it without changes: `getEndDate`, prices,
locker amount, seat checks, renew, auto-renew of a non-expiry seat, receipts and reports.

| Helper | Custom plan |
|---|---|
| `getEndDate()` | start + days − 1. A fixed billing date does not move it. |
| `getPlanPrice()` | No `plan_prices` row, so the existing fallback: 1 Month price ÷ 30 × days |
| `getBillingCyclePrice()` | Same price (no pro-rating) |
| `getLockerPrice()` | Branch locker amount ÷ 30 × days, as for a DAY plan |

Hidden rows never appear in plan lists, dropdowns, counts or the price master
(`Plan::scopeListable`), and the library configuration save does not delete them.

## Forms

The plan dropdown gets a "Custom dates" option (`learner/partials/custom-plan-options`),
driven by `learner/partials/custom-plan-dates` and `public/js/lb-custom-plan.js`. The form's
own start and end date fields are used: in the booking popup the "Valid till" field turns into
a date input while Custom is picked, next to an editable price.

1. The owner picks "Custom" and sets the end date.
2. The page posts the start and end date to `POST custom-plan/resolve` (`customPlan.resolve`),
   which returns the hidden plan `{id, name, days, end_date}`.
3. The plan is added to the dropdown and selected. The page's own code then loads the price,
   end date and locker amount as for any plan.
4. The plan price becomes editable. The posted price is what the booking stores.

Changing the end date picks a new length. Changing the start date keeps the length and moves
the end date. While "Custom dates" is chosen without an end date the plan value is empty, so
the form can't be saved.

The edit plan form has no plan dropdown: its Plan End Date is editable, and changing it makes the
booking a custom plan of that length (the hidden plan id is set; the price becomes editable).

## Database changes

Only `plans` changes.

| Column | Type | Default | Purpose |
|---|---|---|---|
| `is_custom` | tinyint(1) | 0 | Hidden plan created for a custom booking |

Index: `idx_plans_library_custom_days (library_id, is_custom, plan_id)`.

### Running the migration

As for the advanced shifts migrations, run only this file:

```bash
php artisan migrate --path=database/migrations/2026_11_03_000001_add_is_custom_to_plans_table.php
```

Equivalent SQL:

```sql
ALTER TABLE `plans`
  ADD COLUMN `is_custom` TINYINT(1) NOT NULL DEFAULT 0 AFTER `monthdays`,
  ADD INDEX `idx_plans_library_custom_days` (`library_id`, `is_custom`, `plan_id`);
```

Rollback:

```sql
ALTER TABLE `plans`
  DROP INDEX `idx_plans_library_custom_days`,
  DROP COLUMN `is_custom`;
```
