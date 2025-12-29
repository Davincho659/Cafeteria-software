<?php
require_once __DIR__ . '/../Models/Sales.php';
require_once __DIR__ . '/../Models/Purchases.php';
require_once __DIR__ . '/../Models/Inventory.php';
require_once __DIR__ . '/../Models/Expenses.php';
require_once __DIR__ . '/../Models/Products.php';
require_once __DIR__ . '/../Models/CashRegister.php';

class ReportsController {
    private $salesModel;
    private $purchasesModel;
    private $inventoryModel;
    private $expensesModel;
    private $productsModel;
    private $cashRegisterModel;

    public function __construct() {
        $this->salesModel = new Sales();
        $this->purchasesModel = new Purchases();
        $this->inventoryModel = new Inventory();
        $this->expensesModel = new Expenses();
        $this->productsModel = new Products();
        $this->cashRegisterModel = new CashRegister();
    }

    
    public function sales() {
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            $limit = 10; 
            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $offset = ($page - 1) * $limit;
            $query = "";

            if (!empty($_POST['idVenta'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " idVenta = " . intval($_POST['idVenta']);
            }

            if (!empty($_POST['precioDesde'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " total >= " . floatval($_POST['precioDesde']);
            }

            if (!empty($_POST['precioHasta'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " total <= " . floatval($_POST['precioHasta']);
            }
            
            if (!empty($_POST['fecha'])) {
                $partes_fecha = explode(" - ", $_POST['fecha']);

                $fecha_inicio = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[0]))->format('Y-m-d') . ' 00:00:00';
                $fecha_fin = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[1]))->format('Y-m-d') . ' 23:59:59';

                $query .= (!empty($query) ? " AND" : " WHERE") . 
                        " fechaVenta BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            }

            if (!empty($_POST['metodoPago'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . 
                        " metodoPago = '" . $_POST['metodoPago'] . "'";
            }

            $resultados = $this->salesModel->getWithPagination($query, $offset, $limit);
            $total = $this->salesModel->countWithFilters($query);
            $totalPaginas = ceil($total / $limit);

        // 👉 RESPUESTA JSON
        
            echo json_encode([
                'resultados' => $resultados,
                'totalPaginas' => $totalPaginas,
                'paginaActual' => $page,
            ]);
            exit;
        }
        require_once __DIR__ . '/../Views/Reports/sales.report.php';
    }

    public function Daily() {
        // Si es petición AJAX con POST (paginación/filtros) → JSON
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $limit = 10; 
            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $offset = ($page - 1) * $limit;
            $query = "";

            if (!empty($_POST['idVenta'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " idVenta = " . intval($_POST['idVenta']);
            }

            if (!empty($_POST['precioDesde'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " total >= " . floatval($_POST['precioDesde']);
            }

            if (!empty($_POST['precioHasta'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " total <= " . floatval($_POST['precioHasta']);
            }
            
            if (!empty($_POST['fecha'])) {
                $partes_fecha = explode(" - ", $_POST['fecha']);

                $fecha_inicio = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[0]))->format('Y-m-d') . ' 00:00:00';
                $fecha_fin = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[1]))->format('Y-m-d') . ' 23:59:59';

                $query .= (!empty($query) ? " AND" : " WHERE") . 
                        " fechaVenta BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            }

            if (!empty($_POST['metodoPago'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . 
                        " metodoPago = '" . $_POST['metodoPago'] . "'";
            }

            $resultados = $this->salesModel->getWithPagination($query, $offset, $limit);
            $total = $this->salesModel->countWithFilters($query);
            $totalPaginas = ceil($total / $limit);

            echo json_encode([
                'resultados' => $resultados,
                'totalPaginas' => $totalPaginas,
                'paginaActual' => $page,
            ]);
            exit;
        }
        
        // Si es petición AJAX GET (carga inicial del modal) → HTML sin layout
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            require_once __DIR__ . '/../Views/Reports/daily.report.php';
            exit;
        }
        
        // Si es petición normal → cargar con layout (manejado por Index.php)
        require_once __DIR__ . '/../Views/Reports/daily.report.php';
    }

    /**
     * Reporte de Compras
     */
    public function purchases() {
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            $limit = 10;
            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $offset = ($page - 1) * $limit;
            
            $filtros = [];
            if (!empty($_POST['idProveedor'])) $filtros['idProveedor'] = $_POST['idProveedor'];
            if (!empty($_POST['tipoCompra'])) $filtros['tipoCompra'] = $_POST['tipoCompra'];
            
            if (!empty($_POST['fecha'])) {
                $partes = explode(" - ", $_POST['fecha']);
                $filtros['fechaDesde'] = DateTime::createFromFormat('d/m/Y', trim($partes[0]))->format('Y-m-d');
                $filtros['fechaHasta'] = DateTime::createFromFormat('d/m/Y', trim($partes[1]))->format('Y-m-d');
            }

            $resultados = $this->purchasesModel->getFiltered($filtros);
            $total = count($resultados);
            $totalPaginas = ceil($total / $limit);
            $resultados = array_slice($resultados, $offset, $limit);

            echo json_encode([
                'resultados' => $resultados,
                'totalPaginas' => $totalPaginas,
                'paginaActual' => $page
            ]);
            exit;
        }
        require_once __DIR__ . '/../Views/Reports/purchases.report.php';
    }

    /**
     * Reporte de Gastos
     */
    public function expenses() {
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            $limit = 10;
            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $offset = ($page - 1) * $limit;
            
            $filtros = [];
            if (!empty($_POST['tipo'])) $filtros['tipo'] = $_POST['tipo'];
            
            if (!empty($_POST['fecha'])) {
                $partes = explode(" - ", $_POST['fecha']);
                $filtros['fechaDesde'] = DateTime::createFromFormat('d/m/Y', trim($partes[0]))->format('Y-m-d');
                $filtros['fechaHasta'] = DateTime::createFromFormat('d/m/Y', trim($partes[1]))->format('Y-m-d');
            }

            $resultados = $this->expensesModel->getFiltered($filtros);
            $total = count($resultados);
            $totalPaginas = ceil($total / $limit);
            $resultados = array_slice($resultados, $offset, $limit);

            echo json_encode([
                'resultados' => $resultados,
                'totalPaginas' => $totalPaginas,
                'paginaActual' => $page
            ]);
            exit;
        }
        require_once __DIR__ . '/../Views/Reports/expenses.report.php';
    }

    /**
     * Reporte de Rentabilidad (Ganancia = Ventas - Costos - Gastos)
     */
    public function profitability() {
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            
            $fechaDesde = date('Y-m-d');
            $fechaHasta = date('Y-m-d');
            
            if (!empty($_POST['fecha'])) {
                $partes = explode(" - ", $_POST['fecha']);
                $fechaDesde = DateTime::createFromFormat('d/m/Y', trim($partes[0]))->format('Y-m-d');
                $fechaHasta = DateTime::createFromFormat('d/m/Y', trim($partes[1]))->format('Y-m-d');
            }

            // Obtener ventas
            $ventas = $this->salesModel->getSalesByDateRange($fechaDesde, $fechaHasta);
            $totalVentas = 0;
            $totalCostos = 0;

            foreach ($ventas as $venta) {
                if ($venta['estado'] === 'completada') {
                    $totalVentas += (float)$venta['total'];
                    $detalles = $this->salesModel->getSaleDetails($venta['idVenta']);
                    foreach ($detalles as $det) {
                        $costoPromedio = $this->productsModel->getCostoPromedioProducto($det['idProducto']);
                        $totalCostos += $costoPromedio * (float)$det['cantidad'];
                    }
                }
            }

            // Obtener compras
            $compras = $this->purchasesModel->getTotalByDateRange($fechaDesde, $fechaHasta);
            $totalCompras = (float)$compras;

            // Obtener gastos
            $gastos = $this->expensesModel->getTotalByDateRange($fechaDesde, $fechaHasta);
            $totalGastos = (float)$gastos['totalGastos'];

            $gananciaReal = $totalVentas - $totalCostos - $totalGastos;

            echo json_encode([
                'totalVentas' => $totalVentas,
                'totalCostos' => $totalCostos,
                'totalGastos' => $totalGastos,
                'totalCompras' => $totalCompras,
                'gananciaReal' => $gananciaReal,
                'margenPorcentaje' => $totalVentas > 0 ? ($gananciaReal / $totalVentas) * 100 : 0
            ]);
            exit;
        }
        require_once __DIR__ . '/../Views/Reports/profitability.report.php';
    }

    /**
     * Reporte de Movimientos de Caja
     */
    public function cashRegister() {
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            
            $cajaActiva = $this->cashRegisterModel->getCajaActiva();
            if (!$cajaActiva) {
                echo json_encode(['error' => 'No hay caja abierta']);
                exit;
            }

            $resumen = $this->cashRegisterModel->getCajaResumen($cajaActiva['idCaja']);
            echo json_encode(['resumen' => $resumen]);
            exit;
        }
        require_once __DIR__ . '/../Views/Reports/cashRegister.report.php';
    }

    /**
     * Reporte de Inventario (Alertas y Stock Bajo)
     */
    public function inventoryReport() {
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            
            $alertas = $this->inventoryModel->obtenerAlertas(50);
            $stockBajo = $this->inventoryModel->obtenerStockBajo(10);
            $valorInventario = $this->inventoryModel->obtenerValorInventario();

            echo json_encode([
                'alertas' => $alertas,
                'stockBajo' => $stockBajo,
                'valorInventario' => $valorInventario
            ]);
            exit;
        }
        require_once __DIR__ . '/../Views/Reports/inventory.report.php';
    }

    /**
     * Productos más vendidos
     */
    public function topProducts() {
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
            $topProducts = $this->salesModel->getTopProductsToday($limit);

            echo json_encode(['productos' => $topProducts]);
            exit;
        }
        require_once __DIR__ . '/../Views/Reports/topProducts.report.php';
    }

    
}