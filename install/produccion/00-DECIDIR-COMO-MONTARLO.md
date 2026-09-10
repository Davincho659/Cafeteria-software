# 🧭 Cómo queda montado el sistema

---

## La arquitectura: servidor en internet + la caja que aguanta sin él

```
                    [ VPS con dominio y HTTPS ]
                    Base de datos y sistema
                              │
                    ┌─────────┴─────────┐
                    │     internet      │
        ┌───────────┼───────────┬───────┴──────────┐
        │           │           │                  │
   Pantalla     Celular     Celular          El dueño
   de la caja   mesero 1    mesero 2         desde su casa
   (Windows)     (PWA)       (PWA)
```

**Todos apuntan al mismo servidor**, así que lo que hace uno lo ven los demás:
si un mesero toma un pedido en la mesa 4, aparece en la caja sin que nadie
recargue nada.

### ¿Y si se cae el internet?

**La caja sigue cobrando.** Guarda las ventas en el propio equipo y, cuando
vuelve la señal, las envía al servidor sola. El cajero no hace nada distinto.

| Situación | Qué pasa |
|---|---|
| Con internet | Todo en vivo: caja y celulares se ven entre sí al instante |
| Sin internet | La caja cobra e imprime igual, guardando las ventas localmente |
| Vuelve el internet | Las ventas guardadas suben solas al servidor |

> Los celulares sí necesitan conexión para tomar pedidos: son el pedido que
> entra, no el cobro. Lo que no puede detenerse nunca es la caja, y esa es la
> que trabaja sin conexión.

---

## Estado real: qué falta para llegar ahí

Esto es lo que hay hoy, verificado en el código:

| Pieza | Estado |
|---|---|
| Sistema completo (ventas, mesas, inventario, reportes) | ✅ Listo |
| Aplicación instalable en celulares (PWA) | ✅ Listo |
| Preparado para servidor Linux | ✅ Listo |
| **Tiempo real (el pedido del celular aparece en la caja)** | ✅ **Listo** — se actualiza sola en ~4 s |
| **Trabajar sin internet y sincronizar después** | ✅ **Listo** — la caja cobra y se registra sola al volver |

**Ya está todo el plan cubierto.** Lo siguiente es montar el servidor y
conectar los equipos.

---

## Orden de trabajo

| # | Qué | Por qué en ese orden |
|---|---|---|
| 1 | ~~Tiempo real~~ | ✅ Hecho: la caja ve los pedidos del celular en unos 4 s |
| 2 | ~~Modo sin conexión~~ | ✅ Hecho: la caja cobra sin internet y sincroniza sola |
| 3 | Montar el VPS con dominio y HTTPS | Ver `02-MONTAR-EN-VPS.md` |
| 4 | Migrar los datos del dueño | Datos **y** fotos |
| 5 | Configurar la pantalla y los celulares | Ver `03-CONFIGURAR-LA-CAJA.md` |

> Los puntos 1 y 2 ya están. Queda montar el servidor y conectar los equipos.

---

## Sobre Windows y Linux (que conviven)

Son dos equipos distintos y cada uno con lo suyo:

| Equipo | Sistema | Qué corre |
|---|---|---|
| **VPS** | Linux (Ubuntu) | El servidor: la base de datos y el sistema |
| **PC de la caja** | **Windows 11** | Solo Chrome, mostrando el sistema |
| **Celulares** | Android / iPhone | Solo el navegador con la app instalada |

Por eso hubo que ajustar cosas de Linux: **el servidor** es Linux. La caja sigue
siendo Windows 11 y ahí no cambia nada — se configura como se explica en la
guía de la caja.

---

## ¿Y montar todo en el PC de la caja, sin VPS?

Se puede, pero **pierdes lo que pediste desde el principio**:

- Que se vea desde cualquier dispositivo y desde cualquier parte
- Que el dueño revise las ventas sin estar en el negocio
- Que los datos estén a salvo si le pasa algo al equipo

Con el modo sin conexión bien hecho, el VPS deja de tener la desventaja que
preocupaba: **la caja no se detiene aunque se caiga el internet**. Por eso el
plan sigue siendo el VPS.
