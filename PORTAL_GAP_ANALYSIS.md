# Libraro Portal: Change Traceability & Interactive Gap Analysis Report

**Date:** September 14, 2026  
**Scope:** Libraro WebGuard Admin & Library Portal  
**Target:** Recent Feature Deployments, UI Redesigns, and Interactive User Flows

---

## 1. Traceability of Recent Portal Changes

| Area / Module | Recent Changes Made | Files Impacted |
| :--- | :--- | :--- |
| **Booking Form Modal** | • Redesigned modal with card architecture matching `learnerEdit`<br>• Shifted Upload Photo to top with collapsible card (closed by default)<br>• Cleaned toggle chevron arrows (removed square box borders)<br>• Replaced legacy file input with modern `.doc-dropzone`<br>• Fixed double nested background in Plan Addon (`.idProofFields1`)<br>• Pinned modal header & action footer to fixed positions with scrollable body | `popup.blade.php`<br>`public/css/booking-modal.css`<br>`script.blade.php` |
| **Settlement Modal** | • Replaced legacy table rows and checkboxes with interactive plan cards<br>• Click-to-select interaction with visual active states<br>• Smooth loader animation replacing harsh spinner<br>• Dynamic summary calculation (Pending, Extra, Net Amount)<br>• Mobile-first spacing and responsive padding | `script.blade.php`<br>`public/css/settlement-modal.css`<br>`library.blade.php` |
| **Dashboard V2 Cleanup** | • Removed legacy Dashboard V2 views and stylesheets<br>• Cleaned orphaned routes (`library.dashboard.v2` → `library.home`)<br>• Removed dead test scripts and obsolete controllers | `admin-v2.blade.php` (deleted)<br>`dashboard-v2.css` (deleted)<br>`routes/web.php` |
| **Support & AI Assistant** | • Re-positioned floating support icon (`.support-container`) to `bottom: 70px; right: 11px`<br>• Integrated guided question-and-answer Libraro AI widget | `header-sidebar-theme.css`<br>`ai-chat-widget.blade.php`<br>`public/css/ai-chat-widget.css` |
| **Learner Edit Module** | • Modernized edit form layout into colored header cards<br>• Integrated drag-and-drop ID proof `.doc-dropzone`<br>• Avatar photo upload with live preview and Cropper integration | `learnerEdit.blade.php`<br>`public/css/learner-edit.css`<br>`LearnerController.php` |
| **Learner Transactions** | • Unified transaction & activity cards with debit/credit indicators<br>• Direct receipt download buttons<br>• Responsive badges for payment modes and particulars | `transaction-section.blade.php`<br>`learnershow.blade.php`<br>`learner-transaction.css` |
| **Attendance Kiosk** | • Dual mode: Student Mobile QR vs. ID Card Camera Scanner<br>• Anti-proxy rolling QR code (30s refresh)<br>• Fullscreen reception desk toggle and real-time punch feedback | `attendance-qr.blade.php`<br>`public/css/attendance-kiosk.css` |

---

## 2. Interactive Gaps & Vulnerabilities Identified

### Gap 1: File Upload Validation Mismatch (Critical Severity)
* **Problem**: 
  * The frontend UI in both the Booking Modal (`popup.blade.php`) and Learner Edit (`learnerEdit.blade.php`) explicitly informs users:
    > *"Supports JPG, PNG, WEBP, PDF (Max 5 MB)"*
  * However, backend form requests (`StoreLearnerRequest.php` line 41, `LearnerOperationRequest.php` line 260) enforce:
    ```php
    'id_proof' => 'nullable|file|mimes:jpg,png,jpeg,webp|max:200'
    ```
* **User Impact**: 
  1. If a user uploads an ID proof in **PDF format**, the submission fails with: *"The id proof must be a file of type: jpg, png, jpeg, webp."*
  2. If a user uploads an image taken directly from a phone or camera (typically 1 MB – 3 MB), it fails with: *"The id proof may not be greater than 200 kilobytes."*
  3. The profile picture upload (`profile_picture_image`) is also capped at `max:200` (200 KB), rejecting high-res cropped images.
* **Recommended Fix**:
  * Update `StoreLearnerRequest` and `LearnerOperationRequest` rules:
    ```php
    'id_proof' => 'nullable|file|mimes:jpg,png,jpeg,webp,pdf|max:5120',
    'profile_picture_image' => 'nullable|file|mimes:jpg,png,jpeg,webp|max:3072',
    ```

---

### Gap 2: Duplicate `name="seat_no"` Form Fields in Booking Modal (High Severity)
* **Problem**:
  * In `popup.blade.php`, there are **two** fields with `name="seat_no"`:
    1. Line 23: `<input type="hidden" name="seat_no" value="" id="seat_no">`
    2. Line 104: `<select name="seat_no" class="form-select" id="seat_id" disabled>`
  * When a user books via header popup and enables seat allotment (`#general_seat = 'no'`), `#seat_id` is enabled. Both fields are sent in `FormData`. The hidden empty input can override or conflict with the selected dropdown seat.
  * In `script.blade.php` (line 1739), selecting a seat from `#seat_id` triggers `getTypeSeatwise(newSeatId)` but **does not synchronize** `$('#seat_no').val(newSeatId)`.
* **User Impact**:
  * In intermittent browser submissions, `seat_no` can arrive empty or produce a validation error: *"The seat no field is required"*.
* **Recommended Fix**:
  * Ensure selecting `#seat_id` immediately copies its value into `$('#seat_no')`.
  * Rename select to `id="seat_id"` with no `name` attribute, or have only one active element named `seat_no`.

---

### Gap 3: Modal State & Form Validation Reset on Reopen (Medium-High Severity)
* **Problem**:
  * When a user opens `#seatAllotmentModal`, begins typing, encounters an error (e.g. invalid phone, missing plan), and closes the modal via "Cancel" or "X", reopening it retains old validation classes (`.is-invalid`), old error messages (`#error-message`), and dropzone previews.
* **User Impact**:
  * When opening the modal for a different seat or learner, residual error warnings and old preview thumbnails confuse the user.
* **Recommended Fix**:
  * Add a standard Bootstrap modal hide listener:
    ```javascript
    $('#seatAllotmentModal').on('hidden.bs.modal', function () {
        $('#seatAllotmentForm')[0].reset();
        $('#seatAllotmentForm .is-invalid').removeClass('is-invalid');
        $('#seatAllotmentForm .invalid-feedback').remove();
        $('#error-message, #validation-error-message').hide().text('');
        $('#bookingDocDropPreview').hide();
        $('#bookingDocDropContent').show();
        $('#bookingAvatarPreview').hide().attr('src', '');
        $('#bookingAvatarDefaultIcon').show();
    });
    ```

---

### Gap 4: Floating Widget Overlap (Support vs. AI Assistant) (Medium Severity)
* **Problem**:
  * `.support-container` is anchored at `bottom: 70px; right: 11px; z-index: 1000;`.
  * `.custom-libraro-ai-widget` is anchored at `bottom: 24px; right: 24px; z-index: 999999;`.
* **User Impact**:
  * On laptops with scaling (125%/150%) or mobile screens (< 768px), the floating phone icon and AI robot trigger are closely stacked, occasionally covering table action rows or pagination controls.
  * Clicking one trigger can accidentally open the adjacent drawer.
* **Recommended Fix**:
  * Establish a single cohesive vertical floating stack (e.g., AI widget at `bottom: 24px; right: 20px`, Support at `bottom: 96px; right: 24px`), or embed the support phone link directly into the AI header / top bar.

---

### Gap 5: Bootstrap Nested Backdrop Collision with Cropper Modal (Medium Severity)
* **Problem**:
  * Triggering the avatar or ID proof upload opens the global `#cropperModal` while `#seatAllotmentModal` is still open.
  * Bootstrap 5 default modal behavior removes the `modal-open` class from `<body>` when the inner `#cropperModal` is closed, causing the underlying `#seatAllotmentModal` to lose scrollability or trap keyboard focus.
* **User Impact**:
  * After cropping a photo and returning to the booking form, the user cannot scroll down to the "Book Seat Now" button without closing and reopening the modal.
* **Recommended Fix**:
  * Add a listener on `#cropperModal` hide event:
    ```javascript
    $('#cropperModal').on('hidden.bs.modal', function () {
        if ($('#seatAllotmentModal').hasClass('show')) {
            $('body').addClass('modal-open');
        }
    });
    ```

---

### Gap 6: Settlement Modal Zero-Selection Edge Case (Medium Severity)
* **Problem**:
  * When opening the settlement modal, all plan cards are checked by default. If a user unchecks all cards, the net amount displays `₹0` and the status flips to "Already settled" with the confirm button labeled *"Already settled"*.
  * Clicking it only then displays a validation error: *"Please select at least one transaction card."*
* **User Impact**:
  * The user is misled into thinking clicking the button will mark the account settled, instead of being informed that at least one item must be chosen.
* **Recommended Fix**:
  * Disable the confirm button dynamically when `selectedIds.length === 0`, and display an inline notice: *"Select at least one record to proceed with settlement"*.

---

### Gap 7: External CDN Dependency in Attendance Kiosk (Low-Medium Severity)
* **Problem**:
  * `attendance-qr.blade.php` imports Lottie player via `unpkg.com` and references animation assets from `lottie.host`.
* **User Impact**:
  * If a library desk computer has a firewalled or offline network, scan feedback animations fail to render, leaving blank areas.
* **Recommended Fix**:
  * Bundle Lottie JS locally in `public/js/` or provide CSS-only keyframe checkmark fallbacks.

---

## 3. Priority Action Plan

| Priority | Action Item | Estimated Effort |
| :---: | :--- | :---: |
| **P1** | Align `StoreLearnerRequest` and `LearnerOperationRequest` file validation rules (`mimes:jpg,png,jpeg,webp,pdf`, `max:5120`) with UI dropzone specs. | 15 mins |
| **P1** | Synchronize `#seat_id` and `#seat_no` in `popup.blade.php` & `script.blade.php` to prevent duplicate key conflicts. | 15 mins |
| **P2** | Add modal hide reset listener on `#seatAllotmentModal` for clean form re-entry. | 10 mins |
| **P2** | Add `$('body').addClass('modal-open')` check on `#cropperModal` close to avoid scroll locking. | 10 mins |
| **P3** | Adjust floating button offsets between AI Chat and Support widget for clean spacing. | 15 mins |
| **P3** | Disable Settlement confirm button when 0 cards are selected. | 10 mins |
