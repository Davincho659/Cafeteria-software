
<?php require loadView('Layouts/header'); ?>

<div class="container p-2">
    <div class="row">
        <div class="col-md-5">
            <div class="card mt-2">
                <div class="card-body">
                    <form id="productoForm" class="p-2">
                        <h5 class="mb-4 text-center text-primary fw-bold">
                            🛒 Registro de Producto
                        </h5>
                        <!-- Categoría -->
                        <div class="mb-3">
                            <label for="categoria" class="form-label fw-semibold">Categoría</label>
                            <select id="categoria" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="venta">Fritos</option>
                            <option value="insumo">Insumo (Inventario)</option>
                            </select>
                        </div>

                        <!-- Nombre del producto -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-semibold">Nombre del Producto</label>
                            <input 
                            type="text" 
                            id="nombre" 
                            class="form-control" 
                            placeholder="Nombre del producto" 
                            required
                            >
                        </div>

                        <!-- Tipo de producto -->
                        <div class="mb-3">
                            <label for="tipo" class="form-label fw-semibold">Tipo de Producto</label>
                            <select id="tipo" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="venta">Producto de Venta</option>
                            <option value="insumo">Insumo</option>
                            </select>
                        </div>

                        <!-- Imagen del producto -->
                        <div class="mb-3">
                            <label for="imagen" class="form-label fw-semibold">Imagen del Producto</label>
                            <input 
                            type="file" 
                            id="imagen" 
                            class="form-control" 
                            accept="image/*"
                            >
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

