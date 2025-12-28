<?php
require_once __DIR__ . '/../Core/Conexion.php';

class Users {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Obtener usuario por nombre y PIN
     */
    public function getByNameAndPin($nombre, $pin) {
        $sql = "SELECT * FROM usuarios WHERE nombre = ? AND pin = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nombre, $pin]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener usuario por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM usuarios WHERE idUsuario = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todos los usuarios
     */
    public function getAll() {
        $sql = "SELECT idUsuario, nombre, rol FROM usuarios ORDER BY nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crear usuario
     */
    public function create($nombre, $pin, $rol = 'empleado') {
        $sql = "INSERT INTO usuarios (nombre, pin, rol) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nombre, $pin, $rol]);
        return $this->db->lastInsertId();
    }

    /**
     * Actualizar usuario
     */
    public function update($id, $nombre, $pin = null, $rol = null) {
        if ($pin !== null && $rol !== null) {
            $sql = "UPDATE usuarios SET nombre = ?, pin = ?, rol = ? WHERE idUsuario = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $pin, $rol, $id]);
        } elseif ($pin !== null) {
            $sql = "UPDATE usuarios SET nombre = ?, pin = ? WHERE idUsuario = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $pin, $id]);
        } elseif ($rol !== null) {
            $sql = "UPDATE usuarios SET nombre = ?, rol = ? WHERE idUsuario = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $rol, $id]);
        } else {
            $sql = "UPDATE usuarios SET nombre = ? WHERE idUsuario = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $id]);
        }
    }

    /**
     * Eliminar usuario
     */
    public function delete($id) {
        $sql = "DELETE FROM usuarios WHERE idUsuario = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Verificar si existe un usuario con ese nombre
     */
    public function existsByName($nombre) {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE nombre = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nombre]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }

    /**
     * Cambiar PIN de usuario
     */
    public function changePin($id, $newPin) {
        $sql = "UPDATE usuarios SET pin = ? WHERE idUsuario = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newPin, $id]);
    }
}