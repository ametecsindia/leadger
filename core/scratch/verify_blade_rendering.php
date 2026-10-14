<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Permissions;
use App\Models\Setting;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

$adminUser = User::where('email', 'priyamadhuri@ametecsindia.com')->first();
$telecallerUser = User::where('email', 'shwethaametecs@gmail.com')->first();
$GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
$Permissions = Permissions::orderby('id', 'asc')->get();
$Users = $adminUser;
$setting = Setting::first();
$rmiReportEnabled = !empty($setting) ? (bool)$setting->rmi_report_enabled : false;
$managerPerm = Permissions::where('name', 'Manager')->first();
$managerDeletePermission = !empty($managerPerm) ? (bool)$managerPerm->delete_status : false;

$usersController = app(\App\Http\Controllers\Dashboard\UsersController::class);

// 1. Render as Admin
Auth::login($adminUser);
$responseAdmin = $usersController->edit($adminUser->id);
$htmlAdmin = $responseAdmin->render();

// 2. Render as Telecaller
Auth::login($telecallerUser);
$responseTelecaller = $usersController->edit($telecallerUser->id);
$htmlTelecaller = $responseTelecaller->render();

echo "=== ADMIN RENDER CHECK ===\n";
if (strpos($htmlAdmin, 'name="manager_delete_permission"') !== false) {
    echo "✓ Admin view has interactive input name='manager_delete_permission'\n";
} else {
    echo "✗ Admin view missing input name='manager_delete_permission'\n";
}
if (strpos($htmlAdmin, 'Admin Controlled') === false) {
    echo "✓ Admin view does not show 'Admin Controlled' badge (unrestricted)\n";
} else {
    echo "✗ Admin view shows badge\n";
}

echo "\n=== TELECALLER RENDER CHECK ===\n";
if (strpos($htmlTelecaller, 'name="manager_delete_permission"') === false) {
    echo "✓ Telecaller view does NOT have input name='manager_delete_permission'\n";
} else {
    echo "✗ Telecaller view unexpectedly has name='manager_delete_permission'\n";
}
if (strpos($htmlTelecaller, 'Admin Controlled') !== false) {
    echo "✓ Telecaller view displays 'Admin Controlled' badge\n";
} else {
    echo "✗ Telecaller view missing 'Admin Controlled' badge\n";
}
if (strpos($htmlTelecaller, 'disabled') !== false) {
    echo "✓ Telecaller checkbox is disabled\n";
} else {
    echo "✗ Telecaller checkbox not disabled\n";
}
