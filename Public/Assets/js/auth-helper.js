// =====================================================
// AUTH HELPER - Funciones de autenticación globales
// =====================================================

// -----------------------------------------------------
// WRAPPER GLOBAL DE FETCH: protección CSRF
// -----------------------------------------------------
// Envuelve window.fetch para agregar automáticamente la cabecera
// 'X-CSRF-Token' en TODA petición que modifica datos (POST/PUT/PATCH/DELETE)
// del mismo origen. Así no hay que tocar cada fetch de la app: el token viaja
// solo. El token se lee del <meta name="csrf-token"> que pone el Header.
(function () {
    // Evita envolver dos veces si el script se cargara repetido.
    if (window.__fetchCsrfWrapped) return;
    window.__fetchCsrfWrapped = true;

    const originalFetch = window.fetch.bind(window);

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : null;
    }

    function setCsrfToken(token) {
        let meta = document.querySelector('meta[name="csrf-token"]');
        if (!meta) {
            meta = document.createElement('meta');
            meta.setAttribute('name', 'csrf-token');
            document.head.appendChild(meta);
        }
        meta.setAttribute('content', token);
    }

    /** Pide al servidor un token nuevo (se usa para recuperarse sin recargar). */
    async function refreshCsrfToken() {
        try {
            const r = await originalFetch('?pg=login&action=csrfToken', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const j = await r.json();
            if (j && j.token) {
                setCsrfToken(j.token);
                return j.token;
            }
        } catch (e) {
            console.warn('[CSRF] no se pudo renovar el token:', e);
        }
        return null;
    }
    window.refreshCsrfToken = refreshCsrfToken;

    function esMismoOrigen(resource) {
        try {
            const url = new URL(
                (resource instanceof Request ? resource.url : String(resource)),
                window.location.href
            );
            return url.origin === window.location.origin;
        } catch (e) {
            return true; // rutas relativas ("?pg=...") → mismo origen
        }
    }

    function conToken(options, resource, token) {
        const headers = new Headers(
            options.headers || (resource instanceof Request ? resource.headers : undefined)
        );
        headers.set('X-CSRF-Token', token);
        return Object.assign({}, options, { headers: headers });
    }

    window.fetch = async function (resource, options) {
        options = options || {};
        const method = (options.method || (resource instanceof Request ? resource.method : 'GET') || 'GET').toUpperCase();
        const unsafe = method !== 'GET' && method !== 'HEAD' && method !== 'OPTIONS';

        // Las lecturas (GET) no llevan token: se dejan pasar tal cual.
        if (!unsafe || !esMismoOrigen(resource)) {
            return originalFetch(resource, options);
        }

        // Si el cuerpo es un stream de un solo uso no se puede reintentar; en
        // esta app siempre son JSON o FormData, que sí se pueden reenviar.
        let token = getCsrfToken();
        if (!token) {
            token = await refreshCsrfToken();
        }

        let respuesta = await originalFetch(resource, token ? conToken(options, resource, token) : options);

        // ---------------------------------------------------------------
        // AUTO-RECUPERACIÓN
        // Si el servidor rechaza por token vencido (403 + {csrf:true}), se pide
        // uno nuevo y se reintenta UNA vez, de forma transparente.
        // Esto es lo que evita el mensaje "tu sesión expiró" en un POS que
        // permanece abierto todo el día y que no se puede recargar a mano.
        // ---------------------------------------------------------------
        if (respuesta.status === 403) {
            let esCsrf = false;
            try {
                // Se clona para no consumir el cuerpo que espera quien llamó.
                const datos = await respuesta.clone().json();
                esCsrf = datos && datos.csrf === true;
            } catch (e) {
                esCsrf = false;
            }

            if (esCsrf) {
                const nuevo = await refreshCsrfToken();
                if (nuevo) {
                    respuesta = await originalFetch(resource, conToken(options, resource, nuevo));
                }
            }
        }

        return respuesta;
    };
})();

// ============================================================================
// MANTENER LA SESIÓN VIVA
// ============================================================================
// El POS se queda abierto todo el día sin que nadie navegue (ej. una mañana
// floja). Un ping periódico evita que la sesión del servidor se recicle y que
// el cajero se encuentre con la pantalla de login a mitad de turno.
(function () {
    if (window.__keepAliveIniciado) return;
    window.__keepAliveIniciado = true;

    const CADA_MINUTOS = 10;

    setInterval(function () {
        // Solo si la pestaña sigue viva; no gasta datos en segundo plano.
        fetch('?pg=login&action=csrfToken', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (j && j.token && window.refreshCsrfToken) {
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', j.token);
                }
            })
            .catch(function () { /* sin conexión: se reintenta al siguiente ciclo */ });
    }, CADA_MINUTOS * 60 * 1000);
})();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        updateInventoryAlertBadge();
    });
} else {
    updateInventoryAlertBadge();
}
/**
 * Obtener ID del usuario actual desde sesión
 */
async function getUserId() {
    try {
        const response = await fetch('?pg=login&action=checkAuth');
        const data = await response.json();
        
        if (data.success && data.authenticated) {
            return data.usuario.id;
        }
        return null;
    } catch (error) {
        console.error('Error obteniendo usuario:', error);
        return null;
    }
}

/**
 * Obtener información completa del usuario actual
 */
async function getCurrentUser() {
    try {
        const response = await fetch('?pg=login&action=getCurrentUser');
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        }
        return null;
    } catch (error) {
        console.error('Error obteniendo usuario:', error);
        return null;
    }
}

/**
 * Verificar si el usuario está autenticado
 */
async function isAuthenticated() {
    try {
        const response = await fetch('?pg=login&action=checkAuth');
        const data = await response.json();
        return data.success && data.authenticated;
    } catch (error) {
        console.error('Error verificando autenticación:', error);
        return false;
    }
}

/**
 * Cerrar sesión
 */
function logout() {
    if (confirm('¿Estás seguro de cerrar sesión?')) {
        window.location.href = '?pg=logout';
    }
}

/**
 * Mostrar alerta (puedes personalizarla)
 */
function showAlert(message, type = 'info') {
    // Implementación básica con alert()
    // Puedes cambiar esto por Bootstrap alerts, SweetAlert, etc.
    
    const icons = {
        'success': '✅',
        'error': '❌',
        'warning': '⚠️',
        'info': 'ℹ️'
    };
    
    const icon = icons[type] || icons['info'];
    alert(`${icon} ${message}`);
    
    // Opcional: Implementación con Bootstrap Alert
    /*
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
    */
}

/**
 * Formatear número como moneda
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(amount);
}

/**
 * Formatear fecha
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Formatear fecha y hora
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// =====================================================
// ALERTAS DE INVENTARIO EN HEADER
// =====================================================

async function updateInventoryAlertBadge() {
    const headerBadge = document.getElementById('headerInventoryAlertBadge');
    if (!headerBadge) return;

    try {
        const response = await fetch('index.php?pg=inventory&action=getAlertas&limit=100');
        const data = await response.json();

        if (data.success && Array.isArray(data.data)) {
            const count = data.data.length;
            if (count > 0) {
                headerBadge.textContent = count;
                headerBadge.style.display = 'inline-block';
            } else {
                headerBadge.textContent = '';
                headerBadge.style.display = 'none';
            }
        } else {
            headerBadge.textContent = '';
            headerBadge.style.display = 'none';
        }
    } catch (error) {
        console.error('Error loading inventory alert badge:', error);
        headerBadge.textContent = '';
        headerBadge.style.display = 'none';
    }
}





