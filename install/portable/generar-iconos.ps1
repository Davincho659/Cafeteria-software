# ===================================================================
#  GENERAR LOS ICONOS DE LA APLICACION
# ===================================================================
#  Crea, a partir del logo del negocio, los tamanos de icono que piden
#  Android y iPhone para la pantalla de inicio.
#
#  Solo hace falta ejecutarlo si se cambia el logo. El nombre y los
#  colores no: esos se leen de Configuracion cada vez.
#
#  Uso:  powershell -ExecutionPolicy Bypass -File generar-iconos.ps1
# ===================================================================

param(
    [string]$Logo  = "",
    [string]$Color = "#5B3411"
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

# Por defecto, el logo y la carpeta de destino del propio proyecto
$raiz = Split-Path -Parent $PSCommandPath
$proyecto = Resolve-Path (Join-Path $raiz '..\..') -ErrorAction SilentlyContinue
if (-not $proyecto) { $proyecto = (Get-Location).Path }

if (-not $Logo) { $Logo = Join-Path $proyecto 'Public\Assets\img\logo.jpg' }
$destino = Join-Path $proyecto 'Public\Assets\img\pwa'

if (-not (Test-Path $Logo)) {
    Write-Output "ERROR: no se encuentra el logo en $Logo"
    exit 1
}
if (-not (Test-Path $destino)) { New-Item -ItemType Directory $destino -Force | Out-Null }

$imagenLogo = [System.Drawing.Image]::FromFile($Logo)
Write-Output "Logo: $Logo ($($imagenLogo.Width)x$($imagenLogo.Height))"

# --- Iconos normales: el logo llenando el cuadro ---
foreach ($t in @(96, 144, 180, 192, 384, 512)) {
    $bmp = New-Object System.Drawing.Bitmap($t, $t)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.InterpolationMode = 'HighQualityBicubic'
    $g.SmoothingMode = 'HighQuality'
    $rect = New-Object System.Drawing.Rectangle 0, 0, $t, $t
    $g.DrawImage($imagenLogo, $rect)
    $g.Dispose()
    $bmp.Save((Join-Path $destino "icon-$t.png"), [System.Drawing.Imaging.ImageFormat]::Png)
    $bmp.Dispose()
    Write-Output "  icon-$t.png"
}

# --- Iconos 'maskable' ---
# Android recorta el icono en circulo o cuadrado redondeado segun el telefono.
# Por eso el logo va al 72% centrado sobre el color del negocio: asi no se le
# corta ningun borde sea cual sea la forma que aplique el sistema.
foreach ($t in @(192, 512)) {
    $bmp = New-Object System.Drawing.Bitmap($t, $t)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.InterpolationMode = 'HighQualityBicubic'
    $g.SmoothingMode = 'HighQuality'
    $fondo = New-Object System.Drawing.SolidBrush ([System.Drawing.ColorTranslator]::FromHtml($Color))
    $g.FillRectangle($fondo, 0, 0, $t, $t)
    $lado = [int]($t * 0.72)
    $off = [int](($t - $lado) / 2)
    $rect = New-Object System.Drawing.Rectangle $off, $off, $lado, $lado
    $g.DrawImage($imagenLogo, $rect)
    $g.Dispose()
    $bmp.Save((Join-Path $destino "icon-maskable-$t.png"), [System.Drawing.Imaging.ImageFormat]::Png)
    $bmp.Dispose()
    Write-Output "  icon-maskable-$t.png"
}

$imagenLogo.Dispose()
Write-Output ""
Write-Output "Listo. Quien ya tenga la aplicacion instalada debe desinstalarla"
Write-Output "y volver a agregarla para ver el icono nuevo."
