<?php

namespace Tests\Feature;

use App\Models\Learner;
use App\Models\Library;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Learners → Requests: the library sees and answers what learners asked for; the learner sees the answer. */
class LearnerRequestsInboxTest extends TestCase
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

        \Illuminate\Support\Facades\Schema::hasColumn('learner_request', 'reply') || $this->markTestSkipped('run migration 2026_11_19_000002 first');

        $detail = DB::table('learner_detail')->where('status', 1)->whereNull('deleted_at')
            ->whereDate('plan_end_date', '>=', today()->addDays(5))->orderByDesc('id')->first() ?? $this->markTestSkipped('no active learner');
        $this->learner = Learner::withoutGlobalScopes()->whereNull('deleted_at')->find($detail->learner_id) ?? $this->markTestSkipped('learner missing');
        $this->library = Library::find($this->learner->library_id) ?? $this->markTestSkipped('library missing');
        $this->library->forceFill(['library_type' => 3, 'current_branch' => $this->learner->branch_id])->save();
        DB::table('learner_request')->where('learner_id', $this->learner->id)->delete();
    }

    /** One signed-in user at a time, as in the browser (each portal has its own session). */
    private function asLearner(): void
    {
        $this->app['auth']->guard('library')->logout();
        $this->app->forgetScopedInstances();
        $this->actingAs($this->learner, 'learner');
    }

    private function ask(string $type = 'renewPlan', string $text = 'Please renew my plan for next month'): int
    {
        $this->actingAs($this->learner, 'learner');
        $this->post('/learner/request/store', ['request_name' => $type, 'description' => $text])->assertSessionHasNoErrors();
        $this->app['auth']->guard('learner')->logout();
        $this->actingAs($this->library, 'library');

        return (int) DB::table('learner_request')->where('learner_id', $this->learner->id)->orderByDesc('id')->value('id');
    }

    public function test_library_sees_and_marks_done(): void
    {
        $id = $this->ask();
        $this->get('/library/learner-requests')->assertOk()->assertSee('Renew plan')->assertSee('Please renew my plan for next month');

        $this->post('/library/learner-requests/'.$id, ['decision' => 'done', 'reply' => 'Renewed, pay at the desk'])->assertSessionHasNoErrors();
        $row = DB::table('learner_request')->find($id);
        $this->assertSame(1, (int) $row->request_status);
        $this->assertSame('Renewed, pay at the desk', $row->reply);
        $this->assertNotNull($row->handled_at);

        // answered once only
        $this->post('/library/learner-requests/'.$id, ['decision' => 'reject', 'reply' => 'x'])->assertSessionHasErrors('reply');

        $this->actingAs($this->learner, 'learner');
        $this->asLearner();
        $this->get('/learner/request')->assertOk()->assertSee('Renewed, pay at the desk')->assertSee('Done');
    }

    public function test_not_possible_needs_a_reason_and_learner_sees_it(): void
    {
        $id = $this->ask('swapSeat', 'Seat near the window please');
        $this->post('/library/learner-requests/'.$id, ['decision' => 'reject'])->assertSessionHasErrors('reply');
        $this->post('/library/learner-requests/'.$id, ['decision' => 'reject', 'reply' => 'No window seat free this month'])->assertSessionHasNoErrors();
        $this->assertSame(2, (int) DB::table('learner_request')->where('id', $id)->value('request_status'));

        $this->asLearner();
        $this->get('/learner/request')->assertOk()->assertSee('Not possible')->assertSee('No window seat free this month');
    }

    public function test_requests_of_another_library_are_not_reachable(): void
    {
        $id = $this->ask();
        $other = Library::where('id', '!=', $this->library->id)->whereNotNull('library_type')->first() ?? $this->markTestSkipped('only one library');
        $other->forceFill(['library_type' => 3])->save();
        $this->app['auth']->guard('library')->logout();
        $this->actingAs($other, 'library');

        $this->get('/library/learner-requests?tab=all')->assertOk()->assertDontSee('Please renew my plan for next month');
        $this->post('/library/learner-requests/'.$id, ['decision' => 'done'])->assertNotFound();
        $this->assertSame(0, (int) DB::table('learner_request')->where('id', $id)->value('request_status'));
    }
}
