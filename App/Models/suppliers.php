<?php
require_once __DIR__ . '/../Core/Conexion.php';

class Suppliers {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Crear proveedor
     */
    public function create($nombre, $telefono = null) {
        $sql = "INSERT INTO proveedores (nombre, telefono) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nombre, $telefono]);
        return $this->db->lastInsertId();
    }

    /**
     * Obtener todos los proveedores
     */
    public function getAll() {
        $sql = "SELECT * FROM proveedores ORDER BY nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener proveedor por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM proveedores WHERE idProveedor = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar proveedor
     */
    public function update($id, $nombre, $telefono = null) {
        $sql = "UPDATE proveedores SET nombre = ?, telefono = ? WHERE idProveedor = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $telefono, $id]);
    }

    /**
     * Eliminar proveedor
     */
    public function delete($id) {
        $sql = "DELETE FROM proveedores WHERE idProveedor = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Buscar proveedores
     */
    public function search($termino) {
        $sql = "SELECT * FROM proveedores 
                WHERE nombre LIKE ? OR telefono LIKE ?
                ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $like = "%$termino%";
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}