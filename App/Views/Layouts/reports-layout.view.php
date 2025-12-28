<?php if (!isset($_GET['ajax'])) {
    require loadView('Layouts/header');
}  ?>
<link rel="stylesheet" href="assets/css/bills.css">
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
                    <li><a href="?pg=reports&action=sales" class="<?= ($_GET['action'] ?? '') === 'sales' ? 'active' : '' ?>">Ventas</a></li>
                    <li><a href="?pg=reports&action=ventas-mensuales" class="<?= ($_GET['action'] ?? '') === 'ventas-mensuales' ? 'active' : '' ?>">Ventas Mensuales</a></li>
                    <li><a href="?pg=reports&action=ventas-anuales">Ventas Anuales</a></li>
                    <li><a href="?pg=reports&action=ventas-por-producto">Por Producto</a></li>
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
                    <li><a href="?pg=reports&action=compras-mensuales">Compras</a></li>
                    <li><a href="?pg=reports&action=compras-por-proveedor">Por Proveedor</a></li>
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

        </nav>
    </aside>

    <!-- Contenido -->
    <main class="reports-content">
        <?php
        if (isset($_GET['action'])) {
            $view = __DIR__ . '/../Reports/' . $_GET['action'] . '.report.php';

            if (file_exists($view)) {
                require $view;
            } else {
                echo '<h2 style="padding:20px;">Reporte no encontrado.</h2>';
            }
        } else {
            echo '<h2 style="padding:20px;">Seleccione un reporte del menú.</h2>';
        }
        ?>
    </main>

</div>
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />