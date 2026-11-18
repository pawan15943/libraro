<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Learner;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderActivity;
use App\Models\Ordering\OrderCategory;
use App\Models\Ordering\OrderItem;
use App\Models\Ordering\OrderPayment;
use App\Models\Ordering\OrderSetting;
use App\Models\Ordering\OrderTable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Ordering & Services, step 2: learner menu, cart → order, tracking, payment proof, cancel (docs/ORDERING.md). */
class OrderingLearnerTest extends TestCase
{
    use DatabaseTransactions;

    private Learner $learner;

    private OrderSetting $settings;

    private OrderCategory $food;

    private OrderCategory $stationery;

    private OrderItem $tea;

    private OrderItem $sandwich;

    private OrderItem $pen;

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
        Storage::fake('local');

        // a learner with a running plan and a seat
        $detail = DB::table('learner_detail')->where('status', 1)->whereNull('deleted_at')->whereNotNull('seat_no')
            ->whereDate('plan_start_date', '<=', today())->whereDate('plan_end_date', '>=', today()->addDays(10))
            ->orderByDesc('id')->first() ?? $this->markTestSkipped('no learner with an active plan');
        $learner = Learner::withoutGlobalScopes()->whereNull('deleted_at')->find($detail->learner_id) ?? $this->markTestSkipped('learner missing');
        $learner->forceFill(['frozen_status' => 0])->save();
        $this->learner = $learner;
        $bid = (int) $learner->branch_id;

        foreach ([OrderItem::class, OrderCategory::class, OrderTable::class, OrderSetting::class] as $m) {
            $m::withoutGlobalScopes()->where('branch_id', $bid)->forceDelete();
        }
        Branch::withoutGlobalScopes()->where('id', $bid)->update(['upi_id' => 'library@upi']);

        $this->settings = OrderSetting::withoutGlobalScopes()->create([
            'library_id' => $learner->library_id, 'branch_id' => $bid, 'is_enabled' => true,
            'mode_seat' => true, 'mode_table' => true, 'mode_counter' => true, 'delivery_charge' => 5,
            'pay_online' => true, 'pay_offline' => true, 'pay_later' => true, 'max_open_orders' => 3,
        ]);
        $base = ['library_id' => $learner->library_id, 'branch_id' => $bid];
        $this->food = OrderCategory::withoutGlobalScopes()->create($base + ['name' => 'Food', 'icon' => 'flame']);
        $this->stationery = OrderCategory::withoutGlobalScopes()->create($base + ['name' => 'Stationery', 'icon' => 'notebook-pen', 'allow_pay_later' => false]);
        $this->tea = OrderItem::withoutGlobalScopes()->create($base + ['order_category_id' => $this->food->id, 'name' => 'Tea', 'price' => 15]);
        $this->sandwich = OrderItem::withoutGlobalScopes()->create($base + ['order_category_id' => $this->food->id, 'name' => 'Veg Sandwich', 'price' => 60]);
        $this->pen = OrderItem::withoutGlobalScopes()->create($base + ['order_category_id' => $this->stationery->id, 'name' => 'Pen', 'price' => 10, 'modes' => ['counter']]);

        $this->actingAs($learner, 'learner');
    }

    private function cart(array $over = []): array
    {
        return $over + [
            'mode' => 'counter', 'payment_method' => 'offline', 'idempotency_key' => 'k-'.uniqid(),
            'lines' => [['item_id' => $this->tea->id, 'qty' => 2], ['item_id' => $this->sandwich->id, 'qty' => 1]],
        ];
    }

    public function test_menu_page_shows_items_and_hides_when_off(): void
    {
        $this->get('/learner/orders')->assertOk()->assertSee('Veg Sandwich')->assertSee('Where should we bring it?');

        $this->settings->update(['is_enabled' => false]);
        $this->get('/learner/orders')->assertOk()->assertSee('Ordering is not available')->assertDontSee('Veg Sandwich');
    }

    public function test_place_order_with_snapshot_prices_and_one_order_per_key(): void
    {
        $cart = $this->cart(['expected_total' => 90]);
        $res = $this->postJson('/learner/orders', $cart)->assertOk()->assertJson(['success' => true]);
        $order = Order::withoutGlobalScopes()->where('order_no', $res->json('order_no'))->firstOrFail();

        $this->assertSame(Order::NEW, $order->status);
        $this->assertSame(Order::PAY_PENDING, $order->payment_status);
        $this->assertEquals(90, (float) $order->total);
        $this->assertEquals(0, (float) $order->delivery_charge); // counter: no delivery charge
        $this->assertSame(2, $order->lines()->count());
        $this->assertMatchesRegularExpression('/^OD'.$this->learner->branch_id.'-\d{6}-\d{3}$/', $order->order_no);
        $this->assertTrue(OrderActivity::where('order_id', $order->id)->where('action', 'placed')->exists());

        // double click / retry with the same key → the same order, not a second one
        $again = $this->postJson('/learner/orders', $cart)->assertOk();
        $this->assertSame($order->order_no, $again->json('order_no'));
        $this->assertSame(1, Order::withoutGlobalScopes()->where('learner_id', $this->learner->id)->where('idempotency_key', $cart['idempotency_key'])->count());

        // the same item twice in the cart = more quantity
        $res = $this->postJson('/learner/orders', $this->cart(['lines' => [['item_id' => $this->tea->id, 'qty' => 1], ['item_id' => $this->tea->id, 'qty' => 2]]]))->assertOk();
        $o2 = Order::withoutGlobalScopes()->where('order_no', $res->json('order_no'))->firstOrFail();
        $this->assertSame(1, $o2->lines()->count());
        $this->assertSame(3, (int) $o2->lines()->value('quantity'));

        // tracking + history
        $this->get('/learner/orders/'.$order->order_no)->assertOk()->assertSee('Veg Sandwich')->assertSee('Order placed');
        $this->getJson('/learner/orders/'.$order->order_no.'/status')->assertOk()->assertJson(['status' => 'new']);
        $this->get('/learner/orders/history')->assertOk()->assertSee('#'.substr($order->order_no, -3));
    }

    public function test_seat_and_table_delivery(): void
    {
        $res = $this->postJson('/learner/orders', $this->cart(['mode' => 'seat']))->assertOk();
        $order = Order::withoutGlobalScopes()->where('order_no', $res->json('order_no'))->firstOrFail();
        $this->assertNotEmpty($order->seat_no);
        $this->assertEquals(95, (float) $order->total); // + ₹5 delivery

        // table mode needs an open table of this branch
        $this->postJson('/learner/orders', $this->cart(['mode' => 'table']))->assertStatus(422); // no table yet
        $t = OrderTable::withoutGlobalScopes()->create(['library_id' => $this->learner->library_id, 'branch_id' => $this->learner->branch_id, 'label' => '7']);
        $this->postJson('/learner/orders', $this->cart(['mode' => 'table']))->assertStatus(422)->assertJsonPath('errors.order_table_id.0', 'Choose your table.');
        $res = $this->postJson('/learner/orders', $this->cart(['mode' => 'table', 'order_table_id' => $t->id]))->assertOk();
        $this->assertSame('7', Order::withoutGlobalScopes()->where('order_no', $res->json('order_no'))->value('table_label'));

        // an item limited to the counter cannot go to the seat
        $this->postJson('/learner/orders', $this->cart(['mode' => 'seat', 'lines' => [['item_id' => $this->pen->id, 'qty' => 1]]]))
            ->assertStatus(422)->assertJsonFragment(['message' => 'Pen cannot be ordered for seat delivery.']);
    }

    public function test_items_must_be_on_the_menu_and_prices_current(): void
    {
        $this->tea->update(['is_available' => false]);
        $this->postJson('/learner/orders', $this->cart())->assertStatus(422)->assertJsonFragment(['message' => 'Tea is not available right now. Please remove it from your cart.']);
        $this->tea->update(['is_available' => true]);

        $this->food->update(['is_enabled' => false]);
        $this->postJson('/learner/orders', $this->cart())->assertStatus(422);
        $this->food->update(['is_enabled' => true]);

        // another branch's item is never accepted
        $foreign = OrderItem::withoutGlobalScopes()->create(['library_id' => 1, 'branch_id' => $this->learner->branch_id + 999999, 'order_category_id' => $this->food->id, 'name' => 'X', 'price' => 1]);
        $this->postJson('/learner/orders', $this->cart(['lines' => [['item_id' => $foreign->id, 'qty' => 1]]]))->assertStatus(422);

        // price went up while shopping → 409 with the new total, nothing saved
        $this->sandwich->update(['price' => 70]);
        $before = Order::withoutGlobalScopes()->where('learner_id', $this->learner->id)->count();
        $this->postJson('/learner/orders', $this->cart(['expected_total' => 90]))->assertStatus(409)->assertJson(['code' => 'price_changed', 'total' => 100]);
        $this->assertSame($before, Order::withoutGlobalScopes()->where('learner_id', $this->learner->id)->count());
    }

    public function test_payment_methods_follow_settings_categories_and_pay_later_rules(): void
    {
        // stationery does not allow pay later → a mixed cart cannot use it
        $mixed = ['lines' => [['item_id' => $this->tea->id, 'qty' => 1], ['item_id' => $this->pen->id, 'qty' => 1]]];
        $this->postJson('/learner/orders', $this->cart($mixed + ['payment_method' => 'pay_later']))->assertStatus(422)->assertJsonValidationErrors('payment_method');
        $this->postJson('/learner/orders', $this->cart($mixed + ['payment_method' => 'online']))->assertOk();

        // pay later only for selected learners
        $this->settings->update(['pay_later_scope' => 'selected', 'pay_later_learner_ids' => [999999999]]);
        $this->postJson('/learner/orders', $this->cart(['payment_method' => 'pay_later']))->assertStatus(422);
        $this->settings->update(['pay_later_learner_ids' => [$this->learner->id], 'pay_later_limit' => 100]);
        $this->postJson('/learner/orders', $this->cart(['payment_method' => 'pay_later']))->assertOk(); // ₹90 of ₹100
        $this->postJson('/learner/orders', $this->cart(['payment_method' => 'pay_later', 'lines' => [['item_id' => $this->tea->id, 'qty' => 1]]]))
            ->assertStatus(422); // ₹10 room left → hidden

        // online needs the branch UPI ID
        Branch::withoutGlobalScopes()->where('id', $this->learner->branch_id)->update(['upi_id' => null]);
        $this->postJson('/learner/orders', $this->cart(['payment_method' => 'online']))->assertStatus(422);
    }

    public function test_hours_limit_and_plan_rules(): void
    {
        $this->settings->update(['opens_at' => now()->addHours(2)->format('H:i:s'), 'closes_at' => now()->addHours(3)->format('H:i:s')]);
        $this->postJson('/learner/orders', $this->cart())->assertStatus(422);
        $this->settings->update(['opens_at' => null, 'closes_at' => null, 'max_open_orders' => 1]);

        $this->postJson('/learner/orders', $this->cart())->assertOk();
        $this->postJson('/learner/orders', $this->cart())->assertStatus(422); // limit of open orders

        $this->postJson('/learner/orders', $this->cart(['lines' => []]))->assertStatus(422);
    }

    public function test_print_upload_is_checked_and_stored_privately(): void
    {
        $print = OrderItem::withoutGlobalScopes()->create(['library_id' => $this->learner->library_id, 'branch_id' => $this->learner->branch_id,
            'order_category_id' => $this->food->id, 'name' => 'Printout', 'price' => 2, 'price_unit' => 'page', 'needs_upload' => true]);

        $line = ['item_id' => $print->id, 'qty' => 10, 'copies' => 2, 'sides' => 'double'];
        $this->postJson('/learner/orders', $this->cart(['lines' => [$line]]))->assertStatus(422); // no file
        $this->post('/learner/orders', $this->cart(['lines' => [$line + ['file' => UploadedFile::fake()->create('virus.exe', 10)]]]), ['Accept' => 'application/json'])->assertStatus(422);

        $res = $this->post('/learner/orders', $this->cart(['lines' => [$line + ['file' => UploadedFile::fake()->create('notes.pdf', 200, 'application/pdf')]]]), ['Accept' => 'application/json'])->assertOk();
        $order = Order::withoutGlobalScopes()->where('order_no', $res->json('order_no'))->firstOrFail();
        $l = $order->lines()->first();
        $this->assertEquals(40, (float) $l->line_total); // ₹2 × 10 pages × 2 copies
        $this->assertSame(['pages' => 10, 'copies' => 2, 'sides' => 'double'], $l->options);
        $this->assertSame('notes.pdf', $l->upload_name);
        Storage::disk('local')->assertExists($l->upload_path);
    }

    public function test_online_payment_proof_and_cancel(): void
    {
        $res = $this->postJson('/learner/orders', $this->cart(['payment_method' => 'online']))->assertOk();
        $order = Order::withoutGlobalScopes()->where('order_no', $res->json('order_no'))->firstOrFail();

        $this->get('/learner/orders/'.$order->order_no)->assertOk()->assertSee('library@upi')->assertSee('Send payment proof');

        $this->post('/learner/orders/'.$order->order_no.'/payment', ['utr' => '1234 5678 9012', 'screenshot' => UploadedFile::fake()->image('pay.png')])
            ->assertRedirect()->assertSessionHas('success');
        $order->refresh();
        $this->assertSame(Order::PAY_PROCESSING, $order->payment_status);
        $this->assertSame('123456789012', $order->payment_ref);
        $this->assertSame(1, OrderPayment::withoutGlobalScopes()->where('order_id', $order->id)->where('status', 'pending')->count());
        // a second proof while it is being checked is refused
        $this->post('/learner/orders/'.$order->order_no.'/payment', ['screenshot' => UploadedFile::fake()->image('pay2.png')])->assertSessionHasErrors('screenshot');

        // cancel while still new → money being verified goes to refund
        $this->post('/learner/orders/'.$order->order_no.'/cancel', ['reason' => 'Changed my mind'])->assertRedirect();
        $order->refresh();
        $this->assertSame(Order::CANCELLED, $order->status);
        $this->assertSame(Order::PAY_REFUND_PENDING, $order->payment_status);
        $this->post('/learner/orders/'.$order->order_no.'/cancel')->assertSessionHasErrors('order');

        // once staff are preparing, the learner cannot cancel
        $res = $this->postJson('/learner/orders', $this->cart())->assertOk();
        Order::withoutGlobalScopes()->where('order_no', $res->json('order_no'))->update(['status' => Order::PREPARING]);
        $this->post('/learner/orders/'.$res->json('order_no').'/cancel')->assertSessionHasErrors('order');
    }

    public function test_learner_sees_only_own_orders(): void
    {
        $res = $this->postJson('/learner/orders', $this->cart())->assertOk();
        $other = Learner::withoutGlobalScopes()->whereNull('deleted_at')->where('id', '<>', $this->learner->id)->first() ?? $this->markTestSkipped('one learner only');
        $this->actingAs($other, 'learner');
        $this->get('/learner/orders/'.$res->json('order_no'))->assertNotFound();
        $this->post('/learner/orders/'.$res->json('order_no').'/cancel')->assertNotFound();
    }
}
