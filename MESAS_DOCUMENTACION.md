# 📊 DOCUMENTACIÓN: SISTEMA DE GESTIÓN DE MESAS

## 📋 TABLA DE CONTENIDOS
1. [Descripción General](#descripción-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Flujos Funcionales](#flujos-funcionales)
4. [Estructura de Datos](#estructura-de-datos)
5. [Endpoints (Backend)](#endpoints-backend)
6. [Funciones Principales (Frontend)](#funciones-principales-frontend)
7. [Integración y Configuración](#integración-y-configuración)
8. [Troubleshooting](#troubleshooting)

---

## 📌 Descripción General

El sistema de **Gestión de Mesas** permite a los usuarios trasferir productos de un carrito de venta a una mesa específica, creando un nuevo tab/pestaña para cada mesa. Los productos se mantienen en memoria (sin persistencia en BD) y pueden ser facturados, modificados o cancelados en cualquier momento.

**Características principales:**
- ✅ Transferencia instantánea de productos: Venta → Mesa
- ✅ Múltiples mesas simultáneas (tabs dinámicos)
- ✅ Control de estado: libre/ocupada
- ✅ Dashboard en tiempo real
- ✅ Compatibilidad total con ventas normales
- ✅ Rendimiento optimizado (sin consultas innecesarias)

---

## 🏗️ Arquitectura del Sistema

### Backend (Servidor PHP)

```
App/
├── Controllers/
│   └── SalesController.php          ← Maneja toda la lógica de ventas/mesas
├── Models/
│   ├── Tables.php                   ← Modelo de datos para mesas
│   ├── Products.php                 ← Productos
│   └── Categories.php               ← Categorías
├── Core/
│   ├── Conexion.php                 ← Conexión BD
│   └── Functions.php                ← Funciones globales
└── Views/
    └── sales.view.php               ← Vista de ventas + mesas
```

### Frontend (Cliente JS)

```
Public/Assets/js/
├── Sales.js                          ← Lógica principal (7 secciones)
│   ├── Sección 1: Variables globales
│   ├── Sección 2: Inicialización
│   ├── Sección 3: Carga de datos
│   ├── Sección 4: Gestión de carritos
│   ├── Sección 5: Gestión de pestañas (ventas)
│   ├── Sección 6: Gestión de mesas ✨
│   └── Sección 7: Calculadora
└── TablesDashboard.js               ← Dashboard de mesas (opcional)
```

---

## 🔄 Flujos Funcionales

### FLUJO 1: Crear Venta y Agregar Productos

```
Usuario abre página de ventas
    ↓
startSystem() → loadCategories() + loadProducts() + loadTables()
    ↓
Usuario selecciona categoría → loadProducts(idCategoria)
    ↓
Usuario hace click en producto → addToCart(product)
    ↓
Producto se agrega a carts['venta1'].products[]
    ↓
updateCart() actualiza UI y total
```

### FLUJO 2: Transferir Productos a Mesa ✨

```
Usuario está en "Venta 2" con 3 productos en carrito
    ↓
Usuario presiona "Agregar a Mesa"
    ↓
openTableSelectionModal(event) valida que hay productos
    ↓
Carga las mesas: fetch('getTables')
    ↓
showTableSelectionPopup(mesas) muestra popup
    ↓
Usuario selecciona "Mesa 5" (estado libre)
    ↓
transferToTable(5, 5) inicia la transferencia
    ↓
Crea nuevo carrito: carts['mesa5'] = {type: 'table', products: [...copia...]}
    ↓
Actualiza estado de mesa: tables[5].estado = 'ocupada'
    ↓
createTableTab(5, 5, 'mesa5') crea nuevo tab "Mesa 5"
    ↓
Vacía carrito original: carts['venta2'].products = []
    ↓
switchToCart('mesa5') cambia a la mesa
    ↓
closeTable() cierra popup
```

### FLUJO 3: Agregar Más Productos a una Mesa

```
Usuario está en tab "Mesa 5"
    ↓
Usuario selecciona productos nuevos (grilla izquierda)
    ↓
addToCart(product) agrega al carrito de mesa actual
    ↓
updateCart() actualiza conteo y total
    ↓
updateTableDashboardItem(5, cantidadProductos) actualiza dashboard
```

### FLUJO 4: Liberar/Cerrar Mesa

```
Usuario presiona X en tab "Mesa 5"
    ↓
releaseTableTab('mesa5', 5) inicia cierre
    ↓
Cambia a otro tab si es necesario (bootstrap.Tab)
    ↓
fetch('releaseTable', {idMesa: 5}) notifica al servidor
    ↓
Actualiza: tables[5].estado = 'libre', cartId = null
    ↓
Elimina: delete carts['mesa5']
    ↓
Remueve DOM: containerTab.remove() + pane.remove()
```

---

## 📦 Estructura de Datos

### Objeto `carts` (Ventas + Mesas)

```javascript
carts = {
  // Pestaña de venta normal
  'venta1': {
    type: 'sale',              // Tipo: venta o mesa
    products: [
      {
        idProducto: 5,
        nombre: 'Empanada',
        categoria: 'Platos',
        imagen: 'products/emp.jpg',
        categoria_imagen: 'categories/platos.jpg',
        precioVenta: 15000,
        cantidad: 2,
        precioTotal: 30000
      }
    ],
    total: 30000,              // Total en pesos
    tableId: null              // null para ventas
  },
  
  // Pestaña de mesa
  'mesa5': {
    type: 'table',             // Tipo mesa
    tableId: 5,                // ID de la mesa en BD
    tableNumber: 5,            // Número para mostrar
    tableName: 'Mesa 5',       // Nombre completo
    products: [...],           // Misma estructura que venta
    total: 95000
  }
}
```

### Objeto `tables` (Estado de Mesas)

```javascript
tables = {
  '1': {
    idMesa: 1,
    numero: 1,
    estado: 'libre',           // 'libre' o 'ocupada'
    cartId: null,              // ID del carrito si está ocupada, null si libre
    productCount: 0            // Cantidad de artículos
  },
  '5': {
    idMesa: 5,
    numero: 5,
    estado: 'ocupada',
    cartId: 'mesa5',           // Apunta a carts['mesa5']
    productCount: 3
  }
}
```

---

## 🔌 Endpoints (Backend)

### 1. **getCategories** (GET)
```
URL: index.php?pg=sales&action=getCategories
Método: GET
Respuesta:
{
  success: true,
  data: [
    { idCategoria: 1, nombre: 'Platos', imagen: 'categories/platos.jpg' },
    ...
  ]
}
```

### 2. **getProducts** (GET)
```
URL: index.php?pg=sales&action=getProducts&idCategory=1
Método: GET
Respuesta:
{
  success: true,
  data: [
    {
      idProducto: 5,
      idCategoria: 1,
      nombre: 'Empanada',
      precioVenta: 15000,
      precioCompra: 8000,
      tipo: 'Alimento',
      imagen: 'products/emp.jpg',
      categoria: 'Platos',
      categoria_imagen: 'categories/platos.jpg'
    },
    ...
  ]
}
```

### 3. **getTables** (GET) ✨
```
URL: index.php?pg=sales&action=getTables
Método: GET
Respuesta:
{
  success: true,
  data: [
    { idMesa: 1, nombre: 'Mesa 1', numero: 1, estado: 'libre' },
    { idMesa: 2, nombre: 'Mesa 2', numero: 2, estado: 'libre' },
    { idMesa: 5, nombre: 'Mesa 5', numero: 5, estado: 'ocupada' },
    ...
  ]
}
```

### 4. **getTable** (GET) ✨
```
URL: index.php?pg=sales&action=getTable&idMesa=5
Método: GET
Respuesta:
{
  success: true,
  data: { idMesa: 5, nombre: 'Mesa 5', numero: 5, estado: 'ocupada' }
}
```

### 5. **updateTableState** (POST) ✨
```
URL: index.php?pg=sales&action=updateTableState
Método: POST
Body: { idMesa: 5, estado: 'ocupada' }
Respuesta:
{
  success: true,
  message: 'Estado de mesa actualizado',
  data: { idMesa: 5, estado: 'ocupada', ... }
}
```

### 6. **releaseTable** (POST) ✨
```
URL: index.php?pg=sales&action=releaseTable
Método: POST
Body: { idMesa: 5 }
Respuesta:
{
  success: true,
  message: 'Mesa liberada',
  data: { idMesa: 5, estado: 'libre' }
}
```

---

## 🎯 Funciones Principales (Frontend)

### SECCIÓN 1: Inicialización
- `startSystem()` - Inicia carga de datos
- `loadCategories()` - GET a getCategories
- `loadProducts(idCategoria)` - GET a getProducts
- `loadTables()` - GET a getTables

### SECCIÓN 2: Carritos
- `getCart(cartId)` - Obtiene un carrito específico
- `addToCart(product)` - Agrega producto al carrito actual
- `updateCart()` - Recalcula total y actualiza UI
- `showCartProducts(cartId)` - Renderiza productos en el panel derecho
- `dropProduct(idProducto, cartId)` - Elimina producto
- `increaseQty(idProducto, cartId)` - Aumenta cantidad
- `decreaseQty(idProducto, cartId)` - Disminuye cantidad

### SECCIÓN 3: Pestañas de Ventas
- `switchToCart(cartId)` - Cambia carrito activo
- `addTabs()` - Crea nueva pestaña de venta
- `dropTab(tabId)` - Elimina pestaña de venta

### SECCIÓN 4: Gestión de Mesas ✨ 🔥
- `openTableSelectionModal(event)` - Abre selector de mesas
- `showTableSelectionPopup(mesas)` - Renderiza lista de mesas
- `transferToTable(tableId, tableNumber)` - **Función principal**: transfiere productos
- `createTableTab(tableId, tableNumber, tableCartId)` - Crea tab para mesa
- `releaseTableTab(tableCartId, tableId)` - Libera mesa y cierra tab
- `updateTableDashboardItem(tableId, productCount)` - Actualiza conteo
- `closeTable(event)` - Cierra popup de mesas

### SECCIÓN 5: Calculadora
- `openCalculator(productId)` - Abre calculadora
- `closeCalculator(event)` - Cierra calculadora
- `addNumber(number)` - Añade dígito
- `deleteLast()` - Borra último dígito
- `clearCalculator()` - Limpia
- `confirmQuantity()` - Confirma cantidad

---

## ⚙️ Integración y Configuración

### 1. **Verificar BD - Tabla `mesas`**

```sql
-- Estructura esperada
CREATE TABLE mesas (
    idMesa INT(11) PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100),
    numero INT UNIQUE,
    estado ENUM('libre', 'ocupada') DEFAULT 'libre',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Datos de ejemplo
INSERT INTO mesas (nombre, numero) VALUES 
('Mesa 1', 1),
('Mesa 2', 2),
('Mesa 3', 3),
('Mesa 4', 4),
('Mesa 5', 5);
```

### 2. **Archivos Modificados**

| Archivo | Cambios |
|---------|---------|
| `SalesController.php` | ✅ Añadidos endpoints: getTables, getTable, updateTableState, releaseTable |
| `Tables.php` | ✅ Añadidos métodos: getById, getByState, updateState, create, delete |
| `Sales.js` | ✅ Refactorizado COMPLETAMENTE: 7 secciones, soporte full para mesas |
| `sales.view.php` | ✅ Actualizado botón "Agregar a Mesa" para usar openTableSelectionModal |
| `TablesDashboard.js` | ✅ NUEVO: Dashboard de mesas |
| `Footer.view.php` | ✅ Añadido script TablesDashboard.js |

### 3. **Verificar Rutas en Index.php**

El archivo `Public/Index.php` ya soporta las nuevas actions. Simplemente asegúrate de que funciona:

```php
// Despacha correctamente:
// index.php?pg=sales&action=getTables       → SalesController::getTables()
// index.php?pg=sales&action=releaseTable    → SalesController::releaseTable()
// etc.
```

---

## 🚀 Guía de Uso

### Como Usuario:

1. **Abrir Sistema:** Usuario accede a `/Public/Index.php?pg=sales`
2. **Crear Venta:** Se abre por defecto con "Venta 1"
3. **Agregar Productos:** Selecciona categoría y hace click en producto
4. **Ir a Mesa:**
   - Presiona "Agregar a Mesa"
   - Selecciona una mesa libre (verde)
   - Productos se mueven, se crea tab "Mesa X"
5. **Agregar Más:** Continúa usando la grilla de productos normalmente
6. **Facturar:** Presiona "Facturar Mesa" para procesar la venta
7. **Liberar:** Presiona X para cerrar la mesa

### Como Desarrollador:

Para extender la funcionalidad, modifica estas secciones:

**Agregar validación extra:**
```javascript
// En transferToTable():
if (!cartObj.products || cartObj.products.length === 0) {
  alert('El carrito está vacío...');
  return;
}
// Aquí agregar lógica extra
```

**Conectar con facturación:**
```javascript
// En createTableTab(), conectar botón:
const facturarBtn = document.getElementById(`btn-procesar-venta-${tableCartId}`);
if (facturarBtn) {
  facturarBtn.addEventListener('click', () => {
    // Llamar tu lógica de facturación
    procesarFacturaMesa(tableCartId, cartObj);
  });
}
```

---

## 🐛 Troubleshooting

### Problema: Mesas no cargan
**Solución:** 
- Verifica que la tabla `mesas` existe y tiene datos
- Revisa console.log (F12 → Console) para errores
- Asegúrate que `loadTables()` se ejecuta en `startSystem()`

### Problema: Transferencia no funciona
**Solución:**
- Valida que el carrito NO esté vacío
- Verifica que la mesa está marcada como `estado='libre'`
- Revisa si `isTransferring` está en true (timeout de transferencia anterior)

### Problema: Dashboard no se ve
**Solución:**
- El dashboard es opcional. Para activarlo llama: `showTablesDashboard()`
- O agregar botón: `<button onclick="toggleTablesDashboard()">Mesas</button>`

### Problema: Productos no se guardan
**Esperado:** Los productos NO se guardan en BD. Solo en memoria (sesión del usuario).  
Si necesitas persistencia, modifica `transferToTable()` para hacer POST a un nuevo endpoint que guarde en BD.

---

## 📈 Optimizaciones Realizadas

1. ✅ **Cache en memoria:** No se recarga el listado de mesas constantemente
2. ✅ **Lazy loading:** `loadTables()` solo al iniciar
3. ✅ **Debouncing:** Flag `isTransferring` evita clicks múltiples
4. ✅ **Sin polling:** Dashboard se actualiza solo al cambiar estado
5. ✅ **Estructura limpia:** Código dividido en 7 secciones lógicas
6. ✅ **Comentarios JSDoc:** Todas las funciones documentadas

---

## 📞 Soporte

Si encuentras algún problema o tienes sugerencias:

1. Revisa esta documentación completa
2. Verifica las funciones específicas de la sección que necesitas
3. Usa console.log para debuggear
4. Revisa las respuestas de los fetch() en Network tab (F12)

---

**Versión:** 1.0  
**Fecha:** Noviembre 2025  
**Estado:** Producción ✅
