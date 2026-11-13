# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Libraro — a multi-tenant SaaS for managing library/study-hall (reading room) businesses. Laravel 10 / PHP 8.1 monolith with Blade + Vue 3 (via Vite) for the frontend. Each tenant is a "Library" (with "Branches" underneath), and end customers are "Learners" who book seats/plans, pay, get QR-based attendance, receipts, and ID cards.

## Common commands

```bash
composer install                 # PHP deps
npm install                      # JS deps
npm run dev                      # Vite dev server (HMR)
npm run build                    # Vite production build

php artisan serve                # local dev server (app is normally served via XAMPP/Apache at htdocs/library)
php artisan migrate              # run migrations
php artisan config:clear && php artisan cache:clear   # after .env or config changes

php artisan test                 # run full test suite (PHPUnit)
php artisan test --filter=TestName       # run a single test by name
vendor/bin/phpunit tests/Feature/Path/To/FileTest.php   # run a single test file

vendor/bin/pint                  # code style fixer (Laravel Pint)
```

There is no configured JS/PHP linter beyond Pint, and no CI config in this repo — style is enforced via `.editorconfig` (4-space indent, LF line endings) and Pint.

## Architecture

### Multi-guard authentication (four distinct user types)

`config/auth.php` defines four separate session guards, each with its own model/table and session name — there is no single "user" concept:

- `web` → `App\Models\User` (platform administrators, `administrator/*` routes)
- `library` → `App\Models\Library` (tenant/library owners, `library/*` routes)
- `library_user` → `App\Models\LibraryUser` (staff accounts under a library)
- `learner` → `App\Models\Learner` (end customers)

Matching `*_api` sanctum guards exist for API auth (token based, no session — the mobile app is not affected by the portal sessions below).

**Portal sessions.** Each web portal has its own session cookie, so an admin, a library and a learner can be signed in on one browser at the same time: `admin` (guard `web`), `library` (guards `library` + `library_user`, keeps the original `SESSION_COOKIE`, also used by public pages) and `learner` (guard `learner`). `App\Http\Middleware\UsePortalSession` runs first in the `web` group and picks the cookie from the route tag `portal:<name>`; untagged routes use the library portal, and `portal:auto` (login, logout, CSRF refresh) reads `?portal=`, the login `user_type`, or the Referer. It also drops remember-me cookies of other portals. Config lives in `config/session.php` → `portals`. **Any new admin route must sit in the `portal:admin` group and any new learner route in the `portal:learner` group** (`PortalSessionTest` checks this). Logout ends only the current portal; use `currentPortal()` when building logout / CSRF refresh URLs. `logoutOtherGuards()` now only logs out sibling guards of the same portal (library owner vs staff). When touching auth flows, check which guard a route/controller expects before assuming `Auth::user()` — use `getAuthenticatedUser()` (checks `library`, `library_user`, `web`, `learner` in that order) instead where the guard is ambiguous.

### Tenancy: library_id and branch_id scoping

Data is scoped two levels deep:

1. **Library (tenant) scoping** — `App\Models\Scopes\LibraryScope` is a global scope that filters by `library_id` using `getLibraryId()`, applied to tenant-owned models.
2. **Branch scoping** — the `App\Traits\HasBranch` trait adds a global scope filtering by `branch_id` based on the current guard user's `current_branch`, and auto-fills `branch_id` on `creating()`. It's skipped when the `learner` guard is active. Apply this trait to any new model that belongs to a branch.

`getLibraryId()`, `getBranch()`, `getLibrary()`, `getAuthenticatedUser()` and friends live in `app/Helpers/encryption_helper.php`, autoloaded globally as a Composer `files` entry (see `composer.json`) — they're available everywhere without importing. `app/Helpers/HelperService.php`, `ReferralHelper.php`, and `privacy_helper.php` provide additional cross-cutting helpers (breadcrumbs/titles, referral logic, PII handling).

### Layered business logic: Services

Controllers delegate non-trivial logic to `app/Services/*`, one per domain concern (e.g. `LearnerLifecycleService`, `LearnerSeatSwapService`, `SeatAvailabilityService`, `QrBookingService`, `LibraryPaymentService`, `ReceiptService`, `ReferralRewardService`, `SubscriptionPermissionService`). When adding features to learners/seats/billing/referrals, look for an existing service first rather than putting logic in the controller.

### QR-driven booking & attendance flow

`QrEntryController` + `QrBookingService` implement the public seat-booking funnel reached by scanning a branch QR code (`/qr/b/{uuid}` → plan selection → `/branch/{uuid}/book-seat` → payment → `/booking/{id}/payment-qr` or offline confirmation). `AttendanceController` implements a parallel QR-scan attendance flow (`/qr/attendance/link` → scan → learner verification → success). Both are unauthenticated, UUID-keyed public routes distinct from the authenticated dashboard routes.

### Receipts, ID cards, PDFs

`ReceiptService`/`ReceiptController` and `IdCardService`/`IdCardController` generate PDFs via `barryvdh/laravel-dompdf` and `mpdf/mpdf` (both are in use — check which a given feature already uses before adding a third approach).

### Notifications

`NotificationChannelSetting`, `NotificationTemplate`, `NotificationLog`, `NotificationSubscription` model a per-library configurable notification system (channel + template driven), separate from Laravel's built-in notifications. `NotificationController` / `NotificationSentController` manage templates and sent-history respectively. Twilio SDK is used for SMS.

### Calling CRM (admin portal)

`/administrator/crm/*` — leads with stages / sub-stages, calls with dispositions, follow-up reminders, WhatsApp / SMS templates, imports and a lead dashboard (`app/Services/Crm`, `app/Models/Crm`, `resources/views/crm`). Registered-but-unpaid libraries become leads automatically (`crm:sync-libraries`). Permissions are `crm-*` on the `web` guard; routes use `can:has-permission,'crm-…'` — keep the permission **quoted**, a bare word is read as a route parameter and lets everyone through. Details: `docs/CALLING_CRM.md`.

### Referral & rewards

`LibraryReferral`, `LibraryReferralVisit`, `LibraryRewardPoint`, `LibraryRewardRedeem`, `ReferralWallet`, plus `ReferralHelper` and `ReferralRewardService`, implement a referral-tracking and points/redemption system for libraries.

### Licensing

`App\Http\Middleware\LicenseCheck` gates access based on a machine-bound license key (MAC address hash, matched against `storage/framework/cache/.hidden_license_key`), separate from Laravel/Sanctum auth — relevant if a local dev environment reports 403 unexpectedly.

### Payments

Razorpay SDK (`razorpay/razorpay`) handles online payments (seat booking, plan renewals); `WebhookController` handles payment webhooks; `LibraryPaymentService`/`BillingAmountService` compute amounts and reconcile transactions.

### Frontend

Blade views + Vue 3 components built via `laravel-vite-plugin`/Vite, Bootstrap 5 — **except the platform admin portal** (`layouts/admin.blade.php`), which uses Tailwind CSS v4 (standalone CLI, `bin/build-admin-css` → `public/css/admin.min.css`), an Apple-style design system (`resources/css/admin/ds.css`, `<x-ui.*>` components in `resources/views/components/ui`) and `public/js/ui-kit.js` instead of Bootstrap's JS. Read `docs/ADMIN_UI.md` before touching admin views. `resources/` holds views/assets; there's no SPA — Vue is used for islands of interactivity within Blade pages. `yajra/laravel-datatables-oracle` backs server-side DataTables listings (check controllers for `DataTables::of(...)` patterns before writing new listing/filtering endpoints by hand).

### API documentation

`docs/API.md` is the source-of-truth reference for every `routes/api/v1.php` (and `routes/api.php`) endpoint's request payload and response shape, with datatypes — hand-written by reading each controller's validation rules and response construction, since this codebase has no OpenAPI/Resource layer to generate it from. Any change to a route, its controller's validation rules, or its response structure **must** update the matching section in `docs/API.md` in the same PR.

### Known repo quirks

- `app/Http/Controllers/BranchController copy.php` and `LibraryController copy.php` are stray backup files (note the space in the filename) — don't confuse them with the real controllers.
- Both a `database/` and a top-level `db/` directory exist; migrations live under `database/`.
