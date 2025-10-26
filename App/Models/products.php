<?php

require_once __DIR__ . '/../Core/Conexion.php';

class Products {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function getAll(){
        $query="SELECT p.idCategoria , p.idProducto, p.nombre , p.precioCompra, p.precioVenta, p.tipo, c.nombre AS categoria
                FROM productos p INNER JOIN categorias c ON p.idCategoria = c.idCategoria";
        $strm= $this->db->query($query);
        return $strm->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCategory($id) {
        $query = 'SELECT p.idCategoria , p.idProducto, p.nombre , p.precioCompra, p.precioVenta, p.tipo, c.nombre AS categoria 
                    FROM productos AS p INNER JOIN categorias AS c ON p.idCategoria = c.idCategoria
                    WHERE p.idCategoria = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($idCategoria,$nombre,$tipo,$precioVenta = null,$precioCompra = null , $imagen) {
        $query= "INSERT INTO productos (idProducto, idCategoria, nombre, tipo, precioVenta, precioCompra, imagen) VALUES ? ? ? ? ? ? ";
        $stmt = $this->db->prepare($query);
        $stmt->execute(null,[$idCategoria],[$nombre],[$tipo],[$precioVenta],[$precioCompra],[$imagen]);
        return $stmt->fetch();
    }
}