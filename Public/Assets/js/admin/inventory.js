// Inventario - Sistema POS
let stockData = [];
// Filtro de tipo de producto: '' = todos, 'venta' = mercancía, 'insumo' = materia prima
let tipoFiltro = '';

document.addEventListener('DOMContentLoaded', function() {
    loadStock();
    loadInventoryValue();
    loadMovements();
    loadAlerts();
    initializeEventListeners();
    updateInventoryAlertBadge();
});

function formatCurrency(value) {
  return new Intl.NumberFormat('es-CO').format(value);
}

function formatQuantity(value, unidadTipo) {
    // Los datos ya vienen correctamente formateados desde la BD
    // Solo necesitamos convertir a número y aplicar locale si es necesario
    const numValue = parseFloat(value);
    
    if (!isFinite(numValue)) {
        return '0';
    }

    return numValue.toLocaleString('es-CO', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3
    });
}

/**
 * Registra un listener solo si el elemento existe.
 *
 * Importante: antes se llamaba addEventListener directo sobre elementos que no
 * están en todas las vistas (ej. btnFilterMovements). Si uno faltaba, la
 * excepción cortaba la función y TODOS los listeners siguientes quedaban sin
 * registrar (el formulario de ajustar stock dejaba de funcionar sin dar señal).
 */
function on(id, evento, manejador) {
    const el = document.getElementById(id);
    if (el) el.addEventListener(evento, manejador);
    return !!el;
}

function initializeEventListeners() {
    // Refresh
    on('btnRefreshStock', 'click', () => {
        loadStock();
        loadInventoryValue();
    });

    // Buscador
    on('searchStock', 'input', function (e) {
        filterStock(e.target.value);
    });

    // Filtros movimientos
    on('btnFilterMovements', 'click', loadMovements);

    // Form ajustar stock
    on('adjustStockForm', 'submit', saveStockAdjustment);

    // Form registrar consumo de insumos
    on('consumptionForm', 'submit', saveConsumption);
    on('consumeQuantity', 'input', updateConsumeCostPreview);

    // Filtro por tipo (todos / de venta / insumos)
    document.querySelectorAll('input[name="filtroTipo"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            tipoFiltro = this.value;
            renderStock(document.getElementById('searchStock').value);
        });
    });

    // Refresh alertas
    on('btnRefreshAlerts', 'click', loadAlerts);
}

// ============ CARGAR STOCK ============

async function loadStock() {
    try {
        const response = await fetch('?pg=inventory&action=getStock');
        const data = await response.json();
        
        if (data.success) {
            stockData = data.data;
            renderStock();
        }
    } catch (error) {
        console.error('Error loading stock:', error);
        showAlert('Error al cargar el inventario', 'error');
    }
}



function renderStock(filter = '') {
    const tbody = document.getElementById('stockTable');
    tbody.innerHTML = '';
    
    const texto = filter.toLowerCase();
    const filtered = stockData.filter(item => {
        const coincideTexto = item.producto.toLowerCase().includes(texto) ||
                              item.categoria.toLowerCase().includes(texto);
        // tipoFiltro vacío = mostrar todo
        const coincideTipo = !tipoFiltro || (item.tipo || 'venta') === tipoFiltro;
        return coincideTexto && coincideTipo;
    });

    if (filtered.length === 0) {
        const msg = tipoFiltro === 'insumo'
            ? 'No hay insumos con control de stock. Crea productos de tipo "Insumo" en Productos.'
            : 'No hay productos con control de stock';
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">' + msg + '</td></tr>';
        return;
    }
    
    filtered.forEach(item => {
        const tr = document.createElement('tr');
        
        const valorTotal = item.stockActual * item.precioVenta;
        const stockClass = item.stockActual < 0 ? 'text-danger fw-bold' : 'text-success fw-bold';
        const alertIcon = item.stockActual < 0 ? ' <i class="fa-solid fa-triangle-exclamation text-danger" title="¡Stock negativo!"></i>' : '';

        const esInsumo = item.tipo === 'insumo';
        const insignia = esInsumo
            ? '<span class="badge bg-warning text-dark"><i class="fa-solid fa-wheat-awn"></i> Insumo</span>'
            : '<span class="badge bg-light text-dark border">Venta</span>';

        // El nombre se pasa escapado: si trae comillas rompe el onclick.
        const nombreSeguro = String(item.producto).replace(/'/g, "\\'");

        tr.innerHTML = `
            <td>${item.producto}</td>
            <td>${insignia}</td>
            <td>${item.categoria}</td>
            <td class="text-center ${stockClass}">${formatQuantity(item.stockActual, item.unidadTipo)}${alertIcon}</td>
            <td class="text-end">$${formatCurrency(item.precioCompra || 0)}</td>
            <td class="text-end">$${formatCurrency(item.precioVenta || 0)}</td>
            <td class="text-end fw-bold">$${formatCurrency(valorTotal)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-warning" onclick="openConsumptionModal(${item.idProducto}, '${nombreSeguro}', ${item.stockActual}, '${item.unidadAbreviatura || 'u'}', ${item.precioCompra || 0}, '${item.unidadTipo || ''}')" title="Registrar consumo">
                    <i class="fa-solid fa-utensils"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="openAdjustModal(${item.idProducto}, '${nombreSeguro}', ${item.stockActual}, '${item.unidadTipo || ''}', '${item.unidadAbreviatura || 'u'}')" title="Ajustar stock">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <button class="btn btn-sm btn-outline-info" onclick="viewProductHistory(${item.idProducto}, '${nombreSeguro}')" title="Ver historial">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
    });
}

function filterStock(search) {
    renderStock(search);
}

// ============ REGISTRAR CONSUMO DE INSUMOS ============
// Permite anotar cuánto se sacó de un insumo (ej. 5 kg de maíz para los
// fritos). El ajuste sirve para corregir el conteo; esto deja la trazabilidad
// de en qué se usó la materia prima.

let consumeCostoUnitario = 0;

function openConsumptionModal(id, nombre, stockActual, unidad, precioCompra, unidadTipo) {
    const producto = { unidadTipo: unidadTipo, unidadAbreviatura: unidad };

    document.getElementById('consumeProductId').value = id;
    document.getElementById('consumeProductName').value = nombre;
    document.getElementById('consumeCurrentStock').value =
        (typeof formatearCantidadUnidad === 'function')
            ? formatearCantidadUnidad(stockActual, producto)
            : stockActual + ' ' + (unidad || 'u');
    document.getElementById('consumeUnit').textContent = unidad || 'u';

    // Medio kilo de café es válido; media empanada no.
    const inputCant = document.getElementById('consumeQuantity');
    if (typeof aplicarUnidadAlCampo === 'function') {
        aplicarUnidadAlCampo(inputCant, producto, null);
    }

    inputCant.value = '';
    document.getElementById('consumeDescription').value = '';

    consumeCostoUnitario = parseFloat(precioCompra) || 0;
    document.getElementById('consumeCostPreview').style.display = 'none';

    new bootstrap.Modal(document.getElementById('modalConsumption')).show();
}

/** Muestra cuánto dinero representa el consumo mientras se escribe. */
function updateConsumeCostPreview() {
    const cant = parseFloat(document.getElementById('consumeQuantity').value);
    const box = document.getElementById('consumeCostPreview');
    if (isNaN(cant) || cant <= 0 || consumeCostoUnitario <= 0) {
        box.style.display = 'none';
        return;
    }
    document.getElementById('consumeCostValue').textContent = '$' + formatCurrency(cant * consumeCostoUnitario);
    box.style.display = 'block';
}

async function saveConsumption(e) {
    e.preventDefault();

    const idProducto = document.getElementById('consumeProductId').value;
    const cantidad = parseFloat(document.getElementById('consumeQuantity').value);
    const descripcion = document.getElementById('consumeDescription').value.trim();

    if (isNaN(cantidad) || cantidad <= 0) {
        showAlert('Escribe la cantidad que se usó', 'warning');
        return;
    }
    if (!descripcion) {
        showAlert('Indica en qué se usó el insumo', 'warning');
        return;
    }

    try {
        const response = await fetch('?pg=inventory&action=registerConsumption', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idProducto, cantidad, descripcion })
        });
        const data = await response.json();

        if (!data.success) throw new Error(data.error || 'No se pudo registrar');

        bootstrap.Modal.getInstance(document.getElementById('modalConsumption')).hide();
        showAlert('Consumo registrado correctamente', 'success');
        loadStock();
        loadInventoryValue();
    } catch (error) {
        showAlert(error.message, 'error');
    }
}

// ============ VALOR DEL INVENTARIO ============

async function loadInventoryValue() {
    try {
        const response = await fetch('?pg=inventory&action=getInventoryValue');
        const data = await response.json();
        
        if (data.success) {
            const { valorCompra, valorVenta } = data.data;
            const ganancia = valorVenta - valorCompra;
            
            document.getElementById('totalValueCost').textContent = formatCurrency(valorCompra || 0);
            document.getElementById('totalValueSale').textContent = formatCurrency(valorVenta || 0);
            document.getElementById('potentialProfit').textContent = formatCurrency(ganancia || 0);
        }
    } catch (error) {
        console.error('Error loading inventory value:', error);
    }
}

// ============ MOVIMIENTOS ============

async function loadMovements() {
    const tipoMovimiento = document.getElementById('filterMovementType').value;
    const fechaDesde = document.getElementById('filterMovementDateFrom').value;
    const fechaHasta = document.getElementById('filterMovementDateTo').value;
    
    let url = '?pg=inventory&action=getMovements&limit=100';
    
    if (tipoMovimiento) url += `&tipoMovimiento=${tipoMovimiento}`;
    if (fechaDesde) url += `&fechaDesde=${fechaDesde}`;
    if (fechaHasta) url += `&fechaHasta=${fechaHasta}`;
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            renderMovements(data.data);
        }
    } catch (error) {
        console.error('Error loading movements:', error);
        showAlert('Error al cargar los movimientos', 'error');
    }
}

function renderMovements(movements) {
    const tbody = document.getElementById('movementsTable');
    tbody.innerHTML = '';
    
    if (movements.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No hay movimientos registrados</td></tr>';
        return;
    }
    
    movements.forEach(mov => {
        const tr = document.createElement('tr');
        
        const fecha = new Date(mov.fechaMovimiento);
        let tipoClass = '';
        let tipoIcon = '';
        
        switch(mov.tipoMovimiento) {
            case 'entrada':
                tipoClass = 'success';
                tipoIcon = 'arrow-up';
                break;
            case 'salida':
                tipoClass = 'danger';
                tipoIcon = 'arrow-down';
                break;
            case 'ajuste':
                tipoClass = 'warning';
                tipoIcon = 'pen-to-square';
                break;
        }
        
        tr.innerHTML = `
            <td>${fecha.toLocaleString()}</td>
            <td>${mov.producto}</td>
            <td>
                <span class="badge bg-${tipoClass}">
                    <i class="fa-solid fa-${tipoIcon}"></i> ${mov.tipoMovimiento}
                </span>
            </td>
            <td class="text-center">${formatQuantity(mov.cantidad, mov.unidadTipo)}</td>
            <td class="text-center">${formatQuantity(mov.stockAnterior, mov.unidadTipo)}</td>
            <td class="text-center fw-bold">${formatQuantity(mov.stockActual, mov.unidadTipo)}</td>
            <td><small>${mov.descripcion || mov.referencia || '-'}</small></td>
            <td><small>${mov.usuario || '-'}</small></td>
        `;
        
        tbody.appendChild(tr);
    });
}

// ============ AJUSTAR STOCK ============

function openAdjustModal(idProducto, nombre, stockActual, unidadTipo, unidadAbreviatura) {
    document.getElementById('adjustProductId').value = idProducto;
    document.getElementById('adjustProductName').value = nombre;

    // El stock actual se muestra con su unidad: "50.5 kg" en vez de un número
    // suelto que no dice si son kilos, litros o piezas.
    const producto = { unidadTipo: unidadTipo, unidadAbreviatura: unidadAbreviatura };
    document.getElementById('adjustCurrentStock').value =
        (typeof formatearCantidadUnidad === 'function')
            ? formatearCantidadUnidad(stockActual, producto)
            : stockActual;

    const input = document.getElementById('adjustNewStock');
    const label = document.getElementById('adjustNewStockLabel');
    const hint = document.getElementById('adjustUnidadHint');

    // El campo acepta decimales solo si el producto se mide por peso o volumen.
    let regla = null;
    if (typeof aplicarUnidadAlCampo === 'function') {
        input.dataset.permiteCero = 'true'; // un ajuste puede dejar el stock en 0
        regla = aplicarUnidadAlCampo(input, producto, label, 'Nuevo Stock');
    }

    input.value = regla && !regla.decimales ? Math.round(stockActual) : stockActual;

    if (hint) {
        hint.textContent = regla && regla.decimales
            ? `Se admiten decimales (ej: 50.5 ${regla.abreviatura})`
            : 'Solo números enteros';
    }

    document.getElementById('adjustDescription').value = '';

    const modal = new bootstrap.Modal(document.getElementById('modalAdjustStock'));
    modal.show();
}

async function saveStockAdjustment(e) {
    e.preventDefault();
    
    const adjustData = {
        idProducto: parseInt(document.getElementById('adjustProductId').value),
        nuevoStock: parseFloat(document.getElementById('adjustNewStock').value),
        descripcion: document.getElementById('adjustDescription').value.trim(),
        idUsuario: getUserId()
    };
    
    try {
        const response = await fetch('?pg=inventory&action=adjustStock', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(adjustData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Stock ajustado exitosamente', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalAdjustStock')).hide();
            loadStock();
            loadInventoryValue();
            loadMovements();
        } else {
            showAlert(data.error || 'Error al ajustar el stock', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al ajustar el stock', 'error');
    }
}

// ============ HISTORIAL DE PRODUCTO ============

async function viewProductHistory(idProducto, nombre) {
    try {
        const response = await fetch(`?pg=inventory&action=getProductStock&id=${idProducto}`);
        const data = await response.json();
        
        if (data.success) {
            const { stockActual, historial } = data.data;
            const modal = new bootstrap.Modal(document.getElementById('modalViewHistory'));
            const content = document.getElementById('productHistoryContent');
            
            let html = `
                <div class="mb-3">
                    <h6 class="fw-bold">${nombre}</h6>
                    <p class="mb-0"><strong>Stock Actual:</strong> <span class="badge bg-primary">${stockActual}</span></p>
                </div>
                
                <h6 class="fw-bold mb-3">Historial de Movimientos</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-center">Stock Anterior</th>
                                <th class="text-center">Stock Nuevo</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (historial.length === 0) {
                html += '<tr><td colspan="5" class="text-center text-muted">No hay movimientos registrados</td></tr>';
            } else {
                historial.forEach(mov => {
                    const fecha = new Date(mov.fechaMovimiento);
                    let tipoClass = '';
                    
                    switch(mov.tipoMovimiento) {
                        case 'entrada': tipoClass = 'success'; break;
                        case 'salida': tipoClass = 'danger'; break;
                        case 'ajuste': tipoClass = 'warning'; break;
                    }
                    
                    html += `
                        <tr>
                            <td><small>${fecha.toLocaleString()}</small></td>
                            <td><span class="badge bg-${tipoClass}">${mov.tipoMovimiento}</span></td>
                            <td class="text-center">${formatQuantity(mov.cantidad, mov.unidadTipo)}</td>
                            <td class="text-center">${formatQuantity(mov.stockAnterior, mov.unidadTipo)}</td>
                            <td class="text-center fw-bold">${formatQuantity(mov.stockActual, mov.unidadTipo)}</td>
                        </tr>
                    `;
                });
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            content.innerHTML = html;
            modal.show();
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar el historial', 'error');
    }
}

// ============ UTILIDADES ============

function getUserId() {
    // Implementar según tu sistema de sesiones
    return null;
}

function showAlert(message, type = 'info') {
    alert(message);
}

// ============ ALERTAS DE STOCK ============

async function loadAlerts() {
    try {
        const response = await fetch('?pg=inventory&action=getAlertas&limit=100');
        const data = await response.json();
        if (data.success) {
            renderAlerts(data.data);
        }
    } catch (error) {
        console.error('Error loading alerts:', error);
        showAlert('Error al cargar las alertas', 'error');
    }
}

function renderAlerts(alerts) {
    const tbody = document.getElementById('alertsTable');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!alerts || alerts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-success"><i class="fa-solid fa-circle-check me-2"></i>No hay productos con stock negativo</td></tr>';
        return;
    }

    alerts.forEach(a => {
        const tr = document.createElement('tr');
        const fecha = new Date(a.fechaMovimiento);
        const tipoClass = a.tipoMovimiento === 'salida' ? 'danger' : (a.tipoMovimiento === 'ajuste' ? 'warning' : 'info');
        tr.innerHTML = `
            <td>${fecha.toLocaleString()}</td>
            <td>${a.producto}</td>
            <td><span class="badge bg-${tipoClass}">${a.tipoMovimiento || '-'}</span></td>
            <td class="text-center">${formatQuantity(a.cantidad, a.unidadTipo)}</td>
            <td class="text-center">${formatQuantity(a.stockAnterior, a.unidadTipo)}</td>
            <td class="text-center fw-bold text-danger">${formatQuantity(a.stockActual, a.unidadTipo)}</td>
            <td><small>${a.descripcion || a.referencia || 'Stock agotado'}</small></td>
            <td><small>${a.usuario || '-'}</small></td>
        `;
        tbody.appendChild(tr);
    });
}