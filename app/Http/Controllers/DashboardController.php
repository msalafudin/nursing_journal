<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\User;
use App\Services\ShiftDetectionService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private ShiftDetectionService $shiftDetectionService;

    public function __construct(ShiftDetectionService $shiftDetectionService)
    {
        $this->shiftDetectionService = $shiftDetectionService;
    }

    /**
     * Display the dashboard based on user role.
     * 
     * Returns appropriate dashboard for Nurse or Admin.
     * Prevents unauthorized access to admin features.
     * 
     * Requirements: 9.1, 9.3, 9.4
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Prevent unauthorized access to admin features
        if ($user->isAdmin()) {
            return $this->showAdminDashboard($user);
        } elseif ($user->isNurse()) {
            return $this->showNurseDashboard($user);
        }

        // Fallback - should not reach here if roles are properly set
        return redirect('/login')->with('error', 'Invalid user role');
    }

    /**
     * Display nurse dashboard with assigned unit and current shift.
     * 
     * Requirements: 9.2
     */
    private function showNurseDashboard(User $user)
    {
        $currentShift = $this->shiftDetectionService->getCurrentShift();
        $assignedUnit = $user->unit;

        return view('dashboard.nurse', [
            'user' => $user,
            'assignedUnit' => $assignedUnit,
            'currentShift' => $currentShift,
        ]);
    }

    /**
     * Display admin dashboard with statistics and management links.
     * 
     * Requirements: 9.3
     */
    private function showAdminDashboard(User $user)
    {
        $totalUnits = Unit::count();
        $totalActiveUsers = User::where('status', 'active')->count();

        return view('dashboard.admin', [
            'user' => $user,
            'totalUnits' => $totalUnits,
            'totalActiveUsers' => $totalActiveUsers,
        ]);
    }
}
