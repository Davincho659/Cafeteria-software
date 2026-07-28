# 📦 Cómo armar el paquete portable (para ti)

El objetivo: entregar **una sola carpeta** que el dueño copia a su computador y usa
con **doble click**, sin instalar nada, sin permisos de administrador y sin pagar nada.

---

## 1. Descarga XAMPP **portable** (versión ZIP, no el instalador)

Ve a <https://www.apachefriends.org/download.html> y baja la opción **ZIP** (no el .exe).
Descomprímela: obtienes una carpeta `xampp`.

> ⚠️ Importante: la versión **ZIP** no toca el registro de Windows ni pide administrador.
> Es lo que permite que todo sea portable y no rompa nada en el PC del dueño.

---

## 2. Arma esta estructura

```
POS-LaCasaDelPastel\
├── xampp\                       ← la carpeta descomprimida
│   └── htdocs\
│       └── Cafeteria-software\  ← TODO tu proyecto va aquí
├── INICIAR POS.bat
├── APAGAR POS.bat
├── RESPALDAR AHORA.bat
├── EXPORTAR PARA SERVIDOR.bat
└── LEEME PRIMERO.txt
```

Los cuatro `.bat` van en la **raíz**, al lado de la carpeta `xampp` (no dentro).
Cópialos desde `install/portable/`.

### Antes de copiar tu proyecto, límpialo:
- Borra el contenido de `storage/sessions/` y `storage/logs/`.
- **Deja** las carpetas vacías (se recrean solas, pero es más limpio).

---

## 3. Deja la base de datos vacía

El dueño debe empezar sin tus datos de prueba. Con el paquete armado:

1. Arranca el MySQL portable.
2. Borra la base si existe y déjala sin crear: `INICIAR POS.bat` la crea sola
   la primera vez, usando `install/schema.sql` + `install/seed.sql`
   (queda solo el usuario `admin` / PIN `1234`, sin productos ni ventas).

Para verificar que quedó limpia, entra y confirma que **no hay productos**.

---

## 4. Pruébalo ANTES de llevarlo

**En otro computador**, no en el tuyo. Es la única forma de saber que funciona fuera
de tu máquina (tu pregunta era exactamente esa).

- [ ] `INICIAR POS.bat` abre el sistema a pantalla completa
- [ ] Entra con `admin` / `1234`
- [ ] Crea una categoría y un producto de prueba
- [ ] `RESPALDAR AHORA.bat` genera un archivo en `RESPALDOS`
- [ ] `APAGAR POS.bat` cierra todo
- [ ] Vuelve a abrir: **el producto de prueba sigue ahí**
- [ ] Borra el producto de prueba antes de entregar

> Si el puerto 80 está ocupado en ese PC (Skype, IIS), Apache no arranca. Solución:
> edita `xampp\apache\conf\httpd.conf`, cambia `Listen 80` por `Listen 8080` y
> `ServerName localhost:80` por `:8080`. Luego en los `.bat` cambia la URL a
> `http://localhost:8080/...`

---

## 5. Cómo se lo entregas

Copia la carpeta completa a `C:\POS-LaCasaDelPastel` en el PC del dueño
(en la raíz de `C:`, no en Escritorio ni Documentos: rutas más cortas, menos problemas).

Crea accesos directos en el Escritorio de:
- **INICIAR POS.bat** → renómbralo "**ABRIR POS**", ponle un ícono bonito
- **APAGAR POS.bat** → "**CERRAR POS**"

Y entrégale impreso el `LEEME PRIMERO.txt`.

---

## 6. El día de migrar al servidor

1. En el PC del dueño: doble click en **EXPORTAR PARA SERVIDOR.bat**
2. Se genera `PARA-EL-SERVIDOR\migracion_FECHA.sql` y te dice cuántos productos lleva
3. Ese archivo se importa en el servidor → **todo queda idéntico**
4. No borres nada de su PC hasta confirmar que el servidor funciona

Ya probé este proceso completo: exporté, restauré en una base nueva y verifiqué que
las 8 tablas coinciden exactamente, incluida la columna generada `subTotal`.

---

## Detalles que ya están resueltos

- **Respaldo automático**: se hace solo al iniciar y al apagar. Guarda los últimos 30.
- **Hora de Colombia**: forzada en la conexión, sin importar la config del PC.
- **Modo aplicación**: los `.bat` abren Chrome (o Edge) con `--app`, sin barra de
  direcciones. Se ve como un programa normal.
- **Si no hay Chrome ni Edge**: abre el navegador por defecto. Funciona igual.
