<?php
require_once __DIR__ . '/../Core/Conexion.php';

class Sales {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function createSale($idVenta,$idMesa= null,$estado,$metodoPago,$total,$idUsuario) {
        $sql= "INSERT INTO ventas (idVenta,idMesa,estado,metodoPago,total,idUsuario) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idVenta,$idMesa,$estado,$metodoPago,$total,$idUsuario]);
        return $this->db->lastInsertId();
    }

    public function createSalesDetail($idVenta,$idDetalleVenta,$idProducto,$cantidad,$precioUnitario) {
        $sql= "INSERT INTO detalle_venta (idVenta,idDetalleVenta,idProducto,cantidad,precioUnitario) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idVenta,$idDetalleVenta,$idProducto,$cantidad,$precioUnitario]);
        return $this->db->lastInsertId();
    }

    public function getSaleById($idVenta) {
        $sql = "SELECT v.*, t.numero AS mesa_numero, u.nombre AS usuario_nombre FROM ventas v LEFT JOIN mesas t ON v.idMesa = t.idMesa LEFT JOIN usuarios u ON v.idUsuario = u.idUsuario WHERE v.idVenta = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idVenta]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSaleDetails($idVenta) {
        $sql = "SELECT dv.idDetalleVenta, dv.idProducto, p.nombre AS producto_nombre, dv.cantidad, dv.precioUnitario, dv.subTotal FROM detalle_venta dv LEFT JOIN productos p ON dv.idProducto = p.idProducto WHERE dv.idVenta = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idVenta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function filter($filter) {
        $sql = "SELECT * FROM ventas";
        $result = $sql . $filter;
        $stmt = $this->db->query($result);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $sql = "SELECT * FROM ventas";
        $strm= $this->db->query($sql);
        return $strm->fetchAll(PDO::FETCH_ASSOC);
    }

}