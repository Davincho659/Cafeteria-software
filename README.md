# Sistema POS Cafetería – Documentación Completa

Este documento describe de forma didáctica y exhaustiva los flujos recientes del sistema POS de cafetería. Se abordan módulos funcionales, relaciones entre componentes, cálculos clave, reportes y modelo de datos.

## Propósito del sistema
- Gestionar ventas, compras, gastos y caja diaria en una sola interfaz.
- Mantener el inventario actualizado con costos promedio y alertas de stock.
- Ofrecer reportes operativos y financieros (ventas, compras, gastos, inventario, caja, top productos y rentabilidad).
- Controlar sesiones de usuario y trazabilidad de movimientos de caja.

## Módulos principales
- Caja (apertura, cierre, estado y movimientos).
- Ventas (carritos, mesas, facturación, métodos de pago, impacto en inventario y caja).
- Compras (proveedores, entradas de stock y costos).
- Gastos (producto vs externo, impacto en caja y rentabilidad).
- Proveedores (registro y uso en compras).
- Inventario (entradas/salidas, stock mínimo, costos promedios, ajustes).
- Reportes (ventas, compras, gastos, inventario, caja, top productos, rentabilidad/profitability).
- Autenticación y sesiones (control de usuario para caja y operaciones).

## Caja
### Apertura
- Se requiere sesión iniciada. Al autenticar, si no hay caja activa, se muestra modal para ingresar saldo inicial.
- Endpoint: `?pg=cash&action=open` (POST JSON: `saldoInicial`, `notas`).
- Valida: usuario en sesión, monto >= 0, que no exista caja abierta.
- Registra en `cajas` y deja estado ACTIVA.

### Cierre
- Botón de cierre ejecuta `?pg=cash&action=close` (POST/GET según integración) con `idCaja`, `saldoReal`, `notas`.
- Valida que la caja esté activa y corresponda al usuario/instancia.
- Calcula diferencia entre saldo teórico y real, registra movimientos finales y marca la caja CERRADA.
- Devuelve resumen (ingresos, egresos, apertura, efectivo actual, detalle movimientos).

### Estado y resumen
- `?pg=cash&action=active` devuelve caja activa.
- `?pg=cash&action=summary` devuelve resumen de caja activa o una caja específica.
- Relación con movimientos: las ventas generan ingresos de caja; compras y gastos pueden generar egresos si se pagan desde caja.

### Control de usuarios y trazabilidad
- Las aperturas y cierres usan el usuario de sesión (`$_SESSION['usuario_id']`).
- Los movimientos de caja (ingresos/egresos) quedan asociados al usuario que ejecuta la acción (ventas, compras, gastos, ajustes).

## Ventas
### Flujo completo
1. Selección de productos (catálogo con filtros de categoría y búsqueda).
2. Construcción de carrito o venta en mesa (tabs). Cada tab mantiene productos y cantidades.
3. Confirmación de pago: métodos de pago (efectivo, transferencia). Puede haber descuento o ajustes (si está implementado en UI).
4. Creación de venta: valida caja activa, calcula total servidor-side, actualiza inventario y registra detalles.
5. Emisión de factura (vista `bill.view.php`).
6. Si es mesa: se permiten operaciones de agregar/quitar productos y luego cerrar la mesa (completeTableSale).

### Cálculos
- Total venta: suma de `precioUnitario * cantidad` por ítem.
- Impuestos/descuentos: se aplican si existen campos en el payload; de lo contrario, el total es neto.
- Métodos de pago: guardados en `ventas.metodoPago` (efectivo/transferencia). Impactan caja solo si son de caja (efectivo).

### Impacto
- Caja: ventas en efectivo generan movimiento de ingreso y aumentan saldo de caja activa.
- Inventario: descuenta stock por cada producto vendido; usa control de stock y puede disparar alertas de stock bajo.
- Reportes: la venta queda disponible en reportes de ventas, caja y rentabilidad.

## Compras
### Flujo
1. Selección de proveedor (obligatorio o “sin proveedor” si se permite).
2. Registro de productos comprados con cantidades y costos unitarios.
3. Actualización de inventario: se incrementa stock y se recalcula costo promedio del producto.
4. Opcional: si se paga en efectivo, registra egreso en caja.

### Relación con proveedores
- Cada compra referencia `proveedores.idProveedor` para trazabilidad y control de costos.
- Reportes de compras muestran proveedor, tipo de compra y totales.

### Impacto
- Inventario: entrada de stock y actualización de costo promedio.
- Caja: si se paga desde caja, crea un egreso (reduce saldo de caja activa).
- Rentabilidad: el costo de compra alimenta el costo promedio usado en el reporte de rentabilidad.

## Gastos
### Flujo
- Registro de gasto con tipo: `producto` (asociado a insumos) o `externo` (servicios, otros).
- Campos: descripción, monto, fecha, tipo, notas.

### Impacto
- Caja: los gastos se consideran egresos de caja cuando se pagan en efectivo.
- Rentabilidad: los gastos se restan en el cálculo de ganancia real.

## Proveedores
### Flujo de registro
- Alta de proveedor con datos básicos (nombre, contacto, etc.).

### Relación
- Las compras referencian proveedores para control de costos y reportes.

## Inventario
### Entradas y salidas
- Entradas: compras, ajustes positivos.
- Salidas: ventas, ajustes negativos.

### Control de stock
- Se consulta stock por producto; hay alertas de stock bajo (stock mínimo configurable). Visibles en reportes y dashboards.

### Costos y ajustes
- Costo promedio: tras cada compra, costo promedio = (stock previo * costoPrevio + nuevoIngreso * costoCompra) / (stockPrevio + nuevoIngreso).
- Ajustes: permiten corregir stock y costos cuando hay diferencias físicas vs sistema.

## Promedios, margen y rentabilidad
- **Costo promedio**: valor medio ponderado por unidades en inventario.
- **Ganancia (profit)**: ventas netas − costo de ventas − gastos.
- **Margen**: ganancia / ventas netas. Expresa eficiencia (%).
- **Utilidad**: sinónimo de ganancia en este contexto.

## Reportes
### Ventas (`sales.report.php`)
- Lista de ventas con filtros (idVenta, rango de fechas, método de pago, montos). Paginado.
- Datos: idVenta, fecha, método de pago, total, link a factura.

### Compras (`purchases.report.php`)
- Filtros por proveedor, tipo de compra, fecha. Paginado. Muestra totales por página y globales.

### Gastos (`expenses.report.php`)
- Filtros por tipo y fecha. Paginado. Totales de monto y conteo.

### Inventario (`inventory.report.php`)
- Muestra alertas, stock bajo y valor total de inventario.

### Caja (`cashRegister.report.php`)
- Resumen de caja activa: apertura, ingresos, egresos, efectivo actual, detalles de movimientos.

### Top Productos (`topProducts.report.php`)
- Ranking de productos más vendidos del día: cantidad vendida e ingreso generado.

### Profitability (`profitability.report.php`)
- KPIs de rentabilidad: ventas totales, costos de venta (usando costo promedio), gastos, compras y ganancia real.
- Cálculos:
  - `totalVentas`: suma de ventas completadas en rango.
  - `totalCostos`: suma de (costo promedio * cantidad vendida) por línea de detalle.
  - `totalGastos`: suma de gastos en rango.
  - `totalCompras`: suma de compras en rango.
  - `gananciaReal = totalVentas - totalCostos - totalGastos`.
  - `margenPorcentaje = gananciaReal / totalVentas * 100` (si hay ventas > 0).

### Daily (modal desde ventas)
- Versión ligera de ventas del día, con filtros rápidos, paginación y acceso a factura.

## Base de datos (tablas clave)
- `usuarios`: credenciales y roles.
- `cajas`: apertura/cierre de caja, estado, usuario.
- `movimientos_caja`: ingresos/egresos detallados (ventas, compras, gastos, ajustes).
- `ventas`: cabecera de ventas (cliente opcional, método de pago, total, fecha, usuario).
- `detalles_venta`: líneas de productos vendidos (cantidad, precio, costo de referencia).
- `productos`: catálogo, precio de venta, costo promedio, stock, stock mínimo.
- `inventario` o movimientos: entradas/salidas por operación.
- `proveedores`: datos de proveedor.
- `compras` y `detalles_compra`: cabecera y líneas de compra (costo unitario, cantidad).
- `gastos`: registro de egresos, tipo y monto.
- `mesas`: si aplica para ventas en mesa.

## Conexión entre flujos (de principio a fin)
- **Login → Caja**: Al iniciar sesión, si no hay caja activa, se exige apertura con saldo inicial.
- **Ventas**: Requieren caja activa. Al crear venta: descuenta stock, registra detalles, genera ingreso de caja (si efectivo) y queda en reportes.
- **Compras**: Al registrar compra: incrementa stock y recalcula costo promedio. Si se paga en efectivo, genera egreso de caja.
- **Gastos**: Al registrar gasto: genera egreso de caja y afecta rentabilidad.
- **Reportes**: Consumidores de datos consolidados: usan modelos para calcular totales y KPIs.
- **Cierre de caja**: Consolida ingresos/egresos del día, devuelve resumen final y bloquea nuevas ventas hasta próxima apertura.

## Vistas y presentación
- Layouts: Header/Footer y Reports-layout para navegación.
- Vistas específicas para cada reporte con filtros, tablas y KPI cards.
- Modales: apertura de caja en login; reporte diario como overlay en ventas.

## Ejemplo de flujo completo
1. Usuario inicia sesión → abre caja con $200.
2. Vende 3 cafés a $5 cada uno (efectivo):
   - Caja +$15
   - Inventario: −3 unidades de café
3. Registra compra de 10 cafés a $2:
   - Caja −$20 (si paga en efectivo)
   - Inventario +10 unidades; costo promedio se recalcula.
4. Registra gasto de limpieza $5:
   - Caja −$5
5. Consulta Profitability del día:
   - Ventas $15, Costos $? (depende de costo promedio), Gastos $5, Ganancia = Ventas − Costos − Gastos.
6. Cierra caja con conteo real: guarda resumen y detiene nuevas ventas hasta abrir de nuevo.

## Mantenimiento y escalabilidad
- Mantener la consistencia de caja: toda operación de efectivo debe registrar movimiento de caja.
- Asegurar que las ventas siempre validen stock y caja activa.
- Al agregar nuevos métodos de pago, decidir si impactan caja (efectivo) o no (transferencia, tarjeta).
- Para nuevos reportes, reutilizar los modelos existentes y paginación AJAX.
- Costos: preservar el cálculo de costo promedio al recibir compras; no recalcular hacia atrás sin control.

## Archivos clave
- Controladores: `App/Controllers/*Controller.php` (caja, ventas, compras, gastos, reportes).
- Modelos: `App/Models/*.php` (Sales, Purchases, Expenses, Products, Inventory, CashRegister, Suppliers, Tables).
- Vistas: `App/Views/*.view.php` y `App/Views/Reports/*.report.php`.
- JS principal ventas: `Public/Assets/js/Sales.js`.
- JS reportes admin: `Public/Assets/js/admin/reports.js`.
- Ruteo: `Public/Index.php`.

---
Este README busca que un desarrollador pueda entender, mantener y escalar el sistema con claridad técnica y funcional.
