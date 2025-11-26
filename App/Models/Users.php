<?php

require_once __DIR__ . '/../Core/Conexion.php';

class Users {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    
    public function getByUsername($username) {
        $query = "SELECT * FROM usuarios WHERE nombre = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

}