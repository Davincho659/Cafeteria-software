<?php
/**
 * ============================================================================
 * CSRF - Protección contra Cross-Site Request Forgery
 * ============================================================================
 * Última capa de seguridad web importante cuando el sistema está en internet.
 * Sin esto, una página maliciosa abierta en el mismo navegador donde el cajero
 * tiene la sesión podría enviar peticiones (crear/anular ventas, borrar datos)
 * usando su sesión, sin que él lo sepa.
 *
 * Cómo funciona:
 *  - Se genera UN token aleatorio por sesión (Csrf::token()).
 *  - El token viaja al frontend en <meta name="csrf-token"> (Header) y se
 *    reenvía en cada POST mediante la cabecera 'X-CSRF-Token' (wrapper global
 *    de fetch en auth-helper.js) o el campo oculto '_csrf' de un formulario.
 *  - El servidor valida ese token con hash_equals (Csrf::check()).
 *
 * Nota: solo se exige en métodos que MODIFICAN datos (POST). Las lecturas (GET)
 * no lo requieren. El login queda exento (es previo a la sesión de trabajo).
 * ============================================================================
 */
class Csrf {
    /** Clave donde se guarda el token dentro de $_SESSION. */
    private const SESSION_KEY = '_csrf_token';

    /**
     * Devuelve el token de la sesión, generándolo la primera vez.
     */
    public static function token(): string {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Compara (a prueba de ataques de tiempo) un token recibido con el de sesión.
     */
    public static function validate(?string $provided): bool {
        if ($provided === null || $provided === '') {
            return false;
        }
        $stored = $_SESSION[self::SESSION_KEY] ?? '';
        if ($stored === '') {
            return false;
        }
        return hash_equals($stored, $provided);
    }

    /**
     * Extrae el token del request: primero la cabecera (así lo manda el wrapper
     * global de fetch), y como respaldo un campo POST '_csrf' (formularios).
     * No se lee el cuerpo JSON para no consumir php://input (lo leen los
     * controladores).
     */
    public static function extractFromRequest(): ?string {
        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        if (isset($_POST['_csrf']) && $_POST['_csrf'] !== '') {
            return (string) $_POST['_csrf'];
        }
        return null;
    }

    /**
     * Verifica el token del request actual.
     */
    public static function check(): bool {
        return self::validate(self::extractFromRequest());
    }

    /**
     * Aplica la protección en el router: si la petición modifica datos (POST) y
     * el token es inválido, corta la ejecución con 419 (o redirige si no es AJAX).
     */
    public static function enforce(bool $isAjax): void {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        // Solo métodos que modifican estado.
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        if (self::check()) {
            return;
        }

        // Token inválido o ausente. Se usa 403 (estándar y reconocido por todos
        // los servidores); el frontend distingue el caso por el flag "csrf".
        http_response_code(403);
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Tu sesión expiró o la página está desactualizada. Recarga e inténtalo de nuevo.',
                'csrf' => true,
            ]);
        } else {
            header('Location: ?pg=home');
        }
        exit;
    }

    /**
     * Devuelve un <input type="hidden"> listo para formularios tradicionales.
     */
    public static function field(): string {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }
}
