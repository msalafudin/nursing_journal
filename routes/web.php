<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientDataController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Patient data routes
    Route::get('/patient-data/form', [PatientDataController::class, 'showForm'])->name('patient-data.form');
    Route::post('/patient-data/store', [PatientDataController::class, 'store'])->name('patient-data.store');
    Route::put('/patient-data/{patientData}', [PatientDataController::class, 'update'])->name('patient-data.update');

    // User management routes (Admin only)
    Route::middleware('role:Admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', function () {
            return view('users.create', ['units' => \App\Models\Unit::all()]);
        })->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', function (\App\Models\User $user) {
            return view('users.edit', ['user' => $user, 'units' => \App\Models\Unit::all()]);
        })->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');

        // Unit management routes (Admin only)
        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        Route::get('/units/create', [UnitController::class, 'create'])->name('units.create');
        Route::post('/units', [UnitController::class, 'store'])->name('units.store');
        Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit');
        Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::get('/units/{unit}/delete-confirm', [UnitController::class, 'showDeleteConfirm'])->name('units.delete-confirm');
        Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');

        // Report routes (Admin only)
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/compare', [ReportController::class, 'comparePage'])->name('reports.compare');
        Route::get('/reports/detail', [ReportController::class, 'detailPage'])->name('reports.detail');
        Route::get('/reports/detail-data', [ReportController::class, 'getDetailData'])->name('reports.detail-data');
        Route::get('/reports/data', [ReportController::class, 'getData'])->name('reports.data');
        Route::get('/reports/monthly', [ReportController::class, 'getMonthlyData'])->name('reports.monthly');
        Route::get('/reports/monthly-page', [ReportController::class, 'monthlyPage'])->name('reports.monthly-page');
    });
});
