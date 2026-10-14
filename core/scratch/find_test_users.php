<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Permissions;

$adminPerm = Permissions::where('name', 'Admin')->first();
$mgrPerm = Permissions::where('name', 'Manager')->first();
$tcPerm = Permissions::where('name', 'Telecaller')->first();

$admin = User::where('permissions_id', $adminPerm->id)->first();
$manager = User::where('permissions_id', $mgrPerm->id)->first();
$telecaller = User::where('permissions_id', $tcPerm->id)->first();

echo "Admin user: " . ($admin ? "ID {$admin->id} ({$admin->name})" : "None") . "\n";
echo "Manager user: " . ($manager ? "ID {$manager->id} ({$manager->name})" : "None") . "\n";
echo "Telecaller user: " . ($telecaller ? "ID {$telecaller->id} ({$telecaller->name})" : "None") . "\n";

if (!$manager) {
    echo "No manager found with permissions_id {$mgrPerm->id}. Let's see if we should create or temporarily assign one for testing.\n";
}
