<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIs\APIsController;
use App\Http\Controllers\APIs\CronsController;
use App\Http\Controllers\APIs\TelephonyWebhookController;
use App\Http\Controllers\Dashboard\DialerController;
use App\Http\Controllers\Dashboard\CampaignsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/fetch-incoming-vmn', [CronsController::class, 'fetchIncomingVmnLogs'])->name('IncomingCallData');
Route::get('/update-click2call-responses', [CronsController::class, 'updateAllClick2CallResponses']);   // DialerController never had this method
Route::get('/get-click2call-data', [CronsController::class, 'updateAllClick2CallResponses'])->name('updateAllClick2CallResponses');
Route::post('/getSettings', [APIsController::class, 'getSettings'])->name('getSettings');
Route::post('/tracking', [APIsController::class, 'tracking']);
Route::post('/setFcmKey', [APIsController::class, 'setFcmKey']);
Route::post('/punch', [APIsController::class, 'punch']);
Route::post('/external-login', [APIsController::class, 'loginExternal']);
Route::get('/punch-status/{id}', [APIsController::class, 'punchStatus']);

//Crons
Route::get('/login-notifications', [CronsController::class, 'loginNotifications'])->name('api.login-notifications');
Route::get('/absent-notifications', [CronsController::class, 'absentNotifications'])->name('api.absent-notifications');
Route::get('/emails-sync', [CronsController::class, 'syncEmailsFromImap'])->name('api.emails-sync');
Route::get('/reminder-notifications', [CronsController::class, 'reminderNotifications'])->name('api.reminder-notifications');
Route::get('/click2call/fetch', [CronsController::class, 'fetchClick2call'])->name('api.click2call.fetch');
Route::get('/click2call/fetch1', [CronsController::class, 'fetchClick2CallInBatches'])->name('api.click2call.fetch1');
Route::get('/obd/execute', [CronsController::class, 'executeObdCampaigns'])->name('api.obd.execute');

// Telephony / Call Recordings Webhooks & Callbacks
Route::post('/vi_pingback_api', [TelephonyWebhookController::class, 'handleWebhook'])->name('api.vi_pingback');
Route::match(['get', 'post'], '/telephony/webhook', [TelephonyWebhookController::class, 'handleWebhook'])->name('api.telephony.webhook');
Route::match(['get', 'post'], '/recording/callback', [TelephonyWebhookController::class, 'handleWebhook'])->name('api.recording.callback');
Route::match(['get', 'post'], '/dialer/callback', [TelephonyWebhookController::class, 'handleWebhook'])->name('api.dialer.callback');
// Inbound Calls report: provider events for calls to the company's VMNs (secret checked in the controller)
Route::post('/telephony/inbound-call', [\App\Http\Controllers\APIs\InboundCallWebhookController::class, 'handle'])
    ->middleware('throttle:300,1')->name('api.telephony.inboundCall');

// Dialer API endpoints for workspace widgets & quick actions
Route::prefix('dialer')->group(function () {
    Route::get('/channel-config', [DialerController::class, 'getChannelConfig']);
    Route::get('/today-performance', [DialerController::class, 'getTodayPerformance']);
    Route::get('/recent-activity', [DialerController::class, 'getRecentActivity']);
    Route::post('/quick-note', [DialerController::class, 'quickNote']);
    Route::post('/quick-followup', [DialerController::class, 'quickFollowup']);
    Route::post('/quick-tag', [DialerController::class, 'quickTag']);
    Route::get('/phone-history', [DialerController::class, 'phoneHistory']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

