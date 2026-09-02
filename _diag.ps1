$out = @()
$out += "xampp exists: $(Test-Path 'C:\xampp')"
$ports = Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -WarningAction SilentlyContinue
$out += "mysql 3306 reachable: $($ports.TcpTestSucceeded)"
$ports2 = Test-NetConnection -ComputerName 127.0.0.1 -Port 8080 -WarningAction SilentlyContinue
$out += "apache 8080 reachable: $($ports2.TcpTestSucceeded)"
$ports3 = Test-NetConnection -ComputerName 127.0.0.1 -Port 80 -WarningAction SilentlyContinue
$out += "apache 80 reachable: $($ports3.TcpTestSucceeded)"

foreach ($log in @('C:\xampp\php\logs\php_error_log.log','C:\xampp\php\logs\php_error_log','C:\xampp\apache\logs\error.log','C:\xampp\mysql\data\mysql_error.log')) {
  if (Test-Path $log) {
    $out += "=== $log (last 30 lines) ==="
    Get-Content $log -Tail 30 | ForEach-Object { $out += $_ }
  } else {
    $out += "missing: $log"
  }
}
$out | Set-Content 'c:\Users\HUXYN\Downloads\urban-thread\_diag.txt'
Write-Output done