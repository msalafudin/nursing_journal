<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$units = \App\Models\Unit::all();
echo "Units seeded: " . $units->count() . "\n";
foreach ($units as $unit) {
    echo "  - " . $unit->name . " (ID: " . $unit->id . ")\n";
}

echo "\nUsers seeded: " . \App\Models\User::count() . "\n";
foreach (\App\Models\User::all() as $user) {
    $unitName = $user->unit ? $user->unit->name : 'N/A';
    echo "  - " . $user->username . " (" . $user->full_name . ") - Role: " . $user->role . ", Unit: " . $unitName . "\n";
}
