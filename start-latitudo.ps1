# Запуск локального сайта latitudo.local (Apache + PHP + MariaDB) вручную.
# Зачем: OSPanel v6.5.1 в этом окружении не всегда сам стартует модули,
# а после перезагрузки ПК вручную запущенные программы не поднимаются сами.
# Просто запусти этот файл (правый клик -> "Выполнить с помощью PowerShell")
# или в PowerShell:  powershell -ExecutionPolicy Bypass -File .\start-latitudo.ps1

$ErrorActionPreference = 'SilentlyContinue'

$php   = 'C:\OSPanel\modules\PHP-8.2'
$maria = 'C:\OSPanel\modules\MariaDB-10.11'
$apache= 'C:\OSPanel\modules\Apache'

Write-Host "Запускаю локальный сервер latitudo.local..." -ForegroundColor Cyan

# 1) База данных MariaDB (если ещё не запущена)
if (-not (Get-Process -Name mysqld,mariadbd)) {
  Start-Process -FilePath "$maria\bin\mysqld.exe" `
    -ArgumentList @("--defaults-file=$maria\my.ini") -WindowStyle Hidden
  Write-Host "  [+] MariaDB запущена"
} else { Write-Host "  [=] MariaDB уже работает" }

# 2) PHP (FastCGI). PHP_INI_SCAN_DIR очищаем, чтобы НАШ php.ini не перебивали
#    чужие конфиги от PHP, установленного через scoop (иначе ломается short_open_tag).
if (-not (Get-Process -Name php-cgi)) {
  $env:PHP_INI_SCAN_DIR    = ''
  $env:PHP_FCGI_MAX_REQUESTS = '0'
  Start-Process -FilePath "$php\php-cgi.exe" `
    -ArgumentList @('-b','127.0.1.38:9000','-c',"$php\php.ini") -WindowStyle Hidden
  Write-Host "  [+] PHP запущен"
} else { Write-Host "  [=] PHP уже работает" }

# 3) Apache (веб-сервер)
if (-not (Get-Process -Name httpd)) {
  Start-Process -FilePath "$apache\bin\httpd.exe" `
    -ArgumentList @('-d',$apache,'-f',"$apache\conf\httpd.conf") -WindowStyle Hidden
  Write-Host "  [+] Apache запущен"
} else { Write-Host "  [=] Apache уже работает" }

Start-Sleep -Seconds 3

# Проверка
try {
  $r = Invoke-WebRequest -Uri 'http://latitudo.local/' -UseBasicParsing -TimeoutSec 15
  Write-Host ("`nГотово! Сайт отвечает: HTTP {0}. Открывай http://latitudo.local/" -f [int]$r.StatusCode) -ForegroundColor Green
} catch {
  Write-Host "`nСервер пока не отвечает. Проверь логи: C:\OSPanel\logs\" -ForegroundColor Yellow
}
