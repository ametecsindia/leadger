<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- 1. Testing Campaign ---\n";
$camp = App\Models\Campaign::where('name', 'Testing')->orWhere('id', 1)->first();
if ($camp) {
    echo json_encode($camp->toArray(), JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Campaign Testing not found\n";
}

echo "\n--- 2. User Nandini ---\n";
$nandini = App\Models\User::where('email', 'nandini@gmail.com')->first();
if ($nandini) {
    echo json_encode($nandini->toArray(), JSON_PRETTY_PRINT) . "\n";
}

echo "\n--- 3. Click2Call channels / tables ---\n";
$tables = ['leadger_webrtc_flexidialer', 'leadger_flexidialer', 'leadger_smart31_vmn_numbers'];
foreach ($tables as $t) {
    try {
        $rows = DB::select("SELECT * FROM {$t} LIMIT 10");
        echo "Table $t:\n" . json_encode($rows, JSON_PRETTY_PRINT) . "\n";
    } catch (\Exception $e) {
        echo "Table $t error: " . $e->getMessage() . "\n";
    }
}
