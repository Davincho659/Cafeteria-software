// Proveedores - Sistema POS
let allSuppliers = [];
let isEditing = false;

document.addEventListener('DOMContentLoaded', function() {
    loadSuppliers();
    initializeEventListeners();
});

function initializeEventListeners() {
    // Form submit
    document.getElementById('supplierForm').addEventListener('submit', saveSupplier);
    
    // Botón cancelar edición
    document.getElementById('btnCancelEdit').addEventListener('click', cancelEdit);
    
    // Refresh
    document.getElementById('btnRefreshSuppliers').addEventListener('click', loadSuppliers);
    
    // Buscador
    document.getElementById('searchSupplier').addEventListener('input', function(e) {
        filterSuppliers(e.target.value);
    });
}

// ============ CARGAR PROVEEDORES ============

async function loadSuppliers() {
    try {
        const response = await fetch('?pg=suppliers&action=getSuppliers');
        const data = await response.json();
        
        if (data.success) {
            allSuppliers = data.data;
            renderSuppliers();
        }
    } catch (error) {
        console.error('Error loading suppliers:', error);
        showAlert('Error al cargar los proveedores', 'error');
    }
}

function renderSuppliers(filter = '') {
    const tbody = document.getElementById('suppliersTable');
    const emptyState = document.getElementById('emptyState');
    
    tbody.innerHTML = '';
    
    const filtered = allSuppliers.filter(s => 
        s.nombre.toLowerCase().includes(filter.toLowerCase()) ||
        (s.telefono && s.telefono.toString().includes(filter))
    );
    
    if (filtered.length === 0) {
        if (allSuppliers.length === 0) {
            tbody.style.display = 'none';
            emptyState.style.display = 'block';
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No se encontraron proveedores</td></tr>';
        }
        return;
    }
    
    tbody.style.display = '';
    emptyState.style.display = 'none';
    
    filtered.forEach(supplier => {
        const tr = document.createElement('tr');
        
        tr.innerHTML = `
            <td>${supplier.idProveedor}</td>
            <td>${supplier.nombre}</td>
            <td>${supplier.telefono || '-'}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary" onclick="editSupplier(${supplier.idProveedor})" title="Editar">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteSupplier(${supplier.idProveedor}, '${supplier.nombre}')" title="Eliminar">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(tr);
    });
}

function filterSuppliers(search) {
    renderSuppliers(search);
}

// ============ CREAR/EDITAR PROVEEDOR ============

async function saveSupplier(e) {
    e.preventDefault();
    
    const supplierId = document.getElementById('supplierId').value;
    const nombre = document.getElementById('supplierName').value.trim();
    const telefono = document.getElementById('supplierPhone').value.trim();
    
    if (!nombre) {
        showAlert('El nombre es requerido', 'warning');
        return;
    }
    
    const supplierData = {
        nombre: nombre,
        telefono: telefono || null
    };
    
    try {
        let url, method;
        
        if (isEditing && supplierId) {
            // Actualizar
            supplierData.idProveedor = parseInt(supplierId);
            url = '?pg=suppliers&action=updateSupplier';
            method = 'POST';
        } else {
            // Crear
            url = '?pg=suppliers&action=createSupplier';
            method = 'POST';
        }
        
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(supplierData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(data.message, 'success');
            resetForm();
            loadSuppliers();
        } else {
            showAlert(data.error || 'Error al guardar el proveedor', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al guardar el proveedor', 'error');
    }
}

function editSupplier(idProveedor) {
    const supplier = allSuppliers.find(s => s.idProveedor == idProveedor);
    if (!supplier) return;
    
    isEditing = true;
    
    document.getElementById('supplierId').value = supplier.idProveedor;
    document.getElementById('supplierName').value = supplier.nombre;
    document.getElementById('supplierPhone').value = supplier.telefono || '';
    
    document.getElementById('formTitle').textContent = 'Editar Proveedor';
    document.getElementById('btnSaveSupplier').innerHTML = '<i class="fa-solid fa-check"></i> Actualizar';
    document.getElementById('btnCancelEdit').style.display = 'block';
    
    // Scroll al formulario
    document.getElementById('supplierName').focus();
}

function cancelEdit() {
    resetForm();
}

function resetForm() {
    isEditing = false;
    
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = '';
    
    document.getElementById('formTitle').textContent = 'Registrar Proveedor';
    document.getElementById('btnSaveSupplier').innerHTML = '<i class="fa-solid fa-check"></i> Guardar';
    document.getElementById('btnCancelEdit').style.display = 'none';
}

// ============ ELIMINAR PROVEEDOR ============

async function deleteSupplier(idProveedor, nombre) {
    if (!confirm(`¿Estás seguro de eliminar el proveedor "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }
    
    try {
        const response = await fetch(`?pg=suppliers&action=deleteSupplier&id=${idProveedor}`, {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Proveedor eliminado exitosamente', 'success');
            loadSuppliers();
            
            // Si estábamos editando este proveedor, limpiar el form
            if (isEditing && document.getElementById('supplierId').value == idProveedor) {
                resetForm();
            }
        } else {
            showAlert(data.error || 'Error al eliminar el proveedor', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al eliminar el proveedor. Puede que tenga compras asociadas.', 'error');
    }
}

// ============ UTILIDADES ============

function showAlert(message, type = 'info') {
    // Implementa tu sistema de alertas
    alert(message);
}