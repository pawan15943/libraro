<?php

namespace Tests\Feature;

use App\Models\Learner;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Subscription page, plan details removed elsewhere, month-wise payments. */
class LearnerSubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir().'/libraro-test-storage');
        foreach (array_keys(config('logging.channels', [])) as $channel) {
            if (config("logging.channels.$channel.path")) {
                config(["logging.channels.$channel.path" => sys_get_temp_dir()."/libraro-test-$channel.log"]);
            }
        }
        $this->withoutMiddleware([\App\Http\Middleware\LicenseCheck::class, \App\Http\Middleware\LoggerMiddleware::class]);
    }

    private function learner(): Learner
    {
        $learner = Learner::withoutGlobalScopes()->where('status', 1)->whereNull('deleted_at')->whereNotNull('library_id')->orderByDesc('id')->first();
        if (! $learner) {
            $this->markTestSkipped('no active learner in this database');
        }

        return $learner;
    }

    public function test_subscription_page_holds_plan_details_and_others_do_not(): void
    {
        $this->actingAs($this->learner(), 'learner');

        $this->get('/learner/subscription')->assertOk()->assertSee('Subscription')->assertSee('Plan history')->assertSee('lr-hero', false);
        $this->get('/learner/home')->assertOk()->assertDontSee('lr-hero', false);
        $this->get('/learner/profile')->assertOk()->assertSee('My profile')->assertDontSee('lr-hero', false)->assertDontSee('Plan details');
    }

    public function test_payments_are_grouped_by_month_with_receipt_modal(): void
    {
        $learner = $this->learner();
        $this->actingAs($learner, 'learner');

        $row = fn ($date, $amt) => [
            'library_id' => $learner->library_id, 'branch_id' => $learner->branch_id ?? 0, 'learner_id' => $learner->id,
            'total_amount' => $amt, 'paid_amount' => $amt, 'pending_amount' => 0, 'paid_date' => $date, 'is_paid' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('learner_transactions')->where('learner_id', $learner->id)->update(['deleted_at' => now()]);
        DB::table('learner_transactions')->insert([$row('2026-09-10', 1111), $row('2026-09-20', 222), $row('2026-10-05', 3333)]);

        $res = $this->get('/learner/transactions')->assertOk();
        $res->assertSee('October 2026')->assertSee('September 2026')->assertSee('₹1,333')->assertSee('₹3,333')
            ->assertSee('data-receipt', false)->assertSee('rcModal', false);
        $this->assertLessThan(strpos($res->getContent(), 'September 2026'), strpos($res->getContent(), 'October 2026'));
    }
}
