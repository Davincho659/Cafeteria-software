<?php
require_once __DIR__ . '/../Models/Users.php';

class LoginController {
    private $userModel;

    public function __construct() {
        $this->userModel = new Users();
    }

    
    public function login() {
        try {
            // Asegurar que la sesión esté iniciada (Init.php normalmente la inicia)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                // Mostrar formulario de login
                require __DIR__ . '/../Views/Login.view.php';
                return;
            }

            $username = $_POST['username'] ?? null;
            $pin = $_POST['pin'] ?? null;

            if (!$username || !$pin) {
                $error = 'Credenciales incompletas.';
                require __DIR__ . '/../Views/Login.view.php';
                return;
            }
            
            
            
            $user = $this->userModel->getByUsername($username);
            

            if ($user && !empty($user['pin'])) {
                error_log('DEBUG: Usuario existe y tiene pin, verificando password_verify');
                
                $pinVerified = password_verify($pin, $user['pin']) || ($user['pin'] === $pin);
                
                
                if ($pinVerified) {
                    $_SESSION['user_id'] = $user['idUsuario'];
                    $_SESSION['username'] = $user['nombre'];
                    $_SESSION['role'] = $user['rol'] ?? 'empleado';

                    if (strtolower($_SESSION['role']) === 'admin') {
                        header('Location: index.php?pg=adminHome');
                        exit;
                    } else {
                        header('Location: index.php?pg=home');
                        exit;
                    }
                } else {
                    $error = 'Usuario o pin incorrectos.';
                    require __DIR__ . '/../Views/Login.view.php';
                    return;
                }
            } else {
                $error = 'Usuario o pin incorrectos.';
                require __DIR__ . '/../Views/Login.view.php';
                return;
            }

        } catch (Exception $e) {
            $error = 'Error al procesar la solicitud: ' . $e->getMessage();
            require __DIR__ . '/../Views/Login.view.php';
            return;
        }
    }

    public function logout() {
    // Limpia sesión y redirige a la pantalla de login
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
    session_destroy();
    header('Location: index.php?pg=login');
        exit;
    }
}
