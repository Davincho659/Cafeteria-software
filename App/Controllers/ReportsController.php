<?php
require_once __DIR__ . '/../Models/Sales.php';
require_once __DIR__ . '/../Models/Purchases.php';
require_once __DIR__ . '/../Models/Inventory.php';

class ReportsController {
    private $salesModel;
    private $purchasesModel;
    private $inventoryModel;

    public function __construct() {
        $this->salesModel = new Sales();
        $this->purchasesModel = new Purchases();
        $this->inventoryModel = new Inventory();
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

    public function getDailyReport() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $fecha = date('Y-m-d');
            
            // Ventas del día
            $totalVentas = $this->salesModel->getTotalByDateRange($fecha, $fecha);
            $ventasDetalle = $this->salesModel->getTodayTotals();
            
            // Compras del día
            $totalCompras = $this->purchasesModel->getTotalByDateRange($fecha, $fecha);
            
            // Ganancia bruta del día
            $ganancia = $totalVentas - $totalCompras;
            
            // Productos más vendidos
            $topProductos = $this->salesModel->getTopProductsToday(10);
            
            // Ventas del día
            $ventas = $this->salesModel->getSalesByDate($fecha);
            
            // Compras del día
            $compras = $this->purchasesModel->getByDate($fecha);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'fecha' => $fecha,
                    'resumen' => [
                        'totalVentas' => $totalVentas,
                        'cantidadVentas' => $ventasDetalle['totalVentas'],
                        'ventasDetalladas' => $ventasDetalle['totalDetalladas'],
                        'ventasRapidas' => $ventasDetalle['totalRapidas'],
                        'totalCompras' => $totalCompras,
                        'ganancia' => $ganancia
                    ],
                    'topProductos' => $topProductos,
                    'ventas' => $ventas,
                    'compras' => $compras
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
     * Reporte por rango de fechas
     */
    public function getDateRangeReport() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $fechaDesde = $_GET['fechaDesde'] ?? date('Y-m-d');
            $fechaHasta = $_GET['fechaHasta'] ?? date('Y-m-d');
            
            // Validar fechas
            if (strtotime($fechaDesde) > strtotime($fechaHasta)) {
                throw new Exception('La fecha de inicio no puede ser mayor a la fecha final');
            }
            
            // Ventas del período
            $totalVentas = $this->salesModel->getTotalByDateRange($fechaDesde, $fechaHasta);
            $ventas = $this->salesModel->getSalesByDateRange($fechaDesde, $fechaHasta);
            
            // Compras del período
            $totalCompras = $this->purchasesModel->getTotalByDateRange($fechaDesde, $fechaHasta);
            $compras = $this->purchasesModel->getFiltered([
                'fechaDesde' => $fechaDesde,
                'fechaHasta' => $fechaHasta
            ]);
            
            // Ganancia del período
            $ganancia = $totalVentas - $totalCompras;
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'fechaDesde' => $fechaDesde,
                    'fechaHasta' => $fechaHasta,
                    'resumen' => [
                        'totalVentas' => $totalVentas,
                        'cantidadVentas' => count($ventas),
                        'totalCompras' => $totalCompras,
                        'cantidadCompras' => count($compras),
                        'ganancia' => $ganancia
                    ],
                    'ventas' => $ventas,
                    'compras' => $compras
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
     * Reporte de inventario
     */
    public function getInventoryReport() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $stock = $this->inventoryModel->obtenerStockGeneral();
            $valor = $this->inventoryModel->obtenerValorInventario();
            $stockBajo = $this->inventoryModel->obtenerStockBajo(10);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'stock' => $stock,
                    'valor' => $valor,
                    'stockBajo' => $stockBajo,
                    'totalProductos' => count($stock),
                    'productosStockBajo' => count($stockBajo)
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
     * Reporte de productos más vendidos
     */
    public function getTopProductsReport() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $fechaDesde = $_GET['fechaDesde'] ?? date('Y-m-d');
            $fechaHasta = $_GET['fechaHasta'] ?? date('Y-m-d');
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            
            // Obtener productos más vendidos en el rango
            $sql = "SELECT 
                        p.idProducto,
                        p.nombre,
                        c.nombre AS categoria,
                        SUM(dv.cantidad) as cantidadVendida,
                        SUM(dv.subTotal) as totalVentas,
                        AVG(dv.precioUnitario) as precioPromedio
                    FROM detalleventa dv
                    INNER JOIN ventas v ON dv.idVenta = v.idVenta
                    INNER JOIN productos p ON dv.idProducto = p.idProducto
                    INNER JOIN categorias c ON p.idCategoria = c.idCategoria
                    WHERE DATE(v.fechaVenta) BETWEEN ? AND ? 
                    AND v.estado = 'completada'
                    GROUP BY p.idProducto, p.nombre, c.nombre
                    ORDER BY cantidadVendida DESC
                    LIMIT ?";
            
            $db = Database::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute([$fechaDesde, $fechaHasta, $limit]);
            $topProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'fechaDesde' => $fechaDesde,
                    'fechaHasta' => $fechaHasta,
                    'productos' => $topProductos
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
     * Resumen ejecutivo (dashboard)
     */
    public function getExecutiveSummary() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $hoy = date('Y-m-d');
            $mesActual = date('Y-m');
            
            // Ventas de hoy
            $ventasHoy = $this->salesModel->getTotalByDateRange($hoy, $hoy);
            
            // Ventas del mes
            $ventasMes = $this->salesModel->getTotalByDateRange($mesActual . '-01', $hoy);
            
            // Compras de hoy
            $comprasHoy = $this->purchasesModel->getTotalByDateRange($hoy, $hoy);
            
            // Compras del mes
            $comprasMes = $this->purchasesModel->getTotalByDateRange($mesActual . '-01', $hoy);
            
            // Valor del inventario
            $valorInventario = $this->inventoryModel->obtenerValorInventario();
            
            // Productos con stock bajo
            $stockBajo = count($this->inventoryModel->obtenerStockBajo(10));
            
            // Top 5 productos del día
            $topProductosHoy = $this->salesModel->getTopProductsToday(5);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'hoy' => [
                        'fecha' => $hoy,
                        'ventas' => $ventasHoy,
                        'compras' => $comprasHoy,
                        'ganancia' => $ventasHoy - $comprasHoy
                    ],
                    'mes' => [
                        'periodo' => $mesActual,
                        'ventas' => $ventasMes,
                        'compras' => $comprasMes,
                        'ganancia' => $ventasMes - $comprasMes
                    ],
                    'inventario' => [
                        'valorCompra' => $valorInventario['valorCompra'],
                        'valorVenta' => $valorInventario['valorVenta'],
                        'productosBajoStock' => $stockBajo
                    ],
                    'topProductosHoy' => $topProductosHoy
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}