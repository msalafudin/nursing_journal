<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\PatientData;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    /**
     * Display a listing of all units.
     */
    public function index()
    {
        $units = Unit::all();
        return view('units.index', compact('units'));
    }

    /**
     * Show the form for creating a new unit.
     */
    public function create()
    {
        return view('units.create');
    }

    /**
     * Store a newly created unit in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-Z0-9\s]+$/',
                Rule::unique('units', 'name'),
            ],
        ], [
            'name.required' => 'Nama unit harus diisi.',
            'name.min' => 'Nama unit minimal 2 karakter.',
            'name.max' => 'Nama unit maksimal 50 karakter.',
            'name.regex' => 'Nama unit hanya boleh mengandung huruf, angka, dan spasi.',
            'name.unique' => 'Nama unit sudah terdaftar.',
        ]);

        Unit::create($validated);

        return redirect()->route('units.index')
            ->with('success', 'Unit berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified unit.
     */
    public function edit(Unit $unit)
    {
        return view('units.edit', compact('unit'));
    }

    /**
     * Update the specified unit in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-Z0-9\s]+$/',
                Rule::unique('units', 'name')
                    ->ignore($unit->id),
            ],
        ], [
            'name.required' => 'Nama unit harus diisi.',
            'name.min' => 'Nama unit minimal 2 karakter.',
            'name.max' => 'Nama unit maksimal 50 karakter.',
            'name.regex' => 'Nama unit hanya boleh mengandung huruf, angka, dan spasi.',
            'name.unique' => 'Nama unit sudah terdaftar.',
        ]);

        $unit->update($validated);

        return redirect()->route('units.index')
            ->with('success', 'Unit berhasil diperbarui.');
    }

    /**
     * Show the delete confirmation dialog with related data warning.
     */
    public function showDeleteConfirm(Unit $unit)
    {
        $relatedDataCount = PatientData::where('unit_id', $unit->id)->count();
        
        return response()->json([
            'unit' => $unit,
            'related_data_count' => $relatedDataCount,
        ]);
    }

    /**
     * Remove the specified unit from storage.
     */
    public function destroy(Request $request, Unit $unit)
    {
        // Count related patient data
        $relatedDataCount = PatientData::where('unit_id', $unit->id)->count();

        // If there's related data and user hasn't confirmed, return warning
        if ($relatedDataCount > 0 && !$request->input('confirmed')) {
            return response()->json([
                'success' => false,
                'requires_confirmation' => true,
                'related_data_count' => $relatedDataCount,
                'message' => "Unit ini memiliki {$relatedDataCount} data pasien terkait. Apakah Anda yakin ingin menghapus?",
            ], 422);
        }

        // If no related data and not confirmed, still allow deletion (no confirmation needed)
        // But if confirmed, proceed with deletion
        $unit->delete();

        return redirect()->route('units.index')
            ->with('success', 'Unit berhasil dihapus.');
    }
}
