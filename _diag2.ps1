$out = @()
foreach ($log in @('D:\xampp\php\logs\php_error_log.log','D:\xampp\apache\logs\error.log')) {
  if (Test-Path $log) {
    $out += "=== $log (last 25 lines) ==="
    Get-Content $log -Tail 25 | ForEach-Object { $out += $_ }
  } else {
    $out += "missing: $log"
  }
}
$out += '=== GET products/list ==='
$r = curl.exe -s 'http://localhost:8080/urban-thread/api/products/list.php?limit=2'
$out += ($r -join '')
$out += ''
$out += '=== GET products/single?id=1 ==='
$r = curl.exe -s 'http://localhost:8080/urban-thread/api/products/single.php?id=1'
$out += ($r -join '')
$out += ''
$out += '=== GET products/single?id=5 ==='
$r = curl.exe -s 'http://localhost:8080/urban-thread/api/products/single.php?id=5'
$out += ($r -join '')
$out += ''
$out += '=== POST contact/submit ==='
$r = curl.exe -s -X POST 'http://localhost:8080/urban-thread/api/contact/submit.php' -H 'Content-Type: application/json' -H 'Origin: http://localhost:8080' -d '{\"name\":\"Test User\",\"email\":\"diag@example.com\",\"subject\":\"API Test\",\"message\":\"This is a test message from diagnostics.\"}'
$out += ($r -join '')
$out | Set-Content 'c:\Users\HUXYN\Downloads\urban-thread\_diag.txt'
Write-Output done