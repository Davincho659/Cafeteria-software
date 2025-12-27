<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema POS</title>
    <link rel="stylesheet" href="assets/css/bills.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f1f5f9;
            overflow: hidden;
        }

        .reports-container {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        /* Sidebar fijo */
        .reports-sidebar {
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: #e2e8f0;
            overflow-y: auto;
            flex-shrink: 0;
            position: relative;
            z-index: 100;
        }

        /* Contenido principal con scroll */
        .reports-content {
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            background: #f1f5f9;
        }

        /* Header del sidebar */
        .sidebar-header {
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(10px);
        }

        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .sidebar-header p {
            font-size: 12px;
            color: #94a3b8;
            margin: 0;
        }

        /* Navegación compacta */
        .sidebar-nav {
            padding: 12px 0;
        }

        .nav-section {
            margin-bottom: 4px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .section-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .section-links li {
            margin: 0;
        }

        .section-links a {
            display: block;
            padding: 8px 16px 8px 40px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.15s ease;
            border-left: 3px solid transparent;
        }

        .section-links a:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            border-left-color: #3b82f6;
        }

        .section-links a.active {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border-left-color: #3b82f6;
            font-weight: 600;
        }

        /* Scrollbar personalizado sidebar */
        .reports-sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .reports-sidebar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
        }

        .reports-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        /* Scrollbar contenido */
        .reports-content::-webkit-scrollbar {
            width: 8px;
        }

        .reports-content::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .reports-content::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .reports-content::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .reports-sidebar {
                position: fixed;
                left: -280px;
                transition: left 0.3s;
                z-index: 1000;
            }

            .reports-sidebar.mobile-open {
                left: 0;
            }

            .reports-content {
                width: 100%;
            }

            .mobile-menu-btn {
                display: block !important;
                position: fixed;
                top: 16px;
                left: 16px;
                z-index: 999;
                background: #1e293b;
                color: white;
                border: none;
                padding: 10px 14px;
                border-radius: 6px;
                cursor: pointer;
            }
        }

        .mobile-menu-btn {
            display: none;
        }
    </style>
</head>
<body>

<div class="reports-container">
    
    <!-- Sidebar -->
    <aside class="reports-sidebar" id="reportsSidebar">
        <div class="sidebar-header">
            <h2>📊 Reportes</h2>
            <p>Sistema POS</p>
        </div>

        <nav class="sidebar-nav">
            <!-- Ventas -->
            <div class="nav-section">
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3h18v18H3zM3 9h18M9 21V9"/>
                    </svg>
                    Ventas
                </h3>
                <ul class="section-links">
                    <li><a href="?pg=reports&action=sales" class="<?= ($_GET['action'] ?? '') === 'sales' ? 'active' : '' ?>">Ventas Diarias</a></li>
                    <li><a href="?pg=reports&action=ventas-mensuales">Ventas Mensuales</a></li>
                    <li><a href="?pg=reports&action=ventas-anuales">Ventas Anuales</a></li>
                    <li><a href="?pg=reports&action=ventas-por-producto">Por Producto</a></li>
                    <li><a href="?pg=reports&action=ventas-por-empleado">Por Empleado</a></li>
                    <li><a href="?pg=reports&action=ventas-por-cliente">Por Cliente</a></li>
                </ul>
            </div>

            <!-- Compras -->
            <div class="nav-section">
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    Compras
                </h3>
                <ul class="section-links">
                    <li><a href="?pg=reports&action=compras-mensuales">Compras Mensuales</a></li>
                    <li><a href="?pg=reports&action=compras-por-proveedor">Por Proveedor</a></li>
                    <li><a href="?pg=reports&action=historial-compras">Historial de Compras</a></li>
                    <li><a href="?pg=reports&action=cuentas-por-pagar">Cuentas por Pagar</a></li>
                </ul>
            </div>

            <!-- Inventario -->
            <div class="nav-section">
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    </svg>
                    Inventario
                </h3>
                <ul class="section-links">
                    <li><a href="?pg=reports&action=stock-actual">Stock Actual</a></li>
                    <li><a href="?pg=reports&action=productos-mas-vendidos">Más Vendidos</a></li>
                    <li><a href="?pg=reports&action=productos-menos-vendidos">Menos Vendidos</a></li>
                    <li><a href="?pg=reports&action=alertas-stock">Alertas de Stock</a></li>
                    <li><a href="?pg=reports&action=valoracion-inventario">Valoración</a></li>
                </ul>
            </div>

            <!-- Finanzas -->
            <div class="nav-section">
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    Finanzas
                </h3>
                <ul class="section-links">
                    <li><a href="?pg=reports&action=ingresos-gastos">Ingresos vs Gastos</a></li>
                    <li><a href="?pg=reports&action=utilidad-neta">Utilidad Neta</a></li>
                    <li><a href="?pg=reports&action=flujo-caja">Flujo de Caja</a></li>
                    <li><a href="?pg=reports&action=punto-equilibrio">Punto de Equilibrio</a></li>
                </ul>
            </div>

            <!-- Análisis -->
            <div class="nav-section">
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Análisis
                </h3>
                <ul class="section-links">
                    <li><a href="?pg=reports&action=comparativa-mensual">Comparativa Mensual</a></li>
                    <li><a href="?pg=reports&action=comparativa-anual">Comparativa Anual</a></li>
                    <li><a href="?pg=reports&action=tendencias">Tendencias</a></li>
                    <li><a href="?pg=reports&action=rentabilidad">Rentabilidad</a></li>
                </ul>
            </div>

            <!-- Clientes -->
            <div class="nav-section">
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    </svg>
                    Clientes
                </h3>
                <ul class="section-links">
                    <li><a href="?pg=reports&action=clientes-frecuentes">Clientes Frecuentes</a></li>
                    <li><a href="?pg=reports&action=historial-cliente">Historial</a></li>
                    <li><a href="?pg=reports&action=valor-vida">Valor de Vida</a></li>
                </ul>
            </div>

            <!-- Impuestos -->
            <div class="nav-section">
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/>
                    </svg>
                    Impuestos
                </h3>
                <ul class="section-links">
                    <li><a href="?pg=reports&action=iva-cobrado">IVA Cobrado</a></li>
                    <li><a href="?pg=reports&action=iva-pagado">IVA Pagado</a></li>
                    <li><a href="?pg=reports&action=declaracion">Declaración</a></li>
                </ul>
            </div>
        </nav>
    </aside>

    <!-- Botón móvil -->
    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>

    <!-- Contenido -->
    <main class="reports-content">
        <?php
        // Aquí se cargará el contenido dinámico de los reportes según la acción seleccionada
        if (isset($_GET['action'])) {
            $action = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['action']);
            $reportView = __DIR__ . '/../Reports/' . $action . '.report.php';

            if (file_exists($reportView)) {
                require_once $reportView;
            } else {
                echo '<h2 style="padding:20px;">Reporte no encontrado.</h2>';
            }
        } else {
            echo '<h2 style="padding:20px;">Seleccione un reporte del menú lateral.</h2>';
        }
        ?>
    </main>

</div>

<script>
function toggleSidebar() {
    document.getElementById('reportsSidebar').classList.toggle('mobile-open');
}

// Cerrar sidebar en móvil al hacer clic en un enlace
document.querySelectorAll('.section-links a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            document.getElementById('reportsSidebar').classList.remove('mobile-open');
        }
    });
});
</script>

</body>
</html>
