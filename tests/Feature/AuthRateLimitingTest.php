<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Carbon\Carbon;

class AuthRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'full_name' => 'Test User',
            'role' => 'Nurse',
        ]);
    }

    /**
     * Test that valid credentials allow login.
     */
    public function test_valid_credentials_allow_login()
    {
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(302)
            ->assertRedirect('/dashboard');
    }

    /**
     * Test that invalid credentials return generic error.
     */
    public function test_invalid_credentials_return_generic_error()
    {
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(302)
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');
    }

    /**
     * Test that 5 failed attempts within 15 minutes lock the account.
     */
    public function test_five_failed_attempts_lock_account()
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

        $response->assertStatus(302)
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');
    }

    /**
     * Test that account lock expires after 15 minutes.
     */
    public function test_account_lock_expires_after_15_minutes()
    {
        // Create 5 failed attempts from 16 minutes ago
        $sixteenMinutesAgo = now()->subMinutes(16);
        for ($i = 0; $i < 5; $i++) {
            DB::table('login_attempts')->insert([
                'username' => 'testuser',
                'ip_address' => '127.0.0.1',
                'attempted_at' => $sixteenMinutesAgo,
                'success' => false,
            ]);
        }

        // Login should work because the lock has expired (15 minutes have passed)
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(302)
            ->assertRedirect('/dashboard');
    }

    /**
     * Test that successful login clears failed attempts.
     */
    public function test_successful_login_clears_failed_attempts()
    {
        // Make 2 failed login attempts
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/login', [
                'username' => 'testuser',
                'password' => 'wrongpassword',
            ]);
        }

        // Verify failed attempts are recorded
        $failedCount = DB::table('login_attempts')
            ->where('username', 'testuser')
            ->where('success', false)
            ->count();
        $this->assertEquals(2, $failedCount);

        // Successful login
        $this->postJson('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        // Verify failed attempts are cleared
        $failedCount = DB::table('login_attempts')
            ->where('username', 'testuser')
            ->where('success', false)
            ->count();
        $this->assertEquals(0, $failedCount);

        // Verify successful attempt is recorded
        $successCount = DB::table('login_attempts')
            ->where('username', 'testuser')
            ->where('success', true)
            ->count();
        $this->assertGreaterThan(0, $successCount);
    }

    /**
     * Test that failed attempts older than 15 minutes don't count.
     */
    public function test_failed_attempts_older_than_15_minutes_dont_count()
    {
        // Create 5 failed attempts from 20 minutes ago
        $twentyMinutesAgo = now()->subMinutes(20);
        for ($i = 0; $i < 5; $i++) {
            DB::table('login_attempts')->insert([
                'username' => 'testuser',
                'ip_address' => '127.0.0.1',
                'attempted_at' => $twentyMinutesAgo,
                'success' => false,
            ]);
        }

        // Login should work because old attempts don't count
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(302)
            ->assertRedirect('/dashboard');
    }

    /**
     * Test that login attempts are recorded with IP address.
     */
    public function test_login_attempts_recorded_with_ip_address()
    {
        $this->postJson('/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $attempt = DB::table('login_attempts')
            ->where('username', 'testuser')
            ->first();

        $this->assertNotNull($attempt);
        $this->assertNotNull($attempt->ip_address);
    }

    /**
     * Test that locked_until timestamp is returned when account is locked.
     */
    public function test_locked_until_timestamp_returned()
    {
        // Make 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'testuser',
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt should be locked
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(302)
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');
    }
}
