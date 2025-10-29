<?php

require_once __DIR__ . '/../Core/Conexion.php';

class Categories {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll() {
        $query  = "SELECT * FROM categorias";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($idCategoria,$nombre) {
        // if idCategoria is auto-increment, caller can pass null
        $query = "INSERT INTO categorias (nombre) VALUES (?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$nombre]);
        return $this->db->lastInsertId();
    }
    
}
