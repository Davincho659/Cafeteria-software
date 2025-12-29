<?php

require_once __DIR__ . '/../Core/Conexion.php';

class Products {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function getAll(){
    $query="SELECT p.idCategoria, p.idProducto, p.nombre, p.precioCompra, p.precioVenta, p.tipo, p.idUnidadBase, p.imagen, p.manejaStock, 
        c.nombre AS categoria, c.imagen AS categoria_imagen,
        u.nombre AS unidadNombre, u.abreviatura AS unidadAbreviatura
        FROM productos p 
        INNER JOIN categorias c ON p.idCategoria = c.idCategoria
        LEFT JOIN unidades_medida u ON p.idUnidadBase = u.idUnidad";
        $strm= $this->db->query($query);
        return $strm->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCategory($id) {
    $query = 'SELECT p.idCategoria, p.idProducto, p.nombre, p.precioCompra, p.precioVenta, p.tipo, p.idUnidadBase, p.manejaStock, p.imagen, 
            c.nombre AS categoria, c.imagen AS categoria_imagen,
            u.nombre AS unidadNombre, u.abreviatura AS unidadAbreviatura
            FROM productos AS p 
            INNER JOIN categorias AS c ON p.idCategoria = c.idCategoria
            LEFT JOIN unidades_medida u ON p.idUnidadBase = u.idUnidad
            WHERE p.idCategoria = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($idCategoria,$nombre,$tipo,$precioVenta = null,$precioCompra = null , $imagen, $idUnidadBase = 1, $manejaStock = 0) {
        $query = "INSERT INTO productos (idCategoria, nombre, tipo, precioVenta, precioCompra, imagen, idUnidadBase, manejaStock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idCategoria, $nombre, $tipo, $precioVenta, $precioCompra, $imagen, $idUnidadBase, $manejaStock]);
        return $this->db->lastInsertId();
    }

    public function getById($id) {
        $query = 'SELECT p.*, c.nombre AS categoria, c.imagen AS categoria_imagen,
                  u.nombre AS unidadNombre, u.abreviatura AS unidadAbreviatura, u.tipo AS unidadTipo
                  FROM productos p
                  INNER JOIN categorias c ON p.idCategoria = c.idCategoria
                  LEFT JOIN unidades_medida u ON p.idUnidadBase = u.idUnidad
                  WHERE p.idProducto = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $query = "UPDATE productos SET idCategoria = ?, nombre = ?, tipo = ?, precioVenta = ?, precioCompra = ?, imagen = ?, idUnidadBase = ?, manejaStock = ? WHERE idProducto = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            $data['idCategoria'],
            $data['nombre'],
            $data['tipo'],
            $data['precioVenta'],
            $data['precioCompra'],
            $data['imagen'],
            $data['idUnidadBase'] ?? 1,
            $data['manejaStock'] ?? 0,
            $id
        ]);
    }

    public function delete($id) {
        $query = "DELETE FROM productos WHERE idProducto = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    /**
     * Obtener costo promedio ponderado del producto desde historial de compras
     */
    public function getCostoPromedioProducto($idProducto) {
        $sql = "SELECT 
                    COALESCE(SUM(h.cantidad * h.precioUnitario) / NULLIF(SUM(h.cantidad),0), 0) AS costoPromedio
                FROM historial_precio_compra h
                WHERE h.idProducto = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idProducto]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['costoPromedio'] : 0.0;
    }

    /**
     * Obtener último precio de compra
     */
    public function getUltimoPrecioCompra($idProducto) {
        $sql = "SELECT precioUnitario 
                FROM historial_precio_compra 
                WHERE idProducto = ? 
                ORDER BY fechaRegistro DESC, idHistorial DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idProducto]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['precioUnitario'] : 0.0;
    }

    /**
     * Obtener resumen de costos (promedio y último)
     */
    public function getResumenCosto($idProducto) {
        return [
            'costoPromedio' => $this->getCostoPromedioProducto($idProducto),
            'ultimoPrecio' => $this->getUltimoPrecioCompra($idProducto),
        ];
    }

    
}