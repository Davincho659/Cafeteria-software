@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
title Apagar Sistema POS
color 0E

REM ===================================================================
REM  APAGAR POS
REM ===================================================================
REM  Cierra el sistema de forma ORDENADA. Es importante usar esto y no
REM  cerrar la ventana a lo bruto: si MySQL se mata mientras escribe,
REM  la base de datos puede quedar danada y se pierden las ventas.
REM ===================================================================

set "RAIZ=%~dp0"
set "XAMPP=%RAIZ%xampp"
set "MYSQL=%XAMPP%\mysql\bin"

cls
echo.
echo   ============================================
echo      APAGANDO EL SISTEMA POS
echo   ============================================
echo.

REM ---------- 1. Respaldo antes de apagar ----------
echo   [1/3] Guardando copia de seguridad...
set "RESPALDO_OK=no"
if exist "%RAIZ%RESPALDAR AHORA.bat" (
    call "%RAIZ%RESPALDAR AHORA.bat" silencioso
    if not errorlevel 1 set "RESPALDO_OK=si"
)
if "!RESPALDO_OK!"=="si" (
    echo         Copia guardada.
) else (
    echo         [AVISO] No se pudo guardar la copia.
    echo         Puede que la base ya estuviera apagada.
)

REM ---------- 2. Cerrar la base de datos ----------
echo   [2/3] Cerrando la base de datos...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if errorlevel 1 goto MYSQL_YA_APAGADO

REM  Cierre limpio: MySQL termina de escribir y cierra sus archivos.
"%MYSQL%\mysqladmin.exe" -u root shutdown >nul 2>&1

REM  Se le da tiempo (hasta 20 s). Apagarlo a la fuerza antes de tiempo
REM  es justo lo que corrompe la base.
set ESPERA=0
:ESPERA_CIERRE
ping -n 3 127.0.0.1 >nul
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if errorlevel 1 goto MYSQL_CERRADO
set /a ESPERA+=1
if !ESPERA! LSS 10 goto ESPERA_CIERRE

REM  Ultimo recurso: no respondio al cierre limpio.
echo         [AVISO] La base no respondio; se cierra a la fuerza.
echo                 Al volver a abrir, revisa que los datos esten bien.
taskkill /F /IM mysqld.exe >nul 2>&1
ping -n 3 127.0.0.1 >nul
goto MYSQL_CERRADO

:MYSQL_YA_APAGADO
echo         Ya estaba apagada.
goto APAGAR_APACHE

:MYSQL_CERRADO
echo         Base de datos cerrada correctamente.

REM ---------- 3. Cerrar el servidor web ----------
:APAGAR_APACHE
echo   [3/3] Cerrando el servidor...
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if errorlevel 1 goto APACHE_YA_APAGADO

REM  Se cierran solo los Apache de ESTA carpeta: si el computador tiene
REM  otro XAMPP instalado aparte, no se toca.
powershell -NoProfile -Command "Get-CimInstance Win32_Process -Filter \"Name='httpd.exe'\" | Where-Object { $_.ExecutablePath -like '%XAMPP%*' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }" >nul 2>&1
ping -n 3 127.0.0.1 >nul

tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if errorlevel 1 goto APACHE_CERRADO
echo         [AVISO] Sigue abierto un servidor web de otra instalacion.
echo                 No se toca para no afectar otros programas.
goto FIN

:APACHE_YA_APAGADO
echo         Ya estaba apagado.
goto FIN

:APACHE_CERRADO
echo         Servidor cerrado correctamente.

:FIN
color 0A
echo.
echo   ============================================
echo    SISTEMA APAGADO
echo   ============================================
echo.
echo   Ya puedes apagar el computador con tranquilidad.
echo.
ping -n 5 127.0.0.1 >nul
exit /b 0
