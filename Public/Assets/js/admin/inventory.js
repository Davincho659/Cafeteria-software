// Inventario - Sistema POS
let stockData = [];

document.addEventListener('DOMContentLoaded', function() {
    loadStock();
    loadInventoryValue();
    loadMovements();
    loadAlerts();
    initializeEventListeners();
});

function initializeEventListeners() {
    // Refresh
    document.getElementById('btnRefreshStock').addEventListener('click', () => {
        loadStock();
        loadInventoryValue();
    });
    
    // Buscador
    document.getElementById('searchStock').addEventListener('input', function(e) {
        filterStock(e.target.value);
    });
    
    // Filtros movimientos
    document.getElementById('btnFilterMovements').addEventListener('click', loadMovements);
    
    // Form ajustar stock
    document.getElementById('adjustStockForm').addEventListener('submit', saveStockAdjustment);

    // Refresh alertas
    const btnAlerts = document.getElementById('btnRefreshAlerts');
    if (btnAlerts) {
        btnAlerts.addEventListener('click', loadAlerts);
    }
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
    
    const filtered = stockData.filter(item => 
        item.producto.toLowerCase().includes(filter.toLowerCase()) ||
        item.categoria.toLowerCase().includes(filter.toLowerCase())
    );
    
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No hay productos con control de stock</td></tr>';
        return;
    }
    
    filtered.forEach(item => {
        const tr = document.createElement('tr');
        
        const valorTotal = item.stockActual * item.precioVenta;
        const stockClass = item.stockActual < 10 ? 'text-danger fw-bold' : 'text-success';
        
        tr.innerHTML = `
            <td>${item.producto}</td>
            <td>${item.categoria}</td>
            <td class="text-center ${stockClass}">${item.stockActual}</td>
            <td class="text-end">$${parseFloat(item.precioCompra || 0).toFixed(2)}</td>
            <td class="text-end">$${parseFloat(item.precioVenta || 0).toFixed(2)}</td>
            <td class="text-end fw-bold">$${valorTotal.toFixed(2)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary" onclick="openAdjustModal(${item.idProducto}, '${item.producto}', ${item.stockActual})" title="Ajustar stock">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <button class="btn btn-sm btn-outline-info" onclick="viewProductHistory(${item.idProducto}, '${item.producto}')" title="Ver historial">
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

// ============ VALOR DEL INVENTARIO ============

async function loadInventoryValue() {
    try {
        const response = await fetch('?pg=inventory&action=getInventoryValue');
        const data = await response.json();
        
        if (data.success) {
            const { valorCompra, valorVenta } = data.data;
            const ganancia = valorVenta - valorCompra;
            
            document.getElementById('totalValueCost').textContent = parseFloat(valorCompra || 0).toFixed(2);
            document.getElementById('totalValueSale').textContent = parseFloat(valorVenta || 0).toFixed(2);
            document.getElementById('potentialProfit').textContent = parseFloat(ganancia || 0).toFixed(2);
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
            <td class="text-center">${mov.cantidad}</td>
            <td class="text-center">${mov.stockAnterior}</td>
            <td class="text-center fw-bold">${mov.stockActual}</td>
            <td><small>${mov.descripcion || mov.referencia || '-'}</small></td>
            <td><small>${mov.usuario || '-'}</small></td>
        `;
        
        tbody.appendChild(tr);
    });
}

// ============ AJUSTAR STOCK ============

function openAdjustModal(idProducto, nombre, stockActual) {
    document.getElementById('adjustProductId').value = idProducto;
    document.getElementById('adjustProductName').value = nombre;
    document.getElementById('adjustCurrentStock').value = stockActual;
    document.getElementById('adjustNewStock').value = stockActual;
    document.getElementById('adjustDescription').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('modalAdjustStock'));
    modal.show();
}

async function saveStockAdjustment(e) {
    e.preventDefault();
    
    const adjustData = {
        idProducto: parseInt(document.getElementById('adjustProductId').value),
        nuevoStock: parseInt(document.getElementById('adjustNewStock').value),
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
                            <td class="text-center">${mov.cantidad}</td>
                            <td class="text-center">${mov.stockAnterior}</td>
                            <td class="text-center fw-bold">${mov.stockActual}</td>
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
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No hay alertas registradas</td></tr>';
        return;
    }

    alerts.forEach(a => {
        const tr = document.createElement('tr');
        const fecha = new Date(a.fechaMovimiento);
        const tipoClass = a.tipoMovimiento === 'salida' ? 'danger' : (a.tipoMovimiento === 'ajuste' ? 'warning' : 'secondary');
        tr.innerHTML = `
            <td>${fecha.toLocaleString()}</td>
            <td>${a.producto}</td>
            <td><span class="badge bg-${tipoClass}">${a.tipoMovimiento}</span></td>
            <td class="text-center">${a.cantidad}</td>
            <td class="text-center">${a.stockAnterior}</td>
            <td class="text-center fw-bold text-danger">${a.stockActual}</td>
            <td><small>${a.descripcion || a.referencia || '-'}</small></td>
            <td><small>${a.usuario || '-'}</small></td>
        `;
        tbody.appendChild(tr);
    });
}