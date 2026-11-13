<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Learner;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** Learner portal attendance: month summary (calendar + daily log) and single-day page. */
class LearnerAttendanceTest extends TestCase
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

    private function learner(): Learner
    {
        $l = Learner::withoutGlobalScopes()->where('status', 1)->whereNull('deleted_at')->whereNotNull('library_id')->orderByDesc('id')->first();
        if (! $l) {
            $this->markTestSkipped('no active learner in this database');
        }

        return $l;
    }

    /** Pretend the learner joined 40 days ago (rolled back with the test), so past days are inside their range. */
    private function joinedEarly(Learner $l): void
    {
        \App\Models\LearnerDetail::withoutGlobalScopes()->withTrashed()->where('learner_id', $l->id)->update(['plan_start_date' => Carbon::today()->subDays(40)->toDateString()]);
        \DB::table('learners')->where('id', $l->id)->update(['created_at' => Carbon::today()->subDays(40)]);
    }

    private function punch(Learner $l, Carbon $day, ?string $in, ?string $out): void
    {
        Attendance::withoutGlobalScopes()->where('learner_id', $l->id)->whereDate('date', $day)->delete();
        Attendance::withoutGlobalScopes()->create([
            'learner_id' => $l->id, 'library_id' => $l->library_id, 'branch_id' => $l->branch_id,
            'date' => $day->toDateString(), 'in_time' => $in ? $day->toDateString().' '.$in : null,
            'out_time' => $out ? $day->toDateString().' '.$out : null, 'attendance' => 1,
        ]);
    }

    public function test_month_summary_and_day_pages(): void
    {
        $l = $this->learner();
        $this->joinedEarly($l);
        $this->actingAs($l, 'learner');
        $today = Carbon::today();
        // history must reach back for the days below to count: use the first of this month at least
        $full = $today->copy()->subDay();
        $inOnly = $today->copy()->subDays(2);
        $this->punch($l, $full, '09:00:00', '13:30:00');
        $this->punch($l, $inOnly, '10:00:00', null);

        $r = $this->get('/learner/attendance');
        $r->assertOk()->assertSee('Attendance rate')->assertSee('Daily log')->assertSee($l->learner_no);
        $this->get('/learner/attendance?request_name='.$today->copy()->subMonth()->format('Y-m'))->assertOk();
        $this->get('/learner/attendance?request_name=garbage')->assertOk();

        // day pages
        $this->get('/learner/attendance/'.$full->toDateString())->assertOk()->assertSee('Present')->assertSee('09:00 AM')->assertSee('01:30 PM')->assertSee('Punch timeline');
        $this->get('/learner/attendance/'.$inOnly->toDateString())->assertOk()->assertSee('No exit recorded');
        $this->get('/learner/attendance/'.$today->toDateString())->assertOk()->assertSee('Live today');

        // an empty day is "Absent"
        $empty = $today->copy()->subDays(3);
        Attendance::withoutGlobalScopes()->where('learner_id', $l->id)->whereDate('date', $empty)->delete();
        $this->get('/learner/attendance/'.$empty->toDateString())->assertOk();
    }

    public function test_day_page_rules(): void
    {
        $l = $this->learner();
        $this->actingAs($l, 'learner');
        $this->get('/learner/attendance/not-a-date')->assertNotFound();
        $this->get('/learner/attendance/2026-02-31')->assertNotFound();
        $this->get('/learner/attendance/'.now()->addDays(5)->toDateString())->assertRedirect('/learner/attendance/'.now()->toDateString());
        $this->get('/learner/attendance/1999-01-01')->assertRedirect();
    }

    public function test_calendar_counts_present_half_and_absent(): void
    {
        $l = $this->learner();
        $this->joinedEarly($l);
        $svc = app(\App\Services\LearnerAttendanceService::class);
        $day = Carbon::today()->subDay();
        $this->punch($l, $day, '09:00:00', '12:00:00');
        $m = $svc->month($l, $day);
        $cell = collect($m['calendar'])->firstWhere('date', $day->toDateString());
        $this->assertTrue($cell['in_range']);
        $this->assertSame('full', $cell['status']);
        $this->assertSame(3.0, $cell['hours']);
        $d = $svc->day($l, $day);
        $this->assertSame('Present', $d['status']);
        $this->assertSame('3h 0m', $d['duration']);
        $this->assertCount(2, $d['punches']);
    }
}
