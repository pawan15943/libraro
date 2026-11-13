<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Fails while the PHP running the app has a known critical hole (docs/SECURITY.md).
 * Add a row when a new PHP security release fixes something exploitable.
 */
class PhpSecurityVersionTest extends TestCase
{
    /** branch => first fixed version (PHP2Shell: CVE-2026-109618, CVE-2026-109619) */
    private const FIXED = [
        '8.2' => '8.2.35',
        '8.3' => '8.3.36',
        '8.4' => '8.4.27',
        '8.5' => '8.5.12',
    ];

    public function test_php_has_the_latest_critical_security_fixes(): void
    {
        $branch = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

        $this->assertArrayHasKey($branch, self::FIXED, "PHP $branch gets no security fixes any more. Move to PHP 8.3 or newer.");
        $this->assertTrue(
            version_compare(PHP_VERSION, self::FIXED[$branch], '>='),
            'PHP '.PHP_VERSION.' is open to PHP2Shell remote code execution (CVE-2026-109618/109619). Upgrade to '.self::FIXED[$branch].' or newer.'
        );
    }
}
