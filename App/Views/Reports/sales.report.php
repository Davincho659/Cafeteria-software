
<link rel="stylesheet" href="assets/css/bills.css">
<link rel="stylesheet" href="assets/css/flatpick.css">

<div class="container-fluid">

    <!-- ================= FILTROS ================= -->
    <div class="filter-card">
        <h4 class="filter-section-title">🔍 Filtros de búsqueda</h4>
        <div id="active-filters" class="active-filters" style="display:none;">
            <div class="active-filters-title">
                Filtros activos:
                <span id="active-filters-list"></span>
            </div>
        </div>
        <input
                        type="text"
                        id="fechas"
                        class="filter-input"
                        name="fechas"
                        style="visibility:hidden; height:0; padding:0; margin:0;"
                    />
        <form id="filtrosReporte">

            <div class="row">

                <!-- ID Venta -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">ID Venta</label>
                        <input type="number" name="idVenta" class="filter-input" placeholder="Ej: 123">
                    </div>
                </div>

                <!-- Precio desde -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">Precio desde</label>
                        <input type="number" name="precioDesde" class="filter-input" placeholder="$0">
                    </div>
                </div>

                <!-- Precio hasta -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">Precio hasta</label>
                        <input type="number" name="precioHasta" class="filter-input" placeholder="$999999">
                    </div>
                </div>

                <!-- Método de pago -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">Método de pago</label>
                        <select name="metodoPago" class="filter-select">
                            <option value="">Todos</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                    </div>
                </div>
                
                <!-- Fecha -->
                 <div class="col-md-6">
                    <select name="fecha" id="select" class="filter-select"> 
                        <option value="">Seleccionar rango</option> 
                        <option value="<?php echo date('d/m/Y') . ' - ' . date('d/m/Y'); ?>" <?= (isset($_POST['fecha']) && $_POST['fecha'] === date('d/m/Y') . ' - ' . date('d/m/Y')) ? 'selected' : '' ?>> Hoy </option>
                        <option value="<?php echo date('d/m/Y', strtotime('-1 day')) . ' - ' . date('d/m/Y', strtotime('-1 day')); ?>" <?= (isset($_POST['fecha']) && $_POST['fecha'] === date('d/m/Y', strtotime('-1 day')) . ' - ' . date('d/m/Y', strtotime('-1 day'))) ? 'selected' : '' ?>> Ayer </option> 
                        <option value="<?php echo date('d/m/Y', strtotime('first day of this month')) . ' - ' . date('d/m/Y'); ?>" <?php $thisMonthRange = date('d/m/Y', strtotime('first day of this month')) . ' - ' . date('d/m/Y'); echo (isset($_POST['fecha']) && $_POST['fecha'] === $thisMonthRange) ? 'selected' : ''; ?>> Este mes </option> 
                        <option value="custom" id="custom-option">Rango personalizado</option>
                    </select>

                    <!-- Input REAL pero invisible visualmente -->
                    
                </div>

            </div>

            <!-- BOTONES -->
            <div class="mt-3">
                <button type="submit" class="btn-search">🔍 Consultar</button>
                <button type="button" class="btn-clear" onclick="limpiarFiltros()">✕ Limpiar</button>
            </div>

        </form>
        
    </div>

    <!-- ================= RESULTADOS ================= -->

    <div class="mt-4">
        <h3 class="filter-section-title">📊 Resultados del reporte</h3>

        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID Venta</th>
                    <th>Fecha</th>
                    <th>Método Pago</th>
                    <th>Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody id="tablaResultados">
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        Cargando resultados...
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- PAGINACIÓN -->
        <div id="paginacion" class="mt-3"></div>
    </div>
</div>



<script src="assets/js/flatpick.js"></script>
<script src="assets/js/admin/reports.js"></script>