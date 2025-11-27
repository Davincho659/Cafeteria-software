<?php

require_once __DIR__ . '/../Core/Conexion.php';

Class Tables {
    private $db;

    public function __construct() {
        $this-> db = Database::getConnection();
    }

    public function getAll() {
        $query = "SELECT * FROM mesas";
        $strm= $this->db->query($query);
        return $strm->fetchAll(PDO::FETCH_ASSOC);
    }
}