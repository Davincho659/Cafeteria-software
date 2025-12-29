<?php
require_once __DIR__ . '/../Models/Expenses.php';
require_once __DIR__ . '/../Models/Products.php';

class ExpensesController {
    private $expensesModel;
    private $productsModel;

    public function __construct() {
        $this->expensesModel = new Expenses();
        $this->productsModel = new Products();
    }

    /**
     * Crear gasto de producto (merma, rotura, vencimiento, etc)
     */
    public function createProductExpense() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['idProducto'])) {
                throw new Exception('El producto es requerido');
            }
            if (empty($data['cantidad']) || $data['cantidad'] <= 0) {
                throw new Exception('La cantidad debe ser mayor a cero');
            }
            if (empty($data['motivo'])) {
                throw new Exception('El motivo es requerido');
            }

            $idProducto = (int)$data['idProducto'];
            $cantidad = (float)$data['cantidad'];
            $motivo = trim($data['motivo']);
            $monto = isset($data['monto']) ? (float)$data['monto'] : null;
            $idUsuario = $data['idUsuario'] ?? null;

            // Validar que el producto exista
            $producto = $this->productsModel->getById($idProducto);
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }

            // Crear gasto
            $idGasto = $this->expensesModel->crearGastoProducto(
                $idProducto,
                $cantidad,
                $motivo,
                $monto,
                $idUsuario
            );

            echo json_encode([
                'success' => true,
                'message' => 'Gasto de producto registrado exitosamente',
                'idGasto' => $idGasto,
                'data' => [
                    'producto' => $producto['nombre'],
                    'cantidad' => $cantidad,
                    'motivo' => $motivo,
                    'monto' => $monto ?? ($cantidad * $producto['precioCompra'])
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
     * Crear gasto externo (servicios, suministros, etc)
     */
    public function createExternalExpense() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['concepto'])) {
                throw new Exception('El concepto es requerido');
            }
            if (empty($data['monto']) || $data['monto'] <= 0) {
                throw new Exception('El monto debe ser mayor a cero');
            }

            $concepto = trim($data['concepto']);
            $monto = (float)$data['monto'];
            $descripcion = isset($data['descripcion']) ? trim($data['descripcion']) : null;
            $idUsuario = $data['idUsuario'] ?? null;

            // Crear gasto
            $idGasto = $this->expensesModel->crearGastoExterno(
                $concepto,
                $monto,
                $descripcion,
                $idUsuario
            );

            echo json_encode([
                'success' => true,
                'message' => 'Gasto externo registrado exitosamente',
                'idGasto' => $idGasto,
                'data' => [
                    'concepto' => $concepto,
                    'monto' => $monto,
                    'descripcion' => $descripcion
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
     * Obtener gasto por ID
     */
    public function getExpense() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }

            $gasto = $this->expensesModel->getById($id);
            if (!$gasto) {
                throw new Exception('Gasto no encontrado');
            }

            echo json_encode([
                'success' => true,
                'data' => $gasto
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener todos los gastos
     */
    public function getExpenses() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $filtros = [];
            
            if (isset($_GET['tipo'])) {
                $filtros['tipo'] = $_GET['tipo'];
            }
            if (isset($_GET['idProducto'])) {
                $filtros['idProducto'] = $_GET['idProducto'];
            }
            if (isset($_GET['fechaDesde'])) {
                $filtros['fechaDesde'] = $_GET['fechaDesde'];
            }
            if (isset($_GET['fechaHasta'])) {
                $filtros['fechaHasta'] = $_GET['fechaHasta'];
            }
            if (isset($_GET['idUsuario'])) {
                $filtros['idUsuario'] = $_GET['idUsuario'];
            }

            $gastos = empty($filtros) 
                ? $this->expensesModel->getAll() 
                : $this->expensesModel->getFiltered($filtros);

            echo json_encode([
                'success' => true,
                'data' => $gastos
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener gastos del día
     */
    public function getTodayExpenses() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $gastos = $this->expensesModel->getTodayExpenses();
            echo json_encode([
                'success' => true,
                'data' => $gastos
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener total de gastos del día
     */
    public function getTodayTotal() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $total = $this->expensesModel->getTodayTotal();
            echo json_encode([
                'success' => true,
                'total' => $total
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener gastos por rango de fechas
     */
    public function getExpensesByDateRange() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $fechaDesde = $_GET['fechaDesde'] ?? null;
            $fechaHasta = $_GET['fechaHasta'] ?? null;

            if (!$fechaDesde || !$fechaHasta) {
                throw new Exception('Debe proporcionar fechaDesde y fechaHasta');
            }

            $totales = $this->expensesModel->getTotalByDateRange($fechaDesde, $fechaHasta);
            echo json_encode([
                'success' => true,
                'data' => $totales
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener resumen de gastos por tipo
     */
    public function getExpensesSummary() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $fechaDesde = $_GET['fechaDesde'] ?? null;
            $fechaHasta = $_GET['fechaHasta'] ?? null;

            $resumen = $this->expensesModel->getResumenPorTipo($fechaDesde, $fechaHasta);
            echo json_encode([
                'success' => true,
                'data' => $resumen
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener gastos de un producto específico (historial de mermas)
     */
    public function getProductExpenses() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idProducto = isset($_GET['idProducto']) ? intval($_GET['idProducto']) : 0;
            if ($idProducto <= 0) {
                throw new Exception('ID de producto inválido');
            }

            $gastos = $this->expensesModel->getByProduct($idProducto);
            echo json_encode([
                'success' => true,
                'data' => $gastos
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
