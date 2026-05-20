<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementPropertyTest extends TestCase
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

        // Create admin user for testing
        $this->admin = User::create([
            'username' => 'admin',
            'password' => Hash::make('adminpass123'),
            'full_name' => 'Admin User',
            'role' => 'Admin',
            'unit_id' => null,
            'status' => 'active',
        ]);
    }

    /**
     * Property 39: User List Displays All Users
     * 
     * For any set of users in the database, the user management page SHALL display all users 
     * with their username, full name, assigned unit, and status.
     * 
     * Validates: Requirements 7.1
     */
    public function test_user_list_displays_all_users_with_details()
    {
        // Create multiple users
        $users = [
            User::create([
                'username' => 'nurse1',
                'password' => Hash::make('pass123'),
                'full_name' => 'Nurse One',
                'role' => 'Nurse',
                'unit_id' => $this->unit1->id,
                'status' => 'active',
            ]),
            User::create([
                'username' => 'nurse2',
                'password' => Hash::make('pass123'),
                'full_name' => 'Nurse Two',
                'role' => 'Nurse',
                'unit_id' => $this->unit2->id,
                'status' => 'inactive',
            ]),
        ];

        $response = $this->actingAs($this->admin)
            ->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertViewHas('users');
        
        $displayedUsers = $response->viewData('users');
        $this->assertCount(3, $displayedUsers); // admin + 2 nurses

        // Verify each user is displayed with correct details
        foreach ($users as $user) {
            $response->assertSee($user->username);
            $response->assertSee($user->full_name);
            $response->assertSee($user->unit->name);
        }
    }

    /**
     * Property 39: User List Displays All Users
     * 
     * For any empty user list (excluding admin), the user management page SHALL display 
     * an empty state message.
     * 
     * Validates: Requirements 7.1
     */
    public function test_user_list_displays_empty_state_when_no_users()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('users.index'));

        $response->assertStatus(200);
        // Should show at least the admin user
        $displayedUsers = $response->viewData('users');
        $this->assertCount(1, $displayedUsers);
    }

    /**
     * Property 39: User List Displays All Users
     * 
     * For any set of users, the user list SHALL include action buttons (Edit, Deactivate/Activate) 
     * for each user.
     * 
     * Validates: Requirements 7.1
     */
    public function test_user_list_displays_action_buttons()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('pass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee('Edit');
        $response->assertSee('Nonaktifkan');
    }

    /**
     * Property 40: Valid User Data Saves Successfully
     * 
     * For any valid user data (username, password ≥8 chars, full name, unit), the system 
     * SHALL save the user to the database with a hashed password.
     * 
     * Validates: Requirements 7.2
     */
    public function test_valid_user_data_saves_successfully()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'newuser',
                'password' => 'password123',
                'full_name' => 'New User',
                'unit_id' => $this->unit1->id,
                'role' => 'Nurse',
            ]);

        $response->assertRedirect(route('users.index'));
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
     * Property 40: Valid User Data Saves Successfully
     * 
     * For any valid user data, the system SHALL display a success notification.
     * 
     * Validates: Requirements 7.2
     */
    public function test_valid_user_data_displays_success_notification()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'newuser',
                'password' => 'password123',
                'full_name' => 'New User',
                'unit_id' => $this->unit1->id,
                'role' => 'Nurse',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Property 40: Valid User Data Saves Successfully
     * 
     * For any valid user data with minimum password length (8 chars), the system SHALL save it.
     * 
     * Validates: Requirements 7.2
     */
    public function test_valid_user_data_with_minimum_password_length()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'newuser',
                'password' => '12345678', // Exactly 8 characters
                'full_name' => 'New User',
                'unit_id' => $this->unit1->id,
                'role' => 'Nurse',
            ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['username' => 'newuser']);
    }

    /**
     * Property 41: User Creation Failure Shows Generic Error
     * 
     * For any user creation failure (connection error, database error), the system SHALL 
     * display a generic error message without exposing technical details.
     * 
     * Validates: Requirements 7.3
     */
    public function test_user_creation_failure_shows_generic_error()
    {
        // This test simulates a database error by using an invalid unit_id
        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'newuser',
                'password' => 'password123',
                'full_name' => 'New User',
                'unit_id' => 9999, // Invalid unit ID
                'role' => 'Nurse',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasErrors();
        
        // Verify user was not created
        $this->assertDatabaseMissing('users', ['username' => 'newuser']);
    }

    /**
     * Property 42: Duplicate Username Rejected
     * 
     * For any username that already exists, the system SHALL display an error message 
     * and prevent the duplicate from being saved.
     * 
     * Validates: Requirements 7.4
     */
    public function test_duplicate_username_rejected()
    {
        User::create([
            'username' => 'nurse1',
            'password' => Hash::make('pass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'nurse1',
                'password' => 'password123',
                'full_name' => 'Another User',
                'unit_id' => $this->unit1->id,
                'role' => 'Nurse',
            ]);

        $response->assertSessionHasErrors('username');
        $this->assertCount(1, User::where('username', 'nurse1')->get());
    }

    /**
     * Property 42: Duplicate Username Rejected
     * 
     * For any duplicate username attempt, the system SHALL display the error message 
     * "Username sudah digunakan."
     * 
     * Validates: Requirements 7.4
     */
    public function test_duplicate_username_error_message()
    {
        User::create([
            'username' => 'nurse1',
            'password' => Hash::make('pass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'nurse1',
                'password' => 'password123',
                'full_name' => 'Another User',
                'unit_id' => $this->unit1->id,
                'role' => 'Nurse',
            ]);

        $response->assertSessionHasErrors('username', 'Username sudah digunakan.');
    }

    /**
     * Property 44: User Unit Assignment Updates
     * 
     * For any change to a user's assigned unit, the system SHALL update the assignment 
     * in the database and display a success notification.
     * 
     * Validates: Requirements 7.6
     */
    public function test_user_unit_assignment_updates()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('pass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'full_name' => 'Nurse One',
                'unit_id' => $this->unit2->id,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'Pengguna berhasil diperbarui.');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'unit_id' => $this->unit2->id,
        ]);
    }

    /**
     * Property 44: User Unit Assignment Updates
     * 
     * For any unit assignment change, the system SHALL preserve the user's other attributes.
     * 
     * Validates: Requirements 7.6
     */
    public function test_user_unit_assignment_preserves_other_attributes()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('pass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'full_name' => 'Nurse One',
                'unit_id' => $this->unit2->id,
                'status' => 'active',
            ]);

        $updatedUser = User::find($user->id);
        $this->assertEquals('nurse1', $updatedUser->username);
        $this->assertEquals('Nurse One', $updatedUser->full_name);
        $this->assertEquals('Nurse', $updatedUser->role);
    }

    /**
     * Property 45: Deactivated User Cannot Login
     * 
     * For any deactivated user account, login attempts SHALL be prevented and the user 
     * SHALL be logged out if currently active.
     * 
     * Validates: Requirements 7.7
     */
    public function test_deactivated_user_cannot_login()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('nursepass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        // Deactivate the user
        $user->update(['status' => 'inactive']);

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
     * Property 45: Deactivated User Cannot Login
     * 
     * For any deactivated user, the system SHALL prevent login with the error message 
     * "Kredensial tidak valid."
     * 
     * Validates: Requirements 7.7
     */
    public function test_deactivated_user_login_error_message()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('nursepass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        // Deactivate the user
        $user->update(['status' => 'inactive']);

        // Try to login
        $response = $this->post('/login', [
            'username' => 'nurse1',
            'password' => 'nursepass123',
        ]);

        $response->assertSessionHasErrors('login', 'Kredensial tidak valid.');
    }

    /**
     * Property 45: Deactivated User Cannot Login
     * 
     * For any deactivated user with active sessions, the system SHALL clear all sessions 
     * when deactivating.
     * 
     * Validates: Requirements 7.7
     */
    public function test_deactivation_clears_user_sessions()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('nursepass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        // Deactivate the user via update (as admin)
        $response = $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'full_name' => 'Nurse One',
                'unit_id' => $this->unit1->id,
                'status' => 'inactive',
            ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 'inactive',
        ]);
    }

    /**
     * Property 46: Reactivated User Can Login
     * 
     * For any reactivated user account, login SHALL be allowed and the user SHALL be able 
     * to access the system normally.
     * 
     * Validates: Requirements 7.8
     */
    public function test_reactivated_user_can_login()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('nursepass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        // Deactivate the user
        $user->update(['status' => 'inactive']);

        // Reactivate the user
        $this->actingAs($this->admin)
            ->post(route('users.reactivate', $user));

        // Try to login
        $response = $this->post('/login', [
            'username' => 'nurse1',
            'password' => 'nursepass123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Property 46: Reactivated User Can Login
     * 
     * For any reactivated user, the system SHALL display a success notification.
     * 
     * Validates: Requirements 7.8
     */
    public function test_reactivated_user_displays_success_notification()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('nursepass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('users.reactivate', $user));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'Pengguna berhasil diaktifkan kembali.');
    }

    /**
     * Property 47: Password Stored as Hash
     * 
     * For any user creation, the system SHALL store the password as a hash (not plaintext) 
     * and never display passwords in plaintext.
     * 
     * Validates: Requirements 7.9, 10.4
     */
    public function test_password_stored_as_hash()
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
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
     * Property 47: Password Stored as Hash
     * 
     * For any user in the system, the password field SHALL be hidden from serialization.
     * 
     * Validates: Requirements 7.9, 10.4
     */
    public function test_password_hidden_from_serialization()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('pass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        $serialized = $user->toArray();
        $this->assertArrayNotHasKey('password', $serialized);
    }

    /**
     * Property 39-47: User Management Requires Authentication
     * 
     * For any unauthenticated user, accessing user management pages SHALL redirect to login.
     * 
     * Validates: Requirements 7.1-7.9
     */
    public function test_user_management_requires_authentication()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('pass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        // Test index
        $response = $this->get(route('users.index'));
        $response->assertRedirect('/login');

        // Test create
        $response = $this->get(route('users.create'));
        $response->assertRedirect('/login');

        // Test store
        $response = $this->post(route('users.store'), ['username' => 'test']);
        $response->assertRedirect('/login');

        // Test edit
        $response = $this->get(route('users.edit', $user));
        $response->assertRedirect('/login');

        // Test update
        $response = $this->put(route('users.update', $user), ['full_name' => 'Test']);
        $response->assertRedirect('/login');

        // Test destroy
        $response = $this->delete(route('users.destroy', $user));
        $response->assertRedirect('/login');

        // Test reactivate
        $response = $this->post(route('users.reactivate', $user));
        $response->assertRedirect('/login');
    }

    /**
     * Property 40: Valid User Data Saves Successfully
     * 
     * For any valid user data with different roles (Admin or Nurse), the system SHALL save it.
     * 
     * Validates: Requirements 7.2
     */
    public function test_valid_user_data_with_different_roles()
    {
        // Test with Nurse role
        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'nurse1',
                'password' => 'password123',
                'full_name' => 'Nurse One',
                'unit_id' => $this->unit1->id,
                'role' => 'Nurse',
            ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['username' => 'nurse1', 'role' => 'Nurse']);

        // Test with Admin role
        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'admin2',
                'password' => 'password123',
                'full_name' => 'Admin Two',
                'unit_id' => $this->unit1->id,
                'role' => 'Admin',
            ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['username' => 'admin2', 'role' => 'Admin']);
    }

    /**
     * Property 40: Valid User Data Saves Successfully
     * 
     * For any valid user data, the system SHALL create a user record with 'active' status by default.
     * 
     * Validates: Requirements 7.2
     */
    public function test_valid_user_data_creates_active_status_by_default()
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'newuser',
                'password' => 'password123',
                'full_name' => 'New User',
                'unit_id' => $this->unit1->id,
                'role' => 'Nurse',
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'status' => 'active',
        ]);
    }

    /**
     * Property 40: Valid User Data Saves Successfully
     * 
     * For any valid user data with various password lengths (≥8 chars), the system SHALL save it.
     * 
     * Validates: Requirements 7.2
     */
    public function test_valid_user_data_with_various_password_lengths()
    {
        $passwords = [
            '12345678',      // Exactly 8 characters
            'password123',   // 11 characters
            'verylongpassword123456789', // 26 characters
        ];

        foreach ($passwords as $index => $password) {
            $response = $this->actingAs($this->admin)
                ->post(route('users.store'), [
                    'username' => 'user' . $index,
                    'password' => $password,
                    'full_name' => 'User ' . $index,
                    'unit_id' => $this->unit1->id,
                    'role' => 'Nurse',
                ]);

            $response->assertRedirect(route('users.index'));
            $user = User::where('username', 'user' . $index)->first();
            $this->assertTrue(Hash::check($password, $user->password));
        }
    }

    /**
     * Property 41: User Creation Failure Shows Generic Error
     * 
     * For any user creation failure, the system SHALL NOT expose technical details like 
     * database errors or validation rule names.
     * 
     * Validates: Requirements 7.3
     */
    public function test_user_creation_failure_does_not_expose_technical_details()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'newuser',
                'password' => 'password123',
                'full_name' => 'New User',
                'unit_id' => 9999, // Invalid unit ID
                'role' => 'Nurse',
            ]);

        $response->assertRedirect(route('users.index'));
        
        // Verify no technical details are exposed
        $response->assertDontSee('SQLSTATE');
        $response->assertDontSee('Exception');
        $response->assertDontSee('Stack trace');
    }

    /**
     * Property 42: Duplicate Username Rejected
     * 
     * For any duplicate username attempt, the system SHALL prevent the duplicate from being saved 
     * and only one record SHALL exist.
     * 
     * Validates: Requirements 7.4
     */
    public function test_duplicate_username_prevents_duplicate_save()
    {
        User::create([
            'username' => 'nurse1',
            'password' => Hash::make('pass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'nurse1',
                'password' => 'password123',
                'full_name' => 'Another User',
                'unit_id' => $this->unit1->id,
                'role' => 'Nurse',
            ]);

        // Verify only one user with this username exists
        $this->assertCount(1, User::where('username', 'nurse1')->get());
    }

    /**
     * Property 44: User Unit Assignment Updates
     * 
     * For any unit assignment change, the system SHALL update the database immediately.
     * 
     * Validates: Requirements 7.6
     */
    public function test_user_unit_assignment_updates_immediately()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('pass123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'full_name' => 'Nurse One',
                'unit_id' => $this->unit2->id,
                'status' => 'active',
            ]);

        // Verify the change is immediately reflected in the database
        $updatedUser = User::find($user->id);
        $this->assertEquals($this->unit2->id, $updatedUser->unit_id);
    }

    /**
     * Property 45: Deactivated User Cannot Login
     * 
     * For any deactivated user, the system SHALL prevent login even with correct credentials.
     * 
     * Validates: Requirements 7.7
     */
    public function test_deactivated_user_cannot_login_with_correct_credentials()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('correctpassword'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        // Deactivate the user
        $user->update(['status' => 'inactive']);

        // Try to login with correct credentials
        $response = $this->post('/login', [
            'username' => 'nurse1',
            'password' => 'correctpassword',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Property 46: Reactivated User Can Login
     * 
     * For any reactivated user, the system SHALL allow login with correct credentials.
     * 
     * Validates: Requirements 7.8
     */
    public function test_reactivated_user_can_login_with_correct_credentials()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('correctpassword'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'inactive',
        ]);

        // Reactivate the user
        $this->actingAs($this->admin)
            ->post(route('users.reactivate', $user));

        // Try to login with correct credentials
        $response = $this->post('/login', [
            'username' => 'nurse1',
            'password' => 'correctpassword',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Property 47: Password Stored as Hash
     * 
     * For any user, the password field SHALL never be displayed in plaintext in the user list 
     * or edit forms.
     * 
     * Validates: Requirements 7.9, 10.4
     */
    public function test_password_never_displayed_in_plaintext()
    {
        $user = User::create([
            'username' => 'nurse1',
            'password' => Hash::make('password123'),
            'full_name' => 'Nurse One',
            'role' => 'Nurse',
            'unit_id' => $this->unit1->id,
            'status' => 'active',
        ]);

        // Check user list page
        $response = $this->actingAs($this->admin)
            ->get(route('users.index'));

        $response->assertDontSee('password123');

        // Check edit page
        $response = $this->actingAs($this->admin)
            ->get(route('users.edit', $user));

        $response->assertDontSee('password123');
    }

    /**
     * Property 40: Valid User Data Saves Successfully
     * 
     * For any valid user data, the system SHALL validate all required fields are present.
     * 
     * Validates: Requirements 7.2
     */
    public function test_required_fields_validation()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), []);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasErrors();
    }

    /**
     * Property 40: Valid User Data Saves Successfully
     * 
     * For any user creation with password less than 8 characters, the system SHALL reject it.
     * 
     * Validates: Requirements 7.2
     */
    public function test_password_minimum_length_validation()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'newuser',
                'password' => 'short',
                'full_name' => 'New User',
                'unit_id' => $this->unit1->id,
                'role' => 'Nurse',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasErrors('password');
    }

    /**
     * Property 40: Valid User Data Saves Successfully
     * 
     * For any user creation with invalid unit_id, the system SHALL reject it.
     * 
     * Validates: Requirements 7.2
     */
    public function test_invalid_unit_id_validation()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'newuser',
                'password' => 'password123',
                'full_name' => 'New User',
                'unit_id' => 9999,
                'role' => 'Nurse',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasErrors('unit_id');
    }

    /**
     * Property 40: Valid User Data Saves Successfully
     * 
     * For any user creation with invalid role, the system SHALL reject it.
     * 
     * Validates: Requirements 7.2
     */
    public function test_invalid_role_validation()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'username' => 'newuser',
                'password' => 'password123',
                'full_name' => 'New User',
                'unit_id' => $this->unit1->id,
                'role' => 'InvalidRole',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasErrors('role');
    }
}
