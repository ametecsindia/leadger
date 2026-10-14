<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DATABASE AND ENVIRONMENT CHECK ===\n";
$conn = config('database.default');
$host = config("database.connections.{$conn}.host");
$database = config("database.connections.{$conn}.database");
echo "DB Connection: " . $conn . "\n";
echo "DB Host: " . $host . "\n";
echo "DB Database: " . $database . "\n";

$isLocal = ($host === '127.0.0.1' || $host === 'localhost');
echo "Is Local Database: " . ($isLocal ? 'YES' : 'NO') . "\n";

$initialCount = App\Models\Lead::count();
echo "Total Existing Leads: " . $initialCount . "\n";

echo "\n=== SAFE LEAD CRUD TEST ===\n";
$testLeadId = null;
try {
    // 1. Create disposable test lead
    $testLead = new App\Models\Lead();
    $testLead->name = "AUTOTEST_DISPOSABLE_LEAD_" . time();
    $testLead->phonenumber = "9999999999";
    $testLead->email = "disposable_test_" . time() . "@leadger.test";
    $testLead->company = "Test Leadger Corp";
    $testLead->status = 1;
    $testLead->source = 1;
    $testLead->country = "India";
    $testLead->created_by = App\Models\User::first()->id ?? 1;
    $testLead->save();
    
    $testLeadId = $testLead->id;
    echo "1. Create Lead: PASS (Created ID: {$testLeadId})\n";
    
    // 2. Confirm saved and appears in count
    $newCount = App\Models\Lead::count();
    $savedLead = App\Models\Lead::find($testLeadId);
    echo "2. Query Saved Lead: " . ($savedLead && $newCount === ($initialCount + 1) ? 'PASS' : 'FAIL') . "\n";
    
    // 3. Verify saved values
    $nameMatches = ($savedLead->name === $testLead->name);
    echo "3. Verify Saved Values: " . ($nameMatches ? 'PASS' : 'FAIL') . "\n";
    
    // 4. Update selected fields
    $updatedName = $testLead->name . "_UPDATED";
    $savedLead->name = $updatedName;
    $savedLead->save();
    
    $reloadedLead = App\Models\Lead::find($testLeadId);
    $updatePersists = ($reloadedLead->name === $updatedName);
    echo "4. Update Fields and Verify Persistence: " . ($updatePersists ? 'PASS' : 'FAIL') . "\n";
    
    // 5. Delete disposable test lead
    $reloadedLead->delete();
    $finalCount = App\Models\Lead::count();
    $isDeleted = (App\Models\Lead::find($testLeadId) === null);
    echo "5. Delete Disposable Test Lead: " . ($isDeleted && $finalCount === $initialCount ? 'PASS' : 'FAIL') . "\n";
    echo "6. Integrity: Original Lead Count Preserved ({$finalCount} === {$initialCount})\n";
} catch (\Throwable $e) {
    echo "Lead CRUD ERROR: " . $e->getMessage() . "\n";
    if ($testLeadId) {
        App\Models\Lead::where('id', $testLeadId)->delete();
        echo "Cleaned up test record {$testLeadId}\n";
    }
}

echo "\n=== PDF GENERATION TEST ===\n";
try {
    $pdf = app('dompdf.wrapper');
    $pdf->loadHTML('<html><body><h1>Test Agreement Document</h1><p>Customer: Test Customer</p><p>LeadGER Verified</p></body></html>');
    $output = $pdf->output();
    $isPdf = (substr($output, 0, 4) === '%PDF');
    echo "Dompdf Generation: " . ($isPdf ? 'PASS (Valid %PDF header, ' . strlen($output) . ' bytes)' : 'FAIL') . "\n";
} catch (\Throwable $e) {
    echo "Dompdf Generation ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== LARAVEL LOG HEALTH CHECK ===\n";
$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $size = filesize($logPath);
    echo "Log file size: " . number_format($size / 1024, 2) . " KB\n";
    // Check if there are recent critical errors
    $lines = file($logPath);
    $recentErrors = 0;
    $lastErrorMsg = "";
    foreach (array_slice($lines, -100) as $line) {
        if (str_contains($line, '.ERROR:') || str_contains($line, '.CRITICAL:') || str_contains($line, '.EMERGENCY:')) {
            $recentErrors++;
            $lastErrorMsg = trim($line);
        }
    }
    echo "Recent errors in last 100 log lines: {$recentErrors}\n";
    if ($recentErrors > 0) {
        echo "Sample recent log error: " . substr($lastErrorMsg, 0, 120) . "...\n";
    }
} else {
    echo "No log file found.\n";
}
