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

    public function getById($id) {
        $query = "SELECT * FROM categorias WHERE idCategoria = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($nombre) {
        $query = "INSERT INTO categorias (nombre) VALUES (?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$nombre]);
        return $this->db->lastInsertId();
    }

    public function delete($id) {
        $query = "DELETE FROM categorias WHERE idCategoria = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    public function insertImage($id, $imagen) {
        $query = "UPDATE categorias SET imagen = ? WHERE idCategoria = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$imagen, $id]);
    }

    public function update($id, $nombre, $imagen = null) {
        if ($imagen === null) {
            $query = "UPDATE categorias SET nombre = ? WHERE idCategoria = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$nombre, $id]);
        } else {
            $query = "UPDATE categorias SET nombre = ?, imagen = ? WHERE idCategoria = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$nombre, $imagen, $id]);
        }
    }
    
}
