/**
 * ============================================================================
 * CLOSINGS.JS - Cierres diario / mensual / anual
 * ============================================================================
 * Un solo archivo para los tres cierres: el backend devuelve siempre la misma
 * estructura y solo cambia el parámetro del periodo.
 * ============================================================================
 */

const money = (n) => '$' + Number(n || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 });
const num = (n) => Number(n || 0).toLocaleString('es-CO');

function escapeHtmlClosing(str) {
    return String(str == null ? '' : str).replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[c]);
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('closingForm');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        cargarCierre();
    });

    cargarCierre();
});

function cargarCierre() {
    const tipo = typeof cierreTipo !== 'undefined' ? cierreTipo : 'closingDaily';
    const form = document.getElementById('closingForm');

    const loading = document.getElementById('closing-loading');
    const content = document.getElementById('closing-content');
    const errorBox = document.getElementById('closing-error');

    loading.style.display = 'block';
    content.style.display = 'none';
    errorBox.classList.add('d-none');

    fetch(`index.php?pg=reports&action=${tipo}&ajax=1`, {
        method: 'POST',
        body: new FormData(form)
    })
        .then((r) => r.json())
        .then((data) => {
            loading.style.display = 'none';

            if (!data.success) {
                errorBox.textContent = data.error || 'No se pudo calcular el cierre';
                errorBox.classList.remove('d-none');
                return;
            }

            pintarCierre(data);
            content.style.display = 'block';
        })
        .catch((err) => {
            console.error('[CIERRE] Error:', err);
            loading.style.display = 'none';
            errorBox.textContent = 'Error de conexión al calcular el cierre';
            errorBox.classList.remove('d-none');
        });
}

function pintarCierre(data) {
    const r = data.resumen || {};

    document.getElementById('closing-titulo').textContent = data.titulo || '';

    // --- KPIs ---
    document.getElementById('c-total-vendido').textContent = money(r.totalVendido);
    document.getElementById('c-cantidad-ventas').textContent = num(r.cantidadVentas) + ' ventas';
    document.getElementById('c-ticket-promedio').textContent = money(r.ticketPromedio);
    document.getElementById('c-total-anulado').textContent = money(r.totalAnulado);
    document.getElementById('c-cantidad-anuladas').textContent = num(r.cantidadAnuladas) + ' facturas';

    const utilidadEl = document.getElementById('c-utilidad-neta');
    utilidadEl.textContent = money(r.utilidadNeta);
    utilidadEl.className = 'kpi-value ' + (Number(r.utilidadNeta) >= 0 ? 'text-success' : 'text-danger');
    document.getElementById('c-margen').textContent =
        'Margen ' + Number(r.margenPorcentaje || 0).toFixed(1) + '%';

    // --- Estado de resultados ---
    document.getElementById('r-ventas').textContent = money(r.totalVendido);
    document.getElementById('r-costo').textContent = money(r.costoVentas);
    document.getElementById('r-utilidad-bruta').textContent = money(r.utilidadBruta);
    document.getElementById('r-gastos').textContent = money(r.totalGastos);
    document.getElementById('r-utilidad-neta').textContent = money(r.utilidadNeta);
    document.getElementById('r-compras').textContent = money(r.totalCompras);

    // --- Métodos de pago ---
    const efectivo = Number(r.totalEfectivo || 0);
    const bancolombia = Number(r.totalBancolombia || 0);
    const nequi = Number(r.totalNequi || 0);
    const transferencia = Number(r.totalTransferencia || 0); // banco + nequi (+ legacy)
    const totalPagos = efectivo + transferencia;

    document.getElementById('p-efectivo').textContent = money(efectivo);
    const banEl = document.getElementById('p-bancolombia'); if (banEl) banEl.textContent = money(bancolombia);
    const neqEl = document.getElementById('p-nequi'); if (neqEl) neqEl.textContent = money(nequi);
    document.getElementById('p-transferencia').textContent = money(transferencia);

    const pctEfectivo = totalPagos > 0 ? (efectivo / totalPagos) * 100 : 0;
    document.getElementById('p-barra-efectivo').style.width = pctEfectivo.toFixed(1) + '%';
    document.getElementById('p-proporcion').textContent = totalPagos > 0
        ? `${pctEfectivo.toFixed(0)}% efectivo · ${(100 - pctEfectivo).toFixed(0)}% transferencias`
        : 'Sin pagos registrados';

    pintarCajas(data.cajas || []);
    pintarSerie(data.serie || []);
    pintarTopProductos(data.topProductos || []);
    pintarStock(data.stock || {});
}

function pintarCajas(cajas) {
    const tbody = document.getElementById('tabla-cajas');

    if (!cajas.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Sin cajas en el periodo</td></tr>';
        return;
    }

    tbody.innerHTML = cajas.map((c) => {
        const dif = c.diferencia;
        let difTexto = '<span class="text-muted">Sin cerrar</span>';

        if (dif !== null && dif !== undefined) {
            // Se tolera un peso de redondeo para no marcar descuadres falsos.
            if (Math.abs(dif) < 1) {
                difTexto = '<span class="text-success">Cuadrada</span>';
            } else if (dif > 0) {
                difTexto = `<span class="text-warning">Sobra ${money(dif)}</span>`;
            } else {
                difTexto = `<span class="text-danger">Falta ${money(Math.abs(dif))}</span>`;
            }
        }

        // Dinero que entró por Nequi/Bancolombia. No está en el cajón: sirve
        // para confrontarlo contra el movimiento real de la cuenta bancaria.
        const transferencias = Number(c.totalTransferencias) || 0;
        const transferTexto = transferencias > 0
            ? `<span class="text-info fw-semibold">${money(transferencias)}</span>`
            : '<span class="text-muted">—</span>';

        return `<tr>
            <td>#${escapeHtmlClosing(c.idCaja)}</td>
            <td>${escapeHtmlClosing(c.usuario || '—')}</td>
            <td>${formatFecha(c.fechaApertura)}</td>
            <td>${c.fechaCierre ? formatFecha(c.fechaCierre) : '<span class="badge bg-success">Abierta</span>'}</td>
            <td class="text-end">${money(c.saldoInicial)}</td>
            <td class="text-end">${money(c.efectivoEsperado ?? c.saldoCalculado)}</td>
            <td class="text-end">${c.saldoReal === null ? '—' : money(c.saldoReal)}</td>
            <td class="text-end">${difTexto}</td>
            <td class="text-end">${transferTexto}</td>
        </tr>`;
    }).join('');
}

function pintarSerie(serie) {
    const cont = document.getElementById('closing-serie');

    if (!serie.length) {
        cont.innerHTML = '<p class="text-muted">Sin datos en el periodo</p>';
        return;
    }

    const max = Math.max(...serie.map((s) => Number(s.totalVendido) || 0)) || 1;

    cont.innerHTML = serie.map((s) => {
        const valor = Number(s.totalVendido) || 0;
        const pct = (valor / max) * 100;
        return `<div class="serie-row">
            <div class="serie-label">${escapeHtmlClosing(s.periodo)}</div>
            <div class="serie-track"><div class="serie-fill" style="width:${pct.toFixed(1)}%"></div></div>
            <div class="serie-value">${money(valor)}</div>
            <div class="serie-count">${num(s.cantidadVentas)} v.</div>
        </div>`;
    }).join('');
}

function pintarTopProductos(productos) {
    const tbody = document.getElementById('tabla-top-productos');

    if (!productos.length) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Sin ventas</td></tr>';
        return;
    }

    tbody.innerHTML = productos.map((p) => `<tr>
        <td>${escapeHtmlClosing(p.nombre)}
            ${p.categoria ? `<br><small class="text-muted">${escapeHtmlClosing(p.categoria)}</small>` : ''}
        </td>
        <td class="text-end">${num(p.cantidadVendida)}</td>
        <td class="text-end"><strong>${money(p.totalVendido)}</strong></td>
    </tr>`).join('');
}

function pintarStock(stock) {
    document.getElementById('s-valor-costo').textContent = money(stock.valorCosto);
    document.getElementById('s-valor-venta').textContent = money(stock.valorVenta);
    document.getElementById('s-agotados').textContent = num(stock.productosAgotados);
    document.getElementById('s-bajos').textContent = num(stock.productosBajos);

    const tbody = document.getElementById('tabla-reponer');
    const detalle = stock.detalleReponer || [];

    if (!detalle.length) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Todo con stock suficiente</td></tr>';
        return;
    }

    tbody.innerHTML = detalle.map((d) => {
        const agotado = Number(d.stockActual) <= 0;
        return `<tr>
            <td>${escapeHtmlClosing(d.producto)}</td>
            <td class="text-end ${agotado ? 'text-danger fw-bold' : 'text-warning'}">
                ${num(d.stockActual)} ${escapeHtmlClosing(d.unidadAbreviatura || '')}
            </td>
        </tr>`;
    }).join('');
}

function formatFecha(f) {
    if (!f) return '—';
    // "2026-07-23 14:05:00" -> "23/07/2026 14:05"
    const [fecha, hora] = String(f).split(' ');
    const [a, m, d] = fecha.split('-');
    return `${d}/${m}/${a}${hora ? ' ' + hora.slice(0, 5) : ''}`;
}
