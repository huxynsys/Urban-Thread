$out = @()
$out += '=== served js/contact.js fetch URLs ==='
$cj = curl.exe -s 'http://localhost:8080/urban-thread/js/contact.js'
$out += (([regex]::Matches(($cj -join ''), 'fetch\([^)]*') | ForEach-Object { $_.Value }) -join "`n")
$out += '=== local js/contact.js fetch URLs ==='
$lc = Get-Content 'c:\Users\HUXYN\Downloads\urban-thread\js\contact.js' -Raw
$out += (([regex]::Matches($lc, 'fetch\([^)]*') | ForEach-Object { $_.Value }) -join "`n")
$out += '=== served index.php product links (unique) ==='
$ix = curl.exe -s 'http://localhost:8080/urban-thread/index.php'
$out += ((([regex]::Matches(($ix -join ''), '[a-zA-Z0-9_./-]*product\.php\?[^"''`< ]*') | ForEach-Object { $_.Value } | Select-Object -Unique)) -join "`n")
$out += '=== served js/index.js product links (unique) ==='
$ij = curl.exe -s 'http://localhost:8080/urban-thread/js/index.js'
$out += ((([regex]::Matches(($ij -join ''), '[a-zA-Z0-9_./-]*product\.php\?[^"''`< ]*') | ForEach-Object { $_.Value } | Select-Object -Unique)) -join "`n")
$out += '=== served js/shop.js product links (unique) ==='
$sj = curl.exe -s 'http://localhost:8080/urban-thread/js/shop.js'
$out += ((([regex]::Matches(($sj -join ''), '[a-zA-Z0-9_./-]*product\.php\?[^"''`< ]*') | ForEach-Object { $_.Value } | Select-Object -Unique)) -join "`n")
$out | Set-Content 'c:\Users\HUXYN\Downloads\urban-thread\_diag.txt'
Write-Output done