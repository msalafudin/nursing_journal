<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Unit;
use App\Models\PatientData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientDataControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the form display method returns the correct view with unit-specific fields.
     */
    public function test_show_form_returns_view_with_unit_fields()
    {
        // Create a unit
        $unit = Unit::create([
            'name' => 'IGD',
            'status' => 'active',
        ]);

        // Create a nurse user assigned to the unit
        $user = User::create([
            'username' => 'nurse1',
            'password' => bcrypt('password123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $unit->id,
            'status' => 'active',
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Make request to the form display method
        $response = $this->get('/patient-data/form');

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert the view is correct
        $response->assertViewIs('patient-data.form');

        // Assert the view has the required data
        $response->assertViewHas('unit');
        $response->assertViewHas('currentShift');
        $response->assertViewHas('fields');
        $response->assertViewHas('availableShifts');

        // Assert the unit is correct
        $this->assertEquals($unit->id, $response->viewData('unit')->id);

        // Assert the current shift is one of the valid shifts
        $this->assertContains($response->viewData('currentShift'), ['Pagi', 'Siang', 'Malam']);

        // Assert the fields are correct for IGD unit
        $fields = $response->viewData('fields');
        $this->assertNotEmpty($fields);
        $this->assertCount(6, $fields); // IGD has 6 fields (5 required + 1 total)

        // Assert the available shifts are correct
        $availableShifts = $response->viewData('availableShifts');
        $this->assertEquals(['Pagi', 'Siang', 'Malam'], $availableShifts);
    }

    /**
     * Test that the form display method redirects if user is not assigned to a unit.
     */
    public function test_show_form_redirects_if_user_not_assigned_to_unit()
    {
        // Create a nurse user without a unit assignment
        $user = User::create([
            'username' => 'nurse2',
            'password' => bcrypt('password123'),
            'full_name' => 'Nurse Two',
            'role' => 'Nurse',
            'unit_id' => null,
            'status' => 'active',
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Make request to the form display method
        $response = $this->get('/patient-data/form');

        // Assert the response redirects to dashboard
        $response->assertRedirect('/dashboard');

        // Assert the error message is present
        $response->assertSessionHasErrors('error');
    }

    /**
     * Test that the form display method requires authentication.
     */
    public function test_show_form_requires_authentication()
    {
        // Make request without authentication
        $response = $this->get('/patient-data/form');

        // Assert the response redirects to login
        $response->assertRedirect('/login');
    }

    /**
     * Test that different units return different field definitions.
     */
    public function test_show_form_returns_correct_fields_for_different_units()
    {
        // Test for Rawat Inap unit
        $unit = Unit::create([
            'name' => 'Rawat Inap',
            'status' => 'active',
        ]);

        $user = User::create([
            'username' => 'nurse3',
            'password' => bcrypt('password123'),
            'full_name' => 'Nurse Three',
            'role' => 'Nurse',
            'unit_id' => $unit->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $response = $this->get('/patient-data/form');

        $response->assertStatus(200);

        // Assert the fields are correct for Rawat Inap unit
        $fields = $response->viewData('fields');
        $this->assertCount(9, $fields); // Rawat Inap has 9 fields (8 required + 1 total)

        // Verify specific field names
        $fieldKeys = array_column($fields, 'key');
        $this->assertContains('jumlah_pasien_anak', $fieldKeys);
        $this->assertContains('jumlah_pasien_dalam', $fieldKeys);
        $this->assertContains('total', $fieldKeys);
    }

    /**
     * Test that valid patient data is stored successfully.
     * Requirements: 2.2
     */
    public function test_store_valid_patient_data()
    {
        // Create a unit
        $unit = Unit::create([
            'name' => 'IGD',
            'status' => 'active',
        ]);

        // Create a nurse user
        $user = User::create([
            'username' => 'nurse4',
            'password' => bcrypt('password123'),
            'full_name' => 'Nurse Four',
            'role' => 'Nurse',
            'unit_id' => $unit->id,
            'status' => 'active',
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Prepare valid data
        $data = [
            'date' => date('Y-m-d'),
            'shift' => 'Pagi',
            'jumlah_pasien_rawat_inap' => 10,
            'jumlah_pasien_rawat_jalan' => 20,
            'jumlah_pasien_pulang_paksa' => 5,
            'keterangan_penyakit_rawat_inap' => 'Demam tinggi',
            'keterangan_penyakit_rawat_jalan' => 'Batuk pilek',
        ];

        // Make POST request
        $response = $this->postJson('/patient-data/store', $data);

        // Assert the response is successful
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Data pasien berhasil disimpan',
        ]);

        // Assert the data is stored in the database
        $this->assertDatabaseHas('patient_data', [
            'user_id' => $user->id,
            'unit_id' => $unit->id,
            'date' => $data['date'],
            'shift' => $data['shift'],
        ]);

        // Assert the total_patients is calculated correctly
        $patientData = PatientData::where('user_id', $user->id)->first();
        $this->assertEquals(35, $patientData->total_patients); // 10 + 20 + 5
    }

    /**
     * Test that missing required fields trigger validation errors.
     * Requirements: 2.3
     */
    public function test_store_missing_required_fields()
    {
        // Create a unit
        $unit = Unit::create([
            'name' => 'IGD',
            'status' => 'active',
        ]);

        // Create a nurse user
        $user = User::create([
            'username' => 'nurse5',
            'password' => bcrypt('password123'),
            'full_name' => 'Nurse Five',
            'role' => 'Nurse',
            'unit_id' => $unit->id,
            'status' => 'active',
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Prepare data with missing required field
        $data = [
            'date' => date('Y-m-d'),
            'shift' => 'Pagi',
            'jumlah_pasien_rawat_inap' => 10,
            // Missing jumlah_pasien_rawat_jalan
            'jumlah_pasien_pulang_paksa' => 5,
            'keterangan_penyakit_rawat_inap' => 'Demam tinggi',
            'keterangan_penyakit_rawat_jalan' => 'Batuk pilek',
        ];

        // Make POST request
        $response = $this->postJson('/patient-data/store', $data);

        // Assert the response has validation errors
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('jumlah_pasien_rawat_jalan');
    }

    /**
     * Test that numeric fields outside the range are rejected.
     * Requirements: 2.3
     */
    public function test_store_numeric_field_out_of_range()
    {
        // Create a unit
        $unit = Unit::create([
            'name' => 'IGD',
            'status' => 'active',
        ]);

        // Create a nurse user
        $user = User::create([
            'username' => 'nurse6',
            'password' => bcrypt('password123'),
            'full_name' => 'Nurse Six',
            'role' => 'Nurse',
            'unit_id' => $unit->id,
            'status' => 'active',
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Prepare data with out-of-range value
        $data = [
            'date' => date('Y-m-d'),
            'shift' => 'Pagi',
            'jumlah_pasien_rawat_inap' => 10000, // Out of range (max 9999)
            'jumlah_pasien_rawat_jalan' => 20,
            'jumlah_pasien_pulang_paksa' => 5,
            'keterangan_penyakit_rawat_inap' => 'Demam tinggi',
            'keterangan_penyakit_rawat_jalan' => 'Batuk pilek',
        ];

        // Make POST request
        $response = $this->postJson('/patient-data/store', $data);

        // Assert the response has validation errors
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('jumlah_pasien_rawat_inap');
    }

    /**
     * Test that invalid shift is rejected.
     * Requirements: 2.3
     */
    public function test_store_invalid_shift()
    {
        // Create a unit
        $unit = Unit::create([
            'name' => 'IGD',
            'status' => 'active',
        ]);

        // Create a nurse user
        $user = User::create([
            'username' => 'nurse7',
            'password' => bcrypt('password123'),
            'full_name' => 'Nurse Seven',
            'role' => 'Nurse',
            'unit_id' => $unit->id,
            'status' => 'active',
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Prepare data with invalid shift
        $data = [
            'date' => date('Y-m-d'),
            'shift' => 'InvalidShift',
            'jumlah_pasien_rawat_inap' => 10,
            'jumlah_pasien_rawat_jalan' => 20,
            'jumlah_pasien_pulang_paksa' => 5,
            'keterangan_penyakit_rawat_inap' => 'Demam tinggi',
            'keterangan_penyakit_rawat_jalan' => 'Batuk pilek',
        ];

        // Make POST request
        $response = $this->postJson('/patient-data/store', $data);

        // Assert the response has validation errors
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('shift');
    }

    /**
     * Test that duplicate entries are detected.
     * Requirements: 2.6
     */
    public function test_store_duplicate_entry_detection()
    {
        // Create a unit
        $unit = Unit::create([
            'name' => 'IGD',
            'status' => 'active',
        ]);

        // Create a nurse user
        $user = User::create([
            'username' => 'nurse8',
            'password' => bcrypt('password123'),
            'full_name' => 'Nurse Eight',
            'role' => 'Nurse',
            'unit_id' => $unit->id,
            'status' => 'active',
        ]);

        // Create an existing patient data entry
        $existingData = PatientData::create([
            'user_id' => $user->id,
            'unit_id' => $unit->id,
            'date' => date('Y-m-d'),
            'shift' => 'Pagi',
            'data' => [
                'jumlah_pasien_rawat_inap' => 10,
                'jumlah_pasien_rawat_jalan' => 20,
                'jumlah_pasien_pulang_paksa' => 5,
            ],
            'total_patients' => 35,
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Prepare data for duplicate entry
        $data = [
            'date' => date('Y-m-d'),
            'shift' => 'Pagi',
            'jumlah_pasien_rawat_inap' => 15,
            'jumlah_pasien_rawat_jalan' => 25,
            'jumlah_pasien_pulang_paksa' => 10,
            'keterangan_penyakit_rawat_inap' => 'Demam tinggi',
            'keterangan_penyakit_rawat_jalan' => 'Batuk pilek',
        ];

        // Make POST request
        $response = $this->postJson('/patient-data/store', $data);

        // Assert the response indicates duplicate
        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'action' => 'confirm_update',
        ]);
    }

    /**
     * Test that text output is generated correctly.
     * Requirements: 2.7
     */
    public function test_store_generates_text_output()
    {
        // Create a unit
        $unit = Unit::create([
            'name' => 'IGD',
            'status' => 'active',
        ]);

        // Create a nurse user
        $user = User::create([
            'username' => 'nurse9',
            'password' => bcrypt('password123'),
            'full_name' => 'Nurse Nine',
            'role' => 'Nurse',
            'unit_id' => $unit->id,
            'status' => 'active',
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Prepare valid data
        $data = [
            'date' => date('Y-m-d'),
            'shift' => 'Pagi',
            'jumlah_pasien_rawat_inap' => 10,
            'jumlah_pasien_rawat_jalan' => 20,
            'jumlah_pasien_pulang_paksa' => 5,
            'keterangan_penyakit_rawat_inap' => 'Demam tinggi',
            'keterangan_penyakit_rawat_jalan' => 'Batuk pilek',
        ];

        // Make POST request
        $response = $this->postJson('/patient-data/store', $data);

        // Assert the response includes text output
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'text_output',
        ]);

        // Assert text output contains expected information
        $textOutput = $response->json('text_output');
        $this->assertStringContainsString('DATA PASIEN', $textOutput);
        $this->assertStringContainsString('IGD', $textOutput);
        $this->assertStringContainsString('Pagi', $textOutput);
        $this->assertStringContainsString('Nurse Nine', $textOutput);
    }

    /**
     * Test that store requires authentication.
     */
    public function test_store_requires_authentication()
    {
        // Prepare data
        $data = [
            'date' => date('Y-m-d'),
            'shift' => 'Pagi',
            'jumlah_pasien_rawat_inap' => 10,
            'jumlah_pasien_rawat_jalan' => 20,
            'jumlah_pasien_pulang_paksa' => 5,
            'keterangan_penyakit_rawat_inap' => 'Demam tinggi',
            'keterangan_penyakit_rawat_jalan' => 'Batuk pilek',
        ];

        // Make POST request without authentication
        $response = $this->postJson('/patient-data/store', $data);

        // Assert the response is unauthorized
        $response->assertStatus(401);
    }
}
