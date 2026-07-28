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

function formatCurrency(value) {
  return new Intl.NumberFormat('es-CO').format(value);
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
    if (tipo === 'inventory') {
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
    
    // Para reporte de sales, siempre mostrar todas las ventas (incluidas anuladas)
    if (tipo === 'sales') {
        data.append('mostrarTodas', 1);
    }

    fetch(`index.php?pg=reports&action=${tipo}&ajax=1`, {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(data => {
        console.log(data);  
        renderTabla(data.resultados, tipo);
        renderTotalesRow(data.totales, tipo);
        renderPaginacion(data.totalPaginas, data.paginaActual);
        if (typeof renderActiveFilters === 'function') renderActiveFilters();
        actualizarKPIs(data, tipo);
    })
    .catch(() => {
        const tbody = document.getElementById('tablaResultados');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-danger text-center">Error cargando datos</td></tr>`;
        }
        renderTotalesRow(null, tipo);
        actualizarKPIs({ resultados: [], totalPaginas: 0, paginaActual: 1 }, tipo);
    });
}

function renderTabla(rows, tipo = 'sales') {
    const tbody = document.getElementById('tablaResultados');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!rows || rows.length === 0) {
        const colSpan = tipo === 'sales' ? 6 : tipo === 'purchases' ? 5 : 5;
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center text-muted">No hay resultados</td></tr>`;
        return;
    }

    if (tipo === 'sales') {
        rows.forEach(r => {
            // Determinar badge de estado con colores
            let estadoBadge = '<span class="badge bg-secondary">Desconocido</span>';
            if (r.estado === 'completada') {
                estadoBadge = '<span class="badge bg-success">✓ Completada</span>';
            } else if (r.estado === 'cancelada') {
                estadoBadge = '<span class="badge bg-danger">✕ Anulada</span>';
            } else if (r.estado === 'pendiente') {
                estadoBadge = '<span class="badge bg-info">⏳ Pendiente</span>';
            }
            
            tbody.innerHTML += `
                <tr>
                    <td>${r.idVenta}</td>
                    <td>${new Date(r.fechaVenta).toLocaleString()}</td>
                    <td>${r.metodoPago}</td>
                    <td>${estadoBadge}</td>
                    <td>$${Number(r.total).toLocaleString()}</td>
                    <td>
                        <button class="btn btn-sm btn-info"
                            onclick="detail(${r.idVenta})">
                            Ver detalles
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
                    <td>
                        <button class="btn btn-sm btn-info" onclick="window.purchaseDetail(${r.idCompra})">
                            Ver detalles
                        </button>
                    </td>
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
                    <td>${new Date(r.fechaRegistro).toLocaleString()}</td>
                    <td>$${Number(r.monto).toLocaleString()}</td>
                </tr>`;
        });
    }
}

function calcularTotales(rows, tipo = 'sales') {
    let totalVendido = 0;
    let totalVentas = 0;
    let totalAnulado = 0;
    let cantidadAnuladas = 0;
    let totalComprado = 0;
    let totalCompras = 0;
    let totalGastos = 0;
    let cantidadGastos = 0;

    if (Array.isArray(rows)) {
        for (let i = 0; i < rows.length; i++) {
            const item = rows[i] || {};

            if (tipo === 'sales') {
                const monto = Number(item.total) || 0;
                // Las ventas anuladas se listan en la tabla pero NO cuentan como
                // dinero vendido: se totalizan aparte.
                if (item.estado === 'cancelada') {
                    totalAnulado += monto;
                    cantidadAnuladas += 1;
                } else {
                    totalVendido += monto;
                    totalVentas += 1;
                }
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

    return { totalVendido, totalVentas, totalAnulado, cantidadAnuladas, totalComprado, totalCompras, totalGastos, cantidadGastos };
}

// Los KPIs y la fila de totales usan los totales que YA calculó el servidor
// sobre todo el conjunto filtrado (data.totales). Antes se recorrían todas las
// páginas con un fetch por cada una (lento y frágil); ahora es una sola pasada.
function actualizarKPIs(data, tipo = 'sales') {
    const t = (data && data.totales) ? data.totales : null;
    const money = v => `$${(Number(v) || 0).toLocaleString('es-CO')}`;
    const num = v => (Number(v) || 0).toLocaleString('es-CO');
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

    if (tipo === 'sales') {
        const vals = t
            ? { totalVendido: +t.totalVendido || 0, totalVentas: +t.cantidadVentas || 0,
                totalAnulado: +t.totalAnulado || 0, cantidadAnuladas: +t.cantidadAnuladas || 0 }
            : calcularTotales(data.resultados || [], tipo);
        set('kpi-total-vendido', money(vals.totalVendido));
        set('kpi-total-ventas', num(vals.totalVentas));
        set('kpi-total-anulado', money(vals.totalAnulado));
        set('kpi-cantidad-anuladas', num(vals.cantidadAnuladas));
    } else if (tipo === 'purchases') {
        const vals = t
            ? { totalComprado: +t.totalComprado || 0, totalCompras: +t.cantidadCompras || 0 }
            : calcularTotales(data.resultados || [], tipo);
        set('kpi-total-comprado', money(vals.totalComprado));
        set('kpi-total-compras', num(vals.totalCompras));
    } else if (tipo === 'expenses') {
        const vals = t
            ? { totalGastos: +t.totalGastos || 0, cantidadGastos: +t.cantidadGastos || 0 }
            : calcularTotales(data.resultados || [], tipo);
        set('kpi-total-gastos', money(vals.totalGastos));
        set('kpi-cantidad-gastos', num(vals.cantidadGastos));
    }
}

// Fila de totales al pie de la tabla: refleja TODO lo consultado según los
// filtros (día o rango de fechas), no solo la página que se ve.
function renderTotalesRow(totales, tipo = 'sales') {
    const tfoot = document.getElementById('tfootTotales');
    if (!tfoot) return;
    if (!totales) { tfoot.innerHTML = ''; return; }

    const money = v => `$${(Number(v) || 0).toLocaleString('es-CO')}`;
    const num = v => (Number(v) || 0).toLocaleString('es-CO');

    if (tipo === 'sales') {
        let html = `
            <tr class="fila-totales">
                <td colspan="4" class="text-end">TOTAL vendido (completadas):</td>
                <td>${money(totales.totalVendido)}</td>
                <td class="totales-meta">${num(totales.cantidadVentas)} ventas</td>
            </tr>
            <tr class="fila-totales-desglose">
                <td colspan="4" class="text-end">Desglose por método</td>
                <td colspan="2" class="totales-desglose">
                    💵 Efectivo ${money(totales.totalEfectivo)}
                    &nbsp;·&nbsp; 🏦 Bancolombia ${money(totales.totalBancolombia)}
                    &nbsp;·&nbsp; 📱 Nequi ${money(totales.totalNequi)}
                    &nbsp;|&nbsp; <strong>Transferencias ${money(totales.totalTransferencia)}</strong>
                </td>
            </tr>`;
        if (Number(totales.totalAnulado) > 0) {
            html += `
            <tr class="fila-totales-anulado">
                <td colspan="4" class="text-end">Anulado (no cuenta):</td>
                <td>${money(totales.totalAnulado)}</td>
                <td class="totales-meta">${num(totales.cantidadAnuladas)} anuladas</td>
            </tr>`;
        }
        tfoot.innerHTML = html;
    } else if (tipo === 'purchases') {
        tfoot.innerHTML = `
            <tr class="fila-totales">
                <td colspan="4" class="text-end">TOTAL comprado:</td>
                <td>${money(totales.totalComprado)}</td>
                <td class="totales-meta">${num(totales.cantidadCompras)} compras</td>
            </tr>`;
    } else if (tipo === 'expenses') {
        tfoot.innerHTML = `
            <tr class="fila-totales">
                <td colspan="3" class="text-end">TOTAL gastos:</td>
                <td class="totales-meta">${num(totales.cantidadGastos)} gastos</td>
                <td>${money(totales.totalGastos)}</td>
            </tr>`;
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