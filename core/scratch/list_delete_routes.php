<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;

$routes = Route::getRoutes();
$deleteRoutes = [];

foreach ($routes as $route) {
    $uri = $route->uri();
    $methods = implode('|', $route->methods());
    $action = $route->getActionName();
    
    if (stripos($uri, 'delete') !== false || stripos($uri, 'destroy') !== false || in_array('DELETE', $route->methods())) {
        $deleteRoutes[] = [
            'method' => $methods,
            'uri' => $uri,
            'name' => $route->getName(),
            'action' => $action
        ];
    }
}

foreach ($deleteRoutes as $r) {
    echo sprintf("%-15s %-45s %-30s %s\n", $r['method'], $r['uri'], $r['name'], $r['action']);
}
