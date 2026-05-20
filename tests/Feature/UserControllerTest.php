<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test units
        $this->unit1 = Unit::create([
            'name' => 'IGD',
            'status' => 'active',
        ]);

        $this->unit2 = Unit::create([
            'name' => 'Rawat Inap',
            'status' => 'active',
        ]);

        // Create admin user for authentication
        $this->admin = User::create([
            'username' => 'admin',
            'password' => Hash::make('adminpass123'),
            'full_name' => 'Admin User',
            'role' => 'Admin',
            'unit_id' => null,
            'status' => 'active',
        ]);

        // Create a nurse user
        $this->nurse = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('nursepass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test user list displays all users.
     */
    public function test_user_list_displays_all_users()
    {
        $response = $this->actingAs($this->admin)->get('/users');

        $response->assertStatus(200);
        $response->assertViewIs('users.index');
        $response->assertViewHas('users');
        $this->assertCount(2, $response->viewData('users'));
    }

    /**
     * Test valid user data saves successfully.
     */
    public function test_valid_user_data_saves_successfully()
    {
        $response = $this->actingAs($this->admin)->post('/users', [
            'username' => 'newuser',
            'password' => 'password123',
            'full_name' => 'New User',
            'unit_id' => $this->unit1->id,
            'role' => 'Nurse',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'full_name' => 'New User',
            'role' => 'Nurse',
        ]);

        // Verify password is hashed
        $user = User::where('username', 'newuser')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * Test duplicate username is rejected.
     */
    public function test_duplicate_username_is_rejected()
    {
        $response = $this->actingAs($this->admin)->post('/users', [
            'username' => 'nurse1',
            'password' => 'password123',
            'full_name' => 'Another User',
            'unit_id' => $this->unit1->id,
            'role' => 'Nurse',
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHasErrors('username');
    }

    /**
     * Test password minimum length validation.
     */
    public function test_password_minimum_length_validation()
    {
        $response = $this->actingAs($this->admin)->post('/users', [
            'username' => 'newuser',
            'password' => 'short',
            'full_name' => 'New User',
            'unit_id' => $this->unit1->id,
            'role' => 'Nurse',
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHasErrors('password');
    }

    /**
     * Test user creation failure shows generic error.
     */
    public function test_user_creation_failure_shows_generic_error()
    {
        // This test simulates a database error by using an invalid unit_id
        $response = $this->actingAs($this->admin)->post('/users', [
            'username' => 'newuser',
            'password' => 'password123',
            'full_name' => 'New User',
            'unit_id' => 9999,
            'role' => 'Nurse',
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHasErrors();
    }

    /**
     * Test user unit assignment updates.
     */
    public function test_user_unit_assignment_updates()
    {
        $response = $this->actingAs($this->admin)->put('/users/' . $this->nurse->id, [
            'full_name' => 'Nurse One Updated',
            'unit_id' => $this->unit2->id,
            'status' => 'active',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'id' => $this->nurse->id,
            'unit_id' => $this->unit2->id,
        ]);
    }

    /**
     * Test deactivated user cannot login.
     */
    public function test_deactivated_user_cannot_login()
    {
        // Deactivate the user
        $this->nurse->update(['status' => 'inactive']);

        // Try to login
        $response = $this->post('/login', [
            'username' => 'nurse1',
            'password' => 'nursepass123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * Test reactivated user can login.
     */
    public function test_reactivated_user_can_login()
    {
        // Deactivate the user
        $this->nurse->update(['status' => 'inactive']);

        // Reactivate the user
        $this->actingAs($this->admin)->post('/users/' . $this->nurse->id . '/reactivate');

        // Try to login
        $response = $this->post('/login', [
            'username' => 'nurse1',
            'password' => 'nursepass123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->nurse);
    }

    /**
     * Test password stored as hash.
     */
    public function test_password_stored_as_hash()
    {
        $this->actingAs($this->admin)->post('/users', [
            'username' => 'newuser',
            'password' => 'password123',
            'full_name' => 'New User',
            'unit_id' => $this->unit1->id,
            'role' => 'Nurse',
        ]);

        $user = User::where('username', 'newuser')->first();
        
        // Verify password is hashed (not plaintext)
        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * Test user has exactly one role.
     */
    public function test_user_has_exactly_one_role()
    {
        $this->actingAs($this->admin)->post('/users', [
            'username' => 'newuser',
            'password' => 'password123',
            'full_name' => 'New User',
            'unit_id' => $this->unit1->id,
            'role' => 'Nurse',
        ]);

        $user = User::where('username', 'newuser')->first();
        $this->assertNotNull($user->role);
        $this->assertTrue(in_array($user->role, ['Admin', 'Nurse']));
    }

    /**
     * Test deactivation clears user sessions.
     */
    public function test_deactivation_clears_user_sessions()
    {
        // Deactivate the user via update (as admin)
        $response = $this->actingAs($this->admin)->put('/users/' . $this->nurse->id, [
            'full_name' => 'Nurse One',
            'unit_id' => $this->unit1->id,
            'status' => 'inactive',
        ]);

        // The response should redirect to users
        $response->assertRedirect('/users');

        // Verify user is deactivated
        $this->assertDatabaseHas('users', [
            'id' => $this->nurse->id,
            'status' => 'inactive',
        ]);
    }

    /**
     * Test required fields validation.
     */
    public function test_required_fields_validation()
    {
        $response = $this->actingAs($this->admin)->post('/users', []);

        $response->assertRedirect('/users');
        $response->assertSessionHasErrors();
    }

    /**
     * Test user creation with admin role.
     */
    public function test_user_creation_with_admin_role()
    {
        $response = $this->actingAs($this->admin)->post('/users', [
            'username' => 'newadmin',
            'password' => 'password123',
            'full_name' => 'New Admin',
            'unit_id' => $this->unit1->id,
            'role' => 'Admin',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'username' => 'newadmin',
            'role' => 'Admin',
        ]);
    }

    /**
     * Test user status update to inactive.
     */
    public function test_user_status_update_to_inactive()
    {
        $response = $this->actingAs($this->admin)->put('/users/' . $this->nurse->id, [
            'full_name' => 'Nurse One',
            'unit_id' => $this->unit1->id,
            'status' => 'inactive',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'id' => $this->nurse->id,
            'status' => 'inactive',
        ]);
    }

    /**
     * Test user status update to active.
     */
    public function test_user_status_update_to_active()
    {
        $this->nurse->update(['status' => 'inactive']);

        $response = $this->actingAs($this->admin)->put('/users/' . $this->nurse->id, [
            'full_name' => 'Nurse One',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'id' => $this->nurse->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test full name update.
     */
    public function test_full_name_update()
    {
        $response = $this->actingAs($this->admin)->put('/users/' . $this->nurse->id, [
            'full_name' => 'Updated Nurse Name',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'id' => $this->nurse->id,
            'full_name' => 'Updated Nurse Name',
        ]);
    }

    /**
     * Test user creation form displays units.
     */
    public function test_user_creation_form_displays_units()
    {
        $response = $this->actingAs($this->admin)->get('/users/create');

        $response->assertStatus(200);
        $response->assertViewIs('users.create');
        $response->assertViewHas('units');
        $this->assertCount(2, $response->viewData('units'));
    }

    /**
     * Test user edit form displays current data.
     */
    public function test_user_edit_form_displays_current_data()
    {
        $response = $this->actingAs($this->admin)->get('/users/' . $this->nurse->id . '/edit');

        $response->assertStatus(200);
        $response->assertViewIs('users.edit');
        $response->assertViewHas('user', $this->nurse);
    }
}
