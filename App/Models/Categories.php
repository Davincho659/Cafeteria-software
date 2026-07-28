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

    /**
     * ¿Ya existe una categoría con ese nombre?
     *
     * La comparación ignora mayúsculas y espacios sobrantes: "Bebidas",
     * "bebidas" y " Bebidas " son la misma categoría para el negocio.
     */
    public function existsByName($nombre, $exceptoId = null) {
        $query = "SELECT idCategoria FROM categorias WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?))";
        $params = [$nombre];
        if ($exceptoId !== null) {
            $query .= " AND idCategoria <> ?";
            $params[] = $exceptoId;
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function create($nombre) {
        // Sin esta comprobación se podían crear dos categorías "Bebidas" y los
        // productos quedaban repartidos entre ambas, con reportes que parecían
        // incompletos sin motivo aparente.
        if ($this->existsByName($nombre)) {
            throw new Exception('Ya existe una categoría con ese nombre');
        }

        $query = "INSERT INTO categorias (nombre) VALUES (?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([trim($nombre)]);
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
        // Renombrar tampoco puede chocar con otra categoría existente.
        if ($this->existsByName($nombre, $id)) {
            throw new Exception('Ya existe otra categoría con ese nombre');
        }
        $nombre = trim($nombre);

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
