# Admin portal UI ("Libraro UI")

The platform admin portal (`layouts/admin.blade.php`, guard `web`) uses **Tailwind CSS v4** and an Apple-HIG style design system. **No Bootstrap** — neither its CSS nor its JS is loaded there. The library and learner portals still use Bootstrap; nothing below reaches them.

## Build

| What | Where |
|---|---|
| Build entry | `resources/css/admin/app.css` (Tailwind import, `@source` list, `@theme` tokens, imports below) |
| Design system | `resources/css/admin/ds.css` — tokens on `:root` + `.ui-*` component classes + behaviour primitives (modal, dropdown, collapse, tooltip, spinner, pagination) |
| Shell + legacy mapping | `resources/css/admin/legacy.css` — sidebar, toolbar, page head, DataTables skin, old markup still being converted |
| Module styles | `resources/css/admin/crm.css` (Calling CRM) |
| Output | `public/css/admin.min.css` — commit it (servers have no build step) |
| Command | `bin/build-admin-css` (`--watch` while working). Uses the Tailwind **standalone CLI** at `bin/tailwindcss` (not in git; download + `chmod +x`, and on macOS `codesign --force --sign - bin/tailwindcss`). No Node needed. |

Add a new view folder to the `@source` list in `app.css`, or its Tailwind classes are not generated.

### Cascade layers

`ds.css`, `legacy.css`, `crm.css` are imported into `@layer components`, so a Tailwind utility on an element always wins over a component rule. Plugin CSS (DataTables, toastr, flatpickr, tagify) is loaded into `@layer vendor` (lowest) from the layout's `<style>` block, so our styles always win over plugins. Font Awesome stays an unlayered `<link>` (kept for SRI) — it sets `display` on every icon, so hiding an icon needs `!important`.

### Class names that are blocked

`app.css` never generates `collapse uppercase w-25 w-50 w-75 w-100 h-25 h-50 h-75 h-100`: in Tailwind v4 `w-100` is 25rem and `collapse` is `visibility: collapse`, which would silently break old Bootstrap markup. Use `w-full`, `h-full`, `hidden`, `.ui-caption`.

## Tokens → Tailwind

`bg-tint text-tint-text bg-tint-soft` · `bg-bg bg-surface bg-surface-2 bg-surface-3` · `text-label text-label-2 text-label-3 text-label-4` · `border-sep border-sep-strong` · `bg-fill-1 … bg-fill-4` · `text-ok bg-ok-bg text-bad bg-bad-bg text-warn bg-warn-bg text-violet bg-violet-bg` · `rounded-xs … rounded-2xl` (6 / 8 / 10 / 14 / 18 / 22px) · `shadow-0 shadow-1 shadow-2 shadow-pop` · `font-sans` (SF Pro / Inter) `font-display font-mono` · `ease-spring ease-ui`.
Breakpoints are Tailwind's: `sm` 640, `md` 768, `lg` 1024, `xl` 1280. Build phone-first, then add `md:` / `lg:`.

## Components (`resources/views/components/ui`)

`<x-ui.button>` (filled / tinted / gray / outline / plain / destructive / danger / success; sm / lg; icon-only), `<x-ui.card>` (title, subtitle, icon, actions & footer slots, flush, flat, line), `<x-ui.section>`, `<x-ui.input>` (prefix, icon, hint, errors automatic), `<x-ui.select>`, `<x-ui.textarea>`, `<x-ui.switch>`, `<x-ui.check>`, `<x-ui.segmented>` (radios or links), `<x-ui.badge>` (tones, dot, pill, custom colour), `<x-ui.stat>`, `<x-ui.row>` (inset list row), `<x-ui.table>`, `<x-ui.empty>`, `<x-ui.banner>`, `<x-ui.flash>`, `<x-ui.tabs>`, `<x-ui.menu>` + `<x-ui.menu-item>`, `<x-ui.sheet>`, `<x-ui.avatar>`. Usage is in a comment at the top of each file; the live gallery is **Access → Design System** (`admin.design-system`).

Pagination: `$items->links('pagination::ui')`.

## Behaviour (`public/js/ui-kit.js`, ≈8 KB)

Replaces Bootstrap's JS and keeps its data-API, so old markup works: `data-bs-toggle="modal|dropdown|collapse"`, `data-bs-target`, `data-bs-dismiss="modal|alert"`, `bootstrap.Modal.getOrCreateInstance(el).show()`, `new bootstrap.Tooltip(el)`, and the `show/shown/hide/hidden.bs.*` events (native, so jQuery `.on()` hears them). New code can use `window.UI`. Modals are centred dialogs on desktop and bottom sheets on phones (CSS in `ds.css`); dropdowns are `position: fixed` from the trigger (never clipped by tables), flip up near the bottom, close on outside click / Esc / scroll, and support arrow keys.

## Page rules

- Extend `layouts.admin`; set `@section('body_class', 'ax-custom-page')` (the runtime normalizer in `admin-ui.js` then leaves the page alone) and put top-right buttons in `@section('page_actions')`. The layout prints the large title, subtitle and breadcrumb from `App\Support\AdminNav::page()` — don't repeat them.
- Layout with Tailwind utilities (`grid gap-4 md:grid-cols-2`, `flex items-center gap-2` …); controls, cards, tables, badges with `<x-ui.*>`.
- Page-only CSS goes in `@push('styles')` using tokens; prefer utilities.
- New admin screens: add them to `App\Support\AdminNav` (nav item + page title).

## Assets and navigation (`app/Support/AdminAssets.php`, `config/admin_assets.php`)

- **Plugins load only where used.** The layout scans the page's HTML (and the local `public/js/*.js` it links) for markers — `DataTable`, `Swal.`, `validateForm` / `.validate(` / `digit-only`, `Tagify`, `flatpickr`, `data-lb-time`, `new Chart`, `Razorpay(` — and loads just those bundles. Code that only arrives later (AJAX) needs `@section('admin_assets', 'datatables,swal')` or a route entry in `config/admin_assets.php`. SweetAlert also loads itself on first `Swal` call as a fallback.
- **Prerender on hover (Speculation Rules, Chrome/Edge).** Same-origin links are prerendered after ~200 ms of hover, so clicks are instant. **A GET URL that changes data must never be prerendered**: its URL must contain one of the words in `config/admin_assets.php` → `no_prefetch` (delete, destroy, toggle, logout, export, sync, download …), or the link needs `data-no-prefetch`. Better still: make such actions POST/DELETE forms.
- Prerendered pages run their JavaScript. Anything that changes data on page load must wait: `if (document.prerendering) document.addEventListener('prerenderingchange', run, { once: true }); else run();` (see `admin-crm-notify.js`).
- Page changes cross-fade with the sidebar / toolbar / tab bar held still (cross-document View Transitions); the progress bar shows only if the next page takes > 150 ms.
- The page content wrapper is `#axContent` / `.ax-content`.
- Dropdowns inside the toolbar (or any `backdrop-filter` / `transform` ancestor) need `data-ui-portal` on the `.dropdown-menu`, so ui-kit moves it to `<body>` while open.

## Appearance (light / dark)

- Admins pick **Auto / Light / Dark** in the profile menu. The choice is stored in `localStorage.axTheme` and applied as `<html data-theme="light|dark">` by an inline script in `<head>` before first paint (no flash). Auto removes the attribute and follows the OS.
- All colours come from tokens in `ds.css`; the dark values are defined twice there (system dark unless forced light, and forced dark). **Never hard-code `#fff` / greys for surfaces or text** — use `var(--surface)`, `var(--label)`, `bg-surface`, `text-label-3` … so both appearances work. White text on the tint (`--on-tint`) is fine.
- Helper tokens: `--seg-active` (selected segment), `--mix-ink` (use in `color-mix(…, var(--mix-ink))` for text on tinted chips), `--tooltip-bg`, `--scrim`, `--switch-on`, `--scroll-thumb`, `--close-icon-filter`.
- Tailwind `dark:` works for both (system and forced): `dark:bg-…`. Rarely needed when tokens are used.
- Plugins (SweetAlert, CKEditor, Tagify, flatpickr, DataTables, native date pickers) are themed in `legacy.css` → "Plugins follow the appearance".
- Things that must stay light on purpose: QR codes, the Google search preview on the blog editor.

## Sidebar

Accordion: one group open at a time. On load, the group holding the current page is open; otherwise the one opened last (`localStorage.axNavOpen`). Searching the menu temporarily opens every group with a match.
