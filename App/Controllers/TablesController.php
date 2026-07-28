<?php
require_once __DIR__ . '/../Models/Tables.php';

class TablesController {
    private $tablesModel;

    public function __construct() {
        $this->tablesModel = new Tables();
    }

    /**
     * Mostrar vista de mesas
     */
    public function index() {
        require_once __DIR__ . '/../Views/tables.view.php';
    }

    /**
     * Obtener todas las mesas (JSON)
     */
    public function getTables() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $tables = $this->tablesModel->getAll();
            echo json_encode([
                'success' => true,
                'data' => $tables
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Guardar el plano del salón (posiciones X/Y de todas las mesas).
     */
    public function savePositions() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $posiciones = $data['posiciones'] ?? null;
            if (!is_array($posiciones)) {
                throw new Exception('Formato de posiciones inválido');
            }
            $this->tablesModel->savePositions($posiciones);
            echo json_encode(['success' => true, 'message' => 'Distribución guardada']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Obtener estadísticas de mesas
     */
    public function getStatistics() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $stats = $this->tablesModel->getStatistics();
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function createTable() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['nombre'])) {
                throw new Exception('El nombre de la mesa es requerido');
            }

            if (empty($data['numero'])) {
                throw new Exception('El numero de la mesa es requerido');
            }

            $tipo = ($data['tipo'] ?? 'mesa') === 'barra' ? 'barra' : 'mesa';
            $idMesa = $this->tablesModel->create($data['nombre'], $data['numero'], 'libre', $tipo);

            echo json_encode([
                'success' => true,
                'message' => ($tipo === 'barra' ? 'Barra' : 'Mesa') . ' creada exitosamente',
                'idMesa' => $idMesa
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

        public function updateTable() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
           

            if (empty($data['nombre'])) {
                throw new Exception('El nombre de la mesa es requerido');
            }

            if (empty($data['numero'])) {
                throw new Exception('El numero de la mesa es requerido');
            }

            $updated = $this->tablesModel->update($data['idMesa'], $data['nombre'], $data['numero']);

            if (!$updated) {
                throw new Exception('No se pudo actualizar la mesa');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Mesa actualizada exitosamente'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }



    
}