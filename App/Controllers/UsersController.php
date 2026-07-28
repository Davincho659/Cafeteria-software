<?php
require_once __DIR__ . '/../Models/Users.php';

/**
 * ============================================================================
 * USERS CONTROLLER - Administración de usuarios (solo admin)
 * ============================================================================
 * Permite al dueño crear, editar y eliminar los usuarios que entran al POS y
 * asignarles rol (admin / empleado).
 *
 * Reglas de seguridad que se aplican aquí (además del guard de Auth):
 *  - El PIN nunca se devuelve ni se muestra: solo se puede reemplazar.
 *  - No se puede eliminar al último administrador (el negocio quedaría sin
 *    acceso a configuración y reportes).
 *  - Un admin no puede eliminarse a sí mismo ni quitarse su propio rol.
 *  - No se borra un usuario con ventas: se perdería la trazabilidad.
 * ============================================================================
 */
class UsersController {
    private $usersModel;

    /** Longitud mínima del PIN. Corto es cómodo en táctil, pero no trivial. */
    private const PIN_MIN = 4;
    private const PIN_MAX = 20;

    public function __construct() {
        $this->usersModel = new Users();
    }

    /** Vista de gestión de usuarios */
    public function index() {
        require_once __DIR__ . '/../Views/users.view.php';
    }

    /** Listado de usuarios (sin PIN) */
    public function getUsers() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            echo json_encode([
                'success' => true,
                'data' => $this->usersModel->getAll(),
                'currentUserId' => (int) (Auth::id() ?? 0),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /** Crear usuario */
    public function createUser() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = $this->input();

            $nombre = trim((string) ($data['nombre'] ?? ''));
            $pin    = (string) ($data['pin'] ?? '');
            $rol    = $this->validarRol($data['rol'] ?? 'empleado');

            $this->validarNombre($nombre);
            $this->validarPin($pin);

            if ($this->usersModel->nameInUse($nombre)) {
                throw new Exception('Ya existe un usuario con ese nombre');
            }

            $id = $this->usersModel->create($nombre, $pin, $rol);

            echo json_encode([
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'idUsuario' => $id,
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /** Actualizar usuario (nombre, rol y opcionalmente el PIN) */
    public function updateUser() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = $this->input();

            $id = (int) ($data['idUsuario'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Usuario inválido');
            }

            $usuario = $this->usersModel->getById($id);
            if (!$usuario) {
                throw new Exception('El usuario no existe');
            }

            $nombre = trim((string) ($data['nombre'] ?? ''));
            $this->validarNombre($nombre);

            if ($this->usersModel->nameInUse($nombre, $id)) {
                throw new Exception('Ya existe otro usuario con ese nombre');
            }

            $rol = $this->validarRol($data['rol'] ?? $usuario['rol']);

            // No permitir que el admin en sesión se quite su propio rol: se
            // quedaría sin poder volver a entrar a la administración.
            if ($id === (int) Auth::id() && $rol !== 'admin') {
                throw new Exception('No puedes quitarte tu propio rol de administrador');
            }

            // No dejar el sistema sin ningún administrador.
            if ($usuario['rol'] === 'admin' && $rol !== 'admin' && $this->usersModel->countAdmins() <= 1) {
                throw new Exception('Debe existir al menos un administrador');
            }

            // El PIN solo se cambia si se envió uno nuevo (campo vacío = no tocar).
            $pin = (string) ($data['pin'] ?? '');
            if ($pin !== '') {
                $this->validarPin($pin);
                $this->usersModel->update($id, $nombre, $pin, $rol);
            } else {
                $this->usersModel->update($id, $nombre, null, $rol);
            }

            echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /** Eliminar usuario */
    public function deleteUser() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = $this->input();
            $id = (int) ($data['idUsuario'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Usuario inválido');
            }

            $usuario = $this->usersModel->getById($id);
            if (!$usuario) {
                throw new Exception('El usuario no existe');
            }

            if ($id === (int) Auth::id()) {
                throw new Exception('No puedes eliminar tu propio usuario');
            }

            if ($usuario['rol'] === 'admin' && $this->usersModel->countAdmins() <= 1) {
                throw new Exception('Debe existir al menos un administrador');
            }

            // Con ventas registradas no se borra: rompería el historial. Se
            // sugiere cambiarle el PIN para bloquear el acceso.
            if ($this->usersModel->hasSales($id)) {
                throw new Exception('Este usuario tiene ventas registradas y no se puede eliminar. Cámbiale el PIN para bloquear su acceso.');
            }

            $this->usersModel->delete($id);

            echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ---------------------------------------------------------------- helpers

    /** Lee el cuerpo JSON del request (con respaldo a $_POST). */
    private function input(): array {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            throw new Exception('Método no permitido');
        }
        $raw = json_decode(file_get_contents('php://input'), true);
        return is_array($raw) ? $raw : $_POST;
    }

    private function validarNombre(string $nombre): void {
        if ($nombre === '') {
            throw new Exception('El nombre es obligatorio');
        }
        if (mb_strlen($nombre) > 50) {
            throw new Exception('El nombre es demasiado largo (máximo 50 caracteres)');
        }
    }

    private function validarPin(string $pin): void {
        if (mb_strlen($pin) < self::PIN_MIN) {
            throw new Exception('El PIN debe tener al menos ' . self::PIN_MIN . ' caracteres');
        }
        if (mb_strlen($pin) > self::PIN_MAX) {
            throw new Exception('El PIN es demasiado largo');
        }
    }

    private function validarRol($rol): string {
        $rol = (string) $rol;
        // Whitelist: nunca confiar en el valor que manda el cliente.
        if (!in_array($rol, ['admin', 'empleado'], true)) {
            throw new Exception('Rol inválido');
        }
        return $rol;
    }
}
