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
        if (empty($_POST['idVenta']) AND empty($_POST['precioDesde']) AND empty($_POST['precioHasta']) AND empty($_POST['fechaDesde']) AND empty($_POST['fechaHasta']) AND empty($_POST['metodoPago'])) {
            $resultados = $this->salesModel->getAll();
        } else {
            $query = "";
            if (!empty($_POST['idVenta'])) {
                $query .= " WHERE idVenta = " . intval($_POST['idVenta']);
            }
            if (!empty($_POST['precioDesde'])) {
                $query .= ($query == "" ? " WHERE " : " AND ") . " total >= " . floatval($_POST['precioDesde']);
            }
            if (!empty($_POST['precioHasta'])) {
                $query.= ($query == '' ? "WHERE": "AND" ) . "total <= " . floatval($_POST['precioHasta']);
            }
            if (!empty($_POST['fechaDesde']) AND !empty($_POST['fechaHasta'])) {
                $query .= ($query == "" ? " WHERE " : " AND ") . " fechaVenta BETWEEN '" . $_POST['fechaDesde'] . " 00:00:00' AND '" . $_POST['fechaHasta'] . " 23:59:59'";
            }
            if (!empty($_POST['fechaHasta'])) {
                $query .= ($query == "" ? " WHERE " : " AND ") . " fechaVenta <= '" . $_POST['fechaHasta'] . " 23:59:59'";
            }
            if (!empty($_POST['metodoPago'])) {
                $query .= ($query == "" ? " WHERE " : " AND ") . " metodoPago = '" . $_POST['metodoPago'] . "'";
            }
            $resultados = $this->salesModel->filter($query);
        }
        
        
        require_once __DIR__ . '/../Views/dayBills.view.php';
    }

}