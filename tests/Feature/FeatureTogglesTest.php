<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Learner;
use App\Models\Library;
use App\Support\FeatureToggles;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Show / Hide Options (App\Support\FeatureToggles): saving, blocked pages, menus. */
class FeatureTogglesTest extends TestCase
{
    use DatabaseTransactions;

    private Learner $learner;

    private Library $library;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir().'/libraro-test-storage');
        @mkdir(sys_get_temp_dir().'/libraro-test-views', 0777, true);
        config(['view.compiled' => sys_get_temp_dir().'/libraro-test-views']);
        foreach (array_keys(config('logging.channels', [])) as $channel) {
            if (config("logging.channels.$channel.path")) {
                config(["logging.channels.$channel.path" => sys_get_temp_dir()."/libraro-test-$channel.log"]);
            }
        }
        $this->withoutMiddleware([\App\Http\Middleware\LicenseCheck::class, \App\Http\Middleware\LoggerMiddleware::class]);
        FeatureToggles::flush();

        $detail = DB::table('learner_detail')->where('status', 1)->whereNull('deleted_at')->whereNotNull('seat_no')
            ->whereDate('plan_start_date', '<=', today())->whereDate('plan_end_date', '>=', today()->addDays(10))
            ->orderByDesc('id')->first() ?? $this->markTestSkipped('no learner with an active plan');
        $learner = Learner::withoutGlobalScopes()->whereNull('deleted_at')->find($detail->learner_id) ?? $this->markTestSkipped('learner missing');
        $library = Library::find($learner->library_id) ?? $this->markTestSkipped('library missing');
        $library->forceFill(['library_type' => 3, 'current_branch' => $learner->branch_id])->save();
        $this->learner = $learner;
        $this->library = $library;
    }

    private function hide(array $keys): void
    {
        Branch::withoutGlobalScopes()->where('id', $this->learner->branch_id)->update(['hide_field' => $keys ? json_encode($keys) : null]);
        FeatureToggles::flush();
    }

    public function test_save_keeps_only_known_keys(): void
    {
        $this->actingAs($this->library, 'library');
        $this->assertSame(['3', 'chat', 'lp_study'], FeatureToggles::normalise(['3', 'chat', 'nope', '999999', 'lp_study', '3']));
    }

    public function test_hidden_pages_are_blocked_and_shown_pages_open(): void
    {
        $this->hide(['chat', '10']);
        $this->actingAs($this->library->fresh(), 'library');

        $this->get(route('chat.index'))->assertRedirect(route('library.home'));
        $this->get(route('attendance'))->assertRedirect(route('library.home'));
        $this->getJson(route('attendance'))->assertStatus(403);
    }

    public function test_learner_portal_drops_hidden_pages(): void
    {
        $this->hide(['10', 'lp_study']);
        $this->actingAs($this->learner, 'learner');

        $this->get(route('my-attendance'))->assertRedirect(route('learner.home'));
        $this->get(route('learner.stopwatch'))->assertRedirect(route('learner.home'));
        $html = $this->get(route('learner.home'))->getContent();
        $this->assertStringNotContainsString(route('my-attendance'), $html);
        $this->assertStringNotContainsString(route('learner.stopwatch'), $html);
    }

    public function test_legacy_ids_still_read_by_old_checks(): void
    {
        $this->hide(['3', 'chat']);
        $this->actingAs($this->library->fresh(), 'library');
        $this->assertTrue(in_array('3', toggleHideField()));
        $this->assertTrue(FeatureToggles::hidden('chat'));
        $this->assertFalse(FeatureToggles::hidden('quiz'));
    }
}
