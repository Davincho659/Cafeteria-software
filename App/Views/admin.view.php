
<?php require loadView('Layouts/header'); ?>

<div class="container p-2">
    <div class="row">
        <div class="col-md-5">
            <div class="card mt-2">
                <div class="card-body">
                    <form id="productoForm" class="p-2" method="post" enctype="multipart/form-data" action="?pg=admin&action=createProduct">
                        <h5 class="mb-4 text-center text-primary fw-bold">
                            🛒 Registro de Producto
                        </h5>
                        <!-- Categoría -->
                        <div class="mb-3">
                            <label for="categoria" class="form-label fw-semibold">Categoría</label>
                            <select id="categoria" name="categoria" class="form-select" required>
                            <option value="">Seleccione...</option>
                            </select>
                        </div>

                        <!-- Nombre del producto -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-semibold">Nombre del Producto</label>
                            <input 
                            type="text" 
                            id="nombre" name="nombre" 
                            class="form-control" 
                            placeholder="Nombre del producto" 
                            required
                            >
                        </div>

                        <!-- Tipo de producto -->
                        <div class="mb-3">
                            <label for="tipo" class="form-label fw-semibold">Tipo de Producto</label>
                            <select id="tipo" name="tipo" class="form-select" required>
                            <option value="">Seleccione.</option>
                            <option value="venta">Producto de Venta</option>
                            <option value="insumo">Insumo</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Imagen del Producto</label>
                            <input type="file" name="imagen" class="form-control" accept="image/*">
                        </div>

                        <!-- Precios -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                            <label for="precioCompra" class="form-label fw-semibold">Precio de Compra</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                id="precioCompra" 
                                name="precioCompra" 
                                class="form-control" 
                                placeholder="Ej: 25.000"
                            >
                            </div>
                            <div class="col-md-6 mb-3">
                            <label for="precioVenta" class="form-label fw-semibold">Precio de Venta</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                id="precioVenta" 
                                name="precioVenta" 
                                class="form-control" 
                                placeholder="Ej: 45.000"
                            >
                            </div>
                        </div>

                        <!-- Botón -->
                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-primary ">
                            Guardar Producto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <form class="form-inline ">
                <input type="text" class="form-control ms-4"  placeholder="Buscar" id="search">
                <button class="btn" type="submit" id="button">
                <span class="input-group-text" id="basic-addon1"><i class="fa-solid fa-magnifying-glass"></i></span></button>
            </form>
            <table class="table table-sm table-bordered mt-2"> 
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Categoria</th>
                        <th>Tipo</th>
                        <th>Precio de Compra</th>
                        <th>Precio de Venta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="products">

                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require loadView('Layouts/Footer'); ?>
<script src="assets/js/admin/productos.js"></script>

<!-- Modal de Edición -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Editar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" method="post" enctype="multipart/form-data" action="?pg=admin&action=updateProduct">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="idProducto">
                    <div class="mb-3">
                        <label for="edit_categoria" class="form-label">Categoría</label>
                        <select id="edit_categoria" name="categoria" class="form-select" required>
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_nombre" class="form-label">Nombre</label>
                        <input type="text" id="edit_nombre" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_tipo" class="form-label">Tipo</label>
                        <select id="edit_tipo" name="tipo" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="venta">Producto de Venta</option>
                            <option value="insumo">Insumo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Imagen Actual</label>
                        <div id="current_image_container" class="mb-2">
                            <img id="current_image" src="" alt="Imagen actual" style="max-width: 200px; max-height: 200px; object-fit:cover">
                        </div>
                        <label class="form-label">Subir nueva imagen (opcional)</label>
                        <input type="file" name="imagen" class="form-control" accept="image/*">
                        <input type="hidden" name="imagen_actual" id="imagen_actual">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="edit_precioCompra" class="form-label">Precio de Compra</label>
                            <input type="number" step="0.01" id="edit_precioCompra" name="precioCompra" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_precioVenta" class="form-label">Precio de Venta</label>
                            <input type="number" step="0.01" id="edit_precioVenta" name="precioVenta" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

