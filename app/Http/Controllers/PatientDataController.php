<?php

namespace App\Http\Controllers;

use App\Models\PatientData;
use App\Models\Unit;
use App\Services\ShiftDetectionService;
use Illuminate\Http\Request;

class PatientDataController extends Controller
{
    /**
     * The shift detection service instance.
     */
    protected ShiftDetectionService $shiftDetectionService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ShiftDetectionService $shiftDetectionService)
    {
        $this->shiftDetectionService = $shiftDetectionService;
    }

    /**
     * Display the patient data form with unit-specific fields and current shift.
     * 
     * Returns the form view with:
     * - Current shift (auto-detected based on server time)
     * - User's assigned unit
     * - Unit-specific field definitions for form rendering
     * - All available shifts for the dropdown
     * 
     * Requirements: 2.1, 3.4, 3.5
     */
    public function showForm()
    {
        // Get the authenticated user
        $user = auth()->user();

        // Get the user's assigned unit
        $unit = $user->unit;

        // If user is not assigned to a unit, redirect with error
        if (!$unit) {
            return redirect('/dashboard')->withErrors([
                'error' => 'Anda belum ditugaskan ke unit manapun. Silakan hubungi administrator.',
            ]);
        }

        // Get the current shift based on server time (WIB/UTC+7)
        $currentShift = $this->shiftDetectionService->getCurrentShift();

        // Get unit-specific field definitions
        $fields = $unit->getFieldDefinition();

        // Get all available shifts for the dropdown
        $availableShifts = $this->shiftDetectionService->getAvailableShifts();

        // Return the form view with all necessary data
        return view('patient-data.form', [
            'unit' => $unit,
            'currentShift' => $currentShift,
            'fields' => $fields,
            'availableShifts' => $availableShifts,
        ]);
    }

    /**
     * Store patient data to the database.
     * 
     * Validates all required fields and their ranges, then saves to database.
     * Handles duplicate entries by returning a confirmation response.
     * 
     * Requirements: 2.2, 2.3
     */
    public function store(Request $request)
    {
        // Get the authenticated user
        $user = auth()->user();
        $unit = $user->unit;

        // If user is not assigned to a unit, return error
        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum ditugaskan ke unit manapun.',
            ], 403);
        }

        // Get unit-specific field definitions
        $fields = $unit->getFieldDefinition();

        // Build validation rules dynamically based on unit-specific fields
        $rules = [
            'date' => 'required|date',
            'shift' => 'required|in:Pagi,Siang,Malam',
        ];

        $messages = [
            'date.required' => 'Tanggal harus diisi',
            'date.date' => 'Format tanggal tidak valid',
            'shift.required' => 'Shift harus dipilih',
            'shift.in' => 'Shift harus salah satu dari: Pagi, Siang, Malam',
        ];

        // Add validation rules for each field
        foreach ($fields as $field) {
            if ($field['type'] === 'numeric' && !isset($field['auto_calculated'])) {
                if ($field['required']) {
                    $rules[$field['key']] = 'required|numeric|min:' . ($field['min'] ?? 0) . '|max:' . ($field['max'] ?? 9999);
                    $messages[$field['key'] . '.required'] = $field['name'] . ' harus diisi';
                    $messages[$field['key'] . '.numeric'] = $field['name'] . ' harus berupa angka';
                    $messages[$field['key'] . '.min'] = $field['name'] . ' harus minimal ' . ($field['min'] ?? 0);
                    $messages[$field['key'] . '.max'] = $field['name'] . ' harus maksimal ' . ($field['max'] ?? 9999);
                } else {
                    $rules[$field['key']] = 'nullable|numeric|min:' . ($field['min'] ?? 0) . '|max:' . ($field['max'] ?? 9999);
                }
            } elseif ($field['type'] === 'text' && !isset($field['auto_calculated'])) {
                if ($field['required']) {
                    $rules[$field['key']] = 'required|string';
                    $messages[$field['key'] . '.required'] = $field['name'] . ' harus diisi';
                } else {
                    $rules[$field['key']] = 'nullable|string';
                }
            }
        }

        // Validate the request
        $validated = $request->validate($rules, $messages);

        // Check for duplicate entry
        $existingEntry = PatientData::where('unit_id', $unit->id)
            ->where('date', $validated['date'])
            ->where('shift', $validated['shift'])
            ->first();

        if ($existingEntry) {
            return response()->json([
                'success' => false,
                'message' => 'Data untuk tanggal, shift, dan unit ini sudah ada. Apakah Anda ingin memperbarui?',
                'action' => 'confirm_update',
                'existing_id' => $existingEntry->id,
            ], 409);
        }

        // Prepare data for storage
        $data = [];
        $totalPatients = 0;

        foreach ($fields as $field) {
            if ($field['type'] === 'numeric' && !isset($field['auto_calculated'])) {
                $value = $request->input($field['key']);
                $data[$field['key']] = $value !== null ? (int)$value : null;
                if ($value !== null) {
                    // Only count sensus fields (or fields without category) toward total
                    $category = $field['category'] ?? 'count';
                    if ($category !== 'mutasi') {
                        $totalPatients += (int)$value;
                    }
                }
            } elseif ($field['type'] === 'text' && !isset($field['auto_calculated'])) {
                $data[$field['key']] = $request->input($field['key']);
            }
        }

        // Create the patient data record
        $patientData = PatientData::create([
            'user_id' => $user->id,
            'unit_id' => $unit->id,
            'date' => $validated['date'],
            'shift' => $validated['shift'],
            'data' => $data,
            'total_patients' => $totalPatients,
        ]);

        // Generate text output
        $textOutput = $this->generateTextOutput($patientData, $fields);

        return response()->json([
            'success' => true,
            'message' => 'Data pasien berhasil disimpan',
            'data' => $patientData,
            'text_output' => $textOutput,
        ], 201);
    }

    /**
     * Generate formatted text output from patient data.
     * 
     * Requirements: 2.7
     */
    private function generateTextOutput(PatientData $patientData, array $fields): string
    {
        $output = "Assalamualaikum Wr. Wb.\n";
        $output .= "=== DATA PASIEN ===\n";
        $output .= "Unit: " . $patientData->unit->name . "\n";
        $output .= "Tanggal: " . $patientData->date->format('d-m-Y') . "\n";
        $output .= "Shift: " . $patientData->shift . "\n";
        $output .= "Perawat: " . $patientData->user->full_name . "\n";
        $output .= "\n--- DETAIL DATA ---\n";

        foreach ($fields as $field) {
            if (!isset($field['auto_calculated'])) {
                $value = $patientData->data[$field['key']] ?? '';
                $output .= $field['name'] . ": " . $value . "\n";
            }
        }

        // Tampilkan total jumlah pasien
        $output .= "\nTotal Pasien: " . $patientData->total_patients . "\n";

        return $output;
    }

    /**
     * Update existing patient data (replace duplicate).
     */
    public function update(Request $request, PatientData $patientData)
    {
        $user = auth()->user();
        $unit = $user->unit;

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum ditugaskan ke unit manapun.',
            ], 403);
        }

        // Ensure the record belongs to the same unit
        if ($patientData->unit_id !== $unit->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengubah data ini.',
            ], 403);
        }

        $fields = $unit->getFieldDefinition();

        // Build validation rules
        $rules = [
            'date' => 'required|date',
            'shift' => 'required|in:Pagi,Siang,Malam',
        ];

        $messages = [
            'date.required' => 'Tanggal harus diisi',
            'date.date' => 'Format tanggal tidak valid',
            'shift.required' => 'Shift harus dipilih',
            'shift.in' => 'Shift harus salah satu dari: Pagi, Siang, Malam',
        ];

        foreach ($fields as $field) {
            if ($field['type'] === 'numeric' && !isset($field['auto_calculated'])) {
                if ($field['required']) {
                    $rules[$field['key']] = 'required|numeric|min:' . ($field['min'] ?? 0) . '|max:' . ($field['max'] ?? 9999);
                    $messages[$field['key'] . '.required'] = $field['name'] . ' harus diisi';
                    $messages[$field['key'] . '.numeric'] = $field['name'] . ' harus berupa angka';
                    $messages[$field['key'] . '.min'] = $field['name'] . ' harus minimal ' . ($field['min'] ?? 0);
                    $messages[$field['key'] . '.max'] = $field['name'] . ' harus maksimal ' . ($field['max'] ?? 9999);
                } else {
                    $rules[$field['key']] = 'nullable|numeric|min:' . ($field['min'] ?? 0) . '|max:' . ($field['max'] ?? 9999);
                }
            } elseif ($field['type'] === 'text' && !isset($field['auto_calculated'])) {
                if ($field['required']) {
                    $rules[$field['key']] = 'required|string';
                    $messages[$field['key'] . '.required'] = $field['name'] . ' harus diisi';
                } else {
                    $rules[$field['key']] = 'nullable|string';
                }
            }
        }

        $validated = $request->validate($rules, $messages);

        // Prepare data
        $data = [];
        $totalPatients = 0;

        foreach ($fields as $field) {
            if ($field['type'] === 'numeric' && !isset($field['auto_calculated'])) {
                $value = $request->input($field['key']);
                $data[$field['key']] = $value !== null ? (int)$value : null;
                if ($value !== null) {
                    $category = $field['category'] ?? 'count';
                    if ($category !== 'mutasi') {
                        $totalPatients += (int)$value;
                    }
                }
            } elseif ($field['type'] === 'text' && !isset($field['auto_calculated'])) {
                $data[$field['key']] = $request->input($field['key']);
            }
        }

        $patientData->update([
            'user_id' => $user->id,
            'data' => $data,
            'total_patients' => $totalPatients,
        ]);

        $textOutput = $this->generateTextOutput($patientData->fresh(), $fields);

        return response()->json([
            'success' => true,
            'message' => 'Data pasien berhasil diperbarui',
            'data' => $patientData,
            'text_output' => $textOutput,
        ]);
    }
}
