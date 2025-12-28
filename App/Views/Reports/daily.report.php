
<link rel="stylesheet" href="assets/css/bills.css">
<style>
    .kpi-row { margin-top: 1.5rem; }
    .kpi-card {
        background: #0f172a;
        color: #f8fafc;
        border-radius: 12px;
        padding: 16px 18px;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
        height: 100%;
    }
    .kpi-label { font-size: 0.95rem; opacity: 0.85; letter-spacing: 0.3px; }
    .kpi-value { font-size: 1.6rem; font-weight: 700; margin-top: 4px; }
    @media (max-width: 576px) { .kpi-value { font-size: 1.35rem; } }
</style>

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
                        <option value="<?php echo date('d/m/Y') . ' - ' . date('d/m/Y'); ?>" <?= (isset($_POST['fecha']) && $_POST['fecha'] === date('d/m/Y') . ' - ' . date('d/m/Y')) ? 'selected' : '' ?>> Hoy </option>
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
        <br>
    </div>
</div>


<script>
// ============================================================================
// REPORTE DIARIO - SCRIPT AISLADO Y AUTÓNOMO
// ============================================================================
(function() {
    'use strict';
    
    console.log('[DAILY REPORT] Script inicializado');
    
    let paginaActual = 1;
    let flatpickrInstance = null;
    
    // Función principal de inicialización
    function initDailyReport() {
        console.log('[DAILY REPORT] Iniciando configuración...');
        
        const fechasInput = document.getElementById('fechas');
        const selectFecha = document.getElementById('select');
        const form = document.getElementById('filtrosReporte');
        
        if (!fechasInput || !selectFecha || !form) {
            console.error('[DAILY REPORT] Elementos no encontrados');
            return;
        }
        
        // Inicializar flatpickr
        if (typeof flatpickr !== 'undefined') {
            flatpickrInstance = flatpickr(fechasInput, {
                mode: "range",
                maxDate: "today",
                dateFormat: "d/m/Y",
                locale: "es",
                showMonths: 2,
                appendTo: fechasInput.parentElement,
                onClose: function (selectedDates) {
                    if (!selectedDates.length) return;

                    const format = (d) => flatpickrInstance.formatDate(d, "d/m/Y");
                    let rango = "";

                    if (selectedDates.length === 1) {
                        const f = format(selectedDates[0]);
                        rango = `${f} - ${f}`;
                    } else {
                        const fechasOrdenadas = selectedDates.slice().sort((a, b) => a - b);
                        const f1 = format(fechasOrdenadas[0]);
                        const f2 = format(fechasOrdenadas[1]);
                        rango = `${f1} - ${f2}`;
                    }

                    let option = document.getElementById("dynamic-range");
                    if (!option) {
                        option = document.createElement("option");
                        option.id = "dynamic-range";
                        selectFecha.appendChild(option);
                    }

                    option.value = rango;
                    option.textContent = rango;
                    selectFecha.value = rango;
                }
            });
        }
        
        // Event listener para el select
        selectFecha.addEventListener('change', function () {
            if (this.value === 'custom' && flatpickrInstance) {
                flatpickrInstance.clear();
                flatpickrInstance.open();
            }
        });
        
        // Event listener para el formulario
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            paginaActual = 1;
            cargarResultados();
        });
        
        // Cargar resultados iniciales
        cargarResultados();
        
        console.log('[DAILY REPORT] Configuración completada');
    }
    
    // Función para cargar resultados
    function cargarResultados(page = paginaActual) {
        console.log('[DAILY REPORT] Cargando resultados página:', page);
        
        const form = document.getElementById('filtrosReporte');
        const data = new FormData(form);
        data.append('page', page);

        fetch('index.php?pg=reports&action=daily&ajax=1', {
            method: 'POST',
            body: data,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => {
            if (!r.ok) throw new Error('Error en la respuesta del servidor');
            return r.json();
        })
        .then(data => {
            console.log('[DAILY REPORT] Datos recibidos:', data);
            renderTabla(data.resultados);
            renderPaginacion(data.totalPaginas, data.paginaActual);
            paginaActual = data.paginaActual;
        })
        .catch(err => {
            console.error('[DAILY REPORT] Error cargando datos:', err);
            const tbody = document.getElementById('tablaResultados');
            if (tbody) {
                tbody.innerHTML = `
                    <tr><td colspan="5" class="text-danger text-center">Error cargando datos</td></tr>`;
            }
        });
    }
    
    // Función para renderizar tabla
    function renderTabla(rows) {
        const tbody = document.getElementById('tablaResultados');
        if (!tbody) return;
        
        tbody.innerHTML = '';

        if (!rows || rows.length === 0) {
            tbody.innerHTML = `
                <tr><td colspan="5" class="text-center text-muted">No hay resultados</td></tr>`;
            return;
        }

        rows.forEach(r => {
            tbody.innerHTML += `
                <tr>
                    <td>${r.idVenta}</td>
                    <td>${new Date(r.fechaVenta).toLocaleString('es-CO')}</td>
                    <td>${r.metodoPago}</td>
                    <td>$${Number(r.total).toLocaleString('es-CO')}</td>
                    <td>
                        <button class="btn btn-sm btn-success"
                            onclick="window.open('?pg=bill&id=${r.idVenta}','_blank','width=350,height=900')">
                            Ver factura
                        </button>
                    </td>
                </tr>`;
        });
    }
    
    // Función para renderizar paginación
    function renderPaginacion(total, actual) {
        const container = document.getElementById('paginacion');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (total <= 1) return;

        const createItem = (content, pageNum, isActive = false, isDisabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`;
            
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.innerHTML = content;
            
            if (!isDisabled && !isActive) {
                a.onclick = (e) => {
                    e.preventDefault();
                    cargarResultados(pageNum);
                };
            }
            
            li.appendChild(a);
            return li;
        };

        const ul = document.createElement('ul');
        ul.className = 'pagination justify-content-center';

        // Primera página
        ul.appendChild(createItem('«', 1, false, actual === 1));
        // Página anterior
        ul.appendChild(createItem('‹', Math.max(1, actual - 1), false, actual === 1));

        // Páginas numeradas
        const rango = 2;
        let start = Math.max(1, actual - rango);
        let end = Math.min(total, actual + rango);

        if (start > 1) {
            ul.appendChild(createItem('1', 1));
            if (start > 2) ul.appendChild(createItem('...', null, false, true));
        }

        for (let i = start; i <= end; i++) {
            ul.appendChild(createItem(i, i, i === actual));
        }

        if (end < total) {
            if (end < total - 1) ul.appendChild(createItem('...', null, false, true));
            ul.appendChild(createItem(total, total));
        }

        // Página siguiente
        ul.appendChild(createItem('›', Math.min(total, actual + 1), false, actual === total));
        // Última página
        ul.appendChild(createItem('»', total, false, actual === total));

        container.appendChild(ul);
    }
    
    // Función de limpiar filtros
    window.limpiarFiltros = function() {
        const form = document.getElementById('filtrosReporte');
        if (form) {
            form.reset();
            paginaActual = 1;
            cargarResultados();
        }
    };
    
    // Exponer función global para inicialización externa
    window.cargarReporte = initDailyReport;
    
    // Auto-inicializar si el DOM ya está listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDailyReport);
    } else {
        // DOM ya está listo, inicializar inmediatamente
        setTimeout(initDailyReport, 100);
    }
    
})();
</script>