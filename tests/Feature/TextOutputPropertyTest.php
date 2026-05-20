<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Unit;
use App\Models\PatientData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextOutputPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 12: Saved Data Generates Text Output
     * 
     * For any successfully saved patient data, the system SHALL generate a formatted 
     * text representation that can be copied to clipboard.
     * 
     * Validates: Requirements 2.7
     */
    public function test_saved_data_generates_text_output()
    {
        // Test with multiple units and data combinations
        $units = [
            ['name' => 'IGD', 'fields_count' => 6],
            ['name' => 'Rawat Inap', 'fields_count' => 9],
            ['name' => 'Rawat Jalan', 'fields_count' => 8],
            ['name' => 'VK', 'fields_count' => 3],
            ['name' => 'ICU', 'fields_count' => 8],
            ['name' => 'HCU', 'fields_count' => 8],
        ];

        $shifts = ['Pagi', 'Siang', 'Malam'];

        foreach ($units as $unitData) {
            // Create unit
            $unit = Unit::create([
                'name' => $unitData['name'],
                'status' => 'active',
            ]);

            // Create nurse user
            $user = User::create([
                'username' => 'nurse_' . strtolower(str_replace(' ', '_', $unitData['name'])),
                'password' => bcrypt('password123'),
                'full_name' => 'Nurse for ' . $unitData['name'],
                'role' => 'Nurse',
                'unit_id' => $unit->id,
                'status' => 'active',
            ]);

            $this->actingAs($user);

            // Test with each shift
            foreach ($shifts as $shift) {
                // Prepare data based on unit
                $data = $this->prepareDataForUnit($unitData['name'], $shift);

                // Make POST request
                $response = $this->postJson('/patient-data/store', $data);

                // Assert successful response
                $response->assertStatus(201);
                $response->assertJson(['success' => true]);

                // Assert text output is present
                $response->assertJsonStructure(['text_output']);
                $textOutput = $response->json('text_output');

                // Property: Text output must be a non-empty string
                $this->assertIsString($textOutput);
                $this->assertNotEmpty($textOutput);

                // Property: Text output must contain header
                $this->assertStringContainsString('DATA PASIEN', $textOutput);

                // Property: Text output must contain unit name
                $this->assertStringContainsString($unitData['name'], $textOutput);

                // Property: Text output must contain shift
                $this->assertStringContainsString($shift, $textOutput);

                // Property: Text output must contain date (formatted as dd-mm-yyyy)
                $dateObj = \DateTime::createFromFormat('Y-m-d', $data['date']);
                $formattedDate = $dateObj->format('d-m-Y');
                $this->assertStringContainsString($formattedDate, $textOutput);

                // Property: Text output must contain nurse name
                $this->assertStringContainsString($user->full_name, $textOutput);

                // Property: Text output must contain field names and values
                $this->assertStringContainsString('DETAIL DATA', $textOutput);

                // Property: Text output must be formatted with newlines
                $this->assertStringContainsString("\n", $textOutput);

                // Property: Text output must contain all non-auto-calculated field values
                $fields = $unit->getFieldDefinition();
                foreach ($fields as $field) {
                    if (!isset($field['auto_calculated'])) {
                        $fieldName = $field['name'];
                        $this->assertStringContainsString($fieldName, $textOutput);
                    }
                }
            }
        }
    }

    /**
     * Property: Text output format is consistent across all data
     * 
     * For any successfully saved patient data, the text output SHALL follow
     * a consistent format with header, metadata, and detail sections.
     */
    public function test_text_output_format_is_consistent()
    {
        // Create unit and user
        $unit = Unit::create([
            'name' => 'IGD',
            'status' => 'active',
        ]);

        $user = User::create([
            'username' => 'nurse_format_test',
            'password' => bcrypt('password123'),
            'full_name' => 'Format Test Nurse',
            'role' => 'Nurse',
            'unit_id' => $unit->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        // Test with different data values
        $testCases = [
            [
                'date' => date('Y-m-d'),
                'shift' => 'Pagi',
                'jumlah_pasien_rawat_inap' => 0,
                'jumlah_pasien_rawat_jalan' => 0,
                'jumlah_pasien_pulang_paksa' => 0,
                'keterangan_penyakit_rawat_inap' => 'No cases',
                'keterangan_penyakit_rawat_jalan' => 'No cases',
            ],
            [
                'date' => date('Y-m-d'),
                'shift' => 'Siang',
                'jumlah_pasien_rawat_inap' => 9999,
                'jumlah_pasien_rawat_jalan' => 9999,
                'jumlah_pasien_pulang_paksa' => 9999,
                'keterangan_penyakit_rawat_inap' => 'Test description with special chars: !@#$%',
                'keterangan_penyakit_rawat_jalan' => 'Another test description',
            ],
            [
                'date' => date('Y-m-d'),
                'shift' => 'Malam',
                'jumlah_pasien_rawat_inap' => 50,
                'jumlah_pasien_rawat_jalan' => 75,
                'jumlah_pasien_pulang_paksa' => 10,
                'keterangan_penyakit_rawat_inap' => 'Demam tinggi, batuk',
                'keterangan_penyakit_rawat_jalan' => 'Sakit kepala',
            ],
        ];

        foreach ($testCases as $data) {
            $response = $this->postJson('/patient-data/store', $data);

            // If validation fails, check what the error is
            if ($response->status() !== 201) {
                $this->fail('Response status: ' . $response->status() . ', Errors: ' . json_encode($response->json()));
            }

            $response->assertStatus(201);
            $textOutput = $response->json('text_output');

            // Property: Text output must have consistent structure
            $lines = explode("\n", $textOutput);
            
            // First line should be header
            $this->assertStringContainsString('DATA PASIEN', $lines[0]);

            // Should have metadata section
            $hasMetadata = false;
            foreach ($lines as $line) {
                if (strpos($line, 'Unit:') !== false || 
                    strpos($line, 'Tanggal:') !== false || 
                    strpos($line, 'Shift:') !== false || 
                    strpos($line, 'Perawat:') !== false) {
                    $hasMetadata = true;
                    break;
                }
            }
            $this->assertTrue($hasMetadata, 'Text output must contain metadata section');

            // Should have detail section
            $this->assertStringContainsString('DETAIL DATA', $textOutput);
        }
    }

    /**
     * Property: Text output is copyable (contains no null bytes or invalid characters)
     * 
     * For any successfully saved patient data, the text output SHALL be valid
     * for copying to clipboard without encoding issues.
     */
    public function test_text_output_is_copyable()
    {
        // Create unit and user
        $unit = Unit::create([
            'name' => 'Rawat Inap',
            'status' => 'active',
        ]);

        $user = User::create([
            'username' => 'nurse_copy_test',
            'password' => bcrypt('password123'),
            'full_name' => 'Copy Test Nurse',
            'role' => 'Nurse',
            'unit_id' => $unit->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $data = [
            'date' => date('Y-m-d'),
            'shift' => 'Pagi',
            'jumlah_pasien_anak' => 10,
            'jumlah_pasien_dalam' => 20,
            'jumlah_pasien_saraf' => 15,
            'jumlah_pasien_obsgyn' => 8,
            'jumlah_pasien_bedah' => 12,
            'jumlah_inden' => 5,
            'jumlah_rpl' => 3,
            'jumlah_pasien_pulang' => 7,
        ];

        $response = $this->postJson('/patient-data/store', $data);

        $response->assertStatus(201);
        $textOutput = $response->json('text_output');

        // Property: Text output must not contain null bytes
        $this->assertStringNotContainsString("\0", $textOutput);

        // Property: Text output must be valid UTF-8
        $this->assertTrue(mb_check_encoding($textOutput, 'UTF-8'));

        // Property: Text output must be able to be trimmed and still be valid
        $trimmed = trim($textOutput);
        $this->assertNotEmpty($trimmed);
        $this->assertTrue(mb_check_encoding($trimmed, 'UTF-8'));

        // Property: Text output length must be reasonable (not too short, not too long)
        $this->assertGreaterThan(50, strlen($textOutput));
        $this->assertLessThan(10000, strlen($textOutput));
    }

    /**
     * Property: Text output includes all required information
     * 
     * For any successfully saved patient data, the text output SHALL include
     * all required metadata and field values.
     */
    public function test_text_output_includes_all_required_information()
    {
        // Create unit and user
        $unit = Unit::create([
            'name' => 'VK',
            'status' => 'active',
        ]);

        $user = User::create([
            'username' => 'nurse_info_test',
            'password' => bcrypt('password123'),
            'full_name' => 'Info Test Nurse',
            'role' => 'Nurse',
            'unit_id' => $unit->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $testDate = date('Y-m-d');
        $data = [
            'date' => $testDate,
            'shift' => 'Siang',
            'jumlah_pasien_vk' => 25,
            'keterangan' => 'Test keterangan VK',
        ];

        $response = $this->postJson('/patient-data/store', $data);

        $response->assertStatus(201);
        $textOutput = $response->json('text_output');

        // Property: Must contain unit name
        $this->assertStringContainsString('VK', $textOutput);

        // Property: Must contain date in readable format (dd-mm-yyyy)
        $dateObj = \DateTime::createFromFormat('Y-m-d', $testDate);
        $formattedDate = $dateObj->format('d-m-Y');
        $this->assertStringContainsString($formattedDate, $textOutput);

        // Property: Must contain shift
        $this->assertStringContainsString('Siang', $textOutput);

        // Property: Must contain nurse name
        $this->assertStringContainsString('Info Test Nurse', $textOutput);

        // Property: Must contain field names
        $this->assertStringContainsString('Jumlah pasien VK', $textOutput);
        $this->assertStringContainsString('Keterangan', $textOutput);

        // Property: Must contain field values
        $this->assertStringContainsString('25', $textOutput);
        $this->assertStringContainsString('Test keterangan VK', $textOutput);
    }

    /**
     * Helper method to prepare data for different units
     */
    private function prepareDataForUnit(string $unitName, string $shift): array
    {
        $baseData = [
            'date' => date('Y-m-d'),
            'shift' => $shift,
        ];

        switch ($unitName) {
            case 'IGD':
                return array_merge($baseData, [
                    'jumlah_pasien_rawat_inap' => 10,
                    'jumlah_pasien_rawat_jalan' => 20,
                    'jumlah_pasien_pulang_paksa' => 5,
                    'keterangan_penyakit_rawat_inap' => 'Demam',
                    'keterangan_penyakit_rawat_jalan' => 'Batuk',
                ]);
            case 'Rawat Inap':
                return array_merge($baseData, [
                    'jumlah_pasien_anak' => 5,
                    'jumlah_pasien_dalam' => 10,
                    'jumlah_pasien_saraf' => 8,
                    'jumlah_pasien_obsgyn' => 6,
                    'jumlah_pasien_bedah' => 7,
                    'jumlah_inden' => 2,
                    'jumlah_rpl' => 1,
                    'jumlah_pasien_pulang' => 3,
                ]);
            case 'Rawat Jalan':
                return array_merge($baseData, [
                    'jumlah_poli_obgyn' => 15,
                    'jumlah_poli_dalam' => 20,
                    'jumlah_poli_anak' => 18,
                    'jumlah_poli_bedah' => 12,
                    'jumlah_poli_saraf' => 10,
                    'jumlah_poli_fisioterapi' => 8,
                ]);
            case 'VK':
                return array_merge($baseData, [
                    'jumlah_pasien_vk' => 25,
                    'keterangan' => 'Normal delivery',
                ]);
            case 'ICU':
                return array_merge($baseData, [
                    'jumlah_pasien_anak' => 3,
                    'jumlah_pasien_dalam' => 5,
                    'jumlah_pasien_saraf' => 4,
                    'jumlah_pasien_obsgyn' => 2,
                    'jumlah_pasien_bedah' => 3,
                    'jumlah_pasien_inden' => 1,
                    'jumlah_pasien_pulang' => 2,
                ]);
            case 'HCU':
                return array_merge($baseData, [
                    'jumlah_pasien_anak' => 4,
                    'jumlah_pasien_dalam' => 6,
                    'jumlah_pasien_saraf' => 5,
                    'jumlah_pasien_obsgyn' => 3,
                    'jumlah_pasien_bedah' => 4,
                    'jumlah_pasien_inden' => 2,
                    'jumlah_pasien_pulang' => 3,
                ]);
            default:
                return $baseData;
        }
    }
}
