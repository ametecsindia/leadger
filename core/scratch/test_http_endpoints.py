import urllib.request
import urllib.parse
import http.cookiejar

print("=== RUNNING LIVE HTTP ENDPOINT CHECKS (PORT 8000) ===")

base_url = 'http://192.168.0.127:8000'
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# 1. Login page
try:
    resp = opener.open(base_url + '/admin/login')
    html = resp.read().decode('utf-8', errors='ignore')
    has_form = '<form' in html
    has_csrf = 'name="_token"' in html
    has_css = 'custom.css' in html
    print(f"1. GET /admin/login: Status {resp.status} (Length {len(html)}) | Form: {has_form} | CSRF: {has_csrf} | Custom CSS: {has_css}")
except Exception as e:
    print(f"1. GET /admin/login: ERROR: {e}")

# 2. Assets check
assets = [
    '/assets/dashboard/css/custom.css',
    '/assets/dashboard/css/app.css',
    '/assets/dashboard/js/scripts/app.js'
]
for asset in assets:
    try:
        resp = opener.open(base_url + asset)
        print(f"   Asset {asset}: Status {resp.status} (Length {len(resp.read())})")
    except Exception as e:
        print(f"   Asset {asset}: ERROR {e}")

# 3. Public landing / home
try:
    resp = opener.open(base_url + '/home')
    print(f"3. GET /home: Status {resp.status} (Length {len(resp.read())})")
except Exception as e:
    print(f"3. GET /home: {e}")

# 4. Agreement PDF Generation check (dompdf)
print("\n=== DOMPDF AGREEMENT VERIFICATION ===")
try:
    import subprocess
    p = subprocess.run(['php', '-r', """
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$pdf = app('dompdf.wrapper');
$pdf->loadHTML('<h1>LeadGER Agreement</h1><p>Test PDF generation</p>');
$output = $pdf->output();
echo 'DOMPDF Generated PDF bytes: ' . strlen($output);
"""], cwd='C:/Lt_Leadger_source - Copy 1/core', capture_output=True, text=True)
    print(p.stdout.strip() if p.stdout else p.stderr.strip())
except Exception as e:
    print(f"DOMPDF Error: {e}")

print("\n=== ENDPOINT CHECKS COMPLETE ===")
