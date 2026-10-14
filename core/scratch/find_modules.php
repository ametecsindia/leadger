<?php
$dir = __DIR__ . '/../';
$keywords = ['Dialer Responses', 'Dialer Responses', 'Search CR', 'search_cr', 'dialer_responses', 'dialer_logs', 'call_logs', 'c2c', 'click2call', 'campaigns'];

function searchFiles($folder, $terms) {
    $results = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isDir()) continue;
        $path = $file->getPathname();
        if (strpos($path, 'vendor') !== false || strpos($path, '.git') !== false || strpos($path, 'node_modules') !== false || strpos($path, 'storage') !== false) {
            continue;
        }
        $content = file_get_contents($path);
        foreach ($terms as $term) {
            if (stripos($content, $term) !== false) {
                $results[$term][] = $path;
            }
        }
    }
    return $results;
}

$found = searchFiles($dir, ['Dialer Responses', 'Search CR', 'dialer responses', 'search cr', 'responses', 'CR']);
foreach ($found as $term => $files) {
    echo "=== TERM: $term (count: " . count($files) . ") ===\n";
    foreach (array_slice($files, 0, 10) as $f) {
        echo "  " . str_replace($dir, '', $f) . "\n";
    }
}
