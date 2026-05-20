<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Seed the units table with initial nursing units.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'IGD', 'status' => 'active'],
            ['name' => 'Rawat Inap', 'status' => 'active'],
            ['name' => 'Rawat Jalan', 'status' => 'active'],
            ['name' => 'VK', 'status' => 'active'],
            ['name' => 'ICU', 'status' => 'active'],
            ['name' => 'HCU', 'status' => 'active'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['name' => $unit['name']],
                ['status' => $unit['status']]
            );
        }
    }
}
