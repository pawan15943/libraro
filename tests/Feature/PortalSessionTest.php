<?php

namespace Tests\Feature;

use App\Http\Middleware\UsePortalSession;
use App\Models\Branch;
use App\Models\Library;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

/**
 * Admin, library and learner portals keep separate session cookies, so all three
 * can be signed in on one browser without logging each other out.
 *
 * The test acts like one browser: it keeps a cookie jar between requests and
 * uses file sessions, and every request starts with fresh session/auth objects
 * (as a new PHP request would). Whether a portal is signed in is read from its
 * login page: the guest middleware redirects away when someone is signed in.
 */
class PortalSessionTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<string,string> browser cookie jar: name => raw (encrypted) value */
    private array $jar = [];

    private string $password = 'Secret#123';

    protected function setUp(): void
    {
        parent::setUp();

        $dir = sys_get_temp_dir().'/libraro-portal-sessions-'.getmypid();
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        config(['session.driver' => 'file', 'session.files' => $dir]);

        $this->withoutMiddleware([
            \App\Http\Middleware\LicenseCheck::class,
            \App\Http\Middleware\LoggerMiddleware::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);
    }

    /* ------------------------------------------------------------ helpers */

    private function browser(string $method, string $uri, array $data = [], array $headers = [])
    {
        // a new request in a real server: nothing kept in memory
        $this->app['auth']->forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->flushHeaders();
        $this->unencryptedCookies = $this->jar; // values are already encrypted by the app
        $this->defaultCookies = [];

        $response = $this->withHeaders($headers)->call($method, $uri, $data, $this->prepareCookiesForRequest(), [], $this->transformHeadersToServerVars($headers));

        foreach ($response->headers->getCookies() as $cookie) {
            /** @var Cookie $cookie */
            if ($cookie->getValue() === null || $cookie->getValue() === '' || ($cookie->getExpiresTime() && $cookie->getExpiresTime() < time())) {
                unset($this->jar[$cookie->getName()]);
            } else {
                $this->jar[$cookie->getName()] = $cookie->getValue();
            }
        }

        return $response;
    }

    private function signedIn(string $portal): bool
    {
        $page = ['admin' => route('login.administrator', [], false), 'library' => '/library/login', 'learner' => '/learner/login'][$portal];
        $response = $this->browser('GET', $page);

        return $response->isRedirect();
    }

    private function loginLibrary(bool $remember = false): Library
    {
        $library = Library::create([
            'library_name' => 'Portal Test Library',
            'email' => 'portal.lib.'.Str::random(8).'@example.com',
            'password' => bcrypt($this->password),
            'library_mobile' => '9876543210',
            'email_verified_at' => now(),
            'is_paid' => 1,
            'status' => 1,
            'library_type' => 1,
        ]);

        $data = ['user_type' => 'admin', 'email' => $library->email, 'password' => $this->password];
        if ($remember) {
            $data['remember'] = 'on';
        }
        $this->browser('POST', '/login/store', $data)->assertRedirect();

        return $library;
    }

    private function loginLearner(?Library $library = null): int
    {
        $library ??= Library::create(['library_name' => 'Learner Home Library', 'email' => 'portal.home.'.Str::random(8).'@example.com', 'status' => 1]);
        $branch = Branch::withoutGlobalScopes()->create(['library_id' => $library->id, 'name' => 'Main', 'status' => 1]);
        $learnerNo = 'PT'.random_int(100000, 999999);

        $id = DB::table('learners')->insertGetId([
            'library_id' => $library->id,
            'branch_id' => $branch->id,
            'name' => 'Portal Learner',
            'learner_no' => $learnerNo,
            'password' => bcrypt($this->password),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browser('POST', '/login/store', [
            'user_type' => 'learner', 'learner_no' => $learnerNo, 'password' => $this->password,
        ])->assertRedirect();

        return $id;
    }

    private function loginAdmin(): User
    {
        $user = User::create([
            'name' => 'Portal Admin',
            'email' => 'portal.admin.'.Str::random(8).'@example.com',
            'password' => bcrypt($this->password),
        ]);

        $this->browser('POST', '/login/store', [
            'user_type' => 'superadmin', 'email' => $user->email, 'password' => $this->password,
        ])->assertRedirect();

        return $user;
    }

    private function cookie(string $portal): string
    {
        return UsePortalSession::cookieName($portal);
    }

    /* -------------------------------------------------------------- tests */

    public function test_each_portal_has_its_own_cookie(): void
    {
        $names = [$this->cookie('admin'), $this->cookie('library'), $this->cookie('learner')];

        $this->assertCount(3, array_unique($names));
        // library keeps the original cookie, so live library logins survive the release
        $this->assertSame(config('session.portals.library.cookie'), $this->cookie('library'));

        $this->browser('GET', route('login.administrator', [], false));
        $this->browser('GET', '/library/login');
        $this->browser('GET', '/learner/login');

        foreach ($names as $name) {
            $this->assertArrayHasKey($name, $this->jar, "missing session cookie $name");
        }
    }

    public function test_library_learner_and_admin_stay_signed_in_together(): void
    {
        $this->loginLibrary();
        $this->loginLearner();
        $this->loginAdmin();

        $this->assertTrue($this->signedIn('library'), 'library was logged out by another login');
        $this->assertTrue($this->signedIn('learner'), 'learner was logged out by another login');
        $this->assertTrue($this->signedIn('admin'), 'admin was logged out by another login');
    }

    public function test_login_page_of_one_portal_opens_while_another_is_signed_in(): void
    {
        $this->loginLibrary();

        // the old bug: the learner / admin login page sent you to the library dashboard
        $this->browser('GET', '/learner/login')->assertOk();
        $this->browser('GET', route('login.administrator', [], false))->assertOk();
    }

    public function test_logout_only_ends_its_own_portal(): void
    {
        $this->loginLibrary();
        $this->loginLearner();
        $this->loginAdmin();

        $this->browser('POST', '/logout?portal=learner')->assertRedirect(route('login.learner'));

        $this->assertFalse($this->signedIn('learner'));
        $this->assertTrue($this->signedIn('library'));
        $this->assertTrue($this->signedIn('admin'));

        $this->browser('POST', '/logout?portal=library')->assertRedirect(route('login.library'));

        $this->assertFalse($this->signedIn('library'));
        $this->assertTrue($this->signedIn('admin'));

        $this->browser('POST', '/logout?portal=admin')->assertRedirect(route('login.administrator'));
        $this->assertFalse($this->signedIn('admin'));
    }

    public function test_logout_without_portal_uses_the_page_it_came_from(): void
    {
        $this->loginLibrary();
        $this->loginLearner();

        // an old form without ?portal= posted from a learner page
        $this->browser('POST', '/logout', [], ['Referer' => url('/learner/home')])
            ->assertRedirect(route('login.learner'));

        $this->assertFalse($this->signedIn('learner'));
        $this->assertTrue($this->signedIn('library'));
    }

    public function test_remember_me_does_not_leak_into_other_portals(): void
    {
        $this->loginLibrary(true);

        $remember = 'remember_library_'.sha1(\Illuminate\Auth\SessionGuard::class);
        $this->assertArrayHasKey($remember, $this->jar);

        // drop the library session: only the remember-me cookie is left
        unset($this->jar[$this->cookie('library')]);

        $this->assertFalse($this->signedIn('learner'), 'library remember-me signed into the learner portal');
        $this->assertFalse($this->signedIn('admin'), 'library remember-me signed into the admin portal');
        $this->assertTrue($this->signedIn('library'), 'remember-me should still work in the library portal');
    }

    public function test_learner_pages_see_the_learner_library_not_the_signed_in_owner(): void
    {
        $owner = $this->loginLibrary();
        $learnerId = $this->loginLearner(); // a learner of another library

        $learnerLibrary = DB::table('learners')->where('id', $learnerId)->value('library_id');
        $this->assertNotSame($owner->id, $learnerLibrary);

        // inside the learner portal only the learner guard exists
        $this->app['auth']->forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->unencryptedCookies = $this->jar;
        $seen = null;
        \Illuminate\Support\Facades\Route::middleware(['web', 'portal:learner'])->get('/__portal/probe/x', function () use (&$seen) {
            $seen = [
                'portal' => currentPortal(),
                'library_id' => getLibraryId(),
                'library_guard' => \Illuminate\Support\Facades\Auth::guard('library')->check(),
            ];

            return 'ok';
        });
        $this->get('/__portal/probe/x')->assertOk();

        $this->assertSame('learner', $seen['portal']);
        $this->assertFalse($seen['library_guard']);
        $this->assertEquals($learnerLibrary, $seen['library_id']);
    }

    public function test_guest_redirects_go_to_the_portal_login(): void
    {
        $this->browser('GET', '/learner/home')->assertRedirect(route('login.learner'));
        $this->browser('GET', '/home')->assertRedirect(route('login.administrator'));
        $this->browser('GET', '/library/home')->assertRedirect(route('login.library'));
    }

    public function test_csrf_refresh_returns_the_token_of_the_asked_portal(): void
    {
        $this->browser('GET', '/learner/login');
        $this->browser('GET', '/library/login');

        $learner = $this->browser('GET', '/refresh-csrf?portal=learner')->json('token');
        $library = $this->browser('GET', '/refresh-csrf?portal=library')->json('token');

        $this->assertNotEmpty($learner);
        $this->assertNotSame($learner, $library);
        $this->assertSame($learner, $this->browser('GET', '/refresh-csrf?portal=learner')->json('token'));
    }

    public function test_every_web_route_belongs_to_the_right_portal(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            $uri = $route->uri();
            if (str_starts_with($uri, 'api/') || ! in_array('web', $route->gatherMiddleware(), true)) {
                continue;
            }
            $mw = implode(',', array_filter($route->gatherMiddleware(), 'is_string'));
            $tag = UsePortalSession::tagOf($route);

            if (str_contains($mw, 'auth:web')) {
                $this->assertSame('admin', $tag, "admin route /$uri is not in the admin portal");
            } elseif (str_contains($mw, 'auth:learner')) {
                $this->assertSame('learner', $tag, "learner route /$uri is not in the learner portal");
            } elseif (str_contains($mw, 'auth.library_or_user')) {
                $this->assertSame('library', $tag, "library route /$uri is not in the library portal");
            }
        }
    }
}
