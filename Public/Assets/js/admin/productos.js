
document.addEventListener('DOMContentLoaded', function() {
    // Cargar datos iniciales
    loadCategories();
    loadProducts();
    setupFormHandlers();
});

function setupFormHandlers() {
    // Configurar formulario de crear producto
    setupCreateForm();
    // Configurar formulario de editar producto
    setupEditFormHandler();
}

function setupCreateForm() {
    const form = document.getElementById('productoForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        
        fetch('index.php?pg=admin&action=createProduct', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Error de red');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Producto creado exitosamente');
                form.reset();
                loadProducts();
            } else {
                throw new Error(data.error || 'Error al crear producto');
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

    // Vista previa de imagen para el formulario de crear
    const inputImagen = form.querySelector('input[type="file"]');
    if (inputImagen) {
        inputImagen.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                // Validar tamaño (2MB max)
                if (this.files[0].size > 2 * 1024 * 1024) {
                    alert('La imagen es demasiado grande. El tamaño máximo es 2MB.');
                    this.value = '';
                    return;
                }
                
                // Validar tipo
                if (!this.files[0].type.startsWith('image/')) {
                    alert('Por favor selecciona un archivo de imagen válido.');
                    this.value = '';
                    return;
                }
            }
        });
    }
};

function loadCategories() {
    fetch('index.php?pg=admin&action=getCategories')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const selects = ['categoria', 'edit_categoria'];
                selects.forEach(id => {
                    const sel = document.getElementById(id);
                    if (!sel) return;
                    // keep placeholder option
                    sel.innerHTML = '<option value="">Seleccione...</option>';
                    data.data.forEach(cat => {
                        const opt = document.createElement('option');
                        opt.value = cat.idCategoria;
                        opt.textContent = cat.nombre;
                        sel.appendChild(opt);
                    });
                });
            }
        })
        .catch(err => console.error('Error cargando categorías:', err));
}

function loadProducts() {
    fetch('index.php?pg=admin&action=getProducts')
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
    products.forEach(p => {
        const tr = document.createElement('tr');
        const imgPath = p.imagen ? `assets/img/${p.imagen}` : (p.categoria_imagen ? `assets/img/${p.categoria_imagen}` : 'assets/img/products/default.jpg');
        tr.innerHTML = `
            <td><img src="${imgPath}" style="width:50px;height:50px;object-fit:cover"></td>
            <td>${p.nombre}</td>
            <td>${p.categoria}</td>
            <td>${p.tipo}</td>
            <td>${p.precioCompra || ''}</td>
            <td>${p.precioVenta || ''}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="openEdit(${p.idProducto})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="deleteProduct(${p.idProducto})">Eliminar</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function openEdit(id) {
    fetch(`index.php?pg=admin&action=getProduct&id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return alert('Producto no encontrado');
            const p = data.data;
            document.getElementById('edit_id').value = p.idProducto;
            document.getElementById('edit_nombre').value = p.nombre;
            document.getElementById('edit_categoria').value = p.idCategoria;
            document.getElementById('edit_tipo').value = p.tipo;
            document.getElementById('edit_precioCompra').value = p.precioCompra;
            document.getElementById('edit_precioVenta').value = p.precioVenta;
            document.getElementById('current_image').src = p.imagen ? `assets/img/${p.imagen}` : 'assets/img/products/default.jpg';
            document.getElementById('imagen_actual').value = p.imagen || '';

            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        });
}

function setupEditFormHandler() {
    const form = document.getElementById('editForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        
        const fd = new FormData(form);
        fetch('index.php?pg=admin&action=updateProduct', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Producto actualizado exitosamente');
                const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                if (modal) modal.hide();
                loadProducts();
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al actualizar: ' + err.message);
        })
        .finally(() => {
            submitBtn.disabled = false;
        });
    });
}

function deleteProduct(id) {
    if (!confirm('¿Eliminar producto?')) return;
    fetch(`index.php?pg=admin&action=deleteProduct&id=${id}`, {
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