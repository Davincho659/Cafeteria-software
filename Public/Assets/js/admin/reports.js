document.addEventListener('DOMContentLoaded', function () {

    const fechasInput = document.getElementById('fechas');
    const selectFecha = document.getElementById('select');

    // Detectar tipo de reporte (definido en cada vista)
    const tipo = typeof tipoReporte !== 'undefined' ? tipoReporte : 'sales';

    // Si el tipo no usa fechas (ej: cashRegister, inventoryReport), omitir flatpickr
    if (fechasInput && selectFecha) {
        const fp = flatpickr(fechasInput, {
            mode: "range",
            maxDate: "today",
            dateFormat: "d/m/Y",
            locale: "es",
            showMonths: 2,
            appendTo: fechasInput.parentElement,

            onClose: function (selectedDates) {
                if (!selectedDates.length) return;

                const format = (d) => fp.formatDate(d, "d/m/Y");
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

        selectFecha.addEventListener('change', function () {
            if (this.value === 'custom') {
                fp.clear();
                fp.open();
            }
        });
    }

    
    // Render inicial de filtros activos (si aplica)
    if (typeof renderActiveFilters === 'function') {
        renderActiveFilters();
    }

    // Carga inicial
    cargarResultados();
});

let paginaActual = 1;

const formFiltros = document.getElementById('filtrosReporte');
if (formFiltros) {
    formFiltros.addEventListener('submit', e => {
        e.preventDefault();
        paginaActual = 1;
        cargarResultados();
    });
}

function cargarResultados(page = paginaActual) {
    const tipo = typeof tipoReporte !== 'undefined' ? tipoReporte : 'sales';

    // Reportes especiales sin paginación
    if (tipo === 'profitability') {
        cargarRentabilidad();
        return;
    }
    if (tipo === 'cashRegister') {
        cargarCaja();
        return;
    }
    if (tipo === 'inventoryReport') {
        cargarInventario();
        return;
    }
    if (tipo === 'topProducts') {
        cargarTopProductos();
        return;
    }

    // Reportes con paginación (sales, purchases, expenses)
    const form = document.getElementById('filtrosReporte');
    const data = new FormData(form);
    data.append('page', page);

    fetch(`index.php?pg=reports&action=${tipo}&ajax=1`, {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(data => {
        console.log(data);  
        renderTabla(data.resultados, tipo);
        renderPaginacion(data.totalPaginas, data.paginaActual);
        if (typeof renderActiveFilters === 'function') renderActiveFilters();
        actualizarKPIs(data, tipo);
    })
    .catch(() => {
        const tbody = document.getElementById('tablaResultados');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-danger text-center">Error cargando datos</td></tr>`;
        }
        actualizarKPIs({ resultados: [], totalPaginas: 0, paginaActual: 1 }, tipo);
    });
}

function renderTabla(rows, tipo = 'sales') {
    const tbody = document.getElementById('tablaResultados');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!rows || rows.length === 0) {
        const colSpan = tipo === 'sales' ? 5 : tipo === 'purchases' ? 5 : 5;
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center text-muted">No hay resultados</td></tr>`;
        return;
    }

    if (tipo === 'sales') {
        rows.forEach(r => {
            tbody.innerHTML += `
                <tr>
                    <td>${r.idVenta}</td>
                    <td>${new Date(r.fechaVenta).toLocaleString()}</td>
                    <td>${r.metodoPago}</td>
                    <td>$${Number(r.total).toLocaleString()}</td>
                    <td>
                        <button class="btn btn-sm btn-success"
                            onclick="window.open('factura.php?pg=bill&id=${r.idVenta}','_blank','width=350,height=900')">
                            Ver factura
                        </button>
                    </td>
                </tr>`;
        });
    } else if (tipo === 'purchases') {
        rows.forEach(r => {
            tbody.innerHTML += `
                <tr>
                    <td>${r.idCompra}</td>
                    <td>${r.nombreProveedor || 'Sin proveedor'}</td>
                    <td>${new Date(r.fechaCompra).toLocaleString()}</td>
                    <td>${r.tipoCompra}</td>
                    <td>$${Number(r.total).toLocaleString()}</td>
                </tr>`;
        });
    } else if (tipo === 'expenses') {
        rows.forEach(r => {
            const tipoLabel = r.tipo === 'producto' ? '📦 Producto' : '💼 Externo';
            tbody.innerHTML += `
                <tr>
                    <td>${r.idGasto}</td>
                    <td>${tipoLabel}</td>
                    <td>${r.descripcion || '-'}</td>
                    <td>${new Date(r.fechaGasto).toLocaleString()}</td>
                    <td>$${Number(r.monto).toLocaleString()}</td>
                </tr>`;
        });
    }
}

function calcularTotales(rows, tipo = 'sales') {
    let totalVendido = 0;
    let totalVentas = 0;
    let totalComprado = 0;
    let totalCompras = 0;
    let totalGastos = 0;
    let cantidadGastos = 0;

    if (Array.isArray(rows)) {
        for (let i = 0; i < rows.length; i++) {
            const item = rows[i] || {};
            
            if (tipo === 'sales') {
                const monto = Number(item.total) || 0;
                totalVendido += monto;
                totalVentas += 1;
            } else if (tipo === 'purchases') {
                const monto = Number(item.total) || 0;
                totalComprado += monto;
                totalCompras += 1;
            } else if (tipo === 'expenses') {
                const monto = Number(item.monto) || 0;
                totalGastos += monto;
                cantidadGastos += 1;
            }
        }
    }

    return { totalVendido, totalVentas, totalComprado, totalCompras, totalGastos, cantidadGastos };
}

async function actualizarKPIs(data, tipo = 'sales') {
    if (tipo === 'sales') {
        const totalVendidoEl = document.getElementById('kpi-total-vendido');
        const totalVentasEl = document.getElementById('kpi-total-ventas');
        if (!totalVendidoEl || !totalVentasEl) return;

        const totalPaginas = Number(data.totalPaginas) || 0;
        const paginaActual = Number(data.paginaActual) || 1;

        let { totalVendido, totalVentas } = calcularTotales(data.resultados || [], tipo);
        totalVendidoEl.textContent = `$${totalVendido.toLocaleString('es-CL')}`;
        totalVentasEl.textContent = totalVentas.toLocaleString('es-CL');

        if (totalPaginas <= 1) return;

        const formBase = document.getElementById('filtrosReporte');
        if (!formBase) return;

        for (let p = 1; p <= totalPaginas; p++) {
            if (p === paginaActual) continue;

            const formData = new FormData(formBase);
            formData.append('page', p);

            try {
                const resp = await fetch('index.php?pg=reports&action=sales&ajax=1', {
                    method: 'POST',
                    body: formData
                });
                const json = await resp.json();
                const totales = calcularTotales(json.resultados || [], tipo);
                totalVendido += totales.totalVendido;
                totalVentas += totales.totalVentas;
            } catch (e) {
                console.warn('No se pudieron cargar KPIs de la página', p, e);
            }
        }

        totalVendidoEl.textContent = `$${totalVendido.toLocaleString('es-CL')}`;
        totalVentasEl.textContent = totalVentas.toLocaleString('es-CL');
    } else if (tipo === 'purchases') {
        const totalCompradoEl = document.getElementById('kpi-total-comprado');
        const totalComprasEl = document.getElementById('kpi-total-compras');
        if (!totalCompradoEl || !totalComprasEl) return;

        const totalPaginas = Number(data.totalPaginas) || 0;
        const paginaActual = Number(data.paginaActual) || 1;

        let { totalComprado, totalCompras } = calcularTotales(data.resultados || [], tipo);
        totalCompradoEl.textContent = `$${totalComprado.toLocaleString('es-CL')}`;
        totalComprasEl.textContent = totalCompras.toLocaleString('es-CL');

        if (totalPaginas <= 1) return;

        const formBase = document.getElementById('filtrosReporte');
        if (!formBase) return;

        for (let p = 1; p <= totalPaginas; p++) {
            if (p === paginaActual) continue;

            const formData = new FormData(formBase);
            formData.append('page', p);

            try {
                const resp = await fetch('index.php?pg=reports&action=purchases&ajax=1', {
                    method: 'POST',
                    body: formData
                });
                const json = await resp.json();
                const totales = calcularTotales(json.resultados || [], tipo);
                totalComprado += totales.totalComprado;
                totalCompras += totales.totalCompras;
            } catch (e) {
                console.warn('No se pudieron cargar KPIs de la página', p, e);
            }
        }

        totalCompradoEl.textContent = `$${totalComprado.toLocaleString('es-CL')}`;
        totalComprasEl.textContent = totalCompras.toLocaleString('es-CL');
    } else if (tipo === 'expenses') {
        const totalGastosEl = document.getElementById('kpi-total-gastos');
        const cantidadGastosEl = document.getElementById('kpi-cantidad-gastos');
        if (!totalGastosEl || !cantidadGastosEl) return;

        const totalPaginas = Number(data.totalPaginas) || 0;
        const paginaActual = Number(data.paginaActual) || 1;

        let { totalGastos, cantidadGastos } = calcularTotales(data.resultados || [], tipo);
        totalGastosEl.textContent = `$${totalGastos.toLocaleString('es-CL')}`;
        cantidadGastosEl.textContent = cantidadGastos.toLocaleString('es-CL');

        if (totalPaginas <= 1) return;

        const formBase = document.getElementById('filtrosReporte');
        if (!formBase) return;

        for (let p = 1; p <= totalPaginas; p++) {
            if (p === paginaActual) continue;

            const formData = new FormData(formBase);
            formData.append('page', p);

            try {
                const resp = await fetch('index.php?pg=reports&action=expenses&ajax=1', {
                    method: 'POST',
                    body: formData
                });
                const json = await resp.json();
                const totales = calcularTotales(json.resultados || [], tipo);
                totalGastos += totales.totalGastos;
                cantidadGastos += totales.cantidadGastos;
            } catch (e) {
                console.warn('No se pudieron cargar KPIs de la página', p, e);
            }
        }

        totalGastosEl.textContent = `$${totalGastos.toLocaleString('es-CL')}`;
        cantidadGastosEl.textContent = cantidadGastos.toLocaleString('es-CL');
    }
}

function renderPaginacion(total, actual) {
    const cont = document.getElementById('paginacion');
    cont.innerHTML = '';

    if (total <= 1) return;

    let html = '<ul class="pagination mb-0">';

    const createItem = (label, page, disabled = false, active = false, aria = '') => {
        const cls = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`.trim();
        const safeOnClick = disabled ? 'return false;' : `irPagina(${page}); return false;`;
        const ariaAttr = aria ? ` aria-label="${aria}"` : '';
        return `
            <li class="${cls}">
                <a class="page-link" href="#" onclick="${safeOnClick}"${ariaAttr}>${label}</a>
            </li>`;
    };

    // Controles: Primera y Anterior
    html += createItem('&laquo;', 1, actual === 1, false, 'Primera');
    html += createItem('&lsaquo;', Math.max(1, actual - 1), actual === 1, false, 'Anterior');

    // Páginas numeradas con elipsis
    if (total <= 7) {
        for (let i = 1; i <= total; i++) {
            html += createItem(i, i, false, i === actual);
        }
    } else {
        const delta = 2; // cantidad alrededor de la actual
        const start = Math.max(2, actual - delta);
        const end = Math.min(total - 1, actual + delta);

        // Siempre mostrar primera
        html += createItem(1, 1, false, actual === 1);

        // Elipsis si hay hueco entre 1 y start
        if (start > 2) {
            html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }

        // Ventana central
        for (let i = start; i <= end; i++) {
            html += createItem(i, i, false, i === actual);
        }

        // Elipsis si hay hueco entre end y última
        if (end < total - 1) {
            html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }

        // Siempre mostrar última (casilla de página final)
        html += createItem(total, total, false, actual === total);
    }

    // Controles: Siguiente y Última
    html += createItem('&rsaquo;', Math.min(total, actual + 1), actual === total, false, 'Siguiente');
    html += createItem('&raquo;', total, actual === total, false, 'Última');

    html += '</ul>';
    cont.innerHTML = html;
}

function irPagina(p) {
    paginaActual = p;
    cargarResultados(p);
}

function limpiarFiltros() {
    const form = document.getElementById('filtrosReporte');
    if (form) form.reset();
    paginaActual = 1;
    cargarResultados();
    // Limpiar opción dinámica de fecha si existe
    const opt = document.getElementById('dynamic-range');
    if (opt) opt.remove();
    if (typeof renderActiveFilters === 'function') renderActiveFilters();
}

// ==================== REPORTES ESPECIALES ====================

function cargarRentabilidad() {
    const form = document.getElementById('filtrosReporte');
    const data = new FormData(form);

    fetch('index.php?pg=reports&action=profitability&ajax=1', {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }

        // Actualizar KPIs
        document.getElementById('kpi-total-ventas').textContent = `$${Number(data.totalVentas).toLocaleString('es-CL')}`;
        document.getElementById('kpi-total-costos').textContent = `$${Number(data.totalCostos).toLocaleString('es-CL')}`;
        document.getElementById('kpi-total-gastos').textContent = `$${Number(data.totalGastos).toLocaleString('es-CL')}`;
        document.getElementById('kpi-total-compras').textContent = `$${Number(data.totalCompras).toLocaleString('es-CL')}`;
        
        const gananciaEl = document.getElementById('kpi-ganancia-real');
        const ganancia = Number(data.gananciaReal);
        gananciaEl.textContent = `$${ganancia.toLocaleString('es-CL')}`;
        gananciaEl.classList.toggle('kpi-positive', ganancia >= 0);
        gananciaEl.classList.toggle('kpi-negative', ganancia < 0);

        const margenEl = document.getElementById('kpi-margen');
        const margen = Number(data.margenPorcentaje).toFixed(2);
        margenEl.textContent = `${margen}%`;
        margenEl.classList.toggle('kpi-positive', margen >= 0);
        margenEl.classList.toggle('kpi-negative', margen < 0);

        // Tabla de análisis
        const tbody = document.getElementById('tablaResultados');
        const totalVentas = Number(data.totalVentas);
        tbody.innerHTML = `
            <tr>
                <td><strong>💵 Ingresos por Ventas</strong></td>
                <td class="text-success"><strong>$${totalVentas.toLocaleString('es-CL')}</strong></td>
                <td>100.00%</td>
            </tr>
            <tr>
                <td>📦 Costos Promedio Productos</td>
                <td class="text-warning">$${Number(data.totalCostos).toLocaleString('es-CL')}</td>
                <td>${totalVentas > 0 ? ((data.totalCostos / totalVentas) * 100).toFixed(2) : 0}%</td>
            </tr>
            <tr>
                <td>💸 Gastos Operacionales</td>
                <td class="text-danger">$${Number(data.totalGastos).toLocaleString('es-CL')}</td>
                <td>${totalVentas > 0 ? ((data.totalGastos / totalVentas) * 100).toFixed(2) : 0}%</td>
            </tr>
            <tr class="table-active">
                <td><strong>💰 Ganancia Real</strong></td>
                <td class="${ganancia >= 0 ? 'text-success' : 'text-danger'}"><strong>$${ganancia.toLocaleString('es-CL')}</strong></td>
                <td><strong>${margen}%</strong></td>
            </tr>
        `;
    })
    .catch(err => {
        console.error('Error cargando rentabilidad:', err);
        alert('Error al cargar el reporte de rentabilidad');
    });
}

function cargarCaja() {
    fetch('index.php?pg=reports&action=cashRegister&ajax=1', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('loading-message').classList.add('d-none');

        if (data.error) {
            document.getElementById('no-caja-message').classList.remove('d-none');
            return;
        }

        const resumen = data.resumen;
        
        // Mostrar KPIs
        document.getElementById('kpi-container').style.display = 'flex';
        document.getElementById('kpi-monto-apertura').textContent = `$${Number(resumen.montoApertura || 0).toLocaleString('es-CL')}`;
        document.getElementById('kpi-total-ingresos').textContent = `$${Number(resumen.totalIngresos || 0).toLocaleString('es-CL')}`;
        document.getElementById('kpi-total-egresos').textContent = `$${Number(resumen.totalEgresos || 0).toLocaleString('es-CL')}`;
        document.getElementById('kpi-efectivo-caja').textContent = `$${Number(resumen.efectivoActual || 0).toLocaleString('es-CL')}`;

        // Mostrar desglose
        document.getElementById('desglose-section').style.display = 'block';

        // Tabla ingresos
        const tbodyIngresos = document.getElementById('tabla-ingresos');
        const ingresos = resumen.detalleIngresos || {};
        if (Object.keys(ingresos).length === 0) {
            tbodyIngresos.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Sin ingresos</td></tr>';
        } else {
            tbodyIngresos.innerHTML = Object.entries(ingresos).map(([tipo, monto]) => 
                `<tr><td>${tipo}</td><td class="text-end"><strong>$${Number(monto).toLocaleString('es-CL')}</strong></td></tr>`
            ).join('');
        }

        // Tabla egresos
        const tbodyEgresos = document.getElementById('tabla-egresos');
        const egresos = resumen.detalleEgresos || {};
        if (Object.keys(egresos).length === 0) {
            tbodyEgresos.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Sin egresos</td></tr>';
        } else {
            tbodyEgresos.innerHTML = Object.entries(egresos).map(([tipo, monto]) => 
                `<tr><td>${tipo}</td><td class="text-end"><strong>$${Number(monto).toLocaleString('es-CL')}</strong></td></tr>`
            ).join('');
        }
    })
    .catch(err => {
        console.error('Error cargando caja:', err);
        document.getElementById('loading-message').innerHTML = '<p class="text-danger">Error al cargar el reporte</p>';
    });
}

function cargarInventario() {
    fetch('index.php?pg=reports&action=inventoryReport&ajax=1', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('loading-message').classList.add('d-none');

        const alertas = data.alertas || [];
        const stockBajo = data.stockBajo || [];
        const valorInventario = data.valorInventario || {};

        // KPIs
        document.getElementById('kpi-container').style.display = 'flex';
        document.getElementById('kpi-alertas').textContent = alertas.length;
        document.getElementById('kpi-stock-bajo').textContent = stockBajo.length;
        
        const valorCompra = Number(valorInventario.valorCompra || 0);
        const valorVenta = Number(valorInventario.valorVenta || 0);
        document.getElementById('kpi-valor-inventario').textContent = `$${valorCompra.toLocaleString('es-CL')}`;

        // Tabla alertas críticas
        const tbodyAlertas = document.getElementById('tabla-alertas');
        document.getElementById('alertas-section').style.display = 'block';
        
        if (alertas.length === 0) {
            tbodyAlertas.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Sin alertas críticas</td></tr>';
        } else {
            tbodyAlertas.innerHTML = alertas.map(a => `
                <tr>
                    <td>${a.producto}</td>
                    <td class="text-danger"><strong>${a.stockActual}</strong></td>
                    <td>${new Date(a.fechaMovimiento).toLocaleString()}</td>
                    <td><span class="alert-badge alert-critical">⚠️ Stock Negativo</span></td>
                </tr>
            `).join('');
        }

        // Tabla stock bajo
        const tbodyStockBajo = document.getElementById('tabla-stock-bajo');
        document.getElementById('stock-bajo-section').style.display = 'block';
        
        if (stockBajo.length === 0) {
            tbodyStockBajo.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Todos los productos con stock adecuado</td></tr>';
        } else {
            tbodyStockBajo.innerHTML = stockBajo.map(p => `
                <tr>
                    <td>${p.producto}</td>
                    <td class="text-warning"><strong>${p.stockActual}</strong></td>
                    <td>${p.stockMinimo || 'No definido'}</td>
                    <td><span class="alert-badge alert-warning">⚠️ Stock Bajo</span></td>
                </tr>
            `).join('');
        }
    })
    .catch(err => {
        console.error('Error cargando inventario:', err);
        document.getElementById('loading-message').innerHTML = '<p class="text-danger">Error al cargar el reporte</p>';
    });
}

function cargarTopProductos() {
    const form = document.getElementById('filtrosReporte');
    const data = new FormData(form);

    fetch('index.php?pg=reports&action=topProducts&ajax=1', {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(data => {
        const productos = data.productos || [];

        if (productos.length === 0) {
            document.getElementById('tablaResultados').innerHTML = 
                '<tr><td colspan="5" class="text-center text-muted">No hay datos de ventas hoy</td></tr>';
            return;
        }

        // KPIs
        document.getElementById('kpi-container').style.display = 'flex';
        document.getElementById('kpi-top-producto').textContent = productos[0].nombre;
        
        const totalUnidades = productos.reduce((sum, p) => sum + Number(p.totalVendido), 0);
        const totalIngresos = productos.reduce((sum, p) => sum + Number(p.ingresoGenerado), 0);
        
        document.getElementById('kpi-total-unidades').textContent = totalUnidades.toLocaleString('es-CL');
        document.getElementById('kpi-total-ingresos').textContent = `$${totalIngresos.toLocaleString('es-CL')}`;

        // Tabla ranking
        const tbody = document.getElementById('tablaResultados');
        tbody.innerHTML = productos.map((p, idx) => {
            const pos = idx + 1;
            let badgeClass = 'ranking-other';
            if (pos === 1) badgeClass = 'ranking-1';
            else if (pos === 2) badgeClass = 'ranking-2';
            else if (pos === 3) badgeClass = 'ranking-3';

            return `
                <tr>
                    <td class="text-center">
                        <span class="ranking-badge ${badgeClass}">${pos}</span>
                    </td>
                    <td><strong>${p.nombre}</strong></td>
                    <td>${p.categoria || 'Sin categoría'}</td>
                    <td class="text-center"><strong>${Number(p.totalVendido).toLocaleString('es-CL')}</strong></td>
                    <td class="text-success"><strong>$${Number(p.ingresoGenerado).toLocaleString('es-CL')}</strong></td>
                </tr>
            `;
        }).join('');
    })
    .catch(err => {
        console.error('Error cargando top productos:', err);
        alert('Error al cargar el reporte');
    });
}

// CARGA INICIAL (ya está en DOMContentLoaded)

// ==================== BADGES: FILTROS ACTIVOS ====================
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function capitalize(str) {
    return String(str).charAt(0).toUpperCase() + String(str).slice(1);
}

function getFilterValues() {
    const form = document.getElementById('filtrosReporte');
    if (!form) return {};

    const filters = {};
    
    // Filtros comunes
    const idVentaInput = form.querySelector('[name="idVenta"]');
    const precioDesdInput = form.querySelector('[name="precioDesde"]');
    const precioHastaInput = form.querySelector('[name="precioHasta"]');
    const metodoPagoSelect = form.querySelector('[name="metodoPago"]');
    const idProveedorSelect = form.querySelector('[name="idProveedor"]');
    const tipoCompraSelect = form.querySelector('[name="tipoCompra"]');
    const tipoGastoSelect = form.querySelector('[name="tipo"]');
    
    if (idVentaInput) filters.idVenta = idVentaInput.value.trim();
    if (precioDesdInput) filters.precioDesde = precioDesdInput.value.trim();
    if (precioHastaInput) filters.precioHasta = precioHastaInput.value.trim();
    if (metodoPagoSelect) filters.metodoPago = metodoPagoSelect.value.trim();
    if (idProveedorSelect) filters.idProveedor = idProveedorSelect.value.trim();
    if (tipoCompraSelect) filters.tipoCompra = tipoCompraSelect.value.trim();
    if (tipoGastoSelect) filters.tipo = tipoGastoSelect.value.trim();

    const selectFecha = document.getElementById('select');
    if (selectFecha) filters.fecha = selectFecha.value || '';

    return filters;
}

function renderActiveFilters() {
    const cont = document.getElementById('active-filters');
    const list = document.getElementById('active-filters-list');
    if (!cont || !list) return;

    const filters = getFilterValues();
    const { idVenta, precioDesde, precioHasta, fecha, metodoPago, idProveedor, tipoCompra, tipo } = filters;

    const badges = [];
    if (idVenta) badges.push(`<span class="filter-badge">ID: ${escapeHtml(idVenta)}</span>`);
    if (precioDesde) badges.push(`<span class="filter-badge">Desde: $${Number(precioDesde).toLocaleString('es-CL')}</span>`);
    if (precioHasta) badges.push(`<span class="filter-badge">Hasta: $${Number(precioHasta).toLocaleString('es-CL')}</span>`);
    if (fecha && fecha !== 'custom') badges.push(`<span class="filter-badge">📅 ${escapeHtml(fecha)}</span>`);
    if (metodoPago) badges.push(`<span class="filter-badge">💳 ${escapeHtml(capitalize(metodoPago))}</span>`);
    if (idProveedor) {
        const proveedorSelect = document.querySelector('[name="idProveedor"]');
        const proveedorText = proveedorSelect ? proveedorSelect.options[proveedorSelect.selectedIndex].text : idProveedor;
        badges.push(`<span class="filter-badge">🏪 ${escapeHtml(proveedorText)}</span>`);
    }
    if (tipoCompra) badges.push(`<span class="filter-badge">📦 ${escapeHtml(capitalize(tipoCompra))}</span>`);
    if (tipo) badges.push(`<span class="filter-badge">🏷️ ${escapeHtml(capitalize(tipo))}</span>`);

    if (badges.length) {
        cont.style.display = 'block';
        list.innerHTML = badges.join(' ');
    } else {
        cont.style.display = 'none';
        list.innerHTML = '';
    }
}