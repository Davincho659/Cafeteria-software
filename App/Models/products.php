<?php

require_once __DIR__ . '/../Core/Conexion.php';

class Products {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function getAll(){
    $query="SELECT p.idCategoria, p.idProducto, p.nombre, p.precioCompra, p.precioVenta, p.tipo, p.imagen, p.manejaStock, c.nombre AS categoria, c.imagen AS categoria_imagen
        FROM productos p INNER JOIN categorias c ON p.idCategoria = c.idCategoria";
        $strm= $this->db->query($query);
        return $strm->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCategory($id) {
    $query = 'SELECT p.idCategoria, p.idProducto, p.nombre, p.precioCompra, p.precioVenta, p.tipo, p.manejaStock, p.imagen, c.nombre AS categoria, c.imagen AS categoria_imagen
            FROM productos AS p INNER JOIN categorias AS c ON p.idCategoria = c.idCategoria
            WHERE p.idCategoria = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($idCategoria,$nombre,$tipo,$precioVenta = null,$precioCompra = null , $imagen) {
        $query = "INSERT INTO productos (idCategoria, nombre, tipo, precioVenta, precioCompra, imagen) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idCategoria, $nombre, $tipo, $precioVenta, $precioCompra, $imagen]);
        return $this->db->lastInsertId();
    }

    public function getById($id) {
        $query = 'SELECT p.*, c.nombre AS categoria, c.imagen AS categoria_imagen
                  FROM productos p
                  INNER JOIN categorias c ON p.idCategoria = c.idCategoria
                  WHERE p.idProducto = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $query = "UPDATE productos SET idCategoria = ?, nombre = ?, tipo = ?, precioVenta = ?, precioCompra = ?, imagen = ? WHERE idProducto = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            $data['idCategoria'],
            $data['nombre'],
            $data['tipo'],
            $data['precioVenta'],
            $data['precioCompra'],
            $data['imagen'],
            $id
        ]);
    }

    public function delete($id) {
        $query = "DELETE FROM productos WHERE idProducto = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    
}