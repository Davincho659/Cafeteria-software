# 📋 Estado del proyecto — POS "La casa del pastel"

Documento para retomar el desarrollo (por ti o por otro chat) y para llevarlo a producción.

> ℹ️ Este archivo fue **recreado** el 2026-07-27: el `CONTINUACION.md` anterior
> desapareció del disco durante la sesión. Aquí está el estado consolidado.

---

## 1. Qué es y cómo correrlo

- **POS de cafetería**: cobro, mesas, inventario, compras, gastos, reportes, dashboard.
- **Stack**: PHP 8 + MariaDB/MySQL, MVC propio (sin framework), Bootstrap 5, SweetAlert2, FontAwesome.
- **Local**: XAMPP → `http://localhost/Cafeteria-software/Public/Index.php?pg=login`
- **Front controller**: `Public/Index.php`, rutas `?pg=<pagina>&action=<accion>`.
- **BD**: `cafeteria_software`. Credenciales en `App/Core/Config.php` (o variables `DB_*`).
- **Instalación nueva**: ver `install/INSTALACION.md` (o ejecutar `install/instalar.bat`).

### Estructura
```
App/
  Controllers/  (Sales, Tables, Reports, Dashboard, Settings, Users, purchases,
                 inventory, Expenses, Login, Product, suppliers, Cash, Home)
  Models/       (sales, Tables, products, inventory, purchases, cashRegister,
                 Expenses, Users, Settings, Closings, Categories, UnitsOfMeasure, suppliers)
  Core/         (Auth, Csrf, Validator, Conexion, Config, Functions, Init)
  Views/        (+ Layouts/, Reports/, bill.view.php)
Public/
  Index.php     (router + guard de seguridad + CSRF)
  Assets/css, Assets/js  (js/sales/* es el núcleo del POS)
install/        (schema.sql, seed.sql, instalar.bat, INSTALACION.md)
```

---

## 2. Estado: qué está HECHO

### Seguridad (completa)
- PIN con **bcrypt**; `session_regenerate_id(true)` al autenticar.
- **Guard de sesión + roles** (`App/Core/Auth.php`) antes del switch del router.
  `adminOnly`: dashboard, reports, settings, inventory, expenses, **users**.
- `idUsuario` siempre desde la sesión, nunca del cliente.
- **Precio autoritativo**: el servidor usa `precioVenta` de la BD; `detalle_venta.subTotal`
  es columna **GENERADA** (`precio*cantidad` STORED) → total infalsificable.
- **CSRF** (`App/Core/Csrf.php`): token por sesión, `Csrf::enforce()` en `Index.php` para
  POST/PUT/PATCH/DELETE (login exento). Viaja en `<meta name="csrf-token">` y lo reenvía
  **un wrapper global de `fetch`** en `auth-helper.js` → no hubo que tocar cada llamada.
  Rechazo = **HTTP 403** con `{csrf:true}` (403 y no 419: Apache convierte 419 en 500).
- **Inyección SQL** corregida en `Products::getAll` (el filtro `tipo` iba concatenado).
- **Cookies**: `httponly`, `samesite=Lax`, `secure` automático bajo HTTPS, sesión 8 h.
- **Errores**: con `APP_ENV=production` → `display_errors=0` + `storage/logs/php-error.log`.

### Ventas / mesas / pagos
- Carritos de venta y de mesa, transferir venta→mesa, facturar, mesa vacía se libera sola.
- Tablero visual del salón (arrastrable), tipos `mesa` y `barra`, posiciones en %.
- 3 métodos: **Efectivo / Bancolombia / Nequi**. Calculadora táctil de efectivo con devuelta.
- Ventas anuladas excluidas de KPIs y devuelven stock.
- Código de barras (`sales/barcode.js` + `findByBarcode` + `productos.codigoBarras`).

### Factura térmica 80 mm (reescrita)
- El desorden venía de usar **80 mm + márgenes del navegador**; el área imprimible real es
  **72 mm** → `@page { size:80mm auto; margin:0 }` + cuerpo de 72 mm (medido, sin desborde).
- Toma nombre/logo/**NIT/dirección/teléfono** de Configuración (antes estaba quemado
  "Cafetería Bello Horizonte").
- Muestra precio unitario, cajero, mesa, forma de pago y total de artículos.
- Nombres largos envuelven en 2 líneas (antes se cortaban a 20 caracteres).

### Usuarios (`?pg=users`, admin-only)
- CRUD + rol desde la UI. Reglas verificadas: nombre único, PIN ≥ 4, **whitelist de rol**,
  no auto-eliminarse, no quitarse el propio admin, no dejar el sistema sin admin,
  no borrar usuario con ventas (rompería el historial).

### Arqueo de caja — ⚠️ bug financiero grave corregido
- **Antes**: las ventas por Nequi/Bancolombia entraban al efectivo de la caja, así que al
  contar el cajón siempre salía un faltante enorme (~$974 k en los datos de prueba) y el
  arqueo era inútil.
- `movimientos_caja.metodoPago` (auto-migración + **backfill** histórico desde `ventas`).
- `getCajaResumen` expone `totalVentasEfectivo`, `totalVentasTransferencia`,
  `totalEgresosEfectivo` y **`efectivoEsperado` = base + ventas efectivo − salidas efectivo**.
- ⚠️ Se **dejó de usar** la vista SQL `vista_resumen_caja` (mezclaba ambos); el cálculo vive en PHP.
- UI de cierre: desglose claro, aviso de que las transferencias no están en el cajón, y
  **calculadora táctil** para contar (`Public/Assets/css/calculator.css` + funciones
  `*CashCount*` en `home.js`) con atajos por denominación de billete. Avisa del descuadre
  antes de cerrar.

### Insumos (control manual, sin recetas — decisión del dueño)
- `inventario.tipoReferencia` acepta **`consumo`** (auto-migración).
- `Inventory::registrarConsumo()` + acción `registerConsumption`: se anota **cuánto se sacó**
  (ej. 2,5 kg de café para los fritos), no cuánto quedó → sin restas mentales y con
  trazabilidad de en qué se usó. `obtenerConsumoValorizado()` da el costo del consumo.
- Inventario: columna **Tipo** e filtro Todos / De venta / Insumos.
- 🐞 **Bug preexistente corregido**: `initializeEventListeners` (admin/inventory.js) moría por
  excepción porque `btnFilterMovements` no existe en la vista, dejando **sin registrar el
  submit de ajustar stock** y el refresco de alertas. Ahora todo pasa por el helper `on()`.

### Rendimiento
- Reporte de rentabilidad: era **N+1** (una consulta por venta y otra por producto).
  Ahora **una sola consulta** (`Sales::getProfitabilityTotals`) → medido **50× más rápido**
  con 78 ventas; la ventaja crece con el historial. Ventas idénticas al método viejo.
  El costo ahora cae a `precioCompra` si el producto no tiene compras registradas
  (antes lo contaba como **costo 0** e inflaba la ganancia).
- Validado el parseo de fechas (`createFromFormat` podía devolver `false` → error fatal).

### Instalación limpia (`install/`)
- `schema.sql` (16 tablas + 8 vistas + 2 triggers, **sin DEFINER** para que importe en
  cualquier servidor), `seed.sql` (unidades, admin, configuración), `instalar.bat`
  e `INSTALACION.md`. Probado de cero: la app arranca, el login funciona y las
  auto-migraciones se aplican solas.
- Usuario inicial: **`admin` / `1234`** (hash bcrypt verificado). ⚠️ Cambiar al entrar.

---

## 3. Lo que SIGUE (roadmap)

1. **PWA** — `manifest.json` + `service-worker.js` en `Public/` → instalable en la táctil
   y en celulares, pantalla completa. Base para el offline. Gratis.
2. **Tiempo real celular → PC** — empezar con **polling** cada 3–5 s reusando
   `GetTables` / `LoadActiveSales`. Más adelante SSE si hace falta.
3. **Producción**: VPS (Hetzner ~US$5 o Vultr Miami por latencia) + Docker (nginx +
   php-fpm 8.2 + mariadb) + dominio + **HTTPS Let's Encrypt** + **backups diarios**
   (`mysqldump` por cron + copia externa con rclone) y **probar la restauración**.
   Endurecer: `APP_ENV=production`, ufw (22/80/443), SSH con llave, fail2ban,
   phpMyAdmin restringido.
4. **Modo kiosco** en la pantalla táctil: `chrome --kiosk --app=https://...`, sin suspensión.
5. **Hardware**: impresora térmica 80 mm ESC/POS, cajón monedero (RJ11 a la impresora),
   scanner, UPS.
6. **Offline de mostrador** (lo más complejo, al final): Service Worker + IndexedDB con
   cola que sincroniza al volver internet.

---

## 4. Notas de arquitectura CRÍTICAS (para no romper nada)

- **`.body-container { zoom: var(--app-zoom) }`** escala toda la UI.
  ❗ **NO usar `vh`/`vw` para layout** (el zoom los calcula mal). Usar `%` + flex.
  En móvil `--app-zoom:1` y `.body-container{display:block}`.
- **`detalle_venta.subTotal`** es columna **GENERADA**: la BD ignora valores explícitos.
- **Carritos de MESA son "DOM-driven"**: `carts['mesa-X'].products` **no** se llena (solo
  `.total`). Cantidad/eliminar de mesa usan `idDetalleVenta` + servidor + `reloadTableSale`.
  Los carritos normales (`venta1`, `venta2`…) sí usan el array `products`.
- **Carrito activo**: `addToCart` usa `getCurrentTabInfo()` (tab del DOM), pero
  transferir/facturar usan `currentCartId`; se sincronizan con el listener global
  `shown.bs.tab` en `Sales.js`. **Si algo de carritos falla, revisar esto primero.**
- **Pestañas de mesa ocultas**: las mesas se navegan por el tablero "Ver mesas"
  (`.mesa-tab-item { display:none }`), pero sus carritos existen igual.
- **`ventas.metodoPago`** es ENUM `('efectivo','transferencia','bancolombia','nequi')`;
  `transferencia` queda por datos históricos. Whitelist en `SalesController::metodoValido()`.
- **Migraciones "auto"**: `Settings`, `Tables`, `CashRegister` e `Inventory` crean sus
  tablas/columnas en el constructor (`CREATE TABLE IF NOT EXISTS` / `ALTER … ADD COLUMN`).
  **Seguir ese patrón** para columnas nuevas: así no hay que migrar a mano al desplegar.
- **Assets**: siempre `<?= asset('assets/...') ?>` (cache-busting con `filemtime`).
- **CSRF**: cualquier POST nuevo funciona solo si se hace con `fetch` (el wrapper global
  inyecta el token). Un `<form method="post">` tradicional necesita `<?= Csrf::field() ?>`.
- **JS**: registrar listeners con guarda (`if (el)`), nunca `getElementById(x).addEventListener`
  directo sobre elementos que no están en todas las vistas — una excepción corta el resto.

### Cómo probar
- Login por fetch: `POST ?pg=login&action=authenticate` con `{nombre,pin}`.
- Los POST necesitan la cabecera `X-CSRF-Token` (el wrapper lo hace en el navegador;
  con curl hay que leer el `<meta name="csrf-token">` del HTML primero).
- Limpiar datos de prueba en BD tras cada prueba (ventas con `idVenta` altos,
  `UPDATE mesas SET estado='libre'`).
