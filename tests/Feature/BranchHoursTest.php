<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Hour;
use App\Models\Library;
use App\Models\Plan;
use App\Models\PlanType;
use App\Services\BranchHoursService;
use App\Services\ShiftTimingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Library open – close time (hour.open_time + hour.hour): reading it from the forms, shifts
 * fitting inside it, advanced shifts booked inside it, changing it, and filling it for older
 * branches. On a new library + branch inside a rolled back transaction.
 */
class BranchHoursTest extends TestCase
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
            'library_name' => 'Hours Test Library', 'email' => 'hours.' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'), 'email_verified_at' => now(),
            'is_paid' => 1, 'status' => 0, 'library_type' => 3, 'library_no' => 'LBH' . rand(10000, 99999),
        ]);
        $this->branch = Branch::withoutGlobalScopes()->create(['library_id' => $this->lib->id, 'name' => 'Main', 'status' => 1]);
        Hour::withoutGlobalScopes()->create(['library_id' => $this->lib->id, 'branch_id' => $this->branch->id, 'hour' => 16, 'open_time' => '06:00', 'seats' => 50]);
        Plan::withoutGlobalScopes()->create(['library_id' => $this->lib->id, 'name' => '1 MONTH', 'plan_id' => 1, 'type' => 'MONTH']);
        $this->lib->update(['current_branch' => $this->branch->id]);
        $this->actingAs($this->lib->fresh(), 'library');
        librarySetup(true);
        BranchHoursService::forget();
    }

    private function setHours(int $hour, ?string $open): void
    {
        Hour::withoutGlobalScopes()->where('branch_id', $this->branch->id)->update(['hour' => $hour, 'open_time' => $open]);
        BranchHoursService::forget();
    }

    private function saveShifts(array $rows)
    {
        return $this->postJson(route('master.configuration.store'), ['plan_types' => $rows]);
    }

    private function fixed(string $dt, string $start, string $end, $hours, ?int $id = null, ?string $name = null): array
    {
        return array_filter(['plan_type_id' => $id, 'day_type_id' => $dt, 'booking_mode' => 'fixed', 'custom_plan_type' => $name,
            'start_time' => $start, 'end_time' => $end, 'slot_hours' => $hours, 'price' => 500], fn ($v) => $v !== null);
    }

    private function advanced(string $mode, $hours, string $name): array
    {
        return ['day_type_id' => '0', 'booking_mode' => $mode, 'custom_plan_type' => $name, 'start_time' => '', 'end_time' => '', 'slot_hours' => $hours, 'price' => 700];
    }

    private function template(string $mode): PlanType
    {
        return PlanType::withoutGlobalScopes()->where('branch_id', $this->branch->id)->whereNull('parent_id')->where('booking_mode', $mode)->firstOrFail();
    }

    public function test_form_input_gives_the_hours(): void
    {
        $this->assertSame(['hour' => 16, 'open_time' => '06:00'], BranchHoursService::fromInput(['open_time' => '06:00', 'close_time' => '22:00'], false));
        // past midnight
        $this->assertSame(['hour' => 16, 'open_time' => '18:00'], BranchHoursService::fromInput(['open_time' => '18:00', 'close_time' => '10:00'], false));
        $this->assertSame(['hour' => 24, 'open_time' => null], BranchHoursService::fromInput(['all_day' => '1'], true));

        $this->assertSame('open_time', BranchHoursService::fromInput(['all_day' => '1'], false)['field']);     // plan without 24 hours
        $this->assertSame('open_time', BranchHoursService::fromInput(['close_time' => '22:00'], false)['field']);
        $this->assertSame('close_time', BranchHoursService::fromInput(['open_time' => '06:00'], false)['field']);
        $this->assertArrayHasKey('error', BranchHoursService::fromInput(['open_time' => '06:00', 'close_time' => '06:00'], true));
        $this->assertArrayHasKey('error', BranchHoursService::fromInput(['open_time' => '06:00', 'close_time' => '21:30'], false));   // not whole hours
        $this->assertArrayHasKey('error', BranchHoursService::fromInput(['open_time' => '09:00', 'close_time' => '17:00'], false));   // under 10 hrs
    }

    public function test_basic_shifts_fit_inside_the_library_hours(): void
    {
        // open 6 AM – 10 PM
        $this->saveShifts([$this->fixed('2', '05:00', '13:00', 8)])->assertStatus(400)->assertJsonFragment(['status' => false]);
        $this->saveShifts([$this->fixed('3', '14:00', '23:00', 9)])->assertStatus(400);
        $this->saveShifts([$this->fixed('1', '06:00', '22:00', 16), $this->fixed('2', '06:00', '14:00', 8)])->assertOk();

        // open 6 PM – 10 AM: a night shift fits, a day shift doesn't
        PlanType::withoutGlobalScopes()->where('branch_id', $this->branch->id)->forceDelete();
        $this->setHours(16, '18:00');
        $this->saveShifts([$this->fixed('0', '22:00', '06:00', 8, null, 'Night')])->assertOk();
        $this->saveShifts([$this->fixed('0', '12:00', '16:00', 4, null, 'Noon')])->assertStatus(400);
    }

    public function test_advanced_shifts_are_booked_inside_the_library_hours(): void
    {
        // open 6 AM – 10 PM
        $this->saveShifts([$this->advanced('duration', 4, 'Four hours'), $this->advanced('free', 16, 'Direct')])->assertOk();
        $duration = $this->template('duration');
        $free = $this->template('free');
        $timing = app(ShiftTimingService::class);

        $this->assertSame(['06:00', '22:00'], $duration->bookingTimes());
        $this->assertSame('6 AM – 10 PM', $duration->timeLabel());

        $this->assertFalse($timing->validateTiming($duration, '06:00')['error']);
        $this->assertFalse($timing->validateTiming($duration, '18:00')['error']);
        $late = $timing->validateTiming($duration, '19:00');                 // would end at 11 PM
        $this->assertTrue($late['error']);
        $this->assertStringContainsString('6 PM', $late['message']);            // the latest start
        $this->assertTrue($timing->validateTiming($duration, '05:00')['error']);
        $this->assertFalse($timing->validateTiming($free, '17:30', '21:00')['error']);
        $this->assertTrue($timing->validateTiming($free, '21:00', '23:00')['error']);

        // free time on a seat is only offered inside the library hours
        $gaps = $timing->freeGaps($duration, [['start' => '10:00', 'end' => '14:00']]);
        $this->assertSame([['start' => '06:00', 'end' => '10:00', 'minutes' => 240], ['start' => '14:00', 'end' => '22:00', 'minutes' => 480]], $gaps);

        // the hours follow the library: open 24 hours → any time
        $this->setHours(24, null);
        $this->assertFalse($timing->validateTiming($duration, '23:00')['error']);
        $this->assertSame('Any time', $duration->fresh()->timeLabel());
    }

    public function test_changing_the_hours_keeps_every_shift_inside(): void
    {
        $this->saveShifts([$this->fixed('2', '06:00', '14:00', 8), $this->advanced('flexible', 14, 'Long day')])->assertOk();
        $url = route('branch.hours.update', $this->branch->id);

        // First Half (6 AM – 2 PM) would be outside 7 AM – 9 PM
        $this->postJson($url, ['open_time' => '07:00', 'close_time' => '21:00'])->assertStatus(422)->assertJsonValidationErrors('open_time');
        // the 14 hr flexible shift needs at least 14 hrs
        $this->postJson($url, ['open_time' => '06:00', 'close_time' => '18:00'])->assertStatus(422);
        $this->postJson($url, ['open_time' => '05:00', 'close_time' => '21:00'])->assertOk()->assertJson(['success' => true]);
        $this->assertSame(['05:00', '21:00'], BranchHoursService::times($this->branch->id));
        $this->assertSame(16, (int) Hour::withoutGlobalScopes()->where('branch_id', $this->branch->id)->value('hour'));

        // a branch of another library can't be changed
        $other = Branch::withoutGlobalScopes()->where('library_id', '!=', $this->lib->id)->value('id');
        if ($other) {
            $this->postJson(route('branch.hours.update', $other), ['open_time' => '05:00', 'close_time' => '21:00'])->assertNotFound();
        }
    }

    public function test_a_booked_timing_keeps_the_hours_from_shrinking_around_it(): void
    {
        $this->saveShifts([$this->advanced('duration', 4, 'Four hours')])->assertOk();
        $child = app(ShiftTimingService::class)->resolveChild($this->template('duration'), '18:00', null);
        $planId = Plan::withoutGlobalScopes()->where('library_id', $this->lib->id)->value('id');
        $learnerId = DB::table('learners')->insertGetId(['library_id' => $this->lib->id, 'branch_id' => $this->branch->id, 'name' => 'L', 'password' => 'x', 'status' => 1]);
        DB::table('learner_detail')->insert(['library_id' => $this->lib->id, 'branch_id' => $this->branch->id, 'learner_id' => $learnerId, 'plan_id' => $planId, 'plan_type_id' => $child->id, 'plan_price_id' => 700, 'status' => 1]);

        // 6 PM – 10 PM is booked: closing at 8 PM is refused
        $message = app(BranchHoursService::class)->conflict($this->branch->id, 14, '06:00');
        $this->assertNotNull($message);
        $this->assertStringContainsString('6 PM – 10 PM', $message);
        $this->assertNull(app(BranchHoursService::class)->conflict($this->branch->id, 14, '08:00'));
    }

    public function test_a_booked_slot_on_the_seat_is_refused(): void
    {
        $this->saveShifts([$this->advanced('duration', 4, 'Four hours')])->assertOk();
        $timing = app(ShiftTimingService::class);
        $child = $timing->resolveChild($this->template('duration'), '16:00', null);
        $planId = Plan::withoutGlobalScopes()->where('library_id', $this->lib->id)->value('id');
        $learnerId = DB::table('learners')->insertGetId(['library_id' => $this->lib->id, 'branch_id' => $this->branch->id, 'name' => 'L', 'password' => 'x', 'status' => 1]);
        DB::table('learner_detail')->insert(['library_id' => $this->lib->id, 'branch_id' => $this->branch->id, 'learner_id' => $learnerId, 'plan_id' => $planId,
            'plan_type_id' => $child->id, 'plan_price_id' => 700, 'status' => 1, 'seat_no' => 1,
            'plan_start_date' => today()->format('Y-m-d'), 'plan_end_date' => today()->addMonth()->format('Y-m-d')]);
        [$from, $to] = [today(), today()->addMonth()];

        // 4 PM – 8 PM again, or 6 PM – 10 PM over it: refused with the booked time
        $clash = $timing->clashOnSeat($this->branch->id, 1, $from, $to, '16:00', 240);
        $this->assertNotNull($clash);
        $this->assertSame('4 PM – 8 PM is already booked on this seat. Choose a different time slot.', ShiftTimingService::clashMessage($clash));
        $this->assertNotNull($timing->clashOnSeat($this->branch->id, 1, $from, $to, '18:00', 240));
        // right after it, another seat, or later dates: free
        $this->assertNull($timing->clashOnSeat($this->branch->id, 1, $from, $to, '12:00', 240));
        $this->assertNull($timing->clashOnSeat($this->branch->id, 2, $from, $to, '16:00', 240));
        $this->assertNull($timing->clashOnSeat($this->branch->id, 1, today()->addMonths(2), today()->addMonths(3), '16:00', 240));
        // the learner's own booking doesn't block them (upgrade / change plan)
        $this->assertNull($timing->clashOnSeat($this->branch->id, 1, $from, $to, '16:00', 240, $learnerId));
    }

    public function test_open_time_is_guessed_from_the_shifts(): void
    {
        $service = app(BranchHoursService::class);
        $this->assertNull($service->guessOpenTime($this->branch->id, 16));                    // no shifts yet

        PlanType::withoutGlobalScopes()->insert([
            ['library_id' => $this->lib->id, 'branch_id' => $this->branch->id, 'name' => 'Night', 'day_type_id' => 0, 'booking_mode' => 'fixed', 'start_time' => '20:00', 'end_time' => '04:00', 'slot_hours' => 8],
            ['library_id' => $this->lib->id, 'branch_id' => $this->branch->id, 'name' => 'Early', 'day_type_id' => 0, 'booking_mode' => 'fixed', 'start_time' => '04:00', 'end_time' => '10:00', 'slot_hours' => 6],
        ]);
        $this->assertSame('20:00', $service->guessOpenTime($this->branch->id, 16));           // 8 PM – 12 PM
        $this->assertNull($service->guessOpenTime($this->branch->id, 12));                    // they need 14 hrs
        $this->assertNull($service->guessOpenTime($this->branch->id, 24));                    // 24 hours: no open time
    }

    public function test_operating_hours_and_edit_pages_show_the_times(): void
    {
        // the panel pages need the layout data (LoadMenus); only license, logging, e-mail and CSRF checks are off
        $this->withMiddleware();
        $this->withoutMiddleware([
            \App\Http\Middleware\LicenseCheck::class,
            \App\Http\Middleware\LoggerMiddleware::class,
            \App\Http\Middleware\EnsureLibraryEmailIsVerified::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);
        $this->get(route('hour.create', $this->branch->id))->assertOk()
            ->assertSee('name="open_time"', false)->assertSee('value="06:00"', false)
            ->assertSee('value="22:00"', false);
        $this->get(route('branch.edit', $this->branch->id))->assertOk()->assertSee('Library timing');
        $this->get(route('library.configration'))->assertOk()
            ->assertSee('6 AM – 10 PM')->assertSee('How shifts work')->assertSee('Direct booking');
    }
}
