<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Permissions;
use App\Models\Campaign;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Dashboard\UsersController;
use App\Http\Controllers\Dashboard\CampaignsController;

echo "========================================================\n";
echo "   LEADGER - DELETE PERMISSION AUTHORIZATION TEST SUITE  \n";
echo "========================================================\n\n";

$adminUser = User::where('email', 'priyamadhuri@ametecsindia.com')->first();
if (!$adminUser) {
    $adminPerm = Permissions::where('name', 'Admin')->first();
    $adminUser = User::where('permissions_id', $adminPerm->id)->first();
}

$managerPerm = Permissions::where('name', 'Manager')->first();
$managerUser = User::where('permissions_id', $managerPerm->id)->first();

$telecallerPerm = Permissions::where('name', 'Telecaller')->first();
$telecallerUser = User::where('permissions_id', $telecallerPerm->id)->first();

echo "Loaded Users for Testing:\n";
echo " - Admin:       ID {$adminUser->id} ({$adminUser->name}) - Role: {$adminUser->permissionsGroup->name}\n";
echo " - Manager:     ID {$managerUser->id} ({$managerUser->name}) - Role: {$managerUser->permissionsGroup->name}\n";
echo " - Telecaller:  ID {$telecallerUser->id} ({$telecallerUser->name}) - Role: {$telecallerUser->permissionsGroup->name}\n\n";

$usersController = app(UsersController::class);
$campaignsController = app(CampaignsController::class);

$originalStatus = $managerPerm->delete_status;
echo "Original Manager delete_status in DB: {$originalStatus}\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($condition, $testName, $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] $testName" . ($detail ? " ($detail)" : "") . "\n";
        $passCount++;
    } else {
        echo " [FAIL] $testName" . ($detail ? " ($detail)" : "") . "\n";
        $failCount++;
    }
}

// ----------------------------------------------------
// TEST 1: Admin enables Delete Permission -> saved -> delete works
// ----------------------------------------------------
echo "--- TEST 1: Login as Admin -> Enable Delete Permission -> Save -> Verify DB & Delete Works ---\n";
Auth::login($adminUser);

$req1 = Request::create("/admin/users/{$adminUser->id}/update", 'POST', [
    'name' => $adminUser->name,
    'email' => $adminUser->email,
    'mobile' => $adminUser->phone ?? '9999999999',
    'permissions_id' => $adminUser->permissions_id,
    'shift_id' => $adminUser->shift_id ?: 1,
    'status' => $adminUser->status,
    'manager_delete_permission' => '1',
]);

$response1 = $usersController->update($req1, $adminUser->id);
$managerPerm->refresh();

assertTest($managerPerm->delete_status == 1, "TEST 1.1: Setting saved in DB as enabled (delete_status=1)", "delete_status = {$managerPerm->delete_status}");
assertTest(Helper::isDeletePermissionEnabled() === true, "TEST 1.2: Helper::isDeletePermissionEnabled() returns true");
assertTest(Helper::canDelete() === true, "TEST 1.3: Helper::canDelete() returns true for Admin");

// Verify Delete functionality works for Admin when enabled
// We create a dummy test campaign to test deletion
$testCampaign = new Campaign();
$testCampaign->name = 'Test Temp Campaign For Deletion';
$testCampaign->source_id = 1;
$testCampaign->segment_id = 1;
$testCampaign->team_id = 1;
$testCampaign->expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
$testCampaign->created_by = $adminUser->id;
$testCampaign->status = 1;
$testCampaign->save();

$reqDelete1 = Request::create("/admin/campaigns/{$testCampaign->id}/delete", 'GET');
$deleteResp1 = $campaignsController->delete($testCampaign->id);
$checkCampaignDeleted = Campaign::find($testCampaign->id);

assertTest($checkCampaignDeleted === null, "TEST 1.4: Admin can successfully delete campaign when permission is enabled");

echo "\n";

// ----------------------------------------------------
// TEST 2: Admin disables Delete Permission -> saved -> delete blocked
// ----------------------------------------------------
echo "--- TEST 2: Login as Admin -> Disable Delete Permission -> Save -> Verify DB & Delete Blocked ---\n";
Auth::login($adminUser);

$req2 = Request::create("/admin/users/{$adminUser->id}/update", 'POST', [
    'name' => $adminUser->name,
    'email' => $adminUser->email,
    'mobile' => $adminUser->phone ?? '9999999999',
    'permissions_id' => $adminUser->permissions_id,
    'shift_id' => $adminUser->shift_id ?: 1,
    'status' => $adminUser->status,
    // manager_delete_permission omitted (unchecked)
]);

$response2 = $usersController->update($req2, $adminUser->id);
$managerPerm->refresh();

assertTest($managerPerm->delete_status == 0, "TEST 2.1: Setting saved in DB as disabled (delete_status=0)", "delete_status = {$managerPerm->delete_status}");
assertTest(Helper::isDeletePermissionEnabled() === false, "TEST 2.2: Helper::isDeletePermissionEnabled() returns false");
assertTest(Helper::canDelete() === false, "TEST 2.3: Helper::canDelete() returns false even for Admin");

// Verify Delete functionality is blocked
$testCampaign2 = new Campaign();
$testCampaign2->name = 'Test Campaign That Should NOT Be Deleted';
$testCampaign2->source_id = 1;
$testCampaign2->segment_id = 1;
$testCampaign2->team_id = 1;
$testCampaign2->expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
$testCampaign2->created_by = $adminUser->id;
$testCampaign2->status = 1;
$testCampaign2->save();

$deleteResp2 = $campaignsController->delete($testCampaign2->id);
$checkCampaignNotDeleted = Campaign::find($testCampaign2->id);

assertTest($checkCampaignNotDeleted !== null, "TEST 2.4: Delete operation was BLOCKED when permission disabled (record retained)");
assertTest(
    $deleteResp2 instanceof \Illuminate\Http\RedirectResponse && $deleteResp2->getTargetUrl() === route('NoPermission'),
    "TEST 2.5: Delete endpoint redirected to NoPermission (403)"
);

// Clean up testCampaign2 directly for cleanliness
$testCampaign2->delete();

echo "\n";

// ----------------------------------------------------
// TEST 3: Login as TeleCaller -> Edit user profile -> Verify Delete Permission cannot be changed
// ----------------------------------------------------
echo "--- TEST 3: Login as TeleCaller -> Normal Profile Update -> Delete Permission Untouched ---\n";
// Set DB state to enabled first so we can verify Telecaller cannot reset it to 0
$managerPerm->delete_status = 1;
$managerPerm->save();

Auth::login($telecallerUser);

$req3 = Request::create("/admin/users/{$telecallerUser->id}/update", 'POST', [
    'name' => $telecallerUser->name,
    'email' => $telecallerUser->email,
    'mobile' => $telecallerUser->phone ?? '9999999999',
    'permissions_id' => $telecallerUser->permissions_id,
    'shift_id' => $telecallerUser->shift_id ?: 1,
    'status' => $telecallerUser->status,
    // telecaller form does not submit manager_delete_permission
]);

$response3 = $usersController->update($req3, $telecallerUser->id);
$managerPerm->refresh();

assertTest($managerPerm->delete_status == 1, "TEST 3.1: Telecaller profile update preserves delete_status=1 untouched in DB");
assertTest(Helper::canDelete() === false, "TEST 3.2: Helper::canDelete() is false for Telecaller even if enabled");

echo "\n";

// ----------------------------------------------------
// TEST 4: Login as Manager -> Edit user profile -> Verify Delete Permission cannot be changed
// ----------------------------------------------------
echo "--- TEST 4: Login as Manager -> Normal Profile Update -> Delete Permission Untouched ---\n";
Auth::login($managerUser);

$req4 = Request::create("/admin/users/{$managerUser->id}/update", 'POST', [
    'name' => $managerUser->name,
    'email' => $managerUser->email,
    'mobile' => $managerUser->phone ?? '9999999999',
    'permissions_id' => $managerUser->permissions_id,
    'shift_id' => $managerUser->shift_id ?: 1,
    'status' => $managerUser->status,
    // manager form does not submit manager_delete_permission
]);

$response4 = $usersController->update($req4, $managerUser->id);
$managerPerm->refresh();

assertTest($managerPerm->delete_status == 1, "TEST 4.1: Manager profile update preserves delete_status=1 untouched in DB");
assertTest(Helper::canDelete() === false, "TEST 4.2: Helper::canDelete() is false for Manager even if enabled");

echo "\n";

// ----------------------------------------------------
// TEST 5: TeleCaller / Manager manually submits manager_delete_permission -> Backend rejects (HTTP 403)
// ----------------------------------------------------
echo "--- TEST 5: Non-Admin Manually Submits Delete Permission via POST/API -> Rejected with HTTP 403 ---\n";

// 5A: Telecaller tries to enable/disable
Auth::login($telecallerUser);
$tcRejected = false;
$tcErrorStatus = null;

try {
    $req5a = Request::create("/admin/users/{$telecallerUser->id}/update", 'POST', [
        'name' => $telecallerUser->name,
        'email' => $telecallerUser->email,
        'mobile' => $telecallerUser->phone ?? '9999999999',
        'permissions_id' => $telecallerUser->permissions_id,
        'shift_id' => $telecallerUser->shift_id ?: 1,
        'status' => $telecallerUser->status,
        'manager_delete_permission' => '1',
    ]);
    $resp5a = $usersController->update($req5a, $telecallerUser->id);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    $tcRejected = true;
    $tcErrorStatus = $e->getStatusCode();
}

assertTest($tcRejected && $tcErrorStatus === 403, "TEST 5.1: Telecaller manual submission rejected with HTTP 403", "Status: $tcErrorStatus");

// 5B: Telecaller sends AJAX request
try {
    $req5b = Request::create("/admin/users/{$telecallerUser->id}/update", 'POST', [
        'name' => $telecallerUser->name,
        'email' => $telecallerUser->email,
        'mobile' => $telecallerUser->phone ?? '9999999999',
        'permissions_id' => $telecallerUser->permissions_id,
        'shift_id' => $telecallerUser->shift_id ?: 1,
        'status' => $telecallerUser->status,
        'manager_delete_permission' => '0',
    ], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'HTTP_ACCEPT' => 'application/json']);
    $resp5b = $usersController->update($req5b, $telecallerUser->id);
    $isJson403 = ($resp5b->getStatusCode() === 403);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    $isJson403 = ($e->getStatusCode() === 403);
}
assertTest($isJson403, "TEST 5.2: Telecaller AJAX request rejected with 403 JSON");

// 5C: Manager tries to modify Delete Permission
Auth::login($managerUser);
$mgrRejected = false;
$mgrErrorStatus = null;

try {
    $req5c = Request::create("/admin/users/{$managerUser->id}/update", 'POST', [
        'name' => $managerUser->name,
        'email' => $managerUser->email,
        'mobile' => $managerUser->phone ?? '9999999999',
        'permissions_id' => $managerUser->permissions_id,
        'shift_id' => $managerUser->shift_id ?: 1,
        'status' => $managerUser->status,
        'manager_delete_permission' => '0',
    ]);
    $resp5c = $usersController->update($req5c, $managerUser->id);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    $mgrRejected = true;
    $mgrErrorStatus = $e->getStatusCode();
}

assertTest($mgrRejected && $mgrErrorStatus === 403, "TEST 5.3: Manager manual submission rejected with HTTP 403", "Status: $mgrErrorStatus");

echo "\n";

// ----------------------------------------------------
// TEST 6: When Delete Permission is disabled, manually call Delete endpoint/API -> Backend rejects
// ----------------------------------------------------
echo "--- TEST 6: When Delete Permission is disabled, call Delete endpoints -> Rejected ---\n";
// Set Delete Permission to disabled
$managerPerm->delete_status = 0;
$managerPerm->save();

// 6A: Admin tries to delete while disabled
Auth::login($adminUser);
$testCampaign3 = new Campaign();
$testCampaign3->name = 'Test Campaign Reject When Disabled';
$testCampaign3->source_id = 1;
$testCampaign3->segment_id = 1;
$testCampaign3->team_id = 1;
$testCampaign3->expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
$testCampaign3->created_by = $adminUser->id;
$testCampaign3->status = 1;
$testCampaign3->save();

$resp6a = $campaignsController->delete($testCampaign3->id);
$stillExistsAdmin = Campaign::find($testCampaign3->id);
assertTest($stillExistsAdmin !== null, "TEST 6.1: Admin call to delete endpoint blocked when permission disabled");
assertTest(
    $resp6a instanceof \Illuminate\Http\RedirectResponse && $resp6a->getTargetUrl() === route('NoPermission'),
    "TEST 6.2: Admin redirected to NoPermission route"
);

// 6B: Telecaller tries to delete
Auth::login($telecallerUser);
$resp6b = $campaignsController->delete($testCampaign3->id);
$stillExistsTC = Campaign::find($testCampaign3->id);
assertTest($stillExistsTC !== null, "TEST 6.3: Telecaller call to delete endpoint blocked");

// 6C: UsersController::destroy when permission disabled
Auth::login($adminUser);
$dummyUser = User::create([
    'name' => 'Dummy To Delete',
    'email' => 'dummy_delete_' . time() . '@example.com',
    'password' => bcrypt('123456'),
    'permissions_id' => $telecallerPerm->id,
    'status' => 1
]);

$resp6c = $usersController->destroy($dummyUser->id);
$dummyCheck = User::find($dummyUser->id);
assertTest($dummyCheck !== null, "TEST 6.4: UsersController::destroy rejected when delete permission disabled");

// Clean up test data
$testCampaign3->delete();
$dummyUser->delete();

// Restore original status
$managerPerm->delete_status = $originalStatus;
$managerPerm->save();
echo "\nRestored original Manager delete_status to {$originalStatus}.\n";

echo "\n========================================================\n";
echo "TEST RESULTS SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================\n";
