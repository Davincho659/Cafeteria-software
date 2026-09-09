// ============================================================================
// SERVICE WORKER
// ============================================================================
// Es el requisito que pone el navegador para dejar "instalar" la aplicación en
// el celular. Además guarda en el equipo los archivos de presentación (estilos,
// scripts, iconos, tipografias) para que la pantalla abra rapido y no dependa
// de la velocidad de la conexion.
//
// Lo que NO hace, a proposito: guardar datos del negocio. Los productos, las
// ventas y el inventario se piden siempre al servidor. En una caja registradora
// mostrar un precio o un stock viejo es peor que no mostrar nada.
// ============================================================================

const VERSION = 'pos-v1';
const ARCHIVOS_BASE = 'pos-base-v1';

// Solo lo que no cambia entre una venta y otra.
const RUTAS_PRECARGA = [
    'assets/css/pos-theme.css',
    'assets/css/bootstrap.css',
    'assets/css/all.min.css',
    'assets/css/sweetalert2.min.css',
    'assets/js/bootstrap.bundle.min.js',
    'assets/js/sweetalert2.all.min.js',
    'assets/js/auth-helper.js',
    'assets/img/pwa/icon-192.png',
    'assets/img/pwa/icon-512.png',
];

self.addEventListener('install', (evento) => {
    evento.waitUntil(
        caches.open(ARCHIVOS_BASE).then((cache) => {
            // addAll falla entero si un archivo no responde; se agregan de a uno
            // para que una ruta que falte no impida instalar la aplicacion.
            return Promise.all(
                RUTAS_PRECARGA.map((ruta) =>
                    cache.add(ruta).catch(() => null)
                )
            );
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (evento) => {
    evento.waitUntil(
        caches.keys().then((nombres) =>
            Promise.all(
                nombres
                    .filter((n) => n !== ARCHIVOS_BASE)
                    .map((n) => caches.delete(n))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (evento) => {
    const peticion = evento.request;

    // Solo lecturas del propio sitio.
    if (peticion.method !== 'GET') return;
    const url = new URL(peticion.url);
    if (url.origin !== self.location.origin) return;

    // ------------------------------------------------------------------
    // Nada de datos del negocio sale de la cache.
    // ------------------------------------------------------------------
    // Index.php atiende tanto las pantallas como las consultas de datos
    // (?pg=sales&action=...). Servir cualquiera de esas desde la cache
    // mostraria precios, stock o ventas de hace horas.
    const esDatosOPantalla = url.pathname.endsWith('Index.php')
        || url.search.includes('pg=')
        || url.pathname.endsWith('manifest.php');

    if (esDatosOPantalla) {
        evento.respondWith(
            fetch(peticion).catch(() =>
                new Response(
                    '<!doctype html><meta charset="utf-8">' +
                    '<div style="font-family:system-ui;padding:2rem;text-align:center">' +
                    '<h2>Sin conexion</h2>' +
                    '<p>No se pudo conectar con el sistema.<br>' +
                    'Revisa el internet y vuelve a intentarlo.</p>' +
                    '<button onclick="location.reload()" ' +
                    'style="padding:.8rem 1.5rem;font-size:1rem">Reintentar</button></div>',
                    { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                )
            )
        );
        return;
    }

    // ------------------------------------------------------------------
    // Estilos, scripts, iconos y tipografias: primero la copia guardada.
    // ------------------------------------------------------------------
    evento.respondWith(
        caches.match(peticion).then((guardada) => {
            if (guardada) {
                // Se refresca en segundo plano para la proxima vez.
                fetch(peticion).then((respuesta) => {
                    if (respuesta && respuesta.ok) {
                        caches.open(ARCHIVOS_BASE).then((c) => c.put(peticion, respuesta));
                    }
                }).catch(() => null);
                return guardada;
            }

            return fetch(peticion).then((respuesta) => {
                if (respuesta && respuesta.ok) {
                    const copia = respuesta.clone();
                    caches.open(ARCHIVOS_BASE).then((c) => c.put(peticion, copia));
                }
                return respuesta;
            });
        })
    );
});
