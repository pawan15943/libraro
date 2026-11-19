<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Learner;
use App\Models\Library;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderCategory;
use App\Models\Ordering\OrderItem;
use App\Models\Ordering\OrderReplacement;
use App\Models\Ordering\OrderSetting;
use App\Models\Ordering\OrderTable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Ordering & Services, step 7: learner app API and staff board API (docs/API.md). */
class OrderingApiTest extends TestCase
{
    use DatabaseTransactions;

    private Learner $learner;

    private Library $library;

    private OrderItem $tea;

    private OrderItem $coffee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir().'/libraro-test-storage');
        config(['cache.default' => 'array']);
        foreach (array_keys(config('logging.channels', [])) as $channel) {
            if (config("logging.channels.$channel.path")) {
                config(["logging.channels.$channel.path" => sys_get_temp_dir()."/libraro-test-$channel.log"]);
            }
        }
        $this->withoutMiddleware([
            \App\Http\Middleware\LicenseCheck::class, \App\Http\Middleware\LoggerMiddleware::class,
            \App\Http\Middleware\ApiKeyMiddleware::class, \App\Http\Middleware\ApiSecurityMiddleware::class,
            \App\Http\Middleware\CheckDeviceHeader::class, \App\Http\Middleware\EnsureLibraryUserActive::class,
        ]);
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
        Branch::withoutGlobalScopes()->where('id', $bid)->update(['upi_id' => 'library@upi']);
        OrderSetting::withoutGlobalScopes()->create(['library_id' => $learner->library_id, 'branch_id' => $bid, 'is_enabled' => true, 'max_open_orders' => 0]);
        $cat = OrderCategory::withoutGlobalScopes()->create(['library_id' => $learner->library_id, 'branch_id' => $bid, 'name' => 'Drinks']);
        $this->tea = OrderItem::withoutGlobalScopes()->create(['library_id' => $learner->library_id, 'branch_id' => $bid, 'order_category_id' => $cat->id, 'name' => 'Tea', 'price' => 15]);
        $this->coffee = OrderItem::withoutGlobalScopes()->create(['library_id' => $learner->library_id, 'branch_id' => $bid, 'order_category_id' => $cat->id, 'name' => 'Coffee', 'price' => 25]);
    }

    public function test_learner_app_orders_end_to_end(): void
    {
        $this->actingAs($this->learner, 'learner_api');

        $home = $this->getJson('/api/v1/learner/ordering')->assertOk()->assertJson(['status' => true, 'data' => ['enabled' => true, 'eligible' => true]]);
        $this->assertContains('online', $home->json('data.payments'));
        $this->assertEqualsCanonicalizing(['Tea', 'Coffee'], array_column($home->json('data.menu.0.items'), 'name'));

        $place = $this->postJson('/api/v1/learner/orders/place', ['mode' => 'counter', 'payment_method' => 'online', 'idempotency_key' => 'api-1',
            'lines' => [['item_id' => $this->tea->id, 'qty' => 2]], 'expected_total' => 30])->assertOk()->assertJson(['status' => true]);
        $no = $place->json('data.order_no');
        $this->assertEquals(30, $place->json('data.total'));
        $this->assertSame('library@upi', $place->json('data.upi.upi_id'));
        $this->assertTrue($place->json('data.can_pay_online'));

        // same key again → same order
        $this->assertSame($no, $this->postJson('/api/v1/learner/orders/place', ['mode' => 'counter', 'payment_method' => 'online', 'idempotency_key' => 'api-1',
            'lines' => [['item_id' => $this->tea->id, 'qty' => 2]]])->json('data.order_no'));
        // wrong price → 409 with the new prices
        $this->postJson('/api/v1/learner/orders/place', ['mode' => 'counter', 'payment_method' => 'offline', 'idempotency_key' => 'api-2',
            'lines' => [['item_id' => $this->tea->id, 'qty' => 1]], 'expected_total' => 99])->assertOk()->assertJson(['status' => false, 'code' => 'price_changed', 'data' => ['total' => 15]]); // api/v1 sends 4xx as 200 + status:false (NormalizeApiValidationStatus)

        $this->getJson('/api/v1/learner/orders')->assertOk()->assertJsonPath('data.orders.0.order_no', $no);
        $this->getJson('/api/v1/learner/orders/'.$no)->assertOk()->assertJsonPath('data.items.0.name', 'Tea');
        $this->post('/api/v1/learner/orders/'.$no.'/payment', ['utr' => '123412341234', 'screenshot' => UploadedFile::fake()->image('p.png')], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.payment_status', 'processing');

        // staff app: feed, accept, suggest a replacement
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->library, 'library_api');
        $o = Order::withoutGlobalScopes()->where('order_no', $no)->firstOrFail();
        $feed = $this->getJson('/api/v1/library/orders/feed')->assertOk();
        $this->assertNotNull(collect($feed->json('data.orders'))->firstWhere('id', $o->id));
        $this->getJson('/api/v1/library/orders/'.$o->id)->assertOk()->assertJsonPath('data.status', 'under_review');
        $this->postJson('/api/v1/library/orders/'.$o->id.'/act', ['action' => 'accept'])->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->postJson('/api/v1/library/orders/'.$o->id.'/act', ['action' => 'accept'])->assertJson(['status' => false, 'data' => ['status' => 'confirmed']]);
        $p = $o->payments()->firstOrFail();
        $this->postJson('/api/v1/library/orders/'.$o->id.'/payments/'.$p->id.'/review', ['decision' => 'approve'])->assertOk()->assertJsonPath('data.payment_status', 'paid');
        $line = $o->lines()->firstOrFail();
        $this->postJson('/api/v1/library/orders/'.$o->id.'/unavailable', ['line_id' => $line->id, 'item_id' => $this->coffee->id, 'qty' => 2])
            ->assertOk()->assertJsonPath('data.status', 'confirmed')->assertJsonPath('data.replacements.0.status', 'pending'); // partial fulfilment: the rest goes ahead

        // learner answers in the app: dearer coffee → 20 more to pay
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->learner, 'learner_api');
        $r = OrderReplacement::where('order_id', $o->id)->firstOrFail();
        $this->getJson('/api/v1/learner/orders/'.$no)->assertJsonPath('data.replacements_pending.0.choices', ['accept', 'remove', 'other']);
        $ans = $this->postJson('/api/v1/learner/orders/'.$no.'/replacements', ['choices' => [$r->id => 'accept']])->assertOk();
        $this->assertEquals(50, $ans->json('data.total'));
        $this->assertSame('partially_paid', $ans->json('data.payment_status'));
        $this->assertEquals(20, $ans->json('data.due_amount'));

        // other learners cannot see it
        $other = Learner::withoutGlobalScopes()->whereNull('deleted_at')->where('id', '<>', $this->learner->id)->first();
        if ($other) {
            $this->app['auth']->forgetGuards();
            $this->actingAs($other, 'learner_api');
            $this->getJson('/api/v1/learner/orders/'.$no)->assertJson(['status' => false, 'message' => 'Order not found.']);
        }
    }
}
