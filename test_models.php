<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Unit;
use App\Models\PatientData;

echo "Testing Models...\n\n";

// Test User Model
$user = new User();
echo "User Model:\n";
echo "  - isAdmin method: " . (method_exists($user, 'isAdmin') ? "✓" : "✗") . "\n";
echo "  - isNurse method: " . (method_exists($user, 'isNurse') ? "✓" : "✗") . "\n";
echo "  - unit relationship: " . (method_exists($user, 'unit') ? "✓" : "✗") . "\n";
echo "  - patientData relationship: " . (method_exists($user, 'patientData') ? "✓" : "✗") . "\n";
echo "  - Fillable: " . json_encode($user->getFillable()) . "\n";
echo "  - Hidden: " . json_encode($user->getHidden()) . "\n\n";

// Test Unit Model
$unit = new Unit();
echo "Unit Model:\n";
echo "  - getFieldDefinition method: " . (method_exists($unit, 'getFieldDefinition') ? "✓" : "✗") . "\n";
echo "  - users relationship: " . (method_exists($unit, 'users') ? "✓" : "✗") . "\n";
echo "  - patientData relationship: " . (method_exists($unit, 'patientData') ? "✓" : "✗") . "\n";
echo "  - Fillable: " . json_encode($unit->getFillable()) . "\n\n";

// Test PatientData Model
$patientData = new PatientData();
echo "PatientData Model:\n";
echo "  - user relationship: " . (method_exists($patientData, 'user') ? "✓" : "✗") . "\n";
echo "  - unit relationship: " . (method_exists($patientData, 'unit') ? "✓" : "✗") . "\n";
echo "  - Fillable: " . json_encode($patientData->getFillable()) . "\n";
echo "  - Casts: " . json_encode($patientData->getCasts()) . "\n\n";

// Test Unit field definitions
$unit->name = 'IGD';
$fields = $unit->getFieldDefinition();
echo "Unit Field Definitions (IGD):\n";
echo "  - Number of fields: " . count($fields) . "\n";
echo "  - Fields: " . json_encode(array_map(fn($f) => $f['key'], $fields)) . "\n\n";

echo "All tests completed successfully!\n";
