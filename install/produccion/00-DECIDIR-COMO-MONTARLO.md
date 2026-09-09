# 🧭 Lo primero: ¿en la nube o en el local? (léelo antes que nada)

Esta decisión cambia todo lo demás. Tómala antes de pagar nada.

---

## Tu pregunta: ¿qué pasa si se va el internet?

Depende de dónde esté el sistema. Hay dos formas de montarlo:

### Opción A — Todo en un servidor de internet (VPS)

```
   Internet
      │
   [ VPS ]  ← aquí vive el sistema y la base de datos
      │
      ├── Pantalla de la caja  (por internet)
      ├── Celulares meseros    (por internet)
      └── El dueño desde casa  (por internet)
```

| | |
|---|---|
| ✅ | Se entra desde cualquier lugar del mundo |
| ✅ | Los datos están fuera del negocio; si se roban el PC no se pierde nada |
| ❌ | **Si se cae el internet, la caja deja de cobrar** |
| ❌ | Si se cae el internet del proveedor, tampoco |
| 💵 | ~$16.000–25.000 COP/mes |

### Opción B — El sistema vive en el PC de la caja ⭐

```
   [ PC de la caja ]  ← aquí vive el sistema y la base de datos
      │  (WiFi del local, NO necesita internet)
      ├── Pantalla de la caja   (es el mismo equipo)
      └── Celulares meseros     (por el WiFi del local)

   Internet: solo para respaldos y para que el dueño mire desde afuera
```

| | |
|---|---|
| ✅ | **Sin internet sigue funcionando igual**: cobra, imprime, toma pedidos |
| ✅ | Más rápido: todo viaja por la red del local |
| ✅ | Sin pago mensual |
| ❌ | Si el PC se daña, hay que restaurar el respaldo en otro equipo |
| ❌ | Para ver las ventas desde fuera hace falta un paso extra |
| 💵 | $0/mes |

---

## 🎯 Recomendación: **Opción B**

Para una cafetería, la razón es simple:

> **Una caja registradora no puede depender del internet.**
> Si se va la señal a las 12 del día, con la Opción A no puedes cobrar. Con la
> Opción B ni te enteras: el WiFi del local sigue funcionando aunque el
> internet no.

El WiFi del router funciona **aunque no haya internet**. Los celulares de los
meseros se conectan a ese WiFi, no a internet, así que todo sigue igual.

Y el equipo que compraron (N5095, 8 GB de RAM) es de sobra para ser servidor de
un negocio de este tamaño: el sistema pesa poco y son pocos usuarios a la vez.

### Lo que se pierde y cómo se resuelve

| Preocupación | Solución |
|---|---|
| "¿Y si se daña el PC?" | Respaldo automático diario a Google Drive. Se restaura en otro equipo en 30 minutos. |
| "¿Y si el dueño quiere ver las ventas desde su casa?" | Se agrega después con un túnel gratuito (Cloudflare Tunnel), sin mover el sistema |
| "¿Y si abren otro local?" | Ahí sí conviene el VPS. Se migra cuando llegue el momento. |

---

## Entonces, ¿el VPS no sirve?

Sirve, pero **más adelante**. El orden sensato es:

1. **Ahora**: Opción B. Que el negocio empiece a operar, estable y sin costo.
2. **Cuando funcione bien** (2–3 meses): si quieren acceso desde afuera, se
   agrega el túnel gratis.
3. **Si abren otro local**: ahí sí VPS, porque hay que compartir datos entre
   sedes.

Migrar de la Opción B al VPS es el mismo trabajo hoy que dentro de seis meses.
No pierdes nada por esperar, y ganas estabilidad desde el primer día.

---

## Si aun así prefieres el VPS

Es una decisión válida si el internet del local es muy bueno y quieren acceso
remoto desde ya. En ese caso:

- **Contrata internet de respaldo** (un plan de datos con router 4G). Sin eso,
  una caída de la señal para el negocio.
- Sigue la guía `02-MONTAR-EN-VPS.md`.

> El sistema ya está preparado para ambas: no hay que cambiar el código.
