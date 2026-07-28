
<link rel="stylesheet" href="<?= asset('assets/css/bills.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/reports.css') ?>">

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
                            <option value="bancolombia">Bancolombia</option>
                            <option value="nequi">Nequi</option>
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
            <tfoot id="tfootTotales" class="tabla-totales"></tfoot>
        </table>

        <!-- PAGINACIÓN -->
        <div id="paginacion" class="mt-3"></div>
        <br>
    </div>
    <!-- Modal de Detalles de Venta -->
    <div id="saleDetailModalContainer">
        <div class="modal fade" id="saleDetailModal" tabindex="-1" >
            <div class="modal-dialog modal-xl modal-dialog-scrollable  modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Detalles de la venta #<span id="saleDetailId"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div id="saleDetailLoader" class="text-center py-4 d-none">
                            <div class="spinner-border text-info" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2 text-muted">Cargando detalles...</p>
                        </div>

                        <div id="saleDetailError" class="alert alert-danger d-none" role="alert"></div>

                        <div id="saleDetailContent">
                            <table class="table table-sm table-striped table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center" style="width:120px">Cantidad</th>
                                        <th style="width:180px">Precio unitario</th>
                                        <th style="width:180px">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="saleDetailTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Sin datos</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-end mt-2">
                                <div>
                                    <strong>Total:</strong>
                                    $<span id="saleDetailTotal">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-info" id="saleDetailPrintBtn">Imprimir factura</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
// ============================================================================
// REPORTE DIARIO - SCRIPT AISLADO Y AUTÓNOMO
// ============================================================================

// Definir variable para evitar conflictos con reports.js
const tipoReporte = 'daily';

// Variable para almacenar la instancia de inicialización
let dailyReportInitialized = false;

(function() {
    'use strict';
    
    console.log('[DAILY REPORT] Script inicializado');
    
    let paginaActual = 1;
    let flatpickrInstance = null;
    
    // Función principal de inicialización
    function initDailyReport() {
        if (dailyReportInitialized) {
            console.log('[DAILY REPORT] Ya está inicializado, recargando datos...');
            cargarResultados();
            return;
        }
        
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
        
        dailyReportInitialized = true;
        
        // Cargar resultados iniciales
        cargarResultados();
        
        console.log('[DAILY REPORT] Configuración completada');
    }
    
    // Exponer función global INMEDIATAMENTE (antes de cualquier auto-inicialización)
    window.cargarReporte = initDailyReport;
    window.limpiarFiltros = function() {
        const form = document.getElementById('filtrosReporte');
        if (form) {
            form.reset();
            paginaActual = 1;
            cargarResultados();
        }
    };    window.cargarResultados = cargarResultados;    
    // Función para cargar resultados
    function cargarResultados(page = paginaActual) {
        console.log('[DAILY REPORT] Cargando resultados página:', page);
        
        const form = document.getElementById('filtrosReporte');
        const data = new FormData(form);
        data.append('page', page);

        fetch('?pg=sales&action=dailyBillsData', {
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
            renderTotalesDaily(data.totales);
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
            renderTotalesDaily(null);
        });
    }

    // Fila de totales del día/rango consultado (solo ventas completadas).
    function renderTotalesDaily(totales) {
        const tfoot = document.getElementById('tfootTotales');
        if (!tfoot) return;
        if (!totales) { tfoot.innerHTML = ''; return; }
        const money = v => '$' + (Number(v) || 0).toLocaleString('es-CO');
        const num = v => (Number(v) || 0).toLocaleString('es-CO');
        tfoot.innerHTML = `
            <tr class="fila-totales">
                <td colspan="3" class="text-end">TOTAL vendido:</td>
                <td>${money(totales.totalVendido)}</td>
                <td class="totales-meta">${num(totales.cantidadVentas)} ventas</td>
            </tr>
            <tr class="fila-totales-desglose">
                <td colspan="5" class="totales-desglose">
                    💵 Efectivo ${money(totales.totalEfectivo)}
                    &nbsp;·&nbsp; 🏦 Bancolombia ${money(totales.totalBancolombia)}
                    &nbsp;·&nbsp; 📱 Nequi ${money(totales.totalNequi)}
                    &nbsp;|&nbsp; <strong>Transferencias ${money(totales.totalTransferencia)}</strong>
                </td>
            </tr>`;
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
                            onclick="openBillWindow(${r.idVenta})">
                            Ver factura
                        </button>
                        <button class="btn btn-sm btn-info"
                            onclick= "detail(${r.idVenta})">
                            Ver detalles
                        </button>
                        <button class="btn btn-sm btn-danger" 
                            onclick="cancelarVenta(${r.idVenta})">
                            Anular
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
    
    // Auto-inicializar si el DOM ya está listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDailyReport);
    } else {
        // DOM ya está listo, inicializar inmediatamente
        setTimeout(initDailyReport, 100);
    }
    
})();

// ========================
// CANCELAR VENTA
// ========================
window.cancelarVenta = async function(idVenta) {
    const result = await Swal.fire({
        title: '¿Anular venta?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#808080',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar',
        html: `
            <div style="text-align: left; margin-top: 15px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                    Observación (opcional):
                </label>
                <textarea 
                    id="motivoCancelacion" 
                    class="swal2-textarea"
                    placeholder="Ej: Error del cliente, cambio de decisión, etc."
                    style="width: 100%; height: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit;">
                </textarea>
            </div>
        `,
        didOpen: () => {
            const textarea = Swal.getHtmlContainer().querySelector('#motivoCancelacion');
            if (textarea) textarea.focus();
        },
        preConfirm: () => {
            const observacion = Swal.getHtmlContainer().querySelector('#motivoCancelacion').value;
            return { observacion };
        }
    });

    if (!result.isConfirmed) {
        return;
    }

    const observacion = result.value.observacion;
    
    // Mostrar estado de carga
    Swal.fire({
        title: 'Anulando venta...',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch('?pg=sales&action=cancelSaleByInvoice', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ 
                idVenta: idVenta,
                observacion: observacion || null
            })
        });

        const data = await response.json();

        if (data.success) {
            // Mostrar éxito
            Swal.fire({
                icon: 'success',
                title: '¡Venta Anulada!',
                text: 'La venta ha sido cancelada correctamente',
                timer: 1500,
                showConfirmButton: false,
            });
            
            // Recargar tabla
            setTimeout(() => {
                window.cargarResultados();
            }, 1500);
        } else {
            // Mostrar error
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error || 'No se pudo anular la venta'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error en la comunicación con el servidor'
        });
    }
};

// ========================
// MODAL: Detalles de venta
// ========================
window.detail = function(idVenta) {
    try {
        const modalEl = document.getElementById('saleDetailModal');
        const saleIdEl = document.getElementById('saleDetailId');
        const loaderEl = document.getElementById('saleDetailLoader');
        const errorEl = document.getElementById('saleDetailError');
        const tbodyEl = document.getElementById('saleDetailTableBody');
        const totalEl = document.getElementById('saleDetailTotal');
        const printBtn = document.getElementById('saleDetailPrintBtn');

        if (!modalEl || !saleIdEl || !loaderEl || !errorEl || !tbodyEl || !totalEl || !printBtn) {
            console.error('[DAILY REPORT] Elementos del modal no encontrados');
            return;
        }

        // Preparar estado inicial
        saleIdEl.textContent = String(idVenta);
        loaderEl.classList.remove('d-none');
        errorEl.classList.add('d-none');
        tbodyEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Cargando...</td></tr>';
        totalEl.textContent = '0';
        printBtn.onclick = () => window.open('?pg=bill&id=' + idVenta, '_blank', 'width=350,height=900');

        // Mostrar modal (Bootstrap si está disponible)
        try {
            const bs = window.bootstrap;
            if (bs && typeof bs.Modal === 'function') {
                bs.Modal.getOrCreateInstance(modalEl).show();
            } else {
                // Fallback muy básico
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
                document.body.classList.add('modal-open');
            }
        } catch (e) {
            console.warn('[DAILY REPORT] No se pudo abrir modal con Bootstrap:', e);
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
        }

        // Fetch de detalles de la venta
        fetch('?pg=sales&action=GetSale&id=' + encodeURIComponent(idVenta), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            const detalles = data?.data?.detalles || data?.detalles || data?.data?.productos || [];
            tbodyEl.innerHTML = '';

            if (!Array.isArray(detalles) || detalles.length === 0) {
                tbodyEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Sin productos</td></tr>';
                return;
            }

            let total = 0;
            detalles.forEach(d => {
                const nombre = d.producto_nombre || d.nombre || 'Producto';
                const qty = parseInt(d.cantidad || 0, 10);
                const unit = parseFloat((d.precioUnitario ?? d.precioVenta ?? d.precio ?? 0));
                const subtotal = parseFloat((d.subTotal ?? d.precioTotal ?? (qty * unit)));
                total += (isNaN(subtotal) ? 0 : subtotal);

                tbodyEl.innerHTML += `
                    <tr>
                        <td>${nombre}</td>
                        <td class="text-center">${isNaN(qty) ? 0 : qty}</td>
                        <td>$${Number(isNaN(unit) ? 0 : unit).toLocaleString('es-CO')}</td>
                        <td>$${Number(isNaN(subtotal) ? 0 : subtotal).toLocaleString('es-CO')}</td>
                    </tr>`;
            });
            totalEl.textContent = Number(total).toLocaleString('es-CO');
        })
        .catch(err => {
            console.error('[DAILY REPORT] Error detalles:', err);
            errorEl.textContent = 'No se pudieron cargar los detalles: ' + err.message;
            errorEl.classList.remove('d-none');
            tbodyEl.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error cargando detalles</td></tr>';
        })
        .finally(() => {
            loaderEl.classList.add('d-none');
        });
    } catch (error) {
        console.error('[DAILY REPORT] Error mostrando detalles:', error);
    }
};
</script>