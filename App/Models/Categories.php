<?php

require_once __DIR__ . '/../Core/Conexion.php';

class Categories {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAllCategories() {
        $query  = "SELECT * FROM categorias";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }
}
