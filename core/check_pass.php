<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$u = User::find(12); // Ametecs
echo "User 12: " . $u->email . "\n";
$passwords = ['123456', '12345678', 'password', 'password123', 'Leadger@123', 'admin', 'admin123'];
foreach ($passwords as $p) {
    if (Hash::check($p, $u->password)) {
        echo "Password matches: '$p'!\n";
        break;
    }
}

$u1 = User::find(1); // Ejaz
echo "User 1: " . $u1->email . "\n";
foreach ($passwords as $p) {
    if (Hash::check($p, $u1->password)) {
        echo "Password matches: '$p'!\n";
        break;
    }
}
