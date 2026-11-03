# Módulo Convenio FED v3 — IntelHIS

**Convenio FED - Fondo de Estimulo al Desempeno (Red de Salud Perené)**

Versión 3 del módulo: agrega **gestión de indicadores**, **importación del archivo `Resultados.txt`** (exportado del SQL Server local) y una **visualización interactiva** con filtros por establecimiento, año, mes (con opción "todos los meses") e indicador.

## Flujo de trabajo (informático ↔ establecimientos)

1. **Informático**: evalúa los indicadores en su base local **SQL Server** y exporta los resultados a `Resultados.txt` (13 columnas separadas por `;`).
2. **Informático (rol Admin)**: entra a *Convenios → Convenio FED → Importar Resultados.txt* y sube el archivo. El sistema inserta/actualiza los registros y guarda la **fecha y hora de importación**.
3. **Personal de los 12 establecimientos**: entra a *Convenios → Convenio FED* cualquier día y revisa con filtros cómo va el avance de los indicadores de su establecimiento.

## Archivos del módulo (v3)

| Archivo | Rol |
|---------|-----|
| `convenio_fed.php` | Página completa del módulo: visualización interactiva + panel admin (gestión de indicadores + importación) |
| `includes/header.php` | Menú: agrega accesos directos en **Admin** → "FED: Importar Avances" y "FED: Gestion de Indicadores" |
| `Database/update_fed_v3.sql` | SQL de actualización (columna `fecha_importacion`, índices y creación de tablas si faltan) |
| `INSTALL_fed.md` | Este archivo |

## Instalación (3 pasos)

### Paso 1 — Subir los archivos

Suba por FTP/File Manager (o `git push`) los 3 archivos, respetando las rutas:

```
convenio_fed.php            (reemplaza el existente)
includes/header.php         (reemplaza el existente)
Database/update_fed_v3.sql  (nuevo)
```

> Si usa `git`: `git add convenio_fed.php includes/header.php Database/update_fed_v3.sql` → commit → push. El hosting toma los cambios al instante (o tras el deploy que tenga configurado).

### Paso 2 — Aplicar el SQL (recomendado, una sola vez)

1. Entre a phpMyAdmin (InfinityFree) → base **if0_42181393_his** → pestaña **SQL**.
2. Pegue el contenido completo de `Database/update_fed_v3.sql` y ejecute.
3. Si muestra `Duplicate column name` o `Duplicate key name`, ese paso ya estaba aplicado: es seguro continuar.

> **Nota**: aunque no aplique el SQL, el módulo funciona: al importar, el sistema intenta crear automáticamente la columna `fecha_importacion`; si no puede, usa `fecha_registro` (la fecha que viene en el archivo del SQL Server) como fecha de actualización mostrada.

### Paso 3 — Verificar

1. Entre al sitio como **Administrador** → *Convenios → Convenio FED*.
2. Debe ver el panel ámbar **"Gestion de Indicadores / Importar Avances"** (solo visible para admin).
3. En la pestaña **Importar Avances**, suba su `Resultados.txt` y pulse **Importar Ahora**.
4. Debe aparecer: *"Importacion completada: N insertados, M actualizados... Fecha y hora de importacion: ..."*.
5. La tabla **Detalle de Avances** debe mostrar las columnas en este orden:
   `Renipress | Establecimiento | Anio | Mes | Valor Numerador | Valor Denominador | Porcentaje Avance | Observaciones | Indicador | Fecha Importacion`

## Formato del archivo Resultados.txt

13 columnas separadas por `;` (igual que su export actual del SQL Server):

```
id_avance; id_indicador; renipress; establecimiento; anio; trimestre; mes;
valor_numerador; valor_denominador; porcentaje_avance; observaciones;
fecha_registro; usuario
```

Ejemplo real:

```
1;3;319;BAJO MARANKIARI;2026;NULL;01;1.00;1.00;100.00;NULL;2026-11-03 10:57:51.017;NULL
```

Reglas que aplica el importador:

- El `id_avance` del archivo **se ignora** (la web asigna el suyo: evita colisiones de clave).
- `id_indicador` debe estar **registrado en "Gestion de Indicadores"**; si no, la línea se omite con error informado.
- Texto `NULL` o campo vacío → se guarda como NULL.
- `fecha_registro`: se acepta con milisegundos (`2026-11-03 10:57:51.017`); se guarda sin milisegundos.
- Si `porcentaje_avance` viene `NULL`, se recalcula como `numerador / denominador * 100`.
- Codificación **UTF-8** (con o sin BOM) o **ANSI/ISO-8859-1**; saltos de línea Windows o Unix.
- Si la primera línea es una cabecera de columnas, se detecta y se omite.

## Modos de importación

| Modo | Comportamiento | Cuándo usarlo |
|------|----------------|---------------|
| **Actualizar** (recomendado) | Si ya existe la clave (indicador + renipress + año + mes) la **actualiza** con los valores nuevos; si no existe, **inserta** | Uso diario: solo re-envía el archivo y todo se refresca |
| **Reemplazar periodo** | Primero **elimina** los registros de los periodos (indicador + año + mes) que trae el archivo y luego los recarga | Cuando quitó establecimientos o meses en el SQL Server y no quiere dejar datos viejos |

## Gestión de indicadores (Admin)

- **Agregar**: botón "Nuevo Indicador" (código único, nombre, descripción, unidad, meta anual, peso ponderado, periodicidad, responsable, orden).
- **Modificar**: botón "Editar" en cada fila.
- **Desactivar/Activar**: botón de toggle. Desactivar **no borra nada** y conserva el historial de avances; simplemente deja de mostrarse en el reporte (útil cuando los indicadores cambian cada año).
- El **peso ponderado** alimenta el "Avance Global del Convenio FED (ponderado)".

## Visualización (todos los usuarios)

- **Filtros**: Año · Mes (con **"-- Todos los meses --"**) · Establecimiento (los 12 de la red) · Indicador. Se aplican automáticamente al cambiarlos.
- **Aviso de actualización**: "Datos actualizados al: dd/mm/yyyy hh:mm:ss" (fecha/hora de la última importación) para que todos sepan qué tan fresca está la información.
- **Tabla de detalle interactiva**: buscador en vivo, ordenamiento por cualquier columna (clic en el encabezado), contador de registros y **exportación CSV** (abre en Excel).
- **Resumen por indicador**: acumulado numerador/denominador y % de avance (calculado, no promediado), con barras y semáforo (Excelente ≥90, Bueno ≥75, Regular ≥60, Bajo ≥40, Crítico <40).
- **Resumen por establecimiento**: promedio de avance, **los que requieren atención aparecen primero**, con botón "Detalle" que filtra por ese establecimiento.
- **Vista de tarjetas** por indicador con barra de avance.

## Seguridad

- Las acciones de gestión e importación **solo funcionan con rol `admin`** (`verificarAdmin` + verificación CSRF en cada POST). Un usuario común que intente un POST recibe "Acceso denegado" y no se altera la base.
- Toda consulta usa sentencias preparadas (PDO); toda salida se escapó con `htmlspecialchars`.
- Cada importación queda registrada en `LOG_IMPORTACION` (historial reciente visible en la pestaña de importación) y también en el Log de Auditoría del sistema.

## Preguntas frecuentes

**¿Puedo importar varios indicadores en el mismo archivo?**
Sí. El archivo puede mezclar `id_indicador` distintos; cada línea se procesa de forma independiente. El archivo de ejemplo trae solo el indicador 3.

**Importé y no aparece nada en la vista.**
Verifique que el **año del filtro** coincida con el año de los datos (por defecto se muestra el más reciente con datos) y que los `id_indicador` del archivo existan en "Gestion de Indicadores".

**¿Se duplican los registros si importo dos veces el mismo archivo?**
No. El modo "Actualizar" reconoce la misma clave (indicador + renipress + año + mes) y actualiza en lugar de duplicar.

**El archivo trae un indicador con id 99 que no existe.**
Regístrelo primero en "Gestion de Indicadores" (con su código FED-XX) y vuelva a importar; esa línea quedó reportada como error en el mensaje de importación.
