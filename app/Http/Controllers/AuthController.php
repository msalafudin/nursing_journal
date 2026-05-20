<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle user login.
     * 
     * Validates credentials, checks rate limiting, creates session with user data.
     * Returns generic error message for invalid credentials (doesn't reveal which field is wrong).
     */
    public function login(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $validated['username'];
        $password = $validated['password'];
        $ipAddress = $request->ip();

        // Check if account is locked due to failed login attempts
        $lockStatus = $this->checkAccountLock($username);
        if ($lockStatus['locked']) {
            return redirect('/login')->withErrors([
                'login' => 'Akun terkunci sementara. Silakan coba lagi dalam ' . $lockStatus['minutes'] . ' menit.',
            ])->withInput($request->only('username'));
        }

        // Find user by username
        $user = User::where('username', $username)->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($password, $user->password)) {
            // Record failed login attempt
            $this->recordFailedLoginAttempt($username, $ipAddress);

            // Return generic error message (doesn't reveal which field is wrong)
            return redirect('/login')->withErrors([
                'login' => 'Username atau password tidak valid.',
            ])->withInput($request->only('username'));
        }

        // Check if user is active
        if ($user->status !== 'active') {
            return redirect('/login')->withErrors([
                'login' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
            ])->withInput($request->only('username'));
        }

        // // Clear failed login attempts for this user
        // LoginAttempt::where('username', $username)
        //     ->where('success', false)
        //     ->delete();

        // Create session with user data
        $this->createSession($user, $request);

        // Update last login time
        $user->update(['last_login' => Carbon::now()]);

        // Record successful login attempt
        LoginAttempt::create([
            'username' => $username,
            'ip_address' => $ipAddress,
            'success' => true,
        ]);

        return redirect('/dashboard')->with('success', 'Login berhasil.');
    }

    /**
     * Handle user logout.
     * 
     * Clears session data and redirects to login page.
     */
    public function logout(Request $request)
    {
        // Clear session
        Session::flush();

        // Invalidate the session
        $request->session()->invalidate();

        // Regenerate CSRF token
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logout berhasil.');
    }

    /**
     * Check if an account is locked due to failed login attempts.
     * 
     * Returns array with 'locked' boolean and 'minutes' remaining if locked.
     */
    private function checkAccountLock(string $username): array
    {
        // Get failed login attempts in the last 15 minutes
        $fifteenMinutesAgo = Carbon::now()->subMinutes(15);
        
        $failedAttempts = LoginAttempt::where('username', $username)
            ->where('success', false)
            ->where('attempted_at', '>=', $fifteenMinutesAgo)
            ->count();

        if ($failedAttempts >= 5) {
            // Get the oldest failed attempt to calculate unlock time
            $oldestAttempt = LoginAttempt::where('username', $username)
                ->where('success', false)
                ->where('attempted_at', '>=', $fifteenMinutesAgo)
                ->orderBy('attempted_at', 'asc')
                ->first();

            if ($oldestAttempt) {
                $unlockTime = $oldestAttempt->attempted_at->addMinutes(15);
                $minutesRemaining = ceil($unlockTime->diffInSeconds(Carbon::now()) / 60);

                return [
                    'locked' => true,
                    'minutes' => max(1, $minutesRemaining),
                ];
            }
        }

        return ['locked' => false];
    }

    /**
     * Record a failed login attempt.
     */
    private function recordFailedLoginAttempt(string $username, string $ipAddress): void
    {
        LoginAttempt::create([
            'username' => $username,
            'ip_address' => $ipAddress,
            'success' => false,
        ]);
    }

    /**
     * Create a session with user data.
     * 
     * Stores user_id, role, unit_id, and other session data.
     */
    private function createSession(User $user, Request $request): void
    {
        // Store user data in session
        Session::put([
            'user_id' => $user->id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'role' => $user->role,
            'unit_id' => $user->unit_id,
            'unit_name' => $user->unit ? $user->unit->name : null,
            'login_time' => Carbon::now()->timestamp,
            'last_activity' => now()->timestamp,
            'ip_address' => $request->ip(),
        ]);

        // Authenticate the user for Laravel's auth system
        auth()->login($user);
    }
}
