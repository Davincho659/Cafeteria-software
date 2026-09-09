# 🗓️ Qué hacer el día que el dueño termine de cargar

Guía para ti. El dueño está cargando productos con una versión anterior del
sistema; aquí está el orden exacto para ponerlo al día y migrarlo sin perder nada.

---

## Lo primero: no hay urgencia

Los arreglos que se hicieron después de entregarle el sistema **se pueden aplicar
al final**. Ninguno exige actuar ahora:

| Arreglo | ¿Corre prisa? | Por qué |
|---|---|---|
| Permisos de las fotos | **No** | La reparación es retroactiva: arregla las fotos ya guardadas. Probado con 17, incluidas 4 que llevaban días dañadas. |
| Teclado del PIN | No | Es para la pantalla táctil, que todavía no está |
| Zoom y barra superior | No | Afecta cómo se ve, no lo que se guarda |
| Abrir caja desde el menú | No | Él no está cobrando aún |

**Mientras tanto, lo único que importa es que él haga respaldos.** Recuérdale cada
tanto copiar la carpeta `RESPALDOS` a una USB o a Drive.

> ⚠️ Las fotos que suba mientras tanto pueden quedar con permisos que impiden
> copiarlas. **No se pierden ni se ven mal**: el sistema las muestra bien. Se
> arreglan todas de una vez en el paso 3.

---

## El día que te diga "ya terminé"

### Paso 1 · Respaldo antes de tocar nada  ⏱️ 2 min

En su PC: doble click en **`RESPALDAR AHORA.bat`**.

Copia la carpeta `RESPALDOS` completa a tu USB. Es tu red de seguridad: si algo
sale mal en los pasos siguientes, con eso se recupera todo.

### Paso 2 · Actualizar el sistema  ⏱️ 5 min

Llevas en la USB una carpeta `ACTUALIZACION` con el proyecto al día dentro.
La dejas al lado de los `.bat`:

```
POS-LaCasaDelPastel\
├── xampp\
├── ACTUALIZACION\
│   └── Cafeteria-software\     ← la versión nueva que traes
├── ACTUALIZAR SISTEMA.bat
└── ...
```

1. Cierra el sistema con **`APAGAR POS.bat`**
2. Doble click en **`ACTUALIZAR SISTEMA.bat`** y escribe `ACTUALIZAR`

**Qué se conserva** (está comprobado, no es promesa):
- La base de datos completa
- **Las fotos que subió el dueño** — el script excluye a propósito las carpetas
  `products` y `categories`, para que tus fotos de prueba no pisen las suyas
- Los respaldos

Además guarda el código anterior en `VERSION-ANTERIOR`, por si hay que volver atrás.

### Paso 3 · Dejar las fotos listas  ⏱️ 1 min

1. Abre con **`INICIAR POS.bat`**
2. Entra como administrador
3. **Productos → botón "Revisar fotos"**

Esto arregla los permisos de todas las fotos, incluidas las que él subió durante
el mes. Te dirá cuántas revisó y, si alguna no se pudiera recuperar, te da el
nombre para volver a subirla.

**No te saltes este paso**: sin él, algunos productos llegarían sin foto al servidor.

### Paso 4 · Revisar que esté todo  ⏱️ 5 min

Con él al lado, comprueben juntos:

- [ ] Están todos los productos que cargó
- [ ] Cada producto muestra su foto
- [ ] Las categorías están completas
- [ ] El inventario cuadra con lo que él registró
- [ ] Los precios de venta y de compra son los correctos

Si algo falta, es el momento de corregirlo — antes de migrar.

### Paso 5 · Exportar para el servidor  ⏱️ 3 min

Doble click en **`EXPORTAR PARA SERVIDOR.bat`**.

Genera una carpeta con **los datos y las fotos juntos**, y te dice cuántos
productos y cuántas fotos lleva. **Compara esos números con lo que ves en el
sistema**: si no cuadran, algo falta.

Copia esa carpeta a tu USB.

### Paso 6 · Montar el servidor  ⏱️ una tarde

1. Contratas el hosting (~$16.000 COP/mes con dominio y HTTPS)
2. Subes el código
3. Importas `datos.sql` por phpMyAdmin
4. Copias la carpeta `img\` dentro de `Public/Assets/img/` del servidor

> **Los dos últimos pasos van juntos.** La base guarda solo el nombre de la foto
> (`1.jpeg`); si no subes también los archivos, los productos salen sin imagen.

5. Entras y verificas lo mismo del paso 4, ahora en el servidor

### Paso 7 · Cambiar la pantalla al servidor  ⏱️ 2 min

En el acceso directo del escritorio, cambias `localhost` por tu dominio:

```
chrome.exe --kiosk --app=https://tudominio.com/Public/Index.php?pg=login
```

Nada más cambia: se ve igual.

### Paso 8 · Dejar su PC como respaldo  ⏱️ —

**No borres nada de su computador** durante al menos dos semanas. Si algo falla
en la nube, ahí sigue todo funcionando.

---

## Si algo sale mal

| Problema | Qué hacer |
|---|---|
| La actualización rompió algo | Copia `VERSION-ANTERIOR` de vuelta a `xampp\htdocs\Cafeteria-software` |
| Faltan productos tras migrar | Vuelve a importar el `.sql`; el original sigue en su PC |
| Productos sin foto en el servidor | Faltó copiar la carpeta `img\`, o no se corrió "Revisar fotos" |
| Todo se dañó | Restaura el respaldo del paso 1 |

---

## Resumen del día

```
1. Respaldar              2 min
2. Actualizar sistema     5 min
3. Revisar fotos          1 min
4. Verificar con él       5 min
5. Exportar               3 min
   ─────────────────────────────
   En su local:          ~20 min

6. Montar servidor        una tarde
7. Cambiar la pantalla    2 min
8. Dejar su PC de respaldo
```
