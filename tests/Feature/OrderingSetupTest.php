<?php

namespace Tests\Feature;

use App\Models\Library;
use App\Models\Ordering\OrderCategory;
use App\Models\Ordering\OrderItem;
use App\Models\Ordering\OrderSetting;
use App\Models\Ordering\OrderTable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** Ordering & Services, step 1: library set-up — menu, items, tables, settings (docs/ORDERING.md). */
class OrderingSetupTest extends TestCase
{
    use DatabaseTransactions;

    private Library $library;

    private int $branchId;

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

        $library = Library::query()->whereNull('deleted_at')->whereNotNull('current_branch')->orderByDesc('id')->first()
            ?? $this->markTestSkipped('no library with a branch');
        $library->forceFill(['library_type' => 3])->save(); // Ordering is a Pro plan feature
        $this->library = $library;
        $this->branchId = (int) $library->current_branch;

        // start clean for this branch
        foreach ([OrderItem::class, OrderCategory::class, OrderTable::class, OrderSetting::class] as $m) {
            $m::withoutGlobalScopes()->where('branch_id', $this->branchId)->forceDelete();
        }
        $this->actingAs($library, 'library');
    }

    public function test_first_visit_seeds_starter_categories_once(): void
    {
        $this->get('/library/ordering/items')->assertOk()->assertSee('Menu &amp; Items', false)->assertSee('Beverages')->assertSee('Ordering is off for learners');

        $this->assertSame(count(OrderCategory::DEFAULTS), OrderCategory::withoutGlobalScopes()->where('branch_id', $this->branchId)->count());
        $this->assertFalse(OrderSetting::withoutGlobalScopes()->where('branch_id', $this->branchId)->value('is_enabled'));

        // a second visit does not seed again, even after the owner deleted some
        OrderCategory::withoutGlobalScopes()->where('branch_id', $this->branchId)->where('name', 'Medicines')->delete();
        $this->get('/library/ordering/items')->assertOk();
        $this->assertSame(count(OrderCategory::DEFAULTS) - 1, OrderCategory::withoutGlobalScopes()->where('branch_id', $this->branchId)->whereNull('deleted_at')->count());
    }

    public function test_owner_manages_categories_and_items(): void
    {
        $this->get('/library/ordering/items')->assertOk();

        $this->post('/library/ordering/categories', ['name' => 'Juices', 'icon' => 'coffee', 'is_enabled' => 1, 'allow_online' => 1])
            ->assertRedirect()->assertSessionHas('success');
        $cat = OrderCategory::withoutGlobalScopes()->where('branch_id', $this->branchId)->where('name', 'Juices')->firstOrFail();
        $this->assertSame($this->library->id, (int) $cat->library_id);
        $this->assertSame(['online'], $cat->allowedPayments());

        // duplicate name in the same branch is refused; no payment method at all falls back to the counter
        $this->post('/library/ordering/categories', ['name' => 'Juices'])->assertSessionHasErrors('name');
        $this->put('/library/ordering/categories/'.$cat->id, ['name' => 'Fresh Juices', 'icon' => 'droplet'])->assertSessionHasNoErrors();
        $cat->refresh();
        $this->assertSame('Fresh Juices', $cat->name);
        $this->assertFalse($cat->is_enabled);
        $this->assertSame(['offline'], $cat->allowedPayments());

        $item = ['order_category_id' => $cat->id, 'name' => 'Mango Shake', 'price' => 70, 'is_enabled' => 1, 'is_available' => 1,
            'modes' => ['seat', 'table', 'counter']];
        $this->post('/library/ordering/items', $item)->assertRedirect()->assertSessionHasNoErrors();
        $it = OrderItem::withoutGlobalScopes()->where('branch_id', $this->branchId)->where('name', 'Mango Shake')->firstOrFail();
        $this->assertNull($it->modes); // every mode ticked = no limit
        $this->assertTrue($it->allowsMode('table'));

        $this->put('/library/ordering/items/'.$it->id, ['modes' => ['counter'], 'price' => 75.5] + $item)->assertSessionHasNoErrors();
        $it->refresh();
        $this->assertSame(['counter'], $it->modes);
        $this->assertFalse($it->allowsMode('seat'));
        $this->assertEquals(75.5, (float) $it->price);

        // one-tap availability (AJAX)
        $this->postJson('/library/ordering/items/'.$it->id.'/availability', ['is_available' => 0])->assertOk()->assertJson(['is_available' => false]);
        $this->assertFalse($it->fresh()->is_available);

        // bad values
        $this->post('/library/ordering/items', ['price' => -5] + $item)->assertSessionHasErrors('price');
        $this->post('/library/ordering/items', ['modes' => ['rocket']] + $item)->assertSessionHasErrors('modes.0');

        $this->get('/library/ordering/items')->assertOk()->assertSee('Mango Shake')->assertSee('₹75.50');

        // deleting a category soft-deletes its items (past orders keep their snapshot)
        $this->delete('/library/ordering/categories/'.$cat->id)->assertRedirect();
        $this->assertSoftDeleted('order_categories', ['id' => $cat->id]);
        $this->assertSoftDeleted('order_items', ['id' => $it->id]);
    }

    public function test_items_of_another_branch_cannot_be_touched(): void
    {
        $this->get('/library/ordering/items')->assertOk();
        $foreignCat = OrderCategory::withoutGlobalScopes()->create([
            'library_id' => $this->library->id + 999999, 'branch_id' => $this->branchId + 999999, 'name' => 'Other library',
        ]);
        $foreign = OrderItem::withoutGlobalScopes()->create([
            'library_id' => $foreignCat->library_id, 'branch_id' => $foreignCat->branch_id, 'order_category_id' => $foreignCat->id,
            'name' => 'Not yours', 'price' => 10,
        ]);

        $this->put('/library/ordering/categories/'.$foreignCat->id, ['name' => 'x'])->assertNotFound();
        $this->delete('/library/ordering/items/'.$foreign->id)->assertNotFound();
        $this->post('/library/ordering/items', ['order_category_id' => $foreignCat->id, 'name' => 'Sneaky', 'price' => 1])
            ->assertSessionHasErrors('order_category_id');
    }

    public function test_settings_and_tables(): void
    {
        $this->get('/library/ordering/settings')->assertOk()->assertSee('Order modes')->assertSee('Cafeteria tables');

        $form = [
            'form_kind' => 'settings', 'is_enabled' => 1, 'mode_seat' => 1, 'mode_counter' => 1, 'pay_offline' => 1, 'pay_later' => 1,
            'opens_at' => '08:00', 'closes_at' => '22:00', 'prep_minutes' => 10, 'delivery_charge' => 5, 'pay_later_scope' => 'all',
            'pay_later_limit' => 500, 'max_open_orders' => 2, 'reminder_minutes' => 2, 'escalation_minutes' => 5,
            'replacement_enabled' => 1, 'replacement_timeout_minutes' => 10, 'replacement_timeout_action' => 'remove',
            'learner_cancel_until' => 'new', 'upload_types' => 'PDF, jpg ,png', 'upload_max_mb' => 10,
        ];
        $this->post('/library/ordering/settings', $form)->assertRedirect()->assertSessionHasNoErrors();
        $s = OrderSetting::withoutGlobalScopes()->where('branch_id', $this->branchId)->firstOrFail();
        $this->assertTrue($s->is_enabled);
        $this->assertSame(['seat', 'counter'], $s->enabledModes());
        $this->assertSame(['offline', 'pay_later'], $s->enabledPayments());
        $this->assertSame(['pdf', 'jpg', 'png'], $s->uploadExtensions());
        $this->assertTrue($s->isOpenAt(today()->setTime(9, 0)));
        $this->assertFalse($s->isOpenAt(today()->setTime(23, 0)));

        // at least one mode and one payment method; escalation not before the reminder
        $this->post('/library/ordering/settings', ['mode_seat' => 0, 'mode_counter' => 0] + $form)->assertSessionHasErrors('mode_seat');
        $this->post('/library/ordering/settings', ['pay_offline' => 0, 'pay_later' => 0] + $form)->assertSessionHasErrors('pay_online');
        $this->post('/library/ordering/settings', ['escalation_minutes' => 1] + $form)->assertSessionHasErrors('escalation_minutes');

        // tables: ranges + lists, no duplicates
        $this->post('/library/ordering/tables', ['labels' => '1-3, Window, 2'])->assertRedirect()->assertSessionHas('success');
        $this->assertSame(['1', '2', '3', 'Window'], OrderTable::withoutGlobalScopes()->where('branch_id', $this->branchId)->orderBy('sort_order')->pluck('label')->all());
        $this->post('/library/ordering/tables', ['labels' => 'window'])->assertSessionHas('success', 'Those tables are already there.');
    }

    public function test_hours_past_midnight(): void
    {
        $s = new OrderSetting(['opens_at' => '20:00:00', 'closes_at' => '02:00:00']);
        $this->assertTrue($s->isOpenAt(today()->setTime(23, 30)));
        $this->assertTrue($s->isOpenAt(today()->setTime(1, 0)));
        $this->assertFalse($s->isOpenAt(today()->setTime(12, 0)));
        $this->assertTrue((new OrderSetting())->isOpenAt(now()));
    }
}
