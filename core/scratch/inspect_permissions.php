<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Permissions;
use App\Models\User;

echo "--- PERMISSIONS GROUPS ---\n";
$perms = Permissions::all(['id', 'name', 'delete_status', 'settings_status', 'webmaster_status']);
foreach ($perms as $p) {
    echo "ID: {$p->id} | Name: {$p->name} | delete_status: {$p->delete_status} | settings_status: {$p->settings_status} | webmaster_status: {$p->webmaster_status}\n";
}

echo "\n--- CAMPAIGN SAMPLE ---\n";
$c = \App\Models\Campaign::first();
if ($c) {
    print_r($c->toArray());
} else {
    echo "No campaigns found.\n";
}
