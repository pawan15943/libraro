<?php

namespace Tests\Feature;

use App\Http\Middleware\UsePortalSession;
use App\Models\Learner;
use App\Models\Library;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Opens every GET page of each web portal as a signed-in user of that portal
 * and checks it does not crash (5xx) or bounce to a login page.
 *
 * Uses existing accounts of the local database (picked automatically, or set
 * SMOKE_LIBRARY_ID / SMOKE_LEARNER_ID / SMOKE_ADMIN_ID). Everything runs in a
 * transaction that is rolled back, and mail / queue / HTTP calls are faked.
 */
class PortalPagesSmokeTest extends TestCase
{
    use DatabaseTransactions;

    /** pages with side effects outside the database, or file downloads */
    private const SKIP = '#(export|/pdf|pdf$|download|payment/verify|payment/error|ai-chat|sitemap|_ignition|sanctum/)#i';

    /**
     * Pages that were already broken before the portal-session change (present in
     * HEAD too). They are reported, not failed, so this test catches new damage.
     * Remove an entry once it is fixed.
     */
    private const KNOWN_BROKEN = [
        // library
        '/renew/configration/library' => 'Controller::renewConfigration: undefined $slots',
        '/library/library/daily-popup' => 'LibraryController::dailyPopup() does not exist',
        '/getPricePlanwiseUpgrade' => 'LearnerController::getPricePlanwiseUpgrade() does not exist',
        '/getPlanType' => 'AJAX endpoint, needs query parameters',
        '/library/book/category' => 'table books_categories has no migration',
        // admin
        '/subscriptions-permissions' => 'view uses route subscriptions.assignPermissions, which is not defined',
        '/library/count/view' => 'AdminDashboardController::libraryView: undefined $result',
        '/leads' => 'table lead_contacts has no migration',
        '/library' => 'needs migration 2026_09_25_000001 (libraries.is_app_verified) where it is pending',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir().'/libraro-test-storage');
        // code that writes to a named log channel must not touch the real log files
        foreach (array_keys(config('logging.channels', [])) as $channel) {
            if (config("logging.channels.$channel.path")) {
                config(["logging.channels.$channel.path" => sys_get_temp_dir()."/libraro-test-$channel.log"]);
            }
        }
        Mail::fake();
        Queue::fake();
        Notification::fake();
        Http::fake();
        $this->withoutMiddleware([
            \App\Http\Middleware\LicenseCheck::class,
            \App\Http\Middleware\LoggerMiddleware::class,
        ]);
    }

    public function test_library_portal_pages(): void
    {
        $library = env('SMOKE_LIBRARY_ID')
            ? Library::find(env('SMOKE_LIBRARY_ID'))
            : Library::whereNotNull('email_verified_at')->where('status', 1)->where('is_paid', 1)
                ->whereNotNull('current_branch')->whereNull('deleted_at')->orderByDesc('id')->first();
        if (! $library) {
            $this->markTestSkipped('no paid, verified library in this database');
        }

        $this->actingAs($library, 'library');
        $this->assertPortalPagesWork('library');
    }

    public function test_learner_portal_pages(): void
    {
        $learner = env('SMOKE_LEARNER_ID')
            ? Learner::withoutGlobalScopes()->find(env('SMOKE_LEARNER_ID'))
            : Learner::withoutGlobalScopes()->where('status', 1)->whereNull('deleted_at')->orderByDesc('id')->first();
        if (! $learner) {
            $this->markTestSkipped('no active learner in this database');
        }

        $this->actingAs($learner, 'learner');
        $this->assertPortalPagesWork('learner');
    }

    public function test_admin_portal_pages(): void
    {
        $admin = env('SMOKE_ADMIN_ID')
            ? User::find(env('SMOKE_ADMIN_ID'))
            : User::role('superadmin', 'web')->orderBy('id')->first();
        if (! $admin) {
            $this->markTestSkipped('no superadmin in this database');
        }

        $this->actingAs($admin, 'web');
        $this->assertPortalPagesWork('admin');
    }

    private function assertPortalPagesWork(string $portal): void
    {
        $logins = collect(UsePortalSession::portals())->pluck('login_route')
            ->map(fn ($name) => route($name))->all();

        $failures = [];
        $known = [];
        $checked = 0;

        foreach (app('router')->getRoutes() as $route) {
            $uri = $route->uri();
            if (! in_array('GET', $route->methods(), true)
                || UsePortalSession::tagOf($route) !== $portal
                || preg_match(self::SKIP, $uri)
                || str_contains($uri, '{')) { // needs an id (optional ids too: opened from a list)
                continue;
            }

            $url = '/'.ltrim($uri, '/');
            $checked++;

            try {
                $response = $this->get($url);
            } catch (\Throwable $e) {
                $failures[] = "$url → exception ".class_basename($e).': '.$e->getMessage();

                continue;
            }

            $status = $response->getStatusCode();
            $location = (string) $response->headers->get('Location');

            if ($status >= 500 && isset(self::KNOWN_BROKEN[$url])) {
                $known[] = "$url (".self::KNOWN_BROKEN[$url].')';
            } elseif ($status >= 500) {
                $msg = $response->exception ? $response->exception->getMessage() : '';
                $failures[] = "$url → $status ".mb_substr($msg, 0, 160);
            } elseif ($response->isRedirect() && in_array(strtok($location, '?'), $logins, true)) {
                $failures[] = "$url → sent to login ($location): the portal lost its session";
            }

            // a page may flip the active guard; keep signed in for the next one
            $this->app['auth']->shouldUse(UsePortalSession::guards($portal)[0]);
        }

        $this->assertGreaterThan(0, $checked, "no $portal pages found");
        $this->assertSame([], $failures, "$portal portal: ".count($failures)." of $checked pages failed");
        fwrite(STDERR, "\n[$portal] $checked pages opened, no new failures".($known ? "; known old issues:\n  - ".implode("\n  - ", $known) : '')."\n");
    }
}
