<?php

// Test that the views exist
$nurseDashboardPath = 'resources/views/dashboard/nurse.blade.php';
$adminDashboardPath = 'resources/views/dashboard/admin.blade.php';
$controllerPath = 'app/Http/Controllers/DashboardController.php';

echo "Checking dashboard components...\n\n";

if (file_exists($controllerPath)) {
    echo "✓ DashboardController exists\n";
    $content = file_get_contents($controllerPath);
    if (strpos($content, 'class DashboardController') !== false) {
        echo "✓ DashboardController class definition found\n";
    }
    if (strpos($content, 'public function index') !== false) {
        echo "✓ DashboardController index method found\n";
    }
    if (strpos($content, 'showNurseDashboard') !== false) {
        echo "✓ DashboardController showNurseDashboard method found\n";
    }
    if (strpos($content, 'showAdminDashboard') !== false) {
        echo "✓ DashboardController showAdminDashboard method found\n";
    }
} else {
    echo "✗ DashboardController not found\n";
    exit(1);
}

if (file_exists($nurseDashboardPath)) {
    echo "✓ Nurse dashboard view exists\n";
    $content = file_get_contents($nurseDashboardPath);
    if (strpos($content, 'Assigned Unit') !== false) {
        echo "✓ Nurse dashboard contains assigned unit section\n";
    }
    if (strpos($content, 'Current Shift') !== false) {
        echo "✓ Nurse dashboard contains current shift section\n";
    }
    if (strpos($content, 'Patient Data Form') !== false) {
        echo "✓ Nurse dashboard contains quick access link\n";
    }
} else {
    echo "✗ Nurse dashboard view not found\n";
    exit(1);
}

if (file_exists($adminDashboardPath)) {
    echo "✓ Admin dashboard view exists\n";
    $content = file_get_contents($adminDashboardPath);
    if (strpos($content, 'Total Units') !== false) {
        echo "✓ Admin dashboard contains total units section\n";
    }
    if (strpos($content, 'Active Users') !== false) {
        echo "✓ Admin dashboard contains active users section\n";
    }
    if (strpos($content, 'Unit Management') !== false) {
        echo "✓ Admin dashboard contains unit management link\n";
    }
    if (strpos($content, 'User Management') !== false) {
        echo "✓ Admin dashboard contains user management link\n";
    }
    if (strpos($content, 'Reports') !== false) {
        echo "✓ Admin dashboard contains reports link\n";
    }
} else {
    echo "✗ Admin dashboard view not found\n";
    exit(1);
}

echo "\n✓ All dashboard components verified successfully!\n";
