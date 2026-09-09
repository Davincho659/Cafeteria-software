# 🖨️ Que la tirilla salga bien (y sin dar dos veces a imprimir)

Son dos cosas distintas y ambas hacen falta:

1. **Que el texto use todo el ancho del papel** → se ajusta en el sistema
2. **Que no aparezca la ventana de impresión de Windows** → se ajusta en Chrome

---

## 1. El espacio en blanco a la derecha

El rollo mide 80 mm, pero **ninguna impresora térmica imprime los 80 mm**:
reserva unos milímetros a cada lado que no puede alcanzar, y ese margen cambia
según el modelo. Por eso el sistema no usa 80 mm fijos.

### Cómo ajustarlo (desde la app, sin tocar código)

**Configuración → Ancho del tiquete (mm)**

Viene en **72**. Si te queda espacio en blanco a la derecha:

1. Sube el número **de a 2 mm**: 74 → guardar → imprimir una factura → mirar
2. Repite hasta que el texto llegue casi al borde
3. Si en algún momento sale **cortado**, bájalo 2 mm y quédate con ese

La mayoría de impresoras de 80 mm quedan bien entre **72 y 76**.

> Hazlo con una factura real, no con la vista previa: lo que manda es cómo sale
> en el papel.

### Si aun subiéndolo sigue saliendo angosto y pegado a la izquierda

Entonces el problema no es el sistema, sino el **tamaño de papel del
controlador**. Si Windows cree que el papel es A4 y en realidad es un rollo de
80 mm, Chrome encoge todo para que quepa en A4 y la tirilla sale pequeña.

**Revisa esto:**

1. Panel de control → **Dispositivos e impresoras**
2. Click derecho en la impresora → **Preferencias de impresión**
3. **Tamaño de papel**: elige el de 80 mm (suele llamarse `80mm x 297mm`,
   `80 x 3276 mm` o `Roll Paper 80mm`). **No dejes A4 ni Carta.**
4. Si hay opción de **márgenes**, ponlos en 0
5. Aceptar y volver a imprimir

---

## 2. Que no pida imprimir dos veces

Hoy pasa esto: se abre la factura, das a *Imprimir*, y **aparece la ventana de
Windows** donde hay que dar a *Imprimir* otra vez. En hora pico son dos toques
de más en cada venta.

Los navegadores no dejan imprimir sin preguntar… **salvo con una opción de
Chrome hecha justo para cajas registradoras**.

### La solución: `--kiosk-printing`

En el acceso directo del escritorio, agrega esa opción:

```
"C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk-printing --kiosk --app=http://localhost/Cafeteria-software/Public/Index.php?pg=login
```

Con eso, al dar a *Imprimir* **la factura sale directo**, sin ventana ninguna.

> ⚠️ **Importante:** imprime siempre en la **impresora predeterminada de
> Windows**, sin preguntar. Antes de activarlo, deja la térmica como
> predeterminada: Dispositivos e impresoras → click derecho en la térmica →
> **Establecer como impresora predeterminada**.

### Cómo comprobar que quedó

1. Cierra Chrome por completo
2. Abre con el acceso directo modificado
3. Cobra una venta de prueba
4. La tirilla debe salir sola, sin ninguna ventana

Si sigue apareciendo la ventana: Chrome estaba abierto al lanzarlo (la opción
solo aplica al iniciar), o el acceso directo no quedó guardado.

---

## Resumen

| Síntoma | Dónde se arregla |
|---|---|
| Espacio en blanco a la derecha | Configuración → Ancho del tiquete (subir de a 2 mm) |
| Sale muy pequeña y pegada a la izquierda | Tamaño de papel del controlador (no A4) |
| Pide imprimir dos veces | `--kiosk-printing` en el acceso directo |
| Texto cortado por los lados | Configuración → Ancho del tiquete (bajar de a 2 mm) |
