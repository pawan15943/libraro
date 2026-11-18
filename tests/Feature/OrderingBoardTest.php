<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Learner;
use App\Models\Library;
use App\Models\LibraryUser;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderActivity;
use App\Models\Ordering\OrderCategory;
use App\Models\Ordering\OrderItem;
use App\Models\Ordering\OrderPayment;
use App\Models\Ordering\OrderSetting;
use App\Models\Ordering\OrderTable;
use App\Services\Ordering\OrderStatusService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Ordering & Services, step 3: staff order board, status flow, payments, refunds (docs/ORDERING.md). */
class OrderingBoardTest extends TestCase
{
    use DatabaseTransactions;

    private Learner $learner;

    private Library $library;

    private OrderItem $tea;

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

        $detail = DB::table('learner_detail')->where('status', 1)->whereNull('deleted_at')->whereNotNull('seat_no')
            ->whereDate('plan_start_date', '<=', today())->whereDate('plan_end_date', '>=', today()->addDays(10))
            ->orderByDesc('id')->first() ?? $this->markTestSkipped('no learner with an active plan');
        $learner = Learner::withoutGlobalScopes()->whereNull('deleted_at')->find($detail->learner_id) ?? $this->markTestSkipped('learner missing');
        $learner->forceFill(['frozen_status' => 0])->save();
        $library = Library::find($learner->library_id) ?? $this->markTestSkipped('library missing');
        $library->forceFill(['library_type' => 3, 'current_branch' => $learner->branch_id])->save(); // Pro, working in the learner's branch
        $this->learner = $learner;
        $this->library = $library;

        $bid = (int) $learner->branch_id;
        foreach ([OrderItem::class, OrderCategory::class, OrderTable::class, OrderSetting::class] as $m) {
            $m::withoutGlobalScopes()->where('branch_id', $bid)->forceDelete();
        }
        Branch::withoutGlobalScopes()->where('id', $bid)->update(['upi_id' => 'library@upi']);
        OrderSetting::withoutGlobalScopes()->create(['library_id' => $learner->library_id, 'branch_id' => $bid, 'is_enabled' => true,
            'pay_later' => true, 'max_open_orders' => 0, 'learner_cancel_until' => 'confirmed']);
        $cat = OrderCategory::withoutGlobalScopes()->create(['library_id' => $learner->library_id, 'branch_id' => $bid, 'name' => 'Food']);
        $this->tea = OrderItem::withoutGlobalScopes()->create(['library_id' => $learner->library_id, 'branch_id' => $bid, 'order_category_id' => $cat->id, 'name' => 'Tea', 'price' => 15]);
    }

    /** Places an order as the learner, then signs in as the library owner. */
    private function order(array $over = []): Order
    {
        $this->actingAs($this->learner, 'learner');
        $res = $this->postJson('/learner/orders', $over + [
            'mode' => 'seat', 'payment_method' => 'offline', 'idempotency_key' => 'k-'.uniqid(),
            'lines' => [['item_id' => $this->tea->id, 'qty' => 2]],
        ])->assertOk();
        $this->app['auth']->guard('learner')->logout();
        $this->app->forgetScopedInstances();
        $this->actingAs($this->library, 'library');

        return Order::withoutGlobalScopes()->where('order_no', $res->json('order_no'))->firstOrFail();
    }

    private function act(Order $o, string $action, array $extra = [])
    {
        return $this->postJson('/library/orders/'.$o->id.'/act', ['action' => $action] + $extra);
    }

    public function test_board_feed_and_seat_delivery_flow_with_counter_payment(): void
    {
        $o = $this->order();

        $this->get('/library/orders')->assertOk()->assertSee('Order Board')->assertSee('Order sound is off')->assertSee('Live orders');
        $feed = $this->getJson('/library/orders/feed')->assertOk();
        $row = collect($feed->json('orders'))->firstWhere('id', $o->id);
        $this->assertSame('new', $row['tab']);
        $this->assertSame(['accept', 'reject'], array_column($row['actions'], 'key'));
        $this->assertGreaterThanOrEqual(1, $feed->json('counts.new'));

        // opening it = under review; nobody else is reminded about it any more
        $this->get('/library/orders/'.$o->id)->assertOk()->assertSee('Tea')->assertSee('Accept');
        $this->assertSame(Order::UNDER_REVIEW, $o->fresh()->status);

        $this->act($o, 'accept')->assertOk()->assertJson(['status' => 'confirmed']);
        $this->assertSame('library', $o->fresh()->handled_by_type);
        $this->act($o, 'ready')->assertStatus(409); // seat orders are dispatched, not "ready"
        $this->act($o, 'prepare')->assertOk();
        $this->act($o, 'dispatch')->assertOk()->assertJson(['status' => 'out_for_delivery']);
        $this->act($o, 'deliver')->assertOk()->assertJson(['status' => 'delivered']); // still unpaid → stays delivered
        $this->assertSame(OrderStatusService::class, get_class(app(OrderStatusService::class)));
        $this->assertNotContains('complete', array_column(app(OrderStatusService::class)->staffActions($o->fresh()), 'key'));

        // more than due is refused; the right amount closes the order by itself
        $this->post('/library/orders/'.$o->id.'/payments', ['amount' => 50, 'method' => 'cash'])->assertSessionHasErrors('amount');
        $this->post('/library/orders/'.$o->id.'/payments', ['amount' => 30, 'method' => 'cash'])->assertSessionHasNoErrors();
        $o->refresh();
        $this->assertSame(Order::COMPLETED, $o->status);
        $this->assertSame(Order::PAY_PAID, $o->payment_status);
        $this->assertSame(1, OrderPayment::withoutGlobalScopes()->where('order_id', $o->id)->where('status', 'verified')->count());
        $this->assertEqualsCanonicalizing(['placed', 'opened', 'accepted', 'preparing', 'out_for_delivery', 'delivered', 'payment_recorded', 'completed'],
            OrderActivity::where('order_id', $o->id)->pluck('action')->all());
    }

    public function test_counter_pickup_and_paid_orders_close_on_handover(): void
    {
        $o = $this->order(['mode' => 'counter']);
        $this->post('/library/orders/'.$o->id.'/payments', ['amount' => 30, 'method' => 'upi_counter', 'ref' => 'UTR1'])->assertSessionHasNoErrors();
        $this->assertSame(Order::PAY_PAID, $o->fresh()->payment_status);
        $this->assertSame(Order::NEW, $o->fresh()->status); // payment and order status are independent

        $this->act($o, 'accept')->assertOk();
        $this->act($o, 'dispatch')->assertStatus(409); // pickup orders are not dispatched
        $this->act($o, 'ready')->assertOk();
        $this->act($o, 'collect')->assertOk()->assertJson(['status' => 'completed']); // paid → closed straight away
    }

    public function test_double_actions_and_stale_versions_are_refused(): void
    {
        $o = $this->order();
        $v = $o->fresh()->version;

        $this->act($o, 'accept', ['v' => $v])->assertOk();
        // second tap / second staff member with the old version
        $this->act($o, 'accept', ['v' => $v])->assertStatus(409);
        $this->act($o, 'accept')->assertStatus(409)->assertJsonFragment(['status' => 'confirmed']);
        $this->assertSame(1, OrderActivity::where('order_id', $o->id)->where('action', 'accepted')->count());
    }

    public function test_reject_needs_a_reason_and_the_learner_sees_it(): void
    {
        $o = $this->order();
        $this->act($o, 'reject')->assertStatus(422);
        $this->act($o, 'reject', ['reason' => 'Kitchen closed today'])->assertOk()->assertJson(['status' => 'rejected']);

        $this->actingAs($this->learner, 'learner');
        $this->get('/learner/orders/'.$o->order_no)->assertOk()->assertSee('Kitchen closed today');
    }

    public function test_upi_proof_review_and_refund_after_cancel(): void
    {
        $o = $this->order(['payment_method' => 'online']);
        $this->actingAs($this->learner, 'learner');
        $this->post('/learner/orders/'.$o->order_no.'/payment', ['utr' => '111122223333', 'screenshot' => UploadedFile::fake()->image('p.png')])->assertSessionHasNoErrors();
        $this->actingAs($this->library, 'library');
        $p = OrderPayment::withoutGlobalScopes()->where('order_id', $o->id)->firstOrFail();

        $this->get('/library/orders/'.$o->id.'/payments/'.$p->id.'/proof')->assertOk();

        // not received → learner can pay again
        $this->post('/library/orders/'.$o->id.'/payments/'.$p->id.'/review', ['decision' => 'reject', 'note' => 'Not in bank'])->assertSessionHasNoErrors();
        $this->assertSame(Order::PAY_FAILED, $o->fresh()->payment_status);
        $this->post('/library/orders/'.$o->id.'/payments/'.$p->id.'/review', ['decision' => 'approve'])->assertSessionHasErrors('payment'); // already decided

        $this->actingAs($this->learner, 'learner');
        $this->post('/learner/orders/'.$o->order_no.'/payment', ['utr' => '999988887777', 'screenshot' => UploadedFile::fake()->image('p2.png')])->assertSessionHasNoErrors();
        $this->actingAs($this->library, 'library');
        $p2 = OrderPayment::withoutGlobalScopes()->where('order_id', $o->id)->where('status', 'pending')->firstOrFail();
        $this->post('/library/orders/'.$o->id.'/payments/'.$p2->id.'/review', ['decision' => 'approve'])->assertSessionHasNoErrors();
        $this->assertSame(Order::PAY_PAID, $o->fresh()->payment_status);
        $this->assertEquals(30, (float) $o->fresh()->paid_amount);

        // library cancels a paid order → money goes back
        $this->act($o, 'accept')->assertOk();
        $this->act($o, 'cancel', ['reason' => 'Milk finished'])->assertOk();
        $o->refresh();
        $this->assertSame(Order::CANCELLED, $o->status);
        $this->assertSame(Order::PAY_REFUND_PENDING, $o->payment_status);
        $this->post('/library/orders/'.$o->id.'/refunds', ['amount' => 40, 'method' => 'upi_counter'])->assertSessionHasErrors('amount');
        $this->post('/library/orders/'.$o->id.'/refunds', ['amount' => 30, 'method' => 'upi_counter', 'ref' => 'R1'])->assertSessionHasNoErrors();
        $this->assertSame(Order::PAY_REFUNDED, $o->fresh()->payment_status);
    }

    public function test_only_the_owner_cancels_after_preparation_and_can_undo_a_wrong_delivered(): void
    {
        $o = $this->order();
        $this->act($o, 'accept')->assertOk();
        $this->act($o, 'prepare')->assertOk();

        $staff = LibraryUser::withoutGlobalScopes()->where('library_id', $this->library->id)->first();
        if ($staff) {
            $this->app['auth']->guard('library')->logout();
            $this->actingAs($staff, 'library_user');
            $this->assertNotContains('cancel', array_column(app(OrderStatusService::class)->staffActions($o->fresh()), 'key'));
            $this->app['auth']->guard('library_user')->logout();
            $this->actingAs($this->library, 'library');
        }
        $this->assertContains('cancel', array_column(app(OrderStatusService::class)->staffActions($o->fresh()), 'key'));

        $this->act($o, 'dispatch')->assertOk();
        $this->act($o, 'deliver')->assertOk();
        $this->act($o, 'undo')->assertStatus(422); // reason needed
        $this->act($o, 'undo', ['reason' => 'Tapped by mistake'])->assertOk()->assertJson(['status' => 'out_for_delivery']);
        $this->assertTrue(OrderActivity::where('order_id', $o->id)->where('action', 'status_corrected')->exists());
    }

    public function test_print_file_download_and_other_branch_orders_are_hidden(): void
    {
        $print = OrderItem::withoutGlobalScopes()->create(['library_id' => $this->learner->library_id, 'branch_id' => $this->learner->branch_id,
            'order_category_id' => $this->tea->order_category_id, 'name' => 'Print', 'price' => 2, 'needs_upload' => true]);
        $this->actingAs($this->learner, 'learner');
        $res = $this->post('/learner/orders', ['mode' => 'counter', 'payment_method' => 'offline', 'idempotency_key' => 'f-'.uniqid(),
            'lines' => [['item_id' => $print->id, 'qty' => 3, 'file' => UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf')]]], ['Accept' => 'application/json'])->assertOk();
        $this->actingAs($this->library, 'library');
        $o = Order::withoutGlobalScopes()->where('order_no', $res->json('order_no'))->firstOrFail();
        $line = $o->lines()->first();
        $this->get('/library/orders/'.$o->id.'/files/'.$line->id)->assertOk()->assertDownload('notes.pdf');

        $o->forceFill(['branch_id' => $o->branch_id + 999999])->save();
        $this->get('/library/orders/'.$o->id)->assertNotFound();
        $this->act($o, 'accept')->assertNotFound();
        $this->assertNull(collect($this->getJson('/library/orders/feed')->json('orders'))->firstWhere('id', $o->id));
    }
}
