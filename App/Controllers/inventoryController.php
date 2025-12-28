<?php
require_once __DIR__ . '/../Models/Inventory.php';
require_once __DIR__ . '/../Models/Products.php';

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
            if (!isset($data['nuevoStock']) || $data['nuevoStock'] < 0) {
                throw new Exception('El nuevo stock debe ser un número válido');
            }
            if (empty($data['descripcion'])) {
                throw new Exception('La descripción es requerida');
            }

            $idUsuario = $data['idUsuario'] ?? null;

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
}