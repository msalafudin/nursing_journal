<?php

namespace App\Http\Controllers;

use App\Models\PatientData;
use App\Models\Unit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Show the reports page.
     */
    public function index()
    {
        $units = Unit::all();
        $shifts = ['Pagi', 'Siang', 'Malam'];
        
        return view('reports.index', [
            'units' => $units,
            'shifts' => $shifts,
        ]);
    }

    /**
     * Show the monthly reports page.
     */
    public function monthlyPage()
    {
        $units = Unit::all();
        
        return view('reports.monthly', [
            'units' => $units,
        ]);
    }

    /**
     * Get report data with filtering.
     * 
     * Query parameters:
     * - unit_id: integer or 'all' (default: 'all')
     * - shift: string or 'all' (default: 'all')
     * - start_date: YYYY-MM-DD (default: today)
     * - end_date: YYYY-MM-DD (default: today)
     */
    public function getData(Request $request)
    {
        // Validate and get filter parameters
        $validated = $request->validate([
            'unit_id' => 'nullable|integer|exists:units,id',
            'shift' => 'nullable|string|in:Pagi,Siang,Malam',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
        ]);

        // Set defaults
        $unitId = $request->input('unit_id');
        $shift = $request->input('shift');
        $startDate = $request->input('start_date') ?? Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $endDate = $request->input('end_date') ?? Carbon::now('Asia/Jakarta')->format('Y-m-d');

        // Validate date range
        $startDateObj = Carbon::createFromFormat('Y-m-d', $startDate, 'Asia/Jakarta');
        $endDateObj = Carbon::createFromFormat('Y-m-d', $endDate, 'Asia/Jakarta');

        // Check if start_date <= end_date
        if ($startDateObj->gt($endDateObj)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal mulai harus lebih kecil atau sama dengan tanggal akhir',
                'error' => 'invalid_date_range',
            ], 422);
        }

        // Check if date range exceeds 90 days
        $daysDifference = $startDateObj->diffInDays($endDateObj);
        if ($daysDifference > 90) {
            return response()->json([
                'success' => false,
                'message' => 'Rentang tanggal maksimal 90 hari',
                'error' => 'date_range_exceeded',
            ], 422);
        }

        // Build query
        $query = PatientData::query()
            ->with(['unit', 'user'])
            ->whereBetween('date', [$startDate, $endDate]);

        // Apply unit filter
        if ($unitId && $unitId !== 'all') {
            $query->where('unit_id', $unitId);
        }

        // Apply shift filter
        if ($shift && $shift !== 'all') {
            $query->where('shift', $shift);
        }

        // Order by date and shift
        $query->orderBy('date', 'asc')
            ->orderBy('shift', 'asc');

        $data = $query->get();

        // Transform data for chart rendering
        $chartData = $data->map(function ($record) {
            return [
                'date' => $record->date->format('Y-m-d'),
                'unit_id' => $record->unit_id,
                'unit_name' => $record->unit->name,
                'shift' => $record->shift,
                'total_patients' => $record->total_patients,
                'details' => $record->data,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $chartData,
            'filters' => [
                'unit_id' => $unitId,
                'shift' => $shift,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'chart_type' => 'line',
        ]);
    }

    /**
     * Get monthly candle chart data.
     * 
     * Query parameters:
     * - unit_id: integer (required)
     * - year: integer (default: current year)
     * - month: integer 1-12 (default: current month)
     */
    public function getMonthlyData(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => 'required|integer|exists:units,id',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $unitId = $request->input('unit_id');
        $year = $request->input('year') ?? Carbon::now('Asia/Jakarta')->year;
        $month = $request->input('month') ?? Carbon::now('Asia/Jakarta')->month;

        // Get first and last day of the month
        $startDate = Carbon::createFromDate($year, $month, 1, 'Asia/Jakarta')->format('Y-m-d');
        $endDate = Carbon::createFromDate($year, $month, 1, 'Asia/Jakarta')
            ->endOfMonth()
            ->format('Y-m-d');

        // Get all patient data for the month
        $data = PatientData::query()
            ->where('unit_id', $unitId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->orderBy('shift', 'asc')
            ->get();

        // Group by date and calculate OHLC (Open, High, Low, Close)
        $candleData = [];
        $groupedByDate = $data->groupBy('date');

        foreach ($groupedByDate as $date => $records) {
            $totals = $records->pluck('total_patients')->toArray();
            
            if (empty($totals)) {
                continue;
            }

            $candleData[] = [
                'date' => $date,
                'open' => $totals[0], // First entry of the day
                'high' => max($totals), // Maximum value
                'low' => min($totals), // Minimum value
                'close' => end($totals), // Last entry of the day
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $candleData,
            'unit_id' => $unitId,
            'year' => $year,
            'month' => $month,
            'chart_type' => 'candle',
        ]);
    }
}
