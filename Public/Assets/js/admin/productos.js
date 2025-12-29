
document.addEventListener('DOMContentLoaded', function() {
    // Cargar datos iniciales
    loadCategories();
    loadProducts();
    setupFormHandlers();
    setupSearchFilter();
});

// ============================================================================
// FUNCIONES DE MODALES
// ============================================================================

function openCategoryModal() {
    document.getElementById('cat_id').value = '';
    document.getElementById('cat_nombre').value = '';
    document.getElementById('cat_imagen').value = '';
    document.getElementById('categoryModalTitle').innerHTML = '<i class="fa-solid fa-plus"></i> Agregar Categoría';
    document.getElementById('categoryForm').action = '?pg=product&action=createCategory';
    
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
}

function openProductModal() {
    document.getElementById('prod_id').value = '';
    document.getElementById('prod_nombre').value = '';
    document.getElementById('prod_categoria').value = '';
    document.getElementById('prod_tipo').value = '';
    document.getElementById('prod_precioCompra').value = '';
    document.getElementById('prod_precioVenta').value = '';
    document.getElementById('prod_imagen').value = '';
    document.getElementById('productModalTitle').innerHTML = '<i class="fa-solid fa-plus"></i> Agregar Producto';
    document.getElementById('productForm').action = '?pg=product&action=createProduct';
    
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

function openEditCategory(id) {
    fetch(`index.php?pg=product&action=getCategory&id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert('Error: ' + (data.error || 'Categoría no encontrada'));
                return;
            }
            const c = data.data;
            document.getElementById('cat_id').value = c.idCategoria;
            document.getElementById('cat_nombre').value = c.nombre;
            document.getElementById('categoryModalTitle').innerHTML = '<i class="fa-solid fa-pencil"></i> Editar Categoría';
            document.getElementById('categoryForm').action = '?pg=product&action=updateCategory';
            
            const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
            modal.show();
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al cargar la categoría: ' + err.message);
        });
}

function openEditProduct(id) {
    fetch(`index.php?pg=product&action=getProduct&id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return alert('Producto no encontrado');
            const p = data.data;
            document.getElementById('prod_id').value = p.idProducto;
            document.getElementById('prod_nombre').value = p.nombre;
            document.getElementById('prod_categoria').value = p.idCategoria;
            document.getElementById('prod_tipo').value = p.tipo;
            document.getElementById('prod_precioCompra').value = p.precioCompra;
            document.getElementById('prod_precioVenta').value = p.precioVenta;
            document.getElementById('productModalTitle').innerHTML = '<i class="fa-solid fa-pencil"></i> Editar Producto';
            document.getElementById('productForm').action = '?pg=product&action=updateProduct';
            
            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al cargar producto');
        });
}

// ============================================================================
// FUNCIONES DE FORMULARIOS
// ============================================================================

function setupFormHandlers() {
    setupCategoryFormHandler();
    setupProductFormHandler();
}

function setupCategoryFormHandler() {
    const form = document.getElementById('categoryForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        const action = form.action.includes('updateCategory') ? 'actualizada' : 'creada';
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Error de red');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(`Categoría ${action} exitosamente`);
                const modal = bootstrap.Modal.getInstance(document.getElementById('categoryModal'));
                if (modal) modal.hide();
                loadCategories();
                loadProducts();
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
            console.error('Error:', error);
        })
        .finally(() => {
            submitBtn.disabled = false;
        });
    });

    // Validar imagen
    const inputImagen = form.querySelector('input[type="file"]');
    if (inputImagen) {
        inputImagen.addEventListener('change', validateImageFile);
    }
}

function setupProductFormHandler() {
    const form = document.getElementById('productForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        const action = form.action.includes('updateProduct') ? 'actualizado' : 'creado';
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Error de red');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(`Producto ${action} exitosamente`);
                const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
                if (modal) modal.hide();
                loadProducts();
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
            console.error('Error:', error);
        })
        .finally(() => {
            submitBtn.disabled = false;
        });
    });

    // Validar imagen
    const inputImagen = form.querySelector('input[type="file"]');
    if (inputImagen) {
        inputImagen.addEventListener('change', validateImageFile);
    }
}

function validateImageFile(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Validar tamaño (2MB max)
    if (file.size > 2 * 1024 * 1024) {
        alert('La imagen es demasiado grande. El tamaño máximo es 2MB.');
        e.target.value = '';
        return;
    }
    
    // Validar tipo
    if (!file.type.startsWith('image/')) {
        alert('Por favor selecciona un archivo de imagen válido.');
        e.target.value = '';
        return;
    }
}

// ============================================================================
// CARGAR DATOS
// ============================================================================

function loadCategories() {
    fetch('index.php?pg=product&action=getCategories')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Actualizar selects en modales
                document.getElementById('prod_categoria').innerHTML = '<option value="">Seleccione...</option>';
                data.data.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat.idCategoria;
                    opt.textContent = cat.nombre;
                    document.getElementById('prod_categoria').appendChild(opt);
                });
                // Mostrar tabla
                showCategories(data.data);
            }
        })
        .catch(err => console.error('Error cargando categorías:', err));
}

function loadProducts() {
    fetch('index.php?pg=product&action=getProducts')
        .then(res => res.json())
        .then(data => {
            if (data.success) showProducts(data.data);
        })
        .catch(err => console.error('Error cargando productos:', err));
}

function showProducts(products) {
    const tbody = document.getElementById('products');
    if (!tbody) return;
    tbody.innerHTML = '';
    
    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No hay productos registrados</td></tr>';
        return;
    }

    products.forEach(p => {
        const tr = document.createElement('tr');
        const imgPath = p.imagen ? `assets/img/products/${p.imagen}` :'assets/img/products/default.png';
        tr.innerHTML = `
            <td class="text-center"><img src="${imgPath}" class="product-img-sm"></td>
            <td>${p.nombre}</td>
            <td>${p.categoria}</td>
            <td><span class="badge bg-info">${p.tipo}</span></td>
            <td class="text-end">${p.precioCompra ? '$' + parseFloat(p.precioCompra).toLocaleString('es-CO') : '-'}</td>
            <td class="text-end">${p.precioVenta ? '$' + parseFloat(p.precioVenta).toLocaleString('es-CO') : '-'}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-primary" onclick="openEditProduct(${p.idProducto})" title="Editar">
                    <i class="fa-solid fa-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteProduct(${p.idProducto})" title="Eliminar">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function showCategories(categories) {
    const tbody = document.getElementById('categories');
    if (!tbody) return;
    tbody.innerHTML = '';
    
    if (categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No hay categorías registradas</td></tr>';
        return;
    }

    categories.forEach(c => {
        const tr = document.createElement('tr');
        const imgPath = c.imagen ? `assets/img/categories/${c.imagen}` : 'assets/img/categories/default.png';
        tr.innerHTML = `
            <td class="text-center"><img src="${imgPath}" class="product-img-sm"></td>
            <td>${c.nombre}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-primary" onclick="openEditCategory(${c.idCategoria})" title="Editar">
                    <i class="fa-solid fa-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteCategory(${c.idCategoria})" title="Eliminar">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// ============================================================================
// ELIMINAR
// ============================================================================

function deleteProduct(id) {
    if (!confirm('¿Eliminar producto?')) return;
    fetch(`index.php?pg=product&action=deleteProduct&id=${id}`, {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Producto eliminado exitosamente');
            loadProducts();
        } else {
            throw new Error(data.error || 'Error al eliminar');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Error: ' + err.message);
    });
}

function deleteCategory(id) {
    if (!confirm('¿Eliminar categoría?')) return;
    fetch(`index.php?pg=product&action=deleteCategory&id=${id}`, {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Categoría eliminada exitosamente');
            loadCategories();
            loadProducts();
        } else {
            throw new Error(data.error || 'Error al eliminar');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Error: ' + err.message);
    });
}

// ============================================================================
// BÚSQUEDA DE PRODUCTOS
// ============================================================================

function setupSearchFilter() {
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => filterProductsBySearch(e.target.value));
    }
}

function filterProductsBySearch(query) {
    const tbody = document.getElementById('products');
    const rows = tbody.querySelectorAll('tr');
    const searchText = String(query).toLowerCase().trim();

    rows.forEach((row) => {
        const nombreCell = row.cells[1]; // Columna nombre
        const nombre = nombreCell ? nombreCell.textContent.toLowerCase() : '';

        const matches = searchText === '' || nombre.includes(searchText);
        row.style.display = matches ? '' : 'none';
    });
}