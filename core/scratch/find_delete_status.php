<?php
$dir = __DIR__ . '/../';
$results = [];

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($it as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (strpos($path, 'vendor') !== false || strpos($path, '.git') !== false || strpos($path, 'storage') !== false) continue;
    if (!preg_match('/\.(php|blade\.php)$/', $path)) continue;

    $content = file_get_contents($path);
    if (strpos($content, 'delete_status') !== false || strpos($content, 'canDelete') !== false || strpos($content, 'manager_delete') !== false) {
        $results[] = str_replace($dir, '', $path);
    }
}

print_r($results);
