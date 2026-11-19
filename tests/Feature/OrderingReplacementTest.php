<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Learner;
use App\Models\Library;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderActivity;
use App\Models\Ordering\OrderCategory;
use App\Models\Ordering\OrderItem;
use App\Models\Ordering\OrderLine;
use App\Models\Ordering\OrderReplacement;
use App\Models\Ordering\OrderSetting;
use App\Models\Ordering\OrderTable;
use App\Services\Ordering\OrderStatusService;
use App\Services\Ordering\OrderSweepService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Ordering & Services, steps 4–5: replacements, learner answers, reminders / escalation / timeouts. */
class OrderingReplacementTest extends TestCase
{
    use DatabaseTransactions;

    private Learner $learner;

    private Library $library;

    private OrderSetting $settings;

    private OrderItem $veg;

    private OrderItem $grilled;

    private OrderItem $plain;

    private OrderItem $tea;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir().'/libraro-test-storage');
        @mkdir(sys_get_temp_dir().'/libraro-test-views', 0777, true);
        config(['view.compiled' => sys_get_temp_dir().'/libraro-test-views', 'cache.default' => 'array']);
        foreach (array_keys(config('logging.channels', [])) as $channel) {
            if (config("logging.channels.$channel.path")) {
                config(["logging.channels.$channel.path" => sys_get_temp_dir()."/libraro-test-$channel.log"]);
            }
        }
        $this->withoutMiddleware([\App\Http\Middleware\LicenseCheck::class, \App\Http\Middleware\LoggerMiddleware::class]);
        Storage::fake('local');

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
        foreach ([OrderItem::class, OrderCategory::class, OrderTable::class, OrderSetting::class] as $m) {
            $m::withoutGlobalScopes()->where('branch_id', $bid)->forceDelete();
        }
        Order::withoutGlobalScopes()->where('branch_id', $bid)->delete();
        Branch::withoutGlobalScopes()->where('id', $bid)->update(['upi_id' => 'library@upi']);
        $this->settings = OrderSetting::withoutGlobalScopes()->create(['library_id' => $learner->library_id, 'branch_id' => $bid, 'is_enabled' => true,
            'max_open_orders' => 0, 'replacement_timeout_minutes' => 10, 'reminder_minutes' => 2, 'escalation_minutes' => 5]);
        $base = ['library_id' => $learner->library_id, 'branch_id' => $bid];
        $cat = OrderCategory::withoutGlobalScopes()->create($base + ['name' => 'Food']);
        $mk = fn ($n, $p) => OrderItem::withoutGlobalScopes()->create($base + ['order_category_id' => $cat->id, 'name' => $n, 'price' => $p]);
        $this->veg = $mk('Veg Sandwich', 60);
        $this->grilled = $mk('Grilled Sandwich', 70);
        $this->plain = $mk('Plain Sandwich', 40);
        $this->tea = $mk('Tea', 15);
    }

    private function order(array $over = []): Order
    {
        $this->actingAs($this->learner, 'learner');
        $res = $this->postJson('/learner/orders', $over + [
            'mode' => 'counter', 'payment_method' => 'offline', 'idempotency_key' => 'r-'.uniqid(),
            'lines' => [['item_id' => $this->veg->id, 'qty' => 1], ['item_id' => $this->tea->id, 'qty' => 2]],
        ])->assertOk();
        $this->app['auth']->guard('learner')->logout();
        $this->app->forgetScopedInstances();
        $this->actingAs($this->library, 'library');

        return Order::withoutGlobalScopes()->where('order_no', $res->json('order_no'))->firstOrFail();
    }

    private function line(Order $o, int $itemId): OrderLine
    {
        return OrderLine::where('order_id', $o->id)->where('order_item_id', $itemId)->firstOrFail();
    }

    private function answer(Order $o, array $choices)
    {
        $this->actingAs($this->learner, 'learner');
        $res = $this->post('/learner/orders/'.$o->order_no.'/replacements', ['choices' => $choices]);
        $this->app['auth']->guard('learner')->logout();
        $this->actingAs($this->library, 'library');

        return $res;
    }

    public function test_dearer_replacement_needs_the_learner_and_updates_the_total(): void
    {
        $o = $this->order();
        $line = $this->line($o, $this->veg->id);

        $this->get('/library/orders/'.$o->id)->assertOk()->assertSee('Ran out?')->assertSee('Grilled Sandwich');
        $this->post('/library/orders/'.$o->id.'/unavailable', ['line_id' => $line->id, 'item_id' => $this->grilled->id])->assertSessionHasNoErrors();

        $o->refresh();
        $this->assertSame(Order::REPLACEMENT_PENDING, $o->status);
        $this->assertEquals(90, (float) $o->total); // nothing charged yet
        $this->assertFalse($this->veg->fresh()->is_available); // out of stock for everyone
        $r = OrderReplacement::where('order_id', $o->id)->firstOrFail();
        $this->assertEquals(10, (float) $r->price_diff);
        $this->assertTrue(DB::table('notifications')->where('notifiable_id', $this->learner->id)->where('notification_type', 'order')->where('data', 'like', '%ran out%')->exists());
        $this->assertEmpty(array_intersect(['ready', 'collect', 'accept'], array_column(app(OrderStatusService::class)->staffActions($o), 'key'))); // nothing moves on while the learner decides

        // learner sees one box with the price difference, must choose for every item
        $this->actingAs($this->learner, 'learner');
        $this->get('/learner/orders/'.$o->order_no)->assertOk()->assertSee('An item ran out')->assertSee('₹10 more');
        $this->actingAs($this->library, 'library');
        $this->answer($o, [])->assertSessionHasErrors('choices');

        $this->answer($o, [$r->id => 'accept'])->assertRedirect()->assertSessionHasNoErrors();
        $o->refresh();
        $this->assertSame(Order::CONFIRMED, $o->status);
        $this->assertEquals(100, (float) $o->total);
        $this->assertSame(OrderLine::REPLACED, $line->fresh()->fulfilment_status);
        $new = OrderLine::where('order_id', $o->id)->where('replaced_line_id', $line->id)->firstOrFail();
        $this->assertSame('Grilled Sandwich', $new->item_name);
        $this->assertSame('accepted', $r->fresh()->status);
    }

    public function test_cheaper_replacement_on_a_paid_order_makes_a_refund(): void
    {
        $o = $this->order();
        $this->post('/library/orders/'.$o->id.'/payments', ['amount' => 90, 'method' => 'cash']);
        $this->assertSame(Order::PAY_PAID, $o->fresh()->payment_status);

        $line = $this->line($o, $this->veg->id);
        $this->post('/library/orders/'.$o->id.'/unavailable', ['line_id' => $line->id, 'item_id' => $this->plain->id]);
        $r = OrderReplacement::where('order_id', $o->id)->firstOrFail();
        $this->assertEquals(-20, (float) $r->price_diff);
        $this->answer($o, [$r->id => 'accept'])->assertSessionHasNoErrors();

        $o->refresh();
        $this->assertEquals(70, (float) $o->total);
        $this->assertSame(Order::PAY_REFUND_PENDING, $o->payment_status);
        $this->post('/library/orders/'.$o->id.'/refunds', ['amount' => 20, 'method' => 'cash'])->assertSessionHasNoErrors();
        $this->assertSame(Order::PAY_REFUNDED, $o->fresh()->payment_status);
    }

    public function test_removing_every_item_cancels_and_other_asks_staff_again(): void
    {
        $o = $this->order(['lines' => [['item_id' => $this->veg->id, 'qty' => 1]]]);
        $line = $this->line($o, $this->veg->id);
        $this->post('/library/orders/'.$o->id.'/unavailable', ['line_id' => $line->id, 'item_id' => $this->grilled->id]);
        $r = OrderReplacement::where('order_id', $o->id)->firstOrFail();

        // "suggest something else" → back to staff with the line unavailable
        $this->answer($o, [$r->id => 'other'])->assertSessionHasNoErrors();
        $o->refresh();
        $this->assertSame(Order::UNDER_REVIEW, $o->status);
        $this->assertSame(OrderLine::UNAVAILABLE, $line->fresh()->fulfilment_status);

        // second suggestion with no replacement → learner removes → nothing left → cancelled
        $this->post('/library/orders/'.$o->id.'/unavailable', ['line_id' => $line->id])->assertSessionHasNoErrors();
        $r2 = OrderReplacement::where('order_id', $o->id)->where('status', 'pending')->firstOrFail();
        $this->answer($o, [$r2->id => 'accept'])->assertSessionHasErrors('choices'); // nothing to accept
        $this->answer($o, [$r2->id => 'remove'])->assertSessionHasNoErrors();
        $o->refresh();
        $this->assertSame(Order::CANCELLED, $o->status);
        $this->assertEquals(0, (float) $o->total);
    }

    public function test_suggested_item_running_out_too_is_not_added(): void
    {
        $o = $this->order();
        $line = $this->line($o, $this->veg->id);
        $this->post('/library/orders/'.$o->id.'/unavailable', ['line_id' => $line->id, 'item_id' => $this->grilled->id]);
        $r = OrderReplacement::where('order_id', $o->id)->firstOrFail();
        $this->grilled->update(['is_available' => false]);

        $this->answer($o, [$r->id => 'accept'])->assertSessionHasNoErrors();
        $this->assertSame('withdrawn', $r->fresh()->status);
        $this->assertSame(OrderLine::UNAVAILABLE, $line->fresh()->fulfilment_status);
        $this->assertSame(0, OrderLine::where('order_id', $o->id)->where('item_name', 'Grilled Sandwich')->count());

        // staff then take it off the order themselves
        $this->delete('/library/orders/'.$o->id.'/lines/'.$line->id)->assertSessionHasNoErrors();
        $this->assertEquals(30, (float) $o->fresh()->total);
    }

    public function test_partial_fulfilment_keeps_preparing_but_blocks_handover(): void
    {
        $o = $this->order();
        $this->postJson('/library/orders/'.$o->id.'/act', ['action' => 'accept'])->assertOk();
        $this->postJson('/library/orders/'.$o->id.'/act', ['action' => 'prepare'])->assertOk();
        $line = $this->line($o, $this->veg->id);
        $this->post('/library/orders/'.$o->id.'/unavailable', ['line_id' => $line->id, 'item_id' => $this->grilled->id]);

        $o->refresh();
        $this->assertSame(Order::PREPARING, $o->status); // learner allowed partial fulfilment
        $this->postJson('/library/orders/'.$o->id.'/act', ['action' => 'ready'])->assertStatus(409);
        $feedRow = collect($this->getJson('/library/orders/feed')->json('orders'))->firstWhere('id', $o->id);
        $this->assertTrue($feedRow['waiting']);

        $r = OrderReplacement::where('order_id', $o->id)->firstOrFail();
        $this->answer($o, [$r->id => 'remove']);
        $this->assertSame(Order::PREPARING, $o->fresh()->status);
        $this->postJson('/library/orders/'.$o->id.'/act', ['action' => 'ready'])->assertOk();
    }

    public function test_sweep_reminds_escalates_and_applies_replacement_timeouts(): void
    {
        $o = $this->order();
        $sweep = app(OrderSweepService::class);

        Order::withoutGlobalScopes()->where('id', $o->id)->update(['created_at' => now()->subMinutes(3)]);
        $this->assertSame(1, $sweep->sweep((int) $o->branch_id)['reminded']);
        $this->assertNotNull($o->fresh()->reminded_at);
        $this->assertSame(Order::NEW, $o->fresh()->status);

        Order::withoutGlobalScopes()->where('id', $o->id)->update(['created_at' => now()->subMinutes(6)]);
        $this->assertSame(1, $sweep->sweep((int) $o->branch_id)['escalated']);
        $this->assertSame(Order::ATTENTION, $o->fresh()->status); // never rejected by itself
        $this->assertTrue(DB::table('notifications')->where('notifiable_id', $this->library->id)->where('guard', 'library')->where('data', 'like', '%is waiting%')->exists());
        $this->assertSame(0, $sweep->sweep((int) $o->branch_id)['escalated']); // only once

        // replacement not answered in time → removed (library rule "remove")
        $line = $this->line($o, $this->veg->id);
        $this->post('/library/orders/'.$o->id.'/unavailable', ['line_id' => $line->id, 'item_id' => $this->grilled->id]);
        OrderReplacement::where('order_id', $o->id)->update(['deadline_at' => now()->subMinute()]);
        $this->assertSame(1, $sweep->sweep((int) $o->branch_id)['expired']);
        $this->assertSame(OrderLine::REMOVED, $line->fresh()->fulfilment_status);
        $this->assertEquals(30, (float) $o->fresh()->total);
        $this->assertTrue(OrderActivity::where('order_id', $o->id)->where('action', 'replacement_timeout')->exists());

        // rule "staff decides": the order waits for staff instead
        $this->settings->update(['replacement_timeout_action' => 'escalate']);
        $o2 = $this->order(['lines' => [['item_id' => $this->tea->id, 'qty' => 1], ['item_id' => $this->plain->id, 'qty' => 1]]]);
        $l2 = $this->line($o2, $this->tea->id);
        $this->post('/library/orders/'.$o2->id.'/unavailable', ['line_id' => $l2->id]);
        OrderReplacement::where('order_id', $o2->id)->update(['deadline_at' => now()->subMinute()]);
        $sweep->sweep((int) $o2->branch_id);
        $this->assertSame(Order::ATTENTION, $o2->fresh()->status);
        $this->assertSame(OrderLine::UNAVAILABLE, $l2->fresh()->fulfilment_status);

        // the artisan command runs the same sweep
        $this->artisan('orders:sweep', ['--branch' => $o->branch_id])->assertExitCode(0);
    }
}
