<?php
// =====================================================
// INDEX.PHP - Sistema POS Cafetería
// =====================================================

session_start();

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../App/Core/Init.php';
date_default_timezone_set('America/Bogota');

// Sanitizar parámetros de entrada
$pg = isset($_GET['pg']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['pg']) : 'home';
$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['action']) : null;

// =====================================================
// DESPACHADOR DE RUTAS (switch-case)
// =====================================================
switch ($pg) {
    // ====================================================
    // RUTAS PÚBLICAS (login, logout)
    // ====================================================
    case 'login':
        require_once __DIR__ . '/../App/Controllers/LoginController.php';
        $controller = new LoginController();
        
        switch ($action) {
            case 'authenticate':
                $controller->authenticate();
                break;
            default:
                $controller->index();
                break;
        }
        exit;

    case 'logout':
        require_once __DIR__ . '/../App/Controllers/LoginController.php';
        $controller = new LoginController();
        $controller->logout();
        exit;

    // ====================================================
    // REPORTES (layout especial + soporte AJAX)
    // ====================================================
    case 'reports':
        require_once __DIR__ . '/../App/Controllers/ReportsController.php';
        $controller = new ReportsController();

        // Si es petición AJAX → llamar directamente al método (sin layout)
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            switch ($action) {
                case 'sales':
                    $controller->sales();
                    break;
                case 'daily':
                    $controller->Daily();
                    break;
                default:
                    if ($action && method_exists($controller, $action)) {
                        $controller->$action();
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'Acción no válida']);
                    }
                    break;
            }
            exit; // Salir sin layout
        }
        
        // Si NO es AJAX → cargar con layout completo
        require_once __DIR__ . '/../App/Views/Layouts/Reports-layout.view.php';
        exit;

    // ====================================================
    // COMPRAS
    // ====================================================
    case 'purchases':
        require_once __DIR__ . '/../App/Controllers/PurchasesController.php';
        $controller = new PurchasesController();
        
        if ($action === null) {
            $controller->index();
        } else {
            $validActions = [
                'getSuppliers',
                'getProducts',
                'createDetailedPurchase',
                'createQuickPurchase',
                'getPurchases',
                'getPurchase',
                'getTodayPurchases'
            ];
            
            if (in_array($action, $validActions) && method_exists($controller, $action)) {
                $controller->$action();
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Acción no encontrada']);
            }
        }
        exit;

    // ====================================================
    // INVENTARIO
    // ====================================================
    case 'inventory':
        require_once __DIR__ . '/../App/Controllers/InventoryController.php';
        $controller = new InventoryController();
        
        if ($action === null) {
            $controller->index();
        } else {
            $validActions = [
                'getStock',
                'getProductStock',
                'getMovements',
                'adjustStock',
                'getInventoryValue',
                'getLowStock'
            ];
            
            if (in_array($action, $validActions) && method_exists($controller, $action)) {
                $controller->$action();
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Acción no encontrada']);
            }
        }
        exit;

    // ====================================================
    // PROVEEDORES
    // ====================================================
    case 'suppliers':
        require_once __DIR__ . '/../App/Controllers/SuppliersController.php';
        $controller = new SuppliersController();
        
        if ($action === null) {
            $controller->index();
        } else {
            $validActions = [
                'createSupplier',
                'getSuppliers',
                'getSupplier',
                'updateSupplier',
                'deleteSupplier',
                'searchSuppliers'
            ];
            
            if (in_array($action, $validActions) && method_exists($controller, $action)) {
                $controller->$action();
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Acción no encontrada']);
            }
        }
        exit;

    // ====================================================
    // VENTAS
    // ====================================================
    case 'sales':
        require_once __DIR__ . '/../App/Controllers/SalesController.php';
        $controller = new SalesController();
        
        if ($action === null) {
            require_once __DIR__ . '/../App/Views/sales.view.php';
        } else {
            $validActions = [
                'getCategories',
                'getProducts',
                'GetTables',
                'CreateSale',
                'createQuickSale',
                'GetSale',
                'seeTodayBills',
                'checkStock',
                // Mesas / POS
                'transferProductsToTable',
                'updateProductQuantity',
                'removeProductFromSale',
                'completeTableSale',
                'cancelTableSale',
                'addProductToTableSale'
            ];
            
            if (in_array($action, $validActions) && method_exists($controller, $action)) {
                $controller->$action();
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Acción no encontrada']);
            }
        }
        exit;

    // ====================================================
    // PRODUCTOS
    // ====================================================
    case 'product':
        require_once __DIR__ . '/../App/Controllers/ProductController.php';
        $controller = new ProductController();
        
        if ($action === null) {
            require_once __DIR__ . '/../App/Views/product.view.php';
        } else {
            $validActions = [
                'createProduct',
                'getProduct',
                'getCategory',
                'getProducts',
                'updateProduct',
                'deleteProduct',
                'getCategories',
                'createCategory',
                'updateCategory',
                'deleteCategory'
            ];
            
            if (in_array($action, $validActions) && method_exists($controller, $action)) {
                $controller->$action();
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Acción no encontrada']);
            }
        }
        exit;

    // ====================================================
    // RUTAS GENERALES (Home, Admin, etc.)
    // ====================================================
    default:
        // Si hay acción, buscar controlador
        if ($action !== null) {
            $controllerClass = ucfirst(strtolower($pg)) . 'Controller';
            $controllerFile = __DIR__ . '/../App/Controllers/' . $controllerClass . '.php';
            
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                
                if (class_exists($controllerClass)) {
                    $controllerInstance = new $controllerClass();
                    
                    if (method_exists($controllerInstance, $action)) {
                        $controllerInstance->$action();
                        exit;
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => "Método $action no encontrado"]);
                        exit;
                    }
                }
            }
            
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Controlador no encontrado']);
            exit;
        }
        
        // Sin acción, cargar vista directamente
        $viewFile = __DIR__ . '/../App/Views/' . strtolower($pg) . '.view.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
            exit;
        }
        
        // Vista no encontrada, cargar home
        $homeView = __DIR__ . '/../App/Views/home.view.php';
        if (file_exists($homeView)) {
            require_once $homeView;
            exit;
        }
        
        // Nada encontrado
        http_response_code(404);
        echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 404</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <h1 class="display-1">404</h1>
                <h3>Página no encontrada</h3>
                <p class="text-muted">La página que buscas no existe.</p>
                <a href="?pg=home" class="btn btn-primary">Ir al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>';
        break;
}

