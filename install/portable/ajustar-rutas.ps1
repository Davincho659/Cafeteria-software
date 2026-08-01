# ===================================================================
#  AJUSTAR RUTAS DE XAMPP
# ===================================================================
#  XAMPP guarda rutas ABSOLUTAS dentro de sus archivos de configuracion
#  (datadir="C:/xampp/mysql/data", ServerRoot "C:/xampp/apache", ...).
#  Al copiar el paquete a otro computador o a otra carpeta, esas rutas
#  apuntan a un sitio que no existe y los servicios no arrancan:
#
#     Can't change dir to 'C:/xampp/mysql/data'
#
#  Este script detecta la ruta grabada, la compara con la real y
#  reescribe todas las configuraciones si no coinciden.
#
#  Uso:  powershell -ExecutionPolicy Bypass -File ajustar-rutas.ps1 -Xampp "C:\ruta\xampp"
# ===================================================================

param(
    [Parameter(Mandatory = $true)][string]$Xampp
)

$ErrorActionPreference = 'Stop'

function Salir($codigo, $mensaje) {
    Write-Output $mensaje
    exit $codigo
}

if (-not (Test-Path $Xampp)) { Salir 1 "ERROR: no existe la carpeta $Xampp" }

# Ruta real, normalizada al estilo que usan los archivos de XAMPP (con /)
$rutaReal = (Resolve-Path $Xampp).Path.TrimEnd('\')
$realBarra = $rutaReal.Replace('\', '/')

# --- Averiguar que ruta tienen grabada los archivos ---
$myIni = Join-Path $rutaReal 'mysql\bin\my.ini'
if (-not (Test-Path $myIni)) { $myIni = Join-Path $rutaReal 'mysql\my.ini' }
if (-not (Test-Path $myIni)) { Salir 2 "ERROR: no se encuentra my.ini dentro de $rutaReal" }

# --- Carpetas de trabajo que XAMPP necesita ---
# El ZIP no siempre conserva las carpetas vacias. Sin "tmp", InnoDB aborta con
# "Unable to create temporary file" y MySQL no arranca; sin "logs", Apache
# tampoco levanta.
foreach ($carpeta in @('tmp', 'apache\logs', 'mysql\data')) {
    $ruta = Join-Path $rutaReal $carpeta
    if (-not (Test-Path $ruta)) {
        New-Item -ItemType Directory -Path $ruta -Force | Out-Null
        Write-Output "  [creada] $carpeta"
    }
}

$lineaBasedir = Select-String -Path $myIni -Pattern '^\s*basedir\s*=\s*"?([^"\r\n]+)"?' | Select-Object -First 1
if (-not $lineaBasedir) { Salir 3 "ERROR: my.ini no declara basedir" }

# basedir apunta a <xampp>/mysql: se sube un nivel para obtener la raiz grabada
$basedirGrabado = $lineaBasedir.Matches[0].Groups[1].Value.Trim().TrimEnd('/', '\')
$grabadaBarra = ($basedirGrabado -replace '[/\\]mysql$', '').Replace('\', '/')

if ($grabadaBarra -ieq $realBarra) {
    Write-Output "RUTAS-OK"
    exit 0
}

Write-Output "AJUSTANDO"
Write-Output "  Grabada : $grabadaBarra"
Write-Output "  Real    : $realBarra"

# --- Archivos que contienen rutas ---
$objetivos = @(
    'mysql\bin\my.ini'
    'mysql\my.ini'
    'apache\conf\httpd.conf'
    'apache\conf\extra\httpd-xampp.conf'
    'apache\conf\extra\httpd-ssl.conf'
    'apache\conf\extra\httpd-vhosts.conf'
    'apache\conf\extra\httpd-multilang-errordoc.conf'
    'apache\conf\extra\httpd-autoindex.conf'
    'apache\conf\extra\httpd-dav.conf'
    'apache\conf\extra\httpd-manual.conf'
    'apache\conf\extra\httpd-userdir.conf'
    'php\php.ini'
    'phpMyAdmin\config.inc.php'
    'apache\bin\php.ini'
)

# Las dos formas en que puede estar escrita la ruta vieja
$viejaBarra = $grabadaBarra                       # C:/xampp
$viejaContra = $grabadaBarra.Replace('/', '\')    # C:\xampp
$nuevaBarra = $realBarra
$nuevaContra = $realBarra.Replace('/', '\')

$cambiados = 0
foreach ($rel in $objetivos) {
    $ruta = Join-Path $rutaReal $rel
    if (-not (Test-Path $ruta)) { continue }

    try {
        $contenido = [System.IO.File]::ReadAllText($ruta)
        $original = $contenido

        # Se reemplazan ambas variantes, sin distinguir mayusculas
        $contenido = [regex]::Replace($contenido, [regex]::Escape($viejaBarra), $nuevaBarra.Replace('$', '$$'), 'IgnoreCase')
        $contenido = [regex]::Replace($contenido, [regex]::Escape($viejaContra), $nuevaContra.Replace('$', '$$'), 'IgnoreCase')

        if ($contenido -ne $original) {
            # Copia de seguridad la primera vez que se toca cada archivo
            $respaldo = "$ruta.original"
            if (-not (Test-Path $respaldo)) { Copy-Item $ruta $respaldo -Force }

            [System.IO.File]::WriteAllText($ruta, $contenido)
            $cambiados++
            Write-Output "  [OK] $rel"
        }
    } catch {
        Write-Output "  [AVISO] no se pudo ajustar $rel : $($_.Exception.Message)"
    }
}

Write-Output "LISTO:$cambiados"
exit 0
