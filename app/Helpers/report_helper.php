<?php

// Small formatting helpers shared by the report pages.

if (!function_exists('rptInr')) {
    /** Indian-grouped rupees: 125000 → ₹1,25,000 (decimals only when non-zero and asked for). */
    function rptInr($value, bool $decimals = false): string
    {
        $value = (float) $value;
        $neg = $value < 0;
        $value = abs($value);
        $whole = (string) floor($value);
        $frac = $decimals ? substr(number_format($value - floor($value), 2), 1) : '';
        if ($frac === '.00') {
            $frac = '';
        }

        if (strlen($whole) > 3) {
            $last3 = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $whole = $rest . ',' . $last3;
        }

        return ($neg ? '-' : '') . '₹' . $whole . $frac;
    }
}

if (!function_exists('rptShort')) {
    /** Compact rupees for tight spots: ₹1.2L, ₹45.6K, ₹950. */
    function rptShort($value): string
    {
        $v = (float) $value;
        $a = abs($v);
        $s = $v < 0 ? '-' : '';
        if ($a >= 10000000) return $s . '₹' . rtrim(rtrim(number_format($a / 10000000, 1), '0'), '.') . 'Cr';
        if ($a >= 100000) return $s . '₹' . rtrim(rtrim(number_format($a / 100000, 1), '0'), '.') . 'L';
        if ($a >= 1000) return $s . '₹' . rtrim(rtrim(number_format($a / 1000, 1), '0'), '.') . 'K';
        return $s . '₹' . number_format($a);
    }
}

if (!function_exists('rptMode')) {
    /** Normalise the many stored payment-mode spellings to Online / Offline / Pay later. */
    function rptMode($raw): string
    {
        $m = strtolower(trim((string) $raw));
        if (in_array($m, ['1', 'online', 'upi', 'card'], true)) return 'Online';
        if (in_array($m, ['3', 'paylater', 'pay later'], true) || str_contains($m, 'pay')) return 'Pay later';
        if (in_array($m, ['2', 'offline', 'cash'], true) || str_contains($m, 'cash') || str_contains($m, 'offline')) return 'Offline';
        return $m === '' ? 'Offline' : ucfirst($m);
    }
}

if (!function_exists('rptInitials')) {
    function rptInitials($name): string
    {
        return collect(preg_split('/\s+/', trim((string) $name)))->filter()
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
    }
}

if (!function_exists('rptPhone')) {
    /** 10-digit Indian mobile (strips +91 / spaces), or '' when unusable. */
    function rptPhone($raw): string
    {
        $d = preg_replace('/\D/', '', (string) $raw);
        if (strlen($d) > 10 && str_starts_with($d, '91')) $d = substr($d, -10);
        return strlen($d) === 10 ? $d : '';
    }
}
