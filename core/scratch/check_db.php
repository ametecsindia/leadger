<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$files = glob(storage_path('framework/sessions/*'));
usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
foreach (array_slice($files, 0, 3) as $f) {
    $content = file_get_contents($f);
    $data = @unserialize($content);
    if ($data && is_array($data)) {
        foreach ($data as $k => $v) {
            if (str_starts_with($k, 'login_web_')) {
                $user = \App\Models\User::find($v);
                echo "Session: " . basename($f) . " | User ID: $v | Name: " . ($user ? $user->name : 'N/A') . " | Mobile: [" . ($user ? $user->mobile : '') . "] | Click Agent: [" . ($user ? $user->click_agent_number : '') . "]\n";
            }
        }
    }
}
