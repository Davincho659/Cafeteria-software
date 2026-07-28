// Gastos - Sistema POS
let productsCache = [];

document.addEventListener('DOMContentLoaded', function() {
    loadProductsForExpense();
    loadExpenses();
    initializeEvents();
});

function initializeEvents() {
    document.getElementById('productExpenseForm').addEventListener('submit', saveProductExpense);
    document.getElementById('externalExpenseForm').addEventListener('submit', saveExternalExpense);
    document.getElementById('btnRefreshExpenses').addEventListener('click', loadExpenses);
    document.getElementById('btnFilterExpenses').addEventListener('click', loadExpenses);
}

// ============ PRODUCTOS PARA GASTO ============

async function loadProductsForExpense() {
    try {
        const response = await fetch('?pg=purchases&action=getProducts');
        const data = await response.json();
        if (data.success) {
            productsCache = data.data;
            renderExpenseProductSelect();
        }
    } catch (error) {
        console.error('Error loading products:', error);
        alert('Error al cargar productos');
    }
}

function renderExpenseProductSelect() {
    const select = document.getElementById('expenseProductSelect');
    select.innerHTML = '<option value="">Seleccione un producto</option>';
    productsCache.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.idProducto;
        opt.textContent = `${p.nombre} (${p.categoria})`;
        select.appendChild(opt);
    });
}

// ============ GUARDAR GASTO PRODUCTO ============

async function saveProductExpense(e) {
    e.preventDefault();
    const idProducto = parseInt(document.getElementById('expenseProductSelect').value);
    const cantidad = parseFloat(document.getElementById('expenseCantidad').value);
    const motivo = document.getElementById('expenseMotivo').value.trim();
    const montoValue = document.getElementById('expenseMonto').value;
    const monto = montoValue ? parseFloat(montoValue) : null;
    const idUsuario = getUserId();

    if (!idProducto || !cantidad || !motivo) {
        alert('Complete los campos requeridos');
        return;
    }

    const payload = { idProducto, cantidad, motivo, monto, idUsuario };
    try {
        const resp = await fetch('?pg=expenses&action=createProductExpense', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (data.success) {
            alert('Gasto de producto registrado');
            document.getElementById('productExpenseForm').reset();
            loadExpenses();
        } else {
            alert(data.error || 'Error al registrar el gasto');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al registrar gasto');
    }
}

// ============ GUARDAR GASTO EXTERNO ============

async function saveExternalExpense(e) {
    e.preventDefault();
    const concepto = document.getElementById('externalConcepto').value.trim();
    const monto = parseFloat(document.getElementById('externalMonto').value);
    const descripcion = document.getElementById('externalDescripcion').value.trim();
    const idUsuario = getUserId();

    if (!concepto || !monto) {
        alert('Complete los campos requeridos');
        return;
    }

    const payload = { concepto, monto, descripcion, idUsuario };
    try {
        const resp = await fetch('?pg=expenses&action=createExternalExpense', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (data.success) {
            alert('Gasto externo registrado');
            document.getElementById('externalExpenseForm').reset();
            loadExpenses();
        } else {
            alert(data.error || 'Error al registrar el gasto');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al registrar gasto');
    }
}

// ============ LISTADO DE GASTOS ============

async function loadExpenses() {
    const tipo = document.getElementById('filterExpenseTipo').value;
    const fechaDesde = document.getElementById('filterExpenseDateFrom').value;
    const fechaHasta = document.getElementById('filterExpenseDateTo').value;

    let url = '?pg=expenses&action=getExpenses';
    const params = [];
    if (tipo) params.push(`tipo=${tipo}`);
    if (fechaDesde) params.push(`fechaDesde=${fechaDesde}`);
    if (fechaHasta) params.push(`fechaHasta=${fechaHasta}`);
    if (params.length > 0) url += '&' + params.join('&');

    try {
        const resp = await fetch(url);
        const data = await resp.json();
        if (data.success) {
            renderExpenses(data.data);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar gastos');
    }
}

function renderExpenses(items) {
    const tbody = document.getElementById('expensesTable');
    tbody.innerHTML = '';
    if (!items || items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay gastos registrados</td></tr>';
        return;
    }
    items.forEach(g => {
        const tr = document.createElement('tr');
        const fecha = new Date(g.fechaRegistro);
        const tipoClass = g.tipo === 'producto' ? 'warning' : 'secondary';
        const concepto = g.tipo === 'producto' ? (g.producto || '-') : (g.concepto || '-');
        tr.innerHTML = `
            <td>${fecha.toLocaleString()}</td>
            <td><span class="badge bg-${tipoClass}">${g.tipo}</span></td>
            <td>${concepto}</td>
            <td class="text-center">${g.cantidad || '-'}</td>
            <td class="text-end fw-bold">$${parseFloat(g.monto || 0).toFixed(2)}</td>
            <td><small>${g.usuario || '-'}</small></td>
        `;
        tbody.appendChild(tr);
    });
}

function getUserId() {
    return null;
}
