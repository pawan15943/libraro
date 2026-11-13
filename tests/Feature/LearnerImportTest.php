<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\Library;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Learner CSV import (library.csv.upload): the older rules plus advanced shifts
 * (Fixed hours, Direct booking, Flexible), on a new library + branch in a rolled back transaction.
 */
class LearnerImportTest extends TestCase
{
    use DatabaseTransactions;

    private Library $lib;

    private Branch $branch;

    private Plan $plan;

    private const HEADER = 'seat_no,name,email,mobile,plan,plan_type,start_date ,paid_amount,pending_amount,locker,locker_no,shift_start_time,shift_end_time,shift_price';

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir().'/libraro-test-storage');
        config(['mail.default' => 'array']);
        $this->withoutMiddleware();

        $this->lib = Library::create([
            'library_name' => 'Import Test Library', 'email' => 'import.'.uniqid().'@example.com',
            'password' => bcrypt('secret123'), 'email_verified_at' => now(),
            'is_paid' => 1, 'status' => 1, 'library_type' => 3, 'library_no' => 'LBT'.rand(10000, 99999),
        ]);
        $this->branch = Branch::withoutGlobalScopes()->create(['library_id' => $this->lib->id, 'name' => 'Main', 'status' => 1]);
        Hour::withoutGlobalScopes()->create(['library_id' => $this->lib->id, 'branch_id' => $this->branch->id, 'hour' => 24, 'seats' => 50]);
        \App\Services\BranchHoursService::forget();
        $this->plan = Plan::withoutGlobalScopes()->create(['library_id' => $this->lib->id, 'name' => '1 MONTH', 'plan_id' => 1, 'type' => 'MONTH']);
        $this->lib->update(['current_branch' => $this->branch->id]);
        $this->actingAs($this->lib->fresh(), 'library');

        $this->shift('Morning', '08:00', '12:00', 4, 500);
        $this->shift('4 Hr Fixed', '00:00', '00:00', 4, 600, 'duration');
        $this->shift('Custom Shift', '00:00', '00:00', 24, 0, 'free');
        $this->shift('4 Hr Flexible', '00:00', '00:00', 4, 400, 'flexible');
    }

    private function shift(string $name, string $start, string $end, $hours, $price, string $mode = 'fixed'): PlanType
    {
        $type = PlanType::withoutGlobalScopes()->create([
            'library_id' => $this->lib->id, 'branch_id' => $this->branch->id, 'name' => $name,
            'start_time' => $start, 'end_time' => $end, 'slot_hours' => $hours, 'day_type_id' => 0, 'booking_mode' => $mode,
        ]);
        PlanPrice::withoutGlobalScopes()->create([
            'library_id' => $this->lib->id, 'branch_id' => $this->branch->id,
            'plan_id' => $this->plan->id, 'plan_type_id' => $type->id, 'price' => $price,
        ]);

        return $type;
    }

    private function upload(array $rows, string $header = self::HEADER, string $prefix = '')
    {
        $csv = $prefix.$header."\n".implode("\n", $rows)."\n";
        $file = UploadedFile::fake()->createWithContent('learners.csv', $csv);

        return $this->post(route('library.csv.upload'), ['csv_file' => $file]);
    }

    private function row(string $seat, string $name, string $mobile, string $shift, string $start = '', string $end = '', string $price = '', string $paid = '500', string $pending = '0'): string
    {
        return implode(',', [$seat, $name, '', $mobile, '1 month', $shift, now()->format('d-m-Y'), $paid, $pending, 'no', '', $start, $end, $price]);
    }

    private function errors(): array
    {
        return collect(session('invalidRecords', []))->mapWithKeys(fn ($r) => [$r['name'] => $r['error']])->all();
    }

    private function bookedShift(string $name): ?PlanType
    {
        $learner = Learner::withoutGlobalScopes()->where('branch_id', $this->branch->id)->where('name', $name)->first();
        $detail = $learner ? LearnerDetail::withoutGlobalScopes()->where('learner_id', $learner->id)->first() : null;

        return $detail ? PlanType::withoutGlobalScopes()->find($detail->plan_type_id) : null;
    }

    public function test_fixed_and_advanced_shifts_import_and_clashes_are_listed_on_the_page(): void
    {
        $res = $this->upload([
            $this->row('1', 'Old Fixed', '9000000001', 'Morning'),
            $this->row('1', 'Fixed Hours', '9000000002', '4 Hr Fixed', '2 PM'),
            $this->row('1', 'Clash Hours', '9000000003', '4 Hr Fixed', '09:00'),
            $this->row('1', 'No Start', '9000000004', '4 Hr Fixed'),
            $this->row('2', 'Direct', '9000000005', 'Custom Shift', '06:00', '10:30', '450', '450'),
            $this->row('3', 'Flex On Seat', '9000000006', '4 Hr Flexible'),
            $this->row('', 'Flex General', '9000000007', '4 Hr Flexible', '', '', '', '400'),
            $this->row('4', 'No Price', '9000000008', 'Custom Shift', '06:00', '10:00', '', '0'),
            ',,,,,,,,,,,,,',
        ]);

        $res->assertRedirect(route('library.upload.form'));
        $this->assertSame(4, session('successCount'));

        $errors = $this->errors();
        $this->assertSame(['Clash Hours', 'No Start', 'Flex On Seat', 'No Price'], array_keys($errors));
        $this->assertStringStartsWith('Seat Already Booked', $errors['Clash Hours']);
        $this->assertStringStartsWith('Shift Start Time Missing', $errors['No Start']);
        $this->assertStringStartsWith('Flexible Shift On Seat', $errors['Flex On Seat']);
        $this->assertStringStartsWith('Shift Price Missing', $errors['No Price']);
        $this->assertSame(4, session('invalidRecords')[0]['csv_row']);

        $fixed = $this->bookedShift('Fixed Hours');
        $this->assertNotNull($fixed->parent_id);
        $this->assertSame(['14:00', '18:00'], [$fixed->start_time, $fixed->end_time]);

        $direct = $this->bookedShift('Direct');
        $this->assertSame(['06:00', '10:30'], [$direct->start_time, $direct->end_time]);
        $this->assertSame(450.0, (float) getPlanPrice($this->plan->id, $direct->id));

        $this->assertSame('4 Hr Flexible', $this->bookedShift('Flex General')->name);
        $this->assertSame('Morning', $this->bookedShift('Old Fixed')->name);
    }

    public function test_upload_page_lists_failed_rows_and_shift_columns(): void
    {
        $this->upload([
            $this->row('1', 'Good Row', '9000000031', 'Morning'),
            $this->row('1', 'Clash Row', '9000000032', '4 Hr Fixed', '10 AM'),
        ]);

        // Only this page: the portal layout needs menus/subscription from middleware that is off here
        $stub = sys_get_temp_dir().'/libraro-test-views';
        @mkdir($stub.'/layouts', 0777, true);
        file_put_contents($stub.'/layouts/library.blade.php', "@yield('content')");
        view()->getFinder()->prependLocation($stub);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        \Illuminate\Support\Facades\Gate::before(fn () => true);

        $this->get(route('library.upload.form'))
            ->assertOk()
            ->assertSee('<span class="li-td-row" role="cell">3</span>', false)
            ->assertSee('Clash Row')
            ->assertSee('Seat Already Booked')
            ->assertSee('shift_start_time')
            ->assertSee('Fixed hours')
            ->assertSee('Leave seat_no empty (general seat)')
            ->assertSee('1 MONTH')
            ->assertSee('₹500')            // Morning × 1 MONTH
            ->assertSee('Own price')        // Direct booking
            ->assertDontSee('4 Hr Fixed (');   // hidden timing rows are not listed
    }

    public function test_old_rules_still_apply(): void
    {
        $this->upload([$this->row('5', 'Repeat', '9000000011', 'Morning')]);
        $this->upload([
            $this->row('5', 'Repeat', '9000000011', 'Morning'),        // same learner again
            $this->row('6', 'Bad Mobile', '12345', 'Morning'),
            $this->row('6', 'Bad Shift', '9000000013', 'No Such Shift'),
            $this->row('6', 'Bad Amounts', '9000000014', 'Morning', '', '', '', '100', '300'),
        ]);

        $errors = $this->errors();
        // the older duplicate rules: "Duplicate Entry" or "You are already active", by start date
        $this->assertMatchesRegularExpression('/^(Duplicate Entry|You are already active)/', $errors['Repeat']);
        $this->assertStringContainsString('mobile', $errors['Bad Mobile']);
        $this->assertStringStartsWith('Plan Type Not Found', $errors['Bad Shift']);
        $this->assertStringStartsWith('Invalid Amounts', $errors['Bad Amounts']);
    }

    public function test_excel_style_file_imports(): void
    {
        // UTF-8 BOM, "Plan Type" style headers, no optional columns, paid 0
        $res = $this->upload(
            ['Excel One,9000000021,1 month,Morning,'.now()->format('d-m-Y').',0,0'],
            'Name,Mobile,Plan,Plan Type,Start Date,Paid Amount,Pending Amount',
            "\xEF\xBB\xBF"
        );

        $res->assertRedirect();
        $this->assertSame([], $this->errors());
        $this->assertSame(1, session('successCount'));
    }

    public function test_excel_workbook_is_refused_with_a_clear_message(): void
    {
        $file = UploadedFile::fake()->createWithContent('learners.xlsx', 'PK');
        $this->post(route('library.csv.upload'), ['csv_file' => $file])
            ->assertSessionHasErrors();
        $this->assertStringContainsString('CSV (Comma delimited)', session('errors')->first());
    }
}
