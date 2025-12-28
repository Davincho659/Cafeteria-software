document.addEventListener('DOMContentLoaded', function () {

    const fechasInput = document.getElementById('fechas');
    const selectFecha = document.getElementById('select');

    const fp = flatpickr(fechasInput, {
        mode: "range",
        maxDate: "today",
        dateFormat: "d/m/Y",
        locale: "es",
        showMonths: 2,
        appendTo: fechasInput.parentElement, // 🔥 CLAVE

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

    
    // Render inicial
    renderActiveFilters();
});

let paginaActual = 1;

document.getElementById('filtrosReporte').addEventListener('submit', e => {
    e.preventDefault();
    paginaActual = 1;
    cargarResultados();
});

function cargarResultados(page = paginaActual) {

    const form = document.getElementById('filtrosReporte');
    const data = new FormData(form);
    data.append('page', page);


    fetch('index.php?pg=reports&action=sales&ajax=1', {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(data => {
        console.log(data);  
        renderTabla(data.resultados);
        renderPaginacion(data.totalPaginas, data.paginaActual);
        renderActiveFilters();
        actualizarKPIs(data);
    })
    .catch(() => {
        document.getElementById('tablaResultados').innerHTML = `
            <tr><td colspan="5" class="text-danger text-center">Error cargando datos</td></tr>`;
        actualizarKPIs({ resultados: [], totalPaginas: 0, paginaActual: 1 });
            
    });
}

function renderTabla(rows) {
    const tbody = document.getElementById('tablaResultados');
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
}

function calcularTotales(rows) {
    let totalVendido = 0;
    let totalVentas = 0;

    if (Array.isArray(rows)) {
        for (let i = 0; i < rows.length; i++) {
            const venta = rows[i] || {};
            const monto = Number(venta.total) || 0;
            totalVendido += monto;
            totalVentas += 1;
        }
    }

    return { totalVendido, totalVentas };
}

async function actualizarKPIs(data) {
    const totalVendidoEl = document.getElementById('kpi-total-vendido');
    const totalVentasEl = document.getElementById('kpi-total-ventas');
    if (!totalVendidoEl || !totalVentasEl) return;

    const totalPaginas = Number(data.totalPaginas) || 0;
    const paginaActual = Number(data.paginaActual) || 1;

    // Totales de la página visible
    let { totalVendido, totalVentas } = calcularTotales(data.resultados || []);
    totalVendidoEl.textContent = `$${totalVendido.toLocaleString('es-CL')}`;
    totalVentasEl.textContent = totalVentas.toLocaleString('es-CL');

    // Si solo hay una página, ya está listo
    if (totalPaginas <= 1) return;

    const formBase = document.getElementById('filtrosReporte');
    if (!formBase) return;

    // Acumular el resto de páginas con los mismos filtros
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
            const totales = calcularTotales(json.resultados || []);
            totalVendido += totales.totalVendido;
            totalVentas += totales.totalVentas;
        } catch (e) {
            console.warn('No se pudieron cargar KPIs de la página', p, e);
        }
    }

    totalVendidoEl.textContent = `$${totalVendido.toLocaleString('es-CL')}`;
    totalVentasEl.textContent = totalVentas.toLocaleString('es-CL');
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
    document.getElementById('filtrosReporte').reset();
    paginaActual = 1;
    cargarResultados();
    // Limpiar opción dinámica de fecha si existe
    const opt = document.getElementById('dynamic-range');
    if (opt) opt.remove();
    renderActiveFilters();
}

// CARGA INICIAL
cargarResultados();

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
    const idVenta = form.querySelector('[name="idVenta"]').value.trim();
    const precioDesde = form.querySelector('[name="precioDesde"]').value.trim();
    const precioHasta = form.querySelector('[name="precioHasta"]').value.trim();
    const metodoPago = form.querySelector('[name="metodoPago"]').value.trim();
    const fecha = document.getElementById('select')?.value || '';
    return { idVenta, precioDesde, precioHasta, fecha, metodoPago };
}

function renderActiveFilters() {
    const cont = document.getElementById('active-filters');
    const list = document.getElementById('active-filters-list');
    if (!cont || !list) return;

    const { idVenta, precioDesde, precioHasta, fecha, metodoPago } = getFilterValues();

    const badges = [];
    if (idVenta) badges.push(`<span class="filter-badge">ID: ${escapeHtml(idVenta)}</span>`);
    if (precioDesde) badges.push(`<span class="filter-badge">Desde: $${Number(precioDesde).toLocaleString('es-CL')}</span>`);
    if (precioHasta) badges.push(`<span class="filter-badge">Hasta: $${Number(precioHasta).toLocaleString('es-CL')}</span>`);
    if (fecha && fecha !== 'custom') badges.push(`<span class="filter-badge">📅 ${escapeHtml(fecha)}</span>`);
    if (metodoPago) badges.push(`<span class="filter-badge">💳 ${escapeHtml(capitalize(metodoPago))}</span>`);

    if (badges.length) {
        cont.style.display = 'block';
        list.innerHTML = badges.join(' ');
    } else {
        cont.style.display = 'none';
        list.innerHTML = '';
    }
}