<?php
/**
 * ============================================================================
 * AUTH - Autenticación y control de acceso por rol
 * ============================================================================
 * Punto único donde se decide quién puede entrar a qué. Antes NO había ninguna
 * verificación en el servidor: cualquiera podía llamar a las acciones por URL
 * sin haber iniciado sesión. Esto lo cierra.
 *
 * Roles: 'admin' (todo) y 'empleado' (venta y mesas).
 * ============================================================================
 */
class Auth {
    /**
     * Páginas (pg) que solo puede ver un administrador.
     * El resto de páginas conocidas quedan disponibles para cualquier usuario
     * autenticado (admin o empleado).
     */
    private static $adminOnly = [
        'dashboard',
        'reports',
        'settings',
        'inventory',
        'expenses',
        'users',      // gestión de cuentas y roles: solo el dueño/administrador
    ];

    /**
     * Acciones concretas que, dentro de una página permitida al empleado, solo
     * puede ejecutar un administrador. Formato: 'pg.accion'.
     * Ej: el historial de compras vive dentro de la vista de compras (que el
     * empleado sí puede usar para registrar), pero solo el admin lo consulta.
     */
    private static $adminOnlyActions = [
        'purchases.getPurchases',     // listado/historial de compras
        'purchases.getPurchase',      // detalle de una compra del historial
        'product.repararImagenes',    // mantenimiento: reescribe los archivos de imagen
    ];

    /** Páginas públicas: no requieren sesión. */
    private static $public = ['login', 'logout'];

    /** ¿Hay un usuario con sesión iniciada? */
    public static function check() {
        return isset($_SESSION['usuario_id']);
    }

    /** Rol del usuario actual ('admin' | 'empleado' | null). */
    public static function role() {
        return $_SESSION['usuario_rol'] ?? null;
    }

    public static function isAdmin() {
        return self::role() === 'admin';
    }

    /** ID del usuario en sesión (fuente de verdad para registrar movimientos). */
    public static function id() {
        return $_SESSION['usuario_id'] ?? null;
    }

    public static function isPublic($pg) {
        return in_array($pg, self::$public, true);
    }

    public static function isAdminOnly($pg) {
        return in_array($pg, self::$adminOnly, true);
    }

    /**
     * Guarda principal del router. Decide si la petición puede continuar según
     * la página solicitada, si es AJAX y el rol del usuario.
     *
     * Corta la ejecución (exit) cuando no está permitido.
     */
    public static function guard($pg, $isAjax, $action = null) {
        if (self::isPublic($pg)) {
            return; // login/logout siempre accesibles
        }

        // 1) Requiere sesión
        if (!self::check()) {
            self::deny($isAjax, 401, 'Debes iniciar sesión');
            return;
        }

        // 2) Requiere rol admin en las páginas restringidas
        if (self::isAdminOnly($pg) && !self::isAdmin()) {
            self::deny($isAjax, 403, 'No tienes permisos para esta sección');
            return;
        }

        // 3) Acciones puntuales admin-only dentro de páginas permitidas
        if ($action !== null
            && in_array($pg . '.' . $action, self::$adminOnlyActions, true)
            && !self::isAdmin()) {
            self::deny($isAjax, 403, 'No tienes permisos para esta acción');
            return;
        }
    }

    /** Responde el rechazo: JSON para AJAX, redirección para navegación normal. */
    private static function deny($isAjax, $code, $mensaje) {
        http_response_code($code);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $mensaje, 'authRequired' => $code === 401]);
            exit;
        }

        if ($code === 401) {
            header('Location: ?pg=login');
        } else {
            // Sin permiso: se manda al home en vez de mostrar una página cruda
            header('Location: ?pg=home');
        }
        exit;
    }
}
