@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
title Sistema POS - La Casa del Pastel
color 0E

REM ===================================================================
REM  INICIAR POS  -  Arranca todo el sistema con un solo doble click
REM ===================================================================
REM  Este archivo debe estar en la MISMA carpeta que la carpeta "xampp".
REM  No requiere instalar nada ni permisos de administrador.
REM
REM  Nota tecnica: las etiquetas (:NOMBRE) NO pueden ir dentro de un
REM  bloque entre parentesis, y las variables que cambian dentro de un
REM  bloque necesitan !VARIABLE! en vez de %VARIABLE%. Por eso todo el
REM  flujo se maneja con goto y subrutinas.
REM ===================================================================

set "RAIZ=%~dp0"
set "XAMPP=%RAIZ%xampp"
set "MYSQL=%XAMPP%\mysql\bin"
set "APACHE=%XAMPP%\apache\bin"
set "PROYECTO=%XAMPP%\htdocs\Cafeteria-software"
set "URL=http://localhost/Cafeteria-software/Public/Index.php?pg=login"

cls
echo.
echo   ============================================
echo      SISTEMA POS - LA CASA DEL PASTEL
echo   ============================================
echo.

REM ---------- Comprobaciones previas ----------
if not exist "%APACHE%\httpd.exe" goto FALTA_XAMPP
if not exist "%MYSQL%\mysqld.exe" goto FALTA_XAMPP
if not exist "%PROYECTO%\Public\Index.php" goto FALTA_PROYECTO

REM ---------- 1. Base de datos ----------
echo   [1/4] Iniciando la base de datos...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if errorlevel 1 goto ARRANCAR_MYSQL
echo         Ya estaba encendida.
goto MYSQL_LISTO

:ARRANCAR_MYSQL
start "" /B "%MYSQL%\mysqld.exe" --defaults-file="%XAMPP%\mysql\my.ini" --standalone
set INTENTOS=0

:ESPERA_MYSQL
ping -n 3 127.0.0.1 >nul
"%MYSQL%\mysql.exe" -u root -e "SELECT 1" >nul 2>&1
if not errorlevel 1 goto MYSQL_LISTO
set /a INTENTOS+=1
if !INTENTOS! LSS 15 goto ESPERA_MYSQL
goto ERROR_MYSQL

:MYSQL_LISTO
echo         Base de datos lista.

REM ---------- 2. Crear la base la primera vez ----------
echo   [2/4] Verificando los datos...
"%MYSQL%\mysql.exe" -u root -e "USE cafeteria_software;" >nul 2>&1
if not errorlevel 1 goto DATOS_OK

echo         Primera vez: preparando el sistema...
"%MYSQL%\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS cafeteria_software CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" >nul 2>&1
if not exist "%PROYECTO%\install\schema.sql" goto FALTA_SCHEMA
"%MYSQL%\mysql.exe" -u root cafeteria_software < "%PROYECTO%\install\schema.sql"
if exist "%PROYECTO%\install\seed.sql" "%MYSQL%\mysql.exe" -u root cafeteria_software < "%PROYECTO%\install\seed.sql"
echo         Sistema preparado.  Usuario: admin   PIN: 1234
goto DATOS_OK

:DATOS_OK
echo         Datos verificados.

REM ---------- 3. Servidor web ----------
echo   [3/4] Iniciando el servidor...
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if errorlevel 1 goto ARRANCAR_APACHE
echo         Ya estaba encendido.
goto APACHE_LISTO

:ARRANCAR_APACHE
start "" /B "%APACHE%\httpd.exe"
ping -n 4 127.0.0.1 >nul
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if errorlevel 1 goto ERROR_APACHE

:APACHE_LISTO
echo         Servidor listo.

REM ---------- 4. Respaldo de seguridad ----------
echo   [4/4] Guardando copia de seguridad...
if exist "%RAIZ%RESPALDAR AHORA.bat" call "%RAIZ%RESPALDAR AHORA.bat" silencioso
echo         Copia guardada.

REM ---------- Abrir como aplicacion ----------
echo.
echo   Abriendo el sistema...

set "NAVEGADOR="
if exist "%ProgramFiles%\Google\Chrome\Application\chrome.exe" set "NAVEGADOR=%ProgramFiles%\Google\Chrome\Application\chrome.exe"
if exist "%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe" set "NAVEGADOR=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"
if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" set "NAVEGADOR=%LocalAppData%\Google\Chrome\Application\chrome.exe"
if not defined NAVEGADOR if exist "%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe" set "NAVEGADOR=%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe"
if not defined NAVEGADOR if exist "%ProgramFiles%\Microsoft\Edge\Application\msedge.exe" set "NAVEGADOR=%ProgramFiles%\Microsoft\Edge\Application\msedge.exe"

if defined NAVEGADOR start "" "!NAVEGADOR!" --app="%URL%" --start-maximized
if not defined NAVEGADOR start "" "%URL%"

color 0A
echo.
echo   ============================================
echo      EL SISTEMA YA ESTA ABIERTO
echo   ============================================
echo.
echo   NO CIERRES esta ventana mientras trabajas.
echo   Al terminar, usa "APAGAR POS.bat"
echo.
echo   Esta ventana se cierra sola en 10 segundos...
ping -n 11 127.0.0.1 >nul
exit /b 0


REM ===================================================================
REM  ERRORES  -  siempre con pausa, para poder leer el mensaje
REM ===================================================================

:FALTA_XAMPP
color 0C
echo   [ERROR] No se encuentra XAMPP.
echo.
echo   Se busco en:  %XAMPP%
echo.
echo   Este archivo debe estar JUNTO a la carpeta "xampp",
echo   no dentro de ella. La estructura correcta es:
echo.
echo      POS-LaCasaDelPastel\
echo         xampp\                  ^<-- carpeta
echo         INICIAR POS.bat         ^<-- este archivo
echo.
echo   Carpeta actual: %RAIZ%
echo.
pause
exit /b 1

:FALTA_PROYECTO
color 0C
echo   [ERROR] No se encuentra el sistema POS.
echo.
echo   Se busco en:  %PROYECTO%\Public\Index.php
echo.
echo   La carpeta "Cafeteria-software" debe estar dentro de
echo   xampp\htdocs\ y contener la carpeta Public.
echo.
pause
exit /b 1

:FALTA_SCHEMA
color 0C
echo   [ERROR] Falta el archivo de la base de datos.
echo.
echo   Se busco en:  %PROYECTO%\install\schema.sql
echo.
echo   Copia la carpeta "install" completa dentro del proyecto.
echo.
pause
exit /b 1

:ERROR_MYSQL
color 0C
echo.
echo   [ERROR] La base de datos no arranco.
echo.
echo   Causas frecuentes:
echo     - Ya hay otro MySQL usando el puerto 3306
echo     - La carpeta esta en OneDrive o en una ruta muy larga
echo.
echo   Prueba: reinicia el computador y vuelve a intentarlo.
echo.
pause
exit /b 1

:ERROR_APACHE
color 0C
echo.
echo   [ERROR] El servidor web no arranco.
echo.
echo   Casi siempre es porque otro programa usa el puerto 80
echo   (Skype, IIS u otro XAMPP ya instalado).
echo.
echo   Solucion: abre  xampp\apache\conf\httpd.conf  y cambia
echo   "Listen 80" por "Listen 8080". Luego, en este archivo,
echo   cambia localhost por localhost:8080 en la linea URL.
echo.
pause
exit /b 1
