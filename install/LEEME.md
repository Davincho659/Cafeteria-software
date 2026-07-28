# 📁 Qué es esta carpeta

Aquí está **todo lo necesario para montar el sistema en otro computador**.
Esta hoja explica qué es cada archivo y en qué orden se usan.

---

## Primero: ¿por qué hace falta "montar" algo?

El sistema está hecho en **PHP** y guarda los datos en **MySQL**. Eso significa que
un computador no puede simplemente abrir el archivo y ya: necesita un **motor** que
entienda PHP y una **base de datos** que guarde la información.

Ese motor se llama **XAMPP** (trae Apache + PHP + MySQL juntos).

> Piénsalo como un carro: el sistema es la carrocería, XAMPP es el motor.
> Sin motor, la carrocería no se mueve.

### Hay dos formas de tener XAMPP

| | Instalado | **Portable (la que usamos)** |
|---|---|---|
| Cómo se obtiene | Bajas un `.exe` y lo instalas | Bajas un `.zip` y lo descomprimes |
| Pide permisos de administrador | Sí | **No** |
| Modifica el computador | Sí (registro de Windows) | **No, es solo una carpeta** |
| Se puede copiar a otro PC | No | **Sí, se copia y funciona** |

👉 **Vamos a usar la portable.** Por eso la respuesta a "¿tengo que instalar XAMPP
en el PC del dueño?" es **NO**. Solo copias una carpeta.

---

## Los archivos

### 📂 `portable/` ← **ESTO ES LO QUE VAS A USAR**

Los cuatro botones que el dueño va a tener. No son programas complicados: son
archivos de texto con instrucciones para Windows.

| Archivo | Qué hace cuando le dan doble click |
|---|---|
| `INICIAR POS.bat` | Prende la base de datos y el servidor, crea la base si es la primera vez, hace un respaldo y abre el sistema a pantalla completa |
| `APAGAR POS.bat` | Hace un respaldo y apaga todo ordenadamente |
| `RESPALDAR AHORA.bat` | Copia de seguridad manual (guarda las últimas 30) |
| `EXPORTAR PARA SERVIDOR.bat` | Genera el archivo para migrar todo a la nube (Fase 2) |
| `LEEME PRIMERO.txt` | Instructivo **para el dueño**, en lenguaje simple. Imprímelo. |
| `COMO ARMAR EL PAQUETE.md` | Instructivo **para ti**: cómo armar la carpeta paso a paso |

### 📄 `schema.sql` y `seed.sql`

Son la **base de datos vacía**:
- `schema.sql` = las tablas (los "cajones" donde se guardan productos, ventas, etc.)
- `seed.sql` = lo mínimo para arrancar: el usuario `admin` y las unidades de medida

**No los ejecutas a mano.** `INICIAR POS.bat` los usa solo, la primera vez.

### 📂 `otras-opciones/`

Caminos alternativos que **no vas a usar ahora**. Están guardados por si acaso:

- `instalar.bat` + `INSTALACION.md` — la versión con XAMPP instalado (la que
  descartamos porque es más engorrosa)
- `GUIA_PARA_TI.md` — guía de esa misma versión
- `verificar-hosting.php` — para la **Fase 2**: se sube a un hosting y dice si
  ese hosting sirve, antes de pagar

---

## Cómo se usa todo esto, en orden

### 🏠 En tu casa (una sola vez, ~30 min)

1. Descarga XAMPP en **versión ZIP** desde <https://www.apachefriends.org/download.html>
   (la opción ZIP, **no** el instalador `.exe`).
2. Descomprímelo: te queda una carpeta `xampp`.
3. Arma esta estructura en una carpeta nueva:

```
POS-LaCasaDelPastel\
├── xampp\                          ← la que descomprimiste
│   └── htdocs\
│       └── Cafeteria-software\     ← copias TODO el proyecto aquí
├── INICIAR POS.bat                 ← copiados de install\portable\
├── APAGAR POS.bat
├── RESPALDAR AHORA.bat
├── EXPORTAR PARA SERVIDOR.bat
└── LEEME PRIMERO.txt
```

> ⚠️ Los `.bat` van **afuera** de la carpeta `xampp`, no adentro.
> Ellos buscan `xampp\` a su lado; si los metes dentro, no funcionan.

4. Pruébalo **en otro computador** (no en el tuyo, que ya tiene XAMPP instalado
   y puede confundir el resultado). El checklist está en
   `portable/COMO ARMAR EL PAQUETE.md`.

### 🏪 En el negocio (~20 min)

5. Copias la carpeta `POS-LaCasaDelPastel` a `C:\` del PC del dueño.
   (En la raíz de `C:`, no en Escritorio ni Documentos.)
6. Doble click en `INICIAR POS.bat`. Listo, ya funciona.
7. Le creas accesos directos en el escritorio: **ABRIR POS** y **CERRAR POS**.
8. Entras con `admin` / `1234` y **le cambias el PIN de inmediato**.
9. Le entregas impreso el `LEEME PRIMERO.txt`.

### ☁️ El día de pasar a la nube (Fase 2)

10. Doble click en `EXPORTAR PARA SERVIDOR.bat` en el PC del dueño.
11. Ese archivo `.sql` se importa en el servidor → **todo queda idéntico**:
    los mismos productos, precios, inventario y ventas.

---

## Preguntas frecuentes

**¿Y si el dueño ya tiene XAMPP instalado?**
No importa, no estorba. La versión portable usa su propia carpeta.

**¿Se pueden perder los datos?**
El sistema respalda solo cada vez que se abre y se cierra, en la carpeta
`RESPALDOS`. Lo que sí debe hacer el dueño es **copiar esa carpeta a una USB o
a Drive una vez por semana** — si el computador se daña, esa copia es lo único
que salva el trabajo.

**¿El dueño puede dañar algo?**
Lo peor que puede pasar es que borre la carpeta. Por eso los respaldos externos.

**¿Necesita internet?**
No. Todo funciona local, sin conexión.

**¿Se ve como una aplicación o como una página web?**
Como una aplicación: se abre a pantalla completa, sin barra de direcciones.
