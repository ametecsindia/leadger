<?php

use App\Http\Controllers\Click2Call\AgentController;
use App\Http\Controllers\Click2Call\CallController;
use App\Http\Controllers\Click2Call\VoucherController;
use App\Http\Controllers\Click2Call\WebhookController;
use Illuminate\Support\Facades\Route;

/*
| Click2Call kit routes - loaded by App\Providers\Click2CallServiceProvider.
| Browser routes: logged-in users (session + CSRF). Admin pages: the "c2c-admin" gate.
| Webhook: no session, no CSRF; protected by the shared secret (and optional IP allow-list), rate limited.
*/

Route::middleware(['web', 'auth'])->prefix('click2call')->name('c2c.')->group(function () {
    // calling page + call endpoints (any user with Click2Call enabled; each call only by its agent or an admin)
    Route::get('/', [CallController::class, 'dialer'])->name('dialer');
    Route::get('/pool/{pool}', [CallController::class, 'pool'])->name('pool');
    Route::post('/call', [CallController::class, 'start'])->middleware('throttle:20,1')->name('start');
    Route::get('/call/{call}/status', [CallController::class, 'status'])->name('status');
    Route::post('/call/{call}/outcome', [CallController::class, 'outcome'])->name('outcome');
    Route::get('/call/{call}/recording', [CallController::class, 'recording'])->name('recording');

    // admin: Add Voucher (VMN numbers) and Click2Call users
    Route::middleware('can:c2c-admin')->group(function () {
        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers');
        Route::post('/vouchers', [VoucherController::class, 'add'])->middleware('throttle:10,1')->name('vouchers.add');
        Route::post('/vmns/bulk', [VoucherController::class, 'bulk'])->name('vmns.bulk');
        Route::post('/vmns/{vmn}', [VoucherController::class, 'update'])->name('vmns.update');
        Route::delete('/vmns/{vmn}', [VoucherController::class, 'destroy'])->name('vmns.destroy');
        Route::get('/agents', [AgentController::class, 'index'])->name('agents');
        Route::post('/agents/{user}', [AgentController::class, 'update'])->name('agents.update');
    });
});

Route::post('/api/click2call/webhook', [WebhookController::class, 'handle'])
    ->middleware('throttle:120,1')->name('c2c.webhook');
