# 📱 Instalar el sistema como aplicación en los celulares

El sistema ya está preparado para instalarse como aplicación. **No hay que
publicar nada en Play Store ni App Store**: se instala desde el navegador y
queda con el logo de la cafetería en la pantalla de inicio.

---

## ⚠️ Requisito: tiene que estar en el servidor con HTTPS

La instalación **solo funciona con `https://`**. Es una exigencia del navegador,
no del sistema.

| Dónde | ¿Se puede instalar? |
|---|---|
| `localhost` (el PC del negocio) | Sí, pero solo en ese equipo |
| `http://192.168.1.x` (red local) | **No** — los celulares no la ofrecerán |
| `https://tudominio.com` | **Sí** ← el objetivo |

Por eso este paso va **después** de montar el servidor con dominio y certificado.

---

## Android (Chrome)

1. Abrir `https://tudominio.com` en Chrome
2. Aparece abajo un aviso **"Agregar a pantalla de inicio"** — si no sale:
   menú **⋮** → **Instalar aplicación** (o *Agregar a pantalla principal*)
3. Confirmar

Queda con el logo de la cafetería, se abre a pantalla completa y sin barra de
direcciones.

## iPhone / iPad (Safari)

En iPhone **hay que usar Safari**; desde Chrome no se puede instalar.

1. Abrir `https://tudominio.com` en **Safari**
2. Botón **Compartir** (el cuadrito con la flecha hacia arriba)
3. **Agregar a pantalla de inicio**
4. Confirmar

---

## Qué se instaló y qué no

**Sí:**
- Ícono propio con el logo de la cafetería
- Se abre a pantalla completa, se ve como una aplicación normal
- Arranca más rápido: los estilos y scripts quedan guardados en el equipo
- Se adapta a cualquier tamaño de pantalla

**No:**
- **No funciona sin internet.** A propósito: los productos, precios, ventas e
  inventario se piden siempre al servidor. En una caja, mostrar un precio o un
  stock viejo es peor que no mostrar nada. Sin conexión aparece un aviso claro
  con un botón para reintentar.

---

## Si cambian el logo o el nombre del negocio

El nombre y los colores se toman de **Configuración**, así que se actualizan
solos la próxima vez que se instale.

El **ícono** sí hay que regenerarlo, porque son imágenes de tamaño fijo. Desde
el equipo de desarrollo, con el logo nuevo en `Public/Assets/img/logo.jpg`:

```powershell
powershell -ExecutionPolicy Bypass -File install\portable\generar-iconos.ps1
```

Quien ya la tenga instalada debe desinstalarla y volver a agregarla para ver el
ícono nuevo.

---

## Si no aparece la opción de instalar

| Causa | Solución |
|---|---|
| Está entrando por `http://` | Tiene que ser `https://` |
| En iPhone está usando Chrome | En iPhone solo funciona con Safari |
| Ya está instalada | Buscar el ícono en la pantalla de inicio |
| El navegador está desactualizado | Actualizarlo |
