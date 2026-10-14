<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "APP_URL: " . config('app.url') . "\n";
echo "allSubdomains: " . (new App\Http\Middleware\TrustHosts(app()))->hosts()[0] . "\n";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('http://127.0.0.1:8000/admin/login', 'GET');
try {
    $response = $kernel->handle($request);
    echo "STATUS: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() !== 200) {
        $content = $response->getContent();
        // find exception comment in HTML
        if (preg_match('/<!--\s*(.*?)\s*-->/s', $content, $m)) {
            echo "Exception trace from HTML:\n" . $m[1] . "\n";
        } else {
            echo "Response snippet:\n" . substr($content, 0, 1000) . "\n";
        }
    } else {
        echo "Rendered successfully! Bytes: " . strlen($response->getContent()) . "\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
