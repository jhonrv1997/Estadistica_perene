# Módulo Convenio FED v3.1 — IntelHIS

**Convenio FED - Fondo de Estimulo al Desempeno (Red de Salud Perené)**

Versión 3.1 del módulo: agrega **gestión de indicadores**, **importación del archivo `Resultados.txt`** (exportado del SQL Server local) y una **visualización interactiva** con filtros por establecimiento, año, mes (con opción "todos los meses") e indicador. **La v3.1 corrige el error 500 al entrar a la página** (ver sección al final).

## Novedades de la v3.1 (2026-11-04)

- **CORREGIDO el "500 Internal Server Error"** al entrar a `convenio_fed.php`. Causa: con `PDO::ATTR_EMULATE_PREPARES => false` (configuración de `config.php`), MySQL/MariaDB **no admite placeholders `?` dentro de sentencias SHOW** al prepararlas de forma nativa (error 1064). La v3 usaba `prepare("SHOW TABLES LIKE ?")` y caía nada más entrar. Ahora se consulta `information_schema` (100 % compatible) con respaldo `SHOW` + literal seguro.
- **Red de seguridad global**: si cualquier otra cosa fallara en el futuro, la página muestra un panel legible con el motivo técnico en lugar de un 500 en blanco.
- **Auto-reparación del esquema**: se detectó que en el volcado `Database/if0_42181393_his.sql` los ALTER de la tabla de avances apuntan al nombre en singular (`CONVENIO_FED_AVANCE`), de modo que en el servidor real la tabla quedó **sin PRIMARY KEY ni AUTO_INCREMENT**. Con la v3.1, la primera importación repara el esquema sola (y si el hosting no permite `ALTER`, asigna los id manualmente). Opcionalmente ejecute el SQL corregido.
- **`diagnostico_fed.php`**: página de diagnóstico (solo Administrador) que revisa PHP, extensiones, conexión, tablas, columnas y AUTO_INCREMENT, y confirma el estado del servidor.

## Flujo de trabajo (informático ↔ establecimientos)

1. **Informático**: evalúa los indicadores en su base local **SQL Server** y exporta los resultados a `Resultados.txt` (13 columnas separadas por `;`).
2. **Informático (rol Admin)**: entra a *Convenios → Convenio FED → Importar Resultados.txt* y sube el archivo. El sistema inserta/actualiza los registros y guarda la **fecha y hora de importación**.
3. **Personal de los 12 establecimientos**: entra a *Convenios → Convenio FED* cualquier día y revisa con filtros cómo va el avance de los indicadores de su establecimiento.

## Archivos del módulo (v3.1)

| Archivo | Rol |
|---------|-----|
| `convenio_fed.php` | Página completa del módulo: visualización interactiva + panel admin (gestión de indicadores + importación). **v3.1: corrige el error 500** |
| `includes/header.php` | Menú: agrega accesos directos en **Admin** → "FED: Importar Avances" y "FED: Gestion de Indicadores" |
| `Database/update_fed_v3.sql` | SQL de actualización (columna `fecha_importacion`, índices, PK/AUTO_INCREMENT y creación de tablas si faltan) |
| `diagnostico_fed.php` | **Nuevo (v3.1)**: diagnóstico del servidor en un clic (solo Administrador) |
| `INSTALL_fed.md` | Este archivo |

## Instalación (3 pasos)

### Paso 1 — Subir los archivos

Suba por FTP/File Manager (o `git push`) los 4 archivos, respetando las rutas:

```
convenio_fed.php            (reemplaza el existente — versión v3.1)
includes/header.php         (reemplaza el existente)
Database/update_fed_v3.sql  (nuevo)
diagnostico_fed.php         (nuevo, opcional pero recomendado)
```

> **IMPORTANTE (actualización desde la v3):** si ya había subido la v3 y le apareció "500 Internal Server Error" al entrar a Convenio FED, basta con reemplazar `convenio_fed.php` por esta versión v3.1. No hace falta tocar nada más: la causa estaba dentro del archivo (uso de placeholders en sentencias SHOW incompatible con la configuración PDO del sitio).

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

**¿Me sale "500 Internal Server Error" al entrar a convenio_fed.php?**

Ese fue un defecto de la versión v3, ya corregido en la v3.1: con la configuración `PDO::ATTR_EMULATE_PREPARES => false` de `config.php`, MySQL/MariaDB rechaza los placeholders `?` en sentencias `SHOW` (error 1064), y la página fallaba nada más entrar. **Solución: suba el `convenio_fed.php` de esta versión (v3.1).** Si después de eso volviera a ver un error, la página ahora mostrará un **panel rojo con el motivo técnico exacto** (en vez del 500 en blanco); además puede subir `diagnostico_fed.php` a la raíz y abrirlo como Administrador para un reporte completo del servidor.

**¿Puedo importar varios indicadores en el mismo archivo?**
Sí. El archivo puede mezclar `id_indicador` distintos; cada línea se procesa de forma independiente. El archivo de ejemplo trae solo el indicador 3.

**Importé y no aparece nada en la vista.**
Verifique que el **año del filtro** coincida con el año de los datos (por defecto se muestra el más reciente con datos) y que los `id_indicador` del archivo existan en "Gestion de Indicadores".

**¿Se duplican los registros si importo dos veces el mismo archivo?**
No. El modo "Actualizar" reconoce la misma clave (indicador + renipress + año + mes) y actualiza en lugar de duplicar.

**El archivo trae un indicador con id 99 que no existe.**
Regístrelo primero en "Gestion de Indicadores" (con su código FED-XX) y vuelva a importar; esa línea quedó reportada como error en el mensaje de importación.
