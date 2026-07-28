# 🛠️ Instalar el sistema POS en un computador nuevo

Guía para montar el sistema **vacío** (sin datos de prueba) en el computador del negocio.
Al terminar, el dueño podrá entrar y empezar a cargar sus productos e inventario reales.

> ⏱️ Toma unos 20 minutos. No necesitas saber de servidores.

---

## Antes de empezar

Necesitas en el computador destino:

- **XAMPP** (trae Apache + MariaDB + PHP). Descárgalo de `https://www.apachefriends.org`
  y elige una versión con **PHP 8.0 o superior**.
- La **carpeta del proyecto** (`Cafeteria-software`) copiada en una USB o por GitHub.

---

## Paso 1 — Instalar XAMPP

1. Ejecuta el instalador de XAMPP y acepta las opciones por defecto.
2. Al terminar, abre el **Panel de Control de XAMPP**.
3. Pulsa **Start** en **Apache** y en **MySQL**. Ambos deben quedar en verde.

> Si Apache no arranca, casi siempre es porque el puerto 80 está ocupado (Skype, IIS).
> En el panel: `Config → Service and Port Settings → Apache` y cambia el puerto a `8080`.
> Si haces esto, todas las direcciones de esta guía llevarán `:8080`
> (ej. `http://localhost:8080/...`).

---

## Paso 2 — Copiar el proyecto

Copia la carpeta completa del proyecto dentro de la carpeta `htdocs` de XAMPP:

```
C:\xampp\htdocs\Cafeteria-software
```

Debe quedar así: `C:\xampp\htdocs\Cafeteria-software\Public\Index.php`

---

## Paso 3 — Crear la base de datos

1. Abre en el navegador: `http://localhost/phpmyadmin`
2. Click en **Nueva** (menú izquierdo).
3. Nombre de la base de datos: **`cafeteria_software`**
4. Cotejamiento: **`utf8mb4_general_ci`**
5. Click en **Crear**.

---

## Paso 4 — Cargar la estructura y los datos iniciales

Con la base de datos `cafeteria_software` seleccionada en phpMyAdmin:

1. Ve a la pestaña **Importar**.
2. **Seleccionar archivo** → elige `install/schema.sql` → botón **Continuar**.
   *(crea las 16 tablas y las 8 vistas — todavía sin datos)*
3. Repite la importación, ahora con **`install/seed.sql`** → **Continuar**.
   *(crea el usuario administrador y las unidades de medida)*

Debe aparecer un mensaje verde de éxito en ambas.

> 💡 **¿Prefieres la consola?** Desde la carpeta del proyecto:
> ```bash
> C:\xampp\mysql\bin\mysql.exe -u root cafeteria_software < install/schema.sql
> ```

---

## Paso 5 — Entrar al sistema

Abre en el navegador:

```
http://localhost/Cafeteria-software/Public/Index.php?pg=login
```

Entra con el usuario inicial:

| Usuario | PIN    |
|---------|--------|
| `admin` | `1234` |

---

## Paso 6 — ⚠️ Asegurar el sistema (hazlo de inmediato)

1. **Cambia el PIN del administrador**
   Menú → **Usuarios** → editar `admin` → escribe un PIN nuevo → Guardar.
   *(El PIN `1234` es público: cualquiera que lea esta guía lo conoce.)*

2. **Crea los usuarios del personal**
   Menú → **Usuarios** → rol **Empleado** para los meseros/cajeros.
   El empleado solo ve Ventas, Mesas, Compras, Productos y Proveedores;
   no accede a reportes, dashboard, inventario, gastos ni configuración.

3. **Configura los datos del negocio**
   Menú → **Configuración**: nombre, logo, colores y los datos que salen impresos
   en el tiquete (NIT, dirección, teléfono).

---

## Paso 7 — Cargar la información del negocio

En este orden:

1. **Productos → Categorías**: crea las categorías (Fritos, Bebidas, …).
2. **Productos**: crea cada producto.
   - **Tipo `Producto de venta`** → lo que el cliente compra.
   - **Tipo `Insumo`** → materia prima (maíz, café, aceite). No se vende directo.
   - **Maneja stock**: actívalo si quieres controlar existencias de ese producto.
   - **Unidad**: `Unidad` para cosas contables; `Kilogramo`/`Libra`/`Bulto` para
     lo que se pesa; `Litro` para líquidos.
   - **Código de barras**: opcional, para escanear con el lector.
3. **Proveedores**: los que te venden la mercancía.
4. **Compras**: registra las compras. Esto **sube el inventario** y alimenta el
   costo promedio de cada producto (base de los reportes de ganancia).
5. **Mesas**: si atiendes en mesas, créalas y ubícalas arrastrándolas en el plano.

---

## Uso diario

1. **Abrir caja** al empezar el día, con la base en efectivo.
2. **Vender** desde la pantalla de Ventas.
3. **Registrar consumo de insumos** (Inventario → botón 🍴) cuando saques materia
   prima: escribes *cuánto sacaste*, no cuánto quedó.
4. **Cerrar caja** al final: el sistema dice cuánto **debe haber en el cajón** y tú
   cuentas el efectivo con la calculadora táctil. Las transferencias (Nequi /
   Bancolombia) se muestran aparte porque **ese dinero no está en el cajón**.

---

## Copias de seguridad (¡importante!)

Las ventas del negocio están en esa base de datos. Haz una copia periódica:

```bash
C:\xampp\mysql\bin\mysqldump.exe -u root cafeteria_software > respaldo.sql
```

Guarda el archivo `respaldo.sql` fuera del computador (USB, Google Drive).
Para restaurarlo, se importa igual que en el Paso 4.

> Cuando el sistema pase a un servidor en internet, esto se automatiza
> (respaldo diario + copia externa).

---

## Problemas comunes

| Síntoma | Causa y solución |
|---|---|
| Página en blanco o error de conexión | MySQL no está iniciado en el panel de XAMPP. |
| `Unknown database 'cafeteria_software'` | Faltó el Paso 3, o el nombre quedó mal escrito. |
| `Access denied for user 'root'` | El MySQL tiene contraseña. Ponla en `App/Core/Config.php` (`DB_PASS`) o usa la variable de entorno `DB_PASS`. |
| Entra pero no hay productos | Es lo esperado: el sistema arranca vacío (Paso 7). |
| Los cambios de estilo no se ven | Recarga con `Ctrl + F5`. |
| Un botón deja de responder tras actualizar | Recarga la página una vez (`F5`): la protección CSRF necesita el token nuevo. |

---

## Nota técnica

Algunas columnas nuevas (`movimientos_caja.metodoPago`, el valor `consumo` de
`inventario.tipoReferencia`, las columnas de mesas y la tabla `configuracion`)
**se crean solas** la primera vez que la aplicación las necesita, así que no hay
que correr migraciones a mano al actualizar el sistema.
