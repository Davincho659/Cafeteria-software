// ============================================================================
// GESTIÓN DE MESAS - Sistema de administración
// ============================================================================

let tablesData = [];
let editingTableId = null;

// Elementos del DOM
const getEl = (id) => document.getElementById(id);

// Al cargar el documento
document.addEventListener('DOMContentLoaded', () => {
    console.log('📋 Sistema de gestión de mesas iniciado');
    
    // Cargar datos iniciales
    loadTables();
    loadStatistics();
    
    // Event listeners
    const searchInput = getEl('searchTable');
    const filterStatus = getEl('filterStatus');
    const tableForm = getEl('tableForm');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterTables);
    }
    
    if (filterStatus) {
        filterStatus.addEventListener('change', filterTables);
    }
    
    if (tableForm) {
        tableForm.addEventListener('submit', handleSaveTable);
    }
});

// ============================================================================
// CARGAR DATOS
// ============================================================================

async function loadTables() {
    try {
        console.log('Cargando mesas...');
        
        const response = await fetch('?pg=sales&action=GetTables');
        const data = await response.json();
        
        if (data.success) {
            tablesData = data.data || [];
            renderTables(tablesData);
            console.log(`✅ ${tablesData.length} mesas cargadas`);
        } else {
            throw new Error(data.error || 'Error al cargar mesas');
        }
    } catch (error) {
        console.error('Error cargando mesas:', error);
        showError('Error al cargar las mesas');
    }
}

async function loadStatistics() {
    try {
        const response = await fetch('?pg=tables&action=getStatistics');
        const data = await response.json();
        
        if (data.success) {
            const stats = data.data;
            updateStatistics(stats);
        }
    } catch (error) {
        console.warn('No se pudieron cargar las estadísticas:', error);
    }
}

function updateStatistics(stats) {
    const setStatValue = (id, value) => {
        const el = getEl(id);
        if (el) el.textContent = value || 0;
    };
    
    setStatValue('statTotalTables', stats.totalMesas || tablesData.length);
    setStatValue('statAvailableTables', stats.mesasLibres || 0);
    setStatValue('statOccupiedTables', stats.mesasOcupadas || 0);
    setStatValue('statActiveSales', stats.ventasActivas || 0);
}

// ============================================================================
// RENDERIZADO
// ============================================================================

function renderTables(tables) {
    const tbody = getEl('tablesTableBody');
    const emptyState = getEl('emptyState');
    
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!tables || tables.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    No se encontraron mesas
                </td>
            </tr>`;
        if (emptyState) emptyState.style.display = 'block';
        return;
    }
    
    if (emptyState) emptyState.style.display = 'none';
    
    tables.forEach(table => {
        const isOccupied = table.idVenta !== null && table.idVenta !== undefined;
        const numero = table.numero || table.numeroMesa;
        const nombre = table.nombre || table.nombreMesa || 'Sin nombre';
        
        const statusBadge = isOccupied 
            ? '<span class="badge bg-danger">Ocupada</span>'
            : '<span class="badge bg-success">Disponible</span>';
        
        const saleInfo = isOccupied 
            ? `<button class="btn btn-sm btn-info" onclick="viewSaleDetails(${table.idVenta})">
                 <i class="fa-solid fa-eye"></i> Ver venta
               </button>`
            : '<span class="text-muted">—</span>';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="fw-bold">${table.idMesa}</td>
            <td class="text-center">
                <span class="badge bg-primary" style="font-size: 1rem; padding: 6px 12px;">
                    #${numero}
                </span>
            </td>
            <td>${nombre}</td>
            <td class="text-center">${statusBadge}</td>
            <td class="text-center">${saleInfo}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary" 
                        onclick="editTable(${table.idMesa})"
                        title="Editar">
                    <i class="fa-solid fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" 
                        onclick="deleteTable(${table.idMesa})"
                        ${isOccupied ? 'disabled title="No se puede eliminar una mesa ocupada"' : 'title="Eliminar"'}>
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>`;
        tbody.appendChild(row);
    });
    
    // Actualizar estadísticas locales
    const available = tables.filter(t => !t.idVenta).length;
    const occupied = tables.filter(t => t.idVenta).length;
    
    updateStatistics({
        totalMesas: tables.length,
        mesasLibres: available,
        mesasOcupadas: occupied,
        ventasActivas: occupied
    });
}

// ============================================================================
// FILTRADO
// ============================================================================

function filterTables() {
    const searchTerm = getEl('searchTable')?.value.toLowerCase() || '';
    const statusFilter = getEl('filterStatus')?.value || '';
    
    let filtered = tablesData;
    
    // Filtrar por búsqueda
    if (searchTerm) {
        filtered = filtered.filter(table => {
            const numero = String(table.numero || table.numeroMesa || '');
            const nombre = String(table.nombre || table.nombreMesa || '').toLowerCase();
            return numero.includes(searchTerm) || nombre.includes(searchTerm);
        });
    }
    
    // Filtrar por estado
    if (statusFilter) {
        filtered = filtered.filter(table => {
            const isOccupied = table.idVenta !== null && table.idVenta !== undefined;
            if (statusFilter === 'libre') return !isOccupied;
            if (statusFilter === 'ocupada') return isOccupied;
            return true;
        });
    }
    
    renderTables(filtered);
}

// ============================================================================
// MODAL Y FORMULARIO
// ============================================================================

function openTableModal() {
    editingTableId = null;
    
    const modal = getEl('tableModal');
    const title = getEl('tableModalTitle');
    const form = getEl('tableForm');
    
    if (title) {
        title.innerHTML = '<i class="fa-solid fa-plus"></i> Agregar Mesa';
    }
    
    if (form) form.reset();
    
    getEl('tableId').value = '';
    getEl('tableNumber').value = '';
    getEl('tableName').value = '';
    
    if (modal && typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

function editTable(idMesa) {
    
    const table = tablesData.find(t => parseInt(t.idMesa,10) === idMesa);
    console.log(table);
    if (!table) {
        showError('Mesa no encontrada');
        console.error(tablesData);
        return;
    }
    
    editingTableId = idMesa;
    
    const modal = getEl('tableModal');
    const title = getEl('tableModalTitle');
    
    if (title) {
        title.innerHTML = '<i class="fa-solid fa-edit"></i> Editar Mesa';
    }
    
    getEl('tableId').value = idMesa;
    getEl('tableNumber').value = table.numero || table.numeroMesa;
    getEl('tableName').value = table.nombre || table.nombreMesa;
    
    if (modal && typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

async function handleSaveTable(e) {
    e.preventDefault();
    
    const idMesa = getEl('tableId').value;
    const numero = getEl('tableNumber').value;
    const nombre = getEl('tableName').value;
    const tipoSel = document.querySelector('input[name="tableTipo"]:checked');
    const tipo = tipoSel ? tipoSel.value : 'mesa';

    if (!numero || !nombre) {
        showError('Por favor completa todos los campos requeridos');
        return;
    }
    
    const btnSave = getEl('btnSaveTable');
    if (btnSave) {
        btnSave.disabled = true;
        btnSave.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
    }
    
    try {
        const action = idMesa ? 'updateTable' : 'createTable';
        const payload = { numero, nombre, tipo };
        if (idMesa) payload.idMesa = idMesa;
        
        const response = await fetch(`?pg=tables&action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(idMesa ? 'Mesa actualizada correctamente' : 'Mesa creada correctamente');
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(getEl('tableModal'));
            if (modal) modal.hide();
            
            // Recargar datos
            await loadTables();
            await loadStatistics();
        } else {
            throw new Error(data.error || 'Error al guardar la mesa');
        }
    } catch (error) {
        console.error('Error guardando mesa:', error);
        showError(error.message);
    } finally {
        if (btnSave) {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fa-solid fa-check"></i> Guardar';
        }
    }
}

async function deleteTable(idMesa) {
    const table = tablesData.find(t => parseInt(t.idMesa,10) === idMesa);
    if (!table) return;
    
    const numero = table.numero || table.numeroMesa;
    const nombre = table.nombre || table.nombreMesa;
    
    if (!confirm(`¿Estás seguro de eliminar la mesa #${numero} - ${nombre}?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }
    
    try {
        const response = await fetch('?pg=tables&action=deleteTable', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idMesa })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Mesa eliminada correctamente');
            await loadTables();
            await loadStatistics();
        } else {
            throw new Error(data.error || 'Error al eliminar la mesa');
        }
    } catch (error) {
        console.error('Error eliminando mesa:', error);
        showError(error.message);
    }
}

// ============================================================================
// FUNCIONES AUXILIARES
// ============================================================================

function viewSaleDetails(idVenta) {
    openBillWindow(idVenta);
}

function showSuccess(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: message,
            timer: 2000,
            showConfirmButton: false
        });
    } else {
        alert(message);
    }
}

function showError(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message
        });
    } else {
        alert('Error: ' + message);
    }
}
