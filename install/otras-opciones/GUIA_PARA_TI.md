# 🎒 Guía para montar el POS donde el dueño

Esta guía es **para ti**, no para el dueño. Es lo que haces el día que vas al negocio
a dejarle el sistema funcionando para que empiece a cargar sus productos.

> 🎯 **Meta del día:** que quede el sistema abierto en su PC, en modo pantalla completa
> (se ve como una aplicación), vacío y listo para que él cargue productos e inventario.
> **No** se paga servidor todavía.

---

## ANTES DE IR — prepara esto en tu casa

### 1. Lleva estas cosas en una USB

| Qué | De dónde |
|---|---|
| Carpeta `Cafeteria-software` completa | `C:\xampp\htdocs\Cafeteria-software` |
| Instalador de XAMPP (PHP 8+) | `apachefriends.org` |

> ⚠️ **Antes de copiar la carpeta**, borra `storage/sessions/*` y `storage/logs/*`
> si tienen archivos. No son necesarios y se recrean solos.

### 2. Anota estos datos (los vas a necesitar allá)

- PIN nuevo del administrador (**no dejes `1234`**).
- Nombre exacto del negocio, dirección, teléfono y NIT (para el tiquete).
- Nombres de los empleados que van a usar el sistema.

---

## EN EL NEGOCIO — pasos

### Paso 1 · Instalar XAMPP (~10 min)
1. Ejecuta el instalador, todo por defecto.
2. Abre el **Panel de Control de XAMPP**.
3. **Start** en **Apache** y en **MySQL** → ambos en verde.
4. Marca la casilla de inicio automático (o configúralo como servicio) para que
   arranque solo cuando prendan el computador.

> Si Apache no arranca: el puerto 80 está ocupado. `Config → Service and Port Settings`
> → cámbialo a `8080`. Recuerda que entonces la dirección lleva `:8080`.

### Paso 2 · Copiar el proyecto (~2 min)
Copia la carpeta a `C:\xampp\htdocs\Cafeteria-software`
Verifica que exista: `C:\xampp\htdocs\Cafeteria-software\Public\Index.php`

### Paso 3 · Crear la base de datos (~1 min)
Doble click en **`install\instalar.bat`**. Hace todo solo.
Al final muestra la dirección para entrar y los datos de acceso.

### Paso 4 · Entrar y asegurar (~5 min)
Abre: `http://localhost/Cafeteria-software/Public/Index.php?pg=login`
Entra con **admin / 1234** y de inmediato:

1. **Usuarios** → cambia el PIN de `admin`. *(El `1234` está escrito en la guía: cualquiera lo sabe.)*
2. **Usuarios** → crea las cuentas de los empleados con rol **Empleado**.
3. **Configuración** → nombre del negocio, logo, NIT, dirección, teléfono.
   *(Esto es lo que sale impreso en el tiquete.)*

### Paso 5 · Modo aplicación (pantalla completa) (~3 min)

Esto es lo que hace que **se vea como una app** y no como una página web.

1. Click derecho en el Escritorio → **Nuevo → Acceso directo**.
2. Pega esto en la ruta:

```
"C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk --app=http://localhost/Cafeteria-software/Public/Index.php?pg=login
```

3. Nómbralo **POS** y dale un ícono bonito (click derecho → Propiedades → Cambiar icono).
4. Pruébalo: debe abrir a pantalla completa, **sin barra de direcciones**.

> `--kiosk` = pantalla completa sin barras. Se sale con `Alt + F4`.
> Si prefieres que se pueda minimizar, quita `--kiosk` y deja solo `--app=...`.

**Para que arranque solo al prender el PC:** copia ese acceso directo, presiona
`Win + R`, escribe `shell:startup` y pega el acceso directo ahí.

**Que la pantalla no se apague:** Configuración de Windows → Sistema → Inicio/apagado →
Pantalla y Suspensión → **Nunca**.

### Paso 6 · Enseñarle a cargar los productos (~20 min)

Este es el paso más importante del día. El orden correcto es:

1. **Productos → Categorías**: primero las categorías (Fritos, Bebidas, Postres…).
2. **Productos**: uno por uno.
   - **Producto de venta** = lo que el cliente compra.
   - **Insumo** = materia prima (maíz, café, aceite). No se vende suelto.
   - **Maneja stock**: actívalo solo si quiere controlar existencias de ese producto.
   - **Unidad**: `Unidad` para lo contable, `Kilogramo`/`Libra`/`Bulto` para lo que se
     pesa, `Litro` para líquidos.
3. **Proveedores**.
4. **Compras**: aquí sube el inventario y se alimenta el costo real de cada producto
   (es la base de los reportes de ganancia). **Insiste en esto**: si no registra
   compras, los reportes de utilidad no sirven.
5. **Mesas**: si atiende en mesas, se crean y se ubican arrastrándolas en el plano.

### Paso 7 · Respaldo (~5 min) — **no te vayas sin esto**

Crea un archivo `C:\respaldo-pos.bat` con este contenido:

```bat
@echo off
set FECHA=%date:~-4%-%date:~3,2%-%date:~0,2%
C:\xampp\mysql\bin\mysqldump.exe -u root cafeteria_software > "C:\RespaldosPOS\pos-%FECHA%.sql"
echo Respaldo listo en C:\RespaldosPOS
pause
```

Crea la carpeta `C:\RespaldosPOS` y deja un acceso directo en el escritorio.
**Enséñale a darle doble click una vez por semana** y a copiar esos archivos a una
USB o a Google Drive.

> Esos archivos `.sql` son **todo su negocio**. Son también los que vas a usar
> el día que migres al servidor.

---

## Cuando llegue el momento de pasar a la nube

**No se pierde nada.** Este es el proceso, para tu tranquilidad y la del dueño:

1. Él sigue trabajando normal en su PC mientras tú montas el servidor.
2. El día del cambio: generas un respaldo (`respaldo-pos.bat`), lo subes al servidor
   y lo importas. **Todos sus productos, inventario, ventas y configuración quedan
   idénticos** — no se re-teclea nada.
3. Cambias la dirección del acceso directo del escritorio: en vez de `localhost`,
   apunta a `https://tudominio.com`. **Nada más cambia**: la pantalla se ve igual.
4. Su PC queda como respaldo por si se cae el internet.

Por eso conviene cargar los productos **primero en local**: si algo falla en la nube,
su computador sigue teniendo todo.

---

## Checklist antes de irte

- [ ] Apache y MySQL arrancan solos al prender el PC
- [ ] El acceso directo **POS** abre a pantalla completa
- [ ] El PIN de `admin` ya **no** es `1234`
- [ ] Los empleados tienen su usuario y probaron entrar
- [ ] Los datos del negocio salen bien en el tiquete (imprime uno de prueba)
- [ ] La pantalla no se apaga sola
- [ ] El dueño hizo un respaldo él mismo, delante de ti
- [ ] Cargaron juntos al menos 5 productos y una compra, para que quede claro
- [ ] Le dejaste tu teléfono y la carpeta `install/` con la guía suya (`INSTALACION.md`)

---

## Si algo falla

| Síntoma | Solución |
|---|---|
| No abre la página | MySQL/Apache apagados en el Panel de XAMPP |
| `Unknown database` | Volver a correr `install\instalar.bat` |
| `Access denied for user 'root'` | El MySQL tiene contraseña: ponla en `App\Core\Config.php` (`DB_PASS`) |
| La tirilla sale descuadrada | En el diálogo de impresión: márgenes **Ninguno** y escala **100 %** |
| Se ve muy grande o muy pequeño | Ajusta el zoom del sistema en Configuración de Windows → Pantalla |
