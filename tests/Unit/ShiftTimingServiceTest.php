<?php

namespace Tests\Unit;

use App\Models\PlanType;
use App\Services\ShiftTimingService;
use PHPUnit\Framework\TestCase;

/**
 * Pure timing rules for duration/free shift templates (no database needed).
 * Run: vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Unit/ShiftTimingServiceTest.php
 */
class ShiftTimingServiceTest extends TestCase
{
    private function template(string $mode, string $start, string $end, string $hours): PlanType
    {
        return new PlanType([
            'booking_mode' => $mode,
            'start_time' => $start,
            'end_time' => $end,
            'slot_hours' => $hours,
            'parent_id' => null,
        ]);
    }

    public function test_duration_end_is_start_plus_hours(): void
    {
        $result = (new ShiftTimingService)->validateTiming($this->template('duration', '06:00', '22:00', '4'), '07:00');

        $this->assertFalse($result['error']);
        $this->assertSame('11:00', $result['end']);
        $this->assertSame(240, $result['minutes']);
    }

    public function test_duration_must_fit_inside_window(): void
    {
        $service = new ShiftTimingService;
        $template = $this->template('duration', '06:00', '22:00', '4');

        $this->assertTrue($service->validateTiming($template, '05:00')['error']);
        $this->assertTrue($service->validateTiming($template, '19:00')['error']);
        $this->assertFalse($service->validateTiming($template, '18:00')['error']);
    }

    public function test_overnight_window(): void
    {
        $service = new ShiftTimingService;
        $template = $this->template('free', '20:00', '08:00', '12');

        $this->assertFalse($service->validateTiming($template, '23:00', '03:00')['error']);
        $this->assertTrue($service->validateTiming($template, '07:00', '09:00')['error']);
    }

    public function test_free_needs_minimum_length(): void
    {
        $template = $this->template('free', '06:00', '22:00', '16');

        $this->assertTrue((new ShiftTimingService)->validateTiming($template, '09:00', '09:15')['error']);
    }

    public function test_fixed_shift_is_not_a_timed_template(): void
    {
        $this->assertTrue((new ShiftTimingService)->validateTiming($this->template('fixed', '06:00', '14:00', '8'), '07:00')['error']);
    }

    public function test_free_gaps_around_bookings(): void
    {
        $gaps = (new ShiftTimingService)->freeGaps(
            $this->template('duration', '06:00', '22:00', '4'),
            [['start' => '07:00', 'end' => '11:00'], ['start' => '15:00', 'end' => '19:00']]
        );

        $this->assertSame([
            ['start' => '06:00', 'end' => '07:00', 'minutes' => 60],
            ['start' => '11:00', 'end' => '15:00', 'minutes' => 240],
            ['start' => '19:00', 'end' => '22:00', 'minutes' => 180],
        ], $gaps);
    }

    public function test_template_fits_only_when_gap_is_long_enough(): void
    {
        $service = new ShiftTimingService;
        $template = $this->template('duration', '06:00', '22:00', '4');
        $row = fn ($s, $e) => (object) ['start_time' => $s, 'end_time' => $e];

        $this->assertTrue($service->templateFits($template, [$row('07:00', '11:00')]));
        $this->assertFalse($service->templateFits($template, [$row('06:00', '09:00'), $row('12:00', '15:00'), $row('18:00', '22:00')]));
    }
}
