<link rel="stylesheet" href="<?= asset('assets/css/reports.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/closings.css') ?>">

<div class="container-fluid closing-report">

    <div class="filter-card no-print">
        <h4 class="filter-section-title">⏰ Ventas por hora</h4>
        <p class="text-muted">Identifica las horas pico para organizar personal y preparación</p>

        <form id="hourForm" class="row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label" for="hour-desde">Desde</label>
                <input type="date" class="form-control" id="hour-desde" name="desde"
                       value="<?= date('Y-m-d', strtotime('-29 days')) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="hour-hasta">Hasta</label>
                <input type="date" class="form-control" id="hour-hasta" name="hasta"
                       value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-primary">Consultar</button>
            </div>
        </form>
    </div>

    <div id="hour-loading" class="text-center py-4">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
    </div>

    <div id="hour-content" style="display:none;">
        <div class="row g-3 kpi-row">
            <div class="col-12 col-md-4">
                <div class="kpi-card">
                    <div class="kpi-label">🔝 Hora pico</div>
                    <div class="kpi-value" id="h-pico">—</div>
                    <div class="kpi-sub" id="h-pico-sub"></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card">
                    <div class="kpi-label">💰 Total del periodo</div>
                    <div class="kpi-value text-success" id="h-total">$0</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card">
                    <div class="kpi-label">🧾 Ventas totales</div>
                    <div class="kpi-value" id="h-ventas">0</div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <h3 class="filter-section-title">Distribución por hora</h3>
            <div id="hour-bars" class="closing-serie"></div>
        </div>
    </div>
</div>

<script>
(function () {
    const money = (n) => '$' + Number(n || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 });
    const num = (n) => Number(n || 0).toLocaleString('es-CO');
    const fmtHora = (h) => String(h).padStart(2, '0') + ':00';

    const form = document.getElementById('hourForm');

    function cargar() {
        const loading = document.getElementById('hour-loading');
        const content = document.getElementById('hour-content');
        loading.style.display = 'block';
        content.style.display = 'none';

        fetch('index.php?pg=reports&action=salesByHour&ajax=1', { method: 'POST', body: new FormData(form) })
            .then((r) => r.json())
            .then((data) => {
                loading.style.display = 'none';
                if (!data.success) return;

                const horas = data.horas || [];
                const totalPeriodo = horas.reduce((a, h) => a + Number(h.totalVendido), 0);
                const totalVentas = horas.reduce((a, h) => a + Number(h.cantidadVentas), 0);
                const max = Math.max(...horas.map((h) => Number(h.totalVendido)), 1);
                const pico = horas.reduce((a, h) => (Number(h.totalVendido) > Number(a.totalVendido) ? h : a), horas[0]);

                document.getElementById('h-total').textContent = money(totalPeriodo);
                document.getElementById('h-ventas').textContent = num(totalVentas);
                if (pico && Number(pico.totalVendido) > 0) {
                    document.getElementById('h-pico').textContent = fmtHora(pico.hora);
                    document.getElementById('h-pico-sub').textContent = money(pico.totalVendido) + ' · ' + num(pico.cantidadVentas) + ' ventas';
                } else {
                    document.getElementById('h-pico').textContent = '—';
                    document.getElementById('h-pico-sub').textContent = 'Sin ventas en el periodo';
                }

                // Solo mostramos horas con actividad o dentro del rango 6-23 para no llenar de ceros
                document.getElementById('hour-bars').innerHTML = horas
                    .filter((h) => Number(h.totalVendido) > 0 || (h.hora >= 6 && h.hora <= 22))
                    .map((h) => {
                        const pct = (Number(h.totalVendido) / max) * 100;
                        const esPico = pico && h.hora === pico.hora && Number(h.totalVendido) > 0;
                        return `<div class="serie-row">
                            <div class="serie-label">${fmtHora(h.hora)}</div>
                            <div class="serie-track"><div class="serie-fill" style="width:${pct.toFixed(1)}%;${esPico ? 'background:var(--success)' : ''}"></div></div>
                            <div class="serie-value">${money(h.totalVendido)}</div>
                            <div class="serie-count">${num(h.cantidadVentas)} v.</div>
                        </div>`;
                    }).join('');

                content.style.display = 'block';
            })
            .catch((e) => { loading.style.display = 'none'; console.error(e); });
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); cargar(); });
    cargar();
})();
</script>
