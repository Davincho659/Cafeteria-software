<?php if (!isset($_GET['ajax'])) {
    require loadView('Layouts/header');
}  ?>
<link rel="stylesheet" href="<?= asset('assets/css/bills.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/reports.css') ?>">
<div class="reports-container">
    <!-- Botón para abrir el menú de reportes en móvil -->
    <button type="button" class="reports-menu-toggle" id="reportsMenuToggle" aria-label="Abrir menú de reportes">
        <i class="fa-solid fa-bars"></i> Reportes
    </button>
    <!-- Fondo oscuro del drawer (móvil) -->
    <div class="reports-drawer-backdrop" id="reportsDrawerBackdrop"></div>

    <!-- Sidebar -->
    <aside class="reports-sidebar" id="reportsSidebar">
        <div class="sidebar-header">
            <h2>📊 Reportes</h2>
            <p>Sistema POS</p>
        </div>

        <nav class="sidebar-nav">
            <!-- Cierres -->
            <div class="nav-section">
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Cierres
                </h3>
                <ul class="section-links">
                    <li><a href="?pg=reports&action=closingDaily" class="<?= ($_GET['action'] ?? '') === 'closingDaily' ? 'active' : '' ?>">Cierre del día</a></li>
                    <li><a href="?pg=reports&action=closingMonthly" class="<?= ($_GET['action'] ?? '') === 'closingMonthly' ? 'active' : '' ?>">Cierre mensual</a></li>
                    <li><a href="?pg=reports&action=closingYearly" class="<?= ($_GET['action'] ?? '') === 'closingYearly' ? 'active' : '' ?>">Cierre anual</a></li>
                </ul>
            </div>

            <!-- Contabilidad -->
            <div class="nav-section">
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 17V7m0 10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m0 10a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 7a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m0 10V7m0 10a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2"/>
                    </svg>
                    Contabilidad
                </h3>
                <ul class="section-links">
                    <li><a href="?pg=reports&action=accounting" class="<?= ($_GET['action'] ?? '') === 'accounting' ? 'active' : '' ?>">Reporte contable</a></li>
                </ul>
            </div>

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
                    <li><a href="?pg=reports&action=salesProduct" class="<?= ($_GET['action'] ?? '') === 'salesProduct' ? 'active' : '' ?>">Ventas por producto</a></li>
                    <li><a href="?pg=reports&action=salesByHour" class="<?= ($_GET['action'] ?? '') === 'salesByHour' ? 'active' : '' ?>">Ventas por hora</a></li>
                    <!--<li><a href="?pg=reports&action=ventas-anuales">Ventas Anuales</a></li>
                    <li><a href="?pg=reports&action=ventas-por-producto">Por Producto</a></li>-->
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
                    <li><a href="?pg=reports&action=purchases" class="<?= ($_GET['action'] ?? '') === 'purchases' ? 'active' : '' ?>">Compras</a></li>
                    <!-- <li><a href="?pg=reports&action=compras-por-proveedor">Por Proveedor</a></li> -->
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
                    <li><a href="?pg=reports&action=inventory"class="<?= ($_GET['action'] ?? '') === 'inventory' ? 'active' : '' ?>">Inventario</a></li>
                    <!-- 
                    <li><a href="?pg=reports&action=productos-mas-vendidos">Más Vendidos</a></li>
                    <li><a href="?pg=reports&action=productos-menos-vendidos">Menos Vendidos</a></li> -->
                    <li><a href="?pg=reports&action=alertas-stock">Alertas de Stock</a></li>
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
                    <li><a href="?pg=reports&action=flujo-caja">Flujo de Caja</a></li>
                    <li><a href="?pg=reports&action=expenses" class="<?= ($_GET['action'] ?? '') === 'expenses' ? 'active' : '' ?>">Gastos</a></li>
                    <li><a href="?pg=reports&action=profitability" class="<?= ($_GET['action'] ?? '') === 'profitability' ? 'active' : '' ?>">Rentabilidad</a></li>
                    <li><a href="?pg=reports&action=cashRegister" class="<?= ($_GET['action'] ?? '') === 'cashRegister' ? 'active' : '' ?>">Flujo de Caja</a></li>
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
                </ul>
            </div>

        </nav>
    </aside>

    <!-- Contenido -->
    <main class="reports-content">
        <?php
        if (isset($_GET['action'])) {
            // Se limpia el nombre antes de armar la ruta: sin esto se podría
            // pedir ?action=../../algo y cargar un archivo fuera de Reports/.
            $accion = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $_GET['action']);
            $view = __DIR__ . '/../Reports/' . $accion . '.report.php';

            if ($accion !== '' && file_exists($view)) {
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
<script src="<?= asset('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script>
    (function() {
        const sidebar = document.getElementById('reportsSidebar');
        if (!sidebar) return;

        // Restaurar scroll
        const saved = sessionStorage.getItem('reportsSidebarScroll');
        if (saved !== null) {
            sidebar.scrollTop = parseInt(saved, 10) || 0;
        }

        // Guardar scroll al hacer click en links
        sidebar.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link) return;
            sessionStorage.setItem('reportsSidebarScroll', String(sidebar.scrollTop));
        });

        // Guardar scroll al mover
        sidebar.addEventListener('scroll', () => {
            sessionStorage.setItem('reportsSidebarScroll', String(sidebar.scrollTop));
        }, { passive: true });

        // ---- Drawer en móvil ----
        const toggle = document.getElementById('reportsMenuToggle');
        const backdrop = document.getElementById('reportsDrawerBackdrop');

        function openDrawer() {
            sidebar.classList.add('drawer-open');
            if (backdrop) backdrop.classList.add('show');
        }
        function closeDrawer() {
            sidebar.classList.remove('drawer-open');
            if (backdrop) backdrop.classList.remove('show');
        }

        if (toggle) toggle.addEventListener('click', openDrawer);
        if (backdrop) backdrop.addEventListener('click', closeDrawer);

        // Al elegir un reporte, cerrar el drawer (en móvil)
        sidebar.addEventListener('click', (e) => {
            if (e.target.closest('a')) closeDrawer();
        });
    })();
</script>