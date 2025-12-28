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

        onClose: function (_, dateStr) {

            if (!dateStr) return;

            const rango = dateStr.replace(' to ', ' - ');

            let option = document.getElementById('dynamic-range');

            if (!option) {
                option = document.createElement('option');
                option.id = 'dynamic-range';
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
    })
    .catch(() => {
        document.getElementById('tablaResultados').innerHTML = `
            <tr><td colspan="5" class="text-danger text-center">Error cargando datos</td></tr>`;
            
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

function renderPaginacion(total, actual) {
    const cont = document.getElementById('paginacion');
    cont.innerHTML = '';

    if (total <= 1) return;

    let html = '<ul class="pagination">';

    for (let i = 1; i <= total; i++) {
        html += `
            <li class="page-item ${i === actual ? 'active' : ''}">
                <a class="page-link" href="#" onclick="irPagina(${i})">${i}</a>
            </li>`;
    }

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