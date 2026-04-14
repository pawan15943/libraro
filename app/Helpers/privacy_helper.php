<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('learner_contact_masking_feature_id')) {
    function learner_contact_masking_feature_id()
    {
        return 35;
    }
}

if (!function_exists('learner_contact_masking_enabled')) {
    /**
     * True when the Privacy toggle is ON for the current branch.
     * Uses toggle_features.id only (same ids as hide-field switches / branch.hide_field JSON).
     */
    function learner_contact_masking_enabled(): bool
    {
        if (!function_exists('toggleHideField')) {
            return false;
        }

        $featureId = (int) learner_contact_masking_feature_id();

        try {
            $rowExists = DB::table('toggle_features')
                ->where('id', $featureId)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }

        if (!$rowExists) {
            return false;
        }

        $hidden = toggleHideField();
        if (!is_array($hidden)) {
            return false;
        }

        $hiddenIds = array_map('intval', $hidden);

        return in_array($featureId, $hiddenIds, true);
    }
}

if (!function_exists('display_learner_mobile')) {
    function display_learner_mobile(?string $mobile): string
    {
        if (!learner_contact_masking_enabled()) {
            return $mobile ?? '';
        }

        return mask_learner_mobile($mobile);
    }
}

if (!function_exists('display_learner_email')) {
    function display_learner_email(?string $email): string
    {
        if (!learner_contact_masking_enabled()) {
            return $email ?? '';
        }

        return mask_learner_email($email);
    }
}

if (!function_exists('mask_learner_mobile')) {
    /**
     * Mask mobile for list/history/search: first 2 digits + x + last 4 (10-digit style).
     */
    function mask_learner_mobile(?string $mobile): string
    {
        if ($mobile === null || trim($mobile) === '') {
            return '';
        }

        $digits = preg_replace('/\D/', '', $mobile);
        $len = strlen($digits);

        if ($len === 0) {
            return '';
        }

        if ($len <= 6) {
            return substr($digits, 0, 1) . str_repeat('x', max(0, $len - 2)) . substr($digits, -1);
        }

        return substr($digits, 0, 2) . str_repeat('x', $len - 6) . substr($digits, -4);
    }
}

if (!function_exists('mask_learner_email')) {
    /**
     * Mask email for list/history/search: first 2 characters of local part + x, domain unchanged.
     */
    function mask_learner_email(?string $email): string
    {
        if ($email === null || trim($email) === '') {
            return '';
        }

        $email = trim($email);
        $parts = explode('@', $email, 2);
        $local = $parts[0];
        $localLen = strlen($local);

        if ($localLen <= 2) {
            $maskedLocal = $local . str_repeat('x', max(0, 2 - $localLen));
        } else {
            $maskedLocal = substr($local, 0, 2) . str_repeat('x', $localLen - 2);
        }

        return isset($parts[1]) ? ($maskedLocal . '@' . $parts[1]) : $maskedLocal;
    }
}
