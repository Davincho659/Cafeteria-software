<?php require loadView('Layouts/header'); ?>

<link rel="stylesheet" href="<?= asset('assets/css/sales.css') ?>">
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
                                <th>Estado</th>
                                <th width="160">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="products">
                            <tr>
                                <td colspan="8" class="text-center text-muted">Cargando...</td>
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
                        <label class="form-label fw-semibold">Imagen de la categoría</label>
                        <div class="img-picker" data-prefix="cat">
                            <div class="img-picker-tabs">
                                <button type="button" class="ip-tab active" data-mode="file"><i class="fa-solid fa-upload"></i> Subir archivo</button>
                                <button type="button" class="ip-tab" data-mode="url"><i class="fa-solid fa-link"></i> Por enlace</button>
                            </div>
                            <div class="ip-file">
                                <input type="file" id="cat_imagen" name="image" class="form-control" accept="image/*">
                            </div>
                            <div class="ip-url" style="display:none;">
                                <input type="url" id="cat_imagenUrl" name="imageUrl" class="form-control" placeholder="https://... pega el enlace de la imagen">
                                <small class="text-muted d-block mt-1">La imagen se descarga y se guarda en el sistema; no depende del enlace después.</small>
                            </div>
                            <div class="ip-preview" id="cat_preview_box" style="display:none;">
                                <img id="cat_preview" alt="Vista previa">
                                <button type="button" class="ip-clear" title="Quitar imagen"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                            <input type="hidden" name="quitarImagen" id="cat_quitar" value="">
                        </div>
                        <small class="text-muted d-block mt-1">Máx 3MB. JPG, PNG, WEBP o GIF.</small>
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

                    <input type="hidden" id="prod_id" name="idProducto" value="">

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

                        <!-- Unidad de medida -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                Unidad de medida
                            </label>
                            <select id="prod_unidad" name="idUnidadBase" class="form-select">
                                <option value="">Seleccione...</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                ¿Producto maneja inventario?
                            </label>
                            <select id="prod_stock" name="manejaStock" class="form-select">
                                <option value="">Seleccione...</option>
                                <option value="1">Sí, maneja inventario</option>
                                <option value="0">No, no maneja inventario</option>
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

                        <!-- Código de barras (opcional) -->
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">
                                Código de barras <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-barcode"></i></span>
                                <input type="text"
                                       id="prod_codigoBarras"
                                       name="codigoBarras"
                                       class="form-control"
                                       placeholder="Escanea o escribe el código"
                                       autocomplete="off"
                                       inputmode="numeric">
                                <button type="button" class="btn btn-outline-primary" id="btnScanBarcode">
                                    <i class="fa-solid fa-barcode"></i> Escanear
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1" id="scanHint">
                                Toca "Escanear" y pasa el producto por el lector, o escribe el código con el teclado.
                            </small>
                        </div>

                        <!-- Imagen -->
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Imagen del producto</label>
                            <div class="img-picker" data-prefix="prod">
                                <div class="img-picker-tabs">
                                    <button type="button" class="ip-tab active" data-mode="file"><i class="fa-solid fa-upload"></i> Subir archivo</button>
                                    <button type="button" class="ip-tab" data-mode="url"><i class="fa-solid fa-link"></i> Por enlace</button>
                                </div>
                                <div class="ip-file">
                                    <input type="file" id="prod_imagen" name="imagen" class="form-control" accept="image/*">
                                </div>
                                <div class="ip-url" style="display:none;">
                                    <input type="url" id="prod_imagenUrl" name="imagenUrl" class="form-control" placeholder="https://... pega el enlace de la imagen">
                                    <small class="text-muted d-block mt-1">La imagen se descarga y se guarda en el sistema; no depende del enlace después.</small>
                                </div>
                                <div class="ip-preview" id="prod_preview_box" style="display:none;">
                                    <img id="prod_preview" alt="Vista previa">
                                    <button type="button" class="ip-clear" title="Quitar imagen"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                                <input type="hidden" name="quitarImagen" id="prod_quitar" value="">
                            </div>
                            <small class="text-muted d-block mt-1">Máx 3MB. JPG, PNG, WEBP o GIF.</small>
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

<style>
    .img-picker { border: 1px solid var(--border, #ddd); border-radius: 12px; padding: 10px; background: #fff; }
    .img-picker-tabs { display: flex; gap: 8px; margin-bottom: 10px; }
    .ip-tab { flex: 1; padding: 8px 10px; border: 1px solid var(--border, #ddd); background: #f7f2ea;
        border-radius: 8px; font-weight: 600; color: var(--brown-dark, #5B3411); cursor: pointer; font-size: .9rem; }
    .ip-tab.active { background: var(--brown-dark, #5B3411); color: #fff; border-color: var(--brown-dark, #5B3411); }
    .ip-preview { margin-top: 12px; position: relative; display: inline-block; }
    .ip-preview img { max-width: 140px; max-height: 140px; border-radius: 10px; border: 2px solid var(--border, #ddd);
        object-fit: cover; display: block; }
    .ip-clear { position: absolute; top: -8px; right: -8px; width: 26px; height: 26px; border-radius: 50%;
        border: none; background: #c0392b; color: #fff; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,.25); }
</style>

<?php require loadView('Layouts/footer'); ?>
<script src="<?= asset('assets/js/admin/productos.js') ?>"></script>
<script>
// ============================================================================
// SELECTOR DE IMAGEN: 2 opciones (archivo / enlace) + vista previa.
// La imagen por enlace la descarga el servidor y la guarda localmente.
// ============================================================================
(function () {
    function initPicker(picker) {
        const prefix = picker.dataset.prefix;
        const tabs = picker.querySelectorAll('.ip-tab');
        const fileBox = picker.querySelector('.ip-file');
        const urlBox = picker.querySelector('.ip-url');
        const fileInput = picker.querySelector('input[type="file"]');
        const urlInput = picker.querySelector('input[type="url"]');
        const previewBox = document.getElementById(prefix + '_preview_box');
        const previewImg = document.getElementById(prefix + '_preview');
        const quitarInput = document.getElementById(prefix + '_quitar');
        const clearBtn = picker.querySelector('.ip-clear');

        function setMode(mode) {
            tabs.forEach(t => t.classList.toggle('active', t.dataset.mode === mode));
            fileBox.style.display = mode === 'file' ? '' : 'none';
            urlBox.style.display = mode === 'url' ? '' : 'none';
        }
        tabs.forEach(t => t.addEventListener('click', () => setMode(t.dataset.mode)));

        function showPreview(src) {
            if (!src) { previewBox.style.display = 'none'; return; }
            previewImg.src = src;
            previewBox.style.display = 'inline-block';
        }
        previewImg.addEventListener('error', () => { previewBox.style.display = 'none'; });

        // Archivo elegido → preview local + limpiar "quitar"
        fileInput.addEventListener('change', () => {
            const f = fileInput.files[0];
            if (!f) return;
            quitarInput.value = '';
            const r = new FileReader();
            r.onload = e => showPreview(e.target.result);
            r.readAsDataURL(f);
        });

        // Enlace escrito → preview (cliente) + limpiar "quitar"
        const onUrl = () => {
            const u = urlInput.value.trim();
            quitarInput.value = '';
            showPreview(u || null);
        };
        urlInput.addEventListener('input', onUrl);
        urlInput.addEventListener('change', onUrl);

        // Quitar imagen → marca para el backend y limpia
        clearBtn.addEventListener('click', () => {
            fileInput.value = '';
            urlInput.value = '';
            showPreview(null);
            quitarInput.value = '1'; // el servidor la elimina y deja la default
        });

        // Exponer helpers para que productos.js pueda resetear/mostrar la imagen actual
        picker._showCurrent = function (src) { quitarInput.value = ''; showPreview(src); };
        picker._reset = function () {
            fileInput.value = ''; urlInput.value = ''; quitarInput.value = '';
            showPreview(null); setMode('file');
        };
    }

    window.imgPickers = {};
    document.querySelectorAll('.img-picker').forEach(p => { initPicker(p); window.imgPickers[p.dataset.prefix] = p; });
})();
</script>
