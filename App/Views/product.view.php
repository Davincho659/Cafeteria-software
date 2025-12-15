<?php require loadView('Layouts/header'); ?>

<div class="container-fluid py-4">
    <center>
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-dark">
                Administración de Productos y Categorías
            </h3>
            <p class="text-muted mb-0">
                Gestiona las categorías y los productos disponibles en el sistema.
            </p>
        </div>
    </div></center>

    <div class="row g-4">

        <!-- ===============   CATEGORÍAS   ===================== -->
        <div class="col-lg-4">

            <!-- Crear Categoría -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold text-success mb-3">
                        📂 Registro de Categoría
                    </h5>

                    <form method="post" enctype="multipart/form-data"
                          action="?pg=product&action=createCategorie">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Nombre de la categoría
                            </label>
                            <input type="text"
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
                                   name="imagen"
                                   class="form-control"
                                   accept="image/*">
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-success">
                                Guardar Categoría
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Listado de Categorías -->
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="fw-bold text-secondary mb-3">
                        📋 Categorías registradas
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
                            <!-- JS / PHP -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ===================================================== -->
        <!-- ===================   PRODUCTOS   ================== -->
        <!-- ===================================================== -->
        <div class="col-lg-8">

            <!-- Formulario Productos -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold text-primary mb-4">
                        🛒 Registro de Producto
                    </h5>

                    <form method="post" enctype="multipart/form-data"
                          action="?pg=product&action=createProduct">

                        <div class="row">

                            <!-- Categoría -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    Categoría
                                </label>
                                <select id="categoria" name="categoria" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>

                            <!-- Tipo -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    Tipo de producto
                                </label>
                                <select name="tipo" class="form-select" required>
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
                                       name="imagen"
                                       class="form-control"
                                       accept="image/*">
                            </div>

                            <!-- Precios -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    Precio de compra
                                </label>
                                <input type="number"
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
                                       step="0.01"
                                       name="precioVenta"
                                       class="form-control"
                                       placeholder="Ej: 45.000">
                            </div>

                        </div>

                        <div class="d-grid mt-2">
                            <button class="btn btn-primary">
                                Guardar Producto
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Buscador -->
            <div class="d-flex justify-content-end mt-4">
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
            <div class="card shadow-sm mt-3">
                <div class="card-body">
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
                            <!-- JS / PHP -->
                        </tbody>
                    </table>
                    
                </div>
            </div>

        </div>
    </div>
</div>

<?php require loadView('Layouts/footer'); ?>
<script src="assets/js/admin/productos.js"></script>
