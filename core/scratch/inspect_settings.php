<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "--- SETTINGS COLUMNS ---\n";
print_r(Schema::getColumnListing('settings'));

$setting = \App\Models\Setting::first();
echo "--- FIRST SETTING ROW ---\n";
if ($setting) {
    print_r($setting->toArray());
}
