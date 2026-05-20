<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of all users.
     * 
     * Returns a view with all users including their details (username, full name, unit, status).
     * Only accessible to Admin users.
     */
    public function index()
    {
        // Get all users with their unit information
        $users = User::with('unit')->get();
        $units = Unit::all();

        return view('users.index', compact('users', 'units'));
    }

    /**
     * Store a newly created user in storage.
     * 
     * Validates username uniqueness, password minimum length (8 chars),
     * full name, and unit assignment. Hashes password before storing.
     * Returns generic error message on failure.
     */
    public function store(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'username' => 'required|string|unique:users,username',
                'password' => 'required|string|min:8',
                'full_name' => 'required|string',
                'unit_id' => 'required|exists:units,id',
                'role' => 'required|in:Admin,Nurse',
            ]);

            // Create new user with hashed password
            $user = User::create([
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'full_name' => $validated['full_name'],
                'unit_id' => $validated['unit_id'],
                'role' => $validated['role'],
                'status' => 'active',
            ]);

            return redirect('/users')->with('success', 'Pengguna berhasil ditambahkan.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return redirect('/users')->withErrors($e->validator->errors())->withInput($request->only('username', 'full_name', 'unit_id', 'role'));
        } catch (\Exception $e) {
            // Return generic error message on failure
            return redirect('/users')->withErrors([
                'error' => 'Gagal menambahkan pengguna. Silakan coba lagi.',
            ])->withInput($request->only('username', 'full_name', 'unit_id', 'role'));
        }
    }

    /**
     * Update the specified user in storage.
     * 
     * Updates user's full name, unit assignment, and status.
     * Does not allow changing username or password through this method.
     */
    public function update(Request $request, User $user)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'full_name' => 'required|string',
                'unit_id' => 'required|exists:units,id',
                'status' => 'required|in:active,inactive',
            ]);

            // If status is being changed to inactive, clear all active sessions for this user
            if ($validated['status'] === 'inactive' && $user->status === 'active') {
                $this->clearUserSessions($user->id);
            }

            // Update user
            $user->update($validated);

            return redirect('/users')->with('success', 'Pengguna berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect('/users')->withErrors([
                'error' => 'Gagal memperbarui pengguna. Silakan coba lagi.',
            ]);
        }
    }

    /**
     * Deactivate/Activate a user (soft delete via status).
     * 
     * Sets user status to inactive and clears all active sessions.
     * Prevents login for deactivated users.
     */
    public function destroy(User $user)
    {
        try {
            // Clear all active sessions for this user
            $this->clearUserSessions($user->id);

            // Deactivate user
            $user->update(['status' => 'inactive']);

            return redirect('/users')->with('success', 'Pengguna berhasil dinonaktifkan.');
        } catch (\Exception $e) {
            return redirect('/users')->withErrors([
                'error' => 'Gagal menonaktifkan pengguna. Silakan coba lagi.',
            ]);
        }
    }

    /**
     * Reactivate a deactivated user.
     * 
     * Sets user status back to active, allowing login.
     */
    public function reactivate(User $user)
    {
        try {
            // Reactivate user
            $user->update(['status' => 'active']);

            return redirect('/users')->with('success', 'Pengguna berhasil diaktifkan kembali.');
        } catch (\Exception $e) {
            return redirect('/users')->withErrors([
                'error' => 'Gagal mengaktifkan pengguna. Silakan coba lagi.',
            ]);
        }
    }

    /**
     * Clear all active sessions for a user.
     * 
     * Removes all session records for the specified user from the sessions table.
     */
    private function clearUserSessions(int $userId): void
    {
        // Delete all sessions for this user from the sessions table
        DB::table('sessions')
            ->where('user_id', $userId)
            ->delete();
    }
}
