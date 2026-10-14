<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'admin@ametecs.com')->first();
if ($user) {
    $user->password = \Illuminate\Support\Facades\Hash::make('123456');
    $user->save();
    echo "Successfully updated password for admin@ametecs.com to '123456'\n";
}
