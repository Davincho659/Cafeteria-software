<?php
require_once __DIR__ . '/../Models/UnitsOfMeasure.php';

class UnitsController {
    private $unitsModel;

    public function __construct() {
        $this->unitsModel = new UnitsOfMeasure();
    }

    /**
     * GET todas las unidades activas
     * Ruta: ?pg=units&action=getAll
     */
    public function getAll() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $units = $this->unitsModel->getAll();
            echo json_encode([
                'success' => true,
                'data' => $units
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * GET unidades por tipo (peso, volumen, unidad)
     * Ruta: ?pg=units&action=getByTipo&tipo=peso
     */
    public function getByTipo() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $tipo = $_GET['tipo'] ?? null;
            if (!$tipo) {
                throw new Exception('Tipo de unidad requerido');
            }
            $units = $this->unitsModel->getByTipo($tipo);
            echo json_encode([
                'success' => true,
                'data' => $units
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * GET unidad por ID
     * Ruta: ?pg=units&action=getById&id=1
     */
    public function getById() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idUnidad = $_GET['id'] ?? null;
            if (!$idUnidad) {
                throw new Exception('ID de unidad requerido');
            }
            $unit = $this->unitsModel->getById((int)$idUnidad);
            if (!$unit) {
                throw new Exception('Unidad no encontrada');
            }
            echo json_encode([
                'success' => true,
                'data' => $unit
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST crear nueva unidad (solo admin)
     * Body JSON: { nombre, abreviatura, tipo }
     * Ruta: ?pg=units&action=create
     */
    public function create() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?: [];
            $nombre = $data['nombre'] ?? null;
            $abreviatura = $data['abreviatura'] ?? null;
            $tipo = $data['tipo'] ?? null;

            if (!$nombre || !$abreviatura || !$tipo) {
                throw new Exception('Nombre, abreviatura y tipo son requeridos');
            }

            $validTipos = ['peso', 'volumen', 'unidad'];
            if (!in_array($tipo, $validTipos, true)) {
                throw new Exception('Tipo inválido. Use: peso, volumen, unidad');
            }

            $idUnidad = $this->unitsModel->create($nombre, $abreviatura, $tipo);
            $unit = $this->unitsModel->getById($idUnidad);

            echo json_encode([
                'success' => true,
                'message' => 'Unidad creada correctamente',
                'data' => $unit
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST actualizar unidad
     * Body JSON: { idUnidad, nombre, abreviatura, tipo, activo }
     * Ruta: ?pg=units&action=update
     */
    public function update() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?: [];
            $idUnidad = $data['idUnidad'] ?? null;
            $nombre = $data['nombre'] ?? null;
            $abreviatura = $data['abreviatura'] ?? null;
            $tipo = $data['tipo'] ?? null;
            $activo = $data['activo'] ?? 1;

            if (!$idUnidad || !$nombre || !$abreviatura || !$tipo) {
                throw new Exception('Todos los campos son requeridos');
            }

            $this->unitsModel->update((int)$idUnidad, $nombre, $abreviatura, $tipo, (int)$activo);
            $unit = $this->unitsModel->getById((int)$idUnidad);

            echo json_encode([
                'success' => true,
                'message' => 'Unidad actualizada correctamente',
                'data' => $unit
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST desactivar unidad (no eliminar)
     * Body JSON: { idUnidad }
     * Ruta: ?pg=units&action=deactivate
     */
    public function deactivate() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?: [];
            $idUnidad = $data['idUnidad'] ?? null;

            if (!$idUnidad) {
                throw new Exception('ID de unidad requerido');
            }

            // Verificar si está en uso
            if ($this->unitsModel->isInUse((int)$idUnidad)) {
                throw new Exception('No se puede desactivar: la unidad está en uso por productos');
            }

            $this->unitsModel->deactivate((int)$idUnidad);

            echo json_encode([
                'success' => true,
                'message' => 'Unidad desactivada correctamente'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * GET unidades básicas del sistema (u, kg, L)
     * Ruta: ?pg=units&action=getBasic
     */
    public function getBasic() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $units = $this->unitsModel->getBasicUnits();
            echo json_encode([
                'success' => true,
                'data' => $units
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
