<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SessionTimeoutMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that session timeout middleware logs out user after 60 minutes of inactivity
     */
    public function test_session_timeout_after_60_minutes_of_inactivity(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'role' => 'Nurse',
        ]);

        // Login the user
        $this->actingAs($user);

        // Set last_activity to 60 minutes ago
        $sixtyMinutesAgo = now()->subMinutes(60)->timestamp;
        Session::put('last_activity', $sixtyMinutesAgo);

        // Make a request to a protected route
        $response = $this->get('/dashboard');

        // Should be redirected to login
        $this->assertFalse(auth()->check());
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('message', 'Your session has expired due to inactivity. Please login again.');
    }

    /**
     * Test that session timeout middleware logs out user after more than 60 minutes of inactivity
     */
    public function test_session_timeout_after_more_than_60_minutes_of_inactivity(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'role' => 'Nurse',
        ]);

        // Login the user
        $this->actingAs($user);

        // Set last_activity to 90 minutes ago
        $ninetyMinutesAgo = now()->subMinutes(90)->timestamp;
        Session::put('last_activity', $ninetyMinutesAgo);

        // Make a request to a protected route
        $response = $this->get('/dashboard');

        // Should be redirected to login
        $this->assertFalse(auth()->check());
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that session does NOT timeout before 60 minutes of inactivity
     */
    public function test_session_does_not_timeout_before_60_minutes(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'role' => 'Nurse',
        ]);

        // Login the user
        $this->actingAs($user);

        // Set last_activity to 30 minutes ago
        $thirtyMinutesAgo = now()->subMinutes(30)->timestamp;
        Session::put('last_activity', $thirtyMinutesAgo);

        // Make a request to a protected route
        $response = $this->get('/dashboard');

        // User should still be authenticated
        $this->assertTrue(auth()->check());
        // Should not redirect to login
        $response->assertStatus(200);
    }

    /**
     * Test that session last_activity is updated on each request
     */
    public function test_session_last_activity_is_updated_on_each_request(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'role' => 'Nurse',
        ]);

        // Login the user
        $this->actingAs($user);

        // Set last_activity to 30 minutes ago
        $thirtyMinutesAgo = now()->subMinutes(30)->timestamp;
        Session::put('last_activity', $thirtyMinutesAgo);

        // Get the old timestamp
        $oldTimestamp = Session::get('last_activity');

        // Make a request to a protected route
        $this->get('/dashboard');

        // Get the new timestamp
        $newTimestamp = Session::get('last_activity');

        // The new timestamp should be more recent than the old one
        $this->assertGreaterThan($oldTimestamp, $newTimestamp);
    }

    /**
     * Test that unauthenticated users are not affected by session timeout middleware
     */
    public function test_unauthenticated_users_not_affected_by_timeout(): void
    {
        // Make a request without being authenticated
        $response = $this->get('/');

        // Should not be redirected to login (or should be redirected by other middleware)
        $this->assertFalse(auth()->check());
    }

    /**
     * Test that session is flushed on timeout
     */
    public function test_session_is_flushed_on_timeout(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'role' => 'Nurse',
        ]);

        // Login the user
        $this->actingAs($user);

        // Set some session data
        Session::put('custom_data', 'test_value');
        Session::put('last_activity', now()->subMinutes(60)->timestamp);

        // Make a request to a protected route
        $this->get('/dashboard');

        // Session should be flushed
        $this->assertNull(Session::get('custom_data'));
        $this->assertNull(Session::get('last_activity'));
    }
}
