@echo off
rem ---------------------------------------------------------------------------
rem  Start of the local site latitudo-pro.local (OSPanel: Apache + PHP + MySQL).
rem  Double-click this file in Explorer.
rem
rem  This launcher is intentionally ASCII-only. cmd.exe decodes a batch file with
rem  the current console codepage, and "chcp 65001" does not always take effect
rem  in time -- Cyrillic comments then turned into garbage and broke parsing of
rem  whole blocks (Apache silently did not start). All logic, with the Russian
rem  messages, lives in tools/start-latitudo.ps1 -- PowerShell reads UTF-8 fine.
rem ---------------------------------------------------------------------------

set "PS=powershell"
where pwsh >nul 2>&1 && set "PS=pwsh"

"%PS%" -NoProfile -ExecutionPolicy Bypass -File "%~dp0tools\start-latitudo.ps1"

echo.
pause
