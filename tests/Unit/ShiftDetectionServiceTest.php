<?php

namespace Tests\Unit;

use App\Services\ShiftDetectionService;
use Carbon\Carbon;
use Tests\TestCase;

class ShiftDetectionServiceTest extends TestCase
{
    /**
     * Test that the service returns 'Pagi' for morning hours (07:00-13:59).
     */
    public function test_get_current_shift_returns_pagi_for_morning_hours()
    {
        $service = new ShiftDetectionService();

        // Mock the current time to be in the morning shift
        Carbon::setTestNow(Carbon::createFromTimeString('09:00:00', 'Asia/Jakarta'));

        $shift = $service->getCurrentShift();

        $this->assertEquals('Pagi', $shift);
    }

    /**
     * Test that the service returns 'Siang' for afternoon hours (14:00-20:59).
     */
    public function test_get_current_shift_returns_siang_for_afternoon_hours()
    {
        $service = new ShiftDetectionService();

        // Mock the current time to be in the afternoon shift
        Carbon::setTestNow(Carbon::createFromTimeString('16:00:00', 'Asia/Jakarta'));

        $shift = $service->getCurrentShift();

        $this->assertEquals('Siang', $shift);
    }

    /**
     * Test that the service returns 'Malam' for night hours (21:00-06:59).
     */
    public function test_get_current_shift_returns_malam_for_night_hours()
    {
        $service = new ShiftDetectionService();

        // Mock the current time to be in the night shift
        Carbon::setTestNow(Carbon::createFromTimeString('23:00:00', 'Asia/Jakarta'));

        $shift = $service->getCurrentShift();

        $this->assertEquals('Malam', $shift);
    }

    /**
     * Test that the service returns 'Malam' for early morning hours (00:00-06:59).
     */
    public function test_get_current_shift_returns_malam_for_early_morning_hours()
    {
        $service = new ShiftDetectionService();

        // Mock the current time to be in the early morning (still night shift)
        Carbon::setTestNow(Carbon::createFromTimeString('03:00:00', 'Asia/Jakarta'));

        $shift = $service->getCurrentShift();

        $this->assertEquals('Malam', $shift);
    }

    /**
     * Test that the service returns correct shift at shift boundaries.
     */
    public function test_get_current_shift_at_shift_boundaries()
    {
        $service = new ShiftDetectionService();

        // Test at 07:00:00 (start of Pagi)
        Carbon::setTestNow(Carbon::createFromTimeString('07:00:00', 'Asia/Jakarta'));
        $this->assertEquals('Pagi', $service->getCurrentShift());

        // Test at 13:59:59 (end of Pagi)
        Carbon::setTestNow(Carbon::createFromTimeString('13:59:59', 'Asia/Jakarta'));
        $this->assertEquals('Pagi', $service->getCurrentShift());

        // Test at 14:00:00 (start of Siang)
        Carbon::setTestNow(Carbon::createFromTimeString('14:00:00', 'Asia/Jakarta'));
        $this->assertEquals('Siang', $service->getCurrentShift());

        // Test at 20:59:59 (end of Siang)
        Carbon::setTestNow(Carbon::createFromTimeString('20:59:59', 'Asia/Jakarta'));
        $this->assertEquals('Siang', $service->getCurrentShift());

        // Test at 21:00:00 (start of Malam)
        Carbon::setTestNow(Carbon::createFromTimeString('21:00:00', 'Asia/Jakarta'));
        $this->assertEquals('Malam', $service->getCurrentShift());

        // Test at 06:59:59 (end of Malam)
        Carbon::setTestNow(Carbon::createFromTimeString('06:59:59', 'Asia/Jakarta'));
        $this->assertEquals('Malam', $service->getCurrentShift());
    }

    /**
     * Test that the service returns all available shifts.
     */
    public function test_get_available_shifts_returns_all_shifts()
    {
        $service = new ShiftDetectionService();

        $shifts = $service->getAvailableShifts();

        $this->assertEquals(['Pagi', 'Siang', 'Malam'], $shifts);
        $this->assertCount(3, $shifts);
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
