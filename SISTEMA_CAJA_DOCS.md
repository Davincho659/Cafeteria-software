# 📦 SISTEMA DE CAJA - DOCUMENTACIÓN COMPLETA

## 🎯 Funcionalidad Implementada

El sistema de caja gestiona el flujo de efectivo con **apertura y cierre profesional**, validando que solo exista una caja activa a la vez.

---

## 📋 Flujo Completo

### 1️⃣ **Apertura de Caja (Login)**

**Cuándo:** Al hacer login, si no hay caja abierta.

**Proceso:**
1. Usuario inicia sesión con nombre + PIN
2. Sistema verifica si existe caja activa
3. Si `cajaAbierta = false` → Muestra modal automáticamente
4. Usuario ingresa **monto inicial** (efectivo en caja al abrir)
5. Se crea registro en tabla `cajas` con estado `abierta`
6. Redirecciona a Home

**Archivos:**
- Vista: `App/Views/Login.view.php` (líneas 50-80: modal, 151-202: JS)
- Controller: `App/Controllers/LoginController.php` (línea 64: verificación)
- Endpoint: `?pg=cash&action=open` (POST)

---

### 2️⃣ **Operaciones Durante el Día**

Todas las transacciones registran movimientos en `movimientos_caja`:

| Tipo | Signo | Registra en |
|------|-------|-------------|
| **Venta** | + (positivo) | `VENTA` → Ingreso |
| **Compra** | - (negativo) | `COMPRA` → Egreso |
| **Gasto** | - (negativo) | `GASTO` → Egreso |

**Validación:** Si intentas hacer venta/compra/gasto sin caja abierta → Error.

**Archivos:**
- Model: `App/Models/cashRegister.php`
  - `registrarIngresoVenta()` - línea 178
  - `registrarEgresoCompra()` - línea 186
  - `registrarEgresoGasto()` - línea 197

---

### 3️⃣ **Cierre de Caja (Panel Admin)**

**Cuándo:** Al final del día, desde `adminHome`.

**Proceso:**
1. En Home aparece alerta azul: **"Caja Activa | Saldo: $XXX"**
2. Botón rojo: **"Cerrar Caja"**
3. Al hacer clic:
   - Obtiene resumen de caja actual (GET `?pg=reports&action=cashRegister&ajax=1`)
   - Muestra modal con:
     - Saldo Inicial
     - Ingresos (Ventas del día)
     - Egresos (Compras + Gastos)
     - **Saldo Calculado** (lo que debería haber)
4. Usuario ingresa **Saldo Real** (cuenta el dinero físico)
5. Sistema calcula diferencia automáticamente:
   - **Verde**: Sobrante (hay más dinero del esperado)
   - **Rojo**: Faltante (hay menos dinero del esperado)
   - **Azul**: Exacto ✓
6. Confirma cierre → POST a `?pg=cash&action=close`
7. Se actualiza registro en `cajas`:
   - `estado = 'cerrada'`
   - `fechaCierre = NOW()`
   - `saldoReal = [monto ingresado]`
   - `saldoCalculado = saldoInicial + movimientos`
8. Recarga página → alerta desaparece

**Archivos:**
- Vista: `App/Views/adminHome.view.php`
  - Líneas 9-21: Alerta de caja activa
  - Líneas 90-170: Modal de cierre
- JS: `Public/Assets/js/home.js`
  - `verificarEstadoCaja()` - línea 18
  - `abrirModalCerrarCaja()` - línea 54
  - `llenarResumenModal()` - línea 84
  - Cálculo de diferencia - línea 111
  - Envío de cierre - línea 145
- Controller: `App/Controllers/CashController.php`
  - `close()` - línea 79

---

## 🗄️ Estructura de Base de Datos

### Tabla `cajas`
```sql
idCaja INT PK AUTO_INCREMENT
idUsuario INT → usuarios
fechaApertura DATETIME (automático)
fechaCierre DATETIME (NULL hasta cierre)
saldoInicial DECIMAL(10,2) (efectivo al abrir)
saldoReal DECIMAL(10,2) (efectivo físico al cerrar)
saldoCalculado DECIMAL(10,2) (saldoInicial + movimientos)
estado ENUM('abierta','cerrada')
notas TEXT
```

### Tabla `movimientos_caja`
```sql
idMovimiento INT PK AUTO_INCREMENT
idCaja INT → cajas
tipo_movimiento ENUM('VENTA','COMPRA','GASTO','AJUSTE')
referencia VARCHAR(50) (ID de venta/compra/gasto)
tipo_referencia VARCHAR(20) ('venta', 'compra', 'gasto')
monto DECIMAL(10,2) (+ ingreso, - egreso)
descripcion VARCHAR(255)
idUsuario INT → usuarios
fecha DATETIME
```

### Vista `vista_resumen_caja`
Calcula automáticamente:
- `totalVentas`, `totalCompras`, `totalGastos`
- `totalIngresos`, `totalEgresos`, `totalNeto`
- `efectivoActual = saldoInicial + totalNeto`
- `diferencia = saldoReal - efectivoActual`

---

## 🔒 Validaciones Implementadas

1. **Solo una caja abierta:** Trigger de BD rechaza INSERT si ya existe caja activa
2. **Autenticación requerida:** Todos los endpoints validan `$_SESSION['usuario_id']`
3. **Saldo >= 0:** No permite montos negativos
4. **Caja activa obligatoria:** Ventas/compras/gastos fallan sin caja abierta
5. **Confirmación de cierre:** Requiere `confirm()` antes de cerrar

---

## 📡 Endpoints API

| Endpoint | Método | Body | Respuesta |
|----------|--------|------|-----------|
| `?pg=cash&action=active` | GET | - | `{success, data: {...caja}}` |
| `?pg=cash&action=open` | POST | `{saldoInicial, notas?}` | `{success, idCaja, data}` |
| `?pg=cash&action=close` | POST | `{idCaja, saldoReal, notas?}` | `{success, message, resumen}` |
| `?pg=reports&action=cashRegister&ajax=1` | GET | - | `{success, data: {resumen completo}}` |

---

## 🚀 Instalación

### Paso 1: Ejecutar SQL
```bash
# En phpMyAdmin, ejecutar:
SISTEMA_CAJA_COMPLETO.sql
```

### Paso 2: Verificar archivos
- ✅ `App/Models/cashRegister.php`
- ✅ `App/Controllers/CashController.php`
- ✅ `App/Views/Login.view.php` (modal apertura)
- ✅ `App/Views/adminHome.view.php` (botón cierre + modal)
- ✅ `Public/Assets/js/home.js`

### Paso 3: Probar flujo
1. Logout
2. Login → debe aparecer modal "Abrir Caja"
3. Ingresa $1000 → confirma
4. Realiza ventas/compras del día
5. Home → clic "Cerrar Caja"
6. Verifica resumen → ingresa saldo real
7. Confirma cierre → debe cerrar correctamente

---

## 🎨 Interfaz

### Modal de Apertura (Login)
- Campo: Monto Inicial
- Botones: Cancelar | Abrir Caja
- Color: Azul (primary)

### Modal de Cierre (adminHome)
- Resumen automático:
  - Saldo Inicial
  - Ingresos (verde)
  - Egresos (rojo)
  - Saldo Calculado (azul)
- Campo: Saldo Real (grande, input-lg)
- Cálculo automático de diferencia
- Botones: Cancelar | Confirmar Cierre
- Color: Rojo (danger)

---

## 🐛 Solución de Problemas

### Modal no aparece en login
**Causa:** Bootstrap JS no cargado.  
**Solución:** Verificar `<script src="bootstrap.bundle.min.js">` en Footer.

### Error "Ya existe caja abierta"
**Causa:** Trigger funcionando correctamente.  
**Solución:** Cerrar la caja actual primero desde adminHome.

### No se registran movimientos
**Causa:** Falta llamada a `cashRegister->registrarIngreso/Egreso()`.  
**Solución:** Verificar que modelos Sales/Purchases/Expenses llamen los helpers.

### Diferencia incorrecta
**Causa:** Movimientos no sumando correctamente.  
**Solución:** Verificar que egresos tengan signo negativo (`-abs($monto)`).

---

## 📊 Reportes

El reporte de Flujo de Caja (`?pg=reports&action=cashRegister`) muestra:
- Resumen actual de caja activa
- Desglose de movimientos
- Diferencias al cerrar
- Histórico de cajas cerradas

---

## ✅ Checklist de Implementación

- [x] Tablas `cajas` y `movimientos_caja` creadas
- [x] Vista `vista_resumen_caja` funcional
- [x] Trigger de caja única
- [x] Modal de apertura en Login
- [x] Botón + modal de cierre en adminHome
- [x] JavaScript para ambos flujos
- [x] Validaciones de seguridad
- [x] Cálculo automático de diferencias
- [x] Integración con ventas/compras/gastos

---

## 🎓 Conceptos Clave

**Saldo Inicial:** Efectivo al abrir la caja.  
**Saldo Calculado:** Saldo inicial + todos los movimientos registrados.  
**Saldo Real:** Dinero físico contado al cerrar.  
**Diferencia:** Saldo Real - Saldo Calculado (debería ser $0).

---

**Implementado por:** GitHub Copilot  
**Fecha:** Enero 2026  
**Versión:** 1.0 Completa
