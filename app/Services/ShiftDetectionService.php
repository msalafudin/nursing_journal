<?php

namespace App\Services;

use Carbon\Carbon;

class ShiftDetectionService
{
    /**
     * Get the current shift based on the current time (WIB/UTC+7).
     * 
     * Shift definitions:
     * - Pagi (Morning): 07:00:00 - 13:59:59
     * - Siang (Afternoon): 14:00:00 - 20:59:59
     * - Malam (Night): 21:00:00 - 06:59:59
     * 
     * @return string The current shift: 'Pagi', 'Siang', or 'Malam'
     */
    public function getCurrentShift(): string
    {
        $now = Carbon::now('Asia/Jakarta');
        $hour = $now->hour;

        if ($hour >= 7 && $hour < 14) {
            return 'Pagi';
        } elseif ($hour >= 14 && $hour < 21) {
            return 'Siang';
        } else {
            return 'Malam';
        }
    }

    /**
     * Get all available shifts.
     * 
     * @return array Array of shift names
     */
    public function getAvailableShifts(): array
    {
        return ['Pagi', 'Siang', 'Malam'];
    }
}
