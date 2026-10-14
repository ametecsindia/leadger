<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$lead = App\Models\Lead::first();
if ($lead) {
    echo "First lead attributes:\n";
    print_r(array_keys($lead->getAttributes()));
} else {
    $cols = Illuminate\Support\Facades\Schema::getColumnListing((new App\Models\Lead())->getTable());
    echo "Columns: " . implode(', ', $cols) . "\n";
}
