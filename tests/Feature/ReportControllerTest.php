<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Unit;
use App\Models\PatientData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that report page loads successfully.
     */
    public function test_report_page_loads_successfully()
    {
        $user = User::factory()->create(['role' => 'Admin']);
        
        $response = $this->actingAs($user)->get('/reports');
        
        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
    }

    /**
     * Test that report data retrieval returns data for today by default.
     */
    public function test_report_data_retrieval_default_today()
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $unit = Unit::factory()->create();
        
        // Create patient data for today
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        PatientData::factory()->create([
            'unit_id' => $unit->id,
            'date' => $today,
            'shift' => 'Pagi',
            'total_patients' => 50,
        ]);
        
        $response = $this->actingAs($user)->getJson('/reports/data');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['date', 'unit_id', 'unit_name', 'shift', 'total_patients', 'details']
            ],
            'filters',
            'chart_type',
        ]);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test that report data filtering by unit works.
     */
    public function test_report_data_filtering_by_unit()
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $unit1 = Unit::factory()->create(['name' => 'IGD']);
        $unit2 = Unit::factory()->create(['name' => 'Rawat Inap']);
        
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        
        PatientData::factory()->create([
            'unit_id' => $unit1->id,
            'date' => $today,
            'shift' => 'Pagi',
            'total_patients' => 50,
        ]);
        
        PatientData::factory()->create([
            'unit_id' => $unit2->id,
            'date' => $today,
            'shift' => 'Pagi',
            'total_patients' => 30,
        ]);
        
        $response = $this->actingAs($user)->getJson("/reports/data?unit_id={$unit1->id}");
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($unit1->id, $data[0]['unit_id']);
    }

    /**
     * Test that report data filtering by shift works.
     */
    public function test_report_data_filtering_by_shift()
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $unit = Unit::factory()->create();
        
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        
        PatientData::factory()->create([
            'unit_id' => $unit->id,
            'date' => $today,
            'shift' => 'Pagi',
            'total_patients' => 50,
        ]);
        
        PatientData::factory()->create([
            'unit_id' => $unit->id,
            'date' => $today,
            'shift' => 'Siang',
            'total_patients' => 40,
        ]);
        
        $response = $this->actingAs($user)->getJson('/reports/data?shift=Pagi');
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Pagi', $data[0]['shift']);
    }

    /**
     * Test that invalid date range returns validation error.
     */
    public function test_report_data_invalid_date_range()
    {
        $user = User::factory()->create(['role' => 'Admin']);
        
        $response = $this->actingAs($user)->getJson('/reports/data?start_date=2024-01-31&end_date=2024-01-01');
        
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJson(['error' => 'invalid_date_range']);
    }

    /**
     * Test that date range exceeding 90 days returns error.
     */
    public function test_report_data_date_range_exceeded()
    {
        $user = User::factory()->create(['role' => 'Admin']);
        
        $startDate = Carbon::now('Asia/Jakarta')->subDays(100)->format('Y-m-d');
        $endDate = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        
        $response = $this->actingAs($user)->getJson("/reports/data?start_date={$startDate}&end_date={$endDate}");
        
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJson(['error' => 'date_range_exceeded']);
    }

    /**
     * Test that empty data returns empty state.
     */
    public function test_report_data_empty_result()
    {
        $user = User::factory()->create(['role' => 'Admin']);
        
        $response = $this->actingAs($user)->getJson('/reports/data');
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJson(['data' => []]);
    }

    /**
     * Test monthly data retrieval.
     */
    public function test_monthly_data_retrieval()
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $unit = Unit::factory()->create();
        
        $year = Carbon::now('Asia/Jakarta')->year;
        $month = Carbon::now('Asia/Jakarta')->month;
        $date = Carbon::createFromDate($year, $month, 15, 'Asia/Jakarta')->format('Y-m-d');
        
        PatientData::factory()->create([
            'unit_id' => $unit->id,
            'date' => $date,
            'shift' => 'Pagi',
            'total_patients' => 50,
        ]);
        
        PatientData::factory()->create([
            'unit_id' => $unit->id,
            'date' => $date,
            'shift' => 'Siang',
            'total_patients' => 60,
        ]);
        
        $response = $this->actingAs($user)->getJson("/reports/monthly?unit_id={$unit->id}&year={$year}&month={$month}");
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['date', 'open', 'high', 'low', 'close']
            ],
            'unit_id',
            'year',
            'month',
            'chart_type',
        ]);
        $response->assertJson(['success' => true]);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals(50, $data[0]['open']);
        $this->assertEquals(60, $data[0]['high']);
        $this->assertEquals(50, $data[0]['low']);
        $this->assertEquals(60, $data[0]['close']);
    }

    /**
     * Test monthly page loads successfully.
     */
    public function test_monthly_page_loads_successfully()
    {
        $user = User::factory()->create(['role' => 'Admin']);
        
        $response = $this->actingAs($user)->get('/reports/monthly-page');
        
        $response->assertStatus(200);
        $response->assertViewIs('reports.monthly');
    }
}
