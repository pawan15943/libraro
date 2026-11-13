# Calling CRM

Lead calling module of the platform admin portal (`/administrator/crm/*`, guard `web`). The sales team uses it on phones and desktops to call library owners, log every call with its result, set follow-ups, send WhatsApp / SMS reminders and track performance.

## Screens

| Screen | Route | What it is for |
|---|---|---|
| Lead Dashboard | `crm.dashboard` | KPIs, top 5 performers (week / month), calls per day, leads by stage, call results, best time to call, sources, team table |
| Call Queue | `crm.queue` | Leads to call now. Tabs: Due now, Never called, Registered – not paid, Hot, All open |
| All Leads | `crm.leads.*` | Search, filters, bulk assign, CSV export, add / edit, lead page with timeline |
| Follow-ups | `crm.reminders.index` | Overdue / Today / Upcoming / Done |
| Call Log | `crm.calls.index` | Every call with result, duration, notes |
| Import Leads | `crm.import` | CSV upload or pasted list; "Sync registrations" |
| CRM Settings | `crm.settings.index` | Stages + sub-stages, dispositions, WhatsApp / SMS templates |

## Rules

| Rule | Detail |
|---|---|
| Queue | Only leads in open stages. Enrolled, Not Interested and Invalid (`crm_stages.is_closed`) never show in the queue. |
| Registered, not paid | Libraries with `is_paid = 0` become leads (source `signup`). A lead whose library (same id, or the same mobile) is paid moves to Enrolled. Runs hourly (`crm:sync-libraries`), from "Sync registrations", and at most every 5 minutes when a CRM page opens. |
| Duplicates | One lead per mobile (last 10 digits, must start with 6–9). Imports skip numbers already in the CRM. |
| Visibility | `crm-view-all-leads` sees every lead. Others see their own and unassigned (open pool) leads. The first agent to log a call on an unassigned lead owns it. |
| Call | The Call button opens the dialer and the "Log call" sheet with a timer. If the agent leaves the browser for the call, the sheet opens again when they come back. |
| Previous conversation | The "Log call" sheet first shows the lead's pinned notes and every earlier call (result, notes, agent, duration, stage it led to, next call), plus notes and WhatsApp / SMS messages, newest first (`crm.calls.history`). Three show at once; "Show all" opens the rest. |
| Disposition | Moves the lead to the disposition's stage / sub-stage, unless the agent picks one, and suggests the next call after `follow_up_minutes`. |
| Follow-ups | One open follow-up per lead; a new one replaces it. A call within 30 minutes of a follow-up marks it done. Closing a lead cancels its follow-ups. |
| Alerts | `public/js/admin-crm-notify.js` checks `crm.reminders.due` every minute on every admin page: badge, toast, and a browser notification after "Turn on alerts". Each follow-up alerts once (`notified_at`). |
| WhatsApp / SMS | Opens `wa.me` / `sms:` with the template text filled in (no API); the text is saved on the lead's timeline. Placeholders: `{name}`, `{library}`, `{agent}`, `{mobile}`, `{login_link}`. |
| Conversions | A move into a won stage writes an `enrolled` activity, credited to whoever made the change, or to the lead's agent when the sync makes it. Leads nobody worked (no calls) that the sync closes are not counted. |
| Performer score | 1 per call + 2 per connected call + 25 per enrolment. |

## Permissions (guard `web`, category "Calling CRM")

`crm-view-dashboard`, `crm-view-leads`, `crm-view-all-leads`, `crm-create-leads`, `crm-edit-leads`, `crm-delete-leads`, `crm-assign-leads`, `crm-import-leads`, `crm-export-leads`, `crm-log-calls`, `crm-send-messages`, `crm-manage-reminders`, `crm-manage-settings`.

Roles made by the migration: **CRM Manager** (all 13), **Calling Agent** (dashboard, leads, create, edit, log calls, messages, reminders). `superadmin` gets all 13 as well. Routes use `can:has-permission,'crm-…'`. The permission must be quoted, otherwise Laravel reads it as a route parameter and the gate receives `null`.

## Code

| Piece | Role |
|---|---|
| `database/migrations/2026_11_11_000001_create_calling_crm_tables.php` | Tables, default stages / dispositions / templates, permissions, roles; copies the old `lead_contacts` rows |
| `app/Models/Crm/*` | `CrmLead`, `CrmStage` (stage when `parent_id` is null, else sub-stage), `CrmDisposition`, `CrmCall`, `CrmReminder`, `CrmActivity` (timeline), `CrmTemplate`, `CrmImport` |
| `app/Services/Crm/CrmLeadService.php` | Every change to a lead (create, edit, stage, call, reminder, message, assign, import) + timeline |
| `app/Services/Crm/CrmSyncService.php` | Library sign-ups ↔ leads |
| `app/Services/Crm/CrmDashboardService.php` | Dashboard numbers |
| `app/Http/Controllers/Crm/*` | One controller per screen |
| `resources/views/crm/*` | Views; `partials/sheets.blade.php` holds the Log call, Message, Follow-up and Status sheets |
| `public/js/admin-crm.js` | Sheets, call timer, templates |
| `resources/css/admin/crm.css` | CRM styles (part of the Tailwind admin build, see docs/ADMIN_UI.md) |
| `tests/Feature/CallingCrmTest.php` | Permissions, call logging, status, follow-ups, messages, import, export, settings |

The old `/leads` page redirects to the call queue.
