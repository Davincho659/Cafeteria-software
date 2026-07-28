<?php
require_once __DIR__ . '/../Models/sales.php';
require_once __DIR__ . '/../Models/purchases.php';
require_once __DIR__ . '/../Models/inventory.php';
require_once __DIR__ . '/../Models/Expenses.php';
require_once __DIR__ . '/../Models/products.php';
require_once __DIR__ . '/../Models/cashRegister.php';
require_once __DIR__ . '/../Models/Closings.php';

class ReportsController {
    private $salesModel;
    private $purchasesModel;
    private $inventoryModel;
    private $expensesModel;
    private $productsModel;
    private $cashRegisterModel;
    private $closingsModel;

    public function __construct() {
        $this->salesModel = new Sales();
        $this->purchasesModel = new Purchases();
        $this->inventoryModel = new Inventory();
        $this->expensesModel = new Expenses();
        $this->productsModel = new Products();
        $this->cashRegisterModel = new CashRegister();
        $this->closingsModel = new Closings();
    }

    
    public function sales() {
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            $limit = 10; 
            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $offset = ($page - 1) * $limit;
            
            // Permitir mostrar todas las ventas si se especifica, sino solo las completadas
            $mostrarTodas = isset($_POST['mostrarTodas']) && $_POST['mostrarTodas'] == 1;
            $query = $mostrarTodas ? " WHERE 1=1" : " WHERE estado = 'completada'";

            if (!empty($_POST['idVenta'])) {
                $query .= " AND idVenta = " . intval($_POST['idVenta']);
            }

            if (!empty($_POST['precioDesde'])) {
                $query .= " AND total >= " . floatval($_POST['precioDesde']);
            }

            if (!empty($_POST['precioHasta'])) {
                $query .= " AND total <= " . floatval($_POST['precioHasta']);
            }
            
            if (!empty($_POST['fecha'])) {
                $partes_fecha = explode(" - ", $_POST['fecha']);

                $fecha_inicio = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[0]))->format('Y-m-d') . ' 00:00:00';
                $fecha_fin = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[1]))->format('Y-m-d') . ' 23:59:59';

                $query .= " AND fechaVenta BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            }

            $query .= $this->buildMetodoPagoClause($_POST['metodoPago'] ?? '');

            if (!empty($_POST['estado'])
                && in_array($_POST['estado'], ['completada', 'cancelada', 'pendiente'], true)) {
                // Whitelist del estado (antes se concatenaba directo).
                $query .= " AND estado = '" . $_POST['estado'] . "'";
            }

            $resultados = $this->salesModel->getWithPagination($query, $offset, $limit);
            $total = $this->salesModel->countWithFilters($query);
            $totalPaginas = ceil($total / $limit);

        // 👉 RESPUESTA JSON

            echo json_encode([
                'resultados' => $resultados,
                'totalPaginas' => $totalPaginas,
                'paginaActual' => $page,
                // Totales de TODO el conjunto filtrado (no solo la página actual),
                // calculados en una sola consulta para la fila de totales y KPIs.
                'totales' => $this->salesModel->getTotalsWithFilters($query),
            ]);
            exit;
        }
        require_once __DIR__ . '/../Views/Reports/sales.report.php';
    }

    public function Daily() {
        // Si es petición AJAX con POST (paginación/filtros) → JSON
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            error_log('Daily() - Petición AJAX POST recibida');
            error_log('POST data: ' . print_r($_POST, true));
            
            $limit = 10; 
            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $offset = ($page - 1) * $limit;
            $query = " WHERE estado = 'completada'";

            if (!empty($_POST['idVenta'])) {
                $query .= " AND idVenta = " . intval($_POST['idVenta']);
            }

            if (!empty($_POST['precioDesde'])) {
                $query .= " AND total >= " . floatval($_POST['precioDesde']);
            }

            if (!empty($_POST['precioHasta'])) {
                $query .= " AND total <= " . floatval($_POST['precioHasta']);
            }
            
            if (!empty($_POST['fecha'])) {
                $partes_fecha = explode(" - ", $_POST['fecha']);

                $fecha_inicio = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[0]))->format('Y-m-d') . ' 00:00:00';
                $fecha_fin = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[1]))->format('Y-m-d') . ' 23:59:59';

                $query .= " AND fechaVenta BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            }

            $query .= $this->buildMetodoPagoClause($_POST['metodoPago'] ?? '');

            error_log('Query generado: ' . $query);
            $resultados = $this->salesModel->getWithPagination($query, $offset, $limit);
            $total = $this->salesModel->countWithFilters($query);
            $totalPaginas = ceil($total / $limit);

            echo json_encode([
                'resultados' => $resultados,
                'totalPaginas' => $totalPaginas,
                'paginaActual' => $page,
                'totales' => $this->salesModel->getTotalsWithFilters($query),
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
            if (!empty($_POST['precioDesde'])) $filtros['precioDesde'] = floatval($_POST['precioDesde']);
            if (!empty($_POST['precioHasta'])) $filtros['precioHasta'] = floatval($_POST['precioHasta']);
            if (!empty($_POST['tipoCompra'])) $filtros['tipoCompra'] = $_POST['tipoCompra'];            
            if (!empty($_POST['fecha'])) {
                $partes = explode(" - ", $_POST['fecha']);
                $filtros['fechaDesde'] = DateTime::createFromFormat('d/m/Y', trim($partes[0]))->format('Y-m-d');
                $filtros['fechaHasta'] = DateTime::createFromFormat('d/m/Y', trim($partes[1]))->format('Y-m-d');
            }

            $resultados = $this->purchasesModel->getFiltered($filtros);
            $total = count($resultados);
            $totalPaginas = ceil($total / $limit);
            $paginados = array_slice($resultados, $offset, $limit);

            echo json_encode([
                'resultados' => $paginados,
                'totalPaginas' => $totalPaginas,
                'paginaActual' => $page,
                'totales' => $this->purchasesModel->getFilteredTotals($filtros),
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

            if (!empty($_POST['precioDesde'])) {
                $filtros['precioDesde'] = floatval($_POST['precioDesde']);
            }

            if (!empty($_POST['precioHasta'])) {
                $filtros['precioHasta'] = floatval($_POST['precioHasta']);
            }

            $resultados = $this->expensesModel->getFiltered($filtros);
            $total = count($resultados);
            $totalPaginas = ceil($total / $limit);
            $paginados = array_slice($resultados, $offset, $limit);

            echo json_encode([
                'resultados' => $paginados,
                'totalPaginas' => $totalPaginas,
                'paginaActual' => $page,
                'totales' => $this->expensesModel->getFilteredTotals($filtros),
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
                // createFromFormat devuelve false si la fecha no es válida; sin
                // esta guarda, llamar ->format() sobre false es un error fatal.
                $partes = explode(" - ", $_POST['fecha']);
                $d1 = DateTime::createFromFormat('d/m/Y', trim($partes[0] ?? ''));
                $d2 = isset($partes[1]) ? DateTime::createFromFormat('d/m/Y', trim($partes[1])) : $d1;
                if ($d1 instanceof DateTime) {
                    $fechaDesde = $d1->format('Y-m-d');
                    $fechaHasta = ($d2 instanceof DateTime) ? $d2->format('Y-m-d') : $fechaDesde;
                }
            }

            // Ventas y costo de lo vendido en UNA sola consulta.
            // Antes se recorría venta por venta y producto por producto, lanzando
            // una consulta por cada uno (N+1): con un año de historial eran miles
            // de consultas y el reporte se volvía inusable.
            $totales = $this->salesModel->getProfitabilityTotals($fechaDesde, $fechaHasta);
            $totalVentas = (float)$totales['totalVentas'];
            $totalCostos = (float)$totales['totalCostos'];

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
    public function inventory() {
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            
            $valorInventario = $this->inventoryModel->obtenerValorInventario();

            echo json_encode([
                'valorInventario' => $valorInventario
            ]);
            exit;
        }
        require_once __DIR__ . '/../Views/Reports/inventory.report.php';
    }

    /**
     * REPORTE CONTABLE (para la contadora).
     *
     * Consolida en un rango de fechas (día, mes, o rango libre) TODO lo que
     * necesita contabilidad: ventas (con desglose por método de pago), compras
     * y gastos, con sus totales y utilidad. La vista permite imprimir/PDF y
     * exportar a CSV para enviarlo.
     */
    public function accounting() {
        if ($this->esAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            try {
                $hoy   = date('Y-m-d');
                $desde = $this->fechaValida($_POST['desde'] ?? '', 'Y-m-d', date('Y-m-01'));
                $hasta = $this->fechaValida($_POST['hasta'] ?? '', 'Y-m-d', $hoy);
                if ($desde > $hasta) {
                    [$desde, $hasta] = [$hasta, $desde];
                }

                echo json_encode([
                    'success'        => true,
                    'desde'          => $desde,
                    'hasta'          => $hasta,
                    'resumen'        => $this->closingsModel->getResumen($desde, $hasta),
                    'ventas'         => $this->salesModel->getCompletedByRange($desde, $hasta),
                    'ventasAnuladas' => $this->salesModel->getCanceledByRange($desde, $hasta),
                    'compras'        => $this->purchasesModel->getFiltered(['fechaDesde' => $desde, 'fechaHasta' => $hasta]),
                    'gastos'         => $this->expensesModel->getFiltered(['fechaDesde' => $desde, 'fechaHasta' => $hasta]),
                    'topProductos'   => $this->closingsModel->getTopProductos($desde, $hasta, 15),
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
        require_once __DIR__ . '/../Views/Reports/accounting.report.php';
    }

    // ========================================================================
    // CIERRES: diario, mensual y anual
    // ------------------------------------------------------------------------
    // Los tres comparten la misma lógica; solo cambia el rango de fechas y la
    // granularidad de la serie temporal.
    // ========================================================================

    /** Cierre del día. Parámetro: fecha (YYYY-MM-DD). Por defecto, hoy. */
    public function closingDaily() {
        if ($this->esAjax()) {
            $fecha = $this->fechaValida($_POST['fecha'] ?? '', 'Y-m-d', date('Y-m-d'));
            $this->responderCierre($fecha, $fecha, 'dia', 'Cierre del ' . date('d/m/Y', strtotime($fecha)));
        }
        require_once __DIR__ . '/../Views/Reports/closingDaily.report.php';
    }

    /** Cierre mensual. Parámetro: mes (YYYY-MM). Por defecto, mes actual. */
    public function closingMonthly() {
        if ($this->esAjax()) {
            $mes = $this->fechaValida($_POST['mes'] ?? '', 'Y-m', date('Y-m'));
            $desde = $mes . '-01';
            $hasta = date('Y-m-t', strtotime($desde));
            $this->responderCierre($desde, $hasta, 'dia', 'Cierre de ' . date('m/Y', strtotime($desde)));
        }
        require_once __DIR__ . '/../Views/Reports/closingMonthly.report.php';
    }

    /** Cierre anual. Parámetro: anio (YYYY). Por defecto, año actual. */
    public function closingYearly() {
        if ($this->esAjax()) {
            $anio = $this->fechaValida($_POST['anio'] ?? '', 'Y', date('Y'));
            $desde = $anio . '-01-01';
            $hasta = $anio . '-12-31';
            // Serie por mes: 12 puntos en vez de 365
            $this->responderCierre($desde, $hasta, 'mes', 'Cierre del año ' . $anio);
        }
        require_once __DIR__ . '/../Views/Reports/closingYearly.report.php';
    }

    /**
     * Reporte de ventas por hora del día (horas pico).
     * Parámetros: fecha desde/hasta (por defecto, últimos 30 días).
     */
    public function salesByHour() {
        if ($this->esAjax()) {
            header('Content-Type: application/json; charset=utf-8');

            $hasta = $this->fechaValida($_POST['hasta'] ?? '', 'Y-m-d', date('Y-m-d'));
            $desde = $this->fechaValida($_POST['desde'] ?? '', 'Y-m-d', date('Y-m-d', strtotime('-29 days')));

            // Si el usuario invierte el rango, se corrige.
            if ($desde > $hasta) {
                [$desde, $hasta] = [$hasta, $desde];
            }

            try {
                echo json_encode([
                    'success' => true,
                    'desde'   => $desde,
                    'hasta'   => $hasta,
                    'horas'   => $this->closingsModel->getVentasPorHora($desde, $hasta),
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
        require_once __DIR__ . '/../Views/Reports/salesByHour.report.php';
    }

    /**
     * Construye la condición SQL para el filtro de método de pago.
     *
     * Punto clave del negocio: "Transferencia" NO es un método más, es la
     * SUMA de todo lo que no fue efectivo. Por eso al elegir "transferencia"
     * se incluyen también Bancolombia y Nequi (y el histórico 'transferencia').
     * Antes solo traía las que literalmente decían 'transferencia' (casi
     * ninguna), por eso "no salía nada".
     *
     * Todo pasa por lista blanca: nunca se concatena la entrada del usuario.
     */
    private function buildMetodoPagoClause($metodo) {
        $metodo = trim((string) $metodo);
        if ($metodo === '') {
            return '';
        }
        if ($metodo === 'transferencia') {
            return " AND metodoPago IN ('transferencia','bancolombia','nequi')";
        }
        if (in_array($metodo, ['efectivo', 'bancolombia', 'nequi'], true)) {
            return " AND metodoPago = '" . $metodo . "'";
        }
        return ''; // valor no reconocido → sin filtro
    }

    /** ¿La petición espera JSON? */
    private function esAjax() {
        return isset($_GET['ajax']) && $_GET['ajax'] == 1;
    }

    /**
     * Valida que un valor tenga exactamente el formato de fecha esperado.
     * Si no lo cumple, devuelve el valor por defecto (nunca datos del usuario
     * sin verificar dentro de una consulta).
     */
    private function fechaValida($valor, $formato, $porDefecto) {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return $porDefecto;
        }

        $d = DateTime::createFromFormat('!' . $formato, $valor);
        return ($d && $d->format($formato) === $valor) ? $valor : $porDefecto;
    }

    /** Arma y devuelve el JSON del cierre para el rango dado. */
    private function responderCierre($desde, $hasta, $granularidad, $titulo) {
        header('Content-Type: application/json; charset=utf-8');

        try {
            echo json_encode([
                'success'     => true,
                'titulo'      => $titulo,
                'desde'       => $desde,
                'hasta'       => $hasta,
                'resumen'     => $this->closingsModel->getResumen($desde, $hasta),
                'serie'       => $this->closingsModel->getSerie($desde, $hasta, $granularidad),
                'cajas'       => $this->closingsModel->getCajas($desde, $hasta),
                'topProductos' => $this->closingsModel->getTopProductos($desde, $hasta, 10),
                'stock'       => $this->closingsModel->getStockResumen(),
                'inventario'  => $this->closingsModel->getMovimientosInventario($desde, $hasta),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
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