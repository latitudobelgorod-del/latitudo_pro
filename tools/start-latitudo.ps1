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
#  Проверка идёт ПО ЗАНЯТОМУ ПОРТУ, а не по имени процесса — в системе крутится
#  чужой httpd.exe (служба RAIDXpert2 от AMD), и проверка по имени заставляла
#  скрипт считать, что Apache уже работает.
#
#  Адреса (взяты из vhost OSPanel и bitrix/.settings.php, не менять):
#    - MySQL-5.7  127.0.1.26:3306   — Bitrix ждёт базу именно там (НЕ MariaDB!)
#    - PHP-8.2    127.0.1.35:9000   — FastCGI-бэкенд для Apache
#    - Apache     127.0.1.11:80     — вхост сайта, домены прописаны в hosts
# =============================================================================

$ErrorActionPreference = 'Stop'

# Корень OSPanel определяем по расположению проекта: на ноутбуке это D:\OSPanel,
# на прежнем стационарном компе было C:\OSPanel. Хардкода диска нет.
$project = Split-Path -Parent $PSScriptRoot
$osp     = Split-Path -Parent (Split-Path -Parent $project)

$mysql  = Join-Path $osp 'modules\MySQL-5.7'
$php    = Join-Path $osp 'modules\PHP-8.2'
$apache = Join-Path $osp 'modules\Apache'

# Apache запускаем СВОИМ конфигом: штатный конфиг OSPanel слушает 443, а этот порт
# занимает служба RAIDXpert2 под SYSTEM, и Apache падает с
# «AH00072: make_sock: could not bind to address 127.0.1.11:443».
$httpdConf = Join-Path $project '.osp\apache\httpd-standalone.conf'
if (-not (Test-Path $httpdConf)) { $httpdConf = Join-Path $apache 'conf\httpd.conf' }

function Test-Listening([string]$address, [int]$port) {
    $null -ne (Get-NetTCPConnection -State Listen -LocalAddress $address -LocalPort $port -ErrorAction SilentlyContinue)
}

Write-Host "Запускаю локальный сервер latitudo-pro.local..."
Write-Host "  OSPanel: $osp"
Write-Host ""

# 1) MySQL-5.7. После аварийного выключения ПК поднимается до минуты:
#    InnoDB восстанавливает журнал, и порт появляется не сразу.
if (Test-Listening '127.0.1.26' 3306) {
    Write-Host "  [=] MySQL-5.7 уже работает"
} else {
    Start-Process -FilePath "$mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=$mysql\my.ini" -WindowStyle Hidden
    Write-Host "  [+] MySQL-5.7 запущена"
}

# 2) PHP-8.2 FastCGI.
#    PHP_FCGI_MAX_REQUESTS=0 обязателен: иначе php-cgi завершается после 500 запросов
#    и сайт молча перестаёт отвечать. PHP_INI_SCAN_DIR пустой — чтобы наш php.ini
#    не перебивали чужие конфиги (например, от PHP, поставленного через scoop).
if (Test-Listening '127.0.1.35' 9000) {
    Write-Host "  [=] PHP-8.2 уже работает"
} else {
    $env:PHP_FCGI_MAX_REQUESTS = '0'
    $env:PHP_INI_SCAN_DIR = ''
    Start-Process -FilePath "$php\php-cgi.exe" -ArgumentList @('-b', '127.0.1.35:9000', '-c', "$php\php.ini") -WorkingDirectory $php -WindowStyle Hidden
    Write-Host "  [+] PHP-8.2 запущен"
}

# 3) Apache.
if (Test-Listening '127.0.1.11' 80) {
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
    try {
        $r = Invoke-WebRequest 'http://latitudo-pro.local/' -UseBasicParsing -TimeoutSec 60
        $code = [int]$r.StatusCode
    } catch {
        $code = if ($_.Exception.Response) { [int]$_.Exception.Response.StatusCode } else { 0 }
    }
    if ($code -eq 200) { break }
}

Write-Host ""
if ($code -eq 200) {
    Write-Host "Готово! Сайт отвечает: HTTP 200. Открывай http://latitudo-pro.local/"
} else {
    Write-Host "Сайт вернул HTTP $code (0 = соединение не установилось)."
    Write-Host "Смотри логи:"
    Write-Host "  $osp\logs\domains\latitudo-pro.local_error.log"
    Write-Host "  $osp\logs\domains\latitudo-pro.local_php_error.log"
    Write-Host "  $osp\logs\Apache\apache_error.log"
}
