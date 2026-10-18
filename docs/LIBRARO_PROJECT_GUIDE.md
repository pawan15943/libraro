# Libraro: Project Guide (working reference)

Single-page orientation for anyone (human or AI) starting work on this repo. It summarises how the project is built and run, and the conventions we follow. Deep detail lives in the docs it links to; this file tells you where to look and what to watch out for.

Last reviewed: 2026-10-18 (branch `staging`).

---

## 1. What Libraro is

Multi-tenant SaaS for **library / study-hall (reading room) businesses** in India.

- **Library** = tenant (owner account). A library has one or more **Branches**.
- **LibraryUser** = staff account under a library, with per-user permissions and assigned branches.
- **Learner** = end customer who books a **seat** on a **plan** (duration) + **plan type** (shift/slot), pays, gets a receipt, ID card and QR attendance.
- **User** = platform admin / superadmin (Spatie roles).

Stack: **Laravel 10, PHP 8.1, MySQL** (XAMPP locally, `DB_DATABASE=libraro`, `APP_URL=http://localhost/libraro/`).
Packages: Sanctum (API tokens), Spatie Permission, Yajra DataTables, DomPDF + mPDF, Razorpay, Twilio, simple-qrcode, Gemini (AI assistant).

Two front doors:
1. **Web module**: server-rendered Blade dashboards for admin, library owner/staff and learner, plus public QR booking/attendance pages and the marketing site.
2. **API module** (`/api/v1/*`): JSON API for the **Library staff mobile app** (owner/staff) and the **Learner mobile app** (self-service).

---

## 2. Repo layout (what matters)

```
index.php, .htaccess        App is served from the PROJECT ROOT (not /public). Assets are referenced as asset('public/...').
routes/web.php              All web routes (~600 lines, one file)
routes/api.php              Mounts routes/api/v1.php under /api/v1 + 2 cron-style GET routes
routes/api/v1.php           All mobile API routes
app/Http/Controllers/       Web controllers (LearnerController is ~4.8k lines, LibraryController ~2.4k)
app/Http/Controllers/Api/V1/            Library-app API controllers
app/Http/Controllers/Api/V1/Auth/       LibraryAuthController (owner+staff login/profile/branch config), LearnerAuthController
app/Http/Controllers/Api/V1/Learner/    Learner-app controllers (LearnerAppController, LearnerBookingController, LearnerBranchController)
app/Services/               Business logic, one class per domain (see section 6)
app/Http/Requests/          StoreLearnerRequest, LearnerOperationRequest, LearnerRenewRequest, StoreLibraryRequest
app/DTO/LearnerOperationDTO.php, app/Enums/LearnerOperation.php   Learner operation pipeline
app/Helpers/encryption_helper.php   ~3.2k lines of GLOBAL helper functions (autoloaded via composer "files")
app/Helpers/HelperService.php       Breadcrumbs, titles, activity formatting (also autoloaded)
app/Helpers/privacy_helper.php      Learner mobile/email masking
app/Traits/HasBranch.php            Branch global scope + auto-fill branch_id
app/Models/Scopes/LibraryScope.php  library_id global scope
resources/views/            Blade views, grouped by module (learner/, library/, master/, report/, administrator/, ...)
public/css/<module>.css     One stylesheet per module/feature
public/js/main-scripts.js, main-validation.js   Shared jQuery code
docs/                       API.md (source of truth), PERMISSIONS.md, LEARNER_MOBILE_API.md, Postman collections
database/migrations/        Migrations (incremental; the base schema predates them, see the db/*.sql dumps)
db/, libraro_final.sql, menus.sql   Old SQL dumps / seed data for menus & permissions
```

Ignore / don't confuse:
- `BranchController copy.php`, `LibraryController copy.php`, `learner/script.blade01-02-2026.php`: stray backups.
- `scratch/`, `inspect_system.php`, `error_log`: local debugging leftovers.
- `BACKEND_API_DOCUMENTATION.md`: an *aspirational* learner-app spec (`/auth/login`, JWT, `success`/`statusCode` envelope). It does **not** match the implemented API. Use `docs/API.md` and `docs/LEARNER_MOBILE_API.md` instead.
- **No `package.json` / Vite config exists.** CLAUDE.md mentions Vite + Vue 3, but the frontend is plain Blade + jQuery with CDN libraries. `resources/js` only has the Laravel stub.

---

## 3. Authentication: 4 user types, 8 guards

| Type | Model / table | Web guard (session) | API guard (Sanctum) |
|---|---|---|---|
| Platform admin | `User` / users | `web` | `web_api` |
| Library owner | `Library` / libraries | `library` | `library_api` |
| Library staff | `LibraryUser` / library_user | `library_user` | `library_user_api` |
| Learner | `Learner` / learners | `learner` | `learner_api` |

Rules:
- Only one guard should be logged in at a time: `AuthenticateLibraryOrUser` (alias `auth.library_or_user`), `EnforceSingleGuard` (`enforce.guard`) and `logoutOtherGuards()` enforce this on web.
- When the guard is ambiguous, use the global helpers, **not** `Auth::user()`:
  `getAuthenticatedUser()` (web guards only: library, library_user, web, learner), `getLibraryId()`, `getCurrentBranch()`, `getBranch()`, `getLibrary()`. The last three also resolve API guards.
- Owner-only actions: middleware `library.owner` (`LibraryOwnerOnly`) blocks staff on both web and API.
- Deactivated staff: `library_user.active` revokes the token immediately.
- Library owner web routes also need `verified.library` (email verified). Most library web routes also log through `log.requests`.
- Learner web login: `/learner/login`. Learner app login: identifier (UID or email) + password (mobile number).
- `LicenseCheck` (`license.check`) is a MAC-bound license gate. It's registered as an alias but check before assuming it's active anywhere.

---

## 4. Multi-tenancy & branch scoping

Two levels:
1. **library_id**: `LibraryScope` global scope (`where library_id = getLibraryId()`), used on `Seat`, `PlanType`, `LearnerTransaction` and others.
2. **branch_id**: `HasBranch` trait. It adds a `branch` global scope using the logged-in user's `current_branch` and auto-fills `branch_id` on create. It is skipped for the learner guard. Used on `Learner`, `LearnerDetail`, `LearnerTransaction`, `PlanPrice` and others. `PlanType` has its own inline branch scope (`getCurrentBranch()`).

Switching branch = updating `current_branch` on the owner/staff record (`branch/switch` web, `library/branch/switch` API).

⚠️ **Gotchas**
- `LibraryScope` only applies when `getAuthenticatedUser()` returns a user, and that helper only checks the **web/session** guards. On API (Sanctum) requests the library scope therefore does not kick in automatically. API code must filter by `library_id` / `branch_id` explicitly (most services do). Keep doing so in new API code.
- `Learner`, `LearnerDetail` and `LearnerTransaction` add a `withTrashed` global scope, so **soft-deleted rows are included by default**. Filter on `deleted_at` / status yourself when you need live records only.
- Need data across branches? Use `withoutGlobalScope('branch')` (e.g. `PlanType::byBranch($id)`).

---

## 5. Core domain model

```
Library 1─* Branch 1─* Floor / Seat / Hour (operating hours) / PlanType / PlanPrice
Library 1─* Plan (durations: 1 month, 2 months...)          Library 1─* LibraryUser
Learner (person; seat_no, plan_type_id, status, library_id, branch_id, encrypted email/mobile)
  1─* LearnerDetail (table learner_detail; one row per plan period:
        plan_id, plan_type_id, plan_price_id, seat_no, plan_start_date, plan_end_date, join_date, is_paid, status)
  1─* LearnerTransaction (billing: total_amount, paid_amount, pending_amount, discount_amount, locker_amount, payment_mode, is_paid, paid_date, learner_detail_id)
  1─* LearnerTransactionActivity (payment ledger lines: dr_cr, particular, amount)
  1─* LearnerOperationsLog (audit trail of operations)
Booking   = pending seat request from public QR form / learner app (encrypted email/mobile), verified by staff
Attendance, LearnerFeedback, Complaint, Suggestion, Notification*, LibraryReferral/RewardPoint/Redeem/ReferralWallet
Subscription (Libraro plans for libraries: library_type 1=Basic 2=Smart 3=Pro) + subscription_permission
Menu / SubMenu (sidebar, DB-driven with has_permissions), Permission / PermissionCategory
```

- **Plan types** are shifts or slots: Full Day, First Half, Second Half, All Day, Full Night, hourly slots, custom. Seat availability depends on time-overlap of plan types on the same seat (`SeatAvailabilityService`, `planTypeTimesOverlap` helpers).
- **Seats** can be numbered (seat map with floors) or "general" (no seat number).
- Learner email/mobile are **encrypted at rest** (`encryptData`/`decryptData` accessors). Search by these fields means encrypting the search term first (see `LearnerService::getLearnersList`).
- Learner statuses / list filters: `active, about_to_expire, extended, future, expired, deleted, closed, pending_payment, non_expiry`. Extension days per branch control the "extended" grace period. `no_expiry` learners auto-roll cycles (`LearnerService::runDailyUpdate`).

---

## 6. Services: where business logic lives

Controllers should validate, call a service and shape the response. Look for an existing service before adding logic:

| Service | Responsibility |
|---|---|
| `LearnerService` (3k lines) | Learner create (`processLearnerStore`), details, lists & filters, seat map, daily status update |
| `LearnerOperationService` | Operation pipeline for edit / renew / upgrade / change_plan / reactive / swap / close / delete / freeze / gift_days / refund / misc_payment (driven by `LearnerOperationDTO` + `LearnerOperation` enum) |
| `LearnerLifecycleService` | Close/delete, settlement, transactions & activity CRUD, renew eligibility |
| `LearnerDeleteService`, `RenewTransactionDeleteService` | Delete flows with settlement |
| `LearnerSeatSwapService` | Seat swap (shared by web + API) |
| `LearnerGiftDaysService` | Gift days |
| `SeatAvailabilityService` | Available seats, seat status codes, swap eligibility (shared web + API) |
| `PlanService`, `BillingAmountService` | Plan/price calculation, chargeable days |
| `QrBookingService`, `BookingEnquiryService` | Public QR booking funnel and booking verification |
| `AttendanceService` | QR / ID-card / manual attendance, summaries, logs |
| `DashboardService` | Dashboard counts, financials |
| `TransactionActivityService`, `LearnerOperationLogService` | Ledger + audit logs |
| `ReceiptService`, `IdCardService` | PDFs (DomPDF and mPDF are both in use; match the feature you're touching) |
| `LibraryPaymentService`, `LibraryLifecycleService`, `LibraryService`, `LibraryConfigurationService` | Library subscription payments, library status, setup/config |
| `SubscriptionPermissionService` | Plan-based + staff permission resolution (`has()`, `permissionStatusMap()`) |
| `ReferralRewardService` | Referral points |
| `FileUploadService` | Uploads |
| `LibraroAiService` | Gemini-powered in-app AI assistant |

Scheduler (`app/Console/Kernel.php`): `DailyStatusUpdateJob` daily 07:53 (learner statuses), `library:daily-status` daily 03:00. Also `GET /api/update/learner` and `/api/delete-expired-learners` exist as cron-style URLs.

---

## 7. API module (`/api/v1`)

**Source of truth: `docs/API.md`** (every endpoint, payload and response with types). Permissions: `docs/PERMISSIONS.md`. Learner app security/flows: `docs/LEARNER_MOBILE_API.md`. Postman: `docs/*.postman_collection.json`.

### 7.1 Middleware groups (routes/api/v1.php)

| Group | Middleware | Used for |
|---|---|---|
| Public | `api_key`, `throttle:60,1` | app-settings, login/register/OTP/password reset, master static data, states/cities, plans/prices/seats lookup, info pages, demo booking, guest learner booking `learner/book-seat/{uuid}` |
| Public + device | + `device.check` | learner app-settings, learner login (`throttle:10,1`), branch seat-map/plans |
| Library app (owner + staff) | `auth:library_api,library_user_api`, `library_user.active`, `api_key`, `throttle:library_api` (120/min per user) | everything under library/*, masters (floor/plan/plantype/planprice/exam/expense), learners/*, attendance, qr-bookings, reports, referral, feedback, toggle-features |
| Learner app | `api_security`, `auth:learner_api`, `device.check`, `throttle:60,1`, prefix `learner/` | profile, dashboard, renew, notifications, subscriptions, transactions, attendance (incl. qr-scan), faq |

All of `/api/v1` also runs the `api` group (Sanctum stateful, `throttle:api`, `NormalizeApiValidationStatus`) and `check.app.version`.

### 7.2 Headers
- `X-API-KEY` (also accepts `api-key` / `api_key`): must equal `config('app.api_key')` = env `APP_API_KEY`. ⚠️ `APP_API_KEY` is **not present in the local `.env`**. If it's not set elsewhere (cached config), every API call returns 403 `API_SECURITY_FAILED` locally.
- `Authorization: Bearer <sanctum token>` (token name `library_token` for owner/staff).
- `X-Platform` (`android`|`ios`) + `X-Device-Id`: required where `device.check` / `api_security` apply.
- `X-App-Version`: checked when `LIBRARY_FORCE_UPDATE` is on → 426 `APP_UPDATE_REQUIRED`.
- Learner app (`api_security`) also needs `X-Timestamp` (ms, ±5 min), `X-Nonce` (single-use, cached 5 min), `X-Signature` = `hmac_sha256("METHOD|URI|TIMESTAMP|NONCE|BODY", APP_HMAC_SECRET)`.

### 7.3 Response conventions (how we write API responses)
- Envelope built **by hand** in each controller: `{ "status": bool, "message": string, "data": ... }`. List endpoints often add `list_count`. Errors return `data: (object)[]` in auth flows.
- Validation: either `$request->validate([...])`, `Validator::make` returning `$validator->errors()->first()` as `message`, or a FormRequest (`StoreLearnerRequest`, `LearnerOperationRequest` with `prepareData()`).
- **HTTP status normalisation:** `NormalizeApiValidationStatus` rewrites every 4xx (except 401) JSON response on `api/v1/*` to **HTTP 200 with `status:false`** and fills `message` from the first error. 401 and 5xx pass through. Clients must check `status`, not the HTTP code.
- Security-layer errors include `state_code`/`error_code` (`API_SECURITY_FAILED`, `APP_UPDATE_REQUIRED`).
- Pagination: clients send `page_no` (mapped to `page`); responses return `data->items()` + `list_count`.
- Wrap controller bodies in `try/catch`, `Log::error(...)` with line/file, return `status:false`.
- `BaseApiResource` exists (turns nulls into `""`) but most endpoints don't use resources.
- Most "read" endpoints are POST with ids in the body (e.g. `POST library/learners/detail {id}`).

### 7.4 Permissions in the API
- Call `$this->denyWithoutAppPermission(['Book Seat', 'book-seat'])` at the top of the action (defined in base `Controller`). Pass both the permission **name** and **slug**. It resolves via `SubscriptionPermissionService::has()` (owner = subscription plan permissions; staff = plan ∩ assigned permissions).
- `LearnerController::process` maps `operation` → permission via `permissionForLearnerOperation()`.
- `GET app/permissions` returns the full slug→bool map for the app UI.
- Known gaps (from PERMISSIONS.md): `swap-seat` and `gift-days/assign` dedicated endpoints skip the permission check; `exam/*` isn't gated; 8 slug collisions; soft-deleted permissions still returned.

### 7.5 Main API areas (see API.md for payloads)
Auth & profile · branch/shift configure & price · branch status/delete · Floor / Plan / PlanType / PlanPrice / Exam / Expense CRUD (`/store`, `/list`, `/detail`, `/delete`, `/status`) · library expense (separate set) · staff user management & permissions (owner only) · message templates · dashboard, revenue, financials, notifications, branch switch · referral · subscriptions & Razorpay create-order/verify (owner only) · receipt/ID-card signed links · QR bookings (list/detail/verify/delete) · learners (`seat-book`, `list`, `list-by-type`, `pending-payment`, `expired`, `extended`, `activity`, `detail`, `operation`, `close-delete`, `swap-seat`, `seat-status`, `available-seats`, `seat-map`, `gift-days/assign`, `lifecycle`, `settlement`, `other-payment`, `transactions*`) · attendance (`qr-token`, `id-scan`, `manual`, summary, logs) · monthly payment collection report · feedback, suggestions, toggle features · learner app self-service.

### 7.6 Rule when changing the API
Any change to a route, validation rule or response shape **must update `docs/API.md` in the same change** (and PERMISSIONS.md if permission checks change). The Postman collections should follow too.

---

## 8. Web module

### 8.1 Areas & routes (routes/web.php)
- **Public**: marketing site (`SiteController`: home, blog, pages, library directory `/{slug}` catch-all **last**), library register, lead/inquiry/demo forms.
- **Public QR flows** (unauthenticated, UUID-keyed, signed URLs where sensitive):
  - Booking: `/qr/b/{uuid}` → `/branch/{uuid}/book-seat` → `/booking/{id}/payment-qr` (signed) or `/booking/{id}/thank-you`; renew via `/branch/{uuid}/renew-seat`.
  - Attendance: `/qr/attendance/link` → scan → `/attendance/verify-learner` → `/attendance/success`.
  - Signed receipts / ID cards: `/receipt/*/signed/...`, `/id-card/signed/...`.
- **Library owner + staff** (`auth.library_or_user`, `verified.library`, `log.requests`): `library/home` dashboard, learners (`library/learners/*`: list, booking-info, edit, renew, upgrade, change plan, swap, reactive, close, delete with settlement, payments, pending payments, transactions, attendance, gift days, freeze, ID cards), masters (`library/library-master`, plan/plantype/floor/planPrice/expense/exam create pages → generic `master.store`), branches (owner only), staff users (owner only), reports (`ReportController`: monthly, payment collection, partial, pending, upcoming, expired, attendance, activity, learner), notifications & message templates, referral, settings, feedback, AI chat, subscription payment.
- **Admin** (`auth:web`, many under `role:superadmin`): libraries, subscriptions & plan-wise permissions, permission categories, menus, notifications, pages/blogs, admin users/roles/permissions, leads.
- **Learner portal** (`auth:learner`, `enforce.guard:learner`): `learner/home`, profile, ID card, attendance, transactions, requests/complaints/suggestions/feedback, blog.

### 8.2 How web pages are built
- Layouts: `layouts/library.blade.php` (owner/staff), `layouts/admin.blade.php`, `layouts/learner.blade.php`, `layouts/app.blade.php`. Pages `@extends('layouts.library')` + `@section('content')`.
- The library layout always includes `learner.popup` (global booking modal) and `learner.script` (shared learner JS). Changes there affect every library page.
- Partials: `partials/library-sidebar`, `library-header`, `breadcrumbs`, `footer`, `ai-chat-widget`...
- Sidebar menus come from the DB (`menus`, `sub_menus`) via `LoadMenus` middleware (cached), filtered by `has_permissions`.
- Frontend libs (CDN): Bootstrap 5.3, jQuery 3.6, DataTables 1.13 (+buttons), Select2, Choices.js, Flatpickr, Toastr, SweetAlert2, jQuery Validate, Chart.js (+datalabels), Cropper.js, Razorpay checkout, Font Awesome 6.
- Forms post via **jQuery `$.ajax` + `FormData`** with CSRF. Controllers return JSON (`{success, message, redirect}` or `{error, message}` or `{errors:{field: msg}}`); the JS shows `toastr` and marks `.is-invalid` fields. Some older pages use classic form posts with `redirect()->back()->with(...)`.
- Generic master CRUD: forms send hidden `databasemodel` (e.g. `Plan`) + `redirect`; `MasterController@storemaster` resolves `App\Models\{databasemodel}`.
- Listings: Yajra `DataTables::of(...)` server-side or client-side DataTables on `#datatable`.
- Permission checks in Blade: `@can('has-permission', 'Renew Seat')` (Gate in `AuthServiceProvider`: admin → admin_user_permissions/Spatie; library → subscription permissions; staff → library_user_permissions).

### 8.3 UI / CSS rules (from README.md, GEMINI.md, .agents/rules/ui_design.md)
- **Primary colour Navy `#18225f`.** Never Bootstrap blue (`#007bff`/`#0d6efd`). Teal accent `#34939F`, success `#22c55e`/`#16a34a`, danger `#dc3545`/`#ef4444`, dark text `#1e293b`.
- List/CRUD pages use the standard heading block (`.heading-list`), `.table-responsive > table.table.text-center#datatable`, `S.No.` first column, actions in `ul.actionalbls` (`fa-edit`, `fa-trash`, 32px rounded boxes `#f1f5f9`).
- **Every new feature/redesign gets its own CSS file** in `public/css/<module>.css`, linked with `{{ asset('public/css/x.css') }}?v={{ time() }}`. No inline `<style>` or inline styles in new work.
- **Scope all CSS under a unique module wrapper class** (e.g. `.learner-edit-module .btn-action`). No global selectors.
- Reports: no page title block; top bar = right-aligned Filters / Export CSV / Print. Emphasis weight is **600 everywhere** (no 700/800/bold); body 400/500. Seat number tag above learner name: uppercase, 0.68rem, 600, teal.
- Font: 'Outfit' for headings.

---

## 9. How we code (conventions)

- PHP style: 4 spaces, LF, Laravel Pint (`vendor/bin/pint`). Constructor property promotion and method injection of services (`public function store(StoreLearnerRequest $request, LearnerService $service)`).
- Logic goes in **Services**; controllers stay thin where possible (many legacy controllers aren't; don't add to that).
- Use global helpers (`getLibraryId()`, `getCurrentBranch()`, `getBranch()`, `getLibrary()`, `generateSeatNumbers2()`, `encryptData()`...) rather than re-deriving tenancy or seat display.
- Same business rule for web and API → put it in a shared service (e.g. `SeatAvailabilityService`, `LearnerSeatSwapService`) and reference it in a comment on both routes.
- Wrap multi-table writes in `DB::transaction` / `beginTransaction` (operation service does).
- Log errors with `Log::error($msg, ['line'=>..., 'file'=>...])`.
- New branch-owned models: add `use HasBranch;`. New tenant-owned models: add `LibraryScope` and also filter `library_id` explicitly in API code.
- Comments explain *why* (see `RouteServiceProvider` throttle notes, `LibraryOwnerOnly` docblock).
- Tests: only `tests/Feature/ApiSecurityVsAuthTest.php` is meaningful; there's no CI. Verify changes manually / with Postman.
- `.claude/agents/code-reviewer.md` defines the review checklist: correctness → security (guards) → tenancy scoping → API doc sync → service placement → tests.

## 10. Workflow

- Branch: work happens on **`staging`** (remote `github.com/pawan15943/libraro`); other local branches: `AppleUI`, `recover-my-changes`. Commit messages are short and informal ("bug fixes", "Fix Swap Issue").
- Local: XAMPP Apache serves `htdocs/libraro`; `php artisan config:clear && php artisan cache:clear` after `.env`/config edits; `php artisan migrate` for schema changes.
- Secrets live in `.env` (DB, Razorpay, Gemini, Pusher, HMAC). Never commit them. `.env2` is an alternate env file.

## 11. Open issues noted while reviewing (not fixed)

- `PORTAL_GAP_ANALYSIS.md` lists known UI/validation gaps (ID-proof upload limit 200 KB vs UI's "5 MB, PDF", duplicate `seat_no` fields in booking modal, etc.).
- `docs/PERMISSIONS.md` §4 lists permission-data quirks and unguarded endpoints.
- API requests: `LibraryScope` is inert (see §4) and `APP_API_KEY` is missing from the local `.env` (see §7.2).
- CLAUDE.md's Vite/Vue description is stale (no package.json).
