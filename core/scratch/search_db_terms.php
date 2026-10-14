<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$terms = ['Dialer Responses', 'Dialer Logs', 'Search CR', 'Responses', 'Dialer', 'CR'];

$tables = DB::select('SHOW TABLES');
$dbName = DB::getDatabaseName();
$prop = "Tables_in_" . $dbName;

foreach ($tables as $t) {
    $table = $t->$prop;
    // Check if table has text columns
    try {
        $cols = DB::select("SHOW COLUMNS FROM `$table`");
        $textCols = [];
        foreach ($cols as $c) {
            if (preg_match('/(char|text|varchar)/i', $c->Type)) {
                $textCols[] = $c->Field;
            }
        }
        if (empty($textCols)) continue;
        
        foreach (['Dialer Responses', 'Dialer Logs', 'Search CR', 'notSaved', 'callReport'] as $search) {
            $where = [];
            foreach ($textCols as $tc) {
                $where[] = "`$tc` LIKE '%" . addslashes($search) . "%'";
            }
            $sql = "SELECT COUNT(*) as cnt FROM `$table` WHERE " . implode(' OR ', $where);
            $res = DB::select($sql);
            if ($res && $res[0]->cnt > 0) {
                echo "Found '$search' in table `$table` ({$res[0]->cnt} matches)\n";
            }
        }
    } catch (\Exception $e) {
        // skip
    }
}
