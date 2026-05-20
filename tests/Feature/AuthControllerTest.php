<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Unit;
use App\Models\LoginAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test unit
        $this->unit = Unit::create([
            'name' => 'IGD',
            'status' => 'active',
        ]);

        // Create a test user
        $this->user = User::create([
            'username' => 'testuser',
            'password' => Hash::make('password123'),
            'full_name' => 'Test User',
            'role' => 'Nurse',
            'unit_id' => $this->unit->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test successful login with valid credentials.
     */
    public function test_successful_login_with_valid_credentials()
    {
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);
    }

    /**
     * Test login with invalid username returns generic error.
     */
    public function test_login_with_invalid_username_returns_generic_error()
    {
        $response = $this->post('/login', [
            'username' => 'invaliduser',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * Test login with invalid password returns generic error.
     */
    public function test_login_with_invalid_password_returns_generic_error()
    {
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * Test session contains required user data after login.
     */
    public function test_session_contains_required_user_data_after_login()
    {
        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $this->assertEquals(session('user_id'), $this->user->id);
        $this->assertEquals(session('role'), 'Nurse');
        $this->assertEquals(session('unit_id'), $this->unit->id);
        $this->assertEquals(session('username'), 'testuser');
        $this->assertEquals(session('full_name'), 'Test User');
    }

    /**
     * Test login with inactive user is prevented.
     */
    public function test_login_with_inactive_user_is_prevented()
    {
        $this->user->update(['status' => 'inactive']);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * Test rate limiting blocks after 5 failed attempts.
     */
    public function test_rate_limiting_blocks_after_5_failed_attempts()
    {
        // Make 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'testuser',
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt should be blocked
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');
    }

    /**
     * Test failed login attempts are recorded.
     */
    public function test_failed_login_attempts_are_recorded()
    {
        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $this->assertDatabaseHas('login_attempts', [
            'username' => 'testuser',
            'success' => false,
        ]);
    }

    /**
     * Test successful login clears failed attempts.
     */
    public function test_successful_login_clears_failed_attempts()
    {
        // Make 2 failed attempts
        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);
        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        // Verify failed attempts exist
        $this->assertDatabaseCount('login_attempts', 2);

        // Login successfully
        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        // Verify failed attempts are cleared
        $failedAttempts = LoginAttempt::where('username', 'testuser')
            ->where('success', false)
            ->count();
        $this->assertEquals(0, $failedAttempts);
    }

    /**
     * Test successful login is recorded.
     */
    public function test_successful_login_is_recorded()
    {
        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('login_attempts', [
            'username' => 'testuser',
            'success' => true,
        ]);
    }

    /**
     * Test logout clears session data.
     */
    public function test_logout_clears_session_data()
    {
        // Login first
        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($this->user);

        // Logout
        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Test logout redirects to login page.
     */
    public function test_logout_redirects_to_login_page()
    {
        // Login first
        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        // Logout
        $response = $this->post('/logout');

        $response->assertRedirect('/login');
    }

    /**
     * Test login form is displayed.
     */
    public function test_login_form_is_displayed()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * Test last login time is updated.
     */
    public function test_last_login_time_is_updated()
    {
        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $user = User::find($this->user->id);
        $this->assertNotNull($user->last_login);
    }

    /**
     * Test admin user can login.
     */
    public function test_admin_user_can_login()
    {
        $admin = User::create([
            'username' => 'admin',
            'password' => Hash::make('adminpass123'),
            'full_name' => 'Admin User',
            'role' => 'Admin',
            'unit_id' => null,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'adminpass123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->assertEquals(session('role'), 'Admin');
    }

    /**
     * Test rate limiting resets after 15 minutes.
     */
    public function test_rate_limiting_resets_after_15_minutes()
    {
        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'testuser',
                'password' => 'wrongpassword',
            ]);
        }

        // Verify account is locked
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');

        // Simulate 15 minutes passing
        $fifteenMinutesAgo = Carbon::now()->subMinutes(15)->subSecond();
        LoginAttempt::where('username', 'testuser')
            ->where('success', false)
            ->update(['attempted_at' => $fifteenMinutesAgo]);

        // Now login should work
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);
    }

    /**
     * Test login requires username and password.
     */
    public function test_login_requires_username_and_password()
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors(['username', 'password']);
    }
}

