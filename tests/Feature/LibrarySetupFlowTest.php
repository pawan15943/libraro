<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Hour;
use App\Models\Library;
use App\Models\PlanType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * First-time setup inside the panel: Choose plan → Branch → Shift (librarySetup()).
 * Each state is built on a new library inside a rolled back transaction and the page is rendered
 * through the web middleware (not the license / logging / e-mail checks).
 */
class LibrarySetupFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir() . '/libraro-test-storage');
        $this->withoutMiddleware([
            \App\Http\Middleware\LicenseCheck::class,
            \App\Http\Middleware\LoggerMiddleware::class,
            \App\Http\Middleware\EnsureLibraryEmailIsVerified::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);
    }

    private function newLibrary(array $attrs = []): Library
    {
        $lib = Library::create(array_merge([
            'library_name' => 'Setup Test Library',
            'email' => 'setup.' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'library_mobile' => '9876543210',
            'email_verified_at' => now(),
            'is_paid' => 0,
            'status' => 0,
            'library_type' => 1,
        ], $attrs));
        $this->actingAs($lib, 'library');
        librarySetup(true);
        return $lib;
    }

    private function addBranch(Library $lib): Branch
    {
        $branch = Branch::withoutGlobalScopes()->create(['library_id' => $lib->id, 'name' => 'Main', 'status' => 1]);
        Hour::withoutGlobalScopes()->create(['library_id' => $lib->id, 'branch_id' => $branch->id, 'hour' => 12, 'seats' => 40]);
        $lib->update(['current_branch' => $branch->id]);
        librarySetup(true);
        return $branch;
    }

    public function test_helper_follows_the_three_steps(): void
    {
        $lib = $this->newLibrary();
        $this->assertSame(['in_setup' => true, 'done' => ['plan' => false, 'branch' => false, 'shift' => false], 'current' => 'plan'], librarySetup(true));

        $lib->update(['is_paid' => 1]);
        $this->assertSame('branch', librarySetup(true)['current']);

        $branch = $this->addBranch($lib);
        $this->assertSame('shift', librarySetup(true)['current']);

        PlanType::withoutGlobalScopes()->create(['library_id' => $lib->id, 'branch_id' => $branch->id, 'name' => 'Full Day', 'day_type_id' => 1, 'start_time' => '08:00', 'end_time' => '20:00', 'slot_hours' => 12]);
        $this->assertFalse(librarySetup(true)['in_setup']);
    }

    public function test_a_deleted_shift_does_not_send_a_library_back_to_setup(): void
    {
        $lib = $this->newLibrary(['is_paid' => 1]);
        $branch = $this->addBranch($lib);
        $pt = PlanType::withoutGlobalScopes()->create(['library_id' => $lib->id, 'branch_id' => $branch->id, 'name' => 'Full Day', 'day_type_id' => 1, 'start_time' => '08:00', 'end_time' => '20:00', 'slot_hours' => 12]);
        $pt->delete();
        $this->assertFalse(librarySetup(true)['in_setup']);
    }

    public function test_choose_plan_in_the_panel_with_steps(): void
    {
        $this->newLibrary();
        $this->get(route('subscriptions.choosePlan'))->assertOk()
            ->assertSee('lbs-steps', false)->assertSee('Set up your library')
            ->assertSee('lp-sb', false);   // the panel sidebar
    }

    public function test_branch_step_in_the_panel_with_steps(): void
    {
        $this->newLibrary(['is_paid' => 1]);
        $this->get(route('branch.configure.create'))->assertOk()
            ->assertSee('lbs-steps', false)->assertSee('Set up your branch')
            ->assertDontSee('ob-ministeps', false);
    }

    public function test_shift_step_in_the_panel_with_steps(): void
    {
        $lib = $this->newLibrary(['is_paid' => 1]);
        $this->addBranch($lib);
        $this->get(route('library.configration'))->assertOk()
            ->assertSee('lbs-steps', false)->assertSee('Add your shifts');
    }

    public function test_set_up_library_sees_no_steps(): void
    {
        $libId = PlanType::withoutGlobalScopes()->whereNull('parent_id')->value('library_id');
        $lib = $libId ? Library::find($libId) : null;
        if (!$lib) {
            $this->markTestSkipped('No set-up library.');
        }
        $this->actingAs($lib, 'library');
        librarySetup(true);
        $this->get(route('library.configration'))->assertOk()
            ->assertDontSee('lbs-steps', false)->assertDontSee('Set up your library')->assertSee('Shifts &amp; fees', false);
        $this->get(route('branch.configure.create'))->assertOk()
            ->assertDontSee('lbs-steps', false)->assertSee('Add branch');
    }
}
