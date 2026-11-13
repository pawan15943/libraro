<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Safety: tests may only ever touch a *_test database (libraro_test, a copy of the local
        // one). A cached config (php artisan config:cache) or a missing .env.testing would point
        // them at the real database, so stop before any test runs.
        $default = $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$default}.database");
        if (! str_ends_with($database, '_test') || ! $app->environment('testing')) {
            fwrite(STDERR, "\n  Tests stopped: they would run on database \"{$database}\" (env: {$app->environment()}).\n"
                . "  Tests only run on a *_test database. Check .env.testing (DB_DATABASE=libraro_test)\n"
                . "  and clear a cached config with: php artisan config:clear\n\n");
            exit(1);
        }

        return $app;
    }

    public function generateLicenseKey(string $macAddress): string
    {
        return hash('sha256', $macAddress . 'NBC Heena Computer Class 786');
    }
}
