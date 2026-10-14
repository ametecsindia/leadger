<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$backupDir = 'c:/Lt_Leadger_source - Copy 1/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}
$ts = date('Ymd_His');
$backupPath = $backupDir . '/db_backup_before_agent_cleanup_' . $ts . '.sql';

// Try mysqldump
$mysqldumpPath = 'mysqldump';
exec("$mysqldumpPath -u root lt_leadger > \"$backupPath\" 2>&1", $out, $ret);
if ($ret !== 0) {
    // try common xampp path if needed
    $xamppDump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    if (file_exists($xamppDump)) {
        exec("\"$xamppDump\" -u root lt_leadger > \"$backupPath\" 2>&1", $out, $ret);
    }
}

echo "BACKUP_PATH: $backupPath (exit: $ret)\n";

$users = DB::table('users')
    ->leftJoin('permissions', 'permissions.id', '=', 'users.permissions_id')
    ->leftJoin('teams', 'teams.id', '=', 'users.team_id')
    ->select('users.id', 'users.name', 'users.email', 'permissions.name as role', 'teams.name as team_name')
    ->orderBy('users.id', 'asc')
    ->get();

echo "--- USERS LIST ---\n";
foreach ($users as $u) {
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Role: " . ($u->role ?? 'N/A') . " | Team: " . ($u->team_name ?? 'N/A') . "\n";
}
