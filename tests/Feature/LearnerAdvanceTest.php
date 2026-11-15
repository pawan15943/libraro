<?php

namespace Tests\Feature;

use App\Models\Learner;
use App\Services\LearnerAdvanceService;
use App\Support\PaymentTypes;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Advance (paid over the bill): used on renewal, refunded or waived on leaving. docs/LEARNER_ADVANCE.md */
class LearnerAdvanceTest extends TestCase
{
    use DatabaseTransactions;

    private Learner $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir().'/libraro-test-storage');
        foreach (array_keys(config('logging.channels', [])) as $channel) {
            if (config("logging.channels.$channel.path")) {
                config(["logging.channels.$channel.path" => sys_get_temp_dir()."/libraro-test-$channel.log"]);
            }
        }
        $learner = Learner::withoutGlobalScopes()->whereNull('deleted_at')->whereNotNull('library_id')->whereNotNull('branch_id')->orderByDesc('id')->first();
        if (! $learner) {
            $this->markTestSkipped('no learner in this database');
        }
        $this->learner = $learner;
        DB::table('learner_transactions')->where('learner_id', $learner->id)->update(['deleted_at' => now()]);
    }

    /** a bill: total, paid, pending, extra (refund column) */
    private function tx(float $total, float $paid, float $pending, float $extra = 0): int
    {
        return DB::table('learner_transactions')->insertGetId([
            'library_id' => $this->learner->library_id, 'branch_id' => $this->learner->branch_id, 'learner_id' => $this->learner->id,
            'total_amount' => $total, 'paid_amount' => $paid, 'pending_amount' => $pending, 'refund' => $extra,
            'is_paid' => $pending > 0 ? 0 : 1, 'paid_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function row(int $id): object
    {
        return DB::table('learner_transactions')->where('id', $id)->first();
    }

    public function test_booking_extra_pays_the_renewal(): void
    {
        $svc = app(LearnerAdvanceService::class);
        $booking = $this->tx(800, 800, 0, 200);          // ₹1000 for an ₹800 plan
        $this->assertSame(200.0, $svc->balance($this->learner->id));

        $renew = $this->tx(800, 600, 200);               // renewal: ₹600 cash, ₹200 left
        $used = $svc->apply($this->learner->id, $renew);

        $this->assertSame(200.0, $used);
        $this->assertEquals(0, $this->row($booking)->refund);
        $this->assertEquals(800, $this->row($renew)->paid_amount);
        $this->assertEquals(0, $this->row($renew)->pending_amount);
        $this->assertEquals(1, $this->row($renew)->is_paid);
        $this->assertSame(0.0, $svc->balance($this->learner->id));

        $log = DB::table('learner_transaction_activity')->where('learner_id', $this->learner->id)->where('particular', LearnerAdvanceService::ADJUSTED)->first();
        $this->assertNotNull($log);
        $this->assertEquals(200, $log->amount);
        $this->assertSame('Settle', $log->dr_cr);   // not money: reports don't count it again
        $this->assertSame($renew, (int) $log->learner_transaction_id);
    }

    public function test_bigger_advance_carries_forward(): void
    {
        $svc = app(LearnerAdvanceService::class);
        $this->tx(800, 800, 0, 1000);
        $renew = $this->tx(800, 0, 800);
        $this->assertSame(800.0, $svc->apply($this->learner->id, $renew));
        $this->assertSame(200.0, $svc->balance($this->learner->id));
        $this->assertEquals(0, $this->row($renew)->pending_amount);
    }

    public function test_nothing_due_uses_nothing(): void
    {
        $svc = app(LearnerAdvanceService::class);
        $this->tx(800, 800, 0, 200);
        $renew = $this->tx(800, 800, 0);
        $this->assertSame(0.0, $svc->apply($this->learner->id, $renew));
        $this->assertSame(200.0, $svc->balance($this->learner->id));
    }

    public function test_leaving_refunds_the_advance_as_money_out(): void
    {
        $svc = app(LearnerAdvanceService::class);
        $this->tx(800, 800, 0, 200);
        $out = $svc->settle($this->learner->id, 'refund', 2);

        $this->assertSame(200.0, $out['refunded']);
        $this->assertSame(0.0, $svc->balance($this->learner->id));
        $log = DB::table('learner_transaction_activity')->where('learner_id', $this->learner->id)->where('particular', LearnerAdvanceService::REFUNDED)->first();
        $this->assertSame('REFUND', $log->payment_type);
        $this->assertSame('Dr', $log->dr_cr);
        $this->assertSame('OFFLINE', $log->payment_mode);
        $this->assertSame(1, DB::table('learner_transaction_activity')->where('id', $log->id)->whereRaw(PaymentTypes::refundSql())->count());
    }

    public function test_leaving_pays_dues_first_then_waives_the_rest(): void
    {
        $svc = app(LearnerAdvanceService::class);
        $this->tx(800, 800, 0, 300);
        $due = $this->tx(500, 400, 100);
        $out = $svc->settle($this->learner->id, 'waive');

        $this->assertSame(100.0, $out['adjusted']);
        $this->assertSame(200.0, $out['waived']);
        $this->assertEquals(0, $this->row($due)->pending_amount);
        $this->assertSame(0.0, $svc->balance($this->learner->id));
        $this->assertSame(0.0, $svc->pending($this->learner->id));
    }

    public function test_extra_as_price_raises_the_plan_price_and_leaves_no_advance(): void
    {
        $billing = \App\Services\BillingAmountService::calculate(800, 100, 'amount', 50, 1000); // bill 850, paid 1000
        $this->assertEquals(150, $billing['extra_amount']);

        [$price, $same] = \App\Services\BillingAmountService::extraAsPrice(800, 100, 50, 1000, $billing, false);
        $this->assertEquals(800, $price);
        $this->assertSame($billing, $same);

        [$price, $raised] = \App\Services\BillingAmountService::extraAsPrice(800, 100, 50, 1000, $billing, true);
        $this->assertEquals(950, $price);
        $this->assertEquals(1000, $raised['total_amount']);
        $this->assertEquals(0, $raised['extra_amount']);
        $this->assertEquals(0, $raised['pending_amount']);
        $this->assertEquals(50, $raised['discount_amount']);
    }

    public function test_higher_price_needs_the_branch_switch_and_is_in_both_plans(): void
    {
        $perm = DB::table('permissions')->where('guard_name', 'library')->where('name', 'Charge Above Plan Price')->value('id');
        $this->assertNotNull($perm);
        foreach ([1, 3] as $plan) {
            $this->assertTrue(DB::table('subscription_permission')->where('subscription_id', $plan)->where('permission_id', $perm)->exists(), "plan $plan");
        }

        $lib = \App\Models\Library::withoutGlobalScopes()->whereNull('deleted_at')->where('library_type', 1)->first()
            ?? \App\Models\Library::withoutGlobalScopes()->whereNull('deleted_at')->first();
        $branch = DB::table('branches')->where('library_id', $lib->id)->whereNull('deleted_at')->value('id');
        if (! $branch) {
            $this->markTestSkipped('no branch');
        }
        $this->actingAs($lib, 'library');
        DB::table('branches')->where('id', $branch)->update(['allow_higher_price' => 0]);
        $this->assertTrue(extraAsPricePermitted());
        $this->assertFalse(extraAsPriceAllowed($branch));
        DB::table('branches')->where('id', $branch)->update(['allow_higher_price' => 1]);
        $this->assertTrue(extraAsPriceAllowed($branch));
    }

    public function test_branch_card_switch_turns_higher_price_on_and_off(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\LicenseCheck::class, \App\Http\Middleware\LoggerMiddleware::class, \App\Http\Middleware\ProtectPortalPages::class]);
        $lib = \App\Models\Library::withoutGlobalScopes()->find($this->learner->library_id);
        $branch = (int) $this->learner->branch_id;
        $this->actingAs($lib, 'library');

        $this->get(route('branch.list'))->assertOk()->assertSee('Higher price on extra payment')->assertSee('js-higher-price', false);
        $this->post(route('branch.higherPrice', $branch), ['on' => 1])->assertOk()->assertJson(['status' => true, 'on' => true]);
        $this->assertSame(1, (int) DB::table('branches')->where('id', $branch)->value('allow_higher_price'));
        $this->post(route('branch.higherPrice', $branch), ['on' => 0])->assertOk()->assertJson(['on' => false]);
        $this->assertSame(0, (int) DB::table('branches')->where('id', $branch)->value('allow_higher_price'));

        $other = DB::table('branches')->where('library_id', '!=', $lib->id)->value('id');
        if ($other) {
            $this->post(route('branch.higherPrice', $other), ['on' => 1])->assertNotFound();
        }
    }
}
