// =====================================================
// AUTH HELPER - Funciones de autenticación globales
// =====================================================

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