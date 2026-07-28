<?php
// Reporte contable: consolida ventas, compras y gastos de un rango de fechas
// con totales, desglose por método de pago y utilidad. Pensado para enviar a
// la contadora (imprimir/PDF o exportar CSV).
require_once __DIR__ . '/../../Models/Settings.php';
$settings = new Settings();
$nombreNegocio = $settings->get('nombre_negocio', 'Mi Negocio');
?>
<link rel="stylesheet" href="<?= asset('assets/css/reports.css') ?>">

<style>
/* ===== Reporte contable ===== */
.acc-controls { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; }
.acc-controls .filter-group { margin: 0; }
.acc-presets { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.acc-preset {
    padding: 7px 14px; border-radius: 999px; border: 1px solid var(--border, #ddd);
    background: #f7f2ea; color: var(--brown-dark, #5B3411); font-weight: 600; cursor: pointer; font-size: .88rem;
}
.acc-preset:hover { background: #efe6d8; }
.acc-preset.active { background: var(--brown-dark, #5B3411); color: #fff; border-color: var(--brown-dark, #5B3411); }

.acc-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-left: auto; }
.acc-btn {
    padding: 10px 18px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; font-size: .95rem;
}
.acc-btn-primary { background: var(--brown-dark, #5B3411); color: #fff; }
.acc-btn-print   { background: #0d6efd; color: #fff; }
.acc-btn-csv     { background: #198754; color: #fff; }
.acc-btn:disabled { opacity: .5; cursor: not-allowed; }

.acc-report-header { display: none; }

/* Resumen ejecutivo */
.acc-summary-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-top: 18px;
}
.acc-card {
    border: 1px solid var(--border, #e5ddd0); border-radius: 14px; padding: 16px 18px; background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.acc-card .acc-card-label { font-size: .82rem; color: #6b6b6b; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
.acc-card .acc-card-value { font-size: 1.5rem; font-weight: 800; color: var(--brown-dark, #5B3411); margin-top: 6px; }
.acc-card.pos .acc-card-value { color: #157347; }
.acc-card.neg .acc-card-value { color: #b02a37; }
.acc-card.accent { background: var(--brown-dark, #5B3411); }
.acc-card.accent .acc-card-label,
.acc-card.accent .acc-card-value { color: #fff; }

.acc-section-title {
    margin: 30px 0 12px; padding-bottom: 8px; border-bottom: 2px solid var(--brown-dark, #5B3411);
    color: var(--brown-dark, #5B3411); font-size: 1.15rem; font-weight: 800;
}
.acc-methods { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); gap: 10px; margin-top: 8px; }
.acc-method { border: 1px solid var(--border,#e5ddd0); border-radius: 10px; padding: 10px 14px; background: #faf7f2; }
.acc-method .m-label { font-size: .82rem; color: #6b6b6b; font-weight: 600; }
.acc-method .m-value { font-size: 1.15rem; font-weight: 800; color: var(--brown-dark,#5B3411); }

.acc-table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: .92rem; }
.acc-table th, .acc-table td { border: 1px solid #e5ddd0; padding: 8px 10px; }
.acc-table thead th { background: var(--brown-dark, #5B3411); color: #fff; text-align: left; }
.acc-table tbody tr:nth-child(even) { background: #faf7f2; }
.acc-table tfoot td { background: #efe6d8; font-weight: 800; color: var(--brown-dark,#5B3411); }
.acc-table .text-end { text-align: right; }
.acc-table .text-center { text-align: center; }
.acc-empty { padding: 16px; text-align: center; color: #8a8a8a; }

.acc-loading { padding: 40px; text-align: center; color: #6b6b6b; }
.acc-anuladas summary { cursor: pointer; font-weight: 700; color: #b02a37; margin-top: 24px; }

@media print {
    /* Ocultar todo lo que no es el reporte */
    .reports-sidebar, .reports-menu-toggle, .navbar,
    .acc-filter-card, .acc-actions { display: none !important; }

    /* CLAVE: el layout usa alturas fijas (100vh), overflow y zoom para la
       pantalla. En papel eso recorta el reporte a UNA sola página. Aquí se
       liberan alturas/overflow y se quita el zoom para que fluya en varias
       páginas completas. */
    html, body {
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
        background: #fff !important;
    }
    body.body-container, body { zoom: 1 !important; }
    .reports-container, .reports-content {
        display: block !important;
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .container-fluid { width: 100% !important; max-width: none !important; }

    .acc-report-header { display: block !important; text-align: center; margin-bottom: 14px; }
    .acc-report-header h1 { font-size: 1.4rem; margin: 0; color: #000; }
    .acc-report-header p { margin: 2px 0; color: #333; font-size: .9rem; }

    /* Evitar cortes feos entre páginas */
    .acc-card, .acc-method { box-shadow: none; break-inside: avoid; }
    .acc-summary-grid, .acc-methods { break-inside: avoid; }
    .acc-table { font-size: .78rem; page-break-inside: auto; }
    .acc-table thead { display: table-header-group; } /* repite encabezado por página */
    .acc-table tr { page-break-inside: avoid; }
    .acc-section-title { page-break-after: avoid; }

    @page { margin: 12mm; }
}
</style>

<div class="container-fluid">

    <!-- Encabezado que solo se ve al imprimir/PDF -->
    <div class="acc-report-header">
        <h1><?= esc($nombreNegocio) ?></h1>
        <p>Reporte Contable</p>
        <p id="accPrintPeriodo"></p>
    </div>

    <!-- ================= CONTROLES ================= -->
    <div class="filter-card acc-filter-card">
        <h4 class="filter-section-title">📒 Reporte contable</h4>
        <p class="text-muted" style="margin-top:-6px;">
            Consolida ventas, compras y gastos del periodo para enviar a contabilidad.
        </p>

        <div class="acc-controls">
            <div class="filter-group">
                <label class="filter-label">Desde</label>
                <input type="date" id="accDesde" class="filter-input">
            </div>
            <div class="filter-group">
                <label class="filter-label">Hasta</label>
                <input type="date" id="accHasta" class="filter-input">
            </div>
            <button type="button" id="accGenerar" class="acc-btn acc-btn-primary">🔍 Generar</button>

            <div class="acc-actions">
                <button type="button" id="accImprimir" class="acc-btn acc-btn-print" disabled>🖨️ Imprimir / PDF</button>
                <button type="button" id="accCsv" class="acc-btn acc-btn-csv" disabled>📥 Descargar CSV</button>
            </div>
        </div>

        <div class="acc-presets" id="accPresets">
            <button type="button" class="acc-preset" data-preset="hoy">Hoy</button>
            <button type="button" class="acc-preset active" data-preset="mes">Este mes</button>
            <button type="button" class="acc-preset" data-preset="mesPasado">Mes pasado</button>
            <button type="button" class="acc-preset" data-preset="anio">Este año</button>
        </div>
    </div>

    <!-- ================= CONTENIDO ================= -->
    <div id="accContent">
        <div class="acc-loading">Selecciona un periodo y presiona <strong>Generar</strong>.</div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const money = v => '$' + (Number(v) || 0).toLocaleString('es-CO');
    const numf  = v => (Number(v) || 0).toLocaleString('es-CO');
    const elDesde = document.getElementById('accDesde');
    const elHasta = document.getElementById('accHasta');
    const content = document.getElementById('accContent');
    const btnGen  = document.getElementById('accGenerar');
    const btnPrint = document.getElementById('accImprimir');
    const btnCsv  = document.getElementById('accCsv');
    let ultimo = null; // último dataset cargado (para CSV)

    // ---- Fechas: formato YYYY-MM-DD ----
    const iso = d => d.toISOString().slice(0, 10);
    function fmtLargo(isoStr) {
        if (!isoStr) return '';
        const [y, m, d] = isoStr.split('-');
        return `${d}/${m}/${y}`;
    }

    // ---- Presets ----
    function aplicarPreset(preset) {
        const hoy = new Date();
        let desde, hasta;
        if (preset === 'hoy') {
            desde = hasta = new Date(hoy);
        } else if (preset === 'mes') {
            desde = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            hasta = new Date(hoy);
        } else if (preset === 'mesPasado') {
            desde = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
            hasta = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
        } else if (preset === 'anio') {
            desde = new Date(hoy.getFullYear(), 0, 1);
            hasta = new Date(hoy);
        }
        elDesde.value = iso(desde);
        elHasta.value = iso(hasta);
    }

    document.getElementById('accPresets').addEventListener('click', e => {
        const btn = e.target.closest('.acc-preset');
        if (!btn) return;
        document.querySelectorAll('.acc-preset').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        aplicarPreset(btn.dataset.preset);
        cargar();
    });

    // Si el usuario edita las fechas a mano, ningún preset queda activo.
    [elDesde, elHasta].forEach(inp => inp.addEventListener('change', () => {
        document.querySelectorAll('.acc-preset').forEach(b => b.classList.remove('active'));
    }));

    btnGen.addEventListener('click', cargar);
    btnPrint.addEventListener('click', () => window.print());
    btnCsv.addEventListener('click', descargarCsv);

    // ---- Carga de datos ----
    function cargar() {
        if (!elDesde.value || !elHasta.value) {
            content.innerHTML = '<div class="acc-empty">Selecciona las fechas Desde y Hasta.</div>';
            return;
        }
        content.innerHTML = '<div class="acc-loading">⏳ Generando reporte…</div>';
        btnPrint.disabled = true;
        btnCsv.disabled = true;

        const body = new URLSearchParams({ desde: elDesde.value, hasta: elHasta.value });
        fetch('index.php?pg=reports&action=accounting&ajax=1', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: body.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) throw new Error(data && data.error ? data.error : 'Error');
            ultimo = data;
            render(data);
            btnPrint.disabled = false;
            btnCsv.disabled = false;
        })
        .catch(err => {
            console.error('[CONTABLE]', err);
            content.innerHTML = '<div class="acc-empty">❌ No se pudo generar el reporte: ' + (err.message || '') + '</div>';
        });
    }

    // ---- Render ----
    function render(data) {
        const r = data.resumen || {};
        const periodo = `Periodo: ${fmtLargo(data.desde)} — ${fmtLargo(data.hasta)}`;
        document.getElementById('accPrintPeriodo').textContent = periodo;

        const utilidadNeta = Number(r.utilidadNeta) || 0;
        const utilidadBruta = Number(r.utilidadBruta) || 0;

        let html = '';

        // Periodo (visible en pantalla)
        html += `<div class="acc-section-title" style="border:none;margin-bottom:4px;">${periodo}</div>`;

        // Resumen ejecutivo
        html += `<div class="acc-summary-grid">
            <div class="acc-card"><div class="acc-card-label">Ventas netas</div><div class="acc-card-value">${money(r.totalVendido)}</div></div>
            <div class="acc-card"><div class="acc-card-label">Costo de ventas</div><div class="acc-card-value">${money(r.costoVentas)}</div></div>
            <div class="acc-card ${utilidadBruta >= 0 ? 'pos' : 'neg'}"><div class="acc-card-label">Utilidad bruta</div><div class="acc-card-value">${money(utilidadBruta)}</div></div>
            <div class="acc-card"><div class="acc-card-label">Compras</div><div class="acc-card-value">${money(r.totalCompras)}</div></div>
            <div class="acc-card"><div class="acc-card-label">Gastos</div><div class="acc-card-value">${money(r.totalGastos)}</div></div>
            <div class="acc-card accent"><div class="acc-card-label">Utilidad neta</div><div class="acc-card-value">${money(utilidadNeta)}</div></div>
            <div class="acc-card ${utilidadNeta >= 0 ? 'pos' : 'neg'}"><div class="acc-card-label">Margen neto</div><div class="acc-card-value">${(Number(r.margenPorcentaje)||0).toFixed(1)}%</div></div>
            <div class="acc-card"><div class="acc-card-label"># Ventas</div><div class="acc-card-value">${numf(r.cantidadVentas)}</div></div>
        </div>`;

        // Desglose por método de pago
        html += `<div class="acc-section-title">Ventas por método de pago</div>
            <div class="acc-methods">
                <div class="acc-method"><div class="m-label">💵 Efectivo</div><div class="m-value">${money(r.totalEfectivo)}</div></div>
                <div class="acc-method"><div class="m-label">🏦 Bancolombia</div><div class="m-value">${money(r.totalBancolombia)}</div></div>
                <div class="acc-method"><div class="m-label">📱 Nequi</div><div class="m-value">${money(r.totalNequi)}</div></div>
                <div class="acc-method"><div class="m-label">🔁 Transferencias (total)</div><div class="m-value">${money(r.totalTransferencia)}</div></div>
                <div class="acc-method"><div class="m-label">Σ Total ventas</div><div class="m-value">${money(r.totalVendido)}</div></div>
            </div>`;

        // Detalle de ventas
        html += `<div class="acc-section-title">Detalle de ventas (${numf((data.ventas||[]).length)})</div>`;
        html += renderVentas(data.ventas || [], r);

        // Detalle de compras
        html += `<div class="acc-section-title">Detalle de compras (${numf((data.compras||[]).length)})</div>`;
        html += renderCompras(data.compras || [], r);

        // Detalle de gastos
        html += `<div class="acc-section-title">Detalle de gastos (${numf((data.gastos||[]).length)})</div>`;
        html += renderGastos(data.gastos || [], r);

        // Ventas anuladas (referencia, no suman)
        const anuladas = data.ventasAnuladas || [];
        if (anuladas.length) {
            html += `<details class="acc-anuladas">
                <summary>Ventas anuladas en el periodo: ${numf(anuladas.length)} — ${money(r.totalAnulado)} (no cuentan)</summary>`;
            html += renderAnuladas(anuladas);
            html += `</details>`;
        }

        content.innerHTML = html;
    }

    function metodoLabel(m) {
        const map = { efectivo: 'Efectivo', bancolombia: 'Bancolombia', nequi: 'Nequi', transferencia: 'Transferencia' };
        return map[m] || (m || '-');
    }
    function fechaHora(s) {
        if (!s) return '-';
        const d = new Date(s.replace(' ', 'T'));
        return isNaN(d) ? s : d.toLocaleString('es-CO');
    }

    function renderVentas(rows, r) {
        if (!rows.length) return '<div class="acc-empty">Sin ventas en el periodo.</div>';
        let body = '';
        rows.forEach(v => {
            body += `<tr>
                <td>${fechaHora(v.fechaVenta)}</td>
                <td class="text-center">#${v.idVenta}</td>
                <td>${metodoLabel(v.metodoPago)}</td>
                <td class="text-center">${numf(v.items)}</td>
                <td>${v.usuario || '-'}</td>
                <td class="text-end">${money(v.total)}</td>
            </tr>`;
        });
        return `<table class="acc-table">
            <thead><tr><th>Fecha</th><th class="text-center">ID</th><th>Método</th><th class="text-center">Ítems</th><th>Usuario</th><th class="text-end">Total</th></tr></thead>
            <tbody>${body}</tbody>
            <tfoot><tr><td colspan="5" class="text-end">TOTAL VENTAS</td><td class="text-end">${money(r.totalVendido)}</td></tr></tfoot>
        </table>`;
    }

    function renderCompras(rows, r) {
        if (!rows.length) return '<div class="acc-empty">Sin compras en el periodo.</div>';
        let body = '';
        rows.forEach(c => {
            body += `<tr>
                <td>${fechaHora(c.fechaCompra)}</td>
                <td class="text-center">#${c.idCompra}</td>
                <td>${c.nombreProveedor || 'Sin proveedor'}</td>
                <td>${c.tipoCompra || '-'}</td>
                <td class="text-end">${money(c.total)}</td>
            </tr>`;
        });
        return `<table class="acc-table">
            <thead><tr><th>Fecha</th><th class="text-center">ID</th><th>Proveedor</th><th>Tipo</th><th class="text-end">Total</th></tr></thead>
            <tbody>${body}</tbody>
            <tfoot><tr><td colspan="4" class="text-end">TOTAL COMPRAS</td><td class="text-end">${money(r.totalCompras)}</td></tr></tfoot>
        </table>`;
    }

    function renderGastos(rows, r) {
        if (!rows.length) return '<div class="acc-empty">Sin gastos en el periodo.</div>';
        let body = '';
        rows.forEach(g => {
            const tipo = g.tipo === 'producto' ? 'Producto (merma/rotura)' : 'Externo';
            const concepto = g.concepto || g.motivo || g.descripcion || g.producto || '-';
            body += `<tr>
                <td>${fechaHora(g.fechaRegistro)}</td>
                <td class="text-center">#${g.idGasto}</td>
                <td>${tipo}</td>
                <td>${concepto}</td>
                <td class="text-end">${money(g.monto)}</td>
            </tr>`;
        });
        return `<table class="acc-table">
            <thead><tr><th>Fecha</th><th class="text-center">ID</th><th>Tipo</th><th>Concepto</th><th class="text-end">Monto</th></tr></thead>
            <tbody>${body}</tbody>
            <tfoot><tr><td colspan="4" class="text-end">TOTAL GASTOS</td><td class="text-end">${money(r.totalGastos)}</td></tr></tfoot>
        </table>`;
    }

    function renderAnuladas(rows) {
        let body = '';
        rows.forEach(v => {
            body += `<tr>
                <td>${fechaHora(v.fechaVenta)}</td>
                <td class="text-center">#${v.idVenta}</td>
                <td>${metodoLabel(v.metodoPago)}</td>
                <td>${v.descripcion || '-'}</td>
                <td class="text-end">${money(v.total)}</td>
            </tr>`;
        });
        return `<table class="acc-table">
            <thead><tr><th>Fecha</th><th class="text-center">ID</th><th>Método</th><th>Motivo</th><th class="text-end">Total</th></tr></thead>
            <tbody>${body}</tbody></table>`;
    }

    // ---- Exportar CSV ----
    function csvCell(v) {
        const s = String(v == null ? '' : v);
        return /[",\n;]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    }
    function descargarCsv() {
        if (!ultimo) return;
        const r = ultimo.resumen || {};
        const sep = ';'; // Excel en config regional CO usa ; como separador
        const lines = [];
        const row = arr => lines.push(arr.map(csvCell).join(sep));

        row(['REPORTE CONTABLE']);
        row(['Periodo', fmtLargo(ultimo.desde) + ' a ' + fmtLargo(ultimo.hasta)]);
        row([]);
        row(['RESUMEN']);
        row(['Ventas netas', r.totalVendido]);
        row(['Costo de ventas', r.costoVentas]);
        row(['Utilidad bruta', r.utilidadBruta]);
        row(['Compras', r.totalCompras]);
        row(['Gastos', r.totalGastos]);
        row(['Utilidad neta', r.utilidadNeta]);
        row(['Margen neto (%)', (Number(r.margenPorcentaje)||0).toFixed(1)]);
        row([]);
        row(['VENTAS POR METODO']);
        row(['Efectivo', r.totalEfectivo]);
        row(['Bancolombia', r.totalBancolombia]);
        row(['Nequi', r.totalNequi]);
        row(['Transferencias (total)', r.totalTransferencia]);
        row([]);
        row(['DETALLE DE VENTAS']);
        row(['Fecha', 'ID', 'Metodo', 'Items', 'Usuario', 'Total']);
        (ultimo.ventas||[]).forEach(v => row([v.fechaVenta, v.idVenta, metodoLabel(v.metodoPago), v.items, v.usuario || '', v.total]));
        row(['', '', '', '', 'TOTAL', r.totalVendido]);
        row([]);
        row(['DETALLE DE COMPRAS']);
        row(['Fecha', 'ID', 'Proveedor', 'Tipo', 'Total']);
        (ultimo.compras||[]).forEach(c => row([c.fechaCompra, c.idCompra, c.nombreProveedor || '', c.tipoCompra || '', c.total]));
        row(['', '', '', 'TOTAL', r.totalCompras]);
        row([]);
        row(['DETALLE DE GASTOS']);
        row(['Fecha', 'ID', 'Tipo', 'Concepto', 'Monto']);
        (ultimo.gastos||[]).forEach(g => {
            const concepto = g.concepto || g.motivo || g.descripcion || g.producto || '';
            row([g.fechaRegistro, g.idGasto, g.tipo, concepto, g.monto]);
        });
        row(['', '', '', 'TOTAL', r.totalGastos]);

        // BOM para que Excel reconozca los acentos (UTF-8)
        const csv = '﻿' + lines.join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `reporte-contable_${ultimo.desde}_a_${ultimo.hasta}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // ---- Inicial: este mes ----
    aplicarPreset('mes');
    cargar();
})();
</script>
