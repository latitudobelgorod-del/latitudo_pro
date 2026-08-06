# =============================================================================
#  Запуск локального сайта latitudo-pro.local (OSPanel: Apache + PHP + MySQL).
#
#  Вызывается из start-latitudo.cmd в корне проекта (двойной клик из проводника).
#  Почему логика здесь, а не в .cmd: cmd.exe разбирает батник в кодировке консоли,
#  и «chcp 65001» применяется не всегда — при запуске с перенаправлённым выводом
#  кириллица в комментариях превращалась в мусор, ломая разбор целых блоков
#  (Apache молча не стартовал). PowerShell читает UTF-8 без таких сюрпризов.
#
#  Скрипт НИЧЕГО НЕ ГАСИТ и не перезапускает: поднимает только то, что не поднято.
#  Работает на любой машине с OSPanel — корень панели вычисляется от расположения
#  проекта, адреса модулей берутся из её же конфигов, а не зашиты в код.
# =============================================================================

$ErrorActionPreference = 'Stop'

$project = Split-Path -Parent $PSScriptRoot
$osp     = Split-Path -Parent (Split-Path -Parent $project)
$siteUrl = 'http://latitudo-pro.local/'

$mysql  = Join-Path $osp 'modules\MySQL-5.7'
$php    = Join-Path $osp 'modules\PHP-8.2'
$apache = Join-Path $osp 'modules\Apache'

# Apache запускаем своим конфигом, если он собран. На этом ноутбуке это обязательно:
# служба RAIDXpert2 (веб-панель RAID от AMD, под SYSTEM) держит 0.0.0.0:443, и штатный
# конфиг OSPanel падает с «AH00072: make_sock: could not bind to address …:443».
# Где такой службы нет, файла тоже нет — и берётся штатный конфиг панели.
$httpdConf = Join-Path $project '.osp\apache\httpd-standalone.conf'
if (-not (Test-Path $httpdConf)) { $httpdConf = Join-Path $apache 'conf\httpd.conf' }

function Get-SiteStatus {
    try   { [int](Invoke-WebRequest $siteUrl -UseBasicParsing -TimeoutSec 15).StatusCode }
    catch { if ($_.Exception.Response) { [int]$_.Exception.Response.StatusCode } else { 0 } }
}

function Test-PortBusy([int]$port) {
    # Адрес не указываем намеренно: OSPanel назначает модулям адреса вида 127.0.1.x
    # автоматически, и на другой машине они другие. Порт же остаётся тем же.
    $null -ne (Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue)
}

function Test-OurProcess([string]$name) {
    # Фильтр по пути обязателен: в системе крутится ЧУЖОЙ httpd.exe (RAIDXpert2),
    # и проверка по одному имени заставляла скрипт считать Apache запущенным.
    $null -ne (Get-CimInstance Win32_Process -Filter "Name='$name'" -ErrorAction SilentlyContinue |
               Where-Object { $_.ExecutablePath -and $_.ExecutablePath.StartsWith($osp, 'OrdinalIgnoreCase') })
}

Write-Host "Локальный сайт latitudo-pro.local"
Write-Host "  OSPanel: $osp"
Write-Host ""

# Главная проверка — ответ самого сайта. Если он отвечает, значит поднято всё,
# что нужно, и неважно, кто это сделал: панель, прошлый запуск или этот скрипт.
if ((Get-SiteStatus) -eq 200) {
    Write-Host "  [=] Уже работает — сайт отвечает HTTP 200"
    Write-Host ""
    Write-Host "Открывай $siteUrl"
    return
}

# 1) MySQL-5.7. После аварийного выключения ПК поднимается до минуты:
#    InnoDB восстанавливает журнал, и порт появляется не сразу.
if (Test-PortBusy 3306) {
    Write-Host "  [=] MySQL уже работает"
} else {
    Start-Process -FilePath "$mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=$mysql\my.ini" -WindowStyle Hidden
    Write-Host "  [+] MySQL-5.7 запущена"
}

# 2) PHP-8.2 FastCGI.
#    PHP_FCGI_MAX_REQUESTS=0 обязателен: иначе php-cgi завершается после 500 запросов
#    и сайт молча перестаёт отвечать. PHP_INI_SCAN_DIR пустой — чтобы наш php.ini
#    не перебивали чужие конфиги (например, от PHP, поставленного через scoop).
if (Test-PortBusy 9000) {
    Write-Host "  [=] PHP уже работает"
} else {
    $env:PHP_FCGI_MAX_REQUESTS = '0'
    $env:PHP_INI_SCAN_DIR = ''
    Start-Process -FilePath "$php\php-cgi.exe" -ArgumentList @('-b', '127.0.1.35:9000', '-c', "$php\php.ini") -WorkingDirectory $php -WindowStyle Hidden
    Write-Host "  [+] PHP-8.2 запущен"
}

# 3) Apache. Здесь смотрим не на порт, а на процесс ИМЕННО ЭТОЙ панели: порт 80
#    может занимать и стартовая страница самой OSPanel, и вхост другого проекта.
if (Test-OurProcess 'httpd.exe') {
    Write-Host "  [=] Apache уже работает"
} else {
    Start-Process -FilePath "$apache\bin\httpd.exe" -ArgumentList @('-d', $apache, '-f', $httpdConf) -WindowStyle Hidden
    Write-Host "  [+] Apache запущен (конфиг: $(Split-Path -Leaf $httpdConf))"
}

Write-Host ""
Write-Host "Жду готовности базы и проверяю ответ сайта..."

$code = 0
for ($i = 0; $i -lt 24; $i++) {
    Start-Sleep -Seconds 5
    $code = Get-SiteStatus
    if ($code -eq 200) { break }
}

Write-Host ""
if ($code -eq 200) {
    Write-Host "Готово! Сайт отвечает HTTP 200. Открывай $siteUrl"
} else {
    Write-Host "Сайт вернул HTTP $code (0 = соединение не установилось)."
    Write-Host "Смотри логи:"
    Write-Host "  $osp\logs\domains\latitudo-pro.local_error.log"
    Write-Host "  $osp\logs\domains\latitudo-pro.local_php_error.log"
    Write-Host "  $osp\logs\Apache\apache_error.log"
    Write-Host ""
    Write-Host "Если Apache не поднялся — проверь, не занят ли порт 443 чужой службой"
    Write-Host "(на ноутбуке это RAIDXpert2 от AMD): штатный конфиг OSPanel тогда не стартует."
}
