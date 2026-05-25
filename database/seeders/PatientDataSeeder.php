<?php

namespace Database\Seeders;

use App\Models\PatientData;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PatientDataSeeder extends Seeder
{
    public function run(): void
    {
        $units = Unit::all();
        $shifts = ['Pagi', 'Siang', 'Malam'];
        $startDate = Carbon::create(2026, 5, 8);
        $endDate = Carbon::create(2026, 5, 20);

        foreach ($units as $unit) {
            // Cari user yang ditugaskan ke unit ini, atau pakai user pertama
            $user = User::where('unit_id', $unit->id)->first()
                ?? User::first();

            foreach ($startDate->toPeriod($endDate) as $date) {
                foreach ($shifts as $shift) {
                    $data = $this->generateRandomData($unit->name);
                    $total = $this->calculateTotal($unit->name, $data);

                    PatientData::updateOrCreate(
                        [
                            'unit_id' => $unit->id,
                            'date' => $date->format('Y-m-d'),
                            'shift' => $shift,
                        ],
                        [
                            'user_id' => $user->id,
                            'data' => $data,
                            'total_patients' => $total,
                        ]
                    );
                }
            }
        }

        $this->command?->info('Patient data seeded: 8-20 Mei 2026, all units, all shifts.');
    }

    private function generateRandomData(string $unitName): array
    {
        return match ($unitName) {
            'IGD' => [
                'jumlah_pasien_rawat_inap' => rand(5, 30),
                'jumlah_pasien_rawat_jalan' => rand(10, 50),
                'jumlah_pasien_pulang_paksa' => rand(0, 5),
                'keterangan_penyakit_rawat_inap' => 'Demam, Diare, Typoid',
                'keterangan_penyakit_rawat_jalan' => 'Batuk, Flu, ISPA',
            ],
            'Rawat Inap' => [
                // Sensus (snapshot pasien saat ini)
                'sensus_anak' => rand(3, 15),
                'sensus_dalam' => rand(5, 20),
                'sensus_saraf' => rand(2, 10),
                'sensus_obsgyn' => rand(2, 12),
                'sensus_bedah' => rand(3, 15),
                // Mutasi (pergerakan pasien di shift ini)
                'masuk_baru' => rand(0, 5),
                'pasien_pulang' => rand(0, 4),
                'jumlah_inden' => rand(0, 3),
                'jumlah_rpl' => rand(0, 2),
            ],
            'Rawat Jalan' => [
                'jumlah_poli_obgyn' => rand(5, 25),
                'jumlah_poli_dalam' => rand(10, 40),
                'jumlah_poli_anak' => rand(8, 30),
                'jumlah_poli_bedah' => rand(5, 20),
                'jumlah_poli_saraf' => rand(3, 15),
                'jumlah_poli_fisioterapi' => rand(2, 12),
            ],
            'VK' => [
                'jumlah_pasien_vk' => rand(1, 10),
                'keterangan' => 'Normal delivery, SC',
            ],
            'ICU' => [
                'jumlah_pasien_anak' => rand(0, 3),
                'jumlah_pasien_dalam' => rand(1, 5),
                'jumlah_pasien_saraf' => rand(0, 3),
                'jumlah_pasien_obsgyn' => rand(0, 2),
                'jumlah_pasien_bedah' => rand(1, 4),
                'jumlah_pasien_inden' => rand(0, 2),
                'jumlah_pasien_pulang' => rand(0, 3),
            ],
            'HCU' => [
                'jumlah_pasien_anak' => rand(0, 4),
                'jumlah_pasien_dalam' => rand(1, 6),
                'jumlah_pasien_saraf' => rand(0, 4),
                'jumlah_pasien_obsgyn' => rand(0, 3),
                'jumlah_pasien_bedah' => rand(1, 5),
                'jumlah_pasien_inden' => rand(0, 3),
                'jumlah_pasien_pulang' => rand(0, 4),
            ],
            default => [],
        };
    }

    /**
     * Calculate total patients based on unit type.
     * For Rawat Inap (hybrid): only sum sensus fields.
     * For other units: sum all numeric fields.
     */
    private function calculateTotal(string $unitName, array $data): int
    {
        if ($unitName === 'Rawat Inap') {
            // Only count sensus fields (actual patient count)
            $sensusKeys = ['sensus_anak', 'sensus_dalam', 'sensus_saraf', 'sensus_obsgyn', 'sensus_bedah'];
            return collect($data)
                ->filter(fn($v, $k) => in_array($k, $sensusKeys) && is_numeric($v))
                ->sum();
        }

        // For other units, sum all numeric values
        return collect($data)->filter(fn($v) => is_numeric($v))->sum();
    }
}
