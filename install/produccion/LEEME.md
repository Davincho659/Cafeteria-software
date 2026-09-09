# 📚 Todo lo de producción, en un solo lugar

Antes había guías repartidas por varias carpetas. Estas cuatro reemplazan a
todas: sigue el orden.

| # | Documento | Cuándo |
|---|---|---|
| **00** | [Decidir cómo montarlo](00-DECIDIR-COMO-MONTARLO.md) | **Empieza aquí.** Nube o local, y qué pasa si se va el internet |
| **01** | [Montar en el local](01-MONTAR-EN-EL-LOCAL.md) | Si elegiste local (recomendado) |
| **02** | [Montar en un VPS](02-MONTAR-EN-VPS.md) | Si elegiste nube |
| **03** | [Impresión de la tirilla](../portable/IMPRESION-TIRILLA.md) | Cuando la factura no salga bien |

## Consulta rápida

| Necesitas… | Dónde |
|---|---|
| Decidir nube o local | 00 |
| Traer los productos del PC del dueño | 01, Parte 1 |
| Configurar la impresora | 01, Parte 3 |
| Que arranque solo al prender | 01, Parte 4.2 |
| Conectar los celulares | 01, Parte 4.3 |
| Configurar respaldos | 01, Parte 5 |
| Instalar en un servidor de internet | 02 |
| Que la tirilla use todo el papel | Configuración → Ancho del tiquete |
| Que no pida imprimir dos veces | `--kiosk-printing` en el acceso directo |

## Herramientas del paquete

Todas en `install/portable/`:

| Archivo | Para qué |
|---|---|
| `INICIAR POS.bat` | Enciende el sistema |
| `APAGAR POS.bat` | Lo cierra ordenadamente (respalda antes) |
| `RESPALDAR AHORA.bat` | Copia de seguridad manual |
| `EXPORTAR PARA SERVIDOR.bat` | Empaqueta datos + fotos para migrar |
| `ACTUALIZAR SISTEMA.bat` | Actualiza el código sin tocar los datos |
| `DIAGNOSTICO.bat` | Dice qué falta cuando algo no arranca |
| `generar-iconos.ps1` | Rehace los íconos si cambian el logo |

## Lo que no se debe olvidar

1. **Respaldar antes de tocar nada**, siempre.
2. **Probar la restauración** al menos una vez. Un respaldo sin probar no cuenta.
3. **Copiar las fotos junto con los datos**: la base guarda solo el nombre del
   archivo, no la imagen.
4. **No borrar el PC del dueño** hasta que lo nuevo lleve dos semanas funcionando.
