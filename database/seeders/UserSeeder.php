<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the users table with test admin and nurse users.
     */
    public function run(): void
    {
        // Create test admin user
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'password' => Hash::make('password123'),
                'full_name' => 'Administrator',
                'role' => 'Admin',
                'unit_id' => null,
                'status' => 'active',
            ]
        );

        // Get all units for nurse assignments
        $units = Unit::all();

        // Create test nurse users, one for each unit
        $nurseData = [
            ['username' => 'nurse_igd', 'full_name' => 'Perawat IGD', 'unit_name' => 'IGD'],
            ['username' => 'nurse_rawat_inap', 'full_name' => 'Perawat Rawat Inap', 'unit_name' => 'Rawat Inap'],
            ['username' => 'nurse_rawat_jalan', 'full_name' => 'Perawat Rawat Jalan', 'unit_name' => 'Rawat Jalan'],
            ['username' => 'nurse_vk', 'full_name' => 'Perawat VK', 'unit_name' => 'VK'],
            ['username' => 'nurse_icu', 'full_name' => 'Perawat ICU', 'unit_name' => 'ICU'],
            ['username' => 'nurse_hcu', 'full_name' => 'Perawat HCU', 'unit_name' => 'HCU'],
        ];

        foreach ($nurseData as $data) {
            $unit = $units->firstWhere('name', $data['unit_name']);

            User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'password' => Hash::make('password123'),
                    'full_name' => $data['full_name'],
                    'role' => 'Nurse',
                    'unit_id' => $unit?->id,
                    'status' => 'active',
                ]
            );
        }
    }
}
