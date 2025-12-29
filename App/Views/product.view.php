<?php require loadView('Layouts/header'); ?>

<link rel="stylesheet" href="assets/css/sales.css">
<div class="container-fluid py-4">
    <!-- HEADER -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        📦 Administración de Productos y Categorías
                    </h3>
                    <p class="text-muted mb-0">
                        Gestiona las categorías y productos disponibles en el sistema.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-lg" onclick="openCategoryModal()">
                        <i class="fa-solid fa-plus"></i> Agregar Categoría
                    </button>
                    <button class="btn btn-primary btn-lg" onclick="openProductModal()">
                        <i class="fa-solid fa-plus"></i> Agregar Producto
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- ===============   CATEGORÍAS   ===================== -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold text-success mb-3">
                        📂 Categorías Registradas
                    </h5>

                    <table class="table table-sm table-bordered align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th width="120">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="categories">
                            <tr>
                                <td colspan="3" class="text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===================================================== -->
        <!-- ===================   PRODUCTOS   ================== -->
        <!-- ===================================================== -->
        <div class="col-lg-8">

            <!-- Buscador y controles -->
            <div class="d-flex justify-content-end mb-3">
                <div class="input-group" style="max-width: 300px;">
                    <input type="text" id="search"
                           class="form-control"
                           placeholder="Buscar producto">
                    <span class="input-group-text">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                </div>
            </div>

            <!-- Tabla Productos -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold text-primary mb-3">
                        🛒 Productos Registrados
                    </h5>
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th>Compra</th>
                                <th>Venta</th>
                                <th width="120">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="products">
                            <tr>
                                <td colspan="7" class="text-center text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: Crear/Editar Categoría -->
<!-- ========================================== -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="categoryModalTitle">
                    <i class="fa-solid fa-plus"></i> Agregar Categoría
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" method="post" enctype="multipart/form-data"
                      action="?pg=product&action=createCategorie">

                    <input type="hidden" id="cat_id" name="id" value="">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Nombre de la categoría
                        </label>
                        <input type="text"
                               id="cat_nombre"
                               name="nombre"
                               class="form-control"
                               placeholder="Ej: Bebidas, Postres..."
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Imagen de la categoría
                        </label>
                        <input type="file"
                               id="cat_imagen"
                               name="image"
                               class="form-control"
                               accept="image/*">
                        <small class="text-muted d-block mt-1">Máximo 2MB. Formatos: JPG, PNG, GIF</small>
                    </div>

                    <div class="modal-footer mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-save"></i> Guardar Categoría
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: Crear/Editar Producto -->
<!-- ========================================== -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="productModalTitle">
                    <i class="fa-solid fa-plus"></i> Agregar Producto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="productForm" method="post" enctype="multipart/form-data"
                      action="?pg=product&action=createProduct">

                    <input type="hidden" id="prod_id" name="id" value="">

                    <div class="row">

                        <!-- Categoría -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                Categoría
                            </label>
                            <select id="prod_categoria" name="categoria" class="form-select" required>
                                <option value="">Seleccione...</option>
                            </select>
                        </div>

                        <!-- Tipo -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                Tipo de producto
                            </label>
                            <select id="prod_tipo" name="tipo" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="venta">Producto de venta</option>
                                <option value="insumo">Insumo</option>
                            </select>
                        </div>

                        <!-- Nombre -->
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">
                                Nombre del producto
                            </label>
                            <input type="text"
                                   id="prod_nombre"
                                   name="nombre"
                                   class="form-control"
                                   placeholder="Nombre del producto"
                                   required>
                        </div>

                        <!-- Imagen -->
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">
                                Imagen del producto
                            </label>
                            <input type="file"
                                   id="prod_imagen"
                                   name="imagen"
                                   class="form-control"
                                   accept="image/*">
                            <small class="text-muted d-block mt-1">Máximo 2MB. Formatos: JPG, PNG, GIF</small>
                        </div>

                        <!-- Precios -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                Precio de compra
                            </label>
                            <input type="number"
                                   id="prod_precioCompra"
                                   step="0.01"
                                   name="precioCompra"
                                   class="form-control"
                                   placeholder="Ej: 25.000">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                Precio de venta
                            </label>
                            <input type="number"
                                   id="prod_precioVenta"
                                   step="0.01"
                                   name="precioVenta"
                                   class="form-control"
                                   placeholder="Ej: 45.000">
                        </div>

                    </div>

                    <div class="modal-footer mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save"></i> Guardar Producto
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require loadView('Layouts/footer'); ?>
<script src="assets/js/admin/productos.js"></script>
