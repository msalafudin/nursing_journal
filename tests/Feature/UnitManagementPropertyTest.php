<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use App\Models\PatientData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UnitManagementPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user for testing
        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'active',
        ]);
    }

    /**
     * Property 31: Unit List Displays All Units
     * 
     * For any set of units in the database, the unit management page SHALL display all units 
     * with their names and status.
     * 
     * Validates: Requirements 6.1
     */
    public function test_unit_list_displays_all_units_with_names_and_status()
    {
        // Create multiple units with different statuses
        $units = [
            Unit::create(['name' => 'IGD', 'status' => 'active']),
            Unit::create(['name' => 'Rawat Inap', 'status' => 'active']),
            Unit::create(['name' => 'ICU', 'status' => 'inactive']),
            Unit::create(['name' => 'HCU', 'status' => 'active']),
        ];

        $response = $this->actingAs($this->admin)
            ->get(route('units.index'));

        $response->assertStatus(200);
        $response->assertViewHas('units');
        
        $displayedUnits = $response->viewData('units');
        $this->assertCount(4, $displayedUnits);
        
        // Verify each unit is displayed with correct name and status
        foreach ($units as $unit) {
            $response->assertSee($unit->name);
            $response->assertSee($unit->status === 'active' ? 'Aktif' : 'Nonaktif');
        }
    }

    /**
     * Property 31: Unit List Displays All Units
     * 
     * For any empty unit list, the unit management page SHALL display an empty state message.
     * 
     * Validates: Requirements 6.1
     */
    public function test_unit_list_displays_empty_state_when_no_units()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('units.index'));

        $response->assertStatus(200);
        $response->assertSee('Tidak ada unit terdaftar');
    }

    /**
     * Property 31: Unit List Displays All Units
     * 
     * For any single unit in the database, the unit management page SHALL display that unit.
     * 
     * Validates: Requirements 6.1
     */
    public function test_unit_list_displays_single_unit()
    {
        $unit = Unit::create(['name' => 'IGD', 'status' => 'active']);

        $response = $this->actingAs($this->admin)
            ->get(route('units.index'));

        $response->assertStatus(200);
        $displayedUnits = $response->viewData('units');
        $this->assertCount(1, $displayedUnits);
        $this->assertEquals($unit->id, $displayedUnits[0]->id);
    }

    /**
     * Property 31: Unit List Displays All Units
     * 
     * For any set of units, the unit list SHALL include action buttons (Edit, Delete) for each unit.
     * 
     * Validates: Requirements 6.1
     */
    public function test_unit_list_displays_action_buttons()
    {
        $unit = Unit::create(['name' => 'IGD', 'status' => 'active']);

        $response = $this->actingAs($this->admin)
            ->get(route('units.index'));

        $response->assertStatus(200);
        $response->assertSee('Edit');
        $response->assertSee('Hapus');
    }

    /**
     * Property 32: Valid Unit Name Saves Successfully
     * 
     * For any unit name with 2-50 characters containing only letters, numbers, and spaces, 
     * the system SHALL save the unit to the database.
     * 
     * Validates: Requirements 6.2
     */
    public function test_valid_unit_name_saves_successfully()
    {
        $validNames = [
            'IGD',
            'Rawat Inap',
            'Rawat Jalan',
            'Unit 1',
            'ICU Anak',
            'HCU Bedah',
            'VK',
            'A1',
            'Unit 123 ABC',
            str_repeat('A', 50), // Maximum length
        ];

        foreach ($validNames as $name) {
            $response = $this->actingAs($this->admin)
                ->post(route('units.store'), ['name' => $name]);

            $response->assertRedirect(route('units.index'));
            $this->assertDatabaseHas('units', ['name' => $name]);
        }
    }

    /**
     * Property 32: Valid Unit Name Saves Successfully
     * 
     * For any valid unit name, the system SHALL display a success notification.
     * 
     * Validates: Requirements 6.2
     */
    public function test_valid_unit_name_displays_success_notification()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), ['name' => 'IGD']);

        $response->assertRedirect(route('units.index'));
        $response->assertSessionHas('success', 'Unit berhasil ditambahkan.');
    }

    /**
     * Property 32: Valid Unit Name Saves Successfully
     * 
     * For any valid unit name, the system SHALL create a unit record with 'active' status by default.
     * 
     * Validates: Requirements 6.2
     */
    public function test_valid_unit_name_creates_active_status_by_default()
    {
        $this->actingAs($this->admin)
            ->post(route('units.store'), ['name' => 'IGD']);

        $this->assertDatabaseHas('units', [
            'name' => 'IGD',
            'status' => 'active',
        ]);
    }

    /**
     * Property 33: Duplicate Unit Name Rejected
     * 
     * For any unit name that already exists (case-insensitive comparison), the system SHALL 
     * display an error message and prevent the duplicate from being saved.
     * 
     * Validates: Requirements 6.3, 6.9
     */
    public function test_duplicate_unit_name_rejected()
    {
        Unit::create(['name' => 'IGD']);

        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), ['name' => 'IGD']);

        $response->assertSessionHasErrors('name');
        $this->assertCount(1, Unit::where('name', 'IGD')->get());
    }

    /**
     * Property 33: Duplicate Unit Name Rejected
     * 
     * For any unit name that already exists with different case, the system SHALL reject it 
     * (case-insensitive comparison).
     * 
     * Validates: Requirements 6.3, 6.9
     */
    public function test_duplicate_unit_name_case_insensitive_rejected()
    {
        Unit::create(['name' => 'IGD']);

        $testCases = ['igd', 'Igd', 'iGd', 'IGd'];

        foreach ($testCases as $name) {
            $response = $this->actingAs($this->admin)
                ->post(route('units.store'), ['name' => $name]);

            $response->assertSessionHasErrors('name');
        }

        // Verify only one unit exists
        $this->assertCount(1, Unit::all());
    }

    /**
     * Property 33: Duplicate Unit Name Rejected
     * 
     * For any duplicate unit name attempt, the system SHALL display the error message 
     * "Nama unit sudah terdaftar."
     * 
     * Validates: Requirements 6.3, 6.9
     */
    public function test_duplicate_unit_name_error_message()
    {
        Unit::create(['name' => 'IGD']);

        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), ['name' => 'IGD']);

        $response->assertSessionHasErrors('name', 'Nama unit sudah terdaftar.');
    }

    /**
     * Property 34: Unit Edit Updates Database
     * 
     * For any valid unit name change, the system SHALL update the unit in the database 
     * and display a success notification.
     * 
     * Validates: Requirements 6.4
     */
    public function test_unit_edit_updates_database()
    {
        $unit = Unit::create(['name' => 'IGD']);

        $response = $this->actingAs($this->admin)
            ->put(route('units.update', $unit), ['name' => 'IGD Updated']);

        $response->assertRedirect(route('units.index'));
        $response->assertSessionHas('success', 'Unit berhasil diperbarui.');
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'name' => 'IGD Updated']);
    }

    /**
     * Property 34: Unit Edit Updates Database
     * 
     * For any unit edit, the system SHALL preserve the unit ID and other attributes.
     * 
     * Validates: Requirements 6.4
     */
    public function test_unit_edit_preserves_unit_id_and_attributes()
    {
        $unit = Unit::create(['name' => 'IGD', 'status' => 'active']);
        $originalId = $unit->id;

        $this->actingAs($this->admin)
            ->put(route('units.update', $unit), ['name' => 'IGD Updated']);

        $updatedUnit = Unit::find($originalId);
        $this->assertEquals($originalId, $updatedUnit->id);
        $this->assertEquals('active', $updatedUnit->status);
    }

    /**
     * Property 34: Unit Edit Updates Database
     * 
     * For any unit edit with a valid name, the system SHALL allow the update.
     * 
     * Validates: Requirements 6.4
     */
    public function test_unit_edit_with_valid_names()
    {
        $unit = Unit::create(['name' => 'IGD']);

        $validNames = [
            'Rawat Inap',
            'Unit 1',
            'ICU Anak',
            'A1',
        ];

        foreach ($validNames as $name) {
            $response = $this->actingAs($this->admin)
                ->put(route('units.update', $unit), ['name' => $name]);

            $response->assertRedirect(route('units.index'));
            $this->assertDatabaseHas('units', ['id' => $unit->id, 'name' => $name]);
            
            // Refresh unit for next iteration
            $unit->refresh();
        }
    }

    /**
     * Property 35: Delete Unit with Data Shows Warning
     * 
     * For any unit with related patient data, attempting to delete SHALL display a 
     * confirmation dialog with a warning about the related data.
     * 
     * Validates: Requirements 6.5
     */
    public function test_delete_unit_with_data_shows_warning()
    {
        $unit = Unit::create(['name' => 'IGD']);
        
        // Create related patient data
        PatientData::create([
            'user_id' => $this->admin->id,
            'unit_id' => $unit->id,
            'date' => now()->toDateString(),
            'shift' => 'Pagi',
            'data' => ['test' => 'data'],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('units.delete-confirm', $unit));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['related_data_count']);
        $this->assertArrayHasKey('unit', $data);
    }

    /**
     * Property 35: Delete Unit with Data Shows Warning
     * 
     * For any unit with multiple related patient data records, the warning SHALL show 
     * the correct count.
     * 
     * Validates: Requirements 6.5
     */
    public function test_delete_unit_with_multiple_data_shows_correct_count()
    {
        $unit = Unit::create(['name' => 'IGD']);
        
        // Create multiple related patient data records
        for ($i = 0; $i < 5; $i++) {
            PatientData::create([
                'user_id' => $this->admin->id,
                'unit_id' => $unit->id,
                'date' => now()->addDays($i)->toDateString(),
                'shift' => 'Pagi',
                'data' => ['test' => 'data'],
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('units.delete-confirm', $unit));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(5, $data['related_data_count']);
    }

    /**
     * Property 35: Delete Unit with Data Shows Warning
     * 
     * For any unit without related patient data, the delete confirmation SHALL show 
     * zero related data count.
     * 
     * Validates: Requirements 6.5
     */
    public function test_delete_unit_without_data_shows_zero_count()
    {
        $unit = Unit::create(['name' => 'IGD']);

        $response = $this->actingAs($this->admin)
            ->get(route('units.delete-confirm', $unit));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['related_data_count']);
    }

    /**
     * Property 36: Confirmed Unit Delete Removes Record
     * 
     * For any confirmed unit deletion, the system SHALL remove the unit from the database 
     * and display a success notification.
     * 
     * Validates: Requirements 6.6
     */
    public function test_confirmed_unit_delete_removes_record()
    {
        $unit = Unit::create(['name' => 'IGD']);

        $response = $this->actingAs($this->admin)
            ->delete(route('units.destroy', $unit), ['confirmed' => '1']);

        $response->assertRedirect(route('units.index'));
        $response->assertSessionHas('success', 'Unit berhasil dihapus.');
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    /**
     * Property 36: Confirmed Unit Delete Removes Record
     * 
     * For any confirmed unit deletion with related data, the system SHALL remove both 
     * the unit and related patient data.
     * 
     * Validates: Requirements 6.6
     */
    public function test_confirmed_unit_delete_removes_related_data()
    {
        $unit = Unit::create(['name' => 'IGD']);
        
        // Create related patient data
        $patientData = PatientData::create([
            'user_id' => $this->admin->id,
            'unit_id' => $unit->id,
            'date' => now()->toDateString(),
            'shift' => 'Pagi',
            'data' => ['test' => 'data'],
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('units.destroy', $unit), ['confirmed' => '1']);

        $response->assertRedirect(route('units.index'));
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
        $this->assertDatabaseMissing('patient_data', ['id' => $patientData->id]);
    }

    /**
     * Property 36: Confirmed Unit Delete Removes Record
     * 
     * For any confirmed unit deletion, the system SHALL delete only the specified unit.
     * 
     * Validates: Requirements 6.6
     */
    public function test_confirmed_unit_delete_removes_only_specified_unit()
    {
        $unit1 = Unit::create(['name' => 'IGD']);
        $unit2 = Unit::create(['name' => 'Rawat Inap']);

        $this->actingAs($this->admin)
            ->delete(route('units.destroy', $unit1), ['confirmed' => '1']);

        $this->assertDatabaseMissing('units', ['id' => $unit1->id]);
        $this->assertDatabaseHas('units', ['id' => $unit2->id]);
    }

    /**
     * Property 37: Cancelled Unit Delete Preserves Record
     * 
     * For any cancelled unit deletion, the system SHALL close the dialog without removing 
     * the unit, and the unit SHALL remain in the database.
     * 
     * Validates: Requirements 6.7
     */
    public function test_cancelled_unit_delete_preserves_record()
    {
        $unit = Unit::create(['name' => 'IGD']);
        
        // Create related patient data so confirmation is required
        PatientData::create([
            'user_id' => $this->admin->id,
            'unit_id' => $unit->id,
            'date' => now()->toDateString(),
            'shift' => 'Pagi',
            'data' => ['test' => 'data'],
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('units.destroy', $unit));

        // Should return 422 when not confirmed
        $response->assertStatus(422);
        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }

    /**
     * Property 37: Cancelled Unit Delete Preserves Record
     * 
     * For any cancelled unit deletion, the system SHALL return a response indicating 
     * confirmation is required.
     * 
     * Validates: Requirements 6.7
     */
    public function test_cancelled_unit_delete_returns_confirmation_required()
    {
        $unit = Unit::create(['name' => 'IGD']);
        
        // Create related patient data
        PatientData::create([
            'user_id' => $this->admin->id,
            'unit_id' => $unit->id,
            'date' => now()->toDateString(),
            'shift' => 'Pagi',
            'data' => ['test' => 'data'],
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('units.destroy', $unit));

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertTrue($data['requires_confirmation']);
        $this->assertGreaterThan(0, $data['related_data_count']);
    }

    /**
     * Property 37: Cancelled Unit Delete Preserves Record
     * 
     * For any cancelled unit deletion, related patient data SHALL also be preserved.
     * 
     * Validates: Requirements 6.7
     */
    public function test_cancelled_unit_delete_preserves_related_data()
    {
        $unit = Unit::create(['name' => 'IGD']);
        
        $patientData = PatientData::create([
            'user_id' => $this->admin->id,
            'unit_id' => $unit->id,
            'date' => now()->toDateString(),
            'shift' => 'Pagi',
            'data' => ['test' => 'data'],
        ]);

        $this->actingAs($this->admin)
            ->delete(route('units.destroy', $unit));

        $this->assertDatabaseHas('patient_data', ['id' => $patientData->id]);
    }

    /**
     * Property 38: Invalid Unit Name Length Rejected
     * 
     * For any unit name with fewer than 2 characters or more than 50 characters, 
     * the system SHALL display a validation error.
     * 
     * Validates: Requirements 6.8
     */
    public function test_invalid_unit_name_length_rejected()
    {
        // Test too short
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), ['name' => 'A']);

        $response->assertSessionHasErrors('name');

        // Test too long
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), ['name' => str_repeat('A', 51)]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Property 38: Invalid Unit Name Length Rejected
     * 
     * For any unit name with invalid length, the system SHALL display the error message 
     * "Nama unit minimal 2 karakter." or "Nama unit maksimal 50 karakter."
     * 
     * Validates: Requirements 6.8
     */
    public function test_invalid_unit_name_length_error_messages()
    {
        // Test too short
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), ['name' => 'A']);

        $response->assertSessionHasErrors('name', 'Nama unit minimal 2 karakter.');

        // Test too long
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), ['name' => str_repeat('A', 51)]);

        $response->assertSessionHasErrors('name', 'Nama unit maksimal 50 karakter.');
    }

    /**
     * Property 38: Invalid Unit Name Length Rejected
     * 
     * For any unit name at the boundary (exactly 2 and exactly 50 characters), 
     * the system SHALL accept them.
     * 
     * Validates: Requirements 6.8
     */
    public function test_unit_name_boundary_lengths_accepted()
    {
        // Test exactly 2 characters
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), ['name' => 'AB']);

        $response->assertRedirect(route('units.index'));
        $this->assertDatabaseHas('units', ['name' => 'AB']);

        // Test exactly 50 characters
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), ['name' => str_repeat('C', 50)]);

        $response->assertRedirect(route('units.index'));
        $this->assertDatabaseHas('units', ['name' => str_repeat('C', 50)]);
    }

    /**
     * Property 38: Invalid Unit Name Length Rejected
     * 
     * For any unit name with special characters, the system SHALL display a validation error.
     * 
     * Validates: Requirements 6.8
     */
    public function test_invalid_unit_name_with_special_characters_rejected()
    {
        $invalidNames = [
            'IGD@#$',
            'Unit-1',
            'Unit_2',
            'Unit.3',
            'Unit/4',
            'Unit\\5',
            'Unit(6)',
            'Unit[7]',
            'Unit{8}',
            'Unit!9',
            'Unit?0',
            'Unit,1',
            'Unit;2',
            'Unit:3',
            'Unit"4',
            "Unit'5",
        ];

        foreach ($invalidNames as $name) {
            $response = $this->actingAs($this->admin)
                ->post(route('units.store'), ['name' => $name]);

            $response->assertSessionHasErrors('name', 'Nama unit hanya boleh mengandung huruf, angka, dan spasi.');
        }
    }

    /**
     * Property 38: Invalid Unit Name Length Rejected
     * 
     * For any unit name that is empty, the system SHALL display a validation error.
     * 
     * Validates: Requirements 6.8
     */
    public function test_empty_unit_name_rejected()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), ['name' => '']);

        $response->assertSessionHasErrors('name', 'Nama unit harus diisi.');
    }

    /**
     * Property 38: Invalid Unit Name Length Rejected
     * 
     * For any unit name with only spaces, the system SHALL reject it.
     * 
     * Validates: Requirements 6.8
     */
    public function test_unit_name_with_only_spaces_rejected()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), ['name' => '   ']);

        // This should be rejected as it's less than 2 non-space characters
        $response->assertSessionHasErrors('name');
    }

    /**
     * Property 31-38: Unit Management Requires Authentication
     * 
     * For any unauthenticated user, accessing unit management pages SHALL redirect to login.
     * 
     * Validates: Requirements 6.1-6.9
     */
    public function test_unit_management_requires_authentication()
    {
        $unit = Unit::create(['name' => 'IGD']);

        // Test index
        $response = $this->get(route('units.index'));
        $response->assertRedirect('/login');

        // Test create
        $response = $this->get(route('units.create'));
        $response->assertRedirect('/login');

        // Test store
        $response = $this->post(route('units.store'), ['name' => 'Test']);
        $response->assertRedirect('/login');

        // Test edit
        $response = $this->get(route('units.edit', $unit));
        $response->assertRedirect('/login');

        // Test update
        $response = $this->put(route('units.update', $unit), ['name' => 'Test']);
        $response->assertRedirect('/login');

        // Test delete
        $response = $this->delete(route('units.destroy', $unit));
        $response->assertRedirect('/login');
    }
}
