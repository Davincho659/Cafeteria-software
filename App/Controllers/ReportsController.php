<?php

require_once __DIR__ . '/../Models/sales.php';

class ReportsController {
    private $salesModel;

    public function __construct() {
        $this->salesModel = new Sales();
    }

    /**
     * Renderizar página de facturas con filtros
     * GET params: idventa, preciodesde, preciohasta, fechadesde, fechahasta, metodopago, ajax
     */
    public function dayBills() {

        $isAjaxModal = isset($_GET['ajax']) && $_GET['ajax'] == '1';
        $isAjaxData = isset($_GET['ajaxData']) && $_GET['ajaxData'] == '1';

        $limit = 10; 
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $limit;
        
        $query = "";
        
        if (!empty($_POST['idVenta'])) {
            $query .= " AND idVenta = " . intval($_POST['idVenta']);
        }
        if (!empty($_POST['precioDesde'])) {
            $query .= " AND total >= " . floatval($_POST['precioDesde']);
        }
        if (!empty($_POST['precioHasta'])) {
            $query .= " AND total <= " . floatval($_POST['precioHasta']);
        }
        if (!empty($_POST['horaDesde'])) {
            $query .= " AND TIME(fechaVenta) >= '" . $_POST['horaDesde'] . "'";
        }
        if (!empty($_POST['horaHasta'])) {
            $query .= " AND TIME(fechaVenta) <= '" . $_POST['horaHasta'] . "'";
        }
        if (!empty($_POST['metodoPago'])) {
            $query .= " AND metodoPago = '" . $_POST['metodoPago'] . "'";
        }
        
        $resultados = $this->salesModel->getWithPagination($query, $offset, $limit);
        
        $total = $this->salesModel->countWithFilters($query);
        $totalPaginas = ceil($total / $limit);
        
        $paginacion = [
            'paginaActual' => $page,
            'totalPaginas' => $totalPaginas,
            'totalRegistros' => $total,
            'registrosPorPagina' => $limit
        ];
        
        if ($isAjaxData) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'resultados' => $resultados,
                'paginacion' => $paginacion
            ]);
            exit;
        }
        
        
        require_once __DIR__ . '/../Views/dayBills.view.php';
    }

}