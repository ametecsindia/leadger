<?php
$content = file_get_contents('c:/Lt_Leadger_source - Copy 1/core/app/Http/Controllers/Dashboard/ReportsController.php');
preg_match_all('/public function\s+(\w+)\s*\(/', $content, $matches, PREG_OFFSET_CAPTURE);

foreach ($matches[1] as $m) {
    $line = substr_count(substr($content, 0, $m[1]), "\n") + 1;
    echo "Line {$line}: {$m[0]}\n";
}
