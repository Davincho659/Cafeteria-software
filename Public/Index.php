<?php
// =====================================================
// INDEX.PHP - Sistema POS Cafetería
// =====================================================

// La sesión se inicia dentro de Init.php con la cookie endurecida
// (httponly/samesite/secure). No llamar session_start() aquí para no fijar la
// cookie con parámetros por defecto antes de tiempo.

$initCandidates = [
    __DIR__ . '/App/Core/Init.php',
    __DIR__ . '/../App/Core/Init.php',
    dirname(__DIR__) . '/App/Core/Init.php',
];

$initFile = null;
foreach ($initCandidates as $candidate) {
    if (is_file($candidate)) {
        $initFile = $candidate;
        break;
    }
}

if ($initFile === null) {
    http_response_code(500);
    echo 'No se pudo localizar App/Core/Init.php';
    exit;
}

require $initFile;
date_default_timezone_set('America/Bogota');

$appPath = defined('APP_PATH') ? APP_PATH : dirname(dirname($initFile));



// Sanitizar parámetros de entrada
$pg = isset($_GET['pg']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['pg']) : 'home';
$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['action']) : null;

// =====================================================
// GUARDA DE SEGURIDAD (autenticación + rol)
// Debe ir ANTES del despachador: ninguna acción se ejecuta sin pasar por aquí.
// =====================================================
require_once $appPath . '/Core/Auth.php';
// Se considera AJAX (respuesta JSON) cuando: viene ajax=1, trae el header XHR,
// o es un POST con acción (las acciones que modifican datos son POST). Una
// navegación normal con ?action=... (ej. reports) NO se trata como AJAX para
// que el rechazo redirija en vez de devolver JSON.
$isAjax = (isset($_GET['ajax']) && $_GET['ajax'] == 1)
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || ($action !== null && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
Auth::guard($pg, $isAjax, $action);

// =====================================================
// PROTECCIÓN CSRF
// Todo POST/PUT/PATCH/DELETE debe traer un token válido (cabecera X-CSRF-Token
// o campo _csrf). El login queda exento: es previo a la sesión de trabajo y su
// página aún no tiene token. El resto de acciones que modifican datos, sí.
// =====================================================
if (!Auth::isPublic($pg)) {
    Csrf::enforce($isAjax);
}

// =====================================================
// DESPACHADOR DE RUTAS (switch-case)
// =====================================================
switch ($pg) {
    // ====================================================
    // RUTAS PÚBLICAS (login, logout)
    // ====================================================
    case 'login':
        require_once $appPath . '/Controllers/LoginController.php';
        $controller = new LoginController();
        
        switch ($action) {
            case 'authenticate':
                $controller->authenticate();
                break;
            // Endpoints JSON de sesión. Antes caían en default y devolvían el
            // HTML del login, así que el front nunca podía consultarlos.
            case 'csrfToken':
                $controller->csrfToken();
                break;
            case 'checkAuth':
                $controller->checkAuth();
                break;
            case 'getCurrentUser':
                $controller->getCurrentUser();
                break;
            default:
                $controller->index();
                break;
        }
        exit;

    case 'logout':
        require_once $appPath . '/Controllers/LoginController.php';
        $controller = new LoginController();
        $controller->logout();
        exit;

    // ====================================================
    // REPORTES (layout especial + soporte AJAX)
    // ====================================================
    case 'reports':
        require_once $appPath . '/Controllers/ReportsController.php';
        $controller = new ReportsController();

        // Si es petición AJAX → llamar directamente al método (sin layout)
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            switch ($action) {
                case 'sales':
                    $controller->sales();
                    break;
                
                case 'Daily':
                    $controller->Daily();
                    break;
                default:
                    // is_callable (y no method_exists) para no intentar invocar
                    // métodos privados del controlador desde la URL.
                    if ($action && is_callable([$controller, $action])) {
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
        require_once $appPath . '/Views/Layouts/reports-layout.view.php';
        exit;

    // ====================================================
    // COMPRAS
    // ====================================================
    case 'purchases':
        require_once $appPath . '/Controllers/purchasesController.php';
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
        require_once $appPath . '/Controllers/inventoryController.php';
        $controller = new InventoryController();
        
        if ($action === null) {
            $controller->index();
        } else {
            $validActions = [
                'getStock',
                'getProductStock',
                'getMovements',
                'adjustStock',
                'registerConsumption',
                'getInventoryValue',
                'getLowStock',
                'getAlertas'
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
        require_once $appPath . '/Controllers/suppliersController.php';
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
        require_once $appPath . '/Controllers/SalesController.php';
        $controller = new SalesController();
        
        if ($action === null) {
            require_once $appPath . '/Views/sales.view.php';
        } else {
            $validActions = [
                'getCategories',
                'getProducts',
                'findByBarcode',
                'GetTables',
                'CreateSale',
                'GetSale',
                'seeTodayBills',
                'dailyBills',
                'dailyBillsData',
                'checkStock',
                'LoadActiveSales',
                'CompleteSale',
                'CancelSale',
                'cancelSaleByInvoice',
                // Mesas / POS
                'transferProductsToTable',
                'updateProductQuantity',
                'removeProductFromSale',
                'completeTableSale',
                'cancelTableSale',
                'addProductToSale',
                
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
        require_once $appPath . '/Controllers/ProductController.php';
        $controller = new ProductController();
        
        if ($action === null) {
            require_once $appPath . '/Views/product.view.php';
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
                'deleteCategory',
                'getUnits'
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
    // DASHBOARD (admin)
    // ====================================================
    case 'dashboard':
        require_once $appPath . '/Controllers/DashboardController.php';
        $controller = new DashboardController();

        if ($action === null) {
            $controller->index();
        } elseif (in_array($action, ['data'], true) && method_exists($controller, $action)) {
            $controller->$action();
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Acción no encontrada']);
        }
        exit;

    // ====================================================
    // CONFIGURACIÓN DEL NEGOCIO
    // ====================================================
    case 'settings':
        require_once $appPath . '/Controllers/SettingsController.php';
        $controller = new SettingsController();

        if ($action === null) {
            $controller->index();
        } elseif (in_array($action, ['save'], true) && method_exists($controller, $action)) {
            $controller->$action();
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Acción no encontrada']);
        }
        exit;

    // ====================================================
    // GESTIÓN DE USUARIOS (solo admin)
    // ====================================================
    case 'users':
        require_once $appPath . '/Controllers/UsersController.php';
        $controller = new UsersController();

        if ($action === null) {
            $controller->index();
        } else {
            $validActions = [
                'getUsers',
                'createUser',
                'updateUser',
                'deleteUser'
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
    // GESTIÓN DE MESAS
    // ====================================================
    case 'tables':
        require_once $appPath . '/Controllers/TablesController.php';
        $controller = new TablesController();
        
        if ($action === null) {
            $controller->index();
        } else {
            $validActions = [
                'getTables',
                'getStatistics',
                'createTable',
                'updateTable',
                'deleteTable',
                'savePositions'
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
            $controllerFile = $appPath . '/Controllers/' . $controllerClass . '.php';
            if (!file_exists($controllerFile)) {
                $controllerFiles = glob($appPath . '/Controllers/*.php') ?: [];
                foreach ($controllerFiles as $candidate) {
                    if (strtolower(pathinfo($candidate, PATHINFO_FILENAME)) === strtolower($controllerClass)) {
                        $controllerFile = $candidate;
                        break;
                    }
                }
            }
            
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
        $viewFile = $appPath . '/Views/' . strtolower($pg) . '.view.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
            exit;
        }
        
        // Vista no encontrada, cargar home
        $homeView = $appPath . '/Views/home.view.php';
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

