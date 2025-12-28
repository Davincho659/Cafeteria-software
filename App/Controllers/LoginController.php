<?php
require_once __DIR__ . '/../Models/Users.php';

class LoginController {
    private $usersModel;

    public function __construct() {
        $this->usersModel = new Users();
    }

    /**
     * Mostrar vista de login
     */
    public function index() {
        // Si ya está autenticado, redirigir al home
        if (isset($_SESSION['usuario_id'])) {
            header('Location: ?pg=home');
            exit;
        }
        
        require_once __DIR__ . '/../Views/Login.view.php';
    }

    /**
     * Autenticar usuario
     */
    public function authenticate() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // Obtener datos del request
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                // Si no hay JSON, intentar obtener de POST tradicional
                $nombre = $_POST['nombre'] ?? null;
                $pin = $_POST['pin'] ?? null;
            } else {
                $nombre = $data['nombre'] ?? null;
                $pin = $data['pin'] ?? null;
            }
            
            // Validar datos
            if (empty($nombre) || empty($pin)) {
                throw new Exception('Usuario y PIN son requeridos');
            }
            
            // Buscar usuario
            $usuario = $this->usersModel->getByNameAndPin($nombre, $pin);
            
            if (!$usuario) {
                throw new Exception('Usuario o PIN incorrectos');
            }
            
            // Crear sesión
            $_SESSION['usuario_id'] = $usuario['idUsuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            $_SESSION['login_time'] = time();
            
            echo json_encode([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'usuario' => [
                    'id' => $usuario['idUsuario'],
                    'nombre' => $usuario['nombre'],
                    'rol' => $usuario['rol']
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout() {
        // Destruir todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la cookie de sesión si existe
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destruir la sesión
        session_destroy();
        
        // Redirigir al login
        header('Location: ?pg=login');
        exit;
    }

    /**
     * Verificar si el usuario está autenticado (API)
     */
    public function checkAuth() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (isset($_SESSION['usuario_id'])) {
            echo json_encode([
                'success' => true,
                'authenticated' => true,
                'usuario' => [
                    'id' => $_SESSION['usuario_id'],
                    'nombre' => $_SESSION['usuario_nombre'],
                    'rol' => $_SESSION['usuario_rol']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'authenticated' => false
            ]);
        }
    }

    /**
     * Obtener información del usuario actual
     */
    public function getCurrentUser() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode([
                'success' => false,
                'error' => 'No autenticado'
            ]);
            return;
        }
        
        try {
            $usuario = $this->usersModel->getById($_SESSION['usuario_id']);
            
            if (!$usuario) {
                throw new Exception('Usuario no encontrado');
            }
            
            // No incluir el PIN en la respuesta
            unset($usuario['pin']);
            
            echo json_encode([
                'success' => true,
                'data' => $usuario
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}