# Libraro Project Guidelines & CSS Rules

## Primary Color Rule
- **Primary Color**: ALWAYS use **Navy Blue (`#18225f`)** as the primary color across all UI elements, buttons, headers, active states, and icons.
- **NEVER use standard blue (`#007bff`, `#0d6efd`, plain blue)** for primary elements.
- **Secondary / Accent Colors**:
  - Teal Accent: `#34939F`
  - Success Green: `#22c55e` / `#16a34a`
  - Danger Red: `#dc3545` / `#ef4444`
  - Dark Navy Text: `#18225f` / `#1e293b`

---

## Strict CSS Scoping & Separate CSS File Rule
- **Unique Parent Class Scoping**: When writing CSS, ALWAYS use a unique/different parent wrapper class name for your module (e.g., `.custom-notification-module`, `.library-dashboard-section`) and scope all selectors under it (e.g., `.custom-notification-module .btn-action`). This ensures new CSS rules NEVER conflict with or affect any existing UI elements across the codebase.
- **Separate CSS File**: ALWAYS write custom CSS in a separate CSS stylesheet file (e.g., `public/css/custom-styles.css`) rather than using inline `<style>` tags or inline HTML styles.
