import urllib.request
import urllib.parse
import http.cookiejar
import re

print("=== TESTING AUTHENTICATED WORKFLOWS ===")
base_url = 'http://192.168.0.127:8000'
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# 1. Fetch login page to get CSRF token
resp = opener.open(base_url + '/admin/login')
html = resp.read().decode('utf-8', errors='ignore')

token_match = re.search(r'name="_token"\s+value="([^"]+)"', html) or re.search(r'value="([^"]+)"\s+name="_token"', html)
if not token_match:
    token_match = re.search(r'<input[^>]+name=["\']_token["\'][^>]+value=["\']([^"\']+)["\']', html)
if not token_match:
    token_match = re.search(r'<input[^>]+value=["\']([^"\']+)["\'][^>]+name=["\']_token["\']', html)

csrf_token = token_match.group(1) if token_match else None
print(f"Extracted CSRF token: {csrf_token}")

# 2. Check test login or direct authenticated session via artisan tinker or test script
# Let's run a kernel test simulating authenticated admin user to check dashboard routes:
import subprocess
p = subprocess.run(['php', '-r', """
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Http\\Kernel::class);

$user = App\\Models\\User::first();
if (!$user) {
    echo "No user found in database\\n";
    exit(0);
}
Illuminate\\Support\\Facades\\Auth::login($user);

$routes = [
    '/admin' => 'Dashboard Home',
    '/admin/leads' => 'Leads List',
    '/admin/campaigns' => 'Campaigns List',
    '/admin/users' => 'Users List',
    '/admin/sources' => 'Sources List',
    '/admin/statuses' => 'Statuses List',
    '/admin/productservices' => 'Products List',
    '/admin/click2call' => 'Click2Call List',
    '/admin/webrtc' => 'WebRTC List'
];

foreach ($routes as $uri => $name) {
    $req = Illuminate\\Http\\Request::create('http://192.168.0.127:8000' . $uri, 'GET');
    $res = $kernel->handle($req);
    $status = $res->getStatusCode();
    $len = strlen($res->getContent());
    echo "$name ($uri): Status $status | Length $len\\n";
}
"""], cwd='C:/Lt_Leadger_source - Copy 1/core', capture_output=True, text=True)

print(p.stdout)
if p.stderr:
    print("STDERR:\n", p.stderr[:1000])
