<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Learner;
use App\Models\Library;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderCategory;
use App\Models\Ordering\OrderItem;
use App\Models\Ordering\OrderPayment;
use App\Models\Ordering\OrderSetting;
use App\Models\Ordering\OrderTable;
use App\Services\Ordering\OrderDashboardService;
use App\Services\Ordering\OrderingVisibility;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Ordering & Services: plan gating, Show / Hide Options and the dashboard Orders card (docs/ORDERING.md). */
class OrderingVisibilityTest extends TestCase
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
        OrderingVisibility::flush();

        DB::table('toggle_features')->where('category', OrderingVisibility::TOGGLE_CATEGORY)->exists() || $this->markTestSkipped('run migration 2026_11_19_000001 first');

        $detail = DB::table('learner_detail')->where('status', 1)->whereNull('deleted_at')->whereNotNull('seat_no')
            ->whereDate('plan_start_date', '<=', today())->whereDate('plan_end_date', '>=', today()->addDays(10))
            ->orderByDesc('id')->first() ?? $this->markTestSkipped('no learner with an active plan');
        $learner = Learner::withoutGlobalScopes()->whereNull('deleted_at')->find($detail->learner_id) ?? $this->markTestSkipped('learner missing');
        $learner->forceFill(['frozen_status' => 0])->save();
        $library = Library::find($learner->library_id) ?? $this->markTestSkipped('library missing');
        $library->forceFill(['library_type' => 3, 'current_branch' => $learner->branch_id])->save();
        $this->learner = $learner;
        $this->library = $library;

        $bid = (int) $learner->branch_id;
        foreach ([OrderPayment::class, Order::class, OrderItem::class, OrderCategory::class, OrderTable::class, OrderSetting::class] as $m) {
            $m::withoutGlobalScopes()->where('branch_id', $bid)->forceDelete();
        }
        Branch::withoutGlobalScopes()->where('id', $bid)->update(['hide_field' => null]);
        OrderSetting::withoutGlobalScopes()->create(['library_id' => $learner->library_id, 'branch_id' => $bid, 'is_enabled' => true]);
    }

    private function toggleId(string $name): int
    {
        return (int) DB::table('toggle_features')->where('name', $name)->value('id');
    }

    private function hide(string ...$names): void
    {
        Branch::withoutGlobalScopes()->where('id', $this->learner->branch_id)
            ->update(['hide_field' => json_encode(array_map(fn ($n) => (string) $this->toggleId($n), $names))]);
    }

    private function order(string $status, float $total, float $paid, string $when): Order
    {
        return Order::withoutGlobalScopes()->create([
            'library_id' => $this->library->id, 'branch_id' => $this->learner->branch_id, 'learner_id' => $this->learner->id,
            'order_no' => 'OD-T-'.uniqid(), 'status' => $status, 'mode' => 'counter', 'payment_method' => 'offline',
            'payment_status' => $paid >= $total ? Order::PAY_PAID : Order::PAY_PENDING,
            'subtotal' => $total, 'total' => $total, 'paid_amount' => $paid, 'created_at' => $when, 'updated_at' => $when,
        ]);
    }

    private function payment(Order $o, string $kind, float $amount, string $when): void
    {
        OrderPayment::withoutGlobalScopes()->create([
            'library_id' => $o->library_id, 'branch_id' => $o->branch_id, 'order_id' => $o->id, 'kind' => $kind,
            'method' => 'cash', 'amount' => $amount, 'status' => 'verified', 'verified_at' => $when, 'created_at' => $when,
        ]);
    }

    public function test_dashboard_summary_counts_orders_and_money_received(): void
    {
        $today = now()->setTime(10, 0)->toDateTimeString();
        $earlier = now()->startOfMonth()->toDateTimeString();
        $a = $this->order(Order::COMPLETED, 100, 100, $today);
        $this->payment($a, 'payment', 100, $today);
        $this->order(Order::CANCELLED, 50, 0, $today);                 // not counted
        $b = $this->order(Order::DELIVERED, 80, 30, $today);            // ₹50 still to collect
        $this->payment($b, 'payment', 30, $today);
        $c = $this->order(Order::COMPLETED, 40, 40, $earlier);
        $this->payment($c, 'payment', 40, $earlier);
        $this->payment($c, 'refund', 10, $today);                       // refund given today

        $s = app(OrderDashboardService::class)->summary($this->library->id, $this->learner->branch_id);

        $this->assertSame(2, $s['today']['orders']);
        $this->assertEquals(120, $s['today']['income']);                // 100 + 30 − 10
        if (now()->day > 1) {
            $this->assertSame(3, $s['month']['orders']);
        }
        $this->assertEquals(160, $s['month']['income']);                // 100 + 30 + 40 − 10
        $this->assertEquals(50, $s['to_collect']);
        $this->assertSame(0, $s['open']); // completed / cancelled / delivered: nothing being prepared
    }

    public function test_owner_sees_orders_card_and_can_hide_it(): void
    {
        $this->actingAs($this->library, 'library');
        $this->get('/library/home')->assertOk()->assertSee('Orders today');

        $this->hide(OrderingVisibility::HIDE_DASHBOARD);
        $this->app->forgetScopedInstances();
        $this->get('/library/home')->assertOk()->assertDontSee('Orders today');
    }

    public function test_hide_options_remove_orders_links_from_the_sidebar(): void
    {
        $this->actingAs($this->library, 'library');
        $this->get('/library/home')->assertOk()->assertSee(route('orders.reports'), false)->assertSee(route('orders.board'), false);

        $this->hide(OrderingVisibility::HIDE_REPORTS);
        $html = $this->get('/library/home')->assertOk()->getContent();
        $this->assertStringNotContainsString(route('orders.reports'), $html);
        $this->assertStringContainsString(route('orders.board'), $html);

        $this->hide(OrderingVisibility::HIDE_MENU);
        $html = $this->get('/library/home')->assertOk()->getContent();
        $this->assertStringNotContainsString(route('orders.board'), $html);
        $this->assertStringNotContainsString(route('ordering.items'), $html);
        $this->assertStringNotContainsString('Orders today', $html);
    }

    public function test_module_off_for_learners_when_the_plan_does_not_include_it(): void
    {
        $this->actingAs($this->learner, 'learner');
        $this->getJson('/learner/orders')->assertOk();
        $this->assertTrue(OrderingVisibility::planAllows($this->library->id));

        $basic = (int) DB::table('subscriptions')->where('id', '!=', 3)
            ->whereNotIn('id', DB::table('subscription_permission')->join('permissions', 'permissions.id', '=', 'subscription_permission.permission_id')
                ->where('permissions.name', OrderingVisibility::PLAN_PERMISSION)->select('subscription_permission.subscription_id'))
            ->value('id') ?: $this->markTestSkipped('every plan has ordering');
        $this->library->forceFill(['library_type' => $basic])->save();
        OrderingVisibility::flush();

        $this->app->forgetInstance(\App\Services\LearnerPortalService::class);
        $ctx = app(\App\Services\Ordering\LearnerOrderingService::class)->context($this->learner->fresh());
        $this->assertFalse($ctx['enabled']);
        $this->assertFalse($ctx['eligible']);
        $this->assertNull((new \App\Services\LearnerPortalService())->summary($this->learner->fresh())['ordering'] ?? null);
    }
}
