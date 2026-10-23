# Runs tests/js/*.test.html in headless Chrome (no Node needed). From core/:  powershell -File tests/js/run-js-tests.ps1
$ErrorActionPreference = 'Stop'
$core = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$chrome = @('C:\Program Files\Google\Chrome\Application\chrome.exe', 'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe') | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $chrome) { throw 'Google Chrome not found' }

$port = 8099
$server = Start-Process -FilePath php -ArgumentList @('-S', "127.0.0.1:$port", '-t', "`"$core`"") -PassThru -WindowStyle Hidden
$profile = Join-Path $env:TEMP ('js-tests-' + [guid]::NewGuid())
$failed = $false
try {
    Start-Sleep -Seconds 1
    foreach ($test in Get-ChildItem (Join-Path $PSScriptRoot '*.test.html')) {
        $url = "http://127.0.0.1:$port/tests/js/$($test.Name)"
        $dom = & $chrome --headless=new --disable-gpu --no-first-run "--user-data-dir=$profile" --virtual-time-budget=5000 --dump-dom $url 2>$null | Out-String
        $text = if ($dom -match '(?s)<pre id="out">(.*?)</pre>') { [System.Net.WebUtility]::HtmlDecode($Matches[1]) } else { 'no result' }
        Write-Output "== $($test.Name)"
        Write-Output $text
        if ($text -notmatch 'JS TESTS: PASS') { $failed = $true }
    }
}
finally {
    Stop-Process -Id $server.Id -Force -ErrorAction SilentlyContinue
    Remove-Item $profile -Recurse -Force -ErrorAction SilentlyContinue
}
if ($failed) { exit 1 }
