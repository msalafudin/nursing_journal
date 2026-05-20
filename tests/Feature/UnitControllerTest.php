<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use App\Models\PatientData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitControllerTest extends TestCase
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
     * Test: Unit list displays all units
     * Property 31: Unit List Displays All Units
     */
    public function test_unit_list_displays_all_units()
    {
        // Create test units
        Unit::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('units.index'));

        $response->assertStatus(200);
        $response->assertViewHas('units');
        
        $units = $response->viewData('units');
        $this->assertCount(3, $units);
    }

    /**
     * Test: Valid unit name saves successfully
     * Property 32: Valid Unit Name Saves Successfully
     */
    public function test_valid_unit_name_saves_successfully()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), [
                'name' => 'IGD',
            ]);

        $response->assertRedirect(route('units.index'));
        $this->assertDatabaseHas('units', ['name' => 'IGD']);
    }

    /**
     * Test: Valid unit name with numbers and spaces
     */
    public function test_valid_unit_name_with_numbers_and_spaces()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), [
                'name' => 'Rawat Inap 1',
            ]);

        $response->assertRedirect(route('units.index'));
        $this->assertDatabaseHas('units', ['name' => 'Rawat Inap 1']);
    }

    /**
     * Test: Duplicate unit name rejected
     * Property 33: Duplicate Unit Name Rejected
     */
    public function test_duplicate_unit_name_rejected()
    {
        Unit::create(['name' => 'IGD']);

        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), [
                'name' => 'IGD',
            ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test: Duplicate unit name case-insensitive
     */
    public function test_duplicate_unit_name_case_insensitive()
    {
        Unit::create(['name' => 'IGD']);

        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), [
                'name' => 'igd',
            ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test: Unit edit updates database
     * Property 34: Unit Edit Updates Database
     */
    public function test_unit_edit_updates_database()
    {
        $unit = Unit::create(['name' => 'IGD']);

        $response = $this->actingAs($this->admin)
            ->put(route('units.update', $unit), [
                'name' => 'IGD Updated',
            ]);

        $response->assertRedirect(route('units.index'));
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'name' => 'IGD Updated']);
    }

    /**
     * Test: Invalid unit name length rejected (too short)
     * Property 38: Invalid Unit Name Length Rejected
     */
    public function test_invalid_unit_name_too_short()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), [
                'name' => 'A',
            ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test: Invalid unit name length rejected (too long)
     */
    public function test_invalid_unit_name_too_long()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), [
                'name' => str_repeat('A', 51),
            ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test: Invalid unit name with special characters
     */
    public function test_invalid_unit_name_with_special_characters()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), [
                'name' => 'IGD@#$',
            ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test: Delete unit with data shows warning
     * Property 35: Delete Unit with Data Shows Warning
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
    }

    /**
     * Test: Confirmed unit delete removes record
     * Property 36: Confirmed Unit Delete Removes Record
     */
    public function test_confirmed_unit_delete_removes_record()
    {
        $unit = Unit::create(['name' => 'IGD']);

        $response = $this->actingAs($this->admin)
            ->delete(route('units.destroy', $unit), [
                'confirmed' => '1',
            ]);

        $response->assertRedirect(route('units.index'));
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    /**
     * Test: Cancelled unit delete preserves record
     * Property 37: Cancelled Unit Delete Preserves Record
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
     * Test: Unit name must be filled
     */
    public function test_unit_name_required()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('units.store'), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test: Edit form displays current unit data
     */
    public function test_edit_form_displays_current_unit_data()
    {
        $unit = Unit::create(['name' => 'IGD']);

        $response = $this->actingAs($this->admin)
            ->get(route('units.edit', $unit));

        $response->assertStatus(200);
        $response->assertViewHas('unit', $unit);
    }

    /**
     * Test: Create form is displayed
     */
    public function test_create_form_is_displayed()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('units.create'));

        $response->assertStatus(200);
    }
}
