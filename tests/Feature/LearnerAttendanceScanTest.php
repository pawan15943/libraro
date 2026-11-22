<?php

namespace Tests\Feature;

use App\Models\Learner;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Learner portal "Attendance QR" page: scan the library QR (camera) or show my QR (desk scanner). */
class LearnerAttendanceScanTest extends TestCase
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

    /** An active, unfrozen learner whose latest plan runs past today. */
    private function learner(): Learner
    {
        $id = DB::table('learners as l')
            ->join('learner_detail as d', 'd.learner_id', '=', 'l.id')
            ->where('l.status', 1)->where('l.frozen_status', 0)->whereNull('l.deleted_at')->whereNull('d.deleted_at')
            ->whereColumn('d.branch_id', 'l.branch_id')
            ->where('d.plan_end_date', '>=', Carbon::today()->toDateString())
            ->orderByDesc('l.id')->value('l.id');
        if (! $id) {
            $this->markTestSkipped('no active learner with a running plan in this database');
        }

        return Learner::withoutGlobalScopes()->findOrFail($id);
    }

    public function test_page_has_scanner_and_my_qr_tabs(): void
    {
        $l = $this->learner();
        $this->actingAs($l, 'learner');

        $this->get('/learner/attendance/scan')->assertOk()
            ->assertSee('Scan library QR')->assertSee('My QR')
            ->assertSee('id="lscPaneQr"', false)
            ->assertSee($l->learner_no)
            ->assertSee('<svg', false);

        // ?show=qr opens the My QR tab
        $html = $this->get('/learner/attendance/scan?show=qr')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="lscPaneScan"[^>]*hidden/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="lscPaneQr"[^>]*hidden/', $html);
    }

    /** Dashboard card and the phone "Attendance" tab open the scan page; the tab stays lit on the month page. */
    public function test_dashboard_and_bottom_tab_open_the_scan_page(): void
    {
        $l = $this->learner();
        $this->actingAs($l, 'learner');
        $scan = route('learner.attendance.scan');

        $this->get('/learner/home?goal=later')->assertOk();
        $this->get('/learner/home')->assertOk()->assertSee('href="'.$scan.'" class="lh-att', false);

        foreach (['/learner/attendance', '/learner/attendance/scan'] as $page) {
            $this->get($page)->assertOk()->assertSee('href="'.$scan.'" class="lr-tab is-on"', false);
        }
    }

    /** The QR shown on the page is what the desk scanner decodes back to this learner. */
    public function test_my_qr_payload_decodes_to_the_learner(): void
    {
        $l = $this->learner();
        $payload = generateLearnerQrPayload($l->branch_id, $l->learner_no, $l->mobile, $l->name);
        $decoded = decryptLearnerQrPayload($payload);
        $this->assertSame((string) $l->learner_no, $decoded['l_no'] ?? null);
    }

    /**
     * The QR key's base64 padding ("==", "=" or none, or a leading space) depends on the library
     * name; splitting at the first "=" used to reject the QR for about one library in five.
     */
    public function test_library_qr_validates_for_every_key_shape(): void
    {
        $branches = DB::table('branches')->whereNull('deleted_at')->limit(60)->pluck('id');
        if ($branches->isEmpty()) {
            $this->markTestSkipped('no branches');
        }
        foreach ($branches as $b) {
            $t = AttendanceService::attendanceQrTokens((int) $b);
            $this->assertSame((int) $b, AttendanceService::validateQrToken($t['primary']), "rolling QR of branch $b");
            $this->assertSame((int) $b, AttendanceService::validateQrToken($t['for_download']), "fixed QR of branch $b");
        }
        $this->assertFalse(AttendanceService::validateQrToken(base64_encode($branches[0].'|download|forged')));
    }

    public function test_scanning_the_library_qr_marks_attendance(): void
    {
        $l = $this->learner();
        $this->actingAs($l, 'learner');
        cache()->forget('scan_'.$l->id);

        $this->postJson('/learner/attendance/scan', ['qr' => 'not-a-qr'])->assertStatus(422)->assertJson(['status' => false]);

        $qr = AttendanceService::staticAttendanceQrToken((int) $l->branch_id);
        $r = $this->postJson('/learner/attendance/scan', ['qr' => $qr]);
        $r->assertOk()->assertJson(['status' => true]);

        // a second scan within the lock window counts once
        $this->postJson('/learner/attendance/scan', ['qr' => $qr])->assertOk()->assertJson(['type' => 'duplicate']);
        cache()->forget('scan_'.$l->id);
    }
}
