<?php
/**
 * Etiquetas que convierten el sistema en aplicación instalable.
 *
 * Se incluye tanto en el Header como en el Login, para que se pueda instalar
 * desde cualquiera de las dos pantallas.
 *
 * Android lee el manifest; iOS lo ignora en buena parte y necesita sus propias
 * etiquetas apple-*, por eso están ambas.
 */
$__cfgPwa = $cfg ?? null;
if ($__cfgPwa === null) {
    try {
        require_once dirname(__DIR__, 2) . '/Models/Settings.php';
        $__cfgPwa = (new Settings())->getAll();
    } catch (Throwable $e) {
        $__cfgPwa = ['nombre_negocio' => 'POS', 'color_primario' => '#5B3411'];
    }
}
$__nombrePwa = $__cfgPwa['nombre_negocio'] ?? 'POS';
$__colorPwa  = $__cfgPwa['color_primario'] ?? '#5B3411';
?>
<!-- ===== Aplicación instalable (PWA) ===== -->
<link rel="manifest" href="manifest.php">
<meta name="theme-color" content="<?= htmlspecialchars($__colorPwa, ENT_QUOTES) ?>">

<!-- iOS no usa el manifest para esto: necesita sus propias etiquetas -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($__nombrePwa, ENT_QUOTES) ?>">
<link rel="apple-touch-icon" href="<?= asset('assets/img/pwa/icon-180.png') ?>">
<link rel="icon" type="image/png" sizes="192x192" href="<?= asset('assets/img/pwa/icon-192.png') ?>">
<link rel="icon" type="image/png" sizes="96x96" href="<?= asset('assets/img/pwa/icon-96.png') ?>">

<script>
// Registrar el service worker: sin él el navegador no ofrece instalar la app.
// Requiere HTTPS, salvo en localhost, que el navegador considera seguro.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('service-worker.js')
            .catch(function (e) { console.warn('[PWA] no se pudo registrar:', e); });
    });
}
</script>
