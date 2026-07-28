@echo off
chcp 65001 >nul
title Apagar Sistema POS

set "RAIZ=%~dp0"
set "MYSQL=%RAIZ%xampp\mysql\bin"

cls
echo.
echo   ============================================
echo      APAGANDO EL SISTEMA POS
echo   ============================================
echo.

REM Respaldo antes de apagar: nunca se pierde el trabajo del dia
echo   Guardando copia de seguridad...
call "%RAIZ%RESPALDAR AHORA.bat" silencioso
echo   Copia guardada.
echo.

echo   Cerrando la base de datos...
"%MYSQL%\mysqladmin.exe" -u root shutdown >nul 2>&1
ping -n 3 127.0.0.1 >nul
taskkill /F /IM mysqld.exe >nul 2>&1

echo   Cerrando el servidor...
taskkill /F /IM httpd.exe >nul 2>&1

color 0A
echo.
echo   ============================================
echo    SISTEMA APAGADO CORRECTAMENTE
echo   ============================================
echo.
echo   Ya puedes apagar el computador.
echo.
ping -n 4 127.0.0.1 >nul
exit
