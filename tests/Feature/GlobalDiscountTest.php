<?php

namespace Tests\Feature;

use App\Models\GlobalDiscount;
use App\Models\Learner;
use App\Models\LearnerTransaction;
use App\Models\Library;
use App\Services\GlobalDiscountService;
use App\Services\PlanService;
use App\Services\TransactionActivityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Global Discount Master: one library offer every booking form applies by itself (docs/GLOBAL_DISCOUNT.md). */
class GlobalDiscountTest extends TestCase
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

    private function offer(int $libraryId, array $over = []): GlobalDiscount
    {
        GlobalDiscount::withoutGlobalScopes()->where('library_id', $libraryId)->update(['deleted_at' => now()]);

        return GlobalDiscount::withoutGlobalScopes()->create($over + [
            'library_id' => $libraryId, 'name' => 'Diwali Offer', 'discount_type' => 'percentage', 'discount_value' => 10,
            'start_date' => today()->subDay(), 'end_date' => today()->addDays(5), 'is_enabled' => true,
        ]);
    }

    private function planPrice(): object
    {
        $row = DB::table('plan_prices')->whereNull('deleted_at')->where('price', '>', 0)->whereNotNull('branch_id')->orderByDesc('id')->first();
        if (! $row) {
            $this->markTestSkipped('no plan price in this database');
        }

        return $row;
    }

    public function test_offer_rules(): void
    {
        $svc = new GlobalDiscountService();
        $lib = 900000001;
        $this->offer($lib);

        // percent of the plan fee only, not the locker
        $r = $svc->apply(1000, 200, null, 0, null, 'RENEW', $lib);
        $this->assertSame('amount', $r['discount_type']);
        $this->assertEquals(100, $r['discount_value']);
        $this->assertEquals(100, $r['global_discount_amount']);
        $this->assertNotNull($r['global_discount_id']);

        // a bigger manual discount wins, never both
        $r = $svc->apply(1000, 200, 'percentage', 15, null, 'NEW', $lib); // 180 > 100
        $this->assertSame('percentage', $r['discount_type']);
        $this->assertNull($r['global_discount_id']);
        $r = (new GlobalDiscountService())->apply(1000, 0, 'amount', 50, null, 'UPGRADE', $lib); // 50 < 100
        $this->assertEquals(100, $r['discount_value']);

        // editing a booking never adds the offer
        $r = (new GlobalDiscountService())->apply(1000, 0, null, 0, null, 'EDIT', $lib);
        $this->assertNull($r['global_discount_id']);
        $this->assertEquals(0, $r['discount_value']);

        // a fixed amount cannot take the fee below zero
        $this->offer($lib, ['discount_type' => 'amount', 'discount_value' => 1500]);
        $this->assertEquals(1000, (new GlobalDiscountService())->apply(1000, 100, null, 0, null, 'NEW', $lib)['global_discount_amount']);

        // starts and stops by its dates, and can be turned off
        $this->offer($lib, ['start_date' => today()->addDay()]);
        $this->assertNull((new GlobalDiscountService())->active($lib));
        $this->offer($lib, ['end_date' => today()->subDay(), 'start_date' => today()->subDays(3)]);
        $this->assertNull((new GlobalDiscountService())->active($lib));
        $this->offer($lib, ['is_enabled' => false]);
        $this->assertNull((new GlobalDiscountService())->active($lib));
        $this->offer($lib, ['start_date' => today(), 'end_date' => today()]);
        $this->assertNotNull((new GlobalDiscountService())->active($lib));
    }

    public function test_owner_manages_discounts_from_the_master(): void
    {
        $library = Library::query()->whereNull('deleted_at')->orderByDesc('id')->first() ?? $this->markTestSkipped('no library');
        GlobalDiscount::withoutGlobalScopes()->where('library_id', $library->id)->update(['deleted_at' => now()]);
        $library->forceFill(['library_type' => 3])->save(); // Global Discount / Student Quiz are Pro plan features
        $this->actingAs($library, 'library');

        $this->get('/library/global-discount')->assertOk()->assertSee('Global Discount')->assertSee('No discount yet');

        $form = ['name' => 'Diwali Offer', 'discount_type' => 'percentage', 'discount_value' => 10,
            'start_date' => today()->toDateString(), 'end_date' => today()->addDays(10)->toDateString(), 'is_enabled' => 1];
        $this->post('/library/global-discount', $form)->assertRedirect('/library/global-discount')->assertSessionHas('success');
        $d = GlobalDiscount::withoutGlobalScopes()->where('library_id', $library->id)->whereNull('deleted_at')->firstOrFail();

        // only one can run at a time; bad values are refused
        $this->post('/library/global-discount', ['name' => 'Second'] + $form)->assertSessionHasErrors('start_date');
        $this->post('/library/global-discount', ['discount_value' => 120] + $form)->assertSessionHasErrors('discount_value');
        $this->post('/library/global-discount', ['end_date' => today()->subDay()->toDateString()] + $form)->assertSessionHasErrors('end_date');

        $this->get('/library/global-discount')->assertOk()->assertSee('Running now')->assertSee('10% off');

        $this->put('/library/global-discount/'.$d->id, ['discount_type' => 'amount', 'discount_value' => 250] + $form)->assertRedirect();
        $this->assertSame('amount', $d->fresh()->discount_type);

        $this->post('/library/global-discount/'.$d->id.'/toggle')->assertRedirect();
        $this->assertFalse($d->fresh()->is_enabled);
        // turned off, a second one with the same dates is fine
        $this->post('/library/global-discount', ['name' => 'Second'] + $form)->assertSessionHasNoErrors();
        // and the first cannot be turned on again while it overlaps
        $this->post('/library/global-discount/'.$d->id.'/toggle')->assertSessionHas('error');

        $this->delete('/library/global-discount/'.$d->id)->assertRedirect('/library/global-discount');
        $this->assertSoftDeleted('global_discounts', ['id' => $d->id]);
    }

    public function test_booking_price_and_public_qr_price_apply_the_offer(): void
    {
        $pp = $this->planPrice();
        $libraryId = (int) DB::table('branches')->where('id', $pp->branch_id)->value('library_id');
        $this->offer($libraryId, ['discount_value' => 20]);
        $price = (float) getPlanPrice($pp->plan_id, $pp->plan_type_id, $pp->branch_id);
        $off = round($price * 0.2, 2);

        $calc = fn ($op) => app(PlanService::class)->calculatePrice((int) $pp->plan_id, (int) $pp->plan_type_id, today()->toDateString(), (int) $pp->branch_id, 100, null, 0, 0, null, $op);

        foreach (['NEW', 'RENEW', 'UPGRADE', 'CHANGE PLAN', 'REACTIVE'] as $op) {
            $r = $calc($op);
            $this->assertEquals($off, (float) $r['discount_amount'], $op);
            $this->assertEquals($off, (float) $r['global_discount_amount'], $op);
            $this->assertEquals($price + 100, (float) $r['original_amount'], $op);
            $this->assertSame('Diwali Offer', $r['offer']['name'], $op);
        }
        $this->assertEquals(0, (float) $calc('EDIT')['discount_amount']);
        $this->assertEquals(0, (float) $calc(null)['discount_amount']); // plain price lookups stay plain

        $this->postJson('/get-plan-price', ['plan_id' => $pp->plan_id, 'plan_type_id' => $pp->plan_type_id, 'branch_id' => $pp->branch_id, 'plan_start_date' => today()->toDateString()])
            ->assertOk()->assertJsonPath('offer.name', 'Diwali Offer')->assertJsonPath('payable', fn ($v) => abs($v - ($price - $off)) < 0.01);
    }

    public function test_transaction_keeps_the_offer_part_and_renewals_carry_only_the_manual_discount(): void
    {
        $learner = Learner::withoutGlobalScopes()->whereNull('deleted_at')->whereNotNull('library_id')->whereNotNull('branch_id')->orderByDesc('id')->first()
            ?? $this->markTestSkipped('no learner');
        $offer = $this->offer($learner->library_id);
        $detailId = DB::table('learner_detail')->where('learner_id', $learner->id)->value('id') ?? 0;

        app(TransactionActivityService::class)->learnerTransactionAddUpdate([
            'planPrice' => 1000, 'locker' => 0, 'discount' => 150, 'paid_amount' => 850, 'payment_mode' => 1, 'is_paid' => 1,
            'learner_id' => $learner->id, 'learner_detail_id' => $detailId, 'library_id' => $learner->library_id, 'branchId' => $learner->branch_id,
            'payment_type' => 'RENEW', 'paid_date' => today()->toDateString(), 'dr_cr' => 'Cr',
            'global_discount_id' => $offer->id, 'global_discount_amount' => 100,
        ]);

        $t = LearnerTransaction::withoutGlobalScopes()->where('learner_id', $learner->id)->latest('id')->firstOrFail();
        $this->assertEquals($offer->id, $t->global_discount_id);
        $this->assertEquals(100, (float) $t->global_discount_amount);
        $this->assertEquals(850, (float) $t->total_amount);
        $this->assertEquals(50, $t->manualDiscount());
    }

    public function test_booking_forms_load_the_offer_only_while_it_runs(): void
    {
        $learner = Learner::withoutGlobalScopes()->where('status', 1)->whereNull('deleted_at')->whereNotNull('library_id')->whereNotNull('branch_id')->orderByDesc('id')->first()
            ?? $this->markTestSkipped('no active learner');
        $library = Library::find($learner->library_id) ?? $this->markTestSkipped('no library');
        $library->forceFill(['library_type' => 3])->save(); // Global Discount / Student Quiz are Pro plan features
        $this->actingAs($library, 'library');
        session(['current_branch' => $learner->branch_id]);
        DB::table('libraries')->where('id', $library->id)->update(['current_branch' => $learner->branch_id]);

        GlobalDiscount::withoutGlobalScopes()->where('library_id', $library->id)->update(['deleted_at' => now()]);
        $this->get('/library/learners/upgrade/'.$learner->id)->assertOk()->assertDontSee('window.LB_OFFER =', false);

        $this->offer($library->id);
        $this->get('/library/learners/upgrade/'.$learner->id)->assertOk()
            ->assertSee('window.LB_OFFER =', false)->assertSee('lb-offer.js', false)->assertSee('#discountType10', false);
    }

    public function test_students_see_the_running_offer_on_their_dashboard(): void
    {
        $learner = Learner::withoutGlobalScopes()->where('status', 1)->whereNull('deleted_at')->whereNotNull('library_id')->orderByDesc('id')->first()
            ?? $this->markTestSkipped('no active learner');
        $this->actingAs($learner, 'learner');

        GlobalDiscount::withoutGlobalScopes()->where('library_id', $learner->library_id)->update(['deleted_at' => now()]);
        $this->get('/learner/home')->assertOk()->assertDontSee('lr-promo is-offer', false);

        $this->offer($learner->library_id, ['name' => 'Exam Season Offer']);
        $this->get('/learner/home')->assertOk()->assertSee('lr-promo is-offer', false)->assertSee('Exam Season Offer')->assertSee('10% off');
    }
}
