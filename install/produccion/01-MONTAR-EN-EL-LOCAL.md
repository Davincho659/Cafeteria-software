# 🏪 Montar el sistema en el PC de la caja (opción recomendada)

Guía completa, de principio a fin. Tiempo total: **una tarde**.

Al terminar: la caja cobra e imprime sin depender del internet, los meseros
toman pedidos desde el celular, y todo se respalda solo.

---

## Antes de ir: qué llevas

- [ ] Una **USB** con:
  - La carpeta `ACTUALIZACION` (el proyecto al día)
  - Los archivos `.bat` y `ajustar-rutas.ps1` de `install/portable/`
  - El **XAMPP en ZIP** descargado de apachefriends.org
  - El paquete de datos del dueño (lo generas en la Parte 1)
- [ ] El PC todo-en-uno táctil
- [ ] La impresora térmica y su cable
- [ ] La clave del WiFi del local

---

# PARTE 1 · Traer los datos del PC del dueño

> Esto se hace en el computador donde él cargó los productos.

### 1.1 Respaldo de seguridad
Doble click en **RESPALDAR AHORA.bat**. Copia la carpeta `RESPALDOS` a tu USB.

### 1.2 Dejar las fotos listas
Abrir el sistema, entrar como administrador y pulsar **Productos → "Revisar fotos"**.

> Sin esto algunos productos pueden llegar sin imagen. Tarda segundos.

### 1.3 Exportar
Doble click en **EXPORTAR PARA SERVIDOR.bat**.

Genera una carpeta con **los datos y las fotos juntos**, y te dice cuántos
productos y fotos lleva: **anota esos números**, los verificarás al final.

Copia esa carpeta a la USB.

---

# PARTE 2 · Preparar el PC de la caja

## 2.1 Windows listo para trabajar todo el día

| Ajuste | Dónde | Valor |
|---|---|---|
| Que no se apague la pantalla | Configuración → Sistema → Inicio/apagado | **Nunca** |
| Que no se suspenda | Lo mismo | **Nunca** |
| Actualizaciones fuera de horario | Windows Update → Horas activas | 6:00 a 23:00 |
| Sin contraseña al iniciar | `netplwiz` → desmarcar "Los usuarios deben escribir…" | — |

> Lo de la suspensión importa: si el equipo se duerme, deja de responder a los celulares.

## 2.2 IP fija para el PC

Los celulares necesitan saber siempre dónde está la caja. Si la IP cambia, dejan
de conectarse.

1. Abre `cmd` y escribe `ipconfig`. Anota la **Puerta de enlace** (normalmente `192.168.1.1`).
2. Configuración → Red e Internet → Wi-Fi → **Editar** en "Configuración IP"
3. Cambia de *Automático (DHCP)* a **Manual**, activa IPv4 y pon:

| Campo | Valor |
|---|---|
| Dirección IP | `192.168.1.50` (una libre de tu rango) |
| Máscara | `255.255.255.0` |
| Puerta de enlace | La que anotaste |
| DNS | `8.8.8.8` |

**Anota esa IP: es la dirección del sistema para los celulares.**

## 2.3 Instalar el sistema

1. Copia la carpeta del paquete a `C:\POS-LaCasaDelPastel`
2. Doble click en **INICIAR POS.bat**
3. Debe abrir el sistema a pantalla completa

Si algo falla, corre **DIAGNOSTICO.bat**: dice exactamente qué falta.

## 2.4 Cargar los datos del dueño

Crea un archivo `importar.bat` dentro de `C:\POS-LaCasaDelPastel` con esto,
cambiando la ruta por donde tengas el archivo `datos.sql`:

```bat
@echo off
set "MYSQL=%~dp0xampp\mysql\bin"
echo Importando los datos del negocio...
"%MYSQL%\mysql.exe" -u root -e "DROP DATABASE IF EXISTS cafeteria_software; CREATE DATABASE cafeteria_software CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
"%MYSQL%\mysql.exe" -u root cafeteria_software < "D:\migracion\datos.sql"
echo Listo.
pause
```

Ejecútalo con el sistema abierto (la base tiene que estar encendida).

Después **copia las fotos**: todo el contenido de la carpeta `img\` del paquete
va dentro de
`C:\POS-LaCasaDelPastel\xampp\htdocs\Cafeteria-software\Public\Assets\img\`,
reemplazando lo que haya.

> ⚠️ Los dos pasos, o los productos quedan sin foto. La base guarda solo el
> nombre del archivo, no la imagen.

## 2.5 Comprobar que llegó todo

Abre el sistema y verifica **con el dueño al lado**:

- [ ] Están todos los productos (compara con el número que anotaste)
- [ ] Cada producto muestra su foto
- [ ] Las categorías están completas
- [ ] El inventario cuadra
- [ ] Los precios de venta y compra son correctos

---

# PARTE 3 · La impresora

## 3.1 Instalarla
1. Conectar por USB y encender
2. Instalar el driver del fabricante
3. Dispositivos e impresoras → click derecho → **Establecer como predeterminada**

> Es obligatorio que sea la predeterminada: la impresión automática usa esa.

## 3.2 Configurar el papel
Click derecho en la impresora → **Preferencias de impresión**:

- **Tamaño de papel**: el de 80 mm (`80mm x 297mm` o parecido). **Nunca A4.**
- **Márgenes**: 0 si existe la opción

## 3.3 Ajustar el ancho en el sistema
**Configuración → Ancho del tiquete (mm)**. Viene en 72.

1. Imprime una factura de prueba
2. ¿Queda blanco a la derecha? Sube a 74, guarda y prueba otra vez
3. Repite hasta que el texto llegue casi al borde
4. ¿Sale cortado? Bájalo 2 mm

## 3.4 Que no pida imprimir dos veces
Se resuelve con el acceso directo del paso 4.1.

---

# PARTE 4 · Dejarlo como aplicación

## 4.1 Acceso directo de la caja

Click derecho en el Escritorio → **Nuevo → Acceso directo**, y pega:

```
"C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk-printing --kiosk --app=http://localhost/Cafeteria-software/Public/Index.php?pg=login
```

Nómbralo **POS** y ponle un ícono.

| Parte | Para qué |
|---|---|
| `--kiosk-printing` | La tirilla sale sin preguntar |
| `--kiosk` | Pantalla completa, sin barra de direcciones |
| `--app=...` | Abre directo en el sistema |

## 4.2 Que arranque solo al prender

1. `Win + R` → escribe `shell:startup` → Enter
2. Copia ahí un acceso directo a **INICIAR POS.bat**

Así, al prender el equipo, el sistema queda listo sin que nadie haga nada.

## 4.3 Los celulares de los meseros

En cada celular, conectado **al WiFi del local**:

1. Abrir Chrome (o Safari en iPhone)
2. Entrar a `http://192.168.1.50/Cafeteria-software/Public/Index.php?pg=login`
   *(con la IP que fijaste en 2.2)*
3. Menú **⋮** → **Agregar a pantalla de inicio**

Queda con el logo de la cafetería en el celular.

> Por red local queda como acceso directo a pantalla completa. La instalación
> como aplicación completa exige `https://`, que solo se tiene con dominio. Para
> tomar pedidos funciona igual.

---

# PARTE 5 · Respaldos (esto no se salta)

El sistema respalda solo al abrir y al cerrar, en `C:\POS-LaCasaDelPastel\RESPALDOS`.
Pero están **en el mismo PC**: si se daña, se pierden con él.

## 5.1 Copia automática a la nube

1. Instalar **Google Drive para escritorio** con la cuenta del negocio
2. Mover la carpeta `RESPALDOS` dentro de la carpeta de Drive
   (o marcarla para sincronizar)

Con eso cada respaldo sube solo.

## 5.2 Probar la restauración

**Hazlo una vez antes de irte.** Un respaldo que nunca se probó no es un respaldo.

Abre `cmd` en `C:\POS-LaCasaDelPastel` y ejecuta, cambiando el nombre del archivo:

```bat
xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE prueba_restore;"
xampp\mysql\bin\mysql.exe -u root prueba_restore < "RESPALDOS\datos_2026-09-09_10-00-00.sql"
xampp\mysql\bin\mysql.exe -u root -e "SELECT COUNT(*) FROM prueba_restore.productos;"
```

Si sale el número de productos, el respaldo sirve. Luego bórrala:

```bat
xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE prueba_restore;"
```

---

# PARTE 6 · Antes de irte

- [ ] Cambiar el PIN de `admin` (no dejar 1234)
- [ ] Crear el usuario de cada empleado con su PIN
- [ ] Imprimir una factura y verla bien en el papel
- [ ] Hacer una venta completa de prueba y anularla
- [ ] Probar desde un celular por el WiFi
- [ ] Apagar y prender el PC: debe abrir solo
- [ ] Probar la restauración del respaldo (5.2)
- [ ] Dejar tu teléfono y el instructivo impreso

---

# Si algo falla

| Síntoma | Qué revisar |
|---|---|
| No abre el sistema | Correr `DIAGNOSTICO.bat` |
| Los celulares no entran | ¿Están en el WiFi del local? ¿Cambió la IP? Ver 2.2 |
| La tirilla sale angosta | Ancho del tiquete (3.3) y papel del driver (3.2) |
| Pide imprimir dos veces | Falta `--kiosk-printing` en el acceso directo |
| Va lento | Revisar espacio en disco |
| Productos sin foto | Faltó copiar la carpeta `img\` (paso 2.4) |
