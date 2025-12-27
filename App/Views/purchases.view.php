<?php
require_once __DIR__ . '/../Models/Providers.php';
require_once __DIR__ . '/../Models/products.php';

$providersModel = new Providers();
$productsModel = new Products();
$providers = $providersModel->getAll();
$products = $productsModel->getAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">📦 Gestión de Compras</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Lista de Compras</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="purchasesTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Proveedor</th>
                                                    <th>Fecha</th>
                                                    <th>Total</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($purchases as $purchase): ?>
                                                <tr>
                                                    <td><?php echo $purchase['idCompra']; ?></td>
                                                    <td><?php echo $purchase['proveedor_nombre']; ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($purchase['fechaCompra'])); ?></td>
                                                    <td>$<?php echo number_format($purchase['total'], 2); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="viewPurchaseDetails(<?php echo $purchase['idCompra']; ?>)">
                                                            <i class="fas fa-eye"></i> Ver
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Nueva Compra</h5>
                                </div>
                                <div class="card-body">
                                    <form id="purchaseForm">
                                        <div class="form-group">
                                            <label for="provider">Proveedor</label>
                                            <select class="form-control" id="provider" name="idProveedor" required>
                                                <option value="">Seleccionar Proveedor</option>
                                                <?php foreach ($providers as $provider): ?>
                                                <option value="<?php echo $provider['idProveedor']; ?>"><?php echo $provider['nombre']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div id="productsContainer">
                                            <div class="product-row mb-3">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <label>Producto</label>
                                                        <select class="form-control product-select" name="productos[]" required>
                                                            <option value="">Seleccionar Producto</option>
                                                            <?php foreach ($products as $product): ?>
                                                            <option value="<?php echo $product['idProducto']; ?>" data-price="<?php echo $product['precioCompra']; ?>">
                                                                <?php echo $product['nombre']; ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-3">
                                                        <label>Cantidad</label>
                                                        <input type="number" class="form-control quantity-input" name="cantidades[]" min="1" required>
                                                    </div>
                                                    <div class="col-3">
                                                        <label>Precio</label>
                                                        <input type="number" class="form-control price-input" name="precios[]" step="0.01" min="0" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-secondary btn-sm" id="addProductBtn">
                                            <i class="fas fa-plus"></i> Agregar Producto
                                        </button>

                                        <div class="form-group mt-3">
                                            <label>Total: $<span id="totalAmount">0.00</span></label>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-save"></i> Crear Compra
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de compra -->
<div class="modal fade" id="purchaseDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de Compra</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="purchaseDetailsContent">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Calcular total automáticamente
    function calculateTotal() {
        let total = 0;
        $('.product-row').each(function() {
            const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
            const price = parseFloat($(this).find('.price-input').val()) || 0;
            total += quantity * price;
        });
        $('#totalAmount').text(total.toFixed(2));
    }

    // Actualizar precio cuando se selecciona producto
    $(document).on('change', '.product-select', function() {
        const selectedOption = $(this).find('option:selected');
        const price = selectedOption.data('price') || 0;
        $(this).closest('.product-row').find('.price-input').val(price);
        calculateTotal();
    });

    // Recalcular total cuando cambian cantidad o precio
    $(document).on('input', '.quantity-input, .price-input', calculateTotal);

    // Agregar nueva fila de producto
    $('#addProductBtn').click(function() {
        const productRow = $('.product-row').first().clone();
        productRow.find('input').val('');
        productRow.find('select').val('');
        $('#productsContainer').append(productRow);
    });

    // Enviar formulario
    $('#purchaseForm').submit(function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        $.ajax({
            url: '?pg=purchases&action=create',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    Swal.fire('Éxito', result.message, 'success');
                    location.reload();
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error al procesar la solicitud', 'error');
            }
        });
    });
});

function viewPurchaseDetails(idCompra) {
    $.ajax({
        url: '?pg=purchases&action=getDetails&id=' + idCompra,
        type: 'GET',
        success: function(response) {
            const data = JSON.parse(response);
            let html = `
                <div class="row">
                    <div class="col-6">
                        <strong>Proveedor:</strong> ${data.purchase.proveedor_nombre}<br>
                        <strong>Fecha:</strong> ${new Date(data.purchase.fechaCompra).toLocaleString()}<br>
                        <strong>Total:</strong> $${parseFloat(data.purchase.total).toFixed(2)}
                    </div>
                </div>
                <hr>
                <h6>Productos:</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            data.details.forEach(detail => {
                html += `
                    <tr>
                        <td>${detail.producto_nombre}</td>
                        <td>${detail.cantidad}</td>
                        <td>$${parseFloat(detail.precioUnitario).toFixed(2)}</td>
                        <td>$${parseFloat(detail.subtotal).toFixed(2)}</td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            $('#purchaseDetailsContent').html(html);
            $('#purchaseDetailsModal').modal('show');
        }
    });
}
</script>
