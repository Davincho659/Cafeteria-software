<?php
require_once __DIR__ . '/../Models/Suppliers.php';

class SuppliersController {
    private $suppliersModel;

    public function __construct() {
        $this->suppliersModel = new Suppliers();
    }

    /**
     * Mostrar vista de proveedores
     */
    public function index() {
        require_once __DIR__ . '/../Views/suppliers.view.php';
    }

    /**
     * Crear proveedor
     */
    public function createSupplier() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['nombre'])) {
                throw new Exception('El nombre del proveedor es requerido');
            }

            $telefono = $data['telefono'] ?? null;

            $idProveedor = $this->suppliersModel->create($data['nombre'], $telefono);

            echo json_encode([
                'success' => true,
                'message' => 'Proveedor creado exitosamente',
                'idProveedor' => $idProveedor
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener todos los proveedores
     */
    public function getSuppliers() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $suppliers = $this->suppliersModel->getAll();
            echo json_encode([
                'success' => true,
                'data' => $suppliers
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener proveedor por ID
     */
    public function getSupplier() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }

            $supplier = $this->suppliersModel->getById($id);
            if (!$supplier) {
                throw new Exception('Proveedor no encontrado');
            }

            echo json_encode([
                'success' => true,
                'data' => $supplier
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar proveedor
     */
    public function updateSupplier() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idProveedor'])) {
                throw new Exception('El ID del proveedor es requerido');
            }
            if (empty($data['nombre'])) {
                throw new Exception('El nombre del proveedor es requerido');
            }

            $telefono = $data['telefono'] ?? null;

            $success = $this->suppliersModel->update(
                $data['idProveedor'],
                $data['nombre'],
                $telefono
            );

            if (!$success) {
                throw new Exception('Error al actualizar el proveedor');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar proveedor
     */
    public function deleteSupplier() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }

            $success = $this->suppliersModel->delete($id);
            if (!$success) {
                throw new Exception('Error al eliminar el proveedor');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Proveedor eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Buscar proveedores
     */
    public function searchSuppliers() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $termino = $_GET['q'] ?? '';
            if (empty($termino)) {
                $suppliers = $this->suppliersModel->getAll();
            } else {
                $suppliers = $this->suppliersModel->search($termino);
            }

            echo json_encode([
                'success' => true,
                'data' => $suppliers
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}