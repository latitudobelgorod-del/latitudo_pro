@echo off
chcp 65001 > nul
title Запуск latitudo-pro.local
:: -----------------------------------------------------------------------------
::  Запуск локального сайта latitudo-pro.local (OSPanel: Apache + PHP + MySQL).
::
::  Зачем этот файл: OSPanel v6.5.1 после перезагрузки ПК не всегда сам поднимает
::  нужные модули (в частности, базу MySQL-5.7). Скрипт стартует их напрямую,
::  если они ещё не запущены. Запуск через штатный CLI `osp` тут не используется:
::  osp базу MySQL-5.7 не поднимает и не работает из PowerShell.
::
::  Как запускать: ДВОЙНЫМ КЛИКОМ из проводника (или из cmd).
::  Панель OSPanel (ospanel.exe) должна быть уже запущена — иконка в трее.
::
::  ВАЖНО про адреса (не менять, взято из vhost OSPanel и bitrix/.settings.php):
::    - Bitrix ждёт базу на 127.0.1.26  -> модуль MySQL-5.7 (НЕ MariaDB!)
::    - Apache проксирует PHP на 127.0.1.35:9000 -> модуль PHP-8.2
:: -----------------------------------------------------------------------------

set "MYSQL=C:\OSPanel\modules\MySQL-5.7"
set "PHP=C:\OSPanel\modules\PHP-8.2"
set "APACHE=C:\OSPanel\modules\Apache"

echo Запускаю локальный сервер latitudo-pro.local...
echo.

:: 1) База данных MySQL-5.7 (Bitrix подключается к ней на 127.0.1.26)
tasklist /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" > nul
if errorlevel 1 (
    start "" /B "%MYSQL%\bin\mysqld.exe" --defaults-file="%MYSQL%\my.ini"
    echo   [+] MySQL-5.7 запущена
) else (
    echo   [=] MySQL-5.7 уже работает
)

:: 2) PHP-8.2 FastCGI на 127.0.1.35:9000. Чистим PHP_INI_SCAN_DIR, чтобы наш
::    php.ini не перебивали чужие конфиги (например, от PHP через scoop).
tasklist /FI "IMAGENAME eq php-cgi.exe" | find /I "php-cgi.exe" > nul
if errorlevel 1 (
    set "PHP_INI_SCAN_DIR="
    start "" /B "%PHP%\php-cgi.exe" -b 127.0.1.35:9000 -c "%PHP%\php.ini"
    echo   [+] PHP-8.2 запущен
) else (
    echo   [=] PHP-8.2 уже работает
)

:: 3) Apache (веб-сервер)
tasklist /FI "IMAGENAME eq httpd.exe" | find /I "httpd.exe" > nul
if errorlevel 1 (
    start "" /B "%APACHE%\bin\httpd.exe" -d "%APACHE%" -f "%APACHE%\conf\httpd.conf"
    echo   [+] Apache запущен
) else (
    echo   [=] Apache уже работает
)

timeout /t 3 /nobreak > nul

echo.
echo Проверяю ответ сайта...
for /f %%c in ('"C:\OSPanel\bin\curl.exe" -s -o nul -w "%%{http_code}" http://latitudo-pro.local/') do set "CODE=%%c"

if "%CODE%"=="200" (
    echo Готово! Сайт отвечает: HTTP %CODE%. Открывай http://latitudo-pro.local/
) else (
    echo Сайт вернул HTTP "%CODE%". Проверь, что панель OSPanel запущена,
    echo и загляни в логи: C:\OSPanel\logs\ и C:\OSPanel\logs\MySQL-5.7.log
)

echo.
pause
