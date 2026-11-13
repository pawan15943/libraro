<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Library;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Branch setting pages (seats, operating hours, grace days, locker fee, token money) and their
 * save through master.store. Runs on the configured database inside a rolled back transaction,
 * as the owner of the first library that has a branch with an hour row.
 */
class BranchSettingsTest extends TestCase
{
    use DatabaseTransactions;

    private Library $library;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $row = DB::table('hour')->join('branches', 'branches.id', '=', 'hour.branch_id')
            ->whereNull('branches.deleted_at')->select('branches.id', 'branches.library_id')->first();
        if (!$row) {
            $this->markTestSkipped('Needs a branch with operating hours.');
        }
        $this->branch = Branch::withoutGlobalScopes()->find($row->id);
        $this->library = Library::find($row->library_id);
        $this->withoutMiddleware();
        $this->actingAs($this->library, 'library');
        $this->app->useStoragePath(sys_get_temp_dir() . '/libraro-test-storage');
    }

    private function save(string $model, array $data)
    {
        $key = $model === 'Hour' ? ['branch_id' => $this->branch->id] : ['id' => $this->branch->id];
        return $this->postJson(route('master.store'), array_merge(['databasemodel' => $model, 'library_id' => $this->library->id, 'redirect' => '/'], $key, $data));
    }

    public function test_every_page_opens_for_own_branch(): void
    {
        // the layout needs LoadMenus' shared data, so the web middleware runs here (not the license / logging ones)
        $this->withMiddleware()->withoutMiddleware([
            \App\Http\Middleware\LicenseCheck::class,
            \App\Http\Middleware\LoggerMiddleware::class,
            \App\Http\Middleware\EnsureLibraryEmailIsVerified::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);
        foreach (['seat.create', 'hour.create', 'extendDay.create', 'lockeramount.create', 'tokenAmount.create'] as $route) {
            $this->get(route($route, $this->branch->id))->assertOk()->assertSee('branchSettingForm', false);
        }
    }

    public function test_other_librarys_branch_is_refused(): void
    {
        $other = Branch::withoutGlobalScopes()->where('library_id', '!=', $this->library->id)->value('id');
        if (!$other) {
            $this->markTestSkipped('No branch in another library.');
        }
        $this->get(route('lockeramount.create', $other))->assertRedirect(route('branch.list'));
    }

    public function test_grace_locker_token_limits(): void
    {
        $this->save('Branch', ['extend_days' => 31])->assertStatus(422)->assertJsonValidationErrors(['extend_days']);
        $this->save('Branch', ['extend_days' => ''])->assertStatus(422)->assertJsonValidationErrors(['extend_days']);
        $this->save('Branch', ['locker_amount' => -1])->assertStatus(422)->assertJsonValidationErrors(['locker_amount']);
        $this->save('Branch', ['locker_amount' => 'abc'])->assertStatus(422)->assertJsonValidationErrors(['locker_amount']);
        $this->save('Branch', ['token_money' => 100000])->assertStatus(422)->assertJsonValidationErrors(['token_money']);
    }

    public function test_grace_locker_token_save(): void
    {
        $this->save('Branch', ['extend_days' => 7])->assertOk()->assertJson(['success' => true]);
        $this->save('Branch', ['locker_amount' => 150])->assertOk();
        $this->save('Branch', ['token_money' => 300])->assertOk();
        $b = Branch::withoutGlobalScopes()->find($this->branch->id);
        $this->assertSame([7, 150, 300], [(int) $b->extend_days, (int) $b->locker_amount, (int) $b->token_money]);
    }

    public function test_seats_can_only_go_up(): void
    {
        $seats = (int) DB::table('hour')->where('branch_id', $this->branch->id)->value('seats');
        $this->save('Hour', ['seats' => 0])->assertStatus(422)->assertJsonValidationErrors(['seats']);
        if ($seats > 1) {
            $this->save('Hour', ['seats' => $seats - 1])->assertJson(['error' => true]);
        }
        $this->save('Hour', ['seats' => $seats + 5])->assertOk()->assertJson(['success' => true]);
        $this->assertSame($seats + 5, (int) DB::table('hour')->where('branch_id', $this->branch->id)->value('seats'));
    }

    public function test_hours_limits(): void
    {
        $this->save('Hour', ['hour' => 9])->assertStatus(422)->assertJsonValidationErrors(['hour']);
        $this->save('Hour', ['hour' => 25])->assertStatus(422)->assertJsonValidationErrors(['hour']);
        $this->assertLessThanOrEqual(24, getBranchShiftTiming($this->branch->id));
    }
}
