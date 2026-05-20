<?php

namespace Database\Factories;

use App\Models\PatientData;
use App\Models\User;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PatientData>
 */
class PatientDataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = PatientData::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'unit_id' => Unit::factory(),
            'date' => Carbon::now('Asia/Jakarta')->format('Y-m-d'),
            'shift' => $this->faker->randomElement(['Pagi', 'Siang', 'Malam']),
            'data' => [
                'jumlah_pasien_rawat_inap' => $this->faker->numberBetween(0, 100),
                'jumlah_pasien_rawat_jalan' => $this->faker->numberBetween(0, 100),
                'jumlah_pasien_pulang_paksa' => $this->faker->numberBetween(0, 50),
            ],
            'total_patients' => $this->faker->numberBetween(10, 200),
        ];
    }
}
