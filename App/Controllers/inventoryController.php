<?php
require_once __DIR__ . '/../Models/inventory.php';
require_once __DIR__ . '/../Models/products.php';

class InventoryController {
    private $inventoryModel;
    private $productsModel;

    public function __construct() {
        $this->inventoryModel = new Inventory();
        $this->productsModel = new Products();
    }

    /**
     * Mostrar vista de inventario
     */
    public function index() {
        require_once __DIR__ . '/../Views/inventory.view.php';
    }

    /**
     * Obtener stock general (JSON)
     */
    public function getStock() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $stock = $this->inventoryModel->obtenerStockGeneral();
            echo json_encode([
                'success' => true,
                'data' => $stock
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener stock de un producto específico
     */
    public function getProductStock() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idProducto = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($idProducto <= 0) {
                throw new Exception('ID de producto inválido');
            }

            $stockActual = $this->inventoryModel->obtenerStockActual($idProducto);
            $historial = $this->inventoryModel->obtenerHistorialProducto($idProducto);

            echo json_encode([
                'success' => true,
                'data' => [
                    'stockActual' => $stockActual,
                    'historial' => $historial
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
     * Registrar CONSUMO de insumo (salida por uso en producción).
     * Ej: "5 kg de maíz para los fritos del día".
     *
     * A diferencia del ajuste, aquí se indica cuánto SE SACÓ, no cuánto quedó:
     * el encargado no tiene que hacer restas mentales y queda registrado en qué
     * se usó la materia prima.
     */
    public function registerConsumption() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?: [];

            if (empty($data['idProducto'])) {
                throw new Exception('El producto es requerido');
            }

            $cantidad = $data['cantidad'] ?? null;
            if ($cantidad === null || $cantidad === '' || !is_numeric($cantidad)) {
                throw new Exception('La cantidad debe ser un número válido');
            }
            $cantidad = (float) $cantidad;
            if ($cantidad <= 0) {
                throw new Exception('La cantidad consumida debe ser mayor que cero');
            }
            if ($cantidad > Validator::MAX_CANTIDAD) {
                throw new Exception('La cantidad supera el máximo permitido (' . Validator::MAX_CANTIDAD . ')');
            }

            $descripcion = trim((string) ($data['descripcion'] ?? ''));
            if ($descripcion === '') {
                throw new Exception('Indica en qué se usó el insumo');
            }

            $this->inventoryModel->registrarConsumo(
                (int) $data['idProducto'],
                $cantidad,
                mb_substr($descripcion, 0, 255),
                $_SESSION['usuario_id'] ?? null
            );

            echo json_encode([
                'success' => true,
                'message' => 'Consumo registrado correctamente',
                'stockActual' => $this->inventoryModel->obtenerStockActual((int) $data['idProducto']),
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Obtener historial de movimientos
     */
    public function getMovements() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $filtros = [];
            
            if (isset($_GET['idProducto'])) {
                $filtros['idProducto'] = $_GET['idProducto'];
            }
            if (isset($_GET['tipoMovimiento'])) {
                $filtros['tipoMovimiento'] = $_GET['tipoMovimiento'];
            }
            if (isset($_GET['fechaDesde'])) {
                $filtros['fechaDesde'] = $_GET['fechaDesde'];
            }
            if (isset($_GET['fechaHasta'])) {
                $filtros['fechaHasta'] = $_GET['fechaHasta'];
            }
            if (isset($_GET['limit'])) {
                $filtros['limit'] = intval($_GET['limit']);
            }

            $movements = $this->inventoryModel->obtenerMovimientos($filtros);

            echo json_encode([
                'success' => true,
                'data' => $movements
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Ajustar stock manualmente
     */
    public function adjustStock() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idProducto'])) {
                throw new Exception('El producto es requerido');
            }
            // El nuevo stock puede ser 0 (dejar sin existencias), pero debe ser
            // numérico, no negativo y dentro de un tope razonable.
            $nuevoStock = $data['nuevoStock'] ?? null;
            if ($nuevoStock === null || $nuevoStock === '' || !is_numeric($nuevoStock)) {
                throw new Exception('El nuevo stock debe ser un número válido');
            }
            $nuevoStock = (float) $nuevoStock;
            if ($nuevoStock < 0) {
                throw new Exception('El nuevo stock no puede ser negativo');
            }
            if ($nuevoStock > Validator::MAX_CANTIDAD) {
                throw new Exception('El nuevo stock supera el máximo permitido (' . Validator::MAX_CANTIDAD . ')');
            }
            if (empty($data['descripcion'])) {
                throw new Exception('La descripción es requerida');
            }

            $idUsuario = $_SESSION['usuario_id'];

            $this->inventoryModel->ajustarStock(
                $data['idProducto'],
                $data['nuevoStock'],
                $data['descripcion'],
                $idUsuario
            );

            echo json_encode([
                'success' => true,
                'message' => 'Stock ajustado exitosamente'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener valor del inventario
     */
    public function getInventoryValue() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $valor = $this->inventoryModel->obtenerValorInventario();
            echo json_encode([
                'success' => true,
                'data' => $valor
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener productos con stock bajo
     */
    public function getLowStock() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
            $lowStock = $this->inventoryModel->obtenerStockBajo($limite);
            echo json_encode([
                'success' => true,
                'data' => $lowStock
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener alertas de stock negativo
     */
    public function getAlertas() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
            $alertas = $this->inventoryModel->obtenerAlertas($limit);
            echo json_encode([
                'success' => true,
                'data' => $alertas,
                'total' => count($alertas)
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener alertas de un producto específico
     */
    public function getProductAlertas() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idProducto = isset($_GET['idProducto']) ? intval($_GET['idProducto']) : 0;
            if ($idProducto <= 0) {
                throw new Exception('ID de producto inválido');
            }

            $alertas = $this->inventoryModel->obtenerAlertasProducto($idProducto);
            echo json_encode([
                'success' => true,
                'data' => $alertas,
                'total' => count($alertas)
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}