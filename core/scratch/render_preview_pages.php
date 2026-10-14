<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
Illuminate\Support\Facades\Auth::login($user);

$previewDir = __DIR__ . '/../public/previews';
if (!is_dir($previewDir)) {
    mkdir($previewDir, 0755, true);
}

echo "=== RENDERING FULL SUITE OF PREVIEW PAGES ===\n";

$pages = [
    'dashboard_preview.html' => [new App\Http\Controllers\Dashboard\DashboardController(), 'index'],
    'dialer_preview.html'    => [new App\Http\Controllers\Dashboard\Click2CallController(), 'index'],
    'webrtc_preview.html'    => [new App\Http\Controllers\Dashboard\WebRTCController(), 'index'],
    'leads_preview.html'     => [new App\Http\Controllers\Dashboard\LeadsController(), 'index'],
    'campaigns_preview.html' => [new App\Http\Controllers\Dashboard\CampaignsController(), 'index'],
    'campaign_status.html'   => [new App\Http\Controllers\Dashboard\ReportsController(), 'campaignStatus'],
    'agent_performance.html' => [new App\Http\Controllers\Dashboard\ReportsController(), 'agentPerformance'],
    'call_logs_preview.html' => [new App\Http\Controllers\Dashboard\ReportsController(), 'callLogs'],
    'call_channels_preview.html' => [new App\Http\Controllers\Dashboard\CallChannelsController(), 'index'],
];

foreach ($pages as $filename => $action) {
    try {
        $res = app()->call($action);
        if ($res instanceof \Illuminate\View\View) {
            $html = $res->render();
            file_put_contents($previewDir . '/' . $filename, $html);
            echo "[OK] " . $filename . " (" . number_format(strlen($html)) . " bytes)\n";
        }
    } catch (\Throwable $e) {
        echo "[FAIL] " . $filename . ": " . $e->getMessage() . "\n";
    }
}

echo "=== ALL PREVIEWS RENDERED TO public/previews/ ===\n";
