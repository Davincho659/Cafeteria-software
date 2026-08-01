# 📦 Cómo armar el paquete portable (para ti)

El objetivo: entregar **una sola carpeta** que el dueño copia a su computador y usa
con **doble click**, sin instalar nada y sin permisos de administrador.

---

## ⚠️ Lo más importante: tus datos de prueba NO deben viajar

Tus productos y ventas de prueba **no están en el código**. Viven en la carpeta
`xampp\mysql\data\cafeteria_software` de **tu** XAMPP (unos 2 MB).

Por eso la regla es una sola:

> ### Usa un XAMPP **recién descargado**. Nunca copies el tuyo.

Si descargas el ZIP oficial, viene sin la base `cafeteria_software`, y el sistema la
crea **vacía** la primera vez que se abre. Los únicos datos que se instalan son:

| | |
|---|---|
| `schema.sql` | Las tablas vacías (**0 registros**) |
| `seed.sql` | Usuario `admin`, unidades de medida y configuración por defecto |

Nada más. Sin productos, sin ventas, sin inventario.

Si en cambio copiaras tu carpeta `xampp` completa, te llevarías tu base de prueba
dentro — y ahí sí habría que borrar cosas. **Con XAMPP limpio ese problema no existe.**

---

## 1. Descarga XAMPP **portable** (versión ZIP, no el instalador)

Ve a <https://www.apachefriends.org/download.html> y baja la opción **ZIP**
(no el `.exe`). Descomprímela: obtienes una carpeta `xampp` limpia.

> La versión ZIP no toca el registro de Windows ni pide administrador.
> Es lo que permite que todo sea portable.

---

## 2. Arma esta estructura

```
POS-LaCasaDelPastel\
├── xampp\                       ← el ZIP recién descomprimido (limpio)
│   └── htdocs\
│       └── Cafeteria-software\  ← SOLO el código del sistema
├── base-inicial\                ← los archivos que crean la base vacía
│   ├── schema.sql
│   └── seed.sql
├── INICIAR POS.bat
├── APAGAR POS.bat
├── RESPALDAR AHORA.bat
├── EXPORTAR PARA SERVIDOR.bat
├── DIAGNOSTICO.bat
├── ajustar-rutas.ps1            ← sin esto XAMPP no arranca en otra carpeta
└── LEEME PRIMERO.txt
```

**Dos cosas van fuera del proyecto, a propósito:**

- **`base-inicial\`** — son archivos de *instalación*, no del sistema. El proyecto en
  `htdocs` queda solo con el código, que es lo que se sube a un servidor el día de
  la migración.
- **`ajustar-rutas.ps1`** — XAMPP guarda rutas absolutas dentro de sus
  configuraciones (`datadir="C:/xampp/mysql/data"`). Al copiar el paquete a otra
  carpeta esas rutas dejan de existir y MySQL falla con *"Can't change dir"*. Este
  archivo lo detecta y las reescribe solo, la primera vez que se abre.

### Al copiar tu proyecto, limpia esto:
- El contenido de `storage/sessions/` y `storage/logs/`
- La carpeta `.git` (no hace falta en el negocio)

---

## 3. Pruébalo ANTES de llevarlo

**En otro computador**, no en el tuyo (el tuyo ya tiene XAMPP y puede confundir el
resultado).

- [ ] `DIAGNOSTICO.bat` muestra todo en verde
- [ ] `INICIAR POS.bat` abre el sistema a pantalla completa
- [ ] Entra con `admin` / `1234`
- [ ] **Verifica que no haya ni un producto ni una venta** ← lo que te preocupa
- [ ] Crea una categoría y un producto de prueba
- [ ] `RESPALDAR AHORA.bat` genera un archivo en `RESPALDOS`
- [ ] `APAGAR POS.bat` cierra todo
- [ ] Vuelve a abrir: el producto de prueba sigue ahí
- [ ] Bórralo antes de entregar

---

## 4. Cómo se lo entregas

Copia la carpeta completa a `C:\POS-LaCasaDelPastel` (en la raíz de `C:`, no en
Escritorio ni Documentos: rutas más cortas, menos problemas).

Crea accesos directos en el Escritorio:
- **INICIAR POS.bat** → renómbralo "**ABRIR POS**", ponle un ícono
- **APAGAR POS.bat** → "**CERRAR POS**"

Y entrégale impreso el `LEEME PRIMERO.txt`.

---

## 5. El día de migrar al servidor

1. En el PC del dueño: doble click en **EXPORTAR PARA SERVIDOR.bat**
2. Se genera `PARA-EL-SERVIDOR\migracion_FECHA.sql` y te dice cuántos productos lleva
3. Ese archivo se importa en el servidor → **todo queda idéntico**
4. No borres nada de su PC hasta confirmar que el servidor funciona

Ya probé este proceso: exporté, restauré en una base nueva y verifiqué que las
8 tablas coinciden exactamente, incluida la columna generada `subTotal`.

---

## Si algo falla

Corre **`DIAGNOSTICO.bat`**: revisa archivos de XAMPP, del proyecto, la base de
datos, procesos y puertos, y dice exactamente qué falta.

| Problema | Causa |
|---|---|
| `Can't change dir to mysql/data` | Falta `ajustar-rutas.ps1` en la raíz |
| `Table 'usuarios' doesn't exist` | La base quedó a medias; al reabrir se completa sola |
| `path is invalid` en Apache | Rutas mal ajustadas: borra los `.original` y reabre |
| Abre pero en blanco | Puerto distinto; el script lo detecta solo, revisa el mensaje |
