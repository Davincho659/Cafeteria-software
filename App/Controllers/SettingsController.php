<?php
require_once __DIR__ . '/../Models/Settings.php';

class SettingsController {
    private $settings;

    public function __construct() {
        $this->settings = new Settings();
    }

    /** Vista de configuración */
    public function index() {
        require_once __DIR__ . '/../Views/settings.view.php';
    }

    /** Guardar configuración (nombre, colores, mensaje y logo opcional) */
    public function save() {
        header('Content-Type: application/json; charset=utf-8');

        // Solo administradores pueden cambiar la configuración del negocio.
        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        try {
            $valores = [];

            if (isset($_POST['nombre_negocio'])) {
                $nombre = trim($_POST['nombre_negocio']);
                if ($nombre === '') {
                    throw new Exception('El nombre del negocio no puede estar vacío');
                }
                $valores['nombre_negocio'] = mb_substr($nombre, 0, 100);
            }

            if (isset($_POST['moneda'])) {
                $valores['moneda'] = mb_substr(trim($_POST['moneda']), 0, 5) ?: '$';
            }

            if (isset($_POST['mensaje_factura'])) {
                $valores['mensaje_factura'] = mb_substr(trim($_POST['mensaje_factura']), 0, 150);
            }

            // Datos que se imprimen en el tiquete térmico (todos opcionales)
            $camposTicket = [
                'nit'         => 30,
                'direccion'   => 120,
                'telefono'    => 40,
                'mensaje_pie' => 150,
            ];
            foreach ($camposTicket as $campo => $maxLen) {
                if (isset($_POST[$campo])) {
                    $valores[$campo] = mb_substr(trim($_POST[$campo]), 0, $maxLen);
                }
            }

            // Colores: se validan como hex #RRGGBB para no inyectar CSS.
            foreach (Settings::colorKeys() as $clave) {
                if (isset($_POST[$clave])) {
                    $color = trim($_POST[$clave]);
                    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                        throw new Exception("Color inválido en {$clave}");
                    }
                    $valores[$clave] = strtoupper($color);
                }
            }

            // Logo opcional
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $destino = (defined('PUBLIC_PATH') ? PUBLIC_PATH : dirname(dirname(__DIR__)) . '/Public')
                         . '/Assets/img';
                $resultado = saveUploadedImage($_FILES['logo'], $destino, null);
                if (!$resultado['success']) {
                    throw new Exception('No se pudo subir el logo: ' . $resultado['error']);
                }
                $valores['logo'] = $resultado['filename'];
            }

            $this->settings->saveMany($valores);

            echo json_encode([
                'success' => true,
                'message' => 'Configuración guardada correctamente',
                'data'    => $this->settings->getAll(),
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
