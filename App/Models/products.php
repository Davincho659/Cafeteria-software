<?php

require_once __DIR__ . '/../Core/Conexion.php';

class Products {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public static function getAllProducts() {
        $stmt = $this->db->query('SELECT * FROM productos');
        return $stmt->fetchAll();
    }
    public static function getProductById($id) {
        $query = "SELECT * FROM productos WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    
}