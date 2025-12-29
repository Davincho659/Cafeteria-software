// Compras - Sistema POS
let selectedProducts = [];
let allProducts = [];
let allSuppliers = [];

document.addEventListener('DOMContentLoaded', function() {
    loadSuppliers();
    loadProducts();
    loadPurchasesHistory();
    initializeEventListeners();
});

function initializeEventListeners() {
    // Botón guardar compra detallada
    document.getElementById('btnSaveDetailedPurchase').addEventListener('click', saveDetailedPurchase);
    
    // Botón limpiar
    document.getElementById('btnClearPurchase').addEventListener('click', clearPurchase);
    
    // Form compra rápida
    document.getElementById('quickPurchaseForm').addEventListener('submit', saveQuickPurchase);
    
    // Buscador de productos
    document.getElementById('searchProduct').addEventListener('input', function(e) {
        filterProducts(e.target.value);
    });
    
    // Botón refresh historial
    document.getElementById('btnRefreshHistory').addEventListener('click', loadPurchasesHistory);
    
    // Filtros historial
    const filters = ['filterSupplier', 'filterType', 'filterDateFrom', 'filterDateTo'];
    filters.forEach(id => {
        document.getElementById(id).addEventListener('change', loadPurchasesHistory);
    });
}

// ============ CARGAR DATOS ============

async function loadSuppliers() {
    try {
        const response = await fetch('?pg=purchases&action=getSuppliers');
        const data = await response.json();
        
        if (data.success) {
            allSuppliers = data.data;
            renderSuppliers();
        }
    } catch (error) {
        console.error('Error loading suppliers:', error);
        showAlert('Error al cargar proveedores', 'error');
    }
}

function renderSuppliers() {
    const selects = ['supplierSelect', 'quickSupplierSelect', 'filterSupplier'];
    
    selects.forEach(selectId => {
        const select = document.getElementById(selectId);
        const currentValue = select.value;
        
        // Mantener primera opción
        const firstOption = select.options[0];
        select.innerHTML = '';
        select.appendChild(firstOption);
        
        allSuppliers.forEach(supplier => {
            const option = document.createElement('option');
            option.value = supplier.idProveedor;
            option.textContent = supplier.nombre;
            select.appendChild(option);
        });
        
        // Restaurar selección si existe
        if (currentValue) select.value = currentValue;
    });
}

async function loadProducts() {
    try {
        const response = await fetch('?pg=purchases&action=getProducts');
        const data = await response.json();
        
        if (data.success) {
            allProducts = data.data;
            renderProducts();
        }
    } catch (error) {
        console.error('Error loading products:', error);
        showAlert('Error al cargar productos', 'error');
    }
}

function renderProducts(filter = '') {
    const container = document.getElementById('productsList');
    container.innerHTML = '';
    
    const filtered = allProducts.filter(p => 
        p.nombre.toLowerCase().includes(filter.toLowerCase())
    );
    
    if (filtered.length === 0) {
        container.innerHTML = '<div class="col-12"><p class="text-muted text-center">No se encontraron productos</p></div>';
        return;
    }
    
    filtered.forEach(product => {
        const col = document.createElement('div');
        col.className = 'col-md-6 mb-2';
        
        col.innerHTML = `
            <div class="card h-100 product-card" onclick="addProductToPurchase(${product.idProducto})">
                <div class="card-body p-2">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0">${product.nombre}</h6>
                            <small class="text-muted">${product.categoria}</small>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.appendChild(col);
    });
}

function filterProducts(search) {
    renderProducts(search);
}

// ============ COMPRA DETALLADA ============

function addProductToPurchase(idProducto) {
    const product = allProducts.find(p => p.idProducto == idProducto);
    if (!product) return;
    
    // Verificar si ya está en la lista
    const existing = selectedProducts.find(p => p.idProducto == idProducto);
    if (existing) {
        existing.cantidad++;
    } else {
        selectedProducts.push({
            idProducto: product.idProducto,
            nombre: product.nombre,
            cantidad: 1,
            precioUnitario: parseFloat(product.precioCompra) || 0
        });
    }
    
    renderSelectedProducts();
}

function renderSelectedProducts() {
    const container = document.getElementById('selectedProducts');
    
    if (selectedProducts.length === 0) {
        container.innerHTML = '<p class="text-muted text-center"><small>No hay productos seleccionados</small></p>';
        updateTotal();
        return;
    }
    
    container.innerHTML = '';
    
    selectedProducts.forEach((product, index) => {
        const div = document.createElement('div');
        div.className = 'border rounded p-2 mb-2';
        
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <strong class="small">${product.nombre}</strong>
                <button class="btn btn-sm btn-outline-danger" onclick="removeProduct(${index})">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label small mb-1">Cantidad</label>
                    <input type="number" class="form-control form-control-sm" 
                           value="${product.cantidad}" min="1" 
                           onchange="updateProductQuantity(${index}, this.value)">
                </div>
                <div class="col-6">
                    <label class="form-label small mb-1">Precio</label>
                    <input type="number" class="form-control form-control-sm" 
                           value="${product.precioUnitario}" min="0" step="0.01"
                           onchange="updateProductPrice(${index}, this.value)">
                </div>
            </div>
            <div class="text-end mt-2">
                <small class="text-muted">Subtotal: 
                    <strong>$${(product.cantidad * product.precioUnitario).toFixed(2)}</strong>
                </small>
            </div>
        `;
        
        container.appendChild(div);
    });
    
    updateTotal();
}

function updateProductQuantity(index, value) {
    selectedProducts[index].cantidad = parseInt(value) || 1;
    updateTotal();
}

function updateProductPrice(index, value) {
    selectedProducts[index].precioUnitario = parseFloat(value) || 0;
    updateTotal();
}

function removeProduct(index) {
    selectedProducts.splice(index, 1);
    renderSelectedProducts();
}

function updateTotal() {
    const total = selectedProducts.reduce((sum, p) => sum + (p.cantidad * p.precioUnitario), 0);
    document.getElementById('purchaseTotal').textContent = total.toFixed(2);
}

async function saveDetailedPurchase() {
    const idProveedor = document.getElementById('supplierSelect').value;
    
    // Validaciones
    if (!idProveedor) {
        showAlert('Debes seleccionar un proveedor', 'warning');
        return;
    }
    
    if (selectedProducts.length === 0) {
        showAlert('Debes agregar al menos un producto', 'warning');
        return;
    }
    
    const total = selectedProducts.reduce((sum, p) => sum + (p.cantidad * p.precioUnitario), 0);
    
    const purchaseData = {
        idProveedor: parseInt(idProveedor),
        productos: selectedProducts,
        total: total,
        idUsuario: getUserId() // Implementar según tu sistema de sesiones
    };
    
    try {
        const response = await fetch('?pg=purchases&action=createDetailedPurchase', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(purchaseData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Compra registrada exitosamente', 'success');
            clearPurchase();
            loadPurchasesHistory();
        } else {
            showAlert(data.error || 'Error al registrar la compra', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al registrar la compra', 'error');
    }
}

function clearPurchase() {
    selectedProducts = [];
    renderSelectedProducts();
    document.getElementById('supplierSelect').value = '';
}

// ============ COMPRA RÁPIDA ============

async function saveQuickPurchase(e) {
    e.preventDefault();
    
    const idProveedor = document.getElementById('quickSupplierSelect').value;
    const descripcion = document.getElementById('quickDescription').value.trim();
    const total = parseFloat(document.getElementById('quickTotal').value);
    
    if (!idProveedor || !descripcion || !total) {
        showAlert('Todos los campos son requeridos', 'warning');
        return;
    }
    
    const purchaseData = {
        idProveedor: parseInt(idProveedor),
        descripcion: descripcion,
        total: total,
        idUsuario: getUserId()
    };
    
    try {
        const response = await fetch('?pg=purchases&action=createQuickPurchase', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(purchaseData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Compra rápida registrada exitosamente', 'success');
            document.getElementById('quickPurchaseForm').reset();
            loadPurchasesHistory();
        } else {
            showAlert(data.error || 'Error al registrar la compra', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al registrar la compra', 'error');
    }
}

// ============ HISTORIAL ============

async function loadPurchasesHistory() {
    const idProveedor = document.getElementById('filterSupplier').value;
    const tipoCompra = document.getElementById('filterType').value;
    const fechaDesde = document.getElementById('filterDateFrom').value;
    const fechaHasta = document.getElementById('filterDateTo').value;
    
    let url = '?pg=purchases&action=getPurchases';
    const params = [];
    
    if (idProveedor) params.push(`idProveedor=${idProveedor}`);
    if (tipoCompra) params.push(`tipoCompra=${tipoCompra}`);
    if (fechaDesde) params.push(`fechaDesde=${fechaDesde}`);
    if (fechaHasta) params.push(`fechaHasta=${fechaHasta}`);
    
    if (params.length > 0) url += '&' + params.join('&');
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            renderPurchasesHistory(data.data);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar el historial', 'error');
    }
}

function renderPurchasesHistory(purchases) {
    const tbody = document.getElementById('purchasesHistory');
    tbody.innerHTML = '';
    
    if (purchases.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay compras registradas</td></tr>';
        return;
    }
    
    purchases.forEach(purchase => {
        const tr = document.createElement('tr');
        
        const fecha = new Date(purchase.fechaCompra);
        const tipoClass = purchase.tipoCompra === 'detallada' ? 'primary' : 'warning';
        
        tr.innerHTML = `
            <td>${purchase.idCompra}</td>
            <td>${fecha.toLocaleString()}</td>
            <td>${purchase.proveedor || 'N/A'}</td>
            <td><span class="badge bg-${tipoClass}">${purchase.tipoCompra}</span></td>
            <td class="fw-bold">$${parseFloat(purchase.total).toFixed(2)}</td>
            <td>
                <button class="btn btn-sm btn-outline-info" onclick="viewPurchaseDetail(${purchase.idCompra})">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(tr);
    });
}

async function viewPurchaseDetail(idCompra) {
    try {
        const response = await fetch(`?pg=purchases&action=getPurchase&id=${idCompra}`);
        const data = await response.json();
        
        if (data.success) {
            const { compra, detalles } = data.data;
            const modal = new bootstrap.Modal(document.getElementById('modalViewPurchase'));
            const content = document.getElementById('purchaseDetailContent');
            
            let html = `
                <div class="mb-3">
                    <h6 class="fw-bold">Información General</h6>
                    <p class="mb-1"><strong>Proveedor:</strong> ${compra.proveedor}</p>
                    <p class="mb-1"><strong>Fecha:</strong> ${new Date(compra.fechaCompra).toLocaleString()}</p>
                    <p class="mb-1"><strong>Tipo:</strong> <span class="badge bg-${compra.tipoCompra === 'detallada' ? 'primary' : 'warning'}">${compra.tipoCompra}</span></p>
                    ${compra.descripcion ? `<p class="mb-1"><strong>Descripción:</strong> ${compra.descripcion}</p>` : ''}
                </div>
            `;
            
            if (detalles && detalles.length > 0) {
                html += `
                    <h6 class="fw-bold">Productos</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                detalles.forEach(det => {
                    html += `
                        <tr>
                            <td>${det.producto}</td>
                            <td class="text-center">${det.cantidad}</td>
                            <td class="text-end">$${parseFloat(det.precioUnitario).toFixed(2)}</td>
                            <td class="text-end">$${parseFloat(det.subtotal).toFixed(2)}</td>
                        </tr>
                    `;
                });
                
                html += `
                        </tbody>
                    </table>
                `;
            }
            
            html += `
                <div class="border-top pt-3 text-end">
                    <h5 class="fw-bold">Total: $${parseFloat(compra.total).toFixed(2)}</h5>
                </div>
            `;
            
            content.innerHTML = html;
            modal.show();
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar el detalle', 'error');
    }
}

// ============ UTILIDADES ============

function getUserId() {
    // Implementar según tu sistema de sesiones
    // Por ahora retorna null, debes adaptar esto
    return null;
}

function showAlert(message, type = 'info') {
    // Implementa tu sistema de alertas
    // Puedes usar Bootstrap alerts, SweetAlert, etc.
    alert(message);
}