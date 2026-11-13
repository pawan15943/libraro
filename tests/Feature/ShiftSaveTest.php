<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Hour;
use App\Models\Library;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Shifts page save (master.configuration.store): add, edit, remove and the guards around them,
 * on a new library + branch inside a rolled back transaction.
 */
class ShiftSaveTest extends TestCase
{
    use DatabaseTransactions;

    private Library $lib;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir() . '/libraro-test-storage');
        config(['mail.default' => 'array']);
        $this->withoutMiddleware();

        $this->lib = Library::create([
            'library_name' => 'Shift Test Library', 'email' => 'shift.' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'), 'email_verified_at' => now(),
            'is_paid' => 1, 'status' => 0, 'library_type' => 3, 'library_no' => 'LBT' . rand(10000, 99999),
        ]);
        $this->branch = Branch::withoutGlobalScopes()->create(['library_id' => $this->lib->id, 'name' => 'Main', 'status' => 1]);
        Hour::withoutGlobalScopes()->create(['library_id' => $this->lib->id, 'branch_id' => $this->branch->id, 'hour' => 24, 'seats' => 50]);
        \App\Services\BranchHoursService::forget();
        Plan::withoutGlobalScopes()->create(['library_id' => $this->lib->id, 'name' => '1 MONTH', 'plan_id' => 1, 'type' => 'MONTH']);
        $this->lib->update(['current_branch' => $this->branch->id]);
        $this->actingAs($this->lib->fresh(), 'library');
        librarySetup(true);
    }

    private function row(string $dt, string $start, string $end, $hours, $price, ?int $id = null, ?string $name = null, string $mode = 'fixed'): array
    {
        return array_filter([
            'plan_type_id' => $id, 'day_type_id' => $dt, 'booking_mode' => $mode, 'custom_plan_type' => $name,
            'start_time' => $start, 'end_time' => $end, 'slot_hours' => $hours, 'price' => $price,
        ], fn ($v) => $v !== null);
    }

    private function save(array $rows)
    {
        return $this->postJson(route('master.configuration.store'), ['plan_types' => $rows]);
    }

    private function shifts()
    {
        return PlanType::withoutGlobalScopes()->where('branch_id', $this->branch->id)->whereNull('parent_id')->orderBy('id')->get();
    }

    private function priceOf(int $planTypeId)
    {
        return (float) PlanPrice::withoutGlobalScopes()->where('plan_type_id', $planTypeId)->value('price');
    }

    public function test_first_save_finishes_setup_and_opens_the_welcome(): void
    {
        $res = $this->save([$this->row('1', '08:00', '20:00', 12, 800)]);
        $res->assertOk()->assertJson(['status' => true]);
        $this->assertStringContainsString('setup=completed', (string) $res->json('redirect'));
        $this->assertCount(1, $this->shifts());
        $this->assertSame(800.0, $this->priceOf($this->shifts()[0]->id));
    }

    public function test_adding_a_shift_keeps_the_ones_already_saved(): void
    {
        $this->save([$this->row('1', '08:00', '20:00', 12, 800)])->assertOk();
        $first = $this->shifts()[0];

        $res = $this->save([
            $this->row('1', '08:00', '20:00', 12, 800, $first->id),
            $this->row('2', '08:00', '13:00', 5, 500),
        ]);
        $res->assertOk()->assertJson(['status' => true]);
        $this->assertNull($res->json('redirect'));   // later saves stay on the page

        $shifts = $this->shifts();
        $this->assertCount(2, $shifts);
        $this->assertSame($first->id, $shifts[0]->id);
        $this->assertSame(['Full Day', '08:00', '20:00', 800.0], [$shifts[0]->name, substr($shifts[0]->start_time, 0, 5), substr($shifts[0]->end_time, 0, 5), $this->priceOf($shifts[0]->id)]);
        $this->assertSame(['First Half', 500.0], [$shifts[1]->name, $this->priceOf($shifts[1]->id)]);
    }

    public function test_editing_and_removing(): void
    {
        $this->save([$this->row('1', '08:00', '20:00', 12, 800), $this->row('2', '08:00', '13:00', 5, 500)])->assertOk();
        [$a, $b] = [$this->shifts()[0], $this->shifts()[1]];

        // fee and timing of a shift nobody is on can change
        $this->save([$this->row('1', '08:00', '20:00', 12, 900, $a->id), $this->row('2', '07:00', '13:00', 6, 550, $b->id)])->assertOk();
        $this->assertSame(900.0, $this->priceOf($a->id));
        $this->assertSame(['07:00', 550.0], [substr(PlanType::withoutGlobalScopes()->find($b->id)->start_time, 0, 5), $this->priceOf($b->id)]);

        // a shift left out of the save is removed, the other stays
        $this->save([$this->row('1', '08:00', '20:00', 12, 900, $a->id)])->assertOk();
        $this->assertSame([$a->id], $this->shifts()->pluck('id')->all());
        $this->assertSame(0, PlanPrice::withoutGlobalScopes()->where('plan_type_id', $b->id)->count());
    }

    public function test_custom_shift_needs_a_name_and_keeps_it(): void
    {
        $this->save([$this->row('0', '16:00', '20:00', 4, 450)])->assertStatus(422);
        $this->save([$this->row('0', '16:00', '20:00', 4, 450, null, 'Evening')])->assertOk();
        $this->assertSame('Evening', $this->shifts()[0]->name);
    }

    public function test_bad_rows_are_refused_and_nothing_changes(): void
    {
        $this->save([$this->row('1', '08:00', '20:00', 12, 800)])->assertOk();
        $first = $this->shifts()[0];

        // the same shift type twice
        $this->save([$this->row('1', '08:00', '20:00', 12, 800, $first->id), $this->row('1', '08:00', '20:00', 12, 800)])->assertStatus(400);
        // hours that don't match the time
        $this->save([$this->row('1', '08:00', '20:00', 12, 800, $first->id), $this->row('2', '08:00', '13:00', 9, 500)])->assertStatus(400);
        // a missing fee
        $this->save([['plan_type_id' => $first->id, 'day_type_id' => '1', 'start_time' => '08:00', 'end_time' => '20:00', 'slot_hours' => 12]])->assertStatus(422);

        $this->assertSame([$first->id], $this->shifts()->pluck('id')->all());
        $this->assertSame(800.0, $this->priceOf($first->id));
    }

    public function test_a_shift_with_a_learner_cant_be_removed_or_retimed(): void
    {
        $this->save([$this->row('1', '08:00', '20:00', 12, 800), $this->row('2', '08:00', '13:00', 5, 500)])->assertOk();
        [$a, $b] = [$this->shifts()[0], $this->shifts()[1]];
        $planId = Plan::withoutGlobalScopes()->where('library_id', $this->lib->id)->value('id');
        $learnerId = DB::table('learners')->insertGetId(['library_id' => $this->lib->id, 'branch_id' => $this->branch->id, 'name' => 'L', 'password' => 'x', 'status' => 1]);
        DB::table('learner_detail')->insert(['library_id' => $this->lib->id, 'branch_id' => $this->branch->id, 'learner_id' => $learnerId, 'plan_id' => $planId, 'plan_type_id' => $b->id, 'plan_price_id' => 500, 'status' => 1]);

        // removing it is refused, and so is changing its time
        $this->save([$this->row('1', '08:00', '20:00', 12, 800, $a->id)])->assertStatus(400);
        $this->save([$this->row('1', '08:00', '20:00', 12, 800, $a->id), $this->row('2', '07:00', '13:00', 6, 500, $b->id)])->assertStatus(400);
        // its fee can still change
        $this->save([$this->row('1', '08:00', '20:00', 12, 800, $a->id), $this->row('2', '08:00', '13:00', 5, 520, $b->id)])->assertOk();
        $this->assertSame(520.0, $this->priceOf($b->id));
        $this->assertCount(2, $this->shifts());
    }

    public function test_an_advanced_shift_without_a_window_can_be_booked_any_time(): void
    {
        // fixed hours (duration) with no "available from / till", 24 hours a day
        $this->save([$this->row('0', '', '', 24, 700, null, 'Day pass', 'duration')])->assertOk()->assertJson(['status' => true]);
        $t = $this->shifts()[0];
        $this->assertSame(['00:00', '00:00', 24.0], [substr($t->start_time, 0, 5), substr($t->end_time, 0, 5), (float) $t->slot_hours]);
        $this->assertTrue($t->isAnyTime());

        // it ties up no hours of the day
        $this->assertSame(0.0, (float) getBranchShiftTiming($this->branch->id));

        // a booking can start at any hour, also across midnight
        $timing = app(\App\Services\ShiftTimingService::class)->validateTiming($t, '22:00');
        $this->assertFalse($timing['error']);
    }

    public function test_free_and_flexible_without_a_window(): void
    {
        $this->save([
            $this->row('0', '', '', 24, 900, null, 'Own time', 'free'),
            $this->row('0', '', '', 6, 500, null, 'Six hours', 'flexible'),
        ])->assertOk();
        $this->assertCount(2, $this->shifts());
        $this->assertTrue($this->shifts()->every->isAnyTime());
    }

    public function test_advanced_shifts_need_the_library_hours_and_fit_them(): void
    {
        // 12 hours a day, open time not set: an advanced shift can't be added yet
        Hour::withoutGlobalScopes()->where('branch_id', $this->branch->id)->update(['hour' => 12, 'open_time' => null]);
        $this->save([$this->row('0', '', '', 6, 600, null, 'Six', 'flexible')])->assertStatus(400)->assertJsonFragment(['status' => false]);

        // open 8 AM – 8 PM: longer than the library is open is refused, inside it is fine
        Hour::withoutGlobalScopes()->where('branch_id', $this->branch->id)->update(['open_time' => '08:00']);
        \App\Services\BranchHoursService::forget();
        $this->save([$this->row('0', '', '', 20, 600, null, 'Long', 'flexible')])->assertStatus(400);
        $this->save([$this->row('0', '', '', 6, 600, null, 'Six', 'flexible'), $this->row('0', '', '', 12, 900, null, 'Own', 'free')])->assertOk();
        // direct booking is open as long as the library is
        $this->assertSame(12.0, (float) $this->shifts()->firstWhere('booking_mode', 'free')->slot_hours);
    }

    public function test_window_both_or_neither_and_at_most_24_hours(): void
    {
        $this->save([$this->row('0', '09:00', '', 4, 400, null, 'Half window', 'duration')])->assertStatus(422)->assertJsonValidationErrors(['plan_types.0.end_time']);
        $this->save([$this->row('0', '', '', 25, 400, null, 'Too long', 'duration')])->assertStatus(422)->assertJsonValidationErrors(['plan_types.0.slot_hours']);
        // a window still limits the hours
        $this->save([$this->row('0', '09:00', '13:00', 5, 400, null, 'Window', 'duration')])->assertStatus(400);
        // basic shifts still need their time
        $this->save([$this->row('1', '', '', 12, 800)])->assertStatus(422);
    }
}
