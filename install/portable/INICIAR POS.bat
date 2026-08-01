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

REM ---------- 0. Ajustar rutas si el paquete cambio de sitio ----------
REM  XAMPP guarda rutas absolutas en sus configuraciones. Si la carpeta se
REM  copia a otro computador o a otra unidad, MySQL falla con
REM  "Can't change dir to ...\mysql\data" y Apache ni siquiera arranca.
REM  Esto lo detecta y reescribe las rutas antes de encender nada.
echo   [1/5] Revisando la instalacion...
if exist "%RAIZ%ajustar-rutas.ps1" (
    powershell -NoProfile -ExecutionPolicy Bypass -File "%RAIZ%ajustar-rutas.ps1" -Xampp "%XAMPP%" > "%TEMP%\pos_rutas.txt" 2>&1
    find /I "AJUSTANDO" "%TEMP%\pos_rutas.txt" >nul 2>&1
    if not errorlevel 1 (
        echo         Se movio de carpeta: rutas corregidas.
    ) else (
        echo         Instalacion correcta.
    )
    del "%TEMP%\pos_rutas.txt" >nul 2>&1
) else (
    echo         ^(sin ajustador de rutas^)
)

REM ---------- 1. Base de datos ----------
echo   [2/5] Iniciando la base de datos...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if errorlevel 1 goto ARRANCAR_MYSQL
echo         Ya estaba encendida.
goto MYSQL_LISTO

:ARRANCAR_MYSQL
REM  La configuracion de MySQL no siempre esta en el mismo sitio: en XAMPP vive
REM  en mysql\bin\my.ini, pero otras versiones la ponen en mysql\my.ini. Se
REM  busca en ambas antes de arrancar; sin el archivo correcto mysqld aborta
REM  con "Fatal error in defaults handling".
set "MYINI="
if exist "%XAMPP%\mysql\bin\my.ini" set "MYINI=%XAMPP%\mysql\bin\my.ini"
if not defined MYINI if exist "%XAMPP%\mysql\my.ini" set "MYINI=%XAMPP%\mysql\my.ini"
if not defined MYINI if exist "%XAMPP%\mysql\my.cnf" set "MYINI=%XAMPP%\mysql\my.cnf"
if not defined MYINI goto ERROR_MYINI

REM  MySQL necesita una carpeta temporal para trabajar. Si no existe, InnoDB
REM  aborta con "Unable to create temporary file" y la base no levanta.
REM  Suele faltar cuando el ZIP se descomprime sin las carpetas vacias.
if not exist "%XAMPP%\tmp" mkdir "%XAMPP%\tmp" >nul 2>&1

REM  Las rutas se pasan tambien por linea de comandos (tienen prioridad sobre el
REM  archivo): asi la base arranca aunque el my.ini hubiera quedado desfasado.
start "" /B "%MYSQL%\mysqld.exe" --defaults-file="!MYINI!" --basedir="%XAMPP%\mysql" --datadir="%XAMPP%\mysql\data" --plugin-dir="%XAMPP%\mysql\lib\plugin" --tmpdir="%XAMPP%\tmp" --standalone
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
echo   [3/5] Verificando los datos...
REM  Se comprueba una TABLA, no solo la base. Si un intento anterior dejo la
REM  base creada pero vacia, "USE cafeteria_software" tiene exito igual y el
REM  sistema arrancaba sin tablas, fallando al entrar con:
REM     Table 'cafeteria_software.usuarios' doesn't exist
"%MYSQL%\mysql.exe" -u root -e "SELECT 1 FROM cafeteria_software.usuarios LIMIT 1;" >nul 2>&1
if not errorlevel 1 goto DATOS_OK

echo         Preparando la base de datos por primera vez...
if not exist "%PROYECTO%\install\schema.sql" goto FALTA_SCHEMA

"%MYSQL%\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS cafeteria_software CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" >nul 2>&1

REM  Los errores del volcado se guardan para poder mostrarlos si algo falla,
REM  en vez de continuar como si nada.
"%MYSQL%\mysql.exe" -u root cafeteria_software < "%PROYECTO%\install\schema.sql" 2> "%TEMP%\pos_sql_error.txt"
if exist "%PROYECTO%\install\seed.sql" "%MYSQL%\mysql.exe" -u root cafeteria_software < "%PROYECTO%\install\seed.sql" 2>> "%TEMP%\pos_sql_error.txt"

REM  Verificar que la estructura quedo realmente creada
"%MYSQL%\mysql.exe" -u root -e "SELECT 1 FROM cafeteria_software.usuarios LIMIT 1;" >nul 2>&1
if errorlevel 1 goto ERROR_BASE

del "%TEMP%\pos_sql_error.txt" >nul 2>&1
echo         Base de datos creada.  Usuario: admin   PIN: 1234
goto DATOS_OK

:DATOS_OK
echo         Datos verificados.

REM ---------- 3. Servidor web ----------
echo   [4/5] Iniciando el servidor...
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
echo   [5/5] Guardando copia de seguridad...
if exist "%RAIZ%RESPALDAR AHORA.bat" call "%RAIZ%RESPALDAR AHORA.bat" silencioso
echo         Copia guardada.

REM ---------- Abrir como aplicacion ----------
echo.
echo   Abriendo el sistema...

REM  El puerto se lee de la configuracion de Apache. Si alguien lo cambio a
REM  8080 (porque el 80 estaba ocupado), la direccion se arma sola con el
REM  puerto correcto; antes quedaba fija en el 80 y el navegador abria una
REM  pagina en blanco aunque el servidor estuviera funcionando.
set "PUERTO=80"
for /f "usebackq delims=" %%P in (`powershell -NoProfile -Command "$m=Select-String -Path '%XAMPP%\apache\conf\httpd.conf' -Pattern '^\s*Listen\s+(?:[\d\.]+:)?(\d+)' | Select-Object -First 1; if($m){$m.Matches[0].Groups[1].Value}else{'80'}" 2^>nul`) do set "PUERTO=%%P"

set "HOST=localhost"
if not "!PUERTO!"=="80" set "HOST=localhost:!PUERTO!"
set "URL=http://!HOST!/Cafeteria-software/Public/Index.php?pg=login"

REM  Se comprueba que el sistema responda antes de abrir el navegador, para no
REM  dejar al usuario mirando una pagina en blanco sin saber por que.
set "RESPONDE=no"
for /f "usebackq delims=" %%R in (`powershell -NoProfile -Command "try{(Invoke-WebRequest -Uri 'http://!HOST!/Cafeteria-software/Public/Index.php?pg=login' -UseBasicParsing -TimeoutSec 8).StatusCode}catch{'0'}" 2^>nul`) do set "RESPONDE=%%R"
if "!RESPONDE!"=="200" goto ABRIR
if "!RESPONDE!"=="302" goto ABRIR
goto ERROR_NO_RESPONDE

:ABRIR
REM  %ProgramFiles(x86)% lleva parentesis y rompe la sintaxis de los IF en
REM  algunos equipos: se copia antes a una variable normal.
set "PF=%ProgramFiles%"
set "PF86=%ProgramFiles(x86)%"
if not defined PF86 set "PF86=%ProgramFiles%"

set "NAVEGADOR="
if exist "!PF!\Google\Chrome\Application\chrome.exe" set "NAVEGADOR=!PF!\Google\Chrome\Application\chrome.exe"
if not defined NAVEGADOR if exist "!PF86!\Google\Chrome\Application\chrome.exe" set "NAVEGADOR=!PF86!\Google\Chrome\Application\chrome.exe"
if not defined NAVEGADOR if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" set "NAVEGADOR=%LocalAppData%\Google\Chrome\Application\chrome.exe"
if not defined NAVEGADOR if exist "!PF86!\Microsoft\Edge\Application\msedge.exe" set "NAVEGADOR=!PF86!\Microsoft\Edge\Application\msedge.exe"
if not defined NAVEGADOR if exist "!PF!\Microsoft\Edge\Application\msedge.exe" set "NAVEGADOR=!PF!\Microsoft\Edge\Application\msedge.exe"

if defined NAVEGADOR (
    start "" "!NAVEGADOR!" --app="!URL!" --start-maximized
) else (
    start "" "!URL!"
)

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

:ERROR_BASE
color 0C
echo.
echo   [ERROR] La base de datos no se creo correctamente.
echo.
echo   Se ejecuto el archivo:
echo      %PROYECTO%\install\schema.sql
echo   pero la tabla de usuarios no quedo creada.
echo.
if exist "%TEMP%\pos_sql_error.txt" (
    echo   MySQL informo lo siguiente:
    echo   --------------------------------------------
    type "%TEMP%\pos_sql_error.txt"
    echo   --------------------------------------------
    echo.
)
echo   Que hacer:
echo     1^) Comprueba que install\schema.sql este completo
echo        ^(debe pesar unos 40 KB^).
echo     2^) Si la base quedo a medias, borrala y vuelve a abrir:
echo        xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE cafeteria_software;"
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

:ERROR_MYINI
color 0C
echo.
echo   [ERROR] No se encuentra la configuracion de MySQL.
echo.
echo   Se busco el archivo my.ini en:
echo      %XAMPP%\mysql\bin\my.ini
echo      %XAMPP%\mysql\my.ini
echo.
echo   Suele pasar cuando el ZIP de XAMPP se descomprimio a medias.
echo   Vuelve a descomprimirlo completo y reemplaza la carpeta xampp.
echo.
pause
exit /b 1

:ERROR_MYSQL
color 0C
echo.
echo   [ERROR] La base de datos no arranco.
echo.
echo   Configuracion usada: !MYINI!
echo.
echo   Causas frecuentes:
echo     - Ya hay otro MySQL usando el puerto 3306
echo     - La carpeta esta en OneDrive o en una ruta muy larga
echo     - Falta la carpeta xampp\mysql\data
echo.
echo   Para ver el detalle del error, abre este archivo:
echo      %XAMPP%\mysql\data\mysql_error.log
echo.
pause
exit /b 1

:ERROR_NO_RESPONDE
color 0C
echo.
echo   [ERROR] El servidor esta encendido pero el sistema no responde.
echo.
echo   Se intento abrir:  !URL!
echo   Puerto detectado:  !PUERTO!
echo.
echo   Comprueba lo siguiente:
echo.
echo   1) Que la carpeta del sistema se llame exactamente
echo      "Cafeteria-software" y este dentro de xampp\htdocs
echo      Ruta esperada: %PROYECTO%
echo.
echo   2) Si cambiaste el puerto en httpd.conf, revisa que el
echo      cambio este bien escrito ^(por ejemplo "Listen 8080"^).
echo.
echo   3) Abre esta direccion a mano en el navegador:
echo      !URL!
echo.
echo   Detalle del error:  %XAMPP%\apache\logs\error.log
echo.
pause
exit /b 1

:ERROR_APACHE
color 0C
echo.
echo   [ERROR] El servidor web no arranco.
echo.
echo   Mira el mensaje que aparece ARRIBA de este recuadro:
echo.
echo   1) Si dice "Syntax error" o "path is invalid":
echo      Es un problema de rutas, NO del puerto.
echo      Borra los archivos .original de xampp\apache\conf
echo      y vuelve a abrir: las rutas se recalculan solas.
echo.
echo   2) Si dice "Address already in use" o "make_sock":
echo      Otro programa ocupa el puerto 80 (Skype, IIS u otro
echo      XAMPP). Abre xampp\apache\conf\httpd.conf, cambia
echo      "Listen 80" por "Listen 8080", y en ESTE archivo
echo      pon localhost:8080 en la linea que define URL.
echo.
echo   Detalle completo del error:
echo      %XAMPP%\apache\logs\error.log
echo.
pause
exit /b 1
