<?php
require loadView('Layouts/header');

if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    echo '<div class="container mt-5"><div class="alert alert-danger">Acceso restringido a administradores.</div></div>';
    require loadView('Layouts/Footer');
    return;
}
?>
<link rel="stylesheet" href="<?= asset('assets/css/dashboard.css') ?>">

<div class="app-root dashboard-page">
    <div class="dash-wrap">

        <div class="dash-head">
            <div>
                <h1>Dashboard</h1>
                <p id="dash-fecha">Cargando…</p>
            </div>
            <button class="btn btn-outline-secondary btn-sm" id="dash-refresh">
                <i class="fa-solid fa-rotate"></i> Actualizar
            </button>
        </div>

        <!-- Estado de caja -->
        <div id="dash-caja" class="dash-caja"></div>

        <!-- KPIs -->
        <div class="dash-kpis">
            <div class="dash-kpi kpi-ventas">
                <div class="dash-kpi-icon"><i class="fa-solid fa-sack-dollar"></i></div>
                <div class="dash-kpi-body">
                    <span class="dash-kpi-label">Ventas de hoy</span>
                    <span class="dash-kpi-value" id="k-ventas-hoy">$0</span>
                    <span class="dash-kpi-delta" id="k-delta-ayer">vs ayer</span>
                </div>
            </div>
            <div class="dash-kpi kpi-tickets">
                <div class="dash-kpi-icon"><i class="fa-solid fa-receipt"></i></div>
                <div class="dash-kpi-body">
                    <span class="dash-kpi-label">Ventas (n.º)</span>
                    <span class="dash-kpi-value" id="k-num-hoy">0</span>
                    <span class="dash-kpi-delta" id="k-ticket">Ticket $0</span>
                </div>
            </div>
            <div class="dash-kpi kpi-mes">
                <div class="dash-kpi-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="dash-kpi-body">
                    <span class="dash-kpi-label">Acumulado del mes</span>
                    <span class="dash-kpi-value" id="k-mes">$0</span>
                    <span class="dash-kpi-delta" id="k-mes-util">Utilidad $0</span>
                </div>
            </div>
            <div class="dash-kpi kpi-util">
                <div class="dash-kpi-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
                <div class="dash-kpi-body">
                    <span class="dash-kpi-label">Utilidad de hoy</span>
                    <span class="dash-kpi-value" id="k-util-hoy">$0</span>
                    <span class="dash-kpi-delta" id="k-margen">Margen 0%</span>
                </div>
            </div>
        </div>

        <!-- Fila: tendencia 7 días + métodos de pago -->
        <div class="dash-row">
            <div class="dash-card dash-col-2">
                <div class="dash-card-head">
                    <h3>Ventas últimos 7 días</h3>
                </div>
                <div class="dash-bars" id="dash-serie7"></div>
            </div>

            <div class="dash-card">
                <div class="dash-card-head">
                    <h3>Cómo pagaron hoy</h3>
                </div>
                <div class="dash-donut-wrap">
                    <div class="dash-donut" id="dash-donut"></div>
                    <div class="dash-donut-legend">
                        <div><span class="dot dot-efectivo"></span> Efectivo <strong id="leg-efectivo">$0</strong></div>
                        <div><span class="dot dot-transfer"></span> Transferencias <strong id="leg-transfer">$0</strong></div>
                        <div class="leg-sub"><span class="dot dot-banco"></span> Bancolombia <strong id="leg-banco">$0</strong></div>
                        <div class="leg-sub"><span class="dot dot-nequi"></span> Nequi <strong id="leg-nequi">$0</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila: top productos + horas -->
        <div class="dash-row">
            <div class="dash-card">
                <div class="dash-card-head">
                    <h3>Top productos hoy</h3>
                </div>
                <div id="dash-top"></div>
            </div>

            <div class="dash-card dash-col-2">
                <div class="dash-card-head">
                    <h3>Ventas por hora (hoy)</h3>
                </div>
                <div class="dash-hours" id="dash-horas"></div>
            </div>
        </div>

        <!-- Fila: ventas recientes + alertas stock -->
        <div class="dash-row">
            <div class="dash-card dash-col-2">
                <div class="dash-card-head">
                    <h3>Ventas recientes</h3>
                    <a href="?pg=reports&action=sales" class="dash-link">Ver todas</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm dash-table mb-0">
                        <thead>
                            <tr><th>#</th><th>Hora</th><th>Cajero</th><th>Pago</th><th class="text-end">Total</th></tr>
                        </thead>
                        <tbody id="dash-recientes"></tbody>
                    </table>
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-card-head">
                    <h3>Inventario</h3>
                    <a href="?pg=inventory" class="dash-link">Gestionar</a>
                </div>
                <div class="dash-stock-kpis">
                    <div><span id="st-agotados" class="text-danger">0</span><small>Agotados</small></div>
                    <div><span id="st-bajos" class="text-warning">0</span><small>Stock bajo</small></div>
                    <div><span id="st-valor">$0</span><small>Valor (costo)</small></div>
                </div>
                <div id="dash-reponer" class="dash-reponer"></div>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    const money = (n) => '$' + Number(n || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 });
    const num = (n) => Number(n || 0).toLocaleString('es-CO');
    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    const $ = (id) => document.getElementById(id);
    const diaCorto = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

    function fmtHora(f) {
        const h = String(f).split(' ')[1];
        return h ? h.slice(0,5) : '';
    }

    function cargar() {
        fetch('index.php?pg=dashboard&action=data')
            .then(r => r.json())
            .then(pintar)
            .catch(e => console.error('[DASH]', e));
    }

    function pintar(d) {
        if (!d.success) return;

        $('dash-fecha').textContent = 'Datos al ' + new Date().toLocaleString('es-CO');

        // --- KPIs ---
        const hoy = d.hoy, ayer = d.ayer, mes = d.mes;
        $('k-ventas-hoy').textContent = money(hoy.totalVendido);
        $('k-num-hoy').textContent = num(hoy.cantidadVentas);
        $('k-ticket').textContent = 'Ticket ' + money(hoy.ticketPromedio);
        $('k-mes').textContent = money(mes.totalVendido);
        $('k-mes-util').textContent = 'Utilidad ' + money(mes.utilidadNeta);
        $('k-util-hoy').textContent = money(hoy.utilidadNeta);
        $('k-margen').textContent = 'Margen ' + Number(hoy.margenPorcentaje || 0).toFixed(1) + '%';

        // Delta vs ayer
        const deltaEl = $('k-delta-ayer');
        const va = Number(hoy.totalVendido), vb = Number(ayer.totalVendido);
        if (vb > 0) {
            const pct = ((va - vb) / vb) * 100;
            const up = pct >= 0;
            deltaEl.innerHTML = `<i class="fa-solid fa-arrow-${up ? 'up' : 'down'}"></i> ${Math.abs(pct).toFixed(0)}% vs ayer`;
            deltaEl.className = 'dash-kpi-delta ' + (up ? 'delta-up' : 'delta-down');
        } else {
            deltaEl.textContent = 'Sin datos de ayer';
            deltaEl.className = 'dash-kpi-delta';
        }

        // --- Caja ---
        const cajaEl = $('dash-caja');
        if (d.caja && d.caja.abierta) {
            cajaEl.className = 'dash-caja caja-abierta';
            cajaEl.innerHTML = `<i class="fa-solid fa-lock-open"></i>
                <span>Caja abierta desde ${fmtHora(d.caja.fechaApertura)}</span>
                <strong>Efectivo en caja: ${money(d.caja.efectivoActual)}</strong>`;
        } else {
            cajaEl.className = 'dash-caja caja-cerrada';
            cajaEl.innerHTML = `<i class="fa-solid fa-lock"></i> <span>No hay caja abierta</span>`;
        }

        // --- Serie 7 días (barras verticales) ---
        pintarSerie7(d.serie7 || []);

        // --- Donut métodos de pago ---
        window.__hoyBanco = Number(hoy.totalBancolombia || 0);
        window.__hoyNequi = Number(hoy.totalNequi || 0);
        pintarDonut(Number(hoy.totalEfectivo), Number(hoy.totalTransferencia));

        // --- Top productos ---
        pintarTop(d.topHoy || []);

        // --- Horas ---
        pintarHoras(d.horasHoy || []);

        // --- Ventas recientes ---
        pintarRecientes(d.ventasRecientes || []);

        // --- Stock ---
        pintarStock(d.stock || {});
    }

    function pintarSerie7(serie) {
        // Rellenar los 7 días aunque no haya ventas
        const map = {};
        serie.forEach(s => map[s.periodo] = s);
        const dias = [];
        for (let i = 6; i >= 0; i--) {
            const dt = new Date(); dt.setDate(dt.getDate() - i);
            const key = dt.toISOString().slice(0, 10);
            const s = map[key] || { periodo: key, totalVendido: 0, cantidadVentas: 0 };
            dias.push({ dt, total: Number(s.totalVendido), ventas: Number(s.cantidadVentas) });
        }
        const max = Math.max(...dias.map(d => d.total), 1);
        $('dash-serie7').innerHTML = dias.map(d => {
            const pct = (d.total / max) * 100;
            const hoy = d.dt.toDateString() === new Date().toDateString();
            return `<div class="dash-bar-col">
                <div class="dash-bar-value">${d.total > 0 ? money(d.total) : ''}</div>
                <div class="dash-bar-track">
                    <div class="dash-bar-fill ${hoy ? 'is-today' : ''}" style="height:${pct.toFixed(1)}%"></div>
                </div>
                <div class="dash-bar-label">${diaCorto[d.dt.getDay()]}<br><small>${d.dt.getDate()}</small></div>
            </div>`;
        }).join('');
    }

    function pintarDonut(efectivo, transfer) {
        const total = efectivo + transfer;
        const pctE = total > 0 ? (efectivo / total) * 100 : 0;
        const donut = $('dash-donut');
        if (total > 0) {
            donut.style.background =
                `conic-gradient(var(--success) 0 ${pctE}%, var(--info) ${pctE}% 100%)`;
            donut.innerHTML = `<div class="dash-donut-center">${pctE.toFixed(0)}%<small>efectivo</small></div>`;
        } else {
            donut.style.background = 'var(--bg-light)';
            donut.innerHTML = `<div class="dash-donut-center"><small>Sin ventas</small></div>`;
        }
        $('leg-efectivo').textContent = money(efectivo);
        $('leg-transfer').textContent = money(transfer);
        if ($('leg-banco')) $('leg-banco').textContent = money(window.__hoyBanco || 0);
        if ($('leg-nequi')) $('leg-nequi').textContent = money(window.__hoyNequi || 0);
    }

    function pintarTop(top) {
        const cont = $('dash-top');
        if (!top.length) { cont.innerHTML = '<p class="text-muted text-center py-3">Sin ventas hoy</p>'; return; }
        const max = Math.max(...top.map(t => Number(t.totalVendido)), 1);
        cont.innerHTML = top.map((t, i) => {
            const pct = (Number(t.totalVendido) / max) * 100;
            return `<div class="dash-top-item">
                <span class="dash-top-rank">${i + 1}</span>
                <div class="dash-top-info">
                    <div class="dash-top-name">${esc(t.nombre)}</div>
                    <div class="dash-top-track"><div class="dash-top-fill" style="width:${pct}%"></div></div>
                </div>
                <div class="dash-top-val">${money(t.totalVendido)}<small>${num(t.cantidadVendida)} u.</small></div>
            </div>`;
        }).join('');
    }

    function pintarHoras(horas) {
        const activas = horas.filter(h => Number(h.totalVendido) > 0 || (h.hora >= 6 && h.hora <= 22));
        const max = Math.max(...horas.map(h => Number(h.totalVendido)), 1);
        $('dash-horas').innerHTML = activas.map(h => {
            const pct = (Number(h.totalVendido) / max) * 100;
            return `<div class="dash-hour-col" title="${money(h.totalVendido)} · ${num(h.cantidadVentas)} ventas">
                <div class="dash-hour-track"><div class="dash-hour-fill" style="height:${pct.toFixed(1)}%"></div></div>
                <div class="dash-hour-label">${String(h.hora).padStart(2,'0')}</div>
            </div>`;
        }).join('');
    }

    function pintarRecientes(ventas) {
        const tb = $('dash-recientes');
        if (!ventas.length) { tb.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Sin ventas</td></tr>'; return; }
        tb.innerHTML = ventas.map(v => `<tr>
            <td>#${esc(v.idVenta)}</td>
            <td>${fmtHora(v.fechaVenta)}</td>
            <td>${esc(v.usuario || '—')}</td>
            <td><span class="dash-pago dash-pago-${esc(v.metodoPago)}">${esc(v.metodoPago)}</span></td>
            <td class="text-end fw-bold">${money(v.total)}</td>
        </tr>`).join('');
    }

    function pintarStock(stock) {
        $('st-agotados').textContent = num(stock.productosAgotados);
        $('st-bajos').textContent = num(stock.productosBajos);
        $('st-valor').textContent = money(stock.valorCosto);
        const detalle = stock.detalleReponer || [];
        const cont = $('dash-reponer');
        if (!detalle.length) { cont.innerHTML = '<p class="text-muted text-center py-2">Todo con stock suficiente ✔</p>'; return; }
        cont.innerHTML = '<div class="dash-reponer-title">Hay que reponer</div>' + detalle.slice(0, 6).map(dd => {
            const agotado = Number(dd.stockActual) <= 0;
            return `<div class="dash-reponer-item">
                <span>${esc(dd.producto)}</span>
                <span class="${agotado ? 'text-danger fw-bold' : 'text-warning'}">${num(dd.stockActual)} ${esc(dd.unidadAbreviatura || '')}</span>
            </div>`;
        }).join('');
    }

    $('dash-refresh').addEventListener('click', cargar);
    cargar();
    // Auto-refresco cada 60 s para que el cajero vea el dashboard "vivo"
    setInterval(cargar, 60000);
})();
</script>

<?php require loadView('Layouts/Footer'); ?>
