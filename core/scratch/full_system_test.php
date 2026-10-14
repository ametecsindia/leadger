<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// Find an admin user
$user = App\Models\User::where('status', 1)->where('permissions_id', 4)->first();
if (!$user) {
    echo "No admin user found\n";
    exit(1);
}

// Find a sample lead and campaign for testing view routes
$sampleLead = DB::table('leads')->first();
$sampleCampaign = DB::table('campaigns')->first();

$routesToTest = [
    // 1. Dashboard
    ['method' => 'GET', 'uri' => '/admin', 'name' => 'Dashboard Root'],
    ['method' => 'GET', 'uri' => '/admin/dashboard', 'name' => 'Dashboard Main'],
    
    // 2. Leads Module
    ['method' => 'GET', 'uri' => '/admin/leads', 'name' => 'Leads List'],
    ['method' => 'GET', 'uri' => '/admin/leads/create', 'name' => 'Create Lead Form'],
    
    // 3. Campaigns Module
    ['method' => 'GET', 'uri' => '/admin/campaigns', 'name' => 'Campaigns List'],
    ['method' => 'GET', 'uri' => '/admin/campaigns/create', 'name' => 'Create Campaign Form'],
    ['method' => 'GET', 'uri' => '/admin/campaigns/performance', 'name' => 'Campaign Performance'],
    
    // 4. Calling & Dialer
    ['method' => 'GET', 'uri' => '/admin/dialer', 'name' => 'Dialer View'],
    ['method' => 'GET', 'uri' => '/admin/click2Call', 'name' => 'Click2Call List'],
    ['method' => 'GET', 'uri' => '/admin/webrtc', 'name' => 'WebRTC List'],
    ['method' => 'GET', 'uri' => '/admin/calling-settings', 'name' => 'Calling Settings'],
    ['method' => 'GET', 'uri' => '/admin/call-channels', 'name' => 'Call Channels Toggle'],
    
    // 5. Reports (including new Call Report and untouched Dialer Logs)
    ['method' => 'GET', 'uri' => '/admin/reports/call-report', 'name' => 'Call Report (Redesigned)'],
    ['method' => 'POST', 'uri' => '/admin/reports/call-report-data', 'name' => 'Call Report AJAX Data', 'data' => ['from_date' => date('Y-m-d', strtotime('-30 days')), 'to_date' => date('Y-m-d')]],
    ['method' => 'GET', 'uri' => '/admin/reports/callLogs', 'name' => 'Dialer Logs (callLogs)'],
    ['method' => 'GET', 'uri' => '/admin/reports/dialerLog', 'name' => 'Dialer Log Report'],
    ['method' => 'GET', 'uri' => '/admin/reports/recordings', 'name' => 'Recordings List'],
    ['method' => 'GET', 'uri' => '/admin/reports/campaignStatus', 'name' => 'Campaign Status Report'],
    ['method' => 'GET', 'uri' => '/admin/reports/userStatus', 'name' => 'User Status Report'],
    ['method' => 'GET', 'uri' => '/admin/reports/userLogs', 'name' => 'User Logs Report'],
    ['method' => 'GET', 'uri' => '/admin/reports/agentPerformance', 'name' => 'Agent Performance Report'],
    ['method' => 'GET', 'uri' => '/admin/reports/allocations', 'name' => 'Allocations Report'],
    
    // 6. AI & Call Intelligence
    ['method' => 'GET', 'uri' => '/admin/settings/ai-call-intelligence', 'name' => 'AI Call Intelligence Settings'],
    ['method' => 'GET', 'uri' => '/admin/reports/ai-analysis', 'name' => 'AI Analysis Report'],
    ['method' => 'GET', 'uri' => '/admin/reports/call-transcripts', 'name' => 'Call Transcripts Report'],
    
    // 7. Users & Permissions
    ['method' => 'GET', 'uri' => '/admin/users', 'name' => 'Staff / Users List'],
    ['method' => 'GET', 'uri' => '/admin/users/create', 'name' => 'Create User Form'],
    ['method' => 'GET', 'uri' => '/admin/users/user-groups', 'name' => 'User Permissions Groups'],
    ['method' => 'GET', 'uri' => '/admin/teams', 'name' => 'Teams List'],
    
    // 8. Master Data & Configuration
    ['method' => 'GET', 'uri' => '/admin/statuses', 'name' => 'Statuses List'],
    ['method' => 'GET', 'uri' => '/admin/statuses/create', 'name' => 'Create Status Form'],
    ['method' => 'GET', 'uri' => '/admin/disposition-settings', 'name' => 'Disposition Settings'],
    ['method' => 'GET', 'uri' => '/admin/sources', 'name' => 'Sources List'],
    ['method' => 'GET', 'uri' => '/admin/productServices', 'name' => 'Products & Services List'],
    ['method' => 'GET', 'uri' => '/admin/clientTypes', 'name' => 'Client Types List'],
    ['method' => 'GET', 'uri' => '/admin/settings', 'name' => 'General Settings'],
    ['method' => 'GET', 'uri' => '/admin/webmaster', 'name' => 'Webmaster Settings'],
];

if ($sampleLead) {
    $routesToTest[] = ['method' => 'GET', 'uri' => '/admin/leads/' . $sampleLead->id . '/view', 'name' => 'Lead Detail View'];
}

if ($sampleCampaign) {
    $routesToTest[] = ['method' => 'GET', 'uri' => '/admin/campaigns/' . $sampleCampaign->id . '/view', 'name' => 'Campaign Detail View'];
    $routesToTest[] = ['method' => 'GET', 'uri' => '/admin/campaigns/' . $sampleCampaign->id . '/edit', 'name' => 'Campaign Edit View'];
}

echo "=== FULL MODULE-BY-MODULE FUNCTIONAL TESTING ===\n";
echo "Testing as Admin: {$user->name} ({$user->email})\n";
echo str_repeat('-', 75) . "\n";

$passed = 0;
$failed = 0;
$results = [];

foreach ($routesToTest as $route) {
    // Authenticate before each request to avoid session state bleed
    Illuminate\Support\Facades\Auth::login($user);
    
    $method = $route['method'];
    $uri = $route['uri'];
    $name = $route['name'];
    $data = $route['data'] ?? [];
    
    try {
        $req = Illuminate\Http\Request::create('http://127.0.0.1:8000' . $uri, $method, $data);
        // Add CSRF and session cookies simulation
        $req->setLaravelSession($app['session']->driver());
        
        $res = $kernel->handle($req);
        $status = $res->getStatusCode();
        $len = strlen($res->getContent());
        
        $isOk = ($status >= 200 && $status < 400);
        if ($isOk) {
            $passed++;
            $statusStr = "PASS ($status)";
        } else {
            $failed++;
            $statusStr = "FAIL ($status)";
        }
        
        printf("%-35s | %-4s %-32s | %-10s | %6d bytes\n", $name, $method, $uri, $statusStr, $len);
        
        if (!$isOk) {
            // Get snippet of error
            $content = $res->getContent();
            $snippet = substr(strip_tags($content), 0, 150);
            echo "   -> Error snippet: " . trim($snippet) . "\n";
        }
    } catch (\Throwable $e) {
        $failed++;
        printf("%-35s | %-4s %-32s | EXCEPTION  | %s\n", $name, $method, $uri, $e->getMessage());
    }
}

echo str_repeat('-', 75) . "\n";
echo "Total Tested: " . count($routesToTest) . " | Passed: $passed | Failed: $failed\n";
